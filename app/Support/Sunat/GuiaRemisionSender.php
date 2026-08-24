<?php

namespace App\Support\Sunat;

use App\Models\GuiaRemision;

/**
 * Envío de la guía de remisión al proveedor fiscal del negocio.
 *
 * Va aparte de los servicios de factura porque la guía no comparte casi nada
 * con un comprobante de venta: no tiene importes ni IGV, y sí peso, bultos,
 * dos direcciones, un vehículo y un conductor. Compartir clase obligaría a
 * llenar de condicionales un payload que ya es largo.
 *
 * Los dos proveedores reciben los mismos datos con nombres distintos, así que
 * cada uno arma su propio cuerpo a partir de la misma guía.
 */
final class GuiaRemisionSender
{
    private const APISPERU_URL        = 'https://facturacion.apisperu.com/api/v1/despatch/send';
    private const APISPERU_STATUS_URL = 'https://facturacion.apisperu.com/api/v1/despatch/status';

    public function enviar(GuiaRemision $guia): array
    {
        $project  = $guia->project;
        $provider = (string) $project->setting('billing_provider', '');

        return match ($provider) {
            'apisperu' => $this->porApisPeru($guia),
            'nubefact' => $this->porNubefact($guia),
            default    => ['ok' => false, 'message' =>
                'Elige el proveedor de facturación electrónica (Nubefact o APIsPERU) en Ajustes → Facturación.'],
        };
    }

    private function porApisPeru(GuiaRemision $guia): array
    {
        $token = $guia->project->setting('apisperu_token');

        if (! $token) {
            return $this->falla($guia, 'Configura el Token de APIsPERU en Ajustes → Facturación.');
        }

        [$http, $body, $err] = $this->post(
            self::APISPERU_URL,
            ['Authorization: Bearer '.$token],
            $this->payloadApisPeru($guia)
        );

        $resultado = $this->interpretar($guia, $http, $body, $err, function (array $resp) {
            // APIsPERU devuelve ticket o CDR cuando la guia entra de verdad.
            return isset($resp['ticket']) || isset($resp['cdrResponse']) || ($resp['success'] ?? null) === true;
        });

        /* La GRE es asincrona: el ticket solo dice que el XML entro en cola.
           El veredicto —aceptada o rechazada, y por que— esta en el estado.
           Quedarse con el ticket como si fuera la aceptacion es como dar la
           baja por hecha sin leer la respuesta: la prueba real devolvio ticket
           y el veredicto final fue un rechazo por la placa. */
        if (($resultado['ok'] ?? false) && ! empty($resultado['ticket'])) {
            sleep(2);

            return $this->consultarVeredicto($guia, $token, $resultado['ticket']);
        }

        return $resultado;
    }

    /** El veredicto final de una guia enviada: /despatch/status con el ticket. */
    public function consultarVeredicto(GuiaRemision $guia, string $token, string $ticket): array
    {
        $url = self::APISPERU_STATUS_URL
            .'?ticket='.urlencode($ticket)
            .'&ruc='.urlencode((string) $guia->emisor_ruc);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Authorization: Bearer '.$token],
            CURLOPT_TIMEOUT        => 45,
        ]);
        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        \Log::debug('GUIA STATUS', ['guia' => $guia->numero, 'http' => $http, 'body' => $body]);

        $d = json_decode((string) $body, true) ?: [];
        $sunat = is_array($d['sunatResponse'] ?? null) ? $d['sunatResponse'] : $d;
        $cdr   = $sunat['cdrResponse'] ?? null;

        // Aceptada de verdad: CDR con codigo 0 (o exito explicito con CDR).
        if (is_array($cdr) && (string) ($cdr['code'] ?? '') === '0') {
            $guia->update([
                'sunat_status' => 'accepted',
                'sunat_cdr'    => json_encode($cdr, JSON_UNESCAPED_UNICODE),
                'sunat_error'  => null,
            ]);

            return ['ok' => true, 'message' => 'Guía '.$guia->numero.' aceptada por SUNAT: '.($cdr['description'] ?? '')];
        }

        // Rechazo con motivo: es la respuesta que hay que ensenar tal cual.
        if (($sunat['success'] ?? null) === false || isset($sunat['error'])) {
            $motivo = $sunat['error']['message'] ?? ($cdr['description'] ?? null);
            $motivo = is_string($motivo) ? $motivo : json_encode($sunat['error'] ?? $cdr, JSON_UNESCAPED_UNICODE);

            return $this->falla($guia, 'SUNAT rechazó la guía: '.$motivo);
        }

        // Aun en cola: se queda en pending con su ticket, sin mentir.
        $guia->update(['sunat_status' => 'pending', 'sunat_ticket' => $ticket]);

        return ['ok' => true, 'message' => 'Guía '.$guia->numero.' en cola de SUNAT (ticket '.$ticket.'). Consulta el estado en unos segundos.'];
    }

    /** Las placas van sin guiones ni espacios: "ABC-123" es el error 2567. */
    private static function placa(?string $placa): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $placa));
    }

    /** El cuerpo que espera APIsPERU, aparte para poder comprobarlo sin enviarlo. */
    public function payloadApisPeru(GuiaRemision $guia): array
    {
        [$serie, $correlativo] = array_pad(explode('-', (string) $guia->numero, 2), 2, '');

        $envio = [
            'codTraslado'  => $guia->motivo_codigo,
            'desTraslado'  => $guia->motivo_descripcion ?: $guia->motivoLegible(),
            'modTraslado'  => $guia->modalidad,
            'fecTraslado'  => $guia->fecha_traslado->format('Y-m-d\TH:i:sP'),
            'pesoTotal'    => (float) $guia->peso_total,
            'undPesoTotal' => $guia->peso_unidad,
            'llegada'      => [
                'ubigueo'   => $guia->llegada_ubigeo ?: '150101',
                'direccion' => $guia->llegada_direccion,
            ],
            'partida' => [
                'ubigueo'   => $guia->partida_ubigeo ?: '150101',
                'direccion' => $guia->partida_direccion,
            ],
        ];

        if ($guia->bultos !== null) {
            $envio['numBultos'] = (int) $guia->bultos;
        }

        /* En transporte publico responde el transportista; en privado, el
           vehiculo y el conductor del propio negocio. Declarar los dos —o
           ninguno— es motivo de rechazo. */
        if ($guia->esPublico()) {
            $envio['transportista'] = [
                'tipoDoc'     => '6',
                'numDoc'      => $guia->transportista_ruc,
                'rznSocial'   => $guia->transportista_razon_social,
                'nroMtc'      => $guia->transportista_mtc ?: '',
            ];
        } else {
            $envio['vehiculo'] = ['placa' => self::placa($guia->vehiculo_placa)];
            // El schema del proveedor pide `choferes` como lista, con el
            // principal marcado; `chofer` a secas provoca un 500 sin detalle.
            $envio['choferes'] = [[
                'tipo'      => 'Principal',
                'tipoDoc'   => $guia->conductor_doc_tipo ?: '1',
                'nroDoc'    => $guia->conductor_doc_numero,
                'nombres'   => $guia->conductor_nombres,
                'apellidos' => $guia->conductor_apellidos,
                'licencia'  => $guia->conductor_licencia,
            ]];
        }

        $payload = [
            'version'     => '2022',
            'tipoDoc'     => Catalogos::codigoComprobante('guia_remision'),
            'serie'       => $serie,
            'correlativo' => (string) (int) ltrim($correlativo, '0'),
            'fechaEmision' => $guia->created_at?->format('Y-m-d\TH:i:sP') ?? now()->format('Y-m-d\TH:i:sP'),
            'company' => [
                'ruc'         => $guia->emisor_ruc,
                'razonSocial' => $guia->emisor_razon_social,
            ],
            'destinatario' => [
                'tipoDoc'   => $guia->destinatario_doc_tipo ?: '1',
                'numDoc'    => $guia->destinatario_doc_numero ?: '00000000',
                'rznSocial' => $guia->destinatario_nombre,
            ],
            'envio'   => $envio,
            'details' => $guia->items->map(fn ($i) => [
                'codigo'      => $i->codigo ?: (string) ($i->product_id ?? ''),
                'descripcion' => $i->description,
                'unidad'      => $i->unit,
                'cantidad'    => (float) $i->quantity,
            ])->values()->all(),
        ];

        if ($guia->invoice_id && $guia->invoice) {
            // El comprobante que respalda el traslado, si lo hay.
            $payload['addDocs'] = [[
                'tipo'  => $guia->invoice->codigoSunat(),
                'nro'   => $guia->invoice->numero,
            ]];
        }

        return $payload;
    }

    private function porNubefact(GuiaRemision $guia): array
    {
        $url   = $guia->project->setting('nubefact_url');
        $token = $guia->project->setting('nubefact_token');

        if (! $url || ! $token) {
            return $this->falla($guia, 'Configura la URL y Token de Nubefact en Ajustes → Facturación.');
        }

        [$http, $body, $err] = $this->post(
            rtrim($url, '/'),
            ['Authorization: '.$token],
            $this->payloadNubefact($guia)
        );

        return $this->interpretar($guia, $http, $body, $err, function (array $resp) {
            return ! isset($resp['errors']) && ($resp['aceptada_por_sunat'] ?? true) !== false;
        });
    }

    /** El cuerpo que espera Nubefact, con sus propios nombres de campo. */
    public function payloadNubefact(GuiaRemision $guia): array
    {
        [$serie, $correlativo] = array_pad(explode('-', (string) $guia->numero, 2), 2, '');

        $payload = [
            'operacion'                   => 'generar_guia',
            'tipo_de_comprobante'         => 7, // guía de remisión remitente
            'serie'                       => $serie,
            'numero'                      => (int) ltrim($correlativo, '0'),
            'cliente_tipo_de_documento'   => $guia->destinatario_doc_tipo ?: '1',
            'cliente_numero_de_documento' => $guia->destinatario_doc_numero ?: '',
            'cliente_denominacion'        => $guia->destinatario_nombre,
            'cliente_direccion'           => $guia->llegada_direccion,
            'fecha_de_emision'            => ($guia->created_at ?? now())->format('d-m-Y'),
            'observaciones'               => $guia->observaciones ?? '',
            'motivo_de_traslado'          => $guia->motivo_codigo,
            'peso_bruto_total'            => (float) $guia->peso_total,
            'peso_bruto_unidad_de_medida' => $guia->peso_unidad,
            'numero_de_bultos'            => $guia->bultos ?? '',
            'tipo_de_transporte'          => $guia->esPublico() ? '01' : '02',
            'fecha_de_inicio_de_traslado' => $guia->fecha_traslado->format('d-m-Y'),
            'punto_de_partida_ubigeo'     => $guia->partida_ubigeo ?: '',
            'punto_de_partida_direccion'  => $guia->partida_direccion,
            'punto_de_llegada_ubigeo'     => $guia->llegada_ubigeo ?: '',
            'punto_de_llegada_direccion'  => $guia->llegada_direccion,
            'enviar_automaticamente_a_la_sunat' => true,
            'items' => $guia->items->map(fn ($i) => [
                'unidad_de_medida' => $i->unit,
                'codigo'           => $i->codigo ?: '',
                'descripcion'      => $i->description,
                'cantidad'         => (float) $i->quantity,
            ])->values()->all(),
        ];

        if ($guia->esPublico()) {
            $payload['transportista_documento_tipo']   = '6';
            $payload['transportista_documento_numero'] = $guia->transportista_ruc;
            $payload['transportista_denominacion']     = $guia->transportista_razon_social;
            $payload['transportista_placa_numero']     = '';
        } else {
            $payload['transportista_placa_numero']  = self::placa($guia->vehiculo_placa);
            $payload['conductor_documento_tipo']    = $guia->conductor_doc_tipo ?: '1';
            $payload['conductor_documento_numero']  = $guia->conductor_doc_numero;
            $payload['conductor_nombre']            = $guia->conductor_nombres;
            $payload['conductor_apellidos']         = $guia->conductor_apellidos;
            $payload['conductor_numero_licencia']   = $guia->conductor_licencia;
        }

        return $payload;
    }

    /**
     * Guarda el resultado en la guía y devuelve el mismo veredicto a quien llamó.
     *
     * Sin esto la guía se quedaba en "enviando..." para siempre cuando algo
     * fallaba, que es la peor manera de enterarse de que el camión salió sin
     * documento válido.
     */
    private function interpretar(GuiaRemision $guia, int $http, $body, string $err, callable $aceptada): array
    {
        \Log::debug('GUIA SUNAT', ['guia' => $guia->numero, 'http' => $http, 'body' => $body, 'curl_error' => $err]);

        if ($err !== '') {
            return $this->falla($guia, 'Error de conexión: '.$err);
        }

        $resp = json_decode((string) $body, true);

        if (! is_array($resp)) {
            return $this->falla($guia, 'El proveedor respondió algo que no se entiende (HTTP '.$http.').');
        }

        /* La senal viene anidada: {"xml": ..., "sunatResponse": {"success":
           true, "ticket": ...}}. Se normaliza antes de decidir; mirarla solo
           en la raiz rechazaba guias que si habian entrado. */
        $sunat = is_array($resp['sunatResponse'] ?? null) ? $resp['sunatResponse'] : $resp;

        /* Un HTTP de error o un cuerpo con `error` es un no, aunque no traiga
           la clave que se estuviera mirando. Sin esto una guia rechazada se
           guardaba como aceptada y el camion salia con un papel sin valor. */
        if ($http >= 400 || ! empty($resp['error']) || isset($resp['errors']) || ! $aceptada($sunat)) {
            $motivo = $resp['error'] ?? ($resp['errors'] ?? ($resp['message'] ?? ($sunat['error'] ?? null)));
            $motivo = is_string($motivo) && $motivo !== '' ? $motivo : ($motivo ? json_encode($motivo, JSON_UNESCAPED_UNICODE) : null);

            return $this->falla($guia, $motivo ?: 'SUNAT no aceptó la guía (HTTP '.$http.').');
        }

        $ticket = $sunat['ticket'] ?? ($sunat['numTicket'] ?? ($resp['ticket'] ?? null));

        $guia->update([
            'sunat_status'  => 'accepted',
            'sunat_ticket'  => $ticket,
            'sunat_hash'    => $sunat['hash'] ?? ($resp['hash'] ?? null),
            'sunat_cdr'     => isset($sunat['cdrResponse']) ? json_encode($sunat['cdrResponse'], JSON_UNESCAPED_UNICODE) : null,
            'sunat_error'   => null,
            'sunat_sent_at' => now(),
        ]);

        return ['ok' => true, 'message' => 'Guía '.$guia->numero.' aceptada por SUNAT.'
            .($ticket ? ' Ticket '.$ticket.'.' : ''), 'ticket' => $ticket];
    }

    private function falla(GuiaRemision $guia, string $motivo): array
    {
        $guia->update(['sunat_status' => 'error', 'sunat_error' => $motivo]);

        return ['ok' => false, 'message' => $motivo];
    }

    /** @return array{0:int,1:string|bool,2:string} */
    private function post(string $url, array $cabeceras, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => array_merge([
                'Content-Type: application/json',
                'Accept: application/json',
            ], $cabeceras),
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = (string) curl_error($ch);
        curl_close($ch);

        return [$http, $body, $err];
    }
}

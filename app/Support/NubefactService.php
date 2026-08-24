<?php

namespace App\Support;

use App\Models\Invoice;
use App\Support\Sunat\Catalogos;

class NubefactService
{
    public function enviar(Invoice $invoice): array
    {
        $project = $invoice->project;

        $url   = $project->setting('nubefact_url');
        $token = $project->setting('nubefact_token');

        if (!$url || !$token) {
            return ['ok' => false, 'message' => 'Configura la URL y Token de Nubefact en Ajustes → Facturación.'];
        }

        $invoice->load('items');

        // Tipo de comprobante según manual: 1=Factura, 2=Boleta, 3=Nota Crédito, 4=Nota Débito
        $tipo = match ($invoice->type) {
            'factura'      => 1,
            'boleta'       => 2,
            'nota_credito' => 3,
            'nota_debito'  => 4,
            default        => 2,
        };

        /* Catalogo 06. Nubefact usa los mismos codigos y reserva '-' para la
           boleta sin documento (ventas menores), asi que solo se recurre a el
           cuando de verdad no hay numero que declarar. */
        $tipoDoc = trim((string) $invoice->client_doc_number) === ''
            ? '-'
            : Catalogos::codigoDocumentoIdentidad($invoice->client_doc_type, $invoice->client_doc_number);

        $moneda = $invoice->currency === 'USD' ? 2 : 1;

        // Items
        $items = $invoice->items->map(function ($it) {
            $total   = round((float) $it->total, 2);
            $igv     = round((float) $it->igv_amount, 2);
            $sub     = round($total - $igv, 2);
            $qty     = (float) $it->quantity;
            $valUnit = $qty > 0 ? round($sub / $qty, 6) : 0;
            $prcUnit = $qty > 0 ? round($total / $qty, 6) : 0;

            return [
                // Catalogo 03: el producto guarda "CAJA" y SUNAT espera "BX".
                'unidad_de_medida'          => Catalogos::codigoUnidad($it->unit),
                'codigo'                    => '',
                'descripcion'               => $it->description,
                'cantidad'                  => $qty,
                'valor_unitario'            => $valUnit,
                'precio_unitario'           => $prcUnit,
                'descuento'                 => '',
                'subtotal'                  => $sub,
                'tipo_de_igv'               => 1,
                'igv'                       => $igv,
                'total'                     => $total,
                'anticipo_regularizacion'   => false,
                'anticipo_documento_serie'  => '',
                'anticipo_documento_numero' => '',
            ];
        })->values()->all();

        $payload = [
            'operacion'                         => 'generar_comprobante',
            'tipo_de_comprobante'               => $tipo,
            'serie'                             => $invoice->serie,
            'numero'                            => (int) $invoice->correlativo,
            'sunat_transaction'                 => 1,
            'cliente_tipo_de_documento'         => $tipoDoc,
            'cliente_numero_de_documento'       => $invoice->client_doc_number ?? '',
            'cliente_denominacion'              => $invoice->client_name,
            'cliente_direccion'                 => $invoice->client_address ?? '',
            'cliente_email'                     => $invoice->client_email ?? '',
            'cliente_email_1'                   => '',
            'cliente_email_2'                   => '',
            'fecha_de_emision'                  => $invoice->issue_date->format('d-m-Y'),
            'fecha_de_vencimiento'              => $invoice->due_date?->format('d-m-Y') ?? '',
            'moneda'                            => $moneda,
            'tipo_de_cambio'                    => '',
            'porcentaje_de_igv'                 => 18.00,
            'descuento_global'                  => '',
            'total_descuento'                   => '',
            'total_anticipo'                    => '',
            'total_gravada'                     => round((float) $invoice->subtotal, 2),
            'total_inafecta'                    => '',
            'total_exonerada'                   => '',
            'total_igv'                         => round((float) $invoice->igv, 2),
            'total_gratuita'                    => '',
            'total_otros_cargos'                => '',
            'total'                             => round((float) $invoice->total, 2),
            'percepcion_tipo'                   => '',
            'percepcion_base_imponible'         => '',
            'total_percepcion'                  => '',
            'total_incluido_percepcion'         => '',
            'detraccion'                        => false,
            'observaciones'                     => $invoice->notes ?? '',
            // Se rellenan mas abajo solo si el comprobante es una nota.
            'documento_que_se_modifica_tipo'    => '',
            'documento_que_se_modifica_serie'   => '',
            'documento_que_se_modifica_numero'  => '',
            'tipo_de_nota_de_credito'           => '',
            'tipo_de_nota_de_debito'            => '',
            'enviar_automaticamente_a_la_sunat' => true,
            'enviar_automaticamente_al_cliente' => false,
            'formato_de_pdf'                    => 'A4',
            'items'                             => $items,
        ];

        /* Una nota es un documento sobre otro. Nubefact quiere el tipo en su
           propia numeracion (1 factura, 2 boleta), la serie y el numero por
           separado, y el motivo del catalogo 09/10 sin el cero de delante. */
        if ($invoice->esNota()) {
            [$afSerie, $afNumero] = array_pad(explode('-', (string) $invoice->afecta_numero, 2), 2, '');

            $payload['documento_que_se_modifica_tipo']   = $invoice->afecta_tipo === '03' ? 2 : 1;
            $payload['documento_que_se_modifica_serie']  = $afSerie;
            $payload['documento_que_se_modifica_numero'] = (int) ltrim($afNumero, '0');

            $motivo = (int) ltrim((string) $invoice->motivo_codigo, '0');
            if ($invoice->type === 'nota_credito') {
                $payload['tipo_de_nota_de_credito'] = $motivo;
            } else {
                $payload['tipo_de_nota_de_debito'] = $motivo;
            }
        }

        [$http, $body, $err] = $this->post($url, $token, $payload);

        if ($err) {
            $invoice->update(['sunat_status' => 'error', 'sunat_error' => $err]);
            return ['ok' => false, 'message' => 'Error de conexión: ' . $err];
        }

        $response = json_decode($body, true);

        if ($http === 200 && isset($response['aceptada_por_sunat']) && $response['aceptada_por_sunat'] === true) {
            $invoice->update([
                'sunat_status'  => 'accepted',
                'sunat_hash'    => $response['codigo_hash'] ?? null,
                'sunat_cdr'     => $body,
                'sunat_sent_at' => now(),
                'sunat_error'   => null,
                'status'        => 'sent',
            ]);
            return [
                'ok'           => true,
                'sunat_status' => 'accepted',
                'message'      => $response['sunat_description'] ?? 'Aceptado por SUNAT',
                'enlace_pdf'   => $response['enlace_del_pdf'] ?? null,
                'data'         => $response,
            ];
        }

        // Error — según el manual "errors" es un string
        $errorMsg = $response['errors']
            ?? $response['sunat_description']
            ?? $response['sunat_soap_error']
            ?? $body;

        $invoice->update([
            'sunat_status'  => 'rejected',
            'sunat_error'   => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg),
            'sunat_cdr'     => $body,
            'sunat_sent_at' => now(),
        ]);

        return ['ok' => false, 'message' => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg)];
    }

    /**
     * Anulacion en Nubefact: misma URL, otra operacion.
     *
     * Nubefact resuelve la baja de una pasada y devuelve el enlace del PDF de
     * la comunicacion; no hay ticket que consultar despues.
     */
    public function anular(Invoice $invoice): array
    {
        $project = $invoice->project;
        $url     = $project->setting('nubefact_url');
        $token   = $project->setting('nubefact_token');

        if (! $url || ! $token) {
            return ['ok' => false, 'message' => 'Configura la URL y Token de Nubefact en Ajustes → Facturación.'];
        }

        [$serie, $correlativo] = array_pad(explode('-', (string) $invoice->numero, 2), 2, '');

        $payload = [
            'operacion'           => 'generar_anulacion',
            'tipo_de_comprobante' => $invoice->type === 'boleta' ? 2 : 1,
            'serie'               => $serie,
            'numero'              => (int) ltrim($correlativo, '0'),
            'motivo'              => $invoice->baja_motivo ?: 'Error en la emisión',
            'codigo_unico'        => '',
        ];

        [$http, $body, $err] = $this->post($url, $token, $payload);

        \Log::debug('Nubefact BAJA', ['http' => $http, 'body' => $body, 'curl_error' => $err]);

        if ($err) {
            $invoice->update(['baja_estado' => 'rejected', 'baja_error' => $err]);
            return ['ok' => false, 'message' => 'Error de conexión: ' . $err];
        }

        $resp = json_decode($body, true);

        if (! is_array($resp) || isset($resp['errors'])) {
            $motivo = $resp['errors'] ?? 'Nubefact no aceptó la anulación.';
            $invoice->update(['baja_estado' => 'rejected', 'baja_error' => is_string($motivo) ? $motivo : json_encode($motivo)]);
            return ['ok' => false, 'message' => is_string($motivo) ? $motivo : 'Nubefact no aceptó la anulación.'];
        }

        $invoice->update([
            'baja_estado' => 'accepted',
            'baja_ticket' => $resp['numero'] ?? null,
            'baja_error'  => null,
            'baja_at'     => now(),
            'status'      => 'cancelled',
        ]);

        return ['ok' => true, 'message' => 'Anulación comunicada a SUNAT.'];
    }

    /**
     * Una sola puerta de salida hacia Nubefact.
     *
     * Segun el manual se hace POST a la RUTA directamente —sin sufijo
     * /facturas ni /boletas— y la cabecera Authorization lleva el token tal
     * cual, sin el prefijo "Token token=" que usan otros proveedores.
     *
     * @return array{0:int,1:string|bool,2:string}  [http, cuerpo, error de red]
     */
    private function post(string $url, string $token, array $payload): array
    {
        $ch = curl_init(rtrim($url, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $token,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        \Log::debug('Nubefact REQUEST', ['url' => rtrim($url, '/'), 'payload' => $payload]);
        \Log::debug('Nubefact RESPONSE', ['http' => $http, 'body' => $body, 'curl_error' => $err]);

        return [(int) $http, $body, (string) $err];
    }
}

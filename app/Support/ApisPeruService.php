<?php

namespace App\Support;

use App\Models\Invoice;
use App\Support\Sunat\Catalogos;

/**
 * Emisión de comprobantes electrónicos vía APIsPERU (formato Greenter).
 * APIsPERU firma el XML con el certificado de la empresa y lo envía a SUNAT.
 *
 * Config por proyecto (project settings):
 *   apisperu_token     -> token JWT de la empresa (Bearer)
 *   apisperu_ubigeo    -> ubigeo del emisor (ej. 150101), opcional (default 150101)
 * El RUC, razón social y dirección salen del Invoice (emisor_*).
 */
class ApisPeruService
{
    private const URL = 'https://facturacion.apisperu.com/api/v1';

    public function enviar(Invoice $invoice): array
    {
        $project = $invoice->project;
        $token   = $project->setting('apisperu_token');

        if (!$token) {
            return ['ok' => false, 'message' => 'Configura el Token de APIsPERU en Ajustes → Facturación.'];
        }

        $invoice->load('items.product');

        // Endpoint según tipo de comprobante
        $endpoint = match ($invoice->type) {
            'factura'      => '/invoice/send',
            'boleta'       => '/invoice/send',
            'nota_credito' => '/note/send',
            'nota_debito'  => '/note/send',
            default        => '/invoice/send',
        };

        // Catalogo 01. Antes cualquier tipo distinto de factura/boleta caia en
        // '01': una nota de credito viajaba declarada como factura.
        $tipoDoc = Catalogos::codigoComprobante($invoice->type);

        $payload = $this->buildPayload($invoice, $tipoDoc);

        [$http, $body, $err] = $this->post(self::URL . $endpoint, $token, $payload);

        \Log::debug('APIsPERU REQUEST', ['endpoint' => $endpoint, 'payload' => $payload]);
        \Log::debug('APIsPERU RESPONSE', ['http' => $http, 'body' => $body, 'curl_error' => $err]);

        if ($err) {
            $invoice->update(['sunat_status' => 'error', 'sunat_error' => $err]);
            return ['ok' => false, 'message' => 'Error de conexión: ' . $err];
        }

        $resp = json_decode($body, true);

        // Error de APIsPERU (antes de llegar a SUNAT)
        if ($http !== 200 || isset($resp['error'])) {
            $msg = $resp['error'] ?? ('HTTP ' . $http);
            $invoice->update(['sunat_status' => 'error', 'sunat_error' => is_string($msg) ? $msg : json_encode($msg), 'sunat_sent_at' => now()]);
            return ['ok' => false, 'message' => is_string($msg) ? $msg : json_encode($msg)];
        }

        $sunat = $resp['sunatResponse'] ?? [];

        // Aceptada por SUNAT
        if (($sunat['success'] ?? false) === true) {
            $cdr = $sunat['cdrResponse'] ?? [];
            $invoice->update([
                'sunat_status'  => 'accepted',
                'sunat_hash'    => $resp['hash'] ?? null,
                'sunat_cdr'     => $body,
                'sunat_sent_at' => now(),
                'sunat_error'   => null,
                'status'        => 'sent',
            ]);
            return [
                'ok'           => true,
                'sunat_status' => 'accepted',
                'message'      => $cdr['description'] ?? 'Aceptado por SUNAT',
                'data'         => $resp,
            ];
        }

        // Rechazada por SUNAT
        $sunatErr = $sunat['error'] ?? [];
        $errorMsg = is_array($sunatErr)
            ? (($sunatErr['code'] ?? '') . ' - ' . ($sunatErr['message'] ?? 'Rechazado por SUNAT'))
            : (string) $sunatErr;

        $invoice->update([
            'sunat_status'  => 'rejected',
            'sunat_error'   => $errorMsg,
            'sunat_cdr'     => $body,
            'sunat_sent_at' => now(),
        ]);

        return ['ok' => false, 'message' => $errorMsg];
    }

    /** Construye el JSON en formato Greenter que APIsPERU espera. */
    /**
     * Comunicacion de baja: el comprobante deja de surtir efecto.
     *
     * SUNAT no la resuelve al instante —devuelve un ticket que hay que
     * consultar despues—, asi que aqui se guarda ese ticket y la baja queda
     * en curso hasta que se confirme.
     */
    public function anular(Invoice $invoice): array
    {
        $project = $invoice->project;
        $token   = $project->setting('apisperu_token');

        if (! $token) {
            return ['ok' => false, 'message' => 'Configura el Token de APIsPERU en Ajustes → Facturación.'];
        }

        [$serie, $correlativo] = array_pad(explode('-', (string) $invoice->numero, 2), 2, '');

        $payload = [
            'fecGeneracion'   => ($invoice->issue_date ?? now())->format('Y-m-d\TH:i:sP'),
            'fecComunicacion' => now()->format('Y-m-d\TH:i:sP'),
            // Correlativo de la propia comunicacion: una por dia y negocio.
            'correlativo'     => (string) $this->correlativoDeBaja($invoice),
            'company' => [
                'ruc'         => $invoice->emisor_ruc,
                'razonSocial' => $invoice->emisor_razon_social,
                'address'     => [
                    'direccion'    => $invoice->emisor_direccion ?: '-',
                    'provincia'    => 'LIMA',
                    'departamento' => 'LIMA',
                    'distrito'     => 'LIMA',
                    'ubigueo'      => $project->setting('apisperu_ubigeo') ?: '150101',
                ],
            ],
            'details' => [[
                'tipoDoc'       => $invoice->codigoSunat(),
                'serie'         => $serie,
                'correlativo'   => (string) (int) ltrim($correlativo, '0'),
                'desMotivoBaja' => $invoice->baja_motivo ?: 'Error en la emisión',
            ]],
        ];

        [$http, $body, $err] = $this->post(self::URL . '/voided/send', $token, $payload);

        \Log::debug('APIsPERU BAJA', ['http' => $http, 'body' => $body, 'curl_error' => $err]);

        if ($err) {
            $invoice->update(['baja_estado' => 'rejected', 'baja_error' => $err]);
            return ['ok' => false, 'message' => 'Error de conexión: ' . $err];
        }

        $resp = json_decode($body, true);

        /* La baja solo esta hecha si la respuesta lo dice. Antes bastaba con
           que no dijera lo contrario, y un HTTP 500 con {"error": ...} pasaba
           por aceptado: el comprobante quedaba marcado de baja en el sistema y
           vivo en SUNAT.

           La senal viene anidada: {"xml": ..., "sunatResponse": {"success":
           true, "ticket": "..."}}. Se mira ahi, y tambien en la raiz por si el
           proveedor la aplana en otra version. */
        $sunat  = is_array($resp) ? ($resp['sunatResponse'] ?? $resp) : [];
        $ticket = $sunat['ticket'] ?? ($resp['ticket'] ?? null);
        $acepta = ($sunat['success'] ?? null) === true || $ticket !== null || isset($sunat['cdrResponse']);

        if ($http >= 400 || ! is_array($resp) || ! empty($resp['error']) || isset($resp['errors']) || ! $acepta) {
            $motivo = $resp['error'] ?? ($resp['message'] ?? ($resp['errors'] ?? ($sunat['error'] ?? null)));
            $motivo = is_string($motivo) && $motivo !== '' ? $motivo : ($motivo ? json_encode($motivo, JSON_UNESCAPED_UNICODE) : null);
            $motivo = $motivo ?: 'SUNAT no aceptó la baja (HTTP '.$http.').';

            $invoice->update(['baja_estado' => 'rejected', 'baja_error' => $motivo]);

            return ['ok' => false, 'message' => $motivo];
        }

        $invoice->update([
            'baja_estado' => 'accepted',
            'baja_ticket' => $ticket,
            'baja_error'  => null,
            'baja_at'     => now(),
            'status'      => 'cancelled',
        ]);

        return ['ok' => true, 'message' => 'Baja comunicada a SUNAT. Ticket '.$ticket.'.', 'ticket' => $ticket];
    }

    /** Cuantas bajas lleva hoy el negocio: SUNAT numera las comunicaciones. */
    private function correlativoDeBaja(Invoice $invoice): int
    {
        return Invoice::allProjects()
            ->where('project_id', $invoice->project_id)
            ->whereDate('baja_at', now()->toDateString())
            ->count() + 1;
    }

    private function buildPayload(Invoice $invoice, string $tipoDoc): array
    {
        $project = $invoice->project;
        $ubigeo  = $project->setting('apisperu_ubigeo') ?: '150101';
        $moneda  = $invoice->currency === 'USD' ? 'USD' : 'PEN';

        // Catalogo 06. Sin tipo declarado se deduce del numero (11 digitos es
        // RUC, 8 es DNI) en vez de mandar '0' —no domiciliado—, que es lo que
        // hacia antes con cualquier cosa que no reconociera.
        $cliTipoDoc = Catalogos::codigoDocumentoIdentidad(
            $invoice->client_doc_type,
            $invoice->client_doc_number
        );

        $details = $invoice->items->map(function ($it) {
            $total   = round((float) $it->total, 2);
            $igv     = round((float) $it->igv_amount, 2);
            $base    = round($total - $igv, 2);
            $qty     = (float) $it->quantity;
            $valUnit = $qty > 0 ? round($base / $qty, 6) : 0;
            $prcUnit = $qty > 0 ? round($total / $qty, 6) : 0;

            return [
                'tipAfeIgv'        => '10', // Gravado - Operación Onerosa
                // Codigo del emisor (`SellersItemIdentification`): lo define el
                // negocio, SUNAT no impone formato. Se manda el SKU cuando existe
                // —es lo que el comerciante reconoce en su factura— y solo si no
                // hay se cae al id interno, que no significa nada para el.
                'codProducto'      => (string) ($it->product?->sku ?: ($it->product_id ?? '')),
                // Catalogo 03: el producto guarda "CAJA" y SUNAT espera "BX".
                'unidad'           => Catalogos::codigoUnidad($it->unit),
                'descripcion'      => $it->description,
                'cantidad'         => $qty,
                'mtoValorUnitario' => $valUnit,
                'mtoValorVenta'    => $base,
                'mtoBaseIgv'       => $base,
                'porcentajeIgv'    => 18.0,
                'igv'              => $igv,
                'totalImpuestos'   => $igv,
                'mtoPrecioUnitario'=> $prcUnit,
            ];
        })->values()->all();

        $gravadas = round((float) $invoice->subtotal, 2);
        $igvTotal = round((float) $invoice->igv, 2);
        $total    = round((float) $invoice->total, 2);

        $payload = [
            'ublVersion'      => '2.1',
            'tipoOperacion'   => '0101', // Venta interna
            'tipoDoc'         => $tipoDoc,
            'serie'           => $invoice->serie,
            'correlativo'     => (string) $invoice->correlativo,
            'fechaEmision'    => $invoice->issue_date->format('Y-m-d\TH:i:sP'),
            'formaPago'       => ['moneda' => $moneda, 'tipo' => 'Contado'],
            'tipoMoneda'      => $moneda,
            'client' => [
                'tipoDoc'   => $cliTipoDoc,
                'numDoc'    => $invoice->client_doc_number ?: '00000000',
                'rznSocial' => $invoice->client_name,
            ],
            'company' => [
                'ruc'         => $invoice->emisor_ruc,
                'razonSocial' => $invoice->emisor_razon_social,
                // Sin esto el XML lleva el PartyName vacio y SUNAT lo observa
                // (aviso 4092 en la validacion real).
                'nombreComercial' => $project->setting('nombre_comercial') ?: $invoice->emisor_razon_social,
                'address'     => [
                    'direccion'    => $invoice->emisor_direccion ?: '-',
                    'provincia'    => 'LIMA',
                    'departamento' => 'LIMA',
                    'distrito'     => 'LIMA',
                    'ubigueo'      => $ubigeo,
                ],
            ],
            'mtoOperGravadas' => $gravadas,
            'mtoIGV'          => $igvTotal,
            'totalImpuestos'  => $igvTotal,
            'valorVenta'      => $gravadas,
            'subTotal'        => $total,
            'mtoImpVenta'     => $total,
            'details'         => $details,
            'legends'         => [[
                'code'  => '1000',
                'value' => $this->numeroALetras($total, $moneda),
            ]],
        ];

        /* Una nota de credito o debito es un documento sobre otro: sin el tipo
           y el numero del afectado y un codigo de motivo del catalogo 09/10,
           SUNAT la rechaza aunque los importes esten bien. */
        if ($invoice->esNota()) {
            $payload['tipDocAfectado'] = $invoice->afecta_tipo ?: Catalogos::codigoComprobante('factura');
            $payload['numDocfectado']  = $invoice->afecta_numero;
            $payload['codMotivo']      = $invoice->motivo_codigo;
            $payload['desMotivo']      = $invoice->motivo_descripcion
                ?: (Catalogos::MOTIVOS_NOTA_CREDITO[$invoice->motivo_codigo] ?? 'Anulación de la operación');

            // El tipo de nota va aparte del tipo de comprobante.
            $payload['tipoNota'] = $invoice->motivo_codigo;
            unset($payload['formaPago'], $payload['tipoOperacion']);
        }

        // Cliente con dirección si existe
        if ($invoice->client_address) {
            $payload['client']['address'] = ['direccion' => $invoice->client_address];
        }

        return $payload;
    }

    private function post(string $url, string $token, array $payload): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        return [$http, $body, $err];
    }

    /** Convierte el monto a letras para la leyenda 1000 (simplificado). */
    private function numeroALetras(float $monto, string $moneda): string
    {
        // La misma leyenda que imprime el PDF: si divergen, el papel miente.
        return \App\Support\Sunat\MontoEnLetras::de($monto, $moneda);
    }
}

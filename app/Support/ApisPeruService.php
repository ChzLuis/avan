<?php

namespace App\Support;

use App\Models\Invoice;

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

        $invoice->load('items');

        // Endpoint según tipo de comprobante
        $endpoint = match ($invoice->type) {
            'factura'      => '/invoice/send',
            'boleta'       => '/invoice/send',
            'nota_credito' => '/note/send',
            'nota_debito'  => '/note/send',
            default        => '/invoice/send',
        };

        $tipoDoc = match ($invoice->type) {
            'factura' => '01',
            'boleta'  => '03',
            default   => '01',
        };

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
    private function buildPayload(Invoice $invoice, string $tipoDoc): array
    {
        $project = $invoice->project;
        $ubigeo  = $project->setting('apisperu_ubigeo') ?: '150101';
        $moneda  = $invoice->currency === 'USD' ? 'USD' : 'PEN';

        // Tipo de documento del cliente
        $cliTipoDoc = match (strtoupper($invoice->client_doc_type ?? '')) {
            'DNI'       => '1',
            'CE'        => '4',
            'RUC'       => '6',
            'PASAPORTE' => '7',
            default     => '0', // sin documento (varios / boleta genérica)
        };

        $details = $invoice->items->map(function ($it) {
            $total   = round((float) $it->total, 2);
            $igv     = round((float) $it->igv_amount, 2);
            $base    = round($total - $igv, 2);
            $qty     = (float) $it->quantity;
            $valUnit = $qty > 0 ? round($base / $qty, 6) : 0;
            $prcUnit = $qty > 0 ? round($total / $qty, 6) : 0;

            return [
                'tipAfeIgv'        => '10', // Gravado - Operación Onerosa
                'codProducto'      => (string) ($it->product_id ?? ''),
                'unidad'           => $it->unit ?: 'NIU',
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
        $entero   = (int) floor($monto);
        $decimal  = (int) round(($monto - $entero) * 100);
        $unidad   = $moneda === 'USD' ? 'DÓLARES AMERICANOS' : 'SOLES';
        $enLetras = $this->enteroALetras($entero);
        return sprintf('SON %s CON %02d/100 %s', $enLetras, $decimal, $unidad);
    }

    private function enteroALetras(int $n): string
    {
        if ($n === 0) return 'CERO';
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
            'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE'];
        $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $texto = '';
        if ($n >= 1000000) { $texto .= $this->enteroALetras(intdiv($n, 1000000)) . ' MILLONES '; $n %= 1000000; }
        if ($n >= 1000)    { $m = intdiv($n, 1000); $texto .= ($m === 1 ? 'MIL ' : $this->enteroALetras($m) . ' MIL '); $n %= 1000; }
        if ($n >= 100)     { $texto .= ($n === 100 ? 'CIEN ' : $centenas[intdiv($n, 100)] . ' '); $n %= 100; }
        if ($n <= 20)      { $texto .= $unidades[$n]; }
        else               { $texto .= $decenas[intdiv($n, 10)]; if ($n % 10) $texto .= ' Y ' . $unidades[$n % 10]; }

        return trim($texto);
    }
}

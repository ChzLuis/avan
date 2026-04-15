<?php

namespace App\Support;

use App\Models\Invoice;

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

        // Tipo de documento del cliente según manual
        $tipoDoc = match (strtoupper($invoice->client_doc_type ?? '')) {
            'DNI'       => '1',
            'CE'        => '4',
            'RUC'       => '6',
            'PASAPORTE' => '7',
            default     => '-', // ventas menores a S/.700 y otros
        };

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
                'unidad_de_medida'          => $it->unit ?? 'NIU',
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

        // Según el manual: POST a la RUTA directamente (sin sufijo /facturas ni /boletas)
        // Authorization: {token} directamente, sin prefijo "Token token="
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
}

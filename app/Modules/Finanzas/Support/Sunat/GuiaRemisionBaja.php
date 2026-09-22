<?php

namespace App\Modules\Finanzas\Support\Sunat;

use App\Modules\Finanzas\Models\GuiaRemision;
use App\Modules\Finanzas\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * Comunica a SUNAT que una guía de remisión queda sin efecto.
 *
 * Una guía aceptada no se borra: se da de baja con una comunicación (RA),
 * igual que una factura. Hasta ahora el sistema lo impedía con un aviso pero
 * no ofrecía cómo hacerlo, y había que entrar al portal de SUNAT con la clave
 * SOL.
 *
 * Va por el mismo /voided/send que las facturas: es el endpoint de
 * comunicaciones de baja y admite el tipo 09 (guía de remisión remitente).
 * El correlativo de la comunicación es POR DÍA Y NEGOCIO y lo comparten las
 * facturas y las guías: dos comunicaciones con el mismo número el mismo día
 * las rechaza SUNAT, así que se cuentan las dos cosas juntas.
 */
class GuiaRemisionBaja
{
    private const URL = 'https://facturacion.apisperu.com/api/v1/voided/send';

    /** Guía de remisión remitente en el catálogo 01 de SUNAT. */
    private const TIPO_DOC_GUIA = '09';

    public function anular(GuiaRemision $guia): array
    {
        $project  = $guia->project;
        $provider = (string) $project->setting('billing_provider', '');

        if ($provider !== 'apisperu') {
            return $this->falla($guia, $provider === ''
                ? 'Elige el proveedor de facturación electrónica en Ajustes → Facturación.'
                : 'La baja de guías por ahora solo está disponible con APIsPERU.');
        }

        $token = $project->setting('apisperu_token');
        if (! $token) {
            return $this->falla($guia, 'Configura el Token de APIsPERU en Ajustes → Facturación.');
        }

        [$http, $body, $err] = $this->post($token, $this->payload($guia));

        Log::debug('GUIA BAJA SUNAT', [
            'guia' => $guia->numero, 'http' => $http, 'body' => $body, 'curl_error' => $err,
        ]);

        if ($err !== '') {
            return $this->falla($guia, 'Error de conexión: '.$err);
        }

        $resp = json_decode((string) $body, true);

        /* La baja SOLO está hecha si la respuesta lo dice. Con la factura pasó
           que un HTTP 500 con {"error": ...} se daba por aceptado y el
           documento quedaba anulado aquí y vivo en SUNAT. La señal viene
           anidada en sunatResponse, y se mira también en la raíz por si el
           proveedor la aplana. */
        $sunat  = is_array($resp) ? ($resp['sunatResponse'] ?? $resp) : [];
        $ticket = $sunat['ticket'] ?? ($resp['ticket'] ?? null);
        $acepta = ($sunat['success'] ?? null) === true || $ticket !== null || isset($sunat['cdrResponse']);

        if ($http >= 400 || ! is_array($resp) || ! empty($resp['error']) || isset($resp['errors']) || ! $acepta) {
            return $this->falla($guia, $this->motivo($resp, $sunat, $http));
        }

        $guia->update([
            'baja_estado' => 'accepted',
            'baja_ticket' => $ticket,
            'baja_error'  => null,
            'baja_at'     => now(),
            'status'      => 'cancelled',
        ]);

        return [
            'ok'      => true,
            'message' => 'Baja comunicada a SUNAT'.($ticket ? '. Ticket '.$ticket.'.' : '.'),
            'ticket'  => $ticket,
        ];
    }

    public function payload(GuiaRemision $guia): array
    {
        $project = $guia->project;
        [$serie, $correlativo] = array_pad(explode('-', (string) $guia->numero, 2), 2, '');

        $emitida = $guia->sunat_sent_at ?? $guia->created_at ?? now();

        return [
            'fecGeneracion'   => $emitida->format('Y-m-d\TH:i:sP'),
            'fecComunicacion' => now()->format('Y-m-d\TH:i:sP'),
            'correlativo'     => (string) $this->correlativoDeBaja($guia),
            'company' => [
                'ruc'         => $guia->emisor_ruc,
                'razonSocial' => $guia->emisor_razon_social,
                'address'     => [
                    'direccion'    => $project->address ?: '-',
                    'provincia'    => 'LIMA',
                    'departamento' => 'LIMA',
                    'distrito'     => 'LIMA',
                    'ubigueo'      => $project->setting('apisperu_ubigeo') ?: '150101',
                ],
            ],
            'details' => [[
                'tipoDoc'       => self::TIPO_DOC_GUIA,
                'serie'         => $serie,
                // Sin ceros a la izquierda: SUNAT los rechaza en la baja.
                'correlativo'   => (string) (int) ltrim($correlativo, '0'),
                'desMotivoBaja' => $guia->baja_motivo ?: 'Error en la emisión',
            ]],
        ];
    }

    /**
     * Correlativo de la comunicación de baja: uno por día y negocio, contando
     * facturas Y guías. Si cada una llevara su propia cuenta, dos bajas del
     * mismo día saldrían con el mismo número y SUNAT rechazaría la segunda.
     */
    private function correlativoDeBaja(GuiaRemision $guia): int
    {
        $hoy = now()->toDateString();

        $facturas = Invoice::allProjects()
            ->where('project_id', $guia->project_id)
            ->whereDate('baja_at', $hoy)
            ->count();

        $guias = GuiaRemision::allProjects()
            ->where('project_id', $guia->project_id)
            ->whereDate('baja_at', $hoy)
            ->count();

        return $facturas + $guias + 1;
    }

    /** El motivo del rechazo, en el sitio donde el proveedor lo ponga. */
    private function motivo(mixed $resp, mixed $sunat, int $http): string
    {
        $motivo = $resp['error']
            ?? ($resp['message']
            ?? ($resp['errors']
            ?? ($sunat['error'] ?? null)));

        if (is_string($motivo) && $motivo !== '') {
            return $motivo;
        }

        return $motivo
            ? (string) json_encode($motivo, JSON_UNESCAPED_UNICODE)
            : 'SUNAT no aceptó la baja (HTTP '.$http.').';
    }

    private function falla(GuiaRemision $guia, string $motivo): array
    {
        $guia->update(['baja_estado' => 'rejected', 'baja_error' => $motivo]);

        return ['ok' => false, 'message' => $motivo];
    }

    private function post(string $token, array $payload): array
    {
        $ch = curl_init(self::URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer '.$token,
            ],
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

<?php

namespace App\Support\WhatsappCloud;

use App\Models\WaCanal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envia mensajes por la WhatsApp Cloud API (Meta).
 *
 * TRADUCE el formato que ya produce el FlowRunner (string, o array con
 * 'tipo' => lista|imagen|archivo) al JSON de la Graph API. Los flujos del
 * constructor NO se tocan: el mismo bot corre en Baileys y en Meta.
 *
 * Limites de Meta que se respetan aqui (si se pasan, la API rechaza el
 * mensaje entero y el cliente se queda sin respuesta):
 *   - lista: max 10 filas en TOTAL (no por seccion), titulo de fila 24 chars,
 *     descripcion 72, boton 20, cuerpo 1024.
 *   - texto: 4096 chars.
 * Cuando una lista no cabe, se envia el 'fallback' en texto que el FlowRunner
 * ya venia calculando: el cliente recibe algo util, nunca un silencio.
 */
class ClienteCloud
{
    public function __construct(private WaCanal $canal) {}

    /** Envia todas las respuestas de un turno del bot, en orden. */
    public function enviarRespuestas(string $telefono, array $respuestas): array
    {
        $resultados = [];
        foreach ($respuestas as $r) {
            $resultados[] = $this->enviarUna($telefono, $r);
        }
        return $resultados;
    }

    /** @param string|array $respuesta */
    public function enviarUna(string $telefono, $respuesta): array
    {
        $payload = is_array($respuesta)
            ? $this->armarEstructurado($telefono, $respuesta)
            : $this->armarTexto($telefono, (string) $respuesta);

        return $this->post($payload);
    }

    private function armarTexto(string $to, string $texto): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => true,
                'body'        => mb_substr($texto, 0, 4096),
            ],
        ];
    }

    private function armarEstructurado(string $to, array $r): array
    {
        return match ($r['tipo'] ?? 'texto') {
            'lista'   => $this->armarLista($to, $r),
            'imagen'  => $this->armarMedia($to, $r, 'image'),
            'archivo' => $this->armarMedia($to, $r, 'document'),
            default   => $this->armarTexto($to, $r['fallback'] ?? $r['cuerpo'] ?? ''),
        };
    }

    /**
     * Lista interactiva. Meta admite 10 filas COMO MAXIMO en toda la lista;
     * si el flujo trae mas, cae al texto plano en vez de que Meta rechace todo.
     */
    private function armarLista(string $to, array $r): array
    {
        $secciones = [];
        $filasTotales = 0;

        foreach (($r['secciones'] ?? []) as $sec) {
            $filas = [];
            foreach (($sec['filas'] ?? []) as $f) {
                if ($filasTotales >= 10) {
                    break 2;
                }
                $fila = [
                    // El id vuelve como respuesta del cliente: es lo que el
                    // FlowRunner compara para saber que opcion eligio.
                    'id'    => mb_substr((string) ($f['id'] ?? 'op'), 0, 200),
                    'title' => mb_substr((string) ($f['titulo'] ?? ''), 0, 24),
                ];
                if (! empty($f['descripcion'])) {
                    $fila['description'] = mb_substr((string) $f['descripcion'], 0, 72);
                }
                $filas[] = $fila;
                $filasTotales++;
            }
            if ($filas !== []) {
                $secciones[] = [
                    'title' => mb_substr((string) ($sec['titulo'] ?? 'Opciones'), 0, 24),
                    'rows'  => $filas,
                ];
            }
        }

        // Sin filas utiles no hay lista que enviar: va el texto equivalente.
        if ($secciones === []) {
            return $this->armarTexto($to, $r['fallback'] ?? $r['cuerpo'] ?? 'Elige una opción:');
        }

        $interactive = [
            'type'   => 'list',
            'body'   => ['text' => mb_substr((string) ($r['cuerpo'] ?? 'Elige una opción:'), 0, 1024)],
            'action' => [
                'button'   => mb_substr((string) ($r['boton'] ?? 'Ver opciones'), 0, 20),
                'sections' => $secciones,
            ],
        ];
        if (! empty($r['titulo'])) {
            $interactive['header'] = ['type' => 'text', 'text' => mb_substr($r['titulo'], 0, 60)];
        }
        if (! empty($r['pie'])) {
            $interactive['footer'] = ['text' => mb_substr($r['pie'], 0, 60)];
        }

        return [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => $interactive,
        ];
    }

    private function armarMedia(string $to, array $r, string $tipo): array
    {
        $media = ['link' => $r['url'] ?? ''];
        if (! empty($r['caption'])) {
            $media['caption'] = mb_substr($r['caption'], 0, 1024);
        }
        if ($tipo === 'document' && ! empty($r['nombre'])) {
            $media['filename'] = $r['nombre'];
        }

        return [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => $tipo,
            $tipo               => $media,
        ];
    }

    /** Marca el mensaje como leido (los dos checks azules). */
    public function marcarLeido(string $waMessageId): void
    {
        $this->post([
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $waMessageId,
        ]);
    }

    /**
     * Una llamada a la Graph API. Nunca lanza: un fallo de Meta no debe
     * tumbar el webhook (Meta reintentaria y duplicaria la conversacion).
     */
    private function post(array $payload): array
    {
        try {
            $res = Http::withToken($this->canal->access_token)
                ->timeout(20)
                ->asJson()
                ->post($this->canal->urlMensajes(), $payload);

            if ($res->successful()) {
                $this->canal->forceFill([
                    'ultimo_ok_at' => now(),
                    'ultimo_error' => null,
                ])->saveQuietly();

                return ['ok' => true, 'id' => data_get($res->json(), 'messages.0.id')];
            }

            // Meta explica el rechazo en error.message: se guarda visible en el
            // panel porque casi siempre es accionable (token vencido, ventana
            // de 24h cerrada, plantilla no aprobada).
            $motivo = (string) data_get($res->json(), 'error.message', 'HTTP ' . $res->status());
            $this->canal->forceFill(['ultimo_error' => mb_substr($motivo, 0, 255)])->saveQuietly();
            Log::warning('wa_cloud.envio_rechazado', [
                'proyecto' => $this->canal->project_id,
                'codigo'   => data_get($res->json(), 'error.code'),
                'motivo'   => mb_substr($motivo, 0, 200),
            ]);

            return ['ok' => false, 'error' => $motivo];
        } catch (\Throwable $e) {
            $this->canal->forceFill(['ultimo_error' => class_basename($e)])->saveQuietly();
            Log::error('wa_cloud.envio_fallo', [
                'proyecto' => $this->canal->project_id,
                'error'    => class_basename($e),
                'detalle'  => mb_substr($e->getMessage(), 0, 200),
            ]);

            return ['ok' => false, 'error' => class_basename($e)];
        }
    }
}

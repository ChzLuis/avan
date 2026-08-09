<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WaCanal;
use App\Models\WaConversacion;
use App\Models\WaMensaje;
use App\Support\LeadScoring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sincroniza los chats leídos por la extensión (desde WhatsApp Web) hacia el
 * CRM de BIXO. UN solo CRM: la extensión solo empuja datos, no guarda nada.
 *
 * Idempotente: busca por teléfono; si el chat ya existe lo actualiza, no duplica.
 * Auth por token de proyecto (X-Copilot-Token), igual que el resto de la API.
 */
class WhatsappSyncController extends Controller
{
    private function project(Request $r): Project
    {
        $token = $r->header('X-Copilot-Token') ?? $r->input('token');
        $project = $token ? Project::where('copilot_token', $token)->first() : null;
        abort_if(!$project, 401, 'Token inválido.');
        return $project;
    }

    /** Canal "WhatsApp Web" del proyecto (lo crea si no existe). */
    private function canalWeb(Project $project): WaCanal
    {
        return WaCanal::firstOrCreate(
            ['project_id' => $project->id, 'tipo' => 'web'],
            ['nombre' => 'WhatsApp Web', 'activo' => true, 'bot_type' => 'none', 'color' => '#25D366']
        );
    }

    /**
     * Recibe un lote de chats desde la extensión y los sincroniza.
     * Payload: { chats: [ { nombre, telefono, mensajes:[{direccion,texto,fecha}], no_leidos } ] }
     */
    public function sync(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate([
            'chats' => 'required|array|max:200',
            'chats.*.telefono' => 'nullable|string',
            'chats.*.nombre'   => 'nullable|string',
            'chats.*.no_leidos'=> 'nullable|integer',
            'chats.*.mensajes' => 'nullable|array',
        ]);

        $canal = $this->canalWeb($project);
        $creados = 0; $actualizados = 0;

        foreach ($data['chats'] as $chat) {
            $telefono = preg_replace('/[^\d]/', '', $chat['telefono'] ?? '');
            $nombre = trim($chat['nombre'] ?? '') ?: $telefono;
            if ($telefono === '' && $nombre === '') continue;

            // Buscar conversación existente por teléfono en los canales del proyecto.
            $canalIds = WaCanal::where('project_id', $project->id)->pluck('id');
            $conv = WaConversacion::whereIn('wa_canal_id', $canalIds)
                ->where(function ($q) use ($telefono, $nombre) {
                    if ($telefono !== '') $q->where('cliente_telefono', 'like', "%$telefono%");
                    else $q->where('cliente_nombre', $nombre);
                })->first();

            if ($conv) {
                $conv->cliente_nombre = $nombre ?: $conv->cliente_nombre;
                $conv->no_leidos = $chat['no_leidos'] ?? $conv->no_leidos;
                $conv->ultimo_mensaje_at = now();
                $conv->save();
                $actualizados++;
            } else {
                // La columna cliente_telefono no admite NULL y es corta. Si el chat
                // no expone número (contacto guardado por nombre), generamos un id
                // corto derivado del nombre para no dejarla vacía ni desbordarla.
                $telParaGuardar = $telefono !== ''
                    ? substr($telefono, 0, 20)
                    : 'wa_' . substr(md5($nombre), 0, 12);

                $conv = WaConversacion::create([
                    'wa_canal_id'      => $canal->id,
                    'cliente_nombre'   => $nombre,
                    'cliente_telefono' => $telParaGuardar,
                    'estado'           => 'nuevo',
                    'no_leidos'        => $chat['no_leidos'] ?? 0,
                    'ultimo_mensaje_at'=> now(),
                    'bot_activo'       => false,
                ]);
                $creados++;
            }

            // Guardar mensajes que no existan aún (por texto+dirección simple).
            $textos = '';
            foreach (($chat['mensajes'] ?? []) as $m) {
                $texto = trim($m['texto'] ?? '');
                if ($texto === '') continue;
                $dir = ($m['direccion'] ?? 'in') === 'out' ? 'out' : 'in';
                $existe = WaMensaje::where('wa_conversacion_id', $conv->id)
                    ->where('contenido', $texto)->where('direccion', $dir)->exists();
                if (!$existe) {
                    WaMensaje::create([
                        'wa_conversacion_id' => $conv->id,
                        'direccion' => $dir,
                        'tipo' => 'texto',
                        'contenido' => $texto,
                        'estado' => 'recibido',
                    ]);
                }
                $textos .= $texto . "\n";
            }

            // Clasificar el lead con lo conversado (reglas o IA) y guardarlo en clients.
            if (trim($textos) !== '') {
                $clasif = LeadScoring::clasificar($textos);
                $client = \App\Models\Client::allProjects()
                    ->where('project_id', $project->id)
                    ->when($telefono !== '', fn ($q) => $q->where('phone', $telefono))
                    ->when($telefono === '', fn ($q) => $q->where('name', $nombre))
                    ->first();
                if (!$client) {
                    $client = new \App\Models\Client([
                        'project_id' => $project->id, 'name' => $nombre,
                        'phone' => $telefono ?: null, 'etapa' => 'prospecto',
                    ]);
                }
                $client->lead_score = $clasif['score'];
                $client->lead_temp = $clasif['temp'];
                $client->lead_source = $clasif['source'];
                $client->ultima_actividad = now();
                $client->save();
            }
        }

        return response()->json([
            'ok' => true,
            'creados' => $creados,
            'actualizados' => $actualizados,
            'total' => $creados + $actualizados,
        ]);
    }
}

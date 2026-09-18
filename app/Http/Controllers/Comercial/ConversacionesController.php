<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversacionesController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->is_superadmin, 403);

        $project = \App\Models\Project::findOrFail(session('comercial_project_id'));

        $buscar  = trim($request->get('buscar', ''));
        $estado  = $request->get('estado', 'todos');

        /* SOLO LAS DE ESTE NEGOCIO. Las cinco consultas leian `bot_sessions`
           entera, sin filtro: la lista y los contadores de las pestanas
           mezclaban las conversaciones de TODOS los proyectos de la
           plataforma, mientras la pantalla dice ser la de este. La tabla no
           tiene `project_id`; el vinculo va por el flujo (`bot_flows`). */
        $flujos = DB::table('bot_flows')->where('project_id', $project->id)->pluck('id');

        $query = DB::table('bot_sessions as s')
            ->whereIn('s.flow_id', $flujos)
            ->select('s.id', 's.wa_number', 's.current_state', 's.data', 's.last_activity_at', 's.created_at')
            ->orderByDesc('s.last_activity_at');

        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('s.wa_number', 'like', "%{$buscar}%")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(s.data, '$.nombre')) LIKE ?", ["%{$buscar}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(s.data, '$.celular')) LIKE ?", ["%{$buscar}%"]);
            });
        }

        // Filtrar por estado del flujo
        if ($estado === 'completado') {
            $query->where('s.current_state', 'confirmacion_final');
        } elseif ($estado === 'en_proceso') {
            $query->whereNotIn('s.current_state', ['confirmacion_final', 'menu_principal', 'inicio']);
        } elseif ($estado === 'inactivo') {
            $query->whereIn('s.current_state', ['menu_principal', 'inicio']);
        }

        $sesiones = $query->paginate(50);

        // Parsear data JSON
        $sesiones->getCollection()->transform(function($s) {
            $data = json_decode($s->data ?? '{}', true) ?: [];
            $s->nombre   = $data['nombre']   ?? null;
            $s->celular  = $data['celular']  ?? $s->wa_number;
            $s->email    = $data['email']    ?? null;
            $s->ciudad   = $data['ciudad']   ?? null;
            return $s;
        });

        $cnts = [
            'todos'      => DB::table('bot_sessions')->whereIn('flow_id', $flujos)->count(),
            'completado' => DB::table('bot_sessions')->whereIn('flow_id', $flujos)->where('current_state', 'confirmacion_final')->count(),
            /* `whereNotIn` descarta los NULL en silencio: una sesion recien
               creada sin estado no aparecia en ningun contador. */
            'en_proceso' => DB::table('bot_sessions')->whereIn('flow_id', $flujos)
                                ->where(fn ($w) => $w->whereNull('current_state')
                                    ->orWhereNotIn('current_state', ['confirmacion_final', 'menu_principal', 'inicio']))->count(),
            'inactivo'   => DB::table('bot_sessions')->whereIn('flow_id', $flujos)->whereIn('current_state', ['menu_principal', 'inicio'])->count(),
        ];

        return view('comercial.conversaciones', compact('project', 'sesiones', 'cnts', 'estado', 'buscar'));
    }

    public function mensajes($id)
    {
        abort_unless(auth()->user()->is_superadmin, 403);

        /* Tambien por proyecto: con el id a mano se podia abrir la
           conversacion de otro negocio, aunque no saliera en la lista. */
        $flujos = DB::table('bot_flows')
            ->where('project_id', session('comercial_project_id'))->pluck('id');

        $sesion = DB::table('bot_sessions')->where('id', $id)
            ->whereIn('flow_id', $flujos)->first();
        if (!$sesion) return response()->json(['ok' => false], 404);

        $data = json_decode($sesion->data ?? '{}', true) ?: [];

        // Buscar conversaciones ligadas a este número de WhatsApp
        $convIds = DB::table('wa_conversaciones')
            ->where('cliente_telefono', $sesion->wa_number)
            ->pluck('id');

        $mensajes = collect();
        if ($convIds->isNotEmpty()) {
            $mensajes = DB::table('wa_mensajes')
                ->whereIn('wa_conversacion_id', $convIds)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return response()->json([
            'ok'      => true,
            'sesion'  => $sesion,
            'data'    => $data,
            'mensajes'=> $mensajes,
        ]);
    }
}

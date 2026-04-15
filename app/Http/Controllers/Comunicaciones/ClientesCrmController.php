<?php

namespace App\Http\Controllers\Comunicaciones;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WaConversacion;
use App\Models\WaCanal;
use Illuminate\Http\Request;

class ClientesCrmController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    public function index(Request $request)
    {
        $project = $this->project();
        $canales = WaCanal::where('project_id', $project->id)->pluck('id');

        $query = WaConversacion::whereIn('wa_canal_id', $canales)
            ->with(['canal', 'ultimoMensaje'])
            ->orderByDesc('ultimo_mensaje_at');

        if ($request->q) {
            $query->where(fn($q) => $q
                ->where('cliente_nombre', 'like', "%{$request->q}%")
                ->orWhere('cliente_telefono', 'like', "%{$request->q}%")
            );
        }
        if ($request->estado) $query->where('estado', $request->estado);

        $clientes = $query->get();

        $clientesJs = $clientes->map(fn($c) => [
            'id'               => $c->id,
            'cliente_nombre'   => $c->cliente_nombre,
            'cliente_telefono' => $c->cliente_telefono,
            'cliente_sector'   => $c->cliente_sector,
            'cliente_distrito' => $c->cliente_distrito,
            'estado'           => $c->estado,
            'origen_anuncio'   => $c->origen_anuncio,
            'notas'            => $c->notas,
            'no_leidos'        => $c->no_leidos,
            'ultimo_mensaje'   => $c->ultimoMensaje?->contenido,
            'ultimo_mensaje_at'=> $c->ultimo_mensaje_at?->toISOString(),
            'canal_color'      => $c->canal->color,
            'canal_nombre'     => $c->canal->nombre,
            'canal_tipo'       => $c->canal->tipo,
            'created_at'       => $c->created_at->toISOString(),
        ])->values();

        // Stats por estado
        $stats = WaConversacion::whereIn('wa_canal_id', $canales)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')->pluck('total', 'estado');

        return view('comunicaciones.clientes', compact('project', 'clientesJs', 'stats'));
    }
}

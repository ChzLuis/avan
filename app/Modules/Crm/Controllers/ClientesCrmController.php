<?php

namespace App\Modules\Crm\Controllers;

use App\Modules\Bots\Ia\IA;
use App\Modules\Crm\Models\WaMensaje;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Models\Client;
use App\Models\Project;
use App\Modules\Crm\Models\WaConversacion;
use App\Modules\Crm\Models\WaCanal;
use App\Modules\Bots\Support\LeadScoring;
use App\Support\ProjectContext;
use Illuminate\Http\Request;

class ClientesCrmController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    /**
     * Ficha CRM del cliente de una conversación (columna derecha de la bandeja).
     * Une la conversación WhatsApp con el lead (clients): busca/crea por teléfono,
     * lo clasifica (reglas o IA) y devuelve scoring + pipeline + historial.
     */
    public function lead(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'telefono' => 'nullable|string',
            'nombre'   => 'nullable|string',
        ]);
        $telefono = preg_replace('/[^\d]/', '', $data['telefono'] ?? '');
        if ($telefono === '' && empty($data['nombre'])) {
            return response()->json(['error' => 'sin datos'], 422);
        }

        // Buscar/crear el lead en clients dentro del proyecto.
        $client = Client::allProjects()->where('project_id', $project->id)
            ->when($telefono !== '', fn ($q) => $q->where('phone', $telefono))
            ->when($telefono === '', fn ($q) => $q->where('name', $data['nombre']))
            ->first();

        if (!$client) {
            $client = new Client([
                'project_id' => $project->id,
                'name'  => $data['nombre'] ?: $telefono,
                'phone' => $telefono ?: null,
                'etapa' => 'prospecto',
            ]);
            $client->save();
        }

        // Clasificar con los mensajes recientes de ese teléfono.
        $conv = WaConversacion::where('cliente_telefono', 'like', "%$telefono%")
            ->latest('ultimo_mensaje_at')->first();
        $texto = '';
        if ($conv) {
            $texto = \App\Modules\Crm\Models\WaMensaje::where('wa_conversacion_id', $conv->id)
                ->orderBy('created_at')->pluck('contenido')->implode("\n");
        }
        $clasif = $texto !== '' ? LeadScoring::clasificar($texto) : null;
        if ($clasif) {
            $client->lead_score = $clasif['score'];
            $client->lead_temp = $clasif['temp'];
            $client->lead_source = $clasif['source'];
            $client->save();
        }

        $ctx = ProjectContext::for($project);
        return response()->json([
            'id' => $client->id,
            'nombre' => $client->name,
            'telefono' => $client->phone,
            'empresa' => $client->empresa,
            'etapa' => $client->etapa ?: 'prospecto',
            'producto_interes' => $client->producto_interes,
            'clasificacion' => $clasif,
            'pedidos' => $telefono ? $ctx->pedidosDe($telefono, 3) : [],
        ]);
    }

    /** Cambiar la etapa del pipeline desde la ficha de la conversación. */
    public function leadEtapa(Request $request, int $id)
    {
        $project = $this->project();
        $data = $request->validate(['etapa' => 'required|in:prospecto,contactado,propuesta,negociacion,ganado,perdido']);
        $client = Client::allProjects()->where('project_id', $project->id)->findOrFail($id);
        $client->update(['etapa' => $data['etapa']]);
        return response()->json(['ok' => true]);
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

        // Estados del negocio (los mismos que la bandeja; editables desde alli).
        \App\Modules\Crm\Models\CrmEstado::asegurar($project);
        $estados = \App\Modules\Crm\Models\CrmEstado::delProyecto($project->id);

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

        return view('crm::comunicaciones.clientes', compact('project', 'clientesJs', 'stats', 'estados'));
    }
}

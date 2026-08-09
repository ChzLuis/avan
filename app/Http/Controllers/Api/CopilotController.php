<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Ia\IA;
use App\Models\Client;
use App\Models\Project;
use App\Support\LeadScoring;
use App\Support\ProjectContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * API que consume el BIXO Copilot (extensión de Chrome).
 *
 * El cerebro es el CRM de BIXO: aquí se clasifica, se guarda y se lee el cliente.
 * La extensión solo consume. Auth por token de proyecto (header X-Copilot-Token).
 *
 * DOBLE NIVEL en cada función IA: si hay key de IA usa IA; si no, cae a reglas.
 * Así el plan base funciona sin key y la IA es el upgrade.
 */
class CopilotController extends Controller
{
    /** Resuelve el proyecto por el token del header (o aborta 401). */
    private function project(Request $r): Project
    {
        $token = $r->header('X-Copilot-Token') ?? $r->input('token');
        $project = $token ? Project::where('copilot_token', $token)->first() : null;
        abort_if(!$project, 401, 'Token de Copilot inválido o ausente.');
        return $project;
    }

    /** Estado del Copilot: si hay IA activa y qué proveedor. */
    public function status(Request $r): JsonResponse
    {
        $project = $this->project($r);
        return response()->json([
            'ok' => true,
            'proyecto' => $project->name,
            'ia_activa' => LeadScoring::hayIa(),
            'proveedor' => config('ia.provider'),
        ]);
    }

    /**
     * CONTEXTO INSTANTÁNEO: al abrir un chat, todo lo del cliente en una llamada.
     * Crea el cliente si no existe (CRM automático). Clasifica (reglas o IA).
     */
    public function contexto(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate([
            'telefono'     => 'nullable|string',
            'nombre'       => 'nullable|string',
            'conversacion' => 'nullable|string',
            'horas_sin_responder' => 'nullable|integer',
        ]);

        if (empty($data['telefono']) && empty($data['nombre'])) {
            return response()->json(['error' => 'Se requiere telefono o nombre'], 422);
        }

        // Buscar/crear cliente dentro del proyecto (CRM de BIXO).
        // allProjects() escapa el scope de sesión (la API va por token, sin sesión).
        $client = Client::allProjects()->where('project_id', $project->id)
            ->when(!empty($data['telefono']), fn ($q) => $q->where('phone', $data['telefono']))
            ->when(empty($data['telefono']) && !empty($data['nombre']), fn ($q) => $q->where('name', $data['nombre']))
            ->first();

        $nuevo = false;
        if (!$client) {
            $client = new Client([
                'project_id' => $project->id,
                'name'  => $data['nombre'] ?? ($data['telefono'] ?? 'Cliente'),
                'phone' => $data['telefono'] ?? null,
                'etapa' => 'prospecto',
            ]);
            $nuevo = true;
        }

        // Clasificación (doble nivel).
        $clasif = null;
        if (!empty($data['conversacion'])) {
            $clasif = LeadScoring::clasificar($data['conversacion'], $data['horas_sin_responder'] ?? null);
            $client->lead_score  = $clasif['score'];
            $client->lead_temp   = $clasif['temp'];
            $client->lead_source = $clasif['source'];
        }
        $client->ultima_actividad = now();
        $client->save();

        return response()->json([
            'cliente' => [
                'id' => $client->id,
                'nombre' => $client->name,
                'empresa' => $client->empresa,
                'telefono' => $client->phone,
                'etapa' => $client->etapa,
                'lead_temp' => $client->lead_temp,
                'lead_score' => $client->lead_score,
                'lead_source' => $client->lead_source,
                'producto_interes' => $client->producto_interes,
                'monto_estimado' => $client->monto_estimado,
                'proximo_seguimiento' => $client->proximo_seguimiento,
                'notas' => $client->notes,
                'es_nuevo' => $nuevo,
            ],
            'clasificacion' => $clasif,
            'ia_activa' => LeadScoring::hayIa(),
        ]);
    }

    /** Clasificación de lead a demanda (botón "reclasificar"). */
    public function clasificar(Request $r): JsonResponse
    {
        $this->project($r);
        $data = $r->validate(['conversacion' => 'required|string|min:1', 'horas_sin_responder' => 'nullable|integer']);
        return response()->json(LeadScoring::clasificar($data['conversacion'], $data['horas_sin_responder'] ?? null));
    }

    /** Generar respuesta sugerida (solo con IA; sin key devuelve aviso). */
    public function respuesta(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['conversacion' => 'required|string|min:1']);

        if (!LeadScoring::hayIa()) {
            return response()->json(['error' => 'Esta función necesita IA. Configura una API key en BIXO.'], 400);
        }
        try {
            $texto = IA::generarRespuesta($data['conversacion'], ['empresa' => $project->name]);
            return response()->json(['respuesta' => $texto]);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    /** Guardar una nota rápida en el cliente (sin IA). */
    public function nota(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['client_id' => 'required|integer', 'nota' => 'required|string']);
        $client = Client::allProjects()->where('project_id', $project->id)->findOrFail($data['client_id']);
        $client->notes = trim(($client->notes ? $client->notes . "\n" : '') . '• ' . $data['nota']);
        $client->save();
        return response()->json(['ok' => true, 'notas' => $client->notes]);
    }

    // ═══════════════════════════════════════════════════════════════════
    //  API DE CONTEXTO — la "puerta a los datos" que usan el bot y la IA.
    //  Todo el negocio (catálogo, cliente, pedidos, conocimiento) por token.
    // ═══════════════════════════════════════════════════════════════════

    /** Buscar productos del catálogo por nombre/sku. */
    public function buscarProducto(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['q' => 'required|string|min:1']);
        return response()->json([
            'productos' => ProjectContext::for($project)->buscarProducto($data['q']),
        ]);
    }

    /** Catálogo resumido (productos + servicios). */
    public function catalogo(Request $r): JsonResponse
    {
        $project = $this->project($r);
        return response()->json(['catalogo' => ProjectContext::for($project)->catalogo()]);
    }

    /** Catálogo COMPLETO para el POS (productos + servicios con imagen, agrupados). */
    public function catalogoPos(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $q = trim((string) $r->input('q', ''));

        $productos = \App\Models\Product::where('project_id', $project->id)
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('sku', 'like', "%$q%")))
            ->orderBy('name')->limit(200)
            ->get(['id', 'name', 'sku', 'price', 'stock'])
            ->map(fn ($p) => [
                'id' => $p->id, 'tipo' => 'producto', 'nombre' => $p->name,
                'sku' => $p->sku, 'precio' => (float) $p->price, 'stock' => $p->stock,
            ]);

        $servicios = \App\Models\Service::where('project_id', $project->id)
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%$q%"))
            ->where('is_available', true)->orderBy('name')->limit(200)
            ->get(['id', 'name', 'price'])
            ->map(fn ($s) => [
                'id' => 'srv_' . $s->id, 'tipo' => 'servicio', 'nombre' => $s->name,
                'sku' => null, 'precio' => (float) $s->price, 'stock' => null,
            ]);

        return response()->json([
            'items' => $productos->concat($servicios)->values(),
            'total' => $productos->count() + $servicios->count(),
        ]);
    }

    /** Ficha del cliente + historial de pedidos por teléfono. */
    public function clienteInfo(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['telefono' => 'required|string']);
        $cliente = ProjectContext::for($project)->cliente($data['telefono']);
        return response()->json(['cliente' => $cliente]);
    }

    /** Estado de pedidos: por teléfono (lista) o por id (uno). */
    public function pedidos(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $ctx = ProjectContext::for($project);
        if ($r->filled('id')) {
            return response()->json(['pedido' => $ctx->pedido((int) $r->input('id'))]);
        }
        $data = $r->validate(['telefono' => 'required|string']);
        return response()->json(['pedidos' => $ctx->pedidosDe($data['telefono'])]);
    }

    /** Brief completo del contexto para la IA (conocimiento del negocio). */
    public function contextoIa(Request $r): JsonResponse
    {
        $project = $this->project($r);
        return response()->json([
            'brief' => ProjectContext::for($project)->briefParaIa($r->input('telefono')),
            'conocimiento' => ProjectContext::for($project)->conocimiento(),
        ]);
    }

    /** Respuestas rápidas / plantillas del proyecto (para insertar en el chat). */
    public function respuestasRapidas(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $items = \DB::table('wa_respuestas_rapidas')
            ->where('project_id', $project->id)
            ->orderBy('orden')->orderBy('nombre')
            ->get(['nombre', 'texto']);
        return response()->json(['respuestas' => $items]);
    }

    // ═══ Gestión del cliente desde la extensión (pipeline, etiquetas, notas, seguimiento) ═══

    private function client(Request $r, Project $project): Client
    {
        $id = $r->input('client_id');
        $client = Client::allProjects()->where('project_id', $project->id)->find($id);
        abort_if(!$client, 404, 'Cliente no encontrado.');
        return $client;
    }

    /** Cambiar etapa del pipeline (incluye ganado/perdido). */
    public function cambiarEtapa(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['client_id' => 'required|integer', 'etapa' => 'required|in:prospecto,contactado,propuesta,negociacion,ganado,perdido']);
        $client = $this->client($r, $project);
        $client->update(['etapa' => $data['etapa']]);
        return response()->json(['ok' => true, 'etapa' => $client->etapa]);
    }

    /** Guardar etiquetas del cliente (array de strings). */
    public function guardarEtiquetas(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['client_id' => 'required|integer', 'etiquetas' => 'array']);
        $client = $this->client($r, $project);
        $client->update(['etiquetas' => array_values($data['etiquetas'] ?? [])]);
        return response()->json(['ok' => true, 'etiquetas' => $client->etiquetas]);
    }

    /** Agregar una nota (se acumulan en el campo notes con fecha). */
    public function agregarNota(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['client_id' => 'required|integer', 'nota' => 'required|string']);
        $client = $this->client($r, $project);
        $linea = '[' . now()->format('d/m H:i') . '] ' . trim($data['nota']);
        $client->notes = trim(($client->notes ? $client->notes . "\n" : '') . $linea);
        $client->save();
        return response()->json(['ok' => true, 'notas' => $client->notes]);
    }

    /** Ficha de contacto completa (todos los campos editables). */
    public function fichaContacto(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $client = $this->client($r, $project);
        return response()->json(['cliente' => $client->only([
            'id','name','phone','email','empresa','cargo','sexo','fecha_nacimiento',
            'idioma','pais','ciudad','provincia','direccion','lead_source','valor_negocio',
            'etapa','proximo_seguimiento',
        ])]);
    }

    /** Guardar campos de la ficha de contacto. */
    public function guardarFicha(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $client = $this->client($r, $project);
        $data = $r->validate([
            'name' => 'nullable|string|max:120', 'email' => 'nullable|email|max:120',
            'empresa' => 'nullable|string|max:120', 'cargo' => 'nullable|string|max:80',
            'sexo' => 'nullable|string|max:20', 'fecha_nacimiento' => 'nullable|date',
            'idioma' => 'nullable|string|max:30', 'pais' => 'nullable|string|max:60',
            'ciudad' => 'nullable|string|max:80', 'provincia' => 'nullable|string|max:80',
            'direccion' => 'nullable|string|max:200', 'lead_source' => 'nullable|string|max:80',
            'valor_negocio' => 'nullable|numeric',
        ]);
        $client->fill(array_filter($data, fn ($v) => $v !== null));
        $client->save();
        return response()->json(['ok' => true]);
    }

    /** Programar un seguimiento (fecha) y opcional una nota. */
    public function programarSeguimiento(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['client_id' => 'required|integer', 'fecha' => 'required|date', 'nota' => 'nullable|string']);
        $client = $this->client($r, $project);
        $client->proximo_seguimiento = $data['fecha'];
        if (!empty($data['nota'])) {
            $linea = '[seguimiento ' . \Carbon\Carbon::parse($data['fecha'])->format('d/m') . '] ' . $data['nota'];
            $client->notes = trim(($client->notes ? $client->notes . "\n" : '') . $linea);
        }
        $client->save();
        return response()->json(['ok' => true, 'proximo_seguimiento' => $client->proximo_seguimiento]);
    }
}

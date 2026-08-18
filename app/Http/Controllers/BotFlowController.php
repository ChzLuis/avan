<?php

namespace App\Http\Controllers;

use App\Models\BotFlow;
use App\Support\FlowEngine\FlowRunner;
use Illuminate\Http\Request;

/**
 * Constructor visual de bots: el usuario dibuja el flujo (bloques + conexiones)
 * y aquí se guarda/carga. El motor (FlowRunner) lo ejecuta.
 */
class BotFlowController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $flows = BotFlow::where('project_id', $project->id)->latest()->get();
        return view('bot-flows.index', compact('project', 'flows'));
    }

    /** Editor de un flujo (crea uno nuevo si no se pasa id). */
    public function editor(?BotFlow $flow = null)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        if (!$flow || !$flow->exists) {
            $flow = BotFlow::create([
                'project_id' => $project->id,
                'nombre' => 'Nuevo bot',
                'definicion' => $this->flujoInicial(),
            ]);
            return redirect()->route('bot-flows.editor', $flow);
        }
        abort_unless($flow->project_id === $project->id, 403);

        return view('bot-flows.editor', compact('project', 'flow'));
    }

    /**
     * Crea un bot de tienda listo para usar a partir de la plantilla estándar.
     *
     * Nace DESACTIVADO a propósito: el dueño lo revisa, cambia los textos que
     * quiera y lo enciende él. Activar un bot que habla con clientes reales sin
     * que nadie lo haya leído es la clase de sorpresa que no se arregla luego.
     */
    public function desdePlantilla()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $flow = BotFlow::create([
            'project_id' => $project->id,
            'nombre'     => \App\Support\FlowEngine\PlantillaTienda::NOMBRE,
            'activo'     => false,
            'definicion' => \App\Support\FlowEngine\PlantillaTienda::definicion($project),
        ]);

        return redirect()->route('bot-flows.editor', $flow)
            ->with('success', 'Bot de tienda creado. Revisa los textos y actívalo cuando estés conforme.');
    }

    /** Guardar la definición del flujo (autosave desde el editor). */
    public function save(Request $request, BotFlow $flow)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($flow->project_id === $project->id, 403);

        $data = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'definicion' => 'required|array',
            'activo' => 'nullable|boolean',
        ]);
        $flow->update(array_filter([
            'nombre' => $data['nombre'] ?? null,
            'definicion' => $data['definicion'],
            'activo' => $data['activo'] ?? $flow->activo,
        ], fn ($v) => $v !== null));

        return response()->json(['ok' => true]);
    }

    /** Probar el flujo con un mensaje (simulador del editor). */
    public function test(Request $request, BotFlow $flow)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($flow->project_id === $project->id, 403);

        $data = $request->validate([
            'mensaje' => 'required|string',
            'estado' => 'nullable|array',
        ]);

        $runner = new FlowRunner($project, $flow->definicion ?? []);
        $estado = $data['estado'] ?? ['bloque' => null, 'vars' => [], 'esperando' => false];
        $res = $runner->procesar($data['mensaje'], $estado, '51999999999');

        return response()->json($res);
    }

    public function destroy(BotFlow $flow)
    {
        $project = app('active_project');
        abort_unless($flow->project_id === $project->id, 403);
        $flow->delete();
        return response()->json(['ok' => true]);
    }

    /** Flujo de arranque: un saludo simple para no empezar en blanco. */
    private function flujoInicial(): array
    {
        return [
            'inicio' => 'b1',
            'bloques' => [
                'b1' => ['tipo' => 'mensaje', 'texto' => '¡Hola! Gracias por escribir 👋', 'siguiente' => null, 'x' => 60, 'y' => 60],
            ],
        ];
    }
}

<?php

namespace App\Modules\Bots\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bots\Support\FlowEngine\PlantillaComercial;
use App\Modules\Bots\Support\FlowEngine\PlantillaTienda;

use App\Modules\Bots\Models\BotFlow;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
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
        // Checklist del Bot Comercial: qué datos reales tiene el negocio y qué
        // le falta para responder completo (el panel lo enseña como chips).
        $checklistComercial = \App\Modules\Bots\Support\FlowEngine\PlantillaComercial::checklist($project);
        $iaLicenciada = \App\Modules\Bots\Ia\InterpreteComercial::licenciado($project);
        $iaActiva     = \App\Modules\Bots\Ia\InterpreteComercial::activo($project);
        return view('bots::bot-flows.index', compact('project', 'flows', 'checklistComercial', 'iaLicenciada', 'iaActiva'));
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

        return view('bots::bot-flows.editor', compact('project', 'flow'));
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
            'nombre'     => \App\Modules\Bots\Support\FlowEngine\PlantillaTienda::NOMBRE,
            'activo'     => false,
            'definicion' => \App\Modules\Bots\Support\FlowEngine\PlantillaTienda::definicion($project),
        ]);

        return redirect()->route('bot-flows.editor', $flow)
            ->with('success', 'Bot de tienda creado. Revisa los textos y actívalo cuando estés conforme.');
    }

    /**
     * Abre el Bot Comercial predeterminado del negocio.
     *
     * Toda empresa tiene el suyo desde que se crea, así que aquí no se duplica
     * nada: si ya existe se abre, y si falta (empresa anterior al alta
     * automática) se crea al vuelo. Nace DESACTIVADO: el dueño revisa los
     * textos, mira el checklist de datos y lo enciende él.
     */
    public function desdePlantillaComercial()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $flow = BotFlow::comercialDe($project);

        return redirect()->route('bot-flows.editor', $flow)
            ->with('success', $flow->wasRecentlyCreated
                ? 'Bot Comercial listo. Revisa los textos y actívalo cuando estés conforme.'
                : 'Este es tu Bot Comercial. Revisa los textos y actívalo cuando estés conforme.');
    }

    /**
     * Enciende/apaga la IA del Bot Comercial para el proyecto activo.
     *
     * Dos conceptos distintos: la LICENCIA la da el modulo `bot_ia` (se asigna
     * desde el panel de administracion, como cualquier modulo) y aqui solo se
     * gestiona la ACTIVACION. Sin licencia, este endpoint no enciende nada.
     */
    public function toggleIa(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        abort_unless(\App\Modules\Bots\Ia\InterpreteComercial::licenciado($project), 403,
            'La IA no esta incluida en el plan de este negocio.');

        $activar = $request->boolean('activo');
        $project->settings()->updateOrCreate(
            ['key' => \App\Modules\Bots\Ia\InterpreteComercial::AJUSTE],
            ['value' => $activar ? '1' : '0']
        );

        return back()->with('success', $activar
            ? 'IA activada: el bot interpretara mejor los mensajes libres.'
            : 'IA desactivada: el bot sigue atendiendo con el motor estandar.');
    }

    /** Guardar la definición del flujo (autosave desde el editor). */
    public function save(Request $request, BotFlow $flow)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($flow->project_id === $project->id, 403);

        // `definicion` es OPCIONAL: renombrar o encender el bot no debe
        // materializar una copia de la plantilla (el bot dejaria de recibir
        // mejoras solo por cambiarle el nombre). Solo una edicion real del
        // flujo envia la definicion.
        $data = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'definicion' => 'nullable|array',
            'activo' => 'nullable|boolean',
        ]);
        $flow->update(array_filter([
            'nombre' => $data['nombre'] ?? null,
            'definicion' => $data['definicion'] ?? null,
            'activo' => $data['activo'] ?? $flow->activo,
        ], fn ($v) => $v !== null));

        // UN solo bot responde por negocio. El webhook toma el activo mas
        // reciente; con dos encendidos el "ganador" era implicito e invisible.
        // Encender uno apaga a los demas, y la lista muestra cual atiende.
        if (! empty($data['activo'])) {
            BotFlow::where('project_id', $project->id)->whereKeyNot($flow->id)->update(['activo' => false]);
        }

        return response()->json(['ok' => true, 'activo' => (bool) $flow->fresh()->activo]);
    }

    /**
     * Vuelve a la plantilla estandar: descarta la version propia.
     *
     * Solo aplica a bots nacidos de la plantilla comercial. Al borrar la copia,
     * el accessor vuelve a armar la definicion en vivo y el bot recupera las
     * mejoras automaticas.
     */
    public function restaurarPlantilla(BotFlow $flow)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($flow->project_id === $project->id, 403);
        abort_unless($flow->plantilla === BotFlow::COMERCIAL, 422, 'Este bot no nacio de la plantilla.');

        $flow->update(['definicion' => null]);

        return redirect()->route('bot-flows.editor', $flow)
            ->with('success', 'Listo: el bot vuelve a seguir la plantilla estandar y sus mejoras automaticas.');
    }

    /**
     * Estado de la linea de WhatsApp del negocio (para el QR en el panel).
     *
     * El conector Baileys de cada empresa reporta su estado y QR a
     * /api/bot/wa-status (autenticado por copilot_token) y queda en
     * storage/app/bot-wa/{project}.json. Aqui se lee para el proyecto ACTIVO
     * del panel; si el reporte tiene mas de 60 s, el conector esta caido.
     */
    public function waStatus()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $file = storage_path("app/bot-wa/{$project->id}.json");
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        $fresh = is_array($data) && isset($data['ts']) && (time() - (int) $data['ts'] < 60);

        return response()->json([
            'status' => $fresh ? ($data['status'] ?? 'offline') : 'offline',
            'qr'     => $fresh ? ($data['qr'] ?? null) : null,
        ]);
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

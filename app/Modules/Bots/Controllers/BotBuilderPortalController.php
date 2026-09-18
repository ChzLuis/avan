<?php

namespace App\Modules\Bots\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bots\Models\BotFlow;
use App\Models\Project;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use Illuminate\Http\Request;

/**
 * Constructor visual de bots DENTRO del portal CRM (Comunicaciones).
 * Reusa el modelo BotFlow y el motor FlowRunner, pero con el proyecto del
 * portal (comunicaciones_project_id) y el layout del portal.
 */
class BotBuilderPortalController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comunicaciones_project_id'));
    }

    public function index()
    {
        $project = $this->project();
        $flows = BotFlow::where('project_id', $project->id)->latest()->get();
        return view('bots::comunicaciones.bots.index', compact('project', 'flows'));
    }

    public function editor(?int $id = null)
    {
        $project = $this->project();

        if (!$id) {
            $flow = BotFlow::create([
                'project_id' => $project->id,
                'nombre' => 'Nuevo bot',
                'definicion' => ['inicio' => 'b1', 'bloques' => [
                    'b1' => ['tipo' => 'mensaje', 'texto' => '¡Hola! Gracias por escribir 👋', 'siguiente' => null, 'x' => 60, 'y' => 60],
                ]],
            ]);
            return redirect()->route('bixocrm.bots.editor', $flow->id);
        }

        $flow = BotFlow::where('project_id', $project->id)->findOrFail($id);
        return view('bots::comunicaciones.bots.editor', compact('project', 'flow'));
    }

    public function save(Request $request, int $id)
    {
        $project = $this->project();
        $flow = BotFlow::where('project_id', $project->id)->findOrFail($id);
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

    public function test(Request $request, int $id)
    {
        $project = $this->project();
        $flow = BotFlow::where('project_id', $project->id)->findOrFail($id);
        $data = $request->validate(['mensaje' => 'required|string', 'estado' => 'nullable|array']);
        $runner = new FlowRunner($project, $flow->definicion ?? []);
        $estado = $data['estado'] ?? ['bloque' => null, 'vars' => [], 'esperando' => false];
        return response()->json($runner->procesar($data['mensaje'], $estado, '51999999999'));
    }

    public function destroy(int $id)
    {
        $project = $this->project();
        BotFlow::where('project_id', $project->id)->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ── Conexión de WhatsApp (Baileys) ────────────────────────────────────
    private function waFile(int $projectId): string
    {
        $dir = storage_path('app/bot-wa');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return "$dir/{$projectId}.json";
    }

    /** El frontend (constructor) consulta el estado + QR del bot del proyecto. */
    public function waStatus()
    {
        $project = $this->project();
        $file = $this->waFile($project->id);
        $data = is_file($file) ? json_decode(file_get_contents($file), true) : null;
        // Si no hay reporte reciente (>60s), el bot está caído/desconectado.
        $fresh = $data && isset($data['ts']) && (time() - $data['ts'] < 60);
        return response()->json([
            'status' => $fresh ? ($data['status'] ?? 'offline') : 'offline',
            'qr'     => $fresh ? ($data['qr'] ?? null) : null,
        ]);
    }

    /**
     * El bot Baileys reporta su estado y QR aquí (auth por copilot_token).
     * No usa sesión del CRM: es máquina-a-máquina.
     */
    public function waPush(Request $request)
    {
        $token = $request->header('X-Copilot-Token') ?? $request->input('token');
        $project = $token ? Project::where('copilot_token', $token)->first() : null;
        abort_if(!$project, 401, 'Token inválido.');

        $data = $request->validate([
            'status' => 'required|string|max:20',   // qr | connected | starting | offline
            'qr'     => 'nullable|string',           // dataURL del QR
        ]);
        file_put_contents($this->waFile($project->id), json_encode([
            'status' => $data['status'],
            'qr'     => $data['qr'] ?? null,
            'ts'     => time(),
        ]));
        return response()->json(['ok' => true]);
    }
}

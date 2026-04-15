<?php

namespace App\Http\Controllers;

use App\Models\BotFlow;
use App\Models\BotInstance;
use App\Models\BotState;
use App\Models\BotTransition;
use App\Models\BotConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\WaBotController;

class BotStatusController extends Controller
{
    private function readStatus(string $file): array
    {
        $path = base_path('whatsbot/' . $file);
        if (!file_exists($path)) return ['status' => 'offline', 'qr' => null, 'updated_at' => null];
        return json_decode(file_get_contents($path), true) ?: ['status' => 'offline', 'qr' => null, 'updated_at' => null];
    }

    // ── Control PM2 ───────────────────────────────────────────────
    public function botControl(Request $request)
    {
        $data = $request->validate([
            'bot'    => 'required|string',
            'action' => 'required|in:start,stop,restart',
        ]);

        $project = app('active_project');
        $bot     = BotInstance::where('project_id', $project->id)->where('bot_type', $data['bot'])->firstOrFail();

        $pm2Name = $bot->pm2Name();
        $engine  = base_path('whatsbot/engine.js');
        $logDir  = base_path('whatsbot/logs');
        $logFile = "{$logDir}/{$data['bot']}.log";

        @mkdir($logDir, 0775, true);

        $startCmd = "pm2 start {$engine} --name {$pm2Name} -- --bot={$data['bot']} --output {$logFile} --error {$logFile} --merge-logs";

        $cmd = match($data['action']) {
            'start'   => "{$startCmd} 2>&1",
            'stop'    => "pm2 stop {$pm2Name} 2>&1",
            'restart' => "pm2 restart {$pm2Name} 2>&1 || {$startCmd} 2>&1",
        };

        $output = shell_exec($cmd);

        if ($data['action'] === 'stop') {
            file_put_contents($bot->statusFile(), json_encode(['status'=>'offline','qr'=>null,'updated_at'=>now()]));
        }

        return response()->json(['ok' => true, 'output' => trim((string)$output)]);
    }

    // ── Logs del bot ──────────────────────────────────────────────
    public function botLogs(Request $request)
    {
        $bot     = $request->get('bot', 'main');
        $lines   = (int) $request->get('lines', 50);
        $logFile = base_path("whatsbot/logs/{$bot}.log");

        if (!file_exists($logFile)) {
            return response()->json(['ok' => true, 'logs' => 'Sin logs disponibles aún.']);
        }

        // Leer últimas N líneas
        $output = shell_exec("tail -n {$lines} " . escapeshellarg($logFile) . " 2>&1");
        if ($output === null) {
            // Windows fallback
            $all    = file($logFile);
            $output = implode('', array_slice($all, -$lines));
        }

        return response()->json(['ok' => true, 'logs' => $output ?? '']);
    }

    // ── Panel principal ───────────────────────────────────────────
    public function index()
    {
        $project = app('active_project');

        $bots = BotInstance::where('project_id', $project->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function ($bot) use ($project) {
                $bot->status = $bot->readStatus();
                $bot->flow   = BotFlow::where('project_id', $project->id)
                    ->where('bot_type', $bot->bot_type)
                    ->withCount('states')
                    ->first();
                return $bot;
            });

        return view('bots.index', compact('project', 'bots'));
    }

    // ── CRUD BotInstance ──────────────────────────────────────────
    public function botStore(Request $request)
    {
        $project = app('active_project');
        $data = $request->validate([
            'name'        => 'required|string|max:80',
            'bot_type'    => 'required|string|max:40|alpha_dash|unique:bot_instances,bot_type',
            'description' => 'nullable|string|max:150',
            'icon_color'  => 'required|in:green,purple,blue,orange,red',
            'port'        => 'required|integer|min:3001|max:3999',
        ]);

        $bot = BotInstance::create(array_merge($data, ['project_id' => $project->id, 'is_active' => true]));

        // Crear flujo vacío automáticamente
        BotFlow::firstOrCreate(
            ['project_id' => $project->id, 'bot_type' => $bot->bot_type],
            ['name' => 'Flujo ' . $bot->name, 'is_active' => true]
        );

        return response()->json(['ok' => true, 'bot' => $bot]);
    }

    public function botDestroy(BotInstance $bot)
    {
        $project = app('active_project');
        abort_unless($bot->project_id === $project->id, 403);

        // Detener bot si está corriendo
        @shell_exec("pm2 delete {$bot->pm2Name()} 2>&1");

        // Limpiar status file
        $statusFile = $bot->statusFile();
        if (file_exists($statusFile)) unlink($statusFile);

        $bot->delete();
        return response()->json(['ok' => true]);
    }

    // ── Status JSON (polling) ─────────────────────────────────────
    public function status(Request $request)
    {
        $botType = $request->get('bot', 'main');
        $file    = "{$botType}-status.json";
        return response()->json($this->readStatus($file));
    }

    // ── Flujos ────────────────────────────────────────────────────
    public function flowIndex(Request $request)
    {
        $project  = app('active_project');
        $botType  = $request->get('bot', 'main');
        $flow     = BotFlow::where('project_id', $project->id)
                        ->where('bot_type', $botType)
                        ->with('states.transitions.toState')
                        ->first();

        $allStates = $flow ? $flow->states->load('transitions.toState') : collect();

        return view('bots.flow', compact('project', 'flow', 'allStates', 'botType'));
    }

    public function flowStore(Request $request)
    {
        $project = app('active_project');
        $botType = $request->bot_type ?? 'main';

        $flow = BotFlow::firstOrCreate(
            ['project_id' => $project->id, 'bot_type' => $botType],
            ['name' => $request->name ?? 'Flujo ' . $botType, 'is_active' => true]
        );

        return response()->json(['ok' => true, 'flow' => $flow]);
    }

    // ── Estados ───────────────────────────────────────────────────
    public function stateStore(Request $request)
    {
        $project = app('active_project');
        $data    = $request->validate([
            'flow_id'    => 'required|integer',
            'key'        => 'required|string|max:60',
            'label'      => 'required|string|max:100',
            'message'    => 'required|string',
            'input_type' => 'required|in:text,number,image,location,option',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        $flow = BotFlow::where('project_id', $project->id)->findOrFail($data['flow_id']);
        $state = BotState::create($data);
        $this->exportFlow($flow);

        return response()->json(['ok' => true, 'state' => $state]);
    }

    public function stateUpdate(Request $request, BotState $state)
    {
        $project = app('active_project');
        abort_unless($state->flow->project_id === $project->id, 403);

        $data = $request->validate([
            'label'      => 'required|string|max:100',
            'message'    => 'required|string',
            'input_type' => 'required|in:text,number,image,location,option',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ]);

        $state->update($data);
        $this->exportFlow($state->flow);

        return response()->json(['ok' => true, 'state' => $state]);
    }

    public function stateDestroy(BotState $state)
    {
        $project = app('active_project');
        abort_unless($state->flow->project_id === $project->id, 403);
        $flow = $state->flow;
        $state->delete();
        $this->exportFlow($flow);

        return response()->json(['ok' => true]);
    }

    // ── Transiciones ──────────────────────────────────────────────
    public function transitionStore(Request $request)
    {
        $data = $request->validate([
            'from_state_id' => 'required|integer|exists:bot_states,id',
            'to_state_id'   => 'required|integer|exists:bot_states,id',
            'trigger'       => 'nullable|string|max:100',
            'action'        => 'nullable|string|max:60',
            'action_param'  => 'nullable|string|max:100',
        ]);

        $transition = BotTransition::create($data);
        $transition->load('toState');
        $this->exportFlow(BotState::find($data['from_state_id'])->flow);

        return response()->json(['ok' => true, 'transition' => $transition]);
    }

    public function transitionDestroy(BotTransition $transition)
    {
        $flow = $transition->fromState->flow;
        $transition->delete();
        $this->exportFlow($flow);

        return response()->json(['ok' => true]);
    }

    // ── Config ────────────────────────────────────────────────────
    public function configSave(Request $request)
    {
        $project = app('active_project');
        $botType = $request->bot_type ?? 'main';

        foreach ($request->except(['_token', 'bot_type']) as $key => $value) {
            BotConfig::set($project->id, $botType, $key, $value ?? '');
        }

        return response()->json(['ok' => true]);
    }

    // ── Exportar flujo a JSON local (fallback para el bot) ────────────────────
    private function exportFlow(BotFlow $flow): void
    {
        try {
            $flow->load('states.transitions.toState');
            $states = $flow->states->map(fn($s) => [
                'key'        => $s->key,
                'label'      => $s->label,
                'message'    => $s->message,
                'input_type' => $s->input_type,
                'transitions'=> $s->transitions->map(fn($t) => [
                    'trigger'      => $t->trigger,
                    'to'           => $t->toState?->key,
                    'action'       => $t->action,
                    'action_param' => $t->action_param,
                ])->values(),
            ])->keyBy('key');

            $payload = [
                'ok'         => true,
                'flow_id'    => $flow->id,
                'bot_type'   => $flow->bot_type,
                'project_id' => $flow->project_id,
                'states'     => $states,
            ];

            $path = base_path("whatsbot/flow-{$flow->bot_type}.json");
            file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable) {}
    }
}

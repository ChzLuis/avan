<?php

namespace App\Http\Controllers;

use App\Models\BotFlow;
use App\Models\BotInstance;
use App\Models\BotState;
use App\Models\BotTransition;
use App\Models\BotConfig;
use App\Models\BotSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

        $laravelUrl   = config('app.url');
        $chromiumPath = env('CHROMIUM_PATH', '');
        $pm2Wrapper   = '/usr/local/bin/bot-pm2-run';
        $pm2Bin       = trim(shell_exec('which pm2') ?: '/usr/bin/pm2');
        $pm2          = file_exists($pm2Wrapper) ? "sudo {$pm2Wrapper}" : $pm2Bin;
        // Las variables de entorno van dentro del wrapper, no antes de sudo
        $startCmd = "{$pm2} start {$engine} --name {$pm2Name} -f -- --bot={$data['bot']} --port={$bot->port} --output {$logFile} --error {$logFile} --merge-logs";

        $cmd = match($data['action']) {
            'start'   => "{$pm2} delete {$pm2Name} 2>/dev/null; sleep 1; {$startCmd} 2>&1",
            'stop'    => "{$pm2} stop {$pm2Name} 2>&1",
            'restart' => "{$pm2} restart {$pm2Name} 2>&1 || {$startCmd} 2>&1",
        };

        if ($data['action'] === 'start') {
            file_put_contents($bot->statusFile(), json_encode(['status'=>'starting','qr'=>null,'updated_at'=>now()]));
        }

        \Log::info("BOT CMD: {$cmd}");
        $output = shell_exec($cmd);
        \Log::info("BOT OUTPUT: " . (string)$output);

        if ($data['action'] === 'stop') {
            file_put_contents($bot->statusFile(), json_encode(['status'=>'offline','qr'=>null,'updated_at'=>now()]));
        }

        return response()->json(['ok' => true, 'output' => trim((string)$output)]);
    }

    // ── Reset sesión WhatsApp (cambiar número) ────────────────────
    public function resetSession(Request $request)
    {
        $data = $request->validate(['bot' => 'required|string']);

        $project = app('active_project');
        $bot     = BotInstance::where('project_id', $project->id)
                              ->where('bot_type', $data['bot'])
                              ->firstOrFail();

        $port    = $bot->port;
        $token   = 'wa-bot-secret-2024';
        $url     = "http://127.0.0.1:{$port}/reset-session";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['token' => $token]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            file_put_contents($bot->statusFile(), json_encode(['status'=>'qr','qr'=>null,'updated_at'=>now()]));
            return response()->json(['ok' => true, 'message' => 'Sesión reseteada']);
        }

        return response()->json(['ok' => false, 'message' => 'No se pudo conectar con el bot'], 502);
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
        $pm2Wrapper = '/usr/local/bin/bot-pm2-run';
        $pm2Bin     = trim(shell_exec('which pm2') ?: '/usr/bin/pm2');
        $pm2        = file_exists($pm2Wrapper) ? "sudo {$pm2Wrapper}" : $pm2Bin;
        @shell_exec("{$pm2} delete {$bot->pm2Name()} 2>&1");

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

        if ($request->expectsJson() || $request->wantsJson()) {
            if (!$flow) {
                return response()->json(['ok' => false, 'message' => 'Flujo no encontrado']);
            }
            $flow->load('states.transitions.toState');
            $states = $flow->states->sortBy('sort_order')->map(fn($s) => [
                'id'                 => $s->id,
                'key'                => $s->key,
                'label'              => $s->label,
                'message'            => $s->message,
                'images'             => $s->images ?? [],
                'input_type'         => $s->input_type,
                'sort_order'         => $s->sort_order,
                'validation_pattern' => $s->validation_pattern,
                'validation_min'     => $s->validation_min,
                'validation_max'     => $s->validation_max,
                'validation_error'   => $s->validation_error,
                'transitions'        => $s->transitions->map(fn($t) => [
                    'id'           => $t->id,
                    'trigger'      => $t->trigger,
                    'action'       => $t->action,
                    'action_param' => $t->action_param,
                    'to_state_id'  => $t->to_state_id,
                    'to_state_key' => $t->toState?->key,
                ])->values(),
            ])->values();

            return response()->json([
                'ok'      => true,
                'flow_id' => $flow->id,
                'name'    => $flow->name,
                'states'  => $states,
            ]);
        }

        $allStates = $flow ? $flow->states->load('transitions.toState') : collect();

        return view('bots.flow', compact('project', 'flow', 'allStates', 'botType'));
    }

    // ── Sesiones esperando asesor ─────────────────────────────────
    public function esperaAsesor(Request $request)
    {
        $project = app('active_project');
        $botType = $request->get('bot', 'main');

        $flow = BotFlow::where('project_id', $project->id)
            ->where('bot_type', $botType)
            ->first();

        if (!$flow) {
            return response()->json(['ok' => true, 'sessions' => []]);
        }

        $sessions = BotSession::where('flow_id', $flow->id)
            ->where('current_state', 'esperando_asesor')
            ->orderByDesc('last_activity_at')
            ->get(['wa_number', 'last_activity_at', 'data'])
            ->map(fn($s) => [
                'wa_number'        => $s->wa_number,
                'last_activity_at' => $s->last_activity_at?->toIso8601String(),
                'nombre'           => $s->data['nombre'] ?? null,
            ]);

        return response()->json(['ok' => true, 'sessions' => $sessions]);
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
            'flow_id'            => 'required|integer',
            'key'                => 'required|string|max:60',
            'label'              => 'required|string|max:100',
            'message'            => 'required|string',
            'images'             => 'nullable|array|max:5',
            'input_type'         => 'required|in:text,number,image,location,option',
            'sort_order'         => 'integer',
            'is_active'          => 'boolean',
            'validation_pattern' => 'nullable|string|max:200',
            'validation_min'     => 'nullable|integer',
            'validation_max'     => 'nullable|integer',
            'validation_error'   => 'nullable|string|max:200',
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
            'label'              => 'required|string|max:100',
            'message'            => 'required|string',
            'images'             => 'nullable|array|max:5',
            'input_type'         => 'required|in:text,number,image,location,option',
            'sort_order'         => 'integer',
            'is_active'          => 'boolean',
            'validation_pattern' => 'nullable|string|max:200',
            'validation_min'     => 'nullable|integer',
            'validation_max'     => 'nullable|integer',
            'validation_error'   => 'nullable|string|max:200',
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

    public function stateMove(Request $request, BotState $state)
    {
        $project = app('active_project');
        abort_unless($state->flow->project_id === $project->id, 403);

        $direction = $request->input('direction'); // 'up' | 'down'
        $flowId    = $state->flow_id;
        $states    = BotState::where('flow_id', $flowId)->orderBy('sort_order')->get();

        $index = $states->search(fn($s) => $s->id === $state->id);
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= $states->count()) {
            return response()->json(['ok' => false]);
        }

        $current = $states[$index];
        $swap    = $states[$swapIndex];

        $tmpOrder        = $current->sort_order;
        $current->sort_order = $swap->sort_order ?: $swapIndex;
        $swap->sort_order    = $tmpOrder ?: $index;

        // Asegurar que sort_order sea único
        if ($current->sort_order === $swap->sort_order) {
            $current->sort_order = $index;
            $swap->sort_order    = $swapIndex;
        }

        $current->save();
        $swap->save();

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

    // ── Upload imagen para estado ─────────────────────────────────
    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|max:5120']); // 5MB max
        $path = $request->file('image')->store('bot-images', 'public');
        $url  = asset('storage/' . $path);
        return response()->json(['ok' => true, 'url' => $url, 'path' => $path]);
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

    // ── Recargar flujo en el bot via HTTP ────────────────────────
    private function reloadBotFlow(BotFlow $flow): void
    {
        try {
            $bot  = BotInstance::where('project_id', $flow->project_id)
                                ->where('bot_type', $flow->bot_type)
                                ->first();
            $port = $bot?->port ?? 3001;
            Http::timeout(3)->post("http://127.0.0.1:{$port}/reload", [
                'token' => 'wa-bot-secret-2024',
            ]);
        } catch (\Throwable) {}
    }

    // ── Exportar flujo a JSON local y notificar al bot ───────────
    private function exportFlow(BotFlow $flow): void
    {
        Cache::forget("bot.flow.{$flow->bot_type}");
        $this->reloadBotFlow($flow);
        try {
            $flow->load('states.transitions.toState');
            $states = $flow->states->map(fn($s) => [
                'key'                => $s->key,
                'label'              => $s->label,
                'message'            => $s->message,
                'images'             => $s->images ?? [],
                'input_type'         => $s->input_type,
                'validation_pattern' => $s->validation_pattern,
                'validation_min'     => $s->validation_min,
                'validation_max'     => $s->validation_max,
                'validation_error'   => $s->validation_error,
                'transitions'        => $s->transitions->map(fn($t) => [
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

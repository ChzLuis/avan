<?php

namespace App\Modules\Bots\Ia\Providers;

use App\Modules\Bots\Ia\IaProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Conector para Claude (Anthropic Messages API). */
class AnthropicProvider implements IaProvider
{
    public function __construct(
        private string $apiKey,
        private string $model = 'claude-sonnet-5',
    ) {}

    public function nombre(): string
    {
        return 'anthropic';
    }

    public function chat(array $mensajes, array $opciones = []): string
    {
        // Anthropic separa el system del resto de mensajes.
        $system = '';
        $turns = [];
        foreach ($mensajes as $m) {
            if (($m['role'] ?? '') === 'system') {
                $system .= $m['content'] . "\n";
            } else {
                $turns[] = ['role' => $m['role'], 'content' => $m['content']];
            }
        }

        $res = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout((int) ($opciones['timeout'] ?? 60))->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => $opciones['max_tokens'] ?? 1024,
            'temperature' => $opciones['temperature'] ?? 0.4,
            'system' => trim($system),
            'messages' => $turns,
        ]);

        if (!$res->successful()) {
            throw new RuntimeException('Anthropic error: ' . $res->status() . ' ' . $res->body());
        }

        return trim($res->json('content.0.text') ?? '');
    }
}

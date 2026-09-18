<?php

namespace App\Modules\Bots\Ia\Providers;

use App\Modules\Bots\Ia\IaProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Conector para DeepSeek. La API es compatible con el formato de OpenAI
 * (Chat Completions), solo cambia el host y el modelo.
 */
class DeepSeekProvider implements IaProvider
{
    public function __construct(
        private string $apiKey,
        private string $model = 'deepseek-chat',
    ) {}

    public function nombre(): string
    {
        return 'deepseek';
    }

    public function chat(array $mensajes, array $opciones = []): string
    {
        $res = Http::withToken($this->apiKey)
            ->timeout((int) ($opciones['timeout'] ?? 60))
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => $this->model,
                'messages' => $mensajes,
                'temperature' => $opciones['temperature'] ?? 0.4,
                'max_tokens' => $opciones['max_tokens'] ?? 1024,
            ]);

        if (!$res->successful()) {
            throw new RuntimeException('DeepSeek error: ' . $res->status() . ' ' . $res->body());
        }

        return trim($res->json('choices.0.message.content') ?? '');
    }
}

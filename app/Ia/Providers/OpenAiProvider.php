<?php

namespace App\Ia\Providers;

use App\Ia\IaProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Conector para OpenAI (Chat Completions API). */
class OpenAiProvider implements IaProvider
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4o',
    ) {}

    public function nombre(): string
    {
        return 'openai';
    }

    public function chat(array $mensajes, array $opciones = []): string
    {
        $res = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $mensajes, // OpenAI acepta system/user/assistant tal cual
                'temperature' => $opciones['temperature'] ?? 0.4,
                'max_tokens' => $opciones['max_tokens'] ?? 1024,
            ]);

        if (!$res->successful()) {
            throw new RuntimeException('OpenAI error: ' . $res->status() . ' ' . $res->body());
        }

        return trim($res->json('choices.0.message.content') ?? '');
    }
}

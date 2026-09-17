<?php

namespace App\Ia\Providers;

use App\Ia\VisionProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lectura de documentos con GPT-4o. Solo imagen: la API de chat no admite
 * PDF, asi que el PDF se convierte antes (ver `LectorComprobantes`).
 */
class OpenAiVision implements VisionProvider
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4o',
    ) {}

    public function nombre(): string
    {
        return 'openai';
    }

    public function aceptaPdf(): bool
    {
        return false;
    }

    public function leerDocumento(string $base64, string $mime, string $prompt, array $opciones = []): string
    {
        if ($mime === 'application/pdf') {
            throw new RuntimeException('OpenAI vision no acepta PDF directamente.');
        }

        $res = Http::withToken($this->apiKey)
            ->timeout((int) ($opciones['timeout'] ?? 90))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $this->model,
                'max_tokens'  => (int) ($opciones['max_tokens'] ?? 4096),
                'temperature' => (float) ($opciones['temperature'] ?? 0),
                'messages'    => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text',      'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$base64}"]],
                    ],
                ]],
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('OpenAI vision: '.$res->status().' '.$res->body());
        }

        return trim((string) $res->json('choices.0.message.content'));
    }
}

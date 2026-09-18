<?php

namespace App\Modules\Bots\Ia\Providers;

use App\Modules\Bots\Ia\VisionProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lectura de documentos con Gemini. Acepta imagen y PDF (inlineData).
 */
class GeminiVision implements VisionProvider
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-2.0-flash',
    ) {}

    public function nombre(): string
    {
        return 'gemini';
    }

    public function leerDocumento(string $base64, string $mime, string $prompt, array $opciones = []): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $res = Http::timeout((int) ($opciones['timeout'] ?? 90))
            ->post($url.'?key='.$this->apiKey, [
                'contents' => [[
                    'parts' => [
                        ['inline_data' => ['mime_type' => $mime, 'data' => $base64]],
                        ['text' => $prompt],
                    ],
                ]],
                'generationConfig' => [
                    'temperature'     => (float) ($opciones['temperature'] ?? 0),
                    'maxOutputTokens' => (int) ($opciones['max_tokens'] ?? 4096),
                ],
            ]);

        if (! $res->successful()) {
            throw new RuntimeException('Gemini vision: '.$res->status().' '.$res->body());
        }

        return trim((string) $res->json('candidates.0.content.parts.0.text'));
    }
}

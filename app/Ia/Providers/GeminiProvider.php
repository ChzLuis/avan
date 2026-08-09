<?php

namespace App\Ia\Providers;

use App\Ia\IaProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Conector para Google Gemini (generateContent API).
 * Gemini usa un formato distinto: 'contents' con 'parts', roles user/model,
 * y el system va aparte en 'system_instruction'.
 */
class GeminiProvider implements IaProvider
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-1.5-flash',
    ) {}

    public function nombre(): string
    {
        return 'gemini';
    }

    public function chat(array $mensajes, array $opciones = []): string
    {
        $system = '';
        $contents = [];
        foreach ($mensajes as $m) {
            $role = $m['role'] ?? 'user';
            if ($role === 'system') {
                $system .= $m['content'] . "\n";
                continue;
            }
            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]],
            ];
        }

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $opciones['temperature'] ?? 0.4,
                'maxOutputTokens' => $opciones['max_tokens'] ?? 1024,
            ],
        ];
        if (trim($system) !== '') {
            $body['system_instruction'] = ['parts' => [['text' => trim($system)]]];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $res = Http::timeout(60)->post($url, $body);

        if (!$res->successful()) {
            throw new RuntimeException('Gemini error: ' . $res->status() . ' ' . $res->body());
        }

        return trim($res->json('candidates.0.content.parts.0.text') ?? '');
    }
}

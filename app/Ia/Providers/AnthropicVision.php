<?php

namespace App\Ia\Providers;

use App\Ia\VisionProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lectura de documentos con Claude. Acepta imagen y PDF nativo: un PDF
 * digital no se rasteriza, se envia tal cual y el modelo lee su texto —por
 * eso sale mas fiel que pasarlo antes por una imagen.
 */
class AnthropicVision implements VisionProvider
{
    public function __construct(
        private string $apiKey,
        private string $model = 'claude-sonnet-5',
    ) {}

    public function nombre(): string
    {
        return 'anthropic';
    }

    public function leerDocumento(string $base64, string $mime, string $prompt, array $opciones = []): string
    {
        // El PDF viaja como 'document'; las fotos como 'image'. Mandar un PDF
        // en el bloque de imagen lo rechaza la API.
        $adjunto = $mime === 'application/pdf'
            ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64]]
            : ['type' => 'image',    'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $base64]];

        $res = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout((int) ($opciones['timeout'] ?? 90))
          ->post('https://api.anthropic.com/v1/messages', [
              'model'       => $this->model,
              'max_tokens'  => (int) ($opciones['max_tokens'] ?? 4096),
              // Temperatura 0: leer un comprobante es transcribir, no redactar.
              'temperature' => (float) ($opciones['temperature'] ?? 0),
              'messages'    => [[
                  'role'    => 'user',
                  'content' => [$adjunto, ['type' => 'text', 'text' => $prompt]],
              ]],
          ]);

        if (! $res->successful()) {
            throw new RuntimeException('Anthropic vision: '.$res->status().' '.$res->body());
        }

        return trim((string) $res->json('content.0.text'));
    }
}

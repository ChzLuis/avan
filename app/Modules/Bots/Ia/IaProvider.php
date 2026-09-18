<?php

namespace App\Modules\Bots\Ia;

/**
 * Contrato común de todos los proveedores de IA.
 *
 * Cada proveedor (OpenAI, Gemini, Anthropic, DeepSeek) implementa esto.
 * El resto de la app llama a través de la fachada App\Modules\Bots\Ia\IA y NUNCA conoce
 * al proveedor concreto → agregar un 5º proveedor es solo un archivo nuevo.
 */
interface IaProvider
{
    /**
     * Envía una conversación al modelo y devuelve el texto de respuesta.
     *
     * @param array  $mensajes  [['role' => 'system'|'user'|'assistant', 'content' => '...'], ...]
     * @param array  $opciones  ['temperature' => float, 'max_tokens' => int, ...]
     */
    public function chat(array $mensajes, array $opciones = []): string;

    /** Identificador corto: 'openai' | 'gemini' | 'anthropic' | 'deepseek'. */
    public function nombre(): string;
}

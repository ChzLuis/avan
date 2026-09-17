<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proveedor de IA activo
    |--------------------------------------------------------------------------
    | Cuál conector usa SalesPilot. Cambiar aquí (o en .env con IA_PROVIDER)
    | intercambia el motor sin tocar código: 'anthropic'|'openai'|'gemini'|'deepseek'.
    */
    'provider' => env('IA_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | Interprete del Bot Comercial
    |--------------------------------------------------------------------------
    | Timeout corto a proposito: un cliente de WhatsApp no espera 60 segundos
    | para acabar igual en el motor estandar. Si el proveedor no responde a
    | tiempo, el mensaje sigue por reglas como si la IA no existiera.
    */
    'interprete' => [
        'timeout'    => (int) env('IA_INTERPRETE_TIMEOUT', 8),
        'max_tokens' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración por proveedor
    |--------------------------------------------------------------------------
    | Cada uno con su API key (en .env) y su modelo por defecto (override en .env).
    */
    /*
    | Lector de comprobantes (imagen/PDF -> datos). El motor por defecto
    | cuando el negocio no elige uno; la clave sale del ajuste del proyecto
    | (`lector_api_key`) y, si no la tiene, de la global de su motor.
    */
    'lector' => [
        'motor' => env('LECTOR_MOTOR', 'anthropic'),
    ],

    'providers' => [

        'anthropic' => [
            'key'   => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
        ],

        'openai' => [
            'key'   => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o'),
        ],

        'gemini' => [
            'key'   => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        ],

        'deepseek' => [
            'key'   => env('DEEPSEEK_API_KEY'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        ],

    ],

];

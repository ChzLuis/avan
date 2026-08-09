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
    | Configuración por proveedor
    |--------------------------------------------------------------------------
    | Cada uno con su API key (en .env) y su modelo por defecto (override en .env).
    */
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

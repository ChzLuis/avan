<?php

use App\Http\Controllers\Api\CopilotController;
use Illuminate\Support\Facades\Route;

/*
| API del BIXO Copilot (extensión de Chrome).
| Auth por token de proyecto en el header X-Copilot-Token.
| El cerebro es el CRM de BIXO: aquí se clasifica y persiste el lead.
*/
Route::prefix('copilot')->group(function () {
    Route::get('/status', [CopilotController::class, 'status']);
    Route::post('/contexto', [CopilotController::class, 'contexto']);
    Route::post('/clasificar', [CopilotController::class, 'clasificar']);
    Route::post('/respuesta', [CopilotController::class, 'respuesta']);
    Route::post('/nota', [CopilotController::class, 'nota']);

    // API de contexto: la "puerta a los datos" del proyecto (bot / IA / flujos)
    Route::post('/buscar-producto', [CopilotController::class, 'buscarProducto']);
    Route::get('/catalogo', [CopilotController::class, 'catalogo']);
    Route::get('/catalogo-pos', [CopilotController::class, 'catalogoPos']);
    Route::post('/cliente', [CopilotController::class, 'clienteInfo']);
    Route::post('/pedidos', [CopilotController::class, 'pedidos']);
    Route::post('/contexto-ia', [CopilotController::class, 'contextoIa']);
    Route::get('/respuestas-rapidas', [CopilotController::class, 'respuestasRapidas']);
    Route::post('/cliente/etapa', [CopilotController::class, 'cambiarEtapa']);
    Route::post('/cliente/etiquetas', [CopilotController::class, 'guardarEtiquetas']);
    Route::post('/cliente/nota', [CopilotController::class, 'agregarNota']);
    Route::post('/cliente/seguimiento', [CopilotController::class, 'programarSeguimiento']);
    Route::post('/cliente/ficha', [CopilotController::class, 'fichaContacto']);
    Route::post('/cliente/ficha/guardar', [CopilotController::class, 'guardarFicha']);
    // Vender desde el chat (pedido / cotización reales)
    Route::post('/venta/pedido', [\App\Http\Controllers\Api\VentaExtensionController::class, 'crearPedido']);
    Route::post('/venta/cotizacion', [\App\Http\Controllers\Api\VentaExtensionController::class, 'crearCotizacion']);
    Route::post('/venta/encuesta', [\App\Http\Controllers\Api\VentaExtensionController::class, 'encuestaPostventa']);
});

// Puente WhatsApp → motor de bots (lo llama Baileys por cada mensaje entrante)
Route::post('/bot/inbound', [\App\Http\Controllers\Api\BotWebhookController::class, 'inbound']);

// El conector Baileys reporta su estado + QR (para mostrarlo en el constructor)
Route::post('/bot/wa-status', [\App\Http\Controllers\Comunicaciones\BotBuilderPortalController::class, 'waPush']);

// Aprobación de pagos Yape/Plin reportados por el bot (extensión y panel)
Route::prefix('pagos')->group(function () {
    Route::post('/pendientes', [\App\Http\Controllers\Api\PagoController::class, 'pendientes']);
    Route::post('/aprobar',    [\App\Http\Controllers\Api\PagoController::class, 'aprobar']);
    Route::post('/rechazar',   [\App\Http\Controllers\Api\PagoController::class, 'rechazar']);
});

// Sincronización de chats desde la extensión (WhatsApp Web → CRM de BIXO)
Route::post('/wa/sync', [\App\Http\Controllers\Api\WhatsappSyncController::class, 'sync']);

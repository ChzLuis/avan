<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios de carritos abandonados: cada hora
Schedule::command('carts:remind --hours=1')->hourly();

// Expirar demos vencidas: cada día a las 2am
Schedule::job(new \App\Jobs\ExpireDemos)->dailyAt('02:00');

// Sincronización de catálogos externos (SISKOTE y futuros conectores): el
// comando decide por sí solo qué integraciones les toca sincronizar según
// su sync_interval_minutes — sin condición por proveedor aquí.
Schedule::command('catalog:sync')->everyFifteenMinutes()->withoutOverlapping();

// Ningun comprobante muere en silencio: lo que fallo al enviarse se reintenta
// cada hora mientras siga dentro del plazo de 3 dias de SUNAT.
Schedule::command('facturacion:reintentar')->hourly()->withoutOverlapping();

// CRM: recordatorio push de las acciones que vencen (una vez por accion).
Schedule::command('crm:recordar-acciones')->everyMinute()->withoutOverlapping();

// CRM: aviso previo por WhatsApp (X minutos antes de la hora de la accion).
Schedule::command('crm:avisar-acciones')->everyMinute()->withoutOverlapping();

// Conciliación completa nocturna: detecta productos que ya no existen en el
// ERP y los marca huérfanos (nunca en una corrida incremental parcial).
Schedule::command('catalog:sync --full')->dailyAt('03:30')->withoutOverlapping();

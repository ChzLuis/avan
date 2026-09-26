<?php

/**
 * Monta la logistica de MegaHogar: sus 6 locales y una ubicacion por cada uno.
 *
 * Tres almacenes y tres tiendas. La distincion importa para el traslado: mover
 * algo de un almacen a una tienda sale del local, y eso en Peru necesita guia
 * de remision (motivo 04), asi que el sistema tiene que saber que son locales
 * distintos y no estantes del mismo sitio.
 *
 * Cada local arranca con una ubicacion general para que se pueda operar desde
 * el primer dia; despues se afinan estantes y pasillos desde el panel.
 *
 * Correr:  php scripts/megahogar_logistica.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\Sede;
use App\Modules\Inventario\Models\WarehouseLocation;

$project = Project::where('name', 'like', '%MegaHogar%')->firstOrFail();
$pid = $project->id;
echo "Proyecto: {$project->name} (#{$pid})\n";

/** [nombre del local, tipo de ubicacion inicial, codigo] */
$locales = [
    ['Almacén Santa Ana',  'deposito', 'SAN'],
    ['Almacén Palomino',   'deposito', 'PAL'],
    ['Almacén Ascención',  'deposito', 'ASC'],
    ['Tienda Paraíso',     'zona',     'PAR'],
    ['Tienda Muebles',     'zona',     'MUE'],
    ['Tienda Artefactos',  'zona',     'ART'],
];

$hechas = 0;
$ubis = 0;

foreach ($locales as $i => [$nombre, $tipo, $codigo]) {
    $sede = Sede::firstOrCreate(
        ['project_id' => $pid, 'name' => $nombre],
        ['is_active' => true]
    );
    if ($sede->wasRecentlyCreated) {
        $hechas++;
    }

    // Una ubicacion general por local: sin al menos una, no se puede recibir
    // ni mover nada, y el local quedaria de adorno.
    $ubicacion = WarehouseLocation::firstOrCreate(
        ['project_id' => $pid, 'codigo' => $codigo.'-GEN'],
        [
            'sede_id' => $sede->id,
            'nombre' => 'Zona general',
            'tipo' => $tipo,
            'is_active' => true,
            'sort_order' => ($i + 1) * 10,
        ]
    );
    if ($ubicacion->wasRecentlyCreated) {
        $ubis++;
    } elseif (! $ubicacion->sede_id) {
        // Si la ubicacion ya existia suelta, se cuelga de su local.
        $ubicacion->update(['sede_id' => $sede->id]);
    }
}

echo "Locales creados: {$hechas} (total {$project->id}: ".Sede::where('project_id', $pid)->count().")\n";
echo "Ubicaciones creadas: {$ubis}\n\n";

foreach (Sede::where('project_id', $pid)->orderBy('name')->get() as $s) {
    $n = WarehouseLocation::where('sede_id', $s->id)->count();
    printf("  %-24s %d ubicación(es)%s\n", $s->name, $n, $s->manager ? ' · '.$s->manager : '');
}

$sueltas = WarehouseLocation::where('project_id', $pid)->whereNull('sede_id')->count();
if ($sueltas > 0) {
    echo "\n  Ojo: {$sueltas} ubicación(es) sin local asignado.\n";
}

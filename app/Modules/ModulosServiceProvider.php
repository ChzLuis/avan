<?php

namespace App\Modules;

use Illuminate\Support\ServiceProvider;

/**
 * Da de alta lo que cada modulo de app/Modules/ aporta al framework.
 *
 * Hoy: las vistas. Cada carpeta `app/Modules/<Nombre>/Views` queda disponible
 * con el espacio de nombres del modulo en minusculas: la vista
 * `app/Modules/Personas/Views/hr/employees.blade.php` se pinta con
 * `view('personas::hr.employees')`.
 *
 * Es generico a proposito: al mover un modulo nuevo no hay que registrar
 * nada aqui, basta con que exista su carpeta Views/. Las clases PHP no
 * necesitan registro: el PSR-4 `App\` -> `app/` ya resuelve
 * `App\Modules\Personas\Controllers\HRController`.
 */
class ModulosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (glob(app_path('Modules/*/Views'), GLOB_ONLYDIR) ?: [] as $dir) {
            $modulo = strtolower(basename(dirname($dir)));
            $this->loadViewsFrom($dir, $modulo);
        }
    }
}

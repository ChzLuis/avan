<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Shell del Workspace.
 *
 * Antes renderizaba SIEMPRE `layouts.app` (el shell del panel, con su propio
 * `#admin-sidebar`). Como la unificación migró solo una parte de las pantallas
 * al shell comercial, el usuario saltaba de menú al navegar dentro de la
 * MISMA cara: Mi negocio con un menú y Productos/Sedes/Certificados con otro.
 *
 * Ahora hay UN SOLO shell para todo el Workspace: cuando hay negocio activo
 * se delega en el shell comercial (el mismo sidebar por cara), y las decenas
 * de vistas que declaran `<x-app-layout>` lo heredan sin tocarlas ni duplicar
 * Blade. Sin negocio activo (pantallas previas a elegir uno) se conserva el
 * shell anterior, que no depende de un proyecto.
 */
class AppLayout extends Component
{
    public function render(): View
    {
        $project = app()->bound('active_project') ? app('active_project') : null;

        if ($project) {
            return view('comercial.layouts.app', [
                'project'   => $project,
                'pageTitle' => '',
            ]);
        }

        return view('layouts.app');
    }
}

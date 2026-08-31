<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Shell del PANEL (/bixoadmin): su propio encabezado, su selector de negocio
 * y su menú. NO se delega en el shell comercial: la cara de Configuración
 * conserva su diseño, y la de Operación el suyo (decisión del usuario
 * 2026-08-30 — llevarle el header y las alertas de ventas al panel no era
 * lo pedido; lo pedido era no saltar de menú entre pantallas, que se resuelve
 * sirviendo TODA la configuración con este mismo shell).
 */
class AppLayout extends Component
{
    public function render(): View
    {
        return view('layouts.app');
    }
}

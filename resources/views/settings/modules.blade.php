<x-app-layout>
<x-slot name="slot">
<div class="flex flex-1 overflow-hidden">

{{-- PANEL IZQUIERDO — categorías --}}
<div class="list-panel flex-shrink-0">
    <div class="px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="text-sm font-semibold text-gray-700">Centro de control</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <div class="overflow-y-auto flex-1">
        @php
        $cats = [
            ['key'=>'plataformas', 'label'=>'Plataformas digitales', 'desc'=>'Tienda, Work, Portal',
             'icon'=>'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2'],
            ['key'=>'ventas',     'label'=>'Ventas y comercial',    'desc'=>'POS, pedidos, facturas',
             'icon'=>'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['key'=>'clientes',   'label'=>'Clientes y CRM',        'desc'=>'Agenda, clientes, grupos',
             'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['key'=>'empresa',    'label'=>'Empresa y RRHH',        'desc'=>'Empleados, sedes, nómina',
             'icon'=>'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['key'=>'logistica',  'label'=>'Logística y almacén',   'desc'=>'Inventario, despacho',
             'icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['key'=>'integraciones','label'=>'Integraciones',       'desc'=>'Pagos, WhatsApp, Google',
             'icon'=>'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
        ];
        $current = request('cat', 'plataformas');
        @endphp
        @foreach($cats as $cat)
        <a href="{{ route('settings.modules', ['project'=>$project->id]) }}?cat={{ $cat['key'] }}"
           class="list-item {{ $current === $cat['key'] ? 'selected' : '' }}">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $current === $cat['key'] ? 'bg-indigo-600' : 'bg-gray-100' }}">
                <svg class="w-4 h-4 {{ $current === $cat['key'] ? 'text-white' : 'text-gray-500' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $cat['icon'] }}"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ $cat['label'] }}</p>
                <p class="text-xs text-gray-400">{{ $cat['desc'] }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>

{{-- PANEL DERECHO — módulos de la categoría --}}
<div class="detail-panel">

    @if(session('success'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" x-cloak
         class="m-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-2.5 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @php
    $cat = request('cat', 'plataformas');
    $pid = $project->id;

    // Helper para verificar si un módulo está activo
    $isActive = fn(string $key) => in_array($key, $activeModuleIds ?? [])
        || $project->modules()->where('key', $key)->wherePivot('is_active', true)->exists();

    // Definición completa de módulos por categoría
    $moduleDefs = [

        'plataformas' => [
            'title' => 'Plataformas digitales',
            'desc'  => 'Activa los productos digitales que verán tus clientes y tu equipo',
            'items' => [
                [
                    'key'     => 'store',
                    'label'   => 'Avan Store',
                    'desc'    => 'Catálogo público de ventas online. Tus clientes pueden ver productos, hacer pedidos y pagar desde cualquier dispositivo.',
                    'badge'   => 'Catálogo público',
                    'color'   => '#0ea5e9',
                    'icon'    => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                    'url'     => $project->slug ? '/avan/public/p/'.$project->slug : null,
                    'config'  => route('settings.design', ['project'=>$pid]).'?s=tienda',
                ],
                [
                    'key'     => 'orders',
                    'label'   => 'Avan Work',
                    'desc'    => 'Panel operativo para tu equipo: POS, pedidos, cotizaciones, facturas, clientes y agenda.',
                    'badge'   => 'Operaciones internas',
                    'color'   => '#6366f1',
                    'icon'    => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2',
                    'url'     => route('pos.index', ['project'=>$pid]),
                    'config'  => null,
                ],
                [
                    'key'     => 'docs',
                    'label'   => 'Avan Docs',
                    'desc'    => 'Portal de clientes. Tus clientes pueden ver sus pedidos, cotizaciones y facturas desde un enlace personalizado.',
                    'badge'   => 'Portal de clientes',
                    'color'   => '#10b981',
                    'icon'    => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'url'     => null,
                    'config'  => null,
                    'soon'    => true,
                ],
                [
                    'key'     => 'pages',
                    'label'   => 'Avan Pages',
                    'desc'    => 'Página web del negocio con secciones personalizables: inicio, servicios, equipo, contacto y más.',
                    'badge'   => 'Página web',
                    'color'   => '#f59e0b',
                    'icon'    => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'url'     => null,
                    'config'  => null,
                    'soon'    => true,
                ],
            ],
        ],

        'ventas' => [
            'title' => 'Ventas y comercial',
            'desc'  => 'Módulos del área comercial disponibles en Avan Work',
            'items' => [
                [
                    'key'   => 'orders',
                    'label' => 'POS — Punto de venta',
                    'desc'  => 'Caja rápida para ventas presenciales. Registra ventas, cobra y genera ticket en segundos.',
                    'badge' => 'POS',
                    'color' => '#6366f1',
                    'icon'  => 'M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1-4l-3 3m0 0l-3-3m3 3V4',
                    'config'=> route('pos.index', ['project'=>$pid]),
                ],
                [
                    'key'   => 'orders',
                    'label' => 'Pedidos',
                    'desc'  => 'Gestión de pedidos de clientes. Control de estados: nuevo, en proceso, listo, entregado.',
                    'badge' => 'Pedidos',
                    'color' => '#f59e0b',
                    'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    'config'=> route('orders', ['project'=>$pid]),
                ],
                [
                    'key'   => 'quotes',
                    'label' => 'Cotizaciones',
                    'desc'  => 'Genera y envía cotizaciones profesionales a tus clientes. Convierte cotización en pedido con un clic.',
                    'badge' => 'Cotizaciones',
                    'color' => '#10b981',
                    'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'config'=> route('quotes', ['project'=>$pid]),
                ],
                [
                    'key'   => 'invoices',
                    'label' => 'Facturas y boletas',
                    'desc'  => 'Emite boletas y facturas electrónicas. Preparado para integración con SUNAT/OSE (Nubefact, Efact).',
                    'badge' => 'Facturación',
                    'color' => '#8b5cf6',
                    'icon'  => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z',
                    'config'=> route('invoices.index', ['project'=>$pid]),
                ],
                [
                    'key'   => 'catalog',
                    'label' => 'Productos y catálogo',
                    'desc'  => 'Gestiona tu catálogo de productos y servicios. Precios, categorías, fotos e inventario.',
                    'badge' => 'Catálogo',
                    'color' => '#0ea5e9',
                    'icon'  => 'M4 6h16M4 10h16M4 14h16M4 18h16',
                    'config'=> route('catalog', ['project'=>$pid]),
                ],
            ],
        ],

        'clientes' => [
            'title' => 'Clientes y CRM',
            'desc'  => 'Herramientas para gestionar la relación con tus clientes',
            'items' => [
                [
                    'key'   => 'clients',
                    'label' => 'Base de clientes',
                    'desc'  => 'Directorio completo de clientes con historial de compras, contacto y documentos.',
                    'badge' => 'CRM',
                    'color' => '#8b5cf6',
                    'icon'  => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                    'config'=> route('clients', ['project'=>$pid]),
                ],
                [
                    'key'   => 'agenda',
                    'label' => 'Agenda y citas',
                    'desc'  => 'Sistema de reservas y citas. Tus clientes pueden agendar desde el catálogo público.',
                    'badge' => 'Agenda',
                    'color' => '#ec4899',
                    'icon'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'config'=> route('agenda', ['project'=>$pid]),
                ],
                [
                    'key'   => 'groups',
                    'label' => 'Grupos de clientes',
                    'desc'  => 'Segmenta tus clientes en grupos para enviar promociones y gestionar precios diferenciados.',
                    'badge' => 'Segmentación',
                    'color' => '#f97316',
                    'icon'  => 'M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z',
                    'config'=> route('groups.index', ['project'=>$pid, 'type'=>'client']),
                ],
                [
                    'key'   => 'loyalty',
                    'label' => 'Fidelización y puntos',
                    'desc'  => 'Programa de puntos y recompensas para retener a tus mejores clientes.',
                    'badge' => 'Fidelización',
                    'color' => '#eab308',
                    'icon'  => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                    'soon'  => true,
                ],
            ],
        ],

        'empresa' => [
            'title' => 'Empresa y RRHH',
            'desc'  => 'Gestión interna del equipo y la estructura empresarial',
            'items' => [
                [
                    'key'   => 'hr',
                    'label' => 'Empleados',
                    'desc'  => 'Registro de empleados, cargos, contratos y documentos del personal.',
                    'badge' => 'RRHH',
                    'color' => '#f97316',
                    'icon'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                    'config'=> route('hr.employees.index', ['project'=>$pid]),
                ],
                [
                    'key'   => 'sedes',
                    'label' => 'Sedes y sucursales',
                    'desc'  => 'Gestiona múltiples sedes o puntos de venta de tu negocio.',
                    'badge' => 'Sedes',
                    'color' => '#64748b',
                    'icon'  => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
                    'config'=> route('sedes.index', ['project'=>$pid]),
                ],
                [
                    'key'   => 'payroll',
                    'label' => 'Planilla y nómina',
                    'desc'  => 'Cálculo de sueldos, descuentos, gratificaciones y generación de boletas de pago.',
                    'badge' => 'Nómina',
                    'color' => '#10b981',
                    'icon'  => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    'soon'  => true,
                ],
                [
                    'key'   => 'attendance',
                    'label' => 'Asistencia y turnos',
                    'desc'  => 'Control de asistencia, horarios y turnos del personal.',
                    'badge' => 'Asistencia',
                    'color' => '#0ea5e9',
                    'icon'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'soon'  => true,
                ],
            ],
        ],

        'logistica' => [
            'title' => 'Logística y almacén',
            'desc'  => 'Control de inventario, despacho y proveedores',
            'items' => [
                [
                    'key'   => 'inventory',
                    'label' => 'Inventario',
                    'desc'  => 'Control de stock en tiempo real. Alertas de bajo inventario, entradas y salidas.',
                    'badge' => 'Inventario',
                    'color' => '#0ea5e9',
                    'icon'  => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                    'soon'  => true,
                ],
                [
                    'key'   => 'proveedores',
                    'label' => 'Proveedores',
                    'desc'  => 'Directorio de proveedores, órdenes de compra y seguimiento de entregas.',
                    'badge' => 'Compras',
                    'color' => '#6366f1',
                    'icon'  => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                    'config'=> route('proveedores.index', ['project'=>$pid]),
                ],
                [
                    'key'   => 'logistics',
                    'label' => 'Despacho y envíos',
                    'desc'  => 'Gestión de despachos, guías de remisión y seguimiento de entregas a domicilio.',
                    'badge' => 'Despacho',
                    'color' => '#f59e0b',
                    'icon'  => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
                    'soon'  => true,
                ],
            ],
        ],

        'integraciones' => [
            'title' => 'Integraciones externas',
            'desc'  => 'Conecta Avan con servicios de pago, comunicación y más',
            'items' => [
                [
                    'key'   => 'payments',
                    'label' => 'Pagos en línea',
                    'desc'  => 'Culqi (Perú) y Mercado Pago (LATAM). Acepta tarjetas, Yape, Plin y transferencias.',
                    'badge' => 'Pagos',
                    'color' => '#10b981',
                    'icon'  => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                    'config'=> route('settings.design', ['project'=>$pid]).'?s=payments',
                ],
                [
                    'key'   => 'whatsapp',
                    'label' => 'WhatsApp Business API',
                    'desc'  => 'Envía notificaciones de pedidos, cotizaciones y confirmaciones por WhatsApp automáticamente.',
                    'badge' => 'WhatsApp',
                    'color' => '#22c55e',
                    'icon'  => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                    'soon'  => true,
                ],
                [
                    'key'   => 'sunat',
                    'label' => 'SUNAT — Facturación electrónica',
                    'desc'  => 'Integración con OSE (Nubefact, Efact). Emite comprobantes válidos ante SUNAT automáticamente.',
                    'badge' => 'SUNAT',
                    'color' => '#dc2626',
                    'icon'  => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'soon'  => true,
                ],
                [
                    'key'   => 'google',
                    'label' => 'Google Calendar',
                    'desc'  => 'Sincroniza las citas de tu agenda con Google Calendar automáticamente.',
                    'badge' => 'Google',
                    'color' => '#4285F4',
                    'icon'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'soon'  => true,
                ],
            ],
        ],
    ];

    $section   = $moduleDefs[$cat] ?? $moduleDefs['plataformas'];
    $allModuleKeys = $allModules->pluck('id', 'key')->toArray();
    @endphp

    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
        <h2 class="font-semibold text-gray-800">{{ $section['title'] }}</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $section['desc'] }}</p>
    </div>

    {{-- Cards de módulos --}}
    <div class="flex-1 overflow-y-auto p-6">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 max-w-4xl">
            @foreach($section['items'] as $item)
            @php
                $moduleId  = $allModuleKeys[$item['key']] ?? null;
                $enabled   = $moduleId && in_array($moduleId, $activeModuleIds);
                $isSoon    = $item['soon'] ?? false;
            @endphp
            <div class="bg-white border rounded-2xl p-5 flex flex-col gap-4 transition-all
                        {{ $enabled ? 'border-indigo-200 shadow-sm shadow-indigo-100' : 'border-gray-200' }}
                        {{ $isSoon ? 'opacity-60' : '' }}">

                {{-- Top: icono + badge + toggle --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background: {{ $item['color'] }}20; border: 1px solid {{ $item['color'] }}30">
                            <svg class="w-5 h-5" style="color: {{ $item['color'] }}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="{{ $item['icon'] }}"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">{{ $item['label'] }}</p>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full"
                                  style="background: {{ $item['color'] }}15; color: {{ $item['color'] }}">
                                {{ $item['badge'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Toggle --}}
                    @if(!$isSoon && $moduleId)
                    <form method="POST"
                          action="{{ route('settings.modules.update', ['project'=>$pid]) }}"
                          x-data="{ on: {{ $enabled ? 'true' : 'false' }} }">
                        @csrf
                        <input type="hidden" name="module_key" value="{{ $item['key'] }}">
                        <input type="hidden" name="module_id" value="{{ $moduleId }}">
                        <input type="hidden" name="enabled" :value="on ? '1' : '0'">
                        <input type="hidden" name="redirect_cat" value="{{ $cat }}">
                        <button type="submit" @click="on = !on"
                                :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                                class="relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 flex-shrink-0">
                            <span :class="on ? 'translate-x-7' : 'translate-x-1'"
                                  class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 flex-shrink-0"></span>
                        </button>
                    </form>
                    @elseif($isSoon)
                    <span class="text-[10px] bg-amber-50 text-amber-600 border border-amber-200 px-2 py-1 rounded-full font-semibold flex-shrink-0">
                        Próximamente
                    </span>
                    @endif
                </div>

                {{-- Descripción --}}
                <p class="text-xs text-gray-500 leading-relaxed">{{ $item['desc'] }}</p>

                {{-- Footer: estado + acciones --}}
                <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                    <div class="flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 rounded-full {{ $isSoon ? 'bg-amber-400' : ($enabled ? 'bg-green-500' : 'bg-gray-300') }}"></div>
                        <span class="text-[11px] text-gray-400">
                            {{ $isSoon ? 'No disponible aún' : ($enabled ? 'Activo' : 'Inactivo') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!$isSoon && isset($item['config']) && $item['config'] && $enabled)
                        <a href="{{ $item['config'] }}"
                           class="text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Configurar
                        </a>
                        @endif
                        @if(!$isSoon && isset($item['url']) && $item['url'] && $enabled)
                        <a href="{{ $item['url'] }}" target="_blank"
                           class="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Abrir
                        </a>
                        @endif
                    </div>
                </div>

            </div>
            @endforeach
        </div>
    </div>

</div>

</div>
</x-slot>
</x-app-layout>

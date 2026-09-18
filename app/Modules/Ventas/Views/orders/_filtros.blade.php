{{--
    Vistas rápidas + búsqueda + filtros combinables.

    Las vistas rápidas NO son un sistema paralelo: escriben sobre el mismo
    objeto `f` que los desplegables, así que un KPI, un chip y un select
    siempre acaban en el mismo estado.

    En <768 los filtros NO ocupan tres filas permanentes: se reducen a un botón
    con el número de activos y se abren en hoja inferior. En una pantalla de
    390 px, seis selects fijos dejaban el listado —que es lo que se viene a
    ver— por debajo del pliegue.
--}}
<div class="flex-shrink-0 bg-white border-b border-gray-200">

    {{-- Vistas rápidas. El degradado del borde derecho avisa de que hay más
         a la derecha: sin él, en móvil parece que la lista termina en "Hoy". --}}
    <div class="relative">
        <div class="flex gap-1 px-4 pt-2 overflow-x-auto scrollbar-none" style="scrollbar-width:none">
            <template x-for="v in vistas" :key="v.key">
                <button @click="aplicarVista(v.key)"
                        :class="vistaActiva === v.key
                            ? 'border-indigo-600 text-indigo-700'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="px-3 py-2 text-xs font-semibold border-b-2 whitespace-nowrap transition flex-shrink-0">
                    <span x-text="v.label"></span>
                </button>
            </template>
            <span class="w-6 flex-shrink-0 md:hidden" aria-hidden="true"></span>
        </div>
        <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-8 md:hidden"
             style="background:linear-gradient(to right,rgba(255,255,255,0),#fff)" aria-hidden="true"></div>
    </div>

    {{-- ══ Barra: escritorio y tablet ══ --}}
    <div class="flex items-center gap-2 px-4 py-2 flex-wrap">

        {{-- El buscador sigue siendo usable en 768: mantiene ancho propio y
             min-w-0 para poder encogerse sin desbordar la barra. --}}
        <div class="relative flex-1 min-w-0" style="max-width:320px;min-width:180px">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/>
            </svg>
            <input type="search" x-model="search"
                   placeholder="Buscar cliente, teléfono o #ID…"
                   aria-label="Buscar pedidos"
                   class="w-full min-w-0 pl-8 pr-2 py-1.5 text-xs border border-gray-200 rounded-lg
                          focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400">
        </div>

        {{-- Botón de filtros: solo móvil --}}
        <button @click="hojaFiltros = true"
                class="md:hidden ped-btn ped-btn-ghost"
                :aria-label="'Filtros, ' + filtrosActivos + ' activos'">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" d="M3 5h18M6 12h12M10 19h4"/>
            </svg>
            Filtros
            <span x-show="filtrosActivos" x-cloak
                  class="ml-0.5 px-1.5 rounded-full bg-indigo-600 text-white text-[10px] font-bold"
                  x-text="filtrosActivos"></span>
        </button>

        {{-- Selects: ocultos en móvil, donde viven en la hoja inferior --}}
        <div class="hidden md:flex items-center gap-2 flex-wrap">
            @include('ventas::orders._filtros-campos')
        </div>

        <span class="text-[11px] text-gray-400 ml-auto flex-shrink-0 hidden sm:inline">
            <span x-text="filtered.length"></span> de <span x-text="orders.length"></span>
        </span>
    </div>
</div>

{{-- ══ Hoja inferior: solo móvil ══ --}}
<div x-show="hojaFiltros" x-cloak class="md:hidden fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-label="Filtros">
    <div class="absolute inset-0" style="background:rgba(15,23,42,.4)" @click="hojaFiltros=false"></div>
    <div class="absolute left-0 right-0 bottom-0 bg-white rounded-t-2xl p-4 space-y-3 max-h-[80vh] overflow-y-auto"
         @keydown.escape.window="hojaFiltros=false">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900">Filtros</h3>
            <button @click="hojaFiltros=false" class="text-gray-400 text-lg px-2" aria-label="Cerrar filtros">&times;</button>
        </div>
        <div class="grid grid-cols-1 gap-2">
            @include('ventas::orders._filtros-campos')
        </div>
        <div class="flex gap-2 pt-1">
            <button @click="limpiarFiltros()" class="ped-btn ped-btn-ghost flex-1 justify-center">Limpiar</button>
            <button @click="hojaFiltros=false" class="ped-btn ped-btn-primary flex-1 justify-center">
                Ver <span x-text="filtered.length"></span>
            </button>
        </div>
    </div>
</div>

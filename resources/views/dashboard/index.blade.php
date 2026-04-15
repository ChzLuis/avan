<x-app-layout>
<x-slot name="slot">

<div class="flex flex-1 overflow-hidden" x-data="{ mv: 'detail' }">

{{-- ===== PANEL 2: LISTA (stats + actividad reciente) ===== --}}
<div class="list-panel flex-col" style="width:340px;" :class="{ 'mp-hidden': mv === 'detail' }">
    {{-- Header del panel --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white">
        <h2 class="text-sm font-semibold text-gray-700">Resumen</h2>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400">{{ now()->format('d/m/Y') }}</span>
            <button @click="mv = 'detail'" class="md:hidden text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="overflow-y-auto flex-1 p-4 space-y-3">
        {{-- KPIs --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-indigo-50 rounded-lg p-3">
                <p class="text-xs text-indigo-500 font-medium">Pedidos hoy</p>
                <p class="text-2xl font-bold text-indigo-700">{{ $stats['orders_today'] }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <p class="text-xs text-green-500 font-medium">Cotizaciones</p>
                <p class="text-2xl font-bold text-green-700">{{ $stats['quotes_pending'] }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-3">
                <p class="text-xs text-blue-500 font-medium">Reservas</p>
                <p class="text-2xl font-bold text-blue-700">{{ $stats['appointments_today'] }}</p>
            </div>
            <div class="bg-purple-50 rounded-lg p-3">
                <p class="text-xs text-purple-500 font-medium">Clientes</p>
                <p class="text-2xl font-bold text-purple-700">{{ $stats['total_clients'] }}</p>
            </div>
        </div>

        {{-- Actividad reciente --}}
        <div class="mt-4">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Actividad reciente</h3>
            @forelse($recentActivity as $activity)
            <div class="list-item flex-col items-start gap-1 py-2.5">
                <div class="flex items-center gap-2 w-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 flex-shrink-0"></span>
                    <span class="text-sm text-gray-700 flex-1">{{ $activity['text'] }}</span>
                </div>
                <span class="text-xs text-gray-400 ml-3.5">{{ $activity['time'] }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 py-4 text-center">Sin actividad reciente</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ===== PANEL 3: DETALLE (accesos rápidos y módulos) ===== --}}
<div class="detail-panel" :class="{ 'mp-active': mv === 'detail' }">
    {{-- Tabs --}}
    <div x-data="{ tab: 'overview' }">
        <div class="flex items-center border-b border-gray-200 px-4 bg-white">
            <button @click="mv = 'list'" class="md:hidden mr-2 text-gray-400 hover:text-gray-600 flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <button @click="tab='overview'" :class="tab==='overview' ? 'detail-tab active' : 'detail-tab'">Vista general</button>
            <button @click="tab='pending'" :class="tab==='pending' ? 'detail-tab active' : 'detail-tab'">Pendientes</button>
            <button @click="tab='shortcuts'" :class="tab==='shortcuts' ? 'detail-tab active' : 'detail-tab'">Accesos rápidos</button>
        </div>

        <div class="flex-1 overflow-y-auto p-5">

            {{-- Tab: Vista general --}}
            <div x-show="tab==='overview'">
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Bienvenido, {{ auth()->user()->name }}</h2>
                <p class="text-sm text-gray-500 mb-5">Panel de control de <strong>{{ $activeProject->name }}</strong></p>

                {{-- Módulos activos --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($activeProject->activeModules as $module)
                    @php
                        try { $moduleUrl = route($module->route, ['project' => $activeProject->id]); }
                        catch (\Exception $e) { $moduleUrl = '#'; }
                    @endphp
                    <a href="{{ $moduleUrl }}"
                       class="border border-gray-200 rounded-xl p-4 hover:border-indigo-300 hover:bg-indigo-50 transition-all text-center group{{ $moduleUrl === '#' ? ' opacity-50 cursor-not-allowed' : '' }}">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-2 group-hover:bg-indigo-200">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </div>
                        <p class="text-xs font-medium text-gray-700">{{ $module->name }}</p>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Tab: Pendientes --}}
            <div x-show="tab==='pending'" x-cloak>
                <h3 class="font-semibold text-gray-800 mb-4">Tareas pendientes</h3>

                @if($pendingOrders->isNotEmpty())
                <div class="mb-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Pedidos nuevos</p>
                    @foreach($pendingOrders as $order)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $order->client_name }}</p>
                            <p class="text-xs text-gray-500">S/ {{ number_format($order->total, 2) }}</p>
                        </div>
                        <span class="badge-pending">Nuevo</span>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($pendingAppointments->isNotEmpty())
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Reservas de hoy</p>
                    @foreach($pendingAppointments as $apt)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $apt->client_name }}</p>
                            <p class="text-xs text-gray-500">{{ $apt->start_time }}</p>
                        </div>
                        <span class="badge-process">Hoy</span>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($pendingOrders->isEmpty() && $pendingAppointments->isEmpty())
                <p class="text-sm text-gray-400 text-center py-8">No hay pendientes por ahora</p>
                @endif
            </div>

            {{-- Tab: Accesos rápidos --}}
            <div x-show="tab==='shortcuts'" x-cloak>
                <h3 class="font-semibold text-gray-800 mb-4">Acciones rápidas</h3>
                <div class="space-y-2">
                    <a href="{{ route('catalog', ['project' => $activeProject->id]) }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors">
                        <span class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 text-sm">+</span>
                        <span class="text-sm font-medium text-gray-700">Agregar producto</span>
                    </a>
                    <a href="{{ route('orders', ['project' => $activeProject->id]) }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors">
                        <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-sm">→</span>
                        <span class="text-sm font-medium text-gray-700">Ver pedidos</span>
                    </a>
                    <a href="{{ route('settings', ['project' => $activeProject->id]) }}"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 text-sm">⚙</span>
                        <span class="text-sm font-medium text-gray-700">Configurar negocio</span>
                    </a>
                    <a href="/{{ $activeProject->slug }}" target="_blank"
                       class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 border border-gray-200 transition-colors">
                        <span class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 text-sm">↗</span>
                        <span class="text-sm font-medium text-gray-700">Ver página pública</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

</div>{{-- end flex wrapper --}}

</x-slot>
</x-app-layout>

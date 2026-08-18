{{-- Ventas con selector de rango.
     Los tres rangos se consultan de verdad en el servidor (DashboardController
     ::serieVentas); el selector cambia datos, no solo la pestaña. Si el rango
     no tiene ventas se dice, en vez de dibujar una linea plana en cero que
     parece un grafico averiado. --}}
@php
    $ser = $series ?? [];
    $ini = $ser['7d'] ?? ['labels' => [], 'data' => [], 'total' => 0, 'varianza' => null, 'vacia' => true];
@endphp
<section class="self-start rounded-xl border border-slate-200 bg-white"
         aria-labelledby="tit-ventas"
         x-data="panelVentas(@js($ser))">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
        <div>
            <h2 id="tit-ventas" class="text-sm font-semibold text-slate-900">Ventas</h2>
            <p class="mt-0.5 text-2xl font-semibold tabular-nums leading-tight text-slate-900"
               x-text="'S/ ' + fmt(actual.total)">S/ {{ number_format($ini['total'], 2) }}</p>
            <p class="mt-0.5 text-xs text-slate-500">
                <template x-if="actual.varianza !== null">
                    <span>
                        <span class="font-semibold" :class="actual.varianza >= 0 ? 'text-emerald-600' : 'text-red-600'"
                              x-text="(actual.varianza >= 0 ? '↑ ' : '↓ ') + Math.abs(actual.varianza) + '%'"></span>
                        vs. período anterior
                    </span>
                </template>
                <template x-if="actual.varianza === null">
                    <span>Sin período anterior con el que comparar</span>
                </template>
            </p>
        </div>

        <div class="flex rounded-lg border border-slate-200 p-0.5" role="tablist" aria-label="Rango de ventas">
            @foreach(['7d' => '7 días', '30d' => '30 días', 'mes' => 'Este mes'] as $clave => $texto)
            <button type="button" role="tab" @click="elegir('{{ $clave }}')"
                    :aria-selected="rango === '{{ $clave }}'"
                    :class="rango === '{{ $clave }}' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'"
                    class="rounded-md px-2.5 py-1 text-xs font-medium transition focus:outline-none focus:ring-2 focus:ring-indigo-500">
                {{ $texto }}
            </button>
            @endforeach
        </div>
    </div>

    <div class="px-4 py-3">
        <div class="relative h-44" x-show="!actual.vacia">
            <canvas id="chartVentas" aria-label="Ventas por día"></canvas>
        </div>
        <div x-show="actual.vacia" x-cloak class="flex h-44 flex-col items-center justify-center gap-1.5 text-center">
            <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
            </svg>
            <p class="text-sm font-medium text-slate-600">Sin ventas en este período</p>
            <a href="{{ route('bixosales.ventas.express') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Registrar una venta →</a>
        </div>
    </div>
</section>

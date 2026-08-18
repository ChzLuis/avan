{{-- KPI del negocio. Cuatro preguntas, cuatro respuestas.
     El color significa algo: un cero en "Stock crítico" es una buena noticia y
     se pinta como tal; el rojo se reserva para lo que hay que ir a arreglar. --}}
@php
    $pendTotal = $pendientes + ($enProceso ?? 0);
@endphp
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">

    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">{{ $kpi['v'] }}</p>
                <p class="mt-0.5 text-2xl font-semibold tabular-nums leading-tight text-slate-900">S/ {{ number_format($ventasHoy, 2) }}</p>
            </div>
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/>
                </svg>
            </span>
        </div>
        <p class="mt-1.5 text-xs text-slate-500">
            @if($varVentas !== null)
                <span class="font-semibold {{ $varVentas >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $varVentas >= 0 ? '↑' : '↓' }} {{ abs($varVentas) }}%</span>
                vs ayer
            @elseif($ventasHoy > 0)
                Primer día con ventas registradas
            @else
                Sin ventas todavía hoy
            @endif
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Pedidos pendientes</p>
                <p class="mt-0.5 text-2xl font-semibold tabular-nums leading-tight {{ $pendTotal > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $pendTotal }}</p>
            </div>
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ $pendTotal > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
            </span>
        </div>
        <p class="mt-1.5 text-xs text-slate-500">
            @if($pendTotal > 0)
                {{ $pendientes }} nuevo{{ $pendientes === 1 ? '' : 's' }} · {{ $enProceso ?? 0 }} en proceso ·
                <a href="{{ route('bixosales.pedidos') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Atender →</a>
            @else
                <span class="font-semibold text-emerald-600">✓</span> Nada en cola
            @endif
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Por cobrar</p>
                <p class="mt-0.5 text-2xl font-semibold tabular-nums leading-tight text-slate-900">S/ {{ number_format($porCobrar ?? 0, 2) }}</p>
            </div>
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ ($docsVencidos ?? 0) > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                </svg>
            </span>
        </div>
        <p class="mt-1.5 text-xs text-slate-500">
            @if(($docsVencidos ?? 0) > 0)
                <span class="font-semibold text-red-600">{{ $docsVencidos }} vencido{{ $docsVencidos === 1 ? '' : 's' }}</span>
                · S/ {{ number_format($vencido ?? 0, 2) }} ·
                <a href="{{ route('bixosales.cuentas') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Cobrar →</a>
            @elseif(($porCobrar ?? 0) > 0)
                <span class="font-semibold text-emerald-600">✓</span> Nada vencido
            @else
                <span class="font-semibold text-emerald-600">✓</span> Sin deuda pendiente
            @endif
        </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3.5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium text-slate-500">Stock crítico</p>
                <p class="mt-0.5 text-2xl font-semibold tabular-nums leading-tight {{ ($stockCritico ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $stockCritico ?? 0 }}</p>
            </div>
            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ ($stockCritico ?? 0) > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                </svg>
            </span>
        </div>
        <p class="mt-1.5 text-xs text-slate-500">
            @if(($stockCritico ?? 0) > 0)
                Bajo su mínimo ·
                <a href="{{ route('bixosales.reportes.inventario') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Revisar →</a>
            @else
                <span class="font-semibold text-emerald-600">✓</span> Todo en orden
            @endif
        </p>
    </div>
</div>

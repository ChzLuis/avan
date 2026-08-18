{{-- Cola operativa: que atender primero.
     Sustituye a las tarjetas de "Pedidos en curso" y a "Ultimos pedidos".
     Eran cinco bloques distintos contando el mismo pedido (KPI, atencion,
     estado, tarjetas y listado), cada uno con su formato.

     El color no es decoracion: sale de dos ajustes del proyecto,
     `orders_aviso_horas` (24 por defecto) y `orders_critico_horas` (72). Sin
     ellos habria que inventarse cuando un pedido "va tarde", y pintarlo todo
     de rojo —como pasaba antes— hace que el rojo deje de significar nada. --}}
@php
    $cola = $pedidosAtencion ?? collect();
    $tonos = [
        'critico' => 'text-red-600',
        'aviso'   => 'text-amber-600',
        'normal'  => 'text-slate-500',
    ];
@endphp
<section class="w-full rounded-xl border border-slate-200 bg-white" aria-labelledby="tit-cola">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h2 id="tit-cola" class="text-sm font-semibold text-slate-900">Pedidos que requieren atención</h2>
        @if(($pedidosAtencionTotal ?? 0) > $cola->count())
        <a href="{{ route('bixosales.pedidos') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
            Ver todos ({{ $pedidosAtencionTotal }}) →
        </a>
        @endif
    </div>

    @if($cola->isEmpty())
    <p class="px-4 py-5 text-center text-sm text-slate-400">No hay pedidos esperando. Todo despachado.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full min-w-[560px] text-sm">
            <thead>
                <tr class="text-xs text-slate-400">
                    <th class="px-4 py-2 text-left font-medium">Pedido</th>
                    <th class="px-2 py-2 text-left font-medium">Cliente</th>
                    <th class="px-2 py-2 text-left font-medium">Estado</th>
                    <th class="px-2 py-2 text-right font-medium">Tiempo</th>
                    <th class="px-2 py-2 text-right font-medium">Monto</th>
                    <th class="px-4 py-2 text-right font-medium">Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cola as $p)
                <tr class="border-t border-slate-50">
                    <td class="px-4 py-2 font-medium tabular-nums text-slate-900">#{{ $p['id'] }}</td>
                    <td class="max-w-[180px] truncate px-2 py-2 text-slate-700">{{ $p['cliente'] }}</td>
                    <td class="px-2 py-2">
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $p['estado'] }}</span>
                    </td>
                    <td class="px-2 py-2 text-right">
                        <span class="inline-flex items-center gap-1 text-xs font-semibold tabular-nums {{ $tonos[$p['nivel']] }}">
                            @if($p['nivel'] !== 'normal')
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            @endif
                            {{ $p['tiempo'] }}
                        </span>
                    </td>
                    <td class="px-2 py-2 text-right font-semibold tabular-nums text-slate-900">S/ {{ number_format($p['total'], 2) }}</td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('bixosales.pedidos') }}?q={{ $p['id'] }}"
                           class="inline-flex h-7 items-center rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Atender
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</section>

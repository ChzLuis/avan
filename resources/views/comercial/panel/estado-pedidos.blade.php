{{-- Estado de pedidos en barras.
     Antes era una dona con leyenda: para saber cuantos pedidos habia en cada
     estado tocaba leer la leyenda y buscar el color. Una barra por estado se
     entiende de un vistazo y usa los MISMOS estados reales que ya calcula el
     controlador ($donaLabels/$donaData), sea el flujo generico o el de un
     rubro con estados propios (lavanderia). --}}
@php
    $filas = [];
    foreach (($donaLabels ?? []) as $i => $etiqueta) {
        $filas[] = ['label' => $etiqueta, 'n' => (int) ($donaData[$i] ?? 0), 'color' => $donaColors[$i] ?? '#9CA3AF'];
    }
    $maxEstado   = max(1, max(array_column($filas, 'n') ?: [0]));
    $totalEstado = array_sum(array_column($filas, 'n'));
@endphp
<section class="self-start rounded-xl border border-slate-200 bg-white" aria-labelledby="tit-estados">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h2 id="tit-estados" class="text-sm font-semibold text-slate-900">Estado de pedidos</h2>
        <span class="text-xs text-slate-500">{{ $totalEstado }} en total</span>
    </div>
    <div class="px-4 py-3">
        @if($totalEstado === 0)
        <p class="py-4 text-center text-sm text-slate-400">Todavía no hay pedidos registrados.</p>
        @else
        @foreach($filas as $f)
        <div class="flex items-center gap-3 py-1.5">
            <span class="w-32 flex-shrink-0 truncate text-sm text-slate-600">{{ $f['label'] }}</span>
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full" style="width:{{ $f['n'] > 0 ? max(3, round($f['n'] / $maxEstado * 100)) : 0 }}%;background:{{ $f['color'] }}"></div>
            </div>
            <span class="w-8 flex-shrink-0 text-right text-sm font-semibold tabular-nums text-slate-900">{{ $f['n'] }}</span>
        </div>
        @endforeach
        @endif
    </div>
</section>

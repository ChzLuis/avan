{{-- Conversión de cotizaciones.

     Enviadas = cotizaciones con `sent_at`, es decir las que llegaron al
     cliente alguna vez. Incluye a proposito las que despues se aceptaron o
     convirtieron: si solo se contaran las que HOY siguen en estado 'sent', el
     porcentaje SUBIRIA cada vez que una cotizacion avanza, que es justo lo
     contrario de lo que mide.

     Conversión = convertidas ÷ enviadas. --}}
@php
    $c = $conversion ?? ['enviadas' => 0, 'aceptadas' => 0, 'convertidas' => 0, 'pct' => null];
    $celdas = [
        ['n' => $c['enviadas'],    'label' => 'Enviadas',    'clase' => 'bg-indigo-50 text-indigo-600',
         'icono' => 'M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5'],
        ['n' => $c['aceptadas'],   'label' => 'Aceptadas',   'clase' => 'bg-emerald-50 text-emerald-600',
         'icono' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        ['n' => $c['convertidas'], 'label' => 'Convertidas', 'clase' => 'bg-violet-50 text-violet-600',
         'icono' => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z'],
    ];
@endphp
<section class="self-start rounded-xl border border-slate-200 bg-white" aria-labelledby="tit-conversion">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h2 id="tit-conversion" class="text-sm font-semibold text-slate-900">Conversión de cotizaciones</h2>
    </div>

    @if($c['enviadas'] === 0)
    <p class="px-4 py-5 text-center text-sm text-slate-400">Todavía no has enviado ninguna cotización.</p>
    @else
    {{-- Dos por fila: en la columna estrecha del panel, cuatro celdas dejaban
         120 px cada una y "Convertidas" se comia a "Conversión". --}}
    <div class="grid grid-cols-2 divide-x divide-y divide-slate-100">
        @foreach($celdas as $cel)
        <div class="flex items-center gap-3 px-4 py-3.5">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg {{ $cel['clase'] }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $cel['icono'] }}"/>
                </svg>
            </span>
            <div class="min-w-0">
                <p class="text-xl font-semibold leading-tight tabular-nums text-slate-900">{{ $cel['n'] }}</p>
                <p class="text-xs text-slate-500">{{ $cel['label'] }}</p>
            </div>
        </div>
        @endforeach
        <div class="flex items-center gap-3 px-4 py-3.5">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/>
                </svg>
            </span>
            <div class="min-w-0">
                <p class="text-xl font-semibold leading-tight tabular-nums text-slate-900">{{ $c['pct'] }}%</p>
                <p class="text-xs text-slate-500">Conversión</p>
            </div>
        </div>
    </div>
    @endif
</section>

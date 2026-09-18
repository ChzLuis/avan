{{-- Checklist de arranque: la guia de dia uno. Solo existe mientras falte
     algo; cada check nace de una consulta, no de una casilla que alguien
     marca. Al completarse los pasos, la tarjeta desaparece sola. --}}
{{-- `?? null`: una rama antigua del panel (ya retirada) reutilizaba esta
     vista sin calcular $arranque, y sin el blindaje Laravel convierte la
     variable indefinida en excepcion. El resto de variables ya venian
     blindadas; esta era la unica suelta. --}}
@if($arranque ?? null)
<section class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4" aria-labelledby="tit-arranque">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 id="tit-arranque" class="text-sm font-semibold text-slate-900">Pon tu negocio en marcha</h2>
            <p class="text-xs text-slate-500">{{ $arranque['hechos'] }} de {{ $arranque['total'] }} pasos completados</p>
        </div>
        @php
            /* La misma comprobacion que exige la ruta: decidir por todo el
               equipo que la guia sobra es un ajuste del negocio. */
            $_ua = auth()->user();
            $_puedeOcultar = $_ua?->is_superadmin || $_ua?->can('settings.negocio') || $_ua?->can('manage-settings');
        @endphp
        @if($_puedeOcultar)
        <form method="POST" action="{{ route('bixosales.arranque.ocultar') }}">
            @csrf
            <button type="submit" class="text-xs font-medium text-slate-400 transition hover:text-slate-600">
                No volver a mostrar
            </button>
        </form>
        @endif
    </div>

    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-indigo-100">
        <div class="h-full rounded-full bg-indigo-500 transition-all"
             style="width:{{ $arranque['total'] > 0 ? round($arranque['hechos'] / $arranque['total'] * 100) : 0 }}%"></div>
    </div>

    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($arranque['pasos'] as $paso)
        <a href="{{ $paso['hecho'] ? '#' : $paso['url'] }}"
           class="flex items-start gap-2.5 rounded-lg border bg-white p-3 transition
                  {{ $paso['hecho'] ? 'border-emerald-200 opacity-70' : 'border-slate-200 hover:border-indigo-300 hover:shadow-sm' }}">
            <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full
                         {{ $paso['hecho'] ? 'bg-emerald-500 text-white' : 'border-2 border-slate-300' }}">
                @if($paso['hecho'])
                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                @endif
            </span>
            <span class="min-w-0">
                <span class="block text-xs font-semibold leading-snug {{ $paso['hecho'] ? 'text-slate-500 line-through' : 'text-slate-900' }}">{{ $paso['titulo'] }}</span>
                <span class="block text-[11px] leading-snug text-slate-500">{{ $paso['detalle'] }}</span>
            </span>
        </a>
        @endforeach
    </div>
</section>
@endif

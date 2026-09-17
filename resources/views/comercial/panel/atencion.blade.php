{{-- Centro operativo: lo que hay que hacer, ordenado por gravedad.
     Cada fila nace de una consulta. Si una categoria no tiene problema, no
     aparece: no hay filas de relleno ni luces verdes sin comprobar. La tarjeta
     termina donde termina su contenido — no se estira para igualar la columna
     de al lado, que es lo que dejaba media pantalla en blanco. --}}
{{-- $avisos lo arma el dashboard (comercial/dashboard.blade.php): lo comparte con la portada movil. --}}

<section class="self-start rounded-xl border border-slate-200 bg-white" aria-labelledby="tit-atencion">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h2 id="tit-atencion" class="text-sm font-semibold text-slate-900">Requiere tu atención</h2>
        @if(count($avisos))
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-slate-600">{{ count($avisos) }}</span>
        @endif
    </div>

    @forelse($avisos as $a)
    <div class="flex items-center gap-3 border-b border-slate-50 px-4 py-2.5 last:border-0">
        <span class="h-8 w-1 flex-shrink-0 rounded-full {{ $paleta[$a['nivel']][1] }}" aria-hidden="true"></span>
        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg {{ $paleta[$a['nivel']][0] }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $a['icono'] }}"/>
            </svg>
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium leading-snug text-slate-900">{{ $a['titulo'] }}</p>
            <p class="text-xs leading-snug text-slate-500">{{ $a['detalle'] }}</p>
        </div>
        <a href="{{ $a['url'] }}"
           class="inline-flex h-8 flex-shrink-0 items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            {{ $a['accion'] }}
            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
            </svg>
        </a>
    </div>
    @empty
    <div class="flex items-center gap-3 px-4 py-4">
        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
            </svg>
        </span>
        <div>
            <p class="text-sm font-medium text-slate-900">Todo en orden</p>
            <p class="text-xs text-slate-500">Sin deuda vencida, pedidos en cola ni stock bajo mínimo.</p>
        </div>
    </div>
    @endforelse
</section>

{{-- Actividad reciente, compacta: cuatro hechos y un enlace al resto.
     La etiqueta la da el MODELO (`$ev->titulo`), no esta vista: el nombre
     interno del evento —`payment_registered`— llego a verse en pantalla
     porque cada sitio se hacia su propio mapa y ninguno estaba completo. --}}
<section class="self-start rounded-xl border border-slate-200 bg-white" aria-labelledby="tit-actividad">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h2 id="tit-actividad" class="text-sm font-semibold text-slate-900">Actividad reciente</h2>
    </div>
    @forelse(($actividad ?? collect())->take(4) as $ev)
    <div class="flex items-baseline gap-2.5 border-b border-slate-50 px-4 py-2 last:border-0">
        <span class="h-1.5 w-1.5 flex-shrink-0 translate-y-[-1px] rounded-full bg-indigo-500" aria-hidden="true"></span>
        <p class="min-w-0 flex-1 truncate text-sm text-slate-800">{{ $ev->titulo }}</p>
        <p class="flex-shrink-0 text-xs text-slate-400">
            @if($ev->order_id) #{{ $ev->order_id }} @elseif($ev->quote_id) COT-{{ $ev->quote_id }} @endif
            · {{ $ev->created_at->locale('es')->diffForHumans(null, true) }}
        </p>
    </div>
    @empty
    <p class="px-4 py-4 text-sm text-slate-400">Todavía no hay movimientos registrados.</p>
    @endforelse
    @if(($actividad ?? collect())->count() > 0)
    <div class="border-t border-slate-100 px-4 py-2">
        <a href="{{ route('bixosales.pedidos') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Ver toda la actividad →</a>
    </div>
    @endif
</section>

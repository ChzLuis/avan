{{-- De donde viene la venta este mes. Monto, operaciones y participación:
     los tres datos que ya calcula el controlador, en tabla legible y con una
     barra discreta para la participación. Sin gráfico, que aquí no aporta. --}}
<section class="self-start rounded-xl border border-slate-200 bg-white" aria-labelledby="tit-canales">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
        <div>
            <h2 id="tit-canales" class="text-sm font-semibold text-slate-900">Canales de venta</h2>
            <p class="text-xs text-slate-500">Por dónde entró el dinero este mes</p>
        </div>
        <div class="text-right">
            <p class="text-sm font-semibold tabular-nums text-slate-900">S/ {{ number_format($ventasMesTotal ?? 0, 2) }}</p>
            @if(($meta ?? 0) > 0)
            <p class="text-xs font-semibold {{ ($metaPct ?? 0) >= 100 ? 'text-emerald-600' : 'text-indigo-600' }}">{{ $metaPct }}% de la meta</p>
            @endif
        </div>
    </div>

    @php $canalColors = ['Tienda virtual' => '#4F46E5', 'POS / Mostrador' => '#0EA5E9', 'WhatsApp' => '#25D366', 'Cotizaciones' => '#8B5CF6', 'Otros' => '#9CA3AF']; @endphp
    @if(count($canales ?? []))
    <table class="w-full text-sm">
        <thead>
            <tr class="text-xs text-slate-400">
                <th class="px-4 pb-1 pt-2 text-left font-medium">Canal</th>
                <th class="px-2 pb-1 pt-2 text-right font-medium">Ventas</th>
                <th class="hidden px-2 pb-1 pt-2 text-right font-medium sm:table-cell">Oper.</th>
                <th class="px-4 pb-1 pt-2 text-right font-medium">Part.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($canales as $nombre => $c)
            @php $pct = ($ventasMesTotal ?? 0) > 0 ? round($c['t'] / $ventasMesTotal * 100) : 0; @endphp
            <tr class="border-t border-slate-50">
                <td class="px-4 py-2">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 flex-shrink-0 rounded-full" style="background:{{ $canalColors[$nombre] ?? '#9CA3AF' }}"></span>
                        <span class="truncate text-slate-700">{{ $nombre }}</span>
                    </div>
                </td>
                <td class="px-2 py-2 text-right font-semibold tabular-nums text-slate-900">S/ {{ number_format($c['t'], 2) }}</td>
                <td class="hidden px-2 py-2 text-right tabular-nums text-slate-500 sm:table-cell">{{ $c['n'] }}</td>
                <td class="px-4 py-2">
                    <div class="flex items-center justify-end gap-2">
                        <div class="hidden h-1.5 w-16 overflow-hidden rounded-full bg-slate-100 md:block">
                            <div class="h-full rounded-full" style="width:{{ max(2, $pct) }}%;background:{{ $canalColors[$nombre] ?? '#9CA3AF' }}"></div>
                        </div>
                        <span class="w-9 text-right tabular-nums text-slate-600">{{ $pct }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="px-4 py-5 text-center text-sm text-slate-400">
        Aún no hay ventas este mes. Empieza con
        <a href="{{ route('bixosales.ventas.express') }}" class="font-semibold text-indigo-600">Venta Express</a>.
    </p>
    @endif
</section>

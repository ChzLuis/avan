{{--
    Cuentas por Cobrar — F2 v1 (lectura).
    DoD desde el día uno: tokens, .mod-tactil, vocabulario en español, dinero
    canónico del servidor (aquí no se calcula nada: se pinta lo exacto).
--}}
<x-portal-layout :layout="$portalLayout ?? 'comercial'" :project="$project" pageTitle="Cuentas por cobrar">

<div class="mod-tactil flex flex-col h-full w-full overflow-hidden" style="background:var(--superficie-2, #f8f9fb)">

    {{-- Encabezado + KPIs --}}
    <div class="px-6 py-4 border-b flex-shrink-0" style="background:var(--superficie, #fff);border-color:var(--borde, #e5e7eb)">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-lg font-black" style="color:var(--texto, #111827)">Cuentas por cobrar</h1>
                <p class="text-xs" style="color:var(--texto-debil, #6b7280)">
                    Saldo pendiente de pedidos y cotizaciones aceptadas ·
                    @if (($condiciones['plazo'] ?? 0) === 0)
                        cobro al contado
                    @else
                        crédito a {{ $condiciones['plazo'] }} días
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="rounded-xl px-4 py-2 text-right" style="background:var(--aviso-suave, #fef3c7)">
                    {{-- Deuda EXIGIBLE: la que nace de una venta. Antes esta
                         cifra incluia las cotizaciones aceptadas sin convertir,
                         que son una expectativa, no una obligacion de pago. --}}
                    <p class="text-[10px] font-bold uppercase tracking-wide" style="color:var(--aviso-fuerte, #b45309)">Por cobrar</p>
                    <p class="text-lg font-black" style="color:var(--aviso-fuerte, #b45309)">S/ {{ $resumen['total'] }}</p>
                </div>
                <div class="rounded-xl px-4 py-2 text-right" style="background:var(--peligro-suave, #fef2f2)">
                    {{-- Ya no es "+15 días": vencido = pasó la fecha pactada. --}}
                    <p class="text-[10px] font-bold uppercase tracking-wide" style="color:var(--peligro-fuerte, #b91c1c)">Vencido</p>
                    <p class="text-lg font-black" style="color:var(--peligro-fuerte, #b91c1c)">S/ {{ $resumen['vencido'] }}</p>
                    @if (($resumen['vencido_pct'] ?? 0) > 0)
                        <p class="text-[10px] font-semibold" style="color:var(--peligro-fuerte, #b91c1c)">
                            {{ $resumen['vencido_pct'] }}% del saldo
                        </p>
                    @endif
                </div>
                <div class="rounded-xl px-4 py-2 text-right" style="background:var(--acento-suave, #e0e7ff)">
                    <p class="text-[10px] font-bold uppercase tracking-wide" style="color:var(--acento-fuerte, #4338ca)">Documentos</p>
                    <p class="text-lg font-black" style="color:var(--acento-fuerte, #4338ca)">{{ $resumen['documentos'] }}</p>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-3 rounded-xl px-4 py-2 text-sm" role="status"
                 style="background:var(--exito-suave, #dcfce7);color:var(--exito-fuerte, #15803d)">
                {{ session('status') }}
            </div>
        @endif

        {{-- Condiciones de cobro. El plazo definia el vencimiento de todo el
             modulo y solo se podia tocar por base de datos; aqui el negocio lo
             configura. Cambiarlo NO reescribe vencimientos ya pactados salvo
             que se marque la casilla, que es un acto deliberado. --}}
        @if ($condiciones['puede'] ?? false)
        <details class="mt-3 rounded-xl" style="border:1px solid var(--borde, #e5e7eb);background:var(--superficie-2, #f8f9fb)">
            <summary class="mod-tactil px-4 py-2.5 text-xs font-bold cursor-pointer select-none"
                     style="color:var(--texto, #111827)">
                Condiciones de cobro
                <span class="font-normal" style="color:var(--texto-debil, #6b7280)">
                    · {{ ($condiciones['plazo'] ?? 0) === 0 ? 'contado' : 'crédito a ' . $condiciones['plazo'] . ' días' }}
                </span>
            </summary>
            <form method="POST" action="{{ route('bixosales.cuentas.condiciones') }}"
                  class="px-4 pb-4 pt-1 flex flex-wrap items-end gap-4">
                @csrf
                <label class="text-xs" style="color:var(--texto, #111827)">
                    <span class="block font-semibold mb-1">Plazo de cobro (días)</span>
                    <input type="number" name="cxc_plazo_dias" min="0" max="365"
                           value="{{ old('cxc_plazo_dias', $condiciones['plazo']) }}" required
                           class="mod-tactil rounded-lg px-3 py-2 w-28"
                           style="border:1px solid var(--borde, #e5e7eb);background:var(--superficie, #fff)">
                    <span class="block mt-1" style="color:var(--texto-debil, #6b7280)">0 = al contado</span>
                </label>
                <label class="text-xs" style="color:var(--texto, #111827)">
                    <span class="block font-semibold mb-1">Avisar antes de vencer (días)</span>
                    <input type="number" name="cxc_dias_aviso" min="0" max="90"
                           value="{{ old('cxc_dias_aviso', $condiciones['aviso']) }}" required
                           class="mod-tactil rounded-lg px-3 py-2 w-28"
                           style="border:1px solid var(--borde, #e5e7eb);background:var(--superficie, #fff)">
                </label>
                <label class="text-xs flex items-start gap-2 max-w-sm" style="color:var(--texto, #111827)">
                    <input type="checkbox" name="recalcular" value="1" class="mt-0.5">
                    <span>
                        <span class="font-semibold">Aplicar a lo que aún está pendiente</span>
                        <span class="block" style="color:var(--texto-debil, #6b7280)">
                            Recalcula el vencimiento de los documentos sin cobrar. Lo ya
                            saldado no se toca.
                        </span>
                    </span>
                </label>
                <button type="submit" class="mod-tactil rounded-lg px-4 py-2 text-xs font-bold text-white"
                        style="background:var(--acento-oscuro, #4f46e5)">Guardar</button>
            </form>
        </details>
        @endif

        {{-- Antigüedad --}}
        <div class="flex items-center gap-2 mt-3 flex-wrap text-xs">
            <span style="color:var(--texto-debil, #6b7280)" class="font-semibold">Atraso:</span>
            @foreach ($resumen['buckets'] as $rango => $monto)
                <span class="rounded-full px-3 py-1.5 font-semibold"
                      style="background:var(--superficie-2, #f8f9fb);border:1px solid var(--borde, #e5e7eb);color:var(--texto, #111827)">
                    {{ $rango }} días · S/ {{ $monto }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto p-4">
        @if (empty($filas))
            {{-- Estado vacío DISEÑADO (DoD), no una pantalla en blanco --}}
            <div class="flex flex-col items-center justify-center text-center h-full gap-2 py-16">
                <svg class="w-14 h-14" style="color:var(--exito-suave, #dcfce7)" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-bold" style="color:var(--texto, #111827)">Nada pendiente de cobro</p>
                <p class="text-sm" style="color:var(--texto-debil, #6b7280)">Cuando un pedido o una cotización aceptada quede sin pagar, aparecerá aquí.</p>
            </div>
        @else
            <div class="rounded-xl overflow-hidden" style="background:var(--superficie, #fff);border:1px solid var(--borde, #e5e7eb)">
                {{-- Desktop --}}
                <table class="w-full text-sm hidden lg:table">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wide" style="color:var(--texto-debil, #6b7280);background:var(--superficie-2, #f8f9fb)">
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3 text-right">Atraso</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Cobrado</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3">Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $f)
                        <tr style="border-top:1px solid var(--borde-suave, #f3f4f6)">
                            {{-- El enlace ocupa toda su celda: el area de click
                                 llega a 44px sin engordar la fila, que es lo que
                                 pide el DoD sin sacrificar densidad. --}}
                            <td class="font-semibold">
                                @if ($f['tipo'] === 'pedido')
                                    <a href="{{ route('bixosales.pedidos.show', ['order' => $f['id']]) }}"
                                       class="flex items-center px-4 py-3 hover:underline" style="color:var(--acento-fuerte, #4338ca);min-height:44px">PED-{{ $f['id'] }}</a>
                                @else
                                    <a href="{{ route('bixosales.cotizaciones.show', ['quote' => $f['id']]) }}"
                                       class="flex items-center px-4 py-3 hover:underline" style="color:var(--acento-fuerte, #4338ca);min-height:44px">COT-{{ $f['id'] }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $f['cliente'] }}</td>
                            <td class="px-4 py-3" style="color:var(--texto-debil, #6b7280)">
                                {{ $f['fecha'] }}
                                @if ($f['vence'] && $f['vence'] !== $f['fecha'])
                                    <span class="block text-xs">vence {{ $f['vence'] }}</span>
                                @endif
                            </td>
                            {{-- Los dias que se muestran son de ATRASO sobre el vencimiento
                                 pactado, no de antiguedad del documento. Si no hay
                                 vencimiento registrado se avisa de que es estimado. --}}
                            <td class="px-4 py-3 text-right font-semibold"
                                style="color:{{ ! $f['vencido'] ? 'var(--texto, #111827)' : ($f['atraso'] > 30 ? 'var(--peligro-fuerte, #b91c1c)' : 'var(--aviso-fuerte, #b45309)') }}">
                                @if ($f['vencido'])
                                    {{ $f['atraso'] }} <span class="font-normal">d</span>
                                @else
                                    <span style="color:var(--texto-debil, #6b7280)">al día</span>
                                @endif
                                @if ($f['estimado'])
                                    <span class="block text-xs" style="color:var(--texto-debil, #6b7280)"
                                          title="Sin vencimiento pactado: se estima por antigüedad">estimado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">S/ {{ $f['total'] }}</td>
                            <td class="px-4 py-3 text-right" style="color:var(--exito-fuerte, #15803d)">
                                {{ $f['cobrado'] !== '' ? 'S/ ' . $f['cobrado'] : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-black">S/ {{ $f['saldo'] }}</td>
                            <td class="px-4 py-3">
                                <span class="status-pill {{ $f['pago']['cls'] }}">{{ $f['pago']['label'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Móvil: fichas --}}
                <div class="lg:hidden divide-y" style="border-color:var(--borde-suave, #f3f4f6)">
                    @foreach ($filas as $f)
                    <a href="{{ $f['tipo'] === 'pedido' ? route('bixosales.pedidos.show', ['order' => $f['id']]) : route('bixosales.cotizaciones.show', ['quote' => $f['id']]) }}"
                       class="block px-4 py-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold" style="color:var(--acento-fuerte, #4338ca)">
                                    {{ $f['tipo'] === 'pedido' ? 'PED' : 'COT' }}-{{ $f['id'] }}
                                    <span class="font-normal" style="color:var(--texto, #111827)">· {{ $f['cliente'] }}</span>
                                </p>
                                <p class="text-xs mt-0.5" style="color:var(--texto-debil, #6b7280)">
                                    {{ $f['fecha'] }} ·
                                    <span style="color:{{ ! $f['vencido'] ? 'inherit' : ($f['atraso'] > 30 ? 'var(--peligro-fuerte, #b91c1c)' : 'var(--aviso-fuerte, #b45309)') }}">
                                        @if ($f['vencido'])
                                            {{ $f['atraso'] }} días de atraso
                                        @else
                                            al día{{ $f['vence'] && $f['vence'] !== $f['fecha'] ? ' · vence ' . $f['vence'] : '' }}
                                        @endif
                                    </span>
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-black">S/ {{ $f['saldo'] }}</p>
                                <span class="status-pill {{ $f['pago']['cls'] }}">{{ $f['pago']['label'] }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

</x-portal-layout>

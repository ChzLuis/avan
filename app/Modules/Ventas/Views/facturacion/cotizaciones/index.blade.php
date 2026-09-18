<x-facturacion-layout :project="$project" pageTitle="Cotizaciones">

<div class="flex-1 overflow-y-auto p-6 space-y-5" x-data="cotizIndex()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Cotizaciones</h1>
            </div>
            <p class="text-sm text-gray-500 mt-0.5 ml-10">Propuestas comerciales para clientes</p>
        </div>
        <a href="{{ route('facturacion.cotizaciones.create', $project->slug) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nueva Cotización
        </a>
    </div>

    {{-- Stats --}}
    @php
        $totalCotiz = count($quotes);
        $pendientes = collect($quotes)->whereIn('status', ['draft','sent'])->count();
        $aceptadas  = collect($quotes)->where('status','accepted')->count();
        $montoTotal = collect($quotes)->sum('total');
    @endphp
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalCotiz }}</p>
            <p class="text-xs text-gray-400 mt-0.5">cotizaciones</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Pendientes</p>
            <p class="text-2xl font-bold text-amber-500 mt-1">{{ $pendientes }}</p>
            <p class="text-xs text-gray-400 mt-0.5">sin convertir</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Aceptadas</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $aceptadas }}</p>
            <p class="text-xs text-gray-400 mt-0.5">convertidas</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Monto Total</p>
            <p class="text-2xl font-bold text-violet-600 mt-1">S/ {{ number_format($montoTotal, 0) }}</p>
            <p class="text-xs text-gray-400 mt-0.5">en cotizaciones</p>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-3">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input x-model="buscar" type="text" placeholder="Buscar por cliente..." class="flex-1 text-sm outline-none text-gray-700 placeholder-gray-400 bg-transparent">
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/60">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Cliente</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Válida hasta</th>
                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($quotes as $q)
                @php $st = $q['status']; @endphp
                <tr class="hover:bg-violet-50/30 transition-colors"
                    x-show="!buscar || '{{ strtolower($q['client_name'] ?? '') }}'.includes(buscar.toLowerCase())">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800">{{ $q['client_name'] ?? '—' }}</p>
                        <p class="text-xs text-gray-400">{{ $q['created_at'] }}</p>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-500">{{ $q['items_count'] }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $q['valid_until'] ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-800">S/ {{ number_format($q['total'], 2) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium
                            {{ $st === 'accepted' ? 'bg-emerald-50 text-emerald-600' :
                               ($st === 'rejected' ? 'bg-red-50 text-red-600' :
                               ($st === 'sent'     ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500')) }}">
                            {{ ['draft'=>'Borrador','sent'=>'Enviada','accepted'=>'Aceptada','rejected'=>'Rechazada'][$st] ?? ucfirst($st) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            {{-- Ver --}}
                            <button @click="verCotiz({{ $q['id'] }})"
                                    title="Ver cotización"
                                    class="p-1.5 rounded-lg hover:bg-violet-50 text-gray-400 hover:text-violet-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            {{-- Editar --}}
                            <a href="{{ route('facturacion.cotizaciones.edit', [$project->slug, $q['id']]) }}"
                               title="Editar cotización"
                               class="p-1.5 rounded-lg hover:bg-amber-50 text-gray-400 hover:text-amber-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            {{-- Convertir --}}
                            <button @click="abrirConvertir({{ $q['id'] }}, '{{ $q['client_name'] }}')"
                                    title="Convertir a comprobante"
                                    class="p-1.5 rounded-lg hover:bg-emerald-50 text-gray-400 hover:text-emerald-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No hay cotizaciones aún</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── MODAL VER (solo lectura) ── --}}
    <div x-show="modalVer" x-cloak
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="modalVer=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[88vh] flex flex-col">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900" x-text="cotizActual ? 'Cotización · ' + cotizActual.client_name : 'Cargando...'"></h3>
                        <p class="text-xs text-gray-400" x-text="cotizActual ? 'Creada el ' + (cotizActual.created_at||'') : ''"></p>
                    </div>
                </div>
                <button @click="modalVer=false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1 p-6 space-y-4">
                <template x-if="!cotizActual">
                    <div class="flex items-center justify-center py-10">
                        <svg class="w-6 h-6 animate-spin text-violet-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </div>
                </template>
                <template x-if="cotizActual">
                <div class="space-y-4">

                    {{-- Datos cliente + cotización --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4 space-y-1.5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cliente</p>
                            <p class="font-bold text-gray-800" x-text="cotizActual.client_name"></p>
                            <p class="text-sm text-gray-500" x-show="cotizActual.client_phone" x-text="'📞 ' + cotizActual.client_phone"></p>
                            <p class="text-sm text-gray-500" x-show="cotizActual.client_email" x-text="'✉ ' + cotizActual.client_email"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4 space-y-1.5">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Detalles</p>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
                                <div><span class="text-xs text-gray-400">Válida hasta</span><p class="font-medium text-gray-700" x-text="cotizActual.valid_until||'—'"></p></div>
                                <div><span class="text-xs text-gray-400">Estado</span>
                                    <p>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                              :class="{'bg-emerald-50 text-emerald-600': cotizActual.status==='accepted',
                                                       'bg-blue-50 text-blue-600': cotizActual.status==='sent',
                                                       'bg-gray-100 text-gray-600': cotizActual.status==='draft',
                                                       'bg-red-50 text-red-600': cotizActual.status==='rejected'}"
                                              x-text="{'draft':'Borrador','sent':'Enviada','accepted':'Aceptada','rejected':'Rechazada'}[cotizActual.status]||cotizActual.status">
                                        </span>
                                    </p>
                                </div>
                                <div><span class="text-xs text-gray-400">Método pago</span><p class="font-medium text-gray-700" x-text="cotizActual.payment_method||'—'"></p></div>
                                <div><span class="text-xs text-gray-400">Condición</span><p class="font-medium text-gray-700" x-text="cotizActual.payment_condition||'—'"></p></div>
                            </div>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        <div class="grid bg-gray-50 px-4 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wide border-b border-gray-100"
                             style="grid-template-columns: 1fr 70px 100px 100px;">
                            <div>Descripción</div>
                            <div class="text-center">Cant.</div>
                            <div class="text-right">P. Unit</div>
                            <div class="text-right">Total</div>
                        </div>
                        <template x-for="(it,i) in (cotizActual.items||[])" :key="i">
                        <div class="grid items-center px-4 py-2.5 text-sm border-b border-gray-50 last:border-0"
                             :class="i%2===0?'bg-white':'bg-gray-50/40'"
                             style="grid-template-columns: 1fr 70px 100px 100px;">
                            <span class="text-gray-700 pr-3" x-text="it.description"></span>
                            <span class="text-center text-gray-600" x-text="it.quantity"></span>
                            <span class="text-right text-gray-500" x-text="'S/ ' + parseFloat(it.price||0).toFixed(2)"></span>
                            <span class="text-right font-semibold text-gray-800" x-text="'S/ ' + (parseFloat(it.quantity||0)*parseFloat(it.price||0)).toFixed(2)"></span>
                        </div>
                        </template>
                    </div>

                    {{-- Total --}}
                    <div class="flex justify-end">
                        <div class="bg-violet-50 rounded-xl px-6 py-3 text-right">
                            <p class="text-xs text-violet-500 uppercase font-medium">Total</p>
                            <p class="text-2xl font-bold text-violet-700" x-text="'S/ ' + parseFloat(cotizActual.total||0).toFixed(2)"></p>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div x-show="cotizActual.notes"
                         class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-sm text-amber-800"
                         x-text="cotizActual.notes"></div>

                </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                <button @click="modalVer=false" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Cerrar</button>
                <div class="flex gap-2" x-show="cotizActual">
                    <template x-if="cotizActual && cotizActual.status !== 'accepted'">
                        <button @click="abrirConvertir(cotizActual.id, cotizActual.client_name)"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-emerald-700 border-2 border-emerald-300 hover:bg-emerald-50 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                            Convertir
                        </button>
                    </template>
                    <template x-if="cotizActual">
                        <a :href="'{{ url('f/' . $project->slug . '/cotizaciones') }}/' + cotizActual.id + '/edit'"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Editar cotización
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MODAL CONVERTIR (desde lista, sin guardar) ── --}}
    <div x-show="modalConvertir" x-cloak
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="modalConvertir=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="font-semibold text-gray-900">Convertir a Comprobante</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Cliente: <span x-text="convertirNombre" class="text-gray-600 font-medium"></span></p>
                </div>
                <button @click="modalConvertir=false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-3">
                <p class="text-sm text-gray-500 mb-4">Los datos se pre-cargarán en el formulario. Podrás ajustar precios y productos antes de emitir.</p>
                <button @click="convertir('boleta')"
                        class="w-full flex items-center gap-4 p-4 border-2 border-emerald-200 hover:border-emerald-400 rounded-2xl transition-colors group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 group-hover:bg-emerald-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="text-left flex-1">
                        <p class="font-semibold text-gray-800">Boleta de Venta</p>
                        <p class="text-xs text-gray-400">Para personas naturales (DNI)</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @click="convertir('factura')"
                        class="w-full flex items-center gap-4 p-4 border-2 border-blue-200 hover:border-blue-400 rounded-2xl transition-colors group">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="text-left flex-1">
                        <p class="font-semibold text-gray-800">Factura</p>
                        <p class="text-xs text-gray-400">Para empresas (RUC)</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function cotizIndex() {
    return {
        buscar: '',
        modalVer: false,
        modalConvertir: false,
        cotizActual: null,
        convertirId: null,
        convertirNombre: '',

        async verCotiz(id) {
            this.cotizActual = null;
            this.modalVer    = true;
            const r = await fetch(`{{ $showBase }}/${id}`, {
                headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            });
            this.cotizActual = await r.json();
        },

        abrirConvertir(id, nombre) {
            this.convertirId     = id;
            this.convertirNombre = nombre;
            this.modalConvertir  = true;
            this.modalVer        = false;
        },

        convertir(tipo) {
            const base = tipo === 'boleta'
                ? '{{ $boletaCreateUrl }}'
                : '{{ $facturaCreateUrl }}';
            window.location.href = base + '?from_quote=' + this.convertirId;
        },
    }
}
</script>

</x-facturacion-layout>

@php
    $esBoleta    = $docType === 'boleta';
    $titulo      = $esBoleta ? 'Boletas Electrónicas' : 'Facturas Electrónicas';
    $sunatUrl  = route('facturacion.facturas.sunat', [$project->slug, '__ID__']);
    $showUrl   = route('facturacion.facturas.show',  [$project->slug, '__ID__']);
    $pdfBase   = url("f/{$project->slug}/facturas");
    $createUrl = $esBoleta ? route('facturacion.boletas.create', $project->slug) : route('facturacion.facturas.create', $project->slug);
    $serie     = $esBoleta ? $serieBoleta : $serieFactura;
@endphp

<x-facturacion-layout :project="$project" :pageTitle="$titulo">

<div class="flex-1 overflow-y-auto bg-gray-50"
     x-data="factPage()"
     x-init="init()">

    {{-- ── HEADER ── --}}
    <div class="bg-white border-b border-gray-100 px-6 py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center
                            {{ $esBoleta ? 'bg-emerald-100' : 'bg-blue-100' }}">
                    @if($esBoleta)
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    @else
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    @endif
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900">{{ $titulo }}</h1>
                    <p class="text-xs text-gray-400">Serie: <span class="font-mono font-semibold">{{ $serie }}</span> · {{ $project->name }}</p>
                </div>
            </div>

            <a href="{{ $createUrl }}"
               class="flex items-center gap-2 px-5 py-2.5 text-white text-sm font-bold rounded-xl transition-colors shadow-sm
                      {{ $esBoleta ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva {{ $esBoleta ? 'Boleta' : 'Factura' }}
            </a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3 mt-5">
            <div class="rounded-xl border px-4 py-3
                        {{ $esBoleta ? 'bg-emerald-50 border-emerald-100' : 'bg-blue-50 border-blue-100' }}">
                <p class="text-xs font-medium {{ $esBoleta ? 'text-emerald-600' : 'text-blue-600' }}">Total emitidas</p>
                <p class="text-2xl font-bold {{ $esBoleta ? 'text-emerald-700' : 'text-blue-700' }} mt-0.5" x-text="invoices.length"></p>
                <p class="text-xs {{ $esBoleta ? 'text-emerald-500' : 'text-blue-500' }} mt-0.5"
                   x-text="'S/ ' + invoices.reduce((s,i)=>s+i.total,0).toFixed(2)"></p>
            </div>
            <div class="rounded-xl border bg-violet-50 border-violet-100 px-4 py-3">
                <p class="text-xs font-medium text-violet-600">Aceptadas SUNAT</p>
                <p class="text-2xl font-bold text-violet-700 mt-0.5"
                   x-text="invoices.filter(i=>i.sunat_status==='accepted').length"></p>
                <p class="text-xs text-violet-500 mt-0.5">Válidas</p>
            </div>
            <div class="rounded-xl border bg-amber-50 border-amber-100 px-4 py-3">
                <p class="text-xs font-medium text-amber-600">Pendientes SUNAT</p>
                <p class="text-2xl font-bold text-amber-700 mt-0.5"
                   x-text="invoices.filter(i=>!i.sunat_status||i.sunat_status==='rejected').length"></p>
                <p class="text-xs text-amber-500 mt-0.5">Por enviar</p>
            </div>
        </div>
    </div>

    {{-- ── TABLA ── --}}
    <div class="p-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

            {{-- Buscador --}}
            <div class="px-5 py-3 border-b border-gray-50 flex items-center gap-3">
                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search"
                       placeholder="Buscar por número o cliente..."
                       class="flex-1 text-sm text-gray-700 outline-none placeholder-gray-300 bg-transparent">
                <span class="text-xs text-gray-400" x-text="filtered.length + ' resultados'"></span>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-50 bg-gray-50/60">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Número</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Cliente</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Fecha</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">Total</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">SUNAT</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="inv in filtered" :key="inv.id">
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="font-mono text-xs font-bold text-gray-700" x-text="inv.numero"></span>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="text-sm font-medium text-gray-800 truncate max-w-[200px]" x-text="inv.client_name"></p>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-gray-400" x-text="inv.issue_date"></td>
                        <td class="px-4 py-3.5 text-right font-bold text-gray-800"
                            x-text="'S/ ' + parseFloat(inv.total).toFixed(2)"></td>
                        <td class="px-4 py-3.5 text-center">
                            <template x-if="inv.sunat_status === 'accepted'">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aceptada
                                </span>
                            </template>
                            <template x-if="inv.sunat_status === 'rejected'">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Rechazada
                                </span>
                            </template>
                            <template x-if="!inv.sunat_status || (inv.sunat_status !== 'accepted' && inv.sunat_status !== 'rejected')">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Pendiente
                                </span>
                            </template>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Ver --}}
                                <button @click="verFactura(inv)"
                                        title="Ver comprobante"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                {{-- PDF --}}
                                <a :href="`{{ $pdfBase }}/` + inv.id + '/pdf'" target="_blank"
                                   title="Descargar PDF"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </a>
                                {{-- Historial SUNAT --}}
                                <button @click="verHistorial(inv)"
                                        title="Historial SUNAT"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                {{-- Enviar SUNAT --}}
                                <template x-if="inv.sunat_status !== 'accepted'">
                                    <button @click="enviarSunat(inv)" :disabled="sendingId === inv.id"
                                            title="Enviar a SUNAT"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 disabled:opacity-40 transition-colors">
                                        <svg x-show="sendingId !== inv.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        <svg x-show="sendingId === inv.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    </template>

                    <tr x-show="filtered.length === 0">
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center
                                            {{ $esBoleta ? 'bg-emerald-50' : 'bg-blue-50' }}">
                                    <svg class="w-7 h-7 {{ $esBoleta ? 'text-emerald-300' : 'text-blue-300' }}"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-400"
                                       x-text="search ? 'Sin resultados para «' + search + '»' : 'No hay {{ $esBoleta ? 'boletas' : 'facturas' }} aún'"></p>
                                    <p class="text-xs text-gray-300 mt-1" x-show="!search">
                                        Haz clic en «Nueva {{ $esBoleta ? 'Boleta' : 'Factura' }}» para crear la primera
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── MODAL VER COMPROBANTE ── --}}
    <div x-show="modalVer" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.55)"
         @click.self="modalVer=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="font-mono text-lg font-bold text-gray-900" x-text="verData?.numero"></span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                          :class="verData?.sunat_status==='accepted' ? 'bg-emerald-50 text-emerald-600' :
                                  (verData?.sunat_status==='rejected' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600')"
                          x-text="verData?.sunat_status==='accepted' ? '✓ Aceptada SUNAT' :
                                  (verData?.sunat_status==='rejected' ? '✗ Rechazada SUNAT' : 'Pendiente SUNAT')">
                    </span>
                    <span x-show="verData?.quote_id"
                          class="px-2 py-0.5 rounded-full text-xs font-semibold bg-violet-50 text-violet-600">
                        Cotización #<span x-text="verData?.quote_id"></span>
                    </span>
                </div>
                <button @click="modalVer=false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1 p-6 space-y-5">
                <template x-if="verData">
                <div class="space-y-5">

                    {{-- Emisor + Cliente --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Emisor</p>
                            <p class="font-bold text-gray-800" x-text="verData.emisor_razon_social||'—'"></p>
                            <p class="text-sm text-gray-500 mt-0.5" x-show="verData.emisor_ruc" x-text="'RUC: ' + verData.emisor_ruc"></p>
                            <p class="text-sm text-gray-500" x-show="verData.emisor_direccion" x-text="verData.emisor_direccion"></p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Cliente</p>
                            <p class="font-bold text-gray-800" x-text="verData.client_name||'—'"></p>
                            <p class="text-sm text-gray-500 mt-0.5"
                               x-show="verData.client_doc_type||verData.client_doc_number"
                               x-text="(verData.client_doc_type||'') + ' ' + (verData.client_doc_number||'')"></p>
                            <p class="text-sm text-gray-500" x-show="verData.client_email" x-text="verData.client_email"></p>
                            <p class="text-sm text-gray-500" x-show="verData.client_phone" x-text="verData.client_phone"></p>
                            <p class="text-sm text-gray-500" x-show="verData.client_address" x-text="verData.client_address"></p>
                        </div>
                    </div>

                    {{-- Info comprobante --}}
                    <div class="grid grid-cols-4 gap-3">
                        <div class="text-sm"><p class="text-xs text-gray-400 mb-0.5">Fecha emisión</p><p class="font-medium text-gray-700" x-text="verData.issue_date||'—'"></p></div>
                        <div class="text-sm"><p class="text-xs text-gray-400 mb-0.5">Vencimiento</p><p class="font-medium text-gray-700" x-text="verData.due_date||'—'"></p></div>
                        <div class="text-sm"><p class="text-xs text-gray-400 mb-0.5">Moneda</p><p class="font-medium text-gray-700" x-text="verData.currency||'PEN'"></p></div>
                        <div class="text-sm"><p class="text-xs text-gray-400 mb-0.5">Método de pago</p><p class="font-medium text-gray-700" x-text="verData.payment_method||'—'"></p></div>
                    </div>

                    {{-- Items --}}
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        <div class="grid text-[10px] font-bold text-gray-400 uppercase tracking-wide bg-gray-50 border-b border-gray-100 px-4 py-2"
                             style="grid-template-columns: 1fr 70px 70px 90px 80px 90px;">
                            <div>Descripción</div>
                            <div class="text-center">U.M.</div>
                            <div class="text-center">Cant.</div>
                            <div class="text-right">P. Unit</div>
                            <div class="text-right">IGV</div>
                            <div class="text-right">Total</div>
                        </div>
                        <template x-for="(it, i) in (verData.items||[])" :key="i">
                        <div class="grid items-center px-4 py-2.5 border-b border-gray-50 last:border-0 text-sm"
                             :class="i%2===0 ? 'bg-white' : 'bg-gray-50/40'"
                             style="grid-template-columns: 1fr 70px 70px 90px 80px 90px;">
                            <span class="text-gray-700 pr-3" x-text="it.description"></span>
                            <span class="text-center text-gray-400 text-xs" x-text="it.unit||'NIU'"></span>
                            <span class="text-center text-gray-600" x-text="it.quantity"></span>
                            <span class="text-right text-gray-500" x-text="'S/ ' + parseFloat(it.unit_price||0).toFixed(2)"></span>
                            <span class="text-right text-gray-500" x-text="'S/ ' + parseFloat(it.igv_amount||0).toFixed(2)"></span>
                            <span class="text-right font-semibold text-gray-800" x-text="'S/ ' + parseFloat(it.total||0).toFixed(2)"></span>
                        </div>
                        </template>
                    </div>

                    {{-- Totales --}}
                    <div class="flex justify-end">
                        <div class="space-y-1.5 min-w-[240px]">
                            <div class="flex justify-between text-sm text-gray-500">
                                <span>Subtotal (sin IGV)</span>
                                <span x-text="'S/ ' + parseFloat(verData.subtotal||0).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-500">
                                <span>IGV 18%</span>
                                <span x-text="'S/ ' + parseFloat(verData.igv||0).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-900 border-t border-gray-200 pt-2">
                                <span>TOTAL</span>
                                <span x-text="'S/ ' + parseFloat(verData.total||0).toFixed(2)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div x-show="verData.notes"
                         class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3 text-sm text-amber-800"
                         x-text="verData.notes"></div>

                </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                <span x-show="verData?.quote_id"
                      class="text-xs text-violet-600 bg-violet-50 px-3 py-1.5 rounded-full font-medium">
                    Originada de Cotización #<span x-text="verData?.quote_id"></span>
                </span>
                <div class="flex gap-3 ml-auto">
                    <a :href="`{{ $pdfBase }}/` + (verData?.id||'') + '/pdf'" target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        PDF
                    </a>
                    <button @click="modalVer=false" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MODAL HISTORIAL ── --}}
    <div x-show="modalHist" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 class="font-bold text-gray-900">Historial SUNAT</h3>
                    <p class="text-xs text-gray-400" x-text="histData ? histData.numero : ''"></p>
                </div>
                <button @click="modalHist=false" class="text-gray-300 hover:text-gray-500 p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <template x-if="histData">
            <div class="px-6 py-4 space-y-3">
                <div class="flex items-center gap-3 p-3 rounded-xl"
                     :class="histData.sunat_status==='accepted'?'bg-emerald-50':'(histData.sunat_status?\'bg-red-50\':\'bg-amber-50\')">'">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                         :class="histData.sunat_status==='accepted'?'bg-emerald-200':'bg-amber-200'">
                        <svg class="w-4 h-4" :class="histData.sunat_status==='accepted'?'text-emerald-700':'text-amber-700'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold"
                           :class="histData.sunat_status==='accepted'?'text-emerald-700':'text-amber-700'"
                           x-text="histData.sunat_status==='accepted' ? '✓ Aceptada por SUNAT' : (histData.sunat_status==='rejected' ? '✗ Rechazada por SUNAT' : 'Pendiente de envío')"></p>
                        <p class="text-xs text-gray-500" x-text="histData.sunat_sent_at ? 'Enviada: ' + histData.sunat_sent_at : 'Aún no enviada'"></p>
                    </div>
                </div>
                <template x-if="histData.sunat_hash">
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="text-xs text-gray-400 mb-1">Hash SUNAT</p>
                    <p class="font-mono text-xs text-gray-600 break-all" x-text="histData.sunat_hash"></p>
                </div>
                </template>
                <template x-if="histData.sunat_error">
                <div class="bg-red-50 border border-red-100 rounded-xl p-3">
                    <p class="text-xs font-semibold text-red-600 mb-1">Error SUNAT</p>
                    <p class="text-xs text-red-700" x-text="histData.sunat_error"></p>
                </div>
                </template>
                <template x-if="!histData.sunat_status">
                <div class="text-center py-4 text-gray-400 text-sm">
                    Este comprobante aún no ha sido enviado a SUNAT.
                </div>
                </template>
            </div>
            </template>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                <button @click="modalHist=false" class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- MODAL eliminado — creación es página separada --}}
    <div x-show="false" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background:rgba(0,0,0,0.55)">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center
                                {{ $esBoleta ? 'bg-emerald-100' : 'bg-blue-100' }}">
                        <svg class="w-4 h-4 {{ $esBoleta ? 'text-emerald-600' : 'text-blue-600' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Nueva {{ $esBoleta ? 'Boleta' : 'Factura' }} Electrónica</h2>
                        <p class="text-xs text-gray-400">Serie: <span class="font-mono">{{ $serie }}</span></p>
                    </div>
                </div>
                <button @click="showModal=false" class="text-gray-300 hover:text-gray-500 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Error --}}
            <div x-show="errorMsg" x-cloak
                 class="mx-6 mt-3 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex gap-2 flex-shrink-0">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="errorMsg"></span>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4">

                {{-- Fecha --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Fecha de emisión</label>
                        <input type="date" x-model="form.issue_date"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Moneda</label>
                        <select x-model="form.currency"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                            <option value="PEN">S/ Soles (PEN)</option>
                            <option value="USD">$ Dólares (USD)</option>
                        </select>
                    </div>
                </div>

                {{-- Cliente --}}
                <div class="border border-gray-100 rounded-xl overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Datos del cliente</p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo documento</label>
                                <select x-model="form.client_doc_type"
                                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                                    <option value="">— ninguno —</option>
                                    <option value="DNI">DNI</option>
                                    <option value="RUC">RUC</option>
                                    <option value="CE">C. Extranjería</option>
                                    <option value="pasaporte">Pasaporte</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Número de documento
                                    <template x-if="buscandoRuc">
                                        <span class="text-blue-400 font-normal ml-1">
                                            <svg class="inline w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                            </svg>
                                            consultando...
                                        </span>
                                    </template>
                                </label>
                                <input type="text" x-model="form.client_doc_number"
                                       @input.debounce.600ms="buscarRuc()"
                                       placeholder="DNI o RUC"
                                       class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                            </div>
                        </div>

                        <div class="relative">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre / Razón Social <span class="text-red-400">*</span></label>
                            <input type="text" x-model="form.client_name" @input="filterClients()"
                                   placeholder="Se completa automáticamente con el RUC"
                                   class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                            <div x-show="clientSuggestions.length > 0"
                                 class="absolute z-20 top-full mt-1 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                                <template x-for="c in clientSuggestions" :key="c.id">
                                    <div @click="selectClient(c)"
                                         class="px-3 py-2.5 text-sm hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0"
                                         x-text="c.name"></div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
                            <input type="text" x-model="form.client_address"
                                   placeholder="Se completa automáticamente con el RUC"
                                   class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="border border-gray-100 rounded-xl overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Productos / Servicios</p>
                        <button type="button" @click="addItem()"
                                class="text-xs font-semibold flex items-center gap-1
                                       {{ $esBoleta ? 'text-emerald-600 hover:text-emerald-800' : 'text-blue-600 hover:text-blue-800' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar línea
                        </button>
                    </div>

                    <div class="grid grid-cols-12 gap-2 px-4 pt-3 pb-1">
                        <div class="col-span-5 text-[10px] font-semibold text-gray-400 uppercase">Descripción</div>
                        <div class="col-span-2 text-[10px] font-semibold text-gray-400 uppercase text-center">Cant.</div>
                        <div class="col-span-2 text-[10px] font-semibold text-gray-400 uppercase text-right">P. Unit.</div>
                        <div class="col-span-2 text-[10px] font-semibold text-gray-400 uppercase text-right">Total</div>
                        <div class="col-span-1"></div>
                    </div>

                    <div class="px-4 pb-3 space-y-2">
                        <template x-for="(item, idx) in form.items" :key="idx">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <input type="text" x-model="item.description" placeholder="Descripción"
                                   class="col-span-5 border border-gray-200 rounded-lg px-2.5 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                            <input type="number" x-model="item.quantity" min="0.001" step="0.001"
                                   class="col-span-2 border border-gray-200 rounded-lg px-2 py-2 text-xs text-center focus:outline-none focus:ring-1 focus:ring-blue-400">
                            <input type="number" x-model="item.unit_price" min="0" step="0.01" placeholder="0.00"
                                   class="col-span-2 border border-gray-200 rounded-lg px-2 py-2 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400">
                            <span class="col-span-2 text-right text-xs font-bold text-gray-700"
                                  x-text="'S/ ' + lineTotal(item).toFixed(2)"></span>
                            <button type="button" @click="removeItem(idx)" x-show="form.items.length > 1"
                                    class="col-span-1 flex justify-center text-gray-300 hover:text-red-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        </template>
                    </div>

                    <div class="border-t border-gray-100 px-4 py-3 bg-gray-50/50 flex justify-end">
                        <div class="space-y-1.5 min-w-[220px]">
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>Subtotal sin IGV</span>
                                <span x-text="'S/ ' + subtotal().toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>IGV 18%</span>
                                <span x-text="'S/ ' + igvTotal().toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-gray-900 border-t border-gray-200 pt-2">
                                <span>Total a pagar</span>
                                <span x-text="'S/ ' + grandTotal().toFixed(2)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Observaciones <span class="font-normal text-gray-400">(opcional)</span></label>
                    <textarea x-model="form.notes" rows="2" placeholder="Ej: Pago al contado, gracias por su compra..."
                              class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }} resize-none"></textarea>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 flex-shrink-0 bg-gray-50/30">
                <p class="text-xs text-gray-400">Los precios incluyen IGV 18%</p>
                <div class="flex gap-3">
                    <button @click="showModal=false"
                            class="px-4 py-2 text-sm text-gray-600 font-medium rounded-xl border border-gray-200 hover:bg-gray-100 transition-colors">
                        Cancelar
                    </button>
                    <button @click="saveInvoice()" :disabled="saving"
                            class="px-6 py-2 text-white text-sm font-bold rounded-xl transition-colors disabled:opacity-60 flex items-center gap-2
                                   {{ $esBoleta ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }}">
                        <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span x-text="saving ? 'Emitiendo...' : 'Emitir {{ $esBoleta ? 'Boleta' : 'Factura' }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function factPage() {
    return {
        invoices: @json($invoices->values()),
        docType:      '{{ $docType }}',
        search: '',
        sendingId: null,
        modalVer:  false,
        verData:   null,
        modalHist: false,
        histData:  null,
        modalVer:  false,
        verData:   null,
        modalHist: false,
        histData:  null,

        init() {},

        async verFactura(inv) {
            const url  = '{{ $showUrl }}'.replace('__ID__', inv.id);
            const res  = await fetch(url, { headers:{ 'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' } });
            this.verData  = await res.json();
            this.modalVer = true;
        },

        async verHistorial(inv) {
            const url  = '{{ $showUrl }}'.replace('__ID__', inv.id);
            const res  = await fetch(url, { headers:{ 'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' } });
            this.histData  = await res.json();
            this.modalHist = true;
        },

        get filtered() {
            if (!this.search.trim()) return this.invoices;
            const q = this.search.toLowerCase();
            return this.invoices.filter(i =>
                (i.numero || '').toLowerCase().includes(q) ||
                (i.client_name || '').toLowerCase().includes(q)
            );
        },

        async enviarSunat(inv) {
            this.sendingId = inv.id;
            try {
                const res  = await fetch('{{ $sunatUrl }}'.replace('__ID__', inv.id), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                });
                const data = await res.json();
                if (data.ok) {
                    inv.sunat_status = 'accepted';
                    alert('✅ Aceptada por SUNAT correctamente.');
                } else {
                    alert('❌ ' + (data.message ?? 'Error desconocido'));
                }
            } catch(e) { alert('❌ Error de red: ' + e.message); }
            this.sendingId = null;
        },

    };
}
</script>

</x-facturacion-layout>

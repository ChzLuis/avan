@php
$clientsJson = $clients->map(function($c){ return ['id'=>$c->id,'name'=>$c->name,'phone'=>$c->phone??'','email'=>$c->email??'']; })->values();
$quoteItems  = $quote->items->map(function($i){ return ['desc'=>$i->description,'unit'=>$i->unit??'NIU','qty'=>(float)$i->quantity,'price'=>(float)$i->price,'showSugg'=>false,'suggestions'=>[]]; })->values();
@endphp

<x-facturacion-layout :project="$project" pageTitle="Editar Cotización">

<div class="flex-1 overflow-y-auto bg-gray-100"
     x-data="cotizEditPage()"
     x-init="init()">

    {{-- ── TOP BAR ── --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between sticky top-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ $indexUrl }}" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <span class="text-sm font-bold text-gray-800">Cotización</span>
                <span class="ml-2 text-sm text-gray-400">· {{ $quote->client_name }}</span>
            </div>
            @if($quote->status === 'accepted')
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600">Aceptada</span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ $indexUrl }}" class="px-4 py-2 text-sm text-gray-600 font-medium rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button @click="modalConvertir=true"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl border-2 border-emerald-300 text-emerald-700 hover:bg-emerald-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Convertir a comprobante
            </button>
            <button @click="guardar()" :disabled="saving"
                    class="px-6 py-2 text-sm font-bold rounded-xl text-white bg-violet-600 hover:bg-violet-700 disabled:opacity-60 flex items-center gap-2 transition-colors">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-text="saving ? 'Guardando...' : 'Guardar cambios'"></span>
            </button>
        </div>
    </div>

    {{-- ERROR --}}
    <div x-show="errorMsg" x-cloak class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex gap-2">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="errorMsg"></span>
    </div>

    <div class="p-6 space-y-4 max-w-6xl mx-auto">

        {{-- ── ENCABEZADO ── --}}
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="h-1.5 bg-violet-500"></div>
            <div class="p-6 grid grid-cols-3 gap-6">

                {{-- EMISOR --}}
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Emisor</p>
                    <p class="text-base font-bold text-gray-900">{{ $project->setting('razon_social') ?? $project->name }}</p>
                    @if($project->setting('ruc'))
                    <p class="text-sm text-gray-500 mt-1">RUC: <span class="font-mono font-semibold text-gray-700">{{ $project->setting('ruc') }}</span></p>
                    @endif
                    @if($project->address)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $project->address }}</p>
                    @endif
                </div>

                {{-- DATOS COTIZACIÓN --}}
                <div class="border-x border-gray-100 px-6 space-y-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Cotización</p>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Válida hasta</label>
                        <input type="date" x-model="form.valid_until"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Método de pago</label>
                        <select x-model="form.payment_method"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-400">
                            <option value="">— Seleccionar —</option>
                            <option>Contado</option><option>Yape</option><option>Plin</option>
                            <option>Transferencia bancaria</option><option>Tarjeta de crédito</option>
                            <option>Crédito 30 días</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Condición</label>
                        <select x-model="form.payment_condition"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-400">
                            <option value="Contado">Contado</option>
                            <option value="Crédito">Crédito</option>
                        </select>
                    </div>
                </div>

                {{-- CLIENTE --}}
                <div class="space-y-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Cliente</p>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Tipo doc.</label>
                            <select x-model="form.client_doc_type"
                                    class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-400">
                                <option value="">— —</option>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">CE</option>
                                <option value="pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">
                                Número
                                <span x-show="buscandoRuc" class="text-violet-400 normal-case font-normal">
                                    <svg class="inline w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </span>
                            </label>
                            <input type="text" x-model="form.client_doc_number"
                                   @input.debounce.600ms="buscarRuc()"
                                   placeholder="RUC / DNI"
                                   class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                        </div>
                    </div>

                    <div class="relative">
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Nombre / Razón Social <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.client_name" @input="filterClients()"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                        <div x-show="clientSugg.length > 0"
                             class="absolute z-30 top-full mt-1 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                            <template x-for="c in clientSugg" :key="c.id">
                                <div @click="selectClient(c)" class="px-3 py-2.5 text-sm hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0" x-text="c.name"></div>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Dirección</label>
                        <input type="text" x-model="form.client_address"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Teléfono</label>
                            <input type="text" x-model="form.client_phone"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Email</label>
                            <input type="email" x-model="form.client_email"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TABLA DE PRODUCTOS ── --}}
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Productos / Servicios</p>
                <button type="button" @click="addLine()"
                        class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg text-violet-600 hover:bg-violet-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar línea
                </button>
            </div>

            <div class="grid text-[10px] font-bold text-gray-400 uppercase tracking-wide bg-gray-50 border-b border-gray-200"
                 style="grid-template-columns:36px 1fr 80px 80px 120px 110px 36px; padding:8px 16px;">
                <div class="text-center">#</div>
                <div>Descripción</div>
                <div class="text-center">U.M.</div>
                <div class="text-center">Cant.</div>
                <div class="text-right">Precio unit.</div>
                <div class="text-right">Total</div>
                <div></div>
            </div>

            <div>
                <template x-for="(line, idx) in form.lines" :key="idx">
                <div class="grid items-center border-b border-gray-100"
                     :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'"
                     style="grid-template-columns:36px 1fr 80px 80px 120px 110px 36px; padding:6px 16px; gap:6px;">

                    <div class="text-center text-xs text-gray-400 font-semibold" x-text="idx+1"></div>

                    <div class="relative">
                        <input type="text" x-model="line.desc"
                               @input="filterCatalog(idx)"
                               @focus="filterCatalog(idx)"
                               @keydown.escape="line.showSugg=false"
                               placeholder="Buscar producto o servicio..."
                               class="w-full border-0 bg-transparent text-sm text-gray-800 focus:outline-none focus:bg-white focus:border focus:border-violet-300 focus:rounded-lg focus:px-2 py-1 placeholder-gray-300">
                        <div x-show="line.showSugg && line.suggestions.length > 0"
                             @click.outside="line.showSugg=false"
                             class="absolute z-40 top-full mt-1 left-0 w-80 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                            <template x-for="s in line.suggestions" :key="s.id">
                                <div @click="selectProduct(idx, s)"
                                     class="flex items-center justify-between px-3 py-2.5 hover:bg-violet-50 cursor-pointer border-b border-gray-50 last:border-0">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800" x-text="s.name"></p>
                                        <p class="text-xs text-gray-400" x-text="s.desc" x-show="s.desc !== s.name"></p>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 ml-3 flex-shrink-0" x-text="'S/ ' + s.price.toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <input type="text" x-model="line.unit" placeholder="NIU"
                           class="border-0 bg-transparent text-xs text-center text-gray-500 focus:outline-none focus:bg-white focus:border focus:border-violet-300 focus:rounded-lg py-1 w-full">

                    <input type="number" x-model="line.qty" min="0.001" step="0.001"
                           class="border-0 bg-transparent text-xs text-center font-semibold text-gray-800 focus:outline-none focus:bg-white focus:border focus:border-violet-300 focus:rounded-lg py-1 w-full">

                    <input type="number" x-model="line.price" min="0" step="0.01"
                           class="border-0 bg-transparent text-sm text-right font-semibold text-gray-800 focus:outline-none focus:bg-white focus:border focus:border-violet-300 focus:rounded-lg py-1 w-full">

                    <div class="text-right text-sm font-bold text-gray-800"
                         x-text="'S/ ' + lineTotal(line).toFixed(2)"></div>

                    <div class="flex justify-center">
                        <button type="button" @click="removeLine(idx)" x-show="form.lines.length > 1"
                                class="text-gray-300 hover:text-red-400 transition-colors p-1 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                </template>
            </div>

            <div class="flex items-end justify-between p-5">
                <button type="button" @click="addLine()"
                        class="flex items-center gap-2 text-sm text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar otra línea
                </button>
                <div class="min-w-[220px] space-y-2">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Subtotal</span>
                        <span x-text="'S/ ' + grandTotal().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 border-t-2 border-gray-200 pt-2">
                        <span>TOTAL</span>
                        <span class="text-violet-600" x-text="'S/ ' + grandTotal().toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- OBSERVACIONES --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Observaciones</label>
            <textarea x-model="form.notes" rows="3"
                      class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 resize-none"></textarea>
        </div>

        <div class="flex justify-between gap-3 pb-6">
            <button @click="modalConvertir=true"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-xl border-2 border-emerald-300 text-emerald-700 hover:bg-emerald-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                Convertir a comprobante
            </button>
            <div class="flex gap-3 ml-auto">
                <a href="{{ $indexUrl }}" class="px-5 py-2.5 text-sm text-gray-600 font-medium rounded-xl border border-gray-200 hover:bg-gray-50">
                    Cancelar
                </a>
                <button @click="guardar()" :disabled="saving"
                        class="px-8 py-2.5 text-sm font-bold rounded-xl text-white bg-violet-600 hover:bg-violet-700 disabled:opacity-60 flex items-center gap-2">
                    <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span x-text="saving ? 'Guardando...' : 'Guardar cambios'"></span>
                </button>
            </div>
        </div>

    </div>

    {{-- ── MODAL CONVERTIR ── --}}
    <div x-show="modalConvertir" x-cloak
         class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="modalConvertir=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Convertir a Comprobante</h3>
                <button @click="modalConvertir=false" class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 pt-4 pb-2">
                <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-amber-800">Se guardarán los cambios actuales de la cotización antes de continuar. La cotización se mantendrá como referencia.</p>
                </div>
            </div>
            <div class="px-6 pb-6 space-y-3">
                <p class="text-sm text-gray-500 font-medium mb-3">¿Qué tipo de comprobante deseas emitir?</p>
                <button @click="convertir('boleta')" :disabled="converting"
                        class="w-full flex items-center gap-4 p-4 border-2 border-emerald-200 hover:border-emerald-400 rounded-2xl transition-colors group disabled:opacity-50">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 group-hover:bg-emerald-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="text-left flex-1">
                        <p class="font-semibold text-gray-800">Boleta de Venta</p>
                        <p class="text-xs text-gray-400">Para personas naturales (DNI)</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button @click="convertir('factura')" :disabled="converting"
                        class="w-full flex items-center gap-4 p-4 border-2 border-blue-200 hover:border-blue-400 rounded-2xl transition-colors group disabled:opacity-50">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 group-hover:bg-blue-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="text-left flex-1">
                        <p class="font-semibold text-gray-800">Factura</p>
                        <p class="text-xs text-gray-400">Para empresas (RUC)</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div x-show="converting" class="text-center py-2 text-sm text-gray-400">
                    <svg class="w-5 h-5 animate-spin mx-auto text-violet-500 mb-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    Guardando cotización...
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function cotizEditPage() {
    const catalogo   = @json($catalogo->values());
    const allClients = @json($clientsJson);
    const initLines  = @json($quoteItems);

    return {
        saving: false,
        converting: false,
        buscandoRuc: false,
        modalConvertir: false,
        errorMsg: '',
        clientSugg: [],
        form: {
            client_name:       '{{ addslashes($quote->client_name) }}',
            client_phone:      '{{ addslashes($quote->client_phone ?? '') }}',
            client_email:      '{{ addslashes($quote->client_email ?? '') }}',
            client_doc_type:   '{{ $quote->client_doc_type ?? '' }}',
            client_doc_number: '{{ $quote->client_doc_number ?? '' }}',
            client_address:    '',
            valid_until:       '{{ $quote->valid_until?->format('Y-m-d') ?? '' }}',
            payment_method:    '{{ addslashes($quote->payment_method ?? '') }}',
            payment_condition: '{{ addslashes($quote->payment_condition ?? 'Contado') }}',
            notes:             '',
            lines:             [],
        },

        init() {
            this.form.notes          = @json($quote->notes ?? '');
            this.form.client_address = @json($quote->client_address ?? '');
            this.form.lines = initLines.length > 0
                ? initLines
                : [{ desc:'', unit:'NIU', qty:1, price:0, showSugg:false, suggestions:[] }];
        },

        lineTotal(l)  { return (parseFloat(l.qty)||0) * (parseFloat(l.price)||0); },
        grandTotal()  { return this.form.lines.reduce((s,l) => s + this.lineTotal(l), 0); },
        addLine()     { this.form.lines.push({ desc:'', unit:'NIU', qty:1, price:0, showSugg:false, suggestions:[] }); },
        removeLine(i) { this.form.lines.splice(i, 1); },

        filterCatalog(idx) {
            const q   = (this.form.lines[idx].desc||'').toLowerCase();
            const res = q.length < 1
                ? catalogo.slice(0, 8)
                : catalogo.filter(p => p.name.toLowerCase().includes(q)||(p.desc||'').toLowerCase().includes(q)).slice(0,10);
            this.form.lines[idx].suggestions = res;
            this.form.lines[idx].showSugg    = res.length > 0;
        },
        selectProduct(idx, s) {
            this.form.lines[idx].desc     = s.name;
            this.form.lines[idx].price    = s.price;
            this.form.lines[idx].showSugg = false;
        },
        filterClients() {
            const q = (this.form.client_name||'').toLowerCase();
            if (q.length < 2) { this.clientSugg = []; return; }
            this.clientSugg = allClients.filter(c => c.name.toLowerCase().includes(q)).slice(0,6);
        },
        selectClient(c) {
            this.form.client_name  = c.name;
            this.form.client_phone = c.phone;
            this.form.client_email = c.email;
            this.clientSugg = [];
        },

        async buscarRuc() {
            const ruc = (this.form.client_doc_number||'').replace(/\D/g,'');
            if (this.form.client_doc_type !== 'RUC' || ruc.length !== 11) return;
            this.buscandoRuc = true;
            try {
                const res  = await fetch('{{ $rucUrl }}?ruc=' + ruc, {
                    headers:{ 'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.ok) {
                    this.form.client_name    = data.razon_social;
                    this.form.client_address = data.direccion;
                } else {
                    this.errorMsg = data.message || 'RUC no encontrado.';
                    setTimeout(() => this.errorMsg = '', 4000);
                }
            } catch(e) {}
            this.buscandoRuc = false;
        },

        buildPayload() {
            return {
                client_name:       this.form.client_name,
                client_phone:      this.form.client_phone,
                client_email:      this.form.client_email,
                client_doc_type:   this.form.client_doc_type,
                client_doc_number: this.form.client_doc_number,
                client_address:    this.form.client_address,
                valid_until:       this.form.valid_until,
                payment_method:    this.form.payment_method,
                payment_condition: this.form.payment_condition,
                notes:             this.form.notes,
                items: this.form.lines.map(l => ({
                    description: l.desc,
                    quantity:    parseFloat(l.qty)   || 1,
                    price:       parseFloat(l.price) || 0,
                })),
            };
        },

        async guardar() {
            this.errorMsg = '';
            if (!this.form.client_name.trim()) { this.errorMsg = 'El nombre del cliente es obligatorio.'; return; }
            if (this.form.lines.some(l => !l.desc.trim())) { this.errorMsg = 'Todas las líneas deben tener descripción.'; return; }
            if (this.form.lines.some(l => parseFloat(l.price) <= 0)) { this.errorMsg = 'Todos los precios deben ser mayores a 0.'; return; }
            this.saving = true;
            try {
                const res  = await fetch('{{ $updateUrl }}', {
                    method: 'PUT',
                    headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' },
                    body: JSON.stringify(this.buildPayload()),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.errorMsg = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : (data.message || 'Error al guardar.');
                } else {
                    window.location.href = '{{ $indexUrl }}';
                }
            } catch(e) { this.errorMsg = 'Error de red. Intenta de nuevo.'; }
            this.saving = false;
        },

        async convertir(tipo) {
            this.errorMsg = '';
            if (!this.form.client_name.trim()) { this.errorMsg = 'El nombre del cliente es obligatorio.'; this.modalConvertir=false; return; }
            if (this.form.lines.some(l => !l.desc.trim())) { this.errorMsg = 'Todas las líneas deben tener descripción.'; this.modalConvertir=false; return; }
            this.converting = true;
            try {
                const res  = await fetch('{{ $updateUrl }}', {
                    method: 'PUT',
                    headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' },
                    body: JSON.stringify(this.buildPayload()),
                });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    this.errorMsg   = data.message || 'Error al guardar.';
                    this.converting = false;
                    this.modalConvertir = false;
                    return;
                }
                const base = tipo === 'boleta' ? '{{ $boletaCreateUrl }}' : '{{ $facturaCreateUrl }}';
                window.location.href = base + '?from_quote={{ $quote->id }}';
            } catch(e) {
                this.errorMsg   = 'Error de conexión.';
                this.converting = false;
                this.modalConvertir = false;
            }
        },
    };
}
</script>

</x-facturacion-layout>

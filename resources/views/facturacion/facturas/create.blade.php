@php
    $esBoleta   = $docType === 'boleta';
    $titulo     = $esBoleta ? 'Nueva Boleta Electrónica' : 'Nueva Factura Electrónica';
    $serie      = $esBoleta ? $serieBoleta : $serieFactura;
    $storeUrl   = route('facturacion.facturas.store', $project->slug);
    $rucUrl     = route('facturacion.ruc.lookup', $project->slug);
    $listUrl    = $esBoleta ? route('facturacion.boletas', $project->slug) : route('facturacion.facturas', $project->slug);
@endphp

<x-facturacion-layout :project="$project" :pageTitle="$titulo">

<div class="flex-1 overflow-y-auto bg-gray-100"
     x-data="emisionPage()"
     x-init="init()">

    {{-- ── TOP BAR ── --}}
    <div class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between sticky top-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ $listUrl }}"
               class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-gray-800">{{ $titulo }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full font-mono font-semibold
                             {{ $esBoleta ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                    {{ $serie }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ $listUrl }}"
               class="px-4 py-2 text-sm text-gray-600 font-medium rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button @click="guardar(false)"
                    :disabled="saving"
                    class="px-5 py-2 text-sm font-semibold rounded-xl border transition-colors disabled:opacity-50
                           {{ $esBoleta ? 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' : 'border-blue-300 text-blue-700 hover:bg-blue-50' }}">
                Guardar borrador
            </button>
            <button @click="guardar(true)"
                    :disabled="saving"
                    class="px-6 py-2 text-sm font-bold rounded-xl text-white transition-colors disabled:opacity-60 flex items-center gap-2
                           {{ $esBoleta ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }}">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-text="saving ? 'Emitiendo...' : 'Emitir {{ $esBoleta ? 'Boleta' : 'Factura' }}'"></span>
            </button>
        </div>
    </div>

    {{-- Banner desde cotización --}}
    @if(!empty($fromQuote))
    <div class="mx-6 mt-4 p-3 bg-violet-50 border border-violet-200 rounded-xl text-sm text-violet-700 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        <span>Generando desde <strong>Cotización #{{ $fromQuote['id'] }}</strong> · {{ $fromQuote['client_name'] }}. Revisa y ajusta los datos antes de emitir.</span>
    </div>
    @endif

    {{-- ── ERROR ── --}}
    <div x-show="errorMsg" x-cloak
         class="mx-6 mt-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 flex gap-2">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span x-text="errorMsg"></span>
    </div>

    <div class="p-6 space-y-4 max-w-6xl mx-auto">

        {{-- ── ENCABEZADO: EMISOR + COMPROBANTE + CLIENTE ── --}}
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

            {{-- Banda superior con color --}}
            <div class="h-1.5 {{ $esBoleta ? 'bg-emerald-500' : 'bg-blue-600' }}"></div>

            <div class="p-6 grid grid-cols-3 gap-6">

                {{-- EMISOR --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Emisor</p>
                    <p class="text-base font-bold text-gray-900">{{ $emisorRazon }}</p>
                    @if($emisorRuc)
                    <p class="text-sm text-gray-500">RUC: <span class="font-mono font-semibold text-gray-700">{{ $emisorRuc }}</span></p>
                    @endif
                    @if($emisorDir)
                    <p class="text-sm text-gray-500">{{ $emisorDir }}</p>
                    @endif
                    <p class="text-sm text-gray-500">{{ $project->phone ?? '' }}</p>
                </div>

                {{-- DATOS COMPROBANTE --}}
                <div class="border-x border-gray-100 px-6 space-y-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Comprobante</p>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Fecha de emisión</label>
                        <input type="date" x-model="form.issue_date"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Fecha de vencimiento</label>
                        <input type="date" x-model="form.due_date"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Moneda</label>
                        <select x-model="form.currency"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                            <option value="PEN">S/ Soles (PEN)</option>
                            <option value="USD">$ Dólares (USD)</option>
                        </select>
                    </div>
                </div>

                {{-- CLIENTE --}}
                <div class="space-y-3">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Cliente</p>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Tipo doc.</label>
                            <select x-model="form.client_doc_type"
                                    class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
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
                                <span x-show="buscandoRuc" class="text-blue-400 normal-case font-normal">
                                    <svg class="inline w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                </span>
                            </label>
                            <input type="text" x-model="form.client_doc_number"
                                   @input.debounce.600ms="buscarRuc()"
                                   placeholder="RUC / DNI"
                                   class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                        </div>
                    </div>

                    <div class="relative">
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Nombre / Razón social <span class="text-red-400 normal-case">*</span></label>
                        <input type="text" x-model="form.client_name"
                               @input="filterClients()"
                               placeholder="Nombre completo o razón social"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                        <div x-show="clientSugg.length > 0"
                             class="absolute z-30 top-full mt-1 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                            <template x-for="c in clientSugg" :key="c.id">
                                <div @click="selectClient(c)"
                                     class="px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0"
                                     x-text="c.name"></div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Dirección</label>
                        <input type="text" x-model="form.client_address"
                               placeholder="Dirección fiscal"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TABLA DE PRODUCTOS (estilo Excel) ── --}}
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Detalle de productos / servicios</p>
                <button type="button" @click="addLine()"
                        class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors
                               {{ $esBoleta ? 'text-emerald-600 hover:bg-emerald-50' : 'text-blue-600 hover:bg-blue-50' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar línea
                </button>
            </div>

            {{-- Cabecera tabla --}}
            <div class="grid items-center text-[10px] font-bold text-gray-400 uppercase tracking-wide bg-gray-50 border-b border-gray-200"
                 style="grid-template-columns: 36px 1fr 80px 80px 110px 90px 90px 110px 36px; padding: 8px 16px;">
                <div class="text-center">#</div>
                <div>Descripción</div>
                <div class="text-center">U.M.</div>
                <div class="text-center">Cant.</div>
                <div class="text-right">P. Unit (c/IGV)</div>
                <div class="text-right">V. Venta</div>
                <div class="text-right">IGV</div>
                <div class="text-right">Total</div>
                <div></div>
            </div>

            {{-- Filas --}}
            <div>
                <template x-for="(line, idx) in form.lines" :key="idx">
                <div class="grid items-center border-b border-gray-100 transition-colors"
                     :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'"
                     style="grid-template-columns: 36px 1fr 80px 80px 110px 90px 90px 110px 36px; padding: 6px 16px; gap: 6px;">

                    {{-- # --}}
                    <div class="text-center text-xs text-gray-400 font-semibold" x-text="idx + 1"></div>

                    {{-- Descripción con búsqueda predictiva --}}
                    <div class="relative">
                        <input type="text"
                               x-model="line.desc"
                               @input="filterCatalog(idx)"
                               @focus="filterCatalog(idx)"
                               @keydown.escape="line.showSugg = false"
                               placeholder="Buscar producto o servicio..."
                               class="w-full border-0 bg-transparent text-sm text-gray-800 focus:outline-none focus:bg-white focus:border focus:border-blue-300 focus:rounded-lg focus:px-2 py-1 placeholder-gray-300">
                        <div x-show="line.showSugg && line.suggestions.length > 0"
                             @click.outside="line.showSugg = false"
                             class="absolute z-40 top-full mt-1 left-0 w-72 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                            <template x-for="s in line.suggestions" :key="s.id">
                                <div @click="selectProduct(idx, s)"
                                     class="flex items-center justify-between px-3 py-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0">
                                    <div>
                                        <p class="text-sm font-medium text-gray-800" x-text="s.name"></p>
                                        <p class="text-xs text-gray-400" x-text="s.desc" x-show="s.desc !== s.name"></p>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 ml-3 flex-shrink-0"
                                          x-text="'S/ ' + s.price.toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- U.M. --}}
                    <div>
                        <input type="text" x-model="line.unit" placeholder="NIU"
                               class="w-full border-0 bg-transparent text-xs text-center text-gray-500 focus:outline-none focus:bg-white focus:border focus:border-blue-300 focus:rounded-lg py-1">
                    </div>

                    {{-- Cantidad --}}
                    <div>
                        <input type="number" x-model="line.qty" min="0.001" step="0.001"
                               class="w-full border-0 bg-transparent text-xs text-center text-gray-800 font-semibold focus:outline-none focus:bg-white focus:border focus:border-blue-300 focus:rounded-lg py-1">
                    </div>

                    {{-- Precio unitario c/IGV --}}
                    <div>
                        <input type="number" x-model="line.price" min="0" step="0.01"
                               class="w-full border-0 bg-transparent text-sm text-right text-gray-800 font-semibold focus:outline-none focus:bg-white focus:border focus:border-blue-300 focus:rounded-lg py-1">
                    </div>

                    {{-- Valor venta (sin IGV) --}}
                    <div class="text-right text-xs text-gray-500"
                         x-text="'S/ ' + lineValorVenta(line).toFixed(2)"></div>

                    {{-- IGV --}}
                    <div class="text-right text-xs text-gray-500"
                         x-text="'S/ ' + lineIgv(line).toFixed(2)"></div>

                    {{-- Total --}}
                    <div class="text-right text-sm font-bold text-gray-800"
                         x-text="'S/ ' + lineTotal(line).toFixed(2)"></div>

                    {{-- Eliminar --}}
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

            {{-- Botón agregar + Totales --}}
            <div class="flex items-end justify-between p-5">
                <div class="space-y-1">
                    <button type="button" @click="addLine()"
                            class="flex items-center gap-2 text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Agregar otra línea
                    </button>
                    <p class="text-xs text-gray-400 mt-3">Precios incluyen IGV 18%</p>
                </div>

                <div class="min-w-[260px] space-y-2">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Valor venta (sin IGV)</span>
                        <span x-text="'S/ ' + totales.sub.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>IGV 18%</span>
                        <span x-text="'S/ ' + totales.igv.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 border-t-2 border-gray-200 pt-2">
                        <span>TOTAL A PAGAR</span>
                        <span class="{{ $esBoleta ? 'text-emerald-600' : 'text-blue-600' }}"
                              x-text="'S/ ' + totales.total.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── OBSERVACIONES + MÉTODO DE PAGO ── --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 grid grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Observaciones</label>
                <textarea x-model="form.notes" rows="3"
                          placeholder="Ej: Pago al contado. Gracias por su preferencia."
                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }} resize-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Método de pago</label>
                <select x-model="form.payment_method"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }} mb-3">
                    <option value="">— Seleccionar —</option>
                    <option value="Contado">Contado</option>
                    <option value="Yape">Yape</option>
                    <option value="Plin">Plin</option>
                    <option value="Transferencia bancaria">Transferencia bancaria</option>
                    <option value="Tarjeta de crédito">Tarjeta de crédito</option>
                    <option value="Tarjeta de débito">Tarjeta de débito</option>
                    <option value="Crédito 30 días">Crédito 30 días</option>
                </select>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Condición de pago</label>
                <select x-model="form.payment_condition"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                    <option value="Contado">Contado</option>
                    <option value="Crédito">Crédito</option>
                </select>
            </div>
        </div>

        {{-- Botones finales --}}
        <div class="flex justify-end gap-3 pb-6">
            <a href="{{ $listUrl }}"
               class="px-5 py-2.5 text-sm text-gray-600 font-medium rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancelar
            </a>
            <button @click="guardar(false)" :disabled="saving"
                    class="px-5 py-2.5 text-sm font-semibold rounded-xl border transition-colors disabled:opacity-50
                           {{ $esBoleta ? 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' : 'border-blue-300 text-blue-700 hover:bg-blue-50' }}">
                Guardar borrador
            </button>
            <button @click="guardar(true)" :disabled="saving"
                    class="px-8 py-2.5 text-sm font-bold rounded-xl text-white transition-colors disabled:opacity-60 flex items-center gap-2
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

@php
$clientsForJs = $clients->map(function($c){ return ['id'=>$c->id,'name'=>$c->name]; })->values();
@endphp
<script>
function emisionPage() {
    const catalogo   = @json($catalogo->values());
    const allClients = @json($clientsForJs);
    const fromQuote  = @json($fromQuote ?? null);

    return {
        catalogo,
        allClients,
        clientSugg: [],
        buscandoRuc: false,
        saving: false,
        errorMsg: '',

        form: {
            type:              '{{ $docType }}',
            serie:             '{{ $serie }}',
            issue_date:        new Date().toISOString().split('T')[0],
            due_date:          '',
            currency:          'PEN',
            client_name:       '',
            client_doc_type:   '',
            client_doc_number: '',
            client_address:    '',
            notes:             '',
            payment_method:    '',
            payment_condition: 'Contado',
            quote_id:          null,
            lines: [
                { desc:'', unit:'NIU', qty:1, price:0, showSugg:false, suggestions:[] }
            ],
        },

        init() {
            if (fromQuote) {
                this.form.client_name       = fromQuote.client_name       || '';
                this.form.client_doc_type   = fromQuote.client_doc_type   || '';
                this.form.client_doc_number = fromQuote.client_doc_number || '';
                this.form.client_address    = fromQuote.client_address    || '';
                this.form.notes             = fromQuote.notes             || '';
                this.form.payment_method    = fromQuote.payment_method    || '';
                this.form.quote_id          = fromQuote.id;
                if (fromQuote.items && fromQuote.items.length > 0) {
                    this.form.lines = fromQuote.items.map(i => ({
                        desc: i.desc, unit: i.unit||'NIU', qty: i.qty||1, price: i.price||0,
                        showSugg: false, suggestions: [],
                    }));
                }
            }
        },

        get totales() {
            const sub   = this.form.lines.reduce((s,l) => s + this.lineValorVenta(l), 0);
            const igv   = this.form.lines.reduce((s,l) => s + this.lineIgv(l), 0);
            const total = this.form.lines.reduce((s,l) => s + this.lineTotal(l), 0);
            return { sub, igv, total };
        },

        lineTotal(l)      { return (parseFloat(l.qty)||0) * (parseFloat(l.price)||0); },
        lineValorVenta(l) { return this.lineTotal(l) / 1.18; },
        lineIgv(l)        { return this.lineTotal(l) - this.lineValorVenta(l); },

        addLine() {
            this.form.lines.push({ desc:'', unit:'NIU', qty:1, price:0, showSugg:false, suggestions:[] });
        },
        removeLine(idx) { this.form.lines.splice(idx, 1); },

        filterCatalog(idx) {
            const q   = (this.form.lines[idx].desc || '').toLowerCase();
            const res = q.length < 1
                ? catalogo.slice(0, 8)
                : catalogo.filter(p => p.name.toLowerCase().includes(q) || (p.desc||'').toLowerCase().includes(q)).slice(0, 10);
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
        selectClient(c) { this.form.client_name = c.name; this.clientSugg = []; },

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

        async guardar(emitir) {
            this.errorMsg = '';
            if (!this.form.client_name.trim()) { this.errorMsg = 'El nombre del cliente es obligatorio.'; return; }
            if (this.form.lines.some(l => !l.desc.trim())) { this.errorMsg = 'Todas las líneas deben tener descripción.'; return; }
            if (this.form.lines.some(l => parseFloat(l.price) <= 0)) { this.errorMsg = 'Todos los precios deben ser mayores a 0.'; return; }

            this.saving = true;

            const payload = {
                ...this.form,
                igv_included: true,
                status: emitir ? 'issued' : 'draft',
                quote_id: this.form.quote_id || null,
                items: this.form.lines.map(l => ({
                    description: l.desc,
                    unit:        l.unit || 'NIU',
                    quantity:    parseFloat(l.qty)   || 1,
                    unit_price:  parseFloat(l.price) || 0,
                })),
            };

            try {
                const res  = await fetch('{{ $storeUrl }}', {
                    method: 'POST',
                    headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.errorMsg = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : (data.message || 'Error al guardar.');
                } else {
                    window.location.href = '{{ $listUrl }}';
                }
            } catch(e) { this.errorMsg = 'Error de red. Intenta de nuevo.'; }
            this.saving = false;
        },
    };
}
</script>

</x-facturacion-layout>

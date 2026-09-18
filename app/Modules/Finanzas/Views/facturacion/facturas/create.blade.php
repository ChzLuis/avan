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

    {{-- Dos columnas: el formulario y, al lado, el comprobante tal y como se va
         a imprimir. Emitir es irreversible ante SUNAT, asi que ver el papel
         mientras se rellena evita la nota de credito por un dato mal tecleado.
         Bajo 1280px la vista previa se va al final: en pantalla estrecha,
         partirla en dos columnas deja ambas ilegibles. --}}
    <div class="p-6 max-w-[1500px] mx-auto grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_400px] gap-5 items-start">

      <div class="space-y-4 min-w-0">

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
                               min="{{ now()->subDays(3)->toDateString() }}" max="{{ now()->toDateString() }}"
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
                <div class="space-y-3 relative">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cliente</p>
                        {{-- Traer los datos de un comprobante ya emitido. Es la
                             via mas rapida para un cliente que repite, y la unica
                             que recupera su RUC o DNI: la tabla de clientes no
                             guarda documento fiscal. --}}
                        <button type="button" @click="buscadorAbierto = !buscadorAbierto"
                                class="flex items-center gap-1 text-[10px] font-semibold px-2 py-1 rounded-lg border transition-colors
                                       {{ $esBoleta ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-50' : 'border-blue-200 text-blue-700 hover:bg-blue-50' }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar anterior
                        </button>
                    </div>

                    {{-- Panel de comprobantes anteriores --}}
                    <div x-show="buscadorAbierto" x-cloak @click.outside="buscadorAbierto = false"
                         class="absolute z-40 top-7 left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                        <div class="p-2 border-b border-gray-100">
                            <input type="text" x-model="buscaPrevio" x-ref="buscaPrevio"
                                   placeholder="Número, cliente o RUC/DNI..."
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $esBoleta ? 'focus:ring-emerald-400' : 'focus:ring-blue-400' }}">
                        </div>
                        <div class="max-h-72 overflow-y-auto">
                            <template x-if="previosFiltrados.length === 0">
                                <p class="px-3 py-4 text-xs text-gray-400 text-center">Sin comprobantes que coincidan.</p>
                            </template>
                            <template x-for="r in previosFiltrados" :key="r.id">
                                <div class="px-3 py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 truncate" x-text="r.cliente.client_name || 'Sin nombre'"></p>
                                            <p class="text-[10px] text-gray-400 font-mono">
                                                <span x-text="r.numero"></span> ·
                                                <span x-text="r.cliente.client_doc_number || 's/doc'"></span> ·
                                                <span x-text="r.fecha"></span>
                                            </p>
                                        </div>
                                        <div class="flex gap-1 flex-shrink-0">
                                            <button type="button" @click="traerDePrevio(r, false)"
                                                    class="text-[10px] px-2 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100">
                                                Solo cliente
                                            </button>
                                            <button type="button" @click="traerDePrevio(r, true)"
                                                    class="text-[10px] px-2 py-1 rounded-lg font-semibold text-white
                                                           {{ $esBoleta ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }}">
                                                Todo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase mb-1">Tipo doc.</label>
                            @if($esBoleta)
                            <select x-model="form.client_doc_type"
                                    class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                <option value="">— —</option>
                                <option value="DNI">DNI</option>
                                <option value="RUC">RUC</option>
                                <option value="CE">CE</option>
                                <option value="pasaporte">Pasaporte</option>
                            </select>
                            @else
                            {{-- Una factura solo se emite a un RUC: SUNAT no acepta
                                 DNI, carne de extranjeria ni pasaporte como receptor.
                                 Ofrecer esas opciones solo servia para que el
                                 comprobante lo rechazaran despues de emitirlo. Si el
                                 cliente da su DNI, lo que corresponde es una boleta. --}}
                            <div class="w-full border border-gray-200 rounded-lg px-2 py-2 text-sm bg-gray-50 text-gray-700 font-semibold">RUC</div>
                            @endif
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
                                   placeholder="{{ $esBoleta ? 'RUC / DNI' : 'RUC (11 dígitos)' }}"
                                   @if(!$esBoleta) inputmode="numeric" maxlength="11" @endif
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

      {{-- ── VISTA PREVIA: el comprobante como saldra impreso ── --}}
      <aside class="xl:sticky xl:top-20 min-w-0">
        <div class="flex items-center justify-between mb-2 px-1">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Así se verá impreso</p>
            <span class="text-[10px] text-gray-400">Actualiza mientras escribes</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden text-[11px] text-gray-700">
            {{-- Cabecera: emisor y recuadro de denominacion, como en el papel --}}
            <div class="flex gap-3 p-4 border-b border-gray-100">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-900 text-[12px] leading-tight">{{ $emisorRazon }}</p>
                    @if($emisorDir)<p class="text-gray-500 mt-0.5 leading-snug">{{ $emisorDir }}</p>@endif
                    @if($project->phone)<p class="text-gray-500">{{ $project->phone }}</p>@endif
                </div>
                <div class="w-[132px] flex-shrink-0 border-2 rounded-lg p-2 text-center
                            {{ $esBoleta ? 'border-emerald-500' : 'border-blue-600' }}">
                    @if($emisorRuc)<p class="font-mono font-bold text-gray-800">R.U.C. {{ $emisorRuc }}</p>@endif
                    <p class="font-bold text-[10px] leading-tight mt-1 {{ $esBoleta ? 'text-emerald-700' : 'text-blue-700' }}">
                        {{ $esBoleta ? 'BOLETA DE VENTA ELECTRÓNICA' : 'FACTURA ELECTRÓNICA' }}
                    </p>
                    <p class="font-mono font-bold text-gray-800 mt-1">{{ $serie }}-<span class="text-gray-400">·····</span></p>
                </div>
            </div>

            {{-- Receptor --}}
            <div class="px-4 py-3 border-b border-gray-100 space-y-0.5">
                <p><span class="text-gray-400">Señor(es):</span>
                   <span class="font-semibold text-gray-800" x-text="form.client_name || '—'"></span></p>
                <p><span class="text-gray-400" x-text="(form.client_doc_type || 'Doc.') + ':'"></span>
                   <span class="font-mono" x-text="form.client_doc_number || '—'"></span></p>
                <p><span class="text-gray-400">Dirección:</span>
                   <span x-text="form.client_address || '—'"></span></p>
                <p><span class="text-gray-400">F. Emisión:</span>
                   <span x-text="fechaLarga(form.issue_date)"></span>
                   <template x-if="form.due_date">
                       <span><span class="text-gray-400 ml-2">Vence:</span> <span x-text="fechaLarga(form.due_date)"></span></span>
                   </template>
                </p>
            </div>

            {{-- Detalle --}}
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-[9px] uppercase tracking-wide text-gray-500">
                        <th class="text-center py-1.5 px-2 font-bold w-10">Cant</th>
                        <th class="text-left py-1.5 px-2 font-bold">Descripción</th>
                        <th class="text-right py-1.5 px-2 font-bold w-16">P.Unit</th>
                        <th class="text-right py-1.5 px-2 font-bold w-16">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(l, i) in form.lines" :key="'prev'+i">
                        <tr class="border-b border-gray-50 align-top">
                            <td class="text-center py-1.5 px-2" x-text="l.qty || 0"></td>
                            <td class="py-1.5 px-2 break-words" x-text="l.desc || '—'"></td>
                            <td class="text-right py-1.5 px-2 font-mono" x-text="(parseFloat(l.price)||0).toFixed(2)"></td>
                            <td class="text-right py-1.5 px-2 font-mono font-semibold" x-text="lineTotal(l).toFixed(2)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- Totales e importe en letras: lo que SUNAT exige que figure --}}
            <div class="px-4 py-3 border-t border-gray-200 space-y-1">
                <div class="flex justify-between"><span class="text-gray-400">OP. GRAVADA</span>
                    <span class="font-mono" x-text="simbolo + ' ' + totales.sub.toFixed(2)"></span></div>
                <div class="flex justify-between"><span class="text-gray-400">I.G.V. (18%)</span>
                    <span class="font-mono" x-text="simbolo + ' ' + totales.igv.toFixed(2)"></span></div>
                <div class="flex justify-between text-[13px] font-bold text-gray-900 border-t border-gray-200 pt-1.5 mt-1">
                    <span>IMPORTE TOTAL</span>
                    <span class="font-mono {{ $esBoleta ? 'text-emerald-600' : 'text-blue-600' }}"
                          x-text="simbolo + ' ' + totales.total.toFixed(2)"></span>
                </div>
                <p class="text-[9px] text-gray-500 uppercase leading-snug pt-1" x-text="'SON: ' + importeEnLetras()"></p>
            </div>

            <div class="px-4 py-2 bg-gray-50 border-t border-gray-100 flex items-center gap-2">
                <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                </div>
                <p class="text-[9px] text-gray-400 leading-snug">
                    El código QR y el número definitivo se generan al emitir.
                    Representación impresa del comprobante electrónico.
                </p>
            </div>
        </div>
      </aside>

    </div>
</div>

@php
// El cliente viaja completo: antes solo iban id y nombre, asi que elegir uno de
// la lista dejaba direccion, telefono y correo vacios y habia que teclearlos.
$clientsForJs = $clients->map(fn ($c) => [
    'id'      => $c->id,
    'name'    => $c->name,
    'phone'   => $c->phone ?? '',
    'email'   => $c->email ?? '',
    'address' => $c->direccion ?? '',
])->values();
@endphp
<script>
function emisionPage() {
    const catalogo   = @json($catalogo->values());
    const allClients = @json($clientsForJs);
    const fromQuote  = @json($fromQuote ?? null);
    const recientes  = @json($recientes ?? []);
    // Una factura solo se emite a un RUC. Se deja como constante para que las
    // tres vias por las que entran datos del cliente (tecleado, cotizacion y
    // comprobante anterior) no puedan colar otro tipo de documento.
    const esFactura  = {{ $esBoleta ? 'false' : 'true' }};

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
            client_doc_type:   '{{ $esBoleta ? '' : 'RUC' }}',
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
                this.form.client_doc_type   = esFactura ? 'RUC' : (fromQuote.client_doc_type || '');
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
        selectClient(c) {
            this.form.client_name = c.name;
            // Solo se rellena lo que esta vacio: si el operador ya escribio una
            // direccion distinta para esta venta, elegir el cliente no se la pisa.
            if (!this.form.client_address && c.address) this.form.client_address = c.address;
            this.clientSugg = [];
        },

        // ── Traer de un comprobante anterior ──────────────────────────────
        // La tabla de clientes no guarda RUC ni DNI; el unico sitio donde vive
        // el documento del receptor es un comprobante ya emitido.
        buscadorAbierto: false,
        buscaPrevio: '',

        get previosFiltrados() {
            const q = (this.buscaPrevio || '').toLowerCase().trim();
            if (!q) return recientes.slice(0, 8);
            return recientes.filter(r =>
                (r.numero || '').toLowerCase().includes(q) ||
                (r.cliente.client_name || '').toLowerCase().includes(q) ||
                (r.cliente.client_doc_number || '').includes(q)
            ).slice(0, 12);
        },

        traerDePrevio(r, conLineas) {
            Object.assign(this.form, r.cliente);
            if (esFactura) this.form.client_doc_type = 'RUC';
            if (conLineas && r.lineas.length) {
                this.form.lines = r.lineas.map(l => ({ ...l, showSugg: false, suggestions: [] }));
            }
            this.buscadorAbierto = false;
            this.buscaPrevio = '';
            this.clientSugg = [];
        },

        // ── Ayudas de la vista previa ────────────────────────────────────
        get simbolo() { return this.form.currency === 'USD' ? '$' : 'S/'; },

        fechaLarga(iso) {
            if (!iso) return '—';
            const [a, m, d] = iso.split('-');
            return `${d}/${m}/${a}`;
        },

        /** Importe en letras: SUNAT exige que figure en la representación impresa. */
        importeEnLetras() {
            const n = this.totales.total;
            const entero = Math.floor(n);
            const cent = Math.round((n - entero) * 100);
            const moneda = this.form.currency === 'USD' ? 'DÓLARES AMERICANOS' : 'SOLES';
            return `${this.letras(entero)} CON ${String(cent).padStart(2, '0')}/100 ${moneda}`;
        },

        letras(n) {
            if (n === 0) return 'CERO';
            const U = ['','UNO','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE','DIEZ',
                       'ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
            const D = ['','','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
            const C = ['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS',
                       'SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];

            const hasta999 = (x) => {
                if (x === 100) return 'CIEN';
                let t = '';
                const c = Math.floor(x / 100), r = x % 100;
                if (c) t += C[c] + (r ? ' ' : '');
                if (r < 20) t += U[r];
                else {
                    const d = Math.floor(r / 10), u = r % 10;
                    if (d === 2 && u) t += 'VEINTI' + U[u].toLowerCase().toUpperCase();
                    else t += D[d] + (u ? ' Y ' + U[u] : '');
                }
                return t.trim();
            };

            if (n < 1000) return hasta999(n);
            if (n < 1000000) {
                const miles = Math.floor(n / 1000), resto = n % 1000;
                const pref = miles === 1 ? 'MIL' : hasta999(miles) + ' MIL';
                return (pref + (resto ? ' ' + hasta999(resto) : '')).trim();
            }
            const mill = Math.floor(n / 1000000), resto = n % 1000000;
            const pref = mill === 1 ? 'UN MILLÓN' : hasta999(mill) + ' MILLONES';
            return (pref + (resto ? ' ' + this.letras(resto) : '')).trim();
        },

        async buscarRuc() {
            const doc = (this.form.client_doc_number||'').replace(/\D/g,'');

            // El tipo se deduce del propio numero: en Peru 11 digitos es RUC y 8
            // es DNI. Antes habia que elegir "RUC" en el selector ANTES de
            // teclear; si no, escribir el numero no consultaba nada y tampoco
            // avisaba de nada, asi que parecia que la consulta estaba rota.
            if (doc.length === 11 && this.form.client_doc_type !== 'RUC') this.form.client_doc_type = 'RUC';
            // En una factura el receptor es siempre un RUC; no se degrada a DNI.
            if (!esFactura && doc.length === 8 && !this.form.client_doc_type) this.form.client_doc_type = 'DNI';

            if (doc.length !== 11) return;

            this.buscandoRuc = true;
            try {
                const res  = await fetch('{{ $rucUrl }}?ruc=' + doc, {
                    headers:{ 'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}' }
                });
                const data = await res.json();
                if (data.ok) {
                    this.form.client_name    = data.razon_social;
                    this.form.client_address = data.direccion;
                    this.clientSugg = [];
                } else {
                    this.errorMsg = data.message || 'RUC no encontrado.';
                    // Un RUC inexistente es un tropiezo y el aviso se va solo;
                    // que falte el token es algo que hay que ir a configurar, y
                    // ese mensaje se queda hasta que el operador lo lea.
                    if (!/token/i.test(this.errorMsg)) setTimeout(() => this.errorMsg = '', 5000);
                }
            } catch (e) {
                this.errorMsg = 'No se pudo consultar el RUC. Revisa tu conexión.';
                setTimeout(() => this.errorMsg = '', 5000);
            }
            this.buscandoRuc = false;
        },

        async guardar(emitir) {
            this.errorMsg = '';
            if (!this.form.client_name.trim()) { this.errorMsg = 'El nombre del cliente es obligatorio.'; return; }
            if (esFactura) {
                const ruc = (this.form.client_doc_number || '').replace(/\D/g, '');
                if (ruc.length !== 11) {
                    this.errorMsg = 'Una factura necesita el RUC del cliente (11 dígitos). Si solo tienes su DNI, emite una boleta.';
                    return;
                }
                this.form.client_doc_type = 'RUC';
            }
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

@php
    // Las acciones apuntan a la MISMA cara por la que se entro: emitir una guia
    // desde Ventas y acabar de vuelta en el panel de Configuracion rompe el hilo
    // de trabajo. Es el mismo controlador, solo cambia el nombre de la ruta.
    $rutaGuias = ($portalLayout ?? 'panel') === 'comercial' ? 'bixosales.guias' : 'guias';
    $storeUrl  = route($rutaGuias.'.store');
    // Consulta de RUC/DNI: la misma puerta que usa Facturas, elegida por la
    // cara de entrada. Si el rol no la tiene, queda vacia y el campo sigue
    // escribiendose a mano: nunca se bloquea la emision por una API.
    // La del panel se llama `invoices.ruc`, no `facturas.ruc`: ese nombre no
    // existe y `Route::has` devolvia false, asi que la URL quedaba vacia y la
    // consulta salia en silencio. Quien emitia desde el panel tecleaba el RUC
    // y no pasaba NADA: ni razon social, ni direccion, ni ubigeo, ni un aviso.
    $rutaRuc   = ($portalLayout ?? 'panel') === 'comercial' ? 'bixosales.facturas.ruc' : 'invoices.ruc';
    $rucUrl    = \Illuminate\Support\Facades\Route::has($rutaRuc) ? route($rutaRuc) : '';
    $enviarUrl = route($rutaGuias.'.enviar', '__ID__');
@endphp

@php $_saltoGuia = true; @endphp
<style>
/* GUIAS EN MOVIL. Se emite junto al camion, de pie y con prisa: el campo
   tiene que aceptar el dedo y el boton de emitir no puede estar al final
   de veinticinco campos de scroll. */
@media (max-width: 767px) {
    /* 16px es el minimo que no dispara el zoom automatico de iOS, que
       descuadra la pagina entera al tocar un campo. */
    #guia-form input, #guia-form select, #guia-form textarea {
        font-size: 16px; min-height: 46px;
    }
    #guia-form button { min-height: 44px; }

    /* La accion de la pantalla viaja pegada abajo, sobre el pulgar. */
    #guia-pie {
        position: sticky; bottom: 0; z-index: 20;
        background: #fff; border-top: 1px solid #e5e7eb;
        padding-bottom: calc(12px + env(safe-area-inset-bottom));
        box-shadow: 0 -6px 16px rgba(15,23,42,.10);
        border-radius: 0;
    }
    #guia-pie button:last-child { flex: 1; font-size: 15px; font-weight: 700; }

    /* La rejilla de 12 columnas de cada linea es ilegible en 390px: cada
       campo a su ancho, y el de quitar con area suficiente. */
    #guia-form .guia-linea { grid-template-columns: 1fr; gap: 8px; }
    #guia-form .guia-linea > * { grid-column: 1 / -1 !important; }
}
</style>
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Guías de remisión">

{{-- `?nueva=1` lo manda el boton Generar de la portada de Facturacion: sin
     esto caia arriba de la pagina, con la ultima guia y el historico por
     delante, y habia que desplazar a mano hasta el formulario. Ahora lleva
     directo a donde se trabaja. --}}
<div class="max-w-6xl mx-auto px-4 py-6" x-data="guiasPage()"
     x-init="abrirNueva(); @if(request()->boolean('nueva')) $nextTick(() => $refs.formulario?.scrollIntoView({behavior:'smooth', block:'start'})) @endif">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Guías de remisión</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                La factura dice qué se vendió; la guía dice cómo viajó. Serie
                <span class="font-semibold text-gray-700">{{ $serie }}</span>.
            </p>
        </div>
        <div class="flex items-center gap-2">
            {{-- El historico completo vive aparte: aqui solo las ultimas. --}}
            <a href="{{ route((($portalLayout ?? 'panel') === 'comercial' ? 'bixosales.' : '').'guias.consulta') }}"
               class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition">
                Histórico de guías
            </a>
            <button @click="abrirNueva(); $refs.formulario.scrollIntoView({behavior:'smooth', block:'start'})"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition">
                Guía en blanco
            </button>
        </div>
    </div>

    {{-- Solo la ULTIMA guia: al emitir, la pagina recarga y esta franja es
         lo que confirma el numero que salio y da el boton de imprimir. El
         resto vive en el historico (2026-09-11): esta pantalla es para
         emitir, no para buscar. --}}
    @php $ultima = $guias->first(); @endphp

    {{-- AVISO DE GUIAS SIN COMPROBANTE.
         Una guia por VENTA que salio sin factura ni boleta es mercaderia
         entregada sin sustento: el caso "150 guias pero 100 comprobantes" que
         no hay forma de explicar en una fiscalizacion. No basta con mirarlo en
         la ultima guia —la de hace tres puede ser la que falta—, asi que se
         cuentan todas y se avisa aqui, que es donde se emite. --}}
    @php
        $guiasPendientes = $guias->filter(fn ($g) => $g->documentacion()[0] === 'pendiente');
    @endphp
    @if($guiasPendientes->isNotEmpty())
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
        <p class="text-sm font-semibold text-amber-900">
            {{ $guiasPendientes->count() }}
            {{ $guiasPendientes->count() === 1 ? 'guía por venta está' : 'guías por venta están' }}
            Pendiente de comprobante
        </p>
        @php
            $gpLista = $guiasPendientes->take(5)->pluck('numero')->implode(', ');
            $gpResto = $guiasPendientes->count() - 5;
            $gpTexto = $gpLista.($gpResto > 0 ? ' y '.$gpResto.' más' : '').'.';
        @endphp
        <p class="text-xs text-amber-800 mt-0.5">
            Salieron por venta pero no tienen factura ni boleta vinculada: {{ $gpTexto }}
        </p>
    </div>
    @endif
    @if(! $ultima)
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
            <p class="text-sm font-semibold text-gray-600">Todavía no has emitido ninguna guía.</p>
            <p class="text-xs text-gray-400 mt-1 max-w-md mx-auto">
                Emítela antes de que salga el transporte: la guía tiene que viajar
                físicamente con la mercadería.
            </p>
        </div>
    @else
    @php
        $ultEstado = match ($ultima->sunat_status) {
            'accepted' => ['Aceptada por SUNAT', 'text-emerald-700 bg-emerald-50'],
            'pending'  => ['Enviando a SUNAT…', 'text-amber-700 bg-amber-50'],
            'rejected' => ['Rechazada por SUNAT', 'text-red-700 bg-red-50'],
            'error'    => ['Error de envío', 'text-red-700 bg-red-50'],
            default    => ['Sin enviar', 'text-gray-600 bg-gray-100'],
        };
    @endphp
    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 flex flex-wrap items-center gap-x-5 gap-y-2">
        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Última guía</div>
        <div class="font-bold text-gray-900">{{ $ultima->numero }}</div>
        <div class="text-xs text-gray-500">{{ ($ultima->fecha_traslado ?? $ultima->created_at)?->format('d/m/Y') }}</div>
        <div class="text-sm text-gray-800 min-w-0 truncate flex-1">{{ $ultima->destinatario_nombre }}</div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $ultEstado[1] }}">{{ $ultEstado[0] }}</span>
        {{-- SEMAFORO DE DOCUMENTACION. Que SUNAT acepte la guia no dice nada
             sobre si la mercaderia salio con su comprobante de venta: son dos
             cosas distintas. Una guia por VENTA sin factura vinculada es el
             caso "150 guias pero 100 comprobantes" que nadie sabe explicar
             despues, y se ve aqui, al emitir, cuando todavia se puede
             arreglar. El modelo ya calculaba esto (documentacion()) pero la
             pantalla no lo pintaba. --}}
        @php
            [, $ultDocTexto, $ultDocColor] = $ultima->documentacion();
            $ultDocClase = match ($ultDocColor) {
                'verde' => 'text-emerald-700 bg-emerald-50',
                'ambar' => 'text-amber-700 bg-amber-50',
                default => 'text-gray-600 bg-gray-100',
            };
        @endphp
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $ultDocClase }}">{{ $ultDocTexto }}</span>
        <a href="{{ route($rutaGuias.'.pdf', $ultima->id) }}" target="_blank" rel="noopener"
           class="text-sm font-semibold text-indigo-600 hover:underline">Imprimir</a>
        <a href="{{ route((($portalLayout ?? 'panel') === 'comercial' ? 'bixosales.' : '').'guias.consulta') }}"
           class="text-sm text-gray-500 hover:text-indigo-600">Ver todas en el histórico →</a>
    </div>
    @endif

    {{-- ══ Nueva guía ═══════════════════════════════════════════════════ --}}
    <div class="mt-6" x-ref="formulario">


        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Nueva guía de remisión</h3>
                <p class="text-xs text-gray-500 mt-0.5">Se emitirá con la serie {{ $serie }} y se enviará a SUNAT.</p>
            </div>

            <div id="guia-form" class="px-6 py-5 space-y-5">

                {{-- De dónde nace --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">
                        Comprobante que respalda el traslado <span class="font-normal text-gray-400">(opcional)</span>
                    </label>
                    <select x-model="form.invoice_id" @change="desdeVenta()" class="w-full rounded-lg border-gray-300 text-sm">
                        <option value="">Sin comprobante — traslado que no es una venta</option>
                        @foreach($comprobantes as $c)
                        <option value="{{ $c->id }}"
                                data-nombre="{{ $c->client_name }}"
                                data-doc="{{ $c->client_doc_number }}"
                                data-dir="{{ $c->client_address }}">
                            {{ $c->numero }} — {{ $c->client_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Destinatario --}}
                <div class="grid md:grid-cols-3 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Destinatario <span class="text-red-500" title="Obligatorio">*</span></label>
                        <input type="text" x-model="form.destinatario_nombre" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">RUC o DNI</label>
                        <input inputmode="numeric" maxlength="15" type="text" x-model="form.destinatario_doc_numero"
                               @input="consultarDoc('destinatario_doc_numero','destinatario_nombre','llegada_direccion','llegada_ubigeo')"
                               @blur="consultarDoc('destinatario_doc_numero','destinatario_nombre','llegada_direccion','llegada_ubigeo')"
                               class="w-full rounded-lg border-gray-300 text-sm">
                        <p x-show="docBuscando === 'destinatario_doc_numero'" x-cloak class="mt-1 text-xs text-gray-500">Consultando documento...</p>
                    </div>
                </div>

                {{-- Traslado --}}
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Motivo <span class="text-red-500" title="Obligatorio">*</span></label>
                        <select x-model="form.motivo_codigo" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($motivos as $codigo => $texto)
                            <option value="{{ $codigo }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Fecha de traslado <span class="text-red-500" title="Obligatorio">*</span></label>
                        <input type="date" x-model="form.fecha_traslado" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Modalidad <span class="text-red-500" title="Obligatorio">*</span></label>
                        <select x-model="form.modalidad" class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach($modalidades as $codigo => $texto)
                            <option value="{{ $codigo }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Peso y bultos: lo primero que miran en un control --}}
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Peso total <span class="text-red-500" title="Obligatorio">*</span></label>
                        <input inputmode="decimal" type="number" step="0.001" min="0" x-model="form.peso_total" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Unidad</label>
                        <select x-model="form.peso_unidad" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="KGM">Kilogramos</option>
                            <option value="TNE">Toneladas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Bultos</label>
                        <input inputmode="numeric" type="number" min="0" x-model="form.bultos" class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                </div>

                {{-- Puntos --}}
                <div class="grid md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Punto de partida <span class="text-red-500" title="Obligatorio">*</span></label>
                        <input type="text" x-model="form.partida_direccion" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Dirección de donde sale" aria-label="Dirección del punto de partida">
                        <input type="text" x-model="form.partida_ubigeo" inputmode="numeric" maxlength="6" aria-label="Ubigeo del punto de partida"
                               class="w-full mt-2 rounded-lg border-gray-300 text-sm font-mono" placeholder="Ubigeo (6 dígitos)">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Punto de llegada <span class="text-red-500" title="Obligatorio">*</span></label>
                        <input type="text" x-model="form.llegada_direccion" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Dirección de destino" aria-label="Dirección del punto de llegada">
                        <input type="text" x-model="form.llegada_ubigeo" inputmode="numeric" maxlength="6" aria-label="Ubigeo del punto de llegada"
                               class="w-full mt-2 rounded-lg border-gray-300 text-sm font-mono" placeholder="Ubigeo (6 dígitos)">
                    </div>
                <p class="text-xs text-gray-400 -mt-1">
                    <span x-show="!ubigeoNota">El ubigeo es el código de 6 dígitos del distrito. Lima-Lima-Lima es 150101.</span>
                    {{-- Cuando lo trae la consulta se dice de que distrito es:
                         asi se ve de un vistazo si el destino es el correcto. --}}
                    <span x-show="ubigeoNota" x-cloak class="text-emerald-600 font-medium"
                          x-text="'Destino: ' + ubigeoNota"></span>
                </p>
                </div>

                {{-- Quién lo lleva: una modalidad u otra, nunca las dos --}}
                <div x-show="form.modalidad === '01'" class="rounded-xl bg-gray-50 p-3 space-y-3">
                    <p class="text-xs font-semibold text-gray-600">Transportista</p>
                    <div class="grid md:grid-cols-3 gap-3">
                        <input inputmode="numeric" maxlength="11" type="text" x-model="form.transportista_ruc"
                               @input="consultarDoc('transportista_ruc','transportista_razon_social')"
                               @blur="consultarDoc('transportista_ruc','transportista_razon_social')"
                               class="rounded-lg border-gray-300 text-sm" placeholder="RUC" aria-label="RUC del transportista">
                        <input type="text" x-model="form.transportista_razon_social" class="md:col-span-2 rounded-lg border-gray-300 text-sm" placeholder="Razón social" aria-label="Razón social del transportista">
                    </div>
                </div>

                <div x-show="form.modalidad === '02'" class="rounded-xl bg-gray-50 p-3 space-y-3">
                    <p class="text-xs font-semibold text-gray-600">Vehículo y conductor</p>
                    {{-- En categoria M1 o L (auto, camioneta, moto) SUNAT exime
                         de declarar vehiculo y conductor: al marcarlo, esos campos
                         dejan de pedirse y viajan vacios. --}}
                    <label class="flex items-start gap-2 text-xs text-gray-600 cursor-pointer">
                        <input type="checkbox" x-model="form.vehiculo_m1l" class="mt-0.5 rounded border-gray-300">
                        <span>
                            <span class="font-semibold text-gray-700">Traslado en vehículo de categoría M1 o L</span>
                            <span class="block text-gray-500">Auto, camioneta o moto: SUNAT no exige declarar placa ni conductor.</span>
                        </span>
                    </label>
                    <div class="grid md:grid-cols-3 gap-3" x-show="! form.vehiculo_m1l">
                        <input autocapitalize="characters" maxlength="10" type="text" x-model="form.vehiculo_placa" class="rounded-lg border-gray-300 text-sm" placeholder="Placa" aria-label="Placa del vehículo">
                        <input inputmode="numeric" maxlength="15" type="text" x-model="form.conductor_doc_numero"
                               @input="consultarConductor()" @blur="consultarConductor()"
                               class="rounded-lg border-gray-300 text-sm" placeholder="DNI del conductor" aria-label="Documento del conductor">
                        <input inputmode="text" autocapitalize="characters" type="text" x-model="form.conductor_licencia" class="rounded-lg border-gray-300 text-sm" placeholder="Licencia" aria-label="Licencia de conducir">
                        <input type="text" x-model="form.conductor_nombres" class="rounded-lg border-gray-300 text-sm" placeholder="Nombres" aria-label="Nombres del conductor">
                        <input type="text" x-model="form.conductor_apellidos" class="md:col-span-2 rounded-lg border-gray-300 text-sm" placeholder="Apellidos" aria-label="Apellidos del conductor">
                    </div>
                </div>

                {{-- Qué viaja --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-gray-600">Bienes que se trasladan</p>
                        <button @click="agregarLinea()"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">+ Agregar línea</button>
                    </div>
                    <div class="space-y-2">
                        <template x-for="(item, i) in form.items" :key="i">
                            <div class="guia-linea grid grid-cols-12 gap-2">
                                <div class="col-span-5 relative">
                                    <input type="text" x-model="item.description"
                                           @input="buscarProducto(i)" @focus="buscarProducto(i)"
                                           @keydown.arrow-down.prevent="moverSugerencia(i, 1)"
                                           @keydown.arrow-up.prevent="moverSugerencia(i, -1)"
                                           @keydown.enter.prevent="elegirSugerenciaActiva(i)"
                                           @keydown.escape="cerrarSugerencias(i)"
                                           @click.outside="cerrarSugerencias(i)"
                                           class="w-full rounded-lg border-gray-300 text-sm"
                                           placeholder="Buscar producto por nombre o SKU..."
                                           role="combobox" aria-autocomplete="list"
                                           :aria-expanded="!!item.verSugerencias">
                                    <div x-show="item.verSugerencias && item.sugerencias && item.sugerencias.length" x-cloak
                                         class="absolute z-30 mt-1 inset-x-0 rounded-lg border bg-white shadow-lg max-h-56 overflow-y-auto"
                                         style="border-color:#e5e7eb;" role="listbox">
                                        <template x-for="(p, k) in item.sugerencias" :key="p.key">
                                            <button type="button" @mousedown.prevent="elegirProducto(i, p)"
                                                    class="w-full text-left px-3 py-2.5 border-b last:border-b-0"
                                                    :class="k === item.sugerenciaActiva ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                                                    style="border-color:#f3f4f6;" role="option"
                                                    :aria-selected="k === item.sugerenciaActiva">
                                                <span class="block text-sm text-gray-900 truncate" x-text="p.name"></span>
                                                <span class="block text-xs text-gray-400 truncate"
                                                      x-text="[p.sku, p.type_label].filter(Boolean).join(' · ')"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <select x-model="item.unit" class="col-span-4 rounded-lg border-gray-300 text-sm">
                                    @foreach($unidades as $codigo => $nombre)
                                    <option value="{{ $codigo }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.001" min="0" x-model="item.quantity" class="col-span-2 rounded-lg border-gray-300 text-sm">
                                <button @click="form.items.splice(i,1)" x-show="form.items.length > 1" type="button" aria-label="Quitar línea"
                                        class="col-span-1 min-h-[44px] px-2 rounded-lg text-gray-400 hover:text-red-500 active:bg-red-50 text-lg leading-none">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Aviso de la consulta de documento. Es un aviso, no un error:
                     el nombre siempre se puede escribir a mano y la guia se emite
                     igual. Por eso va en ambar y se va solo. --}}
                <p x-show="docAviso" x-cloak x-text="docAviso"
                   class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800"></p>

                <template x-if="errores.length">
                    <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2">
                        <template x-for="e in errores" :key="e">
                            <p class="text-xs text-red-700" x-text="e"></p>
                        </template>
                    </div>
                </template>
            </div>

            <div id="guia-pie" class="px-6 py-4 bg-gray-50 flex justify-end gap-2 rounded-b-2xl">
                {{-- Limpiar vacia 19 campos, incluidos un RUC consultado y un
                     ubigeo traido de la API, y en movil comparte franja con
                     Emitir bajo el pulgar: un toque de mas y se pierde todo.
                     Se pregunta, pero solo si hay algo escrito. --}}
                <button type="button" @click="limpiarGuia()"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600">Limpiar</button>
                {{-- La factura se revisaba antes de emitir y la guia no,
                     aunque es la que VIAJA con la mercaderia: un error aqui
                     se descubre con el camion en la carretera. No graba nada
                     ni gasta correlativo. --}}
                <button type="button" @click="verPrevia()"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-indigo-700 bg-white border border-indigo-200 hover:bg-indigo-50">
                    Vista previa
                </button>
                <button @click="emitir()" :disabled="enviando"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60">
                    <span x-text="enviando ? 'Emitiendo...' : 'Emitir y enviar a SUNAT'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/* La ruta cambia segun la cara por la que se entro (panel o Ventas): cada
   portal tiene sus propias rutas nombradas. */
const PREVIA_GUIA_URL = @json(($portalLayout ?? 'panel') === 'comercial'
    ? route('bixosales.guias.previsualizar')
    : route('guias.previsualizar'));
const CSRF_GUIA = @json(csrf_token());

function guiasPage() {
    return {
        abierta: false,
        huella: '',
        // El MISMO catalogo que los comprobantes.
        catalogo: @json($catalogo ?? []),
        enviando: false,
        errores: [],
        // Consulta de documento: que campo se esta consultando, aviso al pie
        // y memoria del ultimo numero por campo, para no repetir la llamada.
        docBuscando: '',
        docAviso: '',
        docConsultado: {},
        // A que distrito corresponde el ubigeo traido por la consulta: seis
        // digitos sueltos no dicen nada y hay que poder comprobarlos.
        ubigeoNota: '',
        form: {},

        /* BUSCADOR DE PRODUCTOS. El mismo que en comprobantes: se escribe
           y se elige del catalogo, en vez de teclear la descripcion a mano.
           Asi la guia dice exactamente lo que dice la factura. */
        agregarLinea() {
            this.form.items.push({
                description: '', unit: 'NIU', quantity: 1,
                sugerencias: [], verSugerencias: false, sugerenciaActiva: -1,
            });
            // El boton vive arriba y la linea nace abajo: sin esto no se ve
            // que haya pasado nada.
            this.$nextTick(() => {
                const campos = document.querySelectorAll('input[placeholder^="Buscar producto"]');
                const ultimo = campos[campos.length - 1];
                if (ultimo) { ultimo.scrollIntoView({ behavior: 'smooth', block: 'center' }); ultimo.focus({ preventScroll: true }); }
            });
        },

        coincide(p, q) {
            const heno = [p.name, p.sku, p.description, p.type_label].filter(Boolean).join(' ').toLocaleLowerCase('es');
            return heno.includes(q);
        },

        buscarProducto(i) {
            const item = this.form.items[i];
            const q = (item.description || '').trim().toLocaleLowerCase('es');
            const lista = q ? this.catalogo.filter(p => this.coincide(p, q)) : this.catalogo;

            item.sugerencias = lista.slice(0, 10);
            item.verSugerencias = item.sugerencias.length > 0;
            item.sugerenciaActiva = item.sugerencias.length ? 0 : -1;
        },

        moverSugerencia(i, paso) {
            const item = this.form.items[i];
            const n = (item.sugerencias || []).length;
            if (!n) return;
            item.sugerenciaActiva = (item.sugerenciaActiva + paso + n) % n;
        },

        elegirSugerenciaActiva(i) {
            const item = this.form.items[i];
            const p = (item.sugerencias || [])[item.sugerenciaActiva];
            if (p) this.elegirProducto(i, p);
        },

        elegirProducto(i, p) {
            const item = this.form.items[i];
            item.description = p.name;
            // La unidad viene del producto: en una guia, declarar cajas donde
            // eran metros es un problema en el control de carretera.
            item.unit = p.unit || 'NIU';
            this.cerrarSugerencias(i);
        },

        cerrarSugerencias(i) {
            const item = this.form.items[i];
            if (!item) return;
            item.verSugerencias = false;
            item.sugerenciaActiva = -1;
        },

        /* Consulta de documento: al completar 8 (DNI) u 11 (RUC) digitos se
           traen los datos solos. No hay boton "Consultar SUNAT" a proposito:
           un boton mas es un paso mas para quien emite de pie junto al camion,
           y el numero ya dice por su longitud que tipo es. Si la API falla o
           el rol no tiene la ruta, el nombre se escribe a mano: la emision
           nunca se bloquea por una consulta. */
        async consultarDoc(campoDoc, campoNombre, campoDireccion = null, campoUbigeo = null, forzar = false) {
            const url = @json($rucUrl);
            if (!url) return;

            const doc = String(this.form[campoDoc] || '').replace(/\D/g, '');
            if (doc.length !== 8 && doc.length !== 11) return;
            /* `forzar` para el caso del comprobante: el RUC pudo consultarse ya
               (llenando el nombre) y quedar el ubigeo sin poner; sin esto el
               guardia cortaba la segunda consulta y el campo seguia vacio. */
            if (!forzar && this.docConsultado[campoDoc] === doc) return;

            this.docConsultado[campoDoc] = doc;
            this.docBuscando = campoDoc;
            try {
                const res  = await fetch(url + '?doc=' + doc, {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    if (data.razon_social) this.form[campoNombre] = data.razon_social;
                    if (campoDireccion && data.direccion && !this.form[campoDireccion]) {
                        this.form[campoDireccion] = data.direccion;
                    }
                    /* EL UBIGEO VIENE EN LA MISMA CONSULTA y se estaba
                       tirando. SUNAT lo exige y no hay forma de adivinarlo:
                       quien despacha tenia que buscar el codigo de 6 digitos
                       del distrito a mano, o emitir sin el y que lo rechazaran.
                       El de FEISER, por ejemplo, ya venia: 211101 (Juliaca).
                       Solo rellena lo vacio; una direccion de entrega distinta
                       de la fiscal se corrige encima y no se pisa. */
                    if (campoUbigeo && data.ubigeo && !this.form[campoUbigeo]) {
                        this.form[campoUbigeo] = data.ubigeo;
                        if (data.distrito) {
                            this.ubigeoNota = data.ubigeo + ' · ' + [data.distrito, data.provincia, data.departamento]
                                .filter(Boolean).join(', ');
                        }
                    }
                    this.docAviso = '';
                } else {
                    this.avisar(data.message || 'No se encontro ese documento. Escribe el nombre a mano.');
                }
            } catch (e) {
                this.avisar('No se pudo consultar el documento. Escribelo a mano.');
            }
            this.docBuscando = '';
        },

        /* Un aviso de consulta se va solo a los 6 segundos, PERO si lo que
           falta es el token la persona tiene que ir a configurarlo: ese
           mensaje se queda hasta que lo lea. */
        avisar(mensaje) {
            this.docAviso = mensaje;
            if (!/token/i.test(mensaje)) setTimeout(() => this.docAviso = '', 6000);
        },

        /* El conductor viene con nombres y apellidos separados en el
           formulario, pero la consulta devuelve el nombre completo. */
        async consultarConductor() {
            const url = @json($rucUrl);
            if (!url) return;
            const doc = String(this.form.conductor_doc_numero || '').replace(/\D/g, '');
            if (doc.length !== 8) return;
            if (this.docConsultado.conductor_doc_numero === doc) return;

            this.docConsultado.conductor_doc_numero = doc;
            this.docBuscando = 'conductor_doc_numero';
            try {
                const res  = await fetch(url + '?doc=' + doc, {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok && data.razon_social) {
                    const partes = String(data.razon_social).trim().split(/\s+/);
                    // La consulta devuelve "APELLIDO APELLIDO NOMBRES": los dos
                    // primeros son los apellidos en el formato de RENIEC.
                    if (partes.length > 2) {
                        this.form.conductor_apellidos = partes.slice(0, 2).join(' ');
                        this.form.conductor_nombres   = partes.slice(2).join(' ');
                    } else {
                        this.form.conductor_nombres = data.razon_social;
                    }
                    this.docAviso = '';
                } else {
                    // Tambien cuando la respuesta viene ok pero sin nombre: callarse
                    // dejaria al operador esperando un dato que no va a llegar.
                    this.avisar(data.message || 'No se encontro ese DNI. Escribe el nombre a mano.');
                }
            } catch (e) {
                this.avisar('No se pudo consultar el DNI. Escribelo a mano.');
            }
            this.docBuscando = '';
        },

        /* Vaciar el formulario a peticion del usuario. `abrirNueva()` se llama
           tambien al cargar la pagina y al emitir con exito: ahi no hay nada
           que preguntar, por eso la confirmacion vive aqui y no alli. */
        async limpiarGuia() {
            const conDatos = (this.form.destinatario_nombre || '').trim()
                || (this.form.destinatario_doc_numero || '').trim()
                || (this.form.items || []).some(i => (i.description || '').trim());
            if (conDatos && typeof bxConfirmar === 'function') {
                const ok = await bxConfirmar({
                    titulo: 'Vaciar la guía',
                    descripcion: 'Se borrará todo lo escrito, incluidos los datos traídos por RUC. ¿Continuar?',
                });
                if (! ok) return;
            }
            this.abrirNueva();
        },

        abrirNueva() {
            this.errores = [];
            this.huella = '';
            this.form = {
                invoice_id: '',
                destinatario_nombre: '', destinatario_doc_numero: '',
                motivo_codigo: '01',
                fecha_traslado: new Date().toISOString().slice(0, 10),
                modalidad: '02',
                peso_total: '', peso_unidad: 'KGM', bultos: '',
                partida_direccion: @json($project->address ?? ''), llegada_direccion: '',
                partida_ubigeo: @json($project->setting('apisperu_ubigeo') ?: ''), llegada_ubigeo: '',
                transportista_ruc: '', transportista_razon_social: '',
                vehiculo_m1l: false, transbordo_programado: false,
                vehiculo_placa: '', conductor_doc_numero: '',
                conductor_nombres: '', conductor_apellidos: '', conductor_licencia: '',
                items: [{ description: '', unit: 'NIU', quantity: 1, sugerencias: [], verSugerencias: false, sugerenciaActiva: -1 }],
            };
            this.abierta = true;
        },

        /* Si el traslado nace de una venta, el destinatario, la direccion y
           LOS BIENES ya estan en el comprobante: volver a teclearlos solo
           introduce erratas. Las lineas llegan del endpoint de opciones con
           la unidad ya traducida al codigo de SUNAT. */
        async desdeVenta() {
            if (!this.form.invoice_id) return;

            const opcion = this.$el.querySelector(`option[value="${this.form.invoice_id}"]`);
            if (opcion) {
                this.form.destinatario_nombre     = opcion.dataset.nombre || this.form.destinatario_nombre;
                this.form.destinatario_doc_numero = opcion.dataset.doc    || this.form.destinatario_doc_numero;
                this.form.llegada_direccion       = opcion.dataset.dir    || this.form.llegada_direccion;
            }

            const res = await fetch(`{{ route($rutaGuias.'.opciones') }}?invoice_id=${this.form.invoice_id}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json().catch(() => ({}));

            /* Ubigeo de destino: SUNAT lo exige y no viaja en la factura. Se
               propone el de la ultima guia a ese mismo cliente; si el traslado
               va a otro sitio, se corrige encima. Solo se rellena lo vacio: lo
               que el operador ya escribio no se pisa. */
            const destino = data.desde_venta?.ultimo_destino;
            if (destino) {
                if (!this.form.llegada_ubigeo) this.form.llegada_ubigeo = destino.ubigeo || '';
                if (!this.form.llegada_direccion) this.form.llegada_direccion = destino.direccion || '';
            }

            /* Primera guia a ese cliente: no hay guia anterior de donde copiar
               el ubigeo y el campo quedaba vacio, aunque el RUC ya estaba
               escrito y la consulta a SUNAT SI lo devuelve (V.L DISTRIBUCIONES
               -> 150101). Se pregunta por el documento del destinatario. */
            if (!this.form.llegada_ubigeo && this.form.destinatario_doc_numero) {
                await this.consultarDoc(
                    'destinatario_doc_numero', 'destinatario_nombre',
                    'llegada_direccion', 'llegada_ubigeo', true
                );
            }

            if (data.desde_venta?.items?.length) {
                this.form.items = data.desde_venta.items.map(i => ({
                    description: i.description, unit: i.unit, quantity: i.quantity,
                }));
            }
        },

        /* Lo que el servidor va a exigir, comprobado ANTES de salir.
           Sin esto la guia viajaba entera y el motivo del rechazo se pintaba
           en un bloque que vive ARRIBA del formulario: en movil, con la
           pantalla desplazada al pie, el boton volvia de "Emitiendo..." a su
           estado normal y el error quedaba fuera de vista. Parecia que no
           hacia nada. Ahora se avisa en el sitio y el cursor va al campo. */
        faltantes() {
            const f = this.form;
            const req = [
                ['destinatario_nombre', 'el destinatario'],
                ['motivo_codigo',       'el motivo del traslado'],
                ['fecha_traslado',      'la fecha de traslado'],
                ['modalidad',           'la modalidad'],
                ['peso_total',          'el peso total'],
                ['partida_direccion',   'la dirección de partida'],
                ['partida_ubigeo',      'el ubigeo de partida'],
                ['llegada_direccion',   'la dirección de llegada'],
                ['llegada_ubigeo',      'el ubigeo de llegada'],
            ];
            // Transporte privado y vehiculo normal: placa y conductor.
            if (f.modalidad === '02' && ! f.vehiculo_m1l) {
                req.push(['vehiculo_placa', 'la placa del vehículo'],
                         ['conductor_doc_numero', 'el documento del conductor']);
            }
            // Transporte publico: los datos del transportista.
            if (f.modalidad === '01') {
                req.push(['transportista_ruc', 'el RUC del transportista'],
                         ['transportista_razon_social', 'la razón social del transportista']);
            }
            const malos = req.filter(([k]) => String(f[k] ?? '').trim() === '');
            if (! (f.items || []).some(i => (i.description || '').trim())) {
                malos.push(['__items', 'al menos un bien a trasladar']);
            }
            return malos;
        },

        /* Abre la representacion impresa en otra pestana con los datos del
           formulario. Se manda por POST dentro de un solo campo `payload`
           porque una guia lleva 20 campos y no caben en una URL. */
        verPrevia() {
            const f = document.createElement('form');
            f.method = 'POST';
            f.action = PREVIA_GUIA_URL;
            f.target = '_blank';
            f.style.display = 'none';
            const campo = (n, v) => {
                const i = document.createElement('input');
                i.type = 'hidden'; i.name = n; i.value = v;
                f.appendChild(i);
            };
            campo('_token', CSRF_GUIA);
            // Igual que al emitir: los renglones en blanco no son lineas.
            const datos = Object.assign({}, this.form);
            datos.items = (this.form.items || []).filter(
                i => (i.description || '').trim() !== ''
            );
            campo('payload', JSON.stringify(datos));
            document.body.appendChild(f);
            f.submit();
            f.remove();
        },

        async emitir() {
            if (this.enviando) return;

            const faltan = this.faltantes();
            if (faltan.length) {
                const nombres = faltan.map(([, n]) => n);
                const texto = nombres.length === 1
                    ? 'Falta ' + nombres[0] + '.'
                    : 'Faltan ' + nombres.length + ' datos: ' + nombres.slice(0, 3).join(', ')
                      + (nombres.length > 3 ? ' y ' + (nombres.length - 3) + ' más.' : '.');
                bxAviso(texto, 'error');
                // El cursor al primero que falta, para no buscarlo a ojo.
                const campo = document.querySelector('#guia-form [x-model="form.' + faltan[0][0] + '"]')
                           || document.querySelector('#guia-form [x-model\.number="form.' + faltan[0][0] + '"]');
                if (campo) {
                    campo.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    campo.focus({ preventScroll: true });
                }
                return;
            }

            this.enviando = true;
            this.errores  = [];
            // Huella del intento: si la peticion se repite (recarga, doble
            // toque), el servidor devuelve la guia ya creada en vez de otra.
            if (!this.huella) this.huella = 'g' + Date.now() + Math.random().toString(36).slice(2, 8);

            const cuerpo = { ...this.form };
            if (!cuerpo.invoice_id) delete cuerpo.invoice_id;
            if (!cuerpo.bultos) delete cuerpo.bultos;

            const res = await fetch('{{ $storeUrl }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Idempotencia': this.huella,
                },
                body: JSON.stringify(cuerpo),
            });

            const data = await res.json().catch(() => ({}));
            this.enviando = false;

            if (!res.ok) {
                // Se muestran todos los motivos juntos: corregir de uno en uno
                // con el camion esperando no ayuda a nadie.
                this.errores = data.errors
                    ? Object.values(data.errors).flat()
                    : [data.message || 'No se pudo emitir la guía.'];
                return;
            }

            bxAviso(data.message || 'Guía emitida.', 'exito');
            setTimeout(() => location.reload(), 800);
        },

        async reenviar(id) {
            const res = await fetch('{{ $enviarUrl }}'.replace('__ID__', id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                bxAviso(data.message || 'No se pudo enviar.', 'error');
                return;
            }

            bxAviso(data.message || 'Enviando...', 'exito');
            setTimeout(() => location.reload(), 900);
        },
    };
}
</script>

</x-portal-layout>

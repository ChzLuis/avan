{{--
    FORMULARIO DE EMISIÓN — rediseño 2026-09-04.

    Dos columnas en escritorio (contenido · resumen pegado), una sola en móvil
    con el total y el botón de emitir fijos abajo, al alcance del pulgar.

    El orden sigue el de la cabeza de quien factura: qué documento es, a quién,
    qué le vendo, cuánto suma. Antes el tipo de comprobante se preguntaba
    aunque se hubiera entrado por "Facturas", y el receptor quedaba detrás de
    los datos de la serie.

    Todo el estado y los métodos siguen viviendo en `invoicesApp()`: esto es
    solo la cara. No hay un segundo flujo de emisión.
--}}
<div id="inv-detalle-scroll" class="flex-1 overflow-y-auto" style="background:#f8fafc;"
     @scroll="cerrarTodasLasSugerencias()">
{{-- Ancho COMPLETO: el tope de 1400px dejaba franjas muertas a los lados en
     pantallas grandes, y ademas ni ese tope ni las clases lg: existian en el
     CSS compilado, asi que la columna del resumen tampoco se colocaba. --}}
<div class="inv-cuerpo">

    {{-- ══ COLUMNA PRINCIPAL ═══════════════════════════════════════════ --}}
    <div class="space-y-3 min-w-0">

        {{-- ── 1. El documento ─────────────────────────────────────────── --}}
        {{-- En movil arranca plegado: serie, correlativo y fechas ya vienen
             puestas y casi nunca se tocan. Ocupaban la primera pantalla
             entera antes de llegar a lo que de verdad se hace. --}}
        <section class="rounded-xl bg-white border p-4" style="border-color:#e5e7eb;"
                 x-show="!esMovil || paso === 1 || !pasosActivos">
            {{-- Cabecera en UNA franja: quien soy (titulo) a la izquierda y
                 los datos del documento a la derecha, en la misma linea. El
                 rotulo "COMPROBANTE" sobraba: el titulo ya dice que es, y
                 gastaba un renglon entero para no decir nada nuevo. --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-3"
                 @click="if (esMovil) docPlegado = !docPlegado">

                {{-- El titulo se fue a la barra de arriba: aqui solo quedan los
                     datos del documento. En movil, plegado, se resume en una
                     linea para saber que serie y fecha llevan sin desplegar. --}}
                <p x-show="esMovil && docPlegado" x-cloak class="text-xs text-gray-500 mr-auto"
                   x-text="form.serie + ' · ' + (form.issue_date || '')"></p>

            {{-- Cada campo con el ancho de lo que guarda: "F001" son cuatro
                 caracteres y la fecha diez. A cuartos de pantalla salian tres
                 cajas larguisimas medio vacias y el bloque ocupaba una franja
                 entera para tres datos que casi nunca se tocan. --}}
            <div class="inv-cab" x-show="!esMovil || !docPlegado" x-cloak>
                {{-- Etiquetas pequenas: estos campos acompanan al titulo, no
                     compiten con el. --}}
                {{-- El selector solo existe cuando de verdad hay que elegir. --}}
                <div x-show="!seccionFija" class="inv-w-tipo">
                    <label class="label">Tipo</label>
                    <select x-model="form.type" @change="autoSerie()" class="input">
                        <option value="boleta">Boleta</option>
                        <option value="factura">Factura</option>
                        <option value="nota_credito">Nota de crédito</option>
                        <option value="nota_debito">Nota de débito</option>
                    </select>
                </div>

                <div class="inv-w-serie">
                    <label class="label">Serie</label>
                    {{-- Las series las autoriza SUNAT por negocio: se elige de
                         las suyas, no se teclea (una serie inventada la
                         rechaza el sistema al declarar). --}}
                    <select x-show="seriesDelTipo().length > 1" x-model="form.serie" class="input">
                        <template x-for="s in seriesDelTipo()" :key="s">
                            <option :value="s" x-text="s"></option>
                        </template>
                    </select>
                    <input x-show="seriesDelTipo().length <= 1" x-model="form.serie" type="text"
                           class="input" maxlength="10" placeholder="F001">
                </div>

                <div class="inv-w-corr">
                    {{-- NO es un campo: el correlativo lo asigna el sistema al
                         emitir, en la misma transaccion que reserva el numero
                         (escribirlo a mano era la via de colisiones entre dos
                         cajeros). Como campo bloqueado solo gastaba sitio en la
                         primera pantalla para decir "Automatico". Ahora informa:
                         el numero que saldra, o el que ya salio. --}}
                    <label class="label">N.° del comprobante</label>
                    <div class="input bg-gray-50 text-gray-600 flex items-center"
                         x-text="form.correlativo
                             ? (form.serie + '-' + String(form.correlativo).padStart(8, '0'))
                             : (form.serie ? form.serie + ' · siguiente disponible' : 'Se asigna al emitir')"></div>
                </div>

                <div class="inv-w-fecha">
                    <label class="label">Fecha de emisión</label>
                    <input x-model="form.issue_date" type="date" class="input"
                           :min="fechaMinima" :max="fechaMaxima"
                           title="SUNAT admite hasta 3 días atrás">
                </div>

                {{-- El vencimiento se pide en "Pago y detalles", pegado al
                     selector de Condicion que lo hace obligatorio y con los
                     plazos de 15/30/45/60 dias. Aqui arriba estaba lejos de
                     esa decision: se elegia "Credito" y el campo que faltaba
                     quedaba fuera de la vista. --}}
            </div>

                {{-- Solo la flecha de plegar (movil). El distintivo con el tipo
                     se quito: el titulo ya dice "Nueva factura" a dos dedos de
                     ahi, y repetir "Factura" al lado no anadia nada. --}}
                <div class="flex items-center gap-2 inv-distintivo" style="border-color:#e5e7eb;">
                    <svg x-show="esMovil" class="w-4 h-4 text-gray-400 transition-transform flex-shrink-0"
                         :class="docPlegado ? '' : 'rotate-180'"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                    </svg>
                </div>
            </div>

        </section>

        {{-- ── 2. El cliente ───────────────────────────────────────────────
             Tarjeta propia con su icono: cada bloque se reconoce de un vistazo
             sin necesidad de un titulo grande ni una frase que explique lo
             obvio. El rotulo va pequeno, que es una etiqueta, no un anuncio. --}}

        <section class="inv-tarjeta" x-show="!esMovil || !pasosActivos || paso === 1">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="inv-icono-sec">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </span>
                    <h3 class="text-sm font-semibold text-gray-900">Cliente</h3>
                </div>
                <div class="flex items-center gap-2">
                    <span x-show="clienteEstado" x-cloak class="text-xs font-semibold"
                          :class="clienteEstadoOk ? 'text-emerald-600' : 'text-gray-500'"
                          x-text="clienteEstado"></span>
                    <button @click="abrirClientes()" type="button"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Ver clientes</button>
                </div>
            </div>

            {{-- Buscador y documento en la MISMA fila: son dos caminos al
                 mismo dato (a quien facturo), no dos pasos seguidos. En filas
                 separadas la vista bajaba entre campos que se usan como
                 alternativa uno del otro. --}}
            <div class="inv-fila">
                <div class="inv-c2">
                    <label class="label">Tipo doc.</label>
                    {{-- Una FACTURA solo se emite a un RUC: SUNAT no acepta DNI,
                         carne de extranjeria ni pasaporte como receptor. Ofrecer
                         esas opciones solo servia para que el comprobante lo
                         rechazaran DESPUES de emitirlo. Si el cliente da su DNI,
                         lo que corresponde es una boleta. En boleta si se eligen,
                         porque ahi cualquier documento es valido. --}}
                    <div x-show="form.type === 'factura'" x-cloak
                         class="input bg-gray-50 text-gray-700 font-semibold">RUC</div>
                    <select x-show="form.type !== 'factura'" x-model="form.client_doc_type" class="input">
                        <option value="">—</option>
                        <option value="DNI">DNI</option>
                        <option value="RUC">RUC</option>
                        <option value="CE">C.E.</option>
                        <option value="pasaporte">Pasaporte</option>
                    </select>
                </div>
                <div class="inv-c4">
                    <label class="label">N.° de documento</label>
                    {{-- SIN boton: se consulta sola al completar 8 u 11 digitos.
                         El boton solo anadia un clic obligatorio cuando el dato
                         ya estaba entero, y en el mostrador eso es tiempo. El
                         estado se ve DENTRO del campo (reloj, check o aspa) y
                         el borde acompana en verde o rojo, que es lo que se
                         mira de reojo mientras se teclea. --}}
                    <div class="relative">
                        <input x-model="form.client_doc_number" type="text" maxlength="15"
                               class="input pr-9" inputmode="numeric"
                               :class="docAviso ? (docAvisoOk ? 'border-emerald-500' : 'border-red-400') : ''"
                               @input="autoTipoDoc()"
                               @input.debounce.600ms="consultarDocumento()"
                               @keydown.enter.prevent="consultarDocumento(true)">
                        {{-- Buscando: el reloj dice que el sistema esta en ello
                             y evita teclear el nombre a mano por impaciencia. --}}
                        <svg x-show="buscandoRuc" x-cloak
                             class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-indigo-500 animate-spin"
                             fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                        </svg>
                        <svg x-show="!buscandoRuc && docAviso && docAvisoOk" x-cloak
                             class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-emerald-600"
                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                        </svg>
                        <svg x-show="!buscandoRuc && docAviso && !docAvisoOk" x-cloak
                             class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-red-500"
                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    {{-- Encontrado en verde, no encontrado en ROJO: en ambar se
                         leia como sugerencia y se seguia adelante con un RUC
                         que SUNAT no reconoce. --}}
                    <p x-show="docAviso" x-cloak class="text-xs mt-1 font-medium"
                       :class="docAvisoOk ? 'text-emerald-600' : 'text-red-600'" x-text="docAviso"></p>
                    {{-- Regla SUNAT: boleta de S/ 700 o mas siempre con comprador
                         identificado. Se avisa antes de intentar emitir. --}}
                    <p x-show="form.type === 'boleta' && calcTotal() >= 700 && !form.client_doc_number" x-cloak
                       class="text-xs mt-1 text-amber-600">Boleta de S/ 700 o más: SUNAT exige el DNI o RUC del comprador.</p>
                </div>
            <div class="relative inv-c6" @click.outside="clienteSugerencias = []">
                {{-- Con etiqueta, como sus vecinos: sin ella el cajon quedaba
                     mas alto que los demas y la fila se veia desalineada. --}}
                <label class="label">Buscar cliente</label>
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                    </svg>
                    <input x-model="clienteQuery"
                           @input.debounce.250ms="buscarClientes()"
                           @keydown.arrow-down.prevent="moverSugerenciaCliente(1)"
                           @keydown.arrow-up.prevent="moverSugerenciaCliente(-1)"
                           @keydown.enter.prevent="elegirSugerenciaActiva()"
                           @keydown.escape="clienteSugerencias = []"
                           type="text" class="input pl-9"
                           placeholder="Buscar por nombre, razón social, RUC o DNI..."
                           role="combobox" aria-autocomplete="list"
                           :aria-expanded="clienteSugerencias.length > 0">
                    <span x-show="buscandoRuc" x-cloak
                          class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-indigo-600">Consultando...</span>
                </div>

                {{-- Coincidencias internas primero: si el cliente ya existe, no
                     hay por qué salir a preguntarle a SUNAT. --}}
                <div x-show="clienteSugerencias.length" x-cloak
                     class="absolute z-30 mt-1 w-full rounded-lg border bg-white shadow-lg max-h-72 overflow-y-auto"
                     style="border-color:#e5e7eb;" role="listbox">
                    <template x-for="(c, i) in clienteSugerencias" :key="c.id">
                        <button type="button" @mousedown.prevent="elegirCliente(c)"
                                class="w-full text-left px-3 py-2 border-b last:border-b-0 transition-colors"
                                :class="i === clienteActivo ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                                style="border-color:#f3f4f6;" role="option" :aria-selected="i === clienteActivo">
                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="c.nombre"></p>
                            <p class="text-xs text-gray-500 truncate">
                                <span x-show="c.doc_numero" x-text="(c.doc_tipo || 'Doc') + ' ' + c.doc_numero"></span>
                                <span x-show="c.doc_numero && c.direccion"> · </span>
                                <span x-text="c.direccion || ''"></span>
                            </p>
                        </button>
                    </template>
                </div>
            </div>

            </div>

            {{-- Ficha en modo lectura: lo consultado se muestra, no se
                 re-teclea. Se edita solo si hace falta. --}}
            <div x-show="!editandoCliente && form.client_name" x-cloak
                 class="mt-3 rounded-lg bg-gray-50 p-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900" x-text="form.client_name"></p>
                        {{-- Falta de direccion en una FACTURA no es un dato mas:
                             SUNAT la rechaza. En gris se leia como nota al pie
                             y se pasaba de largo; en ambar con aviso se ve. --}}
                        <p class="text-xs mt-0.5"
                           :class="(!form.client_address && form.type === 'factura') ? 'text-amber-600 font-semibold' : 'text-gray-500'"
                           x-text="form.client_address || (form.type === 'factura' ? 'Falta la dirección fiscal · obligatoria' : 'Sin dirección')"></p>
                        <p x-show="form.client_phone || form.client_email" class="text-xs text-gray-400 mt-0.5">
                            <span x-text="form.client_phone || ''"></span>
                            <span x-show="form.client_phone && form.client_email"> · </span>
                            <span x-text="form.client_email || ''"></span>
                        </p>
                    </div>
                    <button @click="editandoCliente = true" type="button"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex-shrink-0">Editar</button>
                </div>
            </div>

            {{-- Los CUATRO datos en UNA fila de 12 columnas, el mismo compas
                 que la fila de arriba: nombre y direccion son los largos y se
                 llevan cuatro columnas cada uno; telefono y email, dos. Antes
                 iban de dos en dos y el bloque gastaba dos filas para lo que
                 cabe en una. En movil siguen apilados. --}}
            <div x-show="editandoCliente || !form.client_name" x-cloak
                 class="inv-fila mt-3">
                <div class="inv-c4">
                    <label class="label">Nombre o razón social <span class="text-red-500">*</span></label>
                    <input x-model="form.client_name" type="text" class="input" placeholder="Cliente">
                </div>
                <div class="inv-c4">
                    <label class="label">Dirección
                        <span x-show="form.type === 'factura'" class="text-red-500">*</span>
                    </label>
                    <input x-model="form.client_address" type="text" class="input"
                           :placeholder="form.type === 'factura' ? 'Domicilio fiscal (obligatorio)' : ''">
                </div>
                <div class="inv-c2">
                    <label class="label">Teléfono</label>
                    <input x-model="form.client_phone" type="tel" inputmode="tel" class="input" placeholder="Ej. 987 654 321">
                </div>
                <div class="inv-c2">
                    <label class="label">Email</label>
                    <input x-model="form.client_email" type="email" class="input" placeholder="Ej. cliente@empresa.com">
                </div>
                <div x-show="editandoCliente" class="flex justify-end">
                    <button @click="editandoCliente = false" type="button"
                            class="text-xs font-semibold text-gray-500 hover:text-gray-700">Listo</button>
                </div>
            </div>
        </section>

        {{-- ── Pago y detalles ─────────────────────────────────────────────
             Condicion, moneda y medio de pago son datos de CABECERA, como la
             serie o la fecha: se eligen al empezar, no al final. --}}
        {{-- Sin rotulo: "Condicion", "Moneda" y "Medio de pago" ya dicen que
             es esto. Un titulo encima solo anadia un renglon mas. --}}
        <section class="inv-tarjeta" x-show="!esMovil || !pasosActivos || paso === 3">
                <div class="inv-fila">
                    <div class="inv-c2">
                        <label class="label">Condición</label>
                        <select x-model="form.payment_condition" @change="alCambiarCondicion()" class="input">
                            <option value="contado">Contado</option>
                            <option value="credito">Crédito</option>
                        </select>
                    </div>
                    <div class="inv-c2">
                        <label class="label">Moneda</label>
                        <select x-model="form.currency" class="input">
                            <option value="PEN">Soles</option>
                            <option value="USD">Dólares</option>
                        </select>
                    </div>
                    <div class="inv-c2">
                        <label class="label">Medio de pago</label>
                        <select x-model="form.payment_method" class="input">
                            <option value="">—</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Yape">Yape</option>
                            <option value="Plin">Plin</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Tarjeta de crédito">Tarjeta de crédito</option>
                            <option value="Tarjeta de débito">Tarjeta de débito</option>
                            <option value="Depósito">Depósito</option>
                        </select>
                    </div>
                    <div class="inv-c6">
                        <label class="label">Observaciones</label>
                        <input x-model="form.notes" type="text" class="input"
                               placeholder="Orden de compra, referencias...">
                    </div>
                </div>

                {{-- PLAZO DE CREDITO. SUNAT exige la fecha de pago en toda venta
                     al credito, y quien elige "Credito" tiene que verla aqui
                     mismo: en otra parte de la pantalla parecia que la opcion
                     no pedia nada mas. Los plazos de calle son 15/30/45/60. --}}
                <div x-show="form.payment_condition === 'credito'" x-cloak
                     class="rounded-lg border p-3 mt-3" style="border-color:#fde68a;background:#fffbeb;">
                    <label class="label">Plazo de pago <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template x-for="d in [15, 30, 45, 60]" :key="d">
                            <button type="button" @click="fijarPlazo(d)"
                                    class="min-h-[34px] px-2.5 rounded-lg border text-xs font-semibold transition"
                                    :class="plazoElegido === d
                                        ? 'bg-indigo-600 border-indigo-600 text-white'
                                        : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    x-text="d + ' días'"></button>
                        </template>
                        <input x-model="form.due_date" @change="plazoElegido = null"
                               type="date" class="input inv-w-fecha" :min="form.issue_date"
                               :required="form.payment_condition === 'credito'">
                        <span class="text-xs"
                              :class="form.due_date ? 'text-gray-500' : 'text-red-600 font-semibold'"
                              x-text="textoPlazo()"></span>
                    </div>
                </div>
        </section>

        {{-- ── 3. Los productos ────────────────────────────────────────── --}}
        <section class="rounded-xl bg-white border p-4" style="border-color:#e5e7eb;"
                 x-show="!esMovil || !pasosActivos || paso === 2">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Productos y servicios</h3>
                <div class="flex items-center gap-2">
                    <button @click="openProductPicker()" type="button"
                            class="min-h-[38px] px-3 rounded-lg border border-indigo-200 bg-indigo-50 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                        Seleccionar del catálogo
                    </button>
                    <button @click="addItem()" type="button"
                            class="min-h-[38px] px-3 rounded-lg text-xs font-semibold text-indigo-600 hover:bg-indigo-50">
                        + Ítem manual
                    </button>
                </div>
            </div>

            {{-- ESCRITORIO: tabla. Llenar una factura es teclear en rejilla,
                 no navegar tarjetas. --}}
            {{-- Alto de 5 renglones: mas que eso empuja el resumen y el boton de
                 guardar fuera de la pantalla. A partir del sexto, la zona
                 scrollea con la cabecera fija. --}}
            {{-- Al desplazar, la lista flotante se quedaria clavada donde
                 estaba (es `fixed`): se cierra, que es lo que hace cualquier
                 desplegable del sistema. --}}
            <div class="hidden md:block overflow-x-auto -mx-1"
                 @scroll="cerrarTodasLasSugerencias()"
                 :class="form.items.length > 5 ? 'fac-rejilla' : ''">
                <table class="w-full text-sm fac-hoja" style="min-width:660px;">
                    <thead>
                        <tr class="text-xs text-gray-500 border-b" style="border-color:#e5e7eb;">
                            <th class="text-left font-semibold py-2 pl-1 w-8">#</th>
                            <th class="text-left font-semibold py-2">Descripción</th>
                            <th class="text-center font-semibold py-2 w-24">UM</th>
                            <th class="text-center font-semibold py-2 w-20">Cant.</th>
                            <th class="text-right font-semibold py-2 w-28">P. unit.</th>
                            <th class="text-right font-semibold py-2 w-20">Desc. %</th>
                            <th class="text-right font-semibold py-2 w-28">Importe</th>
                            <th class="w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in form.items" :key="idx">
                            <tr class="border-b align-top" style="border-color:#f3f4f6;"
                                :class="{ 'fac-fila-vacia': !(item.description || '').trim(), 'fac-fila-sin-precio': (item.description || '').trim() && !(Number(item.unit_price) > 0), 'fac-fila-sin-cant': (item.description || '').trim() && !(Number(item.quantity) > 0) }">
                                <td class="fac-num" x-text="idx + 1"></td>
                                <td class="relative">
                                    <input x-model="item.description"
                                           :data-descripcion="idx"
                                           @input="searchCatalog(idx)" @focus="searchCatalog(idx)"
                                           @keydown.arrow-down="item.showSuggestions && item.suggestions.length ? (($event.preventDefault()), moveCatalogSuggestion(idx, 1)) : moverCelda($event, 'descripcion', idx)"
                                           @keydown.arrow-up="item.showSuggestions && item.suggestions.length ? (($event.preventDefault()), moveCatalogSuggestion(idx, -1)) : moverCelda($event, 'descripcion', idx)"
                                           @keydown.arrow-left="moverCelda($event, 'descripcion', idx)"
                                           @keydown.arrow-right="moverCelda($event, 'descripcion', idx)"
                                           @keydown.enter.prevent="selectActiveSuggestion(idx)"
                                           @keydown.escape="closeCatalogSuggestions(idx)"
                                           @keydown.tab="closeCatalogSuggestions(idx)"
                                           @click.outside="closeCatalogSuggestions(idx)"
                                           type="text" class="input" placeholder="Buscar por nombre, SKU o código..."
                                           role="combobox" aria-autocomplete="list"
                                           :aria-expanded="item.showSuggestions">
                                    {{-- Lista COMPACTA: una linea por producto y seis como maximo.
                                         Con dos lineas por producto y diez productos la lista
                                         tapaba las cinco filas de la rejilla. --}}
                                    {{-- FIJA, no absoluta. La rejilla vive dentro de un
                                         `overflow-x:auto` (necesario para que la tabla no
                                         rompa el ancho en pantallas medianas), y un
                                         contenedor que recorta en X recorta TAMBIEN en Y:
                                         el navegador fuerza `overflow-y` a `auto`. La lista
                                         salia por debajo del campo y quedaba cortada a unos
                                         pocos pixeles: parecia que el buscador no encontraba
                                         nada. Con `position:fixed` y las coordenadas del
                                         campo, la lista flota sobre todo y no la recorta
                                         nadie. --}}
                                    <div x-show="item.showSuggestions && item.suggestions.length" x-cloak
                                         class="fac-sugerencias rounded-md border bg-white shadow-lg overflow-hidden"
                                         style="position:fixed; z-index:50; border-color:#e5e7eb;"
                                         :style="posicionSugerencias(idx)" role="listbox">
                                        <template x-for="(product, sIdx) in item.suggestions" :key="product.key">
                                            <button type="button" @mousedown.prevent="selectCatalogProduct(idx, product)"
                                                    class="fac-sug w-full text-left flex items-center gap-2"
                                                    :class="sIdx === item.activeSuggestion ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                                                    role="option" :aria-selected="sIdx === item.activeSuggestion">
                                                <span class="flex-1 min-w-0 truncate text-gray-900" x-text="product.name"></span>
                                                {{-- Sin SKU no se reserva su hueco: en catalogos que no lo
                                                     usan robaba ancho al nombre para no decir nada. --}}
                                                <span class="text-gray-400 flex-shrink-0 hidden md:inline"
                                                      x-show="product.sku" x-text="product.sku"></span>
                                                <span class="font-semibold text-gray-800 flex-shrink-0" x-text="money(product.price)"></span>
                                            </button>
                                        </template>
                                        <div class="fac-sug-pie" x-show="item.masResultados > 0" x-cloak
                                             x-text="'+' + item.masResultados + ' más · sigue escribiendo para afinar'"></div>
                                    </div>
                                </td>
                                <td>
                                    {{-- Sin este campo la unidad venia del catalogo y no habia
                                         forma de corregirla: salia "Rollo 100 m" en el papel. --}}
                                    <select x-model="item.unit" @change="item.unitTocada = true" class="input px-1 text-xs">
                                        @foreach(\App\Modules\Finanzas\Support\Sunat\Catalogos::unidadesComunes() as $codigoUm => $etiquetaUm)
                                        <option value="{{ $codigoUm }}">{{ $etiquetaUm }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input x-model.number="item.quantity" type="number" inputmode="decimal" min="0.001" step="1"
                                           :data-cantidad="idx"
                                           @focus="$event.target.select()"
                                           @keydown.enter.prevent="siguienteLinea(idx)"
                                           @keydown.arrow-down="moverCelda($event, 'cantidad', idx)"
                                           @keydown.arrow-up="moverCelda($event, 'cantidad', idx)"
                                           @keydown.arrow-left="moverCelda($event, 'cantidad', idx)"
                                           @keydown.arrow-right="moverCelda($event, 'cantidad', idx)"
                                           class="input text-center px-1">
                                </td>
                                <td>
                                    <input x-model.number="item.unit_price" type="number" inputmode="decimal" min="0" step="0.01"
                                           :data-precio="idx" placeholder="0.00"
                                           @focus="$event.target.select()" @keydown.enter.prevent="siguienteLinea(idx)"
                                           @keydown.arrow-down="moverCelda($event, 'precio', idx)"
                                           @keydown.arrow-up="moverCelda($event, 'precio', idx)"
                                           @keydown.arrow-left="moverCelda($event, 'precio', idx)"
                                           @keydown.arrow-right="moverCelda($event, 'precio', idx)"
                                           class="input text-right px-1">
                                </td>
                                <td>
                                    <input x-model.number="item.discount" type="number" inputmode="decimal" min="0" max="100" step="0.01"
                                           :data-descuento="idx"
                                           @focus="$event.target.select()" @keydown.enter.prevent="siguienteLinea(idx)"
                                           @keydown.arrow-down="moverCelda($event, 'descuento', idx)"
                                           @keydown.arrow-up="moverCelda($event, 'descuento', idx)"
                                           @keydown.arrow-left="moverCelda($event, 'descuento', idx)"
                                           @keydown.arrow-right="moverCelda($event, 'descuento', idx)"
                                           class="input text-right px-1" placeholder="0">
                                </td>
                                <td class="fac-imp whitespace-nowrap"
                                    :class="avisoLinea(item) ? 'fac-falta' : ''"
                                    x-text="avisoLinea(item) || money(importeLinea(item))"></td>
                                <td class="fac-quitar">
                                    <button @click="removeItem(idx)" x-show="form.items.length > 1" type="button"
                                            class="text-gray-300 hover:text-red-500 px-1" aria-label="Quitar">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    {{-- TOTALES AL PIE DE LA REJILLA.
                         Vivian en una tarjeta aparte, a la derecha y separada
                         por un hueco: el importe de cada linea quedaba en una
                         vertical y el Total en otra, asi que no se podia
                         comprobar la suma de un vistazo. Como pie de la misma
                         tabla caen bajo la columna Importe y el bloque crece o
                         mengua con las lineas, sin hueco que lo separe. --}}
                    <tfoot class="fac-totales">
                        <tr>
                            <td colspan="6" class="fac-tot-etq">Subtotal</td>
                            <td class="fac-tot-val" x-text="money(calcSubtotal())"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="fac-tot-etq">IGV 18%</td>
                            <td class="fac-tot-val" x-text="money(calcIgv())"></td>
                            <td></td>
                        </tr>
                        <tr class="fac-tot-final">
                            <td colspan="6" class="fac-tot-etq">Total</td>
                            <td class="fac-tot-val" x-text="money(calcTotal())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- El interruptor del IGV va pegado a los totales que gobierna:
                 en la tarjeta de la derecha estaba lejos de las cifras que
                 cambia. --}}
            <label class="hidden md:flex items-center gap-2 mt-2 pt-2 border-t cursor-pointer"
                   style="border-color:#e5e7eb;">
                <input type="checkbox" x-model="form.igv_included" class="rounded border-gray-300">
                <span class="text-xs text-gray-600">Los precios ya incluyen IGV</span>
            </label>

            {{-- MÓVIL: la misma información en vertical, sin tabla que se salga. --}}
            <div class="md:hidden space-y-2">
                <template x-for="(item, idx) in form.items" :key="idx">
                    <div class="rounded-lg border p-2.5" style="border-color:#e5e7eb;">
                        <div class="relative">
                            <input x-model="item.description"
                                   @input="searchCatalog(idx)" @focus="searchCatalog(idx)"
                                   @keydown.enter.prevent="selectActiveSuggestion(idx)"
                                   @keydown.escape="closeCatalogSuggestions(idx)"
                                   @click.outside="closeCatalogSuggestions(idx)"
                                   type="text" class="input" placeholder="Buscar producto...">
                            <div x-show="item.showSuggestions && item.suggestions.length" x-cloak
                                 class="absolute z-30 mt-1 inset-x-0 rounded-lg border bg-white shadow-lg max-h-56 overflow-y-auto"
                                 style="border-color:#e5e7eb;">
                                <template x-for="product in item.suggestions" :key="product.key">
                                    <button type="button" @mousedown.prevent="selectCatalogProduct(idx, product)"
                                            class="w-full text-left px-3 py-2 border-b last:border-b-0 hover:bg-gray-50"
                                            style="border-color:#f3f4f6;">
                                        <span class="text-sm text-gray-900" x-text="product.name"></span>
                                        <span class="block text-xs text-gray-400" x-text="money(product.price)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-2 mt-2">
                            <div>
                                <label class="text-xs text-gray-400">UM</label>
                                <select x-model="item.unit" @change="item.unitTocada = true" class="input px-1 text-xs w-full">
                                        @foreach(\App\Modules\Finanzas\Support\Sunat\Catalogos::unidadesComunes() as $codigoUm => $etiquetaUm)
                                        <option value="{{ $codigoUm }}">{{ $etiquetaUm }}</option>
                                        @endforeach
                                    </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Cantidad</label>
                                <input x-model.number="item.quantity" type="number" inputmode="decimal" min="0.001" step="1"
                                       :data-cantidad="idx"
                                       @focus="$event.target.select()"
                                       @keydown.enter.prevent="siguienteLinea(idx)"
                                       class="input text-center">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">P. unit.</label>
                                <input x-model.number="item.unit_price" type="number" inputmode="decimal" min="0" step="0.01" placeholder="0.00" class="input text-right">
                            </div>
                            <div>
                                <label class="text-xs text-gray-400">Desc. %</label>
                                <input x-model.number="item.discount" type="number" inputmode="decimal" min="0" max="100" step="0.01" class="input text-right" placeholder="0">
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t" style="border-color:#f3f4f6;">
                            <button @click="removeItem(idx)" x-show="form.items.length > 1" type="button"
                                    class="min-h-[40px] -my-2 px-3 text-xs font-semibold text-red-500 active:bg-red-50 rounded-lg">Quitar</button>
                            <span class="text-sm font-semibold ml-auto"
                                  :class="avisoLinea(item) ? 'text-red-600' : 'text-gray-900'"
                                  x-text="avisoLinea(item) || money(importeLinea(item))"></span>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    {{-- ══ COLUMNA DERECHA — RESUMEN ═══════════════════════════════════ --}}
    {{-- Solo MOVIL: lo unico que lleva es el resumen de totales, y en
         escritorio esos totales ya salen al pie de la rejilla de productos.
         Reservaba 320px que quedaban en blanco a la derecha del formulario. --}}
    <div class="inv-resumen space-y-3"
         x-show="!esMovil || !pasosActivos || paso === 3">

        {{-- En escritorio los totales ya viven al pie de la rejilla, cuadrados
             con la columna Importe: repetirlos aqui era leer dos veces lo
             mismo. En movil no hay tabla (son tarjetas), asi que el resumen
             sigue siendo la unica forma de ver el total. --}}
        <section class="rounded-xl bg-white border p-4" style="border-color:#e5e7eb;">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Resumen</h3>

            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span x-text="money(calcSubtotal())"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>IGV 18%</span>
                    <span x-text="money(calcIgv())"></span>
                </div>
                <div class="flex justify-between items-baseline pt-2 mt-1 border-t" style="border-color:#e5e7eb;">
                    <span class="text-sm font-semibold text-gray-700">Total</span>
                    <span class="text-xl font-bold text-gray-900" x-text="money(calcTotal())"></span>
                </div>
            </div>

            <label class="flex items-center gap-2 mt-3 pt-3 border-t cursor-pointer" style="border-color:#e5e7eb;">
                <input type="checkbox" x-model="form.igv_included" class="rounded border-gray-300">
                <span class="text-xs text-gray-600">Los precios ya incluyen IGV</span>
            </label>
        </section>

        <template x-if="saveError">
            <div class="rounded-lg bg-red-50 border border-red-200 px-3 py-2">
                <p class="text-xs text-red-700" x-text="saveError"></p>
            </div>
        </template>
    </div>
</div>
</div>


<style>
    /* REJILLA TIPO HOJA DE CALCULO
       Los campos redondeados y separados hacian que cinco lineas parecieran
       cinco formularios sueltos. Con el campo pegado al borde de su celda se
       lee como una cuadricula: se tabula de corrido y la vista sigue la fila. */
    .fac-hoja { border-collapse: collapse; }
    .fac-hoja thead th { border: 1px solid #e5e7eb; background: #f9fafb; padding: 6px 8px; }
    .fac-hoja tbody td { border: 1px solid #e5e7eb; padding: 0; vertical-align: middle; }
    /* El campo ocupa la celda entera: sin borde propio, sin sombra y sin radio.
       El unico borde visible es el de la cuadricula. */
    .fac-hoja tbody td > input,
    .fac-hoja tbody td > select {
        width: 100%; border: 0; border-radius: 0; box-shadow: none;
        background: transparent; height: 38px; min-height: 0; padding: 0 8px; font-size: 13px;
    }
    .fac-hoja tbody td > input:focus,
    .fac-hoja tbody td > select:focus { outline: 2px solid #6366f1; outline-offset: -2px; background: #fff; }
    .fac-hoja tbody td.fac-num { text-align: center; color: #9ca3af; font-size: 12px; }
    .fac-hoja tbody td.fac-imp { text-align: right; padding: 0 8px; font-weight: 700; }
    /* Renglon sin producto: los numeros por defecto (1 y 0) se atenuan para
       que no parezcan datos. Renglon con producto y sin precio: se marca. */
    .fac-fila-vacia td > input:not([x-model="item.description"]),
    .fac-fila-vacia td > select,
    .fac-fila-vacia td.fac-imp { opacity: .35; }
    .fac-fila-sin-precio td:has(> input[x-model\.number="item.unit_price"]) { background: #fef2f2; }
    .fac-fila-sin-precio td > input[x-model\.number="item.unit_price"] { color: #b91c1c; font-weight: 700; }
    /* Cantidad 0 con producto escrito: el mismo aviso por el otro lado. */
    .fac-fila-sin-cant td:has(> input[x-model\.number="item.quantity"]) { background: #fef2f2; }
    .fac-fila-sin-cant td > input[x-model\.number="item.quantity"] { color: #b91c1c; font-weight: 700; }
    td.fac-imp.fac-falta { color: #b91c1c; font-size: 12px; }
    .fac-hoja tbody td.fac-quitar { text-align: center; }
    /* Sugerencias del buscador: filas de 30px, seis a la vista, sin tapar
       media rejilla. */
    .fac-sugerencias { max-height: 216px; }
    .fac-sug { min-height: 30px; padding: 4px 10px; font-size: 12.5px; border-bottom: 1px solid #f3f4f6; }
    .fac-sug:last-of-type { border-bottom: 0; }
    .fac-sug-pie { padding: 4px 10px; font-size: 11px; color: #6b7280; background: #f9fafb; border-top: 1px solid #e5e7eb; }
    .fac-hoja tbody td:last-child { border: 0; }
    .fac-hoja thead th:last-child { border: 0; background: transparent; }

    /* Pie de totales: parte de la misma cuadricula, alineado con Importe. */
    .fac-totales td { border: 0; padding: 3px 8px; font-size: 13px; }
    .fac-totales .fac-tot-etq { text-align: right; color: #6b7280; padding-right: 12px; }
    .fac-totales .fac-tot-val { text-align: right; color: #111827; font-weight: 600; white-space: nowrap; }
    /* La primera fila del pie se separa del detalle con una linea, para que no
       se lea como una linea de producto mas. */
    .fac-totales tr:first-child td { padding-top: 8px; border-top: 1px solid #e5e7eb; }
    .fac-totales .fac-tot-final td { padding-top: 6px; border-top: 1px solid #e5e7eb; }
    .fac-totales .fac-tot-final .fac-tot-etq { color: #374151; font-weight: 700; font-size: 13px; }
    .fac-totales .fac-tot-final .fac-tot-val { font-size: 17px; font-weight: 800; }

    /* 5 renglones visibles (~46px cada uno) mas la cabecera. */
    /* Solo a partir de la 6a linea: antes de eso NADA recorta, o el
       desplegable del buscador sale cortado bajo el campo. */
    .fac-rejilla { max-height: 268px; overflow-y: auto; }
    .fac-rejilla thead th { position: sticky; top: 0; z-index: 2; background: #fff; }
</style>

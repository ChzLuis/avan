@php
    // El lector habla a la ruta de SU cara: leer desde Operacion y acabar
    // en el panel de Configuracion rompe el hilo de trabajo.
    $lectorUrl = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.facturas.lector')
        : route('invoices.lector');
    $lectorAplicarUrl = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.facturas.lector.aplicar')
        : route('invoices.lector.aplicar');
    // Buscador de clientes y consulta de documento, tambien por cara.
    $clientesUrl = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.facturas.clientes')
        : route('invoices.clientes');
    $rucUrl = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.facturas.ruc')
        : route('invoices.ruc');
    $invoicesApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.facturas')
        : route('invoices.index');
@endphp
@php $_tituloSeccion = ['factura' => 'Facturas', 'boleta' => 'Boletas', 'nota' => 'Notas de crédito y débito'][$seccion ?? ''] ?? 'Comprobantes'; @endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" :pageTitle="$_tituloSeccion">

{{-- ══ El comprobante desde el celular ═══════════════════════════════════
     Quien factura en GABDE está de pie en el mostrador con el teléfono en
     una mano. Esta pantalla nació de escritorio —dos paneles fijos de
     340 px— y en un celular de 390 px dejaba una franja muerta al costado,
     el botón de emitir al final de un scroll largo y campos que hacían
     zoom al tocarlos. Aquí no se cambia ninguna lógica: solo se le da al
     móvil el ancho completo, el pulgar alcanza lo importante y el teclado
     deja de pelear con el formulario. --}}
<style>
/* ── Rejilla del encabezado ──────────────────────────────────────────────
   CSS propio, NO clases sm:col-span-* de Tailwind: el build de este proyecto
   no genera las variantes responsive (el CSS compilado no tiene ni una media
   query de breakpoint), asi que esas clases se quedaban sin regla y cada
   campo caia a su propia fila. El resto de la pantalla ya resuelve lo
   responsive con media queries propias; esto sigue esa misma via. */
/* Acciones del comprobante ya emitido: Descargar e Imprimir con texto.
   Eran iconos grises de 16px arriba a la derecha y pasaban desapercibidos. */
.inv-accion {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 12px; border-radius: 8px;
    border: 1px solid #d1d5db; background: #fff;
    font-size: 12px; font-weight: 600; color: #374151;
    text-decoration: none; white-space: nowrap;
    transition: background .15s, border-color .15s, color .15s;
}
.inv-accion:hover { background: #f3f4f6; border-color: #9ca3af; color: #111827; }
.inv-accion-fuerte { background: #4f46e5; border-color: #4f46e5; color: #fff; }
.inv-accion-fuerte:hover { background: #4338ca; border-color: #4338ca; color: #fff; }
@media (max-width: 640px) { .inv-accion span { display: none; } .inv-accion { padding: 6px 8px; } }

.inv-fila { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 12px; align-items: start; }
.inv-c2 { grid-column: span 2 / span 2; }
.inv-c4 { grid-column: span 4 / span 4; }
.inv-c6 { grid-column: span 6 / span 6; }
/* Cabecera: cada dato con el ancho de lo que guarda, no a tercios. */
.inv-cab { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 12px; }
.inv-w-serie { width: 96px; }
.inv-w-corr  { width: 128px; }
.inv-w-fecha { width: 160px; }
.inv-w-tipo  { width: 176px; }
/* Icono, subtitulo y separador del distintivo: se ocultan o aparecen segun
   el ancho, y tampoco pueden confiarse a variantes sm: de Tailwind. */
/* Cuerpo del formulario: contenido a la izquierda y resumen fijo de 320px a
   la derecha, ocupando TODO el ancho disponible. */
/* Una sola columna a TODO el ancho: la de la derecha solo llevaba el resumen
   de totales, que en escritorio ya sale al pie de la rejilla de productos.
   Reservarle 320px dejaba una franja blanca al costado del formulario. */
.inv-cuerpo { padding: 16px; display: grid; grid-template-columns: minmax(0, 1fr);
              gap: 16px; align-items: start; }
.inv-resumen { display: none; }
@media (max-width: 767px) {
    /* En movil no hay rejilla de productos (son tarjetas): el resumen es la
       unica forma de ver el total, asi que ahi si se muestra. */
    .inv-resumen { display: block; margin-top: 12px; }
}

/* Cada bloque en su tarjeta. El icono identifica la seccion mejor que un
   titulo grande, asi que el rotulo se queda pequeno: es una etiqueta, no un
   anuncio, y encima de un formulario que se llena a diario estorba. */
.inv-tarjeta { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
               padding: 16px; margin-top: 12px; }
.inv-icono-sec { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
                 background: #eef2ff; color: #4f46e5;
                 display: flex; align-items: center; justify-content: center; }

/* Titulo de la barra: solo en movil. En escritorio lo dice la tarjeta. */
/* (el titulo vive ahora en la barra superior, siempre visible) */
.inv-icono { display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
/* Ya solo guarda la flecha de plegar, que es de movil: en escritorio no debe
   dejar ni raya divisoria ni hueco colgando a la derecha. */
.inv-distintivo { display: none; }
@media (max-width: 640px) {
    .inv-icono, .inv-subtitulo { display: none; }
    .inv-distintivo { display: flex; padding-left: 0; border-left: 0; }
    /* En movil todo apilado: 12 columnas en 390 px no se leen. */
    .inv-fila { grid-template-columns: minmax(0, 1fr); }
    .inv-c2, .inv-c4, .inv-c6 { grid-column: 1 / -1; }
    .inv-w-serie, .inv-w-corr { width: calc(50% - 6px); }
    .inv-w-fecha, .inv-w-tipo { width: 100%; }
}

@media (max-width: 767px) {
    /* El panel de lista ya no vale 340 px: vale la pantalla entera. */
    #inv-lista { width: 100% !important; border-right: 0 !important; }

    /* iOS hace zoom en cualquier campo por debajo de 16 px y deja la página
       descuadrada; el vendedor termina pellizcando para volver. */
    #inv-app input, #inv-app select, #inv-app textarea { font-size: 16px !important; }

    /* Dedo, no cursor: 44 px es el mínimo que se acierta sin mirar. */
    #inv-app .input, #inv-app select.input { min-height: 46px; }
    #inv-app button { min-height: 44px; }

    /* Emitir es la acción de la pantalla: se queda fija abajo, sobre el
       pulgar, en vez de esperar al final del scroll. */
    #inv-form-pie {
        position: sticky; bottom: 0; z-index: 20;
        padding-bottom: calc(12px + env(safe-area-inset-bottom));
        box-shadow: 0 -6px 16px rgba(15,23,42,.10);
    }
    #inv-form-pie .btn-primary { flex: 1; font-size: 15px; font-weight: 700; }

    /* En móvil el "+" de la cabecera sobra: lo reemplaza el botón flotante,
       que cae bajo el pulgar. (El buscador y la tira del registro ya no
       viven aquí: se fueron a "Comprobantes emitidos".) */
    #inv-cab { flex-wrap: wrap; }
    #inv-cab .inv-nuevo-desktop { display: none !important; }

    /* Botón de nuevo comprobante al alcance del pulgar. En escritorio no
       existe: allí manda el "+" de la cabecera. */
    #inv-fab {
        position: fixed; right: 18px; z-index: 40;
        bottom: calc(20px + env(safe-area-inset-bottom));
        height: 56px; padding: 0 22px; border-radius: 28px;
        box-shadow: 0 10px 28px rgba(79,70,229,.42);
        display: inline-flex; align-items: center; gap: 8px;
    }
}
@media (min-width: 768px) { #inv-fab { display: none; } }
</style>

<div id="inv-app" class="mod-tactil flex flex-1 overflow-hidden"
     x-data="invoicesApp()"
     x-init="init()"
     @resize.window="isMobile = window.innerWidth < 768">

{{-- Emitir, al alcance del pulgar. Solo aparece en el celular y solo cuando
     se está mirando la lista: dentro del formulario estorbaría al botón de
     emitir, que ya está fijo abajo. --}}
<button id="inv-fab" type="button" @click="openNew()"
        x-show="isMobile && panel==='list' && !creating" x-cloak
        class="bg-indigo-600 text-white font-semibold text-sm active:bg-indigo-700"
        aria-label="Nuevo comprobante">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
    </svg>
    Nuevo
</button>

@if(($seccion ?? '') === 'nota')
{{-- ══ NOTAS DE CREDITO Y DEBITO ═══════════════════════════════════════
     Una nota no es un comprobante que se emita en blanco: corrige a una
     factura o boleta concreta, y SUNAT exige que diga a cual y por que
     motivo de su catalogo. Antes esta seccion abria el formulario de venta
     con el tipo cambiado: salia con serie F001 (la de facturas), sin
     documento afectado, sin motivo y con el cliente vacio.
     Aqui se elige el comprobante y se sigue por su flujo, que hereda
     receptor y lineas y usa la serie propia de notas. --}}
<div class="flex-1 min-h-0 overflow-y-auto" style="background:#f8fafc">
    <div class="mx-auto w-full max-w-3xl px-4 py-6 space-y-5">

        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-4">
            <h2 class="text-sm font-bold text-indigo-900">¿Qué comprobante quieres corregir?</h2>
            <p class="mt-1 text-xs leading-relaxed text-indigo-800/80">
                Una nota de crédito anula o rebaja el importe; la de débito lo aumenta.
                Siempre van sobre una factura o boleta ya emitida y aceptada, de la que
                heredan el cliente y las líneas.
            </p>
            <div class="mt-3 flex gap-2">
                <input type="search" x-model="notaBusca" @keydown.enter="buscarParaNota()"
                       placeholder="Número, cliente o RUC…"
                       class="flex-1 min-w-0 rounded-xl border-gray-300 text-sm">
                <button type="button" @click="buscarParaNota()"
                        class="flex-shrink-0 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                    Buscar
                </button>
            </div>
        </div>

        <div>
            <p class="px-1 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500"
               x-text="notaBusca ? 'Resultados' : 'Comprobantes recientes que admiten nota'"></p>

            <template x-if="corregibles.length === 0">
                <p class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-400">
                    No hay comprobantes aceptados que admitan nota.
                </p>
            </template>

            <div class="space-y-2">
                <template x-for="inv in corregibles" :key="'c'+inv.id">
                    <button type="button" @click="select(inv)"
                            class="flex w-full items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-left transition hover:border-indigo-300 hover:bg-indigo-50/40">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">
                                <span x-text="inv.numero"></span>
                                <span class="ml-1 text-xs font-medium text-gray-400" x-text="inv.type_label"></span>
                            </p>
                            <p class="truncate text-xs text-gray-500" x-text="inv.client_name"></p>
                        </div>
                        <span class="flex-shrink-0 text-sm font-semibold text-gray-700"
                              x-text="'S/ ' + (Number(inv.total)||0).toFixed(2)"></span>
                        <svg class="h-4 w-4 flex-shrink-0 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </template>
            </div>
            <p class="px-1 pt-2 text-xs text-gray-400">
                Al elegir uno se abre su ficha: los botones de nota están al final.
            </p>
        </div>

        <div>
            <p class="px-1 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Notas emitidas</p>
            <template x-if="notasEmitidas.length === 0">
                <p class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-6 text-center text-sm text-gray-400">
                    Todavía no has emitido ninguna nota.
                </p>
            </template>
            <div class="space-y-2">
                <template x-for="inv in notasEmitidas" :key="'n'+inv.id">
                    <button type="button" @click="select(inv)"
                            class="flex w-full items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-left transition hover:border-gray-300">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-gray-900">
                                <span x-text="inv.numero"></span>
                                <span class="ml-1 text-xs font-medium text-gray-400" x-text="inv.type_label"></span>
                            </p>
                            <p class="truncate text-xs text-gray-500" x-text="inv.client_name"></p>
                        </div>
                        <span class="flex-shrink-0 text-sm font-semibold text-gray-700"
                              x-text="'S/ ' + (Number(inv.total)||0).toFixed(2)"></span>
                    </button>
                </template>
            </div>
        </div>

    </div>
</div>
@endif

{{-- La lista lateral solo tiene sentido en la vista general de todos los
     comprobantes. Al entrar a EMITIR (Facturas / Boletas / Notas) sobra: el
     formulario es el trabajo y la busqueda vive en "Comprobantes emitidos"
     (separacion 2026-09-02). --}}
@if(empty($seccion ?? ''))
{{-- PANEL 2: LISTA --}}
<div id="inv-lista" class="flex flex-col border-r overflow-hidden flex-shrink-0"
     style="background:#fff; border-color:#e5e7eb; --list-width:340px; width:var(--list-width,340px);"
     :class="panel==='list'||!isMobile ? 'flex' : 'hidden'">

    {{-- Cabecera de la lista lateral. Aquí NO hay buscador ni descarga del
         registro: esta pantalla es para EMITIR. Buscar un comprobante pasado
         o sacar el registro del mes vive en "Comprobantes emitidos"
         (separación 2026-09-02). La lista queda como contexto: lo último
         emitido, para confirmar de un vistazo que salió. --}}
    <div id="inv-cab" class="px-4 py-3 border-b flex items-center gap-2" style="border-color:#e5e7eb;">
        <span class="flex-1 min-w-0 text-xs font-semibold uppercase tracking-wide text-gray-500">Últimos emitidos</span>
        <a href="{{ route('bixosales.facturas.consulta') }}"
           class="flex-shrink-0 text-xs font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
            Ver todos
        </a>
        <button @click="openNew(@js($seccion === 'nota' ? 'nota_credito' : ($seccion ?: null)))"
                class="inv-nuevo-desktop flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center hover:bg-indigo-700 transition-colors"
                aria-label="Nuevo comprobante">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>

    {{-- Lista --}}
    <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100">
        <template x-if="filtered.length === 0">
            <p class="text-center text-sm text-gray-400 py-10">Sin comprobantes</p>
        </template>
        <template x-for="inv in filtered" :key="inv.id">
            <div @click="select(inv)"
                 class="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors"
                 :class="selected && selected.id===inv.id ? 'bg-indigo-50 border-l-2 border-indigo-500' : ''">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="inv.numero || ('Sin número #'+inv.id)"></p>
                        <p class="text-xs text-gray-500 truncate" x-text="inv.client_name"></p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="text-xs font-semibold text-gray-800" x-text="'S/ '+inv.total.toFixed(2)"></span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium"
                              :class="{
                                'bg-yellow-100 text-yellow-700': inv.status==='draft',
                                'bg-blue-100 text-blue-700': inv.status==='issued',
                                'bg-green-100 text-green-700': inv.status==='sent',
                                'bg-red-100 text-red-700': inv.status==='cancelled'
                              }"
                              x-text="inv.status_label"></span>
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-xs text-gray-400"
                          :class="{
                            'text-purple-600 font-medium': inv.type==='boleta',
                            'text-indigo-600 font-medium': inv.type==='factura'
                          }"
                          x-text="inv.type_label"></span>
                    <span class="text-xs text-gray-400" x-text="inv.issue_date || ''"></span>
                    {{-- El estado ante SUNAT con su color y en cristiano: un
                         "error" pintado de verde decia justo lo contrario de
                         la verdad, y "accepted" es jerga del proveedor. --}}
                    <template x-if="inv.sunat_status">
                        <span class="text-[10px] px-1 py-0.5 rounded border"
                              :class="{
                                'bg-green-50 text-green-600 border-green-200': inv.sunat_status==='accepted' && !inv.sunat_obs,
                                'bg-amber-50 text-amber-700 border-amber-200': inv.sunat_status==='pending' || (inv.sunat_status==='accepted' && inv.sunat_obs),
                                'bg-red-50 text-red-600 border-red-200': ['error','rejected'].includes(inv.sunat_status),
                                'bg-gray-50 text-gray-500 border-gray-200': !['accepted','pending','error','rejected'].includes(inv.sunat_status)
                              }"
                              x-text="inv.sunat_status==='accepted' && inv.sunat_obs
                                        ? 'Aceptada con observación'
                                        : ({accepted:'Aceptada SUNAT', pending:'En SUNAT', error:'Error de envío', rejected:'Rechazada SUNAT'})[inv.sunat_status] || inv.sunat_status"></span>
                    </template>
                </div>
            </div>
        </template>
    </div>
</div>

@endif

{{-- PANEL 3: DETALLE --}}
<div class="flex flex-col flex-1 overflow-hidden bg-white"
     :class="panel==='detail'||!isMobile ? 'flex' : 'hidden'">

    @if(($porVencer['cuantos'] ?? 0) > 0)
    {{-- El plazo de SUNAT avisa SIEMPRE, se este emitiendo o consultando:
         vivia dentro del panel de lista y al ocultarlo se perdia el aviso. --}}
    <div class="px-4 py-2.5 bg-amber-50 border-b border-amber-200 text-xs text-amber-800 leading-snug flex-shrink-0">
        <strong>{{ $porVencer['cuantos'] }} comprobante(s) sin aceptar por SUNAT</strong>
        @if(($porVencer['dias'] ?? null) !== null)
            — al más urgente le {{ $porVencer['dias'] == 1 ? 'queda 1 día' : 'quedan '.$porVencer['dias'].' días' }}@if($porVencer['dias'] === 0) <strong> (vence HOY)</strong>@endif.
        @endif
        Se reintentan solos cada hora; pasado el plazo ya no se pueden enviar.
        <a href="{{ route('bixosales.facturas.consulta') }}?estado=error" class="font-semibold underline">Ver cuáles</a>
    </div>
    @endif

    {{-- Back mobile --}}
    <div class="px-4 py-2 border-b md:hidden" style="border-color:#e5e7eb;">
        <button @click="panel='list'" class="text-sm text-indigo-600 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
        </button>
    </div>

    {{-- Estado vacío --}}
    <template x-if="!selected && !creating">
        <div class="flex flex-col items-center justify-center flex-1 text-gray-400">
            <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
            </svg>
            <p class="text-sm">Selecciona un comprobante</p>
            <button @click="openNew()" class="mt-3 text-sm text-indigo-600 hover:underline">
                + Nuevo comprobante
            </button>
        </div>
    </template>

    {{-- FORMULARIO NUEVO --}}
    <template x-if="creating">
        <div class="flex flex-col flex-1 overflow-hidden">
            <div class="px-4 py-2.5 border-b bg-white flex items-center gap-2" style="border-color:#e5e7eb;">
                {{-- El titulo dice que se esta emitiendo. Entrando por
                     "Facturas", poner "Nuevo Comprobante" y volver a preguntar
                     el tipo repetia una decision ya tomada. --}}
                {{-- Volver vive ARRIBA, junto al titulo, como en cualquier
                     pantalla de movil: abajo competia por el pulgar con
                     "Continuar" y "Emitir", que son acciones sin retorno. --}}
                <button @click="pasoAnterior()" x-show="isMobile && paso > 1" x-cloak type="button"
                        class="md:hidden -ml-1 p-1.5 text-gray-500 hover:text-gray-900 flex-shrink-0"
                        aria-label="Volver al paso anterior">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                {{-- El titulo va AQUI, en la barra de arriba: es la franja que
                     encabeza la pantalla y estaba vacia, mientras el titulo
                     ocupaba alto dentro de la tarjeta de datos. --}}
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="inv-icono-sec">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 leading-tight" x-text="tituloFormulario()"></h2>
                        <p class="text-xs text-gray-500 leading-tight inv-subtitulo" x-text="subtituloFormulario()"></p>
                    </div>
                </div>
                <div class="ml-auto flex items-center gap-2">
                <button @click="vistaPrevia()" type="button"
                        class="min-h-[38px] px-3 rounded-lg border text-xs font-semibold text-gray-600 hover:bg-gray-50 hidden sm:inline-flex items-center"
                        style="border-color:#e5e7eb;">Vista previa</button>
                @if($lectorActivo ?? false)
                {{-- Muchos negocios hacen el comprobante en papel y luego lo
                     registran aqui: esto evita teclearlo dos veces. --}}
                <button @click="lector.abrir()" type="button"
                        class="min-h-[38px] px-3 rounded-lg border border-indigo-200 bg-indigo-50
                               text-xs font-semibold text-indigo-700 hover:bg-indigo-100 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                    </svg>
                    <span class="hidden sm:inline">Leer comprobante</span>
                    <span class="sm:hidden">Escanear</span>
                </button>
                @endif
                <button @click="cerrarFormulario()" class="text-gray-400 hover:text-gray-600 pl-1" aria-label="Cerrar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                </div>
            </div>
            {{-- Guia de pasos (solo movil). Deja ver donde esta y volver a lo
                 ya hecho sin perder lo escrito. --}}
            {{-- Los tres pasos se reparten POR CONTENIDO, no a tercios iguales:
                 con flex-1 "Cliente" recibia el mismo ancho que "Productos" y
                 sobraba hueco a su derecha, asi que la fila entera se veia
                 corrida a la izquierda en vez de ocupar el ancho. --}}
            <div class="md:hidden flex items-center justify-between gap-1 px-4 py-2 border-b bg-white" style="border-color:#e5e7eb;">
                <template x-for="n in 3" :key="n">
                    <button type="button" @click="irAlPaso(n)"
                            class="flex items-center gap-1.5 min-w-0"
                            :class="n < paso ? 'cursor-pointer' : 'cursor-default'">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold flex-shrink-0 transition-colors"
                              :class="n < paso  ? 'bg-emerald-500 text-white'
                                    : n === paso ? 'bg-indigo-600 text-white'
                                                 : 'bg-gray-200 text-gray-500'">
                            <span x-show="n >= paso" x-text="n"></span>
                            <svg x-show="n < paso" x-cloak class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                            </svg>
                        </span>
                        <span class="text-[11px] font-semibold whitespace-nowrap"
                              :class="n === paso ? 'text-gray-900' : 'text-gray-400'"
                              x-text="({1:'Cliente', 2:'Productos', 3:'Cobro'})[n]"></span>
                    </button>
                </template>
            </div>

@include('invoices._formulario')

            {{-- El pie fija lo que de verdad se hace aqui. En movil viaja
                 pegado abajo con el total, para no subir a comprobarlo. --}}
            {{-- En movil el pie va FIJO al borde inferior: llevaba el total y
                 Emitir, y al ir con el scroll obligaba a bajar hasta el final
                 para ver cuanto se cobra. El respiro de abajo respeta la barra
                 del navegador (safe-area) en iPhone. --}}
            <div id="inv-form-pie" class="px-4 py-2.5 border-t bg-white flex items-center gap-2
                                          sticky bottom-0 z-20 md:static"
                 style="border-color:#e5e7eb; padding-bottom:calc(0.625rem + env(safe-area-inset-bottom));">
                <div class="md:hidden mr-auto min-w-0">
                    <p class="text-xs text-gray-400 leading-none">Total</p>
                    <p class="text-base font-bold text-gray-900 leading-tight" x-text="money(calcTotal())"></p>
                </div>

                <button @click="cerrarFormulario()" type="button"
                        class="btn-secondary text-sm hidden md:inline-flex">Cancelar</button>
                <button @click="guardarBorrador()" type="button" :disabled="saving"
                        class="btn-secondary text-sm hidden md:inline-flex"
                        title="Se guarda sin declararlo a SUNAT">Guardar borrador</button>

                {{-- En movil solo en el ultimo paso, para no competir con
                     "Continuar": sin esto, quien tenia que atender otra cosa a
                     media factura solo podia emitirla a medias o perderla. --}}
                <button @click="guardarBorrador()" x-show="isMobile && paso === 3" x-cloak
                        type="button" :disabled="saving"
                        class="btn-secondary text-sm px-3">Borrador</button>

                {{-- En movil el boton principal AVANZA hasta el ultimo paso.
                     Tener "Emitir" visible desde el primero invitaba a emitir
                     a medio llenar y a comerse el correlativo. --}}
                <button x-show="isMobile && paso < 3" x-cloak type="button"
                        @click="puedeAvanzar() ? siguientePaso() : bxAviso(avisoPaso(), 'info')"
                        class="btn-primary text-sm"
                        :class="puedeAvanzar() ? '' : 'opacity-60'">Continuar</button>

                {{-- Emitir NO declara de golpe: primero se ve el comprobante
                     tal como quedara y se confirma. Un comprobante emitido es
                     irreversible (solo se corrige con nota de credito), asi
                     que el ultimo vistazo va ANTES de crearlo: si algo esta
                     mal se corrige sin gastar correlativo. --}}
                <button x-show="!isMobile || paso === 3" type="button"
                        @click="confirmarEmision()" :disabled="saving"
                        class="btn-primary text-sm"
                        x-text="saving ? 'Emitiendo...' : etiquetaEmitir()"></button>
            </div>
        </div>
    </template>

    {{-- PANEL DETALLE --}}
    <template x-if="selected && !creating">
        <div class="flex flex-col flex-1 overflow-hidden">

            {{-- Header --}}
            <div class="px-5 py-3 border-b flex items-center justify-between" style="border-color:#e5e7eb;">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800" x-text="selected.numero || 'Comprobante #'+selected.id"></h2>
                    <p class="text-xs text-gray-500" x-text="selected.type_label + ' · ' + selected.client_name"></p>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Descargar e Imprimir es LO QUE SE HACE con un comprobante
                         ya emitido; eran dos iconos grises de 16px y no se veian.
                         Ahora llevan texto y peso visual. --}}
                    <a :href="`{{ $invoicesApiBase }}/`+selected.id+`/pdf?descargar=1`"
                       class="inv-accion inv-accion-fuerte" title="Descargar el PDF del comprobante">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/>
                        </svg>
                        <span>Descargar</span>
                    </a>
                    <a x-show="selected.client_phone && selected.status !== 'draft'" :href="waComprobante()"
                       target="_blank" rel="noopener"
                       class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded transition-colors"
                       title="Enviar por WhatsApp (adjunta el PDF descargado)">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2z"/>
                        </svg>
                    </a>
                    {{-- XML firmado y CDR: los ficheros que valen ante SUNAT y que
                         el comprador tiene derecho a recibir. Solo si SUNAT acepto. --}}
                    <a x-show="selected.sunat_status === 'accepted'" :href="`{{ $invoicesApiBase }}/`+selected.id+`/xml`"
                       class="px-1.5 py-1 text-[10px] font-bold tracking-wide text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                       title="Descargar XML firmado">XML</a>
                    <a x-show="selected.sunat_status === 'accepted'" :href="`{{ $invoicesApiBase }}/`+selected.id+`/cdr`"
                       class="px-1.5 py-1 text-[10px] font-bold tracking-wide text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                       title="Descargar constancia de SUNAT (CDR)">CDR</a>
                    <a :href="`{{ $invoicesApiBase }}/`+selected.id+`/pdf`"
                       target="_blank" rel="noopener"
                       class="inv-accion" title="Abrir para imprimir">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Imprimir</span>
                    </a>
                    <button @click="deleteInvoice()"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">

                {{-- Estado y badges --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs px-2 py-1 rounded-full font-medium"
                          :class="{
                            'bg-yellow-100 text-yellow-700': selected.status==='draft',
                            'bg-blue-100 text-blue-700': selected.status==='issued',
                            'bg-green-100 text-green-700': selected.status==='sent',
                            'bg-red-100 text-red-700': selected.status==='cancelled'
                          }"
                          x-text="selected.status_label"></span>
                    <template x-if="selected.sunat_status">
                        <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-200"
                              x-text="'SUNAT: '+selected.sunat_status"></span>
                    </template>
                    <div class="ml-auto flex items-center gap-1">
                        <label class="text-xs text-gray-500">Estado:</label>
                        <select x-model="editStatus" @change="updateStatus()" class="text-xs border border-gray-200 rounded px-2 py-1">
                            <option value="draft">Borrador</option>
                            <option value="issued">Emitida</option>
                            <option value="sent">Enviada</option>
                            <option value="cancelled">Anulada</option>
                        </select>
                    </div>
                </div>

                {{-- Datos del comprobante --}}
                {{-- El numero ya esta en el titulo de arriba Y dentro de la
                     representacion impresa; el tipo tambien va junto al titulo.
                     Repetirlos aqui solo gastaba sitio: queda lo que no se ve en
                     ningun otro lado de esta pantalla. --}}
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-gray-400">Fecha emisión</p>
                        <p class="text-gray-700" x-text="selected.issue_date || '—'"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Vencimiento</p>
                        <p class="text-gray-700" x-text="selected.due_date || '—'"></p>
                    </div>
                </div>

                {{-- REPRESENTACION IMPRESA. Antes aqui habia una ficha de datos
                     (emisor, receptor, tabla simple, totales) que NO se parecia al
                     comprobante que el cliente acaba recibiendo: sin logo, sin QR, sin
                     importe en letras y sin el recuadro fiscal. Quien emitia no podia
                     revisar en pantalla lo que iba a imprimir. Ahora se muestra el
                     MISMO documento del PDF, con la plantilla que el negocio tenga
                     elegida, para que lo que se ve sea lo que se entrega. --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <iframe :src="`{{ $invoicesApiBase }}/`+selected.id+`/pdf?vista=incrustada`"
                            class="w-full bg-white" style="height:1120px;border:0"
                            title="Representación impresa del comprobante"></iframe>
                </div>

                {{-- Info pago --}}
                <template x-if="selected.payment_method || selected.paid_at">
                    <div class="text-sm grid grid-cols-2 gap-2">
                        <template x-if="selected.payment_method">
                            <div>
                                <p class="text-xs text-gray-400">Método de pago</p>
                                <p class="text-gray-700" x-text="selected.payment_method"></p>
                            </div>
                        </template>
                        <template x-if="selected.paid_at">
                            <div>
                                <p class="text-xs text-gray-400">Fecha de pago</p>
                                <p class="text-gray-700" x-text="selected.paid_at"></p>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Notas --}}
                <template x-if="selected.notes">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Observaciones</p>
                        <p class="text-sm text-gray-600 bg-gray-50 border rounded p-2" x-text="selected.notes"></p>
                    </div>
                </template>

                {{-- SUNAT info --}}
                <template x-if="selected.sunat_status || selected.sunat_error">
                    <div class="border rounded-lg p-3 bg-yellow-50 text-xs">
                        <p class="font-semibold text-yellow-700 mb-1">SUNAT / OSE</p>
                        {{-- El estado en cristiano: "pending" es jerga del
                             proveedor y no le dice nada a quien factura. --}}
                        <template x-if="selected.sunat_status">
                            <p class="text-yellow-700">Estado:
                                <span class="font-medium"
                                      x-text="({accepted:'Aceptado por SUNAT', pending:'Enviando a SUNAT...', error:'Error de envío', rejected:'Rechazado por SUNAT'})[selected.sunat_status] || selected.sunat_status"></span>
                            </p>
                        </template>
                        <template x-if="selected.sunat_error">
                            <p class="text-red-600 mt-1" x-text="selected.sunat_error"></p>
                        </template>
                        {{-- Decia "proximamente disponible" con la integracion
                             llevando meses en produccion: se contradecia con el
                             estado real que se muestra encima. --}}
                        <template x-if="selected.sunat_status === 'pending'">
                            <p class="text-yellow-600 mt-2 italic">Se está declarando en segundo plano. Si falla, se reintenta solo cada hora.</p>
                        </template>
                    </div>
                </template>

                {{-- Bloque SUNAT --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 border-b border-gray-200">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span class="text-xs font-semibold text-gray-700">SUNAT</span>
                        </div>
                        <template x-if="selected.sunat_status">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                  :class="{
                                    'bg-green-100 text-green-700': selected.sunat_status==='accepted',
                                    'bg-red-100 text-red-700':    selected.sunat_status==='rejected',
                                    'bg-yellow-100 text-yellow-700': selected.sunat_status==='pending' || selected.sunat_status==='error',
                                  }"
                                  x-text="{accepted:'Aceptado',rejected:'Rechazado',pending:'Pendiente',error:'Error'}[selected.sunat_status] ?? selected.sunat_status">
                            </span>
                        </template>
                    </div>
                    <div class="px-3 py-3 space-y-2">
                        <template x-if="selected.sunat_status === 'accepted'">
                            <p class="text-xs text-green-700">✓ Comprobante aceptado y registrado en SUNAT.</p>
                        </template>
                        <template x-if="selected.sunat_error">
                            <p class="text-xs text-red-600 break-words" x-text="selected.sunat_error"></p>
                        </template>
                        {{-- Un comprobante aceptado ya no se borra: se corrige.
                             Estas son las dos vias legales, y aparecen justo
                             donde el usuario acaba de leer que SUNAT lo acepto. --}}
                        <template x-if="selected.sunat_status === 'accepted' && selected.baja_estado !== 'accepted' && !['nota_credito','nota_debito'].includes(selected.type)">
                            <div class="pt-2 border-t border-gray-100 space-y-2">
                                <p class="text-xs text-gray-500 leading-snug">
                                    Ya no se puede borrar: existe en SUNAT. Para dejarlo sin efecto,
                                    emite una nota de crédito o comunica la baja.
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="abrirNota('nota_credito')"
                                            class="py-2 px-2 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition">
                                        Nota de crédito
                                    </button>
                                    <button @click="abrirNota('nota_debito')"
                                            class="py-2 px-2 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition">
                                        Nota de débito
                                    </button>
                                </div>
                                {{-- La factura va por comunicacion de baja; la boleta, por
                                     resumen diario. El servidor elige el camino. --}}
                                <button x-show="['factura','boleta'].includes(selected.type)" @click="darDeBaja()" :disabled="bajaEnCurso"
                                        class="w-full py-2 px-3 rounded-lg text-xs font-semibold text-red-600 border border-red-200 hover:bg-red-50 transition disabled:opacity-60">
                                    <span x-text="bajaEnCurso ? 'Enviando a SUNAT...' : (selected.type === 'boleta' ? 'Anular boleta (resumen diario)' : 'Comunicar baja a SUNAT')"></span>
                                </button>
                            </div>
                        </template>

                        <template x-if="selected.baja_estado">
                            <p class="text-xs pt-2 border-t border-gray-100"
                               :class="selected.baja_estado === 'accepted' ? 'text-red-600 font-semibold' : 'text-gray-500'"
                               x-text="{pending:'Baja en tramite ante SUNAT (se consulta cada hora)...',accepted:'Dada de baja ante SUNAT.',rejected:'SUNAT no acepto la baja: ' + (selected.baja_error || '')}[selected.baja_estado] ?? ''"></p>
                        </template>

                        <template x-if="selected.sunat_status !== 'accepted' && selected.status !== 'cancelled'">
                            <button @click="enviarSunat()"
                                    :disabled="sendingSunat"
                                    class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-semibold transition
                                           bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-60 disabled:cursor-not-allowed">
                                <svg x-show="sendingSunat" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span x-text="sendingSunat ? 'Enviando...' : (selected.sunat_status ? 'Reintentar envío' : 'Enviar a SUNAT')"></span>
                            </button>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </template>

</div>

{{-- Selector del catalogo: cabecera de busqueda (nombre/SKU, marca,
     categoria), lista acotada y pie con el conteo. CSS propio: las clases
     Tailwind arbitrarias del modal anterior no estaban compiladas y la lista
     se derramaba sobre toda la pagina. --}}
<style>
    .fac-pk-fondo { position: fixed; inset: 0; z-index: 70; background: rgba(15,23,42,.55); }
    /* CENTRADO POR REJILLA, NO POR TRANSFORM.
       Centrar con `left:50% + translate(-50%,-50%)` depende de que el bloque
       contenedor sea la ventana. Dentro del area de trabajo del panel (que
       tiene su propio scroll y un padding del ancho de la barra lateral) ese
       50% se medía contra otra caja: el modal salía corrido a la izquierda,
       fuera de pantalla, y se comía el buscador, los nombres y los SKU.
       Con `inset:0` + grid el modal ocupa la ventana entera y el hijo se
       centra solo, que es como ya funcionaban los demas modales. */
    .fac-pk-marco { position: fixed; inset: 0; z-index: 71; display: grid; place-items: center;
                    padding: 16px; pointer-events: none; }
    .fac-pk { width: min(1040px, 100%); max-height: 86vh; background: #fff; border-radius: 14px;
              box-shadow: 0 24px 60px rgba(0,0,0,.28); display: flex; flex-direction: column;
              overflow: hidden; pointer-events: auto; }
    .fac-pk-cab { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; }
    .fac-pk-cab h3 { margin: 0; font-size: 15px; font-weight: 800; color: #111827; }
    .fac-pk-cab .cerrar { margin-left: auto; width: 36px; height: 36px; border: 0; background: transparent; color: #6b7280; font-size: 22px; cursor: pointer; border-radius: 8px; }
    .fac-pk-cab .cerrar:hover { background: #f3f4f6; }
    .fac-pk-filtros { display: grid; grid-template-columns: 1fr 180px 180px; gap: 8px; padding: 10px 16px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; }
    .fac-pk-filtros input, .fac-pk-filtros select { height: 38px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; background: #fff; min-width: 0; }
    .fac-pk-filtros input:focus, .fac-pk-filtros select:focus { outline: 2px solid #6366f1; outline-offset: -1px; }
    .fac-pk-lista { flex: 1 1 auto; min-height: 0; overflow-y: auto; }
    /* Rejilla del selector: una columna por dato, cabecera fija al desplazar. */
    .fac-pk-tabla { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
    .fac-pk-tabla thead th { position: sticky; top: 0; z-index: 1; background: #f9fafb; text-align: left;
                             font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase;
                             letter-spacing: .04em; padding: 7px 10px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
    .fac-pk-tabla tbody tr { cursor: pointer; border-bottom: 1px solid #f3f4f6; }
    .fac-pk-tabla tbody tr:hover { background: #f9fafb; }
    .fac-pk-tabla tbody tr.is-on { background: #eef2ff; }
    .fac-pk-tabla tbody tr:focus-visible { outline: 2px solid #6366f1; outline-offset: -2px; }
    .fac-pk-tabla td { padding: 6px 10px; vertical-align: middle;
                       white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    /* El nombre NO se recorta a una linea: lo que distingue "VULCANIZADO 2x16"
       de "VULCANIZADO 2x18" esta al final, justo donde caian los puntos
       suspensivos. Con dos lineas cabe entero y se puede elegir sin abrir
       nada. Las demas columnas siguen en una linea: son cortas. */
    .fac-pk-tabla .c-nom { white-space: normal; line-height: 1.3;
                           display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    /* Anchos fijos: el nombre se queda con lo que sobra. Asi el precio cae
       siempre en la misma vertical y se comparan dos filas de un vistazo. */
    .fac-pk-tabla .c-chk { width: 34px; text-align: center; padding-left: 12px; padding-right: 0; }
    .fac-pk-tabla .c-nom { color: #111827; font-weight: 600; }
    .fac-pk-tabla .c-sku { width: 96px; color: #6b7280; font-size: 12px; }
    .fac-pk-tabla .c-mar { width: 88px; }
    .fac-pk-tabla .c-cat { width: 150px; color: #6b7280; font-size: 12px; }
    .fac-pk-tabla .c-pre { width: 96px; text-align: right; font-weight: 700; color: #111827; padding-right: 14px; }
    .fac-pk-check { width: 18px; height: 18px; border: 2px solid #d1d5db; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; color: transparent; font-size: 12px; font-weight: 900; }
    .fac-pk-tabla tr.is-on .fac-pk-check { background: #4f46e5; border-color: #4f46e5; color: #fff; }
    .fac-pk-marca { font-size: 11px; color: #4b5563; background: #f3f4f6; border-radius: 999px; padding: 2px 8px; white-space: nowrap; }
    .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
    .fac-pk-vacio { padding: 40px 16px; text-align: center; color: #6b7280; font-size: 13px; }
    .fac-pk-pie { padding: 10px 16px; border-top: 1px solid #e5e7eb; background: #f9fafb; display: flex; align-items: center; gap: 10px; }
    .fac-pk-pie .conteo { font-size: 12px; color: #6b7280; }
    .fac-pk-pie .sel { font-size: 13px; color: #111827; font-weight: 600; }
    .fac-pk-btn { height: 40px; padding: 0 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: #374151; }
    .fac-pk-btn.pri { background: #4f46e5; border-color: #4f46e5; color: #fff; }
    .fac-pk-btn:disabled { opacity: .5; cursor: default; }
    /* Estrecho: caben el producto y el precio. SKU, marca y categoria se
       esconden por orden de utilidad, no de golpe. */
    @media (max-width: 900px) { .fac-pk-tabla .c-cat { display: none; } }
    @media (max-width: 720px) { .fac-pk-tabla .c-sku { display: none; } }
    @media (max-width: 640px) {
        .fac-pk-filtros { grid-template-columns: 1fr 1fr; }
        .fac-pk-filtros input { grid-column: 1 / -1; }
        .fac-pk-tabla .c-mar { display: none; }
    }
    /* Muy estrecho (390 px): a dos columnas "Todas las categorias" se cortaba
       a media palabra y no se sabia que filtro era. Cada uno a su fila. */
    @media (max-width: 420px) {
        .fac-pk-filtros { grid-template-columns: 1fr; }
        .fac-pk-filtros select { grid-column: 1 / -1; }
    }
</style>
<div x-show="productPickerOpen" x-cloak @keydown.escape.window="productPickerOpen = false"
     role="dialog" aria-modal="true" aria-labelledby="invoice-product-picker-title">
    <div class="fac-pk-fondo" @click="productPickerOpen = false"></div>
    <div class="fac-pk-marco">
    <div class="fac-pk">
        <div class="fac-pk-cab">
            <h3 id="invoice-product-picker-title">Seleccionar del catálogo</h3>
            <button type="button" class="cerrar" @click="productPickerOpen = false" aria-label="Cerrar">&times;</button>
        </div>

        {{-- Cabecera de busqueda: lo que se teclea, la marca y la categoria
             se combinan (se intersecan). Enter marca el primer resultado. --}}
        <div class="fac-pk-filtros">
            <input id="invoice-product-picker-search" x-ref="productPickerSearch" x-model="productPickerSearch"
                   type="search" autocomplete="off" placeholder="Buscar por nombre o SKU..."
                   @keydown.enter.prevent="pickerProducts[0] && togglePickerProduct(pickerProducts[0].key)">
            <select x-model="pickerMarca" @change="alCambiarMarca()" aria-label="Marca">
                <option value="">Todas las marcas</option>
                <template x-for="m in pickerMarcas" :key="m.valor">
                    <option :value="m.valor" x-text="m.etiqueta"></option>
                </template>
            </select>
            <select x-model="pickerCategoria" @change="alCambiarCategoria()" aria-label="Categoría">
                <option value="">Todas las categorías</option>
                <template x-for="c in pickerCategorias" :key="c.valor">
                    <option :value="c.valor" x-text="c.etiqueta"></option>
                </template>
            </select>
        </div>

        {{-- COLUMNAS, no fichas. El SKU y la categoria iban en letra pequena
             bajo el nombre: para comparar dos productos habia que leer en
             zigzag. En columnas cada dato cae siempre en el mismo sitio y la
             vista baja en vertical, como en una hoja de calculo. --}}
        <div class="fac-pk-lista">
            <template x-if="pickerProducts.length === 0">
                <div class="fac-pk-vacio">No hay productos con ese filtro. Prueba otra palabra o quita la marca o la categoría.</div>
            </template>
            <table class="fac-pk-tabla" x-show="pickerProducts.length" role="listbox">
                <thead>
                    <tr>
                        <th class="c-chk"><span class="sr-only">Elegir</span></th>
                        <th class="c-nom">Producto</th>
                        <th class="c-sku" x-show="columnaUtil('sku')">SKU</th>
                        <th class="c-mar" x-show="columnaUtil('brand')">Marca</th>
                        <th class="c-cat" x-show="columnaUtil('category')">Categoría</th>
                        <th class="c-pre">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="product in pickerProducts" :key="product.key">
                        <tr :class="isPickerProductSelected(product.key) && 'is-on'"
                            @click="togglePickerProduct(product.key)"
                            role="option" :aria-selected="isPickerProductSelected(product.key)" tabindex="0"
                            @keydown.enter.prevent="togglePickerProduct(product.key)"
                            @keydown.space.prevent="togglePickerProduct(product.key)">
                            <td class="c-chk"><span class="fac-pk-check">&#10003;</span></td>
                            <td class="c-nom" x-text="product.name" :title="product.name"></td>
                            <td class="c-sku" x-show="columnaUtil('sku')" x-text="product.sku || '—'"></td>
                            <td class="c-mar" x-show="columnaUtil('brand')"><span class="fac-pk-marca" x-show="product.brand" x-text="product.brand"></span></td>
                            <td class="c-cat" x-show="columnaUtil('category')" x-text="product.category || '—'" :title="product.category"></td>
                            <td class="c-pre" x-text="money(product.price)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="fac-pk-pie">
            <span class="conteo" x-text="pickerProducts.length + (pickerProducts.length === 1 ? ' producto' : ' productos')"></span>
            <span class="sel" x-show="productPickerSelected.length" x-text="productPickerSelected.length + (productPickerSelected.length === 1 ? ' seleccionado' : ' seleccionados')"></span>
            <span style="flex:1"></span>
            <button type="button" class="fac-pk-btn" @click="productPickerOpen = false">Cancelar</button>
            <button type="button" class="fac-pk-btn pri" @click="applyPickerProducts()" :disabled="productPickerSelected.length === 0">Agregar al comprobante</button>
        </div>
    </div>
    </div>
</div>


{{-- ══ Confirmar antes de emitir ═════════════════════════════════════════
     El comprobante se ve aqui tal como saldra impreso, con el mismo calculo
     que usa la emision. Hasta que no se confirma NO existe: ni fila, ni
     correlativo, ni envio. Corregir no cuesta nada; una factura mal emitida
     solo se arregla con nota de credito. --}}
<div x-show="confirmarAbierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="confirmarAbierto = false">
    <div class="absolute inset-0 bg-black/50" @click="confirmarAbierto = false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col"
         style="max-height:92vh">

        <div class="px-5 py-3.5 border-b flex items-center gap-3 flex-shrink-0" style="border-color:#e5e7eb">
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-gray-900">Revisa antes de emitir</h3>
                <p class="text-xs text-gray-500 leading-tight" x-text="resumenConfirmacion()"></p>
            </div>
            <button type="button" @click="confirmarAbierto = false"
                    class="ml-auto text-gray-400 hover:text-gray-600 flex-shrink-0" aria-label="Cerrar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Un comprobante fiscal es irreversible: se avisa antes, no despues. --}}
        <p class="px-5 py-2 text-[11px] leading-snug text-amber-800 bg-amber-50 border-b flex-shrink-0"
           style="border-color:#fde68a">
            Al confirmar se emite y se declara a SUNAT. Ya no se podra editar: una
            correccion exige nota de credito.
        </p>

        {{-- Lo que se va a emitir, en claro y ANTES de la previa: cada linea con
             su cantidad x precio y el total en grande. La previa es la
             representacion impresa; esto es para leerlo de un vistazo. --}}
        <div class="px-5 py-3 border-b bg-white flex-shrink-0" style="border-color:#e5e7eb">
            {{-- Fecha y direccion ANTES del detalle: la fecha de emision fija el
                 periodo tributario y la direccion es la que queda impresa en el
                 comprobante. Las dos dejan de poder cambiarse al confirmar. --}}
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 mb-2 pb-2 border-b text-xs" style="border-color:#f1f5f9">
                <div class="flex items-baseline gap-1.5">
                    <dt class="text-gray-400">Se emite el</dt>
                    <dd class="font-semibold text-gray-900" id="confirmar-fecha" x-text="fechaLarga(form.issue_date)"></dd>
                </div>
                <div class="flex items-baseline gap-1.5 min-w-0">
                    <dt class="text-gray-400 flex-shrink-0">Cliente</dt>
                    <dd class="font-semibold text-gray-900 truncate" x-text="form.client_name || '-'"></dd>
                </div>
                <div class="col-span-2 flex items-baseline gap-1.5 min-w-0">
                    <dt class="text-gray-400 flex-shrink-0">Direccion</dt>
                    <dd id="confirmar-direccion"
                        x-text="form.client_address || 'sin direccion'"
                        :class="form.client_address ? 'text-gray-700' : 'text-gray-400 italic'"></dd>
                </div>
            </dl>
            <table class="w-full text-xs" id="confirmar-resumen">
                <template x-for="(l, i) in lineasUtiles()" :key="i">
                    <tr>
                        <td class="py-0.5 pr-2 text-gray-800" x-text="l.description"></td>
                        <td class="py-0.5 px-2 text-right text-gray-500 whitespace-nowrap" x-text="Number(l.quantity) + ' × ' + money(l.unit_price)"></td>
                        <td class="py-0.5 pl-2 text-right font-semibold text-gray-900 whitespace-nowrap" x-text="money(importeLinea(l))"></td>
                    </tr>
                </template>
            </table>
            <div class="flex items-baseline justify-between mt-2 pt-2 border-t" style="border-color:#e5e7eb">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total a emitir</span>
                <strong class="text-2xl text-gray-900" id="confirmar-total" x-text="money(calcTotal())"></strong>
            </div>
        </div>

        <div class="flex-1 overflow-auto bg-gray-100 p-3 min-h-[240px]">
            <template x-if="confirmarCargando">
                <p class="text-center text-xs text-gray-500 py-10">Preparando la vista previa...</p>
            </template>
            <template x-if="!confirmarCargando && confirmarHtml">
                <iframe x-ref="marcoPrevia" class="w-full bg-white rounded-lg shadow-sm"
                        style="height:60vh;border:0" title="Vista previa del comprobante"></iframe>
            </template>
            <template x-if="!confirmarCargando && !confirmarHtml">
                <p class="text-center text-xs text-red-600 py-10"
                   x-text="confirmarError || 'No se pudo cargar la vista previa.'"></p>
            </template>
        </div>

        <div class="px-5 py-3 border-t flex items-center justify-end gap-2 flex-shrink-0"
             style="border-color:#e5e7eb">
            <button type="button" @click="confirmarAbierto = false" :disabled="saving"
                    class="min-h-[42px] px-4 rounded-lg border text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    style="border-color:#e5e7eb">Corregir</button>
            <button type="button" @click="emitirConfirmado()" :disabled="saving || confirmarCargando"
                    class="min-h-[42px] px-5 rounded-lg text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-2">
                <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span x-text="saving ? 'Emitiendo...' : 'Confirmar y enviar a SUNAT'"></span>
            </button>
        </div>
    </div>
</div>

{{-- ══ Emitir una nota sobre el comprobante ══════════════════════════════
     El motivo sale del catalogo oficial (09 para credito, 10 para debito):
     escribirlo a mano es lo que hace que SUNAT rechace la nota. --}}
<div x-show="notaAbierta" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0" style="background:rgba(15,23,42,.5)" @click="notaAbierta = false"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900"
                x-text="notaTipo === 'nota_debito' ? 'Nota de débito' : 'Nota de crédito'"></h3>
            <p class="text-xs text-gray-500 mt-0.5">
                Sobre <span class="font-semibold" x-text="selected?.numero"></span>
                · <span x-text="notaTipo === 'nota_debito' ? 'aumenta el importe' : 'anula o rebaja el importe'"></span>
            </p>
        </div>

        <div class="px-5 py-4 space-y-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Motivo</label>
                <select x-model="notaMotivo" class="w-full rounded-lg border-gray-300 text-sm">
                    <template x-for="(texto, codigo) in (notaTipo === 'nota_debito' ? motivosDebito : motivosCredito)" :key="codigo">
                        <option :value="codigo" x-text="codigo + ' — ' + texto"></option>
                    </template>
                </select>
                <p class="text-xs text-gray-400 mt-1">Los motivos son los del catálogo de SUNAT.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">
                    Detalle <span class="font-normal text-gray-400">(opcional)</span>
                </label>
                <input type="text" x-model="notaDetalle" maxlength="250"
                       class="w-full rounded-lg border-gray-300 text-sm"
                       placeholder="Se usa el texto del motivo si lo dejas vacío">
            </div>

            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                <p class="text-xs text-amber-800 leading-snug">
                    Se emitirá por el importe completo del comprobante
                    (<span x-text="selected ? 'S/ ' + (Number(selected.total)||0).toFixed(2) : ''"></span>) y se enviará a SUNAT.
                    Una nota emitida no se puede deshacer.
                </p>
            </div>
        </div>

        <div class="px-5 py-3 bg-gray-50 flex justify-end gap-2">
            <button @click="notaAbierta = false" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-600">
                Cancelar
            </button>
            <button @click="emitirNota()" :disabled="$data.notaEnCurso || !$data.notaMotivo"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60">
                <span x-text="$data.notaEnCurso ? 'Emitiendo...' : 'Emitir y enviar'"></span>
            </button>
        </div>
    </div>
</div>

@if($lectorActivo ?? false)
@include('invoices._lector')
@endif
</div>


<script>
// Rutas del lector de comprobantes. Se resuelven en Blade porque cambian
// segun la cara por la que se entro (Configuracion u Operacion).
/* Red de seguridad para `bxAviso`.
   Se define en `partials/avisos`, que va al FINAL del body y solo existe
   cuando Alpine monta ese componente. Si por lo que sea aun no esta (orden de
   carga, un fallo en otro componente), cualquier llamada revienta y se lleva
   por delante la accion que la hizo: pulsar "Emitir factura" no hacia nada,
   porque su primera validacion avisa con esta funcion. */
if (typeof window.bxAviso !== 'function') {
    window.bxAviso = function (msg, tipo) {
        // El evento lo recoge `partials/avisos` en cuanto monte; si no ha
        // montado, al menos no se pierde el mensaje ni se rompe quien avisa.
        window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg: msg, type: tipo || 'success' } }));
    };
}

const LECTOR_URL         = @json($lectorUrl ?? '');
const LECTOR_APLICAR_URL = @json($lectorAplicarUrl ?? '');
const CSRF               = @json(csrf_token());
// Buscador de clientes y consulta RUC/DNI. Estas dos constantes se perdieron
// en una edicion y el formulario decia "sin conexion" con el servidor sano:
// el fetch reventaba antes de salir del navegador. El test de la pantalla
// comprueba ahora que toda *_URL usada este definida.
const CLIENTES_URL       = @json($clientesUrl ?? '');
const RUC_URL            = @json($rucUrl ?? '');
const PREVIA_URL         = @json(($portalLayout ?? 'panel') === 'comercial' ? route('bixosales.facturas.previsualizar') : route('invoices.previsualizar'));
const NEGOCIO            = @json($project->name ?? '');

/* Alpine viene dentro del bundle (`app-*.js`), que carga como MODULO al
   principio del documento: arranca ANTES de que este <script> llegue a
   definir la funcion. Cuando Alpine encontraba `x-data="invoicesApp()"` la
   funcion todavia no existia, el objeto no se creaba y la plantilla reportaba
   "X is not defined" por CADA propiedad (notaEnCurso, notaDetalle, selected).
   Sintoma: la pantalla pintada pero muerta, con el boton Emitir sin responder.
   Registrarla en `alpine:init` es el orden que Alpine garantiza. */
(function registrar() {
    const alta = () => window.Alpine.data('invoicesApp', invoicesApp);
    // Si Alpine ya arranco, `alpine:init` no vuelve a dispararse: se registra
    // en el acto. Si aun no, se espera al evento.
    if (window.Alpine && window.Alpine.data) alta();
    else document.addEventListener('alpine:init', alta);
})();

function invoicesApp() {
    return {
        invoices: @json($invoices),
        catalogo: @json($catalogo ?? collect()),
        search: '',
        filterType: '',
        panel: 'list',
        isMobile: window.innerWidth < 768,
        selected: null,
        creating: false,
        sendingSunat: false,
        // Notas de credito/debito y baja: los motivos vienen del catalogo
        // oficial, no de una lista escrita a mano en la pantalla.
        motivosCredito: @json(\App\Support\Sunat\Catalogos::MOTIVOS_NOTA_CREDITO),
        motivosDebito:  @json(\App\Support\Sunat\Catalogos::MOTIVOS_NOTA_DEBITO),
        notaAbierta: false, notaTipo: 'nota_credito', notaMotivo: '01',
        notaDetalle: '', notaEnCurso: false, bajaEnCurso: false,
        buscandoRuc: false,
        editStatus: '',
        saving: false,
        saveError: '',
        // Plazo de credito pulsado (15/30/45/60) o null si la fecha se
        // escribio a mano. Solo sirve para pintar el boton activo.
        plazoElegido: null,
        // Confirmacion previa a emitir: el comprobante todavia no existe.
        confirmarAbierto: false,
        confirmarCargando: false,
        confirmarHtml: '',
        confirmarError: '',
        productPickerOpen: false,
        productPickerSearch: '',
        pickerMarca: '', pickerCategoria: '',
        productPickerSelected: [],
        huellaEmision: '',
        // Seccion "Notas de credito y debito": buscador del comprobante a corregir.
        notaBusca: '',

        /* PASOS EN MOVIL. Emitir en el mostrador es: a quien -> que -> cobrar.
           Con todo en una columna larga, el cajero subia y bajaba para
           comprobar el cliente mientras metia productos. En escritorio no
           aplica: ahi caben las dos columnas y se ve todo a la vez. */
        paso: 1,
        docPlegado: true,
        respaldoPendiente: null,
        get esMovil() { return this.isMobile; },
        get pasosActivos() { return true; },

        // Buscador de clientes del formulario.
        clienteQuery: '', clienteSugerencias: [], clienteActivo: -1,
        clienteEstado: '', clienteEstadoOk: false,
        editandoCliente: false,
        docAviso: '', docAvisoOk: false,
        // Con `?tipo=` el comprobante ya esta decidido por el menu.
        seccionFija: @js(($seccion ?? '') === 'factura' || ($seccion ?? '') === 'boleta'),
        seriesFactura: @js(array_values(array_filter([$serieFactura]))),
        seriesBoleta:  @js(array_values(array_filter([$serieBoleta]))),
        seriesNotaCredito: @js(array_values(array_filter([$serieNotaCredito]))),
        seriesNotaDebito:  @js(array_values(array_filter([$serieNotaDebito]))),
        fechaMinima: '', fechaMaxima: '',

        /* LECTOR DE COMPROBANTES. Vive dentro de `invoicesApp` para poder
           escribir en `form` sin puentes entre componentes: leer y emitir son
           la misma pantalla, no dos. */
        lector: {
            abierto: false, paso: 'subir', arrastrando: false, aplicando: false,
            archivo: null, previa: null, nombre: '', error: '',
            datos: null, avisos: [], revisar: [], duplicado: null, id: null,
            elegidos: {},
            padre: null,

            abrir() { this.reiniciar(); this.abierto = true; },

            reiniciar() {
                this.paso = 'subir'; this.archivo = null; this.nombre = '';
                this.error = ''; this.datos = null; this.avisos = []; this.revisar = [];
                this.duplicado = null; this.elegidos = {}; this.id = null;
                if (this.previa) { URL.revokeObjectURL(this.previa); this.previa = null; }
            },

            subtitulo() {
                if (this.paso === 'revisar')    return 'Revisa antes de continuar';
                if (this.paso === 'analizando') return 'Leyendo el documento';
                return 'Foto o PDF de la factura o boleta';
            },

            elegir(archivo) {
                if (!archivo) return;
                // El limite del servidor es 12 MB: avisar aqui evita una subida
                // larga desde el celular que acaba rechazada.
                if (archivo.size > 12 * 1024 * 1024) {
                    this.error = 'El archivo pesa demasiado. Haz la foto con menos resolucion.';
                    this.paso  = 'error';
                    return;
                }
                this.archivo = archivo;
                this.nombre  = archivo.name;
                if (this.previa) URL.revokeObjectURL(this.previa);
                this.previa = archivo.type.startsWith('image/') ? URL.createObjectURL(archivo) : null;
                this.paso   = 'previa';
            },

            async analizar() {
                if (!this.archivo) return;
                this.paso = 'analizando';

                const cuerpo = new FormData();
                cuerpo.append('archivo', this.archivo);

                try {
                    const res = await fetch(LECTOR_URL, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                        body: cuerpo,
                    });
                    const data = await res.json().catch(() => ({}));

                    if (!res.ok || !data.ok) {
                        this.error = data.mensaje
                            || 'No pudimos leer el comprobante. Prueba con otra foto mas nitida.';
                        this.paso = 'error';
                        return;
                    }

                    this.datos     = data.datos;
                    this.avisos    = data.avisos || [];
                    this.revisar   = data.revisar || [];
                    this.duplicado = data.duplicado || null;
                    this.id        = data.lectura;

                    // Lo que el emparejador dio por seguro llega ya elegido.
                    this.elegidos = {};
                    this.datos.items.forEach((it, i) => {
                        if (it.product_id) {
                            this.elegidos[i] = { id: it.product_id, nombre: it.product_nombre || it.descripcion };
                        }
                    });

                    this.paso = 'revisar';
                } catch (e) {
                    this.error = 'No pudimos conectar para analizar el comprobante. Intentalo de nuevo.';
                    this.paso  = 'error';
                }
            },

            elegido(i) { return this.elegidos[i] || null; },
            usar(i, s) { this.elegidos[i] = { id: s.id, nombre: s.nombre }; },
            quitar(i)  { delete this.elegidos[i]; },

            numeroDoc() {
                const s = this.datos && this.datos.serie, n = this.datos && this.datos.numero;
                return s && n ? (s + '-' + n) : (s || n || '');
            },

            moneda(v) {
                if (v === null || v === undefined) return '\u2014';
                const simbolo = (this.datos && this.datos.moneda === 'USD') ? '$' : 'S/';
                return simbolo + ' ' + Number(v).toFixed(2);
            },

            /* Traduce la lectura al formulario. La conversion la hace el
               servidor: si cambian los campos del comprobante se tocan alli y
               no en cada pantalla que lea. */
            async aplicar() {
                if (this.aplicando) return;
                this.aplicando = true;

                const productos = {};
                Object.entries(this.elegidos).forEach(([i, p]) => {
                    productos[i] = { product_id: p.id, descripcion: p.nombre };
                });

                try {
                    const res = await fetch(LECTOR_APLICAR_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ lectura: this.id, productos: productos }),
                    });
                    const data = await res.json().catch(() => ({}));

                    if (!res.ok || !data.ok) {
                        this.error = data.mensaje || 'No pudimos cargar los datos en el formulario.';
                        this.paso  = 'error';
                        return;
                    }

                    // A partir de aqui manda el formulario de siempre: el lector
                    // no emite nada, solo deja los campos puestos.
                    this.padre.aplicarLectura(data.form);
                    this.abierto = false;
                } catch (e) {
                    this.error = 'No pudimos cargar los datos. Intentalo de nuevo.';
                    this.paso  = 'error';
                } finally {
                    this.aplicando = false;
                }
            },
        },

        form: {
            type: 'boleta', serie: '{{ $serieBoleta }}', correlativo: '', issue_date: '', due_date: '',
            client_name: '', client_phone: '', client_email: '',
            client_doc_type: '', client_doc_number: '', client_address: '',
            payment_method: '', payment_condition: 'contado', currency: 'PEN', notes: '',
            igv_included: true,
            items: [1,2,3,4,5].map(function () { return { description: '', unit: 'NIU', quantity: 1, unit_price: '', discount: '', catalogKey: null, showSuggestions: false, suggestions: [], activeSuggestion: -1 }; })
        },

        init() {
            // El lector escribe en `form`: se le pasa el componente.
            this.lector.padre = this;

            // Desde la portada del modulo se llega con ?lector=1: se abre ya
            // el lector en vez de obligar a buscar el boton.
            if (new URLSearchParams(location.search).get('lector') === '1') {
                this.creating = true;
                this.lector.abierto = true;
            }

            /* Se guarda al vuelo mientras se escribe, y tambien al ocultarse
               la pestana: es el momento exacto en que el movil la descarta. */
            this.$watch('form', () => this.respaldar(), { deep: true });
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.respaldar();
            });

            // set today as default
            const today = new Date().toISOString().slice(0,10);
            this.form.issue_date = today;

            // Al entrar por una seccion concreta del menu, el formulario de
            // ESE documento sale directo y la lista queda filtrada: es lo que
            // se viene a hacer. Sin seccion (?tipo= vacio) manda la lista.
            const seccion = @js($seccion ?? '');
            if (seccion === 'nota') {
                /* Una nota NO se emite en blanco: corrige a un comprobante
                   concreto y hereda su receptor, sus lineas y su motivo. Abrir
                   aqui el formulario de venta daba una nota con serie de
                   factura, sin documento afectado y sin motivo — la habria
                   rechazado SUNAT. Esta seccion muestra las notas emitidas y
                   manda a elegir el comprobante a corregir. */
                this.filterType = '';
                this.panel = 'list';
                return;
            }
            if (seccion) {
                this.filterType = seccion;
                this.openNew(seccion);
            }
        },

        /* Comprobantes que ADMITEN nota: aceptados por SUNAT, no dados de baja
           y que no sean ellos mismos una nota. Es la misma condicion que abre
           los botones de nota en la ficha, para no ofrecer aqui uno que luego
           no deje emitirla. */
        get corregibles() {
            const q = (this.notaBusca || '').toLowerCase().trim();
            return this.invoices.filter(inv => {
                if (['nota_credito','nota_debito'].includes(inv.type)) return false;
                if (inv.sunat_status !== 'accepted') return false;
                if (inv.baja_estado === 'accepted') return false;
                if (!q) return true;
                return (inv.numero || '').toLowerCase().includes(q)
                    || (inv.client_name || '').toLowerCase().includes(q);
            }).slice(0, q ? 30 : 8);
        },

        get notasEmitidas() {
            return this.invoices.filter(inv => ['nota_credito','nota_debito'].includes(inv.type));
        },

        buscarParaNota() { /* el filtro es reactivo; el boton es para el teclado movil */ },

        get filtered() {
            return this.invoices.filter(inv => {
                const s = !this.search ||
                    inv.client_name.toLowerCase().includes(this.search.toLowerCase()) ||
                    (inv.numero || '').toLowerCase().includes(this.search.toLowerCase());
                const t = !this.filterType || inv.type === this.filterType;
                return s && t;
            });
        },

        async select(inv) {
            this.creating = false;
            this.panel = 'detail';
            // fetch full detail
            const res = await fetch(`{{ $invoicesApiBase }}/` + inv.id, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            this.selected = data;
            this.editStatus = data.status;
        },

        cerrarFormulario() {
            const seccion = @js($seccion ?? '');
            this.selected = null;
            if (seccion === 'nota') { this.creating = false; this.panel = 'list'; return; }
            if (seccion) {
                // Sin lista detras, cerrar dejaba la pantalla en blanco:
                // se empieza un comprobante nuevo del mismo tipo. Aqui la
                // seccion solo puede ser factura o boleta: 'nota' ya salio
                // arriba, porque no se emite desde este formulario.
                this.openNew(seccion);
                return;
            }
            this.creating = false;
            this.panel = 'list';
        },

        openNew(tipo) {
            this.selected = null;
            this.creating = true;
            this.panel = 'detail';
            const today = new Date().toISOString().slice(0,10);
            // Cada seccion del menu (Facturas / Boletas / Notas) abre SU
            // formulario: llegar a "Boletas" y encontrar el tipo en factura
            // obligaba a corregirlo a mano en cada emision.
            const t = tipo || 'boleta';
            const serie = t === 'factura' ? '{{ $serieFactura }}'
                        : (t === 'boleta' ? '{{ $serieBoleta }}' : '');
            this.form = {
                type: t, serie: serie, correlativo: '', issue_date: today, due_date: '',
                client_name: '', client_phone: '', client_email: '',
                client_doc_type: '', client_doc_number: '', client_address: '',
                payment_method: '', payment_condition: 'contado', currency: 'PEN', notes: '',
                igv_included: true,
                items: [1,2,3,4,5].map(function () { return { description: '', unit: 'NIU', quantity: 1, unit_price: '', discount: '', catalogKey: null, showSuggestions: false, suggestions: [], activeSuggestion: -1 }; })
            };
            this.saveError = '';
            // Huella nueva por comprobante: reusarla haria que el servidor
            // devolviera el anterior en vez de emitir este.
            this.huellaEmision = '';
            this.paso = 1;
            this.docPlegado = true;
            this.clienteQuery = '';
            this.clienteSugerencias = [];
            this.clienteActivo = -1;
            this.clienteEstado = '';
            this.editandoCliente = false;
            this.docAviso = '';
            this.productPickerOpen = false;
            this.productPickerSelected = [];
        },

        /* Rellena el formulario con lo que devolvio el lector. Se parte de
           openNew() para heredar la forma exacta del `form` (y sus campos de
           UI): asi anadir un campo al comprobante no obliga a tocar tambien
           el lector. */
        aplicarLectura(datos) {
            this.openNew(datos.type);
            Object.assign(this.form, datos);
            // Las lineas se reemplazan enteras: openNew deja una vacia.
            if (datos.items && datos.items.length) {
                this.form.items = datos.items;
            }
            this.saveError = '';
            this.panel = 'detail';
            // El usuario tiene que ver lo que va a emitir antes de darle a
            // Emitir: se vuelve arriba del formulario.
            this.$nextTick(() => {
                const cont = document.querySelector('#inv-detalle .overflow-y-auto');
                if (cont) cont.scrollTop = 0;
            });
        },

        /* Lleva al paso donde esta el campo que el servidor rechazo. */
        irAlPasoDelError(errores) {
            const campos = Object.keys(errores);
            const paso = campos.some(c => c.startsWith('client_') || c === 'type' || c === 'serie'
                                       || c === 'issue_date' || c === 'due_date') ? 1
                       : campos.some(c => c.startsWith('items')) ? 2
                       : 3;
            if (paso !== this.paso) { this.paso = paso; this.alTope(); }
        },

        /* ── Respaldo local ───────────────────────────────────────── */

        /* El formulario a medio llenar se guarda en el propio telefono. No es
           un borrador del sistema: es una red para que una llamada entrante,
           un cambio de app o que el navegador descarte la pestana no borren
           media factura con el cliente delante. Se limpia al emitir. */
        claveRespaldo() {
            return 'bx.emision.' + @js($project->id ?? 0) + '.' + (this.form.type || 'boleta');
        },

        respaldar() {
            if (!this.creating) return;
            try {
                // Sin los campos de UI del autocompletado: no aportan nada al
                // volver y engordan lo que se guarda.
                const items = (this.form.items || []).map(i => ({
                    description: i.description, unit: i.unit, quantity: i.quantity,
                    unit_price: i.unit_price, discount: i.discount, catalogKey: i.catalogKey,
                }));
                localStorage.setItem(this.claveRespaldo(), JSON.stringify({
                    form: { ...this.form, items },
                    paso: this.paso,
                    cuando: Date.now(),
                }));
            } catch (e) { /* modo privado o sin espacio: se sigue sin red */ }
        },

        /* Solo se ofrece si tiene algo que merezca la pena y es de hoy: una
           factura de anteayer ya no sirve (SUNAT solo admite 3 dias) y
           reaparecer sola confundiria mas que ayudar. */
        recuperarRespaldo() {
            try {
                const crudo = localStorage.getItem(this.claveRespaldo());
                if (!crudo) return;

                const guardado = JSON.parse(crudo);
                const horas = (Date.now() - (guardado.cuando || 0)) / 3600000;
                const tieneAlgo = (guardado.form?.client_name || '').trim() !== ''
                    || (guardado.form?.items || []).some(i => (i.description || '').trim() !== '');

                if (horas > 12 || !tieneAlgo) { this.olvidarRespaldo(); return; }

                this.respaldoPendiente = guardado;
            } catch (e) { /* respaldo ilegible: se ignora */ }
        },

        aplicarRespaldo() {
            if (!this.respaldoPendiente) return;
            const g = this.respaldoPendiente;

            Object.assign(this.form, g.form);
            this.form.items = (g.form.items || []).map(i => ({
                ...this.emptyInvoiceItem(), ...i,
            }));
            this.paso = g.paso || 1;
            this.respaldoPendiente = null;
            bxAviso('Recuperamos lo que estabas escribiendo.', 'exito');
        },

        olvidarRespaldo() {
            this.respaldoPendiente = null;
            try { localStorage.removeItem(this.claveRespaldo()); } catch (e) {}
        },

        /* ── Pasos (solo movil) ───────────────────────────────────── */

        etiquetaPaso() {
            return ({ 1: 'Cliente', 2: 'Productos', 3: 'Cobro' })[this.paso] || '';
        },

        /* No se deja avanzar sin lo minimo: llegar al final y descubrir que
           falta el cliente obliga a rehacer el camino entero. */
        puedeAvanzar() {
            if (this.paso === 1) {
                if ((this.form.client_name || '').trim() === '') return false;
                /* En una FACTURA el domicilio fiscal del receptor es
                   obligatorio para SUNAT. La ficha en modo lectura muestra
                   "Sin direccion" en gris pequenito y se pasaba de largo: el
                   rechazo aparecia recien al declarar, con el comprobante ya
                   numerado. Se para aqui, que es donde se arregla en un clic. */
                if (this.form.type === 'factura' && (this.form.client_address || '').trim() === '') return false;
                return true;
            }
            if (this.paso === 2) return this.form.items.some(i => (i.description || '').trim() !== '');
            return true;
        },

        avisoPaso() {
            if (this.paso === 1) {
                if ((this.form.client_name || '').trim() === '') return 'Escribe o busca el cliente para seguir.';
                if (this.form.type === 'factura' && (this.form.client_address || '').trim() === '') {
                    return 'Una factura necesita la dirección fiscal del cliente.';
                }
                return '';
            }
            if (this.paso === 2) return 'Añade al menos un producto.';
            return '';
        },

        siguientePaso() {
            if (this.paso < 3 && this.puedeAvanzar()) {
                this.paso++;
                this.alTope();
            }
        },

        pasoAnterior() {
            if (this.paso > 1) { this.paso--; this.alTope(); }
        },

        irAlPaso(n) {
            // Solo se puede saltar a lo ya visitado: adelantarse dejaria
            // huecos que el usuario no ve hasta el final.
            if (n < this.paso) { this.paso = n; this.alTope(); }
        },

        alTope() {
            this.$nextTick(() => {
                const cont = document.querySelector('#inv-detalle-scroll');
                if (cont) cont.scrollTop = 0;
                else window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        },

        /* ── Cabecera ─────────────────────────────────────────────── */

        etiquetaTipo() {
            return ({ factura: 'Factura', boleta: 'Boleta',
                      nota_credito: 'Nota de crédito', nota_debito: 'Nota de débito'
                    })[this.form.type] || 'Comprobante';
        },

        tituloFormulario() {
            return 'Nueva ' + this.etiquetaTipo().toLowerCase();
        },

        subtituloFormulario() {
            const t = this.etiquetaTipo().toLowerCase();
            return 'Completa la información para emitir la ' + t + ' electrónica.';
        },

        etiquetaEmitir() {
            return 'Emitir ' + this.etiquetaTipo().toLowerCase();
        },

        /* Series autorizadas del tipo. SUNAT las asigna por negocio, asi que
           salen de los ajustes y no se teclean a mano. */
        seriesDelTipo() {
            if (this.form.type === 'factura') return this.seriesFactura;
            if (this.form.type === 'boleta')  return this.seriesBoleta;
            if (this.form.type === 'nota_credito') return this.seriesNotaCredito;
            if (this.form.type === 'nota_debito')  return this.seriesNotaDebito;
            return [];
        },

        /* ── Cliente ──────────────────────────────────────────────── */

        /* Un solo cajon: digitos buscan documento, letras buscan nombre. Lo
           interno se consulta siempre primero; salir a SUNAT solo tiene
           sentido si el cliente aun no existe aqui. */
        async buscarClientes() {
            const q = (this.clienteQuery || '').trim();
            if (q.length < 2) { this.clienteSugerencias = []; return; }

            try {
                const res = await fetch(CLIENTES_URL + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                this.clienteSugerencias = data.clientes || [];
                this.clienteActivo = this.clienteSugerencias.length ? 0 : -1;
            } catch (e) {
                this.clienteSugerencias = [];
            }
        },

        moverSugerenciaCliente(paso) {
            const n = this.clienteSugerencias.length;
            if (!n) return;
            this.clienteActivo = (this.clienteActivo + paso + n) % n;
        },

        elegirSugerenciaActiva() {
            const c = this.clienteSugerencias[this.clienteActivo];
            if (c) { this.elegirCliente(c); return; }
            // Sin coincidencias internas: si escribio un documento, se consulta.
            const digitos = (this.clienteQuery || '').replace(/\D/g, '');
            if (digitos.length === 8 || digitos.length === 11) {
                this.form.client_doc_number = digitos;
                this.autoTipoDoc();
                this.consultarDocumento(true);
                this.clienteQuery = '';
            }
        },

        elegirCliente(c) {
            this.form.client_name       = c.nombre || '';
            this.form.client_doc_type   = c.doc_tipo || this.form.client_doc_type;
            this.form.client_doc_number = c.doc_numero || '';
            this.form.client_address    = c.direccion || '';
            this.form.client_phone      = c.telefono || '';
            this.form.client_email      = c.email || '';

            this.clienteSugerencias = [];
            this.clienteQuery = '';
            this.clienteActivo = -1;
            this.editandoCliente = false;
            this.clienteEstado = 'Cliente seleccionado';
            this.clienteEstadoOk = true;
            this.docAviso = '';
        },

        /* Abre el catalogo completo de clientes reutilizando el buscador: es
           el mismo listado, con la busqueda vacia. */
        abrirClientes() {
            this.clienteQuery = '';
            this.clienteSugerencias = [];
            this.$nextTick(() => {
                const campo = document.querySelector('[placeholder^="Buscar por nombre"]');
                if (campo) campo.focus();
            });
            this.buscarTodosLosClientes();
        },

        async buscarTodosLosClientes() {
            try {
                const res = await fetch(CLIENTES_URL + '?q=' + encodeURIComponent('  '), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                this.clienteSugerencias = data.clientes || [];
            } catch (e) { /* sin red, el usuario escribe a mano */ }
        },

        /* El tipo de documento se deduce del numero: 11 es RUC, 8 es DNI.
           Preguntarlo antes de escribir era un paso de mas. */
        autoTipoDoc() {
            const n = (this.form.client_doc_number || '').replace(/\D/g, '');
            /* El aviso de "un RUC tiene 11 digitos" se escribio cuando el
               numero iba a medias, y se quedaba pegado aunque despues se
               completara: en pantalla salia un RUC de 11 digitos correcto con
               una alerta debajo diciendo que estaba mal. Se borra en cuanto el
               numero deja de ser incompleto. */
            if (this.documentoConsultable() && !this.docAvisoOk) this.docAviso = '';
            if (this.form.type === 'factura') { this.form.client_doc_type = 'RUC'; return; }
            if (n.length === 11) this.form.client_doc_type = 'RUC';
            else if (n.length === 8) this.form.client_doc_type = 'DNI';
        },

        documentoConsultable() {
            const n = (this.form.client_doc_number || '').replace(/\D/g, '');
            return n.length === 8 || n.length === 11;
        },

        /* Consulta a SUNAT/RENIEC. Se dispara sola al completar el numero;
           el boton queda como accion manual para reintentar. */
        async consultarDocumento(manual = false) {
            const n = (this.form.client_doc_number || '').replace(/\D/g, '');

            if (!this.documentoConsultable()) {
                if (manual && n.length) {
                    /* En una factura el receptor SIEMPRE es RUC (SUNAT no
                       admite DNI), asi que nombrar el DNI ahi despista: el
                       aviso dice solo lo que aplica al documento en curso. */
                    this.docAviso = this.form.type === 'factura'
                        ? 'Una factura necesita un RUC de 11 dígitos.'
                        : 'Un RUC tiene 11 dígitos y un DNI 8.';
                    this.docAvisoOk = false;
                }
                return;
            }
            if (this.buscandoRuc) return;

            this.buscandoRuc = true;
            this.docAviso = 'Consultando...';

            try {
                /* Se intenta DOS veces antes de rendirse: en el mostrador la red
                   va y viene, y un corte de medio segundo no puede obligar a
                   teclear la razon social a mano. Cada intento se suelta a los
                   12 s para que el guardian de reentrada no bloquee el boton.
                   El error real queda en la consola (F12) para diagnosticarlo. */
                const pedir = () => {
                    const ctrl = new AbortController();
                    const t = setTimeout(() => ctrl.abort(), 12000);
                    return fetch(RUC_URL + '?doc=' + n, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        signal: ctrl.signal,
                    }).finally(() => clearTimeout(t));
                };

                let res;
                try {
                    res = await pedir();
                } catch (primero) {
                    console.warn('Consulta RUC/DNI, primer intento fallido:', primero);
                    await new Promise(r => setTimeout(r, 1500));
                    res = await pedir();
                }

                if (!res.ok) {
                    console.warn('Consulta RUC/DNI: el servidor respondio', res.status);
                }
                const data = await res.json().catch(() => ({}));

                if (!data.ok) {
                    /* Sin resultado NO es un callejon: el comprobante se puede
                       emitir escribiendo los datos a mano. El aviso lo dice,
                       para que nadie se quede esperando a que aparezcan.
                       Y se LIMPIA lo que hubiera: si no, tras consultar un RUC
                       valido y luego uno que no existe, quedaban en pantalla el
                       nombre y la direccion del primero junto al documento del
                       segundo. Eso se emite sin que nadie lo note. */
                    this.form.client_name    = '';
                    this.form.client_address = '';
                    this.docAviso = data.message
                        || 'No encontrado en SUNAT/RENIEC · escribe los datos a mano';
                    this.docAvisoOk = false;
                    return;
                }

                /* REEMPLAZA, no hereda: con `data.direccion || lo_anterior`,
                   consultar un RUC sin direccion se quedaba con la del RUC
                   consultado ANTES. Salia el nombre de uno y el domicilio de
                   otro, y asi se emitia. Si el padron no la trae, el campo
                   queda vacio y se escribe a mano, que es lo correcto. */
                this.form.client_name    = data.razon_social || '';
                this.form.client_address = data.direccion || '';

                // El estado del contribuyente importa: a un RUC de baja no se
                // le deberia facturar sin saberlo.
                const nota = [data.estado, data.condicion].filter(Boolean).join(' · ');
                this.docAviso = nota ? ('Encontrado · ' + nota) : 'Encontrado';
                /* El aviso vive justo donde aparece el teclado. Si el
                   contribuyente no esta activo hay que verlo si o si. */
                if (data.estado && data.estado.toUpperCase() !== 'ACTIVO') {
                    bxAviso('Atención: el contribuyente figura como ' + data.estado, 'error');
                }
                this.docAvisoOk = data.estado ? data.estado.toUpperCase() === 'ACTIVO' : true;
                this.editandoCliente = false;
            } catch (e) {
                console.error('Consulta RUC/DNI fallida tras reintentar:', e);
                this.docAviso = (e && e.name === 'AbortError')
                    ? 'El servicio tarda demasiado. Pulsa "Consultar" para reintentar o escribe los datos a mano.'
                    : 'Sin conexión con el servidor. Pulsa "Consultar" para reintentar o escribe los datos a mano.';
                this.docAvisoOk = false;
            } finally {
                this.buscandoRuc = false;
            }
        },

        /* ── Lineas y totales ─────────────────────────────────────── */

        /* Importe de la linea, con su descuento. Antes se calculaba suelto en
           el markup y el descuento ni se contemplaba. */
        /* Que le falta a la linea, en la columna del importe. Antes solo
           avisaba del precio: con cantidad 0 el importe decia "S/ 0.00" y
           parecia un dato bueno. Solo habla de lineas con producto escrito;
           las vacias todavia no son nada. */
        avisoLinea(item) {
            if (!(item.description || '').trim()) return '';
            if (!(Number(item.unit_price) > 0)) return 'Falta precio';
            if (!(Number(item.quantity) > 0))   return 'Falta cantidad';
            return '';
        },

        /* ── Plazo de credito ─────────────────────────────────────── */

        /* Suma dias a la fecha de emision. Se calcula sobre la emision y no
           sobre hoy: un comprobante con fecha de ayer vence 30 dias despues de
           ESA fecha, que es la que va en el XML. */
        fijarPlazo(dias) {
            const base = this.form.issue_date || new Date().toISOString().slice(0, 10);
            const f = new Date(base + 'T12:00:00');
            f.setDate(f.getDate() + dias);
            this.form.due_date = f.toISOString().slice(0, 10);
            this.plazoElegido = dias;
        },

        textoPlazo() {
            if (!this.form.due_date) return 'Falta la fecha: SUNAT la exige en toda venta al crédito.';
            const emision = new Date((this.form.issue_date || new Date().toISOString().slice(0, 10)) + 'T12:00:00');
            const vence   = new Date(this.form.due_date + 'T12:00:00');
            const dias    = Math.round((vence - emision) / 86400000);
            if (dias < 0) return 'El vencimiento no puede ser anterior a la emisión.';
            const fecha = vence.toLocaleDateString('es-PE', { day: '2-digit', month: 'long', year: 'numeric' });
            if (dias === 0) return 'Vence el mismo día de la emisión (' + fecha + ').';
            return 'Vence el ' + fecha + ' · ' + dias + (dias === 1 ? ' día' : ' días') + ' de plazo.';
        },

        /* Al contado el vencimiento sobra: dejarlo puesto declara una fecha de
           pago que no existe. Al pasar a credito se propone 30 dias, el plazo
           mas comun, sin obligar a nada. */
        alCambiarCondicion() {
            if (this.form.payment_condition === 'contado') {
                this.form.due_date = '';
                this.plazoElegido  = null;
            } else if (!this.form.due_date) {
                this.fijarPlazo(30);
            }
        },

        importeLinea(item) {
            const cant   = parseFloat(item.quantity) || 0;
            const precio = parseFloat(item.unit_price) || 0;
            const desc   = Math.min(100, Math.max(0, parseFloat(item.discount) || 0));
            return cant * precio * (1 - desc / 100);
        },

        /* ── Acciones del pie ─────────────────────────────────────── */

        /* Un borrador se guarda tal cual, sin declararlo. Se emite despues
           desde el detalle, cuando este revisado. */
        guardarBorrador() {
            this.save('draft');
        },

        /* La MISMA hoja que saldra impresa, sin gastar correlativo. Se abre
           en una pestana nueva con un POST clasico: el navegador lo permite
           porque nace de un clic, cosa que un window.open tras fetch no. */
        vistaPrevia() {
            if (!this.form.items.some(i => (i.description || '').trim())) {
                bxAviso('Agrega al menos un producto para ver la vista previa.', 'info');
                return;
            }
            const f = document.createElement('form');
            f.method = 'POST'; f.action = PREVIA_URL; f.target = '_blank'; f.style.display = 'none';
            const campo = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
            campo('_token', CSRF);
            // Igual que al guardar: la previa no muestra renglones en blanco.
            campo('payload', JSON.stringify({
                ...this.form,
                items: this.lineasUtiles(),
            }));
            document.body.appendChild(f);
            f.submit();
            f.remove();
        },

        /* Enlace de WhatsApp al cliente con los datos del comprobante. El PDF
           no viaja solo: WhatsApp no admite adjuntar desde un enlace, asi que
           el cajero lo descarga con el boton de al lado y lo arrastra. */
        waComprobante() {
            if (!this.selected || !this.selected.client_phone) return '#';
            let tel = String(this.selected.client_phone).replace(/\D/g, '');
            if (tel.length === 9) tel = '51' + tel;
            const moneda = { PEN: 'S/', USD: 'US$', EUR: '€' }[this.selected.currency] || this.selected.currency || 'S/';
            const texto = `Hola ${this.selected.client_name || ''}, le compartimos su ${this.selected.type_label} ${this.selected.numero} `
                + `por ${moneda} ${Number(this.selected.total || 0).toFixed(2)} emitida el ${this.selected.issue_date || ''}. `
                + `Adjuntamos el PDF. Gracias por su compra. — ${NEGOCIO}`;
            return 'https://wa.me/' + tel + '?text=' + encodeURIComponent(texto);
        },

        autoSerie() {
            const series = {
                factura: @js($serieFactura), boleta: @js($serieBoleta),
                nota_credito: @js($serieNotaCredito), nota_debito: @js($serieNotaDebito),
            };
            if (series[this.form.type]) this.form.serie = series[this.form.type];

            // Al pasar a factura el receptor es un RUC por definicion. Sin esto
            // quedaba dentro un 'DNI' escogido antes, invisible porque el
            // selector se oculta, y SUNAT rechazaba el comprobante ya emitido.
            if (this.form.type === 'factura') this.form.client_doc_type = 'RUC';
        },

        /* Una linea esta en blanco si no tiene ni concepto ni precio: es la
           que acaba de nacer y todavia no se ha tocado. */
        lineaEnBlanco(i) {
            return !i || ((i.description || '').trim() === ''
                && String(i.unit_price ?? '').trim() === ''
                && !i.catalogKey);
        },

        addItem() {
            /* Si la ultima linea sigue en blanco NO se crea otra: se salta a
               ella. Pulsar "+" tres veces dejaba tres renglones vacios que
               luego habia que borrar uno a uno, y con el scroll largo ni se
               veian. Asi el boton siempre lleva a una linea util. */
            const ultima = this.form.items[this.form.items.length - 1];
            if (!this.lineaEnBlanco(ultima)) {
                this.form.items.push(this.emptyInvoiceItem());
            }

            /* El boton vive arriba y la linea nace abajo: sin esto el cajero
               pulsaba "+" y no veia nada moverse, tenia que buscar la tarjeta
               nueva y volver a tocarla para escribir. */
            this.$nextTick(() => {
                const campos = document.querySelectorAll('#inv-detalle-scroll input[placeholder^="Buscar"]');
                const ultimo = campos[campos.length - 1];
                if (!ultimo) return;
                ultimo.scrollIntoView({ behavior: 'smooth', block: 'center' });
                ultimo.focus({ preventScroll: true });
            });
        },
        removeItem(idx) {
            if (this.form.items.length > 1) this.form.items.splice(idx, 1);
        },

        emptyInvoiceItem() {
            return {
                /* Precio y descuento nacen VACIOS, no en 0: un 0 escrito en el
                   campo se lee como un precio de verdad ("esta a cero") y
                   obliga a borrarlo antes de teclear. Vacio se ve como lo que
                   es: falta por llenar. El calculo ya trata el vacio como 0. */
                description: '', unit: 'NIU', quantity: 1, unit_price: '', discount: '',
                catalogKey: null, showSuggestions: false, suggestions: [], activeSuggestion: -1,
            };
        },

        money(value) {
            return 'S/ ' + (Number(value) || 0).toFixed(2);
        },

        /* La fecha de emision en claro para el aviso de confirmacion. Se parte
           la cadena a mano: `new Date('2026-09-12')` la lee como UTC y en Peru
           (UTC-5) se muestra el dia ANTERIOR. */
        fechaLarga(iso) {
            const p = String(iso || '').split('-');
            if (p.length !== 3) return iso || '-';
            const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                           'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
            return Number(p[2]) + ' de ' + (meses[Number(p[1]) - 1] || '?') + ' de ' + p[0];
        },

        matchesCatalog(product, query) {
            const haystack = [product.name, product.sku, product.description, product.type_label, product.brand, product.category]
                .filter(Boolean).join(' ').toLocaleLowerCase('es');
            return haystack.includes(query.toLocaleLowerCase('es'));
        },

        /* Coordenadas de la lista flotante, calculadas desde el campo.
           `position:fixed` la libera del contenedor que la recortaba, pero a
           cambio hay que decirle donde ponerse. Si abajo no cabe, se abre
           hacia arriba: en la ultima fila de la rejilla la lista se salia por
           el borde inferior de la ventana. */
        posicionSugerencias(idx) {
            const campo = document.querySelector('#inv-app [data-descripcion="' + idx + '"]');
            if (!campo || campo.offsetParent === null) return 'display:none';
            const c = campo.getBoundingClientRect();
            const alto = Math.min(216, ((this.form.items[idx]?.suggestions?.length || 0) * 30) + 24);
            const cabeAbajo = (window.innerHeight - c.bottom) > alto + 8;
            const vertical = cabeAbajo
                ? 'top:' + (c.bottom + 2) + 'px'
                : 'bottom:' + (window.innerHeight - c.top + 2) + 'px';
            /* `position` y `z-index` viajan AQUI, no en el atributo `style`:
               Alpine reescribe `style` entero al evaluar `:style` y se llevaba
               por delante lo que hubiera puesto a mano. Comprobado en Chrome:
               la lista se quedaba en `position:static` y volvia a recortarse. */
            /* La lista NO se ata al ancho del campo: la columna Descripcion es
               estrecha y "ROLLOS DE CABLE VULCANIZADO 2x16" se cortaba justo
               en el calibre, que es lo unico que distingue un producto de
               otro. Al flotar puede pasarse de ancho; se le da un minimo
               comodo sin salirse de la ventana. */
            const ancho = Math.min(
                Math.max(c.width, 460),
                window.innerWidth - c.left - 16
            );
            return 'position:fixed;z-index:50;' + vertical
                 + ';left:' + c.left + 'px;width:' + ancho + 'px';
        },

        searchCatalog(idx) {
            const item = this.form.items[idx];
            if (!item) return;
            const query = (item.description || '').trim();
            item.catalogKey = null;
            if (query.length < 3) {
                item.suggestions = []; item.masResultados = 0;
                item.activeSuggestion = -1; item.showSuggestions = false;
                return;
            }
            const coincidencias = this.catalogo.filter(product => this.matchesCatalog(product, query));
            // Seis a la vista: mas que eso tapa la rejilla y no se lee. El
            // pie dice cuantos quedan fuera para que el cajero afine.
            item.suggestions = coincidencias.slice(0, 6);
            item.masResultados = Math.max(0, coincidencias.length - 6);
            item.activeSuggestion = item.suggestions.length ? 0 : -1;
            item.showSuggestions = item.suggestions.length > 0;
        },

        cerrarTodasLasSugerencias() {
            this.form.items.forEach(i => { i.showSuggestions = false; i.activeSuggestion = -1; });
        },

        closeCatalogSuggestions(idx) {
            const item = this.form.items[idx];
            if (!item) return;
            item.showSuggestions = false;
            item.activeSuggestion = -1;
        },

        moveCatalogSuggestion(idx, direction) {
            const item = this.form.items[idx];
            if (!item) return;
            if (!item.showSuggestions) this.searchCatalog(idx);
            if (!item.suggestions.length) return;
            item.activeSuggestion = (item.activeSuggestion + direction + item.suggestions.length) % item.suggestions.length;
        },

        selectActiveSuggestion(idx) {
            const item = this.form.items[idx];
            if (!item?.showSuggestions || item.activeSuggestion < 0) return;
            const product = item.suggestions[item.activeSuggestion];
            if (product) this.selectCatalogProduct(idx, product);
        },

        selectCatalogProduct(idx, product) {
            const item = this.form.items[idx];
            if (!item || !product) return;
            item.description = product.name;
            item.unit = product.unit || 'NIU';
            item.unit_price = Number(product.price) || 0;
            item.catalogKey = product.key;
            item.showSuggestions = false;
            item.suggestions = [];
            item.activeSuggestion = -1;
            // El siguiente dato SIEMPRE es la cantidad: se lleva el foco solo,
            // como al tabular en una hoja de calculo.
            this.$nextTick(() => this.enfocarCampo('cantidad', idx));
        },

        /**
         * Enter en Cantidad: abre la linea siguiente y salta a su descripcion.
         * Si ya es la ultima con contenido, crea una nueva. Asi se teclea la
         * venta entera sin tocar el raton.
         */
        siguienteLinea(idx) {
            const item = this.form.items[idx];
            if (!item) return;
            // Sin descripcion no tiene sentido abrir otra linea: seria una fila
            // vacia entre medias que luego hay que borrar a mano.
            if (!(item.description || '').trim()) return;

            if (idx === this.form.items.length - 1) {
                this.addItem();
            }
            this.$nextTick(() => this.enfocarCampo('descripcion', idx + 1));
        },

        /* Alpine 3 no admite `:ref` dinamico ($refs solo ve x-ref fijos): el
           salto de foco "funcionaba" en el codigo y nunca en la pantalla. El
           cajero tecleaba la cantidad con el cursor en ninguna parte y la
           linea se quedaba en 1. Se busca el campo en el DOM y, como la rejilla
           de escritorio y las tarjetas de movil existen a la vez, se toma el
           que esta visible. */
        enfocarCampo(tipo, idx) {
            const nodos = document.querySelectorAll('#inv-app [data-' + tipo + '="' + idx + '"]');
            const visible = [...nodos].find(n => n.offsetParent !== null) || nodos[0];
            if (visible) { visible.focus(); if (typeof visible.select === 'function') visible.select(); }
        },

        /* ── Moverse por la rejilla como en una hoja de calculo ──────
           Quien factura en el mostrador viene de Excel: espera las flechas
           para andar por la tabla. Sin esto, cada salto de celda era un clic
           con el raton, y en una venta de diez lineas eso son cuarenta clics.

           Orden de las columnas, el mismo que se ve: descripcion, cantidad,
           precio, descuento. Arriba y abajo cambian de fila conservando la
           columna. Las flechas izquierda/derecha solo saltan cuando el cursor
           esta en el borde del texto, para no romper la edicion dentro del
           campo. En Descripcion, arriba/abajo los usa la lista de sugerencias
           cuando esta abierta, que manda ahi. */
        COLUMNAS_REJILLA: ['descripcion', 'cantidad', 'precio', 'descuento'],

        moverCelda(ev, tipo, idx) {
            const campo = ev.target;
            const cols  = this.COLUMNAS_REJILLA;
            const col   = cols.indexOf(tipo);
            if (col === -1) return;

            const tecla = ev.key;

            // Arriba/abajo: misma columna, otra fila.
            if (tecla === 'ArrowDown' || tecla === 'ArrowUp') {
                const destino = idx + (tecla === 'ArrowDown' ? 1 : -1);
                if (destino < 0) return;
                // Bajar desde la ultima linea abre una nueva, como en Excel.
                if (destino >= this.form.items.length) {
                    if (tecla !== 'ArrowDown') return;
                    this.addItem();
                }
                ev.preventDefault();
                this.$nextTick(() => this.enfocarCampo(tipo, destino));
                return;
            }

            /* Izquierda/derecha: solo desde el borde del texto. En medio de un
               numero las flechas tienen que seguir moviendo el cursor, o no se
               puede corregir un digito. En `type=number` el navegador no da
               selectionStart: ahi se salta siempre. */
            if (tecla === 'ArrowLeft' || tecla === 'ArrowRight') {
                const numerico = campo.type === 'number';
                const pos = numerico ? null : campo.selectionStart;
                const fin = (campo.value || '').length;
                const enBorde = numerico
                    || (tecla === 'ArrowLeft'  && pos === 0)
                    || (tecla === 'ArrowRight' && pos === fin);
                if (!enBorde) return;

                const destino = col + (tecla === 'ArrowRight' ? 1 : -1);
                if (destino < 0 || destino >= cols.length) return;
                ev.preventDefault();
                this.enfocarCampo(cols[destino], idx);
            }
        },

        /* El aviso nombra la linea, pero quien factura no deberia buscarla:
           el cursor va al campo que falta, en la fila real (el filtro de
           lineasUtiles cambia los indices, asi que se busca en form.items). */
        enfocarPrimeraLineaMala(esMala, campo) {
            const idx = this.form.items.findIndex(i => (i.description || '').trim() && esMala(i));
            if (idx >= 0) this.$nextTick(() => this.enfocarCampo(campo, idx));
        },

        openProductPicker() {
            this.form.items.forEach(item => item.showSuggestions = false);
            this.productPickerSearch = '';
            this.pickerMarca = ''; this.pickerCategoria = '';
            this.productPickerSelected = [];
            this.productPickerOpen = true;
            this.$nextTick(() => this.$refs.productPickerSearch?.focus());
        },

        get pickerProducts() {
            const query = (this.productPickerSearch || '').trim();
            // Texto, marca y categoria se INTERSECAN: lo mismo que promete la
            // cabecera del selector.
            return this.catalogo.filter(product =>
                (!query || this.matchesCatalog(product, query))
                && (!this.pickerMarca || product.brand === this.pickerMarca)
                && (!this.pickerCategoria || product.category === this.pickerCategoria)
            ).slice(0, 150);
        },

        /* FILTROS QUE SE MIRAN ENTRE SI.
           Marca y categoria no son independientes: INDECO hace cables y 3M
           hace cintas. Con las dos listas completas se podia elegir
           "INDECO + Amarres y precintos" —una combinacion que no existe— y el
           selector respondia "no hay productos", como si fallara.

           Cada lista se calcula sobre lo que deja ver el OTRO filtro (no sobre
           el suyo propio, o al elegir una marca desapareceria el resto y no se
           podria cambiar). El numero entre parentesis dice cuantos hay, asi se
           ve de un vistazo donde esta el grueso del catalogo. */
        cuentaPorClave(clave, filtrados) {
            const cuenta = new Map();
            filtrados.forEach(p => {
                const v = p[clave];
                if (v) cuenta.set(v, (cuenta.get(v) || 0) + 1);
            });
            return [...cuenta.entries()]
                .sort((a, b) => a[0].localeCompare(b[0], 'es'))
                .map(([valor, n]) => ({ valor, etiqueta: valor + ' (' + n + ')' }));
        },

        get pickerMarcas() {
            // Acotadas por la categoria elegida, no por la marca elegida.
            const base = this.catalogo.filter(p => !this.pickerCategoria || p.category === this.pickerCategoria);
            return this.cuentaPorClave('brand', base);
        },

        get pickerCategorias() {
            const base = this.catalogo.filter(p => !this.pickerMarca || p.brand === this.pickerMarca);
            return this.cuentaPorClave('category', base);
        },

        /* Si la marca elegida deja sin sentido la categoria (o al reves), se
           suelta la que sobra en vez de dejar el selector en cero resultados
           sin explicar por que. */
        alCambiarMarca() {
            if (!this.pickerMarca || !this.pickerCategoria) return;
            const existe = this.catalogo.some(p => p.brand === this.pickerMarca && p.category === this.pickerCategoria);
            if (!existe) this.pickerCategoria = '';
        },

        alCambiarCategoria() {
            if (!this.pickerMarca || !this.pickerCategoria) return;
            const existe = this.catalogo.some(p => p.brand === this.pickerMarca && p.category === this.pickerCategoria);
            if (!existe) this.pickerMarca = '';
        },

        /* Una columna solo se gana su ancho si DISTINGUE. Con "VUL" tecleado
           los 5 resultados son INDECO, categoria "Cables vulcanizados NMT" y
           sin SKU: tres columnas repitiendo lo mismo mientras el nombre se
           corta justo en el calibre. Si esta vacia en todos, o trae el mismo
           valor en todos, se quita y el nombre ocupa su sitio. */
        columnaUtil(campo) {
            const filas = this.pickerProducts;
            if (filas.length === 0) return false;
            let primero = null, distintos = false;
            for (const p of filas) {
                const v = (p[campo] || '').trim();
                if (!v) continue;
                if (primero === null) primero = v;
                else if (v !== primero) { distintos = true; break; }
            }
            if (primero === null) return false;   // vacia en todas
            return distintos || filas.length === 1;
        },

        isPickerProductSelected(key) {
            return this.productPickerSelected.includes(key);
        },

        togglePickerProduct(key) {
            const index = this.productPickerSelected.indexOf(key);
            if (index >= 0) this.productPickerSelected.splice(index, 1);
            else this.productPickerSelected.push(key);
        },

        applyPickerProducts() {
            const selected = this.productPickerSelected
                .map(key => this.catalogo.find(product => product.key === key))
                .filter(Boolean);
            if (!selected.length) return;

            let emptyIndex = this.form.items.findIndex(item =>
                !(item.description || '').trim() && (Number(item.unit_price) || 0) === 0
            );

            selected.forEach(product => {
                if (emptyIndex >= 0) {
                    this.selectCatalogProduct(emptyIndex, product);
                    emptyIndex = -1;
                    return;
                }
                const item = this.emptyInvoiceItem();
                item.description = product.name;
                item.unit = product.unit || 'NIU';
                item.unit_price = Number(product.price) || 0;
                item.catalogKey = product.key;
                this.form.items.push(item);
            });

            this.productPickerOpen = false;
            this.productPickerSelected = [];
        },

        calcSubtotal() {
            // Se suma el MISMO importe que muestra cada fila (con su
            // descuento). Calcularlo aparte hacia que el total del pie —el que
            // el cajero lee en voz alta y cobra— no coincidiera con la factura.
            const lineTotal = this.form.items.reduce((s, i) => s + this.importeLinea(i), 0);
            return this.form.igv_included ? lineTotal / 1.18 : lineTotal;
        },
        calcIgv() {
            const sub = this.calcSubtotal();
            return sub * 0.18;
        },
        calcTotal() {
            return this.calcSubtotal() + this.calcIgv();
        },

        /* Emite o guarda como borrador: es el MISMO envio, el backend decide
           con `status`. Dos caminos separados acabarian divergiendo. */
        /* Las lineas que de verdad viajan: la rejilla arranca con cinco filas
           para teclear de corrido, pero al comprobante solo van las escritas.
           La previa y la emision tienen que ver EXACTAMENTE lo mismo, o la
           hoja que se confirma no seria la que se emite. */
        lineasUtiles() {
            const usadas = (this.form.items || []).filter(i => (i.description || '').trim() !== '');
            return usadas.length ? usadas : this.form.items.slice(0, 1);
        },

        resumenConfirmacion() {
            const n = this.lineasUtiles().length;
            const cliente = (this.form.client_name || '').trim() || 'sin cliente';
            return `${this.etiquetaTipo()} para ${cliente} · ${n} ${n === 1 ? 'producto' : 'productos'}`;
        },

        /* Puerta antes de emitir: se muestra el comprobante ya calculado y no
           se crea nada hasta que se confirma. */
        async confirmarEmision() {
            if (!(this.form.client_name || '').trim()) {
                bxAviso('Escribe o busca el cliente antes de emitir.', 'info');
                if (this.isMobile) { this.paso = 1; this.alTope(); }
                return;
            }
            if (!this.form.items.some(i => (i.description || '').trim())) {
                bxAviso('Añade al menos un producto antes de emitir.', 'info');
                if (this.isMobile) { this.paso = 2; this.alTope(); }
                return;
            }
            /* Una linea con precio 0 casi nunca es a proposito: es la cantidad
               o el precio que no se llego a teclear. Emitida, solo se corrige
               con nota de credito. Se corta aqui, con nombres, antes de gastar
               correlativo. */
            const sinPrecio = this.lineasUtiles().filter(i => !(Number(i.unit_price) > 0));
            if (sinPrecio.length) {
                bxAviso('No se puede emitir: ' + (sinPrecio.length === 1 ? 'la línea' : 'las líneas') + ' "'
                    + sinPrecio.map(i => i.description.trim()).join('", "') + '" '
                    + (sinPrecio.length === 1 ? 'tiene' : 'tienen') + ' precio 0. Escribe el precio antes de emitir.', 'error');
                if (this.isMobile) { this.paso = 2; this.alTope(); }
                this.enfocarPrimeraLineaMala(i => !(Number(i.unit_price) > 0), 'precio');
                return;
            }

            /* Cantidad 0 es el mismo fallo por el otro lado: una linea que no
               vende nada. El importe sale 0 y la factura declara un producto
               que no se entrego. */
            const sinCantidad = this.lineasUtiles().filter(i => !(Number(i.quantity) > 0));
            if (sinCantidad.length) {
                bxAviso('No se puede emitir: ' + (sinCantidad.length === 1 ? 'la línea' : 'las líneas') + ' "'
                    + sinCantidad.map(i => i.description.trim()).join('", "') + '" '
                    + (sinCantidad.length === 1 ? 'tiene' : 'tienen') + ' cantidad 0. Escribe cuántos antes de emitir.', 'error');
                if (this.isMobile) { this.paso = 2; this.alTope(); }
                this.enfocarPrimeraLineaMala(i => !(Number(i.quantity) > 0), 'cantidad');
                return;
            }

            this.confirmarAbierto  = true;
            this.confirmarCargando = true;
            this.confirmarHtml     = '';
            this.confirmarError    = '';

            try {
                const cuerpo = new FormData();
                cuerpo.append('_token', CSRF);
                cuerpo.append('payload', JSON.stringify({
                    ...this.form,
                    items: this.lineasUtiles(),
                }));
                const res = await fetch(PREVIA_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    body: cuerpo,
                    signal: AbortSignal.timeout(20000),
                });
                if (!res.ok) throw new Error('respuesta ' + res.status);
                this.confirmarHtml = await res.text();

                /* La previa es un documento completo con sus propios estilos:
                   inyectarla en la pagina le pisaria el CSS al panel. Va en un
                   iframe con srcdoc, aislada. */
                this.$nextTick(() => {
                    if (this.$refs.marcoPrevia) this.$refs.marcoPrevia.srcdoc = this.confirmarHtml;
                });
            } catch (e) {
                this.confirmarError = 'No se pudo cargar la vista previa. Revisa la conexión e inténtalo de nuevo.';
            } finally {
                this.confirmarCargando = false;
            }
        },

        async emitirConfirmado() {
            this.confirmarAbierto = false;
            await this.save();
        },

        async save(estado = null) {
            // Huella del intento: si la peticion se repite (doble clic, recarga,
            // red lenta) el servidor devuelve el mismo comprobante en vez de
            // emitir otro.
            if (!this.huellaEmision) this.huellaEmision = 'e' + Date.now() + Math.random().toString(36).slice(2, 8);
            this.saving = true;
            this.saveError = '';
            const res = await fetch(`{{ $invoicesApiBase }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Idempotencia': this.huellaEmision
                },
                // Las lineas en blanco de la rejilla NO viajan: la pantalla
                // arranca con cinco para teclear de corrido, pero al negocio
                // solo le interesan las que tienen producto.
                body: JSON.stringify((() => {
                    const base = { ...this.form, items: this.lineasUtiles() };
                    return estado ? { ...base, status: estado } : base;
                })())
            });
            const data = await res.json();
            if (!res.ok) {
                const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error al guardar.');
                this.saveError = msgs;
                this.saving = false;

                /* En movil el aviso vivia en la columna del paso 3: si el
                   campo culpable era del paso 1, el cajero pulsaba Emitir y
                   "no pasaba nada". Se dice en pantalla y se lleva al paso
                   donde esta el problema. */
                bxAviso(msgs, 'error');
                if (this.isMobile) this.irAlPasoDelError(data.errors || {});
                return;
            }
            const inv = data.invoice;
            // Map to list format
            const listItem = {
                id: inv.id,
                numero: inv.numero,
                type: inv.type,
                type_label: { boleta:'Boleta', factura:'Factura', nota_credito:'N. Crédito', nota_debito:'N. Débito' }[inv.type] || inv.type,
                client_name: inv.client_name,
                total: parseFloat(inv.total),
                status: inv.status,
                status_label: { draft:'Borrador', issued:'Emitida', sent:'Enviada', cancelled:'Anulada' }[inv.status] || inv.status,
                issue_date: inv.issue_date ? new Date(inv.issue_date).toLocaleDateString('es-PE') : '',
                sunat_status: inv.sunat_status,
            };
            // El comprobante ya esta en el sistema: la red local sobra.
            this.olvidarRespaldo();
            this.invoices.unshift(listItem);
            this.creating = false;
            this.saving = false;
            await this.select(listItem);
            // Que el siguiente paso sea obvio: el comprobante ya existe y desde
            // aqui se descarga, se imprime o se manda por WhatsApp.
            bxAviso(inv.status === 'draft'
                ? 'Borrador guardado. Emítelo desde el detalle cuando esté listo.'
                : `${listItem.type_label} ${inv.numero} emitida. Descárgala o envíala por WhatsApp desde el detalle.`, 'success');
        },

        async updateStatus() {
            /* Se pintaba el estado nuevo SIN mirar si el servidor lo acepto:
               con mala señal el cajero veia "Anulada" en pantalla mientras en
               el sistema seguia emitida. Ahora manda la respuesta. */
            const anterior = this.selected.status;

            try {
                const res = await fetch(`{{ $invoicesApiBase }}/` + this.selected.id, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: this.editStatus }),
                    signal: AbortSignal.timeout(15000),
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    this.editStatus = anterior;
                    bxAviso(data.message || 'No se pudo cambiar el estado.', 'error');
                    return;
                }
            } catch (e) {
                this.editStatus = anterior;
                bxAviso('Sin conexión: el estado no se cambió.', 'error');
                return;
            }

            this.selected.status = this.editStatus;
            this.selected.status_label = { draft:'Borrador', issued:'Emitida', sent:'Enviada', cancelled:'Anulada' }[this.editStatus] || this.editStatus;
            const idx = this.invoices.findIndex(i => i.id === this.selected.id);
            if (idx > -1) { this.invoices[idx].status = this.editStatus; this.invoices[idx].status_label = this.selected.status_label; }
        },

        /* El padron de SUNAT escribe mejor que nadie la razon social. */
        async buscarRuc() {
            const ruc = (this.form.client_doc_number || '').replace(/\D/g, '');
            if (ruc.length !== 11 || this.buscandoRuc) return;

            this.buscandoRuc = true;
            const res = await fetch(`{{ route('invoices.ruc') }}?ruc=${ruc}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            this.buscandoRuc = false;

            if (!data.ok) {
                bxAviso(data.message || 'No se pudo consultar el RUC.', 'error');
                return;
            }

            // Mismo criterio que consultarDocumento(): los datos de un
            // contribuyente nunca se mezclan con los del consultado antes.
            this.form.client_name    = data.razon_social || '';
            this.form.client_address = data.direccion || '';
            bxAviso('Datos traídos del padrón de SUNAT.', 'exito');
        },

        abrirNota(tipo) {
            this.notaTipo    = tipo;
            // El motivo por defecto es el primero de su catalogo: '01' es
            // 'Anulacion de la operacion' en credito y 'Intereses por mora' en
            // debito, que son los casos habituales de cada uno.
            this.notaMotivo  = '01';
            this.notaDetalle = '';
            this.notaAbierta = true;
        },

        async emitirNota() {
            if (this.notaEnCurso) return;
            this.notaEnCurso = true;

            const res = await fetch(`{{ $invoicesApiBase }}/` + this.selected.id + '/nota', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: this.notaTipo,
                    motivo_codigo: this.notaMotivo,
                    motivo_descripcion: this.notaDetalle || null,
                }),
            });

            const data = await res.json().catch(() => ({}));
            this.notaEnCurso = false;

            if (!res.ok) {
                bxAviso(data.message || 'No se pudo emitir la nota.', 'error');
                return;
            }

            this.notaAbierta = false;
            // La nota es un comprobante mas: aparece en la lista al momento,
            // sin recargar, para que se vea que quedo emitida.
            if (data.nota) this.invoices.unshift(data.nota);
            bxAviso(data.message || 'Nota emitida.', 'exito');
        },

        async darDeBaja() {
            const motivo = await bxConfirmar({
                titulo: 'Comunicar la baja a SUNAT',
                descripcion: 'El comprobante ' + this.selected.numero + ' quedara sin efecto. '
                    + 'Su numero no se reutiliza. Escribe el motivo, que viaja a SUNAT.',
                boton: 'Comunicar baja',
                entrada: { etiqueta: 'Motivo de la baja', requerido: true, marcador: 'Error en el RUC del cliente' },
            });

            if (!motivo) return;

            this.bajaEnCurso = true;
            const res = await fetch(`{{ $invoicesApiBase }}/` + this.selected.id + '/baja', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ motivo }),
            });

            const data = await res.json().catch(() => ({}));
            this.bajaEnCurso = false;

            if (!res.ok) {
                bxAviso(data.message || 'No se pudo comunicar la baja.', 'error');
                return;
            }

            const upd = { baja_estado: 'pending', status: 'cancelled', status_label: 'Anulada' };
            const idx = this.invoices.findIndex(i => i.id === this.selected.id);
            if (idx > -1) this.invoices[idx] = { ...this.invoices[idx], ...upd };
            this.selected = { ...this.selected, ...upd };
            bxAviso(data.message || 'Baja en tramite.', 'exito');
        },

        async enviarSunat() {
            if (!this.selected) return;
            this.sendingSunat = true;
            const res = await fetch(`{{ $invoicesApiBase }}/` + this.selected.id + '/sunat', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || !data.ok) {
                this.sendingSunat = false;
                this.actualizarEstado({ sunat_status: 'error', sunat_error: data.message || 'No se pudo enviar.' });
                bxAviso(data.message || 'No se pudo enviar a SUNAT.', 'error');
                return;
            }

            /* El envio va en segundo plano: cuando esta respuesta llega, SUNAT
               todavia no ha dicho nada. Pintar "Aceptado" aqui era mentir —y
               fue lo que oculto durante dos dias que las facturas rebotaban
               con el error 0111. Se marca "enviando" y se pregunta el
               veredicto de verdad. */
            this.actualizarEstado({ sunat_status: 'pending', sunat_error: null });
            bxAviso('Enviando a SUNAT...', 'info');
            this.vigilarSunat();
        },

        /* Pregunta por el veredicto hasta que SUNAT conteste. Se rinde a los
           40 s: el comprobante sigue en la cola y el reintento automatico lo
           recoge, pero al usuario no se le deja mirando un spinner eterno. */
        async vigilarSunat() {
            const id = this.selected?.id;
            if (!id) { this.sendingSunat = false; return; }

            for (let intento = 0; intento < 20; intento++) {
                await new Promise(r => setTimeout(r, 2000));
                // El usuario cambio de comprobante: se deja de vigilar ESTE,
                // pero soltando la bandera. Salir sin soltarla dejaba el boton
                // "Enviando..." deshabilitado para todos los comprobantes
                // hasta recargar la pagina.
                if (this.selected?.id !== id) { this.sendingSunat = false; return; }

                let inv;
                try {
                    const res = await fetch(`{{ $invoicesApiBase }}/` + id, { headers: { 'Accept': 'application/json' } });
                    inv = (await res.json()).invoice;
                } catch (e) { continue; }
                if (!inv) continue;

                if (inv.sunat_status === 'accepted') {
                    this.sendingSunat = false;
                    this.actualizarEstado({ sunat_status: 'accepted', status: 'sent',
                                            status_label: 'Enviada', sunat_error: null });
                    bxAviso('✓ Aceptada por SUNAT', 'exito');
                    return;
                }
                if (inv.sunat_status === 'error' || inv.sunat_status === 'rejected') {
                    this.sendingSunat = false;
                    this.actualizarEstado({ sunat_status: inv.sunat_status, sunat_error: inv.sunat_error });
                    bxAviso(inv.sunat_error || 'SUNAT rechazó el comprobante.', 'error');
                    return;
                }
            }

            this.sendingSunat = false;
            bxAviso('SUNAT está tardando. El envío sigue en curso y se reintenta solo.', 'info');
        },

        /* Un solo sitio escribe el estado en la ficha y en la lista: tenerlo
           duplicado hacia que una se actualizara y la otra no. */
        actualizarEstado(cambios) {
            const idx = this.invoices.findIndex(i => i.id === this.selected?.id);
            if (idx > -1) this.invoices[idx] = { ...this.invoices[idx], ...cambios };
            if (this.selected) this.selected = { ...this.selected, ...cambios };
        },

        async deleteInvoice() {
            if (! await bxConfirmar({ descripcion: '¿Eliminar este comprobante?' })) return;
            const res = await fetch(`{{ $invoicesApiBase }}/` + this.selected.id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!res.ok) { bxAviso(data.message || 'No se pudo eliminar.', 'error'); return; }
            this.invoices = this.invoices.filter(i => i.id !== this.selected.id);
            this.selected = null;
            this.panel = 'list';
        }
    };
}
</script>
</x-portal-layout>

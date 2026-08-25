{{--
    Detalle del pedido — cajon enlazable.

    El contenido (modal de pago y ficha) se trasladó VERBATIM desde
    index.blade.php: no se reescribe lógica de negocio ya probada. Solo cambia
    el contenedor, que pasa de columna fija a cajón superpuesto.

    Vive dentro del x-data de index.blade.php, así que comparte estado Alpine
    (selected, payModal, registerPay, waAction…) sin necesidad de props.

    Accesibilidad: mismo patrón validado en el Paso 1 (inert, foco gestionado,
    Escape, ARIA, velo). El ancho evita 100vw a propósito: con anclaje derecho,
    100vw mete el borde izquierdo debajo del sidebar y recorta el texto.
--}}
<div id="ped-overlay"
     x-show="selected" x-cloak
     @click="cerrarPedido()"
     aria-hidden="true"></div>

<aside id="ped-drawer"
       :class="selected ? '' : 'hidden-panel'"
       x-effect="selected
            ? ($el.removeAttribute('inert'), $nextTick(() => $el.querySelector('[data-primer-foco]')?.focus()))
            : ($el.setAttribute('inert',''), filaOrigen?.focus())"
       @keydown.escape.window="selected && cerrarPedido()"
       role="dialog" aria-modal="true" aria-labelledby="ped-drawer-titulo"
       :aria-hidden="selected ? 'false' : 'true'" inert>

    <div class="ped-drawer-top">
        <h2 id="ped-drawer-titulo" class="text-sm font-bold text-gray-900">
            Pedido <span x-text="selected ? ('#' + (selected.tag_code || selected.id)) : ''"></span>
        </h2>
        <button data-primer-foco @click="cerrarPedido()"
                class="text-gray-400 hover:text-gray-700 text-lg leading-none px-2"
                aria-label="Cerrar detalle">&times;</button>
    </div>

    <div class="ped-drawer-body">
    {{-- MODAL REGISTRAR PAGO --}}
    <div x-show="payModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,.5)" @click.self="payModal=false" @keydown.escape.window="payModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5 space-y-3">
            <h3 class="font-black text-gray-900">Registrar pago</h3>
            <div>
                <label class="label text-[10px]">Tipo</label>
                <div class="flex gap-1.5 mt-1">
                    <button @click="payForm.status='paid'" class="s-btn flex-1" :class="payForm.status==='paid'?'bg-green-600 text-white':'bg-gray-100 text-gray-600'">Pago total</button>
                    <button @click="payForm.status='partial'" class="s-btn flex-1" :class="payForm.status==='partial'?'bg-blue-600 text-white':'bg-gray-100 text-gray-600'">Adelanto</button>
                </div>
            </div>
            <div><label class="label text-[10px]">Método</label>
                <select x-model="payForm.method" class="input text-sm mt-0.5"><option value="">—</option><template x-for="m in paymentMethods" :key="'pm'+m"><option :value="m" x-text="m"></option></template><option>Yape</option><option>Plin</option><option>Transferencia</option><option>Efectivo</option><option>Tarjeta</option></select>
            </div>
            {{-- Un adelanto SIN importe deja el pedido diciendo "cobre algo" sin
                 saber cuanto: el servidor ya lo rechaza, y aqui se avisa antes
                 de intentarlo. --}}
            <div>
                <label class="label text-[10px]">
                    Monto (S/)
                    <span x-show="payForm.status==='partial'" style="color:#b91c1c">·  obligatorio para un adelanto</span>
                </label>
                <input type="number" step="0.1" min="0" x-model="payForm.amount" class="input text-sm mt-0.5"
                       :required="payForm.status==='partial'">
                <p x-show="payForm.status==='paid' && !payForm.amount" x-cloak class="text-[10px] mt-1" style="color:#5b6270">
                    Sin monto se dará por cobrado el saldo pendiente.
                </p>
            </div>
            <div><label class="label text-[10px]">Código de operación (opcional)</label><input type="text" x-model="payForm.reference" class="input text-sm mt-0.5" placeholder="N° operación Yape/banco"></div>
            <div class="flex gap-2 pt-1">
                <button @click="payModal=false" class="s-btn flex-1 bg-gray-100 text-gray-600">Cancelar</button>
                <button @click="registerPay()" class="s-btn flex-1 bg-indigo-600 text-white"
                        :disabled="payForm.status==='partial' && !payForm.amount"
                        :style="(payForm.status==='partial' && !payForm.amount) ? 'opacity:.5;cursor:not-allowed' : ''">Guardar pago</button>
            </div>
        </div>
    </div>

    {{-- DETALLE PEDIDO --}}
    {{-- Antes era `selected && !creating`: el formulario de alta vivía en esta
         misma columna y competía por ella. Ya no existe aquí (es el Paso 4). --}}
    <template x-if="selected">
        <div class="max-w-2xl mx-auto space-y-4">

            {{-- Cabecera resumen: quién, cuánto y si ya pagó — sin scrollear --}}
            <div class="detail-section !mb-0">
                <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="ord-avatar w-11 h-11 text-base flex-shrink-0" :style="'background:'+avatarColor(selected.client_name)+';color:#fff'" x-text="(selected.client_name||'?')[0].toUpperCase()"></div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-black text-gray-900 truncate" x-text="selected.client_name"></h2>
                            <div class="flex items-center gap-2 flex-wrap mt-0.5">
                                <span class="text-xs font-mono text-gray-400" x-text="'Pedido #'+selected.id"></span>
                                <span x-show="selected.sales_channel" class="ch-tag" :class="chClass(selected.sales_channel)" x-text="chLabel(selected.sales_channel)"></span>
                                <span class="text-xs text-gray-400" x-text="selected.created_at"></span>
                            </div>
                            <template x-if="selected.client_phone">
                                <a :href="'https://wa.me/'+waPhone(selected)" target="_blank"
                                   class="text-xs text-green-600 hover:underline inline-flex items-center gap-1 mt-1">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                    <span x-text="selected.client_phone"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-2xl font-black text-gray-900" x-text="money(selected.total)"></div>
                        <span class="status-pill mt-1" :class="(selected.pill_pago||{}).cls" x-text="'Pago: '+((selected.pill_pago||{label:'Pendiente'}).label)"></span>
                    </div>
                </div>

                {{-- Barra de acciones: todo lo que se le hace a un pedido, en un solo sitio --}}
                <div class="px-4 py-2.5 border-t border-gray-100 bg-gray-50/60 flex items-center gap-2 flex-wrap">
@if ($puede['editar'] ?? false) {{-- registrar pago exige edicion --}}
                    <button x-show="isPendingPay(selected)"
                            @click="payForm={status:'paid',method:selected.payment_method||'',amount:selected.total,reference:''}; payModal=true"
                            class="s-btn bg-indigo-600 text-white">Registrar pago</button>
@endif
                    <div class="relative">
@if ($puede['editar'] ?? false) {{-- enviar WhatsApp exige edicion --}}
                        <button @click="waOpen=!waOpen" class="s-btn text-white" style="background:#25d366">WhatsApp ▾</button>
@endif
                        <div x-show="waOpen" x-cloak @click.outside="waOpen=false" class="absolute left-0 z-30 mt-1 w-56 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
                            <template x-for="tpl in waTemplates(selected)" :key="tpl.key">
                                <button @click="sendWa(selected, tpl)" class="w-full text-left px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-green-50" x-text="tpl.label"></button>
                            </template>
                        </div>
                    </div>
                    {{-- Antes vivían dentro de un desplegable "Exportar": nadie los
                         encontraba (ni el propio dueño de la tienda). Imprimir el
                         ticket y mandar la nota son acciones de todos los días, no
                         una exportación ocasional, así que van a la vista. Además
                         "Exportar" es lenguaje de sistema: el vendedor dice "le
                         mando la nota" o "imprimo el ticket". --}}
                    <span class="hidden sm:block w-px h-5 bg-gray-200" aria-hidden="true"></span>
                    {{-- Agrupados en rejilla propia: en movil salian 2+1 (dos en
                         una fila y el tercero suelto). Como grupo de 3 columnas
                         siempre quedan alineados; en sm+ vuelven al flujo flex. --}}
                    <div class="grid grid-cols-3 gap-1.5 w-full sm:flex sm:w-auto sm:gap-2">
                        <button onclick="exportOrderPDF()" title="Abrir la nota de pedido en A4 para imprimir o guardar en PDF"
                                class="s-btn bg-white text-gray-700 border border-gray-200 hover:bg-indigo-50 justify-center">Nota PDF</button>
                        <button onclick="exportOrderImg()" title="Guardar el pedido como imagen para enviarlo por WhatsApp"
                                class="s-btn bg-white text-gray-700 border border-gray-200 hover:bg-indigo-50 justify-center">Imagen</button>
                        <button onclick="printOrderTicket()" title="Imprimir en impresora térmica de 58 mm"
                                class="s-btn bg-white text-gray-700 border border-gray-200 hover:bg-indigo-50 justify-center">Ticket</button>
                    </div>
@if ($puede['eliminar'] ?? false) {{-- solo quien puede borrar ve el boton --}}
                    <button @click="del()" class="s-btn text-red-500 bg-white border border-red-100 hover:bg-red-50 ml-auto">Eliminar</button>
@endif
                </div>
            </div>

            {{-- Cambio de estado --}}
            <div class="detail-section">
                <div class="detail-section-header">Estado del pedido</div>

                {{-- Lavandería: estados del flujo + botón avanzar --}}
                <template x-if="conFlujo && lavStates.length">
                    <div class="p-3 space-y-2.5">
                        {{-- Mutar el estado operativo exige capacidad logistica.
                             Sin ella los estados se MUESTRAN pero no son
                             controles: nada de botones que acaben en 403. --}}
                        @if ($puede['logistica'] ?? false)
                        <div class="flex gap-1.5 flex-wrap">
                            <template x-for="st in lavStates" :key="st.key">
                                <button @click="changeLaundry(st.key)"
                                        :style="selected.laundry_status===st.key ? ('background:'+st.color+';color:#fff') : ''"
                                        :class="selected.laundry_status===st.key ? '' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                        class="s-btn text-xs transition">
                                    <span x-text="st.label"></span>
                                </button>
                            </template>
                        </div>
                        {{-- Botón avanzar al siguiente estado --}}
                        <template x-if="lavNext(selected)">
                            <button @click="advanceLaundry(selected)"
                                    class="w-full py-2 rounded-lg text-sm font-semibold text-white transition flex items-center justify-center gap-1.5"
                                    :style="'background:'+(lavNext(selected)?.color||'#4f46e5')">
                                Avanzar a <span x-text="lavNext(selected)?.label"></span> →
                            </button>
                        </template>
                        @else
                        <div class="flex gap-1.5 flex-wrap" role="list" aria-label="Estados del flujo operativo">
                            <template x-for="st in lavStates" :key="st.key">
                                <span role="listitem"
                                      :style="selected.laundry_status===st.key ? ('background:'+st.color+';color:#fff') : ''"
                                      :class="selected.laundry_status===st.key ? '' : 'bg-gray-100 text-gray-400'"
                                      class="s-btn text-xs" style="cursor:default">
                                    <span x-text="st.label"></span>
                                </span>
                            </template>
                        </div>
                        @endif
                        {{-- Semáforo de tiempo en estado actual --}}
                        <template x-if="selected.sla_level && selected.sla_level!=='ok'">
                            <div class="text-xs px-3 py-2 rounded-lg flex items-center gap-2"
                                 :style="'background:'+slaColor(selected)+'1a;color:'+slaColor(selected)">
                                <span class="w-2 h-2 rounded-full" :style="'background:'+slaColor(selected)"></span>
                                <span x-text="'Lleva '+selected.sla_minutes+' min en este estado'"></span>
                                <span x-show="selected.sla_level==='over'" class="font-bold">· ¡Atrasado!</span>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Otros rubros: estados genéricos --}}
                <template x-if="!conFlujo || !lavStates.length">
                    {{-- Progreso, no cinco botones grises indistinguibles: se
                         distingue lo ya recorrido, el estado actual y lo que
                         viene. Sin permiso de edicion es informacion, no
                         controles: nada parece pulsable. --}}
                    <div class="p-3">
                        <ol class="ped-steps" role="list">
                            <template x-for="(st, key) in statuses" :key="key">
                                <li class="ped-step" :class="claseEtapa(key)">
                                    @if ($puede['editar'] ?? false)
                                        <button @click="quickStatus(key)" class="ped-step-btn"
                                                :aria-current="selected.status===key ? 'step' : 'false'">
                                            <span class="ped-step-dot" aria-hidden="true">
                                                <svg x-show="claseEtapa(key)==='es-pasada'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                            </span>
                                            <span class="ped-step-lbl" x-text="st.label"></span>
                                        </button>
                                    @else
                                        <div class="ped-step-btn" :aria-current="selected.status===key ? 'step' : 'false'">
                                            <span class="ped-step-dot" aria-hidden="true">
                                                <svg x-show="claseEtapa(key)==='es-pasada'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                                            </span>
                                            <span class="ped-step-lbl" x-text="st.label"></span>
                                        </div>
                                    @endif
                                </li>
                            </template>
                        </ol>
                    </div>
                </template>
            </div>

            {{-- Productos --}}
            <div class="detail-section">
                <div class="detail-section-header">Productos</div>
                {{-- Origen: cotización que generó este pedido (F1c). Lee la FK
                     que F1b pobló; hasta ahora la trazabilidad existía en el
                     esquema pero no se veía en ninguna pantalla. --}}
                <template x-if="selected.quote_id">
                    <div style="display:flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;color:#6b7280;border-bottom:1px solid #f3f4f6">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                        </svg>
                        <span>Originada en</span>
                        <a :href="'{{ route('bixosales.cotizaciones.show', ['quote' => '__ID__']) }}'.replace('__ID__', selected.quote_id)"
                           style="color:#4338ca;font-weight:700;text-decoration:underline;text-underline-offset:2px;padding:13px 8px;margin:-13px -8px"
                           x-text="'COT-' + selected.quote_id"></a>
                    </div>
                </template>

                <template x-if="selected.items && selected.items.length > 0">
                    <div>
                        <template x-for="(it, i) in selected.items" :key="i">
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 last:border-0">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-gray-500" x-text="it.quantity+'×'"></span>
                                </div>
                                <p class="flex-1 text-sm text-gray-800" x-text="it.name"></p>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-semibold text-gray-900" x-text="money(odLineCents(it.price, it.quantity, it.discount))"></p>
                                    <p x-show="(parseFloat(it.discount)||0) > 0" class="text-[10px] text-emerald-600" x-text="'-'+it.discount+'% desc.'"></p>
                                    {{-- Eloquent entrega price como STRING (cast decimal:2), asi que
                                         .toFixed reventaba al refrescar por AJAX. Se usa la misma via
                                         entera que el subtotal de arriba: cantidad 1, descuento 0. --}}
                                    <p class="text-[10px] text-gray-400" x-text="money(odLineCents(it.price, 1, 0)) + ' c/u'"></p>
                                </div>
                            </div>
                        </template>
                        <div class="flex justify-between items-center px-4 py-3 bg-gray-50">
                            <span class="text-xs text-gray-500" x-text="(selected.items||[]).reduce((s,i)=>s+i.quantity,0)+' unidades'"></span>
                            <span class="text-lg font-black text-gray-900" x-text="money(selected.total)"></span>
                        </div>
                    </div>
                </template>
                <template x-if="!selected.items || selected.items.length === 0">
                    <div class="px-4 py-3 flex justify-between items-center">
                        <span class="text-sm text-gray-500" x-text="selected.items_count+' ítem(s)'"></span>
                        <span class="text-lg font-black text-gray-900" x-text="money(selected.total)"></span>
                    </div>
                </template>
            </div>

            {{-- Pago + Notas en grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="detail-section">
                    <div class="detail-section-header">Pago</div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase">Estado</span>
                            <span class="status-pill" :class="(selected.pill_pago||{}).cls" x-text="(selected.pill_pago||{label:'Pendiente'}).label"></span>
                        </div>
                        <div>
                            <label class="label text-[10px]">Método</label>
                            @if ($puede['editar'] ?? false)
                                <select x-model="form.payment_method" class="input text-sm mt-0.5">
                                    <option value="">—</option>
                                    <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                                </select>
                            @else
                                {{-- Solo lectura: un select editable aqui prometia
                                     una edicion que el servidor iba a negar. --}}
                                <p class="text-sm text-gray-700 mt-0.5" x-text="selected.payment_method || '—'"></p>
                            @endif
                        </div>
                        {{-- Historial de cobros: cada abono por separado, con su
                             metodo y su referencia. Antes dos pagos parciales
                             eran indistinguibles porque solo sobrevivia la suma
                             en una columna. Una reversion se muestra tachada,
                             no se oculta: el rastro de la correccion importa. --}}
                        <div x-show="cobros.length" x-cloak class="mt-1">
                            <label class="label text-[10px]">Cobros registrados</label>
                            <ul class="mt-1 space-y-1">
                                <template x-for="c in cobros" :key="'cb'+c.id">
                                    <li class="flex items-baseline justify-between gap-2 text-[11px] rounded-lg px-2 py-1"
                                        :style="c.reversion ? 'background:#fef2f2' : 'background:#f8f9fb'">
                                        <span class="min-w-0">
                                            <span class="font-bold" :style="c.reversion ? 'text-decoration:line-through;color:#b91c1c' : 'color:#111827'"
                                                  x-text="'S/ ' + c.importe"></span>
                                            <span class="text-gray-500" x-text="c.metodo ? ' · ' + c.metodo : ''"></span>
                                            <span class="text-gray-500" x-text="c.referencia ? ' · ' + c.referencia : ''"></span>
                                            <span x-show="c.reversion" class="block" style="color:#b91c1c"
                                                  x-text="'Anulado: ' + (c.motivo || 'sin motivo')"></span>
                                        </span>
                                        <span class="text-gray-500 flex-shrink-0" x-text="c.fecha"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <div x-show="selected.payment_proof" class="mt-1">
                            <label class="label text-[10px]">Comprobante</label>
                            <a :href="selected.payment_proof" target="_blank" class="block mt-1">
                                <img :src="selected.payment_proof" class="rounded-lg border border-gray-200 max-h-32 w-full object-contain hover:opacity-90 transition">
                            </a>
                        </div>
                        <div x-show="!selected.payment_proof" class="text-[10px] text-gray-400 bg-gray-50 rounded-lg p-2 text-center">Sin comprobante aún</div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-header">Historial</div>
                    <div class="p-3 max-h-44 overflow-y-auto space-y-2">
                        <template x-for="(ev,ix) in events" :key="ix">
                            <div class="text-[11px] leading-snug border-l-2 border-indigo-200 pl-2">
                                <div class="font-semibold text-gray-700" x-text="ev.label"></div>
                                <div class="text-gray-400" x-text="ev.user+' · '+ev.at"></div>
                            </div>
                        </template>
                        <div x-show="!events.length" class="text-[11px] text-gray-400 text-center py-2">Sin movimientos registrados aún</div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-header">Notas internas</div>
                    <div class="p-4">
                        <textarea x-model="form.notes" class="input resize-none text-sm w-full" rows="4" placeholder="Observaciones, instrucciones..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Tracking WhatsApp --}}
            <template x-if="selected.sales_channel === 'whatsapp'">
                <div class="detail-section border-green-200" style="border-color:#bbf7d0">
                    <div class="detail-section-header" style="background:#f0fdf4;color:#166534">WhatsApp — Seguimiento</div>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-600">+51 <span x-text="selected.wa_number"></span></p>
                            <span class="status-pill" :class="(waStatusLabel[selected.wa_status]||{}).color"
                                  x-text="(waStatusLabel[selected.wa_status]||{}).label||'—'"></span>
                        </div>
                        {{-- Steps --}}
                        <div class="flex items-center gap-1 overflow-x-auto py-1">
                            <template x-for="step in [
                                {key:'pending',label:'Recibido',icon:'1'},
                                {key:'pago_recibido',label:'Pago enviado',icon:'2'},
                                {key:'pago_confirmado',label:'Confirmado',icon:'3'},
                                {key:'en_camino',label:'En camino',icon:'4'},
                                {key:'entregado',label:'Entregado',icon:'5'},
                            ]" :key="step.key">
                                <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm border-2 transition"
                                         :class="['pending','pago_recibido','pago_confirmado','en_camino','entregado'].indexOf(selected.wa_status) >= ['pending','pago_recibido','pago_confirmado','en_camino','entregado'].indexOf(step.key) ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-200 text-gray-300'"
                                         x-text="step.icon"></div>
                                    <p class="text-[9px] text-gray-500 whitespace-nowrap" x-text="step.label"></p>
                                </div>
                                <div class="w-6 h-0.5 bg-gray-200 flex-shrink-0 mb-4" x-show="step.key !== 'entregado'"></div>
                            </template>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <template x-for="btn in waActionsFor(selected.wa_status)" :key="btn.key">
                                <button @click="waAction(btn.key)" :disabled="waActing" :class="btn.cls"
                                        class="text-xs px-4 py-2 rounded-lg font-semibold disabled:opacity-50 transition"
                                        x-text="waActing ? 'Enviando...' : btn.label"></button>
                            </template>
                            <button @click="refreshSelected()" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-50 transition">↻ Actualizar</button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Guardar --}}
            <div class="flex gap-3">
                <button @click="save()" :disabled="saving" class="btn-primary px-8 py-2.5 text-sm font-bold"
                        x-text="saving ? 'Guardando...' : 'Guardar cambios'"></button>
            </div>

        </div>
    </template>

    </div>
</aside>

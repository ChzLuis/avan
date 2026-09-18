@extends('crm::comunicaciones.layouts.app')
@section('pageTitle', 'Constructor de bot')
@section('content')

<script>
  window.__botFlow = {
    id: {{ $flow->id }},
    nombre: @json($flow->nombre),
    definicion: @json($flow->definicion ?? ['inicio'=>null,'bloques'=>[]]),
    saveUrl: @json(route('bixocrm.bots.save', $flow->id)),
    testUrl: @json(route('bixocrm.bots.test', $flow->id)),
    csrf: @json(csrf_token()),
  };
</script>

<div x-data="botEditor(window.__botFlow)" class="flex w-full" style="height:calc(100vh - 56px); background:#fff; position:relative;">

    {{-- ═══ PALETA DE BLOQUES (izquierda) ═══ --}}
    <div class="w-52 flex-shrink-0 border-r border-gray-200 bg-gray-50 overflow-y-auto p-3">
        <input x-model="nombre" @input.debounce.600ms="save()"
               class="w-full text-sm font-semibold border border-gray-200 rounded-lg px-2 py-1.5 mb-3"
               placeholder="Nombre del bot">
        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-2">Bloques</div>
        <template x-for="b in paleta" :key="b.tipo">
            <button @click="addBlock(b.tipo)"
                    class="w-full flex items-center gap-2 px-2.5 py-2 mb-1.5 bg-white border border-gray-200 rounded-lg text-left hover:border-indigo-400 hover:shadow-sm transition">
                <span class="w-6 h-6 rounded flex items-center justify-center text-xs flex-shrink-0" :style="`background:${b.color}22;color:${b.color}`" x-text="b.icon"></span>
                <span class="text-xs font-medium text-gray-700" x-text="b.label"></span>
            </button>
        </template>

        <div class="mt-4 pt-3 border-t border-gray-100">
            <button @click="openTest=true" class="w-full text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg py-2">▶ Probar bot</button>
            <button @click="abrirConexion()" class="w-full text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg py-2 mt-2 flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                Conectar WhatsApp
            </button>
            <button @click="openReglas=true" class="w-full text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg py-2 mt-2">⚙ Disparos y Reglas</button>
        </div>
    </div>

    {{-- ═══ CANVAS (centro) ═══ --}}
    <div class="relative overflow-hidden bg-gray-50 flex-1" style="min-width:0"
         x-ref="canvas" @mousemove="onMove($event)" @mouseup="onUp()"
         style="background-image:radial-gradient(#d1d5db 1px, transparent 1px); background-size:20px 20px;">

        <svg class="absolute inset-0 w-full h-full" style="pointer-events:none" x-html="edgesSvg()"></svg>

        <template x-for="(b, id) in bloques" :key="id">
            <div class="absolute group" :style="`left:${b.x||60}px; top:${b.y||60}px`">
                <div class="relative rounded-xl border-2 bg-white shadow-sm select-none"
                     :style="`width:180px; cursor:${dragId===id?'grabbing':'grab'}; border-color:${selected===id?'#6366f1':meta(b.tipo).color}`"
                     @mousedown="startDrag(id,$event)" @click="selected=id">
                    <div class="h-1 rounded-t-lg" :style="`background:${meta(b.tipo).color}`"></div>
                    <div class="px-2.5 py-2">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs" x-text="meta(b.tipo).icon"></span>
                            <span class="text-[11px] font-bold text-gray-700" x-text="meta(b.tipo).label"></span>
                        </div>
                        <div class="text-[11px] text-gray-500 mt-1 line-clamp-2" x-text="resumen(b)"></div>
                    </div>
                    {{-- punto de conexión (salida) --}}
                    <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 rounded-full bg-white border-2 border-indigo-500 cursor-crosshair opacity-0 group-hover:opacity-100"
                         @mousedown.stop="startLink(id,$event)" title="Conectar a otro bloque"></div>
                    {{-- punto de entrada --}}
                    <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-gray-300"></div>
                </div>
            </div>
        </template>

        <div class="absolute bottom-3 left-4 text-[11px] text-gray-400 bg-white/90 border border-gray-100 rounded px-2 py-1">
            Agrega bloques desde la izquierda · Arrastra para mover · Jala del punto derecho para conectar
        </div>
    </div>

    {{-- ═══ CONFIGURACIÓN (derecha) ═══ --}}
    <div class="w-72 flex-shrink-0 border-l border-gray-200 bg-white overflow-y-auto p-4" x-show="selected" x-cloak>
        <template x-if="selected && bloques[selected]">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-gray-500 uppercase" x-text="meta(bloques[selected].tipo).label"></span>
                    <button @click="delBlock(selected)" class="text-red-400 hover:text-red-600 text-xs">Eliminar</button>
                </div>

                {{-- Campos según tipo --}}
                <template x-if="['mensaje','pregunta','fin'].includes(bloques[selected].tipo)">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">Texto</label>
                        <textarea x-model="bloques[selected].texto" @input.debounce.600ms="save()" rows="3"
                                  class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5"></textarea>
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='pregunta'">
                    <div class="mt-3">
                        <label class="text-xs font-semibold text-gray-600">Guardar respuesta en variable</label>
                        <input x-model="bloques[selected].guardar_en" @input.debounce.600ms="save()"
                               placeholder="ej: nombre" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='buscar_producto'">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">Qué buscar</label>
                        <input x-model="bloques[selected].consulta" @input.debounce.600ms="save()"
                               placeholder="variable entre llaves o texto fijo" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Encabezado</label>
                        <input x-model="bloques[selected].encabezado" @input.debounce.600ms="save()"
                               class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='ia'">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">Instrucción para la IA</label>
                        <textarea x-model="bloques[selected].instruccion" @input.debounce.600ms="save()" rows="4"
                                  class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5"
                                  placeholder="Ej: Responde como vendedor de la ferretería usando el catálogo."></textarea>
                        <p class="text-[11px] text-gray-400 mt-1">La IA responde con el catálogo, cliente y datos reales del proyecto.</p>
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='opciones'">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">Texto del menú</label>
                        <input x-model="bloques[selected].texto" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Opciones</label>
                        <template x-for="(op,i) in (bloques[selected].opciones||[])" :key="i">
                            <div class="flex gap-1 mt-1">
                                <input x-model="op.texto" @input.debounce.600ms="save()" class="flex-1 text-sm border border-gray-200 rounded px-2 py-1">
                                <button @click="bloques[selected].opciones.splice(i,1);save()" class="text-red-400 text-xs px-1">✕</button>
                            </div>
                        </template>
                        <button @click="addOpcion()" class="text-xs text-indigo-600 mt-1.5">+ Agregar opción</button>
                    </div>
                </template>

                {{-- ── Enviar Lista (menú desplegable nativo de WhatsApp) ── --}}
                <template x-if="bloques[selected].tipo==='lista'">
                    <div>
                        <div class="text-[11px] bg-cyan-50 text-cyan-700 rounded-lg px-2 py-1.5 mb-2">📋 Lista desplegable de WhatsApp (el cliente toca "Ver opciones").</div>
                        <label class="text-xs font-semibold text-gray-600">Título</label>
                        <input x-model="bloques[selected].titulo" @input.debounce.600ms="save()" placeholder="Ej: Nuestro menú" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-2 block">Mensaje (cuerpo)</label>
                        <textarea x-model="bloques[selected].texto" @input.debounce.600ms="save()" rows="2" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5"></textarea>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div>
                                <label class="text-xs font-semibold text-gray-600">Texto del botón</label>
                                <input x-model="bloques[selected].boton" @input.debounce.600ms="save()" placeholder="Ver opciones" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600">Pie (opcional)</label>
                                <input x-model="bloques[selected].pie" @input.debounce.600ms="save()" placeholder="Ej: Entrega hasta 22h" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                            </div>
                        </div>

                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Secciones y opciones</label>
                        <template x-for="(sec,si) in (bloques[selected].secciones||[])" :key="si">
                            <div class="border border-gray-100 rounded-lg p-2 mt-1.5 bg-gray-50">
                                <div class="flex gap-1 items-center">
                                    <input x-model="sec.titulo" @input.debounce.600ms="save()" placeholder="Nombre de la sección" class="flex-1 text-xs font-semibold border border-gray-200 rounded px-2 py-1">
                                    <button @click="bloques[selected].secciones.splice(si,1);save()" class="text-red-400 text-[11px] px-1">✕ sección</button>
                                </div>
                                <template x-for="(fila,fi) in (sec.filas||[])" :key="fi">
                                    <div class="flex gap-1 mt-1 pl-2">
                                        <input x-model="fila.titulo" @input.debounce.600ms="save()" placeholder="Opción" class="flex-1 text-xs border border-gray-200 rounded px-2 py-1">
                                        <input x-model="fila.descripcion" @input.debounce.600ms="save()" placeholder="Descripción (opc.)" class="flex-1 text-xs border border-gray-200 rounded px-2 py-1">
                                        <button @click="sec.filas.splice(fi,1);save()" class="text-red-400 text-xs px-1">✕</button>
                                    </div>
                                </template>
                                <button @click="if(!sec.filas)sec.filas=[]; sec.filas.push({titulo:'Nueva opción',descripcion:'',siguiente:null}); save()" class="text-[11px] text-cyan-600 mt-1 ml-2">+ opción</button>
                            </div>
                        </template>
                        <button @click="bloques[selected].secciones.push({titulo:'Sección',filas:[{titulo:'Opción',descripcion:'',siguiente:null}]});save()" class="text-xs text-cyan-600 mt-2">+ Agregar sección</button>
                        <p class="text-[11px] text-gray-400 mt-2">Cada opción se conecta a un bloque arrastrando desde el nodo (igual que el menú).</p>
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='condicion'">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">Reglas (si el mensaje contiene…)</label>
                        <template x-for="(rg,i) in (bloques[selected].reglas||[])" :key="i">
                            <div class="mt-1 border border-gray-100 rounded-lg p-2">
                                <input x-model="rg.contiene" @input.debounce.600ms="save()" placeholder="palabras, separadas, por comas"
                                       class="w-full text-sm border border-gray-200 rounded px-2 py-1">
                                <button @click="bloques[selected].reglas.splice(i,1);save()" class="text-red-400 text-[11px] mt-1">quitar regla</button>
                            </div>
                        </template>
                        <button @click="addRegla()" class="text-xs text-indigo-600 mt-1.5">+ Agregar regla</button>
                        <p class="text-[11px] text-gray-400 mt-2">Cada regla se conecta a un bloque distinto arrastrando desde el nodo.</p>
                    </div>
                </template>

                <template x-if="['imagen','archivo'].includes(bloques[selected].tipo)">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">URL del archivo</label>
                        <input x-model="bloques[selected].url" @input.debounce.600ms="save()" placeholder="https://…"
                               class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <template x-if="bloques[selected].tipo==='imagen'">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mt-3 block">Texto (caption)</label>
                                <input x-model="bloques[selected].caption" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='webhook'">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">URL del webhook</label>
                        <input x-model="bloques[selected].url" @input.debounce.600ms="save()" placeholder="https://…"
                               class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Método</label>
                        <select x-model="bloques[selected].metodo" @change="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                            <option value="post">POST</option>
                            <option value="get">GET</option>
                        </select>
                        <label class="flex items-center gap-2 mt-3 text-xs text-gray-600">
                            <input type="checkbox" x-model="bloques[selected].responder_body" @change="save()">
                            Responder al cliente con lo que devuelva
                        </label>
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='espera'">
                    <div>
                        <label class="text-xs font-semibold text-gray-600">Mensaje mientras espera (opcional)</label>
                        <input x-model="bloques[selected].texto" @input.debounce.600ms="save()" placeholder="Un momento…"
                               class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>

                {{-- ── Bloques con datos reales ── --}}
                <template x-if="bloques[selected].tipo==='catalogo'">
                    <div>
                        <div class="text-[11px] bg-emerald-50 text-emerald-700 rounded-lg px-2 py-1.5 mb-2">📋 Envía tu catálogo real (productos y servicios con precio).</div>
                        <label class="text-xs font-semibold text-gray-600">Encabezado</label>
                        <input x-model="bloques[selected].encabezado" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Máximo de items</label>
                        <input type="number" min="1" max="50" x-model.number="bloques[selected].limite" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Si no hay productos</label>
                        <input x-model="bloques[selected].vacio" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='estado_pedido'">
                    <div>
                        <div class="text-[11px] bg-cyan-50 text-cyan-700 rounded-lg px-2 py-1.5 mb-2">📦 Consulta el último pedido del cliente por su número.</div>
                        <label class="text-xs font-semibold text-gray-600">Si no tiene pedidos</label>
                        <input x-model="bloques[selected].sin_pedido" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='cotizar'">
                    <div>
                        <div class="text-[11px] bg-yellow-50 text-yellow-700 rounded-lg px-2 py-1.5 mb-2">🧾 Cotiza automáticamente con precios reales del catálogo.</div>
                        <label class="text-xs font-semibold text-gray-600">Qué cotizar</label>
                        <input x-model="bloques[selected].consulta" @input.debounce.600ms="save()" placeholder="variable entre llaves o texto fijo" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Si no encuentra</label>
                        <input x-model="bloques[selected].no_encontrado" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='registrar_crm'">
                    <div>
                        <div class="text-[11px] bg-fuchsia-50 text-fuchsia-700 rounded-lg px-2 py-1.5 mb-2">🎯 Marca al cliente en tu CRM (etapa y/o etiqueta).</div>
                        <label class="text-xs font-semibold text-gray-600">Mover a etapa</label>
                        <select x-model="bloques[selected].etapa" @change="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                            <option value="">— sin cambio —</option>
                            <option value="prospecto">Prospecto</option>
                            <option value="contactado">Contactado</option>
                            <option value="cotizado">Cotizado</option>
                            <option value="negociacion">Negociación</option>
                            <option value="ganado">Ganado</option>
                            <option value="perdido">Perdido</option>
                        </select>
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Agregar etiqueta</label>
                        <input x-model="bloques[selected].etiqueta" @input.debounce.600ms="save()" placeholder="ej: interesado, VIP" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Mensaje al cliente (opcional)</label>
                        <input x-model="bloques[selected].texto" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>

                <template x-if="bloques[selected].tipo==='agendar'">
                    <div>
                        <div class="text-[11px] bg-orange-50 text-orange-700 rounded-lg px-2 py-1.5 mb-2">📅 Registra una cita/seguimiento en la ficha del cliente.</div>
                        <label class="text-xs font-semibold text-gray-600">Nota del seguimiento</label>
                        <input x-model="bloques[selected].nota" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                        <label class="text-xs font-semibold text-gray-600 mt-3 block">Mensaje al cliente</label>
                        <input x-model="bloques[selected].texto" @input.debounce.600ms="save()" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- ═══ CONECTAR WHATSAPP (modal QR) ═══ --}}
    <div x-show="openConexion" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="cerrarConexion()">
        <div class="bg-white rounded-2xl shadow-xl w-80 p-6 text-center">
            <div class="flex items-center justify-between mb-3">
                <span class="font-bold text-gray-800">Conectar WhatsApp</span>
                <button @click="cerrarConexion()" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            {{-- Conectado --}}
            <template x-if="waStatus==='connected'">
                <div class="py-6">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="font-bold text-green-700">WhatsApp conectado ✅</p>
                    <p class="text-xs text-gray-400 mt-1">El bot ya está atendiendo.</p>
                </div>
            </template>

            {{-- QR listo --}}
            <template x-if="waStatus==='qr' && waQr">
                <div>
                    <p class="text-xs text-gray-500 mb-2">WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
                    <img :src="waQr" class="w-56 h-56 mx-auto rounded-xl border border-gray-200">
                    <p class="text-[11px] text-gray-400 mt-2">Escanea con el teléfono del bot</p>
                </div>
            </template>

            {{-- Esperando / offline --}}
            <template x-if="waStatus!=='connected' && !(waStatus==='qr' && waQr)">
                <div class="py-8">
                    <svg class="w-8 h-8 text-gray-300 mx-auto animate-spin mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    <p class="text-sm text-gray-500" x-text="waStatus==='offline' ? 'Bot no conectado. Generando QR…' : 'Preparando conexión…'"></p>
                    <p class="text-[11px] text-gray-400 mt-1">Si tarda, verifica que el conector esté corriendo.</p>
                </div>
            </template>
        </div>
    </div>

    {{-- ═══ SIMULADOR DE CHAT (modal) ═══ --}}
    <div x-show="openTest" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="openTest=false">
        <div class="bg-white rounded-2xl shadow-xl w-96 flex flex-col" style="height:70vh">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <span class="font-semibold text-gray-700">Probar bot</span>
                <div class="flex gap-2">
                    <button @click="resetChat()" class="text-xs text-gray-500 hover:text-gray-700">Reiniciar</button>
                    <button @click="openTest=false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50" x-ref="chatBox">
                <template x-for="(m,i) in chat" :key="i">
                    <div :class="m.from==='bot' ? 'flex' : 'flex justify-end'">
                        <div :class="m.from==='bot' ? 'bg-white border' : 'bg-emerald-500 text-white'"
                             class="max-w-[75%] rounded-2xl px-3 py-2 text-sm whitespace-pre-wrap" x-text="m.texto"></div>
                    </div>
                </template>
                <div x-show="thinking" class="text-xs text-gray-400">escribiendo…</div>
            </div>
            <div class="p-2 border-t flex gap-2">
                <input x-model="chatInput" @keydown.enter="sendTest()" placeholder="Escribe un mensaje…"
                       class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <button @click="sendTest()" class="bg-emerald-600 text-white rounded-lg px-3 text-sm">Enviar</button>
            </div>
        </div>
    </div>

    {{-- ═══ DISPAROS Y REGLAS (modal, nivel flujo) ═══ --}}
    <div x-show="openReglas" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="openReglas=false">
        <div class="bg-white rounded-2xl shadow-xl w-[520px] max-h-[85vh] overflow-y-auto">
            <div class="px-5 py-3 border-b flex items-center justify-between sticky top-0 bg-white">
                <span class="font-semibold text-gray-700">⚙ Disparos y Reglas</span>
                <button @click="openReglas=false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="p-5 space-y-6">
                {{-- DISPAROS --}}
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-bold text-gray-800">🎯 Disparos</span>
                    </div>
                    <p class="text-[11px] text-gray-500 mb-3">El bot solo arranca cuando el mensaje del cliente contiene alguna de estas palabras. Si no hay disparos, responde a todo.</p>
                    <template x-for="(d,i) in disparos" :key="i">
                        <div class="flex gap-1 mt-1.5">
                            <input x-model="d.palabras_txt" @input.debounce.500ms="save()" placeholder="precio, catálogo, cuánto cuesta"
                                   class="flex-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                            <button @click="disparos.splice(i,1);save()" class="text-red-400 text-xs px-1">✕</button>
                        </div>
                    </template>
                    <button @click="disparos.push({palabras_txt:''});save()" class="text-xs text-indigo-600 mt-2">+ Agregar disparo</button>
                </div>

                {{-- REGLAS --}}
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-bold text-gray-800">🛡️ Reglas (cuándo NO responder)</span>
                    </div>
                    <p class="text-[11px] text-gray-500 mb-3">Usan tu CRM real: el bot se calla si el cliente ya tiene un vendedor, está en cierta etapa o fuera de horario.</p>

                    <template x-for="(rg,i) in reglas" :key="i">
                        <div class="mt-1.5 border border-gray-100 rounded-lg p-2.5 bg-gray-50">
                            <div class="flex items-center gap-2">
                                <select x-model="rg.tipo" @change="save()" class="flex-1 text-sm border border-gray-200 rounded px-2 py-1.5 bg-white">
                                    <option value="tiene_vendedor">No responder si tiene vendedor asignado</option>
                                    <option value="etapa">No responder si está en etapa…</option>
                                    <option value="horario">Solo responder en horario…</option>
                                </select>
                                <button @click="reglas.splice(i,1);save()" class="text-red-400 text-xs px-1">✕</button>
                            </div>
                            <template x-if="rg.tipo==='etapa'">
                                <select x-model="rg.valor" @change="save()" class="w-full mt-2 text-sm border border-gray-200 rounded px-2 py-1.5 bg-white">
                                    <option value="ganado">Ganado</option>
                                    <option value="perdido">Perdido</option>
                                    <option value="negociacion">Negociación</option>
                                    <option value="cotizado">Cotizado</option>
                                </select>
                            </template>
                            <template x-if="rg.tipo==='horario'">
                                <div class="flex items-center gap-2 mt-2 text-xs text-gray-600">
                                    <span>De</span>
                                    <input type="number" min="0" max="23" x-model.number="rg.desde" @input.debounce.500ms="save()" class="w-16 border border-gray-200 rounded px-2 py-1 bg-white">
                                    <span>a</span>
                                    <input type="number" min="0" max="24" x-model.number="rg.hasta" @input.debounce.500ms="save()" class="w-16 border border-gray-200 rounded px-2 py-1 bg-white">
                                    <span>hrs</span>
                                </div>
                            </template>
                        </div>
                    </template>
                    <button @click="reglas.push({tipo:'tiene_vendedor'});save()" class="text-xs text-indigo-600 mt-2">+ Agregar regla</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function botEditor(cfg){
  return {
    id: cfg.id, nombre: cfg.nombre,
    bloques: cfg.definicion.bloques || {},
    inicio: cfg.definicion.inicio || null,
    selected: null, dragId: null, dragOff:{x:0,y:0},
    linking:false, linkFrom:null, mouse:{x:0,y:0},
    openTest:false, chat:[], chatInput:'', chatEstado:null, thinking:false,
    openReglas:false,
    openConexion:false, waStatus:'', waQr:null, _waTimer:null,
    disparos: (cfg.definicion.disparos||[]).map(d=>({palabras_txt:(d.palabras||[]).join(', ')})),
    reglas: cfg.definicion.reglas || [],
    _timer:null,

    paleta:[
      {tipo:'mensaje',label:'Mensaje',icon:'💬',color:'#3b82f6'},
      {tipo:'pregunta',label:'Pregunta',icon:'❓',color:'#8b5cf6'},
      {tipo:'opciones',label:'Menú',icon:'☰',color:'#0ea5e9'},
      {tipo:'lista',label:'Enviar Lista',icon:'📋',color:'#06b6d4'},
      {tipo:'categorias',label:'Categorías',icon:'🛍️',color:'#8b5cf6'},
      {tipo:'buscar_agregar',label:'Buscar + Agregar',icon:'🔎',color:'#f59e0b'},
      {tipo:'ver_carrito',label:'Ver Carrito',icon:'🛒',color:'#16a34a'},
      {tipo:'pago_qr',label:'Pago QR (Yape/Plin)',icon:'📲',color:'#7c3aed'},
      {tipo:'buscar_producto',label:'Buscar producto',icon:'🔍',color:'#f59e0b'},
      {tipo:'ia',label:'Respuesta IA',icon:'✨',color:'#ec4899'},
      {tipo:'condicion',label:'Condición',icon:'🔀',color:'#ef4444'},
      {tipo:'imagen',label:'Imagen',icon:'🖼️',color:'#10b981'},
      {tipo:'archivo',label:'Archivo',icon:'📎',color:'#14b8a6'},
      {tipo:'webhook',label:'WebHook',icon:'🔗',color:'#6366f1'},
      {tipo:'espera',label:'Espera',icon:'⏳',color:'#a855f7'},
      // ── Bloques con datos reales (el diferenciador de BIXO) ──
      {tipo:'catalogo',label:'Catálogo real',icon:'📋',color:'#22c55e'},
      {tipo:'estado_pedido',label:'Estado pedido',icon:'📦',color:'#0891b2'},
      {tipo:'cotizar',label:'Cotizar auto',icon:'🧾',color:'#eab308'},
      {tipo:'registrar_crm',label:'Registrar en CRM',icon:'🎯',color:'#d946ef'},
      {tipo:'agendar',label:'Agendar',icon:'📅',color:'#f97316'},
      {tipo:'fin',label:'Fin',icon:'■',color:'#64748b'},
    ],
    meta(t){ return this.paleta.find(p=>p.tipo===t) || {label:t,icon:'?',color:'#999'}; },
    resumen(b){
      if(b.tipo==='buscar_producto') return 'Busca: '+(b.consulta||'—');
      if(b.tipo==='ia') return b.instruccion ? b.instruccion.slice(0,50) : 'Responde con IA';
      if(b.tipo==='opciones') return (b.opciones||[]).length+' opciones';
      if(b.tipo==='lista') return 'Lista: '+((b.secciones||[]).reduce((n,s)=>n+(s.filas||[]).length,0))+' opciones';
      if(b.tipo==='categorias') return 'Muestra categorías del catálogo';
      if(b.tipo==='buscar_agregar') return 'Busca y agrega al carrito';
      if(b.tipo==='ver_carrito') return 'Ver carrito + finalizar';
      if(b.tipo==='pago_qr') return 'Envía QR Yape/Plin y espera comprobante';
      if(b.tipo==='condicion') return (b.reglas||[]).length+' reglas';
      if(b.tipo==='imagen') return b.url ? 'Imagen' : 'Sin URL';
      if(b.tipo==='archivo') return b.url ? 'Archivo' : 'Sin URL';
      if(b.tipo==='webhook') return b.url || 'Sin URL';
      if(b.tipo==='espera') return b.texto || 'Pausa';
      if(b.tipo==='catalogo') return 'Envía catálogo real';
      if(b.tipo==='estado_pedido') return 'Consulta pedido del cliente';
      if(b.tipo==='cotizar') return 'Cotiza: '+(b.consulta||('{'+'{mensaje}'+'}'));
      if(b.tipo==='registrar_crm') return 'CRM: '+[b.etapa,b.etiqueta].filter(Boolean).join(' / ')||'marca lead';
      if(b.tipo==='agendar') return b.nota || 'Agenda seguimiento';
      return b.texto || '—';
    },

    newId(){ let i=1; while(this.bloques['b'+i]) i++; return 'b'+i; },
    addBlock(tipo){
      const id=this.newId();
      const base={tipo, x:80+Object.keys(this.bloques).length*30%300, y:80+Object.keys(this.bloques).length*30%300, siguiente:null};
      if(tipo==='mensaje'||tipo==='fin') base.texto='Escribe aquí…';
      if(tipo==='pregunta'){ base.texto='¿Tu pregunta?'; base.guardar_en=''; }
      if(tipo==='opciones'){ base.texto='Elige:'; base.opciones=[{texto:'Opción 1',siguiente:null}]; }
      if(tipo==='lista'){ base.titulo='Nuestro menú'; base.texto='Elige una opción:'; base.pie=''; base.boton='Ver opciones'; base.secciones=[{titulo:'Opciones',filas:[{titulo:'Opción 1',descripcion:'',siguiente:null}]}]; }
      if(tipo==='categorias'){ base.titulo='🛍️ Categorías'; base.texto='Elige una categoría:'; }
      if(tipo==='buscar_agregar'){ base.consulta='{'+'{mensaje}'+'}'; }
      if(tipo==='ver_carrito'){ base.envio=0; }
      if(tipo==='pago_qr'){ base.texto='📲 *Pago por Yape / Plin*'; base.pedir_comprobante='📸 Cuando pagues, *envíame la captura del comprobante* para validar tu pedido.'; base.recibido='✅ ¡Gracias! Recibimos tu comprobante. Un asesor validará el pago.'; }
      if(tipo==='buscar_producto'){ base.consulta='{'+'{busqueda}'+'}'; base.encabezado='Esto encontré:'; }
      if(tipo==='ia'){ base.instruccion='Responde como asistente de ventas usando el catálogo del negocio.'; }
      if(tipo==='condicion'){ base.reglas=[{contiene:'',siguiente:null}]; base.si_no=null; }
      if(tipo==='imagen'){ base.url=''; base.caption=''; }
      if(tipo==='archivo'){ base.url=''; base.nombre='documento'; }
      if(tipo==='webhook'){ base.url=''; base.metodo='post'; base.responder_body=false; }
      if(tipo==='espera'){ base.texto=''; }
      if(tipo==='catalogo'){ base.encabezado='📋 *Nuestro catálogo:*'; base.limite=15; base.vacio='Aún no tenemos productos publicados.'; }
      if(tipo==='estado_pedido'){ base.sin_pedido='No encontré pedidos asociados a tu número.'; }
      if(tipo==='cotizar'){ base.consulta='{'+'{mensaje}'+'}'; base.no_encontrado='No encontré eso para cotizar.'; }
      if(tipo==='registrar_crm'){ base.etapa=''; base.etiqueta=''; base.texto=''; }
      if(tipo==='agendar'){ base.nota='Cita solicitada por bot'; base.texto='✅ ¡Listo! Registré tu solicitud, te contactaremos pronto.'; }
      this.bloques[id]=base;
      if(!this.inicio) this.inicio=id;
      this.selected=id; this.save();
    },
    delBlock(id){
      delete this.bloques[id];
      Object.values(this.bloques).forEach(b=>{ if(b.siguiente===id) b.siguiente=null; (b.opciones||[]).forEach(o=>{if(o.siguiente===id)o.siguiente=null;}); });
      if(this.inicio===id) this.inicio=Object.keys(this.bloques)[0]||null;
      this.selected=null; this.save();
    },
    addOpcion(){ this.bloques[this.selected].opciones.push({texto:'Nueva opción',siguiente:null}); this.save(); },
    addRegla(){ if(!this.bloques[this.selected].reglas) this.bloques[this.selected].reglas=[]; this.bloques[this.selected].reglas.push({contiene:'',siguiente:null}); this.save(); },

    // arrastrar
    startDrag(id,e){ this.dragId=id; const b=this.bloques[id]; const p=this.pt(e); this.dragOff={x:p.x-(b.x||0),y:p.y-(b.y||0)}; },
    startLink(id,e){ this.linking=true; this.linkFrom=id; },
    onMove(e){ const p=this.pt(e); this.mouse=p; if(this.dragId){ this.bloques[this.dragId].x=Math.max(0,p.x-this.dragOff.x); this.bloques[this.dragId].y=Math.max(0,p.y-this.dragOff.y); } },
    onUp(){
      if(this.linking && this.linkFrom){
        const target=this.blockAt(this.mouse);
        if(target && target!==this.linkFrom) this.bloques[this.linkFrom].siguiente=target;
      }
      if(this.dragId) this.save();
      this.dragId=null; this.linking=false; this.linkFrom=null;
    },
    pt(e){ const r=this.$refs.canvas.getBoundingClientRect(); return {x:e.clientX-r.left, y:e.clientY-r.top}; },
    blockAt(p){ for(const[id,b]of Object.entries(this.bloques)){ const x=b.x||60,y=b.y||60; if(p.x>=x&&p.x<=x+180&&p.y>=y&&p.y<=y+70) return id; } return null; },

    // conexiones SVG
    edgesSvg(){
      let s='<defs><marker id="ar" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L6,3 L0,6 Z" fill="#94a3b8"/></marker></defs>';
      for(const[id,b]of Object.entries(this.bloques)){
        const conns=[]; if(b.siguiente) conns.push(b.siguiente); (b.opciones||[]).forEach(o=>{if(o.siguiente)conns.push(o.siguiente);});
        for(const to of conns){ const t=this.bloques[to]; if(!t) continue;
          const x1=(b.x||60)+180, y1=(b.y||60)+35, x2=(t.x||60), y2=(t.y||60)+35;
          const mx=(x1+x2)/2;
          s+=`<path d="M${x1},${y1} C${mx},${y1} ${mx},${y2} ${x2},${y2}" stroke="#94a3b8" stroke-width="2" fill="none" marker-end="url(#ar)"/>`;
        }
      }
      return s;
    },

    // guardar
    save(){
      clearTimeout(this._timer);
      this._timer=setTimeout(()=>{
        fetch(cfg.saveUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':cfg.csrf},
          body:JSON.stringify({nombre:this.nombre, definicion:{inicio:this.inicio,bloques:this.bloques,
            disparos:this.disparos.map(d=>({palabras:(d.palabras_txt||'').split(',').map(s=>s.trim()).filter(Boolean)})).filter(d=>d.palabras.length),
            reglas:this.reglas}})});
      },300);
    },

    // simulador
    // ── Conectar WhatsApp (QR con polling) ──
    abrirConexion(){
      this.openConexion=true; this.waStatus=''; this.waQr=null;
      this.pollWa();
      this._waTimer=setInterval(()=>this.pollWa(), 3000);
    },
    cerrarConexion(){ this.openConexion=false; clearInterval(this._waTimer); },
    async pollWa(){
      try{
        const r=await fetch('{{ route("bixocrm.bots.wa.status") }}',{headers:{'Accept':'application/json'}});
        const d=await r.json();
        this.waStatus=d.status||'offline'; this.waQr=d.qr||null;
        if(d.status==='connected'){ clearInterval(this._waTimer); }
      }catch(e){ this.waStatus='offline'; }
    },
    resetChat(){ this.chat=[]; this.chatEstado=null; },
    async sendTest(){
      const msg=this.chatInput.trim(); if(!msg) return;
      this.chat.push({from:'user',texto:msg}); this.chatInput=''; this.thinking=true;
      this.$nextTick(()=>this.$refs.chatBox.scrollTop=9e9);
      try{
        const r=await fetch(cfg.testUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':cfg.csrf},
          body:JSON.stringify({mensaje:msg, estado:this.chatEstado})});
        const d=await r.json();
        (d.respuestas||[]).forEach(t=>{
          const texto = (typeof t==='object' && t!==null) ? (t.fallback||t.cuerpo||t.caption||'[lista]') : t;
          this.chat.push({from:'bot',texto});
        });
        this.chatEstado=d.estado;
      }catch(e){ this.chat.push({from:'bot',texto:'Error: '+e.message}); }
      this.thinking=false; this.$nextTick(()=>this.$refs.chatBox.scrollTop=9e9);
    },
  };
}
</script>
@endsection

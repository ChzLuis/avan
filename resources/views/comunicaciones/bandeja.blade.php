@extends('comunicaciones.layouts.app')
@section('pageTitle', 'Bandeja')
@section('content')

@php
$estadoColores = [
    'nuevo'       => ['bg'=>'#dcfce7','text'=>'#166534','label'=>'Nuevo'],
    'contactado'  => ['bg'=>'#dbeafe','text'=>'#1e40af','label'=>'Contactado'],
    'demo_enviada'=> ['bg'=>'#fef3c7','text'=>'#92400e','label'=>'Demo enviada'],
    'propuesta'   => ['bg'=>'#ede9fe','text'=>'#5b21b6','label'=>'Propuesta'],
    'cerrado'     => ['bg'=>'#d1fae5','text'=>'#065f46','label'=>'Cerrado'],
    'perdido'     => ['bg'=>'#fee2e2','text'=>'#991b1b','label'=>'Perdido'],
    'academia'    => ['bg'=>'#e0f2fe','text'=>'#0c4a6e','label'=>'Academia'],
];
@endphp

<div class="flex h-full w-full overflow-hidden"
     x-data="bandeja()" x-init="init()"
     style="height:100%">

{{-- ══════════════════════
     COLUMNA IZQUIERDA — Bandeja
══════════════════════ --}}
<div class="flex flex-col bg-white border-r border-gray-200 flex-shrink-0" style="width:300px;">

    {{-- Header bandeja --}}
    <div class="px-3 py-3 border-b border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-sm font-bold text-gray-900">Bandeja</h2>
            <span class="text-xs bg-green-100 text-green-700 font-black px-1.5 py-0.5 rounded-full"
                  x-show="totalNoLeidos > 0" x-text="totalNoLeidos"></span>
        </div>

        {{-- Buscador --}}
        <div class="relative mb-2">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <input type="text" x-model="busqueda" placeholder="Buscar nombre o teléfono..."
                   class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg bg-gray-50 focus:outline-none focus:ring-1 focus:ring-green-400">
        </div>

        {{-- Filtros --}}
        <div class="flex gap-1 flex-wrap">
            @foreach([['todos','Todos'],['sin_leer','Sin leer']] as [$val,$lbl])
            <button @click="filtroEstado='{{ $val }}'"
                    :class="filtroEstado==='{{ $val }}' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    class="px-2 py-0.5 text-[10px] font-semibold rounded-full transition-colors">{{ $lbl }}</button>
            @endforeach
            @foreach($canales as $c)
            <button @click="filtroCanal='{{ $c->tipo }}'"
                    :class="filtroCanal==='{{ $c->tipo }}' ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    :style="filtroCanal==='{{ $c->tipo }}' ? 'background:{{ $c->color }}' : ''"
                    class="px-2 py-0.5 text-[10px] font-semibold rounded-full transition-colors">{{ $c->nombre }}</button>
            @endforeach
        </div>
    </div>

    {{-- Métricas rápidas --}}
    <div class="grid grid-cols-3 gap-0 border-b border-gray-100 text-center">
        <div class="py-2 border-r border-gray-100">
            <p class="text-lg font-black text-gray-900">{{ $metricas['total_hoy'] }}</p>
            <p class="text-[9px] text-gray-400 uppercase font-medium">Hoy</p>
        </div>
        <div class="py-2 border-r border-gray-100">
            <p class="text-lg font-black text-red-600">{{ $metricas['sin_leer'] }}</p>
            <p class="text-[9px] text-gray-400 uppercase font-medium">Sin leer</p>
        </div>
        <div class="py-2">
            <p class="text-lg font-black text-green-600">{{ $metricas['cerrados'] }}</p>
            <p class="text-[9px] text-gray-400 uppercase font-medium">Cerrados</p>
        </div>
    </div>

    {{-- Lista de conversaciones --}}
    <div class="flex-1 overflow-y-auto">
        <template x-for="conv in conversacionesFiltradas" :key="conv.id">
            <div @click="abrirConversacion(conv)"
                 :class="convActiva?.id === conv.id ? 'border-l-2 border-green-500' : 'border-l-2 border-transparent hover:bg-gray-50'"
                 :style="convActiva?.id === conv.id ? 'background:#0d2a1a10' : ''"
                 class="px-3 py-2.5 cursor-pointer transition-colors border-b border-gray-50">
                <div class="flex items-start gap-2">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                         :style="`background:${conv.canal_color}`"
                         x-text="(conv.cliente_nombre || conv.cliente_telefono).charAt(0).toUpperCase()"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-semibold text-gray-900 truncate"
                                  x-text="conv.cliente_nombre || conv.cliente_telefono"></span>
                            <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="conv.tiempo"></span>
                        </div>
                        <p class="text-[11px] text-gray-500 truncate mt-0.5"
                           x-text="conv.ultimo_mensaje || 'Sin mensajes'"></p>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full"
                                  :style="`background:${conv.canal_color}22; color:${conv.canal_color}`"
                                  x-text="conv.canal_nombre"></span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full font-medium"
                                  :style="estadoBadge(conv.estado)"
                                  x-text="estadoLabel(conv.estado)"></span>
                            <span x-show="conv.no_leidos > 0"
                                  class="ml-auto bg-green-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full"
                                  x-text="conv.no_leidos"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="conversacionesFiltradas.length === 0"
             class="flex flex-col items-center justify-center py-16 text-gray-400">
            <svg class="w-10 h-10 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-xs">Sin conversaciones</p>
        </div>
    </div>
</div>

{{-- ══════════════════════
     COLUMNA CENTRAL — Chat
══════════════════════ --}}
<div class="flex flex-col flex-1 min-w-0" x-show="convActiva">

    {{-- Header chat --}}
    <div class="flex items-center gap-3 px-4 py-3 flex-shrink-0"
         style="background:#0f1117;border-bottom:1px solid #1e2130;">
        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0"
             :style="`background:${convActiva?.canal_color}`"
             x-text="(convActiva?.cliente_nombre || convActiva?.cliente_telefono || '?').charAt(0).toUpperCase()"></div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-white truncate" x-text="convActiva?.cliente_nombre || convActiva?.cliente_telefono"></p>
            <div class="flex items-center gap-2">
                <p class="text-xs text-gray-400" x-text="convActiva?.cliente_telefono"></p>
                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full"
                      :style="`background:${convActiva?.canal_color}33; color:${convActiva?.canal_color}`"
                      x-text="convActiva?.canal_nombre"></span>
            </div>
        </div>
        <select x-model="estadoActual"
                @change="cambiarEstado(estadoActual)"
                class="text-xs border text-white rounded-lg px-2 py-1 focus:outline-none"
                style="background:#1e2130;border-color:#374151;">
            @foreach($estadoColores as $key => $cfg)
            <option value="{{ $key }}">{{ $cfg['label'] }}</option>
            @endforeach
        </select>

        {{-- Toggle bot --}}
        <button @click="toggleBot()"
                :title="convActiva?.bot_activo ? 'Bot activo — clic para pausar' : 'Bot pausado — clic para activar'"
                class="flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-semibold border transition-colors"
                :class="convActiva?.bot_activo
                    ? 'border-green-600 text-green-400 hover:bg-green-900/30'
                    : 'border-gray-600 text-gray-500 hover:bg-gray-800'">
            🤖
            <span x-text="convActiva?.bot_activo ? 'Bot ON' : 'Bot OFF'"></span>
        </button>
    </div>

    {{-- Mensajes --}}
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-2" id="chat-messages" style="background:#f0f0ea;">
        <template x-if="cargandoMensajes">
            <div class="flex justify-center py-8">
                <svg class="w-6 h-6 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
        </template>

        <template x-for="(msg, i) in mensajes" :key="msg.id">
            <div>
                <template x-if="i === 0 || esDiaDiferente(mensajes[i-1], msg)">
                    <div class="flex items-center gap-2 my-3">
                        <div class="flex-1 h-px bg-gray-300 opacity-40"></div>
                        <span class="text-[10px] text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full"
                              x-text="formatearFecha(msg.created_at)"></span>
                        <div class="flex-1 h-px bg-gray-300 opacity-40"></div>
                    </div>
                </template>
                <div :class="msg.direccion === 'saliente' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.direccion === 'saliente'
                                 ? 'rounded-2xl rounded-tr-sm'
                                 : 'bg-white text-gray-900 rounded-2xl rounded-tl-sm border border-gray-200'"
                         :style="msg.direccion === 'saliente' ? 'background:#25d366;color:white' : ''"
                         class="max-w-[72%] px-3 py-2 text-sm shadow-sm">
                        <p class="whitespace-pre-wrap break-words" x-text="msg.contenido"></p>
                        <div :class="msg.direccion === 'saliente' ? 'text-green-200' : 'text-gray-400'"
                             class="flex items-center justify-end gap-1 mt-0.5">
                            <span class="text-[10px]" x-text="formatearHora(msg.created_at)"></span>
                            <template x-if="msg.direccion === 'saliente'">
                                <span class="text-[10px]"
                                      :class="msg.estado === 'leido' ? 'text-blue-200' : ''"
                                      x-text="msg.estado === 'leido' ? '✓✓' : msg.estado === 'entregado' ? '✓✓' : '✓'"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <div id="chat-bottom"></div>
    </div>

    {{-- Input --}}
    <div class="flex-shrink-0 bg-white border-t border-gray-200 px-3 py-2">
        <template x-if="textoMensaje.includes('[')">
            <p class="text-[10px] text-amber-600 mb-1 px-1">Recuerda reemplazar [nombre], [FECHA], [LINK]</p>
        </template>
        <div class="flex items-end gap-2">
            <button @click="modalRespuestas = true"
                    class="p-2 text-gray-400 hover:text-green-600 transition-colors flex-shrink-0"
                    title="Respuestas rápidas">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </button>
            <textarea x-model="textoMensaje"
                      @keydown.enter.prevent="if(!$event.shiftKey) enviarMensaje()"
                      @keydown.enter.shift="textoMensaje += '\n'"
                      placeholder="Escribe un mensaje... (Enter para enviar)"
                      rows="1"
                      class="flex-1 resize-none text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 bg-gray-50"
                      style="max-height:120px;--tw-ring-color:#25d366"
                      x-ref="inputMensaje"
                      @input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight,120)+'px'"></textarea>
            <button @click="enviarMensaje()"
                    :disabled="!textoMensaje.trim() || enviando"
                    class="p-2 text-white rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-colors flex-shrink-0"
                    style="background:#25d366">
                <svg x-show="!enviando" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <svg x-show="enviando" x-cloak class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

{{-- Empty: sin conversación activa --}}
<div class="flex-1 flex flex-col items-center justify-center text-gray-500" x-show="!convActiva">
    <svg class="w-16 h-16 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
    <p class="text-sm">Selecciona una conversación</p>
</div>

{{-- ══════════════════════
     COLUMNA DERECHA — Detalle
══════════════════════ --}}
<div class="flex-col bg-white border-l border-gray-200 overflow-y-auto flex-shrink-0"
     :class="convActiva ? 'flex' : 'hidden'"
     style="width:270px;">
    <div class="px-4 py-4 space-y-4">

        {{-- Cliente --}}
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Cliente</p>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                     :style="`background:${convActiva?.canal_color}`"
                     x-text="(convActiva?.cliente_nombre || '?').charAt(0).toUpperCase()"></div>
                <div class="flex-1 min-w-0">
                    <input x-model="editNombre" @change="guardarDetalle()"
                           class="text-sm font-semibold text-gray-900 border-0 border-b border-dashed border-gray-300 focus:outline-none focus:border-green-400 bg-transparent w-full"
                           placeholder="Nombre del cliente">
                    <p class="text-xs text-gray-500" x-text="convActiva?.cliente_telefono"></p>
                </div>
            </div>
            <div class="space-y-1">
                <input x-model="editSector" @change="guardarDetalle()"
                       placeholder="Sector / tipo de negocio"
                       class="w-full text-xs border border-gray-100 rounded-lg px-2 py-1.5 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-green-400">
                <input x-model="editDistrito" @change="guardarDetalle()"
                       placeholder="Distrito"
                       class="w-full text-xs border border-gray-100 rounded-lg px-2 py-1.5 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-green-400">
            </div>
        </div>

        {{-- Origen --}}
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Origen</p>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold px-2 py-1 rounded-full"
                      :style="`background:${convActiva?.canal_color}22; color:${convActiva?.canal_color}`"
                      x-text="convActiva?.canal_nombre"></span>
                <span class="text-xs text-gray-600" x-text="origenLabel(convActiva?.origen_anuncio)"></span>
            </div>
        </div>

        {{-- Estado lead --}}
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Estado del lead</p>
            <select x-model="estadoActual" @change="cambiarEstado(estadoActual)"
                    class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-400">
                @foreach($estadoColores as $key => $cfg)
                <option value="{{ $key }}">{{ $cfg['label'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Notas --}}
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Notas</p>
            <textarea x-model="editNotas" rows="4"
                      placeholder="Notas del cliente..."
                      class="w-full text-xs border border-gray-200 rounded-lg px-2 py-2 focus:outline-none focus:ring-1 focus:ring-green-400 resize-none"></textarea>
            <button @click="guardarDetalle()"
                    class="mt-1 w-full text-xs text-white py-1.5 rounded-lg transition-colors"
                    style="background:#25d366">
                Guardar notas
            </button>
        </div>

        {{-- Acciones --}}
        <button @click="archivarConversacion()"
                class="w-full text-xs border border-gray-200 text-gray-600 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
            Archivar conversación
        </button>
    </div>
</div>

{{-- Modal respuestas rápidas --}}
<div x-show="modalRespuestas" x-cloak
     @keydown.escape.window="modalRespuestas=false"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="modalRespuestas=false"></div>
    <div class="relative bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[70vh] flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="font-bold text-sm text-gray-900">Respuestas rápidas</h3>
            <button @click="modalRespuestas=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="px-3 py-2 border-b border-gray-100">
            <input x-model="buscadorRespuestas" placeholder="Buscar respuesta..."
                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-400 bg-gray-50">
        </div>
        <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
            <template x-for="r in respuestasFiltradas" :key="r.id">
                <button @click="usarRespuesta(r)"
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                              :class="r.canal === 'bixo' ? 'bg-green-100 text-green-700' : r.canal === 'academy' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
                              x-text="r.canal.toUpperCase()"></span>
                        <span class="text-xs font-semibold text-gray-900" x-text="r.nombre"></span>
                    </div>
                    <p class="text-xs text-gray-500 line-clamp-2" x-text="r.texto"></p>
                </button>
            </template>
            <div x-show="respuestasFiltradas.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                Sin resultados
            </div>
        </div>
    </div>
</div>

</div>

<script>
const CONVERSACIONES_INIT = @json($conversacionesJs);
const RESPUESTAS_INIT = @json($respuestasRapidas);

function bandeja() {
    return {
        conversaciones: CONVERSACIONES_INIT,
        convActiva: null,
        mensajes: [],
        cargandoMensajes: false,
        enviando: false,
        textoMensaje: '',
        busqueda: '',
        filtroCanal: 'todos',
        filtroEstado: 'todos',
        estadoActual: 'nuevo',
        editNombre: '',
        editSector: '',
        editDistrito: '',
        editNotas: '',
        modalRespuestas: false,
        buscadorRespuestas: '',
        respuestas: RESPUESTAS_INIT,
        pollingInterval: null,
        serverTime: Math.floor(Date.now() / 1000),

        get totalNoLeidos() {
            return this.conversaciones.reduce((s, c) => s + (c.no_leidos || 0), 0);
        },

        get conversacionesFiltradas() {
            return this.conversaciones.filter(c => {
                if (this.filtroEstado === 'sin_leer' && c.no_leidos === 0) return false;
                if (this.filtroCanal !== 'todos' && c.canal_tipo !== this.filtroCanal) return false;
                if (this.busqueda) {
                    const q = this.busqueda.toLowerCase();
                    if (!(c.cliente_nombre || '').toLowerCase().includes(q) &&
                        !(c.cliente_telefono || '').toLowerCase().includes(q)) return false;
                }
                return true;
            }).map(c => ({
                ...c,
                tiempo: c.ultimo_mensaje_at ? this.tiempoRelativo(c.ultimo_mensaje_at) : '',
            })).sort((a,b) => new Date(b.ultimo_mensaje_at||0) - new Date(a.ultimo_mensaje_at||0));
        },

        get respuestasFiltradas() {
            if (!this.buscadorRespuestas) return this.respuestas;
            const q = this.buscadorRespuestas.toLowerCase();
            return this.respuestas.filter(r =>
                r.nombre.toLowerCase().includes(q) || r.texto.toLowerCase().includes(q)
            );
        },

        init() {
            this.pollingInterval = setInterval(() => this.poll(), 3000);
        },

        async abrirConversacion(conv) {
            this.convActiva = conv;
            this.estadoActual = conv.estado;
            this.editNombre   = conv.cliente_nombre   || '';
            this.editSector   = conv.cliente_sector   || '';
            this.editDistrito = conv.cliente_distrito || '';
            this.editNotas    = conv.notas            || '';
            this.mensajes = [];
            this.cargandoMensajes = true;

            const res = await fetch(`/bixocrm/${conv.id}/mensajes`, {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await res.json();
            this.mensajes = data.mensajes;
            this.cargandoMensajes = false;
            conv.no_leidos = 0;
            this.$nextTick(() => this.scrollBottom());
        },

        async enviarMensaje() {
            if (!this.textoMensaje.trim() || !this.convActiva || this.enviando) return;
            this.enviando = true;
            const contenido = this.textoMensaje;
            this.textoMensaje = '';

            const res = await fetch(`/bixocrm/${this.convActiva.id}/enviar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ contenido, tipo: 'texto' }),
            });
            const data = await res.json();
            if (data.ok) {
                this.mensajes.push(data.mensaje);
                this.convActiva.ultimo_mensaje = contenido;
                this.convActiva.ultimo_mensaje_at = new Date().toISOString();
                this.$nextTick(() => this.scrollBottom());
            }
            this.enviando = false;
            this.$refs.inputMensaje?.focus();
        },

        async toggleBot() {
            if (!this.convActiva) return;
            const res = await fetch(`/bixocrm/chatbot/toggle/${this.convActiva.id}`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const data = await res.json();
            if (data.ok) {
                this.convActiva.bot_activo = data.bot_activo;
                const c = this.conversaciones.find(x => x.id === this.convActiva.id);
                if (c) c.bot_activo = data.bot_activo;
            }
        },

        async cambiarEstado(estado) {
            if (!this.convActiva) return;
            await fetch(`/bixocrm/${this.convActiva.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ estado }),
            });
            this.convActiva.estado = estado;
            const c = this.conversaciones.find(x => x.id === this.convActiva.id);
            if (c) c.estado = estado;
        },

        async guardarDetalle() {
            if (!this.convActiva) return;
            await fetch(`/bixocrm/${this.convActiva.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    cliente_nombre:   this.editNombre,
                    cliente_sector:   this.editSector,
                    cliente_distrito: this.editDistrito,
                    notas:            this.editNotas,
                }),
            });
            this.convActiva.cliente_nombre   = this.editNombre;
            this.convActiva.cliente_sector   = this.editSector;
            this.convActiva.cliente_distrito = this.editDistrito;
            this.convActiva.notas            = this.editNotas;
        },

        async archivarConversacion() {
            if (!this.convActiva) return;
            await fetch(`/bixocrm/${this.convActiva.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ archivado: true }),
            });
            this.conversaciones = this.conversaciones.filter(c => c.id !== this.convActiva.id);
            this.convActiva = null;
        },

        async poll() {
            try {
                const params = new URLSearchParams({ since: this.serverTime });
                if (this.convActiva) params.append('conversacion_id', this.convActiva.id);

                const res = await fetch(`/bixocrm/poll?${params}`, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                const data = await res.json();
                this.serverTime = data.server_time;

                data.conversaciones_actualizadas.forEach(updated => {
                    const idx = this.conversaciones.findIndex(c => c.id === updated.id);
                    if (idx >= 0) Object.assign(this.conversaciones[idx], updated);
                    else this.conversaciones.push(updated);
                });

                if (data.mensajes_nuevos?.length > 0) {
                    const existingIds = new Set(this.mensajes.map(m => m.id));
                    data.mensajes_nuevos.forEach(m => {
                        if (!existingIds.has(m.id)) {
                            this.mensajes.push(m);
                            this.$nextTick(() => this.scrollBottom());
                        }
                    });
                }
            } catch(e) { /* ignore network errors during poll */ }
        },

        usarRespuesta(r) {
            this.textoMensaje = r.texto;
            this.modalRespuestas = false;
            this.$nextTick(() => this.$refs.inputMensaje?.focus());
        },

        estadoBadge(estado) {
            const map = {
                nuevo:        'background:#dcfce7;color:#166534',
                contactado:   'background:#dbeafe;color:#1e40af',
                demo_enviada: 'background:#fef3c7;color:#92400e',
                propuesta:    'background:#ede9fe;color:#5b21b6',
                cerrado:      'background:#d1fae5;color:#065f46',
                perdido:      'background:#fee2e2;color:#991b1b',
                academia:     'background:#e0f2fe;color:#0c4a6e',
            };
            return map[estado] || 'background:#f3f4f6;color:#374151';
        },

        estadoLabel(estado) {
            const map = {
                nuevo:'Nuevo', contactado:'Contactado', demo_enviada:'Demo',
                propuesta:'Propuesta', cerrado:'Cerrado', perdido:'Perdido', academia:'Academia'
            };
            return map[estado] || estado || '';
        },

        origenLabel(origen) {
            const map = {
                anuncio_bixo:    'Anuncio BIXO',
                anuncio_academy: 'Anuncio Academia',
                academy_organico:'Orgánico Academia',
                organico:        'Orgánico',
            };
            return map[origen] || origen || 'Desconocido';
        },

        scrollBottom() {
            document.getElementById('chat-bottom')?.scrollIntoView({ behavior: 'smooth' });
        },

        tiempoRelativo(iso) {
            if (!iso) return '';
            const diff = Math.floor((Date.now() - new Date(iso)) / 1000);
            if (diff < 60) return 'ahora';
            if (diff < 3600) return Math.floor(diff/60) + 'm';
            if (diff < 86400) return Math.floor(diff/3600) + 'h';
            return Math.floor(diff/86400) + 'd';
        },

        formatearHora(iso) {
            if (!iso) return '';
            return new Date(iso).toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'});
        },

        formatearFecha(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            const hoy = new Date();
            const ayer = new Date(); ayer.setDate(ayer.getDate()-1);
            if (d.toDateString() === hoy.toDateString()) return 'Hoy';
            if (d.toDateString() === ayer.toDateString()) return 'Ayer';
            return d.toLocaleDateString('es-PE', {day:'numeric', month:'long'});
        },

        esDiaDiferente(a, b) {
            if (!a || !b) return false;
            return new Date(a.created_at).toDateString() !== new Date(b.created_at).toDateString();
        },
    }
}
</script>

@endsection

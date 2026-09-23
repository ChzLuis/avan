@extends('crm::comunicaciones.layouts.app')
@section('pageTitle', 'Bandeja')
@section('content')

{{-- Los estados los define cada negocio (tabla crm_estados); llegan en \$estados. --}}

<div class="flex h-full w-full overflow-hidden"
     x-data="bandeja()" x-init="init()"
     style="height:100%">

{{-- ══════════════════════
     COLUMNA IZQUIERDA — Lista de chats (estilo WhatsApp)
══════════════════════ --}}
<div class="flex-col bg-white border-r border-gray-200 flex-shrink-0 w-full md:w-[340px]"
     :class="convActiva ? 'hidden md:flex' : 'flex'">

    {{-- Cabecera --}}
    <div class="px-3 pt-3 pb-2 border-b border-gray-100">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-base font-bold text-gray-900">Chats</h2>
            <div class="flex items-center gap-1">
                <span class="text-[10px] bg-green-100 text-green-700 font-black px-1.5 py-0.5 rounded-full" x-show="totalNoLeidos > 0" x-text="totalNoLeidos"></span>
                <button @click="alternarAvisos()" class="p-1.5 rounded-lg hover:bg-gray-100 relative" :class="avisos ? 'text-green-600' : 'text-gray-400'"
                        :title="avisos ? 'Avisos activados: sonido y notificación al llegar un mensaje (clic para apagar)' : 'Activar sonido y notificaciones de mensajes nuevos'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span x-show="!avisos" class="absolute inset-0 flex items-center justify-center pointer-events-none"><span class="block w-5 h-px bg-gray-400" style="transform:rotate(45deg)"></span></span>
                </button>
                <button @click="modalRespuestas = true" class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-gray-100" title="Respuestas rápidas">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </button>
            </div>
        </div>
        {{-- Sesion caida: sin esto el CRM parece vivo pero no trae nada (ni avisos ni chats). --}}
        <div x-show="sesionCaida" x-cloak class="mb-3 rounded-xl border px-3 py-2.5 flex items-center gap-3" style="background:#fef2f2;border-color:#fca5a5">
            <span class="text-xl flex-shrink-0">⚠️</span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-900">Tu sesión caducó</p>
                <p class="text-[11px] text-gray-600">No estás recibiendo mensajes nuevos. Vuelve a entrar para seguir atendiendo.</p>
            </div>
            <a href="{{ route('bixocrm.login') }}" class="text-xs font-bold text-white px-3 py-1.5 rounded-lg flex-shrink-0" style="background:#dc2626">Entrar</a>
        </div>
        {{-- Activar avisos: visible hasta que el dispositivo quede suscrito (o se oculte por hoy). --}}
        <div x-show="!pushListo && !bannerOculto" x-cloak class="mb-3 rounded-xl border px-3 py-2.5 flex items-center gap-3" style="background:#fff7ed;border-color:#fdba74">
            <span class="text-xl flex-shrink-0">🔔</span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-900">Entérate de cada mensaje nuevo</p>
                <p class="text-[11px] text-gray-600">Sonido y notificación en este dispositivo, aunque el CRM esté cerrado.</p>
            </div>
            <button @click="alternarAvisos()" class="text-xs font-bold text-white px-3 py-1.5 rounded-lg flex-shrink-0" style="background:#ea580c">Activar</button>
            <button @click="bannerOculto = true; try { sessionStorage.setItem('bx_banner_avisos', '1'); } catch (e) {}" class="text-gray-400 hover:text-gray-600 text-sm flex-shrink-0" title="Ahora no">✕</button>
        </div>
        <div class="relative">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input type="text" x-model="busqueda" placeholder="Buscar un chat o un teléfono"
                   class="w-full pl-8 pr-3 py-2 text-xs border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:ring-1 focus:ring-green-400">
        </div>
        {{-- Pestañas: como WhatsApp (Todos / No leídos) y como un CRM (Mías / Sin asignar / Cerradas).
             Van en dos filas: en una sola, "Archivadas" quedaba fuera de la pantalla. --}}
        <div class="flex gap-1 mt-2 flex-wrap">
            <template x-for="t in [['todas','Todas'],['sin_leer','No leídas']]" :key="t[0]">
                <button @click="vista = t[0]"
                        :class="vista === t[0] ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-2.5 py-1 text-[11px] font-semibold rounded-full whitespace-nowrap transition-colors flex items-center gap-1">
                    <span x-text="t[1]"></span>
                    <span class="opacity-70" x-text="'(' + contarVista(t[0]) + ')'"></span>
                </button>
            </template>

            {{-- Un filtro por estado, con el color que el negocio configuro.
                 Antes habia pestanas fijas (Mias, Sin asignar, Cerradas) que
                 quedaban en (0) o repetian el total, y no se podia filtrar por
                 el estado, que es lo unico que se usa de verdad. Solo aparecen
                 los estados que TIENEN chats. --}}
            <template x-for="e in estadosConChats" :key="'f-'+e.clave">
                <button @click="vista = 'estado:' + e.clave"
                        :style="vista === 'estado:' + e.clave
                                ? 'background:' + e.color + ';color:#fff'
                                : 'background:' + e.color + '1f;color:' + e.color"
                        class="px-2.5 py-1 text-[11px] font-semibold rounded-full whitespace-nowrap transition-colors flex items-center gap-1">
                    <span x-text="e.nombre"></span>
                    <span class="opacity-70" x-text="'(' + contarVista('estado:' + e.clave) + ')'"></span>
                </button>
            </template>

            {{-- Estas viven al final y solo si tienen algo: en un negocio que no
                 asigna conversaciones estaban siempre vacias ocupando sitio. --}}
            <template x-for="t in [['mias','Mías'],['sin_asignar','Sin asignar'],['archivadas','Archivadas']]" :key="t[0]">
                <button x-show="contarVista(t[0]) > 0 || vista === t[0]"
                        @click="vista = t[0]"
                        :class="vista === t[0] ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                        class="px-2.5 py-1 text-[11px] font-semibold rounded-full whitespace-nowrap transition-colors flex items-center gap-1">
                    <span x-text="t[1]"></span>
                    <span class="opacity-70" x-text="'(' + contarVista(t[0]) + ')'"></span>
                </button>
            </template>
        </div>
        <div class="flex gap-1 mt-1.5 flex-wrap" x-show="{{ $canales->count() }} > 1">
            <button @click="filtroCanal='todos'" :class="filtroCanal==='todos' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'" class="px-2 py-0.5 text-[10px] font-semibold rounded-full">Todas las líneas</button>
            @foreach($canales as $c)
            <button @click="filtroCanal='{{ $c->tipo }}'"
                    :class="filtroCanal==='{{ $c->tipo }}' ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    :style="filtroCanal==='{{ $c->tipo }}' ? 'background:{{ $c->color }}' : ''"
                    class="px-2 py-0.5 text-[10px] font-semibold rounded-full transition-colors">{{ $c->nombre }}</button>
            @endforeach
        </div>
    </div>

    @if($canales->isEmpty())
    {{-- Sin linea de WhatsApp: la bandeja no puede recibir nada. Es el primer paso. --}}
    <a href="{{ route('bixocrm.conectar') }}" class="block m-3 p-3 rounded-xl border border-green-200 bg-green-50 hover:bg-green-100 transition">
        <p class="text-xs font-bold text-green-800">Conecta tu WhatsApp</p>
        <p class="text-[11px] text-green-700 mt-0.5">Aún no hay ninguna línea conectada. El asistente te guía en 3 pasos.</p>
    </a>
    @endif

    {{-- Lista --}}
    <div class="flex-1 overflow-y-auto">
        <template x-for="conv in conversacionesFiltradas" :key="conv.id">
            <div @click="Math.abs(deslizX) < 5 && abrirConversacion(conv)"
                 @contextmenu.prevent="menuConv = conv.id"
                 @touchstart.passive="deslizarInicio($event, conv)" @touchmove.passive="deslizarMover($event)" @touchend="deslizarFin(conv)"
                 :class="convActiva?.id === conv.id ? 'bg-green-50' : 'hover:bg-gray-50'"
                 :style="deslizId === conv.id && deslizX ? 'transform:translateX(' + deslizX + 'px);background:' + (deslizX < 0 ? '#fef3c7' : '#dcfce7') : ''"
                 class="group relative px-3 py-2.5 cursor-pointer transition-colors border-b border-gray-50">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-full flex items-center justify-center text-white text-base font-bold flex-shrink-0 relative"
                         :style="`background:${conv.canal_color}`"
                         x-text="(conv.cliente_nombre || conv.cliente_telefono).charAt(0).toUpperCase()">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            {{-- min-w-0: sin el, este span con `truncate` no cede
                                 ancho y aplasta la fecha de la derecha hasta
                                 dejarla en una rayita. Mismo caso que los pies. --}}
                            <span class="text-sm font-semibold text-gray-900 truncate flex items-center gap-1 min-w-0">
                                <span class="truncate" x-text="conv.cliente_nombre || conv.cliente_telefono"></span>
                                <span x-show="conv.fijada" class="text-gray-400 text-[10px] flex-shrink-0" title="Fijado">📌</span>
                            </span>
                            <span class="text-[10px] flex-shrink-0 whitespace-nowrap tabular-nums" :class="conv.no_leidos > 0 ? 'text-green-600 font-bold' : 'text-gray-400'" x-text="conv.tiempo"></span>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <p class="text-xs text-gray-500 truncate flex items-center gap-1">
                                <template x-if="borradorDe(conv)">
                                    <span class="truncate"><span class="text-red-500 font-semibold">Borrador:</span> <span x-text="borradorDe(conv)"></span></span>
                                </template>
                                <template x-if="!borradorDe(conv)">
                                    <span class="truncate flex items-center gap-1">
                                        {{-- Mismo acuse que dentro del chat: la lista pintaba
                                             un ✓ gris fijo y todos los chats se veian igual,
                                             sin poder distinguir lo entregado de lo fallido. --}}
                                        <span x-show="esSalienteConv(conv)"
                                              x-text="acuseConv(conv)"
                                              :class="conv.ultimo_estado === 'leido' ? 'text-sky-500'
                                                    : (conv.ultimo_estado === 'fallido' || conv.ultimo_estado === 'pendiente') ? 'text-red-500'
                                                    : 'text-gray-400'"
                                              :title="conv.ultimo_estado === 'fallido' ? 'No se envio'
                                                    : conv.ultimo_estado === 'pendiente' ? 'No se entrego'
                                                    : conv.ultimo_estado === 'leido' ? 'Leido'
                                                    : conv.ultimo_estado === 'entregado' ? 'Entregado' : 'Enviado'"></span>
                                        <span x-text="previewMensaje(conv)"></span>
                                    </span>
                                </template>
                            </p>
                            <span class="flex items-center gap-1 flex-shrink-0">
                                <span x-show="conv.bot_activo" class="text-[10px]" title="Bot atendiendo">🤖</span>
                                <span x-show="conv.no_leidos > 0" class="bg-green-500 text-white text-[10px] font-black min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center" x-text="conv.no_leidos"></span>
                            </span>
                        </div>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full" :style="`background:${conv.canal_color}22; color:${conv.canal_color}`" x-text="conv.canal_nombre" x-show="{{ $canales->count() }} > 1"></span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full font-medium" :style="estadoBadge(conv.estado)" x-text="estadoLabel(conv.estado)"></span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-medium" x-show="conv.asignado_a" x-text="conv.asignado_a"></span>
                        </div>
                    </div>
                    {{-- Menú del chat (⋯), como WhatsApp --}}
                    <button @click.stop="menuConv = menuConv === conv.id ? null : conv.id"
                            class="absolute right-2 top-2 p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-white"
                            :class="menuConv === conv.id ? 'opacity-100' : ''">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="menuConv === conv.id" x-cloak @click.outside="menuConv = null" @click.stop
                         class="absolute right-2 top-9 z-30 w-52 bg-white border border-gray-200 rounded-xl shadow-xl py-1 text-sm">
                        <button @click="fijar(conv)" class="w-full text-left px-3 py-2 hover:bg-gray-50" x-text="conv.fijada ? 'Desfijar chat' : 'Fijar chat'"></button>
                        <button @click="marcarNoLeida(conv)" class="w-full text-left px-3 py-2 hover:bg-gray-50">Marcar como no leído</button>
                        <button @click="asignar(conv, conv.asignado_a === YO ? null : YO)" class="w-full text-left px-3 py-2 hover:bg-gray-50" x-text="conv.asignado_a === YO ? 'Quitarme la asignación' : 'Asignármelo'"></button>
                        <button @click="archivar(conv, !conv.archivado)" class="w-full text-left px-3 py-2 hover:bg-gray-50" x-text="conv.archivado ? 'Desarchivar chat' : 'Archivar chat'"></button>
                        <div class="border-t border-gray-100 my-1"></div>
                        <button @click="eliminarChat(conv)" class="w-full text-left px-3 py-2 text-red-600 hover:bg-red-50">Eliminar chat</button>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="conversacionesFiltradas.length === 0" class="flex flex-col items-center justify-center py-16 text-gray-400">
            <svg class="w-10 h-10 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p class="text-xs">Nada por aquí</p>
        </div>
    </div>

    {{-- Pie: métricas del día --}}
    <div class="hidden md:grid grid-cols-3 border-t border-gray-100 text-center bg-gray-50">
        <div class="py-1.5 border-r border-gray-100"><p class="text-sm font-black text-gray-900">{{ $metricas['total_hoy'] }}</p><p class="text-[9px] text-gray-400 uppercase">Hoy</p></div>
        <div class="py-1.5 border-r border-gray-100"><p class="text-sm font-black text-red-600">{{ $metricas['sin_leer'] }}</p><p class="text-[9px] text-gray-400 uppercase">Sin leer</p></div>
        <div class="py-1.5"><p class="text-sm font-black text-green-600">{{ $metricas['cerrados'] }}</p><p class="text-[9px] text-gray-400 uppercase">Cerrados</p></div>
    </div>
</div>

{{-- ══════════════════════
     COLUMNA CENTRAL — Chat
══════════════════════ --}}
<div class="flex-col flex-1 min-w-0" :class="convActiva ? 'flex' : 'hidden'">

    {{-- Header chat: nombre y linea a la izquierda, acciones a la derecha. En movil, flecha para volver a la lista. --}}
    <div class="flex items-center gap-2 px-2 md:px-4 py-2 flex-shrink-0 bg-white border-b border-gray-200">
        <button @click="volverALista()" class="md:hidden p-1.5 -ml-1 rounded-lg text-gray-500 hover:bg-gray-100" title="Volver">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <div class="w-9 h-9 md:w-10 md:h-10 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 cursor-pointer"
             :style="`background:${convActiva?.canal_color}`" @click="mostrarFicha = !mostrarFicha"
             x-text="(convActiva?.cliente_nombre || convActiva?.cliente_telefono || '?').charAt(0).toUpperCase()"></div>
        <div class="flex-1 min-w-0 cursor-pointer" @click="mostrarFicha = !mostrarFicha">
            <p class="text-sm font-bold text-gray-900 truncate" x-text="convActiva?.cliente_nombre || convActiva?.cliente_telefono"></p>
            <p class="text-[11px] text-gray-500 truncate">
                <span x-show="!esMovil" class="font-semibold text-gray-700 tracking-wide select-all" style="font-size:13px" x-text="convActiva?.cliente_telefono"></span>
                <span x-show="{{ $canales->count() }} > 1 && !esMovil" x-text="' · ' + (convActiva?.canal_nombre || '')"></span>
                <span x-show="convActiva?.asignado_a" x-text="' · ' + (convActiva?.asignado_a || '')"></span>
                <span x-show="convActiva?.bot_activo" class="text-green-600 font-semibold" x-text="esMovil ? '🤖 Bot atendiendo' : ' · Bot atendiendo'"></span>
            </p>
        </div>
        <button @click="toggleBot()"
                :title="convActiva?.bot_activo ? 'Bot activo — clic para atender tú' : 'Bot pausado — clic para que atienda el bot'"
                class="px-2 py-1 rounded-lg text-[11px] font-semibold border transition-colors flex-shrink-0"
                :class="convActiva?.bot_activo ? 'border-green-500 text-green-700 bg-green-50' : 'border-gray-300 text-gray-500'">
            🤖 <span class="hidden sm:inline" x-text="convActiva?.bot_activo ? 'ON' : 'OFF'"></span>
        </button>
        <button @click="buscarEnChat = !buscarEnChat" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 hidden sm:block" title="Buscar en la conversación">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
        </button>
        <button @click="mostrarFicha = !mostrarFicha" class="p-1.5 rounded-lg hover:bg-gray-100" :class="mostrarFicha ? 'text-green-600' : 'text-gray-400'" title="Ficha del cliente">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </button>
        <div class="relative">
            <button @click="menuChat = !menuChat" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100" title="Más">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
            </button>
            <div x-show="menuChat" x-cloak @click.outside="menuChat = false"
                 class="absolute right-0 top-9 z-30 w-56 bg-white border border-gray-200 rounded-xl shadow-xl py-1 text-sm">
                <button @click="buscarEnChat = !buscarEnChat; menuChat=false" class="w-full text-left px-3 py-2 hover:bg-gray-50 sm:hidden">Buscar en el chat</button>
                <button @click="fijar(convActiva); menuChat=false" class="w-full text-left px-3 py-2 hover:bg-gray-50" x-text="convActiva?.fijada ? 'Desfijar chat' : 'Fijar chat'"></button>
                <button @click="marcarNoLeida(convActiva); menuChat=false" class="w-full text-left px-3 py-2 hover:bg-gray-50">Marcar como no leído</button>
                <button @click="asignar(convActiva, convActiva?.asignado_a === YO ? null : YO); menuChat=false" class="w-full text-left px-3 py-2 hover:bg-gray-50" x-text="convActiva?.asignado_a === YO ? 'Quitarme la asignación' : 'Asignármelo'"></button>
                <button @click="archivar(convActiva, !convActiva?.archivado); menuChat=false" class="w-full text-left px-3 py-2 hover:bg-gray-50" x-text="convActiva?.archivado ? 'Desarchivar chat' : 'Archivar chat'"></button>
                <div class="border-t border-gray-100 my-1"></div>
                <button @click="eliminarChat(convActiva); menuChat=false" class="w-full text-left px-3 py-2 text-red-600 hover:bg-red-50">Eliminar chat</button>
            </div>
        </div>
    </div>
    <div x-show="buscarEnChat" x-cloak class="px-4 py-2 bg-white border-b border-gray-100">
        <input type="text" x-model="busquedaChat" placeholder="Buscar en esta conversación…" class="w-full px-3 py-1.5 text-xs border border-gray-200 rounded-lg bg-gray-50 focus:outline-none">
    </div>

    {{-- Mensajes --}}
    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-2" id="chat-messages"
         style="background-color:#efeae2;background-image:radial-gradient(rgba(0,0,0,.035) 1px, transparent 1px);background-size:18px 18px;">
        <template x-if="cargandoMensajes">
            <div class="flex justify-center py-8">
                <svg class="w-6 h-6 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
        </template>

        <template x-for="(msg, i) in mensajes" :key="msg.id">
            <div x-show="!busquedaChat || (msg.contenido || '').toLowerCase().includes(busquedaChat.toLowerCase())">
                <template x-if="i === 0 || esDiaDiferente(mensajes[i-1], msg)">
                    <div class="flex items-center gap-2 my-3">
                        <div class="flex-1 h-px bg-gray-300 opacity-40"></div>
                        <span class="text-[10px] text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full"
                              x-text="formatearFecha(msg.created_at)"></span>
                        <div class="flex-1 h-px bg-gray-300 opacity-40"></div>
                    </div>
                </template>
                <div class="group flex" :class="esSaliente(msg) ? 'justify-end' : 'justify-start'">
                    <div :class="esSaliente(msg)
                                 ? 'rounded-2xl rounded-tr-sm text-gray-900'
                                 : 'bg-white text-gray-900 rounded-2xl rounded-tl-sm'"
                         :style="esSaliente(msg) ? 'background:#d9fdd3' : ''"
                         class="relative max-w-[88%] md:max-w-[72%] px-3 py-1.5 text-sm shadow-sm">
                        {{-- Opciones del mensaje: un solo boton (en movil no existe "pasar el mouse"). --}}
                        {{-- SIEMPRE visible: las variantes md:opacity-0 / group-hover NO estan en el CSS
                             compilado, asi que el boton quedaba invisible y no se podia tocar. --}}
                        <button @click.stop="menuMensaje = msg"
                                class="absolute -top-2 w-6 h-6 rounded-full bg-white border border-gray-200 shadow text-[11px] text-gray-500 hover:bg-gray-50"
                                :class="esSaliente(msg) ? 'left-1' : 'right-1'" title="Opciones del mensaje">⋯</button>
                        {{-- Adjuntos: imagen en linea, PDF como enlace. Todo lo demas, texto. --}}
                        <template x-if="msg.tipo === 'imagen' && msg.media_url">
                            <a :href="msg.media_url" target="_blank" class="block mb-1">
                                <img :src="msg.media_url" class="rounded-lg max-h-64 object-cover" loading="lazy" alt="imagen">
                            </a>
                        </template>
                        <template x-if="msg.tipo === 'documento' && msg.media_url">
                            <a :href="msg.media_url" target="_blank" class="flex items-center gap-2 mb-1 underline">
                                <span>📄</span><span class="truncate max-w-[220px]" x-text="msg.contenido || 'Documento'"></span>
                            </a>
                        </template>
                        <template x-if="msg.tipo === 'audio' && msg.media_url">
                            <audio controls preload="none" :src="msg.media_url" class="max-w-full mb-1" style="height:36px"></audio>
                        </template>
                        <template x-if="msg.tipo === 'video' && msg.media_url">
                            <video controls preload="none" :src="msg.media_url" class="rounded-lg max-h-64 mb-1"></video>
                        </template>
                        <template x-if="msg.tipo === 'ubicacion' && msg.media_url">
                            <a :href="msg.media_url" target="_blank" class="flex items-center gap-2 mb-1 underline">
                                <span x-text="msg.contenido || '📍 Ubicación'"></span><span class="text-[11px] opacity-70">· ver en el mapa</span>
                            </a>
                        </template>
                        <template x-if="!(['documento','audio','video','ubicacion'].includes(msg.tipo) && msg.media_url)">
                            <p class="whitespace-pre-wrap break-words" x-text="msg.contenido"></p>
                        </template>
                        <div class="flex items-center justify-end gap-1 mt-0.5 text-gray-400">
                            <span class="text-[10px]" x-text="formatearHora(msg.created_at)"></span>
                            <template x-if="esSaliente(msg)">
                                <span class="text-[10px]"
                                      :class="msg.estado === 'leido' ? 'text-sky-500' : (msg.estado === 'fallido' || msg.estado === 'pendiente') ? 'text-red-500 font-semibold' : ''"
                                      :title="msg.estado === 'fallido' ? (msg.error || 'Meta rechazó el envío') : msg.estado === 'pendiente' ? 'No se entregó' : msg.estado === 'leido' ? 'Leído' : msg.estado === 'entregado' ? 'Entregado' : 'Enviado'"
                                      x-text="msg.estado === 'leido' || msg.estado === 'entregado' ? '✓✓' : (msg.estado === 'pendiente' || msg.estado === 'fallido') ? '⚠' : '✓'"></span>
                            </template>
                            <template x-if="esSaliente(msg) && msg.estado === 'fallido'">
                                <span class="text-[10px] text-red-500 truncate max-w-[220px]" x-text="msg.error || 'No se pudo enviar'"></span>
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
        {{-- WhatsApp rechazó el envío: hay que decirlo, no dejar que el asesor
             crea que el cliente lo recibió. El texto vuelve al campo. --}}
        <div x-show="errorEnvio" x-cloak
             class="flex items-start gap-2 mb-2 px-2 py-1.5 rounded-lg bg-red-50 border border-red-200">
            <span class="text-red-500 text-xs leading-4">⚠</span>
            <p class="flex-1 min-w-0 text-[11px] text-red-700 leading-4">
                <span class="font-semibold">No se entregó:</span>
                <span x-text="errorEnvio"></span>
            </p>
            <button @click="errorEnvio = null"
                    class="text-red-400 hover:text-red-600 text-xs leading-4">✕</button>
        </div>
        <template x-if="textoMensaje.includes('[')">
            <p class="text-[10px] text-amber-600 mb-1 px-1">Recuerda reemplazar [nombre], [FECHA], [LINK]</p>
        </template>
        {{-- Ventana de 24 h de Meta cerrada: el texto libre no llega; solo una plantilla aprobada. --}}
        <div x-show="ventana.es_meta && !ventana.abierta" x-cloak
             class="flex items-center gap-2 mb-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200">
            <span class="text-amber-500 text-sm">⏳</span>
            <p class="flex-1 min-w-0 text-[11px] text-amber-800 leading-4">
                Pasaron más de 24 h desde el último mensaje del cliente. WhatsApp solo deja escribirle con una <b>plantilla aprobada</b>; cuando él responda, el chat se abre otra vez.
            </p>
            <button @click="abrirPlantillas()" class="text-[11px] font-semibold text-white px-2.5 py-1 rounded-lg flex-shrink-0" style="background:#d97706">Elegir plantilla</button>
        </div>
        {{-- Movil: respuestas rapidas a un toque, sin abrir el modal. --}}
        <div x-show="esMovil && respuestas.length && !grabando && !textoMensaje" class="flex gap-1.5 overflow-x-auto mb-2 pb-0.5" style="scrollbar-width:none;-webkit-overflow-scrolling:touch">
            <template x-for="r in respuestas.slice(0, 8)" :key="'chip' + r.id">
                <button @click="usarRespuesta(r)" class="flex-shrink-0 text-[11px] font-semibold px-2.5 py-1 rounded-full border border-green-200 text-green-800 bg-green-50 whitespace-nowrap" x-text="r.nombre"></button>
            </template>
            <button @click="modalRespuestas = true" class="flex-shrink-0 text-[11px] px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 whitespace-nowrap">Todas…</button>
        </div>
        <div x-show="adjunto" x-cloak class="flex items-center gap-2 mb-2 px-2 py-1.5 rounded-lg bg-green-50 border border-green-200 text-[11px] text-green-800">
            <span>📎</span><span class="truncate" x-text="adjunto?.name"></span>
            <button @click="adjunto = null; $refs.inputArchivo.value = ''" class="ml-auto text-green-600 hover:text-green-800">✕</button>
        </div>
        <div class="flex items-end gap-2 relative">
            <input type="file" x-ref="inputArchivo" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf,.mp3,.ogg,.m4a,.aac" @change="adjunto = $event.target.files[0] || null; masOpciones = false">
            {{-- Movil: un solo "+" agrupa adjuntar, plantillas y respuestas rapidas. --}}
            <template x-if="esMovil">
                <div class="relative flex-shrink-0">
                    <button @click="masOpciones = !masOpciones" class="p-2 rounded-full text-gray-500 hover:text-green-600 transition-colors" :class="masOpciones ? 'bg-gray-100' : ''" :style="'transition:transform .15s;' + (masOpciones ? 'transform:rotate(45deg)' : '')">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div x-show="masOpciones" x-cloak @click.outside="masOpciones = false"
                         style="bottom:3rem" class="absolute left-0 z-30 w-52 bg-white rounded-2xl shadow-xl border border-gray-100 py-1 text-sm">
                        <button @click="$refs.inputArchivo.click()" class="w-full text-left px-4 py-2.5 hover:bg-gray-50 flex items-center gap-3"><span>📎</span> Foto o PDF</button>
                        <button @click="masOpciones = false; grabando ? pararGrabacion() : grabarNota()" class="w-full text-left px-4 py-2.5 hover:bg-gray-50 flex items-center gap-3"><span>🎤</span> Nota de voz</button>
                        <button x-show="ventana.es_meta" @click="masOpciones = false; abrirPlantillas()" class="w-full text-left px-4 py-2.5 hover:bg-gray-50 flex items-center gap-3"><span>📋</span> Plantillas de Meta</button>
                        <button @click="masOpciones = false; modalRespuestas = true" class="w-full text-left px-4 py-2.5 hover:bg-gray-50 flex items-center gap-3"><span>⚡</span> Respuestas rápidas</button>
                    </div>
                </div>
            </template>
            <button x-show="!esMovil" @click="grabando ? pararGrabacion() : grabarNota()"
                    :class="grabando ? 'text-red-600 animate-pulse' : 'text-gray-400 hover:text-green-600'"
                    class="p-2 transition-colors flex-shrink-0" :title="grabando ? 'Detener y adjuntar la nota de voz' : 'Grabar nota de voz'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
            </button>
            <button x-show="!esMovil" @click="$refs.inputArchivo.click()"
                    class="p-2 text-gray-400 hover:text-green-600 transition-colors flex-shrink-0"
                    title="Adjuntar imagen o PDF (hasta 20 MB)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
            </button>
            <button x-show="ventana.es_meta && !esMovil" @click="abrirPlantillas()"
                    class="p-2 text-gray-400 hover:text-green-600 transition-colors flex-shrink-0"
                    title="Plantillas aprobadas por Meta (sirven pasadas las 24 h)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
            </button>
            <button x-show="!esMovil" @click="modalRespuestas = true"
                    class="p-2 text-gray-400 hover:text-green-600 transition-colors flex-shrink-0"
                    title="Respuestas rápidas">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </button>
            {{-- Grabando: en vez del campo, el contador de segundos (como WhatsApp). --}}
            <div x-show="grabando" x-cloak class="flex-1 flex items-center gap-2 text-sm border border-red-200 rounded-xl px-3 py-2 bg-red-50 text-red-700">
                <span class="rounded-full bg-red-500 animate-pulse flex-shrink-0" style="width:10px;height:10px"></span>
                <span class="font-semibold tabular-nums" x-text="grabTiempo()"></span>
                <span class="text-[11px] text-red-500 truncate">Grabando… toca ■ para enviar</span>
                <button @click="cancelarGrabacion()" class="ml-auto text-[11px] text-red-600 underline flex-shrink-0">Cancelar</button>
            </div>
            {{-- Emojis: en movil el teclado del telefono ya los trae, aqui no. --}}
            <div class="relative" x-show="!esMovil" @click.outside="panelEmojis = false">
                <button @click="panelEmojis = !panelEmojis" type="button"
                        class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100" title="Emojis">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <div x-show="panelEmojis" x-cloak
                     class="absolute bg-white border border-gray-200 rounded-xl shadow-xl p-2 z-40"
                     style="bottom:100%;left:0;margin-bottom:.5rem;width:280px;max-height:220px;overflow-y:auto">
                    <template x-for="e in EMOJIS" :key="e">
                        <button type="button" @click="ponerEmoji(e)" class="text-xl leading-none p-1 rounded hover:bg-gray-100" x-text="e"></button>
                    </template>
                </div>
            </div>
            <textarea x-show="!grabando" x-model="textoMensaje"
                      @paste="pegarArchivo($event)"
                      @keydown.enter.prevent="if(!$event.shiftKey) enviarMensaje()"
                      @keydown.enter.shift="textoMensaje += '\n'"
                      :placeholder="esMovil ? 'Escribe un mensaje' : 'Escribe un mensaje... (Enter para enviar)'"
                      rows="1"
                      class="flex-1 resize-none text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-1 bg-gray-50"
                      style="max-height:120px;--tw-ring-color:#25d366"
                      x-ref="inputMensaje"
                      @input="$el.style.height='auto'; $el.style.height=Math.min($el.scrollHeight,120)+'px'"></textarea>
            <button x-show="esMovil && !textoMensaje.trim() && !adjunto && !grabando" @click="grabarNota()"
                    class="p-2 text-white rounded-full flex-shrink-0" style="background:#25d366" title="Grabar nota de voz">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
            </button>
            <button x-show="esMovil && grabando" @click="pararGrabacion()"
                    class="p-2 text-white rounded-full flex-shrink-0 animate-pulse" style="background:#dc2626" title="Detener y adjuntar">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
            </button>
            <button x-show="!esMovil || textoMensaje.trim() || adjunto" @click="enviarMensaje()"
                    :disabled="(!textoMensaje.trim() && !adjunto) || enviando"
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

{{-- ═══ FICHA DEL CLIENTE: un solo panel. En PC es una columna plegable; en movil, un cajon superpuesto. ═══ --}}
<div x-show="convActiva && mostrarFicha" x-cloak
     class="fixed inset-y-0 right-0 z-40 w-[88vw] max-w-sm shadow-2xl lg:static lg:shadow-none lg:w-[300px] lg:max-w-none flex-col bg-white border-l border-gray-200 overflow-y-auto flex-shrink-0 flex">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Ficha del cliente</span>
        <button @click="mostrarFicha = false" class="p-1 rounded-lg text-gray-400 hover:bg-gray-100" title="Cerrar">✕</button>
    </div>
    <div class="p-4 space-y-4">
        {{-- Quien es --}}
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0"
                 :style="`background:${convActiva?.canal_color}`"
                 x-text="(convActiva?.cliente_nombre || convActiva?.cliente_telefono || '?').charAt(0).toUpperCase()"></div>
            <div class="flex-1 min-w-0">
                <input x-model="editNombre" @change="guardarDetalle()"
                       class="text-sm font-semibold text-gray-900 border-0 border-b border-dashed border-gray-300 focus:outline-none focus:border-green-400 bg-transparent w-full"
                       placeholder="Nombre del cliente">
                <p class="text-sm font-semibold text-gray-700 mt-0.5 tracking-wide select-all" x-text="convActiva?.cliente_telefono"></p>
            </div>
        </div>

        {{-- Estado del lead (el que se ve en la lista) --}}
        <div>
            <div class="flex items-center justify-between mb-1">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Estado</p>
                <button @click="abrirEstados()" class="text-[10px] text-gray-400 hover:text-gray-700 hover:underline">Editar estados</button>
            </div>
            <select x-model="estadoActual" @change="cambiarEstado(estadoActual)"
                    class="w-full text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-green-400">
                <template x-for="e in estados" :key="e.clave">
                    <option :value="e.clave" x-text="e.nombre"></option>
                </template>
            </select>
            <template x-if="etapaAviso">
                <p class="text-[11px] mt-1" x-text="etapaAviso.texto"
                   :class="etapaAviso.ok ? 'text-green-600' : 'text-red-600 font-semibold'"></p>
            </template>
        </div>

        {{-- Temperatura (IA) --}}
        <div class="rounded-xl border border-gray-100 p-3 bg-gray-50" x-show="lead?.clasificacion">
            <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">Temperatura</div>
            <template x-if="lead?.clasificacion">
                <div>
                    <div class="text-2xl font-extrabold"
                         :class="{'text-red-600':lead.clasificacion.temp==='caliente','text-amber-600':lead.clasificacion.temp==='tibio','text-blue-600':lead.clasificacion.temp==='frio'}">
                        <span x-text="lead.clasificacion.score + '%'"></span>
                        <span class="text-xs font-semibold" x-text="{caliente:'🔥 Caliente',tibio:'🟡 Tibio',frio:'🔵 Frío'}[lead.clasificacion.temp] || '⚪ Nuevo'"></span>
                    </div>
                    <div class="h-1.5 rounded-full bg-gray-200 mt-2 overflow-hidden">
                        <div class="h-full rounded-full"
                             :class="{'bg-red-500':lead.clasificacion.temp==='caliente','bg-amber-500':lead.clasificacion.temp==='tibio','bg-blue-500':lead.clasificacion.temp==='frio'}"
                             :style="`width:${lead.clasificacion.score}%`"></div>
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1.5" x-text="lead.clasificacion.motivo"></div>
                </div>
            </template>
        </div>

        {{-- La Etapa del embudo ya NO se elige aqui: el Estado de arriba la
             arrastra (ver etapaDeEstado). Tener dos selectores que significaban
             lo mismo dejaba al chat en "contactado" y al cliente en "propuesta".
             Se muestra solo como lectura, para saber donde cae en el Pipeline. --}}
        <div x-show="lead && lead.etapa" class="text-[11px] text-gray-400">
            En el embudo: <span class="font-semibold text-gray-600" x-text="etapaNombre(lead.etapa)"></span>
        </div>

        {{-- Datos --}}
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Datos</p>
            <div class="space-y-1.5">
                <input x-model="editSector" @change="guardarDetalle()" placeholder="Sector / tipo de negocio"
                       class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-green-400">
                <input x-model="editDistrito" @change="guardarDetalle()" placeholder="Distrito"
                       class="w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-gray-50 focus:outline-none focus:ring-1 focus:ring-green-400">
            </div>
            <div class="flex items-center gap-2 flex-wrap mt-2 text-xs">
                <span class="font-bold px-2 py-0.5 rounded-full" :style="`background:${convActiva?.canal_color}22; color:${convActiva?.canal_color}`" x-text="convActiva?.canal_nombre"></span>
                <span class="text-gray-500" x-text="origenLabel(convActiva?.origen_anuncio)"></span>
            </div>
            <div class="mt-2 space-y-1 text-sm" x-show="lead?.empresa || lead?.producto_interes">
                <div x-show="lead?.empresa"><span class="text-gray-400 text-xs">Empresa:</span> <span x-text="lead?.empresa"></span></div>
                <div x-show="lead?.producto_interes"><span class="text-gray-400 text-xs">Interés:</span> <span x-text="lead?.producto_interes"></span></div>
            </div>
        </div>

        {{-- Pedidos --}}
        <div x-show="lead?.pedidos?.length">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Últimos pedidos</p>
            <template x-for="p in (lead?.pedidos||[])" :key="p.id">
                <div class="flex items-center justify-between text-xs py-1 border-b border-gray-50">
                    <span x-text="'#'+p.id+' · '+p.estado"></span>
                    <span class="font-semibold" x-text="'S/ '+p.total"></span>
                </div>
            </template>
        </div>

        {{-- Notas --}}
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Notas</p>
            <textarea x-model="editNotas" rows="4" placeholder="Notas del cliente..."
                      class="w-full text-xs border border-gray-200 rounded-lg px-2 py-2 focus:outline-none focus:ring-1 focus:ring-green-400 resize-none"></textarea>
            <button @click="guardarDetalle()" class="mt-1 w-full text-xs text-white py-1.5 rounded-lg" style="background:#25d366">Guardar notas</button>
        </div>

        {{-- Acciones de este cliente: lo siguiente que hay que hacer, con recordatorio push. --}}
        <div class="rounded-xl border border-gray-200 p-2.5">
            <div class="flex items-center justify-between mb-1.5">
                <p class="text-[11px] font-bold text-gray-700 uppercase tracking-wide">Siguiente acción</p>
                <a href="/bixocrm/acciones" class="text-[10px] text-gray-400 hover:underline">Ver todas</a>
            </div>
            <template x-for="a in accionesConv" :key="'acc' + a.id">
                <div class="flex items-start gap-2 py-1">
                    <button @click="accionHecha(a)" class="mt-0.5 w-5 h-5 rounded-full border-2 flex-shrink-0 flex items-center justify-center"
                            :class="a.hecho_at ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300'"
                            :title="a.hecho_at ? 'Hecha: toca para reabrirla' : 'Marcar como hecha'">
                        <span x-show="a.hecho_at" class="text-[10px] leading-none">✓</span>
                    </button>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-800" :class="a.hecho_at ? 'line-through text-gray-400' : ''" x-text="a.titulo"></p>
                        <p class="text-[10px] flex items-center gap-1" :class="a.vencida && !a.hecho_at ? 'text-red-600 font-semibold' : 'text-gray-400'">
                            <span x-text="a.vence_texto || 'Sin fecha'"></span>
                            <span x-show="a.avisar_whatsapp" class="text-green-600" :title="'Aviso por WhatsApp ' + a.avisar_minutos + ' min antes'">💬</span>
                        </p>
                    </div>
                </div>
            </template>
            <div class="mt-1.5 space-y-1.5">
                <div class="flex gap-1.5">
                    <select x-model="accionTipo" class="text-[11px] border border-gray-200 rounded-lg px-1.5 py-1.5 flex-shrink-0">
                        <option value="llamada">📞 Llamar</option>
                        <option value="whatsapp">💬 Escribir</option>
                        <option value="reunion">🤝 Reunión</option>
                        <option value="tarea">✅ Tarea</option>
                    </select>
                    <input x-model="accionNueva" @keydown.enter="crearAccion()" :placeholder="accionTipo === 'llamada' ? 'Ej: Llamar para cerrar' : 'Qué hay que hacer'" class="flex-1 min-w-0 text-xs border border-gray-200 rounded-lg px-2 py-1.5">
                </div>
                <div class="flex gap-1.5">
                    <select x-model="accionCuando" class="text-[11px] border border-gray-200 rounded-lg px-1.5 py-1.5 flex-shrink-0">
                        <option value="2h">En 2 h</option>
                        <option value="hoy">Hoy a las…</option>
                        <option value="manana">Mañana 9:00</option>
                        <option value="manana_hora">Mañana a las…</option>
                        <option value="lunes">Lunes 9:00</option>
                        <option value="exacta">Fecha y hora…</option>
                        <option value="">Sin fecha</option>
                    </select>
                    <input x-show="accionCuando === 'hoy' || accionCuando === 'manana_hora'" type="time" x-model="accionHora" class="text-[11px] border border-gray-200 rounded-lg px-1.5 py-1.5 flex-1 min-w-0">
                    <input x-show="accionCuando === 'exacta'" type="datetime-local" x-model="accionFecha" class="text-[11px] border border-gray-200 rounded-lg px-1.5 py-1.5 flex-1 min-w-0">
                    <button @click="crearAccion()" :disabled="!accionNueva.trim()" class="text-xs font-bold text-white px-3 py-1.5 rounded-lg disabled:opacity-50 flex-shrink-0" style="background:#f59e0b">Guardar</button>
                </div>
                {{-- Recordatorio por WhatsApp: opcional, apagado salvo que se active a proposito. --}}
                <label class="flex items-center gap-2 text-[11px] text-gray-600 cursor-pointer" x-show="accionCuando !== ''">
                    <input type="checkbox" x-model="accionAvisar" class="rounded border-gray-300">
                    Avisarme por WhatsApp antes
                </label>
                <div x-show="accionAvisar && accionCuando !== ''" x-cloak class="flex gap-1.5">
                    <select x-model="accionAvisarMin" class="text-[11px] border border-gray-200 rounded-lg px-1.5 py-1.5 flex-shrink-0">
                        <option value="10">10 min antes</option>
                        <option value="30">30 min antes</option>
                        <option value="60">1 h antes</option>
                        <option value="1440">1 día antes</option>
                    </select>
                    <input x-model="accionAvisarTel" placeholder="51955354646" inputmode="numeric" class="flex-1 min-w-0 text-[11px] border border-gray-200 rounded-lg px-2 py-1.5">
                </div>
            </div>
        </div>

        <a :href="'/bixocrm/tratos?nuevo=1&conversacion=' + (convActiva?.id || '')"
           class="block text-center text-xs font-semibold text-white py-2 rounded-lg" style="background:#16a34a">+ Crear trato desde este chat</a>

        <div class="flex gap-2">
            <button @click="archivar(convActiva, !convActiva?.archivado)" class="flex-1 text-xs border border-gray-200 text-gray-600 py-1.5 rounded-lg hover:bg-gray-50" x-text="convActiva?.archivado ? 'Desarchivar' : 'Archivar'"></button>
            <button @click="eliminarChat(convActiva)" class="flex-1 text-xs border border-red-200 text-red-600 py-1.5 rounded-lg hover:bg-red-50">Eliminar chat</button>
        </div>
    </div>
</div>

{{-- Empty: sin conversación activa --}}
<div class="flex-1 flex-col items-center justify-center text-gray-500 hidden md:flex" x-show="!convActiva">
    <svg class="w-16 h-16 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
    <p class="text-sm">Selecciona una conversación</p>
</div>

{{-- Reenviar un mensaje a otra conversacion del negocio --}}
<div x-show="reenvio" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5)" @keydown.escape.window="reenvio=null">
    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl" @click.outside="reenvio=null">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 text-sm">Reenviar a…</h3>
            <p class="text-xs text-gray-500 mt-0.5 truncate" x-text="reenvio?.contenido"></p>
            <input type="text" x-model="buscadorReenvio" placeholder="Buscar nombre o teléfono…" class="mt-2 w-full px-3 py-1.5 text-xs border border-gray-200 rounded-lg bg-gray-50 focus:outline-none">
        </div>
        <div class="max-h-72 overflow-y-auto">
            <template x-for="c in conversaciones.filter(c => c.id !== convActiva?.id && (c.cliente_nombre + ' ' + c.cliente_telefono).toLowerCase().includes(buscadorReenvio.toLowerCase()))" :key="c.id">
                <button @click="reenviarA(c)" class="w-full text-left px-5 py-2.5 hover:bg-green-50 border-b border-gray-50">
                    <p class="text-sm font-medium text-gray-900" x-text="c.cliente_nombre"></p>
                    <p class="text-xs font-medium text-gray-600 tracking-wide" x-text="c.cliente_telefono"></p>
                </button>
            </template>
        </div>
        <div x-show="errorReenvio" x-cloak class="px-5 py-2 text-[11px] text-red-700 bg-red-50" x-text="errorReenvio"></div>
        <div class="px-5 py-3 border-t border-gray-100 text-right">
            <button @click="reenvio=null" class="px-4 py-1.5 text-sm border border-gray-200 rounded-xl">Cancelar</button>
        </div>
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

{{-- El cierre del x-data va DESPUES de todos los modales: si queda aqui, los modales
     de abajo caen fuera de Alpine y no responden (paso con el menu de 3 puntos). --}}


{{-- Modal: plantillas aprobadas por Meta --}}
<div x-show="modalPlantillas" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/40 p-0 sm:p-4" @click.self="modalPlantillas = false">
    <div class="bg-white w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">Plantillas aprobadas por Meta</h3>
            <button @click="modalPlantillas = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-4 overflow-y-auto flex-1 space-y-3">
            <p x-show="cargandoPlantillas" class="text-xs text-gray-500">Consultando a Meta…</p>
            <p x-show="errorPlantillas" x-cloak class="text-xs text-red-600" x-text="errorPlantillas"></p>
            <p x-show="!cargandoPlantillas && !errorPlantillas && plantillas.length === 0" x-cloak class="text-xs text-gray-500">
                No hay plantillas aprobadas. Créalas en Meta: <b>WhatsApp Manager → Herramientas de la cuenta → Plantillas de mensajes</b>; la aprobación suele tardar minutos.
            </p>
            <template x-if="!plantillaSel">
                <div class="space-y-2">
                    <template x-for="p in plantillas" :key="p.nombre + p.idioma">
                        <button @click="elegirPlantilla(p)" class="w-full text-left p-3 rounded-xl border border-gray-200 hover:border-green-400 hover:bg-green-50">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-gray-800" x-text="p.nombre"></span>
                                <span class="text-[10px] text-gray-400" x-text="p.idioma + (p.categoria ? ' · ' + p.categoria.toLowerCase() : '')"></span>
                            </div>
                            <p class="text-[11px] text-gray-600 mt-1 line-clamp-3 whitespace-pre-wrap" x-text="p.cuerpo"></p>
                        </button>
                    </template>
                </div>
            </template>
            <template x-if="plantillaSel">
                <div class="space-y-3">
                    <button @click="plantillaSel = null" class="text-[11px] text-gray-500 hover:underline">← Elegir otra</button>
                    <div class="p-3 rounded-xl bg-[#e7ffdb] text-[13px] text-gray-800 whitespace-pre-wrap" x-text="vistaPreviaPlantilla()"></div>
                    <template x-for="(v, i) in plantillaParams" :key="i">
                        <div>
                            <label class="text-[11px] font-semibold text-gray-600" x-text="'Valor para {{' + (i + 1) + '}}'"></label>
                            <input x-model="plantillaParams[i]" class="w-full mt-1 text-sm border border-gray-200 rounded-lg px-3 py-2" :placeholder="'Ej: ' + (i === 0 ? (convActiva?.cliente_nombre || 'nombre') : 'dato ' + (i + 1))">
                        </div>
                    </template>
                    <p class="text-[10px] text-gray-400">Meta cobra cada plantilla enviada según su categoría.</p>
                </div>
            </template>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 flex justify-end gap-2">
            <button @click="modalPlantillas = false" class="px-3 py-2 text-xs rounded-lg border border-gray-200 text-gray-600">Cancelar</button>
            <button x-show="plantillaSel" @click="enviarPlantilla()" :disabled="enviando || plantillaParams.some(v => !String(v || '').trim())"
                    class="px-3 py-2 text-xs font-semibold rounded-lg text-white disabled:opacity-40" style="background:#25d366">Enviar plantilla</button>
        </div>
    </div>
</div>


{{-- Modal: estados de conversacion del negocio --}}
<div x-show="modalEstados" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="modalEstados = false" style="background:rgba(0,0,0,.4)">
    <div class="bg-white w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <div>
                <h3 class="text-sm font-bold text-gray-900">Estados de conversación</h3>
                <p class="text-[11px] text-gray-500">Los que uses en tu negocio. El chip del chat usa su color.</p>
            </div>
            <button @click="modalEstados = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-3 overflow-y-auto flex-1 space-y-2">
            <template x-for="(e, i) in estadosEdit" :key="i">
                <div class="flex items-center gap-2 border border-gray-200 rounded-xl px-2 py-1.5">
                    <input type="color" x-model="e.color" class="w-8 h-8 rounded border border-gray-200 p-0.5 flex-shrink-0" title="Color del chip">
                    <input type="text" x-model="e.nombre" maxlength="60" placeholder="Nombre del estado" class="flex-1 min-w-0 text-sm border border-gray-200 rounded-lg px-2 py-1.5">
                    <button @click="marcarInicial(i)" :class="e.es_inicial ? 'bg-green-100 text-green-700 border-green-300' : 'text-gray-400 border-gray-200'"
                            class="text-[10px] px-1.5 py-1 rounded-lg border flex-shrink-0" title="Con este estado nacen los chats nuevos">Inicial</button>
                    <button @click="e.es_final = !e.es_final" :class="e.es_final ? 'bg-gray-800 text-white border-gray-800' : 'text-gray-400 border-gray-200'"
                            class="text-[10px] px-1.5 py-1 rounded-lg border flex-shrink-0" title="Cuenta como cerrada">Cierra</button>
                    <div class="flex flex-col flex-shrink-0">
                        <button @click="moverEstado(i, -1)" class="text-gray-300 hover:text-gray-600 text-[10px] leading-none">▲</button>
                        <button @click="moverEstado(i, 1)" class="text-gray-300 hover:text-gray-600 text-[10px] leading-none">▼</button>
                    </div>
                    <button @click="estadosEdit.splice(i, 1)" class="text-red-400 hover:text-red-600 text-sm flex-shrink-0" title="Quitar">🗑</button>
                </div>
            </template>
            <button @click="nuevoEstado()" class="w-full text-xs font-semibold text-green-700 border border-dashed border-green-300 rounded-xl py-2 hover:bg-green-50">+ Añadir estado</button>
            <p class="text-[11px] text-gray-400 px-1">Si quitas un estado, los chats que lo tenían pasan al inicial.</p>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 flex justify-end gap-2">
            <button @click="modalEstados = false" class="px-3 py-2 text-xs rounded-lg border border-gray-200 text-gray-600">Cancelar</button>
            <button @click="guardarEstados()" :disabled="guardandoEstados" class="px-3 py-2 text-xs font-semibold rounded-lg text-white disabled:opacity-50" style="background:#25d366">Guardar</button>
        </div>
    </div>
</div>


{{-- Opciones de un mensaje (reenviar, copiar, eliminar) --}}
<div x-show="menuMensaje" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4" @click.self="menuMensaje = null" style="background:rgba(0,0,0,.4)">
    <div class="bg-white w-full sm:max-w-sm rounded-t-2xl sm:rounded-2xl shadow-xl p-2">
        <p class="text-[11px] text-gray-500 px-3 py-2 line-clamp-2" x-text="menuMensaje?.contenido || 'Adjunto'"></p>
        <button @click="abrirReenvio(menuMensaje); menuMensaje = null" class="w-full text-left px-3 py-3 rounded-xl hover:bg-gray-50 text-sm flex items-center gap-3">↪ Reenviar a otro chat</button>
        <button @click="copiarMensaje(menuMensaje); menuMensaje = null" class="w-full text-left px-3 py-3 rounded-xl hover:bg-gray-50 text-sm flex items-center gap-3">⧉ Copiar texto</button>
        <button @click="eliminarMensaje(menuMensaje)" class="w-full text-left px-3 py-3 rounded-xl hover:bg-red-50 text-sm text-red-600 flex items-center gap-3">🗑 Quitar de mi bandeja</button>
        <button @click="menuMensaje = null" class="w-full px-3 py-2.5 text-xs text-gray-500">Cancelar</button>
    </div>
</div>

</div>
<script>
const CONVERSACIONES_INIT = @json($conversacionesJs);
const RESPUESTAS_INIT = @json($respuestasRapidas);
const ESTADOS_INIT = @json($estados);
const YO = @json(auth()->user()->name ?? '');

function bandeja() {
    return {
        conversaciones: CONVERSACIONES_INIT,
        convActiva: null,
        lead: null,
        etapaAviso: null,      // resultado del ultimo guardado de etapa
        etapaGuardada: null,   // etapa confirmada por el servidor (para revertir)
        mensajes: [],
        cargandoMensajes: false,
        enviando: false,
        textoMensaje: '',
        panelEmojis: false,
        EMOJIS: ['😀','😃','😄','😁','😅','😂','🙂','😉','😊','😍','😘','🤗','🤔','😐','😴','😒','😞','😭','😡','👍','👎','👌','🙏','👏','💪','🤝','👋','☝️','✌️','❤️','🔥','⭐','✨','🎉','🎁','💯','✅','❌','⚠️','❓','❗','💬','📌','📎','📷','🎥','🎤','📄','📦','🚚','🏠','🏪','🛒','💰','💵','💳','🧾','📈','📉','📊','⏰','📅','🕐','📍','📱','☎️','✉️','🔗','🔒','🎯','🚀','👀','🙌','😎','🤩','🥳','😢','🤦','🤷','🙃','😇'],
        ponerEmoji(e) {
            this.textoMensaje = (this.textoMensaje || '') + e;
            this.panelEmojis = false;
            this.$nextTick(() => this.$refs.inputMensaje?.focus());
        },
        errorEnvio: null,
        avisos: (() => { try { return localStorage.getItem('bx_avisos') === '1'; } catch (e) { return false; } })(),
        ultimoAviso: {},
        esMovil: window.innerWidth < 768,
        accionesConv: [], accionNueva: '', accionCuando: 'manana',
        accionTipo: 'llamada', accionHora: '10:00', accionFecha: '',
        // El aviso por WhatsApp es opcional: solo cuando se marca a proposito.
        accionAvisar: false, accionAvisarMin: '10',
        accionAvisarTel: (() => { try { return localStorage.getItem('bx_avisar_tel') || ''; } catch (e) { return ''; } })(),
        deslizX: 0, deslizId: null, deslizX0: null,
        masOpciones: false,
        ventana: { es_meta: false, abierta: true, cierra_at: null },
        modalPlantillas: false, plantillas: [], plantillaSel: null, plantillaParams: [], cargandoPlantillas: false, errorPlantillas: '',
        adjunto: null,
        vista: 'todas',
        menuConv: null,
        menuChat: false,
        mostrarFicha: window.innerWidth >= 1280,
        buscarEnChat: false,
        busquedaChat: '',
        grabando: false,
        grabSegundos: 0, grabTimer: null, grabCancelada: false,
        grabador: null,
        reenvio: null,
        menuMensaje: null,
        buscadorReenvio: '',
        errorReenvio: null,
        busqueda: @json((string) request('q', '')),
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
        estados: ESTADOS_INIT,
        modalEstados: false, estadosEdit: [], guardandoEstados: false,
        pollingInterval: null,
        serverTime: Math.floor(Date.now() / 1000),

        get totalNoLeidos() {
            return this.conversaciones.reduce((s, c) => s + (c.no_leidos || 0), 0);
        },

        contarVista(v) {
            return this.conversaciones.filter(c => this.enVista(c, v)).length;
        },
        /* Estados que cierran el ciclo: no se listan en "Todas" para que la
           bandeja muestre lo que sigue vivo. Antes estaba escrito a mano
           ['cerrado','perdido'] y 'cerrado' NO existe entre los estados que
           configura el negocio, asi que ese filtro nunca encontraba nada. */
        estadoCierra(estado) {
            return ['cerrado', 'perdido', 'venta', 'ganado', 'no_responde'].includes(estado);
        },

        enVista(c, v) {
            const cerrada = this.estadoCierra(c.estado);
            if (v === 'archivadas') return !!c.archivado;
            if (c.archivado) return false;
            // Filtro por un estado concreto ("estado:propuesta").
            if (v.startsWith('estado:')) return c.estado === v.slice(7);
            if (v === 'sin_leer') return c.no_leidos > 0;
            if (v === 'mias') return c.asignado_a === YO;
            if (v === 'sin_asignar') return !c.asignado_a && !cerrada;
            return !cerrada;
        },

        /* Solo los estados que tienen chats, en el orden configurado: mostrar
           diez chips, seis de ellos en (0), no ayuda a nadie. */
        get estadosConChats() {
            const vivos = this.conversaciones.filter(c => !c.archivado);
            return this.estados
                .filter(e => vivos.some(c => c.estado === e.clave))
                .map(e => ({ ...e, color: e.color || '#6b7280' }));
        },
        get conversacionesFiltradas() {
            return this.conversaciones.filter(c => {
                if (!this.enVista(c, this.vista)) return false;
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
            })).sort((a,b) => (b.fijada?1:0) - (a.fijada?1:0) || new Date(b.ultimo_mensaje_at||0) - new Date(a.ultimo_mensaje_at||0));
        },
        previewMensaje(c) {
            const t = c.ultimo_tipo;
            if (t === 'imagen') return '📷 Foto' + (c.ultimo_mensaje && !c.ultimo_mensaje.startsWith('📷') ? ' · ' + c.ultimo_mensaje : '');
            if (t === 'audio') return '🎤 Audio';
            if (t === 'video') return '🎬 Video';
            if (t === 'documento') return '📄 ' + (c.ultimo_mensaje || 'Documento');
            return c.ultimo_mensaje || 'Sin mensajes';
        },
        // Como WhatsApp: si quedo algo escrito sin enviar, eso manda en la lista.
        borradorDe(c) {
            if (this.convActiva && this.convActiva.id === c.id) return (this.textoMensaje || '').trim();
            return this.leerBorrador(c.id).trim();
        },
        esSaliente(m) { return m.direccion === 'saliente' || m.direccion === 'out'; },

        // ── Acciones del cliente (fase 3) ──
        async cargarAcciones(conv) {
            this.accionesConv = [];
            try {
                const r = await fetch('/bixocrm/acciones?json=1&conversacion_id=' + conv.id, { headers: { Accept: 'application/json' } });
                const d = await r.json();
                this.accionesConv = (d.acciones || []).filter(a => !a.hecho_at).slice(0, 5);
            } catch (e) {}
        },
        cuandoAccion() {
            const p = n => String(n).padStart(2, '0');
            const f = d => d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
            const d = new Date();
            const [hh, mm] = (this.accionHora || '10:00').split(':').map(Number);
            if (this.accionCuando === '2h') return f(new Date(Date.now() + 2 * 3600e3));
            if (this.accionCuando === 'hoy') { d.setHours(hh, mm, 0, 0); return f(d); }
            if (this.accionCuando === 'manana') { d.setDate(d.getDate() + 1); d.setHours(9, 0, 0, 0); return f(d); }
            if (this.accionCuando === 'manana_hora') { d.setDate(d.getDate() + 1); d.setHours(hh, mm, 0, 0); return f(d); }
            if (this.accionCuando === 'lunes') { d.setDate(d.getDate() + ((8 - d.getDay()) % 7 || 7)); d.setHours(9, 0, 0, 0); return f(d); }
            if (this.accionCuando === 'exacta') return this.accionFecha || null;
            return null;
        },
        async crearAccion() {
            if (!this.accionNueva.trim() || !this.convActiva) return;
            const r = await fetch('/bixocrm/acciones', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                body: JSON.stringify({
                    titulo: this.accionNueva,
                    tipo: this.accionTipo,
                    vence_at: this.cuandoAccion(),
                    wa_conversacion_id: this.convActiva.id,
                    // Solo si el usuario marco la casilla.
                    avisar_whatsapp: (this.accionAvisar && this.accionAvisarTel.trim()) ? this.accionAvisarTel.trim() : null,
                    avisar_minutos: this.accionAvisar ? Number(this.accionAvisarMin) : null,
                }) });
            const d = await r.json().catch(() => ({}));
            if (d.ok) {
                this.accionesConv.push(d.accion);
                this.accionNueva = '';
                if (this.accionAvisar && this.accionAvisarTel.trim()) {
                    try { localStorage.setItem('bx_avisar_tel', this.accionAvisarTel.trim()); } catch (e) {}
                }
                if (typeof bxAviso === 'function') {
                    bxAviso(d.accion.avisar_whatsapp
                        ? `Acción guardada. Te llega un WhatsApp ${d.accion.avisar_minutos} min antes.`
                        : 'Acción guardada. Te avisará por notificación al vencer.', 'success');
                }
            } else if (typeof bxAviso === 'function') {
                bxAviso('No se pudo guardar la acción', 'error');
            }
        },
        async accionHecha(a) {
            const r = await fetch('/bixocrm/acciones/' + a.id, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' }, body: JSON.stringify({ hecha: !a.hecho_at }) });
            const d = await r.json().catch(() => ({}));
            if (d.ok) {
                Object.assign(a, d.accion);
                // Se avisa de lo que acaba de pasar: antes desaparecia sin explicacion.
                if (typeof bxAviso === 'function') {
                    bxAviso(a.hecho_at ? 'Acción marcada como hecha (toca el círculo para reabrirla)' : 'Acción pendiente otra vez', 'success');
                }
            }
        },

        // ── Deslizar un chat en movil: derecha = no leido, izquierda = archivar ──
        deslizarInicio(e, conv) { if (!this.esMovil) return; this.deslizX0 = e.touches[0].clientX; this.deslizId = conv.id; this.deslizX = 0; },
        deslizarMover(e) { if (this.deslizX0 === null) return; this.deslizX = Math.max(-110, Math.min(110, e.touches[0].clientX - this.deslizX0)); },
        deslizarFin(conv) {
            if (this.deslizX0 === null) return;
            const dx = this.deslizX; this.deslizX0 = null; this.deslizX = 0; this.deslizId = null;
            if (dx < -80) { this.archivar(conv, !conv.archivado); if (typeof bxAviso === 'function') bxAviso(conv.archivado ? 'Chat desarchivado' : 'Chat archivado'); }
            else if (dx > 80) { this.marcarNoLeida(conv); if (typeof bxAviso === 'function') bxAviso('Marcado como no leído'); }
        },

        // ── Plantillas de Meta (ventana de 24 h) ──
        async abrirPlantillas() {
            if (!this.convActiva) return;
            this.modalPlantillas = true; this.plantillaSel = null; this.errorPlantillas = ''; this.cargandoPlantillas = true;
            try {
                const r = await fetch(`/bixocrm/${this.convActiva.id}/plantillas`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const d = await r.json().catch(() => ({}));
                this.plantillas = d.plantillas || [];
                if (!d.ok) this.errorPlantillas = d.error || 'No se pudieron leer las plantillas.';
            } catch (e) { this.errorPlantillas = 'No se pudieron leer las plantillas.'; }
            this.cargandoPlantillas = false;
        },
        elegirPlantilla(p) {
            this.plantillaSel = p;
            this.plantillaParams = Array.from({ length: p.parametros || 0 }, (_, i) => i === 0 ? (this.convActiva?.cliente_nombre || '') : '');
        },
        vistaPreviaPlantilla() {
            let t = this.plantillaSel?.cuerpo || '';
            this.plantillaParams.forEach((v, i) => { t = t.split('{{' + (i + 1) + '}}').join(v || '{{' + (i + 1) + '}}'); });
            return t;
        },
        async enviarPlantilla() {
            if (!this.plantillaSel || !this.convActiva || this.enviando) return;
            this.enviando = true;
            try {
                const r = await fetch(`/bixocrm/${this.convActiva.id}/plantilla`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: JSON.stringify({ nombre: this.plantillaSel.nombre, idioma: this.plantillaSel.idioma, cuerpo: this.plantillaSel.cuerpo, parametros: this.plantillaParams }),
                });
                const d = await r.json().catch(() => ({}));
                if (d.mensaje) { this.mensajes.push(d.mensaje); this.$nextTick(() => this.scrollBottom()); }
                if (d.ok) { this.modalPlantillas = false; this.errorEnvio = null; }
                else this.errorEnvio = d.error || 'Meta no aceptó la plantilla.';
            } catch (e) { this.errorEnvio = 'No se pudo enviar la plantilla.'; }
            this.enviando = false;
        },

        // ── Acciones de chat (estilo WhatsApp) ──
        async patchConv(conv, datos) {
            const res = await fetch(`/bixocrm/${conv.id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify(datos),
            });
            if (res.ok) {
                const c = this.conversaciones.find(x => x.id === conv.id);
                if (c) Object.assign(c, datos);
                if (this.convActiva?.id === conv.id) Object.assign(this.convActiva, datos);
            }
            this.menuConv = null;
            return res.ok;
        },
        fijar(conv) { this.patchConv(conv, { fijada: !conv.fijada }); },
        archivar(conv, si) {
            this.patchConv(conv, { archivado: si });
            if (si && this.convActiva?.id === conv.id) this.convActiva = null;
        },
        marcarNoLeida(conv) {
            this.patchConv(conv, { no_leidos: 1 });
            if (this.convActiva?.id === conv.id) this.convActiva = null;
        },
        asignar(conv, a) { this.patchConv(conv, { asignado_a: a }); },
        async eliminarChat(conv) {
            const ok = typeof bxConfirmar === 'function'
                ? await bxConfirmar({ titulo: 'Eliminar chat', mensaje: 'Se borra de tu bandeja con todos sus mensajes y adjuntos. En el teléfono del cliente no cambia nada.', boton: 'Eliminar' })
                : confirm('¿Eliminar este chat de la bandeja? No se puede deshacer.');
            if (!ok) return;
            const res = await fetch(`/bixocrm/${conv.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            if (res.ok) {
                this.conversaciones = this.conversaciones.filter(x => x.id !== conv.id);
                if (this.convActiva?.id === conv.id) { this.convActiva = null; this.mensajes = []; }
            }
            this.menuConv = null;
        },
        // Quita el mensaje del CRM. En el telefono del cliente se queda: la API de Meta
        // no permite borrar lo ya enviado, asi que no se ofrece esa opcion.
        async eliminarMensaje(msg) {
            this.menuMensaje = null;
            if (!this.convActiva) return;
            const res = await fetch(`/bixocrm/${this.convActiva.id}/mensajes/${msg.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const d = await res.json().catch(() => ({}));
            if (d.ok) {
                this.mensajes = this.mensajes.filter(m => m.id !== msg.id);
                if (typeof bxAviso === 'function') bxAviso('Quitado de tu bandeja (el cliente lo sigue viendo)', 'success');
            } else if (typeof bxAviso === 'function') {
                bxAviso(d.error || 'No se pudo eliminar', 'error');
            }
        },
        // ── Borradores: lo escrito y no enviado sobrevive al cambiar de chat ──
        claveBorrador(id) { return 'bx_borrador_' + id; },
        guardarBorrador() {
            if (!this.convActiva) return;
            const t = (this.textoMensaje || '').trim();
            try {
                if (t) localStorage.setItem(this.claveBorrador(this.convActiva.id), this.textoMensaje);
                else localStorage.removeItem(this.claveBorrador(this.convActiva.id));
            } catch (e) {}
        },
        leerBorrador(id) { try { return localStorage.getItem(this.claveBorrador(id)) || ''; } catch (e) { return ''; } },
        olvidarBorrador(id) { try { localStorage.removeItem(this.claveBorrador(id)); } catch (e) {} },
        hayBorrador(id) { return this.leerBorrador(id) !== ''; },

        copiarMensaje(msg) { navigator.clipboard?.writeText(msg.contenido || '').catch(() => {}); },

        get respuestasFiltradas() {
            if (!this.buscadorRespuestas) return this.respuestas;
            const q = this.buscadorRespuestas.toLowerCase();
            return this.respuestas.filter(r =>
                r.nombre.toLowerCase().includes(q) || r.texto.toLowerCase().includes(q)
            );
        },

        // ── Avisos: sonido + notificacion del navegador (tambien en el celular con la pestaña abierta) ──
        pushListo: false,
        sesionCaida: false,
        bannerOculto: (() => { try { return sessionStorage.getItem('bx_banner_avisos') === '1'; } catch (e) { return false; } })(),
        aviso(msg, tipo) { if (typeof bxAviso === 'function') bxAviso(msg, tipo || 'info'); else alert(msg); },
        async alternarAvisos() {
            // Solo se apaga si ya estaba completo (permiso + suscripcion); si no, se completa.
            if (this.avisos && this.pushListo) {
                this.avisos = false; this.pushListo = false;
                try { localStorage.setItem('bx_avisos', '0'); } catch (e) {}
                this.aviso('Avisos apagados en este dispositivo.');
                return;
            }
            if (!('Notification' in window)) { this.aviso('Este navegador no soporta notificaciones. En iPhone: agrega el CRM a la pantalla de inicio desde Safari y ábrelo desde ahí.', 'error'); return; }
            let permiso = Notification.permission;
            if (permiso !== 'granted') {
                try { permiso = await Notification.requestPermission(); } catch (e) {}
            }
            if (permiso !== 'granted') {
                this.aviso('El navegador tiene las notificaciones bloqueadas para arindg.com. Ábrelas en Configuración del sitio y vuelve a tocar la campana.', 'error');
                return;
            }
            this.avisos = true;
            try { localStorage.setItem('bx_avisos', '1'); } catch (e) {}
            this.sonar();
            await this.suscribirPush(true);
        },
        // Push real (llega aunque el CRM este cerrado, si esta instalado como app o Chrome sigue abierto).
        async suscribirPush(avisar = false) {
            try {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) { if (avisar) this.aviso('Este navegador no soporta push. Instala el CRM como app (menú ⋮ → Instalar) y actívalo desde ahí.', 'error'); return; }
                if (Notification.permission !== 'granted') return;
                const reg = await Promise.race([navigator.serviceWorker.ready, new Promise((_, rj) => setTimeout(() => rj(new Error('sw')), 8000))]);
                // Si la sesion caduco, esto responde 401 y no trae clave: hay que
                // decir "vuelve a entrar", no reventar con un error de JavaScript.
                const resClave = await fetch('/bixocrm/push/clave', { headers: { 'Accept': 'application/json' } });
                if (resClave.status === 401 || resClave.status === 419) throw new Error('sesion');
                const { clave } = await resClave.json().catch(() => ({}));
                if (!clave) throw new Error('sin_clave');
                const raw = Uint8Array.from(atob(clave.replace(/-/g, '+').replace(/_/g, '/').padEnd(clave.length + (4 - clave.length % 4) % 4, '=')), c => c.charCodeAt(0));
                let sub = await reg.pushManager.getSubscription();
                if (!sub) sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: raw });
                const r = await fetch('/bixocrm/push/suscribir', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(sub.toJSON()),
                });
                if (r.status === 401 || r.status === 419) throw new Error('sesion');
                if (!r.ok) throw new Error('registro ' + r.status);
                this.pushListo = true;
                if (avisar) {
                    // Prueba real: si llega, el celular esta listo.
                    const pr = await fetch('/bixocrm/push/probar', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
                    const d = await pr.json().catch(() => ({}));
                    this.aviso(d.ok ? 'Notificaciones activadas ✅ Te acaba de llegar una de prueba.' : 'Suscrito, pero la prueba no llegó (respuesta ' + (d.enviadas ?? '?') + '). Avísame para revisar.', d.ok ? 'success' : 'error');
                }
            } catch (e) {
                console.warn('push', e);
                if (avisar) {
                    const m = (e && e.message) || '';
                    let txt = 'No se pudo activar el push: ' + (m === 'sw' ? 'el service worker no cargó; recarga la página e inténtalo de nuevo.' : (m || 'error desconocido'));
                    if (m === 'sesion')   txt = 'Tu sesión caducó. Vuelve a entrar al CRM y toca Activar otra vez.';
                    if (m === 'sin_clave') txt = 'El servidor no devolvió la clave de notificaciones. Recarga la página e inténtalo de nuevo.';
                    if (/push service error/i.test(m)) {
                        txt = 'El navegador no pudo registrarse en el servicio de push de Google. En Android: Ajustes → Aplicaciones → Chrome → Notificaciones → permitir (y apaga "No molestar"); luego vuelve a tocar Activar. En Brave: Ajustes → Privacidad → "Usar los servicios de Google para la mensajería push".';
                    }
                    this.aviso(txt, 'error');
                }
            }
        },
        sonar() {
            try {
                // Un solo contexto reutilizado: crear uno por aviso agota el limite del
                // navegador y a partir de ahi deja de sonar sin avisar.
                if (!this._audio) this._audio = new (window.AudioContext || window.webkitAudioContext)();
                const ctx = this._audio;
                // Chrome lo deja "suspendido" hasta que el usuario interactua con la pagina.
                if (ctx.state === 'suspended') ctx.resume().catch(() => {});
                [[880, 0], [1175, 0.12]].forEach(([f, t]) => {
                    const o = ctx.createOscillator(), g = ctx.createGain();
                    o.type = 'sine'; o.frequency.value = f;
                    g.gain.setValueAtTime(0.0001, ctx.currentTime + t);
                    g.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + t + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + t + 0.25);
                    o.connect(g).connect(ctx.destination);
                    o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.3);
                });
            } catch (e) {}
        },
        avisar(conv) {
            // Basta con tener el push activo o la campana encendida: antes, con el push
            // activado y la campana apagada, no sonaba nada estando el CRM abierto.
            if (!this.avisos && !this.pushListo) return;
            this.sonar();
            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    const n = new Notification(conv.cliente_nombre || conv.cliente_telefono, {
                        body: (conv.ultimo_mensaje || 'Mensaje nuevo').slice(0, 120),
                        icon: '/favicon.ico', tag: 'bx-conv-' + conv.id, renotify: true,
                    });
                    n.onclick = () => { window.focus(); const c = this.conversaciones.find(x => x.id === conv.id); if (c) this.abrirConversacion(c); n.close(); };
                } catch (e) {}
            }
        },

        // Cierra lo que este abierto por encima de la lista; devuelve true si cerro algo.
        cerrarCapaSuperior() {
            if (this.modalEstados) { this.modalEstados = false; return true; }
            if (this.modalRespuestas) { this.modalRespuestas = false; return true; }
            if (this.modalPlantillas) { this.modalPlantillas = false; return true; }
            if (this.panelEmojis) { this.panelEmojis = false; return true; }
            if (this.menuMensaje) { this.menuMensaje = null; return true; }
            if (this.reenvio) { this.reenvio = null; return true; }
            if (this.mostrarFicha && this.esMovil) { this.mostrarFicha = false; return true; }
            if (this.convActiva) { this.convActiva = null; this.mensajes = []; return true; }
            return false;
        },
        volverALista() {
            // Si el chat se abrio apilando historial, retroceder dispara popstate y cierra.
            this.guardarBorrador();
            if (this.esMovil && history.state && history.state.bxChat) { history.back(); return; }
            this.convActiva = null;
            this.mensajes = [];
        },

        init() {
            this.arrancarSondeo();
            // Volver a la pestaña: consultar YA, sin esperar al siguiente turno.
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) { this.poll(); }
                this.arrancarSondeo();
            });
            // Cerrar o recargar la pestaña no debe llevarse lo escrito a medias.
            window.addEventListener('beforeunload', () => this.guardarBorrador());
            // Boton "atras" del telefono: cierra el chat o el modal abierto, no la app.
            window.addEventListener('popstate', () => {
                if (!this.cerrarCapaSuperior() && this.esMovil) {
                    // Nada que cerrar: se deja salir (se repone el paso para no quedar atrapado).
                    try { history.pushState({ bxSalida: 1 }, '', location.pathname + location.search); } catch (e) {}
                    history.back();
                }
            });
            if (this.avisos) setTimeout(() => this.suscribirPush(), 1500);
            // Abrir la conversacion que pide la URL (la notificacion push llega con ?conversacion=ID).
            const pedida = new URLSearchParams(location.search).get('conversacion');
            if (pedida) { const c = this.conversaciones.find(x => String(x.id) === pedida); if (c) this.$nextTick(() => this.abrirConversacion(c)); }
            // Chrome exige un gesto para el audio: el primer clic "desbloquea" el contexto.
            document.addEventListener('click', () => { try { new (window.AudioContext || window.webkitAudioContext)().resume(); } catch (e) {} }, { once: true });
        },

        async abrirConversacion(conv) {
            // Un paso en el historial por cada chat abierto: asi el boton "atras" del
            // telefono vuelve a la lista en vez de cerrar la app (queja real en movil).
            if (this.esMovil && !this.convActiva) {
                try { history.pushState({ bxChat: conv.id }, '', location.pathname + location.search); } catch (e) {}
            }
            this.guardarBorrador();      // lo escrito en el chat anterior no se pierde
            this.convActiva = conv;
            this.textoMensaje = this.leerBorrador(conv.id);
            this.estadoActual = conv.estado;
            this.editNombre   = conv.cliente_nombre   || '';
            this.editSector   = conv.cliente_sector   || '';
            this.editDistrito = conv.cliente_distrito || '';
            this.editNotas    = conv.notas            || '';
            this.mensajes = [];
            this.cargandoMensajes = true;
            this.cargarLead(conv); // ficha CRM del cliente (columna derecha)

            const res = await fetch(`/bixocrm/${conv.id}/mensajes`, {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await res.json();
            this.mensajes = data.mensajes;
            this.ventana = data.ventana || { es_meta: false, abierta: true, cierra_at: null };
            this.cargarAcciones(conv);
            this.cargandoMensajes = false;
            conv.no_leidos = 0;
            this.$nextTick(() => this.scrollBottom());
        },

        // Carga la ficha CRM del cliente (scoring, pipeline, historial).
        async cargarLead(conv) {
            this.lead = null;
            this.etapaAviso = null;
            this.etapaGuardada = null;
            try {
                const res = await fetch(`/bixocrm/lead`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        telefono: conv.cliente_telefono || '',
                        nombre: conv.cliente_nombre || '',
                    }),
                });
                if (res.ok) this.lead = await res.json();
                this.etapaGuardada = this.lead?.etapa ?? null;
                this.etapaAviso = null;
            } catch (e) { this.lead = { nombre: conv.cliente_nombre, telefono: conv.cliente_telefono }; }
        },

        async guardarEtapa() {
            // Sin ficha cargada no hay a quien guardarle la etapa: se avisa en
            // vez de no hacer nada (antes el select se movia y no pasaba nada).
            if (!this.lead?.id) {
                this.etapaAviso = { ok: false, texto: 'No se pudo: la ficha del cliente no cargo' };
                return;
            }

            const anterior = this.etapaGuardada;
            const elegida  = this.lead.etapa;
            this.etapaAviso = { ok: true, texto: 'Guardando...' };

            try {
                const res = await fetch(`/bixocrm/lead/${this.lead.id}/etapa`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ etapa: elegida }),
                });

                // Sin mirar res.ok el guardado fallaba en silencio: con la
                // pestana abierta un rato el token CSRF vence y responde 419,
                // el select se quedaba en la etapa nueva y el servidor con la
                // vieja, asi que al recargar "no habia guardado".
                if (!res.ok) {
                    this.lead.etapa = anterior;
                    this.etapaAviso = {
                        ok: false,
                        texto: res.status === 419
                            ? 'Caduco la sesion: recarga la pagina (F5) y vuelve a intentar'
                            : 'No se pudo guardar (error ' + res.status + ')',
                    };
                    return;
                }

                this.etapaGuardada = elegida;
                this.etapaAviso = { ok: true, texto: 'Etapa guardada' };
                setTimeout(() => { this.etapaAviso = null; }, 2500);
            } catch (e) {
                this.lead.etapa = anterior;
                this.etapaAviso = { ok: false, texto: 'Sin conexion: no se guardo' };
            }
        },

        async enviarMensaje() {
            if ((!this.textoMensaje.trim() && !this.adjunto) || !this.convActiva || this.enviando) return;
            this.enviando = true;
            const contenido = this.textoMensaje;
            this.olvidarBorrador(this.convActiva.id);
            const archivo = this.adjunto;
            this.textoMensaje = '';

            // Multipart siempre: el adjunto viaja como archivo y Meta lo
            // descarga desde la URL publica que le da el servidor.
            const fd = new FormData();
            fd.append('contenido', contenido);
            if (archivo) fd.append('archivo', archivo);
            const res = await fetch(`/bixocrm/${this.convActiva.id}/enviar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));

            // El mensaje se pinta igual (quedo guardado en el historial), pero
            // si WhatsApp no lo entrego hay que DECIRLO: el texto vuelve al
            // campo para poder reintentar.
            if (data.mensaje) {
                this.mensajes.push(data.mensaje);
                this.convActiva.ultimo_mensaje = data.mensaje.contenido;
                this.convActiva.ultimo_mensaje_at = new Date().toISOString();
                this.$nextTick(() => this.scrollBottom());
            }
            if (!data.ok) {
                this.errorEnvio = data.error || (data.errors ? Object.values(data.errors).flat().join(' ') : 'No se pudo enviar el mensaje a WhatsApp.');
                this.textoMensaje = contenido;
                this.guardarBorrador();   // si no salio, que no se pierda al cambiar de chat
            } else {
                this.errorEnvio = null;
                this.adjunto = null;
                if (this.$refs.inputArchivo) this.$refs.inputArchivo.value = '';
            }
            this.enviando = false;
            this.$refs.inputMensaje?.focus();
        },

        // Ctrl+V con una imagen en el portapapeles (captura de pantalla, foto
        // copiada): entra como adjunto, sin pasar por el explorador de archivos.
        pegarArchivo(e) {
            const items = Array.from(e.clipboardData?.items || []);
            const it = items.find(i => i.kind === 'file' && (i.type.startsWith('image/') || i.type === 'application/pdf'));
            if (!it) return;
            const f = it.getAsFile();
            if (!f) return;
            e.preventDefault();
            const ext = f.type === 'application/pdf' ? 'pdf' : (f.type.split('/')[1] || 'png');
            this.adjunto = f.name && f.name !== 'image.png' ? f : new File([f], 'pegado-' + Date.now() + '.' + ext, { type: f.type });
        },

        // Nota de voz: el navegador graba webm/opus; el servidor la convierte a
        // ogg/opus (lo unico que WhatsApp acepta como nota de voz).
        async grabarNota() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const trozos = [];
                this.grabador = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
                this.grabador.ondataavailable = e => { if (e.data.size) trozos.push(e.data); };
                this.grabador.onstop = () => {
                    stream.getTracks().forEach(t => t.stop());
                    this.grabando = false;
                    if (this.grabCancelada) return;
                    this.adjunto = new File(trozos, 'nota-' + Date.now() + '.webm', { type: 'audio/webm' });
                    this.enviarMensaje();
                };
                this.grabador.start();
                this.grabando = true;
                this.grabCancelada = false;
                this.grabSegundos = 0;
                this.grabTimer = setInterval(() => { this.grabSegundos++; }, 1000);
            } catch (e) {
                this.errorEnvio = 'No se pudo acceder al micrófono.';
            }
        },
        pararGrabacion() { clearInterval(this.grabTimer); this.grabador?.stop(); },
        cancelarGrabacion() { this.grabCancelada = true; clearInterval(this.grabTimer); this.grabador?.stop(); },
        grabTiempo() { const s = this.grabSegundos || 0; return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); },

        abrirReenvio(msg) { this.reenvio = msg; this.errorReenvio = null; this.buscadorReenvio = ''; },

        async reenviarA(conv) {
            if (!this.reenvio || !this.convActiva) return;
            const res = await fetch(`/bixocrm/${this.convActiva.id}/reenviar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ mensaje_id: this.reenvio.id, destino_id: conv.id }),
            });
            const data = await res.json().catch(() => ({}));
            if (data.ok) {
                this.reenvio = null;
                conv.ultimo_mensaje = data.mensaje?.contenido || conv.ultimo_mensaje;
                if (typeof bxAviso === 'function') bxAviso('Reenviado a ' + conv.cliente_nombre, 'success');
            } else {
                this.errorReenvio = data.error || 'No se pudo reenviar.';
            }
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

        /* Estados del chat que significan lo mismo que una etapa del embudo.
           El panel muestra UN solo selector (Estado); la etapa se mantiene al
           dia por dentro para que Pipeline, Tratos y Copilot sigan leyendola. */
        /* Acuse del ultimo mensaje en la LISTA de chats. Mismos criterios que
           el acuse de dentro del chat, para que no digan cosas distintas. */
        esSalienteConv(conv) {
            return conv.ultimo_direccion === 'saliente' || conv.ultimo_direccion === 'out';
        },

        acuseConv(conv) {
            const e = conv.ultimo_estado;
            if (e === 'leido' || e === 'entregado') return '✓✓';
            if (e === 'pendiente' || e === 'fallido') return '⚠';
            return '✓';
        },

        etapaNombre(etapa) {
            return ({
                prospecto: 'Prospecto', contactado: 'Contactado',
                propuesta: 'Propuesta', negociacion: 'Negociacion',
                ganado: 'Ganado', perdido: 'Perdido',
            })[etapa] || etapa;
        },

        etapaDeEstado(estado) {
            return ({
                nuevo: 'prospecto',
                contactado: 'contactado',
                seguimiento: 'contactado',
                demo_enviada: 'propuesta',
                propuesta: 'propuesta',
                negociacion: 'negociacion',
                proyecto: 'negociacion',
                venta: 'ganado',
                perdido: 'perdido',
            })[estado] || null;
        },

        async cambiarEstado(estado) {
            if (!this.convActiva) return;

            const anterior = this.convActiva.estado;
            this.etapaAviso = { ok: true, texto: 'Guardando...' };

            try {
                const res = await fetch(`/bixocrm/${this.convActiva.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ estado }),
                });

                // Antes no se miraba la respuesta: con la pestana abierta un
                // rato el token CSRF vence, el servidor responde 419 y el
                // selector se quedaba en el valor nuevo sin haber guardado.
                if (!res.ok) {
                    this.estadoActual = anterior;
                    this.etapaAviso = {
                        ok: false,
                        texto: res.status === 419
                            ? 'Caduco la sesion: recarga la pagina (F5) y vuelve a intentar'
                            : 'No se pudo guardar (error ' + res.status + ')',
                    };
                    return;
                }

                this.convActiva.estado = estado;
                const c = this.conversaciones.find(x => x.id === this.convActiva.id);
                if (c) c.estado = estado;

                // Arrastrar la etapa del cliente cuando el estado equivale a una.
                const etapa = this.etapaDeEstado(estado);
                if (etapa && this.lead?.id && this.lead.etapa !== etapa) {
                    this.lead.etapa = etapa;
                    await this.guardarEtapa();
                    return;
                }

                this.etapaAviso = { ok: true, texto: 'Estado guardado' };
                setTimeout(() => { this.etapaAviso = null; }, 2500);
            } catch (e) {
                this.estadoActual = anterior;
                this.etapaAviso = { ok: false, texto: 'Sin conexion: no se guardo' };
            }
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

        archivarConversacion() { if (this.convActiva) this.archivar(this.convActiva, true); },
        async archivarConversacionLegacy() {
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

        /** Delante 1,5 s (se esta atendiendo); de fondo 5 s, para no gastar de mas. */
        arrancarSondeo() {
            const ms = document.hidden ? 5000 : 1500;
            if (this._sondeoMs === ms && this.pollingInterval) return;
            this._sondeoMs = ms;
            if (this.pollingInterval) clearInterval(this.pollingInterval);
            this.pollingInterval = setInterval(() => this.poll(), ms);
        },

        async poll() {
            try {
                const params = new URLSearchParams({ since: this.serverTime });
                if (this.convActiva) params.append('conversacion_id', this.convActiva.id);

                const res = await fetch(`/bixocrm/poll?${params}`, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                if (res.status === 401 || res.status === 419) { this.sesionCaida = true; return; }
                if (!res.ok) return;
                this.sesionCaida = false;
                const data = await res.json();
                this.serverTime = data.server_time;

                data.conversaciones_actualizadas.forEach(updated => {
                    const idx = this.conversaciones.findIndex(c => c.id === updated.id);
                    // Mensaje NUEVO del cliente (no visto aun): sonido + notificacion.
                    const entrante = (updated.ultimo_direccion === 'in' || updated.ultimo_direccion === 'entrante')
                        && updated.ultimo_mensaje_at && this.ultimoAviso[updated.id] !== updated.ultimo_mensaje_at
                        && (idx < 0 || this.conversaciones[idx].ultimo_mensaje_at !== updated.ultimo_mensaje_at);
                    if (entrante) {
                        this.ultimoAviso[updated.id] = updated.ultimo_mensaje_at;
                        const enPantalla = this.convActiva?.id === updated.id && !document.hidden;
                        if (!enPantalla) this.avisar(updated);
                    }
                    if (idx >= 0) {
                        // Nunca pisar con undefined lo que el sondeo no trae.
                        Object.keys(updated).forEach(k => { if (updated[k] !== undefined) this.conversaciones[idx][k] = updated[k]; });
                        if (this.convActiva?.id === updated.id && updated.bot_activo !== undefined) this.convActiva.bot_activo = updated.bot_activo;
                    } else {
                        this.conversaciones.push(updated);
                    }
                });

                // Insignia con los no leidos en el icono de la app instalada.
                try { if ('setAppBadge' in navigator) { this.totalNoLeidos > 0 ? navigator.setAppBadge(this.totalNoLeidos) : navigator.clearAppBadge(); } } catch (e) {}
                (data.estados || []).forEach(e => {
                    const m = this.mensajes.find(x => x.id === e.id);
                    if (m) Object.assign(m, e);
                });
                if (data.mensajes_nuevos?.length > 0) {
                    const existingIds = new Set(this.mensajes.map(m => m.id));
                    data.mensajes_nuevos.forEach(m => {
                        if (!existingIds.has(m.id)) {
                            this.mensajes.push(m);
                            if (!this.esSaliente(m)) { this.ventana.abierta = true; this.ventana.cierra_at = new Date(Date.now() + 86400000).toISOString(); }
                            this.$nextTick(() => this.scrollBottom());
                        }
                    });
                }
            } catch(e) { /* un corte de red puntual no se avisa: el siguiente sondeo reintenta */ }
        },

        usarRespuesta(r) {
            this.textoMensaje = r.texto;
            this.modalRespuestas = false;
            this.$nextTick(() => this.$refs.inputMensaje?.focus());
        },

        // El chip usa el color del estado con transparencia (fondo) y el color pleno (texto).
        estadoBadge(estado) {
            const e = this.estados.find(x => x.clave === estado);
            return e ? `background:${e.color}22;color:${e.color}` : 'background:#f3f4f6;color:#374151';
        },

        estadoLabel(estado) {
            return this.estados.find(x => x.clave === estado)?.nombre || estado || '';
        },

        // ── Gestionar estados (crear, renombrar, color, orden, borrar) ──
        abrirEstados() {
            this.estadosEdit = this.estados.map((e, i) => ({ ...e, id: e.id ?? null, orden: i }));
            this.modalEstados = true;
            this.cargarEstadosParaEditar();
        },
        async cargarEstadosParaEditar() {
            try {
                const r = await fetch('/bixocrm/estados', { headers: { Accept: 'application/json' } });
                const d = await r.json();
                if (d.estados) { this.estados = d.estados; this.estadosEdit = d.estados.map(e => ({ ...e })); }
            } catch (e) {}
        },
        nuevoEstado() { this.estadosEdit.push({ id: null, clave: null, nombre: '', color: '#64748b', es_inicial: false, es_final: false }); },
        moverEstado(i, d) {
            const j = i + d;
            if (j < 0 || j >= this.estadosEdit.length) return;
            const t = this.estadosEdit[i]; this.estadosEdit[i] = this.estadosEdit[j]; this.estadosEdit[j] = t;
        },
        marcarInicial(i) { this.estadosEdit.forEach((e, k) => e.es_inicial = k === i); },
        async guardarEstados() {
            const lista = this.estadosEdit.filter(e => (e.nombre || '').trim());
            if (!lista.length) { if (typeof bxAviso === 'function') bxAviso('Deja al menos un estado', 'error'); return; }
            this.guardandoEstados = true;
            try {
                const r = await fetch('/bixocrm/estados', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                    body: JSON.stringify({ estados: lista }),
                });
                const d = await r.json().catch(() => ({}));
                if (d.ok) {
                    this.estados = d.estados;
                    this.modalEstados = false;
                    if (typeof bxAviso === 'function') bxAviso('Estados guardados', 'success');
                } else if (typeof bxAviso === 'function') {
                    bxAviso('No se pudo guardar: ' + (d.message || 'revisa los nombres'), 'error');
                }
            } catch (e) { if (typeof bxAviso === 'function') bxAviso('No se pudo guardar', 'error'); }
            this.guardandoEstados = false;
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

        /* Hora para hoy, "Ayer", dia de la semana esta semana y fecha corta
           mas atras: "4d" obligaba a calcular de que dia hablaba. */
        tiempoRelativo(iso) {
            if (!iso) return '';
            const d = new Date(iso);
            if (isNaN(d)) return '';
            const diff = Math.floor((Date.now() - d) / 1000);

            if (diff < 60) return 'ahora';
            if (diff < 3600) return Math.floor(diff/60) + ' min';

            const hoy = new Date();
            const ayer = new Date(); ayer.setDate(ayer.getDate() - 1);
            if (d.toDateString() === hoy.toDateString()) {
                return d.toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'});
            }
            if (d.toDateString() === ayer.toDateString()) return 'Ayer';
            if (diff < 7 * 86400) {
                const dia = d.toLocaleDateString('es-PE', {weekday:'short'}).replace('.', '');
                return dia.charAt(0).toUpperCase() + dia.slice(1);
            }
            return d.toLocaleDateString('es-PE', {day:'2-digit', month:'2-digit'});
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

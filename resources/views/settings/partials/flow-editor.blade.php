{{-- ═══════════════════════════════════════════════════════════════════
     EDITOR VISUAL DE FLUJO DE ESTADOS
     Canvas arrastrable (SVG) + panel lateral de configuración.
     Recibe: $selP (proyecto)
═══════════════════════════════════════════════════════════════════ --}}
@php
    $ed_catalog = \App\Modules\Ventas\Support\OrderFlow::catalog($selP);
    $ed_active  = \App\Modules\Ventas\Support\OrderFlow::activeKeys($selP);
    $ed_core    = \App\Modules\Ventas\Support\OrderFlow::coreKeys($selP);
    $ed_states  = \App\Modules\Ventas\Support\OrderFlow::activeStates($selP);
    $ed_config  = \App\Modules\Ventas\Support\OrderFlow::config($selP);
    $ed_pos     = \App\Modules\Ventas\Support\OrderFlow::positions($selP);
    $ed_trans   = \App\Modules\Ventas\Support\OrderFlow::effectiveTransitions($selP);

    // Preparar nodos para JS con posición (guardada o auto en fila)
    $ed_nodes = [];
    $i = 0;
    // Auto-layout en cuadrícula: 3 columnas para que ningún nodo se salga del canvas.
    $colW = 200; $rowH = 110; $cols = 3; $padX = 30; $padY = 30;
    foreach ($ed_states as $k => $st) {
        $cfg = $ed_config[$k] ?? [];
        $t   = \App\Modules\Ventas\Support\OrderFlow::times($selP, $k);
        $col = $i % $cols;
        $row = intdiv($i, $cols);
        $ed_nodes[] = [
            'key'    => $k,
            'label'  => $st['label'],
            'icon'   => $st['icon'],
            'color'  => $st['color'],
            'core'   => in_array($k, $ed_core),
            'x'      => $ed_pos[$k]['x'] ?? ($padX + $col * $colW),
            'y'      => $ed_pos[$k]['y'] ?? ($padY + $row * $rowH),
            'notify' => (bool) ($st['notify'] ?? false),
            'alert'  => (bool) ($cfg['alert'] ?? false),
            'wa_message'    => $cfg['wa_message'] ?? '',
            'time_target'   => $t['target'] ?? '',
            'time_warn'     => $t['warn'] ?? '',
            'time_critical' => $t['critical'] ?? '',
        ];
        $i++;
    }
    // Estados inactivos (para poder añadirlos desde el editor)
    $ed_inactive = [];
    foreach ($ed_catalog as $k => $st) {
        if (!in_array($k, $ed_active)) {
            $ed_inactive[] = ['key'=>$k,'label'=>$st['label'],'icon'=>$st['icon'],'color'=>$st['color']];
        }
    }
@endphp

{{-- Datos del editor en variable global (evita romper el atributo x-data con JSON) --}}
<script>
    window.__flowEditorData = {
        nodes: @json($ed_nodes),
        edges: @json(array_map(fn($t)=>['from'=>$t['from'],'to'=>$t['to']], $ed_trans)),
        inactive: @json($ed_inactive),
        saveUrl: @json(route('settings.flow.diagram')),
        saveFlowUrl: @json(route('settings.flow.update')),
        projectId: {{ $selP->id }},
        csrf: @json(csrf_token()),
    };
</script>

<div x-data="flowEditor(window.__flowEditorData)"
     class="flex w-full" style="height:calc(100vh - 56px); background:#fff; position:relative; z-index:1;">

    {{-- ═══ CANVAS (pizarra) ═══ --}}
    <div class="relative overflow-hidden bg-gray-50" style="flex:1 1 0%; min-width:0; height:100%; background-image:radial-gradient(#e2e8f0 1px, transparent 1px); background-size:20px 20px;">

        {{-- Toolbar superior --}}
        <div class="absolute top-0 left-0 right-0 z-20 flex items-center justify-between px-4 py-2.5 bg-white border-b border-gray-200">
            <div class="flex items-center gap-2 text-gray-700">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="5" rx="1"/><rect x="14" y="16" width="7" height="5" rx="1"/><path d="M6.5 8v4a2 2 0 002 2h5.5m0 0V16"/></svg>
                <span class="text-sm font-semibold">Editor de flujo</span>
                <span class="text-xs text-gray-400">· {{ $selP->name }}</span>
            </div>
            <div class="flex items-center gap-2">
                {{-- Indicador de guardado automático --}}
                <span class="text-[11px] text-gray-400 flex items-center gap-1" x-show="!saving">
                    <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Guardado automático
                </span>
                <span class="text-[11px] text-gray-400" x-show="saving" x-cloak>Guardando…</span>

                {{-- Añadir estado --}}
                <div class="relative" x-show="inactive.length">
                    <button @click="showAdd=!showAdd" class="text-xs flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        Estado
                    </button>
                    <div x-show="showAdd" x-cloak @click.outside="showAdd=false"
                         class="absolute right-0 mt-1 w-52 bg-white rounded-xl border border-gray-200 shadow-lg z-30 py-1 max-h-64 overflow-y-auto">
                        <template x-for="s in inactive" :key="s.key">
                            <button @click="addNode(s); showAdd=false" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 text-left">
                                <span class="w-2 h-2 rounded-full" :style="`background:${s.color}`"></span><span x-text="s.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <button @click="save()" :disabled="saving"
                        class="text-xs flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Guardar
                </button>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="toast" x-cloak x-transition
             class="absolute top-14 left-1/2 -translate-x-1/2 z-30 bg-green-600 text-white text-sm px-4 py-2 rounded-lg shadow-lg"
             x-text="toast"></div>


        {{-- Área del canvas: SVG (flechas) de fondo + nodos HTML encima --}}
        <div class="absolute inset-0 overflow-hidden" style="top:44px" x-ref="canvas"
             @mousemove="onMove($event)" @mouseup="onUp()" @mouseleave="onUp()"
             @wheel.prevent="onWheel($event)">

            {{-- Capa transformable (zoom + paneo) --}}
            <div x-ref="stage" style="position:absolute; top:0; left:0; width:100%; height:100%; transform-origin:0 0;"
                 :style="`transform: translate(${pan.x}px, ${pan.y}px) scale(${zoom})`">

            {{-- SVG para las flechas. Se pinta con x-html (Alpine NO renderiza
                 <template x-for> dentro de <svg> por el namespace XML). --}}
            <svg class="absolute" style="pointer-events:none; left:0; top:0; width:3000px; height:2000px; overflow:visible" x-html="edgesSvg()"
                 @click="onSvgClick($event)"></svg>

            {{-- Barra de acciones de la flecha seleccionada --}}
            <template x-if="selectedEdge!==null && edges[selectedEdge]">
                <div class="absolute z-30 flex items-center gap-0.5 bg-white rounded-lg shadow-lg border border-gray-200 p-1"
                     :style="`left:${edgeMid(edges[selectedEdge]).x - 50}px; top:${edgeMid(edges[selectedEdge]).y - 42}px`">
                    <button @click="invertEdge(selectedEdge)" class="w-7 h-7 flex items-center justify-center rounded hover:bg-gray-100 text-gray-500" title="Invertir dirección">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </button>
                    <button @click="startReaim(selectedEdge)" class="w-7 h-7 flex items-center justify-center rounded hover:bg-indigo-50 text-indigo-600" title="Reapuntar a otro estado">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r="1" fill="currentColor"/></svg>
                    </button>
                    <button @click="removeEdge(selectedEdge); selectedEdge=null" class="w-7 h-7 flex items-center justify-center rounded hover:bg-red-50 text-red-500" title="Eliminar conexión">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>

            {{-- Nodos como divs HTML posicionados (Alpine funciona sin problemas de namespace) --}}
            <template x-for="n in nodes" :key="n.key">
                <div class="absolute group" style="width:170px"
                     :style="`left:${n.x}px; top:${n.y}px`"
                     @mouseenter="setHover(n)" @mouseleave="clearHover(n)">

                    {{-- Caja del estado (diseño pulido tipo referencia) --}}
                    <div class="relative rounded-2xl border-2 bg-white transition-shadow"
                         :style="`width:170px; cursor:${dragging&&dragging.key===n.key?'grabbing':'grab'};
                                  border-color:${(linking && hoverNode && hoverNode.key===n.key && linkFrom.key!==n.key) ? '#22c55e' : (selected===n.key ? '#6366f1' : n.color)};
                                  box-shadow:${selected===n.key ? '0 4px 16px rgba(99,102,241,.18)' : '0 1px 4px rgba(16,24,40,.06)'}`"
                         @mousedown="startDrag(n, $event)" @click="selectNode(n, $event)" @mouseup="tryConnect(n)">
                        {{-- Franja de color superior (indicador del estado, sobrio) --}}
                        <div class="h-1 rounded-t-xl" :style="`background:${n.color}`"></div>
                        <div class="flex items-start gap-2.5 px-3 py-2.5">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 mt-1.5" :style="`background:${n.color}`"></span>
                            <div class="min-w-0 flex-1">
                                <div class="text-[13px] font-semibold text-gray-800 truncate leading-tight" x-text="n.label"></div>
                                <div class="text-[10px] text-gray-400 mt-0.5" x-text="n.core ? 'Estado fijo' : 'Configurable'"></div>
                                {{-- Badges de tiempo (puntos de color sobrios) --}}
                                <div class="flex gap-1 mt-2 flex-wrap items-center">
                                    <template x-if="n.time_target"><span class="inline-flex items-center gap-1 text-[9px] font-semibold px-1.5 py-0.5 rounded" style="background:#f0fdf4;color:#15803d"><span class="w-1.5 h-1.5 rounded-full" style="background:#22c55e"></span><span x-text="n.time_target+'m'"></span></span></template>
                                    <template x-if="n.time_warn"><span class="inline-flex items-center gap-1 text-[9px] font-semibold px-1.5 py-0.5 rounded" style="background:#fffbeb;color:#b45309"><span class="w-1.5 h-1.5 rounded-full" style="background:#f59e0b"></span><span x-text="n.time_warn+'m'"></span></span></template>
                                    <template x-if="n.time_critical"><span class="inline-flex items-center gap-1 text-[9px] font-semibold px-1.5 py-0.5 rounded" style="background:#fef2f2;color:#b91c1c"><span class="w-1.5 h-1.5 rounded-full" style="background:#ef4444"></span><span x-text="n.time_critical+'m'"></span></span></template>
                                    <template x-if="n.notify"><span class="inline-flex items-center text-[9px] font-semibold px-1.5 py-0.5 rounded" style="background:#eff6ff;color:#1d4ed8">WhatsApp</span></template>
                                </div>
                            </div>
                            {{-- Menú ⋮ (SVG sobrio) --}}
                            <button @click.stop="selected=n.key" class="text-gray-300 hover:text-gray-600 flex-shrink-0 mt-0.5" title="Configurar">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- 4 PUNTOS DE CONEXIÓN (arriba/abajo/izq/der) — visibles al pasar el mouse --}}
                    <template x-for="side in ['top','right','bottom','left']" :key="side">
                        <div class="absolute w-3.5 h-3.5 rounded-full bg-white border-2 border-indigo-500 cursor-crosshair opacity-0 group-hover:opacity-100 hover:scale-150 hover:bg-indigo-500 transition-all z-20"
                             :style="portStyle(side)"
                             @mousedown.stop.prevent="startLink(n, side, $event)"
                             title="Arrastra desde aquí hasta otro estado"></div>
                    </template>
                </div>
            </template>
            </div>{{-- /capa stage (zoom) --}}

            {{-- ═══ TOOLBAR VERTICAL (herramientas, fijo sobre el canvas) ═══ --}}
            <div class="absolute top-3 left-3 z-20 flex flex-col gap-1 bg-white border border-gray-200 rounded-xl shadow-sm p-1">
                <button @click="tool='select'" :class="tool==='select' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50'"
                        class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors" title="Seleccionar">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l7.5 18 2.5-7.5L20.5 11 3 3z"/></svg>
                </button>
                <button @click="tool='pan'" :class="tool==='pan' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-50'"
                        class="w-9 h-9 rounded-lg flex items-center justify-center transition-colors" title="Mover lienzo (arrastrar fondo)">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18M8 7l4-4 4 4M8 17l4 4 4-4M7 8l-4 4 4 4M17 8l4 4-4 4"/></svg>
                </button>
                <div class="h-px bg-gray-100 mx-1 my-0.5"></div>
                <button @click="zoomBy(0.15)" class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors" title="Acercar">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M11 8v6M8 11h6M20 20l-3.5-3.5"/></svg>
                </button>
                <button @click="zoomBy(-0.15)" class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors" title="Alejar">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M8 11h6M20 20l-3.5-3.5"/></svg>
                </button>
                <button @click="fitView()" class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-50 transition-colors" title="Ajustar a la vista">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/></svg>
                </button>
            </div>

            {{-- ═══ CONTROL DE ZOOM (esquina inferior derecha) ═══ --}}
            <div class="absolute bottom-3 right-3 z-20 flex items-center gap-1 bg-white border border-gray-200 rounded-lg shadow-sm px-1 py-0.5">
                <button @click="zoomBy(-0.15)" class="w-7 h-7 rounded flex items-center justify-center text-gray-500 hover:bg-gray-50" title="Alejar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14"/></svg>
                </button>
                <button @click="zoom=1;pan={x:0,y:0}" class="text-xs font-semibold text-gray-600 tabular-nums w-12 text-center hover:text-indigo-600" title="Restablecer zoom" x-text="Math.round(zoom*100)+'%'"></button>
                <button @click="zoomBy(0.15)" class="w-7 h-7 rounded flex items-center justify-center text-gray-500 hover:bg-gray-50" title="Acercar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>

            {{-- ═══ MINI-MAPA (esquina inferior izquierda) ═══ --}}
            <div class="absolute bottom-3 left-3 z-20 bg-white/95 border border-gray-200 rounded-lg shadow-sm p-1.5" style="width:160px">
                <svg width="148" height="96" style="display:block" x-html="minimapSvg()"></svg>
            </div>

            <div class="absolute top-3 left-1/2 -translate-x-1/2 text-[11px] text-gray-400 bg-white/90 border border-gray-100 rounded-lg px-2.5 py-1 z-10 pointer-events-none">
                Arrastra para mover · Jala desde los puntos · Clic en una flecha para editarla
            </div>
        </div>

    {{-- ═══ PANEL LATERAL DERECHO ═══ --}}
    <div class="w-80 flex-shrink-0 border-l border-gray-200 bg-white overflow-y-auto" style="height:100%">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <p class="text-sm font-semibold text-gray-800">Configuración del estado</p>
        </div>

        {{-- Sin selección --}}
        <div x-show="!selected" x-cloak class="p-6 text-center text-gray-400 text-sm">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
            Selecciona un estado en la pizarra para configurarlo.
        </div>

        {{-- Estado seleccionado --}}
        <template x-if="selected">
            <div class="p-4 space-y-4" x-data>
                <template x-for="n in nodes.filter(x=>x.key===selected)" :key="n.key">
                    <div class="space-y-4">
                        {{-- Nombre + icono --}}
                        <div class="flex items-center gap-2">
                            <span class="text-2xl" x-text="n.icon"></span>
                            <div>
                                <p class="text-base font-bold text-gray-800" x-text="n.label"></p>
                                <p class="text-[11px] text-gray-400" x-text="n.core ? 'Estado fijo' : 'Estado configurable'"></p>
                            </div>
                        </div>

                        {{-- Color --}}
                        <div>
                            <label class="text-xs font-semibold text-gray-600">🎨 Color</label>
                            <input type="color" x-model="n.color" @change="markDirty()"
                                   class="mt-1 w-full h-9 rounded-lg border border-gray-200 cursor-pointer">
                        </div>

                        {{-- Tiempos --}}
                        <div>
                            <label class="text-xs font-semibold text-gray-600">Tiempos de gestión (min)</label>
                            <div class="grid grid-cols-3 gap-2 mt-1.5">
                                <div>
                                    <input type="number" min="0" x-model="n.time_target" @change="markDirty()" placeholder="—"
                                           class="w-full text-sm text-center rounded-lg border border-gray-200 py-1.5">
                                    <p class="text-[10px] text-center text-gray-500 mt-1 flex items-center justify-center gap-1"><span class="w-1.5 h-1.5 rounded-full" style="background:#22c55e"></span>Objetivo</p>
                                </div>
                                <div>
                                    <input type="number" min="0" x-model="n.time_warn" @change="markDirty()" placeholder="—"
                                           class="w-full text-sm text-center rounded-lg border border-gray-200 py-1.5">
                                    <p class="text-[10px] text-center text-gray-500 mt-1 flex items-center justify-center gap-1"><span class="w-1.5 h-1.5 rounded-full" style="background:#f59e0b"></span>Alerta</p>
                                </div>
                                <div>
                                    <input type="number" min="0" x-model="n.time_critical" @change="markDirty()" placeholder="—"
                                           class="w-full text-sm text-center rounded-lg border border-gray-200 py-1.5">
                                    <p class="text-[10px] text-center text-gray-500 mt-1 flex items-center justify-center gap-1"><span class="w-1.5 h-1.5 rounded-full" style="background:#ef4444"></span>Crítico</p>
                                </div>
                            </div>
                        </div>

                        {{-- Alerta --}}
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="n.alert" @change="markDirty()" class="rounded border-gray-300 text-indigo-600">
                            <span class="text-xs text-gray-700">Alertar si supera el tiempo crítico</span>
                        </label>

                        {{-- WhatsApp --}}
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="n.notify" @change="markDirty()" class="rounded border-gray-300 text-green-600">
                            <span class="text-xs text-gray-700">Avisar al cliente por WhatsApp</span>
                        </label>
                        <div x-show="n.notify" x-cloak>
                            <textarea x-model="n.wa_message" @change="markDirty()" rows="2" class="input text-sm w-full"
                                      placeholder="Mensaje (vacío = por defecto)"></textarea>
                            <p class="text-[10px] text-gray-400 mt-0.5">Variables: <code>{cliente}</code> <code>{ticket}</code> <code>{negocio}</code></p>
                        </div>

                        {{-- Quitar del flujo (si no es core) --}}
                        <template x-if="!n.core">
                            <button @click="removeNode(n.key)" class="w-full text-xs flex items-center justify-center gap-1.5 text-red-500 hover:text-red-700 border border-red-200 rounded-lg py-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Quitar estado del flujo
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

<script>
// Registrar como componente Alpine (funciona aunque Alpine cargue con defer/Vite).
// Si Alpine ya arrancó, lo definimos de inmediato; si no, esperamos alpine:init.
function _defFlowEditor(){
  window.flowEditor = function(cfg){
    return {
        _cfg: cfg,
        nodes: cfg.nodes,
        edges: cfg.edges,
        inactive: cfg.inactive,
        selected: null,
        dragging: null, dragOff: {x:0,y:0},
        linking: false, linkFrom: null, linkStart: {x:0,y:0}, linkSide: null, hoverNode: null,
        connectMode: null,   // key del estado origen mientras conectas por clic
        selectedEdge: null,  // índice de flecha seleccionada
        reaimEdge: null,     // índice de flecha que se está reapuntando
        mouse: {x:0,y:0},
        saving: false, toast: '', showAdd: false, dirty: false, dbg: '', _saveTimer: null,

        // ── Zoom / paneo del lienzo ──
        zoom: 1, pan: {x:0, y:0}, tool: 'select',
        panning: false, panStart: {x:0,y:0}, panOrigin: {x:0,y:0},

        truncate(s, n){ return s && s.length > n ? s.slice(0,n)+'…' : (s||''); },
        node(key){ return this.nodes.find(n=>n.key===key); },

        // ── Selección / clic en un estado ──
        selectNode(n, e){
            // Reapuntar una flecha: este clic cambia su destino.
            if(this.reaimEdge !== null && this.edges[this.reaimEdge]){
                if(this.edges[this.reaimEdge].from !== n.key){
                    this.edges[this.reaimEdge].to = n.key;
                    this.edges[this.reaimEdge].toSide = 'left';
                    this.dirty=true; this.autoSave();
                }
                this.reaimEdge = null; this.selectedEdge = null;
                return;
            }
            if(this.justDragged){ this.justDragged=false; return; }
            this.selectedEdge = null;
            this.selected = n.key;
        },

        // ── Arrastrar nodos ──
        startDrag(n, e){
            this.dragging = n;
            const pt = this.svgPoint(e);
            this.dragOff = { x: pt.x - n.x, y: pt.y - n.y };
            this.justDragged = false;
        },
        onMove(e){
            const pt = this.svgPoint(e);
            this.mouse = pt;
            if(this.dragging){
                this.dragging.x = Math.max(0, pt.x - this.dragOff.x);
                this.dragging.y = Math.max(10, pt.y - this.dragOff.y);
                this.justDragged = true;
                this.dirty = true;
                this.dbg = 'movido '+this.dragging.key;
            }
        },
        onUp(){
            const moved = !!this.dragging && this.justDragged;
            this.dragging = null;
            // Si estábamos jalando una flecha y soltamos sobre un nodo → crear conexión.
            if(this.linking){
                if(this.hoverNode && this.linkFrom && this.hoverNode.key !== this.linkFrom.key){
                    const toSide = this.nearestSide(this.hoverNode, this.mouse);
                    const exists = this.edges.some(ed=>ed.from===this.linkFrom.key && ed.to===this.hoverNode.key);
                    if(!exists){
                        this.edges.push({from:this.linkFrom.key, to:this.hoverNode.key, fromSide:this.linkSide, toSide});
                        this.dirty=true; this.autoSave();
                    }
                }
                this.linking=false; this.linkFrom=null; this.linkSide=null;
            }
            if(moved){ this.autoSave(); }
        },

        // ── Posición de cada punto de conexión respecto al nodo (170x~62) ──
        portStyle(side){
            const b = {top:'-7px; left:79px', right:'27px; left:163px', bottom:'56px; left:79px', left:'27px; left:-7px'};
            const [top,left] = ({top:['-7px','79px'], right:['27px','163px'], bottom:['56px','79px'], left:['27px','-7px']})[side];
            return `top:${top}; left:${left};`;
        },
        // Coordenada absoluta de un puerto (para dibujar la flecha)
        portXY(n, side){
            const w=170, h=62;
            switch(side){
                case 'top':    return {x:n.x+w/2, y:n.y};
                case 'bottom': return {x:n.x+w/2, y:n.y+h};
                case 'left':   return {x:n.x,     y:n.y+h/2};
                default:       return {x:n.x+w,   y:n.y+h/2}; // right
            }
        },
        // Lado más cercano de un nodo al punto (para elegir dónde entra la flecha)
        nearestSide(n, pt){
            const c = {x:n.x+85, y:n.y+31};
            const dx = pt.x-c.x, dy = pt.y-c.y;
            if(Math.abs(dx) > Math.abs(dy)) return dx>0 ? 'right' : 'left';
            return dy>0 ? 'bottom' : 'top';
        },

        // Guardado automático de posiciones (debounced, silencioso).
        autoSave(){
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(()=>this.saveDiagram(), 600);
        },
        async saveDiagram(){
            const cfg = this._cfg;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || cfg.csrf;
            const positions = {}; this.nodes.forEach(n=>positions[n.key]={x:Math.round(n.x),y:Math.round(n.y)});
            const transitions = this.edges.map(e=>({from:e.from,to:e.to}));
            try{
                const r = await fetch(cfg.saveUrl, { method:'POST', credentials:'same-origin',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                    body: JSON.stringify({ positions, transitions }) });
                this.dbg = r.ok ? 'auto-guardado ✓' : 'auto-guardado HTTP '+r.status;
                if(r.ok){ this.toast='Guardado'; setTimeout(()=>this.toast='',1200); }
            }catch(e){ this.dbg='auto-guardado error: '+e.message; }
        },

        // ── Conectar flechas: arrastrar desde un punto de conexión ──
        startLink(n, side, e){
            this.linking = true;
            this.linkFrom = n;
            this.linkSide = side;
            const p = this.portXY(n, side);
            this.linkStart = p;
            this.mouse = p;
            this.dragging = null;   // no arrastrar el nodo mientras conecto
            this.dbg = 'jalando flecha desde '+n.key+'('+side+')';
        },
        setHover(n){ this.hoverNode = n; },
        clearHover(n){ if(this.hoverNode && this.hoverNode.key===n.key) this.hoverNode = null; },
        // Compat: al soltar sobre la caja de un nodo (además del mouseup global)
        tryConnect(n){
            if(this.linking && this.linkFrom && this.linkFrom.key!==n.key){ this.hoverNode = n; }
        },
        removeEdge(idx){ this.edges.splice(idx,1); this.dirty=true; this.autoSave(); },

        // ── Selección y edición de flechas (Fase 2) ──
        onSvgClick(e){
            const idx = e.target?.getAttribute?.('data-edge');
            if(idx !== null && idx !== undefined){ this.selectedEdge = parseInt(idx); this.selected=null; }
            else { this.selectedEdge = null; }
        },
        invertEdge(idx){
            const e = this.edges[idx]; if(!e) return;
            [e.from, e.to] = [e.to, e.from];
            [e.fromSide, e.toSide] = [e.toSide||'left', e.fromSide||'right'];
            this.dirty=true; this.autoSave();
        },
        // Reapuntar: el próximo clic en un estado cambia el destino de la flecha
        startReaim(idx){ this.reaimEdge = idx; this.dbg='clic en un estado para reapuntar la flecha'; },

        // ── Path curvo de una flecha (usa los lados/puertos) ──
        edgePath(e){
            const a = this.node(e.from), b = this.node(e.to);
            if(!a || !b) return '';
            const fromSide = e.fromSide || 'right';
            const toSide   = e.toSide   || 'left';
            const p1 = this.portXY(a, fromSide);
            const p2 = this.portXY(b, toSide);
            // Puntos de control según el lado para una curva Bézier suave
            const off = 50;
            const c1 = this.ctrlPoint(p1, fromSide, off);
            const c2 = this.ctrlPoint(p2, toSide, off);
            return `M${p1.x},${p1.y} C${c1.x},${c1.y} ${c2.x},${c2.y} ${p2.x},${p2.y}`;
        },
        ctrlPoint(p, side, off){
            switch(side){
                case 'top':    return {x:p.x, y:p.y-off};
                case 'bottom': return {x:p.x, y:p.y+off};
                case 'left':   return {x:p.x-off, y:p.y};
                default:       return {x:p.x+off, y:p.y}; // right
            }
        },
        // Punto medio de una flecha (para poner el botón de eliminar / handle)
        edgeMid(e){
            const a=this.node(e.from), b=this.node(e.to); if(!a||!b) return {x:0,y:0};
            const p1=this.portXY(a,e.fromSide||'right'), p2=this.portXY(b,e.toSide||'left');
            return {x:(p1.x+p2.x)/2, y:(p1.y+p2.y)/2};
        },

        // Genera TODO el SVG (defs + flechas) como string. x-html evita el bug SVG+Alpine.
        edgesSvg(){
            let s = '<defs>'
                + '<marker id="ed-arrow" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto" markerUnits="strokeWidth"><path d="M0,0 L8,3 L0,6 Z" fill="#6366f1"/></marker>'
                + '<marker id="ed-arrow-sel" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto" markerUnits="strokeWidth"><path d="M0,0 L8,3 L0,6 Z" fill="#ef4444"/></marker>'
                + '</defs>';
            this.edges.forEach((e,idx)=>{
                const d = this.edgePath(e); if(!d) return;
                const sel = this.selectedEdge===idx;
                const color = sel ? '#ef4444' : '#6366f1';
                const marker = sel ? 'ed-arrow-sel' : 'ed-arrow';
                // línea ancha invisible para clic fácil + línea visible
                s += `<path d="${d}" fill="none" stroke="transparent" stroke-width="14" style="pointer-events:stroke;cursor:pointer" data-edge="${idx}"/>`;
                s += `<path d="${d}" fill="none" stroke="${color}" stroke-width="${sel?3:2}" marker-end="url(#${marker})" opacity="0.85" style="pointer-events:none"/>`;
            });
            // Flecha en construcción
            if(this.linking && this.linkFrom){
                s += `<line x1="${this.linkStart.x}" y1="${this.linkStart.y}" x2="${this.mouse.x}" y2="${this.mouse.y}" stroke="#22c55e" stroke-width="2.5" stroke-dasharray="6 4" opacity="0.8" style="pointer-events:none"/>`;
            }
            return s;
        },

        // ── Añadir / quitar nodos ──
        addNode(s){
            const n = { key:s.key, label:s.label, icon:s.icon, color:s.color, core:false,
                        x: 60 + this.nodes.length*30, y: 200,
                        notify:false, alert:false, wa_message:'', time_target:'', time_warn:'', time_critical:'' };
            this.nodes.push(n);
            this.inactive = this.inactive.filter(x=>x.key!==s.key);
            this.selected = n.key; this.dirty=true;
        },
        removeNode(key){
            this.nodes = this.nodes.filter(n=>n.key!==key);
            this.edges = this.edges.filter(e=>e.from!==key && e.to!==key);
            if(this.selected===key) this.selected=null;
            this.dirty=true;
        },
        markDirty(){ this.dirty=true; },

        // ── Coordenadas del "mundo" (deshaciendo paneo + zoom) ──
        svgPoint(e){
            const r = this.$refs.canvas.getBoundingClientRect();
            return {
                x: (e.clientX - r.left - this.pan.x) / this.zoom,
                y: (e.clientY - r.top  - this.pan.y) / this.zoom,
            };
        },

        // ── Zoom ──
        zoomBy(delta){
            const z = Math.min(2, Math.max(0.3, +(this.zoom + delta).toFixed(2)));
            // Zoom hacia el centro visible del canvas
            const r = this.$refs.canvas.getBoundingClientRect();
            const cx = r.width/2, cy = r.height/2;
            const wx = (cx - this.pan.x)/this.zoom, wy = (cy - this.pan.y)/this.zoom;
            this.zoom = z;
            this.pan.x = cx - wx*z;
            this.pan.y = cy - wy*z;
        },
        onWheel(e){
            const r = this.$refs.canvas.getBoundingClientRect();
            const mx = e.clientX - r.left, my = e.clientY - r.top;
            const wx = (mx - this.pan.x)/this.zoom, wy = (my - this.pan.y)/this.zoom;
            const z = Math.min(2, Math.max(0.3, +(this.zoom + (e.deltaY<0?0.1:-0.1)).toFixed(2)));
            this.zoom = z;
            this.pan.x = mx - wx*z;
            this.pan.y = my - wy*z;
        },

        // ── Ajustar todos los nodos a la vista ──
        fitView(){
            if(!this.nodes.length) return;
            const w=170, h=62, pad=60;
            let minX=Infinity,minY=Infinity,maxX=-Infinity,maxY=-Infinity;
            this.nodes.forEach(n=>{
                minX=Math.min(minX,n.x); minY=Math.min(minY,n.y);
                maxX=Math.max(maxX,n.x+w); maxY=Math.max(maxY,n.y+h);
            });
            const r = this.$refs.canvas.getBoundingClientRect();
            const bw=maxX-minX+pad*2, bh=maxY-minY+pad*2;
            const z = Math.min(2, Math.max(0.3, +Math.min(r.width/bw, r.height/bh).toFixed(2)));
            this.zoom = z;
            this.pan.x = (r.width  - (maxX+minX)*z)/2;
            this.pan.y = (r.height - (maxY+minY)*z)/2;
        },

        // ── Mini-mapa: vista general de todos los nodos ──
        minimapSvg(){
            const W=148, H=96, w=170, h=62;
            if(!this.nodes.length) return '';
            let minX=Infinity,minY=Infinity,maxX=-Infinity,maxY=-Infinity;
            this.nodes.forEach(n=>{
                minX=Math.min(minX,n.x); minY=Math.min(minY,n.y);
                maxX=Math.max(maxX,n.x+w); maxY=Math.max(maxY,n.y+h);
            });
            const pad=30; minX-=pad;minY-=pad;maxX+=pad;maxY+=pad;
            const bw=maxX-minX||1, bh=maxY-minY||1;
            const s=Math.min(W/bw, H/bh);
            const ox=(W-bw*s)/2, oy=(H-bh*s)/2;
            const map=(x,y)=>[ox+(x-minX)*s, oy+(y-minY)*s];
            let svg='';
            // aristas
            this.edges.forEach(e=>{
                const a=this.node(e.from), b=this.node(e.to); if(!a||!b) return;
                const [x1,y1]=map(a.x+w/2,a.y+h/2), [x2,y2]=map(b.x+w/2,b.y+h/2);
                svg+=`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#cbd5e1" stroke-width="1"/>`;
            });
            // nodos
            this.nodes.forEach(n=>{
                const [x,y]=map(n.x,n.y);
                svg+=`<rect x="${x}" y="${y}" width="${w*s}" height="${h*s}" rx="2" fill="${n.color||'#94a3b8'}" opacity="0.85"/>`;
            });
            // recuadro de viewport
            const r = this.$refs.canvas ? this.$refs.canvas.getBoundingClientRect() : {width:800,height:500};
            const vx=(-this.pan.x/this.zoom), vy=(-this.pan.y/this.zoom);
            const vw=r.width/this.zoom, vh=r.height/this.zoom;
            const [rx,ry]=map(vx,vy);
            svg+=`<rect x="${rx}" y="${ry}" width="${vw*s}" height="${vh*s}" fill="none" stroke="#6366f1" stroke-width="1.2"/>`;
            return svg;
        },

        // ── Guardar (diagrama + config de estados) ──
        async save(){
            const cfg = this._cfg;
            // Token CSRF fresco del meta tag (más confiable que el renderizado)
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || cfg.csrf;
            this.saving = true;
            try {
                // 1. Guardar posiciones + transiciones (diagrama)
                const positions = {}; this.nodes.forEach(n=>positions[n.key]={x:Math.round(n.x),y:Math.round(n.y)});
                const transitions = this.edges.map(e=>({from:e.from,to:e.to}));
                this.dbg = 'guardando... url='+cfg.saveUrl;
                const r1 = await fetch(cfg.saveUrl, { method:'POST', credentials:'same-origin',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                    body: JSON.stringify({ positions, transitions }) });
                this.dbg = 'diagrama respondio HTTP '+r1.status;
                if(!r1.ok) throw new Error('Diagrama HTTP '+r1.status);

                // 2. Guardar estados activos + su config (form clásico)
                const fd = new FormData();
                fd.append('_token', csrf);
                fd.append('project_id', cfg.projectId);
                this.nodes.forEach(n=>{
                    fd.append('states[]', n.key);
                    if(n.time_target)   fd.append('time_target['+n.key+']', n.time_target);
                    if(n.time_warn)     fd.append('time_warn['+n.key+']', n.time_warn);
                    if(n.time_critical) fd.append('time_critical['+n.key+']', n.time_critical);
                    if(n.alert)  fd.append('alert['+n.key+']', '1');
                    if(n.notify) fd.append('notify['+n.key+']', '1');
                    if(n.color)  fd.append('color['+n.key+']', n.color);
                    if(n.wa_message) fd.append('wa_message['+n.key+']', n.wa_message);
                });
                const r2 = await fetch(cfg.saveFlowUrl, { method:'POST', credentials:'same-origin', redirect:'manual',
                    headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body: fd });
                // updateFlow hace redirect (302/opaqueredirect) → se considera éxito
                if(r2.status !== 0 && !r2.ok && r2.status!==302) throw new Error('Flujo HTTP '+r2.status);

                this.dirty = false;
                this.dbg = 'GUARDADO OK ✓';
                this.toast = '✓ Guardado correctamente';
                setTimeout(()=>this.toast='', 2500);
            } catch(err){
                console.error('Error al guardar el flujo:', err);
                this.dbg = 'ERROR: ' + (err.message || err);
                this.toast = '✕ ' + (err.message || 'Error al guardar');
                setTimeout(()=>this.toast='', 5000);
            }
            this.saving = false;
        },
    };
  };
}
// Definir ya, y también en alpine:init por si Alpine aún no cargó.
_defFlowEditor();
document.addEventListener('alpine:init', _defFlowEditor);
</script>

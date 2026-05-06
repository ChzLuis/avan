<x-app-layout>
<x-slot name="pageTitle">Constructor Bot</x-slot>
<x-slot name="slot">

@php
$bots   = \App\Models\BotInstance::where('project_id', $activeProject->id)->get();
$pid    = $activeProject->id;

$actions = [
    ''                    => '— Sin acción —',
    'show_lista'          => 'Cargar catálogo de productos',
    'select_item'         => 'Seleccionar ítem del catálogo',
    'save_quantity'       => 'Guardar cantidad',
    'create_order_lista'  => 'Crear pedido desde lista',
    'create_order'        => 'Crear pedido general',
    'save_solo_nombre'    => 'Guardar nombre',
    'save_solo_dni'       => 'Guardar DNI',
    'save_solo_celular'   => 'Guardar celular',
    'save_solo_email'     => 'Guardar email',
    'save_solo_ciudad'    => 'Guardar ciudad',
    'save_delivery'       => 'Guardar dirección de entrega',
    'save_payment'        => 'Guardar comprobante de pago',
    'save_rifa_payment'   => 'Guardar comprobante (rifa)',
    'confirmar_datos_correctos' => 'Confirmar datos correctos',
    'reiniciar_datos'     => 'Reiniciar datos',
    'complete'            => 'Finalizar flujo',
];

$inputTypes = [
    'text'     => 'Texto libre',
    'number'   => 'Número',
    'option'   => 'Opción de menú',
    'image'    => 'Imagen / comprobante',
    'location' => 'Ubicación',
];

$variables = ['{nombre}','{dni}','{celular}','{email}','{ciudad}',
              '{item_nombre}','{item_cantidad}','{item_total}',
              '{order_number}','{negocio}','{rifas_lista}'];
@endphp

<div class="flex flex-col h-full w-full overflow-hidden bg-gray-50" x-data="botBuilder()" x-init="init()">

{{-- ── TOP BAR ── --}}
<div class="flex-shrink-0 bg-white border-b border-gray-200 px-5 py-3 flex items-center gap-3">
    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(139,92,246,.12)">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#8b5cf6">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
        </svg>
    </div>
    <div>
        <h1 class="text-sm font-bold text-gray-800">Constructor Bot</h1>
        <p class="text-xs text-gray-400">{{ $activeProject->name }}</p>
    </div>

    {{-- Tabs --}}
    <div class="ml-6 flex items-center gap-1 bg-gray-100 rounded-lg p-1">
        @foreach(['bots'=>'Mis Bots','flujo'=>'Editor de Flujo','soporte'=>'Soporte Humano','riesgos'=>'Riesgos'] as $tab=>$label)
        <button @click="activeTab='{{ $tab }}'"
                :class="activeTab==='{{ $tab }}' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                class="px-3 py-1.5 text-xs font-medium rounded-md transition">
            {{ $label }}
        </button>
        @endforeach
    </div>

    <div class="ml-auto flex items-center gap-2">
        {{-- Selector de bot activo --}}
        <select x-model="activeBotType" @change="loadFlow()"
                class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-violet-300">
            <option value="">— Seleccionar bot —</option>
            @foreach($bots as $bot)
            <option value="{{ $bot->bot_type }}">{{ $bot->name }}</option>
            @endforeach
        </select>
        <button @click="openNewBot()"
                class="flex items-center gap-1 text-xs bg-violet-600 text-white px-3 py-1.5 rounded-lg hover:bg-violet-700 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Bot
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: MIS BOTS
══════════════════════════════════════════════════ --}}
<div x-show="activeTab==='bots'" class="flex-1 overflow-y-auto p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 max-w-6xl mx-auto">

        @foreach($bots as $bot)
        @php
            $st     = $bot->readStatus();
            $status = $st['status'] ?? 'offline';
            $colors = $bot->iconColors();
            $badgeCls = match($status) {
                'connected' => 'bg-green-100 text-green-700',
                'qr'        => 'bg-yellow-100 text-yellow-700',
                'starting'  => 'bg-blue-100 text-blue-600',
                default     => 'bg-gray-100 text-gray-500',
            };
            $badgeTxt = match($status) {
                'connected' => 'Conectado',
                'qr'        => 'Escanear QR',
                'starting'  => 'Iniciando...',
                default     => 'Offline',
            };
            $sessCount = \App\Models\BotSession::whereHas('flow', fn($q)=>$q->where('bot_type',$bot->bot_type))
                ->where('last_activity_at', '>=', now()->subHours(24))->count();
        @endphp

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden flex flex-col" id="bc-{{ $bot->bot_type }}">
            {{-- Header --}}
            <div class="p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl {{ $colors[0] }} flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 {{ $colors[1] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-800">{{ $bot->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $bot->description ?: 'Puerto ' . $bot->port }}</p>
                    </div>
                    <span id="bb-badge-{{ $bot->bot_type }}" class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $badgeCls }}">{{ $badgeTxt }}</span>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <p class="text-sm font-bold text-gray-800">{{ $bot->flow?->states()->count() ?? 0 }}</p>
                        <p class="text-xs text-gray-400">estados</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <p class="text-sm font-bold text-gray-800">{{ $sessCount }}</p>
                        <p class="text-xs text-gray-400">conv. 24h</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <p class="text-sm font-bold text-gray-800">{{ $bot->port }}</p>
                        <p class="text-xs text-gray-400">puerto</p>
                    </div>
                </div>

                {{-- QR / Estado --}}
                <div id="bb-qr-{{ $bot->bot_type }}">
                    @if($status === 'connected')
                        <div class="flex items-center gap-2 py-2 text-green-600 text-xs font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            WhatsApp conectado
                        </div>
                    @elseif($status === 'qr' && ($st['qr'] ?? null))
                        <div class="flex flex-col items-center py-2">
                            <p class="text-xs text-gray-500 mb-2">Escanea con WhatsApp</p>
                            <img src="{{ $st['qr'] }}" class="w-36 h-36 rounded-xl border border-gray-200">
                        </div>
                    @elseif($status === 'starting' || $status === 'qr')
                        <div class="flex items-center gap-2 py-2 text-blue-500 text-xs">
                            <div class="w-3.5 h-3.5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                            Iniciando, espera el QR...
                        </div>
                    @else
                        <p class="text-xs text-gray-400 py-2">Bot detenido</p>
                    @endif
                </div>
            </div>

            {{-- Acciones --}}
            <div class="mt-auto px-4 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-2">
                <button onclick="bbBotAction('{{ $bot->bot_type }}','start')"
                        class="text-xs bg-green-600 text-white px-2.5 py-1.5 rounded-lg hover:bg-green-700 transition">▶ Iniciar</button>
                <button onclick="bbBotAction('{{ $bot->bot_type }}','restart')"
                        class="text-xs bg-blue-600 text-white px-2.5 py-1.5 rounded-lg hover:bg-blue-700 transition">↻ Reiniciar</button>
                <button onclick="bbBotAction('{{ $bot->bot_type }}','stop')"
                        class="text-xs bg-red-100 text-red-600 px-2.5 py-1.5 rounded-lg hover:bg-red-200 transition">■ Detener</button>
                <div class="ml-auto flex gap-2">
                    <button @click="activeBotType='{{ $bot->bot_type }}'; activeTab='flujo'; loadFlow()"
                            class="text-xs bg-violet-600 text-white px-2.5 py-1.5 rounded-lg hover:bg-violet-700 transition">
                        ✏️ Editar flujo
                    </button>
                </div>
            </div>

            {{-- Logs --}}
            <div id="bb-logs-{{ $bot->bot_type }}" class="hidden border-t border-gray-100">
                <div class="flex items-center justify-between px-4 py-2 bg-gray-900">
                    <span class="text-xs text-gray-400 font-mono">logs</span>
                    <button onclick="bbLoadLogs('{{ $bot->bot_type }}')" class="text-xs text-gray-400 hover:text-white">↻</button>
                </div>
                <pre id="bb-logs-content-{{ $bot->bot_type }}" class="bg-gray-950 text-green-400 text-xs font-mono p-3 max-h-40 overflow-y-auto">—</pre>
            </div>
        </div>
        @endforeach

        @if($bots->isEmpty())
        <div class="col-span-3 text-center py-16 text-gray-400">
            <p class="text-sm mb-2">No hay bots configurados</p>
            <button @click="openNewBot()" class="text-xs text-violet-600 hover:underline">+ Crear primer bot</button>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: EDITOR DE FLUJO
══════════════════════════════════════════════════ --}}
<div x-show="activeTab==='flujo'" class="flex-1 flex overflow-hidden">

    {{-- Sin bot seleccionado --}}
    <div x-show="!activeBotType" class="flex-1 flex items-center justify-center text-gray-400">
        <div class="text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            <p class="text-sm">Selecciona un bot arriba para editar su flujo</p>
        </div>
    </div>

    {{-- Editor --}}
    <template x-if="activeBotType">
    <div class="flex flex-1 overflow-hidden">

        {{-- PANEL IZQ: Lista de estados --}}
        <div class="w-72 border-r border-gray-200 bg-white flex flex-col flex-shrink-0">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <div>
                    <p class="text-xs font-bold text-gray-700">Estados del flujo</p>
                    <p class="text-xs text-gray-400" x-text="activeBotType"></p>
                </div>
                <button @click="openNewState()"
                        class="w-7 h-7 bg-violet-600 text-white rounded-lg flex items-center justify-center hover:bg-violet-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto" id="bb-states-list">
                <template x-if="loadingStates">
                    <div class="p-6 text-center text-gray-400 text-xs">Cargando estados...</div>
                </template>
                <template x-if="!loadingStates && states.length === 0">
                    <div class="p-6 text-center text-gray-400 text-xs">Sin estados. Crea el primero →</div>
                </template>
                <template x-for="(st, i) in states" :key="st.id">
                    <div :id="'bb-row-'+st.id"
                         @click="selectState(st)"
                         :class="selectedState?.id === st.id ? 'bg-violet-50 border-l-2 border-violet-500' : 'hover:bg-gray-50 border-l-2 border-transparent'"
                         class="border-b border-gray-100 px-3 py-3 cursor-pointer transition">
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col gap-0.5 flex-shrink-0">
                                <button @click.stop="moveState(st.id,'up')" class="text-gray-300 hover:text-gray-500 leading-none text-xs">▲</button>
                                <button @click.stop="moveState(st.id,'down')" class="text-gray-300 hover:text-gray-500 leading-none text-xs">▼</button>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-mono text-violet-600 truncate" x-text="st.key"></span>
                                    <span x-show="st.key==='inicio'" class="text-xs bg-green-100 text-green-700 px-1 rounded">inicio</span>
                                    <span x-show="st.key==='esperando_asesor'" class="text-xs bg-orange-100 text-orange-700 px-1 rounded">soporte</span>
                                </div>
                                <p class="text-xs text-gray-500 truncate" x-text="st.label"></p>
                            </div>
                            <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded flex-shrink-0"
                                  x-text="inputTypeLabel(st.input_type)"></span>
                        </div>
                        {{-- Transiciones mini --}}
                        <div x-show="st.transitions && st.transitions.length" class="mt-1.5 ml-8 flex flex-wrap gap-1">
                            <template x-for="t in (st.transitions||[])" :key="t.id">
                                <span class="text-xs bg-violet-50 text-violet-600 px-1.5 py-0.5 rounded"
                                      x-text="(t.trigger||'*') + ' → ' + (t.to_state_key||'?')"></span>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- PANEL DER: Editor de estado --}}
        <div class="flex-1 overflow-y-auto bg-gray-50">
            <template x-if="!selectedState">
                <div class="flex items-center justify-center h-full text-gray-400">
                    <div class="text-center">
                        <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                        </svg>
                        <p class="text-sm">Selecciona un estado para editarlo</p>
                    </div>
                </div>
            </template>

            <template x-if="selectedState">
            <div class="p-6 max-w-3xl mx-auto space-y-5">

                {{-- Header estado --}}
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-800" x-text="selectedState.label || selectedState.key"></h2>
                        <p class="text-xs text-gray-400 font-mono" x-text="'key: ' + selectedState.key"></p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="saveState()" :disabled="savingState"
                                class="text-xs bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition disabled:opacity-50">
                            <span x-text="savingState ? 'Guardando...' : '✓ Guardar'"></span>
                        </button>
                        <button @click="deleteState(selectedState.id)"
                                class="text-xs bg-red-50 text-red-500 px-3 py-2 rounded-lg hover:bg-red-100 transition">
                            Eliminar
                        </button>
                    </div>
                </div>

                {{-- Datos básicos --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Datos básicos</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Identificador (key)</label>
                            <input x-model="selectedState.key" type="text"
                                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-violet-300">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Etiqueta (label)</label>
                            <input x-model="selectedState.label" type="text"
                                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 block mb-1">Tipo de entrada</label>
                        <select x-model="selectedState.input_type"
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
                            @foreach($inputTypes as $v=>$l)
                            <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Mensaje --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Mensaje</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($variables as $var)
                            <button @click="insertVar('{{ $var }}')"
                                    class="text-xs bg-violet-50 text-violet-600 px-2 py-0.5 rounded hover:bg-violet-100 transition font-mono">
                                {{ $var }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <textarea x-model="selectedState.message" id="state-message" rows="6"
                              class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300 font-mono"
                              placeholder="Escribe el mensaje que enviará el bot..."></textarea>
                    <p class="text-xs text-gray-400">Usa *negrita* y las variables de arriba. Saltos de línea con Enter.</p>
                </div>

                {{-- Validaciones --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Validaciones</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Mínimo</label>
                            <input x-model="selectedState.validation_min" type="number"
                                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300"
                                   placeholder="ej: 1">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Máximo</label>
                            <input x-model="selectedState.validation_max" type="number"
                                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300"
                                   placeholder="ej: 100">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 block mb-1">Patrón regex (opcional)</label>
                        <input x-model="selectedState.validation_pattern" type="text"
                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-violet-300"
                               placeholder="ej: ^\d{8}$">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 block mb-1">Mensaje de error</label>
                        <input x-model="selectedState.validation_error" type="text"
                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300"
                               placeholder="ej: Por favor escribe un número válido">
                    </div>
                </div>

                {{-- Transiciones --}}
                <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Transiciones</p>
                        <button @click="openNewTransition()"
                                class="text-xs bg-violet-50 text-violet-600 px-3 py-1.5 rounded-lg hover:bg-violet-100 transition">
                            + Agregar
                        </button>
                    </div>
                    <p class="text-xs text-gray-400">Define hacia qué estado va el bot según la respuesta del usuario.</p>

                    <template x-if="selectedState.transitions && selectedState.transitions.length === 0">
                        <p class="text-xs text-gray-400 text-center py-3">Sin transiciones. El estado es terminal.</p>
                    </template>

                    <template x-for="t in (selectedState.transitions||[])" :key="t.id">
                        <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1 grid grid-cols-3 gap-2">
                                <div>
                                    <p class="text-xs text-gray-400 mb-1">Trigger</p>
                                    <p class="text-xs font-mono text-gray-700" x-text="t.trigger || '(cualquier input)'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-1">Acción</p>
                                    <p class="text-xs text-gray-700" x-text="t.action || '—'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 mb-1">Ir a estado</p>
                                    <p class="text-xs font-mono text-violet-600" x-text="t.to_state_key || '?'"></p>
                                </div>
                            </div>
                            <button @click="deleteTransition(t.id)"
                                    class="text-gray-300 hover:text-red-500 transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

            </div>
            </template>
        </div>
    </div>
    </template>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: SOPORTE HUMANO
══════════════════════════════════════════════════ --}}
<div x-show="activeTab==='soporte'" class="flex-1 overflow-y-auto p-6">
    <div class="max-w-2xl mx-auto space-y-5">

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Estado del asesor</p>
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-orange-400"></div>
                <p class="text-sm text-gray-700">Estado <span class="font-mono font-semibold">esperando_asesor</span> activo en el motor</p>
            </div>
            <p class="text-xs text-gray-500">
                Cuando un usuario elige la opción 5 o 6 del catálogo, el bot guarda su sesión en estado <code class="bg-gray-100 px-1 rounded">esperando_asesor</code>
                y responde con un mensaje de espera. Cualquier mensaje siguiente recibe: "Un asesor te atenderá en breve".
                Para liberar al usuario escribe <strong>MENU</strong>.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Mensaje de espera</p>
            <textarea rows="3" x-model="soporteMsg"
                      class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300"
                      placeholder="⏳ Tu mensaje fue recibido. Un asesor te responderá en breve..."></textarea>
            <button @click="saveSoporteConfig()"
                    class="text-xs bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition">
                Guardar mensaje
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Sesiones en espera de asesor</p>
            <div id="bb-espera-list" class="space-y-2">
                <p class="text-xs text-gray-400">Cargando...</p>
            </div>
            <button @click="loadEsperaList()" class="text-xs text-violet-600 hover:underline">↻ Actualizar</button>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════
     TAB: RIESGOS
══════════════════════════════════════════════════ --}}
<div x-show="activeTab==='riesgos'" class="flex-1 overflow-y-auto p-6">
    <div class="max-w-2xl mx-auto space-y-5">

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Timeout de inactividad</p>
            <div class="flex items-center gap-3">
                <input type="number" x-model="cfg.timeout_min" min="1" max="60"
                       class="w-20 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
                <span class="text-sm text-gray-600">minutos antes de preguntar "¿sigues ahí?"</span>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Palabras de reset</p>
            <p class="text-xs text-gray-500">El usuario puede escribir estas palabras en cualquier momento para volver al inicio.</p>
            <div class="flex flex-wrap gap-2">
                <template x-for="(w, i) in cfg.reset_words" :key="i">
                    <span class="flex items-center gap-1 bg-gray-100 text-gray-700 text-xs px-2.5 py-1 rounded-full">
                        <span x-text="w"></span>
                        <button @click="cfg.reset_words.splice(i,1)" class="text-gray-400 hover:text-red-500">×</button>
                    </span>
                </template>
                <input type="text" placeholder="+ agregar" @keydown.enter="addResetWord($event)"
                       class="text-xs border border-dashed border-gray-300 rounded-full px-3 py-1 focus:outline-none focus:border-violet-400 w-24">
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Multimedia inesperado</p>
            <div class="space-y-2">
                @foreach(['foto'=>'Foto','video'=>'Video','audio'=>'Audio','sticker'=>'Sticker','ubicacion'=>'Ubicación inesperada'] as $k=>$l)
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-600 w-28">{{ $l }}</span>
                    <select x-model="cfg.media_{{ $k }}"
                            class="flex-1 text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-violet-300">
                        <option value="ignore">Ignorar</option>
                        <option value="ask">Preguntar motivo</option>
                        <option value="asesor">Derivar a asesor</option>
                        <option value="error">Mensaje de error</option>
                    </select>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button @click="saveRiesgosConfig()"
                    class="text-xs bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition">
                Guardar configuración
            </button>
        </div>

    </div>
</div>

</div>{{-- /x-data --}}

{{-- ══════════════════════════════════════
     MODALES
══════════════════════════════════════ --}}

{{-- Modal: Nuevo Bot --}}
<div id="bb-modal-new-bot" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Nuevo Bot WhatsApp</h3>
            <button onclick="document.getElementById('bb-modal-new-bot').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Nombre del bot</label>
                <input id="nb-name" type="text" placeholder="Bot Ventas"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Identificador único</label>
                <input id="nb-type" type="text" placeholder="ventas"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-violet-300">
                <p class="text-xs text-gray-400 mt-1">Solo letras, números y guiones. Ej: ventas, soporte, tienda2</p>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Descripción</label>
                <input id="nb-desc" type="text" placeholder="Bot de atención al cliente..."
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Color</label>
                    <select id="nb-color" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
                        <option value="green">Verde</option>
                        <option value="purple">Morado</option>
                        <option value="blue">Azul</option>
                        <option value="orange">Naranja</option>
                        <option value="red">Rojo</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Puerto HTTP</label>
                    <input id="nb-port" type="number" value="3004" min="3001" max="3999"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button onclick="document.getElementById('bb-modal-new-bot').classList.add('hidden')" class="text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-50">Cancelar</button>
            <button onclick="bbSaveNewBot()" class="text-sm bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition">Crear bot</button>
        </div>
    </div>
</div>

{{-- Modal: Nuevo Estado --}}
<div id="bb-modal-new-state" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Nuevo Estado</h3>
            <button onclick="document.getElementById('bb-modal-new-state').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Identificador (key)</label>
                <input id="ns-key" type="text" placeholder="pedir_nombre"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-violet-300">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Etiqueta</label>
                <input id="ns-label" type="text" placeholder="Pedir nombre"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Tipo de entrada</label>
                <select id="ns-type" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
                    @foreach($inputTypes as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Mensaje inicial</label>
                <textarea id="ns-message" rows="3"
                          class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300"
                          placeholder="¿Cuál es tu nombre?"></textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button onclick="document.getElementById('bb-modal-new-state').classList.add('hidden')" class="text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-50">Cancelar</button>
            <button onclick="bbSaveNewState()" class="text-sm bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition">Crear estado</button>
        </div>
    </div>
</div>

{{-- Modal: Nueva Transición --}}
<div id="bb-modal-new-trans" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Nueva Transición</h3>
            <button onclick="document.getElementById('bb-modal-new-trans').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Trigger (dejar vacío = cualquier input)</label>
                <input id="nt-trigger" type="text" placeholder="1 | si | imagen | location"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-violet-300">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Acción</label>
                <select id="nt-action" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-300">
                    @foreach($actions as $v=>$l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Estado destino</label>
                <select id="nt-to-state" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 font-mono focus:outline-none focus:ring-2 focus:ring-violet-300">
                    <option value="">— Seleccionar —</option>
                </select>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button onclick="document.getElementById('bb-modal-new-trans').classList.add('hidden')" class="text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-50">Cancelar</button>
            <button onclick="bbSaveNewTransition()" class="text-sm bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition">Agregar</button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="bb-toast" class="fixed bottom-6 right-6 hidden z-50">
    <div id="bb-toast-inner" class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white bg-gray-800 min-w-48"></div>
</div>

<script>
const bbCsrf   = '{{ csrf_token() }}';
const bbBase   = '{{ url("/bixoadmin/bots") }}';

function botBuilder() {
    return {
        activeTab:     'bots',
        activeBotType: '',
        states:        [],
        selectedState: null,
        loadingStates: false,
        savingState:   false,
        flowId:        null,
        soporteMsg:    '⏳ Tu mensaje fue recibido. Un asesor te responderá en breve.\n\nSi deseas volver al menú escribe *MENU*.',
        cfg: {
            timeout_min: 5,
            reset_words: ['MENU','CANCELAR','SALIR','REINICIAR'],
            media_foto: 'ask', media_video: 'asesor', media_audio: 'error',
            media_sticker: 'ignore', media_ubicacion: 'ignore',
        },

        init() {
            // Polling de status de bots
            setInterval(() => {
                @foreach($bots as $bot)
                this.refreshBotStatus('{{ $bot->bot_type }}');
                @endforeach
            }, 6000);
            this.loadEsperaList();
        },

        // ── Bot status polling ─────────────────────────
        async refreshBotStatus(bot) {
            try {
                const r = await fetch(`${bbBase}/status?bot=${bot}`);
                const d = await r.json();
                const badge = document.getElementById(`bb-badge-${bot}`);
                const qrDiv = document.getElementById(`bb-qr-${bot}`);
                if (!badge) return;

                const map = {
                    connected: ['bg-green-100 text-green-700',   'Conectado'],
                    qr:        ['bg-yellow-100 text-yellow-700', 'Escanear QR'],
                    starting:  ['bg-blue-100 text-blue-600',     'Iniciando...'],
                    offline:   ['bg-gray-100 text-gray-500',     'Offline'],
                };
                const [cls, txt] = map[d.status] || map.offline;
                badge.className  = `text-xs font-semibold px-2.5 py-1 rounded-full ${cls}`;
                badge.textContent = txt;

                if (qrDiv) {
                    if (d.status === 'connected') {
                        qrDiv.innerHTML = `<div class="flex items-center gap-2 py-2 text-green-600 text-xs font-medium"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>WhatsApp conectado</div>`;
                    } else if (d.status === 'qr' && d.qr) {
                        qrDiv.innerHTML = `<div class="flex flex-col items-center py-2"><p class="text-xs text-gray-500 mb-2">Escanea con WhatsApp</p><img src="${d.qr}" class="w-36 h-36 rounded-xl border border-gray-200"></div>`;
                    } else if (d.status === 'starting' || d.status === 'qr') {
                        qrDiv.innerHTML = `<div class="flex items-center gap-2 py-2 text-blue-500 text-xs"><div class="w-3.5 h-3.5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>Iniciando, espera el QR...</div>`;
                    }
                }
            } catch(e) {}
        },

        // ── Load flow ──────────────────────────────────
        async loadFlow() {
            if (!this.activeBotType) return;
            this.loadingStates = true;
            this.selectedState = null;
            this.states = [];
            try {
                const r = await fetch(`${bbBase}/flow?bot=${this.activeBotType}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const d = await r.json();
                this.flowId = d.flow_id ?? null;
                this.states = d.states ?? [];
            } catch(e) {
                bbToast('Error al cargar flujo', 'error');
            } finally {
                this.loadingStates = false;
            }
        },

        // ── Select state ───────────────────────────────
        selectState(st) {
            this.selectedState = JSON.parse(JSON.stringify(st));
        },

        inputTypeLabel(t) {
            const m = {text:'Texto',number:'Número',option:'Opción',image:'Imagen',location:'Ubic.'};
            return m[t] || t;
        },

        insertVar(v) {
            const ta = document.getElementById('state-message');
            if (!ta) return;
            const s = ta.selectionStart, e = ta.selectionEnd;
            const cur = this.selectedState.message || '';
            this.selectedState.message = cur.slice(0,s) + v + cur.slice(e);
            this.$nextTick(() => { ta.focus(); ta.setSelectionRange(s+v.length, s+v.length); });
        },

        // ── Save state ─────────────────────────────────
        async saveState() {
            if (!this.selectedState) return;
            this.savingState = true;
            try {
                const r = await fetch(`${bbBase}/states/${this.selectedState.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
                    body: JSON.stringify({
                        key:                this.selectedState.key,
                        label:              this.selectedState.label,
                        message:            this.selectedState.message,
                        input_type:         this.selectedState.input_type,
                        validation_min:     this.selectedState.validation_min,
                        validation_max:     this.selectedState.validation_max,
                        validation_pattern: this.selectedState.validation_pattern,
                        validation_error:   this.selectedState.validation_error,
                    }),
                });
                const d = await r.json();
                if (d.ok) {
                    bbToast('Estado guardado ✓', 'success');
                    await this.loadFlow();
                    // Re-seleccionar el estado actualizado
                    const updated = this.states.find(s => s.id === this.selectedState.id);
                    if (updated) this.selectedState = JSON.parse(JSON.stringify(updated));
                } else {
                    bbToast(d.message || 'Error al guardar', 'error');
                }
            } catch(e) { bbToast('Error de conexión', 'error'); }
            finally { this.savingState = false; }
        },

        // ── Delete state ───────────────────────────────
        async deleteState(id) {
            if (!confirm('¿Eliminar este estado? Se eliminarán también sus transiciones.')) return;
            try {
                const r = await fetch(`${bbBase}/states/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': bbCsrf },
                });
                const d = await r.json();
                if (d.ok) {
                    bbToast('Estado eliminado', 'error');
                    this.selectedState = null;
                    await this.loadFlow();
                }
            } catch(e) { bbToast('Error al eliminar', 'error'); }
        },

        // ── Move state ─────────────────────────────────
        async moveState(id, dir) {
            try {
                await fetch(`${bbBase}/states/${id}/move`, {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
                    body: JSON.stringify({ direction: dir }),
                });
                await this.loadFlow();
            } catch(e) {}
        },

        // ── Transiciones ───────────────────────────────
        openNewTransition() {
            if (!this.selectedState) return;
            // Poblar select de estados destino
            const sel = document.getElementById('nt-to-state');
            sel.innerHTML = '<option value="">— Seleccionar —</option>';
            this.states.forEach(s => {
                const o = document.createElement('option');
                o.value = s.id; o.textContent = s.key + ' — ' + s.label;
                sel.appendChild(o);
            });
            document.getElementById('bb-modal-new-trans').classList.remove('hidden');
        },

        async deleteTransition(id) {
            if (!confirm('¿Eliminar esta transición?')) return;
            try {
                const r = await fetch(`${bbBase}/transitions/${id}`, {
                    method: 'DELETE', headers: { 'X-CSRF-TOKEN': bbCsrf },
                });
                const d = await r.json();
                if (d.ok) {
                    bbToast('Transición eliminada', 'error');
                    await this.loadFlow();
                    const updated = this.states.find(s => s.id === this.selectedState?.id);
                    if (updated) this.selectedState = JSON.parse(JSON.stringify(updated));
                }
            } catch(e) { bbToast('Error', 'error'); }
        },

        // ── Soporte ────────────────────────────────────
        async saveSoporteConfig() {
            if (!this.activeBotType) { bbToast('Selecciona un bot primero', 'error'); return; }
            try {
                await fetch(`${bbBase}/config`, {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
                    body: JSON.stringify({
                        bot: this.activeBotType,
                        key: 'soporte_mensaje',
                        value: this.soporteMsg,
                    }),
                });
                bbToast('Mensaje de soporte guardado ✓', 'success');
            } catch(e) { bbToast('Error', 'error'); }
        },

        async loadEsperaList() {
            const el = document.getElementById('bb-espera-list');
            if (!el) return;
            try {
                const r = await fetch(`/bixoadmin/bots/espera-asesor?bot=${this.activeBotType || 'rifa'}`);
                const d = await r.json();
                if (!d.sessions || d.sessions.length === 0) {
                    el.innerHTML = '<p class="text-xs text-gray-400">Sin usuarios esperando asesor.</p>';
                    return;
                }
                el.innerHTML = d.sessions.map(s => `
                    <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg border border-orange-100">
                        <div>
                            <p class="text-xs font-semibold text-gray-800">${s.wa_number}</p>
                            <p class="text-xs text-gray-500">${s.last_activity_at || '—'}</p>
                        </div>
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">esperando</span>
                    </div>`).join('');
            } catch(e) {
                el.innerHTML = '<p class="text-xs text-gray-400">No disponible</p>';
            }
        },

        // ── Riesgos ────────────────────────────────────
        addResetWord(e) {
            const v = e.target.value.trim().toUpperCase();
            if (v && !this.cfg.reset_words.includes(v)) {
                this.cfg.reset_words.push(v);
            }
            e.target.value = '';
        },

        async saveRiesgosConfig() {
            if (!this.activeBotType) { bbToast('Selecciona un bot primero', 'error'); return; }
            try {
                await fetch(`${bbBase}/config`, {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
                    body: JSON.stringify({
                        bot: this.activeBotType,
                        key: 'riesgos_config',
                        value: JSON.stringify(this.cfg),
                    }),
                });
                bbToast('Configuración guardada ✓', 'success');
            } catch(e) { bbToast('Error', 'error'); }
        },

        // ── Modales ────────────────────────────────────
        openNewBot() { document.getElementById('bb-modal-new-bot').classList.remove('hidden'); },
        openNewState() {
            if (!this.activeBotType) { bbToast('Selecciona un bot primero', 'error'); return; }
            document.getElementById('bb-modal-new-state').classList.remove('hidden');
        },
    };
}

// ── Helpers globales ───────────────────────────────────────────
async function bbBotAction(bot, action) {
    bbToast(`${({start:'Iniciando',stop:'Deteniendo',restart:'Reiniciando'})[action]} bot...`, 'info');
    try {
        await fetch(`${bbBase}/control`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
            body: JSON.stringify({ bot, action }),
        });
        bbToast(action === 'stop' ? 'Bot detenido' : 'Bot iniciado ✓', action === 'stop' ? 'error' : 'success');
    } catch(e) { bbToast('Error de conexión', 'error'); }
}

async function bbSaveNewBot() {
    const name  = document.getElementById('nb-name').value.trim();
    const type  = document.getElementById('nb-type').value.trim();
    const desc  = document.getElementById('nb-desc').value.trim();
    const color = document.getElementById('nb-color').value;
    const port  = parseInt(document.getElementById('nb-port').value);
    if (!name || !type) { bbToast('Nombre e identificador requeridos', 'error'); return; }
    try {
        const r = await fetch(`${bbBase}/instances`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
            body: JSON.stringify({ name, bot_type: type, description: desc, icon_color: color, port }),
        });
        const d = await r.json();
        if (d.ok) {
            document.getElementById('bb-modal-new-bot').classList.add('hidden');
            bbToast('Bot creado ✓ — recargando...', 'success');
            setTimeout(() => location.reload(), 1200);
        } else { bbToast(d.message || 'Error al crear', 'error'); }
    } catch(e) { bbToast('Error de conexión', 'error'); }
}

async function bbSaveNewState() {
    const key     = document.getElementById('ns-key').value.trim();
    const label   = document.getElementById('ns-label').value.trim();
    const type    = document.getElementById('ns-type').value;
    const message = document.getElementById('ns-message').value.trim();
    if (!key || !label) { bbToast('Key y etiqueta requeridos', 'error'); return; }

    // Necesitamos el flow_id — lo obtenemos del Alpine store
    const alpine = document.querySelector('[x-data]').__x.$data;
    if (!alpine.flowId) { bbToast('Primero carga un bot en la pestaña Flujo', 'error'); return; }

    try {
        const r = await fetch(`${bbBase}/states`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
            body: JSON.stringify({ flow_id: alpine.flowId, key, label, input_type: type, message }),
        });
        const d = await r.json();
        if (d.ok) {
            document.getElementById('bb-modal-new-state').classList.add('hidden');
            bbToast('Estado creado ✓', 'success');
            await alpine.loadFlow();
        } else { bbToast(d.message || 'Error', 'error'); }
    } catch(e) { bbToast('Error de conexión', 'error'); }
}

async function bbSaveNewTransition() {
    const trigger  = document.getElementById('nt-trigger').value.trim();
    const action   = document.getElementById('nt-action').value;
    const toStateId = document.getElementById('nt-to-state').value;
    if (!toStateId) { bbToast('Selecciona el estado destino', 'error'); return; }

    const alpine = document.querySelector('[x-data]').__x.$data;
    if (!alpine.selectedState) { bbToast('Selecciona un estado primero', 'error'); return; }

    try {
        const r = await fetch(`${bbBase}/transitions`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': bbCsrf },
            body: JSON.stringify({
                from_state_id: alpine.selectedState.id,
                to_state_id:   parseInt(toStateId),
                trigger:       trigger || null,
                action:        action || null,
            }),
        });
        const d = await r.json();
        if (d.ok) {
            document.getElementById('bb-modal-new-trans').classList.add('hidden');
            bbToast('Transición agregada ✓', 'success');
            await alpine.loadFlow();
            const updated = alpine.states.find(s => s.id === alpine.selectedState.id);
            if (updated) alpine.selectedState = JSON.parse(JSON.stringify(updated));
        } else { bbToast(d.message || 'Error', 'error'); }
    } catch(e) { bbToast('Error de conexión', 'error'); }
}

// Auto slug desde nombre
document.getElementById('nb-name').addEventListener('input', function() {
    document.getElementById('nb-type').value = this.value.toLowerCase()
        .replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim();
});

// ── Toast ──────────────────────────────────────────────────────
let bbToastTimer = null;
function bbToast(msg, type='info') {
    const t = document.getElementById('bb-toast');
    const i = document.getElementById('bb-toast-inner');
    const c = {success:'bg-green-600',error:'bg-red-600',info:'bg-gray-800'};
    i.className  = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white min-w-48 ${c[type]||c.info}`;
    i.textContent = msg;
    t.classList.remove('hidden');
    clearTimeout(bbToastTimer);
    bbToastTimer = setTimeout(() => t.classList.add('hidden'), 3500);
}
</script>

</x-slot>
</x-app-layout>

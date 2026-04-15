@extends('comunicaciones.layouts.app')
@section('pageTitle', 'Chatbot')
@section('content')

@php
$canalesJs = $canales->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'color' => $c->color])->values();
$flowsJs   = $flows->map(fn($f) => [
    'id'               => $f->id,
    'wa_canal_id'      => $f->wa_canal_id,
    'nombre'           => $f->nombre,
    'trigger_keywords' => implode(', ', $f->trigger_keywords ?? []),
    'response_text'    => $f->response_text,
    'es_bienvenida'    => $f->es_bienvenida,
    'activo'           => $f->activo,
    'orden'            => $f->orden,
])->values();
@endphp

<div class="flex-1 overflow-y-auto bg-gray-50 p-6" x-data="chatbotApp()" x-init="init()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">Chatbot WhatsApp</h1>
            <p class="text-sm text-gray-500 mt-0.5">Respuestas automáticas por palabras clave</p>
        </div>
        <button @click="abrirModal(null)"
                class="px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-sm"
                style="background:#25d366">
            + Nuevo flow
        </button>
    </div>

    {{-- Selector de canal --}}
    @if($canales->count() > 1)
    <div class="flex gap-2 mb-5 flex-wrap">
        <button @click="filtroCanal = null"
                :class="!filtroCanal ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors">
            Todos
        </button>
        @foreach($canales as $c)
        <button @click="filtroCanal = {{ $c->id }}"
                :class="filtroCanal === {{ $c->id }} ? 'text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
                :style="filtroCanal === {{ $c->id }} ? 'background:{{ $c->color }}' : ''"
                class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors">
            {{ $c->nombre }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- Ayuda --}}
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-5">
        <p class="text-sm font-bold text-blue-800 mb-1">¿Cómo funciona el chatbot?</p>
        <ul class="text-xs text-blue-700 space-y-1 list-disc list-inside">
            <li><strong>Flow de bienvenida</strong>: se envía automáticamente cuando un contacto escribe por primera vez.</li>
            <li><strong>Flows por keywords</strong>: si el mensaje contiene alguna de las palabras clave, se responde automáticamente.</li>
            <li>Puedes <strong>desactivar el bot</strong> en una conversación específica desde la bandeja.</li>
        </ul>
    </div>

    {{-- Lista de flows --}}
    <template x-if="flowsFiltrados.length === 0">
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v3"/>
            </svg>
            <p class="text-sm font-medium text-gray-500 mb-1">Sin flows configurados</p>
            <p class="text-xs text-gray-400">Crea tu primer flow de respuesta automática</p>
        </div>
    </template>

    <div class="space-y-3">
        <template x-for="flow in flowsFiltrados" :key="flow.id">
            <div class="bg-white rounded-2xl border border-gray-200 p-4 flex items-start gap-4">

                {{-- Indicador bienvenida / keyword --}}
                <div class="flex-shrink-0 mt-0.5">
                    <template x-if="flow.es_bienvenida">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm"
                             style="background:#25d366" title="Bienvenida">
                            👋
                        </div>
                    </template>
                    <template x-if="!flow.es_bienvenida">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm"
                             style="background:#6366f1" title="Keyword">
                            🔑
                        </div>
                    </template>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <p class="font-semibold text-gray-900 text-sm" x-text="flow.nombre"></p>
                        <span x-show="flow.es_bienvenida"
                              class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-green-100 text-green-700">
                            Bienvenida
                        </span>
                        <span x-show="!flow.activo"
                              class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-red-100 text-red-600">
                            Inactivo
                        </span>
                        {{-- Canal badge --}}
                        <template x-for="c in CANALES.filter(c => c.id === flow.wa_canal_id)" :key="c.id">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full text-white"
                                  :style="'background:' + c.color"
                                  x-text="c.nombre"></span>
                        </template>
                    </div>

                    {{-- Keywords --}}
                    <template x-if="!flow.es_bienvenida && flow.trigger_keywords">
                        <div class="flex flex-wrap gap-1 mb-1.5">
                            <template x-for="kw in flow.trigger_keywords.split(',').map(k=>k.trim()).filter(k=>k)" :key="kw">
                                <span class="text-[11px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-mono" x-text="kw"></span>
                            </template>
                        </div>
                    </template>

                    {{-- Respuesta --}}
                    <p class="text-xs text-gray-500 line-clamp-2" x-text="flow.response_text"></p>
                </div>

                {{-- Acciones --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="abrirModal(flow)"
                            class="px-3 py-1.5 text-xs font-medium border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Editar
                    </button>
                    <button @click="eliminar(flow)"
                            class="px-3 py-1.5 text-xs font-medium border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </template>
    </div>

</div>

{{-- Modal --}}
<div x-show="modal" x-cloak
     @keydown.escape.window="modal=false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" @click="modal=false"></div>
    <div class="relative bg-white rounded-2xl w-full max-w-xl shadow-2xl" @click.stop>

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900" x-text="form.id ? 'Editar flow' : 'Nuevo flow'"></h3>
            <button @click="modal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">

            {{-- Canal --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Canal *</label>
                <select x-model.number="form.wa_canal_id"
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                    @foreach($canales as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Nombre --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nombre del flow *</label>
                <input x-model="form.nombre" placeholder="Ej: Bienvenida BIXO"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>

            {{-- Tipo --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-2">Tipo de disparador</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" :value="true" x-model="form.es_bienvenida" class="accent-green-500">
                        <span class="text-sm text-gray-700">👋 Bienvenida (primer mensaje)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" :value="false" x-model="form.es_bienvenida" class="accent-indigo-500">
                        <span class="text-sm text-gray-700">🔑 Palabras clave</span>
                    </label>
                </div>
            </div>

            {{-- Keywords (solo si no es bienvenida) --}}
            <div x-show="!form.es_bienvenida">
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Palabras clave
                    <span class="font-normal text-gray-400">(separadas por coma)</span>
                </label>
                <input x-model="form.trigger_keywords"
                       placeholder="hola, precio, info, cotización"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 font-mono">
                <p class="text-[10px] text-gray-400 mt-1">Si el mensaje del cliente contiene alguna de estas palabras, se dispara este flow.</p>
            </div>

            {{-- Respuesta --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Mensaje de respuesta *
                    <span class="font-normal text-gray-400">(soporta formato WhatsApp: *negrita*, _cursiva_)</span>
                </label>
                <textarea x-model="form.response_text" rows="6"
                          placeholder="Hola 👋 Gracias por contactarnos. Soy el asistente de *BIXO*..."
                          class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 resize-none font-mono"></textarea>
                <p class="text-[10px] text-gray-400 mt-1 text-right" x-text="(form.response_text || '').length + ' / 4096'"></p>
            </div>

            {{-- Orden y estado --}}
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Orden</label>
                    <input x-model.number="form.orden" type="number" min="0"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.activo" class="accent-green-500 w-4 h-4">
                        <span class="text-sm text-gray-700">Activo</span>
                    </label>
                </div>
            </div>

        </div>

        <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-100">
            <button @click="modal=false"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-gray-50">
                Cancelar
            </button>
            <button @click="guardar()" :disabled="guardando"
                    class="px-4 py-2 text-sm font-semibold text-white rounded-xl disabled:opacity-60"
                    style="background:#25d366">
                <span x-text="guardando ? 'Guardando...' : 'Guardar flow'"></span>
            </button>
        </div>
    </div>
</div>

@endsection

<script>
const CANALES   = @json($canalesJs);
const FLOWS_INIT = @json($flowsJs);

function chatbotApp() {
    return {
        flows: [...FLOWS_INIT],
        filtroCanal: null,
        modal: false,
        guardando: false,
        form: {},

        init() {},

        get flowsFiltrados() {
            if (!this.filtroCanal) return this.flows;
            return this.flows.filter(f => f.wa_canal_id === this.filtroCanal);
        },

        abrirModal(flow) {
            if (flow) {
                this.form = { ...flow };
            } else {
                this.form = {
                    id: null,
                    wa_canal_id: CANALES[0]?.id ?? null,
                    nombre: '',
                    trigger_keywords: '',
                    response_text: '',
                    es_bienvenida: false,
                    activo: true,
                    orden: 0,
                };
            }
            this.modal = true;
        },

        async guardar() {
            if (!this.form.nombre || !this.form.response_text) {
                return alert('Nombre y mensaje de respuesta son requeridos.');
            }
            this.guardando = true;
            try {
                const res = await fetch('{{ route('bixocrm.chatbot.guardar') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (data.ok) {
                    this.modal = false;
                    window.location.reload();
                } else {
                    alert('Error al guardar.');
                }
            } catch(e) { alert('Error de red.'); }
            this.guardando = false;
        },

        async eliminar(flow) {
            if (!confirm(`¿Eliminar flow "${flow.nombre}"?`)) return;
            const res = await fetch(`{{ url('/bixocrm/chatbot/flows') }}/${flow.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            if ((await res.json()).ok) window.location.reload();
        },
    };
}
</script>

@extends('comunicaciones.layouts.app')
@section('pageTitle', 'Clientes CRM')
@section('content')

<div class="flex-1 overflow-auto p-6"
     x-data="crm()" x-init="init()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">Clientes & Leads</h1>
            <p class="text-xs text-gray-500 mt-0.5">Todas las conversaciones de WhatsApp como CRM</p>
        </div>

        {{-- Buscador --}}
        <div class="flex items-center gap-2">
            <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text" x-model="busqueda" placeholder="Buscar..."
                       class="pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-green-400 w-52">
            </div>
        </div>
    </div>

    {{-- Stats por estado --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 mb-6">
        @php
        $estadoCfg = [
            'nuevo'        => ['label'=>'Nuevo',      'bg'=>'#dcfce7','text'=>'#166534'],
            'contactado'   => ['label'=>'Contactado', 'bg'=>'#dbeafe','text'=>'#1e40af'],
            'demo_enviada' => ['label'=>'Demo',       'bg'=>'#fef3c7','text'=>'#92400e'],
            'propuesta'    => ['label'=>'Propuesta',  'bg'=>'#ede9fe','text'=>'#5b21b6'],
            'cerrado'      => ['label'=>'Cerrado',    'bg'=>'#d1fae5','text'=>'#065f46'],
            'perdido'      => ['label'=>'Perdido',    'bg'=>'#fee2e2','text'=>'#991b1b'],
            'academia'     => ['label'=>'Academia',   'bg'=>'#e0f2fe','text'=>'#0c4a6e'],
        ];
        @endphp
        @foreach($estadoCfg as $key => $cfg)
        <button @click="filtroEstado === '{{ $key }}' ? filtroEstado='' : filtroEstado='{{ $key }}'"
                :class="filtroEstado === '{{ $key }}' ? 'ring-2 ring-offset-1' : 'opacity-80 hover:opacity-100'"
                class="rounded-xl px-3 py-2 text-center transition-all"
                style="background:{{ $cfg['bg'] }};--tw-ring-color:{{ $cfg['text'] }}">
            <p class="text-lg font-black" style="color:{{ $cfg['text'] }}">{{ $stats[$key] ?? 0 }}</p>
            <p class="text-[10px] font-semibold" style="color:{{ $cfg['text'] }}">{{ $cfg['label'] }}</p>
        </button>
        @endforeach
    </div>

    {{-- Vista toggle --}}
    <div class="flex items-center gap-2 mb-4">
        <div class="flex rounded-lg border border-gray-200 overflow-hidden">
            <button @click="vista='lista'"
                    :class="vista==='lista' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="px-3 py-1.5 text-xs font-medium transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Lista
            </button>
            <button @click="vista='kanban'"
                    :class="vista==='kanban' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                    class="px-3 py-1.5 text-xs font-medium transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                </svg>
                Kanban
            </button>
        </div>
        <span class="text-xs text-gray-400" x-text="`${clientesFiltrados.length} clientes`"></span>
    </div>

    {{-- Vista Lista --}}
    <div x-show="vista === 'lista'">
        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider hidden md:table-cell">Teléfono</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Canal</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Estado</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider hidden xl:table-cell">Sector</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Último mensaje</th>
                        <th class="text-left px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-wider hidden md:table-cell">Fecha</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="c in clientesFiltrados" :key="c.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                         :style="`background:${c.canal_color}`"
                                         x-text="(c.cliente_nombre || c.cliente_telefono).charAt(0).toUpperCase()"></div>
                                    <span class="font-medium text-gray-900 text-xs"
                                          x-text="c.cliente_nombre || c.cliente_telefono"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs text-gray-500" x-text="c.cliente_telefono"></span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
                                      :style="`background:${c.canal_color}22; color:${c.canal_color}`"
                                      x-text="c.canal_nombre"></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-[10px] font-semibold px-2 py-1 rounded-full"
                                      :style="estadoBadge(c.estado)"
                                      x-text="estadoLabel(c.estado)"></span>
                            </td>
                            <td class="px-4 py-3 hidden xl:table-cell">
                                <span class="text-xs text-gray-500" x-text="c.cliente_sector || '—'"></span>
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell max-w-[180px]">
                                <span class="text-xs text-gray-500 truncate block" x-text="c.ultimo_mensaje || '—'"></span>
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span class="text-xs text-gray-400" x-text="formatFecha(c.ultimo_mensaje_at)"></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a :href="`/comunicaciones?abrir=${c.id}`"
                                   class="text-xs font-medium hover:underline"
                                   style="color:#25d366">Abrir chat</a>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="clientesFiltrados.length === 0">
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-400">
                            Sin clientes que coincidan con el filtro
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vista Kanban --}}
    <div x-show="vista === 'kanban'" class="overflow-x-auto">
        <div class="flex gap-3 pb-4" style="min-width:max-content">
            @foreach($estadoCfg as $key => $cfg)
            <div class="flex-shrink-0 rounded-2xl p-3" style="width:230px;background:#f8fafc;border:1px solid #e2e8f0;">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold px-2 py-1 rounded-full"
                          style="background:{{ $cfg['bg'] }};color:{{ $cfg['text'] }}">
                        {{ $cfg['label'] }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $stats[$key] ?? 0 }}</span>
                </div>
                <div class="space-y-2">
                    <template x-for="c in clientesPorEstado('{{ $key }}')" :key="c.id">
                        <div class="bg-white rounded-xl p-3 border border-gray-100 shadow-sm">
                            <div class="flex items-center gap-2 mb-1.5">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                     :style="`background:${c.canal_color}`"
                                     x-text="(c.cliente_nombre || c.cliente_telefono).charAt(0).toUpperCase()"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-900 truncate"
                                       x-text="c.cliente_nombre || c.cliente_telefono"></p>
                                    <p class="text-[10px] text-gray-400" x-text="c.cliente_telefono"></p>
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-500 line-clamp-2 mb-2" x-text="c.ultimo_mensaje || 'Sin mensajes'"></p>
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full"
                                      :style="`background:${c.canal_color}22; color:${c.canal_color}`"
                                      x-text="c.canal_nombre"></span>
                                <a :href="`/comunicaciones?abrir=${c.id}`"
                                   class="text-[10px] font-medium hover:underline"
                                   style="color:#25d366">Chat</a>
                            </div>
                        </div>
                    </template>
                    <div x-show="clientesPorEstado('{{ $key }}').length === 0"
                         class="text-center py-4 text-[10px] text-gray-400">
                        Sin clientes
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

<script>
const CLIENTES_INIT = @json($clientesJs);

function crm() {
    return {
        clientes: CLIENTES_INIT,
        busqueda: '',
        filtroEstado: '',
        vista: 'lista',

        init() {},

        get clientesFiltrados() {
            return this.clientes.filter(c => {
                if (this.filtroEstado && c.estado !== this.filtroEstado) return false;
                if (this.busqueda) {
                    const q = this.busqueda.toLowerCase();
                    if (!(c.cliente_nombre || '').toLowerCase().includes(q) &&
                        !(c.cliente_telefono || '').toLowerCase().includes(q) &&
                        !(c.cliente_sector || '').toLowerCase().includes(q)) return false;
                }
                return true;
            });
        },

        clientesPorEstado(estado) {
            return this.clientes.filter(c => {
                if (c.estado !== estado) return false;
                if (this.busqueda) {
                    const q = this.busqueda.toLowerCase();
                    if (!(c.cliente_nombre || '').toLowerCase().includes(q) &&
                        !(c.cliente_telefono || '').toLowerCase().includes(q)) return false;
                }
                return true;
            });
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
                nuevo:'Nuevo', contactado:'Contactado', demo_enviada:'Demo enviada',
                propuesta:'Propuesta', cerrado:'Cerrado', perdido:'Perdido', academia:'Academia'
            };
            return map[estado] || estado || '';
        },

        formatFecha(iso) {
            if (!iso) return '—';
            const d = new Date(iso);
            const hoy = new Date();
            const ayer = new Date(); ayer.setDate(ayer.getDate()-1);
            if (d.toDateString() === hoy.toDateString()) return 'Hoy ' + d.toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'});
            if (d.toDateString() === ayer.toDateString()) return 'Ayer';
            return d.toLocaleDateString('es-PE', {day:'numeric', month:'short'});
        },
    }
}
</script>

@endsection

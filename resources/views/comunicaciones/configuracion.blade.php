@extends('comunicaciones.layouts.app')
@section('pageTitle', 'Canales')
@section('content')
<div class="flex-1 overflow-y-auto bg-gray-50 p-6">
<div class="max-w-3xl mx-auto py-8 px-4" x-data="configuracion()" x-init="init()">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">Canales de WhatsApp</h1>
            <p class="text-sm text-gray-500 mt-0.5">Números conectados a la WhatsApp Business API</p>
        </div>
        <button @click="abrirModal(null)"
                class="px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-sm"
                style="background:#25d366">
            + Nuevo canal
        </button>
    </div>

    {{-- Lista --}}
    <div class="space-y-3">
        @forelse($canales as $canal)
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                     style="background:{{ $canal->color ?? '#25d366' }}">
                    {{ strtoupper(substr($canal->nombre, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-gray-900">{{ $canal->nombre }}</p>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 uppercase">{{ $canal->tipo }}</span>
                        @if($canal->activo)
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-green-100 text-green-700">Activo</span>
                        @else
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-red-100 text-red-700">Inactivo</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $canal->telefono ?? 'Sin teléfono' }}
                        @if($canal->phone_number_id) · Phone ID: <span class="font-mono">{{ $canal->phone_number_id }}</span>@endif
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="abrirModal({{ $canal->id }})"
                            class="px-3 py-1.5 text-xs font-medium border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        Editar
                    </button>
                    <button @click="eliminar({{ $canal->id }}, '{{ $canal->nombre }}')"
                            class="px-3 py-1.5 text-xs font-medium border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>

            @if($canal->verify_token)
            <div class="mt-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">URL Webhook para Meta</p>
                <div class="flex items-center gap-2">
                    <code class="text-xs text-gray-700 flex-1 truncate">{{ url('/wa/webhook/' . $canal->verify_token) }}</code>
                    <button onclick="navigator.clipboard.writeText('{{ url('/wa/webhook/' . $canal->verify_token) }}').then(()=>this.textContent='✓').catch(()=>{})"
                            class="text-xs font-medium flex-shrink-0" style="color:#25d366">Copiar</button>
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Verify token: <span class="font-mono">{{ $canal->verify_token }}</span></p>
            </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-sm font-medium text-gray-500 mb-1">Sin canales configurados</p>
            <p class="text-xs text-gray-400">Agrega tu primer número de WhatsApp Business</p>
        </div>
        @endforelse
    </div>

    {{-- Instrucciones --}}
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-2xl p-4">
        <p class="text-sm font-bold text-blue-800 mb-2">¿Cómo obtener las credenciales?</p>
        <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
            <li>Entra a <strong>developers.facebook.com</strong> → tu app → WhatsApp → API Setup</li>
            <li>Copia el <strong>Phone Number ID</strong> y genera un <strong>Access Token permanente</strong></li>
            <li>En Webhooks, pega la URL que aparece debajo de cada canal y tu Verify Token</li>
            <li>Suscríbete a: <code class="bg-blue-100 px-1 rounded">messages</code>, <code class="bg-blue-100 px-1 rounded">message_deliveries</code>, <code class="bg-blue-100 px-1 rounded">message_reads</code></li>
        </ol>
    </div>

</div>

{{-- Modal --}}
<div x-show="modal" x-cloak
     @keydown.escape.window="modal=false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" @click="modal=false"></div>
    <div class="relative bg-white rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900" x-text="form.id ? 'Editar canal' : 'Nuevo canal'"></h3>
            <button @click="modal=false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nombre *</label>
                    <input x-model="form.nombre" placeholder="Ej: WhatsApp BIXO"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tipo *</label>
                    <select x-model="form.tipo"
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="bixo">BIXO</option>
                        <option value="academy">Academy</option>
                        <option value="partners">Partners</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Teléfono</label>
                    <input x-model="form.telefono" placeholder="+51999999999"
                           class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" x-model="form.color" class="w-10 h-9 rounded border border-gray-200 cursor-pointer p-0.5">
                        <input x-model="form.color" class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Phone Number ID</label>
                <input x-model="form.phone_number_id" placeholder="123456789012345"
                       class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 font-mono">
                <p class="text-[10px] text-gray-400 mt-1">Lo encuentras en Meta → WhatsApp → API Setup</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Access Token
                    <span x-show="form.id" class="text-gray-400 font-normal">(dejar vacío para no cambiar)</span>
                </label>
                <textarea x-model="form.access_token" rows="2"
                          placeholder="EAAxxxxx..."
                          class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 font-mono resize-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Verify Token</label>
                <div class="flex gap-2">
                    <input x-model="form.verify_token" placeholder="token_secreto"
                           class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 font-mono">
                    <button type="button" @click="form.verify_token = generarToken()"
                            class="px-3 py-2 text-xs border border-gray-200 rounded-lg hover:bg-gray-50 whitespace-nowrap">
                        Generar
                    </button>
                </div>
            </div>

            <template x-if="form.verify_token">
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">URL Webhook</p>
                    <code class="text-xs text-gray-700 break-all" x-text="`{{ url('/wa/webhook/') }}` + form.verify_token"></code>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-100">
            <button @click="modal=false" class="px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-gray-50">
                Cancelar
            </button>
            <button @click="guardar()" :disabled="guardando"
                    class="px-4 py-2 text-sm font-semibold text-white rounded-xl disabled:opacity-60"
                    style="background:#25d366">
                <span x-text="guardando ? 'Guardando...' : 'Guardar'"></span>
            </button>
        </div>
    </div>
</div>

</div>

<script>
const CANALES_INIT = @json($canalesJs);

function configuracion() {
    return {
        modal: false,
        guardando: false,
        form: {},

        init() {},

        abrirModal(id) {
            if (id) {
                const c = CANALES_INIT.find(x => x.id === id);
                this.form = { ...c, access_token: '' };
            } else {
                this.form = { id: null, nombre: '', tipo: 'bixo', telefono: '', phone_number_id: '', access_token: '', verify_token: '', color: '#25d366' };
            }
            this.modal = true;
        },

        async guardar() {
            if (!this.form.nombre || !this.form.tipo) return alert('Nombre y tipo son requeridos');
            this.guardando = true;
            const res = await fetch('{{ route('bixocrm.canales.guardar') }}', {
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
                alert('Error al guardar');
            }
            this.guardando = false;
        },

        async eliminar(id, nombre) {
            if (!confirm(`¿Eliminar "${nombre}"? Se perderán todas sus conversaciones.`)) return;
            const res = await fetch(`{{ url('/bixocrm/canales') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            if ((await res.json()).ok) window.location.reload();
        },

        generarToken() {
            return Math.random().toString(36).substring(2, 10) + Math.random().toString(36).substring(2, 10);
        },
    }
}
</script>

@endsection

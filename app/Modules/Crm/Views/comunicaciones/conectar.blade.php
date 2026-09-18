@extends('crm::comunicaciones.layouts.app')
@section('pageTitle', 'Conectar WhatsApp')
@section('content')

{{-- Asistente de conexion con Meta (WhatsApp Cloud API) en 3 pasos.
     Paso 1 prueba las credenciales contra Meta; al continuar SE GUARDA el
     canal (misma ruta que la pantalla de canales), porque Meta verifica el
     webhook comparando el verify token con los canales guardados: mostrarlo
     antes de guardarlo hacia fallar la verificacion (visto el 2026-09-18).
     Paso 2 da la URL y el token para pegar en developers.facebook.com;
     paso 3 confirma. --}}
<div class="max-w-3xl mx-auto py-8 px-4" x-data="asistenteMeta()" x-init="init()">

    @if(session('bienvenida'))
    <div class="mb-6 p-4 rounded-2xl border border-green-200 bg-green-50">
        <p class="text-sm font-bold text-green-800">Tu CRM está listo. Falta lo más importante: tu WhatsApp.</p>
        <p class="text-xs text-green-700 mt-1">Necesitas una cuenta en developers.facebook.com con un número de WhatsApp Business. Si aún no la tienes, la guía completa está en <code>docs/guias/CONECTAR_WHATSAPP_META.md</code>.</p>
    </div>
    @endif

    <h1 class="text-xl font-bold text-gray-900 mb-1">Conectar WhatsApp con Meta</h1>
    <p class="text-sm text-gray-500 mb-6">Tres pasos. Las credenciales se guardan solo cuando Meta confirma que funcionan.</p>

    {{-- Pasos --}}
    <div class="flex items-center gap-2 mb-6 text-xs font-semibold">
        <template x-for="(t, i) in ['Credenciales', 'Webhook en Meta', 'Listo']" :key="i">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-white"
                      :style="paso >= i+1 ? 'background:#25d366' : 'background:#d1d5db'" x-text="i+1"></span>
                <span :class="paso >= i+1 ? 'text-gray-900' : 'text-gray-400'" x-text="t"></span>
                <span class="w-8 h-px bg-gray-200" x-show="i < 2"></span>
            </div>
        </template>
    </div>

    {{-- PASO 1: credenciales --}}
    <div x-show="paso === 1" class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <p class="text-sm text-gray-600">Cópialas de <b>developers.facebook.com → tu app → WhatsApp → Configuración de la API</b>.</p>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Nombre de la línea</label>
            <input x-model="form.nombre" placeholder="Línea principal" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Phone number ID</label>
            <input x-model="form.phone_number_id" placeholder="123456789012345" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-mono">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">ID de la cuenta de WhatsApp Business (WABA)</label>
            <input x-model="form.waba_id" placeholder="Está justo debajo del Phone number ID" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-mono">
            <p class="text-[11px] text-gray-400 mt-1">Con él BIXO suscribe la app sola y puede usar tus plantillas aprobadas para escribir pasadas las 24 h.</p>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Token de acceso (permanente)</label>
            <textarea x-model="form.access_token" rows="2" placeholder="EAAG..." class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-mono"></textarea>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">App secret</label>
            <input x-model="form.app_secret" type="password" autocomplete="new-password" placeholder="firma los mensajes que entran" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm font-mono">
            <p class="text-[11px] text-gray-400 mt-1">Está en <b>Configuración de la app → Básica → Clave secreta</b>. Sin ella, el webhook rechaza todo.</p>
        </div>

        <div x-show="error" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="error"></div>
        <div x-show="probado" x-cloak class="p-3 rounded-xl bg-green-50 border border-green-200 text-xs text-green-800">
            Meta reconoce el número <b x-text="probado?.numero"></b> <span x-show="probado?.nombre">(<span x-text="probado?.nombre"></span>)</span>.
        </div>

        <div class="flex items-center gap-3">
            <button type="button" @click="probar()" :disabled="cargando || !form.phone_number_id || !form.access_token"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white disabled:opacity-50" style="background:#25d366">
                <span x-text="cargando ? 'Consultando a Meta...' : 'Probar conexión'"></span>
            </button>
            <button type="button" x-show="probado" x-cloak @click="guardar()" :disabled="cargando" class="px-4 py-2 rounded-xl text-sm font-semibold border border-gray-300 text-gray-700 disabled:opacity-50">
                <span x-text="cargando ? 'Guardando...' : 'Guardar y continuar →'"></span>
            </button>
        </div>
    </div>

    {{-- PASO 2: webhook --}}
    <div x-show="paso === 2" x-cloak class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
        <div x-show="suscripcion && suscripcion.ok" x-cloak class="p-3 rounded-xl bg-green-50 border border-green-200 text-xs text-green-800">✓ La app quedó suscrita a tu cuenta de WhatsApp Business: Meta ya puede entregarte los mensajes.</div>
        <div x-show="suscripcion && !suscripcion.ok" x-cloak class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">No se pudo suscribir la app sola (<span x-text="suscripcion?.error"></span>). Hazlo en Meta: WhatsApp → Configuración → Webhooks → Suscribirse.</div>
        <p class="text-sm text-gray-600">La línea ya está guardada. En <b>WhatsApp → Configuración → Webhook</b> pega estos dos datos, pulsa <b>Verificar y guardar</b> y suscríbete al campo <b>messages</b>.</p>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">URL de devolución de llamada</label>
            <div class="flex items-center gap-2 p-2 rounded-xl bg-gray-50 border border-gray-200">
                <code class="text-xs text-gray-700 flex-1 truncate">{{ $webhookUrl }}</code>
                <button type="button" @click="copiar('{{ $webhookUrl }}', $event)" class="text-xs font-semibold text-green-700">Copiar</button>
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Token de verificación</label>
            <div class="flex items-center gap-2 p-2 rounded-xl bg-gray-50 border border-gray-200">
                <code class="text-xs text-gray-700 flex-1 font-mono" x-text="form.verify_token"></code>
                <button type="button" @click="copiar(form.verify_token, $event)" class="text-xs font-semibold text-green-700">Copiar</button>
            </div>
            <p class="text-[11px] text-gray-400 mt-1">Lo generamos por ti. Meta lo usa una vez para comprobar que la URL es tuya.</p>
        </div>
        <div x-show="error" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700" x-text="error"></div>
        <div class="flex items-center gap-3">
            <button type="button" @click="paso = 1" class="px-4 py-2 rounded-xl text-sm border border-gray-300 text-gray-700">← Atrás</button>
            <button type="button" @click="paso = 3" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" style="background:#25d366">Ya lo verifiqué en Meta →</button>
        </div>
    </div>

    {{-- PASO 3: listo --}}
    <div x-show="paso === 3" x-cloak class="bg-white rounded-2xl border border-green-200 p-6 space-y-3">
        <p class="text-base font-bold text-green-800">Línea conectada.</p>
        <p class="text-sm text-gray-600">Envía un mensaje de WhatsApp a tu número y aparecerá en la bandeja. Si no llega en un minuto, revisa que el webhook en Meta esté "verificado" y suscrito a <b>messages</b>.</p>
        <a href="{{ route('bixocrm.bandeja') }}" class="inline-block px-4 py-2 rounded-xl text-sm font-semibold text-white" style="background:#25d366">Ir a la bandeja</a>
    </div>
</div>

<script>
function asistenteMeta() {
    return {
        paso: 1, cargando: false, error: '', probado: null, suscripcion: null,
        form: {
            id: {{ $canal?->id ?? 'null' }},
            nombre: @json($canal?->nombre ?? 'Línea principal'),
            tipo: 'bixo',
            phone_number_id: @json($canal?->phone_number_id ?? ''),
            waba_id: @json($canal?->waba_id ?? ''),
            access_token: '', app_secret: '',
            verify_token: @json($canal?->verify_token ?? ''),
            color: '#25d366',
        },
        init() {
            if (!this.form.verify_token) this.form.verify_token = 'bixo_' + Math.random().toString(36).slice(2, 10) + Math.random().toString(36).slice(2, 10);
        },
        async probar() {
            this.cargando = true; this.error = ''; this.probado = null;
            try {
                const r = await fetch(@json(route('bixocrm.conectar.probar')), {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || @json(csrf_token()), 'Accept': 'application/json' },
                    body: JSON.stringify({ phone_number_id: this.form.phone_number_id, access_token: this.form.access_token }),
                });
                const d = await r.json();
                if (d.ok) this.probado = d; else this.error = d.error || d.message || 'Meta rechazó las credenciales.';
            } catch (e) { this.error = 'No se pudo consultar a Meta.'; }
            this.cargando = false;
        },
        async guardar() {
            this.cargando = true; this.error = '';
            try {
                const r = await fetch(@json(route('bixocrm.canales.guardar')), {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || @json(csrf_token()), 'Accept': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const d = await r.json();
                if (d.ok) { this.form.id = d.canal.id; this.form.access_token = ''; this.form.app_secret = ''; this.suscripcion = d.suscripcion; this.paso = 2; } else this.error = d.message || 'No se pudo guardar.';
            } catch (e) { this.error = 'No se pudo guardar.'; }
            this.cargando = false;
        },
        copiar(t, ev) { navigator.clipboard?.writeText(t).then(() => { ev.target.textContent = '✓ Copiado'; }).catch(() => {}); },
    };
}
</script>
@endsection

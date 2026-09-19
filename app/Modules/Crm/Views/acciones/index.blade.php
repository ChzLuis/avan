@extends('crm::comunicaciones.layouts.app')
@section('pageTitle', 'Acciones')
@section('content')

{{-- Acciones: la siguiente cosa que hay que hacer con cada cliente. Pensada para el celular:
     una lista por urgencia (vencidas / hoy / proximas), un toque para marcar hecha, y el
     recordatorio llega por push al asesor cuando vence (crm:recordar-acciones, cada minuto). --}}
<div class="flex flex-col flex-1 min-w-0 bg-gray-50" x-data="acciones()" x-init="init()">

    <div class="flex items-center gap-2 px-3 md:px-5 py-3 bg-white border-b border-gray-200">
        <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs">
            <template x-for="p in pestanas" :key="p.id">
                <button @click="pestana = p.id" :class="pestana === p.id ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 font-semibold whitespace-nowrap">
                    <span x-text="p.nombre"></span> <span class="opacity-70" x-text="'(' + grupo(p.id).length + ')'"></span>
                </button>
            </template>
        </div>
        <button @click="nueva = true; $nextTick(() => $refs.titulo?.focus())" class="ml-auto px-3 py-1.5 rounded-lg text-xs font-semibold text-white" style="background:#16a34a">+ Acción</button>
    </div>

    {{-- Alta rapida --}}
    <div x-show="nueva" x-cloak class="px-3 md:px-5 py-3 bg-white border-b border-gray-200">
        <div class="flex flex-col md:flex-row gap-2">
            <select x-model="form.tipo" class="text-sm border border-gray-200 rounded-lg px-2 py-2 md:flex-shrink-0">
                <option value="llamada">📞 Llamar</option>
                <option value="whatsapp">💬 Escribir</option>
                <option value="reunion">🤝 Reunión</option>
                <option value="tarea">✅ Tarea</option>
            </select>
            <input x-ref="titulo" x-model="form.titulo" @keydown.enter="guardar()" placeholder="¿Qué hay que hacer? Ej: Llamar a Rosa por la propuesta" class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2">
            <div class="flex gap-1.5 flex-wrap">
                <template x-for="a in atajos" :key="a.t">
                    <button @click="form.vence_at = a.f()" :class="form.vence_at === a.f() ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700'" class="text-[11px] px-2.5 py-1.5 rounded-full font-semibold" x-text="a.t"></button>
                </template>
                <input type="datetime-local" x-model="form.vence_at" class="text-[11px] border border-gray-200 rounded-lg px-2 py-1">
            </div>
            <div class="flex gap-2">
                <button @click="guardar()" :disabled="!form.titulo.trim() || guardando" class="px-3 py-2 rounded-lg text-xs font-semibold text-white disabled:opacity-50" style="background:#16a34a">Guardar</button>
                <button @click="nueva = false" class="px-3 py-2 rounded-lg text-xs border border-gray-200 text-gray-600">Cancelar</button>
            </div>
        </div>
        {{-- Recordatorio por WhatsApp: opcional, solo si se marca. --}}
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <label class="flex items-center gap-2 text-[11px] text-gray-600 cursor-pointer" x-show="form.vence_at">
                <input type="checkbox" x-model="form.avisar" class="rounded border-gray-300">
                Avisarme por WhatsApp antes
            </label>
            <template x-if="form.avisar && form.vence_at">
                <div class="flex gap-2">
                    <select x-model="form.avisar_minutos" class="text-[11px] border border-gray-200 rounded-lg px-2 py-1.5">
                        <option value="10">10 min antes</option>
                        <option value="30">30 min antes</option>
                        <option value="60">1 h antes</option>
                        <option value="1440">1 día antes</option>
                    </select>
                    <input x-model="form.avisar_tel" placeholder="51955354646" inputmode="numeric" class="text-[11px] border border-gray-200 rounded-lg px-2 py-1.5 w-40">
                </div>
            </template>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-3 md:p-5 space-y-2">
        <template x-for="a in grupo(pestana)" :key="a.id">
            <div class="bg-white rounded-2xl border px-3 py-3" :class="a.vencida && !a.hecho_at ? 'border-red-200' : 'border-gray-200'">
                {{-- Que hay que hacer --}}
                <p class="text-sm font-semibold text-gray-900 flex items-start gap-1.5" :class="a.hecho_at ? 'line-through text-gray-400' : ''">
                    <span x-text="iconoTipo(a.tipo)"></span>
                    <span class="flex-1 min-w-0" x-text="a.titulo"></span>
                </p>
                {{-- Cuando y con quien --}}
                <p class="text-[11px] mt-1 flex items-center gap-2 flex-wrap">
                    <span class="font-semibold" :class="a.vencida && !a.hecho_at ? 'text-red-600' : 'text-gray-600'" x-text="cuandoTexto(a)"></span>
                    <a x-show="a.wa_conversacion_id" :href="'/bixocrm?conversacion=' + a.wa_conversacion_id" class="text-green-700 font-semibold hover:underline"
                       x-text="'💬 ' + nombreContacto(a)"></a>
                    <span x-show="a.avisar_whatsapp" class="text-gray-500"
                          x-text="'🔔 aviso ' + (a.avisar_minutos >= 60 ? (a.avisar_minutos / 60) + ' h' : a.avisar_minutos + ' min') + ' antes'"></span>
                </p>
                <p x-show="a.notas" class="text-[11px] text-gray-500 mt-1 whitespace-pre-wrap" x-text="a.notas"></p>

                {{-- Acciones con TEXTO: nada de iconos que se toquen sin querer --}}
                <div class="flex flex-wrap gap-1.5 mt-2.5">
                    <a x-show="!a.hecho_at && a.tipo === 'llamada' && a.contacto_telefono" :href="'tel:+' + a.contacto_telefono"
                       class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg bg-gray-100 text-gray-700">📞 Llamar</a>
                    <a x-show="!a.hecho_at && a.tipo !== 'llamada' && a.wa_conversacion_id" :href="'/bixocrm?conversacion=' + a.wa_conversacion_id"
                       class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg bg-gray-100 text-gray-700">💬 Abrir chat</a>
                    <button x-show="!a.hecho_at" @click="hacer(a)" class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg text-white" style="background:#16a34a">✓ Ya la hice</button>
                    <button x-show="!a.hecho_at" @click="posponer(a)" class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg bg-gray-100 text-gray-700">Posponer a mañana</button>
                    <button x-show="a.hecho_at" @click="reabrir(a)" class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg bg-gray-100 text-gray-700">↩ Reabrir</button>
                    <button @click="borrar(a)" class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 ml-auto">Borrar</button>
                </div>
            </div>
        </template>
        <div x-show="grupo(pestana).length === 0" class="text-center text-sm text-gray-400 py-16">
            <div class="text-4xl mb-2">✅</div>Nada por aquí.
        </div>
    </div>

    {{-- Se puede deshacer durante unos segundos: marcar hecha no es un camino sin retorno. --}}
    <div x-show="deshacer" x-cloak class="fixed left-1/2 z-50 flex items-center gap-3 px-4 py-2.5 rounded-xl shadow-lg text-white text-xs"
         style="bottom:calc(72px + env(safe-area-inset-bottom));transform:translateX(-50%);background:#1f2937">
        <span x-text="deshacer?.texto"></span>
        <button @click="deshacerAhora()" class="font-bold underline">Deshacer</button>
    </div>
</div>

<script>
function acciones() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const local = (d) => { const p = n => String(n).padStart(2, '0'); return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes()); };
    return {
        acciones: @json($acciones),
        pestana: 'hoy', nueva: false, guardando: false,
        deshacer: null, _deshacerTimer: null,
        form: {
            titulo: '', vence_at: '', tipo: 'llamada',
            avisar: false, avisar_minutos: '10',
            avisar_tel: (() => { try { return localStorage.getItem('bx_avisar_tel') || ''; } catch (e) { return ''; } })(),
        },
        iconoTipo(t) { return { llamada: '📞', whatsapp: '💬', reunion: '🤝' }[t] || '✅'; },
        // El nombre de perfil de WhatsApp puede ser solo un emoji (⌚) o estar vacio:
        // en ese caso se muestra el telefono, que si identifica al cliente.
        nombreContacto(a) {
            const n = (a.contacto || '').replace(/[^\p{L}\p{N}]/gu, '').trim();
            return n ? a.contacto : (a.contacto_telefono || 'chat');
        },
        pestanas: [{ id: 'vencidas', nombre: 'Vencidas' }, { id: 'hoy', nombre: 'Hoy' }, { id: 'proximas', nombre: 'Próximas' }, { id: 'hechas', nombre: 'Hechas' }],
        atajos: [
            { t: 'En 2 h', f: () => local(new Date(Date.now() + 2 * 3600e3)) },
            { t: 'Mañana 9:00', f: () => { const d = new Date(); d.setDate(d.getDate() + 1); d.setHours(9, 0, 0, 0); return local(d); } },
            { t: 'Lunes 9:00', f: () => { const d = new Date(); d.setDate(d.getDate() + ((8 - d.getDay()) % 7 || 7)); d.setHours(9, 0, 0, 0); return local(d); } },
        ],
        init() {
            if (this.grupo('vencidas').length && !this.grupo('hoy').length) this.pestana = 'vencidas';
            if (new URLSearchParams(location.search).get('nueva')) this.nueva = true;
        },
        grupo(id) {
            const hoyFin = new Date(); hoyFin.setHours(23, 59, 59, 999);
            return this.acciones.filter(a => {
                if (id === 'hechas') return !!a.hecho_at;
                if (a.hecho_at) return false;
                const v = a.vence_at ? new Date(a.vence_at) : null;
                if (id === 'vencidas') return v && v < new Date();
                if (id === 'hoy') return !v || (v >= new Date() && v <= hoyFin);
                return v && v > hoyFin;
            });
        },
        async guardar() {
            if (!this.form.titulo.trim()) return;
            this.guardando = true;
            const avisa = this.form.avisar && this.form.vence_at && this.form.avisar_tel.trim();
            const r = await fetch('/bixocrm/acciones', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body: JSON.stringify({
                    titulo: this.form.titulo,
                    tipo: this.form.tipo,
                    vence_at: this.form.vence_at || null,
                    avisar_whatsapp: avisa ? this.form.avisar_tel.trim() : null,
                    avisar_minutos: avisa ? Number(this.form.avisar_minutos) : null,
                }) });
            const d = await r.json().catch(() => ({}));
            if (d.ok) {
                this.acciones.unshift(d.accion);
                if (avisa) { try { localStorage.setItem('bx_avisar_tel', this.form.avisar_tel.trim()); } catch (e) {} }
                this.form = { ...this.form, titulo: '', vence_at: '', avisar: false };
                this.nueva = false;
            }
            else if (typeof bxAviso === 'function') bxAviso('No se pudo guardar', 'error');
            this.guardando = false;
        },
        async patch(a, cuerpo) {
            const r = await fetch('/bixocrm/acciones/' + a.id, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: JSON.stringify(cuerpo) });
            const d = await r.json().catch(() => ({}));
            if (d.ok) Object.assign(a, d.accion);
        },
        // Fecha en palabras: "Hoy 22:00", "Mañana 09:00", "sáb. 19 sep., 22:00".
        cuandoTexto(a) {
            if (!a.vence_at) return 'Sin fecha';
            const v = new Date(a.vence_at), h = new Date(), m = new Date();
            m.setDate(m.getDate() + 1);
            const hora = v.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', hour12: false });
            const mismoDia = (x, y) => x.toDateString() === y.toDateString();
            if (mismoDia(v, h)) return (v < new Date() && !a.hecho_at ? 'Hoy ' : 'Hoy ') + hora;
            if (mismoDia(v, m)) return 'Mañana ' + hora;
            return a.vence_texto || v.toLocaleDateString('es-PE');
        },

        // Marcar hecha / reabrir, siempre con opcion de deshacer unos segundos.
        hacer(a) { this.patch(a, { hecha: true }); this.mostrarDeshacer(a, 'Marcada como hecha', { hecha: false }); },
        reabrir(a) { this.patch(a, { hecha: false }); this.mostrarDeshacer(a, 'Vuelve a estar pendiente', { hecha: true }); },
        mostrarDeshacer(a, texto, revertir) {
            clearTimeout(this._deshacerTimer);
            this.deshacer = { id: a.id, texto, revertir };
            this._deshacerTimer = setTimeout(() => { this.deshacer = null; }, 6000);
        },
        deshacerAhora() {
            const d = this.deshacer;
            if (!d) return;
            const a = this.acciones.find(x => x.id === d.id);
            if (a) this.patch(a, d.revertir);
            this.deshacer = null;
        },
        posponer(a) {
            const antes = a.vence_at;
            const d = new Date(); d.setDate(d.getDate() + 1); d.setHours(9, 0, 0, 0);
            this.patch(a, { vence_at: local(d) });
            this.mostrarDeshacer(a, 'Movida a mañana 9:00', { vence_at: antes ? local(new Date(antes)) : null });
        },
        async borrar(a) {
            const ok = typeof bxConfirmar === 'function'
                ? await bxConfirmar({ titulo: 'Borrar acción', descripcion: '"' + a.titulo + '" se elimina para siempre. ¿Continuar?', confirmar: 'Sí, borrar' })
                : confirm('¿Borrar "' + a.titulo + '"? Se elimina para siempre.');
            if (!ok) return;
            const r = await fetch('/bixocrm/acciones/' + a.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
            if (r.ok) this.acciones = this.acciones.filter(x => x.id !== a.id);
        },
    };
}
</script>
@endsection

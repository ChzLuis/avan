@extends('crm::comunicaciones.layouts.app')
@section('pageTitle', 'Tratos')
@section('content')

{{-- Tratos: embudo de ventas en tablero (arrastrar entre etapas) o lista.
     Propio de BIXO: cada trato conserva el enlace al chat de WhatsApp del que
     nacio, cada etapa lleva una probabilidad (prevision ponderada) y se ve
     cuantos dias lleva un trato sin moverse. --}}
<div class="flex flex-col flex-1 min-w-0 bg-gray-50" x-data="tratos()" x-init="init()">

    {{-- Barra superior --}}
    <div class="flex flex-wrap items-center gap-2 px-3 md:px-5 py-3 bg-white border-b border-gray-200">
        <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs">
            <button @click="vista='tablero'" :class="vista==='tablero' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 font-semibold">Tablero</button>
            <button @click="vista='lista'" :class="vista==='lista' ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-1.5 font-semibold">Lista</button>
        </div>
        <button @click="abrirNuevo()" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white" style="background:#16a34a">+ Trato</button>
        <div class="flex items-center gap-1 text-xs ml-1">
            <button @click="soloMios=!soloMios" :class="soloMios ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-1 rounded-full font-semibold">Solo míos</button>
            <button @click="verCerrados=!verCerrados" :class="verCerrados ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600'" class="px-2.5 py-1 rounded-full font-semibold">Ganados y perdidos</button>
        </div>
        <div class="ml-auto flex items-center gap-3 text-xs text-gray-600">
            <span><b x-text="abiertos.length"></b> abiertos · <b x-text="dinero(totalAbierto)"></b></span>
            <span class="hidden md:inline" title="Suma de valor × probabilidad de la etapa">previsión <b x-text="dinero(prevision)"></b></span>
            <button @click="editarEtapas()" class="px-2.5 py-1 rounded-lg border border-gray-200 hover:bg-gray-50 font-semibold">Etapas</button>
        </div>
    </div>

    {{-- TABLERO --}}
    <div x-show="vista==='tablero'" class="flex-1 overflow-x-auto overflow-y-hidden" :style="window.innerWidth < 768 ? 'scroll-snap-type:x mandatory' : ''">
        <div class="flex gap-3 p-3 md:p-4 h-full min-w-max">
            <template x-for="et in etapasVisibles" :key="et.id">
                <div class="flex flex-col w-[270px] md:w-[290px] rounded-2xl bg-gray-100/80 h-full" :style="window.innerWidth < 768 ? 'width:84vw;scroll-snap-align:start' : ''"
                     @dragover.prevent="sobre = et.id" @dragleave="sobre = null" @drop.prevent="soltar(et)"
                     :class="sobre === et.id ? 'ring-2 ring-indigo-400' : ''">
                    <div class="px-3 pt-3 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :style="`background:${et.color}`"></span>
                            <p class="text-sm font-bold text-gray-900 truncate" x-text="et.nombre"></p>
                            <span class="ml-auto text-[10px] text-gray-500" x-text="et.probabilidad + '%'" x-show="!et.es_ganado && !et.es_perdido"></span>
                        </div>
                        <p class="text-[11px] text-gray-500 mt-0.5"><span x-text="dinero(sumaEtapa(et))"></span> · <span x-text="porEtapa(et).length"></span> <span x-text="porEtapa(et).length === 1 ? 'trato' : 'tratos'"></span></p>
                    </div>
                    <div class="flex-1 overflow-y-auto px-2 pb-2 space-y-2">
                        <template x-for="t in porEtapa(et)" :key="t.id">
                            <div draggable="true" @dragstart="arrastrando = t" @dragend="arrastrando = null; sobre = null"
                                 @click="abrirEditar(t)"
                                 class="bg-white rounded-xl border border-gray-200 p-3 cursor-pointer hover:shadow-md hover:border-indigo-300 transition">
                                <p class="text-sm font-semibold text-gray-900 leading-snug" x-text="t.titulo"></p>
                                <p class="text-xs text-gray-500 truncate mt-0.5" x-text="t.contacto_nombre || t.contacto_telefono || 'Sin contacto'"></p>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs font-bold text-gray-800" x-text="dinero(t.valor, t.moneda)"></span>
                                    <span class="flex items-center gap-1">
                                        <a x-show="t.wa_conversacion_id" :href="'/bixocrm?q=' + encodeURIComponent(t.contacto_telefono || '')" @click.stop
                                           class="text-[11px] px-1.5 py-0.5 rounded-full bg-green-50 text-green-700 font-semibold" title="Abrir el chat de WhatsApp">💬</a>
                                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-bold flex items-center justify-center" :title="t.asesor || 'Sin asesor'" x-text="(t.asesor || '?').charAt(0).toUpperCase()"></span>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-1.5 text-[10px]">
                                    <span :class="t.dias_en_etapa >= 7 && !et.es_ganado && !et.es_perdido ? 'text-red-600 font-semibold' : 'text-gray-400'"
                                          x-text="t.dias_en_etapa === 0 ? 'hoy' : t.dias_en_etapa + (t.dias_en_etapa === 1 ? ' día' : ' días') + ' aquí'"></span>
                                    <span class="text-gray-400" x-show="t.cierre_estimado" x-text="'cierre ' + fecha(t.cierre_estimado)"></span>
                                </div>
                            </div>
                        </template>
                        <button @click="abrirNuevo(et)" class="w-full py-2 text-xs text-gray-400 hover:text-gray-700 hover:bg-white rounded-xl border border-dashed border-gray-300">+ añadir aquí</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- LISTA --}}
    <div x-show="vista==='lista'" x-cloak class="flex-1 overflow-auto p-3 md:p-5">
        <table class="w-full text-sm bg-white rounded-xl border border-gray-200 overflow-hidden">
            <thead class="bg-gray-50 text-[11px] uppercase text-gray-500">
                <tr><th class="text-left px-3 py-2">Trato</th><th class="text-left px-3 py-2">Contacto</th><th class="text-left px-3 py-2">Etapa</th><th class="text-right px-3 py-2">Valor</th><th class="text-left px-3 py-2 hidden md:table-cell">Asesor</th><th class="text-left px-3 py-2 hidden md:table-cell">Cierre</th><th class="text-right px-3 py-2 hidden md:table-cell">Días</th></tr>
            </thead>
            <tbody>
                <template x-for="t in listaVisible" :key="t.id">
                    <tr @click="abrirEditar(t)" class="border-t border-gray-100 hover:bg-indigo-50/40 cursor-pointer">
                        <td class="px-3 py-2 font-semibold text-gray-900" x-text="t.titulo"></td>
                        <td class="px-3 py-2 text-gray-600" x-text="t.contacto_nombre || t.contacto_telefono || '—'"></td>
                        <td class="px-3 py-2"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full text-white" :style="`background:${etapaDe(t)?.color}`" x-text="etapaDe(t)?.nombre"></span></td>
                        <td class="px-3 py-2 text-right font-semibold" x-text="dinero(t.valor, t.moneda)"></td>
                        <td class="px-3 py-2 text-gray-600 hidden md:table-cell" x-text="t.asesor || '—'"></td>
                        <td class="px-3 py-2 text-gray-600 hidden md:table-cell" x-text="t.cierre_estimado ? fecha(t.cierre_estimado) : '—'"></td>
                        <td class="px-3 py-2 text-right hidden md:table-cell" :class="t.dias_en_etapa >= 7 ? 'text-red-600 font-semibold' : 'text-gray-500'" x-text="t.dias_en_etapa"></td>
                    </tr>
                </template>
                <tr x-show="listaVisible.length === 0"><td colspan="7" class="px-3 py-8 text-center text-gray-400 text-xs">Sin tratos todavía. Crea el primero o ábrelo desde un chat.</td></tr>
            </tbody>
        </table>
    </div>

    {{-- MODAL: nuevo / editar trato --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5)" @keydown.escape.window="modal=false">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[92vh] overflow-y-auto" @click.outside="modal=false">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 text-sm" x-text="form.id ? 'Editar trato' : 'Nuevo trato'"></h3>
                <button @click="modal=false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div class="p-5 space-y-3 text-sm">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Título</label>
                    <input x-model="form.titulo" placeholder="Ej. Tienda virtual para Ferretería Lima" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1">Contacto</label><input x-model="form.contacto_nombre" placeholder="Nombre" class="w-full px-3 py-2 border border-gray-200 rounded-xl"></div>
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1">WhatsApp</label><input x-model="form.contacto_telefono" placeholder="51999111222" class="w-full px-3 py-2 border border-gray-200 rounded-xl"></div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1">Valor</label><input x-model.number="form.valor" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-200 rounded-xl"></div>
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1">Moneda</label>
                        <select x-model="form.moneda" class="w-full px-3 py-2 border border-gray-200 rounded-xl"><option>PEN</option><option>USD</option></select></div>
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1">Cierre estimado</label><input x-model="form.cierre_estimado" type="date" class="w-full px-3 py-2 border border-gray-200 rounded-xl"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1">Etapa</label>
                        <select x-model.number="form.etapa_id" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                            <template x-for="et in etapas" :key="et.id"><option :value="et.id" x-text="et.nombre"></option></template>
                        </select></div>
                    <div><label class="block text-xs font-semibold text-gray-600 mb-1">Asesor</label>
                        <select x-model="form.asesor_id" class="w-full px-3 py-2 border border-gray-200 rounded-xl">
                            <option value="">Sin asignar</option>
                            @foreach($asesores as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                        </select></div>
                </div>
                <div><label class="block text-xs font-semibold text-gray-600 mb-1">Notas</label><textarea x-model="form.notas" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl"></textarea></div>
                <p x-show="form.wa_conversacion_id" class="text-[11px] text-green-700 bg-green-50 rounded-lg px-2 py-1">Nace de una conversación de WhatsApp: el chat quedará enlazado al trato.</p>
                <p x-show="error" x-cloak class="text-xs text-red-700 bg-red-50 rounded-lg px-2 py-1" x-text="error"></p>
            </div>
            <div class="px-5 py-3 border-t border-gray-100 flex items-center gap-2">
                <button x-show="form.id" @click="eliminar()" class="text-xs text-red-600 hover:underline">Eliminar</button>
                <div class="ml-auto flex gap-2">
                    <button @click="modal=false" class="px-4 py-2 text-sm border border-gray-200 rounded-xl">Cancelar</button>
                    <button @click="guardar()" :disabled="guardando" class="px-4 py-2 text-sm font-semibold text-white rounded-xl disabled:opacity-50" style="background:#16a34a">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: motivo de perdida --}}
    <div x-show="modalPerdida" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5)">
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl p-5">
            <h3 class="font-bold text-gray-900 text-sm mb-1">¿Por qué se perdió?</h3>
            <p class="text-xs text-gray-500 mb-3">Sirve para ver después dónde se pierden las ventas.</p>
            <div class="flex flex-wrap gap-1.5 mb-3">
                <template x-for="m in ['Precio', 'Eligió a la competencia', 'No responde', 'Sin presupuesto', 'No era el momento', 'Otro']" :key="m">
                    <button @click="motivoPerdida = m" :class="motivoPerdida === m ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-2.5 py-1 rounded-full text-xs font-semibold" x-text="m"></button>
                </template>
            </div>
            <input x-model="motivoPerdida" placeholder="Motivo" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
            <div class="flex justify-end gap-2 mt-4">
                <button @click="modalPerdida=false; pendientePerdida=null" class="px-4 py-2 text-sm border border-gray-200 rounded-xl">Cancelar</button>
                <button @click="confirmarPerdida()" class="px-4 py-2 text-sm font-semibold text-white rounded-xl bg-red-600">Marcar perdido</button>
            </div>
        </div>
    </div>

    {{-- MODAL: etapas --}}
    <div x-show="modalEtapas" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.5)" @keydown.escape.window="modalEtapas=false">
        <div class="bg-white rounded-2xl w-full max-w-xl shadow-2xl max-h-[92vh] overflow-y-auto" @click.outside="modalEtapas=false">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-900 text-sm">Etapas del embudo</h3>
                <p class="text-xs text-gray-500 mt-0.5">Cada negocio vende a su manera. La probabilidad alimenta la previsión. Una etapa borrada manda sus tratos a la primera.</p>
            </div>
            <div class="p-5 space-y-2 text-sm">
                <template x-for="(et, i) in etapasEdit" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="color" x-model="et.color" class="w-8 h-8 rounded border border-gray-200 p-0">
                        <input x-model="et.nombre" placeholder="Nombre" class="flex-1 px-2 py-1.5 border border-gray-200 rounded-lg">
                        <input x-model.number="et.probabilidad" type="number" min="0" max="100" class="w-16 px-2 py-1.5 border border-gray-200 rounded-lg text-right" title="% de probabilidad">
                        <label class="text-[10px] text-gray-500 flex items-center gap-1"><input type="checkbox" x-model="et.es_ganado"> ganado</label>
                        <label class="text-[10px] text-gray-500 flex items-center gap-1"><input type="checkbox" x-model="et.es_perdido"> perdido</label>
                        <button @click="i > 0 && etapasEdit.splice(i-1, 0, etapasEdit.splice(i,1)[0])" class="text-gray-400 hover:text-gray-700" title="Subir">↑</button>
                        <button @click="i < etapasEdit.length-1 && etapasEdit.splice(i+1, 0, etapasEdit.splice(i,1)[0])" class="text-gray-400 hover:text-gray-700" title="Bajar">↓</button>
                        <button @click="etapasEdit.splice(i,1)" class="text-red-400 hover:text-red-600" title="Quitar">✕</button>
                    </div>
                </template>
                <button @click="etapasEdit.push({id:null, nombre:'', color:'#6366f1', probabilidad:50, es_ganado:false, es_perdido:false})" class="text-xs text-indigo-600 font-semibold">+ añadir etapa</button>
                <p x-show="errorEtapas" x-cloak class="text-xs text-red-700 bg-red-50 rounded-lg px-2 py-1" x-text="errorEtapas"></p>
            </div>
            <div class="px-5 py-3 border-t border-gray-100 flex justify-end gap-2">
                <button @click="modalEtapas=false" class="px-4 py-2 text-sm border border-gray-200 rounded-xl">Cancelar</button>
                <button @click="guardarEtapas()" class="px-4 py-2 text-sm font-semibold text-white rounded-xl" style="background:#16a34a">Guardar etapas</button>
            </div>
        </div>
    </div>
</div>

<script>
function tratos() {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const cab = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
    return {
        etapas: @json($etapas),
        tratos: @json($tratosJs),
        vista: @json($vista),
        soloMios: false, verCerrados: false,
        arrastrando: null, sobre: null,
        modal: false, guardando: false, error: '', form: {},
        modalPerdida: false, pendientePerdida: null, motivoPerdida: '',
        modalEtapas: false, etapasEdit: [], errorEtapas: '',
        YO: @json(auth()->id()),

        init() {
            const pre = @json($prellenado);
            if (pre) this.abrirNuevo(null, pre);
        },
        get etapasVisibles() { return this.etapas.filter(e => this.verCerrados || (!e.es_ganado && !e.es_perdido)); },
        etapaDe(t) { return this.etapas.find(e => e.id === t.etapa_id); },
        esCerrado(t) { const e = this.etapaDe(t); return !!(e && (e.es_ganado || e.es_perdido)); },
        filtrados() { return this.tratos.filter(t => !this.soloMios || t.asesor_id === this.YO); },
        porEtapa(et) { return this.filtrados().filter(t => t.etapa_id === et.id).sort((a,b) => a.orden - b.orden || b.id - a.id); },
        get abiertos() { return this.filtrados().filter(t => !this.esCerrado(t)); },
        get listaVisible() { return this.filtrados().filter(t => this.verCerrados || !this.esCerrado(t)).sort((a,b) => (this.etapaDe(a)?.orden ?? 0) - (this.etapaDe(b)?.orden ?? 0) || b.id - a.id); },
        get totalAbierto() { return this.abiertos.reduce((s, t) => s + (t.valor || 0), 0); },
        get prevision() { return this.abiertos.reduce((s, t) => s + (t.valor || 0) * ((this.etapaDe(t)?.probabilidad ?? 0) / 100), 0); },
        sumaEtapa(et) { return this.porEtapa(et).reduce((s, t) => s + (t.valor || 0), 0); },
        dinero(n, moneda = 'PEN') { return (moneda === 'USD' ? '$ ' : 'S/ ') + Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); },
        fecha(d) { if (!d) return ''; const [y,m,dd] = d.split('-'); return dd + '/' + m; },

        abrirNuevo(et = null, pre = {}) {
            this.error = '';
            this.form = { id: null, titulo: '', contacto_nombre: '', contacto_telefono: '', valor: 0, moneda: 'PEN', etapa_id: et?.id || this.etapasVisibles[0]?.id, asesor_id: '', cierre_estimado: '', notas: '', wa_conversacion_id: null, ...pre };
            this.modal = true;
        },
        abrirEditar(t) { this.error = ''; this.form = { ...t, asesor_id: t.asesor_id || '' }; this.modal = true; },
        async guardar() {
            if (!this.form.titulo?.trim()) { this.error = 'Ponle un título al trato.'; return; }
            this.guardando = true; this.error = '';
            const url = this.form.id ? `/bixocrm/tratos/${this.form.id}` : '/bixocrm/tratos';
            const res = await fetch(url, { method: this.form.id ? 'PATCH' : 'POST', headers: cab, body: JSON.stringify({ ...this.form, asesor_id: this.form.asesor_id || null }) });
            const d = await res.json().catch(() => ({}));
            this.guardando = false;
            if (!res.ok) { this.error = d.message || Object.values(d.errors || {}).flat().join(' ') || 'No se pudo guardar.'; return; }
            const i = this.tratos.findIndex(x => x.id === d.trato.id);
            if (i >= 0) this.tratos[i] = d.trato; else this.tratos.push(d.trato);
            // Si se cambio la etapa desde el formulario, se mueve con la misma regla que arrastrando.
            if (this.form.id && this.form.etapa_id && this.form.etapa_id !== d.trato.etapa_id) await this.moverA(d.trato, this.etapas.find(e => e.id === this.form.etapa_id));
            this.modal = false;
        },
        async eliminar() {
            if (!confirm('¿Eliminar este trato?')) return;
            const res = await fetch(`/bixocrm/tratos/${this.form.id}`, { method: 'DELETE', headers: cab });
            if (res.ok) { this.tratos = this.tratos.filter(x => x.id !== this.form.id); this.modal = false; }
        },
        soltar(et) {
            const t = this.arrastrando; this.sobre = null;
            if (!t || t.etapa_id === et.id) return;
            this.moverA(t, et);
        },
        async moverA(t, et, motivo = null) {
            if (!et) return;
            if (et.es_perdido && !motivo) { this.pendientePerdida = { t, et }; this.motivoPerdida = ''; this.modalPerdida = true; return; }
            const res = await fetch(`/bixocrm/tratos/${t.id}/mover`, { method: 'PATCH', headers: cab, body: JSON.stringify({ etapa_id: et.id, motivo_perdida: motivo }) });
            const d = await res.json().catch(() => ({}));
            if (res.ok) { const i = this.tratos.findIndex(x => x.id === t.id); if (i >= 0) this.tratos[i] = d.trato; }
        },
        confirmarPerdida() {
            const p = this.pendientePerdida; this.modalPerdida = false; this.pendientePerdida = null;
            if (p) this.moverA(p.t, p.et, this.motivoPerdida || 'Sin motivo');
        },
        editarEtapas() { this.etapasEdit = this.etapas.map(e => ({ ...e })); this.errorEtapas = ''; this.modalEtapas = true; },
        async guardarEtapas() {
            const res = await fetch('/bixocrm/tratos/etapas', { method: 'PUT', headers: cab, body: JSON.stringify({ etapas: this.etapasEdit }) });
            const d = await res.json().catch(() => ({}));
            if (!res.ok) { this.errorEtapas = d.message || Object.values(d.errors || {}).flat().join(' ') || 'No se pudo guardar.'; return; }
            this.etapas = d.etapas; this.modalEtapas = false;
            location.reload();
        },
    };
}
</script>
@endsection

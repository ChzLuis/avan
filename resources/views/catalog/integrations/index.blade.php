<x-app-layout>
<style>
.ci-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px}
.ci-btn{min-height:40px;border-radius:10px;font-weight:700;font-size:13px;display:inline-flex;align-items:center;justify-content:center;gap:6px;cursor:pointer;border:2px solid transparent;padding:0 14px;transition:.15s}
.ci-btn:disabled{opacity:.5;cursor:not-allowed}
.ci-btn-p{background:#6D28D9;color:#fff}.ci-btn-p:hover{filter:brightness(1.08)}
.ci-btn-o{background:#fff;border-color:#D1D5DB;color:#111827}.ci-btn-o:hover{border-color:#6D28D9;color:#6D28D9}
.ci-btn-g{background:#059669;color:#fff}
.ci-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:3px 10px;border-radius:999px}
.ci-badge-connected{background:#DCFCE7;color:#166534}
.ci-badge-pending{background:#F3F4F6;color:#6B7280}
.ci-badge-warning{background:#FEF3C7;color:#92400E}
.ci-badge-error{background:#FEE2E2;color:#991B1B}
.ci-badge-disabled{background:#F3F4F6;color:#9CA3AF}
.ci-in{width:100%;border:2px solid #E5E7EB;border-radius:10px;padding:10px 13px;font-size:13.5px;outline:0}
.ci-in:focus{border-color:#6D28D9}
</style>

<div class="p-5 max-w-5xl mx-auto" x-data="catalogIntegrations()" x-init="init()">
    <div class="mb-5 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-xl font-black text-gray-900">Conecta tu catálogo</h1>
            <p class="text-sm text-gray-500">Sincroniza productos y servicios desde tu ERP automáticamente. Tú sigues manejando precios y stock ahí — aquí solo se reflejan.</p>
        </div>
        <button class="ci-btn ci-btn-p" @click="openNew()">+ Conectar catálogo</button>
    </div>

    {{-- Listado de integraciones --}}
    <div class="space-y-3 mb-8">
        <template x-for="i in integrations" :key="i.id">
            <div class="ci-card p-4">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-700 flex items-center justify-center font-black text-sm" x-text="i.provider_name.slice(0,2).toUpperCase()"></div>
                        <div>
                            <p class="font-black text-gray-900" x-text="i.name"></p>
                            <p class="text-xs text-gray-400" x-text="i.provider_name + ' · ' + i.products_count + ' producto(s) vinculados'"></p>
                        </div>
                    </div>
                    <span class="ci-badge" :class="'ci-badge-'+i.status" x-text="statusLabel(i.status)"></span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3 text-xs">
                    <div><p class="text-gray-400 font-bold uppercase text-[10px]">Última sync</p><p class="font-semibold text-gray-700" x-text="i.last_successful_sync_at ? fmtDate(i.last_successful_sync_at) : 'Nunca'"></p></div>
                    <div><p class="text-gray-400 font-bold uppercase text-[10px]">Modo</p><p class="font-semibold text-gray-700" x-text="i.sync_mode==='full' ? 'Completa' : 'Incremental'"></p></div>
                    <div><p class="text-gray-400 font-bold uppercase text-[10px]">Cada</p><p class="font-semibold text-gray-700" x-text="i.sync_interval_minutes+' min'"></p></div>
                    <div><p class="text-gray-400 font-bold uppercase text-[10px]">Estado</p><p class="font-semibold text-gray-700" x-text="i.active ? 'Activa' : 'Desactivada'"></p></div>
                </div>

                <p x-show="i.last_sync_error" x-cloak class="mt-2 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="i.last_sync_error"></p>

                <div class="flex flex-wrap gap-2 mt-3">
                    <button class="ci-btn ci-btn-o" @click="testConnection(i)" :disabled="busy===i.id">Probar conexión</button>
                    <button class="ci-btn ci-btn-p" @click="syncNow(i)" :disabled="busy===i.id">
                        <span x-text="busy===i.id ? 'Sincronizando…' : 'Sincronizar ahora'"></span>
                    </button>
                    <button class="ci-btn ci-btn-o" @click="openHistory(i)">Ver historial</button>
                    <button class="ci-btn ci-btn-o" @click="toggleActive(i)" x-text="i.active ? 'Desactivar' : 'Activar'"></button>
                </div>
                <p x-show="feedback[i.id]" x-cloak class="text-xs font-bold mt-2" :class="feedback[i.id] && feedback[i.id].ok ? 'text-emerald-600' : 'text-red-600'" x-text="feedback[i.id] && feedback[i.id].message"></p>
            </div>
        </template>

        <div x-show="!integrations.length" class="ci-card p-8 text-center">
            <p class="text-sm font-bold text-gray-700">Todavía no conectaste ningún catálogo</p>
            <p class="text-xs text-gray-400 mt-1">Conecta tu ERP y tus productos aparecerán acá — con precio y stock siempre al día.</p>
        </div>
    </div>

    {{-- MODAL: nueva conexión --}}
    <div x-show="newOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,.5)" @click.self="newOpen=false" @keydown.escape.window="newOpen=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-5 space-y-3 max-h-[85vh] overflow-y-auto">
            <h3 class="font-black text-gray-900">Conectar catálogo</h3>

            <template x-if="!selectedProvider">
                <div class="space-y-2">
                    <p class="text-xs text-gray-500">Elige el sistema desde el que quieres traer tus productos:</p>
                    <template x-for="p in availableProviders" :key="p.key">
                        <button class="w-full text-left ci-card p-3 hover:border-violet-400" @click="pickProvider(p)">
                            <p class="font-bold text-sm text-gray-900" x-text="p.name"></p>
                            <p class="text-[11px] text-gray-400" x-text="[p.capabilities.products&&'Productos', p.capabilities.services&&'Servicios', p.capabilities.categories&&'Categorías'].filter(Boolean).join(' · ')"></p>
                        </button>
                    </template>
                </div>
            </template>

            <template x-if="selectedProvider">
                <div class="space-y-3">
                    <button class="text-[11px] font-bold text-gray-400" @click="selectedProvider=null">← Elegir otro proveedor</button>
                    <label class="block"><span class="text-xs font-bold text-gray-600">Nombre de esta conexión</span>
                        <input type="text" x-model="form.name" class="ci-in mt-1" placeholder="Ej. Catálogo tienda principal"></label>

                    <template x-for="field in schemaFields" :key="field.name">
                        <label class="block">
                            <span class="text-xs font-bold text-gray-600" x-text="field.label + (field.required ? ' *' : '')"></span>
                            <input :type="field.type==='password' ? 'password' : (field.type==='url' ? 'url' : 'text')"
                                   x-model="form.credentials[field.name]" class="ci-in mt-1" :placeholder="field.default || ''">
                            <span x-show="field.help" class="text-[10px] text-gray-400" x-text="field.help"></span>
                        </label>
                    </template>

                    <p x-show="createError" x-cloak class="text-xs font-bold text-red-600" x-text="createError"></p>
                    <div class="flex gap-2 pt-1">
                        <button class="ci-btn ci-btn-o flex-1" @click="newOpen=false">Cancelar</button>
                        <button class="ci-btn ci-btn-p flex-1" :disabled="creating" @click="createIntegration()">
                            <span x-text="creating ? 'Conectando…' : 'Probar y conectar'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- MODAL: vista previa antes de la primera sync --}}
    <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,.5)" @click.self="previewOpen=false" @keydown.escape.window="previewOpen=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-5 space-y-3 max-h-[85vh] overflow-y-auto">
            <h3 class="font-black text-gray-900">Vista previa del catálogo</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="ci-card p-3"><p class="text-[10px] text-gray-400 font-bold uppercase">Nuevos</p><p class="text-xl font-black text-emerald-600" x-text="previewData.new_count"></p></div>
                <div class="ci-card p-3"><p class="text-[10px] text-gray-400 font-bold uppercase">Ya vinculados</p><p class="text-xl font-black text-gray-700" x-text="previewData.existing_count"></p></div>
            </div>
            <div class="max-h-64 overflow-y-auto divide-y">
                <template x-for="p in previewData.sample" :key="p.external_id">
                    <div class="flex justify-between items-center py-2 text-xs">
                        <span class="font-semibold text-gray-800" x-text="p.name"></span>
                        <span :class="p.is_new ? 'text-emerald-600 font-bold' : 'text-gray-400'" x-text="p.is_new ? 'Nuevo' : 'Actualiza'"></span>
                    </div>
                </template>
            </div>
            <p class="text-[11px] text-gray-400" x-text="'Mostrando '+ (previewData.sample ? previewData.sample.length : 0) +' de '+ (previewData.total_count ?? '?') +' productos totales.'"></p>
            <div class="flex gap-2">
                <button class="ci-btn ci-btn-o flex-1" @click="previewOpen=false">Cerrar</button>
                <button class="ci-btn ci-btn-p flex-1" @click="confirmFirstSync()">Confirmar y sincronizar</button>
            </div>
        </div>
    </div>

    {{-- MODAL: historial --}}
    <div x-show="historyOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,.5)" @click.self="historyOpen=false" @keydown.escape.window="historyOpen=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-5 space-y-2 max-h-[85vh] overflow-y-auto">
            <h3 class="font-black text-gray-900">Historial de sincronizaciones</h3>
            <template x-for="r in historyRuns" :key="r.id">
                <div class="border-b border-gray-100 py-2 text-xs">
                    <div class="flex justify-between">
                        <span class="font-bold" :class="r.status==='completed' ? 'text-emerald-600' : (r.status==='failed' ? 'text-red-600' : 'text-amber-600')" x-text="r.status"></span>
                        <span class="text-gray-400" x-text="fmtDate(r.created_at)"></span>
                    </div>
                    <p class="text-gray-500 mt-0.5" x-text="'Creados: '+r.items_created+' · Actualizados: '+r.items_updated+' · Sin cambios: '+r.items_unchanged+' · Fallidos: '+r.items_failed"></p>
                </div>
            </template>
            <p x-show="!historyRuns.length" class="text-xs text-gray-400 text-center py-4">Sin ejecuciones todavía.</p>
            <button class="ci-btn ci-btn-o w-full" @click="historyOpen=false">Cerrar</button>
        </div>
    </div>
</div>

<script>
function catalogIntegrations() {
    return {
        integrations: @js($integrations),
        availableProviders: @js($availableProviders),
        newOpen: false, previewOpen: false, historyOpen: false,
        selectedProvider: null, schemaFields: [], form: { name: '', credentials: {} },
        creating: false, createError: '', busy: null, feedback: {},
        previewData: {}, previewIntegrationId: null, historyRuns: [],
        csrf: document.querySelector('meta[name=csrf-token]').content,
        init() {},
        statusLabel(s) { return { connected: 'Conectado', pending: 'Sin sincronizar', warning: 'Con observaciones', error: 'Error', disabled: 'Desactivado' }[s] || s; },
        fmtDate(iso) { return iso ? new Date(iso).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' }) : '—'; },
        openNew() { this.newOpen = true; this.selectedProvider = null; this.createError = ''; },
        async pickProvider(p) {
            this.selectedProvider = p;
            this.form = { name: '', credentials: {} };
            const r = await fetch(`{{ url('/bixoadmin/catalog-integrations/schema') }}/${p.key}`, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            this.schemaFields = d.fields;
            d.fields.forEach(f => { if (f.default) this.form.credentials[f.name] = f.default; });
        },
        async createIntegration() {
            this.creating = true; this.createError = '';
            try {
                const r = await fetch(@js(route('catalog-integrations.store')), {
                    method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ provider: this.selectedProvider.key, name: this.form.name || this.selectedProvider.name, credentials: this.form.credentials }),
                });
                const d = await r.json();
                if (!r.ok || !d.ok) throw new Error(d.message || 'No se pudo crear la conexión.');
                this.newOpen = false;
                await this.openPreview(d.integration_id);
                location.reload();
            } catch (e) { this.createError = e.message; }
            this.creating = false;
        },
        async testConnection(i) {
            this.busy = i.id;
            const r = await fetch(`{{ url('/bixoadmin/catalog-integrations') }}/${i.id}/test`, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' } });
            const d = await r.json();
            this.feedback = { ...this.feedback, [i.id]: d };
            this.busy = null;
        },
        async syncNow(i) {
            this.busy = i.id;
            const r = await fetch(`{{ url('/bixoadmin/catalog-integrations') }}/${i.id}/sync`, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' } });
            const d = await r.json();
            this.feedback = { ...this.feedback, [i.id]: d };
            this.busy = null;
            setTimeout(() => location.reload(), 1200);
        },
        async toggleActive(i) {
            await fetch(`{{ url('/bixoadmin/catalog-integrations') }}/${i.id}`, {
                method: 'PUT', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                body: JSON.stringify({ active: !i.active }),
            });
            location.reload();
        },
        async openPreview(integrationId) {
            this.previewIntegrationId = integrationId;
            const r = await fetch(`{{ url('/bixoadmin/catalog-integrations') }}/${integrationId}/preview`, { headers: { Accept: 'application/json' } });
            this.previewData = await r.json();
            this.previewOpen = true;
        },
        async confirmFirstSync() {
            this.previewOpen = false;
            await fetch(`{{ url('/bixoadmin/catalog-integrations') }}/${this.previewIntegrationId}/sync`, { method: 'POST', headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' } });
            setTimeout(() => location.reload(), 1200);
        },
        async openHistory(i) {
            const r = await fetch(`{{ url('/bixoadmin/catalog-integrations') }}/${i.id}/history`, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            this.historyRuns = d.runs;
            this.historyOpen = true;
        },
    };
}
</script>
</x-app-layout>

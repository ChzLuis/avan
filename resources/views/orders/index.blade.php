@php
    $ordersApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.pedidos')
        : route('orders');
    $isSales = ($portalLayout ?? 'panel') === 'comercial';
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Pedidos">

<style>
.ord-list-item { display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-bottom:1px solid #f3f4f6;cursor:pointer;transition:background .15s; }
.ord-list-item:hover { background:#f9fafb; }
.ord-list-item.active { background:#eef2ff; border-left:3px solid #4f46e5; }
.ord-avatar { width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0; }
.status-pill { display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px;white-space:nowrap; }
.s-pending    { background:#fef9c3;color:#854d0e; }
.s-process    { background:#dbeafe;color:#1e40af; }
.s-done       { background:#dcfce7;color:#166534; }
.s-cancelled  { background:#fee2e2;color:#991b1b; }
.s-btn { padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:2px solid transparent;cursor:pointer;transition:all .15s; }
.s-btn.active  { border-color:currentColor; }
.kpi-card { background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 18px; }
.detail-section { background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:12px; }
.detail-section-header { padding:10px 16px;background:#f9fafb;border-bottom:1px solid #f3f4f6;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280; }
.ch-tag { display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:600;padding:2px 7px;border-radius:99px; }
.ch-pos       { background:#ede9fe;color:#6d28d9; }
.ch-whatsapp  { background:#dcfce7;color:#166534; }
.ch-ecommerce { background:#dbeafe;color:#1e40af; }
.ch-default   { background:#f3f4f6;color:#6b7280; }
</style>

<div class="flex flex-col flex-1 overflow-hidden bg-gray-50" x-data="{
    orders: {{ Js::from($orders->map(fn($o) => [
        'id'             => $o->id,
        'client_name'    => $o->client_name,
        'client_phone'   => $o->client_phone,
        'status'         => $o->status,
        'total'          => (float)$o->total,
        'notes'          => $o->notes,
        'payment_method' => $o->payment_method ?? '',
        'sales_channel'  => $o->sales_channel ?? '',
        'created_at'     => $o->created_at->format('d/m/Y H:i'),
        'created_ts'     => $o->created_at->timestamp,
        'items_count'    => $o->items->count(),
        'items'          => $o->items->map(fn($i) => ['name'=>$i->name,'price'=>(float)$i->price,'quantity'=>(int)$i->quantity])->values(),
        'wa_number'      => $o->wa_number ?? '',
        'wa_status'      => $o->wa_status ?? '',
        'delivery_address'=> $o->delivery_address ?? '',
        'shipping_cost'  => (float)($o->shipping_cost ?? 0),
        'payment_proof'  => $o->payment_proof ? (str_starts_with($o->payment_proof,'http') ? $o->payment_proof : str_replace('http://','https://',asset('storage/'.$o->payment_proof))) : null,
    ])) }},
    paymentMethods:    {{ Js::from($paymentMethods) }},
    paymentConditions: {{ Js::from($paymentConditions) }},
    salesChannels:     {{ Js::from($salesChannels) }},

    search: '',
    filterStatus: '',
    selected: null,
    creating: false,
    saving: false,
    form: { client_name:'', client_phone:'', notes:'', status:'pending', payment_method:'', items:[{name:'',price:'',quantity:1}] },

    statuses: {
        pending:   { label:'Nuevo',          icon:'🟡', cls:'s-pending'  },
        process:   { label:'En proceso',     icon:'🔵', cls:'s-process'  },
        done:      { label:'Completado',     icon:'🟢', cls:'s-done'     },
        cancelled: { label:'Cancelado',      icon:'🔴', cls:'s-cancelled' },
    },

    get filtered() {
        return this.orders.filter(o => {
            const s = !this.search || o.client_name.toLowerCase().includes(this.search.toLowerCase()) || String(o.id).includes(this.search);
            const f = !this.filterStatus || o.status === this.filterStatus;
            return s && f;
        });
    },

    avatarColor(name) {
        const colors = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#14b8a6'];
        let h = 0; for (let c of (name||'?')) h = c.charCodeAt(0) + ((h<<5)-h);
        return colors[Math.abs(h) % colors.length];
    },

    timeAgo(ts) {
        const d = Math.floor(Date.now()/1000) - ts;
        if (d < 60)   return 'Ahora';
        if (d < 3600) return Math.floor(d/60)+'m';
        if (d < 86400)return Math.floor(d/3600)+'h';
        return Math.floor(d/86400)+'d';
    },

    chClass(ch) {
        if (!ch) return 'ch-default';
        if (ch === 'whatsapp') return 'ch-whatsapp';
        if (ch === 'pos') return 'ch-pos';
        if (ch === 'ecommerce' || ch === 'web') return 'ch-ecommerce';
        return 'ch-default';
    },

    select(o) {
        this.selected = {...o};
        this.form = { status: o.status, notes: o.notes||'', payment_method: o.payment_method||'' };
        this.creating = false;
        this.refreshSelected();
    },

    async refreshSelected() {
        if (!this.selected) return;
        try {
            const res = await fetch('{{ $ordersApiBase }}/' + this.selected.id, {
                headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            if (!res.ok) return;
            const data = await res.json();
            const o = data.order ?? data;
            const idx = this.orders.findIndex(x => x.id === o.id);
            if (idx > -1) this.orders[idx] = {...this.orders[idx], ...o};
            this.selected = {...this.selected, ...o};
        } catch(e) {}
    },

    openNew() {
        this.selected = null; this.creating = true;
        this.form = { client_name:'', client_phone:'', notes:'', status:'pending', payment_method:'', items:[{name:'',price:'',quantity:1}] };
    },

    addItem()    { this.form.items.push({name:'',price:'',quantity:1}); },
    removeItem(i){ this.form.items.splice(i,1); },
    get formTotal() { return this.form.items.reduce((s,i)=>s+(parseFloat(i.price)||0)*(parseInt(i.quantity)||1),0); },

    async save() {
        this.saving = true;
        const base   = '{{ $ordersApiBase }}';
        const url    = this.creating ? base : base+'/'+this.selected.id;
        const method = this.creating ? 'POST' : 'PUT';
        const body   = this.creating
            ? { ...this.form }
            : { status: this.form.status, notes: this.form.notes, payment_method: this.form.payment_method };
        const res  = await fetch(url, { method, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify(body) });
        const data = await res.json();
        if (this.creating) {
            this.orders.unshift({...data.order, items:data.order.items||[], items_count:(data.order.items||[]).length});
            this.selected = data.order; this.creating = false;
        } else {
            const idx = this.orders.findIndex(o=>o.id===data.order.id);
            if (idx>-1) this.orders[idx]={...this.orders[idx],...data.order};
            this.selected = {...this.selected,...data.order};
        }
        this.saving = false;
    },

    async quickStatus(status) {
        if (!this.selected) return;
        const res  = await fetch('{{ $ordersApiBase }}/'+this.selected.id, {
            method:'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body: JSON.stringify({ status, notes: this.form.notes })
        });
        const data = await res.json();
        const idx  = this.orders.findIndex(o=>o.id===this.selected.id);
        if (idx>-1) this.orders[idx].status = status;
        this.selected.status = status; this.form.status = status;
    },

    async del() {
        if (!confirm('¿Eliminar este pedido?')) return;
        const res = await fetch('{{ $ordersApiBase }}/'+this.selected.id, {
            method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
        });
        if (!res.ok) { alert('Error al eliminar ('+res.status+')'); return; }
        this.orders = this.orders.filter(o=>o.id!==this.selected.id);
        this.selected = null; this.creating = false;
    },

    waStatusLabel: {
        pending:          { label:'Pago pendiente',    color:'text-yellow-600 bg-yellow-50', icon:'⏳' },
        pago_recibido:    { label:'Pago recibido',     color:'text-blue-600 bg-blue-50',     icon:'💳' },
        pago_confirmado:  { label:'Pago confirmado',   color:'text-indigo-600 bg-indigo-50', icon:'✅' },
        preparando:       { label:'Preparando',        color:'text-orange-600 bg-orange-50', icon:'📦' },
        en_camino:        { label:'En camino',         color:'text-purple-600 bg-purple-50', icon:'🚚' },
        entregado:        { label:'Entregado',         color:'text-green-600 bg-green-50',   icon:'🎉' },
        entregado_espera: { label:'Esperando cliente', color:'text-teal-600 bg-teal-50',     icon:'📲' },
        problema:         { label:'Problema',          color:'text-red-600 bg-red-50',       icon:'❌' },
    },
    waActing: false,
    async waAction(action) {
        if (this.waActing) return;
        this.waActing = true;
        try {
            const res  = await fetch('{{ $ordersApiBase }}/'+this.selected.id+'/wa-action', {
                method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                body: JSON.stringify({ action })
            });
            const data = await res.json();
            if (data.ok) {
                const idx = this.orders.findIndex(o=>o.id===this.selected.id);
                if (idx>-1) this.orders[idx].wa_status = data.wa_status;
                this.selected.wa_status = data.wa_status;
                await this.refreshSelected();
            }
        } finally { this.waActing = false; }
    },
    waActionsFor(ws) {
        if (!ws) return [];
        if (ws==='pending'||ws==='pago_recibido') return [{ key:'confirmar_pago', label:'✅ Confirmar pago', cls:'bg-indigo-600 text-white' }];
        if (ws==='pago_confirmado'||ws==='preparando') return [{ key:'en_camino', label:'🚚 En camino', cls:'bg-indigo-600 text-white' }];
        if (ws==='en_camino') return [{ key:'entregado', label:'🎉 Entregado', cls:'bg-green-600 text-white' }];
        return [];
    },
}">

{{-- ══ Header ══════════════════════════════════════════════════════════════ --}}
<div class="flex-shrink-0 px-5 py-3 border-b border-gray-200 bg-white flex items-center justify-between">
    <h1 class="text-base font-bold text-gray-900">Pedidos</h1>
    <button @click="openNew()" class="btn-primary text-xs px-4 py-2 flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Nuevo pedido
    </button>
</div>

{{-- ══ BODY ══════════════════════════════════════════════════════════════════ --}}
<div class="flex flex-1 overflow-hidden">

{{-- ── LISTA ─────────────────────────────────────────────────────────────── --}}
<div class="flex flex-col bg-white border-r border-gray-200 flex-shrink-0" style="width:320px">

    {{-- Search + filtros --}}
    <div class="px-3 py-2.5 border-b border-gray-100 space-y-2 flex-shrink-0">
        <div class="relative">
            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
            <input x-model="search" type="text" placeholder="Buscar por nombre o #ID..." class="w-full pl-8 pr-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <div class="flex gap-1 overflow-x-auto pb-0.5">
            <button @click="filterStatus=''" :class="filterStatus==='' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="text-[11px] px-2.5 py-1 rounded-full font-medium whitespace-nowrap transition flex-shrink-0">Todos <span x-text="'('+orders.length+')'"></span></button>
            <button @click="filterStatus='pending'" :class="filterStatus==='pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="text-[11px] px-2.5 py-1 rounded-full font-medium whitespace-nowrap transition flex-shrink-0">🟡 Nuevos</button>
            <button @click="filterStatus='process'" :class="filterStatus==='process' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="text-[11px] px-2.5 py-1 rounded-full font-medium whitespace-nowrap transition flex-shrink-0">🔵 En proceso</button>
            <button @click="filterStatus='done'" :class="filterStatus==='done' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="text-[11px] px-2.5 py-1 rounded-full font-medium whitespace-nowrap transition flex-shrink-0">🟢 Completados</button>
        </div>
    </div>

    {{-- Items --}}
    <div class="overflow-y-auto flex-1">
        <div x-show="filtered.length===0" class="py-12 text-center text-gray-400 text-sm">Sin pedidos</div>
        <template x-for="o in filtered" :key="o.id">
            <div @click="select(o)" class="ord-list-item" :class="selected && selected.id===o.id ? 'active' : ''">
                {{-- Avatar --}}
                <div class="ord-avatar" :style="'background:'+avatarColor(o.client_name)+';color:#fff'" x-text="(o.client_name||'?')[0].toUpperCase()"></div>
                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1 mb-0.5">
                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="o.client_name"></p>
                        <p class="text-sm font-black text-gray-900 flex-shrink-0" x-text="'S/'+parseFloat(o.total).toFixed(0)"></p>
                    </div>
                    <div class="flex items-center justify-between gap-1">
                        <div class="flex items-center gap-1.5">
                            <span class="status-pill" :class="(statuses[o.status]||{}).cls" x-text="(statuses[o.status]||{}).label"></span>
                            <span x-show="o.sales_channel" class="ch-tag" :class="chClass(o.sales_channel)" x-text="o.sales_channel"></span>
                        </div>
                        <span class="text-[10px] text-gray-400 flex-shrink-0" x-text="timeAgo(o.created_ts)"></span>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-0.5" x-text="o.items_count+' ítem(s) · #'+o.id"></p>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- ── DETALLE ───────────────────────────────────────────────────────────── --}}
<div class="flex-1 overflow-y-auto bg-gray-50 p-5">

    {{-- Empty state --}}
    <div x-show="!selected && !creating" class="h-full flex items-center justify-center flex-col gap-3 text-gray-300">
        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <p class="text-sm text-gray-400">Selecciona un pedido</p>
    </div>

    {{-- FORMULARIO NUEVO --}}
    <template x-if="creating">
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Nuevo pedido</h2>
                <button @click="creating=false" class="text-gray-400 hover:text-gray-600 text-sm">✕ Cancelar</button>
            </div>

            <div class="detail-section">
                <div class="detail-section-header">Cliente</div>
                <div class="p-4 grid grid-cols-2 gap-3">
                    <div class="col-span-2">
                        <label class="label">Nombre *</label>
                        <input x-model="form.client_name" class="input" placeholder="Nombre del cliente">
                    </div>
                    <div>
                        <label class="label">Teléfono</label>
                        <input x-model="form.client_phone" class="input" placeholder="999 999 999">
                    </div>
                    <div x-show="paymentMethods.length > 0">
                        <label class="label">Método de pago</label>
                        <select x-model="form.payment_method" class="input text-sm">
                            <option value="">—</option>
                            <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                        </select>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-header">Productos / Servicios</div>
                <div class="p-4 space-y-2">
                    <template x-for="(item, i) in form.items" :key="i">
                        <div class="flex gap-2 items-center">
                            <input x-model="form.items[i].name" class="input text-sm flex-1" placeholder="Descripción">
                            <input type="number" x-model="form.items[i].price" class="input text-sm w-24" placeholder="Precio" step="0.01" min="0">
                            <input type="number" x-model="form.items[i].quantity" class="input text-sm w-16" placeholder="Cant." min="1">
                            <button @click="removeItem(i)" class="text-red-400 hover:text-red-600 flex-shrink-0">✕</button>
                        </div>
                    </template>
                    <button @click="addItem()" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium mt-1">+ Agregar línea</button>
                    <div class="flex justify-end pt-2 border-t border-gray-100">
                        <p class="text-sm font-bold text-gray-900">Total: S/ <span x-text="formTotal.toFixed(2)"></span></p>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="detail-section-header">Notas</div>
                <div class="p-4">
                    <textarea x-model="form.notes" class="input resize-none text-sm" rows="2" placeholder="Instrucciones, referencias, etc."></textarea>
                </div>
            </div>

            <button @click="save()" :disabled="saving" class="btn-primary w-full py-3 text-sm font-bold"
                    x-text="saving ? 'Guardando...' : 'Crear pedido'"></button>
        </div>
    </template>

    {{-- DETALLE PEDIDO --}}
    <template x-if="selected && !creating">
        <div class="max-w-2xl mx-auto space-y-4">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-mono text-gray-400" x-text="'#'+selected.id"></span>
                        <span x-show="selected.sales_channel" class="ch-tag" :class="chClass(selected.sales_channel)" x-text="selected.sales_channel"></span>
                    </div>
                    <h2 class="text-xl font-black text-gray-900" x-text="selected.client_name"></h2>
                    <p class="text-xs text-gray-400 mt-0.5" x-text="selected.created_at"></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button @click="del()" class="text-xs text-red-400 hover:text-red-600 border border-red-200 hover:border-red-400 rounded-lg px-3 py-1.5 transition">Eliminar</button>
                </div>
            </div>

            {{-- Cambio rápido de estado --}}
            <div class="detail-section">
                <div class="detail-section-header">Estado del pedido</div>
                <div class="p-3 flex gap-2 flex-wrap">
                    <template x-for="(st, key) in statuses" :key="key">
                        <button @click="quickStatus(key)"
                                :class="selected.status===key ? 'ring-2 ring-offset-1 ring-indigo-500 '+st.cls : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                                class="s-btn text-xs transition"
                                x-text="st.icon+' '+st.label">
                        </button>
                    </template>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="detail-section">
                <div class="detail-section-header">Cliente</div>
                <div class="p-4 flex items-center gap-3">
                    <div class="ord-avatar w-10 h-10 text-base" :style="'background:'+avatarColor(selected.client_name)+';color:#fff'" x-text="(selected.client_name||'?')[0].toUpperCase()"></div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900" x-text="selected.client_name"></p>
                        <template x-if="selected.client_phone">
                            <a :href="'https://wa.me/51'+selected.client_phone.replace(/\D/g,'')" target="_blank"
                               class="text-xs text-green-600 hover:underline flex items-center gap-1 mt-0.5">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="w-3 h-3"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                <span x-text="selected.client_phone"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Productos --}}
            <div class="detail-section">
                <div class="detail-section-header">Productos</div>
                <template x-if="selected.items && selected.items.length > 0">
                    <div>
                        <template x-for="(it, i) in selected.items" :key="i">
                            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-50 last:border-0">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-gray-500" x-text="it.quantity+'×'"></span>
                                </div>
                                <p class="flex-1 text-sm text-gray-800" x-text="it.name"></p>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-semibold text-gray-900" x-text="'S/ '+(it.price*it.quantity).toFixed(2)"></p>
                                    <p class="text-[10px] text-gray-400" x-text="'S/ '+it.price.toFixed(2)+' c/u'"></p>
                                </div>
                            </div>
                        </template>
                        <div class="flex justify-between items-center px-4 py-3 bg-gray-50">
                            <span class="text-xs text-gray-500" x-text="(selected.items||[]).reduce((s,i)=>s+i.quantity,0)+' unidades'"></span>
                            <span class="text-lg font-black text-gray-900" x-text="'S/ '+parseFloat(selected.total).toFixed(2)"></span>
                        </div>
                    </div>
                </template>
                <template x-if="!selected.items || selected.items.length === 0">
                    <div class="px-4 py-3 flex justify-between items-center">
                        <span class="text-sm text-gray-500" x-text="selected.items_count+' ítem(s)'"></span>
                        <span class="text-lg font-black text-gray-900" x-text="'S/ '+parseFloat(selected.total).toFixed(2)"></span>
                    </div>
                </template>
            </div>

            {{-- Pago + Notas en grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="detail-section">
                    <div class="detail-section-header">Pago</div>
                    <div class="p-4 space-y-2">
                        <div>
                            <label class="label text-[10px]">Método</label>
                            <select x-model="form.payment_method" class="input text-sm mt-0.5">
                                <option value="">—</option>
                                <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                            </select>
                        </div>
                        <div x-show="selected.payment_proof" class="mt-1">
                            <label class="label text-[10px]">Comprobante</label>
                            <a :href="selected.payment_proof" target="_blank" class="block mt-1">
                                <img :src="selected.payment_proof" class="rounded-lg border border-gray-200 max-h-32 w-full object-contain hover:opacity-90 transition">
                            </a>
                        </div>
                        <div x-show="!selected.payment_proof" class="text-[10px] text-gray-400 bg-gray-50 rounded-lg p-2 text-center">Sin comprobante aún</div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-header">Notas internas</div>
                    <div class="p-4">
                        <textarea x-model="form.notes" class="input resize-none text-sm w-full" rows="4" placeholder="Observaciones, instrucciones..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Tracking WhatsApp --}}
            <template x-if="selected.sales_channel === 'whatsapp'">
                <div class="detail-section border-green-200" style="border-color:#bbf7d0">
                    <div class="detail-section-header" style="background:#f0fdf4;color:#166534">WhatsApp — Seguimiento</div>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-600">+51 <span x-text="selected.wa_number"></span></p>
                            <span class="status-pill" :class="(waStatusLabel[selected.wa_status]||{}).color"
                                  x-text="(waStatusLabel[selected.wa_status]||{}).icon+' '+((waStatusLabel[selected.wa_status]||{}).label||'—')"></span>
                        </div>
                        {{-- Steps --}}
                        <div class="flex items-center gap-1 overflow-x-auto py-1">
                            <template x-for="step in [
                                {key:'pending',label:'Recibido',icon:'🛒'},
                                {key:'pago_recibido',label:'Pago enviado',icon:'💳'},
                                {key:'pago_confirmado',label:'Confirmado',icon:'✅'},
                                {key:'en_camino',label:'En camino',icon:'🚚'},
                                {key:'entregado',label:'Entregado',icon:'🎉'},
                            ]" :key="step.key">
                                <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm border-2 transition"
                                         :class="['pending','pago_recibido','pago_confirmado','en_camino','entregado'].indexOf(selected.wa_status) >= ['pending','pago_recibido','pago_confirmado','en_camino','entregado'].indexOf(step.key) ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-200 text-gray-300'"
                                         x-text="step.icon"></div>
                                    <p class="text-[9px] text-gray-500 whitespace-nowrap" x-text="step.label"></p>
                                </div>
                                <div class="w-6 h-0.5 bg-gray-200 flex-shrink-0 mb-4" x-show="step.key !== 'entregado'"></div>
                            </template>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <template x-for="btn in waActionsFor(selected.wa_status)" :key="btn.key">
                                <button @click="waAction(btn.key)" :disabled="waActing" :class="btn.cls"
                                        class="text-xs px-4 py-2 rounded-lg font-semibold disabled:opacity-50 transition"
                                        x-text="waActing ? 'Enviando...' : btn.label"></button>
                            </template>
                            <button @click="refreshSelected()" class="text-xs px-3 py-2 border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-50 transition">↻ Actualizar</button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Guardar --}}
            <div class="flex gap-3">
                <button @click="save()" :disabled="saving" class="btn-primary px-8 py-2.5 text-sm font-bold"
                        x-text="saving ? 'Guardando...' : 'Guardar cambios'"></button>
            </div>

        </div>
    </template>

</div>{{-- /detalle --}}
</div>{{-- /body --}}
</div>
</x-portal-layout>

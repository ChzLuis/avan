@php
    $ordersApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.pedidos')
        : route('orders');
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Pedidos">
<div class="flex flex-1 overflow-hidden" x-data="{
    orders: {{ Js::from($orders->map(fn($o) => [
        'id'=>$o->id,'client_name'=>$o->client_name,'client_phone'=>$o->client_phone,
        'status'=>$o->status,'total'=>(float)$o->total,'notes'=>$o->notes,
        'payment_method'=>$o->payment_method??'','payment_condition'=>$o->payment_condition??'','sales_channel'=>$o->sales_channel??'',
        'created_at'=>$o->created_at->format('d/m/Y H:i'),
        'items_count'=>$o->items->count(),
        'wa_number'=>$o->wa_number??'', 'wa_status'=>$o->wa_status??'',
        'delivery_address'=>$o->delivery_address??'', 'shipping_cost'=>(float)($o->shipping_cost??0),
        'payment_proof'=>$o->payment_proof ? str_replace('http://','https://',asset('storage/'.$o->payment_proof)) : null,
    ])) }},
    paymentMethods:    {{ Illuminate\Support\Js::from($paymentMethods) }},
    paymentConditions: {{ Illuminate\Support\Js::from($paymentConditions) }},
    salesChannels:     {{ Illuminate\Support\Js::from($salesChannels) }},
    search: '',
    filterStatus: '',
    filterCanal: new URLSearchParams(window.location.search).get('canal') || '',
    panel: 'list',

    selected: null,
    creating: false,
    tab: 'detail',
    form: { client_name:'', client_phone:'', notes:'', status:'pending', items:[{name:'',price:'',quantity:1}] },
    saving: false,

    statuses: { pending:'Nuevo', process:'En proceso', done:'Completado', cancelled:'Cancelado' },
    statusColors: { pending:'badge-pending', process:'badge-process', done:'badge-active', cancelled:'badge-inactive' },

    get filtered() {
        return this.orders.filter(o => {
            const s = !this.search || o.client_name.toLowerCase().includes(this.search.toLowerCase());
            const f = !this.filterStatus || o.status === this.filterStatus;
            const c = !this.filterCanal || o.sales_channel === this.filterCanal;
            return s && f && c;
        });
    },

    select(o) { this.selected={...o}; this.form={...o}; this.creating=false; this.tab='detail'; this.panel='detail'; this.refreshSelected(); },

    async refreshSelected() {
        if (!this.selected) return;
        try {
            const base = '{{ $ordersApiBase }}';
            const res  = await fetch(base + '/' + this.selected.id, {
                headers: { 'Accept':'application/json', 'X-CSRF-TOKEN': window._csrfToken }
            });
            if (!res.ok) return;
            const data = await res.json();
            const o = data.order ?? data;
            const idx = this.orders.findIndex(x => x.id === o.id);
            if (idx > -1) { this.orders[idx] = {...this.orders[idx], ...o}; }
            this.selected = {...this.selected, ...o};
        } catch(e) {}
    },

    openNew() {
        this.selected=null; this.creating=true; this.tab='detail'; if(window.innerWidth<768)this.panel='detail';
        this.form={client_name:'',client_phone:'',notes:'',status:'pending',items:[{name:'',price:'',quantity:1}]};
    },

    addItem()   { this.form.items.push({name:'',price:'',quantity:1}); },
    removeItem(i){ this.form.items.splice(i,1); },

    get total() { return this.form.items.reduce((s,i)=>s+(parseFloat(i.price)||0)*(parseInt(i.quantity)||0),0); },

    async save() {
        this.saving=true;
        const base = '{{ $ordersApiBase }}';
        const url  = this.creating ? base : base + '/' + this.selected.id;
        const method = this.creating ? 'POST' : 'PUT';
        const body = this.creating ? {...this.form} : {status:this.form.status,notes:this.form.notes};
        const res = await fetch(url, {
            method,
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (this.creating) {
            this.orders.unshift(data.order);
        } else {
            const idx = this.orders.findIndex(o=>o.id===data.order.id);
            if(idx>-1) this.orders[idx]={...this.orders[idx],...data.order};
        }
        this.selected=data.order; this.creating=false;
        this.saving=false;
    },

    async del() {
        if(!confirm('Eliminar este pedido?')) return;
        const base = '{{ $ordersApiBase }}';
        await fetch(base + '/' + this.selected.id, {
            method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
        });
        this.orders=this.orders.filter(o=>o.id!==this.selected.id);
        this.selected=null;
    },

    waStatusLabel: {
        pending:          { label:'Pago pendiente',   color:'text-yellow-600 bg-yellow-50',  icon:'⏳' },
        pago_recibido:    { label:'Pago recibido',    color:'text-blue-600   bg-blue-50',    icon:'💳' },
        pago_confirmado:  { label:'Pago confirmado',  color:'text-indigo-600 bg-indigo-50',  icon:'✅' },
        preparando:       { label:'Preparando',       color:'text-orange-600 bg-orange-50',  icon:'📦' },
        en_camino:        { label:'En camino',        color:'text-purple-600 bg-purple-50',  icon:'🚚' },
        entregado:        { label:'Entregado',        color:'text-green-600  bg-green-50',   icon:'🎉' },
        entregado_espera: { label:'Esperando cliente',color:'text-teal-600   bg-teal-50',    icon:'📲' },
        problema:         { label:'Problema reportado',color:'text-red-600   bg-red-50',     icon:'❌' },
    },

    waActionsFor(ws) {
        if (!ws) return [];
        if (ws === 'pending' || ws === 'pago_recibido')
            return [{ key:'confirmar_pago', label:'✅ Confirmar pago', cls:'btn-primary' }];
        if (ws === 'pago_confirmado' || ws === 'preparando')
            return [{ key:'en_camino', label:'🚚 Marcar en camino', cls:'btn-primary' }];
        if (ws === 'en_camino')
            return [{ key:'entregado', label:'📦 Marcar entregado', cls:'bg-green-600 text-white rounded-lg px-4 py-2 text-sm font-semibold hover:bg-green-700' }];
        return [];
    },

    waActing: false,
    async waAction(action) {
        if (this.waActing) return;
        this.waActing = true;
        try {
            const base = '{{ $ordersApiBase }}';
            const res  = await fetch(base + '/' + this.selected.id + '/wa-action', {
                method:  'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' },
                body:    JSON.stringify({ action }),
            });
            const data = await res.json();
            if (data.ok) {
                const idx = this.orders.findIndex(o => o.id === this.selected.id);
                if (idx > -1) this.orders[idx].wa_status = data.wa_status;
                this.selected.wa_status = data.wa_status;
                await this.refreshSelected();
            }
        } finally { this.waActing = false; }
    }
}">

{{-- PANEL 2: LISTA --}}
<div class="flex-shrink-0 border-r border-gray-200 bg-white flex-col" :class="panel==='list' ? 'flex w-full md:w-[340px]' : 'hidden md:flex md:w-[340px]'">
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0">
        <div class="relative flex-1 mr-2">
            <input type="text" x-model="search" placeholder="Buscar cliente..." class="input pl-8 py-1.5 text-xs">
            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
        </div>
        <button @click="openNew()" class="btn-primary text-xs px-3 py-1.5">+ Nuevo</button>
    </div>

    <div class="px-3 py-2 border-b border-gray-100 flex gap-1 overflow-x-auto flex-shrink-0">
        <button @click="filterStatus=''; filterCanal=''" :class="filterStatus==='' && filterCanal==='' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">Todos</button>
        <button @click="filterStatus='pending'; filterCanal=''" :class="filterStatus==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">Nuevos</button>
        <button @click="filterStatus='process'; filterCanal=''" :class="filterStatus==='process' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">En proceso</button>
        <button @click="filterStatus='done'; filterCanal=''" :class="filterStatus==='done' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">Completados</button>
        <button @click="filterStatus=''; filterCanal='whatsapp'"
                :class="filterCanal==='whatsapp' ? 'text-white' : 'bg-gray-100 text-gray-500'"
                :style="filterCanal==='whatsapp' ? 'background:#25D366' : ''"
                class="text-xs px-2.5 py-1 rounded-full flex items-center gap-1">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
            </svg>
            WhatsApp
        </button>
    </div>

    <div class="overflow-y-auto flex-1">
        <template x-for="o in filtered" :key="o.id">
            <div @click="select(o)" class="list-item" :class="selected && selected.id===o.id ? 'selected' : ''">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="o.client_name"></p>
                        <span x-show="o.sales_channel==='whatsapp'"
                              class="flex-shrink-0 w-3.5 h-3.5 rounded-full flex items-center justify-center"
                              style="background:#25D366" title="Pedido WhatsApp">
                            <svg viewBox="0 0 24 24" fill="white" class="w-2.5 h-2.5"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                        </span>
                    </div>
                    <p class="text-xs text-gray-500" x-text="'S/ '+parseFloat(o.total).toFixed(2) + ' · ' + o.created_at"></p>
                    <p x-show="o.wa_status" class="text-[10px] font-medium mt-0.5"
                       :class="(waStatusLabel[o.wa_status]||{}).color||'text-gray-500'"
                       x-text="(waStatusLabel[o.wa_status]||{}).icon+' '+(waStatusLabel[o.wa_status]||{}).label"></p>
                </div>
                <span :class="statusColors[o.status]" x-text="statuses[o.status]"></span>
            </div>
        </template>
        <div x-show="filtered.length===0" class="py-10 text-center text-sm text-gray-400">Sin pedidos</div>
    </div>
</div>

{{-- PANEL 3: DETALLE --}}
<div class="flex-col overflow-hidden bg-white" :class="panel==='detail' ? 'flex flex-1' : 'hidden md:flex md:flex-1'">
    <button @click="panel='list'" type="button" class="md:hidden flex items-center gap-1 px-4 py-3 text-sm text-gray-500 border-b border-gray-100 w-full hover:bg-gray-50 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Volver a la lista
    </button>
    <div x-show="!selected && !creating" class="flex-1 flex items-center justify-center flex-col gap-2 text-gray-300">
        <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-sm text-gray-400">Selecciona un pedido o crea uno nuevo</p>
    </div>

    <template x-if="selected || creating">
        <div class="flex flex-col h-full">
            <div class="flex border-b border-gray-200 px-4 bg-white flex-shrink-0">
                <button @click="tab='detail'" :class="tab==='detail' ? 'detail-tab active' : 'detail-tab'">Detalle</button>
                <button @click="tab='items'" :class="tab==='items' ? 'detail-tab active' : 'detail-tab'" x-show="creating">Items</button>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                <div x-show="tab==='detail'" class="space-y-4">
                    <h3 class="font-semibold text-gray-800" x-text="creating ? 'Nuevo pedido' : '#' + selected.id + ' — ' + selected.client_name"></h3>

                    <template x-if="creating">
                        <div class="space-y-4">
                            <div>
                                <label class="label">Cliente *</label>
                                <input type="text" x-model="form.client_name" class="input" placeholder="Nombre del cliente">
                            </div>
                            <div>
                                <label class="label">Teléfono</label>
                                <input type="text" x-model="form.client_phone" class="input" placeholder="999 999 999">
                            </div>
                            <div>
                                <label class="label">Notas</label>
                                <textarea x-model="form.notes" class="input resize-none" rows="2"></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2" x-show="paymentMethods.length || paymentConditions.length || salesChannels.length">
                                <div x-show="paymentMethods.length > 0">
                                    <label class="label">Método de pago</label>
                                    <select x-model="form.payment_method" class="input text-sm">
                                        <option value="">—</option>
                                        <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                                    </select>
                                </div>
                                <div x-show="paymentConditions.length > 0">
                                    <label class="label">Condición de pago</label>
                                    <select x-model="form.payment_condition" class="input text-sm">
                                        <option value="">—</option>
                                        <template x-for="c in paymentConditions" :key="c"><option :value="c" x-text="c"></option></template>
                                    </select>
                                </div>
                                <div x-show="salesChannels.length > 0">
                                    <label class="label">Canal de venta</label>
                                    <select x-model="form.sales_channel" class="input text-sm">
                                        <option value="">—</option>
                                        <template x-for="ch in salesChannels" :key="ch"><option :value="ch" x-text="ch"></option></template>
                                    </select>
                                </div>
                            </div>
                            <div class="border border-gray-200 rounded-lg p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Productos / Servicios</p>
                                    <button @click="addItem()" class="text-xs text-indigo-600 hover:text-indigo-800">+ Agregar</button>
                                </div>
                                <template x-for="(item, i) in form.items" :key="i">
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <input x-model="form.items[i].name" class="input text-xs w-full sm:flex-1" placeholder="Descripción">
                                        <input type="number" x-model="form.items[i].price" class="input text-xs w-28 sm:w-20" placeholder="Precio" step="0.01">
                                        <input type="number" x-model="form.items[i].quantity" class="input text-xs w-20 sm:w-16" placeholder="Cant." min="1">
                                        <button @click="removeItem(i)" class="text-red-400 hover:text-red-600 text-xs flex-shrink-0">✕</button>
                                    </div>
                                </template>
                                <div class="flex justify-end pt-1">
                                    <p class="text-sm font-semibold text-gray-800">Total: S/ <span x-text="total.toFixed(2)"></span></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!creating && selected">
                        <div class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Cliente</p>
                                    <p class="text-sm font-medium text-gray-800" x-text="selected.client_name"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Total</p>
                                    <p class="text-sm font-medium text-gray-800" x-text="'S/ '+parseFloat(selected.total).toFixed(2)"></p>
                                </div>
                            </div>

                            {{-- ── TRACKING WHATSAPP (estilo Temu) ── --}}
                            <template x-if="selected.sales_channel === 'whatsapp'">
                                <div class="rounded-xl border border-green-200 bg-green-50/50 overflow-hidden">
                                    {{-- Header --}}
                                    <div class="flex items-center gap-2 px-4 py-3 border-b border-green-100" style="background:#dcfce7">
                                        <svg viewBox="0 0 24 24" fill="#16a34a" class="w-4 h-4 flex-shrink-0"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-green-800">Pedido WhatsApp</p>
                                            <p class="text-[10px] text-green-700" x-text="'+51 ' + selected.wa_number"></p>
                                        </div>
                                        <span class="text-xs font-bold px-2 py-1 rounded-full"
                                              :class="(waStatusLabel[selected.wa_status]||{}).color||'bg-gray-100 text-gray-500'"
                                              x-text="(waStatusLabel[selected.wa_status]||{}).icon + ' ' + ((waStatusLabel[selected.wa_status]||{}).label||'—')">
                                        </span>
                                    </div>

                                    {{-- Timeline --}}
                                    <div class="px-4 py-3">
                                        <div class="relative">
                                            {{-- Línea vertical --}}
                                            <div class="absolute left-[13px] top-1 bottom-1 w-0.5 bg-gray-200"></div>

                                            <template x-for="(step, i) in [
                                                { key:'pending',         label:'Pedido recibido',    icon:'🛒' },
                                                { key:'pago_recibido',   label:'Pago enviado',       icon:'💳' },
                                                { key:'pago_confirmado', label:'Pago confirmado',    icon:'✅' },
                                                { key:'en_camino',       label:'En camino',          icon:'🚚' },
                                                { key:'entregado',       label:'Entregado',          icon:'🎉' },
                                            ]" :key="step.key">
                                                <div class="flex items-center gap-3 mb-2 relative">
                                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-sm flex-shrink-0 z-10 border-2 transition-all"
                                                         :class="[
                                                             'pending','pago_recibido','pago_confirmado','en_camino','entregado','entregado_espera','problema'
                                                         ].indexOf(selected.wa_status) >= [
                                                             'pending','pago_recibido','pago_confirmado','en_camino','entregado','entregado','problema'
                                                         ].indexOf(step.key)
                                                         ? 'bg-green-500 border-green-500 text-white shadow-md'
                                                         : 'bg-white border-gray-300 text-gray-300'"
                                                         x-text="step.icon">
                                                    </div>
                                                    <div>
                                                        <p class="text-xs font-semibold"
                                                           :class="[
                                                               'pending','pago_recibido','pago_confirmado','en_camino','entregado','entregado_espera','problema'
                                                           ].indexOf(selected.wa_status) >= [
                                                               'pending','pago_recibido','pago_confirmado','en_camino','entregado','entregado','problema'
                                                           ].indexOf(step.key)
                                                           ? 'text-gray-800' : 'text-gray-400'"
                                                           x-text="step.label"></p>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Problema especial --}}
                                            <div x-show="selected.wa_status === 'problema'"
                                                 class="flex items-center gap-3 mt-1 bg-red-50 rounded-lg px-3 py-2 border border-red-200">
                                                <span class="text-base">⚠️</span>
                                                <p class="text-xs font-semibold text-red-700">Cliente reportó un problema</p>
                                            </div>
                                        </div>

                                        {{-- Delivery info --}}
                                        <div x-show="selected.delivery_address || selected.shipping_cost > 0"
                                             class="mt-3 pt-3 border-t border-green-100 space-y-1">
                                            <p x-show="selected.delivery_address" class="text-xs text-gray-600">
                                                📍 <span x-text="selected.delivery_address"></span>
                                            </p>
                                            <p x-show="selected.shipping_cost > 0" class="text-xs text-gray-600">
                                                🚚 Delivery: <strong x-text="'S/ '+parseFloat(selected.shipping_cost).toFixed(2)"></strong>
                                            </p>
                                        </div>

                                        {{-- Comprobante de pago --}}
                                        <div class="mt-3 pt-3 border-t border-green-100">
                                            <div class="flex items-center justify-between mb-2">
                                                <p class="text-xs font-semibold text-gray-600">💳 Comprobante de pago</p>
                                                <button @click="refreshSelected()" class="text-xs text-indigo-500 hover:text-indigo-700 flex items-center gap-0.5">↻ Actualizar</button>
                                            </div>
                                            <template x-if="selected.payment_proof">
                                                <div>
                                                    <a :href="selected.payment_proof" target="_blank" class="block">
                                                        <img :src="selected.payment_proof" alt="Comprobante"
                                                             class="rounded-lg border border-gray-200 max-h-56 object-contain w-full cursor-pointer hover:opacity-90 transition">
                                                    </a>
                                                    <a :href="selected.payment_proof" target="_blank"
                                                       class="mt-1.5 inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline">
                                                        🔍 Ver imagen completa
                                                    </a>
                                                </div>
                                            </template>
                                            <template x-if="!selected.payment_proof">
                                                <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-3 border border-dashed border-gray-200">
                                                    <span class="text-lg">📎</span>
                                                    <p class="text-xs text-gray-400">El cliente aún no ha enviado comprobante de pago</p>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Botones de acción --}}
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <template x-for="btn in waActionsFor(selected.wa_status)" :key="btn.key">
                                                <button @click="waAction(btn.key)"
                                                        :disabled="waActing"
                                                        :class="btn.cls"
                                                        class="text-xs px-3 py-2 rounded-lg font-semibold disabled:opacity-50 transition"
                                                        x-text="waActing ? 'Enviando...' : btn.label">
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            {{-- ── FIN TRACKING ── --}}

                            <div>
                                <label class="label">Estado</label>
                                <select x-model="form.status" class="input">
                                    <option value="pending">Nuevo</option>
                                    <option value="process">En proceso</option>
                                    <option value="done">Completado</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Notas</label>
                                <textarea x-model="form.notes" class="input resize-none" rows="3"></textarea>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="border-t border-gray-200 px-5 py-3 flex gap-3 bg-white flex-shrink-0">
                <button @click="save()" :disabled="saving" class="btn-primary flex-1"
                        x-text="saving ? 'Guardando...' : 'Guardar'"></button>
                <button x-show="!creating" @click="del()" class="btn-danger px-4">Eliminar</button>
            </div>
        </div>
    </template>
</div>

</div>
</x-portal-layout>

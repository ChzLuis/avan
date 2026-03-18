<x-app-layout>
<x-slot name="slot">
<div class="flex flex-1 overflow-hidden" x-data="{
    orders: {{ Js::from($orders->map(fn($o) => [
        'id'=>$o->id,'client_name'=>$o->client_name,'client_phone'=>$o->client_phone,
        'status'=>$o->status,'total'=>(float)$o->total,'notes'=>$o->notes,
        'payment_method'=>$o->payment_method??'','payment_condition'=>$o->payment_condition??'','sales_channel'=>$o->sales_channel??'',
        'created_at'=>$o->created_at->format('d/m/Y H:i'),
        'items_count'=>$o->items->count()
    ])) }},
    paymentMethods:    {{ Illuminate\Support\Js::from($paymentMethods) }},
    paymentConditions: {{ Illuminate\Support\Js::from($paymentConditions) }},
    salesChannels:     {{ Illuminate\Support\Js::from($salesChannels) }},
    search: '',
    filterStatus: '',
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
            return s && f;
        });
    },

    select(o) { this.selected={...o}; this.form={...o}; this.creating=false; this.tab='detail'; if(window.innerWidth<768)this.panel='detail'; },

    openNew() {
        this.selected=null; this.creating=true; this.tab='detail'; if(window.innerWidth<768)this.panel='detail';
        this.form={client_name:'',client_phone:'',notes:'',status:'pending',items:[{name:'',price:'',quantity:1}]};
    },

    addItem()   { this.form.items.push({name:'',price:'',quantity:1}); },
    removeItem(i){ this.form.items.splice(i,1); },

    get total() { return this.form.items.reduce((s,i)=>s+(parseFloat(i.price)||0)*(parseInt(i.quantity)||0),0); },

    async save() {
        this.saving=true;
        const base = window.location.pathname.replace(/\/[0-9]+\/orders.*/,'') + '/' + {{ $project->id }} + '/orders';
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
        const base = window.location.pathname.replace(/\/[0-9]+\/orders.*/,'') + '/' + {{ $project->id }} + '/orders';
        await fetch(base + '/' + this.selected.id, {
            method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
        });
        this.orders=this.orders.filter(o=>o.id!==this.selected.id);
        this.selected=null;
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
        <button @click="filterStatus=''" :class="filterStatus==='' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">Todos</button>
        <button @click="filterStatus='pending'" :class="filterStatus==='pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">Nuevos</button>
        <button @click="filterStatus='process'" :class="filterStatus==='process' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">En proceso</button>
        <button @click="filterStatus='done'" :class="filterStatus==='done' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                class="text-xs px-2.5 py-1 rounded-full">Completados</button>
    </div>

    <div class="overflow-y-auto flex-1">
        <template x-for="o in filtered" :key="o.id">
            <div @click="select(o)" class="list-item" :class="selected && selected.id===o.id ? 'selected' : ''">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="o.client_name"></p>
                    <p class="text-xs text-gray-500" x-text="'S/ '+parseFloat(o.total).toFixed(2) + ' · ' + o.created_at"></p>
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
                            <div class="grid grid-cols-3 gap-2" x-show="paymentMethods.length || paymentConditions.length || salesChannels.length">
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
                                    <div class="grid grid-cols-6 gap-2 items-center">
                                        <input x-model="form.items[i].name" class="input col-span-3 text-xs" placeholder="Descripción">
                                        <input type="number" x-model="form.items[i].price" class="input col-span-1 text-xs" placeholder="Precio" step="0.01">
                                        <input type="number" x-model="form.items[i].quantity" class="input col-span-1 text-xs" placeholder="Cant." min="1">
                                        <button @click="removeItem(i)" class="text-red-400 hover:text-red-600 text-xs">✕</button>
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
</x-slot>
</x-app-layout>

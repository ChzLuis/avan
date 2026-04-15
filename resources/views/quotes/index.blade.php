@php
    $quotesApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.cotizaciones')
        : route('quotes');
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Cotizaciones">
<div class="flex flex-1 overflow-hidden" x-data="{
    quotes: {{ Js::from($quotes->map(fn($q) => [
        'id'=>$q->id,'client_name'=>$q->client_name,'client_phone'=>$q->client_phone ?? '',
        'status'=>$q->status,'total'=>(float)$q->total,'notes'=>$q->notes ?? '',
        'valid_until'=>$q->valid_until?->format('d/m/Y') ?? '',
        'payment_method'=>$q->payment_method??'','payment_condition'=>$q->payment_condition??'',
        'created_at'=>$q->created_at->format('d/m/Y'),
        'token'=>$q->token ?? '','sent_at'=>$q->sent_at?->format('d/m/Y H:i') ?? '',
    ])) }},
    paymentMethods:    {{ Illuminate\Support\Js::from($paymentMethods) }},
    paymentConditions: {{ Illuminate\Support\Js::from($paymentConditions) }},
    search: '',
    filterStatus: '',
    panel: 'list',

    selected: null,
    creating: false,
    tab: 'detail',
    form: { client_name:'', client_phone:'', notes:'', valid_until:'', status:'draft', items:[{description:'',price:'',quantity:1}] },
    saving: false,

    statuses: { draft:'Borrador', sent:'Enviada', accepted:'Aceptada', rejected:'Rechazada' },
    statusColors: { draft:'badge-inactive', sent:'badge-process', accepted:'badge-active', rejected:'badge-cancelled' },

    get filtered() {
        return this.quotes.filter(q => {
            const s = !this.search || q.client_name.toLowerCase().includes(this.search.toLowerCase());
            const f = !this.filterStatus || q.status === this.filterStatus;
            return s && f;
        });
    },

    select(q) { this.selected={...q}; this.form={...q}; this.creating=false; this.tab='detail'; if(window.innerWidth<768)this.panel='detail'; },

    openNew() {
        this.selected=null; this.creating=true; this.tab='detail'; if(window.innerWidth<768)this.panel='detail';
        this.form={client_name:'',client_phone:'',notes:'',valid_until:'',status:'draft',items:[{description:'',price:'',quantity:1}]};
    },

    addItem()    { this.form.items.push({description:'',price:'',quantity:1}); },
    removeItem(i){ this.form.items.splice(i,1); },
    get total()  { return this.form.items.reduce((s,i)=>s+(parseFloat(i.price)||0)*(parseInt(i.quantity)||0),0); },

    async save() {
        this.saving=true;
        const base = '{{ $quotesApiBase }}';
        const url  = this.creating ? base : base + '/' + this.selected.id;
        const body = this.creating ? {...this.form} : {status:this.form.status,notes:this.form.notes};
        const res  = await fetch(url, {
            method: this.creating ? 'POST' : 'PUT',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (this.creating) { this.quotes.unshift(data.quote); }
        else {
            const idx = this.quotes.findIndex(q=>q.id===data.quote.id);
            if(idx>-1) this.quotes[idx]={...this.quotes[idx],...data.quote};
        }
        this.selected=data.quote; this.creating=false; this.saving=false;
    },

    async del() {
        if(!confirm('Eliminar esta cotización?')) return;
        const base = '{{ $quotesApiBase }}';
        await fetch(base+'/'+this.selected.id, {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        this.quotes=this.quotes.filter(q=>q.id!==this.selected.id); this.selected=null;
    },

    sending: false,
    portalUrl: '',
    async sendToClient() {
        if (!this.selected) return;
        this.sending = true;
        const base = '{{ $quotesApiBase }}';
        const res = await fetch(base + '/' + this.selected.id + '/send', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.ok) {
            this.portalUrl = data.url;
            const idx = this.quotes.findIndex(q => q.id === this.selected.id);
            if (idx > -1) { this.quotes[idx].token = data.quote.token; this.quotes[idx].sent_at = data.quote.sent_at; this.quotes[idx].status = 'sent'; }
            this.selected = { ...this.selected, token: data.quote.token, status: 'sent' };
            this.form.status = 'sent';
        }
        this.sending = false;
    },

    copyLink() {
        navigator.clipboard.writeText(this.portalUrl || (this.selected?.token ? '{{ url('/b/'.$project->slug.'/c/') }}/' + this.selected.token : ''));
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
        <button @click="openNew()" class="btn-primary text-xs px-3 py-1.5">+ Nueva</button>
    </div>

    <div class="px-3 py-2 border-b border-gray-100 flex gap-1 overflow-x-auto flex-shrink-0">
        <button @click="filterStatus=''" :class="filterStatus==='' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Todas</button>
        <button @click="filterStatus='draft'" :class="filterStatus==='draft' ? 'bg-gray-200 text-gray-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Borrador</button>
        <button @click="filterStatus='sent'" :class="filterStatus==='sent' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Enviadas</button>
        <button @click="filterStatus='accepted'" :class="filterStatus==='accepted' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="text-xs px-2.5 py-1 rounded-full">Aceptadas</button>
    </div>

    <div class="overflow-y-auto flex-1">
        <template x-for="q in filtered" :key="q.id">
            <div @click="select(q)" class="list-item" :class="selected && selected.id===q.id ? 'selected' : ''">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate" x-text="q.client_name"></p>
                    <p class="text-xs text-gray-500" x-text="'S/ '+parseFloat(q.total).toFixed(2) + ' · ' + q.created_at"></p>
                </div>
                <span :class="statusColors[q.status]" x-text="statuses[q.status]"></span>
            </div>
        </template>
        <div x-show="filtered.length===0" class="py-10 text-center text-sm text-gray-400">Sin cotizaciones</div>
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm text-gray-400">Selecciona una cotización o crea una nueva</p>
    </div>

    <template x-if="selected || creating">
        <div class="flex flex-col h-full">
            <div class="flex border-b border-gray-200 px-4 bg-white flex-shrink-0">
                <button @click="tab='detail'" :class="tab==='detail' ? 'detail-tab active' : 'detail-tab'">Detalle</button>
            </div>
            <div class="flex-1 overflow-y-auto p-5 space-y-4">
                <h3 class="font-semibold text-gray-800" x-text="creating ? 'Nueva cotización' : 'Cotización #' + selected.id"></h3>

                <template x-if="creating">
                    <div class="space-y-4">
                        <div><label class="label">Cliente *</label><input type="text" x-model="form.client_name" class="input"></div>
                        <div><label class="label">Teléfono</label><input type="text" x-model="form.client_phone" class="input"></div>
                        <div><label class="label">Válida hasta</label><input type="date" x-model="form.valid_until" class="input"></div>
                        <div><label class="label">Notas</label><textarea x-model="form.notes" class="input resize-none" rows="2"></textarea></div>
                        <div class="grid grid-cols-2 gap-2" x-show="paymentMethods.length || paymentConditions.length">
                            <div x-show="paymentMethods.length > 0"><label class="label">Método de pago</label>
                                <select x-model="form.payment_method" class="input text-sm"><option value="">—</option>
                                    <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                                </select></div>
                            <div x-show="paymentConditions.length > 0"><label class="label">Condición de pago</label>
                                <select x-model="form.payment_condition" class="input text-sm"><option value="">—</option>
                                    <template x-for="c in paymentConditions" :key="c"><option :value="c" x-text="c"></option></template>
                                </select></div>
                        </div>
                        <div class="border border-gray-200 rounded-lg p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Items</p>
                                <button @click="addItem()" class="text-xs text-indigo-600">+ Agregar</button>
                            </div>
                            <template x-for="(item, i) in form.items" :key="i">
                                <div class="flex flex-wrap gap-2 items-center">
                                    <input x-model="form.items[i].description" class="input text-xs w-full sm:flex-1" placeholder="Descripción">
                                    <input type="number" x-model="form.items[i].price" class="input text-xs w-28 sm:w-20" placeholder="Precio" step="0.01">
                                    <input type="number" x-model="form.items[i].quantity" class="input text-xs w-20 sm:w-16" placeholder="Cant." min="1">
                                    <button @click="removeItem(i)" class="text-red-400 text-xs flex-shrink-0">✕</button>
                                </div>
                            </template>
                            <div class="flex justify-end pt-1">
                                <p class="text-sm font-semibold">Total: S/ <span x-text="total.toFixed(2)"></span></p>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!creating && selected">
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Cliente</p><p class="text-sm font-medium" x-text="selected.client_name"></p></div>
                            <div class="bg-gray-50 rounded-lg p-3"><p class="text-xs text-gray-500">Total</p><p class="text-sm font-medium" x-text="'S/ '+parseFloat(selected.total).toFixed(2)"></p></div>
                        </div>
                        <div>
                            <label class="label">Estado</label>
                            <select x-model="form.status" class="input">
                                <option value="draft">Borrador</option>
                                <option value="sent">Enviada</option>
                                <option value="accepted">Aceptada</option>
                                <option value="rejected">Rechazada</option>
                            </select>
                        </div>
                        <div><label class="label">Notas</label><textarea x-model="form.notes" class="input resize-none" rows="3"></textarea></div>

                        {{-- Panel Enviar al cliente --}}
                        <div class="border-t border-gray-100 pt-3 space-y-2">
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Portal del cliente</p>

                            {{-- Sin token aún --}}
                            <template x-if="!selected.token && !portalUrl">
                                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 space-y-2">
                                    <p class="text-xs text-indigo-700">Genera un enlace único para que el cliente vea y apruebe esta cotización.</p>
                                    <button @click="sendToClient()" :disabled="sending"
                                            class="w-full flex items-center justify-center gap-2 py-2 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition disabled:opacity-60">
                                        <svg x-show="!sending" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                        </svg>
                                        <svg x-show="sending" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <span x-text="sending ? 'Generando...' : 'Generar enlace y enviar'"></span>
                                    </button>
                                </div>
                            </template>

                            {{-- Con token (ya enviada) --}}
                            <template x-if="selected.token || portalUrl">
                                <div class="bg-green-50 border border-green-200 rounded-xl p-3 space-y-2">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <p class="text-xs font-semibold text-green-800">Enlace generado</p>
                                        <span x-show="selected.sent_at" class="text-xs text-green-600 ml-auto" x-text="'Enviado ' + selected.sent_at"></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <code class="flex-1 text-xs bg-white border border-green-200 rounded-lg px-2 py-1.5 text-gray-700 truncate font-mono"
                                              x-text="portalUrl || ('{{ url('/b/'.$project->slug.'/c/') }}/' + selected.token)"></code>
                                        <button @click="copyLink()" title="Copiar enlace"
                                                class="flex-shrink-0 p-1.5 rounded-lg bg-white border border-green-200 hover:bg-green-100 transition">
                                            <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                        <a :href="portalUrl || ('{{ url('/b/'.$project->slug.'/c/') }}/' + selected.token)"
                                           target="_blank"
                                           class="flex-shrink-0 p-1.5 rounded-lg bg-white border border-green-200 hover:bg-green-100 transition">
                                            <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    </div>
                                    @if($project->whatsapp)
                                    <a :href="'https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text=' + encodeURIComponent('Hola, te comparto tu cotización: ' + (portalUrl || ('{{ url('/b/'.$project->slug.'/c/') }}/' + selected.token)))"
                                       target="_blank"
                                       class="w-full flex items-center justify-center gap-1.5 py-1.5 text-xs font-semibold text-white bg-[#25D366] hover:bg-[#20ba5a] rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                        </svg>
                                        Enviar por WhatsApp
                                    </a>
                                    @endif
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="border-t border-gray-200 px-5 py-3 flex gap-3 bg-white flex-shrink-0">
                <button @click="save()" :disabled="saving" class="btn-primary flex-1" x-text="saving ? 'Guardando...' : 'Guardar'"></button>
                <button x-show="!creating" @click="del()" class="btn-danger px-4">Eliminar</button>
            </div>
        </div>
    </template>
</div>

</div>
</x-portal-layout>

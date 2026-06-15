@php
    $quotesApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.cotizaciones')
        : route('quotes');
    $currency = $project->setting('currency_symbol', 'S/');
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Cotizaciones">

<style>
/* ── Reset & Base ── */
.q-wrap { display:flex; height:calc(100vh - 56px); overflow:hidden; background:#f8f9fb; font-family:inherit; }

/* ── Sidebar lista ── */
.q-sidebar { width:300px; flex-shrink:0; display:flex; flex-direction:column; background:#fff; border-right:1px solid #e5e7eb; }
.q-sidebar-head { padding:10px 12px; border-bottom:1px solid #e5e7eb; display:flex; gap:8px; align-items:center; }
.q-search { flex:1; border:1px solid #e5e7eb; border-radius:8px; padding:6px 10px 6px 30px; font-size:12px; outline:none; background:#f8f9fb url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%239ca3af' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 8px center; }
.q-search:focus { border-color:#6366f1; background-color:#fff; }
.q-btn-new { background:#6366f1; color:#fff; border:none; border-radius:8px; padding:6px 12px; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap; }
.q-btn-new:hover { background:#4f46e5; }
.q-filters { display:flex; gap:4px; padding:8px 12px; border-bottom:1px solid #f3f4f6; overflow-x:auto; }
.q-filter { border:none; border-radius:20px; padding:3px 10px; font-size:11px; font-weight:500; cursor:pointer; background:#f3f4f6; color:#6b7280; white-space:nowrap; }
.q-filter.active { background:#eef2ff; color:#6366f1; }
.q-list { overflow-y:auto; flex:1; }
.q-item { padding:10px 14px; border-bottom:1px solid #f3f4f6; cursor:pointer; display:flex; align-items:center; gap:10px; transition:background .1s; }
.q-item:hover { background:#f8f9fb; }
.q-item.active { background:#eef2ff; border-left:3px solid #6366f1; }
.q-item-body { flex:1; min-width:0; }
.q-item-name { font-size:13px; font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.q-item-meta { font-size:11px; color:#9ca3af; margin-top:1px; display:flex; gap:6px; align-items:center; }
.q-item-total { font-size:12px; font-weight:700; color:#374151; flex-shrink:0; }

/* ── Badges estado ── */
.qbadge { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; text-transform:uppercase; letter-spacing:.3px; }
.qbadge-draft    { background:#f3f4f6; color:#6b7280; }
.qbadge-sent     { background:#dbeafe; color:#1d4ed8; }
.qbadge-accepted { background:#dcfce7; color:#15803d; }
.qbadge-rejected { background:#fee2e2; color:#dc2626; }

/* ── Zona central ── */
.q-main { flex:1; display:flex; flex-direction:column; overflow:hidden; min-width:0; }
.q-main-head { padding:12px 20px; border-bottom:1px solid #e5e7eb; background:#fff; display:flex; align-items:center; gap:12px; flex-shrink:0; }
.q-main-title { font-size:15px; font-weight:700; color:#111827; }
.q-main-sub { font-size:12px; color:#9ca3af; }
.q-main-body { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:16px; }

/* ── Tabla de productos ── */
.q-table-wrap { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.q-table { width:100%; border-collapse:collapse; }
.q-table th { font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:.4px; padding:8px 12px; background:#f9fafb; border-bottom:1px solid #e5e7eb; text-align:left; }
.q-table th.r, .q-table td.r { text-align:right; }
.q-table td { padding:6px 8px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.q-table tr:last-child td { border-bottom:none; }
.q-table tr:hover td { background:#fafafa; }
.q-td-input { border:1px solid transparent; border-radius:6px; padding:5px 8px; font-size:13px; width:100%; background:transparent; outline:none; color:#111827; transition:border .15s,background .15s; min-width:0; }
.q-td-input:focus { border-color:#6366f1; background:#fff; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-td-input.desc { min-width:180px; }
.q-td-input.num  { width:72px; text-align:right; }
.q-td-sub { font-size:13px; font-weight:600; color:#374151; white-space:nowrap; text-align:right; }
.q-add-row { width:100%; border:none; background:none; padding:8px 12px; font-size:12px; color:#6366f1; cursor:pointer; text-align:left; display:flex; align-items:center; gap:6px; }
.q-add-row:hover { background:#f5f3ff; }

/* ── Buscador catálogo ── */
.q-catalog-wrap { position:relative; }
.q-catalog-input { border:1.5px dashed #c7d2fe; border-radius:8px; padding:7px 12px 7px 32px; font-size:12px; width:100%; outline:none; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%236366f1' stroke-width='2' viewBox='0 0 24 24'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 10px center; color:#374151; }
.q-catalog-input:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-catalog-drop { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,.1); z-index:100; overflow:hidden; max-height:220px; overflow-y:auto; }
.q-catalog-item { display:flex; align-items:center; justify-content:space-between; padding:8px 14px; cursor:pointer; border-bottom:1px solid #f3f4f6; gap:8px; }
.q-catalog-item:hover { background:#f5f3ff; }
.q-catalog-item:last-child { border-bottom:none; }
.q-catalog-name { font-size:12px; font-weight:500; color:#111827; }
.q-catalog-sku  { font-size:10px; color:#9ca3af; }
.q-catalog-price{ font-size:12px; font-weight:700; color:#6366f1; flex-shrink:0; }

/* ── Datos cliente ── */
.q-client-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.q-field label { font-size:11px; font-weight:600; color:#6b7280; display:block; margin-bottom:3px; }
.q-field input, .q-field select, .q-field textarea { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:7px 10px; font-size:13px; outline:none; color:#111827; background:#fff; transition:border .15s; }
.q-field input:focus, .q-field select:focus, .q-field textarea:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.q-section { background:#fff; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; }
.q-section-head { padding:10px 16px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; }
.q-section-title { font-size:12px; font-weight:700; color:#374151; text-transform:uppercase; letter-spacing:.4px; }
.q-section-body { padding:14px 16px; }

/* ── Panel derecho ── */
.q-panel { width:240px; flex-shrink:0; display:flex; flex-direction:column; gap:12px; padding:16px 14px; overflow-y:auto; background:#f8f9fb; border-left:1px solid #e5e7eb; }
.q-summary { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:14px; }
.q-summary-row { display:flex; justify-content:space-between; font-size:12px; color:#6b7280; padding:3px 0; }
.q-summary-total { display:flex; justify-content:space-between; font-size:16px; font-weight:800; color:#111827; padding-top:8px; margin-top:6px; border-top:2px solid #e5e7eb; }
.q-actions { display:flex; flex-direction:column; gap:6px; }
.q-btn { border:none; border-radius:8px; padding:9px 14px; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:7px; justify-content:center; transition:opacity .15s,background .15s; width:100%; }
.q-btn:disabled { opacity:.5; cursor:not-allowed; }
.q-btn-primary  { background:#6366f1; color:#fff; }
.q-btn-primary:hover:not(:disabled) { background:#4f46e5; }
.q-btn-green    { background:#25d366; color:#fff; }
.q-btn-green:hover:not(:disabled)  { background:#1fb855; }
.q-btn-outline  { background:#fff; color:#374151; border:1px solid #e5e7eb; }
.q-btn-outline:hover { background:#f3f4f6; }
.q-btn-danger   { background:#fff; color:#dc2626; border:1px solid #fee2e2; }
.q-btn-danger:hover { background:#fee2e2; }

/* ── Estado timeline ── */
.q-status-bar { display:flex; gap:4px; align-items:center; }
.q-status-step { flex:1; text-align:center; font-size:10px; font-weight:600; padding:4px 2px; border-radius:6px; cursor:pointer; border:1.5px solid transparent; transition:all .15s; }
.q-status-step.done   { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
.q-status-step.active { background:#dbeafe; color:#1d4ed8; border-color:#93c5fd; }
.q-status-step.idle   { background:#f3f4f6; color:#9ca3af; }

/* ── Portal minimalista ── */
.q-portal { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:10px 12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.q-portal-label { font-size:11px; font-weight:700; color:#166534; flex:1; min-width:80px; }
.q-portal-btns { display:flex; gap:4px; }
.q-portal-btn { border:1px solid #bbf7d0; background:#fff; border-radius:6px; padding:4px 8px; font-size:11px; font-weight:600; color:#374151; cursor:pointer; display:flex; align-items:center; gap:3px; }
.q-portal-btn:hover { background:#dcfce7; }

/* ── Empty state ── */
.q-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; color:#d1d5db; }

/* ── Mobile ── */
@media(max-width:767px){
  .q-sidebar { width:100%; border-right:none; }
  .q-panel { display:none; }
  .q-main { min-height:0; }
  .q-client-grid { grid-template-columns:1fr; }
}
</style>

<div class="q-wrap" x-data="{
    quotes: {{ Js::from($quotes->map(fn($q) => [
        'id'               => $q->id,
        'client_name'      => $q->client_name,
        'client_phone'     => $q->client_phone ?? '',
        'client_email'     => $q->client_email ?? '',
        'client_doc_type'  => $q->client_doc_type ?? '',
        'client_doc_number'=> $q->client_doc_number ?? '',
        'client_address'   => $q->client_address ?? '',
        'status'           => $q->status,
        'total'            => (float)$q->total,
        'notes'            => $q->notes ?? '',
        'valid_until'      => $q->valid_until?->format('Y-m-d') ?? '',
        'payment_method'   => $q->payment_method ?? '',
        'payment_condition'=> $q->payment_condition ?? '',
        'created_at'       => $q->created_at->format('d/m/Y'),
        'token'            => $q->token ?? '',
        'sent_at'          => $q->sent_at?->format('d/m/Y H:i') ?? '',
        'items'            => $q->items->map(fn($i) => ['id'=>$i->id,'description'=>$i->description,'price'=>(float)$i->price,'quantity'=>(float)$i->quantity,'discount'=>0])->toArray(),
    ])) }},

    products: {{ Js::from($products->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'price'=>(float)$p->price,'sku'=>$p->sku??''])) }},
    paymentMethods:    {{ Illuminate\Support\Js::from($paymentMethods) }},
    paymentConditions: {{ Illuminate\Support\Js::from($paymentConditions) }},

    search: '', filterStatus: '',
    panel: 'list',
    selected: null, creating: false,
    saving: false, sending: false,
    catalogSearch: '', catalogOpen: false,
    portalUrl: '',

    form: {
        client_name:'', client_phone:'', client_email:'',
        client_doc_type:'', client_doc_number:'', client_address:'',
        notes:'', valid_until:'', status:'draft',
        payment_method:'', payment_condition:'',
        items: []
    },

    get filtered() {
        return this.quotes.filter(q => {
            const s = !this.search || q.client_name.toLowerCase().includes(this.search.toLowerCase()) || (q.client_phone||'').includes(this.search);
            const f = !this.filterStatus || q.status === this.filterStatus;
            return s && f;
        });
    },

    get filteredCatalog() {
        if (!this.catalogSearch) return this.products.slice(0,10);
        const q = this.catalogSearch.toLowerCase();
        return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.sku&&p.sku.toLowerCase().includes(q))).slice(0,10);
    },

    get subtotal() { return this.form.items.reduce((s,i) => s + (parseFloat(i.price)||0)*(parseFloat(i.quantity)||1)*(1-(parseFloat(i.discount)||0)/100), 0); },
    get igv()      { return this.subtotal * 0; },
    get grandTotal(){ return this.subtotal + this.igv; },

    fmt(n) { return '{{ $currency }} ' + parseFloat(n||0).toFixed(2); },

    statusLabel: { draft:'Borrador', sent:'Enviada', accepted:'Aceptada', rejected:'Rechazada' },

    select(q) {
        this.selected = {...q};
        this.form = {
            client_name: q.client_name||'', client_phone: q.client_phone||'',
            client_email: q.client_email||'', client_doc_type: q.client_doc_type||'',
            client_doc_number: q.client_doc_number||'', client_address: q.client_address||'',
            notes: q.notes||'', valid_until: q.valid_until||'', status: q.status,
            payment_method: q.payment_method||'', payment_condition: q.payment_condition||'',
            items: q.items && q.items.length ? q.items.map(i=>({...i,discount:i.discount||0})) : [{description:'',price:'',quantity:1,discount:0}]
        };
        this.portalUrl = q.token ? '{{ url('/b/'.$project->slug.'/c/') }}/' + q.token : '';
        this.creating = false;
        if(window.innerWidth < 768) this.panel = 'detail';
    },

    openNew() {
        this.selected = null; this.creating = true; this.portalUrl = '';
        this.form = { client_name:'', client_phone:'', client_email:'', client_doc_type:'', client_doc_number:'', client_address:'', notes:'', valid_until:'', status:'draft', payment_method:'', payment_condition:'', items:[{description:'',price:'',quantity:1,discount:0}] };
        if(window.innerWidth < 768) this.panel = 'detail';
    },

    addItem()     { this.form.items.push({description:'',price:'',quantity:1,discount:0}); this.$nextTick(()=>{ const inputs=document.querySelectorAll('.q-td-input.desc'); if(inputs.length) inputs[inputs.length-1].focus(); }); },
    removeItem(i) { if(this.form.items.length>1) this.form.items.splice(i,1); },

    addFromCatalog(p) {
        const existing = this.form.items.find(i => i.description === p.name);
        if (existing) { existing.quantity = (parseFloat(existing.quantity)||1) + 1; }
        else { this.form.items.push({description:p.name, price:p.price, quantity:1, discount:0}); }
        this.catalogSearch = ''; this.catalogOpen = false;
    },

    async save() {
        if (!this.form.client_name.trim()) { alert('Ingresa el nombre del cliente'); return; }
        this.saving = true;
        const base = '{{ $quotesApiBase }}';
        const url  = this.creating ? base : base + '/' + this.selected.id;
        const method = this.creating ? 'POST' : (this.selected ? 'PUT' : 'POST');
        const body = {
            client_name: this.form.client_name, client_phone: this.form.client_phone,
            client_email: this.form.client_email, client_doc_type: this.form.client_doc_type,
            client_doc_number: this.form.client_doc_number, client_address: this.form.client_address,
            notes: this.form.notes, valid_until: this.form.valid_until,
            payment_method: this.form.payment_method, payment_condition: this.form.payment_condition,
            status: this.form.status,
            items: this.form.items.filter(i=>i.description).map(i=>({description:i.description,price:parseFloat(i.price)||0,quantity:parseFloat(i.quantity)||1})),
        };
        if (!this.creating) body.status = this.form.status;
        const fullUrl = this.creating ? base : base + '/' + this.selected.id + '/full';
        const res = await fetch(fullUrl, { method: this.creating ? 'POST' : 'PUT', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify(body) });
        const data = await res.json();
        const q = data.quote;
        if (q) {
            const display = {...q, items: q.items||this.form.items, total:parseFloat(q.total||0)};
            const row = { id:q.id, client_name:q.client_name, client_phone:q.client_phone||'', client_email:q.client_email||'', client_doc_type:q.client_doc_type||'', client_doc_number:q.client_doc_number||'', client_address:q.client_address||'', status:q.status, total:parseFloat(q.total||0), notes:q.notes||'', valid_until:q.valid_until||'', payment_method:q.payment_method||'', payment_condition:q.payment_condition||'', created_at:q.created_at||new Date().toLocaleDateString('es'), token:q.token||'', sent_at:q.sent_at||'', items:q.items||[] };
            if (this.creating) { this.quotes.unshift(row); } else { const idx=this.quotes.findIndex(x=>x.id===q.id); if(idx>-1) this.quotes[idx]=row; }
            this.selected = row; this.creating = false;
            this.form.items = (q.items||[]).map(i=>({...i,discount:0}));
        }
        this.saving = false;
    },

    async del() {
        if(!confirm('¿Eliminar esta cotización?')) return;
        await fetch('{{ $quotesApiBase }}/'+this.selected.id, {method:'DELETE',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        this.quotes = this.quotes.filter(q=>q.id!==this.selected.id);
        this.selected = null; this.creating = false; this.panel = 'list';
    },

    async setStatus(s) {
        this.form.status = s;
        if (!this.creating && this.selected) await this.save();
    },

    async sendToClient() {
        if (!this.selected) return;
        this.sending = true;
        const res = await fetch('{{ $quotesApiBase }}/'+this.selected.id+'/send', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
        const data = await res.json();
        if (data.ok) {
            this.portalUrl = data.url;
            const idx = this.quotes.findIndex(q=>q.id===this.selected.id);
            if(idx>-1){ this.quotes[idx].token=data.quote.token; this.quotes[idx].status='sent'; }
            this.selected = {...this.selected, token:data.quote.token, status:'sent'};
            this.form.status = 'sent';
        }
        this.sending = false;
    },

    sendWhatsApp() {
        const link = this.portalUrl || (this.selected?.token ? '{{ url('/b/'.$project->slug.'/c/') }}/'+this.selected.token : '');
        if (!link) { alert('Primero genera el enlace del portal'); return; }
        const wa = '{{ preg_replace('/\D/','', $project->whatsapp ?? '') }}';
        if (!wa) { alert('Configura el número de WhatsApp en ajustes'); return; }
        const msg = 'Hola ' + (this.selected?.client_name||'') + ', te comparto tu cotización: ' + link;
        window.open('https://wa.me/'+wa+'?text='+encodeURIComponent(msg),'_blank');
    },

    copyLink() {
        const link = this.portalUrl || (this.selected?.token ? '{{ url('/b/'.$project->slug.'/c/') }}/'+this.selected.token : '');
        if (link) navigator.clipboard.writeText(link);
    },
}">

{{-- ══ SIDEBAR LISTA ══ --}}
<div class="q-sidebar" :class="panel==='detail' ? 'hidden md:flex' : 'flex'">
    <div class="q-sidebar-head">
        <input type="text" x-model="search" placeholder="Buscar cliente..." class="q-search">
        <button @click="openNew()" class="q-btn-new">+ Nueva</button>
    </div>
    <div class="q-filters">
        <button @click="filterStatus=''" class="q-filter" :class="filterStatus==='' ? 'active':''">Todas</button>
        <button @click="filterStatus='draft'" class="q-filter" :class="filterStatus==='draft' ? 'active':''">Borrador</button>
        <button @click="filterStatus='sent'" class="q-filter" :class="filterStatus==='sent' ? 'active':''">Enviadas</button>
        <button @click="filterStatus='accepted'" class="q-filter" :class="filterStatus==='accepted' ? 'active':''">Aceptadas</button>
    </div>
    <div class="q-list">
        <template x-for="q in filtered" :key="q.id">
            <div class="q-item" :class="selected && selected.id===q.id ? 'active':''" @click="select(q)">
                <div class="q-item-body">
                    <div class="q-item-name" x-text="q.client_name"></div>
                    <div class="q-item-meta">
                        <span x-text="q.created_at"></span>
                        <span x-show="q.client_phone" x-text="'· '+q.client_phone"></span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                    <span class="q-item-total" x-text="fmt(q.total)"></span>
                    <span :class="'qbadge qbadge-'+q.status" x-text="{draft:'Borrador',sent:'Enviada',accepted:'Aceptada',rejected:'Rechazada'}[q.status]"></span>
                </div>
            </div>
        </template>
        <div x-show="filtered.length===0" style="padding:40px 0;text-align:center;font-size:13px;color:#9ca3af">Sin cotizaciones</div>
    </div>
</div>

{{-- ══ ZONA CENTRAL ══ --}}
<div class="q-main" :class="panel==='list' ? 'hidden md:flex' : 'flex'">

    {{-- Back mobile --}}
    <button @click="panel='list'" class="md:hidden" style="display:flex;align-items:center;gap:6px;padding:10px 16px;font-size:13px;color:#6b7280;border:none;background:#fff;border-bottom:1px solid #e5e7eb;width:100%;cursor:pointer">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        Volver a la lista
    </button>

    {{-- Empty state --}}
    <div x-show="!selected && !creating" class="q-empty">
        <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p style="font-size:13px;color:#9ca3af">Selecciona una cotización o crea una nueva</p>
        <button @click="openNew()" style="margin-top:4px;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer">+ Nueva cotización</button>
    </div>

    <template x-if="selected || creating">
    <div style="display:flex;flex-direction:column;height:100%;overflow:hidden">

        {{-- Header --}}
        <div class="q-main-head">
            <div style="flex:1">
                <div class="q-main-title" x-text="creating ? 'Nueva cotización' : 'Cotización #' + selected.id"></div>
                <div class="q-main-sub" x-text="creating ? 'Completa los datos y agrega productos' : selected.created_at + (selected.client_name ? ' · ' + selected.client_name : '')"></div>
            </div>
            {{-- Status timeline --}}
            <template x-if="!creating">
            <div class="q-status-bar" style="min-width:300px">
                <template x-for="(s,label) in {draft:'Borrador',sent:'Enviada',accepted:'Aceptada',rejected:'Rechazada'}" :key="s">
                    <div class="q-status-step"
                         :class="form.status===s ? 'active' : ((['draft','sent','accepted'].indexOf(form.status) > ['draft','sent','accepted'].indexOf(s)) ? 'done' : 'idle')"
                         @click="setStatus(s)"
                         x-text="label"></div>
                </template>
            </div>
            </template>
        </div>

        <div class="q-main-body">

            {{-- TABLA DE PRODUCTOS --}}
            <div class="q-section">
                <div class="q-section-head">
                    <span class="q-section-title">📦 Productos</span>
                    <button @click="addItem()" style="font-size:12px;color:#6366f1;font-weight:600;border:none;background:none;cursor:pointer">+ Agregar línea</button>
                </div>

                {{-- Buscador catálogo --}}
                @if($products->count())
                <div style="padding:10px 14px;border-bottom:1px solid #f3f4f6">
                    <div class="q-catalog-wrap">
                        <input type="text" x-model="catalogSearch"
                               @focus="catalogOpen=true" @input="catalogOpen=true"
                               @keydown.escape="catalogOpen=false;catalogSearch=''"
                               class="q-catalog-input" placeholder="🔍 Buscar producto del catálogo para agregar...">
                        <div class="q-catalog-drop" x-show="catalogOpen && filteredCatalog.length" @click.outside="catalogOpen=false;catalogSearch=''">
                            <template x-for="p in filteredCatalog" :key="p.id">
                                <div class="q-catalog-item" @click="addFromCatalog(p)">
                                    <div>
                                        <div class="q-catalog-name" x-text="p.name"></div>
                                        <div class="q-catalog-sku" x-show="p.sku" x-text="'SKU: '+p.sku"></div>
                                    </div>
                                    <span class="q-catalog-price" x-text="fmt(p.price)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                @endif

                <div class="q-table-wrap" style="border-radius:0;border:none">
                    <table class="q-table">
                        <thead>
                            <tr>
                                <th style="width:40%">Producto / Descripción</th>
                                <th class="r" style="width:10%">Cant.</th>
                                <th class="r" style="width:14%">Precio</th>
                                <th class="r" style="width:10%">Desc.%</th>
                                <th class="r" style="width:14%">Subtotal</th>
                                <th style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, i) in form.items" :key="i">
                                <tr>
                                    <td><input class="q-td-input desc" x-model="form.items[i].description" placeholder="Descripción del producto"
                                               @keydown.tab.prevent="$event.shiftKey ? (i>0?$refs['qty'+(i-1)].focus():null) : $refs['qty'+i].focus()"></td>
                                    <td><input class="q-td-input num" type="number" x-model="form.items[i].quantity" :x-ref="'qty'+i" min="0.001" step="any" placeholder="1"
                                               @keydown.tab.prevent="$refs['price'+i].focus()"></td>
                                    <td><input class="q-td-input num" type="number" x-model="form.items[i].price" :x-ref="'price'+i" min="0" step="any" placeholder="0.00"
                                               @keydown.tab.prevent="$refs['disc'+i].focus()"></td>
                                    <td><input class="q-td-input num" type="number" x-model="form.items[i].discount" :x-ref="'disc'+i" min="0" max="100" step="any" placeholder="0"
                                               @keydown.tab.prevent="i<form.items.length-1 ? $nextTick(()=>document.querySelectorAll('.q-td-input.desc')[i+1]?.focus()) : addItem()"></td>
                                    <td class="r"><span class="q-td-sub" x-text="fmt((parseFloat(item.price)||0)*(parseFloat(item.quantity)||1)*(1-(parseFloat(item.discount)||0)/100))"></span></td>
                                    <td style="text-align:center">
                                        <button @click="removeItem(i)" x-show="form.items.length>1" style="border:none;background:none;color:#d1d5db;cursor:pointer;font-size:15px;padding:2px 4px" title="Eliminar">✕</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <button class="q-add-row" @click="addItem()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Agregar línea
                    </button>
                </div>
            </div>

            {{-- DATOS DEL CLIENTE --}}
            <div class="q-section">
                <div class="q-section-head"><span class="q-section-title">👤 Cliente</span></div>
                <div class="q-section-body">
                    <div class="q-client-grid">
                        <div class="q-field"><label>Nombre *</label><input type="text" x-model="form.client_name" placeholder="Nombre completo"></div>
                        <div class="q-field"><label>Celular</label><input type="tel" x-model="form.client_phone" placeholder="999 999 999"></div>
                        <div class="q-field"><label>Email</label><input type="email" x-model="form.client_email" placeholder="cliente@email.com"></div>
                        <div class="q-field"><label>DNI / RUC</label><input type="text" x-model="form.client_doc_number" placeholder="12345678"></div>
                        <div class="q-field" style="grid-column:1/-1"><label>Dirección</label><input type="text" x-model="form.client_address" placeholder="Av. Principal 123, Lima"></div>
                    </div>
                </div>
            </div>

            {{-- CONDICIONES --}}
            <div class="q-section">
                <div class="q-section-head"><span class="q-section-title">📋 Condiciones</span></div>
                <div class="q-section-body">
                    <div class="q-client-grid">
                        <div class="q-field"><label>Válida hasta</label><input type="date" x-model="form.valid_until"></div>
                        <div class="q-field" x-show="paymentMethods.length">
                            <label>Método de pago</label>
                            <select x-model="form.payment_method"><option value="">—</option>
                                <template x-for="m in paymentMethods" :key="m"><option :value="m" x-text="m"></option></template>
                            </select>
                        </div>
                        <div class="q-field" x-show="paymentConditions.length">
                            <label>Condición de pago</label>
                            <select x-model="form.payment_condition"><option value="">—</option>
                                <template x-for="c in paymentConditions" :key="c"><option :value="c" x-text="c"></option></template>
                            </select>
                        </div>
                        <div class="q-field" style="grid-column:1/-1"><label>Notas internas</label><textarea x-model="form.notes" rows="2" placeholder="Observaciones, términos especiales..."></textarea></div>
                    </div>
                </div>
            </div>

            {{-- PORTAL CLIENTE (solo si tiene token) --}}
            <template x-if="!creating && (selected?.token || portalUrl)">
                <div class="q-portal">
                    <span class="q-portal-label">🔗 Portal cliente ✓</span>
                    <div class="q-portal-btns">
                        <button class="q-portal-btn" @click="copyLink()" title="Copiar enlace">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            Copiar
                        </button>
                        <a class="q-portal-btn" :href="portalUrl || ('{{ url('/b/'.$project->slug.'/c/') }}/'+selected.token)" target="_blank">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
                            Abrir
                        </a>
                        @if($project->whatsapp)
                        <button class="q-portal-btn" @click="sendWhatsApp()" style="background:#25d366;color:#fff;border-color:#25d366">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                            WA
                        </button>
                        @endif
                        <button class="q-portal-btn" @click="exportMenu=!exportMenu" style="position:relative" x-data="{exportMenu:false}" @click.outside="exportMenu=false">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                            Exportar
                            <div x-show="exportMenu" x-cloak style="position:absolute;top:calc(100% + 4px);right:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,.1);z-index:200;min-width:130px;overflow:hidden">
                                <button onclick="exportQuotePDF()" style="display:flex;align-items:center;gap:6px;width:100%;padding:8px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer;text-align:left" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                    PDF
                                </button>
                                <button onclick="exportQuoteImg()" style="display:flex;align-items:center;gap:6px;width:100%;padding:8px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer;text-align:left" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    Imagen PNG
                                </button>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

        </div>{{-- end q-main-body --}}
    </div>
    </template>
</div>

{{-- ══ PANEL DERECHO STICKY ══ --}}
<div class="q-panel" x-show="selected || creating">

    {{-- Resumen financiero --}}
    <div class="q-summary">
        <div style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">Resumen</div>
        <div class="q-summary-row"><span>Subtotal</span><span x-text="fmt(subtotal)"></span></div>
        <div class="q-summary-row" x-show="igv>0"><span>IGV (18%)</span><span x-text="fmt(igv)"></span></div>
        <div class="q-summary-total"><span>Total</span><span x-text="fmt(grandTotal)"></span></div>
        <div style="font-size:11px;color:#9ca3af;margin-top:6px" x-text="form.items.filter(i=>i.description).length + ' producto(s)'"></div>
    </div>

    {{-- Acciones --}}
    <div class="q-actions">
        <button class="q-btn q-btn-primary" @click="save()" :disabled="saving">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span x-text="saving ? 'Guardando...' : 'Guardar'"></span>
        </button>

        <template x-if="!creating">
            <button class="q-btn q-btn-outline" @click="sendToClient()" :disabled="sending">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span x-text="sending ? 'Generando...' : (selected?.token ? 'Reenviar enlace' : 'Generar enlace')"></span>
            </button>
        </template>

        @if($project->whatsapp)
        <template x-if="!creating && (selected?.token || portalUrl)">
            <button class="q-btn q-btn-green" @click="sendWhatsApp()">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
                WhatsApp
            </button>
        </template>
        @endif

        <template x-if="!creating">
            <div x-data="{exportMenu:false}" style="position:relative">
                <button class="q-btn q-btn-outline" @click="exportMenu=!exportMenu" @click.outside="exportMenu=false">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Exportar
                </button>
                <div x-show="exportMenu" x-cloak style="position:absolute;bottom:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,.12);z-index:200;overflow:hidden">
                    <button onclick="exportQuotePDF()" style="display:flex;align-items:center;gap:6px;width:100%;padding:9px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        PDF
                    </button>
                    <button onclick="exportQuoteImg()" style="display:flex;align-items:center;gap:6px;width:100%;padding:9px 12px;border:none;background:none;font-size:12px;font-weight:500;color:#374151;cursor:pointer" onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        Imagen PNG
                    </button>
                </div>
            </div>
        </template>

        <template x-if="!creating">
            <button class="q-btn q-btn-outline" @click="del()" style="color:#dc2626;border-color:#fee2e2" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fff'">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                Eliminar
            </button>
        </template>
    </div>

</div>

</div>

{{-- Scripts para exportar --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function buildQuoteHtml() {
    const qWrap = document.querySelector('[x-data]');
    const al = qWrap ? qWrap.__x?.$data : null;
    const q = al?.selected;
    const form = al?.form;
    if (!q) return null;
    const cur = '{{ $project->setting('currency_symbol','S/') }}';
    const logo = '{{ $project->logo_url ?? '' }}';
    const biz = '{{ $project->business_name ?? $project->name }}';
    const items = (form?.items || []).filter(i => i.description);
    const subtotal = items.reduce((s,i) => s + parseFloat(i.price||0)*parseFloat(i.quantity||1)*(1-(parseFloat(i.discount||0)/100)), 0);
    const igv = subtotal * 0.18;
    const total = subtotal + igv;
    const fmt = n => cur + ' ' + parseFloat(n).toFixed(2);
    const rows = items.map(i => {
        const sub = parseFloat(i.price||0)*parseFloat(i.quantity||1)*(1-(parseFloat(i.discount||0)/100));
        return `<tr>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;font-size:13px">${i.description}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:center;font-size:13px">${i.quantity}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:right;font-size:13px">${fmt(i.price)}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:right;font-size:13px;font-weight:600">${fmt(sub)}</td>
        </tr>`;
    }).join('');
    return `<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>body{font-family:Arial,sans-serif;margin:0;padding:32px;color:#111827;background:#fff}
    *{box-sizing:border-box}</style></head><body>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #6366f1">
        <div>${logo ? `<img src="${logo}" style="height:48px;object-fit:contain">` : ''}<h2 style="margin:8px 0 0;font-size:18px;color:#111827">${biz}</h2></div>
        <div style="text-align:right">
            <div style="font-size:22px;font-weight:800;color:#6366f1">COTIZACIÓN</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px">#${q.id} · ${q.created_at || new Date().toLocaleDateString('es')}</div>
            ${q.valid_until ? `<div style="font-size:11px;color:#9ca3af">Válida hasta: ${q.valid_until}</div>` : ''}
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
        <div style="background:#f9fafb;border-radius:8px;padding:14px">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:8px">Cliente</div>
            ${q.client_name ? `<div style="font-size:14px;font-weight:600">${q.client_name}</div>` : ''}
            ${q.client_phone ? `<div style="font-size:12px;color:#6b7280">Tel: ${q.client_phone}</div>` : ''}
            ${q.client_doc_number ? `<div style="font-size:12px;color:#6b7280">${q.client_doc_type||'Doc'}: ${q.client_doc_number}</div>` : ''}
            ${q.client_address ? `<div style="font-size:12px;color:#6b7280">${q.client_address}</div>` : ''}
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:14px">
            <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:8px">Condiciones</div>
            ${form?.payment_method ? `<div style="font-size:12px">Pago: ${form.payment_method}</div>` : ''}
            ${form?.payment_condition ? `<div style="font-size:12px">Condición: ${form.payment_condition}</div>` : ''}
            ${form?.notes ? `<div style="font-size:12px;color:#6b7280;margin-top:4px">${form.notes}</div>` : ''}
        </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
        <thead><tr style="background:#6366f1">
            <th style="padding:10px;text-align:left;color:#fff;font-size:11px;font-weight:600;border-radius:6px 0 0 0">Descripción</th>
            <th style="padding:10px;text-align:center;color:#fff;font-size:11px;font-weight:600">Cant.</th>
            <th style="padding:10px;text-align:right;color:#fff;font-size:11px;font-weight:600">Precio</th>
            <th style="padding:10px;text-align:right;color:#fff;font-size:11px;font-weight:600;border-radius:0 6px 0 0">Subtotal</th>
        </tr></thead>
        <tbody>${rows}</tbody>
    </table>
    <div style="display:flex;justify-content:flex-end">
        <div style="min-width:220px">
            <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:12px;color:#6b7280"><span>Subtotal</span><span>${fmt(subtotal)}</span></div>
            <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:12px;color:#6b7280"><span>IGV (18%)</span><span>${fmt(igv)}</span></div>
            <div style="display:flex;justify-content:space-between;padding:10px 0 4px;font-size:18px;font-weight:800;border-top:2px solid #6366f1;margin-top:6px;color:#6366f1"><span>Total</span><span>${fmt(total)}</span></div>
        </div>
    </div>
    </body></html>`;
}

async function exportQuotePDF() {
    const html = buildQuoteHtml();
    if (!html) { alert('No hay cotización seleccionada'); return; }
    const { jsPDF } = window.jspdf;
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:800px;height:1px;border:none';
    document.body.appendChild(iframe);
    iframe.contentDocument.open();
    iframe.contentDocument.write(html);
    iframe.contentDocument.close();
    await new Promise(r => setTimeout(r, 600));
    const canvas = await html2canvas(iframe.contentDocument.body, {scale:2, useCORS:true, backgroundColor:'#fff', width:800});
    document.body.removeChild(iframe);
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF({orientation:'portrait', unit:'mm', format:'a4'});
    const pW = pdf.internal.pageSize.getWidth();
    const pH = pdf.internal.pageSize.getHeight();
    const ratio = canvas.width / canvas.height;
    const imgH = pW / ratio;
    if (imgH <= pH) {
        pdf.addImage(imgData, 'PNG', 0, 0, pW, imgH);
    } else {
        let yPos = 0, remaining = canvas.height;
        while (remaining > 0) {
            const sliceH = Math.min(remaining, Math.floor(canvas.width * pH / pW));
            const sliceCanvas = document.createElement('canvas');
            sliceCanvas.width = canvas.width; sliceCanvas.height = sliceH;
            sliceCanvas.getContext('2d').drawImage(canvas, 0, yPos, canvas.width, sliceH, 0, 0, canvas.width, sliceH);
            if (yPos > 0) pdf.addPage();
            pdf.addImage(sliceCanvas.toDataURL('image/png'), 'PNG', 0, 0, pW, sliceH * pW / canvas.width);
            yPos += sliceH; remaining -= sliceH;
        }
    }
    const al = document.querySelector('[x-data]').__x?.$data;
    pdf.save('cotizacion-' + (al?.selected?.id || 'export') + '.pdf');
}

async function exportQuoteImg() {
    const html = buildQuoteHtml();
    if (!html) { alert('No hay cotización seleccionada'); return; }
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:800px;height:1px;border:none';
    document.body.appendChild(iframe);
    iframe.contentDocument.open();
    iframe.contentDocument.write(html);
    iframe.contentDocument.close();
    await new Promise(r => setTimeout(r, 600));
    const canvas = await html2canvas(iframe.contentDocument.body, {scale:2, useCORS:true, backgroundColor:'#fff', width:800});
    document.body.removeChild(iframe);
    const link = document.createElement('a');
    const al = document.querySelector('[x-data]').__x?.$data;
    link.download = 'cotizacion-' + (al?.selected?.id || 'export') + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}
</script>
</x-portal-layout>

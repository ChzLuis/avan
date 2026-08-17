@php
    $ordersApiBase = ($portalLayout ?? 'panel') === 'comercial'
        ? route('bixosales.pedidos')
        : route('orders');
    $isSales = ($portalLayout ?? 'panel') === 'comercial';
@endphp
<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Pedidos">

<style>
/* Clases heredadas que SIGUEN en uso por el detalle trasladado al cajón.
   Se conservan tal cual; solo se descartaron las del maestro-detalle
   (.ord-list-item, .ord-avatar, .ord-back-btn, .ord-col-detail). */
.status-pill { display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:99px;white-space:nowrap; }
.s-pending   { background:#fef9c3;color:#854d0e; }
.s-process   { background:#dbeafe;color:#1e40af; }
.s-done      { background:#dcfce7;color:#166534; }
.s-cancelled { background:#fee2e2;color:#991b1b; }
.s-btn { display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:var(--tactil-min, 44px);padding:0 14px;border-radius:8px;font-size:12px;font-weight:600;border:2px solid transparent;cursor:pointer;transition:all .15s; }
/* Cierre del drawer y botones de icono: area tactil completa (medidos 28x18). */
#ped-drawer [aria-label="Cerrar detalle"] { min-width:var(--tactil-min, 44px);min-height:var(--tactil-min, 44px);display:inline-flex;align-items:center;justify-content:center; }
#ped-drawer button, #ped-drawer a.s-btn, #ped-drawer select { min-height:var(--tactil-min, 44px); }
/* Toolbar y pestañas de la lista (medidos 30-34px): objetivo tactil 44 en TODO
   control interactivo del modulo. El shell queda fuera (carril UX1). */
[x-init="initPedidos()"] :is(button, a[href], select, input:not([type=checkbox]):not([type=radio]), [role=button]) {
  min-height:var(--tactil-min, 44px);
}
[x-init="initPedidos()"] .flex.flex-wrap.gap-1\.5, [x-init="initPedidos()"] .flex.items-center.gap-2 { gap:var(--tactil-gap, 8px); }
#ped-drawer .flex.gap-1\.5 > .s-btn, #ped-drawer .flex.gap-2 > * { }

.s-btn.active { border-color:currentColor; }
.detail-section { background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:12px; }
.detail-section-header { padding:10px 16px;background:#f9fafb;border-bottom:1px solid #f3f4f6;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280; }
.ch-tag { display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:600;padding:2px 7px;border-radius:99px; }
.ch-pos { background:#ede9fe;color:#6d28d9; }
.ch-whatsapp { background:#dcfce7;color:#166534; }
.ch-ecommerce { background:#dbeafe;color:#1e40af; }
.ch-default { background:#f3f4f6;color:#6b7280; }
.kpi-card { background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 18px; }

/* Densidad 44 px por fila: acordada como estándar. Una futura opción
   "Compacta" podría bajar a 36 px, pero queda fuera de este paso. */
.ped-th { padding:8px 12px; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
.ped-td { padding:0 12px; height:44px; vertical-align:middle; }

.ped-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:8px; font-size:12px; font-weight:600; transition:background .15s,color .15s,box-shadow .15s; white-space:nowrap; }
.ped-btn-primary { background:#4f46e5; color:#fff; }
.ped-btn-primary:hover { background:#4338ca; }
.ped-btn-ghost { background:#f3f4f6; color:#4b5563; }
.ped-btn-ghost:hover { background:#e5e7eb; }

.ped-kpi { display:flex; flex-direction:column; align-items:flex-start; gap:2px; padding:8px 12px; background:#fff; border:1px solid #e5e7eb; border-radius:10px; text-align:left; transition:border-color .15s, box-shadow .15s; }
.ped-kpi:hover { border-color:#c7d2fe; }
.ped-kpi.is-active { border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.10); }
.ped-kpi-lbl { font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.04em; }
.ped-kpi-val { font-size:17px; font-weight:800; line-height:1.1; }

.pf-select { font-size:11px; padding:5px 8px; border:1px solid #e5e7eb; border-radius:8px; background:#fff; color:#4b5563; max-width:170px; flex-shrink:0; }
.pf-select:focus { outline:none; border-color:#818cf8; box-shadow:0 0 0 3px rgba(99,102,241,.10); }

/* Estado heredado (status='pagado' en 3 pedidos): se muestra tal cual y
   marcado, en vez de esconderlo bajo una etiqueta que no le corresponde. */
.status-pill.s-legacy { background:#f3f4f6; color:#6b7280; border:1px dashed #d1d5db; }
.status-pill.s-paid    { background:#dcfce7; color:#166534; }
.status-pill.s-partial { background:#fef3c7; color:#92400e; }
.status-pill.s-refunded,.status-pill.s-rejected { background:#fee2e2; color:#991b1b; }

/* ══ Progreso del pedido ══
   Pasado con marca, actual destacado, futuro neutro. */
.ped-steps { display:flex; flex-direction:column; gap:2px; list-style:none; margin:0; padding:0; }
.ped-step-btn { display:flex; align-items:center; gap:10px; width:100%; padding:7px 8px; border-radius:8px; text-align:left; font-size:12px; transition:background .15s; }
.ped-step-dot { display:flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:99px; border:2px solid #d1d5db; background:#fff; flex-shrink:0; }
.ped-step-lbl { font-weight:600; color:#9ca3af; }
.ped-step.es-pasada .ped-step-dot { background:#10b981; border-color:#10b981; color:#fff; }
.ped-step.es-pasada .ped-step-lbl { color:#4b5563; }
.ped-step.es-actual .ped-step-dot { border-color:#4f46e5; box-shadow:0 0 0 3px rgba(79,70,229,.15); }
.ped-step.es-actual .ped-step-lbl { color:#312e81; font-weight:800; }
.ped-step.es-actual .ped-step-btn { background:#eef2ff; }
button.ped-step-btn:hover { background:#f3f4f6; cursor:pointer; }
div.ped-step-btn { cursor:default; }

/* Fila: hover sutil y marca clara cuando su cajón está abierto. */
tbody tr.is-selected { background:#eef2ff; box-shadow:inset 3px 0 0 #4f46e5; }

/* ══ Cajón de detalle ══
   Superpuesto, no columna: la tabla conserva el ancho completo. */
#ped-drawer {
    position:fixed; top:var(--topbar-h,52px); right:0; bottom:0;
    z-index:70; width:clamp(560px,42vw,680px); max-width:100vw;
    background:#fff; border-left:1px solid #e5e7eb;
    box-shadow:-8px 0 24px rgba(15,23,42,.10);
    display:flex; flex-direction:column;
    transition:transform .22s ease;
}
#ped-drawer.hidden-panel { transform:translateX(100%); box-shadow:none; pointer-events:none; }
.ped-drawer-top { flex-shrink:0; display:flex; align-items:center; justify-content:space-between; gap:8px; padding:10px 16px; border-bottom:1px solid #e5e7eb; }
.ped-drawer-body { flex:1; overflow-y:auto; padding:16px; background:#f9fafb; }

#ped-overlay { position:fixed; top:var(--topbar-h,52px); left:0; right:0; bottom:0; z-index:69; background:rgba(15,23,42,.28); }

/* NO 100vw: con anclaje a la derecha, 100vw mete el borde izquierdo debajo del
   sidebar y le recorta las primeras letras a cada línea. Lección del Paso 1. */
@media (max-width:768px) {
    #ped-drawer { width:calc(100vw - var(--sidebar-w,56px)); }
}
@media (prefers-reduced-motion:reduce) {
    #ped-drawer { transition:none; }
}
</style>
<div class="flex flex-col flex-1 overflow-hidden bg-gray-50" x-init="initPedidos()" x-data="{
    @php
        // Copia unica desde el controlador: antes este bloque recomputaba
        // supportsFlow() por su cuenta y el drawer mostraba la rama de flujo
        // aunque la capacidad estuviera apagada. `conFlujo` sustituye al
        // nombre ambiguo `esLavanderia` (retail no es lavanderia).
        $conFlujo  = !empty($flujoOperativo);
        $lavStates = $flujoOperativo;
    @endphp
    conFlujo: {{ $conFlujo ? 'true' : 'false' }},
    lavStates: {{ Js::from(collect($lavStates)->map(fn($s) => [
        'key'=>$s['key'], 'label'=>$s['label'], 'icon'=>$s['icon'], 'color'=>$s['color'],
    ])->values()) }},
    orders: {{ Js::from($orders->map(function($o) use ($project, $conFlujo) {
        $sla = $conFlujo && $o->laundry_status ? \App\Support\OrderFlow::slaStatus($project, $o) : null;
        return [
        'id'             => $o->id,
        'tag_code'       => $o->tag_code ?? '',
        'client_name'    => $o->client_name,
        'client_phone'   => $o->client_phone,
        'status'         => $o->status,
        'laundry_status' => $o->laundry_status ?? '',
        'pieces_count'   => $o->pieces_count ?? 0,
        'sla_level'      => $sla['level'] ?? '',
        'sla_minutes'    => $sla['minutes'] ?? 0,
        // String canonico (UX2): un float aqui reintroduce binario en el
        // importe antes de pintarlo (mismo contrato que Cotizaciones F1c).
        'total'          => \App\Support\LineMath::canon((string) $o->total),
        'notes'          => $o->notes,
        'payment_method' => $o->payment_method ?? '',
        'sales_channel'  => $o->sales_channel ?? '',
        'created_at'     => $o->created_at->format('d/m/Y H:i'),
        'created_ts'     => $o->created_at->timestamp,
        'items_count'    => $o->items->count(),
        // Origen del pedido (FK poblada en F1b): permite mostrar
        // "Originada en COT-x" y cerrar el circulo de trazabilidad.
        'quote_id'       => $o->quote_id,
        // price/discount como string canonico (UX2, mismo contrato F1c);
        // quantity entero: no es dinero.
        'items'          => $o->items->map(fn($i) => ['name'=>$i->name,'price'=>\App\Support\LineMath::canon((string)$i->price),'quantity'=>(int)$i->quantity,'discount'=>\App\Support\LineMath::canon((string)($i->discount ?? 0))])->values(),
        'wa_number'      => $o->wa_number ?? '',
        'wa_status'      => $o->wa_status ?? '',
        'delivery_address'=> $o->delivery_address ?? '',
        'shipping_cost'  => (float)($o->shipping_cost ?? 0),
        'payment_status' => $o->payment_status ?: 'pending',
        'pill_comercial' => \App\Support\OrderStatus::comercialPresentacion($o->status),
        'pill_pago'      => \App\Support\OrderStatus::pagoPresentacion($o->payment_status),
        'debe'           => \App\Support\OrderStatus::debe($o->status, $o->payment_status),
        'comercial_key'  => \App\Support\OrderStatus::comercialCanonico($o->status),
        'pago_key'       => \App\Support\OrderStatus::pago($o->payment_status),
        'created_at_full'=> $o->created_at->format('d/m/Y H:i'),
        'updated_ts'     => $o->updated_at ? $o->updated_at->timestamp : $o->created_at->timestamp,
        'responsable'    => $o->delivery_person_name ?: ($o->created_by ? ('Usuario #'.$o->created_by) : ''),
        'payment_proof'  => $o->payment_proof ? (str_starts_with($o->payment_proof,'http') ? $o->payment_proof : str_replace('http://','https://',asset('storage/'.$o->payment_proof))) : null,
    ]; })) }},
    paymentMethods:    {{ Js::from($paymentMethods) }},
    paymentConditions: {{ Js::from($paymentConditions) }},
    salesChannels:     {{ Js::from($salesChannels) }},
    kpis: {{ Js::from($kpis ?? []) }},
    payModal: false,
    payForm: { status:'paid', method:'', amount:'', reference:'' },
    cobros: [],
    waOpen: false,
    events: [],

    /* Dinero exacto (UX2): centavos BigInt + separadores sobre el string,
       sin pasar por float (mismo contrato que Cotizaciones F1c). */
    odCentsDe(txt) {
        const m = /^(-?)(\d+)(?:\.(\d{1,2}))?$/.exec(String(txt ?? '').trim());
        if (!m) return 0n;
        const v = BigInt(m[2]) * 100n + BigInt((m[3] || '').padEnd(2, '0'));
        return m[1] === '-' ? -v : v;
    },
    odPresent(exacto) {
        const m = /^(-?)(\d+)\.(\d{2})$/.exec(String(exacto));
        if (!m) return String(exacto);
        return m[1] + m[2].replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '.' + m[3];
    },
    money(v){
        const c = typeof v === 'bigint' ? v : this.odCentsDe(v);
        const neg = c < 0n ? '-' : ''; const a = c < 0n ? -c : c;
        return 'S/ ' + neg + this.odPresent((a / 100n) + '.' + String(a % 100n).padStart(2, '0'));
    },
    isPendingPay(o){ return !!o.debe; },
    async registerPay(){
        if(!this.selected) return;
        const res = await fetch(`{{ $ordersApiBase }}/${this.selected.id}/pay`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},
            body: JSON.stringify({ status:this.payForm.status, method:this.payForm.method||null, amount:this.payForm.amount||null, reference:this.payForm.reference||null }) });
        // El estado y el adelanto los DERIVA el servidor desde el libro de
        // cobros, asi que se toman de su respuesta en vez de suponerlos: antes
        // se pintaba el estado sin esperar y la pantalla podia mentir si el
        // servidor rechazaba.
        let data = null; try { data = await res.json(); } catch(e) {}
        if(res.ok){
            const srv = data?.order || {};
            this.selected.payment_status = srv.payment_status ?? this.payForm.status;
            this.selected.advance_amount = srv.advance_amount ?? this.selected.advance_amount;
            if(this.payForm.method) this.selected.payment_method = this.payForm.method;
            this.cobros = data?.cobros || [];
            const o = this.orders.find(x=>x.id===this.selected.id);
            if(o){ o.payment_status = this.selected.payment_status; o.debe = this.selected.payment_status!=='paid'; }
            this.payModal=false; this.loadEvents();
        } else {
            // 422 de validacion trae errors.amount; el del libro trae message.
            const msg = data?.errors?.amount?.[0] || data?.message || 'No se pudo registrar el pago.';
            alert(msg);
        }
    },
    async loadEvents(){
        if(!this.selected){ this.events=[]; return; }
        try{ const r = await fetch(`{{ $ordersApiBase }}/${this.selected.id}/events`, {headers:{'Accept':'application/json'}}); const d = await r.json(); this.events = d.events||[]; }catch(e){ this.events=[]; }
    },
    waPhone(o){ let n=(o.client_phone||o.wa_number||'').replace(/\D/g,''); if(n.length===9) n='51'+n; return n; },
    waTemplates(o){
        const store = {{ Js::from($project->name) }};
        const items = (o.items||[]).map(i=>`- ${i.quantity} x ${i.name}`).join('%0A');
        const total = this.money(o.total);
        return [
            { key:'confirmacion', label:'Confirmación del pedido', text:`Hola ${o.client_name} 👋, confirmamos tu pedido #${o.id} en ${store}:%0A${items}%0ATotal: ${total}. ¡Gracias por tu compra!` },
            { key:'solicitud_pago', label:'Solicitud de pago', text:`Hola ${o.client_name}, tu pedido #${o.id} en ${store} está listo para ser confirmado. Total a pagar: ${total}. ¿Te enviamos los datos de pago?` },
            { key:'confirmacion_pago', label:'Confirmación de pago', text:`Hola ${o.client_name}, ¡recibimos tu pago de tu pedido #${o.id} en ${store}! Ya estamos preparándolo. Te avisamos cuando esté listo. 🙌` },
            { key:'pedido_listo', label:'Pedido listo', text:`Hola ${o.client_name} 🎉, tu pedido #${o.id} de ${store} ya está listo. Coordinemos la entrega o el recojo cuando gustes.` },
            { key:'estado', label:'Estado del pedido', text:`Hola ${o.client_name}, te contamos que tu pedido #${o.id} en ${store} está en proceso. Cualquier consulta, por aquí. 😊` },
        ];
    },
    async sendWa(o, tpl){
        const phone = this.waPhone(o);
        if(!phone){ alert('El pedido no tiene teléfono.'); return; }
        window.open(`https://wa.me/${phone}?text=${tpl.text}`, '_blank');
        this.waOpen=false;
        try{ await fetch(`{{ $ordersApiBase }}/${o.id}/wa-sent`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}, body: JSON.stringify({template:tpl.key,to:phone}) }); this.loadEvents(); }catch(e){}
    },

    search: '',
    selected: null,
    saving: false,

    statuses: {
        pending:   { label:'Nuevo',          cls:'s-pending'  },
        process:   { label:'En proceso',     cls:'s-process'  },
        done:      { label:'Completado',     cls:'s-done'     },
        cancelled: { label:'Cancelado',      cls:'s-cancelled' },
    },

    // ── Lavandería: helpers de estado (leen del flujo configurado en admin) ──
    lavState(o) {
        return this.lavStates.find(s => s.key === o.laundry_status) || null;
    },
    lavLabel(o) {
        const s = this.lavState(o);
        return s ? s.label : (this.statuses[o.status]||{}).label || o.status;
    },
    lavColor(o) {
        const s = this.lavState(o);
        return s ? s.color : '#6b7280';
    },
    slaColor(o) {
        return o.sla_level === 'over' ? '#EF4444' : (o.sla_level === 'warn' ? '#F59E0B' : '#10B981');
    },
    lavNext(o) {
        const i = this.lavStates.findIndex(s => s.key === o.laundry_status);
        return (i >= 0 && i < this.lavStates.length - 1) ? this.lavStates[i+1] : null;
    },
    async changeLaundry(key) {
        if (!this.selected || this.selected.laundry_status === key) return;
        await this._postLaundry(this.selected, key);
    },
    async advanceLaundry(o) {
        const next = this.lavNext(o);
        if (!next) return;
        await this._postLaundry(o, next.key);
    },
    async _postLaundry(o, key) {
        const res = await fetch(`{{ $ordersApiBase }}/${o.id}/laundry-status`, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
            body: JSON.stringify({ status: key })
        });
        if (res.ok) {
            const d = await res.json();
            o.laundry_status = d.laundry_status;
            o.sla_level = 'ok'; o.sla_minutes = 0;
            // Mantener coherente el status genérico en la UI
            const map = { recibido:'pending', cotizado:'pending', entregado:'done', anulado:'cancelled' };
            o.status = map[d.laundry_status] || 'process';
        } else { alert('No se pudo cambiar el estado'); }
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

    /* Canal con etiqueta humana: 'cotizacion' cruda se veia en lista y drawer. */
    chLabel(ch) {
        const mapa = { whatsapp:'WhatsApp', cotizacion:'Cotización', pos:'POS',
                       web:'Web', mostrador:'Mostrador', manual:'Manual', tienda:'Tienda' };
        if (!ch) return '';
        return mapa[ch] || (ch.charAt(0).toUpperCase() + ch.slice(1));
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
        // En móvil el detalle abre como pantalla completa: siempre desde arriba
        setTimeout(() => document.querySelector('.ped-drawer-body')?.scrollTo({top:0}), 50);
        this.refreshSelected();
        this.loadEvents();
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
            // El API devuelve fechas ISO crudas; sin esto pisan la fecha ya
            // formateada de la lista y el detalle muestra 2026-08-14T21:41:15Z.
            delete o.created_at; delete o.updated_at;
            const idx = this.orders.findIndex(x => x.id === o.id);
            if (idx > -1) this.orders[idx] = {...this.orders[idx], ...o};
            this.selected = {...this.selected, ...o};
            // Cobros del pedido: sin esto el historial solo lo veria quien
            // acaba de registrar uno, no quien abre el pedido despues.
            this.cobros = data.cobros || [];
        } catch(e) {}
    },



    async save() {
        this.saving = true;
        const base   = '{{ $ordersApiBase }}';
        // El alta vive en el Paso 4: aqui solo se edita el pedido abierto.
        const url    = base + '/' + this.selected.id;
        const method = 'PUT';
        const body   = { status: this.form.status, notes: this.form.notes, payment_method: this.form.payment_method };
        const res  = await fetch(url, { method, headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify(body) });
        const data = await res.json();
        {
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
        this.selected = null;
    },

    waStatusLabel: {
        pending:          { label:'Pago pendiente',    color:'text-yellow-600 bg-yellow-50', icon:'⏳' },
        pago_recibido:    { label:'Pago recibido',     color:'text-blue-600 bg-blue-50',     icon:'' },
        pago_confirmado:  { label:'Pago confirmado',   color:'text-indigo-600 bg-indigo-50', icon:'' },
        preparando:       { label:'Preparando',        color:'text-orange-600 bg-orange-50', icon:'' },
        en_camino:        { label:'En camino',         color:'text-purple-600 bg-purple-50', icon:'' },
        entregado:        { label:'Entregado',         color:'text-green-600 bg-green-50',   icon:'' },
        entregado_espera: { label:'Esperando cliente', color:'text-teal-600 bg-teal-50',     icon:'' },
        problema:         { label:'Problema',          color:'text-red-600 bg-red-50',       icon:'' },
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
        if (ws==='pending'||ws==='pago_recibido') return [{ key:'confirmar_pago', label:'Confirmar pago', cls:'bg-indigo-600 text-white' }];
        if (ws==='pago_confirmado'||ws==='preparando') return [{ key:'en_camino', label:'En camino', cls:'bg-indigo-600 text-white' }];
        if (ws==='en_camino') return [{ key:'entregado', label:'Entregado', cls:'bg-green-600 text-white' }];
        return [];
    },

    // ══ Filtros combinables (AND) ══════════════════════════════════════════
    // Un solo objeto de filtros. Las vistas rápidas y los KPIs escriben AQUÍ,
    // no en un sistema paralelo: antes convivían `tab`, `filterStatus` y los
    // KPIs del servidor con criterios distintos que no cuadraban entre sí.
    f: { comercial:'', pago:'', operacion:'', canal:'', fecha:'', responsable:'' },
    vistaActiva: 'todos',
    hojaFiltros: false,
    puede: {{ Js::from($puede ?? []) }},
    filaOrigen: null,

    vistas: [
        { key:'todos',      label:'Todos' },
        { key:'por_cobrar', label:'Por cobrar' },
        { key:'atrasados',  label:'Atrasados' },
        { key:'hoy',        label:'Hoy' },
        { key:'preparacion',label:'En proceso' },      // filtro comercial status=process
        { key:'listos',     label:'Completados' },    // filtro comercial status=done
    ],

    aplicarVista(key) {
        this.limpiarFiltros(false);
        this.vistaActiva = key;
        if (key === 'por_cobrar')       this.f.pago = 'pending';
        else if (key === 'nuevos')      this.f.comercial = 'pending';
        else if (key === 'preparacion') this.f.comercial = 'process';
        else if (key === 'listos')      this.f.comercial = 'done';
        else if (key === 'hoy')         this.f.fecha = 'hoy';
        // 'atrasados' no es un valor de campo sino una regla temporal; se
        // resuelve en filtered() leyendo vistaActiva.
    },

    limpiarFiltros(resetVista = true) {
        this.f = { comercial:'', pago:'', operacion:'', canal:'', fecha:'', responsable:'' };
        this.search = '';
        if (resetVista) this.vistaActiva = 'todos';
    },

    get filtrosActivos() {
        return Object.values(this.f).filter(v => v !== '').length;
    },

    get responsables() {
        return [...new Set(this.orders.map(o => o.responsable).filter(Boolean))].sort();
    },

    get filtered() {
        const q     = (this.search || '').toLowerCase().trim();
        const ahora = Math.floor(Date.now() / 1000);

        return this.orders.filter(o => {
            if (q) {
                const enTexto = (o.client_name || '').toLowerCase().includes(q)
                    || String(o.id).includes(q)
                    || (o.tag_code || '').toLowerCase().includes(q)
                    || (o.client_phone || '').includes(q);
                if (!enTexto) return false;
            }
            if (this.f.comercial   && o.comercial_key !== this.f.comercial) return false;
            if (this.f.pago        && o.pago_key      !== this.f.pago)      return false;
            if (this.f.canal       && o.sales_channel !== this.f.canal)     return false;
            if (this.f.responsable && o.responsable   !== this.f.responsable) return false;
            if (this.f.operacion   && (o.laundry_status || '') !== this.f.operacion) return false;

            if (this.f.fecha) {
                const dias = this.f.fecha === 'hoy' ? 1 : Number(this.f.fecha);
                if ((ahora - o.created_ts) > dias * 86400) return false;
            }

            // Mismo criterio que el KPI del servidor: activo y sin tocar +48 h.
            if (this.vistaActiva === 'atrasados') {
                const activo = !['done','cancelled'].includes(o.comercial_key);
                if (!activo || (ahora - o.updated_ts) < 172800) return false;
            }
            return true;
        });
    },

    // ══ Presentación: los tres estados, separados ══════════════════════════
    // Etiqueta y color vienen calculados por OrderStatus en el servidor, para
    // no repetir aquí el mapa de sinónimos (pagado → paid).
    claseEtapa(key) {
        if (!this.selected) return 'es-futura';
        const orden = Object.keys(this.statuses);
        const i = orden.indexOf(key), actual = orden.indexOf(this.selected.status);
        if (actual < 0) return 'es-futura';
        return i < actual ? 'es-pasada' : (i === actual ? 'es-actual' : 'es-futura');
    },
    pillComercial(o){ return o.pill_comercial || { label:'—', cls:'s-legacy' }; },
    pillPago(o){ return o.pill_pago || { label:'Debe', cls:'s-pending' }; },
    estadoOperativo(o){
        const e = this.lavStates.find(s => s.key === o.laundry_status);
        // Sin emoji delante (DoD): el label ya es explicito y la pill da color.
        return e ? e.label : '';
    },
    responsable(o){ return o.responsable || '—'; },

    // ══ Drawer enlazable ═══════════════════════════════════════════════════
    // La URL es el estado. Abrir empuja /pedidos/{id} al historial y cerrar
    // vuelve atrás, así Atrás/Adelante del navegador funcionan solos. Los
    // filtros y el scroll no se tocan: viven en Alpine, no en el DOM.
    abrirPedido(o, el) {
        this.filaOrigen = el || null;
        this.select(o);
        const url = '{{ $ordersApiBase }}/' + o.id;
        if (window.location.pathname !== url) history.pushState({ pedido:o.id }, '', url);
    },

    cerrarPedido() {
        this.selected = null;
        const base = '{{ $ordersApiBase }}';
        if (window.location.pathname !== base) {
            // back() si el drawer lo abrió esta misma sesión de navegación;
            // replaceState si se entró directo por URL, para no salir del sitio.
            if (history.state && history.state.pedido) history.back();
            else history.replaceState({}, '', base);
        }
    },

    initPedidos() {
        window.addEventListener('popstate', (e) => {
            const id = e.state && e.state.pedido;
            if (id) { const o = this.orders.find(x => x.id === id); if (o) this.select(o); }
            else { this.selected = null; }
        });

        // Entrada directa a /pedidos/{id}: el listado ya está pintado y el
        // pedido se abre después, conservando filtros y posición.
        @if (!empty($pedidoInicial))
            this.$nextTick(() => {
                const o = this.orders.find(x => x.id === {{ (int) $pedidoInicial }});
                if (o) { this.select(o); history.replaceState({ pedido:o.id }, '', window.location.pathname); }
            });
        @endif
    },
}">

{{-- ══ Cabecera ═══════════════════════════════════════════════════════════ --}}
<div class="flex-shrink-0 px-4 sm:px-6 py-3 border-b border-gray-200 bg-white flex items-center justify-between gap-3 flex-wrap">
    <div class="min-w-0">
        <h1 class="text-base sm:text-lg font-bold text-gray-900">Pedidos</h1>
        <p class="text-[11px] text-gray-500 mt-0.5 hidden sm:block">Centro operativo de pedidos</p>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        @if ($puede['ver'] ?? false)
            <a href="{{ $isSales ? route('bixosales.pedidos') : route('orders') }}-export"
               class="ped-btn ped-btn-ghost"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0 4-4m-4 4-4-4M4 19h16"/></svg>Exportar</a>
        @endif
        {{-- Paso 4 aún no existe: en lugar de un CTA hacia una ruta inexistente,
             apunta a Venta Express, que ya está en producción y protegida. --}}
        @if ($puede['crear'] ?? false)
            <a href="{{ $isSales ? route('bixosales.ventas.express') : route('ventas.express') }}"
               class="ped-btn ped-btn-primary"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>Nuevo pedido</a>
        @endif
    </div>
</div>

{{-- ══ KPIs ═══════════════════════════════════════════════════════════════ --}}
{{-- Clicables: aplican la MISMA vista rápida que la barra, no un filtrado
     alternativo. Calculados solo en servidor (OrderController@index). --}}
<div class="flex-shrink-0 grid grid-cols-2 lg:grid-cols-4 gap-2 px-4 sm:px-6 py-3 bg-gray-50 border-b border-gray-200">
    {{-- La cobranza vive en Cuentas por Cobrar: aqui el primer KPI es
         comercial. "Por cobrar" sobrevive solo como vista rapida (filtro). --}}
    <button @click="aplicarVista('nuevos')" class="ped-kpi" :class="vistaActiva==='nuevos' && 'is-active'">
        <span class="ped-kpi-lbl">Nuevos</span>
        <span class="ped-kpi-val text-amber-700">{{ $kpis['nuevos'] ?? 0 }}</span>
    </button>
    <button @click="aplicarVista('todos')" class="ped-kpi" :class="vistaActiva==='todos' && 'is-active'">
        <span class="ped-kpi-lbl">Activos</span>
        <span class="ped-kpi-val text-blue-600">{{ $kpis['activos'] ?? 0 }}</span>
    </button>
    {{-- Los KPIs son SIEMPRE comerciales; no se simula Operacion con el
         status comercial. Las metricas operativas llegaran con la evolucion
         propia del modulo Operacion. --}}
    <button @click="aplicarVista('listos')" class="ped-kpi" :class="vistaActiva==='listos' && 'is-active'">
        <span class="ped-kpi-lbl">Completados</span>
        <span class="ped-kpi-val text-emerald-600">{{ $kpis['completados'] ?? 0 }}</span>
    </button>
    <button @click="aplicarVista('atrasados')" class="ped-kpi" :class="vistaActiva==='atrasados' && 'is-active'">
        <span class="ped-kpi-lbl">Atrasados +48h</span>
        <span class="ped-kpi-val text-red-600">{{ $kpis['atrasados'] ?? 0 }}</span>
    </button>
</div>

@include('orders._filtros')

{{-- ══ Listado a ancho completo ═══════════════════════════════════════════ --}}
<div class="flex flex-col flex-1 overflow-hidden bg-white">
    @include('orders._fila')
</div>

@include('orders._drawer')
</div>

{{-- ── Exportación: nota de pedido (PDF/imagen) y ticket 58mm ─────────────────
     El pedido seleccionado se lee del estado Alpine del contenedor principal.
     Mismo patrón probado en Cotizaciones: HTML off-screen → html2canvas → jsPDF. --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function odLineCents(price, qty, disc) {
    const rx = /^\d+(\.\d{1,2})?$/;
    const p = String(price ?? '0'), d = String(disc ?? '0');
    if (!rx.test(p) || !rx.test(d)) return 0n;
    const toC = (s) => { const [e, f=''] = s.split('.'); return BigInt(e)*100n + BigInt(f.padEnd(2,'0')); };
    return (toC(p) * BigInt(Math.max(1, parseInt(qty)||1)) * (10000n - toC(d)) + 5000n) / 10000n;
}
function odLineFmt(c) { return (c/100n) + '.' + String(c%100n).padStart(2,'0'); }

function currentOrder() {
    const root = document.querySelector('[x-data]');
    const al = root ? (root.__x?.$data ?? window.Alpine?.$data(root)) : null;
    return al?.selected || null;
}

function orderPayLabel(o) {
    return ({pending:'PAGO PENDIENTE', partial:'ADELANTO RECIBIDO', paid:'PAGADO', rejected:'PAGO RECHAZADO', refunded:'REEMBOLSADO'})[o.payment_status||'pending'] || 'PAGO PENDIENTE';
}

function buildOrderDocHtml() {
    const o = currentOrder();
    if (!o) return null;
    const biz  = {{ Js::from($project->name) }};
    const ruc  = {{ Js::from($project->setting('ruc') ?? '') }};
    const tel  = {{ Js::from($project->phone ?? '') }};
    const dir  = {{ Js::from($project->address ?? '') }};
    const centsDe = t => { const m = /^(-?)(\d+)(?:\.(\d{1,2}))?$/.exec(String(t ?? '').trim()); if (!m) return 0n; const v = BigInt(m[2]) * 100n + BigInt((m[3] || '').padEnd(2, '0')); return m[1] === '-' ? -v : v; };
    const fmt = n => { const c = typeof n === 'bigint' ? n : centsDe(n); const neg = c < 0n ? '-' : ''; const a = c < 0n ? -c : c; const ent = String(a / 100n).replace(/\B(?=(\d{3})+(?!\d))/g, ','); return 'S/ ' + neg + ent + '.' + String(a % 100n).padStart(2, '0'); };
    const paid = (o.payment_status||'pending') === 'paid';
    const items = (o.items||[]);
    const envio = Number(o.shipping_cost||0);
    const rows = items.map(i => `<tr>
        <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;font-size:13px">${i.name}</td>
        <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:center;font-size:13px">${i.quantity}</td>
        <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:right;font-size:13px">${fmt(i.price)}</td>
        <td style="padding:8px 10px;border-bottom:1px solid #f3f4f6;text-align:right;font-size:13px;font-weight:600">${fmt(odLineCents(i.price, i.quantity, i.discount))}</td>
    </tr>`).join('');
    return `<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>body{font-family:Arial,sans-serif;margin:0;padding:32px;color:#111827;background:#fff}*{box-sizing:border-box}</style></head><body>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:22px;padding-bottom:14px;border-bottom:2px solid #4f46e5">
        <div>
            <h2 style="margin:0;font-size:19px">${biz}</h2>
            ${ruc ? `<div style="font-size:11px;color:#6b7280">RUC ${ruc}</div>` : ''}
            ${dir ? `<div style="font-size:11px;color:#6b7280">${dir}</div>` : ''}
            ${tel ? `<div style="font-size:11px;color:#6b7280">Tel: ${tel}</div>` : ''}
        </div>
        <div style="text-align:right">
            <div style="font-size:20px;font-weight:800;color:#4f46e5">NOTA DE PEDIDO</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px">#${String(o.id).padStart(6,'0')} · ${o.created_at||''}</div>
            <div style="display:inline-block;margin-top:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:800;${paid ? 'background:#dcfce7;color:#15803d' : 'background:#fef3c7;color:#b45309'}">${orderPayLabel(o)}</div>
        </div>
    </div>
    <div style="background:#f9fafb;border-radius:8px;padding:12px 14px;margin-bottom:20px">
        <div style="font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:5px">Cliente</div>
        <div style="font-size:14px;font-weight:600">${o.client_name||''}</div>
        ${o.client_phone ? `<div style="font-size:12px;color:#6b7280">Tel: ${o.client_phone}</div>` : ''}
        ${o.delivery_address ? `<div style="font-size:12px;color:#6b7280">Entrega: ${o.delivery_address}</div>` : ''}
    </div>
    <table style="width:100%;border-collapse:collapse;margin-bottom:14px">
        <thead><tr style="background:#4f46e5">
            <th style="padding:9px 10px;text-align:left;color:#fff;font-size:11px;font-weight:600;border-radius:6px 0 0 0">Descripción</th>
            <th style="padding:9px 10px;text-align:center;color:#fff;font-size:11px;font-weight:600">Cant.</th>
            <th style="padding:9px 10px;text-align:right;color:#fff;font-size:11px;font-weight:600">Precio</th>
            <th style="padding:9px 10px;text-align:right;color:#fff;font-size:11px;font-weight:600;border-radius:0 6px 0 0">Importe</th>
        </tr></thead>
        <tbody>${rows}</tbody>
    </table>
    <div style="display:flex;justify-content:flex-end">
        <div style="min-width:220px">
            ${envio > 0 ? `<div style="display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#6b7280"><span>Envío</span><span>${fmt(envio)}</span></div>` : ''}
            ${o.payment_method ? `<div style="display:flex;justify-content:space-between;padding:3px 0;font-size:12px;color:#6b7280"><span>Método de pago</span><span>${o.payment_method}</span></div>` : ''}
            <div style="display:flex;justify-content:space-between;padding:9px 0 3px;font-size:18px;font-weight:800;border-top:2px solid #4f46e5;margin-top:5px;color:#4f46e5"><span>Total</span><span>${fmt(o.total)}</span></div>
        </div>
    </div>
    ${o.notes ? `<div style="margin-top:16px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400e"><b>Notas:</b> ${o.notes}</div>` : ''}
    <div style="text-align:center;font-size:9px;color:#9ca3af;margin-top:26px;padding-top:10px;border-top:1px dashed #d1d5db">
        Documento referencial — no es un comprobante de pago electrónico.<br>¡Gracias por su compra!
    </div>
    </body></html>`;
}

function buildOrderTicketHtml() {
    const o = currentOrder();
    if (!o) return null;
    const biz = {{ Js::from($project->name) }};
    const ruc = {{ Js::from($project->setting('ruc') ?? '') }};
    const centsDe = t => { const m = /^(-?)(\d+)(?:\.(\d{1,2}))?$/.exec(String(t ?? '').trim()); if (!m) return 0n; const v = BigInt(m[2]) * 100n + BigInt((m[3] || '').padEnd(2, '0')); return m[1] === '-' ? -v : v; };
    const fmt = n => { const c = typeof n === 'bigint' ? n : centsDe(n); const neg = c < 0n ? '-' : ''; const a = c < 0n ? -c : c; const ent = String(a / 100n).replace(/\B(?=(\d{3})+(?!\d))/g, ','); return 'S/ ' + neg + ent + '.' + String(a % 100n).padStart(2, '0'); };
    const paid = (o.payment_status||'pending') === 'paid';
    const envio = Number(o.shipping_cost||0);
    const rows = (o.items||[]).map(i => `<div style="margin-bottom:4px">
        <div>${i.name}</div>
        <div style="display:flex;justify-content:space-between;color:#444"><span>${i.quantity} x ${fmt(i.price)}${(parseFloat(i.discount)||0)>0 ? ' -'+i.discount+'%' : ''}</span><span>${fmt(odLineCents(i.price, i.quantity, i.discount))}</span></div>
    </div>`).join('');
    return `<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        @page { size: 58mm auto; margin: 2mm; }
        body{font-family:'Courier New',monospace;margin:0;padding:6px;color:#111;width:52mm;font-size:11px}
        *{box-sizing:border-box}
        .dash{border-top:1px dashed #333;margin:6px 0}
        .center{text-align:center}
    </style></head><body>
    <div class="center" style="font-weight:700;font-size:13px">${biz}</div>
    ${ruc ? `<div class="center" style="font-size:10px;color:#444">RUC ${ruc}</div>` : ''}
    <div class="center" style="font-size:10px;color:#444">${o.created_at||''} · PED-${String(o.id).padStart(6,'0')}</div>
    ${o.client_name ? `<div class="center" style="font-size:10px;margin-top:3px">${o.client_name}</div>` : ''}
    <div class="dash"></div>
    ${rows}
    <div class="dash"></div>
    ${envio > 0 ? `<div style="display:flex;justify-content:space-between"><span>Envío</span><span>${fmt(envio)}</span></div>` : ''}
    <div style="display:flex;justify-content:space-between;font-weight:700;font-size:13px"><span>TOTAL</span><span>${fmt(o.total)}</span></div>
    ${o.payment_method ? `<div style="display:flex;justify-content:space-between;font-size:10px;color:#444"><span>Pago</span><span>${o.payment_method}</span></div>` : ''}
    <div class="center" style="font-weight:700;margin-top:6px">${paid ? '*** PAGADO ***' : '* PAGO PENDIENTE *'}</div>
    <div class="center" style="font-size:9px;color:#666;margin-top:8px">Documento referencial, no es<br>comprobante de pago electrónico.</div>
    <div class="center" style="font-size:10px;margin-top:6px">¡Gracias por su compra!</div>
    </body></html>`;
}

async function renderOffscreenOrder(html, widthPx) {
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:' + widthPx + 'px;height:1px;border:none';
    document.body.appendChild(iframe);
    iframe.contentDocument.open();
    iframe.contentDocument.write(html);
    iframe.contentDocument.close();
    await new Promise(r => setTimeout(r, 600));
    const canvas = await html2canvas(iframe.contentDocument.body, {scale:2, useCORS:true, backgroundColor:'#fff', width:widthPx});
    document.body.removeChild(iframe);
    return canvas;
}

async function exportOrderPDF() {
    const html = buildOrderDocHtml();
    if (!html) { alert('No hay pedido seleccionado'); return; }
    const { jsPDF } = window.jspdf;
    const canvas = await renderOffscreenOrder(html, 800);
    const pdf = new jsPDF({orientation:'portrait', unit:'mm', format:'a4'});
    const pW = pdf.internal.pageSize.getWidth();
    const pH = pdf.internal.pageSize.getHeight();
    const imgH = pW / (canvas.width / canvas.height);
    if (imgH <= pH) {
        pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, pW, imgH);
    } else {
        let yPos = 0, remaining = canvas.height;
        while (remaining > 0) {
            const sliceH = Math.min(remaining, Math.floor(canvas.width * pH / pW));
            const sc = document.createElement('canvas');
            sc.width = canvas.width; sc.height = sliceH;
            sc.getContext('2d').drawImage(canvas, 0, yPos, canvas.width, sliceH, 0, 0, canvas.width, sliceH);
            if (yPos > 0) pdf.addPage();
            pdf.addImage(sc.toDataURL('image/png'), 'PNG', 0, 0, pW, sliceH * pW / canvas.width);
            yPos += sliceH; remaining -= sliceH;
        }
    }
    pdf.save('pedido-' + (currentOrder()?.id || 'export') + '.pdf');
}

async function exportOrderImg() {
    const html = buildOrderDocHtml();
    if (!html) { alert('No hay pedido seleccionado'); return; }
    const canvas = await renderOffscreenOrder(html, 800);
    const link = document.createElement('a');
    link.download = 'pedido-' + (currentOrder()?.id || 'export') + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function printOrderTicket() {
    const html = buildOrderTicketHtml();
    if (!html) { alert('No hay pedido seleccionado'); return; }
    const w = window.open('', '_blank', 'width=350,height=600');
    w.document.open(); w.document.write(html); w.document.close();
    // Bandera anti doble impresión: load y el timeout de respaldo pueden
    // dispararse ambos (mismo bug que hubo en el ticket de cotizaciones).
    let printed = false;
    const doPrint = () => { if (printed) return; printed = true; w.focus(); w.print(); };
    w.onload = doPrint;
    setTimeout(doPrint, 400);
}
</script>
</x-portal-layout>

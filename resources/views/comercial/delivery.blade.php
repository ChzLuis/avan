<x-portal-layout layout="comercial" :project="$project" pageTitle="Delivery">
<div x-data="deliveryPage()" x-init="init()" style="display:flex;flex-direction:column;height:100%;background:#F8F9FB;">

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;flex-shrink:0;">
    <div>
        <h1 style="font-size:16px;font-weight:700;color:#111827;margin:0;">Delivery</h1>
        <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">Pedidos a domicilio en tiempo real</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <div style="display:flex;gap:6px;">
            <span style="background:#FFFBEB;color:#D97706;font-size:11px;font-weight:600;padding:4px 10px;border-radius:99px;border:1px solid #FDE68A;"
                  x-text="pendientes + ' pendiente' + (pendientes!==1?'s':'')"></span>
            <span style="background:#EFF6FF;color:#2563EB;font-size:11px;font-weight:600;padding:4px 10px;border-radius:99px;border:1px solid #BFDBFE;"
                  x-text="enRuta + ' en ruta'"></span>
        </div>
        <button @click="abrirNuevo()"
                style="display:flex;align-items:center;gap:6px;padding:8px 16px;background:#2563EB;color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo Pedido
        </button>
    </div>
</div>

{{-- Tabs --}}
<div style="display:flex;gap:4px;padding:10px 16px;background:#fff;border-bottom:1px solid #E5E8EF;overflow-x:auto;flex-shrink:0;">
    <template x-for="tab in tabs" :key="tab.key">
        <button @click="tabActivo=tab.key"
                :style="tabActivo===tab.key
                    ? 'background:#EFF6FF;color:#2563EB;border-color:#BFDBFE;'
                    : 'background:#fff;color:#6B7280;border-color:#E5E8EF;'"
                style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;border:1px solid;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .12s;">
            <span x-text="tab.label"></span>
            <span :style="tab.badgeStyle" style="font-size:10px;padding:1px 6px;border-radius:99px;font-weight:700;"
                  x-text="countTab(tab.key)"></span>
        </button>
    </template>
</div>

{{-- Grid pedidos --}}
<div style="flex:1;overflow:auto;padding:16px;">
    <div x-show="pedidosFiltrados.length===0"
         style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:200px;color:#9CA3AF;">
        <svg style="width:40px;height:40px;margin-bottom:10px;opacity:.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10h10zM13 8h4l3 3v5h-7V8z"/>
        </svg>
        <p style="font-size:13px;">No hay pedidos en esta categoría</p>
    </div>
    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));">
        <template x-for="o in pedidosFiltrados" :key="o.id">
            <div style="background:#fff;border:1px solid #E5E8EF;border-radius:14px;overflow:hidden;transition:box-shadow .15s;"
                 onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'"
                 onmouseout="this.style.boxShadow='none'">

                {{-- Header con color de estado --}}
                <div :style="dsHeaderStyle(o.delivery_status)"
                     style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-weight:800;font-size:13px;" x-text="'#' + o.id"></span>
                        <span style="font-size:11px;font-weight:600;background:rgba(255,255,255,.25);padding:2px 8px;border-radius:99px;"
                              x-text="dsLabel(o.delivery_status)"></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:5px;font-size:11px;opacity:.85;">
                        <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="tiempoDesde(o.created_at)"></span>
                    </div>
                </div>

                {{-- Cuerpo --}}
                <div style="padding:14px;display:flex;flex-direction:column;gap:10px;">
                    {{-- Cliente --}}
                    <div>
                        <p style="font-size:14px;font-weight:700;color:#111827;" x-text="o.client_name"></p>
                        <div style="display:flex;align-items:flex-start;gap:5px;margin-top:4px;">
                            <svg style="width:13px;height:13px;color:#9CA3AF;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <span style="font-size:12px;color:#6B7280;" x-text="o.delivery_address"></span>
                        </div>
                        <div x-show="o.client_phone" style="display:flex;align-items:center;gap:5px;margin-top:3px;">
                            <svg style="width:12px;height:12px;color:#9CA3AF;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <a :href="'tel:'+o.client_phone" x-text="o.client_phone"
                               style="font-size:11px;color:#6B7280;text-decoration:none;"
                               onmouseover="this.style.color='#2563EB'" onmouseout="this.style.color='#6B7280'"></a>
                        </div>
                    </div>

                    {{-- Items --}}
                    <div style="background:#F8F9FB;border-radius:8px;padding:8px 10px;display:flex;flex-direction:column;gap:3px;">
                        <template x-for="item in o.items" :key="item.name">
                            <div style="display:flex;justify-content:space-between;font-size:12px;color:#374151;">
                                <span x-text="item.quantity + '× ' + item.name"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Total --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div>
                            <span style="font-size:18px;font-weight:800;color:#111827;" x-text="'S/ ' + o.total.toFixed(2)"></span>
                            <span x-show="o.shipping_cost>0" style="font-size:11px;color:#9CA3AF;margin-left:4px;"
                                  x-text="'+S/ '+o.shipping_cost.toFixed(2)+' delivery'"></span>
                        </div>
                    </div>

                    {{-- Repartidor --}}
                    <div x-show="o.delivery_person_name"
                         style="display:flex;align-items:center;gap:6px;background:#EFF6FF;border-radius:8px;padding:6px 10px;">
                        <svg style="width:14px;height:14px;color:#2563EB;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span style="font-size:12px;font-weight:500;color:#1E40AF;" x-text="o.delivery_person_name"></span>
                    </div>

                    {{-- Acciones --}}
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <template x-if="o.delivery_status==='pending'">
                            <button @click="asignar(o)"
                                    style="flex:1;font-size:12px;font-weight:600;background:#2563EB;color:#fff;padding:7px;border-radius:8px;border:none;cursor:pointer;">
                                Asignar repartidor
                            </button>
                        </template>
                        <template x-if="o.delivery_status==='assigned'">
                            <button @click="cambiarDs(o,'in_route')"
                                    style="flex:1;font-size:12px;font-weight:600;background:#F59E0B;color:#fff;padding:7px;border-radius:8px;border:none;cursor:pointer;">
                                Despachar
                            </button>
                        </template>
                        <template x-if="o.delivery_status==='in_route'">
                            <button @click="cambiarDs(o,'delivered')"
                                    style="flex:1;font-size:12px;font-weight:600;background:#10B981;color:#fff;padding:7px;border-radius:8px;border:none;cursor:pointer;">
                                Entregado
                            </button>
                        </template>
                        <template x-if="!['delivered','rejected'].includes(o.delivery_status)">
                            <button @click="cambiarDs(o,'rejected')"
                                    style="font-size:12px;font-weight:600;background:#FEE2E2;color:#EF4444;padding:7px 10px;border-radius:8px;border:none;cursor:pointer;">
                                Rechazar
                            </button>
                        </template>
                        <button x-show="o.client_phone" @click="verWhatsapp(o)"
                                style="font-size:12px;font-weight:600;background:#DCFCE7;color:#16A34A;padding:7px 10px;border-radius:8px;border:none;cursor:pointer;">
                            WA
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

{{-- Modal asignar repartidor --}}
<div x-show="modalAsignar" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="modalAsignar=false"></div>
    <div style="position:relative;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.15);width:100%;max-width:380px;">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E8EF;display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:15px;font-weight:700;color:#111827;">Asignar Repartidor</p>
            <button @click="modalAsignar=false" style="background:none;border:none;cursor:pointer;color:#9CA3AF;">
                <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Repartidor</label>
                <input x-model="asignarForm.nombre" type="text" placeholder="Nombre del repartidor" list="repartidores-list"
                       style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                <datalist id="repartidores-list">
                    @foreach($repartidores as $r)
                    <option value="{{ $r['name'] }}">
                    @endforeach
                </datalist>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:6px;">Notas</label>
                <textarea x-model="asignarForm.notas" rows="2" placeholder="Indicaciones especiales..."
                          style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;resize:none;font-family:inherit;"></textarea>
            </div>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #E5E8EF;display:flex;gap:8px;justify-content:flex-end;">
            <button @click="modalAsignar=false" style="padding:8px 16px;font-size:13px;color:#6B7280;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;">Cancelar</button>
            <button @click="confirmarAsignar()" style="padding:8px 20px;font-size:13px;font-weight:600;background:#2563EB;color:#fff;border:none;border-radius:8px;cursor:pointer;">Asignar</button>
        </div>
    </div>
</div>

{{-- Modal Nuevo Pedido --}}
<div x-show="modalNuevo" x-cloak style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);" @click="modalNuevo=false"></div>
    <div style="position:relative;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.15);width:100%;max-width:500px;overflow-y:auto;max-height:90vh;">
        <div style="padding:16px 20px;border-bottom:1px solid #E5E8EF;display:flex;align-items:center;justify-content:space-between;">
            <p style="font-size:15px;font-weight:700;color:#111827;">Nuevo Pedido Delivery</p>
            <button @click="modalNuevo=false" style="background:none;border:none;cursor:pointer;color:#9CA3AF;">
                <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Cliente *</label>
                    <input x-model="nuevoForm.client_name" type="text"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Teléfono</label>
                    <input x-model="nuevoForm.client_phone" type="tel"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                           onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
                </div>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Dirección *</label>
                <input x-model="nuevoForm.delivery_address" type="text"
                       style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;"
                       onfocus="this.style.borderColor='#2563EB'" onblur="this.style.borderColor='#E5E8EF'">
            </div>
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <label style="font-size:11px;font-weight:600;color:#6B7280;">Productos</label>
                    <button @click="addItem()" style="font-size:12px;color:#2563EB;background:none;border:none;cursor:pointer;font-weight:600;">+ Agregar</button>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <template x-for="(item,i) in nuevoForm.items" :key="i">
                        <div style="display:flex;gap:6px;align-items:center;">
                            <input x-model="item.name" type="text" placeholder="Producto"
                                   style="flex:1;font-size:12px;border:1px solid #E5E8EF;border-radius:7px;padding:7px 10px;outline:none;font-family:inherit;">
                            <input x-model.number="item.quantity" type="number" min="1"
                                   style="width:52px;font-size:12px;border:1px solid #E5E8EF;border-radius:7px;padding:7px;outline:none;text-align:center;font-family:inherit;">
                            <input x-model.number="item.price" type="number" min="0" step="0.01" placeholder="S/"
                                   style="width:80px;font-size:12px;border:1px solid #E5E8EF;border-radius:7px;padding:7px 10px;outline:none;font-family:inherit;">
                            <button @click="nuevoForm.items.splice(i,1)"
                                    style="color:#EF4444;background:none;border:none;cursor:pointer;">
                                <svg style="width:15px;height:15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <div style="text-align:right;font-size:13px;font-weight:600;color:#374151;margin-top:6px;">
                    Subtotal: S/ <span x-text="subtotalNuevo.toFixed(2)"></span>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Costo delivery</label>
                    <input x-model.number="nuevoForm.shipping_cost" type="number" min="0" step="0.50"
                           style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;font-family:inherit;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Método pago</label>
                    <select x-model="nuevoForm.payment_method"
                            style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;background:#fff;font-family:inherit;">
                        <option value="">-- Seleccionar --</option>
                        <option>Efectivo</option><option>Yape</option><option>Plin</option>
                        <option>Transferencia</option><option>Tarjeta</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="display:block;font-size:11px;font-weight:600;color:#6B7280;margin-bottom:5px;">Notas</label>
                <textarea x-model="nuevoForm.delivery_notes" rows="2"
                          style="width:100%;font-size:13px;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;outline:none;resize:none;font-family:inherit;"></textarea>
            </div>
            <div style="background:#EFF6FF;border-radius:10px;padding:12px;text-align:center;">
                <span style="font-size:14px;font-weight:700;color:#1E40AF;">
                    Total: S/ <span x-text="(subtotalNuevo+(nuevoForm.shipping_cost||0)).toFixed(2)"></span>
                </span>
            </div>
        </div>
        <div style="padding:14px 20px;border-top:1px solid #E5E8EF;display:flex;gap:8px;justify-content:flex-end;">
            <button @click="modalNuevo=false" style="padding:8px 16px;font-size:13px;color:#6B7280;background:none;border:1px solid #E5E8EF;border-radius:8px;cursor:pointer;">Cancelar</button>
            <button @click="crearPedido()" :disabled="creando"
                    style="padding:8px 20px;font-size:13px;font-weight:600;background:#2563EB;color:#fff;border:none;border-radius:8px;cursor:pointer;"
                    x-text="creando?'Creando...':'Crear Pedido'"></button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div x-show="toast.show" x-cloak x-transition
     :style="toast.type==='error'?'background:#EF4444;':'background:#10B981;'"
     style="position:fixed;bottom:20px;right:20px;color:#fff;font-size:13px;font-weight:500;padding:10px 18px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);z-index:9999;"
     x-text="toast.msg">
</div>

</div>

@push('scripts')
<script>
function deliveryPage() {
    return {
        pedidos: @json($ordersJson),
        tabActivo: 'activos',
        modalAsignar: false, modalNuevo: false,
        pedidoSeleccionado: null,
        asignarForm: { nombre:'', notas:'' },
        nuevoForm: { client_name:'', client_phone:'', delivery_address:'', delivery_notes:'', shipping_cost:5, payment_method:'', items:[{name:'',quantity:1,price:0}] },
        creando: false,
        toast: { show:false, msg:'', type:'ok' },

        tabs: [
            { key:'activos',   label:'Activos',    badgeStyle:'background:#FEF3C7;color:#D97706;' },
            { key:'pending',   label:'Pendientes', badgeStyle:'background:#FEF3C7;color:#D97706;' },
            { key:'assigned',  label:'Asignados',  badgeStyle:'background:#DBEAFE;color:#2563EB;' },
            { key:'in_route',  label:'En ruta',    badgeStyle:'background:#EDE9FE;color:#7C3AED;' },
            { key:'delivered', label:'Entregados', badgeStyle:'background:#D1FAE5;color:#059669;' },
        ],

        init() { setInterval(() => this.recargar(), 20000); },

        get pedidosFiltrados() {
            if (this.tabActivo==='activos') return this.pedidos.filter(o=>!['delivered','rejected'].includes(o.delivery_status));
            return this.pedidos.filter(o=>o.delivery_status===this.tabActivo);
        },
        get pendientes() { return this.pedidos.filter(o=>o.delivery_status==='pending').length; },
        get enRuta()     { return this.pedidos.filter(o=>o.delivery_status==='in_route').length; },
        countTab(key) {
            return key==='activos'
                ? this.pedidos.filter(o=>!['delivered','rejected'].includes(o.delivery_status)).length
                : this.pedidos.filter(o=>o.delivery_status===key).length;
        },

        dsLabel(s) {
            return {pending:'Pendiente',assigned:'Asignado',in_route:'En ruta',delivered:'Entregado',rejected:'Rechazado'}[s]||s;
        },
        dsHeaderStyle(s) {
            const m = {
                pending:   'background:#F59E0B;color:#fff;',
                assigned:  'background:#2563EB;color:#fff;',
                in_route:  'background:#7C3AED;color:#fff;',
                delivered: 'background:#10B981;color:#fff;',
                rejected:  'background:#9CA3AF;color:#fff;',
            };
            return m[s]||'background:#E5E8EF;color:#374151;';
        },
        tiempoDesde(iso) {
            const d=Math.floor((Date.now()-new Date(iso))/60000);
            if(d<1) return 'ahora'; if(d<60) return d+'m'; return Math.floor(d/60)+'h '+(d%60)+'m';
        },

        asignar(o) { this.pedidoSeleccionado=o; this.asignarForm={nombre:o.delivery_person_name||'',notas:''}; this.modalAsignar=true; },
        async confirmarAsignar() {
            await this.updateDs(this.pedidoSeleccionado,'assigned',{delivery_person_name:this.asignarForm.nombre,delivery_notes:this.asignarForm.notas});
            this.modalAsignar=false;
        },
        async cambiarDs(o,ds) {
            if (ds==='rejected' && ! await bxConfirmar({ descripcion: '¿Rechazar este pedido?' })) return;
            await this.updateDs(o,ds,{});
        },
        async updateDs(o,ds,extra) {
            const r=await fetch(`/bixosales/delivery/${o.id}/status`,{
                method:'PUT',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
                body:JSON.stringify({delivery_status:ds,...extra})
            });
            const d=await r.json();
            if(d.ok){ const i=this.pedidos.findIndex(x=>x.id===o.id); if(i>=0) this.pedidos[i]={...this.pedidos[i],delivery_status:ds,...extra}; this.showToast('Estado actualizado'); }
        },
        verWhatsapp(o) {
            const msg=encodeURIComponent(`Hola ${o.client_name}, tu pedido #${o.id} está en camino a ${o.delivery_address}. Total: S/ ${o.total.toFixed(2)}`);
            window.open(`https://wa.me/${o.client_phone.replace(/\D/g,'')}?text=${msg}`,'_blank');
        },
        abrirNuevo() { this.nuevoForm={client_name:'',client_phone:'',delivery_address:'',delivery_notes:'',shipping_cost:5,payment_method:'',items:[{name:'',quantity:1,price:0}]}; this.modalNuevo=true; },
        addItem() { this.nuevoForm.items.push({name:'',quantity:1,price:0}); },
        get subtotalNuevo() { return this.nuevoForm.items.reduce((s,i)=>s+(i.price||0)*(i.quantity||0),0); },
        async crearPedido() {
            if(!this.nuevoForm.client_name||!this.nuevoForm.delivery_address){ this.showToast('Completa los campos','error'); return; }
            this.creando=true;
            try {
                const r=await fetch('/bixosales/delivery',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify(this.nuevoForm)});
                const d=await r.json();
                if(!r.ok) throw new Error(d.message||'Error');
                this.pedidos.unshift({...d.order,items:this.nuevoForm.items,delivery_status:'pending'});
                this.modalNuevo=false; this.showToast('Pedido creado');
            } catch(e){ this.showToast(e.message,'error'); } finally{ this.creando=false; }
        },
        async recargar() {
            const r=await fetch('/bixosales/delivery/data'); const d=await r.json();
            if(d.orders) this.pedidos=d.orders;
        },
        showToast(msg,type='ok') { this.toast={show:true,msg,type}; setTimeout(()=>{this.toast.show=false;},3000); },
    };
}
</script>
@endpush
</x-portal-layout>

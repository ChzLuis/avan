<x-portal-layout layout="comercial" :project="$project" pageTitle="Pedidos Bot">
<div style="display:flex;height:100%;overflow:hidden;">

<div style="display:flex;flex-direction:column;flex:1;min-width:0;overflow:hidden;">

{{-- Header --}}
<div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;background:#fff;flex-shrink:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div>
            <h1 style="font-size:15px;font-weight:700;color:#111827;margin:0;">Pedidos Bot</h1>
            <p style="font-size:11px;color:#9CA3AF;margin:2px 0 0;">{{ $project->name }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:12px;font-size:12px;color:#6B7280;">
            <span>Total: <strong id="cnt-total" style="color:#111827;">{{ $ventas->count() }}</strong></span>
            <span style="color:#D97706;">Pendientes: <strong id="cnt-pend">{{ $ventas->where('status','pendiente')->count() }}</strong></span>
            <span style="color:#2563EB;">Pagados: <strong id="cnt-pago">{{ $ventas->where('status','pagado')->count() }}</strong></span>
            <span style="color:#16A34A;">Enviados: <strong id="cnt-envi">{{ $ventas->where('status','enviado')->count() }}</strong></span>
            <button onclick="location.reload()"
                    style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:16px;line-height:1;"
                    onmouseover="this.style.color='#6B7280'" onmouseout="this.style.color='#9CA3AF'">↻</button>
        </div>
    </div>
    {{-- Filtros --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        @php $hoy = now()->format('Y-m-d'); $hace30 = now()->subDays(30)->format('Y-m-d'); @endphp
        <input type="date" id="f-desde" value="{{ $hace30 }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
        <span style="color:#9CA3AF;font-size:12px;">→</span>
        <input type="date" id="f-hasta" value="{{ $hoy }}"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;font-family:inherit;">
        <select id="f-estado"
                style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 10px;outline:none;background:#fff;font-family:inherit;">
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="pagado">Pagado</option>
            <option value="enviado">Enviado</option>
            <option value="cancelado">Cancelado</option>
        </select>
        <input type="text" id="f-buscar" placeholder="Nombre, DNI o celular"
               style="font-size:12px;border:1px solid #E5E8EF;border-radius:8px;padding:6px 12px;outline:none;width:200px;font-family:inherit;">
        <button onclick="aplicarFiltros()"
                style="font-size:12px;background:#2563EB;color:#fff;padding:6px 14px;border-radius:8px;border:none;cursor:pointer;font-weight:600;">
            Buscar
        </button>
    </div>
</div>

{{-- Lista --}}
<div style="flex:1;overflow-y:auto;padding:12px;background:#F8F9FB;">
    @if($ventas->isEmpty())
    <div style="text-align:center;padding:60px 20px;color:#9CA3AF;">
        <div style="font-size:40px;margin-bottom:10px;">🎟️</div>
        <p style="font-size:13px;">Aún no hay ventas registradas</p>
    </div>
    @else
    <div style="display:flex;flex-direction:column;gap:6px;">
    @foreach($ventas as $v)
    @php
    $statusStyle = match($v->status) {
        'pendiente' => 'background:#FFFBEB;color:#D97706;border-color:#FDE68A;',
        'pagado'    => 'background:#DBEAFE;color:#2563EB;border-color:#BFDBFE;',
        'enviado'   => 'background:#DCFCE7;color:#16A34A;border-color:#BBF7D0;',
        'cancelado' => 'background:#FEE2E2;color:#EF4444;border-color:#FECACA;',
        default     => 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;',
    };
    $statusLabel = match($v->status) {
        'pendiente' => 'Pendiente',
        'pagado'    => 'Pagado',
        'enviado'   => 'Enviado ✓',
        'cancelado' => 'Cancelado',
        default     => $v->status,
    };
    @endphp
    <div style="background:#fff;border:1px solid #E5E8EF;border-radius:12px;padding:12px 14px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:border-color .12s,box-shadow .12s;"
         onclick="abrirDetalle({{ $v->id }})" id="rv-{{ $v->id }}"
         onmouseover="this.style.borderColor='#2563EB';this.style.boxShadow='0 2px 8px rgba(0,0,0,.06)'"
         onmouseout="this.style.borderColor='#E5E8EF';this.style.boxShadow='none'">

        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="font-size:13px;font-weight:700;color:#111827;">{{ $v->nombre ?? '—' }}</span>
                @if($v->dni)
                <span style="font-size:11px;color:#9CA3AF;">· DNI {{ $v->dni }}</span>
                @endif
                @if($v->ciudad)
                <span style="font-size:11px;color:#9CA3AF;">· 📍 {{ Str::limit($v->ciudad, 20) }}</span>
                @endif
                <span style="font-size:11px;color:#9CA3AF;">· 📱 +{{ preg_replace('/\D/','',$v->wa_number) }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-top:3px;flex-wrap:wrap;">
                <span style="font-size:11px;color:#7C3AED;">{{ $v->rifa?->nombre ?? $v->plan_nombre }}</span>
                <span style="font-size:11px;color:#6B7280;">{{ $v->tickets }} ticket(s)</span>
                <span style="font-size:11px;font-weight:700;color:#374151;">S/ {{ number_format($v->monto, 2) }}</span>
                <span style="font-size:11px;color:#9CA3AF;">{{ $v->created_at->timezone('America/Lima')->format('d/m H:i') }}</span>
                @if($v->status === 'enviado' && $v->ticket_code)
                <span style="font-size:11px;background:#DCFCE7;color:#16A34A;padding:2px 8px;border-radius:99px;font-family:monospace;">🎫 {{ $v->ticket_code }}</span>
                @endif
            </div>
        </div>

        <span style="font-size:11px;font-weight:600;padding:2px 10px;border-radius:99px;border:1px solid;flex-shrink:0;{{ $statusStyle }}">{{ $statusLabel }}</span>
        @if($v->payment_proof)
        <span style="font-size:12px;color:#6366F1;flex-shrink:0;">🧾</span>
        @endif
        <span style="color:#D1D5DB;flex-shrink:0;font-size:16px;">›</span>
    </div>
    @endforeach
    </div>
    @endif
</div>

</div>

{{-- Panel Bot WA --}}
<div id="bot-panel" style="position:fixed;top:52px;right:0;bottom:0;z-index:40;transition:width .2s ease;width:220px;border-left:1px solid #E5E8EF;background:#fff;display:flex;">
    <button id="bot-panel-toggle" onclick="toggleBotPanel()"
            style="width:22px;background:#F8F9FB;border-right:1px solid #E5E8EF;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;border:none;">
        <span style="writing-mode:vertical-lr;font-size:10px;font-weight:700;text-transform:uppercase;color:#6B7280;transform:rotate(180deg);letter-spacing:.06em;">BOT</span>
        <svg id="bot-chevron" style="width:12px;height:12px;transform:rotate(180deg);transition:transform .2s;margin-top:6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
    <div id="bot-panel-content" style="flex:1;display:flex;flex-direction:column;overflow:hidden;padding:12px;gap:10px;">
        <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">WhatsApp Bot</p>
        <div style="display:flex;align-items:center;gap:7px;">
            <span id="bot-dot" style="width:9px;height:9px;border-radius:50%;background:#D1D5DB;flex-shrink:0;display:inline-block;"></span>
            <span id="bot-status-text" style="font-size:11px;font-weight:600;color:#6B7280;">Verificando...</span>
        </div>
        <div id="bot-qr-wrap" style="display:flex;flex-direction:column;align-items:center;gap:8px;">
            <div id="bot-qr-spinner" style="display:flex;flex-direction:column;align-items:center;gap:5px;padding:8px 0;">
                <div style="width:22px;height:22px;border:2px solid #E5E8EF;border-top-color:#2563EB;border-radius:50%;animation:spin .8s linear infinite;"></div>
                <p style="font-size:11px;color:#9CA3AF;margin:0;">Cargando...</p>
            </div>
            <img id="bot-qr-img" src="" style="width:160px;height:160px;border-radius:12px;border:1px solid #E5E8EF;display:none;" alt="QR">
            <p id="bot-qr-hint" style="font-size:10px;color:#9CA3AF;text-align:center;line-height:1.4;display:none;">WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
            <p id="bot-connected-msg" style="font-size:11px;color:#16A34A;font-weight:600;text-align:center;display:none;">✓ WhatsApp conectado</p>
            <p id="bot-offline-msg" style="font-size:11px;color:#9CA3AF;text-align:center;display:none;">Bot no iniciado</p>
        </div>
    </div>
</div>

</div>

{{-- Modal Detalle --}}
<div id="modal-detalle" style="position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;background:rgba(0,0,0,.5);display:none;">
    <div style="width:100%;max-width:480px;background:#fff;border-radius:16px;box-shadow:0 24px 64px rgba(0,0,0,.15);margin:0 16px;max-height:90vh;overflow-y:auto;">
        <div style="padding:14px 20px;border-bottom:1px solid #E5E8EF;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;z-index:2;">
            <div>
                <span style="font-size:15px;font-weight:700;color:#111827;" id="md-titulo">Pedido</span>
                <span id="md-status-badge" style="font-size:11px;padding:2px 8px;border-radius:99px;margin-left:8px;border:1px solid;"></span>
            </div>
            <button onclick="cerrarDetalle()" style="background:none;border:none;cursor:pointer;color:#9CA3AF;font-size:18px;">✕</button>
        </div>
        <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
            <div id="md-vista">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px;">Datos del participante</p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div style="background:#F8F9FB;border-radius:10px;padding:10px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">Nombre completo</p>
                        <p style="font-size:14px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-nombre">—</p>
                    </div>
                    <div style="background:#F8F9FB;border-radius:10px;padding:10px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">DNI</p>
                        <p style="font-size:14px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-dni">—</p>
                    </div>
                    <div style="background:#F8F9FB;border-radius:10px;padding:10px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">Celular / WhatsApp</p>
                        <p style="font-size:14px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-celular">—</p>
                    </div>
                    <div style="background:#F8F9FB;border-radius:10px;padding:10px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">Ciudad / Dirección</p>
                        <p style="font-size:14px;font-weight:600;color:#111827;margin:0;user-select:all;" id="md-ciudad">—</p>
                    </div>
                </div>
                <div style="background:#F5F3FF;border-radius:10px;padding:10px;margin-top:8px;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">Plan / Producto</p>
                    <p style="font-size:14px;font-weight:600;color:#6D28D9;margin:0;user-select:all;" id="md-plan">—</p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
                    <div style="background:#F8F9FB;border-radius:10px;padding:10px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">Tickets</p>
                        <p style="font-size:14px;font-weight:600;color:#111827;margin:0;" id="md-tickets">—</p>
                    </div>
                    <div style="background:#F8F9FB;border-radius:10px;padding:10px;">
                        <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">Monto</p>
                        <p style="font-size:14px;font-weight:600;color:#111827;margin:0;" id="md-monto">—</p>
                    </div>
                </div>
                <div id="md-ticket-code-row" style="background:#DCFCE7;border-radius:10px;padding:10px;margin-top:8px;display:none;">
                    <p style="font-size:10px;color:#9CA3AF;margin:0 0 4px;">N° de Membresía</p>
                    <p style="font-size:15px;font-weight:800;color:#16A34A;margin:0;font-family:monospace;user-select:all;" id="md-ticket-code">—</p>
                </div>
                <p style="font-size:11px;color:#9CA3AF;margin:8px 0 0;" id="md-fecha">—</p>
            </div>

            {{-- Form enviar tickets --}}
            <div id="md-validar-form" style="display:none;flex-direction:column;gap:10px;border-top:1px solid #E5E8EF;padding-top:14px;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">N° de Tickets a enviar</p>
                <div id="vf-tickets-campos" style="display:flex;flex-direction:column;gap:7px;"></div>
                <p style="font-size:11px;color:#9CA3AF;margin:0;">Se enviarán al cliente por WhatsApp automáticamente</p>
                <div style="display:flex;gap:8px;">
                    <button onclick="rifaEnviarTickets()"
                            style="flex:1;font-size:13px;font-weight:600;background:#16A34A;color:#fff;padding:10px;border-radius:10px;border:none;cursor:pointer;">
                        🎟️ Enviar tickets
                    </button>
                    <button onclick="ocultarFormValidar()"
                            style="font-size:12px;color:#6B7280;background:none;border:none;cursor:pointer;padding:0 12px;">
                        Cancelar
                    </button>
                </div>
            </div>

            {{-- Form editar --}}
            <div id="md-edit" style="display:none;flex-direction:column;gap:10px;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0;">Editar datos</p>
                <div>
                    <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:4px;">Nombre completo</label>
                    <input type="text" id="ef-nombre"
                           style="width:100%;border:1px solid #E5E8EF;border-radius:10px;padding:10px 14px;font-size:13px;outline:none;font-family:inherit;">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div>
                        <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:4px;">DNI</label>
                        <input type="text" id="ef-dni"
                               style="width:100%;border:1px solid #E5E8EF;border-radius:10px;padding:10px 14px;font-size:13px;outline:none;font-family:inherit;">
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:#6B7280;margin-bottom:4px;">Ciudad</label>
                        <input type="text" id="ef-ciudad"
                               style="width:100%;border:1px solid #E5E8EF;border-radius:10px;padding:10px 14px;font-size:13px;outline:none;font-family:inherit;">
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:4px;">
                    <button onclick="guardarEdicion()"
                            style="flex:1;font-size:13px;font-weight:600;background:#2563EB;color:#fff;padding:10px;border-radius:10px;border:none;cursor:pointer;">
                        Guardar
                    </button>
                    <button onclick="cancelarEdicion()"
                            style="font-size:12px;color:#6B7280;background:none;border:none;cursor:pointer;padding:0 12px;">
                        Cancelar
                    </button>
                </div>
            </div>

            {{-- Comprobante --}}
            <div id="md-comprobante-wrap" style="display:none;">
                <p style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin:0 0 8px;">Comprobante de pago</p>
                <img id="md-comprobante-img" src="" alt="Comprobante"
                     style="width:100%;border-radius:12px;border:1px solid #E5E8EF;cursor:zoom-in;"
                     onclick="window.open(this.src,'_blank')">
                <p style="font-size:10px;color:#9CA3AF;margin:4px 0 0;text-align:center;">Toca la imagen para abrir en pantalla completa</p>
            </div>
        </div>
        <div id="md-acciones" style="position:sticky;bottom:0;background:#fff;padding:12px 20px;border-top:1px solid #E5E8EF;display:flex;flex-wrap:wrap;gap:6px;"></div>
    </div>
</div>

{{-- Toast --}}
<div id="toast" style="position:fixed;bottom:20px;right:20px;z-index:9999;display:none;">
    <div id="toast-inner" style="padding:10px 18px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);font-size:13px;font-weight:500;color:#fff;background:#374151;"></div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
const csrf = '{{ csrf_token() }}';
const ventas = @json($ventasJson);
let activeId = null;

const statusStyles = {
    pendiente: 'background:#FFFBEB;color:#D97706;border-color:#FDE68A;',
    pagado:    'background:#DBEAFE;color:#2563EB;border-color:#BFDBFE;',
    enviado:   'background:#DCFCE7;color:#16A34A;border-color:#BBF7D0;',
    cancelado: 'background:#FEE2E2;color:#EF4444;border-color:#FECACA;',
};
const statusLabels = { pendiente:'Pendiente', pagado:'Pagado', enviado:'Enviado', cancelado:'Cancelado' };

function abrirDetalle(id) {
    activeId = id;
    const v = ventas[id];
    if (!v) return;

    document.getElementById('md-titulo').innerHTML = 'Pedido #' + id;

    const badge = document.getElementById('md-status-badge');
    badge.innerHTML = statusLabels[v.status] || v.status;
    badge.style.cssText = 'font-size:11px;padding:2px 8px;border-radius:99px;margin-left:8px;border:1px solid;' + (statusStyles[v.status] || 'background:#F3F4F6;color:#6B7280;border-color:#E5E7EB;');

    document.getElementById('md-nombre').innerHTML = v.nombre || '—';
    document.getElementById('md-dni').innerHTML = v.dni || '—';
    const rawNum = (v.wa_number || '').replace(/\D/g,'');
    document.getElementById('md-celular').innerHTML = (rawNum.length >= 10 ? '+' + rawNum : rawNum) || '—';
    document.getElementById('md-ciudad').innerHTML = v.ciudad || '—';
    document.getElementById('md-plan').innerHTML = v.plan_nombre || '—';
    document.getElementById('md-tickets').innerHTML = v.tickets + ' ticket(s)';
    document.getElementById('md-monto').innerHTML = 'S/ ' + parseFloat(v.monto).toFixed(2);
    document.getElementById('md-fecha').innerHTML = v.created_at || '';

    const codeRow = document.getElementById('md-ticket-code-row');
    if (v.ticket_code && v.status === 'enviado') {
        document.getElementById('md-ticket-code').innerHTML = v.ticket_code;
        codeRow.style.display = 'block';
    } else {
        codeRow.style.display = 'none';
    }

    const compWrap = document.getElementById('md-comprobante-wrap');
    if (v.payment_proof) {
        document.getElementById('md-comprobante-img').src = v.payment_proof;
        compWrap.style.display = 'block';
    } else {
        compWrap.style.display = 'none';
    }

    document.getElementById('md-vista').style.display = 'block';
    document.getElementById('md-edit').style.display = 'none';
    document.getElementById('md-validar-form').style.display = 'none';

    const acciones = document.getElementById('md-acciones');
    acciones.innerHTML = '';
    acciones.innerHTML += `<button onclick="activarEdicion()" style="font-size:11px;background:#F3F4F6;color:#374151;padding:7px 12px;border-radius:8px;border:none;cursor:pointer;">✏️ Editar datos</button>`;
    acciones.innerHTML += `<button onclick="rifaEliminar(${id})" style="font-size:11px;background:#FEE2E2;color:#EF4444;padding:7px 12px;border-radius:8px;border:none;cursor:pointer;">🗑️ Eliminar</button>`;

    if (v.status === 'pendiente' || v.status === 'comprobante') {
        acciones.innerHTML += `<button onclick="rifaValidarSinTicket(${id})" style="flex:1;font-size:13px;font-weight:600;background:#16A34A;color:#fff;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;">✓ Validar pago</button>`;
        acciones.innerHTML += `<button onclick="rifaCancelar(${id})" style="font-size:11px;background:#FEE2E2;color:#EF4444;padding:7px 12px;border-radius:8px;border:none;cursor:pointer;">✕ Cancelar</button>`;
    }
    if (v.status === 'pagado') {
        acciones.innerHTML += `<button onclick="mostrarFormValidar()" style="flex:1;font-size:13px;font-weight:600;background:#16A34A;color:#fff;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;">🎟️ Enviar ticket</button>`;
    }
    if (v.status === 'enviado') {
        acciones.innerHTML += `<a href="/rifas/${id}/ticket-preview" target="_blank" style="font-size:11px;background:#F3F4F6;color:#374151;padding:7px 12px;border-radius:8px;text-decoration:none;">👁️ Ver boleto</a>`;
    }

    document.getElementById('modal-detalle').style.display = 'flex';
}

function mostrarFormValidar() {
    const v = ventas[activeId];
    const cantidad = parseInt(v?.tickets) || 1;
    const existentes = (v?.status === 'enviado' && v?.ticket_numbers?.length) ? v.ticket_numbers : [];
    const campos = document.getElementById('vf-tickets-campos');
    campos.innerHTML = '';
    for (let i = 0; i < cantidad; i++) {
        campos.innerHTML += `
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:11px;color:#9CA3AF;width:20px;text-align:right;">${i+1}.</span>
                <input type="text" id="vf-t-${i}" value="${existentes[i]||''}"
                       style="flex:1;border:1px solid #E5E8EF;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;font-family:inherit;"
                       placeholder="N° ticket ${i+1}" autocomplete="off">
            </div>`;
    }
    document.getElementById('md-validar-form').style.display = 'flex';
    document.getElementById('md-acciones').style.display = 'none';
    setTimeout(() => document.getElementById('vf-t-0')?.focus(), 100);
}

function ocultarFormValidar() {
    document.getElementById('md-validar-form').style.display = 'none';
    document.getElementById('md-acciones').style.display = 'flex';
}

async function rifaEnviarTickets() {
    const v = ventas[activeId];
    const cantidad = parseInt(v?.tickets) || 1;
    const numeros = [];
    for (let i = 0; i < cantidad; i++) {
        const val = document.getElementById('vf-t-' + i)?.value.trim();
        if (!val) { showToast('Completa el ticket N° ' + (i+1), 'error'); return; }
        numeros.push(val);
    }
    const id = activeId;
    const btn = document.querySelector('#md-validar-form button[onclick="rifaEnviarTickets()"]');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Enviando...'; }
    showToast('Enviando...', 'info');
    try {
        const r = await fetch(`/bixosales/pedidos-bot/${id}/enviar-membresia`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero_membresia: numeros.join(', '), ticket_numbers: numeros })
        });
        const d = await r.json();
        if (d.ok) {
            showToast('Tickets enviados ✓', 'success');
            setTimeout(() => { cerrarDetalle(); location.reload(); }, 1200);
        } else {
            showToast('Error: ' + (d.error || 'desconocido'), 'error');
            if (btn) { btn.disabled = false; btn.textContent = '🎟️ Enviar tickets'; }
        }
    } catch(e) {
        showToast('Error de conexión', 'error');
        if (btn) { btn.disabled = false; btn.textContent = '🎟️ Enviar tickets'; }
    }
}

async function rifaValidarSinTicket(id) {
    if (!confirm('¿Validar este pago? (sin comprobante)')) return;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/validar`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
    const d = await r.json();
    if (d.ok) {
        showToast('Pago validado ✓', 'success');
        setTimeout(() => { cerrarDetalle(); location.reload(); }, 1000);
    } else {
        showToast('Error al validar', 'error');
    }
}

function cerrarDetalle() {
    document.getElementById('modal-detalle').style.display = 'none';
    activeId = null;
}
document.getElementById('modal-detalle')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarDetalle();
});

function activarEdicion() {
    const v = ventas[activeId];
    document.getElementById('ef-nombre').value = v.nombre || '';
    document.getElementById('ef-dni').value = v.dni || '';
    document.getElementById('ef-ciudad').value = v.ciudad || '';
    document.getElementById('md-vista').style.display = 'none';
    document.getElementById('md-edit').style.display = 'flex';
}
function cancelarEdicion() {
    document.getElementById('md-vista').style.display = 'block';
    document.getElementById('md-edit').style.display = 'none';
}
async function guardarEdicion() {
    const id = activeId;
    const nombre = document.getElementById('ef-nombre').value;
    const dni = document.getElementById('ef-dni').value;
    const ciudad = document.getElementById('ef-ciudad').value;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/editar`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, dni, ciudad })
    });
    const d = await r.json();
    if (d.ok) {
        ventas[id].nombre = nombre; ventas[id].dni = dni; ventas[id].ciudad = ciudad;
        showToast('Datos guardados ✓', 'success');
        cancelarEdicion();
        document.getElementById('md-nombre').innerHTML = nombre || '—';
        document.getElementById('md-dni').innerHTML = dni || '—';
        document.getElementById('md-ciudad').innerHTML = ciudad || '—';
    } else {
        showToast('Error al guardar', 'error');
    }
}

async function rifaCancelar(id) {
    if (!confirm('¿Cancelar esta venta?')) return;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/cancelar`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
    const d = await r.json();
    if (d.ok) { showToast('Venta cancelada', 'error'); setTimeout(() => { cerrarDetalle(); location.reload(); }, 1000); }
    else showToast('Error al cancelar', 'error');
}

async function rifaEliminar(id) {
    if (!confirm('¿Eliminar este pedido permanentemente?')) return;
    const r = await fetch(`/bixosales/pedidos-bot/${id}/eliminar`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf } });
    const d = await r.json();
    if (d.ok) { showToast('Pedido eliminado', 'error'); cerrarDetalle(); setTimeout(() => location.reload(), 800); }
    else showToast('Error al eliminar', 'error');
}

function aplicarFiltros() {
    const desde  = document.getElementById('f-desde').value;
    const hasta  = document.getElementById('f-hasta').value;
    const estado = document.getElementById('f-estado').value;
    const buscar = document.getElementById('f-buscar').value.toLowerCase();
    document.querySelectorAll('[id^="rv-"]').forEach(row => {
        const id = row.id.replace('rv-', '');
        const v = ventas[id];
        if (!v) { row.style.display = 'none'; return; }
        const fechaParts = v.created_at ? v.created_at.split(' ')[0].split('/') : [];
        const fecha = fechaParts.length === 3 ? `${fechaParts[2]}-${fechaParts[1]}-${fechaParts[0]}` : '';
        const okFecha  = (!desde || fecha >= desde) && (!hasta || fecha <= hasta);
        const okEstado = !estado || v.status === estado;
        const okBuscar = !buscar || (v.nombre + ' ' + (v.dni||'') + ' ' + (v.wa_number||'')).toLowerCase().includes(buscar);
        row.style.display = (okFecha && okEstado && okBuscar) ? '' : 'none';
    });
}

document.getElementById('f-buscar')?.addEventListener('keypress', e => { if (e.key==='Enter') aplicarFiltros(); });
document.getElementById('f-estado')?.addEventListener('change', aplicarFiltros);
document.getElementById('f-desde')?.addEventListener('change', aplicarFiltros);
document.getElementById('f-hasta')?.addEventListener('change', aplicarFiltros);

let botPanelOpen = true;
function toggleBotPanel() {
    botPanelOpen = !botPanelOpen;
    const panel   = document.getElementById('bot-panel');
    const content = document.getElementById('bot-panel-content');
    const chevron = document.getElementById('bot-chevron');
    panel.style.width   = botPanelOpen ? '220px' : '22px';
    content.style.display = botPanelOpen ? 'flex' : 'none';
    chevron.style.transform = botPanelOpen ? 'rotate(180deg)' : 'rotate(0deg)';
}

function checkBotStatus() {
    fetch('/bot-status?bot=rifa')
        .then(r => r.json())
        .then(d => {
            const dot     = document.getElementById('bot-dot');
            const txt     = document.getElementById('bot-status-text');
            const spinner = document.getElementById('bot-qr-spinner');
            const qrImg   = document.getElementById('bot-qr-img');
            const qrHint  = document.getElementById('bot-qr-hint');
            const connMsg = document.getElementById('bot-connected-msg');
            const offMsg  = document.getElementById('bot-offline-msg');

            spinner.style.display = qrImg.style.display = qrHint.style.display =
            connMsg.style.display = offMsg.style.display = 'none';

            if (d.status === 'connected') {
                dot.style.background = '#22C55E';
                txt.innerHTML = 'Conectado ✓'; txt.style.color = '#16A34A';
                connMsg.style.display = 'block';
            } else if (d.status === 'qr' && d.qr) {
                dot.style.background = '#F59E0B';
                txt.innerHTML = 'Escanear QR'; txt.style.color = '#D97706';
                qrImg.src = d.qr; qrImg.style.display = 'block';
                qrHint.style.display = 'block';
            } else if (d.status === 'qr' || d.status === 'starting') {
                dot.style.background = '#3B82F6';
                txt.innerHTML = 'Iniciando...'; txt.style.color = '#2563EB';
                spinner.style.display = 'flex';
            } else {
                dot.style.background = '#D1D5DB';
                txt.innerHTML = 'Offline'; txt.style.color = '#9CA3AF';
                offMsg.style.display = 'block';
            }
        })
        .catch(() => {
            document.getElementById('bot-dot').style.background = '#D1D5DB';
            document.getElementById('bot-status-text').innerHTML = 'Sin conexión';
        });
}

checkBotStatus();
setInterval(checkBotStatus, 8000);

function showToast(msg, type = 'info') {
    const inner = document.getElementById('toast-inner');
    const colors = { success:'#16A34A', error:'#EF4444', info:'#374151' };
    inner.style.background = colors[type] || colors.info;
    inner.innerHTML = msg;
    document.getElementById('toast').style.display = 'block';
    setTimeout(() => { document.getElementById('toast').style.display = 'none'; }, 3000);
}
</script>

</x-portal-layout>

<x-portal-layout layout="comercial" :project="$project" pageTitle="Pedidos Bot">
<div class="flex h-full overflow-hidden">

<div class="flex flex-col flex-1 min-w-0 overflow-hidden">

<div class="px-6 py-4 border-b border-gray-200 bg-white flex-shrink-0">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h1 class="text-base font-semibold text-gray-800">Pedidos Bot</h1>
            <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-gray-500">
            <span>Total: <strong id="cnt-total">{{ $ventas->count() }}</strong></span>
            <span class="text-yellow-600">Pendientes: <strong id="cnt-pend">{{ $ventas->where('status','pendiente')->count() }}</strong></span>
            <span class="text-blue-600">Pagados: <strong id="cnt-pago">{{ $ventas->where('status','pagado')->count() }}</strong></span>
            <span class="text-green-600">Enviados: <strong id="cnt-envi">{{ $ventas->where('status','enviado')->count() }}</strong></span>
            <button onclick="location.reload()" class="ml-2 text-gray-400 hover:text-gray-600">↻</button>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        @php $hoy = now()->format('Y-m-d'); $hace30 = now()->subDays(30)->format('Y-m-d'); @endphp
        <input type="date" id="f-desde" value="{{ $hace30 }}" class="text-xs border rounded-lg px-2 py-1.5">
        <span>→</span>
        <input type="date" id="f-hasta" value="{{ $hoy }}" class="text-xs border rounded-lg px-2 py-1.5">
        <select id="f-estado" class="text-xs border rounded-lg px-2 py-1.5">
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="pagado">Pagado</option>
            <option value="enviado">Enviado</option>
            <option value="cancelado">Cancelado</option>
        </select>
        <input type="text" id="f-buscar" placeholder="Nombre, DNI o celular" class="text-xs border rounded-lg px-3 py-1.5 w-48">
        <button onclick="aplicarFiltros()" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg">Buscar</button>
    </div>
</div>

<div class="flex-1 overflow-y-auto p-4 bg-gray-50/30">
@if($ventas->isEmpty())
<div class="text-center py-20 text-gray-400">
    <div class="text-5xl mb-3">🎟️</div>
    <p class="text-sm">Aún no hay ventas registradas</p>
</div>
@else
<div class="space-y-2">
@foreach($ventas as $v)
@php
$statusClass = match($v->status) {
    'pendiente' => 'bg-yellow-100 text-yellow-700',
    'pagado'    => 'bg-blue-100 text-blue-700',
    'enviado'   => 'bg-green-100 text-green-700',
    'cancelado' => 'bg-red-100 text-red-700',
    default     => 'bg-gray-100 text-gray-500',
};
$statusLabel = match($v->status) {
    'pendiente' => 'Pendiente',
    'pagado'    => 'Pagado',
    'enviado'   => 'Enviado ✓',
    'cancelado' => 'Cancelado',
    default     => $v->status,
};
@endphp
<div class="bg-white rounded-xl border border-gray-200 px-4 py-3 flex items-center gap-3 cursor-pointer hover:border-blue-300 hover:shadow-sm transition"
     onclick="abrirDetalle({{ $v->id }})" id="rv-{{ $v->id }}">

    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-semibold text-gray-800">{{ $v->nombre ?? '—' }}</span>
            @if($v->dni)<span class="text-xs text-gray-400">· DNI {{ $v->dni }}</span>@endif
            @if($v->ciudad)<span class="text-xs text-gray-400">· 📍 {{ Str::limit($v->ciudad, 20) }}</span>@endif
            <span class="text-xs text-gray-400">· 📱 +{{ preg_replace('/\D/','',$v->wa_number) }}</span>
        </div>
        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
            <span class="text-xs text-purple-700">{{ $v->rifa?->nombre ?? $v->plan_nombre }}</span>
            <span class="text-xs text-gray-500">{{ $v->tickets }} ticket(s)</span>
            <span class="text-xs font-bold text-gray-700">S/ {{ number_format($v->monto, 2) }}</span>
            <span class="text-xs text-gray-400">{{ $v->created_at->timezone('America/Lima')->format('d/m H:i') }}</span>
            @if($v->status === 'enviado' && $v->ticket_code)
                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-mono">🎫 {{ $v->ticket_code }}</span>
            @endif
        </div>
    </div>

    <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0 {{ $statusClass }}">{{ $statusLabel }}</span>
    @if($v->payment_proof)<span class="text-xs text-indigo-500 flex-shrink-0">🧾</span>@endif
    <span class="text-gray-300 flex-shrink-0">›</span>
</div>
@endforeach
</div>
@endif
</div>

</div>

{{-- PANEL BOT WA --}}
<div id="bot-panel" style="position:fixed; top:48px; right:0; bottom:0; z-index:40; transition:width .2s ease; width:220px; border-left:1px solid #e5e7eb; background:#fff; display:flex;">
    <button id="bot-panel-toggle" onclick="toggleBotPanel()" style="width:22px; background:#f8fafc; border-right:1px solid #e5e7eb; cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center;">
        <span style="writing-mode:vertical-lr; font-size:10px; font-weight:600; text-transform:uppercase; color:#64748b; transform:rotate(180deg);">BOT</span>
        <svg id="bot-chevron" style="width:12px;height:12px;transform:rotate(180deg);transition:transform .2s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>
    <div id="bot-panel-content" class="flex-1 flex flex-col overflow-hidden p-3 gap-3">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">WhatsApp Bot</p>

        {{-- Badge de estado --}}
        <div class="flex items-center gap-2">
            <span id="bot-dot" style="width:9px;height:9px;border-radius:50%;background:#d1d5db;flex-shrink:0;display:inline-block;"></span>
            <span id="bot-status-text" class="text-xs font-medium text-gray-500">Verificando...</span>
        </div>

        {{-- QR o estado --}}
        <div id="bot-qr-wrap" class="flex flex-col items-center gap-2">
            <div id="bot-qr-spinner" class="flex flex-col items-center gap-1 py-2">
                <div class="w-6 h-6 border-2 border-gray-300 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs text-gray-400">Cargando...</p>
            </div>
            <img id="bot-qr-img" src="" class="w-40 h-40 rounded-xl border border-gray-200" style="display:none">
            <p id="bot-qr-hint" class="text-xs text-gray-400 text-center leading-snug" style="display:none">WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
            <p id="bot-connected-msg" class="text-xs text-green-600 font-medium text-center" style="display:none">✓ WhatsApp conectado</p>
            <p id="bot-offline-msg" class="text-xs text-gray-400 text-center" style="display:none">Bot no iniciado</p>
        </div>
    </div>
</div>

</div>

{{-- MODAL DETALLE --}}
<div id="modal-detalle" class="fixed inset-0 z-50 items-center justify-center bg-black/50" style="display:none">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-5 py-4 border-b flex justify-between items-center sticky top-0 bg-white">
            <div>
                <span class="font-semibold text-gray-800" id="md-titulo">Pedido</span>
                <span id="md-status-badge" class="ml-2 text-xs px-2 py-0.5 rounded-full"></span>
            </div>
            <button onclick="cerrarDetalle()" class="text-gray-400 hover:text-gray-700 text-xl">✕</button>
        </div>
        <div class="p-5 space-y-4">
            <div id="md-vista">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">DATOS DEL PARTICIPANTE</p>
                <div class="grid grid-cols-2 gap-3 mt-2">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">Nombre completo</p>
                        <p class="text-base font-semibold select-all" id="md-nombre">—</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">DNI</p>
                        <p class="text-base font-semibold select-all" id="md-dni">—</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">Celular / WhatsApp</p>
                        <p class="text-base font-semibold select-all" id="md-celular">—</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">Ciudad / Dirección</p>
                        <p class="text-base font-semibold select-all" id="md-ciudad">—</p>
                    </div>
                </div>
                <div class="bg-purple-50 rounded-xl p-3 mt-3">
                    <p class="text-xs text-gray-400 mb-1">Plan / Producto</p>
                    <p class="text-base font-semibold text-purple-800 select-all" id="md-plan">—</p>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">Tickets</p>
                        <p class="text-base font-semibold" id="md-tickets">—</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">Monto</p>
                        <p class="text-base font-semibold" id="md-monto">—</p>
                    </div>
                </div>
                <div id="md-ticket-code-row" class="hidden bg-green-50 rounded-xl p-3 mt-3">
                    <p class="text-xs text-gray-400 mb-1">N° de Membresía</p>
                    <p class="text-base font-bold text-green-700 font-mono select-all" id="md-ticket-code">—</p>
                </div>
                <p class="text-xs text-gray-400 pt-2" id="md-fecha">—</p>
            </div>

            {{-- FORM ENVIAR TICKETS --}}
            <div id="md-validar-form" class="hidden space-y-3 border-t pt-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">N° de Tickets a enviar</p>
                <div id="vf-tickets-campos" class="space-y-2"></div>
                <p class="text-xs text-gray-400">Se enviarán al cliente por WhatsApp automáticamente</p>
                <div class="flex gap-2">
                    <button onclick="rifaEnviarTickets()" class="flex-1 bg-green-600 text-white rounded-xl py-3 text-sm font-semibold hover:bg-green-700 transition">🎟️ Enviar tickets</button>
                    <button onclick="ocultarFormValidar()" class="px-4 text-gray-500 hover:text-gray-700 text-sm">Cancelar</button>
                </div>
            </div>

            <div id="md-edit" class="hidden space-y-3">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Editar datos</p>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Nombre completo</label>
                    <input type="text" id="ef-nombre" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-400">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">DNI</label>
                        <input type="text" id="ef-dni" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Ciudad</label>
                        <input type="text" id="ef-ciudad" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-blue-400">
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button onclick="guardarEdicion()" class="flex-1 bg-blue-600 text-white rounded-xl py-3 text-sm font-medium hover:bg-blue-700 transition">Guardar</button>
                    <button onclick="cancelarEdicion()" class="px-4 text-gray-500 hover:text-gray-700 text-sm transition">Cancelar</button>
                </div>
            </div>

            <div id="md-comprobante-wrap" class="hidden">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">COMPROBANTE DE PAGO</p>
                <img id="md-comprobante-img" src="" alt="Comprobante" class="w-full rounded-xl border border-gray-200 cursor-zoom-in" onclick="window.open(this.src,'_blank')">
                <p class="text-xs text-gray-400 mt-1 text-center">Toca la imagen para abrir en pantalla completa</p>
            </div>
        </div>
        <div id="md-acciones" class="sticky bottom-0 bg-white px-5 py-3 border-t border-gray-100 flex flex-wrap gap-2"></div>
    </div>
</div>

<div id="toast" class="fixed bottom-6 right-6 hidden z-[60]">
    <div id="toast-inner" class="px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white bg-gray-800"></div>
</div>

<script>
const csrf = '{{ csrf_token() }}';
const ventas = @json($ventasJson);
let activeId = null;

function abrirDetalle(id) {
    activeId = id;
    const v = ventas[id];
    if (!v) return;

    document.getElementById('md-titulo').innerHTML = 'Pedido #' + id;

    const statusBadge = document.getElementById('md-status-badge');
    const statusLabels = {pendiente:'Pendiente', pagado:'Pagado', enviado:'Enviado', cancelado:'Cancelado'};
    const statusColors = {pendiente:'bg-yellow-100 text-yellow-700', pagado:'bg-blue-100 text-blue-700', enviado:'bg-green-100 text-green-700', cancelado:'bg-red-100 text-red-700'};
    statusBadge.innerHTML = statusLabels[v.status] || v.status;
    statusBadge.className = 'ml-2 text-xs px-2 py-0.5 rounded-full ' + (statusColors[v.status] || 'bg-gray-100');

    document.getElementById('md-nombre').innerHTML = v.nombre || '—';
    document.getElementById('md-dni').innerHTML = v.dni || '—';
    const rawNum = (v.wa_number || '').replace(/\D/g,'');
    const fmtNum = rawNum.length >= 10 ? '+' + rawNum : rawNum;
    document.getElementById('md-celular').innerHTML = fmtNum || '—';
    document.getElementById('md-ciudad').innerHTML = v.ciudad || '—';
    document.getElementById('md-plan').innerHTML = v.plan_nombre || '—';
    document.getElementById('md-tickets').innerHTML = v.tickets + ' ticket(s)';
    document.getElementById('md-monto').innerHTML = 'S/ ' + parseFloat(v.monto).toFixed(2);
    document.getElementById('md-fecha').innerHTML = v.created_at || '';

    const codeRow = document.getElementById('md-ticket-code-row');
    if (v.ticket_code && v.status === 'enviado') {
        document.getElementById('md-ticket-code').innerHTML = v.ticket_code;
        codeRow.classList.remove('hidden');
    } else {
        codeRow.classList.add('hidden');
    }

    const compWrap = document.getElementById('md-comprobante-wrap');
    if (v.payment_proof) {
        document.getElementById('md-comprobante-img').src = v.payment_proof;
        compWrap.classList.remove('hidden');
    } else {
        compWrap.classList.add('hidden');
    }

    document.getElementById('md-vista').classList.remove('hidden');
    document.getElementById('md-edit').classList.add('hidden');
    document.getElementById('md-validar-form').classList.add('hidden');



    const acciones = document.getElementById('md-acciones');
    acciones.innerHTML = '';
    acciones.innerHTML += '<button onclick="activarEdicion()" class="text-xs bg-gray-100 text-gray-600 px-4 py-2 rounded-xl hover:bg-gray-200 transition">✏️ Editar datos</button>';
    acciones.innerHTML += '<button onclick="rifaEliminar(' + id + ')" class="text-xs bg-red-50 text-red-600 px-4 py-2 rounded-xl hover:bg-red-100 transition">🗑️ Eliminar</button>';

    if (v.status === 'pendiente' || v.status === 'comprobante') {
        acciones.innerHTML += '<button onclick="rifaValidarSinTicket(' + id + ')" class="flex-1 text-sm bg-green-600 text-white px-4 py-2.5 rounded-xl hover:bg-green-700 transition font-semibold">✓ Validar pago</button>';
        acciones.innerHTML += '<button onclick="rifaCancelar(' + id + ')" class="text-xs bg-red-50 text-red-600 px-4 py-2 rounded-xl hover:bg-red-100 transition">✕ Cancelar</button>';
    }
    if (v.status === 'pagado') {
        acciones.innerHTML += '<button onclick="mostrarFormValidar()" class="flex-1 text-sm bg-green-600 text-white px-4 py-2.5 rounded-xl hover:bg-green-700 transition font-semibold">🎟️ Enviar ticket</button>';
    }
    if (v.status === 'enviado') {
        acciones.innerHTML += '<a href="/rifas/' + id + '/ticket-preview" target="_blank" class="text-xs bg-gray-100 text-gray-600 px-4 py-2 rounded-xl hover:bg-gray-200 transition">👁️ Ver boleto</a>';
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
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 w-6 text-right">${i+1}.</span>
                <input type="text" id="vf-t-${i}" value="${existentes[i] || ''}"
                    class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-green-400"
                    placeholder="N° ticket ${i+1}" autocomplete="off">
            </div>`;
    }
    document.getElementById('md-validar-form').classList.remove('hidden');
    document.getElementById('md-acciones').classList.add('hidden');
    setTimeout(() => document.getElementById('vf-t-0')?.focus(), 100);
}

function ocultarFormValidar() {
    document.getElementById('md-validar-form').classList.add('hidden');
    document.getElementById('md-acciones').classList.remove('hidden');
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
    document.getElementById('md-vista').classList.add('hidden');
    document.getElementById('md-edit').classList.remove('hidden');
}

function cancelarEdicion() {
    document.getElementById('md-vista').classList.remove('hidden');
    document.getElementById('md-edit').classList.add('hidden');
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
    const desde = document.getElementById('f-desde').value;
    const hasta = document.getElementById('f-hasta').value;
    const estado = document.getElementById('f-estado').value;
    const buscar = document.getElementById('f-buscar').value.toLowerCase();
    document.querySelectorAll('[id^="rv-"]').forEach(row => {
        const id = row.id.replace('rv-', '');
        const v = ventas[id];
        if (!v) { row.style.display = 'none'; return; }
        const fechaParts = v.created_at ? v.created_at.split(' ')[0].split('/') : [];
        const fecha = fechaParts.length === 3 ? `${fechaParts[2]}-${fechaParts[1]}-${fechaParts[0]}` : '';
        const okFecha = (!desde || fecha >= desde) && (!hasta || fecha <= hasta);
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
    const panel = document.getElementById('bot-panel');
    const content = document.getElementById('bot-panel-content');
    const chevron = document.getElementById('bot-chevron');
    panel.style.width = botPanelOpen ? '220px' : '22px';
    content.style.display = botPanelOpen ? 'flex' : 'none';
    chevron.style.transform = botPanelOpen ? 'rotate(180deg)' : 'rotate(0deg)';
}

function checkBotStatus() {
    fetch('/bot-status?bot=rifa')
        .then(r => r.json())
        .then(d => {
            const dot      = document.getElementById('bot-dot');
            const txt      = document.getElementById('bot-status-text');
            const spinner  = document.getElementById('bot-qr-spinner');
            const qrImg    = document.getElementById('bot-qr-img');
            const qrHint   = document.getElementById('bot-qr-hint');
            const connMsg  = document.getElementById('bot-connected-msg');
            const offMsg   = document.getElementById('bot-offline-msg');

            spinner.style.display  = 'none';
            qrImg.style.display    = 'none';
            qrHint.style.display   = 'none';
            connMsg.style.display  = 'none';
            offMsg.style.display   = 'none';

            if (d.status === 'connected') {
                dot.style.background = '#22c55e';
                txt.innerHTML = 'Conectado ✓';
                txt.className = 'text-xs font-medium text-green-600';
                connMsg.style.display = 'block';
            } else if (d.status === 'qr' && d.qr) {
                dot.style.background = '#f59e0b';
                txt.innerHTML = 'Escanear QR';
                txt.className = 'text-xs font-medium text-yellow-600';
                qrImg.src = d.qr;
                qrImg.style.display = 'block';
                qrHint.style.display = 'block';
            } else if (d.status === 'qr' || d.status === 'starting') {
                dot.style.background = '#3b82f6';
                txt.innerHTML = 'Iniciando...';
                txt.className = 'text-xs font-medium text-blue-600';
                spinner.style.display = 'flex';
            } else {
                dot.style.background = '#d1d5db';
                txt.innerHTML = 'Offline';
                txt.className = 'text-xs font-medium text-gray-400';
                offMsg.style.display = 'block';
            }
        })
        .catch(() => {
            document.getElementById('bot-dot').style.background = '#d1d5db';
            document.getElementById('bot-status-text').innerHTML = 'Sin conexión';
        });
}

checkBotStatus();
setInterval(checkBotStatus, 8000);

function showToast(msg, type = 'info') {
    const inner = document.getElementById('toast-inner');
    const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-gray-800' };
    inner.className = `px-4 py-3 rounded-xl shadow-lg text-white ${colors[type] || colors.info}`;
    inner.innerHTML = msg;
    document.getElementById('toast').classList.remove('hidden');
    setTimeout(() => document.getElementById('toast').classList.add('hidden'), 3000);
}
</script>

</x-portal-layout>

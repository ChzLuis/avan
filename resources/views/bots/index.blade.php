<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="px-6 py-3 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-base font-semibold text-gray-800">Canales WhatsApp</h1>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <button onclick="openNewBot()"
        class="flex items-center gap-1.5 text-xs bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Nuevo Bot
    </button>
</div>

{{-- BOTS GRID --}}
<div class="flex-1 overflow-y-auto bg-gray-50/30 p-6">
    <div id="bots-grid" class="grid grid-cols-1 md:grid-cols-2 gap-5">

        @foreach($bots as $bot)
        @php
            $st      = $bot->status;
            $status  = $st['status'] ?? 'offline';
            $qr      = $st['qr'] ?? null;
            $colors  = $bot->iconColors();
            $badge   = match($status) {
                'connected' => ['bg-green-100 text-green-700',  'Conectado'],
                'qr'        => ['bg-yellow-100 text-yellow-700','Escanear QR'],
                'starting'  => ['bg-blue-100 text-blue-600',    'Iniciando...'],
                default     => ['bg-gray-100 text-gray-500',    'Offline'],
            };
            $statesCount = $bot->flow?->states_count ?? 0;
        @endphp

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden" id="bot-card-{{ $bot->bot_type }}">
            <div class="p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl {{ $colors[0] }} flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 {{ $colors[1] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800">{{ $bot->name }}</p>
                        <p class="text-xs text-gray-400">{{ $bot->description }}</p>
                    </div>
                    <span id="badge-{{ $bot->bot_type }}" class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $badge[0] }}">{{ $badge[1] }}</span>
                </div>

                <div id="qr-{{ $bot->bot_type }}">
                    @if($status === 'qr' && $qr)
                        <div class="flex flex-col items-center py-3">
                            <p class="text-xs text-gray-500 mb-2">WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
                            <img src="{{ $qr }}" class="w-44 h-44 rounded-xl border border-gray-200">
                        </div>
                    @elseif($status === 'qr')
                        <div class="flex flex-col items-center py-4 gap-2">
                            <div class="w-8 h-8 border-2 border-yellow-400 border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-xs text-yellow-600 font-medium">Bot iniciado, generando QR...</p>
                            <p class="text-xs text-gray-400">Se actualizará automáticamente</p>
                        </div>
                    @elseif($status === 'connected')
                        <div class="flex items-center gap-2 py-3 text-green-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm font-medium">WhatsApp conectado correctamente</span>
                        </div>
                    @elseif($status === 'starting')
                        <div class="flex flex-col items-center py-4 gap-2">
                            <div class="w-8 h-8 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-xs text-blue-600 font-medium">Iniciando bot...</p>
                            <p class="text-xs text-gray-400">El QR aparecerá en unos segundos</p>
                        </div>
                    @else
                        <div class="py-3 text-center">
                            <p class="text-xs text-gray-400">Bot no iniciado — usa los botones de abajo</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center gap-2">
                <button onclick="botAction('{{ $bot->bot_type }}','start')"
                    class="flex items-center gap-1 text-xs bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Iniciar
                </button>
                <button onclick="botAction('{{ $bot->bot_type }}','restart')"
                    class="flex items-center gap-1 text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Reiniciar
                </button>
                <button onclick="botAction('{{ $bot->bot_type }}','stop')"
                    class="flex items-center gap-1 text-xs bg-red-100 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-200 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"/></svg>
                    Detener
                </button>
                <button onclick="toggleLogs('{{ $bot->bot_type }}')"
                    class="flex items-center gap-1 text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Logs
                </button>
                <div class="ml-auto flex items-center gap-2">
                    <span class="text-xs text-gray-400">{{ $statesCount }} estados</span>
                    @if($bot->bot_type === 'rifa')
                    <button onclick="toggleVentas('{{ $bot->bot_type }}')"
                        class="flex items-center gap-1 text-xs bg-purple-50 border border-purple-200 text-purple-700 px-3 py-1.5 rounded-lg hover:bg-purple-100 transition">
                        🎟️ Ventas
                    </button>
                    @endif
                    <a href="{{ route('bots.flow', ['bot'=>$bot->bot_type]) }}"
                       class="flex items-center gap-1 text-xs bg-white border border-gray-200 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Ver flujo
                    </a>
                    <button onclick="deleteBot('{{ $bot->id }}','{{ $bot->name }}')"
                        class="text-xs text-gray-400 hover:text-red-500 px-2 py-1.5 transition" title="Eliminar bot">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>

            {{-- Logs --}}
            <div id="logs-{{ $bot->bot_type }}" class="hidden border-t border-gray-100">
                <div class="flex items-center justify-between px-5 py-2 bg-gray-900">
                    <span class="text-xs text-gray-400 font-mono">logs — {{ $bot->pm2Name() }}</span>
                    <button onclick="loadLogs('{{ $bot->bot_type }}')" class="text-xs text-gray-400 hover:text-white">↻ Actualizar</button>
                </div>
                <pre id="logs-content-{{ $bot->bot_type }}" class="bg-gray-950 text-green-400 text-xs font-mono p-4 overflow-x-auto max-h-48 overflow-y-auto">Cargando...</pre>
            </div>

            {{-- Ventas Rifa --}}
            @if($bot->bot_type === 'rifa')
            <div id="ventas-{{ $bot->bot_type }}" class="hidden border-t border-gray-100">
                <div class="flex items-center justify-between px-5 py-2.5 bg-purple-50">
                    <span class="text-xs font-semibold text-purple-700">🎟️ Ventas de Rifa</span>
                    <button onclick="loadVentas('{{ $bot->bot_type }}')" class="text-xs text-purple-500 hover:text-purple-700">↻ Actualizar</button>
                </div>
                <div id="ventas-content-{{ $bot->bot_type }}" class="p-4 max-h-96 overflow-y-auto space-y-2">
                    <p class="text-xs text-gray-400 text-center py-4">Cargando...</p>
                </div>
            </div>
            @endif
        </div>
        @endforeach

        @if($bots->isEmpty())
        <div class="col-span-2 text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            <p class="text-sm">No hay bots configurados</p>
            <button onclick="openNewBot()" class="mt-3 text-xs text-indigo-600 hover:underline">+ Crear primer bot</button>
        </div>
        @endif

    </div>
</div>
</div>

{{-- MODAL: Nuevo Bot --}}
<div id="modal-new-bot" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Nuevo Bot WhatsApp</h3>
            <button onclick="closeNewBot()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Nombre</label>
                <input id="nb-name" type="text" placeholder="Bot Soporte" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Identificador único (bot_type)</label>
                <input id="nb-type" type="text" placeholder="soporte" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono">
                <p class="text-xs text-gray-400 mt-1">Solo letras, números y guiones. Ej: soporte, ventas, tienda2</p>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 block mb-1">Descripción</label>
                <input id="nb-desc" type="text" placeholder="Atención al cliente..." class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Color</label>
                    <select id="nb-color" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="green">Verde</option>
                        <option value="purple">Morado</option>
                        <option value="blue">Azul</option>
                        <option value="orange">Naranja</option>
                        <option value="red">Rojo</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">Puerto HTTP</label>
                    <input id="nb-port" type="number" value="3003" min="3001" max="3999" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2">
            <button onclick="closeNewBot()" class="text-sm text-gray-500 px-4 py-2 rounded-lg hover:bg-gray-50">Cancelar</button>
            <button onclick="saveNewBot()" class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">Crear bot</button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="toast" class="fixed bottom-6 right-6 hidden z-50">
    <div id="toast-inner" class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white bg-gray-800 min-w-48"></div>
</div>

<script>
const csrf    = '{{ csrf_token() }}';
const baseUrl = '{{ url("/bixoadmin/bots") }}';

// ── Polling ────────────────────────────────────────────────────
const botTypes = @json($bots->pluck('bot_type'));

function refreshStatus(bot) {
    fetch(`${baseUrl}/status?bot=${bot}`)
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById(`badge-${bot}`);
            const qrDiv = document.getElementById(`qr-${bot}`);
            if (!badge || !qrDiv) return;

            const labels = {
                connected: ['bg-green-100 text-green-700',   'Conectado'],
                qr:        ['bg-yellow-100 text-yellow-700', 'Escanear QR'],
                starting:  ['bg-blue-100 text-blue-600',     'Iniciando...'],
                offline:   ['bg-gray-100 text-gray-500',     'Offline'],
            };
            const [cls, txt] = labels[data.status] || labels.offline;
            badge.className  = `text-xs font-semibold px-2.5 py-1 rounded-full ${cls}`;
            badge.textContent = txt;

            if (data.status === 'connected') {
                qrDiv.innerHTML = `<div class="flex items-center gap-2 py-3 text-green-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-sm font-medium">WhatsApp conectado correctamente</span></div>`;
            } else if (data.status === 'qr' && data.qr) {
                qrDiv.innerHTML = `<div class="flex flex-col items-center py-3"><p class="text-xs text-gray-500 mb-2">WhatsApp → Dispositivos vinculados → Vincular dispositivo</p><img src="${data.qr}" class="w-44 h-44 rounded-xl border border-gray-200"></div>`;
            } else if (data.status === 'qr') {
                qrDiv.innerHTML = `<div class="flex flex-col items-center py-4 gap-2"><div class="w-8 h-8 border-2 border-yellow-400 border-t-transparent rounded-full animate-spin"></div><p class="text-xs text-yellow-600 font-medium">Generando QR...</p><p class="text-xs text-gray-400">Se actualizará automáticamente</p></div>`;
            } else if (data.status === 'starting') {
                qrDiv.innerHTML = `<div class="flex flex-col items-center py-4 gap-2"><div class="w-8 h-8 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div><p class="text-xs text-blue-600 font-medium">Iniciando bot...</p><p class="text-xs text-gray-400">El QR aparecerá en unos segundos</p></div>`;
            } else {
                qrDiv.innerHTML = `<div class="py-3 text-center"><p class="text-xs text-gray-400">Bot no iniciado — usa los botones de abajo</p></div>`;
            }
        }).catch(() => {});
}

setInterval(() => botTypes.forEach(refreshStatus), 5000);

// ── Control PM2 ────────────────────────────────────────────────
async function botAction(bot, action) {
    const labels = { start:'Iniciando', stop:'Deteniendo', restart:'Reiniciando' };
    showToast(`${labels[action]} bot...`, 'info');
    try {
        const res  = await fetch(`${baseUrl}/control`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf },
            body: JSON.stringify({ bot, action }),
        });
        const data = await res.json();
        if (action === 'stop') {
            showToast('Bot detenido', 'error');
        } else {
            showToast('Bot iniciado — esperando QR...', 'success');
            setTimeout(() => refreshStatus(bot), 3000);
        }
        if (data.output) console.log('PM2:', data.output);
    } catch(e) { showToast('Error al controlar el bot', 'error'); }
}

// ── Logs ───────────────────────────────────────────────────────
function toggleLogs(bot) {
    const panel = document.getElementById(`logs-${bot}`);
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        loadLogs(bot);
    } else {
        panel.classList.add('hidden');
    }
}
async function loadLogs(bot) {
    const pre = document.getElementById(`logs-content-${bot}`);
    if (!pre) return;
    pre.textContent = 'Cargando...';
    try {
        const res  = await fetch(`${baseUrl}/logs?bot=${bot}&lines=60`);
        const data = await res.json();
        pre.textContent = data.logs || 'Sin logs.';
        pre.scrollTop   = pre.scrollHeight;
    } catch(e) { pre.textContent = 'Error al cargar logs.'; }
}

// ── Nuevo Bot ──────────────────────────────────────────────────
function openNewBot()  { document.getElementById('modal-new-bot').classList.remove('hidden'); }
function closeNewBot() { document.getElementById('modal-new-bot').classList.add('hidden'); }

// Auto-generar bot_type desde el nombre
document.getElementById('nb-name').addEventListener('input', function() {
    document.getElementById('nb-type').value = this.value.toLowerCase()
        .replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim();
});

async function saveNewBot() {
    const name  = document.getElementById('nb-name').value.trim();
    const type  = document.getElementById('nb-type').value.trim();
    const desc  = document.getElementById('nb-desc').value.trim();
    const color = document.getElementById('nb-color').value;
    const port  = parseInt(document.getElementById('nb-port').value);

    if (!name || !type) { showToast('Nombre e identificador son requeridos', 'error'); return; }

    try {
        const res  = await fetch(`${baseUrl}/instances`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf },
            body: JSON.stringify({ name, bot_type: type, description: desc, icon_color: color, port }),
        });
        const data = await res.json();
        if (data.ok) {
            closeNewBot();
            showToast('Bot creado — recargando...', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Error al crear el bot', 'error');
        }
    } catch(e) { showToast('Error de conexión', 'error'); }
}

// ── Eliminar Bot ───────────────────────────────────────────────
async function deleteBot(id, name) {
    if (!confirm(`¿Eliminar el bot "${name}"? Se detendrá y se borrará su configuración.`)) return;
    try {
        const res  = await fetch(`${baseUrl}/instances/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const data = await res.json();
        if (data.ok) {
            showToast('Bot eliminado', 'error');
            setTimeout(() => location.reload(), 1000);
        }
    } catch(e) { showToast('Error al eliminar', 'error'); }
}

// ── Ventas Rifa ────────────────────────────────────────────────
function toggleVentas(bot) {
    const panel = document.getElementById(`ventas-${bot}`);
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        loadVentas(bot);
    } else {
        panel.classList.add('hidden');
    }
}

async function loadVentas(bot) {
    const container = document.getElementById(`ventas-content-${bot}`);
    if (!container) return;
    container.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Cargando...</p>';
    try {
        const res  = await fetch(`/rifas/ventas-json`);
        const data = await res.json();
        if (!data.ventas || data.ventas.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Sin ventas registradas</p>';
            return;
        }
        const statusBadge = s => ({
            pendiente: '<span class="px-1.5 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Pendiente</span>',
            pagado:    '<span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Pagado</span>',
            enviado:   '<span class="px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700">Enviado</span>',
            cancelado: '<span class="px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-700">Cancelado</span>',
        }[s] || `<span class="text-xs text-gray-400">${s}</span>`);

        container.innerHTML = data.ventas.map(v => `
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 bg-white border border-gray-100 rounded-lg px-3 py-2.5" id="rv-${v.id}">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-semibold text-gray-800">${v.nombre || '—'}</span>
                    <span class="text-xs text-gray-400">DNI: ${v.dni || '—'}</span>
                    <span class="text-xs text-gray-400">WA: ${v.wa_number}</span>
                </div>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <span class="text-xs text-purple-700">${v.plan_nombre}</span>
                    <span class="text-xs text-gray-500">${v.tickets} ticket(s)</span>
                    <span class="text-xs font-semibold text-gray-700">S/ ${Number(v.monto).toFixed(2)}</span>
                    ${v.ticket_code ? `<span class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">${v.ticket_code}</span>` : ''}
                    ${statusBadge(v.status)}
                </div>
            </div>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                ${v.payment_proof ? `<a href="/${v.payment_proof}" target="_blank" class="text-xs text-indigo-600 hover:underline">🧾 Comprobante</a>` : ''}
                ${v.status === 'pendiente' ? `
                    <button onclick="rifaValidar(${v.id})" class="text-xs bg-blue-600 text-white px-2.5 py-1 rounded-lg hover:bg-blue-700">✓ Validar</button>
                    <button onclick="rifaCancelar(${v.id})" class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-lg hover:bg-gray-200">Cancelar</button>
                ` : ''}
                ${v.status === 'pagado' ? `
                    <a href="/rifas/${v.id}/ticket-preview" target="_blank" class="text-xs bg-purple-100 text-purple-700 px-2.5 py-1 rounded-lg hover:bg-purple-200">👁️ Boleto</a>
                    <button onclick="rifaEnviar(${v.id})" class="text-xs bg-green-600 text-white px-2.5 py-1 rounded-lg hover:bg-green-700">🎟️ Enviar</button>
                ` : ''}
                ${v.status === 'enviado' ? `
                    <a href="/rifas/${v.id}/ticket-preview" target="_blank" class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-lg hover:bg-gray-200">👁️ Boleto</a>
                ` : ''}
            </div>
        </div>`).join('');
    } catch(e) { container.innerHTML = '<p class="text-xs text-red-400 text-center py-4">Error al cargar ventas</p>'; }
}

async function rifaValidar(id) {
    if (!confirm('¿Validar este pago?')) return;
    const r = await fetch(`/rifas/${id}/validar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) loadVentas('rifa');
}
async function rifaEnviar(id) {
    if (!confirm('¿Enviar ticket por WhatsApp?')) return;
    showToast('Generando y enviando ticket...', 'info');
    const r = await fetch(`/rifas/${id}/enviar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) { showToast('Ticket enviado ✓', 'success'); loadVentas('rifa'); }
    else showToast('Error al enviar', 'error');
}
async function rifaCancelar(id) {
    if (!confirm('¿Cancelar esta venta?')) return;
    const r = await fetch(`/rifas/${id}/cancelar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) loadVentas('rifa');
}

// ── Toast ──────────────────────────────────────────────────────
let toastTimer = null;
function showToast(msg, type = 'info') {
    const toast  = document.getElementById('toast');
    const inner  = document.getElementById('toast-inner');
    const colors = { success:'bg-green-600', error:'bg-red-600', info:'bg-gray-800' };
    inner.className  = `flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white min-w-48 ${colors[type]||colors.info}`;
    inner.textContent = msg;
    toast.classList.remove('hidden');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.add('hidden'), 3500);
}
</script>

</x-slot>
</x-app-layout>

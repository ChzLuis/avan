<x-app-layout>
<x-slot name="slot">

@php
    $csrf    = csrf_token();
    $botLabel = $botType === 'rifa' ? 'Bot Rifa' : 'Bot Principal';
    $inputTypes = ['text'=>'Texto libre','number'=>'Número','image'=>'Imagen','location'=>'Ubicación','option'=>'Opción de menú'];
    $actions = ['none'=>'Sin acción','save_name'=>'Guardar nombre','save_phone'=>'Guardar teléfono','save_address'=>'Guardar dirección','create_order'=>'Crear pedido','save_payment'=>'Guardar comprobante pago','complete'=>'Completar flujo'];
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="px-6 py-3 border-b border-gray-200 bg-white flex items-center gap-3 flex-shrink-0">
    <a href="{{ route('bots.index') }}" class="text-gray-400 hover:text-gray-600 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h1 class="text-base font-semibold text-gray-800">Flujo — {{ $botLabel }}</h1>
        <p class="text-xs text-gray-400">{{ $project->name }}</p>
    </div>
    <div class="ml-auto flex items-center gap-2">
        <button onclick="openNewState()"
                class="flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo estado
        </button>
    </div>
</div>

{{-- BODY --}}
<div class="flex flex-1 overflow-hidden">

    {{-- LISTA DE ESTADOS --}}
    <div class="w-80 border-r border-gray-200 bg-white flex flex-col flex-shrink-0">
        <div class="px-4 py-3 border-b border-gray-100 flex-shrink-0">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Estados del flujo</p>
        </div>
        <div class="flex-1 overflow-y-auto" id="states-list">
            @if(!$flow)
                <div class="p-6 text-center text-gray-400 text-sm">
                    <p class="mb-3">Sin flujo configurado</p>
                    <button onclick="createFlow()" class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">
                        Crear flujo
                    </button>
                </div>
            @elseif($allStates->isEmpty())
                <div class="p-6 text-center text-gray-400 text-sm">
                    Agrega tu primer estado con el botón de arriba
                </div>
            @else
                @foreach($allStates as $state)
                <div class="state-row border-b border-gray-100 px-4 py-3 cursor-pointer hover:bg-gray-50 transition {{ !$state->is_active ? 'opacity-50' : '' }}"
                     onclick="selectState({{ $state->id }})"
                     id="row-{{ $state->id }}">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $state->is_active ? 'bg-indigo-500' : 'bg-gray-300' }}"></div>
                        <span class="text-sm font-medium text-gray-800 truncate">{{ $state->label }}</span>
                        <span class="ml-auto text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-mono">{{ $state->key }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-1 ml-4">
                        <span class="text-[10px] text-gray-400">{{ $inputTypes[$state->input_type] ?? $state->input_type }}</span>
                        <span class="text-[10px] text-gray-400">·</span>
                        <span class="text-[10px] text-gray-400">{{ $state->transitions->count() }} transiciones</span>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- DETALLE DEL ESTADO --}}
    <div class="flex-1 overflow-y-auto bg-gray-50/30 p-6" id="detail-panel">
        <div class="flex flex-col items-center justify-center h-full text-gray-300">
            <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm">Selecciona un estado para editarlo</p>
        </div>
    </div>

</div>
</div>

{{-- MODAL: Nuevo/Editar estado --}}
<div id="modal-state" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <h3 class="font-semibold text-gray-900 mb-5" id="modal-title">Nuevo estado</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">Clave (key)</label>
                    <input type="text" id="s-key" placeholder="ej: menu" class="input" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,'')">
                    <p class="text-xs text-gray-400 mt-1">Solo letras, números y _</p>
                </div>
                <div>
                    <label class="label">Nombre visible</label>
                    <input type="text" id="s-label" placeholder="ej: Menú principal" class="input">
                </div>
            </div>
            <div>
                <label class="label">Mensaje del bot</label>
                <textarea id="s-message" rows="4" placeholder="Hola! Soy el bot de {negocio}. ¿En qué puedo ayudarte?&#10;&#10;1️⃣ Ver productos&#10;2️⃣ Hacer pedido" class="input resize-none"></textarea>
                <p class="text-xs text-gray-400 mt-1">Usa <code class="bg-gray-100 px-1 rounded">{negocio}</code>, <code class="bg-gray-100 px-1 rounded">{order_number}</code>, <code class="bg-gray-100 px-1 rounded">{rifa_nombre}</code>, <code class="bg-gray-100 px-1 rounded">{rifa_total}</code></p>
            </div>
            <div>
                <label class="label">Imágenes del bot <span class="text-gray-400 font-normal">(hasta 5)</span></label>
                <div id="s-image-drop" ondragover="event.preventDefault()" ondrop="dropImages(event)"
                     class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-indigo-300 transition"
                     onclick="document.getElementById('s-image-input').click()">
                    <svg class="w-6 h-6 mx-auto text-gray-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs text-gray-400">Haz clic o arrastra imágenes aquí</p>
                    <p class="text-xs text-gray-300">JPG, PNG — máx. 5MB cada una</p>
                </div>
                <input type="file" id="s-image-input" accept="image/*" multiple class="hidden" onchange="uploadImages(this.files)">
                <div id="s-image-previews" class="flex flex-wrap gap-2 mt-2"></div>
                <p class="text-xs text-gray-400 mt-1">El bot enviará las imágenes junto al mensaje del estado</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">Tipo de entrada</label>
                    <select id="s-input-type" class="input">
                        @foreach($inputTypes as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Orden</label>
                    <input type="number" id="s-order" value="{{ $allStates->count() }}" class="input">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="s-active" checked class="w-4 h-4 rounded text-indigo-600">
                <label for="s-active" class="text-sm text-gray-700">Estado activo</label>
            </div>
        </div>
        <div class="flex gap-2 mt-6">
            <button onclick="closeModal()" class="flex-1 py-2.5 border border-gray-200 text-sm text-gray-600 rounded-xl hover:bg-gray-50 transition">Cancelar</button>
            <button onclick="saveState()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition">Guardar</button>
        </div>
    </div>
</div>

<script>
const csrf    = '{{ $csrf }}';
const flowId  = {{ $flow?->id ?? 'null' }};
const botType = '{{ $botType }}';
const baseUrl = '{{ url("/bixoadmin/bots") }}';
let editingStateId = null;
let stateImages    = []; // URLs de imágenes del estado actual

// ── Upload de imágenes ────────────────────────────────────────
async function uploadImages(files) {
    const remaining = 5 - stateImages.length;
    const toUpload  = Array.from(files).slice(0, remaining);
    for (const file of toUpload) {
        const fd = new FormData();
        fd.append('image', file);
        fd.append('_token', csrf);
        try {
            const res  = await fetch(`${baseUrl}/upload-image`, { method:'POST', body: fd });
            const data = await res.json();
            if (data.ok) { stateImages.push(data.url); renderPreviews(); }
        } catch(e) { console.error('Upload:', e); }
    }
}
function dropImages(e) {
    e.preventDefault();
    uploadImages(e.dataTransfer.files);
}
function removeImage(idx) {
    stateImages.splice(idx, 1);
    renderPreviews();
}
function renderPreviews() {
    const container = document.getElementById('s-image-previews');
    container.innerHTML = stateImages.map((url, i) => `
        <div class="relative group w-16 h-16">
            <img src="${url}" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
            <button onclick="removeImage(${i})" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
        </div>`).join('');
    // Mostrar add más si hay menos de 5
    if (stateImages.length < 5) {
        container.innerHTML += `<div onclick="document.getElementById('s-image-input').click()" class="w-16 h-16 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center cursor-pointer hover:border-indigo-300 text-gray-300 text-xl">+</div>`;
    }
}
let states = @json($allStates);

const inputTypes = @json($inputTypes);
const actions    = @json($actions);

// ── Crear flujo si no existe ──────────────────────────────────
async function createFlow() {
    const r = await fetch(`${baseUrl}/flow`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' },
        body: JSON.stringify({ bot_type: botType, name: 'Flujo ' + botType })
    });
    if (r.ok) location.reload();
}

// ── Modal estado ──────────────────────────────────────────────
function openNewState() {
    if (!flowId) { if(confirm('No hay flujo creado. ¿Crear ahora?')) createFlow(); return; }
    editingStateId = null;
    stateImages    = [];
    renderPreviews();
    document.getElementById('modal-title').textContent = 'Nuevo estado';
    document.getElementById('s-key').value = '';
    document.getElementById('s-label').value = '';
    document.getElementById('s-message').value = '';
    document.getElementById('s-input-type').value = 'text';
    document.getElementById('s-order').value = states.length;
    document.getElementById('s-active').checked = true;
    document.getElementById('s-key').disabled = false;
    document.getElementById('modal-state').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-state').classList.add('hidden');
}

async function saveState() {
    const payload = {
        flow_id:    flowId,
        key:        document.getElementById('s-key').value.trim(),
        label:      document.getElementById('s-label').value.trim(),
        message:    document.getElementById('s-message').value.trim(),
        images:     stateImages,
        input_type: document.getElementById('s-input-type').value,
        sort_order: parseInt(document.getElementById('s-order').value) || 0,
        is_active:  document.getElementById('s-active').checked ? 1 : 0,
    };

    if (!payload.key || !payload.label || !payload.message) { alert('Completa todos los campos requeridos'); return; }

    const url    = editingStateId ? `${baseUrl}/states/${editingStateId}` : `${baseUrl}/states`;
    const method = editingStateId ? 'PUT' : 'POST';

    const r = await fetch(url, {
        method,
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' },
        body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.ok) { closeModal(); location.reload(); }
    else alert(d.message || 'Error al guardar');
}

// ── Seleccionar estado para ver detalle ───────────────────────
function selectState(id) {
    document.querySelectorAll('.state-row').forEach(r => r.classList.remove('bg-indigo-50', 'border-l-2', 'border-indigo-500'));
    const row = document.getElementById('row-' + id);
    if (row) row.classList.add('bg-indigo-50', 'border-l-2', 'border-indigo-500');

    const state = states.find(s => s.id === id);
    if (!state) return;

    const otherStates = states.filter(s => s.id !== id);

    document.getElementById('detail-panel').innerHTML = `
        <div class="max-w-2xl space-y-5">

            {{-- Header --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">${state.label}</h2>
                        <span class="text-xs font-mono bg-gray-100 text-gray-500 px-2 py-0.5 rounded mt-1 inline-block">${state.key}</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editState(${state.id})" class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">Editar</button>
                        <button onclick="deleteState(${state.id})" class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 transition">Eliminar</button>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 whitespace-pre-wrap border border-gray-100">${state.message}</div>
                <div class="flex gap-3 mt-3">
                    <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-lg font-medium">${inputTypes[state.input_type] || state.input_type}</span>
                    <span class="text-xs ${state.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'} px-2 py-1 rounded-lg font-medium">${state.is_active ? 'Activo' : 'Inactivo'}</span>
                </div>
            </div>

            {{-- Transiciones --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-800">Transiciones</p>
                    <button onclick="openTransition(${state.id})" class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition">+ Agregar</button>
                </div>

                ${state.transitions.length === 0
                    ? '<p class="text-xs text-gray-400 text-center py-4">Sin transiciones — agrega una para definir a dónde va el flujo</p>'
                    : state.transitions.map(t => `
                        <div class="flex items-center gap-3 py-2.5 border-b border-gray-100 last:border-0">
                            <div class="flex-shrink-0 text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded font-mono font-medium min-w-12 text-center">
                                ${t.trigger || '*'}
                            </div>
                            <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <span class="text-sm text-gray-700">${t.to_state?.label || '?'}</span>
                                ${t.action && t.action !== 'none' ? `<span class="ml-2 text-xs bg-yellow-50 text-yellow-700 px-1.5 py-0.5 rounded">${actions[t.action] || t.action}</span>` : ''}
                            </div>
                            <button onclick="deleteTransition(${t.id})" class="text-gray-300 hover:text-red-500 transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    `).join('')
                }
            </div>
        </div>
    `;
}

function editState(id) {
    const state = states.find(s => s.id === id);
    if (!state) return;
    editingStateId = id;
    document.getElementById('modal-title').textContent = 'Editar estado';
    document.getElementById('s-key').value = state.key;
    document.getElementById('s-label').value = state.label;
    document.getElementById('s-message').value = state.message;
    stateImages = state.images || [];
    renderPreviews();
    document.getElementById('s-input-type').value = state.input_type;
    document.getElementById('s-order').value = state.sort_order;
    document.getElementById('s-active').checked = !!state.is_active;
    document.getElementById('s-key').disabled = true;
    document.getElementById('modal-state').classList.remove('hidden');
}

async function deleteState(id) {
    if (!confirm('¿Eliminar este estado? También se eliminarán sus transiciones.')) return;
    const r = await fetch(`${baseUrl}/states/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' }
    });
    if ((await r.json()).ok) location.reload();
}

// ── Transiciones ──────────────────────────────────────────────
function openTransition(fromId) {
    const otherStates = states.filter(s => s.id !== fromId);
    const opts = otherStates.map(s => `<option value="${s.id}">${s.label} (${s.key})</option>`).join('');
    const actOpts = Object.entries(actions).map(([v,l]) => `<option value="${v}">${l}</option>`).join('');

    const html = `
        <div id="modal-transition" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                <h3 class="font-semibold text-gray-900 mb-5">Nueva transición</h3>
                <div class="space-y-4">
                    <div>
                        <label class="label">Cuando el usuario responde</label>
                        <input type="text" id="t-trigger" placeholder="ej: 1, 2, si, no (vacío = cualquier respuesta)" class="input">
                    </div>
                    <div>
                        <label class="label">Ir al estado</label>
                        <select id="t-to" class="input">${opts}</select>
                    </div>
                    <div>
                        <label class="label">Acción a ejecutar</label>
                        <select id="t-action" class="input">${actOpts}</select>
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button onclick="document.getElementById('modal-transition').remove()" class="flex-1 py-2.5 border border-gray-200 text-sm text-gray-600 rounded-xl hover:bg-gray-50 transition">Cancelar</button>
                    <button onclick="saveTransition(${fromId})" class="flex-1 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">Guardar</button>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', html);
}

async function saveTransition(fromId) {
    const payload = {
        from_state_id: fromId,
        to_state_id:   parseInt(document.getElementById('t-to').value),
        trigger:       document.getElementById('t-trigger').value.trim() || null,
        action:        document.getElementById('t-action').value || null,
    };
    const r = await fetch(`${baseUrl}/transitions`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' },
        body: JSON.stringify(payload)
    });
    if ((await r.json()).ok) location.reload();
}

async function deleteTransition(id) {
    if (!confirm('¿Eliminar esta transición?')) return;
    const r = await fetch(`${baseUrl}/transitions/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' }
    });
    if ((await r.json()).ok) location.reload();
}
</script>

</x-slot>
</x-app-layout>

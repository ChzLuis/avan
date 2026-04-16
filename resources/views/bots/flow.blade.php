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
                <div class="state-row border-b border-gray-100 px-3 py-3 cursor-pointer hover:bg-gray-50 transition {{ !$state->is_active ? 'opacity-50' : '' }}"
                     onclick="selectState({{ $state->id }})"
                     id="row-{{ $state->id }}">
                    <div class="flex items-center gap-2">
                        <div class="flex flex-col gap-0.5 flex-shrink-0" onclick="event.stopPropagation()">
                            <button onclick="moveState({{ $state->id }}, 'up')"
                                    class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded transition p-0.5"
                                    title="Subir">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button onclick="moveState({{ $state->id }}, 'down')"
                                    class="text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded transition p-0.5"
                                    title="Bajar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $state->is_active ? 'bg-indigo-500' : 'bg-gray-300' }}"></div>
                        <span class="text-sm font-medium text-gray-800 truncate">{{ $state->label }}</span>
                        <span class="ml-auto text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded font-mono">{{ $state->key }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-1 ml-10">
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
                <p class="text-xs text-gray-400 mt-1">Variables: <code class="bg-gray-100 px-1 rounded">{negocio}</code> <code class="bg-gray-100 px-1 rounded">{rifas_lista}</code> <code class="bg-gray-100 px-1 rounded">{rifa_nombre}</code> <code class="bg-gray-100 px-1 rounded">{rifa_precio}</code> <code class="bg-gray-100 px-1 rounded">{rifa_total}</code> <code class="bg-gray-100 px-1 rounded">{nombre}</code> <code class="bg-gray-100 px-1 rounded">{dni}</code> <code class="bg-gray-100 px-1 rounded">{ciudad}</code></p>
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
                    <select id="s-input-type" class="input" onchange="toggleValidation()">
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

            {{-- Validación dinámica según tipo --}}
            <div id="validation-section" class="border border-gray-100 rounded-xl p-4 bg-gray-50 space-y-3 hidden">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Validación de entrada</p>

                {{-- Para number: min / max --}}
                <div id="val-number" style="display:none">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Valor mínimo</label>
                            <input type="number" id="s-val-min" placeholder="ej: 1" class="input">
                        </div>
                        <div>
                            <label class="label">Valor máximo</label>
                            <input type="number" id="s-val-max" placeholder="ej: 10" class="input">
                        </div>
                    </div>
                </div>

                {{-- Para text: patrón regex --}}
                <div id="val-text" style="display:none">
                    <label class="label">Patrón de validación (regex)</label>
                    <div class="flex gap-2 mb-1">
                        <button type="button" onclick="setPattern('^\d{8}$')" class="text-xs bg-white border border-gray-200 px-2 py-1 rounded hover:bg-indigo-50 hover:border-indigo-300 transition">DNI (8 dígitos)</button>
                        <button type="button" onclick="setPattern('^\d{9}$')" class="text-xs bg-white border border-gray-200 px-2 py-1 rounded hover:bg-indigo-50 hover:border-indigo-300 transition">Celular (9 dígitos)</button>
                        <button type="button" onclick="setPattern('^[a-zA-ZÀ-ÿ\s]{2,}$')" class="text-xs bg-white border border-gray-200 px-2 py-1 rounded hover:bg-indigo-50 hover:border-indigo-300 transition">Solo letras</button>
                    </div>
                    <input type="text" id="s-val-pattern" placeholder="ej: ^\d{8}$ para DNI de 8 dígitos" class="input font-mono text-xs">
                </div>

                {{-- Para option: informativo --}}
                <div id="val-option" style="display:none">
                    <p class="text-xs bg-blue-50 text-blue-700 px-3 py-2 rounded-lg">Las opciones válidas se definen por los <strong>triggers</strong> de las transiciones de este estado.</p>
                </div>

                {{-- Mensaje de error (para number y text) --}}
                <div id="val-error-wrap">
                    <label class="label">Mensaje cuando no sea válido</label>
                    <input type="text" id="s-val-error" placeholder="ej: Por favor escribe solo números" class="input">
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

// ── Validación dinámica ───────────────────────────────────────
function toggleValidation() {
    const type = document.getElementById('s-input-type').value;
    const section   = document.getElementById('validation-section');
    const valNumber = document.getElementById('val-number');
    const valText   = document.getElementById('val-text');
    const valOption = document.getElementById('val-option');
    const valError  = document.getElementById('val-error-wrap');

    // Ocultar todo primero
    valNumber.style.display = 'none';
    valText.style.display   = 'none';
    valOption.style.display = 'none';
    valError.style.display  = 'block';

    if (type === 'number') {
        section.classList.remove('hidden');
        valNumber.style.display = 'block';
    } else if (type === 'text') {
        section.classList.remove('hidden');
        valText.style.display = 'block';
    } else if (type === 'option') {
        section.classList.remove('hidden');
        valOption.style.display = 'block';
        valError.style.display  = 'none';
    } else {
        section.classList.add('hidden');
    }
}

function setPattern(pattern) {
    document.getElementById('s-val-pattern').value = pattern;
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
    document.getElementById('s-val-min').value = '';
    document.getElementById('s-val-max').value = '';
    document.getElementById('s-val-pattern').value = '';
    document.getElementById('s-val-error').value = '';
    toggleValidation();
    document.getElementById('modal-state').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal-state').classList.add('hidden');
}

async function saveState() {
    const inputType = document.getElementById('s-input-type').value;
    const payload = {
        flow_id:             flowId,
        key:                 document.getElementById('s-key').value.trim(),
        label:               document.getElementById('s-label').value.trim(),
        message:             document.getElementById('s-message').value.trim(),
        images:              stateImages,
        input_type:          inputType,
        sort_order:          parseInt(document.getElementById('s-order').value) || 0,
        is_active:           document.getElementById('s-active').checked ? 1 : 0,
        validation_pattern:  inputType === 'text'   ? (document.getElementById('s-val-pattern').value.trim() || null) : null,
        validation_min:      inputType === 'number'  ? (document.getElementById('s-val-min').value !== '' ? parseInt(document.getElementById('s-val-min').value) : null) : null,
        validation_max:      inputType === 'number'  ? (document.getElementById('s-val-max').value !== '' ? parseInt(document.getElementById('s-val-max').value) : null) : null,
        validation_error:    ['text','number'].includes(inputType) ? (document.getElementById('s-val-error').value.trim() || null) : null,
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

// ── Helpers escape ────────────────────────────────────────────
function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Tabs del panel derecho ────────────────────────────────────
function showTab(tabId) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('text-indigo-600','border-indigo-500');
        b.classList.add('text-gray-500','border-transparent');
    });
    document.getElementById(tabId).classList.remove('hidden');
    const btn = document.getElementById('btn-' + tabId);
    if (btn) { btn.classList.add('text-indigo-600','border-indigo-500'); btn.classList.remove('text-gray-500','border-transparent'); }
}

function toggleValTab() {
    const type = document.getElementById('e-input-type')?.value;
    const sec  = document.getElementById('e-val-section');
    const num  = document.getElementById('e-val-number');
    const txt  = document.getElementById('e-val-text');
    const opt  = document.getElementById('e-val-option');
    const err  = document.getElementById('e-val-error-wrap');
    if (!sec) return;
    [num, txt, opt].forEach(el => el && (el.style.display='none'));
    if (err) err.style.display = 'block';
    if      (type === 'number') { sec.classList.remove('hidden'); num.style.display='block'; }
    else if (type === 'text')   { sec.classList.remove('hidden'); txt.style.display='block'; }
    else if (type === 'option') { sec.classList.remove('hidden'); opt.style.display='block'; err.style.display='none'; }
    else                        { sec.classList.add('hidden'); }
}

function setPatternInline(p) { const el = document.getElementById('e-val-pattern'); if(el) el.value=p; }

// ── Renderizar previews de imágenes (inline panel) ────────────
function renderPreviewsInline() {
    const c = document.getElementById('e-image-previews');
    if (!c) return;
    c.innerHTML = stateImages.map((url,i) => `
        <div class="relative group w-16 h-16">
            <img src="${url}" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
            <button onclick="removeImageInline(${i})" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs hidden group-hover:flex items-center justify-center">✕</button>
        </div>`).join('');
    if (stateImages.length < 5) {
        c.innerHTML += `<div onclick="document.getElementById('e-image-input').click()" class="w-16 h-16 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center cursor-pointer hover:border-indigo-300 text-gray-300 text-xl">+</div>`;
    }
}
function removeImageInline(i) { stateImages.splice(i,1); renderPreviewsInline(); }
async function uploadImagesInline(files) {
    const rem = 5 - stateImages.length;
    for (const file of Array.from(files).slice(0, rem)) {
        const fd = new FormData(); fd.append('image', file); fd.append('_token', csrf);
        try { const r = await fetch(`${baseUrl}/upload-image`,{method:'POST',body:fd}); const d=await r.json(); if(d.ok){stateImages.push(d.url);renderPreviewsInline();} } catch(e){}
    }
}

// ── Seleccionar estado → panel con tabs editables ─────────────
function selectState(id) {
    document.querySelectorAll('.state-row').forEach(r => r.classList.remove('bg-indigo-50','border-l-2','border-indigo-500'));
    const row = document.getElementById('row-' + id);
    if (row) row.classList.add('bg-indigo-50','border-l-2','border-indigo-500');

    const state = states.find(s => s.id === id);
    if (!state) return;

    stateImages = state.images || [];

    const inputTypeOpts = Object.entries(inputTypes).map(([v,l]) =>
        `<option value="${v}" ${state.input_type===v?'selected':''}>${l}</option>`).join('');

    const actionOpts = Object.entries(actions).map(([v,l]) => `<option value="${v}">${l}</option>`).join('');

    const otherStates = states.filter(s => s.id !== id);
    const toStateOpts = otherStates.map(s => `<option value="${s.id}">${escHtml(s.label)} (${s.key})</option>`).join('');

    const transHtml = state.transitions.length === 0
        ? '<p class="text-xs text-gray-400 text-center py-6">Sin transiciones — agrega una para definir a dónde va el flujo</p>'
        : state.transitions.map(t => `
            <div class="flex items-center gap-3 py-2.5 border-b border-gray-100 last:border-0">
                <div class="flex-shrink-0 text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded font-mono font-medium min-w-12 text-center">${escHtml(t.trigger||'*')}</div>
                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="flex-1 min-w-0">
                    <span class="text-sm text-gray-700">${escHtml(t.to_state?.label||'?')}</span>
                    ${t.action && t.action!=='none' ? `<span class="ml-2 text-xs bg-yellow-50 text-yellow-700 px-1.5 py-0.5 rounded">${escHtml(actions[t.action]||t.action)}</span>` : ''}
                </div>
                <button onclick="deleteTransition(${t.id})" class="text-gray-300 hover:text-red-500 transition flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>`).join('');

    document.getElementById('detail-panel').innerHTML = `
    <div class="max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-semibold text-gray-900">${escHtml(state.label)}</h2>
                <span class="text-xs font-mono bg-gray-100 text-gray-500 px-2 py-0.5 rounded mt-1 inline-block">${escHtml(state.key)}</span>
            </div>
            <button onclick="deleteState(${id})" class="text-xs bg-red-50 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-100 transition">Eliminar</button>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            {{-- Tabs --}}
            <div class="flex border-b border-gray-200">
                <button id="btn-tab-msg"   onclick="showTab('tab-msg')"   class="tab-btn text-indigo-600 border-b-2 border-indigo-500 px-5 py-3 text-sm font-medium">Mensaje</button>
                <button id="btn-tab-val"   onclick="showTab('tab-val')"   class="tab-btn text-gray-500 border-b-2 border-transparent px-5 py-3 text-sm font-medium hover:text-gray-700">Validación</button>
                <button id="btn-tab-trans" onclick="showTab('tab-trans')" class="tab-btn text-gray-500 border-b-2 border-transparent px-5 py-3 text-sm font-medium hover:text-gray-700">
                    Transiciones
                    <span class="ml-1.5 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">${state.transitions.length}</span>
                </button>
            </div>

            {{-- Tab Mensaje --}}
            <div id="tab-msg" class="tab-panel p-5 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">Nombre visible</label>
                        <input id="e-label" type="text" value="${escHtml(state.label)}" class="input">
                    </div>
                    <div>
                        <label class="label">Tipo de entrada</label>
                        <select id="e-input-type" class="input" onchange="toggleValTab()">${inputTypeOpts}</select>
                    </div>
                </div>
                <div>
                    <label class="label">Mensaje del bot</label>
                    <textarea id="e-message" rows="6" class="input resize-none">${escHtml(state.message)}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Variables: <code class="bg-gray-100 px-1 rounded">{negocio}</code> <code class="bg-gray-100 px-1 rounded">{rifas_lista}</code> <code class="bg-gray-100 px-1 rounded">{rifa_nombre}</code> <code class="bg-gray-100 px-1 rounded">{rifa_precio}</code> <code class="bg-gray-100 px-1 rounded">{rifa_total}</code> <code class="bg-gray-100 px-1 rounded">{nombre}</code> <code class="bg-gray-100 px-1 rounded">{dni}</code> <code class="bg-gray-100 px-1 rounded">{ciudad}</code></p>
                </div>
                <div>
                    <label class="label">Imágenes <span class="text-gray-400 font-normal">(hasta 5)</span></label>
                    <div ondragover="event.preventDefault()" ondrop="event.preventDefault();uploadImagesInline(event.dataTransfer.files)"
                         onclick="document.getElementById('e-image-input').click()"
                         class="border-2 border-dashed border-gray-200 rounded-xl p-3 text-center cursor-pointer hover:border-indigo-300 transition">
                        <p class="text-xs text-gray-400">Clic o arrastra imágenes aquí</p>
                    </div>
                    <input type="file" id="e-image-input" accept="image/*" multiple class="hidden" onchange="uploadImagesInline(this.files)">
                    <div id="e-image-previews" class="flex flex-wrap gap-2 mt-2"></div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="e-active" ${state.is_active ? 'checked' : ''} class="w-4 h-4 rounded text-indigo-600">
                    <label for="e-active" class="text-sm text-gray-700">Estado activo</label>
                </div>
                <button onclick="saveStateInline(${id})" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition">Guardar cambios</button>
            </div>

            {{-- Tab Validación --}}
            <div id="tab-val" class="tab-panel hidden p-5 space-y-4">
                <div id="e-val-section" class="space-y-4">
                    <div id="e-val-number" style="display:none">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label">Valor mínimo</label>
                                <input type="number" id="e-val-min" value="${state.validation_min??''}" placeholder="ej: 1" class="input">
                            </div>
                            <div>
                                <label class="label">Valor máximo</label>
                                <input type="number" id="e-val-max" value="${state.validation_max??''}" placeholder="ej: 10" class="input">
                            </div>
                        </div>
                    </div>
                    <div id="e-val-text" style="display:none">
                        <label class="label">Patrón de validación (regex)</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <button type="button" onclick="setPatternInline('^\\\\d{8}$')" class="text-xs bg-white border border-gray-200 px-2 py-1 rounded hover:bg-indigo-50 hover:border-indigo-300 transition">DNI (8 dígitos)</button>
                            <button type="button" onclick="setPatternInline('^\\\\d{9}$')" class="text-xs bg-white border border-gray-200 px-2 py-1 rounded hover:bg-indigo-50 hover:border-indigo-300 transition">Celular (9 dígitos)</button>
                            <button type="button" onclick="setPatternInline('^[a-zA-Z\\u00C0-\\u017E\\\\s]{2,}$')" class="text-xs bg-white border border-gray-200 px-2 py-1 rounded hover:bg-indigo-50 hover:border-indigo-300 transition">Solo letras</button>
                        </div>
                        <input type="text" id="e-val-pattern" value="${escHtml(state.validation_pattern||'')}" placeholder="ej: ^\\d{8}$ para DNI" class="input font-mono text-xs">
                    </div>
                    <div id="e-val-option" style="display:none">
                        <p class="text-xs bg-blue-50 text-blue-700 px-3 py-2 rounded-lg">Las opciones válidas se definen por los <strong>triggers</strong> en la pestaña Transiciones.</p>
                    </div>
                    <div id="e-val-error-wrap">
                        <label class="label">Mensaje cuando no sea válido</label>
                        <input type="text" id="e-val-error" value="${escHtml(state.validation_error||'')}" placeholder="ej: Por favor escribe solo números" class="input">
                    </div>
                </div>
                <p id="e-val-none" class="text-xs text-gray-400 text-center py-4" style="display:none">Cambia el tipo de entrada en la pestaña Mensaje para configurar validación.</p>
                <button onclick="saveStateInline(${id})" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition">Guardar cambios</button>
            </div>

            {{-- Tab Transiciones --}}
            <div id="tab-trans" class="tab-panel hidden p-5">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-semibold text-gray-800">Transiciones</p>
                    <button onclick="openTransition(${id})" class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition">+ Agregar</button>
                </div>
                ${transHtml}
            </div>
        </div>
    </div>`;

    renderPreviewsInline();
    toggleValTab();
}

// ── Guardar estado desde panel inline ─────────────────────────
async function saveStateInline(id) {
    const inputType = document.getElementById('e-input-type').value;
    const payload = {
        label:               document.getElementById('e-label').value.trim(),
        message:             document.getElementById('e-message').value.trim(),
        images:              stateImages,
        input_type:          inputType,
        is_active:           document.getElementById('e-active').checked ? 1 : 0,
        validation_pattern:  inputType === 'text'   ? (document.getElementById('e-val-pattern').value.trim() || null) : null,
        validation_min:      inputType === 'number' ? (document.getElementById('e-val-min').value !== '' ? parseInt(document.getElementById('e-val-min').value) : null) : null,
        validation_max:      inputType === 'number' ? (document.getElementById('e-val-max').value !== '' ? parseInt(document.getElementById('e-val-max').value) : null) : null,
        validation_error:    ['text','number'].includes(inputType) ? (document.getElementById('e-val-error').value.trim() || null) : null,
    };
    if (!payload.label || !payload.message) { alert('Completa nombre y mensaje'); return; }
    const btn = event.target; btn.disabled = true; btn.textContent = 'Guardando…';
    try {
        const r = await fetch(`${baseUrl}/states/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' },
            body: JSON.stringify(payload)
        });
        const d = await r.json();
        if (d.ok) {
            // Actualizar estado local
            const idx = states.findIndex(s => s.id === id);
            if (idx >= 0) Object.assign(states[idx], d.state, { images: stateImages });
            // Actualizar sidebar label
            const row = document.getElementById('row-' + id);
            if (row) row.querySelector('span.font-medium')?.setAttribute('textContent', payload.label);
            btn.textContent = '✓ Guardado'; btn.classList.add('bg-green-600'); btn.classList.remove('bg-indigo-600');
            setTimeout(() => { btn.textContent='Guardar cambios'; btn.classList.remove('bg-green-600'); btn.classList.add('bg-indigo-600'); btn.disabled=false; location.reload(); }, 1000);
        } else { alert(d.message || 'Error al guardar'); btn.disabled=false; btn.textContent='Guardar cambios'; }
    } catch(e) { alert('Error de conexión'); btn.disabled=false; btn.textContent='Guardar cambios'; }
}

// editState ya no abre modal — abre el panel inline
function editState(id) { selectState(id); }

async function deleteState(id) {
    if (!confirm('¿Eliminar este estado? También se eliminarán sus transiciones.')) return;
    const r = await fetch(`${baseUrl}/states/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' }
    });
    if ((await r.json()).ok) location.reload();
}

// ── Reordenar estados ─────────────────────────────────────────
async function moveState(id, direction) {
    const r = await fetch(`${baseUrl}/states/${id}/move`, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf, 'Accept':'application/json' },
        body: JSON.stringify({ direction })
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

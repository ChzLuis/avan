<x-portal-layout layout="comercial" :project="$project" pageTitle="Pedidos Bot">
<div class="flex flex-col h-full overflow-hidden">

{{-- Header --}}
<div class="px-6 py-4 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-base font-semibold text-gray-800">Pedidos Bot</h1>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <div class="flex items-center gap-3 text-xs text-gray-500">
        <span>Total: <strong>{{ $ventas->count() }}</strong></span>
        <span class="text-yellow-600">Pendientes: <strong>{{ $ventas->where('status','pendiente')->count() }}</strong></span>
        <span class="text-blue-600">Pagados: <strong>{{ $ventas->where('status','pagado')->count() }}</strong></span>
        <span class="text-green-600">Enviados: <strong>{{ $ventas->where('status','enviado')->count() }}</strong></span>
    </div>
</div>

{{-- Content --}}
<div class="flex-1 overflow-y-auto p-6 bg-gray-50/30">

@if($ventas->isEmpty())
<div class="text-center py-20 text-gray-400">
    <div class="text-5xl mb-3">🎟️</div>
    <p class="text-sm">Aún no hay ventas registradas</p>
</div>
@else
<div class="space-y-3" id="ventas-list">
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
<div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center gap-3"
     id="rv-{{ $v->id }}">

    {{-- Datos participante --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-semibold text-gray-800">{{ $v->nombre ?? '—' }}</span>
            @if($v->dni)
            <span class="text-xs text-gray-400">DNI: {{ $v->dni }}</span>
            @endif
            @if($v->ciudad)
            <span class="text-xs text-gray-400">📍 {{ $v->ciudad }}</span>
            @endif
            <span class="text-xs text-gray-400">📱 {{ $v->wa_number }}</span>
        </div>
        <div class="flex items-center gap-2 mt-1 flex-wrap">
            <span class="text-xs font-medium text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full">
                {{ $v->rifa?->nombre ?? $v->plan_nombre }}
            </span>
            <span class="text-xs text-gray-500">{{ $v->tickets }} ticket(s)</span>
            <span class="text-xs font-bold text-gray-800">S/ {{ number_format($v->monto, 2) }}</span>
            @if($v->ticket_code)
            <span class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ $v->ticket_code }}</span>
            @endif
            @if($v->ticket_numbers)
            <span class="text-xs text-indigo-600">#{{ implode(', #', array_map(fn($n) => str_pad($n,5,'0',STR_PAD_LEFT), $v->ticket_numbers)) }}</span>
            @endif
        </div>
        <div class="text-xs text-gray-400 mt-1">{{ $v->created_at->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Comprobante --}}
    @if($v->payment_proof)
    <a href="{{ asset($v->payment_proof) }}" target="_blank"
       class="text-xs text-indigo-600 hover:underline flex-shrink-0">
        🧾 Comprobante
    </a>
    @endif

    {{-- Status + Acciones --}}
    <div class="flex items-center gap-2 flex-shrink-0">
        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusClass }}">{{ $statusLabel }}</span>

        @if($v->status === 'pendiente')
        <button onclick="rifaValidar({{ $v->id }})"
                class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">
            ✓ Validar
        </button>
        <button onclick="rifaCancelar({{ $v->id }})"
                class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition">
            Cancelar
        </button>
        @endif

        @if($v->status === 'pagado')
        <a href="{{ route('rifas.ticket.preview', $v->id) }}" target="_blank"
           class="text-xs bg-purple-100 text-purple-700 px-3 py-1.5 rounded-lg hover:bg-purple-200 transition">
            👁️ Boleto
        </a>
        <button onclick="rifaEnviar({{ $v->id }})"
                class="text-xs bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 transition">
            🎟️ Enviar ticket
        </button>
        @endif

        @if($v->status === 'enviado')
        <a href="{{ route('rifas.ticket.preview', $v->id) }}" target="_blank"
           class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition">
            👁️ Ver boleto
        </a>
        @endif
    </div>
</div>
@endforeach
</div>
@endif

</div>
</div>

{{-- Toast --}}
<div id="toast" class="fixed bottom-6 right-6 hidden z-50">
    <div id="toast-inner" class="px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white bg-gray-800"></div>
</div>

<script>
const csrf = document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}';

async function rifaValidar(id) {
    if (!confirm('¿Validar este pago y asignar números?')) return;
    const r = await fetch(`/bixosales/rifas/${id}/validar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) { showToast('Pago validado ✓', 'success'); setTimeout(()=>location.reload(), 1000); }
    else showToast('Error al validar', 'error');
}

async function rifaEnviar(id) {
    if (!confirm('¿Generar y enviar el ticket por WhatsApp?')) return;
    showToast('Generando ticket...', 'info');
    const r = await fetch(`/bixosales/rifas/${id}/enviar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) { showToast('Ticket enviado ✓', 'success'); setTimeout(()=>location.reload(), 1000); }
    else showToast('Error al enviar', 'error');
}

async function rifaCancelar(id) {
    if (!confirm('¿Cancelar esta venta?')) return;
    const r = await fetch(`/bixosales/rifas/${id}/cancelar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) { showToast('Venta cancelada', 'error'); setTimeout(()=>location.reload(), 1000); }
}

let _t;
function showToast(msg, type='info') {
    const el = document.getElementById('toast');
    const inner = document.getElementById('toast-inner');
    const colors = { success:'bg-green-600', error:'bg-red-600', info:'bg-gray-800' };
    inner.className = `px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white ${colors[type]}`;
    inner.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(_t);
    _t = setTimeout(() => el.classList.add('hidden'), 3000);
}
</script>

</x-portal-layout>

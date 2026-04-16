<x-app-layout>
<x-slot name="slot">

<div class="flex flex-col h-full w-full overflow-hidden">

{{-- TOP BAR --}}
<div class="px-6 py-3 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
        <h1 class="text-base font-semibold text-gray-800">Ventas de Rifa</h1>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500">Total: <strong>{{ $ventas->count() }}</strong></span>
        <span class="text-xs text-gray-500 ml-3">Pendientes: <strong class="text-yellow-600">{{ $ventas->where('status','pendiente')->count() }}</strong></span>
        <span class="text-xs text-gray-500 ml-1">Pagados: <strong class="text-blue-600">{{ $ventas->where('status','pagado')->count() }}</strong></span>
        <span class="text-xs text-gray-500 ml-1">Enviados: <strong class="text-green-600">{{ $ventas->where('status','enviado')->count() }}</strong></span>
    </div>
</div>

{{-- CONTENT --}}
<div class="flex-1 overflow-y-auto bg-gray-50/30 p-6">

@if($ventas->isEmpty())
<div class="text-center py-20 text-gray-400">
    <div class="text-4xl mb-3">🎟️</div>
    <p class="text-sm">Aún no hay ventas registradas</p>
</div>
@else
<div class="space-y-3">
@foreach($ventas as $v)
<div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col sm:flex-row sm:items-center gap-4"
     id="venta-{{ $v->id }}">

    {{-- Info principal --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-semibold text-gray-800">{{ $v->nombre ?? '—' }}</span>
            <span class="text-xs text-gray-400">DNI: {{ $v->dni ?? '—' }}</span>
            <span class="text-xs text-gray-400">WA: {{ $v->wa_number }}</span>
        </div>
        <div class="flex items-center gap-2 mt-1 flex-wrap">
            <span class="text-xs font-medium text-purple-700 bg-purple-50 px-2 py-0.5 rounded-full">{{ $v->plan_nombre }}</span>
            <span class="text-xs text-gray-500">{{ $v->tickets }} ticket(s)</span>
            <span class="text-xs font-semibold text-gray-700">S/ {{ number_format($v->monto, 2) }}</span>
            @if($v->ticket_code)
            <span class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ $v->ticket_code }}</span>
            @endif
        </div>
        <div class="text-xs text-gray-400 mt-1">{{ $v->created_at->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Comprobante --}}
    <div class="flex-shrink-0">
        @if($v->payment_proof)
        <a href="{{ asset($v->payment_proof) }}" target="_blank"
           class="flex items-center gap-1 text-xs text-indigo-600 hover:underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Ver comprobante
        </a>
        @else
        <span class="text-xs text-gray-400">Sin comprobante</span>
        @endif
    </div>

    {{-- Status + acciones --}}
    <div class="flex items-center gap-2 flex-shrink-0">
        @php
        $statusClass = match($v->status) {
            'pendiente' => 'bg-yellow-100 text-yellow-700',
            'pagado'    => 'bg-blue-100 text-blue-700',
            'enviado'   => 'bg-green-100 text-green-700',
            'cancelado' => 'bg-red-100 text-red-700',
            default     => 'bg-gray-100 text-gray-600',
        };
        $statusLabel = match($v->status) {
            'pendiente' => 'Pendiente',
            'pagado'    => 'Pagado',
            'enviado'   => 'Enviado',
            'cancelado' => 'Cancelado',
            default     => $v->status,
        };
        @endphp
        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $statusClass }}">{{ $statusLabel }}</span>

        @if($v->status === 'pendiente')
        <button onclick="validar({{ $v->id }})"
                class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition">
            ✓ Validar pago
        </button>
        <button onclick="cancelar({{ $v->id }})"
                class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-200 transition">
            Cancelar
        </button>
        @endif

        @if($v->status === 'pagado')
        <a href="{{ route('rifas.ticket.preview', $v->id) }}" target="_blank"
           class="text-xs bg-purple-600 text-white px-3 py-1.5 rounded-lg hover:bg-purple-700 transition">
            👁️ Ver boleto
        </a>
        <button onclick="enviarTicket({{ $v->id }})"
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

<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;

async function validar(id) {
    if (!confirm('¿Validar este pago?')) return;
    const r = await fetch(`/rifas/${id}/validar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) location.reload();
    else alert('Error al validar');
}

async function enviarTicket(id) {
    if (!confirm('¿Enviar el ticket por WhatsApp?')) return;
    const btn = event.target;
    btn.textContent = 'Enviando...';
    btn.disabled = true;
    const r = await fetch(`/rifas/${id}/enviar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) location.reload();
    else { alert('Error al enviar'); btn.disabled = false; btn.textContent = '🎟️ Enviar ticket'; }
}

async function cancelar(id) {
    if (!confirm('¿Cancelar esta venta?')) return;
    const r = await fetch(`/rifas/${id}/cancelar`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if (d.ok) location.reload();
}
</script>

</x-slot>
</x-app-layout>

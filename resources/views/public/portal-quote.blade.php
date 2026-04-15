<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@php
  $primaryColor = $settings['primary_color'] ?? '#4f46e5';
  $logoUrl      = $project->logo_url ? asset('storage/'.$project->logo_url) : null;
  $statusMap    = [
      'draft'    => ['label'=>'Borrador',  'color'=>'#64748b', 'bg'=>'#f1f5f9'],
      'sent'     => ['label'=>'Pendiente', 'color'=>'#d97706', 'bg'=>'#fef3c7'],
      'accepted' => ['label'=>'Aceptada',  'color'=>'#16a34a', 'bg'=>'#dcfce7'],
      'rejected' => ['label'=>'Rechazada', 'color'=>'#dc2626', 'bg'=>'#fee2e2'],
  ];
  $st = $statusMap[$quote->status] ?? $statusMap['draft'];
@endphp
<title>Cotización #{{ $quote->id }} — {{ $project->name }}</title>
<meta name="robots" content="noindex, nofollow">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
  :root { --c: {{ $primaryColor }}; }
  body  { font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; }
  .btn-p { background: var(--c); color:#fff; transition: filter .15s; }
  .btn-p:hover { filter: brightness(.92); }
  @media print {
    .no-print { display: none !important; }
    body { background: white; }
    .print-card { box-shadow: none !important; border: none !important; }
  }
</style>
</head>
<body class="min-h-screen" x-data="{
  accepting: false,
  accepted: {{ $quote->status === 'accepted' ? 'true' : 'false' }},
  rejected: {{ $quote->status === 'rejected' ? 'true' : 'false' }},
  notes: '',
  error: '',

  async accept() {
    this.accepting = true; this.error = '';
    try {
      const res = await fetch('{{ route('portal.quote.accept', ['slug'=>$project->slug,'token'=>$quote->token]) }}', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}' },
        body: JSON.stringify({ notes: this.notes })
      });
      const data = await res.json();
      if (data.ok) { this.accepted = true; }
      else { this.error = data.message || 'No se pudo procesar. Inténtalo de nuevo.'; }
    } catch(e) { this.error = 'Error de conexión.'; }
    this.accepting = false;
  }
}">

{{-- HEADER --}}
<header class="bg-white border-b border-gray-100 px-4 py-4 no-print">
  <div class="max-w-3xl mx-auto flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      @if($logoUrl)
      <img src="{{ $logoUrl }}" alt="{{ $project->name }}" class="h-9 w-auto object-contain rounded-lg">
      @else
      <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black"
           style="background:{{ $primaryColor }}">
        {{ strtoupper(substr($project->name,0,1)) }}
      </div>
      @endif
      <div>
        <p class="font-bold text-gray-900 text-sm">{{ $project->name }}</p>
        <p class="text-xs text-gray-400">Portal del cliente</p>
      </div>
    </div>
    <button onclick="window.print()"
            class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-800 border border-gray-200 rounded-lg px-3 py-1.5 transition">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
      </svg>
      Imprimir / PDF
    </button>
  </div>
</header>

{{-- CONTENIDO --}}
<main class="max-w-3xl mx-auto px-4 py-8 space-y-6">

  {{-- Tarjeta principal --}}
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden print-card">

    {{-- Header de la cotización --}}
    <div class="px-4 sm:px-8 py-4 sm:py-6 border-b border-gray-100" style="background: linear-gradient(135deg, {{ $primaryColor }}15, {{ $primaryColor }}05)">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="text-xs font-bold uppercase tracking-widest" style="color:{{ $primaryColor }}">
              Cotización
            </span>
            <span class="text-xs px-2 py-0.5 rounded-full font-semibold"
                  style="background:{{ $st['bg'] }};color:{{ $st['color'] }}">
              {{ $st['label'] }}
            </span>
          </div>
          <h1 class="text-3xl font-black text-gray-900">#{{ str_pad($quote->id, 4, '0', STR_PAD_LEFT) }}</h1>
          <p class="text-sm text-gray-500 mt-1">Emitida el {{ $quote->created_at->format('d \d\e F, Y') }}</p>
          @if($quote->valid_until)
          <p class="text-sm text-gray-500">Válida hasta el <strong>{{ $quote->valid_until->format('d \d\e F, Y') }}</strong></p>
          @endif
        </div>
        <div class="text-right">
          @if($logoUrl)
          <img src="{{ $logoUrl }}" alt="{{ $project->name }}" class="h-12 w-auto object-contain ml-auto mb-2">
          @endif
          <p class="font-bold text-gray-800">{{ $project->name }}</p>
          @if($project->phone)
          <p class="text-xs text-gray-500">{{ $project->phone }}</p>
          @endif
          @if($project->address)
          <p class="text-xs text-gray-500">{{ $project->address }}</p>
          @endif
        </div>
      </div>
    </div>

    {{-- Datos del cliente --}}
    <div class="px-4 sm:px-8 py-4 sm:py-5 border-b border-gray-100 bg-gray-50">
      <p class="text-xs font-bold uppercase tracking-wide text-gray-400 mb-3">Preparado para</p>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
          <p class="text-xs text-gray-400">Cliente</p>
          <p class="font-semibold text-gray-800">{{ $quote->client_name }}</p>
        </div>
        @if($quote->client_phone)
        <div>
          <p class="text-xs text-gray-400">Teléfono / WhatsApp</p>
          <p class="font-semibold text-gray-800">{{ $quote->client_phone }}</p>
        </div>
        @endif
        @if($quote->client_email)
        <div>
          <p class="text-xs text-gray-400">Correo</p>
          <p class="font-semibold text-gray-800">{{ $quote->client_email }}</p>
        </div>
        @endif
      </div>
    </div>

    {{-- Tabla de items --}}
    <div class="px-4 sm:px-8 py-4 sm:py-6">
      <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b-2 border-gray-200">
            <th class="text-left pb-3 text-xs font-bold uppercase tracking-wide text-gray-400 w-1/2">Descripción</th>
            <th class="text-right pb-3 text-xs font-bold uppercase tracking-wide text-gray-400">Precio unit.</th>
            <th class="text-right pb-3 text-xs font-bold uppercase tracking-wide text-gray-400">Cant.</th>
            <th class="text-right pb-3 text-xs font-bold uppercase tracking-wide text-gray-400">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @foreach($quote->items as $item)
          <tr>
            <td class="py-3 font-medium text-gray-800">{{ $item->description ?? $item->name ?? '—' }}</td>
            <td class="py-3 text-right text-gray-600">S/ {{ number_format($item->price, 2) }}</td>
            <td class="py-3 text-right text-gray-600">{{ $item->quantity }}</td>
            <td class="py-3 text-right font-semibold text-gray-900">S/ {{ number_format($item->price * $item->quantity, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr class="border-t-2 border-gray-200">
            <td colspan="3" class="pt-4 text-right font-bold text-gray-700 pr-4">Total</td>
            <td class="pt-4 text-right font-black text-xl" style="color:{{ $primaryColor }}">
              S/ {{ number_format($quote->total, 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
      </div>
    </div>

    {{-- Condiciones --}}
    @if($quote->payment_method || $quote->payment_condition || $quote->notes)
    <div class="px-4 sm:px-8 pb-4 sm:pb-6 space-y-3">
      @if($quote->payment_method || $quote->payment_condition)
      <div class="flex flex-wrap gap-3">
        @if($quote->payment_method)
        <div class="bg-gray-50 rounded-xl px-4 py-2.5 text-sm">
          <p class="text-xs text-gray-400">Método de pago</p>
          <p class="font-semibold text-gray-700">{{ $quote->payment_method }}</p>
        </div>
        @endif
        @if($quote->payment_condition)
        <div class="bg-gray-50 rounded-xl px-4 py-2.5 text-sm">
          <p class="text-xs text-gray-400">Condición</p>
          <p class="font-semibold text-gray-700">{{ $quote->payment_condition }}</p>
        </div>
        @endif
      </div>
      @endif
      @if($quote->notes)
      <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
        <p class="text-xs font-semibold text-amber-700 mb-1">Notas</p>
        <p class="text-sm text-amber-900 leading-relaxed">{{ $quote->notes }}</p>
      </div>
      @endif
    </div>
    @endif
  </div>

  {{-- ACCIÓN DEL CLIENTE --}}
  <div class="no-print">

    {{-- Ya aceptada --}}
    <div x-show="accepted" class="bg-green-50 border border-green-200 rounded-2xl px-6 py-5 flex items-center gap-4">
      <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center flex-shrink-0">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
      <div>
        <p class="font-black text-green-900 text-lg">¡Cotización aceptada!</p>
        <p class="text-sm text-green-700">{{ $project->name }} fue notificado. Pronto se pondrán en contacto contigo.</p>
      </div>
    </div>

    {{-- Ya rechazada --}}
    <div x-show="rejected" class="bg-red-50 border border-red-200 rounded-2xl px-6 py-5">
      <p class="font-bold text-red-800">Esta cotización fue rechazada.</p>
      <p class="text-sm text-red-600 mt-1">Si deseas una nueva cotización, contacta a {{ $project->name }}.</p>
    </div>

    {{-- Pendiente de respuesta --}}
    <div x-show="!accepted && !rejected"
         class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-6 space-y-4">
      <div>
        <h3 class="font-black text-gray-900 text-lg">¿Apruebas esta cotización?</h3>
        <p class="text-sm text-gray-500 mt-1">Al aceptar, notificamos a <strong>{{ $project->name }}</strong> para proceder con tu pedido.</p>
      </div>
      <textarea x-model="notes" rows="2"
                placeholder="Comentario o instrucción adicional (opcional)"
                class="w-full border-2 border-gray-200 focus:border-indigo-400 rounded-xl px-4 py-3 text-sm outline-none resize-none transition"></textarea>
      <p x-show="error" class="text-red-500 text-sm font-medium" x-text="error"></p>
      <div class="flex flex-col sm:flex-row gap-3">
        <button @click="accept()" :disabled="accepting"
                class="flex-1 btn-p py-3.5 rounded-xl font-black text-sm flex items-center justify-center gap-2 disabled:opacity-60">
          <svg x-show="!accepting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
          <svg x-show="accepting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          <span x-text="accepting ? 'Procesando...' : 'Aceptar cotización'"></span>
        </button>
        @if($project->whatsapp)
        <a href="https://wa.me/{{ preg_replace('/\D/','',$project->whatsapp) }}?text={{ urlencode('Hola, tengo consultas sobre la cotización #'.str_pad($quote->id,4,'0',STR_PAD_LEFT)) }}"
           target="_blank"
           class="flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl border-2 border-gray-200 hover:border-green-400 hover:bg-green-50 text-sm font-semibold text-gray-700 transition">
          <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.535 5.845L.057 23.571l5.926-1.553A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.901 0-3.681-.506-5.215-1.389l-.375-.222-3.516.922.938-3.428-.244-.394A9.957 9.957 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
          </svg>
          Consultar por WhatsApp
        </a>
        @endif
      </div>
    </div>
  </div>

</main>

<footer class="text-center py-6 text-xs text-gray-400 no-print">
  <p>{{ $project->name }} &nbsp;·&nbsp; Portal de clientes &nbsp;·&nbsp;
    <a href="{{ url('/b/'.$project->slug) }}" class="hover:underline">Volver al portal</a>
  </p>
</footer>
</body>
</html>

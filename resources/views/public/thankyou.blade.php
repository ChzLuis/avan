<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>¡Gracias por tu pedido! — {{ $project->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $primaryColor = $settings['primary_color'] ?? '#4f46e5';
        $logoUrl      = $project->logo_url ?? null;
        $whatsapp     = preg_replace('/\D/', '', $project->whatsapp ?? '');
        $currency     = $settings['currency'] ?? 'PEN';
        $isQuote      = str_contains(strtolower($order->status ?? ''), 'quote') || ($order->items->isEmpty() && $order->total == 0);
    @endphp
    <style>
        body { font-family: 'Inter', sans-serif; }
        .accent { color: {{ $primaryColor }}; }
        .btn-accent { background: {{ $primaryColor }}; color: #fff; }
        .btn-accent:hover { opacity: .9; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-start justify-center px-4 py-12">

<div class="w-full max-w-lg">

    {{-- Brand --}}
    <div class="flex items-center gap-3 mb-8">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $project->name }}" class="h-10 object-contain">
        @else
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm"
                 style="background:{{ $primaryColor }}">
                {{ strtoupper(substr($project->name, 0, 2)) }}
            </div>
        @endif
        <span class="font-bold text-gray-900">{{ $project->name }}</span>
    </div>

    {{-- Success card --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-4">
        {{-- Top banner --}}
        <div class="px-6 py-8 text-center border-b border-gray-100">
            <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-gray-900 mb-1">
                ¡Gracias, {{ explode(' ', $order->client_name)[0] }}!
            </h1>
            <p class="text-gray-500 text-sm">
                @if($order->status === 'draft')
                    Tu cotización fue recibida. Te contactaremos a la brevedad.
                @else
                    Tu pedido fue confirmado. Nos pondremos en contacto pronto.
                @endif
            </p>
            <p class="mt-3 text-xs text-gray-400">
                Pedido N° <span class="font-black text-gray-700 text-sm">#{{ $order->id }}</span>
            </p>
        </div>

        {{-- Order items --}}
        @if($order->items->isNotEmpty())
        <div class="px-6 py-4 border-b border-gray-100">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-3">Resumen del pedido</p>
            <div class="space-y-2">
                @foreach($order->items as $item)
                <div class="flex justify-between items-start text-sm">
                    <div class="flex-1 min-w-0 pr-4">
                        <span class="text-gray-800 font-medium">{{ $item->name }}</span>
                        @if($item->quantity > 1)
                        <span class="text-gray-400 ml-1">× {{ $item->quantity }}</span>
                        @endif
                    </div>
                    <span class="text-gray-700 font-semibold whitespace-nowrap">
                        {{ $currency }} {{ number_format($item->price * $item->quantity, 2) }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Totals --}}
        <div class="px-6 py-4 bg-gray-50 space-y-1.5 text-sm">
            @php
                $subtotal = $order->items->sum(fn($i) => $i->price * $i->quantity);
            @endphp
            @if($order->discount && $order->discount > 0)
            <div class="flex justify-between text-gray-500">
                <span>Subtotal</span>
                <span>{{ $currency }} {{ number_format($subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-green-600 font-medium">
                <span>Descuento{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span>
                <span>- {{ $currency }} {{ number_format($order->discount, 2) }}</span>
            </div>
            @endif
            @if($order->shipping_cost && $order->shipping_cost > 0)
            <div class="flex justify-between text-gray-500">
                <span>Envío</span>
                <span>{{ $currency }} {{ number_format($order->shipping_cost, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between font-black text-gray-900 text-base pt-1.5 border-t border-gray-200">
                <span>Total</span>
                <span class="accent">{{ $currency }} {{ number_format($order->total, 2) }}</span>
            </div>
        </div>

        {{-- Delivery address --}}
        @if($order->delivery_address)
        <div class="px-6 py-3 border-t border-gray-100 text-sm text-gray-600">
            <span class="font-semibold">Entrega:</span> {{ $order->delivery_address }}
        </div>
        @endif

        {{-- Notes --}}
        @if($order->notes)
        <div class="px-6 py-3 border-t border-gray-100 text-sm text-gray-500 italic">
            "{{ $order->notes }}"
        </div>
        @endif
    </div>

    {{-- CTAs --}}
    <div class="space-y-3">
        @if($whatsapp)
        @php
            $waMsg = urlencode('Hola! Acabo de hacer el pedido #' . $order->id . ' en ' . $project->name . '. ¿Me pueden confirmar?');
        @endphp
        <a href="https://wa.me/{{ $whatsapp }}?text={{ $waMsg }}"
           target="_blank" rel="noopener"
           class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold text-sm transition btn-accent">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.121.55 4.112 1.514 5.845L.057 23.25l5.538-1.453A11.943 11.943 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.854 0-3.6-.5-5.107-1.375l-.366-.217-3.787.994 1.01-3.692-.238-.38A9.943 9.943 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
            </svg>
            Consultar por WhatsApp
        </a>
        @endif

        <a href="/{{ $project->slug }}"
           class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl font-semibold text-sm border-2 border-gray-200 text-gray-700 hover:bg-gray-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Seguir comprando
        </a>
    </div>

</div>

</body>
</html>

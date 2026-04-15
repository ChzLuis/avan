<x-app-layout>
<x-slot name="slot">
<div class="flex flex-1 overflow-hidden" x-data="{ mv: '{{ request()->has('s') ? 'detail' : 'list' }}' }">

{{-- PANEL LISTA --}}
<div class="list-panel flex-shrink-0" :class="{ 'mp-hidden': mv === 'detail' }">
    <div class="px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0">
        <h2 class="text-sm font-semibold text-gray-700">Pagos</h2>
        <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <div class="overflow-y-auto flex-1">
        @php
            $sections = [
                ['key'=>'methods',  'label'=>'Métodos aceptados', 'desc'=>'Qué ve el cliente al pagar',        'icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['key'=>'manual',   'label'=>'Pagos manuales',    'desc'=>'Yape, Plin, transferencia',         'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                ['key'=>'culqi',    'label'=>'Culqi',             'desc'=>'Tarjetas Visa/MC + Yape automático','icon'=>'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                ['key'=>'mp',       'label'=>'Mercado Pago',      'desc'=>'Multi-país LATAM',                  'icon'=>'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 13v-1m0-4c-1.657 0-3-.895-3-2s1.343-2 3-2 3-.895 3-2'],
            ];
            $current = request('s', 'methods');
        @endphp
        @foreach($sections as $s)
        <a href="{{ route('settings.payments') }}?s={{ $s['key'] }}"
           @click="mv = 'detail'"
           class="list-item {{ $current === $s['key'] ? 'selected' : '' }}">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                        {{ $current === $s['key'] ? 'bg-indigo-600' : 'bg-gray-100' }}">
                <svg class="w-4 h-4 {{ $current === $s['key'] ? 'text-white' : 'text-gray-500' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $s['icon'] }}"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800">{{ $s['label'] }}</p>
                <p class="text-xs text-gray-400">{{ $s['desc'] }}</p>
            </div>
            @php
                $badge = match($s['key']) {
                    'manual' => $project->setting('payment_manual_enabled','0')==='1' ? 'Activo' : null,
                    'culqi'  => $project->setting('culqi_enabled','0')==='1' ? 'Activo' : null,
                    'mp'     => $project->setting('mp_enabled','0')==='1' ? 'Activo' : null,
                    default  => null
                };
            @endphp
            @if($badge)
            <span class="text-[10px] bg-green-100 text-green-700 border border-green-200 px-1.5 py-0.5 rounded-full font-semibold flex-shrink-0">{{ $badge }}</span>
            @endif
        </a>
        @endforeach
    </div>
</div>

{{-- PANEL DETALLE --}}
<div class="detail-panel" :class="{ 'mp-active': mv === 'detail' }">

    {{-- Botón volver (solo mobile) --}}
    <button @click="mv = 'list'" type="button"
            class="md:hidden flex items-center gap-2 px-4 py-3 text-sm text-indigo-600 border-b border-gray-100 w-full hover:bg-gray-50 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver a Pagos
    </button>

    @if(session('success'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" x-cloak
         class="m-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-2.5 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @php
        $s = request('s', 'methods');
        $pid = $project->id;
        $paymentOptions = [
            ['key'=>'efectivo',      'label'=>'Efectivo',              'icon'=>'💵'],
            ['key'=>'yape',          'label'=>'Yape',                  'icon'=>'🟣'],
            ['key'=>'plin',          'label'=>'Plin',                  'icon'=>'🔵'],
            ['key'=>'transferencia', 'label'=>'Transferencia bancaria', 'icon'=>'🏦'],
            ['key'=>'tarjeta',       'label'=>'Tarjeta crédito/débito', 'icon'=>'💳'],
            ['key'=>'qr',            'label'=>'Pago con QR',           'icon'=>'📲'],
            ['key'=>'contra_entrega','label'=>'Contra entrega',         'icon'=>'🚚'],
        ];
        $savedPayments = json_decode($project->setting('accepted_payments', '[]'), true) ?? [];
        $manualMethods = [
            ['key'=>'yape',          'label'=>'Yape',                  'color'=>'#7c3aed', 'emoji'=>'🟣'],
            ['key'=>'plin',          'label'=>'Plin',                  'color'=>'#0284c7', 'emoji'=>'🔵'],
            ['key'=>'transferencia', 'label'=>'Transferencia bancaria', 'color'=>'#0891b2', 'emoji'=>'🏦'],
            ['key'=>'qr',            'label'=>'Pago con QR genérico',  'color'=>'#059669', 'emoji'=>'📲'],
            ['key'=>'contra_entrega','label'=>'Contra entrega',         'color'=>'#b45309', 'emoji'=>'🚚'],
        ];
        $savedManual = json_decode($project->setting('payment_manual_methods', '["yape","plin"]'), true) ?? [];
    @endphp

    {{-- ── Métodos aceptados ── --}}
    @if($s === 'methods')
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0">
            <h2 class="font-semibold text-gray-800">Métodos de pago aceptados</h2>
            <p class="text-xs text-gray-400 mt-0.5">Íconos visibles en el carrito de tu tienda online</p>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" action="{{ route('settings.payments.update') }}?s=methods"
                  class="space-y-5 max-w-lg">
                @csrf
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-700">
                    Selecciona todos los métodos que acepta tu negocio. Estos íconos aparecen en el paso final del carrito para que el cliente sepa cómo puede pagarte.
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach($paymentOptions as $pm)
                    <label class="flex items-center gap-2.5 p-3 rounded-xl border-2 cursor-pointer transition
                                  {{ in_array($pm['key'], $savedPayments) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                        <input type="checkbox" name="accepted_payments[]" value="{{ $pm['key'] }}"
                               {{ in_array($pm['key'], $savedPayments) ? 'checked' : '' }}
                               class="accent-indigo-600 w-4 h-4 flex-shrink-0">
                        <span class="text-lg leading-none">{{ $pm['icon'] }}</span>
                        <span class="text-sm text-gray-700 font-medium">{{ $pm['label'] }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="pt-2">
                    <button type="submit" class="btn-primary">Guardar métodos</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Pagos manuales ── --}}
    @if($s === 'manual')
    @php $manualEnabled = $project->setting('payment_manual_enabled', '0') === '1'; @endphp
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0 flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-gray-800">Pagos manuales</h2>
                <p class="text-xs text-gray-400 mt-0.5">Yape, Plin, transferencia bancaria</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                         {{ $manualEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $manualEnabled ? 'Activo' : 'Inactivo' }}
            </span>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.payments.update') }}?s=manual"
              class="space-y-6 max-w-lg"
              x-data="{ open: {{ $manualEnabled ? 'true' : 'false' }} }">
            @csrf
            <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition
                          {{ $manualEnabled ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-gray-50' }}">
                <input type="checkbox" name="payment_manual_enabled" value="1" @change="open=$el.checked"
                       {{ $manualEnabled ? 'checked' : '' }}
                       class="w-5 h-5 rounded accent-indigo-600 flex-shrink-0">
                <div>
                    <p class="font-semibold text-gray-800 text-sm">Activar pagos manuales</p>
                    <p class="text-xs text-gray-500 mt-0.5">El cliente podrá subir su número de operación después de confirmar el pedido</p>
                </div>
            </label>

            <div x-show="open" class="space-y-5">
                {{-- Métodos disponibles --}}
                <div>
                    <p class="text-xs font-semibold text-gray-700 mb-2">Métodos activos</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($manualMethods as $mm)
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-sm
                                      {{ in_array($mm['key'], $savedManual) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            <input type="checkbox" name="payment_manual_methods[]" value="{{ $mm['key'] }}"
                                   {{ in_array($mm['key'], $savedManual) ? 'checked' : '' }}
                                   class="accent-indigo-600 flex-shrink-0">
                            <span>{{ $mm['emoji'] }}</span>
                            <span class="text-gray-700 font-medium">{{ $mm['label'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Datos de cuenta --}}
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <p class="text-xs font-semibold text-gray-700">Datos de tu cuenta (el cliente los verá al pagar)</p>
                    </div>
                    <div class="p-4 space-y-4 bg-white">
                        <div>
                            <label class="label text-xs">Yape — Número o nombre</label>
                            <input type="text" name="payment_yape_number" class="input text-sm"
                                   placeholder="Ej: 955 354 646 — Juan García"
                                   value="{{ $project->setting('payment_yape_number') }}">
                        </div>
                        <div>
                            <label class="label text-xs">Plin — Número o nombre</label>
                            <input type="text" name="payment_plin_number" class="input text-sm"
                                   placeholder="Ej: 955 354 646 — Juan García"
                                   value="{{ $project->setting('payment_plin_number') }}">
                        </div>
                        <div>
                            <label class="label text-xs">Transferencia bancaria — Datos de cuenta</label>
                            <textarea name="payment_bank_details" class="input text-sm" rows="3"
                                      placeholder="Ej: BCP — Cta Corriente: 123-456789-0-01&#10;CCI: 002-123-456789-01">{{ $project->setting('payment_bank_details') }}</textarea>
                        </div>
                        <div>
                            <label class="label text-xs">Instrucciones adicionales (opcional)</label>
                            <input type="text" name="payment_manual_instructions" class="input text-sm"
                                   placeholder="Ej: Envía tu comprobante al WhatsApp luego de pagar"
                                   value="{{ $project->setting('payment_manual_instructions') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Guardar pagos manuales</button>
            </div>
        </form>
        </div>
    </div>
    @endif

    {{-- ── Culqi ── --}}
    @if($s === 'culqi')
    @php $culqiEnabled = $project->setting('culqi_enabled', '0') === '1'; @endphp
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0 flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-gray-800">Culqi</h2>
                <p class="text-xs text-gray-400 mt-0.5">Tarjetas Visa/Mastercard + Yape con cobro automático · ~3.5% por transacción</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-[10px] bg-orange-100 text-orange-700 font-bold px-1.5 py-0.5 rounded-full">Recomendado Perú</span>
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                             {{ $culqiEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $culqiEnabled ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.payments.update') }}?s=culqi"
              class="space-y-5 max-w-lg"
              x-data="{ open: {{ $culqiEnabled ? 'true' : 'false' }} }">
            @csrf
            <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition
                          {{ $culqiEnabled ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-gray-50' }}">
                <input type="checkbox" name="culqi_enabled" value="1" @change="open=$el.checked"
                       {{ $culqiEnabled ? 'checked' : '' }}
                       class="w-5 h-5 rounded accent-indigo-600 flex-shrink-0">
                <div>
                    <p class="font-semibold text-gray-800 text-sm">Activar Culqi</p>
                    <p class="text-xs text-gray-500 mt-0.5">El cliente podrá pagar con tarjeta o Yape directamente desde el carrito</p>
                </div>
            </label>

            <div x-show="open" class="space-y-5">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-700">
                    <p class="font-semibold mb-2">¿Cómo obtener tus llaves?</p>
                    <ol class="list-decimal pl-4 space-y-1 leading-relaxed">
                        <li>Regístrate en <strong>culqi.com</strong> como comercio</li>
                        <li>En tu panel → Desarrollo → API Keys</li>
                        <li>Copia tu <strong>Llave pública</strong> (pk_...) y <strong>Llave secreta</strong> (sk_...)</li>
                        <li>Para pruebas usa las llaves de <strong>Sandbox</strong></li>
                    </ol>
                </div>
                <div>
                    <label class="label">Llave pública <span class="text-gray-400 font-normal">(pk_test_... o pk_live_...)</span></label>
                    <input type="text" name="culqi_public_key" class="input font-mono"
                           placeholder="pk_test_xxxxxxxxxxxxx"
                           value="{{ $project->setting('culqi_public_key') }}">
                </div>
                <div>
                    <label class="label">Llave secreta <span class="text-gray-400 font-normal">(sk_test_... o sk_live_...)</span></label>
                    <input type="password" name="culqi_secret_key" class="input font-mono"
                           placeholder="sk_test_xxxxxxxxxxxxx"
                           value="{{ $project->setting('culqi_secret_key') ? '••••••••••••••••' : '' }}">
                    <p class="text-xs text-gray-400 mt-1">Se guarda encriptada. Nunca se muestra completa.</p>
                </div>
                <div x-data="{ mode: '{{ $project->setting('culqi_mode', 'test') }}' }">
                    <label class="label">Modo de operación</label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center gap-2 text-sm cursor-pointer p-3 rounded-xl border-2 flex-1 transition"
                               :class="mode==='test' ? 'border-amber-400 bg-amber-50' : 'border-gray-200'">
                            <input type="radio" name="culqi_mode" value="test" x-model="mode" class="accent-amber-500">
                            <span>🧪 <strong>Pruebas</strong> (sandbox)</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer p-3 rounded-xl border-2 flex-1 transition"
                               :class="mode==='live' ? 'border-green-400 bg-green-50' : 'border-gray-200'">
                            <input type="radio" name="culqi_mode" value="live" x-model="mode" class="accent-green-500">
                            <span>🚀 <strong>Producción</strong></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Guardar Culqi</button>
            </div>
        </form>
        </div>
    </div>
    @endif

    {{-- ── Mercado Pago ── --}}
    @if($s === 'mp')
    @php $mpEnabled = $project->setting('mp_enabled', '0') === '1'; @endphp
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-gray-100 bg-white flex-shrink-0 flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-gray-800">Mercado Pago</h2>
                <p class="text-xs text-gray-400 mt-0.5">Tarjetas, wallets, cuotas — disponible en toda LATAM</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="text-[10px] bg-cyan-100 text-cyan-700 font-bold px-1.5 py-0.5 rounded-full">Multi-país LATAM</span>
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                             {{ $mpEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $mpEnabled ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
        <form method="POST" action="{{ route('settings.payments.update') }}?s=mp"
              class="space-y-5 max-w-lg"
              x-data="{ open: {{ $mpEnabled ? 'true' : 'false' }} }">
            @csrf
            <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition
                          {{ $mpEnabled ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-gray-50' }}">
                <input type="checkbox" name="mp_enabled" value="1" @change="open=$el.checked"
                       {{ $mpEnabled ? 'checked' : '' }}
                       class="w-5 h-5 rounded accent-indigo-600 flex-shrink-0">
                <div>
                    <p class="font-semibold text-gray-800 text-sm">Activar Mercado Pago</p>
                    <p class="text-xs text-gray-500 mt-0.5">El cliente verá un botón de pago con Mercado Pago al confirmar el pedido</p>
                </div>
            </label>

            <div x-show="open" class="space-y-5">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-700">
                    <p class="font-semibold mb-2">¿Cómo obtener tu Access Token?</p>
                    <ol class="list-decimal pl-4 space-y-1 leading-relaxed">
                        <li>Entra a <strong>mercadopago.com.pe</strong> → Tu cuenta → Panel de desarrolladores</li>
                        <li>Crea una aplicación nueva</li>
                        <li>En Credenciales → copia el <strong>Access Token</strong> de producción o pruebas</li>
                    </ol>
                </div>
                <div>
                    <label class="label">Access Token <span class="text-gray-400 font-normal">(APP_USR-... o TEST-...)</span></label>
                    <input type="password" name="mp_access_token" class="input font-mono"
                           placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                           value="{{ $project->setting('mp_access_token') ? '••••••••••••••••' : '' }}">
                    <p class="text-xs text-gray-400 mt-1">Usa TEST-... para pruebas y APP_USR-... para producción.</p>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-500 flex gap-2">
                    <span class="text-lg leading-none flex-shrink-0">🌎</span>
                    <div>
                        <p class="font-medium text-gray-600 mb-1">Disponibilidad</p>
                        <p class="leading-relaxed">Mercado Pago está disponible en Perú, México, Colombia, Chile, Argentina, Brasil y más.
                        Para otros países puedes integrar Stripe (global) o PayU (LATAM) contactando a soporte BIXO.</p>
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary">Guardar Mercado Pago</button>
            </div>
        </form>
        </div>
    </div>
    @endif

</div>
</div>
</x-slot>
</x-app-layout>

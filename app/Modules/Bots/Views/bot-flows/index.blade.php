<x-portal-layout layout="panel" :project="$project" pageTitle="Bots">

<div class="p-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Constructor de bots</h1>
            <p class="text-xs text-gray-500">Crea flujos que responden con la info real de tu negocio (catálogo, precios, IA).</p>
        </div>
        @can('settings.negocio')
        <div class="flex items-center gap-2">
            {{-- Camino recomendado: un bot de tienda que ya funciona, en vez de
                 un lienzo en blanco donde hay que armar el flujo desde cero. --}}
            <form method="POST" action="{{ route('bot-flows.plantilla-comercial') }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    💬 Activar Bot Comercial
                </button>
            </form>
            <form method="POST" action="{{ route('bot-flows.plantilla') }}">
                @csrf
                <button type="submit"
                        class="px-4 py-2 border border-indigo-300 hover:bg-indigo-50 text-indigo-700 text-sm font-medium rounded-lg">
                    🛒 Crear bot de tienda
                </button>
            </form>
            <a href="{{ route('bot-flows.editor.new') }}"
               class="px-4 py-2 border border-gray-300 hover:border-indigo-400 hover:text-indigo-600 text-gray-600 text-sm font-medium rounded-lg">
                Empezar en blanco
            </a>
        </div>
        @endcan
    </div>

    {{-- Qué datos reales tiene el negocio: el Bot Comercial responde con
         esto. Lo que falte, el bot lo dirá honestamente y derivará al asesor. --}}
    <div class="flex flex-wrap items-center gap-2 mb-5">
        <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Datos para el Bot Comercial:</span>
        @foreach($checklistComercial as $item)
        <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-1 rounded-full
                     {{ $item['ok'] ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
            {{ $item['ok'] ? '✓' : '⚠' }} {{ $item['texto'] }}
        </span>
        @endforeach
    </div>

    {{-- Inteligencia Artificial del Bot Comercial: licencia (modulo bot_ia)
         y activacion (setting) son cosas distintas. Sin licencia no hay toggle. --}}
    <div class="flex items-center justify-between bg-white border border-gray-200 rounded-xl px-4 py-3 mb-5">
        <div>
            <span class="text-sm font-semibold text-gray-800">Inteligencia Artificial</span>
            <p class="text-[11px] text-gray-500 max-w-xl">
                La IA mejora la comprensión de mensajes libres, recomendaciones y contexto.
                Los precios, productos y demás información continúan obteniéndose de los datos reales del negocio.
            </p>
        </div>
        @if(!$iaLicenciada)
            <span class="text-[11px] font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-500 border border-gray-200 whitespace-nowrap">
                IA no incluida en este plan
            </span>
        @else
            @can('settings.negocio')
            <form method="POST" action="{{ route('bot-flows.ia') }}">
                @csrf
                <input type="hidden" name="activo" value="{{ $iaActiva ? 0 : 1 }}">
                <button type="submit" role="switch" aria-checked="{{ $iaActiva ? 'true' : 'false' }}"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $iaActiva ? 'bg-indigo-600' : 'bg-gray-300' }}"
                        title="{{ $iaActiva ? 'Desactivar IA' : 'Activar IA' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $iaActiva ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
            </form>
            @else
            <span class="text-[11px] font-semibold px-3 py-1.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200 whitespace-nowrap">
                {{ $iaActiva ? 'IA activa' : 'IA desactivada' }}
            </span>
            @endcan
        @endif
    </div>

    {{-- Linea de WhatsApp: aqui se escanea el QR. El estado lo reporta el
         conector de ESTA empresa cada pocos segundos; sin conector, "sin conexion". --}}
    <div x-data="lineaWa(@js(route('bot-flows.wa-status')))" x-init="sondear()"
         class="bg-white border border-gray-200 rounded-xl px-4 py-3 mb-5">
        <div class="flex items-center justify-between gap-4">
            <div>
                <span class="text-sm font-semibold text-gray-800">Línea de WhatsApp</span>
                <p class="text-[11px] text-gray-500 max-w-xl">
                    El bot responde por el número de WhatsApp que conectes aquí. Se escanea una sola vez; la sesión queda guardada.
                </p>
            </div>
            <span class="text-[11px] font-semibold px-3 py-1.5 rounded-full border whitespace-nowrap"
                  :class="{
                    'bg-green-50 text-green-700 border-green-200': estado==='connected',
                    'bg-yellow-50 text-yellow-700 border-yellow-200': estado==='qr',
                    'bg-blue-50 text-blue-600 border-blue-200': estado==='starting',
                    'bg-gray-100 text-gray-500 border-gray-200': !['connected','qr','starting'].includes(estado),
                  }"
                  x-text="{connected:'✓ WhatsApp conectado', qr:'Escanea el QR', starting:'Iniciando…'}[estado] || 'Sin conexión'"></span>
        </div>

        <template x-if="estado==='qr' && qr">
            <div class="mt-3 flex flex-col sm:flex-row items-center gap-4 bg-yellow-50/60 border border-yellow-100 rounded-xl p-4">
                <img :src="qr" alt="QR de WhatsApp" class="w-44 h-44 rounded-xl border border-gray-200 bg-white">
                <ol class="text-xs text-gray-600 space-y-1.5 list-decimal pl-4">
                    <li>Abre <b>WhatsApp</b> en el teléfono del negocio.</li>
                    <li>Toca <b>⋮ → Dispositivos vinculados → Vincular un dispositivo</b>.</li>
                    <li>Apunta la cámara a este código.</li>
                    <li>Listo: el bot empieza a responder por ese número.</li>
                </ol>
            </div>
        </template>
        <template x-if="estado==='starting' || (estado==='qr' && !qr)">
            <div class="mt-3 flex items-center gap-2 text-xs text-blue-600">
                <div class="w-3.5 h-3.5 border-2 border-blue-400 border-t-transparent rounded-full animate-spin"></div>
                Generando el código QR…
            </div>
        </template>
        <template x-if="!['connected','qr','starting'].includes(estado)">
            <p class="mt-2 text-[11px] text-gray-400">El conector de WhatsApp de este negocio no está en línea. Pide a soporte que lo active para poder escanear el QR.</p>
        </template>
    </div>

    

    <script>
    function lineaWa(url){
        return {
            estado: 'offline', qr: null,
            async sondear(){
                try {
                    const r = await fetch(url, {headers: {'Accept': 'application/json'}});
                    const d = await r.json();
                    this.estado = d.status || 'offline';
                    this.qr = d.qr || null;
                } catch (e) { this.estado = 'offline'; }
                // El QR caduca cada ~20 s: se refresca seguido mientras se espera el escaneo.
                setTimeout(() => this.sondear(), this.estado === 'connected' ? 15000 : 4000);
            },
        };
    }
    </script>

    {{-- Solo un bot atiende la linea: encender uno apaga los demas. --}}
    <p class="text-[11px] text-gray-400 mb-2">Solo <b>un bot</b> responde por la línea de WhatsApp. Al encender uno, los demás se apagan.</p>

    @if($flows->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <div class="text-4xl mb-2">🤖</div>
            <p class="text-sm">Aún no tienes bots. Crea el primero.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($flows as $f)
                @can('settings.negocio')
                <a href="{{ route('bot-flows.editor', $f) }}"
                   class="block bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition">
                @else
                <div class="block bg-white border border-gray-200 rounded-xl p-4">
                @endcan
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-gray-800">{{ $f->nombre }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $f->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $f->activo ? '● Responde en WhatsApp' : 'Apagado' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ count($f->definicion['bloques'] ?? []) }} bloques · editado {{ $f->updated_at->locale('es')->diffForHumans() }}</p>
                @can('settings.negocio')
                </a>
                @else
                </div>
                @endcan
            @endforeach
        </div>
    @endif
</div>
</x-portal-layout>

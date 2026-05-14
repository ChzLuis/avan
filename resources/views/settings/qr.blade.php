<x-app-layout>
<x-slot name="slot">

@php
  $catalogUrl = url('/'.$project->slug);
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

  {{-- TOP BAR --}}
  <div class="px-6 py-3 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
      <h1 class="text-base font-semibold text-gray-800">Código QR</h1>
      <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <a href="{{ $catalogUrl }}" target="_blank"
       class="flex items-center gap-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">
      Ver tienda ↗
    </a>
  </div>

  {{-- CONTENT --}}
  <div class="flex-1 overflow-y-auto bg-gray-50/30 px-6 py-6">

    <div x-data="{
      url: '{{ $catalogUrl }}',
      size: 300,
      margin: 2,
      fg: '#0f172a',
      bg: '#ffffff',
      logo: false,
      get qrSrc() {
        return 'https://api.qrserver.com/v1/create-qr-code/?size='+this.size+'x'+this.size
          +'&data='+encodeURIComponent(this.url)
          +'&color='+this.fg.replace('#','')
          +'&bgcolor='+this.bg.replace('#','')
          +'&margin='+this.margin
          +'&format=png';
      },
      download() {
        const a = document.createElement('a');
        a.href = this.qrSrc;
        a.download = 'qr-catalogo.png';
        a.click();
      },
      copyUrl() {
        navigator.clipboard.writeText(this.url);
        this.$dispatch('copied');
      }
    }"
    @copied.window="$el.querySelector('.copy-ok').classList.remove('hidden'); setTimeout(()=>$el.querySelector('.copy-ok').classList.add('hidden'),2000)"
    class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-4xl">

      {{-- Panel QR --}}
      <div class="bg-white rounded-2xl border border-gray-200 p-6 flex flex-col items-center gap-5 shadow-sm">

        {{-- QR imagen --}}
        <div class="p-4 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50">
          <img :src="qrSrc" :width="Math.min(size,260)" :height="Math.min(size,260)"
               class="rounded-xl" alt="QR de tu catálogo" loading="lazy">
        </div>

        {{-- Acciones --}}
        <div class="flex gap-2 w-full">
          <button @click="download()"
                  class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Descargar PNG
          </button>
          <button @click="copyUrl()"
                  class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition relative">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Copiar URL
            <span class="copy-ok hidden absolute -top-8 left-1/2 -translate-x-1/2 text-xs bg-gray-800 text-white px-2 py-1 rounded whitespace-nowrap">¡Copiado!</span>
          </button>
        </div>

        {{-- URL --}}
        <div class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs text-gray-500 font-mono break-all" x-text="url"></div>
      </div>

      {{-- Panel opciones --}}
      <div class="space-y-5">

        {{-- URL personalizable --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm space-y-4">
          <p class="text-sm font-semibold text-gray-700">Personalizar QR</p>

          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">URL</label>
            <input type="url" x-model="url" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-400 font-mono">
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Tamaño: <span x-text="size"></span>px</label>
            <input type="range" x-model.number="size" min="100" max="600" step="50" class="w-full accent-indigo-600">
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1.5">Margen: <span x-text="margin"></span></label>
            <input type="range" x-model.number="margin" min="0" max="10" step="1" class="w-full accent-indigo-600">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Color QR</label>
              <div class="flex items-center gap-2">
                <input type="color" x-model="fg" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                <span class="text-sm font-mono text-gray-600" x-text="fg"></span>
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Fondo</label>
              <div class="flex items-center gap-2">
                <input type="color" x-model="bg" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                <span class="text-sm font-mono text-gray-600" x-text="bg"></span>
              </div>
            </div>
          </div>

          {{-- Presets --}}
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-2">Presets de color</label>
            <div class="flex gap-2 flex-wrap">
              <button @click="fg='#0f172a'; bg='#ffffff'"
                      class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                Clásico
              </button>
              <button @click="fg='#dc2626'; bg='#ffffff'"
                      class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 text-red-700 hover:bg-red-50 transition">
                Rojo
              </button>
              <button @click="fg='#4f46e5'; bg='#eef2ff'"
                      class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-indigo-200 text-indigo-700 hover:bg-indigo-50 transition">
                Índigo
              </button>
              <button @click="fg='#f8fafc'; bg='#0f172a'"
                      class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-700 bg-gray-900 text-gray-100 hover:bg-gray-800 transition">
                Dark
              </button>
            </div>
          </div>
        </div>

        {{-- Cómo usarlo --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
          <p class="text-sm font-semibold text-gray-700 mb-3">¿Cómo usarlo?</p>
          <ol class="space-y-2">
            @foreach([
              ['n'=>'1','t'=>'Descarga el PNG','d'=>'Guarda el código QR en tu dispositivo.'],
              ['n'=>'2','t'=>'Imprímelo o compártelo','d'=>'Úsalo en flyers, menús, tarjetas o redes sociales.'],
              ['n'=>'3','t'=>'Clientes lo escanean','d'=>'Con la cámara del celular y van directo a tu catálogo.'],
            ] as $step)
            <li class="flex items-start gap-3">
              <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ $step['n'] }}</span>
              <div>
                <p class="text-xs font-semibold text-gray-700">{{ $step['t'] }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $step['d'] }}</p>
              </div>
            </li>
            @endforeach
          </ol>
        </div>

        {{-- Compartir por WhatsApp --}}
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
          <p class="text-sm font-semibold text-green-800 mb-1">Comparte el enlace directo</p>
          <p class="text-xs text-green-600 mb-3">Envíalo por WhatsApp para que tus clientes vean tu catálogo.</p>
          <a :href="'https://wa.me/?text='+encodeURIComponent('¡Hola! Te comparto mi catálogo online: '+url)"
             target="_blank"
             class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            Compartir por WhatsApp
          </a>
        </div>

      </div>
    </div>

  </div>
</div>

</x-slot>
</x-app-layout>

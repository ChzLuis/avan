<x-app-layout>
<x-slot name="slot">

@php
  $s = fn($k) => $project->settings()->where('key',$k)->value('value') ?? '';

  $catalogUrl   = $project->custom_domain ? 'https://'.$project->custom_domain : url('/'.$project->slug);
  $logoUrl      = $s('logo_url');
  $logoFull     = $logoUrl ? asset('storage/'.$logoUrl) : '';
  $primaryColor = $s('primary_color') ?: '#e85d04';

  // QR settings guardados
  $qrMode         = $s('qr_mode')          ?: 'catalog';   // catalog | orders
  $qrTableCount   = (int)($s('qr_table_count') ?: 10);
  $qrReception    = $s('qr_reception')     ?: 'auto';       // auto | manual
  $qrPayment      = $s('qr_payment')       ?: 'cashier';    // cashier | waiter
  $qrSchedule     = json_decode($s('qr_schedule') ?: '{}', true) ?: [];

  $days = ['lun'=>'Lun','mar'=>'Mar','mie'=>'Mié','jue'=>'Jue','vie'=>'Vie','sab'=>'Sáb','dom'=>'Dom'];
  $defSched = ['open'=>true,'from'=>'08:00','to'=>'22:00'];
  foreach($days as $k=>$_) {
    if(!isset($qrSchedule[$k])) $qrSchedule[$k] = $defSched;
  }
@endphp

<div class="flex flex-col h-full w-full overflow-hidden" x-data="qrPage()" x-init="init()">

  {{-- TOP BAR --}}
  <div class="flex-shrink-0 px-6 py-3 border-b border-gray-200 bg-white flex items-center justify-between">
    <div>
      <h1 class="text-base font-semibold text-gray-800">Carta QR</h1>
      <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <a href="{{ $catalogUrl }}" target="_blank"
       class="flex items-center gap-1.5 text-xs font-medium text-orange-600 bg-orange-50 border border-orange-200 px-3 py-1.5 rounded-lg hover:bg-orange-100 transition">
      Ver carta ↗
    </a>
  </div>

  {{-- CONTENT --}}
  <div class="flex-1 overflow-y-auto bg-gray-50 px-4 py-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">

      {{-- ══════════════════════════════════════════════════════════
           1. MODO DE EXPERIENCIA
      ══════════════════════════════════════════════════════════ --}}
      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <p class="text-sm font-bold text-gray-800">Modo de experiencia</p>
          <p class="text-xs text-gray-400 mt-0.5">¿Qué pueden hacer tus clientes cuando escanean el QR?</p>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">

          {{-- Opción: Solo carta --}}
          <label class="relative cursor-pointer">
            <input type="radio" name="qr_mode" value="catalog" x-model="mode" class="sr-only">
            <div :class="mode==='catalog'
                   ? 'border-orange-400 bg-orange-50 ring-2 ring-orange-400'
                   : 'border-gray-200 bg-white hover:border-gray-300'"
                 class="rounded-xl border-2 p-5 transition-all">
              <div class="flex items-start gap-4">
                <div :class="mode==='catalog' ? 'bg-orange-500' : 'bg-gray-100'"
                     class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors">
                  <svg class="w-5 h-5" :class="mode==='catalog' ? 'text-white' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-800">Solo carta</p>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">1 QR</span>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Tus clientes escanean y ven el menú. Sin pedidos.</p>
                  <div class="mt-3 flex flex-wrap gap-1.5">
                    <span class="text-[11px] bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">Fácil de implementar</span>
                    <span class="text-[11px] bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">Solo consulta</span>
                  </div>
                </div>
              </div>
            </div>
          </label>

          {{-- Opción: Carta + Pedidos --}}
          <label class="relative cursor-pointer">
            <input type="radio" name="qr_mode" value="orders" x-model="mode" class="sr-only">
            <div :class="mode==='orders'
                   ? 'border-orange-400 bg-orange-50 ring-2 ring-orange-400'
                   : 'border-gray-200 bg-white hover:border-gray-300'"
                 class="rounded-xl border-2 p-5 transition-all">
              <div class="flex items-start gap-4">
                <div :class="mode==='orders' ? 'bg-orange-500' : 'bg-gray-100'"
                     class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors">
                  <svg class="w-5 h-5" :class="mode==='orders' ? 'text-white' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-800">Carta + Pedidos desde mesa</p>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">QR × mesa</span>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Los clientes ven la carta y hacen pedidos desde su celular.</p>
                  <div class="mt-3 flex flex-wrap gap-1.5">
                    <span class="text-[11px] bg-orange-50 text-orange-700 border border-orange-200 px-2 py-0.5 rounded-full">Pedidos en tiempo real</span>
                    <span class="text-[11px] bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded-full">QR por mesa</span>
                  </div>
                </div>
              </div>
            </div>
          </label>

        </div>
      </div>

      {{-- ══════════════════════════════════════════════════════════
           2. QR SECTION — tabs según modo
      ══════════════════════════════════════════════════════════ --}}
      <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Tabs --}}
        <div class="flex border-b border-gray-200 px-6 pt-4 gap-1">
          <button @click="qrTab='flyer'"
                  :class="qrTab==='flyer' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                  class="pb-3 px-1 text-sm border-b-2 transition-colors">
            QR General
          </button>
          <button x-show="mode==='orders'" @click="qrTab='mesas'"
                  :class="qrTab==='mesas' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700'"
                  class="pb-3 px-1 text-sm border-b-2 transition-colors ml-4">
            QR por Mesa
          </button>
        </div>

        {{-- TAB: Flyer QR General --}}
        <div x-show="qrTab==='flyer'" class="p-6">
          <div class="flex flex-col lg:flex-row gap-8">

            {{-- Preview --}}
            <div class="flex-shrink-0 flex flex-col items-center gap-4">
              <div id="flyer-preview" class="rounded-2xl overflow-hidden shadow-xl" style="width:280px; background:#ffffff;">
                <div id="preview-header" style="background:{{ $primaryColor }}; padding:18px 18px 26px; display:flex; flex-direction:column; align-items:center; gap:5px; position:relative;">
                  @if($logoFull)
                    <img src="{{ $logoFull }}" alt="{{ $project->name }}" style="max-height:44px; max-width:150px; object-fit:contain" id="preview-logo">
                  @else
                    <p id="preview-name" style="color:#fff; font-weight:800; font-size:18px; text-align:center; letter-spacing:-0.02em; margin:0">{{ $project->name }}</p>
                  @endif
                  <p id="tagline-text" style="font-size:11px; font-weight:600; text-align:center; color:rgba(255,255,255,0.85); margin:0">Escanea y pide desde tu mesa</p>
                </div>
                <div style="margin:-22px 18px 0; position:relative; z-index:2; background:#fff; border-radius:14px; box-shadow:0 4px 18px rgba(0,0,0,0.12); padding:12px; text-align:center;">
                  <img id="qr-img" src="" style="width:168px; height:168px; border-radius:8px; display:block; margin:0 auto;" alt="QR">
                </div>
                <div style="padding:10px 18px 4px; text-align:center">
                  <p id="url-text" style="font-size:10px; color:#6b7280; font-family:monospace; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $catalogUrl }}</p>
                </div>
                <div style="padding:4px 16px 12px; text-align:center">
                  <p style="font-size:9px; color:#d1d5db">Generado con BIXO · bixo.app</p>
                </div>
              </div>

              {{-- Botones --}}
              <div style="display:flex; flex-direction:column; gap:8px; width:280px">
                <button id="btn-download" style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px; padding:11px; color:#fff; font-size:13px; font-weight:700; border-radius:14px; border:none; cursor:pointer; background:#e85d04">
                  <svg style="width:15px;height:15px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                  Descargar flyer
                </button>
                <div style="display:flex; gap:8px">
                  <button id="btn-copy" style="flex:1; display:flex; align-items:center; justify-content:center; gap:5px; padding:9px; background:#fff; border:1px solid #e5e7eb; color:#374151; font-size:12px; font-weight:600; border-radius:14px; cursor:pointer; position:relative">
                    <svg style="width:13px;height:13px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Copiar URL
                    <span id="copied-tip" style="display:none; position:absolute; top:-30px; left:50%; transform:translateX(-50%); font-size:11px; background:#1f2937; color:#fff; padding:3px 8px; border-radius:6px; white-space:nowrap">¡Copiado!</span>
                  </button>
                  <a id="btn-wa" href="#" target="_blank" style="flex:1; display:flex; align-items:center; justify-content:center; gap:5px; padding:9px; background:#25D366; color:#fff; font-size:12px; font-weight:700; border-radius:14px; text-decoration:none">
                    <svg style="width:13px;height:13px" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                  </a>
                </div>
              </div>
            </div>

            {{-- Controles --}}
            <div class="flex-1 min-w-0 space-y-5">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">URL de la carta</label>
                <input id="inp-url" type="url" value="{{ $catalogUrl }}" class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-orange-400 font-mono">
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Tamaño QR: <span id="lbl-size">280</span>px</label>
                  <input id="inp-size" type="range" min="100" max="500" step="50" value="280" class="w-full accent-orange-500">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Margen: <span id="lbl-margin">2</span></label>
                  <input id="inp-margin" type="range" min="0" max="8" step="1" value="2" class="w-full accent-orange-500">
                </div>
              </div>

              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Color QR</label>
                  <div class="flex items-center gap-2">
                    <input id="inp-fg" type="color" value="#1a1a1a" class="w-9 h-9 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                    <span id="lbl-fg" class="text-xs font-mono text-gray-600">#1a1a1a</span>
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Fondo QR</label>
                  <div class="flex items-center gap-2">
                    <input id="inp-bg" type="color" value="#ffffff" class="w-9 h-9 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                    <span id="lbl-bg" class="text-xs font-mono text-gray-600">#ffffff</span>
                  </div>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1.5">Color header</label>
                  <div class="flex items-center gap-2">
                    <input id="inp-primary" type="color" value="{{ $primaryColor }}" class="w-9 h-9 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                    <span id="lbl-primary" class="text-xs font-mono text-gray-600">{{ $primaryColor }}</span>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-2">Presets</label>
                <div class="flex gap-2 flex-wrap">
                  <button class="preset px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-700" data-fg="#1a1a1a" data-bg="#ffffff">Clásico</button>
                  <button class="preset px-3 py-1.5 text-xs font-semibold rounded-lg border border-orange-200 text-orange-700 hover:bg-orange-50" data-fg="#e85d04" data-bg="#fff8f5">Naranja</button>
                  <button class="preset px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 text-red-700 hover:bg-red-50" data-fg="#dc2626" data-bg="#ffffff">Rojo</button>
                  <button class="preset px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-700 bg-gray-900 text-gray-100 hover:bg-gray-800" data-fg="#f8fafc" data-bg="#0f172a">Dark</button>
                </div>
              </div>

              <div class="flex items-center gap-3 pt-1">
                <button id="btn-reset" class="text-xs text-gray-400 hover:text-red-500 hover:underline transition-colors">↺ Resetear</button>
              </div>
            </div>
          </div>
        </div>

        {{-- TAB: QR por Mesa --}}
        <div x-show="qrTab==='mesas' && mode==='orders'" class="p-6">
          <div class="flex flex-col sm:flex-row sm:items-end gap-4 mb-6">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1.5">Número de mesas</label>
              <div class="flex items-center gap-3">
                <input type="number" x-model.number="tableCount" min="1" max="50"
                       class="w-24 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-orange-400 text-center font-bold">
                <span class="text-xs text-gray-400">máximo 50</span>
              </div>
            </div>
            <button @click="downloadAllMesas()"
                    class="flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Descargar todas
            </button>
            <p class="text-xs text-gray-400 ml-auto hidden sm:block">Cada QR lleva a: <span class="font-mono">{{ $catalogUrl }}?mesa=N</span></p>
          </div>

          {{-- Grid de mesas --}}
          <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr))">
            <template x-for="n in tableCount" :key="n">
              <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex flex-col items-center gap-2 hover:border-orange-300 transition">
                <img :src="getMesaQr(n)" :alt="'Mesa '+n" class="w-24 h-24 rounded-lg" loading="lazy">
                <p class="text-xs font-bold text-gray-700">Mesa <span x-text="n"></span></p>
                <button @click="downloadMesa(n)"
                        class="text-[11px] text-orange-600 hover:text-orange-800 font-medium">
                  ↓ Descargar
                </button>
              </div>
            </template>
          </div>
        </div>

      </div>

      {{-- ══════════════════════════════════════════════════════════
           3. CONFIGURACIÓN DE PEDIDOS (solo modo orders)
      ══════════════════════════════════════════════════════════ --}}
      <div x-show="mode==='orders'" x-transition class="space-y-4">

        {{-- Horario --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
              <p class="text-sm font-bold text-gray-800">Horario de pedidos</p>
              <p class="text-xs text-gray-400 mt-0.5">Fuera de este horario los clientes solo pueden ver la carta</p>
            </div>
          </div>
          <div class="p-6 space-y-2">
            @foreach($days as $dayKey => $dayLabel)
            @php $sch = $qrSchedule[$dayKey] ?? $defSched; @endphp
            <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0"
                 x-data="{ open: {{ $sch['open'] ? 'true' : 'false' }} }">
              <div class="w-16 flex-shrink-0">
                <label class="flex items-center gap-2 cursor-pointer">
                  <div @click="open=!open"
                       :class="open ? 'bg-orange-500' : 'bg-gray-200'"
                       class="relative w-9 h-5 rounded-full transition-colors cursor-pointer flex-shrink-0">
                    <div :class="open ? 'translate-x-4' : 'translate-x-0.5'"
                         class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform"></div>
                  </div>
                  <span class="text-xs font-semibold text-gray-600">{{ $dayLabel }}</span>
                </label>
              </div>
              <template x-if="open">
                <div class="flex items-center gap-2 flex-1">
                  <input type="time" name="qr_schedule[{{ $dayKey }}][from]"
                         value="{{ $sch['from'] }}"
                         class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-orange-400">
                  <span class="text-xs text-gray-400">—</span>
                  <input type="time" name="qr_schedule[{{ $dayKey }}][to]"
                         value="{{ $sch['to'] }}"
                         class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-orange-400">
                  <input type="hidden" name="qr_schedule[{{ $dayKey }}][open]" :value="open ? '1' : '0'">
                </div>
              </template>
              <template x-if="!open">
                <div class="flex-1">
                  <input type="hidden" name="qr_schedule[{{ $dayKey }}][open]" value="0">
                  <span class="text-xs text-gray-400 italic">Cerrado</span>
                </div>
              </template>
            </div>
            @endforeach
          </div>
        </div>

        {{-- Recepción + Pago en misma fila --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          {{-- Recepción de pedidos --}}
          <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
              <p class="text-sm font-bold text-gray-800">Recepción de pedidos</p>
            </div>
            <div class="p-5 space-y-3">
              <label class="flex items-start gap-3 cursor-pointer group">
                <input type="radio" name="qr_reception" value="auto" x-model="reception" class="mt-0.5 accent-orange-500">
                <div :class="reception==='auto' ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200'"
                     class="flex-1 rounded-xl border p-3 transition-colors">
                  <p class="text-sm font-semibold text-gray-800">Automático</p>
                  <p class="text-xs text-gray-500 mt-0.5">Todos los pedidos se comandarán automáticamente a cocina.</p>
                </div>
              </label>
              <label class="flex items-start gap-3 cursor-pointer group">
                <input type="radio" name="qr_reception" value="manual" x-model="reception" class="mt-0.5 accent-orange-500">
                <div :class="reception==='manual' ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200'"
                     class="flex-1 rounded-xl border p-3 transition-colors">
                  <p class="text-sm font-semibold text-gray-800">Manual</p>
                  <p class="text-xs text-gray-500 mt-0.5">El mozo revisa y aprueba cada pedido antes de comandarlo.</p>
                </div>
              </label>
            </div>
          </div>

          {{-- Método de cobro --}}
          <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
              <p class="text-sm font-bold text-gray-800">Método de cobro</p>
            </div>
            <div class="p-5 space-y-3">
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="radio" name="qr_payment" value="cashier" x-model="payment" class="mt-0.5 accent-orange-500">
                <div :class="payment==='cashier' ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200'"
                     class="flex-1 rounded-xl border p-3 transition-colors">
                  <p class="text-sm font-semibold text-gray-800">Pago en caja</p>
                  <p class="text-xs text-gray-500 mt-0.5">El cobro se gestiona en caja al finalizar la mesa.</p>
                </div>
              </label>
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="radio" name="qr_payment" value="waiter" x-model="payment" class="mt-0.5 accent-orange-500">
                <div :class="payment==='waiter' ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200'"
                     class="flex-1 rounded-xl border p-3 transition-colors">
                  <p class="text-sm font-semibold text-gray-800">Pago con mozo</p>
                  <p class="text-xs text-gray-500 mt-0.5">El mozo cobra en la mesa cuando el cliente lo solicite.</p>
                </div>
              </label>
            </div>
          </div>

        </div>
      </div>

      {{-- ══════════════════════════════════════════════════════════
           BOTÓN GUARDAR
      ══════════════════════════════════════════════════════════ --}}
      <div class="flex justify-end">
        <button @click="saveSettings()"
                :disabled="saving"
                class="flex items-center gap-2 px-6 py-2.5 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white text-sm font-semibold rounded-xl transition">
          <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <svg x-show="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <span x-text="saving ? 'Guardando...' : 'Guardar configuración'"></span>
        </button>
      </div>

    </div>{{-- /max-w-6xl --}}
  </div>{{-- /overflow-y-auto --}}
</div>{{-- /x-data --}}

@php
  $qrSavedMode      = $qrMode;
  $qrSavedTableCount = $qrTableCount;
  $qrSavedReception = $qrReception;
  $qrSavedPayment   = $qrPayment;
@endphp

<script>
const QR_CATALOG_URL = @json($catalogUrl);
const QR_PRIMARY     = @json($primaryColor);
const QR_LOGO        = @json($logoFull);
const QR_PROJECT     = @json($project->name);
const QR_PROJECT_ID  = {{ $project->id }};
const CSRF           = document.querySelector('meta[name="csrf-token"]').content;

function qrPage() {
    return {
        mode:       '{{ $qrSavedMode }}',
        qrTab:      'flyer',
        tableCount: {{ $qrSavedTableCount }},
        reception:  '{{ $qrSavedReception }}',
        payment:    '{{ $qrSavedPayment }}',
        saving:     false,

        init() {
            this.$watch('mode', v => { if(v !== 'orders') this.qrTab = 'flyer'; });
            initFlyerQR();
        },

        getMesaQr(n) {
            const url = QR_CATALOG_URL + '?mesa=' + n;
            return 'https://api.qrserver.com/v1/create-qr-code/?size=240x240'
                + '&data=' + encodeURIComponent(url)
                + '&color=1a1a1a&bgcolor=ffffff&margin=2&format=png';
        },

        async downloadMesa(n) {
            const url = QR_CATALOG_URL + '?mesa=' + n;
            await downloadMesaFlyer(n, url);
        },

        async downloadAllMesas() {
            for (let i = 1; i <= this.tableCount; i++) {
                const url = QR_CATALOG_URL + '?mesa=' + i;
                await downloadMesaFlyer(i, url);
                await new Promise(r => setTimeout(r, 350));
            }
        },

        async saveSettings() {
            this.saving = true;
            try {
                const form = new FormData();
                form.append('_token', CSRF);
                form.append('section', 'qr_settings');
                form.append('qr_mode', this.mode);
                form.append('qr_table_count', this.tableCount);
                form.append('qr_reception', this.reception);
                form.append('qr_payment', this.payment);

                // Horario
                document.querySelectorAll('[name^="qr_schedule"]').forEach(el => {
                    form.append(el.name, el.value);
                });

                const res = await fetch('{{ route("settings.qr.save") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: form,
                });
                const data = await res.json();
                if (data.ok) {
                    showToast('Configuración guardada', 'success');
                } else {
                    showToast('Error al guardar', 'error');
                }
            } catch(e) {
                showToast('Error de conexión', 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}

/* ── Flyer QR logic ── */
function initFlyerQR() {
    const DEFAULTS = {
        url: QR_CATALOG_URL, size: 280, margin: 2,
        fg: '#1a1a1a', bg: '#ffffff', primaryColor: QR_PRIMARY,
    };
    const LS_KEY = 'qr_state_' + QR_PROJECT_ID;
    function saveState() { try { localStorage.setItem(LS_KEY, JSON.stringify(state)); } catch(e){} }
    function loadState() { try { const s = localStorage.getItem(LS_KEY); return s ? Object.assign({}, DEFAULTS, JSON.parse(s)) : Object.assign({}, DEFAULTS); } catch(e) { return Object.assign({}, DEFAULTS); } }
    let state = loadState();

    const qrImg      = document.getElementById('qr-img');
    const urlText    = document.getElementById('url-text');
    const tagline    = document.getElementById('tagline-text');
    const btnDl      = document.getElementById('btn-download');
    const btnCopy    = document.getElementById('btn-copy');
    const btnWa      = document.getElementById('btn-wa');
    const btnReset   = document.getElementById('btn-reset');
    const copiedTip  = document.getElementById('copied-tip');
    const inpUrl     = document.getElementById('inp-url');
    const inpSize    = document.getElementById('inp-size');
    const inpMargin  = document.getElementById('inp-margin');
    const inpFg      = document.getElementById('inp-fg');
    const inpBg      = document.getElementById('inp-bg');
    const inpPrimary = document.getElementById('inp-primary');
    const lblSize    = document.getElementById('lbl-size');
    const lblMargin  = document.getElementById('lbl-margin');
    const lblFg      = document.getElementById('lbl-fg');
    const lblBg      = document.getElementById('lbl-bg');
    const lblPrimary = document.getElementById('lbl-primary');

    function getQrSrc(sz) {
        sz = sz || state.size;
        return 'https://api.qrserver.com/v1/create-qr-code/?size='+sz+'x'+sz
            +'&data='+encodeURIComponent(state.url)
            +'&color='+state.fg.replace('#','')
            +'&bgcolor='+state.bg.replace('#','')
            +'&margin='+state.margin+'&format=png';
    }

    function render() {
        qrImg.src = getQrSrc();
        urlText.textContent = state.url.replace(/^https?:\/\//,'');
        const hdr = document.getElementById('preview-header');
        if (hdr) hdr.style.background = state.primaryColor;
        btnWa.href = 'https://wa.me/?text='+encodeURIComponent('¡Hola! Te comparto nuestra carta: '+state.url);
        lblSize.textContent    = state.size;
        lblMargin.textContent  = state.margin;
        lblFg.textContent      = state.fg;
        lblBg.textContent      = state.bg;
        lblPrimary.textContent = state.primaryColor;
        inpUrl.value     = state.url;
        inpSize.value    = state.size;
        inpMargin.value  = state.margin;
        inpFg.value      = state.fg;
        inpBg.value      = state.bg;
        inpPrimary.value = state.primaryColor;
    }

    if(inpUrl)     inpUrl.addEventListener('input',     () => { state.url=inpUrl.value;               render(); saveState(); });
    if(inpSize)    inpSize.addEventListener('input',    () => { state.size=+inpSize.value;             render(); saveState(); });
    if(inpMargin)  inpMargin.addEventListener('input',  () => { state.margin=+inpMargin.value;         render(); saveState(); });
    if(inpFg)      inpFg.addEventListener('input',      () => { state.fg=inpFg.value;                 render(); saveState(); });
    if(inpBg)      inpBg.addEventListener('input',      () => { state.bg=inpBg.value;                 render(); saveState(); });
    if(inpPrimary) inpPrimary.addEventListener('input', () => { state.primaryColor=inpPrimary.value;  render(); saveState(); });
    if(btnReset)   btnReset.addEventListener('click',   () => { state=Object.assign({},DEFAULTS); try{localStorage.removeItem(LS_KEY);}catch(e){} render(); });

    document.querySelectorAll('.preset').forEach(btn => {
        btn.addEventListener('click', () => { state.fg=btn.dataset.fg; state.bg=btn.dataset.bg; render(); });
    });

    if(btnCopy) btnCopy.addEventListener('click', () => {
        navigator.clipboard.writeText(state.url);
        copiedTip.style.display='block';
        setTimeout(()=>copiedTip.style.display='none', 2000);
    });

    if(btnDl) btnDl.addEventListener('click', () => downloadGeneralFlyer(state, getQrSrc));

    render();
}

/* ── Download: Flyer general ── */
async function downloadGeneralFlyer(state, getQrSrc) {
    const W = 700, PAD = 32, CR = 28;
    const canvas = document.createElement('canvas');
    canvas.width = W;
    const ctx = canvas.getContext('2d');

    let logoImg=null, lw=0, lh=0;
    if (QR_LOGO) {
        try {
            logoImg = await loadImg(QR_LOGO);
            const maxW=200, maxH=80, r=Math.min(maxW/logoImg.width, maxH/logoImg.height);
            lw=Math.round(logoImg.width*r); lh=Math.round(logoImg.height*r);
        } catch(e){}
    }

    const topH   = 48 + (logoImg ? lh+14 : 52) + 30;
    const qrSize = 320, qPad = 22;
    const qcH    = qrSize + qPad*2;
    const cH     = topH + qcH + 22 + 16 + 38;
    canvas.height = cH + PAD*2;
    const H = canvas.height;
    const cX=PAD, cY=PAD, cW=W-PAD*2;

    ctx.fillStyle = '#18181b';
    ctx.fillRect(0,0,W,H);

    ctx.shadowColor='rgba(0,0,0,0.4)'; ctx.shadowBlur=40; ctx.shadowOffsetY=8;
    roundRect(ctx,cX,cY,cW,cH,CR); ctx.fillStyle='#ffffff'; ctx.fill();
    ctx.shadowColor='transparent'; ctx.shadowBlur=0; ctx.shadowOffsetY=0;

    ctx.save();
    roundRect(ctx,cX,cY,cW,cH,CR); ctx.clip();

    const colorZone = topH + qcH*0.55;
    const g = ctx.createLinearGradient(0,cY,0,cY+colorZone+28);
    g.addColorStop(0, state.primaryColor);
    g.addColorStop(0.85, state.primaryColor);
    g.addColorStop(1, '#ffffff');
    ctx.fillStyle=g; ctx.fillRect(cX,cY,cW,colorZone+28);

    let y = cY + 48;
    if (logoImg) {
        ctx.drawImage(logoImg, cX+(cW-lw)/2, y, lw, lh);
        y += lh + 14;
    } else {
        ctx.fillStyle='#ffffff'; ctx.font='bold 32px system-ui'; ctx.textAlign='center';
        ctx.fillText(QR_PROJECT, cX+cW/2, y+36);
        y += 52;
    }
    ctx.fillStyle='rgba(255,255,255,0.9)'; ctx.font='600 15px system-ui'; ctx.textAlign='center';
    ctx.fillText('Escanea y pide desde tu mesa', cX+cW/2, y);
    y += 30;

    const qcX=cX+(cW-(qrSize+qPad*2))/2, qcY=y;
    ctx.shadowColor='rgba(0,0,0,0.12)'; ctx.shadowBlur=20; ctx.shadowOffsetY=4;
    roundRect(ctx,qcX,qcY,qrSize+qPad*2,qrSize+qPad*2,18);
    ctx.fillStyle='#ffffff'; ctx.fill();
    ctx.shadowColor='transparent'; ctx.shadowBlur=0; ctx.shadowOffsetY=0;

    const qrUrl=getQrSrc(qrSize);
    const qrI=await loadImg(qrUrl);
    ctx.drawImage(qrI, qcX+qPad, qcY+qPad, qrSize, qrSize);
    y = qcY + qrSize + qPad*2 + 22;

    ctx.fillStyle='#9ca3af'; ctx.font='500 13px monospace'; ctx.textAlign='center';
    ctx.fillText(state.url.replace(/^https?:\/\//,''), cX+cW/2, y);
    y += 14;
    ctx.fillStyle='#d1d5db'; ctx.font='400 11px system-ui'; ctx.textAlign='center';
    ctx.fillText('Generado con BIXO · bixo.app', cX+cW/2, y+22);

    ctx.restore();

    const link=document.createElement('a');
    link.download='carta-qr-'+QR_PROJECT.toLowerCase().replace(/\s+/g,'-')+'.png';
    link.href=canvas.toDataURL('image/png');
    link.click();
}

/* ── Download: Mesa individual ── */
async function downloadMesaFlyer(n, mesaUrl) {
    const W=560, PAD=28, CR=22;
    const canvas=document.createElement('canvas');
    const ctx=canvas.getContext('2d');
    const qrSize=260, qPad=18;

    let logoImg=null, lw=0, lh=0;
    if (QR_LOGO) {
        try {
            logoImg=await loadImg(QR_LOGO);
            const maxW=160, maxH=58, r=Math.min(maxW/logoImg.width, maxH/logoImg.height);
            lw=Math.round(logoImg.width*r); lh=Math.round(logoImg.height*r);
        } catch(e){}
    }

    const topH   = 40 + (logoImg ? lh+10 : 44) + 48 + 24;
    const qcH    = qrSize + qPad*2;
    const cH     = topH + qcH + 20 + 32;
    canvas.width  = W;
    canvas.height = cH + PAD*2;
    const H=canvas.height;
    const cX=PAD, cY=PAD, cW=W-PAD*2;

    ctx.fillStyle='#18181b'; ctx.fillRect(0,0,W,H);

    ctx.shadowColor='rgba(0,0,0,0.35)'; ctx.shadowBlur=30; ctx.shadowOffsetY=6;
    roundRect(ctx,cX,cY,cW,cH,CR); ctx.fillStyle='#ffffff'; ctx.fill();
    ctx.shadowColor='transparent'; ctx.shadowBlur=0; ctx.shadowOffsetY=0;

    ctx.save();
    roundRect(ctx,cX,cY,cW,cH,CR); ctx.clip();

    // Header naranja
    const hdrH = topH;
    ctx.fillStyle = QR_PRIMARY;
    ctx.fillRect(cX,cY,cW,hdrH);

    let y = cY + 40;
    if (logoImg) {
        ctx.drawImage(logoImg, cX+(cW-lw)/2, y, lw, lh);
        y += lh + 10;
    } else {
        ctx.fillStyle='#ffffff'; ctx.font='bold 26px system-ui'; ctx.textAlign='center';
        ctx.fillText(QR_PROJECT, cX+cW/2, y+30);
        y += 44;
    }

    // Badge mesa
    const badgeTxt = 'MESA ' + n;
    ctx.font = 'bold 22px system-ui'; ctx.textAlign = 'center';
    const bW = ctx.measureText(badgeTxt).width + 40, bH = 42, bX = cX+(cW-bW)/2, bY = y;
    roundRect(ctx,bX,bY,bW,bH,21); ctx.fillStyle='rgba(255,255,255,0.22)'; ctx.fill();
    ctx.fillStyle='#ffffff'; ctx.fillText(badgeTxt, cX+cW/2, bY+bH/2+8);
    y += bH + 14;

    ctx.fillStyle='rgba(255,255,255,0.82)'; ctx.font='600 13px system-ui';
    ctx.fillText('Escanea para ver la carta y pedir', cX+cW/2, y);
    y += 24;

    // QR card
    const qcX=cX+(cW-(qrSize+qPad*2))/2, qcY=y;
    ctx.shadowColor='rgba(0,0,0,0.10)'; ctx.shadowBlur=18; ctx.shadowOffsetY=4;
    roundRect(ctx,qcX,qcY,qrSize+qPad*2,qrSize+qPad*2,16);
    ctx.fillStyle='#ffffff'; ctx.fill();
    ctx.shadowColor='transparent'; ctx.shadowBlur=0; ctx.shadowOffsetY=0;

    const qrUrl='https://api.qrserver.com/v1/create-qr-code/?size='+qrSize+'x'+qrSize
        +'&data='+encodeURIComponent(mesaUrl)
        +'&color=1a1a1a&bgcolor=ffffff&margin=2&format=png';
    const qrI=await loadImg(qrUrl);
    ctx.drawImage(qrI, qcX+qPad, qcY+qPad, qrSize, qrSize);
    y = qcY + qrSize + qPad*2 + 20;

    ctx.fillStyle='#d1d5db'; ctx.font='400 10px system-ui'; ctx.textAlign='center';
    ctx.fillText('Generado con BIXO · bixo.app', cX+cW/2, y);

    ctx.restore();

    const link=document.createElement('a');
    link.download='mesa-'+n+'-'+QR_PROJECT.toLowerCase().replace(/\s+/g,'-')+'.png';
    link.href=canvas.toDataURL('image/png');
    link.click();
}

function loadImg(src) {
    return new Promise((res,rej) => {
        const img=new Image(); img.crossOrigin='anonymous';
        img.onload=()=>res(img); img.onerror=rej; img.src=src;
    });
}

function roundRect(ctx,x,y,w,h,r){
    ctx.beginPath();
    ctx.moveTo(x+r,y); ctx.lineTo(x+w-r,y); ctx.quadraticCurveTo(x+w,y,x+w,y+r);
    ctx.lineTo(x+w,y+h-r); ctx.quadraticCurveTo(x+w,y+h,x+w-r,y+h);
    ctx.lineTo(x+r,y+h); ctx.quadraticCurveTo(x,y+h,x,y+h-r);
    ctx.lineTo(x,y+r); ctx.quadraticCurveTo(x,y,x+r,y);
    ctx.closePath();
}

function showToast(msg, type) {
    const t=document.createElement('div');
    t.style.cssText='position:fixed;top:20px;right:20px;z-index:9999;padding:12px 20px;border-radius:12px;font-size:14px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.15);transition:opacity .3s';
    t.style.background = type==='success' ? '#22c55e' : '#ef4444';
    t.style.color='#fff'; t.textContent=msg;
    document.body.appendChild(t);
    setTimeout(()=>{ t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, 2800);
}
</script>

</x-slot>
</x-app-layout>

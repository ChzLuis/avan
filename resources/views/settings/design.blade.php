<x-app-layout>
<x-slot name="slot">

@php
  $s          = request('s', 'plantilla');
  $activeTpl  = $project->setting('catalog_template', 'default') ?: 'default';
  $allTpls    = \App\Support\CatalogTemplates::all();
  $tplInfo    = $allTpls[$activeTpl] ?? $allTpls['default'];
  $tplFields  = [
    'ella'     => ['announcement','split_banner'],
    'flash'    => ['announcement','countdown'],
    'boutique' => ['split_banner'],
    'urban'    => ['announcement'],
    'porto'    => ['announcement','tabs_title'],
    'nordic'   => ['trust_bar'],
    'fresh'    => ['trust_bar'],
    'default'  => [],
  ];
  $extraFields = $tplFields[$activeTpl] ?? [];
  $storeMode   = $project->setting('store_mode', 'direct');
  $quotePrice  = $project->setting('quote_price_display', 'show');
  $savedWaFull = preg_replace('/\D/', '', $project->setting('quote_whatsapp', preg_replace('/\D/', '', $project->whatsapp ?? '')));
  $savedCountry= $project->setting('quote_whatsapp_country', '51');
  $localWaNum  = str_starts_with($savedWaFull, $savedCountry) ? substr($savedWaFull, strlen($savedCountry)) : $savedWaFull;
  $countries   = [
    ['code'=>'51',  'flag'=>'🇵🇪', 'name'=>'Perú'],
    ['code'=>'1',   'flag'=>'🇺🇸', 'name'=>'USA'],
    ['code'=>'52',  'flag'=>'🇲🇽', 'name'=>'México'],
    ['code'=>'57',  'flag'=>'🇨🇴', 'name'=>'Colombia'],
    ['code'=>'56',  'flag'=>'🇨🇱', 'name'=>'Chile'],
    ['code'=>'54',  'flag'=>'🇦🇷', 'name'=>'Argentina'],
    ['code'=>'591', 'flag'=>'🇧🇴', 'name'=>'Bolivia'],
    ['code'=>'593', 'flag'=>'🇪🇨', 'name'=>'Ecuador'],
    ['code'=>'595', 'flag'=>'🇵🇾', 'name'=>'Paraguay'],
    ['code'=>'598', 'flag'=>'🇺🇾', 'name'=>'Uruguay'],
    ['code'=>'58',  'flag'=>'🇻🇪', 'name'=>'Venezuela'],
    ['code'=>'34',  'flag'=>'🇪🇸', 'name'=>'España'],
    ['code'=>'55',  'flag'=>'🇧🇷', 'name'=>'Brasil'],
  ];
  $savedPayments  = json_decode($project->setting('accepted_payments', '[]'), true) ?? [];
  $paymentOptions = [
    ['key'=>'efectivo',      'label'=>'Efectivo',              'icon'=>'💵'],
    ['key'=>'yape',          'label'=>'Yape',                  'icon'=>'🟣'],
    ['key'=>'plin',          'label'=>'Plin',                  'icon'=>'🔵'],
    ['key'=>'transferencia', 'label'=>'Transferencia bancaria', 'icon'=>'🏦'],
    ['key'=>'tarjeta',       'label'=>'Tarjeta crédito/débito', 'icon'=>'💳'],
    ['key'=>'qr',            'label'=>'Pago con QR',           'icon'=>'📲'],
    ['key'=>'contra_entrega','label'=>'Contra entrega',         'icon'=>'🚚'],
  ];
  $fontOptions = [
    'Inter'              => 'Inter — Moderna y limpia',
    'Poppins'            => 'Poppins — Geométrica',
    'Jost'               => 'Jost — Editorial',
    'Lato'               => 'Lato — Amigable',
    'Raleway'            => 'Raleway — Elegante',
    'Playfair Display'   => 'Playfair Display — Serif clásica',
    'Cormorant Garamond' => 'Cormorant — Lujo editorial',
    'Montserrat'         => 'Montserrat — Corporativa',
    'Nunito'             => 'Nunito — Redondeada',
    'Oswald'             => 'Oswald — Bold condensada',
  ];
  $savedFontTitle = $project->setting('font_title') ?: $project->setting('font', 'Inter');
  $savedFontBody  = $project->setting('font_body')  ?: $project->setting('font', 'Inter');
@endphp

<div class="flex flex-col h-full w-full overflow-hidden">

  {{-- TOP BAR --}}
  <div class="px-6 py-3 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
      <h1 class="text-base font-semibold text-gray-800">Diseño</h1>
      <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <a href="{{ url('/'.$project->slug) }}" target="_blank"
       class="flex items-center gap-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">
      Ver tienda ↗
    </a>
  </div>

  {{-- HORIZONTAL TABS --}}
  <div class="flex border-b border-gray-200 bg-white px-2 overflow-x-auto flex-shrink-0">
    @foreach([
      ['k'=>'plantilla','l'=>'Plantilla', 'icon'=>'🎨', 'd'=>'Elige el diseño'],
      ['k'=>'marca',    'l'=>'Marca',     'icon'=>'🏷️', 'd'=>'Colores y logo'],
      ['k'=>'portada',  'l'=>'Portada',   'icon'=>'🖼️', 'd'=>'Hero y banners'],
      ['k'=>'catalogo', 'l'=>'Catálogo',  'icon'=>'📦', 'd'=>'Grid y filtros'],
      ['k'=>'sistema',  'l'=>'Sistema',   'icon'=>'⚙️', 'd'=>'Modo y SEO'],
    ] as $tab)
    <a href="{{ route('settings.design') }}?s={{ $tab['k'] }}"
       class="flex items-center gap-2 px-4 py-3 border-b-2 transition whitespace-nowrap
              {{ $s === $tab['k']
                 ? 'border-indigo-600 text-indigo-700 bg-indigo-50/50'
                 : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
      <span class="text-base leading-none">{{ $tab['icon'] }}</span>
      <div class="flex flex-col">
        <span class="text-xs font-semibold leading-tight">{{ $tab['l'] }}</span>
        <span class="text-[10px] leading-tight {{ $s === $tab['k'] ? 'text-indigo-400' : 'text-gray-400' }}">{{ $tab['d'] }}</span>
      </div>
    </a>
    @endforeach
  </div>

  {{-- SESSION TOAST --}}
  @if(session('success'))
  <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3000)" x-cloak
       class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-2.5 text-sm flex items-center gap-2 flex-shrink-0">
    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    {{ session('success') }}
  </div>
  @endif

  {{-- CONTENT AREA --}}
  <div class="flex-1 overflow-y-auto bg-gray-50/30" id="design-content">
    <div class="px-6 py-6 space-y-5">

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: PLANTILLA --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'plantilla')
      @php
        $allTemplates   = \App\Support\CatalogTemplates::all();
        $grouped        = \App\Support\CatalogTemplates::grouped();
        $activeTemplate = $project->setting('catalog_template', '');
        $tplHasView = ['default','direct','ella','nordic','flash','boutique','urban','fresh','porto','licoreria'];
      @endphp
      <div x-data="{
          selected: '{{ $activeTemplate }}',
          applying: false,
          applyingKey: '',
          appliedMsg: '',
          async applyTemplate(key) {
              if (this.applying) return;
              this.applying = true;
              this.applyingKey = key;
              this.appliedMsg = '';
              const res = await fetch('{{ route('settings.design.applyTemplate') }}', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                  body: JSON.stringify({ template: key })
              });
              const json = await res.json();
              this.applying = false;
              this.applyingKey = '';
              if (json.ok) {
                  this.selected = key;
                  this.appliedMsg = '¡Plantilla aplicada! Tu catálogo ya usa el nuevo diseño.';
                  setTimeout(() => this.appliedMsg = '', 6000);
              }
          }
      }">

        {{-- Toast éxito Alpine --}}
        <div x-show="appliedMsg" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2 shadow-sm">
          <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <div class="flex-1">
            <span x-text="appliedMsg"></span>
            <a href="{{ url('/' . $project->slug) }}" target="_blank"
               class="ml-2 underline font-semibold hover:text-green-900">Ver ahora ↗</a>
          </div>
        </div>


        {{-- Plantillas por grupo --}}
        @foreach($grouped as $categoryName => $templates)
        <div>
          <div class="flex items-center gap-2 mb-3">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ $categoryName }}</h3>
            <div class="flex-1 h-px bg-gray-100"></div>
            <span class="text-xs text-gray-400">{{ count($templates) }}</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
            @foreach($templates as $key => $tpl)
            @php $hasView = in_array($key, $tplHasView); @endphp
            <div class="relative group rounded-2xl border-2 overflow-hidden transition-all duration-200 cursor-pointer
                        {{ $activeTemplate === $key ? 'border-indigo-500 shadow-md shadow-indigo-100' : 'border-gray-200 hover:border-indigo-300 hover:shadow-sm' }}"
                 :class="selected === '{{ $key }}' ? 'border-indigo-500 shadow-md shadow-indigo-100' : 'border-gray-200 hover:border-indigo-300'"
                 @click="applyTemplate('{{ $key }}')">

              {{-- Preview visual --}}
              <div class="relative overflow-hidden" style="height:88px; background: {{ $tpl['preview_bg'] }}">
                <div class="absolute top-0 left-0 right-0 h-5 flex items-center px-2 gap-1"
                     style="background: rgba(0,0,0,0.35)">
                  <div class="w-3 h-1.5 rounded-sm" style="background: {{ $tpl['preview_accent'] }}"></div>
                  <div class="flex-1 flex justify-center gap-2">
                    <div class="w-5 h-1 rounded-sm bg-white/50"></div>
                    <div class="w-5 h-1 rounded-sm bg-white/50"></div>
                    <div class="w-5 h-1 rounded-sm bg-white/50"></div>
                  </div>
                  <div class="w-4 h-3 rounded-sm" style="background: {{ $tpl['preview_accent'] }}"></div>
                </div>
                <div class="absolute bottom-2 left-2 right-2 flex gap-1.5">
                  @for($i=0; $i<3; $i++)
                  <div class="flex-1 rounded-md overflow-hidden border border-white/30" style="height:42px">
                    <div class="h-6 flex items-center justify-center" style="background:rgba(255,255,255,0.30)">
                      <div class="w-5 h-3 rounded-sm" style="background:{{ $tpl['preview_accent'] }}; opacity:0.7"></div>
                    </div>
                    <div class="h-4 px-1.5 flex flex-col justify-center gap-0.5" style="background:rgba(255,255,255,0.18)">
                      <div class="h-0.5 rounded-full bg-white/80" style="width:75%"></div>
                      <div class="h-0.5 rounded-full" style="width:45%; background:{{ $tpl['preview_accent'] }}"></div>
                    </div>
                  </div>
                  @endfor
                </div>
                <span class="absolute top-6 left-2 text-sm leading-none">{{ $tpl['icon'] }}</span>
                @if(!$hasView)
                <div class="absolute top-6 right-2 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-amber-400 text-amber-900">Pronto</div>
                @endif
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-200"></div>
                <div x-show="applyingKey === '{{ $key }}'" x-cloak
                     class="absolute inset-0 flex items-center justify-center"
                     style="background: rgba(255,255,255,0.5)">
                  <svg class="w-5 h-5 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                  </svg>
                </div>
                <div x-show="selected === '{{ $key }}'" x-cloak
                     class="absolute top-6 right-2 w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center shadow">
                  <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
              </div>

              {{-- Info --}}
              <div class="p-3 bg-white">
                <div class="flex items-start justify-between gap-1">
                  <p class="text-xs font-bold text-gray-800 leading-tight">{{ $tpl['label'] }}</p>
                  @if($activeTemplate === $key)
                  <span class="flex-shrink-0 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-indigo-100 text-indigo-700">activa</span>
                  @endif
                </div>
                <p class="text-[11px] text-gray-400 mt-0.5 leading-snug line-clamp-2">{{ $tpl['description'] }}</p>
                <div class="mt-2 flex items-center gap-1">
                  <div class="w-3.5 h-3.5 rounded-full border border-gray-200/50"
                       style="background: {{ $tpl['preview_bg'] }}"></div>
                  <div class="w-3.5 h-3.5 rounded-full border border-gray-200/50"
                       style="background: {{ $tpl['preview_accent'] }}"></div>
                  <span class="text-[10px] text-gray-400 font-mono ml-1">{{ $tpl['settings']['font'] ?? 'Inter' }}</span>
                  @if($hasView)
                  <span class="ml-auto text-[9px] font-semibold text-green-600 bg-green-50 px-1.5 py-0.5 rounded-full">Listo</span>
                  @endif
                </div>
              </div>
            </div>
            @endforeach
          </div>
        </div>
        @endforeach

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
          <p class="font-semibold mb-1">¿Qué pasa al aplicar una plantilla?</p>
          <ul class="text-xs text-blue-600 space-y-1 list-disc pl-4 leading-relaxed">
            <li>Se configura automáticamente: color principal, fondo del hero, títulos y banners</li>
            <li>Cambia el layout del catálogo y el estilo de las tarjetas de productos</li>
            <li>La tipografía y paleta de colores se ajustan al rubro de la plantilla</li>
            <li>Todos los cambios son editables desde las otras secciones de diseño</li>
          </ul>
        </div>

      </div>
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: MARCA --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'marca')
      @php $pc = $project->setting('primary_color','#4f46e5'); $sc = $project->setting('secondary_color','#6b7280'); @endphp
      <div class="max-w-2xl mx-auto">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5" id="marca-form">
        @csrf

        {{-- Colores de marca (paletas + pickers unificados) --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-gray-800">Color de marca</p>
              <p class="text-xs text-gray-400 mt-0.5">Elige una paleta o define tu propio color</p>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" id="prev1" class="px-3 py-1.5 rounded-lg text-white text-xs font-medium shadow-sm transition" style="background:{{ $pc }}">Botón</button>
              <span id="prev2" class="px-2 py-0.5 rounded-full text-white text-xs font-bold" style="background:{{ $pc }}">OFERTA</span>
            </div>
          </div>
          <div class="p-4 space-y-4">
            {{-- Paletas rápidas --}}
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
              @foreach([
                ['name'=>'Índigo',    'p'=>'#4f46e5','s'=>'#6366f1'],
                ['name'=>'Verde',     'p'=>'#16a34a','s'=>'#4ade80'],
                ['name'=>'Naranja',   'p'=>'#ea580c','s'=>'#fb923c'],
                ['name'=>'Rojo',      'p'=>'#dc2626','s'=>'#f87171'],
                ['name'=>'Azul',      'p'=>'#2563eb','s'=>'#60a5fa'],
                ['name'=>'Violeta',   'p'=>'#7c3aed','s'=>'#a78bfa'],
                ['name'=>'Rosa',      'p'=>'#db2777','s'=>'#f472b6'],
                ['name'=>'Teal',      'p'=>'#0d9488','s'=>'#2dd4bf'],
                ['name'=>'Amarillo',  'p'=>'#ca8a04','s'=>'#facc15'],
                ['name'=>'Negro',     'p'=>'#18181b','s'=>'#71717a'],
                ['name'=>'Slate',     'p'=>'#475569','s'=>'#94a3b8'],
                ['name'=>'Café',      'p'=>'#92400e','s'=>'#d97706'],
              ] as $tone)
              <button type="button"
                      onclick="setColors('{{ $tone['p'] }}','{{ $tone['s'] }}')"
                      class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition text-left tone-btn"
                      data-p="{{ $tone['p'] }}" data-s="{{ $tone['s'] }}">
                <div class="w-4 h-4 rounded-full flex-shrink-0 border border-white shadow-sm" style="background:{{ $tone['p'] }}"></div>
                <span class="text-xs text-gray-600 font-medium truncate">{{ $tone['name'] }}</span>
              </button>
              @endforeach
            </div>

            {{-- Pickers personalizados --}}
            <div class="border-t border-gray-100 pt-4 grid grid-cols-2 gap-4">
              <div>
                <label class="label">Color principal</label>
                <div class="flex items-center gap-2 mt-1">
                  <input type="color" id="cp1" name="primary_color" value="{{ $pc }}"
                         class="w-10 h-9 rounded-lg cursor-pointer border border-gray-200 p-0.5 flex-shrink-0"
                         oninput="syncColor(this,'ct1','prev1','prev2')">
                  <input type="text" id="ct1" value="{{ $pc }}" maxlength="7" placeholder="#4f46e5"
                         class="flex-1 font-mono text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-indigo-400 uppercase min-w-0"
                         oninput="syncText(this,'cp1','prev1','prev2')">
                </div>
                <p class="text-xs text-gray-400 mt-1">Botones y badges</p>
              </div>
              <div>
                <label class="label">Color secundario</label>
                <div class="flex items-center gap-2 mt-1">
                  <input type="color" id="cp2" name="secondary_color" value="{{ $sc }}"
                         class="w-10 h-9 rounded-lg cursor-pointer border border-gray-200 p-0.5 flex-shrink-0"
                         oninput="syncColor(this,'ct2','prev3','prev4')">
                  <input type="text" id="ct2" value="{{ $sc }}" maxlength="7" placeholder="#6b7280"
                         class="flex-1 font-mono text-sm border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:border-indigo-400 uppercase min-w-0"
                         oninput="syncText(this,'cp2','prev3','prev4')">
                </div>
                <p class="text-xs text-gray-400 mt-1">Hover y acentos</p>
              </div>
            </div>
          </div>
        </div>
        <script>
        function syncColor(picker, textId, p1, p2) {
            document.getElementById(textId).value = picker.value;
            if(p1) document.getElementById(p1).style.background = picker.value;
            if(p2) document.getElementById(p2).style.background = picker.value;
            highlightTone();
        }
        function syncText(input, pickerId, p1, p2) {
            if(/^#[0-9a-fA-F]{6}$/.test(input.value)) {
                document.getElementById(pickerId).value = input.value;
                if(p1) document.getElementById(p1).style.background = input.value;
                if(p2) document.getElementById(p2).style.background = input.value;
            }
            highlightTone();
        }
        function setColors(p, s) {
            document.getElementById('cp1').value = p;
            document.getElementById('ct1').value = p;
            document.getElementById('cp2').value = s;
            document.getElementById('ct2').value = s;
            document.getElementById('prev1').style.background = p;
            document.getElementById('prev2').style.background = p;
            document.getElementById('prev3').style.background = s;
            highlightTone();
        }
        function highlightTone() {
            var p = document.getElementById('cp1').value;
            var s = document.getElementById('cp2').value;
            document.querySelectorAll('.tone-btn').forEach(function(btn){
                var match = btn.dataset.p === p && btn.dataset.s === s;
                btn.classList.toggle('border-indigo-400', match);
                btn.classList.toggle('ring-1', match);
                btn.classList.toggle('ring-indigo-300', match);
                btn.classList.toggle('bg-indigo-50', match);
            });
        }
        highlightTone();
        </script>

        {{-- Logo & Favicon --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Logo & Favicon</p>
            <p class="text-xs text-gray-400 mt-0.5">Imágenes de identidad del negocio</p>
          </div>
          <div class="p-4 space-y-4" x-data="{
              logoPreview: '{{ $project->setting('logo_url') ? (str_starts_with($project->setting('logo_url'), 'http') ? $project->setting('logo_url') : asset('storage/'.$project->setting('logo_url'))) : '' }}',
              faviPreview: '{{ $project->setting('favicon_url') ? (str_starts_with($project->setting('favicon_url'), 'http') ? $project->setting('favicon_url') : asset('storage/'.$project->setting('favicon_url'))) : '' }}',
              uploadLogo(e) {
                  const file = e.target.files[0]; if (!file) return;
                  const fd = new FormData();
                  fd.append('file', file);
                  fd.append('type', 'logo');
                  fd.append('_token', '{{ csrf_token() }}');
                  fetch('{{ route('settings.upload-logo') }}', { method: 'POST', body: fd })
                      .then(r => r.json())
                      .then(d => { if (d.url) { this.logoPreview = d.url; document.getElementById('logo_url_input').value = d.path; } });
              },
              uploadFavi(e) {
                  const file = e.target.files[0]; if (!file) return;
                  const fd = new FormData();
                  fd.append('file', file);
                  fd.append('type', 'favicon');
                  fd.append('_token', '{{ csrf_token() }}');
                  fetch('{{ route('settings.upload-logo') }}', { method: 'POST', body: fd })
                      .then(r => r.json())
                      .then(d => { if (d.url) { this.faviPreview = d.url; document.getElementById('favicon_url_input').value = d.path; } });
              }
          }">
            {{-- Logo --}}
            <div>
              <label class="label">Logo del negocio</label>
              <div class="flex items-center gap-4">
                <div class="w-20 h-16 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 flex-shrink-0 overflow-hidden">
                  <template x-if="logoPreview">
                    <img :src="logoPreview" class="max-w-full max-h-full object-contain p-1">
                  </template>
                  <template x-if="!logoPreview">
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </template>
                </div>
                <div class="flex-1">
                  <label class="flex items-center gap-2 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium rounded-lg cursor-pointer transition w-fit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Subir imagen
                    <input type="file" accept="image/*" class="hidden" @change="uploadLogo($event)">
                  </label>
                  <p class="text-xs text-gray-400 mt-1.5">PNG transparente recomendado · Máx 2MB</p>
                  <input type="hidden" name="logo_url" id="logo_url_input" value="{{ $project->setting('logo_url') }}">
                </div>
              </div>
            </div>
            {{-- Altura logo --}}
            <div>
              <label class="label">Altura del logo (px)</label>
              <input type="number" name="logo_height" class="input" min="20" max="300"
                     placeholder="40" value="{{ $project->setting('logo_height', '40') }}">
            </div>
            {{-- Altura header --}}
            <div>
              <label class="label">Altura del encabezado (px)</label>
              <input type="number" name="header_height" class="input" min="48" max="400"
                     placeholder="72" value="{{ $project->setting('header_height', '72') }}">
              <p class="text-xs text-gray-400 mt-1">Altura mínima del header del catálogo público</p>
            </div>
            {{-- Favicon --}}
            <div>
              <label class="label">Favicon</label>
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center bg-gray-50 flex-shrink-0 overflow-hidden">
                  <template x-if="faviPreview">
                    <img :src="faviPreview" class="max-w-full max-h-full object-contain p-1">
                  </template>
                  <template x-if="!faviPreview">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </template>
                </div>
                <div class="flex-1">
                  <label class="flex items-center gap-2 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium rounded-lg cursor-pointer transition w-fit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Subir favicon
                    <input type="file" accept="image/*" class="hidden" @change="uploadFavi($event)">
                  </label>
                  <p class="text-xs text-gray-400 mt-1.5">32×32 px · ICO o PNG</p>
                  <input type="hidden" name="favicon_url" id="favicon_url_input" value="{{ $project->setting('favicon_url') }}">
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Tipografía --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Tipografía</p>
            <p class="text-xs text-gray-400 mt-0.5">Fuentes de Google Fonts</p>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="label">Fuente de títulos</label>
              <select name="font_title" class="input">
                @foreach($fontOptions as $fk => $fl)
                <option value="{{ $fk }}" {{ $savedFontTitle === $fk ? 'selected' : '' }}>{{ $fl }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="label">Fuente de cuerpo de texto</label>
              <select name="font_body" class="input">
                @foreach($fontOptions as $fk => $fl)
                <option value="{{ $fk }}" {{ $savedFontBody === $fk ? 'selected' : '' }}>{{ $fl }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        {{-- Estilo global --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Estilo global</p>
          </div>
          <div class="p-4 space-y-4">
            <div>
              <label class="label">Radio de bordes (botones y cards)</label>
              <div class="grid grid-cols-3 gap-2 mt-1">
                @foreach(['sharp'=>'Cuadrado', 'rounded'=>'Redondeado', 'pill'=>'Píldora'] as $br_v => $br_l)
                <label class="text-center p-3 rounded-xl border-2 cursor-pointer transition
                    {{ $project->setting('border_radius', 'rounded') === $br_v ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                  <input type="radio" name="border_radius" value="{{ $br_v }}"
                         {{ $project->setting('border_radius', 'rounded') === $br_v ? 'checked' : '' }} class="sr-only">
                  <div class="h-7 bg-indigo-400 flex items-center justify-center text-white text-xs font-bold mb-1
                      {{ $br_v === 'sharp' ? 'rounded-none' : ($br_v === 'rounded' ? 'rounded-lg' : 'rounded-full') }}">Btn</div>
                  <span class="text-xs text-gray-600 font-medium">{{ $br_l }}</span>
                </label>
                @endforeach
              </div>
            </div>
            <div>
              <label class="label">Color del encabezado (header)</label>
              <div class="flex items-center gap-3 mt-1">
                <input type="color" name="header_bg_color"
                       value="{{ $project->setting('header_bg_color', '#ffffff') }}"
                       class="h-9 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                <span class="text-xs text-gray-400">Fondo del header del catálogo público</span>
              </div>
            </div>
            <div>
              <label class="label">Color del texto del encabezado</label>
              <div class="flex items-center gap-3 mt-1">
                <input type="color" name="header_text_color"
                       value="{{ $project->setting('header_text_color', '#111827') }}"
                       class="h-9 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                <span class="text-xs text-gray-400">Color del texto e íconos en el header</span>
              </div>
            </div>
            <div>
              <label class="label">Símbolo de moneda</label>
              <select name="currency_symbol" class="input">
                @foreach(['S/'=>'S/ — Sol peruano','Soles'=>'Soles — Sol peruano (palabra)','$'=>'$ — Dólar','€'=>'€ — Euro','COP$'=>'COP$ — Peso colombiano','CLP$'=>'CLP$ — Peso chileno','ARS$'=>'ARS$ — Peso argentino','MXN$'=>'MXN$ — Peso mexicano','Bs.'=>'Bs. — Boliviano'] as $cs_v => $cs_l)
                <option value="{{ $cs_v }}" {{ $project->setting('currency_symbol', 'S/') === $cs_v ? 'selected' : '' }}>{{ $cs_l }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>

        {{-- WhatsApp --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">WhatsApp</p>
          </div>
          <div class="p-4">
            <label class="label">Mensaje de WhatsApp</label>
            <input type="text" name="whatsapp_msg" class="input mt-1"
                   placeholder="Hola, vi tu catálogo y me interesa..."
                   value="{{ $project->setting('whatsapp_msg') }}">
            <p class="text-xs text-gray-400 mt-1">Se envía cuando el cliente hace clic en el botón flotante de WhatsApp</p>
          </div>
        </div>

        <div>
          <button type="submit" class="btn-primary">Guardar marca</button>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: PORTADA --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'portada')
      <div class="max-w-2xl mx-auto">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5">
        @csrf

        {{-- Banner principal (Hero) --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100"
               style="background: {{ $tplInfo['preview_bg'] }}">
            <p class="text-sm font-semibold text-white drop-shadow">Banner principal (Hero)</p>
            <p class="text-xs text-white/70 mt-0.5">Plantilla activa: {{ $tplInfo['label'] }}</p>
          </div>
          <div class="p-4 space-y-4">
            <div>
              <label class="label">Título principal</label>
              <input type="text" name="hero_title" class="input"
                     placeholder="{{ $tplInfo['settings']['hero_title'] ?? 'Ej: Bienvenido a nuestra tienda' }}"
                     value="{{ $project->setting('hero_title') }}">
            </div>
            <div>
              <label class="label">Subtítulo</label>
              <input type="text" name="hero_subtitle" class="input"
                     placeholder="{{ $tplInfo['settings']['hero_subtitle'] ?? 'Ej: Encuentra todo lo que necesitas al mejor precio' }}"
                     value="{{ $project->setting('hero_subtitle') }}">
            </div>
            <div>
              <label class="label">Badge / etiqueta sobre el título</label>
              <input type="text" name="hero_badge" class="input"
                     placeholder="{{ $tplInfo['settings']['hero_badge'] ?? 'Ej: ¡Nuevos productos!' }}"
                     value="{{ $project->setting('hero_badge') }}">
            </div>
            <div x-data="{ color: '{{ $project->setting('hero_bg_color', $tplInfo['preview_bg']) }}' }">
              <label class="label">Color de fondo del hero</label>
              <div class="flex items-center gap-3 mt-1">
                <input type="color" name="hero_bg_color" x-model="color"
                       class="w-14 h-12 rounded-xl cursor-pointer border-2 border-gray-200 p-0.5">
                <div>
                  <code class="text-sm text-gray-600 font-mono" x-text="color"></code>
                  <p class="text-xs text-gray-400 mt-0.5">Recomendado: <code class="bg-gray-100 px-1 rounded">{{ $tplInfo['preview_bg'] }}</code></p>
                </div>
              </div>
            </div>
            <div>
              <label class="label">Imagen de fondo del hero (URL)</label>
              <input type="url" name="hero_image" class="input"
                     placeholder="https://images.unsplash.com/photo-..."
                     value="{{ $project->setting('hero_image') }}">
              <p class="text-xs text-gray-400 mt-1">Aparece sobre el color de fondo. Mínimo 1920×600 px recomendado.</p>
            </div>
            <div x-data="{ ov: {{ (int)($project->setting('hero_overlay', '50')) }} }">
              <label class="label flex items-center justify-between">
                <span>Opacidad del overlay oscuro</span>
                <span class="font-mono text-indigo-600 text-sm" x-text="ov + '%'"></span>
              </label>
              <input type="range" name="hero_overlay" x-model="ov" min="0" max="85" step="5"
                     class="w-full mt-1 accent-indigo-600">
              <p class="text-xs text-gray-400 mt-1">Capa oscura sobre la imagen para que el texto sea legible</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label">Alineación del contenido</label>
                <select name="hero_align" class="input">
                  <option value="left"   {{ $project->setting('hero_align', 'left') === 'left'   ? 'selected' : '' }}>Izquierda</option>
                  <option value="center" {{ $project->setting('hero_align', 'left') === 'center' ? 'selected' : '' }}>Centrado</option>
                  <option value="right"  {{ $project->setting('hero_align', 'left') === 'right'  ? 'selected' : '' }}>Derecha</option>
                </select>
              </div>
              <div>
                <label class="label">Altura del hero</label>
                <select name="hero_height" class="input">
                  <option value="small"  {{ $project->setting('hero_height', 'medium') === 'small'  ? 'selected' : '' }}>Pequeño (300px)</option>
                  <option value="medium" {{ $project->setting('hero_height', 'medium') === 'medium' ? 'selected' : '' }}>Mediano (480px)</option>
                  <option value="large"  {{ $project->setting('hero_height', 'medium') === 'large'  ? 'selected' : '' }}>Grande (600px)</option>
                  <option value="full"   {{ $project->setting('hero_height', 'medium') === 'full'   ? 'selected' : '' }}>Pantalla completa</option>
                </select>
              </div>
            </div>
            {{-- CTAs --}}
            <div class="border-t border-gray-200 pt-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Botones de acción (CTAs)</p>
              <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2"
                   x-data="{ on: {{ ($project->setting('hero_cta1_show', '1') === '1') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-gray-700">CTA principal</span>
                  <div class="flex-shrink-0">
                    <input type="hidden" name="hero_cta1_show" :value="on ? '1' : '0'">
                    <button type="button" @click="on = !on"
                            :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                            class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                      <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                            class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                    </button>
                  </div>
                </div>
                <div x-show="on">
                  <input type="text" name="hero_cta1_text" class="input text-sm"
                         placeholder="Ver catálogo"
                         value="{{ $project->setting('hero_cta1_text', 'Ver catálogo') }}">
                </div>
              </div>
              <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2"
                   x-data="{ on: {{ ($project->setting('hero_cta2_show', '0') === '1') ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-gray-700">CTA secundario</span>
                  <div class="flex-shrink-0">
                    <input type="hidden" name="hero_cta2_show" :value="on ? '1' : '0'">
                    <button type="button" @click="on = !on"
                            :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                            class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                      <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                            class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                    </button>
                  </div>
                </div>
                <div x-show="on">
                  <input type="text" name="hero_cta2_text" class="input text-sm"
                         placeholder="Contáctanos"
                         value="{{ $project->setting('hero_cta2_text', 'Contáctanos') }}">
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Announcement bar --}}
        @if(in_array('announcement', $extraFields))
        <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
          <div class="px-4 py-3 bg-amber-50 border-b border-amber-100 flex items-center gap-2">
            <p class="text-sm font-semibold text-amber-800">Barra de anuncio</p>
            <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-200 text-amber-700">{{ $tplInfo['label'] }}</span>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="label">Texto del anuncio</label>
              <input type="text" name="announcement_text" class="input"
                     placeholder="Ej: Envío gratis en pedidos mayores a S/ 100"
                     value="{{ $project->setting('announcement_text') }}">
              <p class="text-xs text-gray-400 mt-1">Aparece en la barra superior de la tienda. Ideal para promociones y avisos.</p>
            </div>
            <div x-data="{ color: '{{ $project->setting('announcement_bg', $tplInfo['preview_accent'] ?? '#4f46e5') }}' }">
              <label class="label">Color de fondo</label>
              <div class="flex items-center gap-3 mt-1">
                <input type="color" name="announcement_bg" x-model="color"
                       class="w-12 h-10 rounded-lg cursor-pointer border-2 border-gray-200 p-0.5">
                <code class="text-sm text-gray-600 font-mono" x-text="color"></code>
              </div>
            </div>
          </div>
        </div>
        @endif

        {{-- Countdown --}}
        @if(in_array('countdown', $extraFields))
        <div class="bg-white rounded-xl border border-red-200 overflow-hidden">
          <div class="px-4 py-3 bg-red-50 border-b border-red-100 flex items-center gap-2">
            <p class="text-sm font-semibold text-red-800">Contador regresivo</p>
            <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-200 text-red-700">Flash</span>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="label">Texto del countdown</label>
              <input type="text" name="countdown_label" class="input"
                     placeholder="Ej: ¡Oferta termina en:"
                     value="{{ $project->setting('countdown_label', '¡Oferta termina en:') }}">
            </div>
            <div>
              <label class="label">Fecha de fin de oferta</label>
              <input type="datetime-local" name="countdown_end" class="input"
                     value="{{ $project->setting('countdown_end') }}">
              <p class="text-xs text-gray-400 mt-1">Cuando llegue a cero el contador desaparecerá automáticamente.</p>
            </div>
          </div>
        </div>
        @endif

        {{-- Split banner --}}
        @if(in_array('split_banner', $extraFields))
        <div class="bg-white rounded-xl border border-purple-200 overflow-hidden">
          <div class="px-4 py-3 bg-purple-50 border-b border-purple-100 flex items-center gap-2">
            <p class="text-sm font-semibold text-purple-800">Banner dividido 50/50</p>
            <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-200 text-purple-700">{{ $tplInfo['label'] }}</span>
          </div>
          <div class="p-4 space-y-3">
            <p class="text-xs text-gray-500">Sección que divide la pantalla en dos mitades con texto e imagen de fondo.</p>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label text-xs">Lado izquierdo — Título</label>
                <input type="text" name="split_left_title" class="input text-sm"
                       placeholder="Ej: Nueva colección"
                       value="{{ $project->setting('split_left_title') }}">
              </div>
              <div>
                <label class="label text-xs">Lado izquierdo — Subtítulo</label>
                <input type="text" name="split_left_sub" class="input text-sm"
                       placeholder="Ej: Piezas únicas"
                       value="{{ $project->setting('split_left_sub') }}">
              </div>
              <div>
                <label class="label text-xs">Lado derecho — Título</label>
                <input type="text" name="split_right_title" class="input text-sm"
                       placeholder="Ej: Accesorios"
                       value="{{ $project->setting('split_right_title') }}">
              </div>
              <div>
                <label class="label text-xs">Lado derecho — Subtítulo</label>
                <input type="text" name="split_right_sub" class="input text-sm"
                       placeholder="Ej: Completa tu look"
                       value="{{ $project->setting('split_right_sub') }}">
              </div>
            </div>
          </div>
        </div>
        @endif

        {{-- Trust bar --}}
        @if(in_array('trust_bar', $extraFields))
        <div class="bg-white rounded-xl border border-green-200 overflow-hidden">
          <div class="px-4 py-3 bg-green-50 border-b border-green-100 flex items-center gap-2">
            <p class="text-sm font-semibold text-green-800">Barra de confianza</p>
            <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-200 text-green-700">{{ $tplInfo['label'] }}</span>
          </div>
          <div class="p-4 space-y-3">
            <p class="text-xs text-gray-500">Iconos de beneficios bajo el hero (envío gratis, garantía, etc.).</p>
            @foreach([1,2,3] as $ti)
            <div class="flex items-center gap-2">
              <input type="text" name="trust_icon_{{ $ti }}" class="input text-sm w-16 flex-shrink-0 text-center"
                     placeholder="🚚" value="{{ $project->setting('trust_icon_'.$ti) }}">
              <input type="text" name="trust_text_{{ $ti }}" class="input text-sm flex-1"
                     placeholder="{{ ['Envío rápido', 'Garantía 30 días', 'Pago seguro'][$ti-1] }}"
                     value="{{ $project->setting('trust_text_'.$ti) }}">
            </div>
            @endforeach
          </div>
        </div>
        @endif

        {{-- Tabs título (Porto) --}}
        @if(in_array('tabs_title', $extraFields))
        <div class="bg-white rounded-xl border border-orange-200 overflow-hidden">
          <div class="px-4 py-3 bg-orange-50 border-b border-orange-100 flex items-center gap-2">
            <p class="text-sm font-semibold text-orange-800">Tabs de productos</p>
            <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-orange-200 text-orange-700">Porto</span>
          </div>
          <div class="p-4 space-y-3">
            <p class="text-xs text-gray-500">Etiquetas de las pestañas de productos en el home.</p>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="label text-xs">Tab 1</label>
                <input type="text" name="tab1_label" class="input text-sm"
                       placeholder="Destacados"
                       value="{{ $project->setting('tab1_label', 'Destacados') }}">
              </div>
              <div>
                <label class="label text-xs">Tab 2</label>
                <input type="text" name="tab2_label" class="input text-sm"
                       placeholder="Nuevos"
                       value="{{ $project->setting('tab2_label', 'Nuevos') }}">
              </div>
              <div>
                <label class="label text-xs">Tab 3</label>
                <input type="text" name="tab3_label" class="input text-sm"
                       placeholder="Ofertas"
                       value="{{ $project->setting('tab3_label', 'Ofertas') }}">
              </div>
            </div>
          </div>
        </div>
        @endif

        {{-- Banners de sección --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Banners de sección</p>
            <p class="text-xs text-gray-400 mt-0.5">Texto de los bloques de categorías / colecciones destacadas</p>
          </div>
          <div class="p-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label text-xs">Banner 1 — Título</label>
                <input type="text" name="banner1_title" class="input text-sm"
                       placeholder="{{ $tplInfo['settings']['banner1_title'] ?? 'Nuevos productos' }}"
                       value="{{ $project->setting('banner1_title') }}">
              </div>
              <div>
                <label class="label text-xs">Banner 1 — Descripción</label>
                <input type="text" name="banner1_sub" class="input text-sm"
                       placeholder="{{ $tplInfo['settings']['banner1_sub'] ?? 'Descubre lo último' }}"
                       value="{{ $project->setting('banner1_sub') }}">
              </div>
              <div>
                <label class="label text-xs">Banner 2 — Título</label>
                <input type="text" name="banner2_title" class="input text-sm"
                       placeholder="{{ $tplInfo['settings']['banner2_title'] ?? 'Ofertas especiales' }}"
                       value="{{ $project->setting('banner2_title') }}">
              </div>
              <div>
                <label class="label text-xs">Banner 2 — Descripción</label>
                <input type="text" name="banner2_sub" class="input text-sm"
                       placeholder="{{ $tplInfo['settings']['banner2_sub'] ?? 'Precios imperdibles' }}"
                       value="{{ $project->setting('banner2_sub') }}">
              </div>
            </div>
          </div>
        </div>

        <div>
          <button type="submit" class="btn-primary">Guardar portada</button>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: CATALOGO --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'catalogo')
      @php
        $cardStylesAll = \App\Support\CatalogTemplates::cardStyles();
        $activeTplCat  = $project->setting('catalog_template', 'default') ?: 'default';
        $allTplsCat    = \App\Support\CatalogTemplates::all();
        $tplInfoCat    = $allTplsCat[$activeTplCat] ?? $allTplsCat['default'];
      @endphp
      <div class="max-w-2xl mx-auto">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5">
        @csrf

        {{-- Catálogo --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Catálogo</p>
            <p class="text-xs text-gray-400 mt-0.5">Configuración del grid de productos</p>
          </div>
          <div class="p-4 space-y-4">
            <div>
              <label class="label">Título de la sección de productos</label>
              <input type="text" name="catalog_section_title" class="input"
                     placeholder="Nuestros productos"
                     value="{{ $project->setting('catalog_section_title', 'Nuestros productos') }}">
            </div>
            <div>
              <label class="label">Estilo de tarjeta de producto</label>
              <select name="card_style" class="input">
                @foreach($cardStylesAll as $cs_key => $cs_label)
                <option value="{{ $cs_key }}"
                    {{ $project->setting('card_style', $tplInfoCat['settings']['card_style'] ?? 'minimal') === $cs_key ? 'selected' : '' }}>
                  {{ ucfirst($cs_key) }} — {{ $cs_label }}
                </option>
                @endforeach
              </select>
              <p class="text-xs text-gray-400 mt-1">Ya configurado por tu plantilla activa. Cámbialo aquí para personalizar.</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label">Columnas en escritorio</label>
                <select name="catalog_cols_desktop" class="input">
                  @foreach(['2'=>'2 columnas','3'=>'3 columnas','4'=>'4 columnas'] as $cv=>$cl)
                  <option value="{{ $cv }}" {{ $project->setting('catalog_cols_desktop', '3') === $cv ? 'selected' : '' }}>{{ $cl }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="label">Columnas en móvil</label>
                <select name="catalog_cols_mobile" class="input">
                  @foreach(['1'=>'1 columna','2'=>'2 columnas'] as $cv=>$cl)
                  <option value="{{ $cv }}" {{ $project->setting('catalog_cols_mobile', '2') === $cv ? 'selected' : '' }}>{{ $cl }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Filtros</p>
            <p class="text-xs text-gray-400 mt-0.5">¿Qué filtros aparecen en el panel lateral?</p>
          </div>
          <div class="p-4 space-y-3">
            @foreach([
              ['key'=>'catalog_filter_price',  'label'=>'Filtro por precio',     'def'=>'1'],
              ['key'=>'catalog_filter_cats',   'label'=>'Filtro por categorías',  'def'=>'1'],
              ['key'=>'catalog_filter_sale',   'label'=>'Filtro "En oferta"',     'def'=>'1'],
              ['key'=>'catalog_filter_search', 'label'=>'Campo de búsqueda',      'def'=>'1'],
            ] as $ff)
            <label class="flex items-center justify-between cursor-pointer py-1">
              <span class="text-sm text-gray-700">{{ $ff['label'] }}</span>
              <div x-data="{ on: {{ ($project->setting($ff['key'], $ff['def']) === '1') ? 'true' : 'false' }} }" class="flex-shrink-0">
                <input type="hidden" name="{{ $ff['key'] }}" :value="on ? '1' : '0'">
                <button type="button" @click="on = !on"
                        :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                        class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                  <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                        class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Badges --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Badges</p>
            <p class="text-xs text-gray-400 mt-0.5">Etiquetas en las tarjetas de producto</p>
          </div>
          <div class="p-4 grid grid-cols-2 gap-3">
            @foreach([
              ['key'=>'catalog_badge_sale',     'label'=>'Badge "Oferta"',    'def'=>'OFERTA'],
              ['key'=>'catalog_badge_new',      'label'=>'Badge "Nuevo"',     'def'=>'NUEVO'],
              ['key'=>'catalog_badge_featured', 'label'=>'Badge "Destacado"', 'def'=>'DESTACADO'],
              ['key'=>'catalog_badge_sold_out', 'label'=>'Badge "Agotado"',   'def'=>'AGOTADO'],
            ] as $bg)
            <div>
              <label class="label text-xs">{{ $bg['label'] }}</label>
              <input type="text" name="{{ $bg['key'] }}" class="input text-sm"
                     placeholder="{{ $bg['def'] }}"
                     value="{{ $project->setting($bg['key'], $bg['def']) }}">
            </div>
            @endforeach
          </div>
        </div>

        {{-- Opciones de producto --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Opciones de producto</p>
          </div>
          <div class="p-4 space-y-3">
            @foreach([
              ['key'=>'catalog_show_ratings', 'label'=>'Mostrar estrellas / puntuación',    'def'=>'0'],
              ['key'=>'catalog_quick_view',   'label'=>'Activar Quick View (vista rápida)', 'def'=>'1'],
              ['key'=>'catalog_show_sku',     'label'=>'Mostrar código de producto (SKU)',  'def'=>'0'],
              ['key'=>'catalog_show_stock',   'label'=>'Mostrar disponibilidad / stock',   'def'=>'1'],
              ['key'=>'wholesale_enabled',    'label'=>'Habilitar precio por mayor',        'def'=>'0'],
            ] as $fo)
            <label class="flex items-center justify-between cursor-pointer py-1">
              <span class="text-sm text-gray-700">{{ $fo['label'] }}</span>
              <div x-data="{ on: {{ ($project->setting($fo['key'], $fo['def']) === '1') ? 'true' : 'false' }} }" class="flex-shrink-0">
                <input type="hidden" name="{{ $fo['key'] }}" :value="on ? '1' : '0'">
                <button type="button" @click="on = !on"
                        :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                        class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                  <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                        class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Botones de producto --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Botones de producto</p>
          </div>
          <div class="p-4 space-y-4">
            <div>
              <label class="label">Texto "Agregar al carrito"</label>
              <input type="text" name="btn_cart_text" class="input"
                     placeholder="Agregar al carrito"
                     value="{{ $project->setting('btn_cart_text', 'Agregar al carrito') }}">
            </div>
            <div>
              <label class="label">Texto "Cotizar"</label>
              <input type="text" name="btn_quote_text" class="input"
                     placeholder="Cotizar"
                     value="{{ $project->setting('btn_quote_text', 'Cotizar') }}">
            </div>
            <div>
              <label class="label">Forma global de botones</label>
              <div class="grid grid-cols-3 gap-2 mt-1">
                @foreach(['sharp'=>'Cuadrado', 'rounded'=>'Redondeado', 'pill'=>'Píldora'] as $bs_v => $bs_l)
                <label class="text-center p-3 rounded-xl border-2 cursor-pointer transition
                    {{ $project->setting('btn_shape', 'rounded') === $bs_v ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300' }}">
                  <input type="radio" name="btn_shape" value="{{ $bs_v }}"
                         {{ $project->setting('btn_shape', 'rounded') === $bs_v ? 'checked' : '' }} class="sr-only">
                  <div class="h-7 bg-indigo-500 flex items-center justify-center text-white text-xs font-bold mb-1
                      {{ $bs_v === 'sharp' ? 'rounded-none' : ($bs_v === 'rounded' ? 'rounded-lg' : 'rounded-full') }}">Btn</div>
                  <span class="text-xs text-gray-600 font-medium">{{ $bs_l }}</span>
                </label>
                @endforeach
              </div>
            </div>
            <label class="flex items-center justify-between cursor-pointer py-1">
              <span class="text-sm text-gray-700">Mostrar ícono en el botón del carrito</span>
              <div x-data="{ on: {{ ($project->setting('btn_show_icon', '1') === '1') ? 'true' : 'false' }} }" class="flex-shrink-0">
                <input type="hidden" name="btn_show_icon" :value="on ? '1' : '0'">
                <button type="button" @click="on = !on"
                        :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                        class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                  <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                        class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </label>
          </div>
        </div>

        {{-- Elementos flotantes --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Elementos flotantes</p>
          </div>
          <div class="p-4 space-y-4">
            <label class="flex items-center justify-between cursor-pointer py-1">
              <span class="text-sm text-gray-700">Mostrar carrito flotante</span>
              <div x-data="{ on: {{ ($project->setting('float_cart_show', '1') === '1') ? 'true' : 'false' }} }" class="flex-shrink-0">
                <input type="hidden" name="float_cart_show" :value="on ? '1' : '0'">
                <button type="button" @click="on = !on"
                        :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                        class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                  <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                        class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </label>
            <div>
              <label class="label">Posición del carrito flotante</label>
              <select name="float_cart_pos" class="input">
                <option value="bottom-right" {{ $project->setting('float_cart_pos', 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Abajo derecha</option>
                <option value="bottom-left"  {{ $project->setting('float_cart_pos', 'bottom-right') === 'bottom-left'  ? 'selected' : '' }}>Abajo izquierda</option>
              </select>
            </div>
            <div class="border-t border-gray-100 pt-4 space-y-3">
              <label class="flex items-center justify-between cursor-pointer py-1">
                <span class="text-sm text-gray-700">Mostrar botón de WhatsApp flotante</span>
                <div x-data="{ on: {{ ($project->setting('float_wa_show', '1') === '1') ? 'true' : 'false' }} }" class="flex-shrink-0">
                  <input type="hidden" name="float_wa_show" :value="on ? '1' : '0'">
                  <button type="button" @click="on = !on"
                          :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                          class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                    <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                          class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                  </button>
                </div>
              </label>
              <div>
                <label class="label">Tooltip del botón WA</label>
                <input type="text" name="float_wa_tooltip" class="input"
                       placeholder="¿Necesitas ayuda?"
                       value="{{ $project->setting('float_wa_tooltip', '¿Necesitas ayuda?') }}">
              </div>
              <div>
                <label class="label">Posición del botón WA</label>
                <select name="float_wa_pos" class="input">
                  <option value="bottom-right" {{ $project->setting('float_wa_pos', 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>Abajo derecha</option>
                  <option value="bottom-left"  {{ $project->setting('float_wa_pos', 'bottom-right') === 'bottom-left'  ? 'selected' : '' }}>Abajo izquierda</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button type="submit" class="btn-primary">Guardar catálogo</button>
          <a href="{{ url('/' . $project->slug) }}" target="_blank"
             class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Ver tienda ↗
          </a>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: SISTEMA --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'sistema')
      <div class="max-w-2xl mx-auto">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5">
        @csrf

        {{-- Modo de venta --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Modo de venta</p>
            <p class="text-xs text-gray-400 mt-0.5">¿Qué pueden hacer los clientes en tu tienda?</p>
          </div>
          <div class="p-4 space-y-3">
            <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition
                          {{ $storeMode === 'direct' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
              <input type="radio" name="store_mode" value="direct"
                     {{ $storeMode === 'direct' ? 'checked' : '' }}
                     class="mt-0.5 accent-indigo-600 flex-shrink-0">
              <div>
                <p class="text-sm font-semibold text-gray-800">Compra directa</p>
                <p class="text-xs text-gray-500 mt-0.5">El cliente agrega al carrito y realiza un pedido. Se muestra el precio.</p>
              </div>
            </label>
            <label class="flex items-start gap-3 p-3 rounded-xl border-2 cursor-pointer transition
                          {{ $storeMode === 'quote_only' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
              <input type="radio" name="store_mode" value="quote_only"
                     {{ $storeMode === 'quote_only' ? 'checked' : '' }}
                     class="mt-0.5 accent-indigo-600 flex-shrink-0">
              <div>
                <p class="text-sm font-semibold text-gray-800">Solo cotizaciones</p>
                <p class="text-xs text-gray-500 mt-0.5">El cliente arma su lista y la envía para cotizar. El precio puede mostrarse como referencial u ocultarse.</p>
              </div>
            </label>
            <div class="pl-7 space-y-2 pt-1">
              <p class="text-xs font-semibold text-gray-500">Precio en modo cotización:</p>
              <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="radio" name="quote_price_display" value="show"
                       {{ $quotePrice === 'show' ? 'checked' : '' }} class="accent-indigo-600">
                Mostrar precio referencial
              </label>
              <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="radio" name="quote_price_display" value="hide"
                       {{ $quotePrice === 'hide' ? 'checked' : '' }} class="accent-indigo-600">
                Ocultar precio
              </label>
            </div>
          </div>
        </div>

        {{-- WhatsApp para cotizaciones --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">WhatsApp para cotizaciones</p>
            <p class="text-xs text-gray-400 mt-0.5">El cliente podrá enviar el detalle de su cotización por WhatsApp</p>
          </div>
          <div class="p-4 space-y-4">
            <div x-data="{ country: '{{ $savedCountry }}', local: '{{ $localWaNum }}' }">
              <label class="label">Número de WhatsApp</label>
              <div class="flex items-center gap-0 mt-1">
                <select x-model="country" name="quote_whatsapp_country"
                        class="border border-r-0 border-gray-300 rounded-l-xl px-2 py-2.5 text-sm bg-gray-50 outline-none focus:border-indigo-400 transition cursor-pointer flex-shrink-0">
                  @foreach($countries as $c)
                  <option value="{{ $c['code'] }}" {{ $savedCountry === $c['code'] ? 'selected' : '' }}>
                    {{ $c['flag'] }} +{{ $c['code'] }} {{ $c['name'] }}
                  </option>
                  @endforeach
                </select>
                <input type="tel" x-model="local" placeholder="999 888 777"
                       class="flex-1 border border-gray-300 rounded-r-xl px-3 py-2.5 text-sm outline-none focus:border-indigo-400 transition min-w-0">
                <input type="hidden" name="quote_whatsapp" :value="country + local.replace(/\D/g,'')">
              </div>
            </div>
            <div>
              <label class="label">Mensaje plantilla</label>
              <textarea name="quote_wa_msg" class="input" rows="2"
                        placeholder="Ej: Hola, me interesa cotizar los siguientes productos:">{{ $project->setting('quote_wa_msg', 'Hola, me interesa cotizar los siguientes productos:') }}</textarea>
            </div>
          </div>
        </div>

        {{-- Métodos de pago --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Métodos de pago aceptados</p>
            <p class="text-xs text-gray-400 mt-0.5">Íconos visibles en el carrito (modo Compra directa)</p>
          </div>
          <div class="p-4">
            <div class="grid grid-cols-2 gap-2">
              @foreach($paymentOptions as $pm)
              <label class="flex items-center gap-2.5 p-2.5 rounded-xl border-2 cursor-pointer transition
                            {{ in_array($pm['key'], $savedPayments) ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                <input type="checkbox" name="accepted_payments[]" value="{{ $pm['key'] }}"
                       {{ in_array($pm['key'], $savedPayments) ? 'checked' : '' }}
                       class="accent-indigo-600 w-4 h-4 flex-shrink-0">
                <span class="text-base leading-none">{{ $pm['icon'] }}</span>
                <span class="text-sm text-gray-700 font-medium">{{ $pm['label'] }}</span>
              </label>
              @endforeach
            </div>
          </div>
        </div>

        {{-- Footer --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Footer</p>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="label">Tagline / slogan</label>
              <input type="text" name="footer_tagline" class="input"
                     placeholder="Tu tienda de confianza desde 2020"
                     value="{{ $project->setting('footer_tagline') }}">
            </div>
            <div>
              <label class="label">Copyright</label>
              <input type="text" name="footer_copyright" class="input"
                     placeholder="© 2026 Mi Tienda. Todos los derechos reservados."
                     value="{{ $project->setting('footer_copyright', '© ' . date('Y') . ' ' . $project->name . '. Todos los derechos reservados.') }}">
            </div>
            @foreach([
              ['key'=>'footer_show_social',  'label'=>'Mostrar íconos de redes sociales', 'def'=>'1'],
              ['key'=>'footer_show_payment', 'label'=>'Mostrar badges de métodos de pago', 'def'=>'1'],
              ['key'=>'footer_show_address', 'label'=>'Mostrar dirección en el footer',    'def'=>'1'],
            ] as $ft)
            <label class="flex items-center justify-between cursor-pointer py-1">
              <span class="text-sm text-gray-700">{{ $ft['label'] }}</span>
              <div x-data="{ on: {{ ($project->setting($ft['key'], $ft['def']) === '1') ? 'true' : 'false' }} }" class="flex-shrink-0">
                <input type="hidden" name="{{ $ft['key'] }}" :value="on ? '1' : '0'">
                <button type="button" @click="on = !on"
                        :class="on ? 'bg-indigo-600' : 'bg-gray-200'"
                        class="relative w-10 h-5 rounded-full transition-colors focus:outline-none">
                  <span :class="on ? 'translate-x-5' : 'translate-x-1'"
                        class="block w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Textos del sistema --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Textos del sistema</p>
            <p class="text-xs text-gray-400 mt-0.5">Mensajes y literales del catálogo y carrito</p>
          </div>
          <div class="p-4 grid grid-cols-2 gap-3">
            @foreach([
              ['key'=>'cart_title',              'label'=>'Título del carrito',         'def'=>'Tu carrito'],
              ['key'=>'cart_empty_msg',          'label'=>'Mensaje carrito vacío',       'def'=>'Tu carrito está vacío'],
              ['key'=>'btn_checkout_text',       'label'=>'Botón finalizar compra',      'def'=>'Finalizar compra'],
              ['key'=>'btn_send_quote_text',     'label'=>'Botón enviar cotización WA',  'def'=>'Enviar cotización por WhatsApp'],
              ['key'=>'txt_no_results',          'label'=>'Mensaje sin resultados',      'def'=>'No se encontraron productos'],
              ['key'=>'txt_search_placeholder',  'label'=>'Placeholder de búsqueda',    'def'=>'Buscar productos...'],
              ['key'=>'txt_view_more',           'label'=>'Texto "Ver más"',             'def'=>'Ver todos los productos'],
              ['key'=>'txt_all_cats',            'label'=>'Texto "Todas las categorías"','def'=>'Todas las categorías'],
            ] as $tx)
            <div>
              <label class="label text-xs">{{ $tx['label'] }}</label>
              <input type="text" name="{{ $tx['key'] }}" class="input text-sm"
                     placeholder="{{ $tx['def'] }}"
                     value="{{ $project->setting($tx['key'], $tx['def']) }}">
            </div>
            @endforeach
          </div>
        </div>

        {{-- Pantalla de Login --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden"
             x-data="{
               bgType: '{{ $project->setting('login_bg_type', 'gradient') }}',
               color1: '{{ $project->setting('login_color1', '#4f46e5') }}',
               color2: '{{ $project->setting('login_color2', '#7c3aed') }}'
             }">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Pantalla de Login</p>
            <p class="text-xs text-gray-400 mt-0.5">Personaliza el fondo y el mensaje de bienvenida</p>
          </div>
          <div class="p-4 space-y-4">
            <div>
              <label class="label">Tipo de fondo</label>
              <div class="grid grid-cols-3 gap-3 mt-2">
                @foreach([
                  ['val'=>'gradient','label'=>'Degradado'],
                  ['val'=>'solid',   'label'=>'Color sólido'],
                  ['val'=>'image',   'label'=>'Imagen URL'],
                ] as $opt)
                <label class="flex flex-col items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition"
                       :class="bgType==='{{ $opt['val'] }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                  <input type="radio" name="login_bg_type" value="{{ $opt['val'] }}"
                         x-model="bgType" class="sr-only">
                  <span class="text-xs font-medium"
                        :class="bgType==='{{ $opt['val'] }}' ? 'text-indigo-700' : 'text-gray-500'">
                    {{ $opt['label'] }}
                  </span>
                </label>
                @endforeach
              </div>
            </div>
            <div x-show="bgType === 'gradient'" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="label">Color inicial</label>
                  <div class="flex items-center gap-2 mt-1">
                    <input type="color" name="login_color1" x-model="color1"
                           class="w-12 h-10 rounded-lg cursor-pointer border border-gray-200 p-0.5">
                    <code class="text-sm text-gray-600 font-mono" x-text="color1"></code>
                  </div>
                </div>
                <div>
                  <label class="label">Color final</label>
                  <div class="flex items-center gap-2 mt-1">
                    <input type="color" name="login_color2" x-model="color2"
                           class="w-12 h-10 rounded-lg cursor-pointer border border-gray-200 p-0.5">
                    <code class="text-sm text-gray-600 font-mono" x-text="color2"></code>
                  </div>
                </div>
              </div>
              <div class="h-16 rounded-xl border border-gray-200 transition-all"
                   :style="'background: linear-gradient(135deg, ' + color1 + ' 0%, ' + color2 + ' 100%)'"></div>
            </div>
            <div x-show="bgType === 'solid'" class="space-y-2">
              <label class="label">Color de fondo</label>
              <div class="flex items-center gap-3">
                <input type="color" name="login_color1" x-model="color1"
                       class="w-14 h-12 rounded-xl cursor-pointer border-2 border-gray-200 p-0.5">
                <div>
                  <code class="text-sm text-gray-700 font-mono" x-text="color1"></code>
                  <p class="text-xs text-gray-400 mt-0.5">Se usa como fondo sólido</p>
                </div>
              </div>
              <div class="h-16 rounded-xl border border-gray-200 transition-all"
                   :style="'background:' + color1"></div>
            </div>
            <div x-show="bgType === 'image'">
              <label class="label">URL de la imagen de fondo</label>
              <input type="url" name="login_bg_image" class="input mt-1"
                     placeholder="https://images.unsplash.com/photo-..."
                     value="{{ $project->setting('login_bg_image') }}">
              <p class="text-xs text-gray-400 mt-1">Usa una imagen de alta resolución (mínimo 1920×1080).</p>
            </div>
            <div class="border-t border-gray-100 pt-4 space-y-3">
              <div>
                <label class="label">Título de bienvenida</label>
                <input type="text" name="login_heading" class="input"
                       placeholder="Bienvenido de vuelta"
                       value="{{ $project->setting('login_heading', 'Bienvenido de vuelta') }}">
              </div>
              <div>
                <label class="label">Subtítulo</label>
                <input type="text" name="login_subtitle" class="input"
                       placeholder="Ingresa a tu panel de gestión"
                       value="{{ $project->setting('login_subtitle', 'Ingresa a tu panel de gestión') }}">
              </div>
            </div>
          </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">SEO</p>
            <p class="text-xs text-gray-400 mt-0.5">Configura cómo aparece tu catálogo en buscadores</p>
          </div>
          <div class="p-4 space-y-3">
            <div>
              <label class="label">Título para buscadores (SEO Title)</label>
              <input type="text" name="seo_title" class="input"
                     placeholder="Ej: {{ $project->name }} — Catálogo Online"
                     value="{{ $project->setting('seo_title') }}"
                     maxlength="70">
              <p class="text-xs text-gray-400 mt-1">Máximo 60–70 caracteres. Aparece en la pestaña y en Google.</p>
            </div>
            <div>
              <label class="label">Descripción para buscadores (Meta description)</label>
              <textarea name="seo_description" class="input" rows="3"
                        placeholder="Ej: Explora el catálogo de {{ $project->name }}. Encuentra productos de calidad y haz tu pedido en línea."
                        maxlength="160">{{ $project->setting('seo_description') }}</textarea>
              <p class="text-xs text-gray-400 mt-1">Máximo 155–160 caracteres. Es el texto que Google muestra bajo el título.</p>
            </div>
            <div>
              <label class="label">Palabras clave (Keywords)</label>
              <input type="text" name="seo_keywords" class="input"
                     placeholder="Ej: tienda online, {{ $project->name }}, productos, Lima"
                     value="{{ $project->setting('seo_keywords') }}">
              <p class="text-xs text-gray-400 mt-1">Separadas por coma.</p>
            </div>
          </div>
        </div>

        <div>
          <button type="submit" class="btn-primary">Guardar sistema</button>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}
      @endif

    </div>
  </div>

</div>

<script>
document.addEventListener('alpine:initialized', function() {
    var el = document.getElementById('design-content');
    if (el) el.scrollTop = 0;
});
</script>

</x-slot>
</x-app-layout>

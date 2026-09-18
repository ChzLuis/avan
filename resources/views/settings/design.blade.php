<x-app-layout>
<x-slot name="slot">

@php
  $isOwnerOrSuper = auth()->user()?->is_superadmin || ($project && $project->owner_id === auth()->id());
  $requestedDesignSection = request('s', $isOwnerOrSuper ? 'plantilla' : 'constructor');
  $s = $requestedDesignSection === 'plantilla' && $isOwnerOrSuper ? 'plantilla' : 'constructor';
  $storeUrl   = $project->custom_domain
    ? 'https://' . $project->custom_domain
    : url('/' . $project->slug);
  $activeTpl  = $project->setting('catalog_template', 'default') ?: 'default';
  $allTpls    = \App\Modules\Tienda\Support\CatalogTemplates::all();
  $tplInfo    = $allTpls[$activeTpl] ?? $allTpls['default'];
  $templateManifest = \App\Modules\Tienda\Support\CatalogTemplates::manifest($activeTpl);
  $templateComponents = $templateManifest['components'] ?? [];
  $componentCatalog = $templateManifest['component_catalog'] ?? [];
  $componentLabels = [];
  foreach ($componentCatalog as $componentKey => $componentData) {
      $componentLabels[$componentKey] = $componentData['label'] ?? ucwords(str_replace(['_','-'], ' ', $componentKey));
  }

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
  $pc = $project->setting('primary_color', $tplInfo['settings']['primary_color'] ?? '#4f46e5');
  $sc = $project->setting('secondary_color', $tplInfo['settings']['secondary_color'] ?? '#6366f1');
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

<div class="designer-shell flex flex-col h-full min-h-0 w-full overflow-hidden" data-designer-section="{{ $s }}">

  {{-- TOP BAR --}}
  <div class="px-6 py-3 border-b border-gray-200 bg-white flex items-center justify-between flex-shrink-0">
    <div>
      <h1 class="text-base font-semibold text-gray-800">Diseño</h1>
      <p class="text-xs text-gray-400 mt-0.5">{{ $project->name }}</p>
    </div>
    <a href="{{ $storeUrl }}" target="_blank"
       class="flex items-center gap-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">
      Ver tienda ↗
    </a>
  </div>

  {{-- HORIZONTAL TABS --}}
  <div class="flex border-b border-gray-200 bg-white px-2 overflow-x-auto flex-shrink-0">
    @foreach(array_filter([
      $isOwnerOrSuper ? ['k'=>'plantilla','l'=>'Plantillas', 'icon'=>'◫', 'd'=>'Elige el diseño'] : null,
      ['k'=>'constructor','l'=>'Constructor visual','icon'=>'▦','d'=>'Configura toda la tienda'],
    ]) as $tab)
    @if(!$tab) @continue @endif
    <a href="{{ route('settings.design') }}?s={{ $tab['k'] }}"
       data-primary-designer-tab="{{ $tab['k'] }}"
       @if($s === $tab['k']) aria-current="page" @endif
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

  {{-- CONTENT AREA --}}
  <div class="flex-1 min-h-0 overflow-y-auto bg-gray-50/30" id="design-content" data-design-scroll-container>
    <div class="px-6 py-6 space-y-5">

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: PLANTILLA --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'plantilla' && $isOwnerOrSuper)
      @php
        $allTemplates   = \App\Modules\Tienda\Support\CatalogTemplates::all();
        $grouped        = \App\Modules\Tienda\Support\CatalogTemplates::grouped();
        $activeTemplate = $project->setting('catalog_template', '');
        $tplHasView = $project->setting('storefront_structure_v2', '0') === '1'
          ? array_keys($allTemplates)
          : ['default','direct','ella','nordic','flash','boutique','urban','fresh','porto','licoreria','farma','lavanderia'];
        $templateLabels = collect($allTemplates)->mapWithKeys(fn ($template, $key) => [$key => $template['label']])->all();
        // Plantillas soportadas (mantenidas activamente): se ofrecen primero.
        $supportedTemplateKeys = \App\Modules\Tienda\Support\CatalogTemplates::supportedKeys();
        $supportedTemplates = collect($allTemplates)->only($supportedTemplateKeys)->map(fn ($t) => [
          'name' => $t['label'],
          'short_description' => \Illuminate\Support\Str::limit($t['description'], 96),
          'icon' => $t['icon'],
          'preview_bg' => $t['preview_bg'],
          'preview_accent' => $t['preview_accent'],
        ])->all();
        $activeTemplateIsSupported = in_array($activeTemplate, $supportedTemplateKeys, true);
        $appliedTplKey = request('applied');
        $initialAppliedTheme = ($appliedTplKey && isset($allTemplates[$appliedTplKey]))
          // Mismas claves que usa la ruta por JS (theme.name / theme.description,
          // que salen de `name` y `short_description`). Con `label`/`description`
          // la MISMA accion mostraba un texto al aplicar la plantilla y otro
          // distinto al recargar con ?applied.
          ? ['name' => $allTemplates[$appliedTplKey]['name'] ?? $allTemplates[$appliedTplKey]['label'],
             'description' => \Illuminate\Support\Str::limit(
                 $allTemplates[$appliedTplKey]['short_description'] ?? $allTemplates[$appliedTplKey]['description'] ?? '', 60)]
          : null;
      @endphp

        {{-- Plantillas soportadas (recomendadas) --}}
        @include('settings.partials.supported-template-selector')

        <div class="hidden mt-8 mb-1 items-center gap-2" aria-hidden="true">
          <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Todas las plantillas</h3>
          <div class="flex-1 h-px bg-gray-100"></div>
        </div>
        <div x-data="{
          selected: '{{ $activeTemplate }}',
          applying: false,
          applyingKey: '',
          appliedMsg: '',
          filter: 'all',
          pending: null,
          templateLabels: @js($templateLabels),
          requestTemplate(key) { this.pending = key; },
          async applyTemplate(key) {
            if (this.applying) return;
            this.applying = true;
            this.applyingKey = key;
            this.appliedMsg = '';
            try {
              const res = await fetch('{{ route('settings.design.applyTemplate') }}', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ template: key })
              });
              const json = await res.json();
              if (!res.ok || !json.ok) throw new Error(json.message || 'No se pudo aplicar la plantilla.');
              this.selected = key;
              this.appliedMsg = `${this.templateLabels[key] || key} aplicada correctamente.`;
              const target = new URL(window.location.href);
              target.searchParams.set('s', 'plantilla');
              target.searchParams.set('applied', key);
              setTimeout(() => window.location.assign(target.toString()), 450);
            } catch (error) {
              this.appliedMsg = '';
              bxAviso('Error: ' + (error.message || 'No se pudo aplicar la plantilla.'), 'error');
            } finally {
              this.applying = false;
              this.applyingKey = '';
            }
          }
        }">

          <div class="hidden rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-violet-50 p-5 shadow-sm" aria-hidden="true">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Plantilla activa</p><h2 class="mt-1 text-xl font-bold text-slate-900" x-text="templateLabels[selected] || @js($tplInfo['label'])">{{ $tplInfo['label'] }}</h2><p class="mt-1 text-sm text-slate-500">Tus colores, logo, portada, catálogo, checkout y contenido son globales y se conservan al cambiar de plantilla.</p></div>
              <div class="flex flex-wrap gap-2"><a href="{{ $storeUrl }}" target="_blank" class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700">Vista previa ↗</a><a href="{{ route('settings.experience') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Configurar inicio</a></div>
            </div>
          </div>
          @if(request('applied') && isset($allTemplates[request('applied')]))
            <div class="mt-4 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 sm:flex-row sm:items-center sm:justify-between" role="status">
              <div><strong class="block text-sm">{{ $allTemplates[request('applied')]['label'] }} aplicada</strong><span class="text-xs text-emerald-700">La tienda pública ya está usando esta identidad visual.</span></div>
              <a href="{{ $storeUrl }}?theme_refresh={{ now()->timestamp }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Ver cambio en la tienda ↗</a>
            </div>
          @endif
          <div class="hidden mt-5 gap-2 overflow-x-auto pb-1" aria-hidden="true">
            <button @click="filter='all'" :class="filter==='all'?'bg-indigo-600 text-white':'bg-white text-slate-600'" class="rounded-full border px-3 py-1.5 text-xs font-semibold">Todas</button>
            @foreach(array_keys($grouped) as $group)<button @click="filter=@js($group)" :class="filter===@js($group)?'bg-indigo-600 text-white':'bg-white text-slate-600'" class="whitespace-nowrap rounded-full border px-3 py-1.5 text-xs font-semibold">{{ $group }}</button>@endforeach
          </div>

          {{-- Gestión de plantillas personalizadas del proyecto (creadas por el admin) --}}
          <div class="mt-6 bg-white rounded-xl border border-gray-200 p-4">
            <h4 class="text-sm font-semibold">Plantillas del proyecto</h4>
            <p class="text-xs text-gray-400">Crea y gestiona plantillas personalizadas para este proyecto.</p>
            <div class="mt-3 space-y-3">
              @php $projectTemplates = \App\Modules\Tienda\Models\ProjectTemplate::where('project_id', $project->id)->orderByDesc('is_active')->get(); @endphp
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($projectTemplates as $pt)
                <div class="p-3 border rounded-lg flex items-center justify-between">
                  <div>
                    <div class="text-sm font-medium">{{ $pt->name }} {!! $pt->is_active ? '<span class="ml-2 text-xs text-green-600">(activa)</span>' : '' !!}</div>
                    <div class="text-xs text-gray-400">{{ $pt->description }}</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button type="button" class="text-sm px-2 py-1 bg-indigo-600 text-white rounded" @click="(async()=>{ const res=await fetch('{{ route('settings.design.applyProjectTemplate') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({id:{{ $pt->id }},apply_to_project:'1'})}); const j=await res.json(); if(j.ok) location.reload(); else bxAviso(j.message||'Error', 'error'); })()">Aplicar</button>
                    <form method="POST" action="{{ route('settings.design.projectTemplates.destroy', $pt->id) }}" data-bx-confirmar="¿Eliminar esta plantilla?" data-bx-boton="Eliminar">
                      @csrf @method('DELETE')
                      <button type="submit" class="text-sm px-2 py-1 bg-red-50 text-red-700 border border-red-100 rounded">Eliminar</button>
                    </form>
                  </div>
                </div>
                @endforeach
              </div>

              <form method="POST" action="{{ route('settings.design.projectTemplates.store') }}" class="mt-2 flex gap-2">
                @csrf
                <input type="text" name="name" placeholder="Nombre de plantilla" class="input flex-1" required>
                <input type="hidden" name="settings" value='{{ json_encode($project->settings()->pluck("value","key")->toArray()) }}'>
                <button type="submit" class="btn-primary">Crear desde ajustes actuales</button>
              </form>
            </div>
          </div>

        <div x-show="pending" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
          <div @click.outside="pending=null" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-bold text-slate-900">Cambiar plantilla</h3><p class="mt-2 text-sm text-slate-600">Cambiará la estructura visual, pero se conservarán todos tus ajustes, productos, categorías y contenido.</p><div class="mt-5 flex justify-end gap-2"><button @click="pending=null" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-600">Cancelar</button><button @click="let k=pending;pending=null;applyTemplate(k)" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Continuar</button></div></div>
        </div>
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
            <a href="{{ $storeUrl }}" target="_blank"
               class="ml-2 underline font-semibold hover:text-green-900">Ver ahora ↗</a>
          </div>
        </div>


        {{-- Plantillas por grupo --}}
        @if(false)
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
                 x-show="filter === 'all' || filter === @js($categoryName)"
                 @click="if (selected !== '{{ $key }}') requestTemplate('{{ $key }}')">

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
        @endif

        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
          <p class="font-semibold mb-1">¿Qué pasa al cambiar de motor?</p>
          <ul class="text-xs text-blue-600 space-y-1 list-disc pl-4 leading-relaxed">
            <li>Ecommerce incluye sitio web, Inicio, Tienda, páginas, carrito y checkout.</li>
            <li>Directo abre un catálogo ligero para vender o cotizar con menos pasos.</li>
            <li>Productos, páginas, colores y configuraciones se conservan.</li>
            <li>La apariencia se personaliza con temas y presets, sin crear otro motor.</li>
          </ul>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-sm text-gray-700">
          <p class="font-semibold mb-2">Componentes compatibles con <span class="font-semibold text-gray-900">{{ $tplInfo['label'] }}</span></p>
          <p class="text-xs text-gray-400">Estas son las opciones de diseño que el panel mostrará para esta plantilla.</p>
          <div class="mt-3 flex flex-wrap gap-2">
            @forelse($templateComponents as $component)
              <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-800 text-[11px] font-semibold">
                {{ $componentLabels[$component] ?? ucwords(str_replace(['_','-'], ' ', $component)) }}
              </span>
            @empty
              <span class="text-xs text-gray-500">Plantilla sin opciones adicionales configuradas.</span>
            @endforelse
          </div>
        </div>
      @endif

      @if($s === 'constructor')
      <div class="constructor-stack" x-data="{ cvTab: (new URLSearchParams(location.search).get('cv') || 'marca') }"
           x-init="$watch('cvTab', v => { const u = new URL(location.href); u.searchParams.set('cv', v); history.replaceState(null,'',u); })">
      <section class="constructor-intro" aria-labelledby="constructor-title">
        <div class="constructor-intro-copy">
          <span>CONFIGURACIÓN DE LA TIENDA</span>
          <h2 id="constructor-title">Diseña tu tienda paso a paso</h2>
          <p>Sigue el orden recomendado. Cada opción aparece una sola vez y los cambios se guardan por sección.</p>
        </div>
        <a href="{{ $storeUrl }}" target="_blank">Ver tienda ↗</a>
      </section>
      <nav class="constructor-nav" data-constructor-nav aria-label="Pasos para configurar la tienda">
        <p class="constructor-nav-title">CONFIGURA EN ESTE ORDEN</p>

        <button type="button" @click="cvTab='marca'"
                :class="['marca','navegacion'].includes(cvTab) ? 'is-active' : ''"
                class="ux-step">
          <span class="ux-step-number">1</span>
          <span class="ux-step-copy">
            <strong>Identidad</strong>
            <small>Marca, logo y encabezado</small>
          </span>
          <span class="ux-step-check">›</span>
        </button>

        <button type="button" @click="cvTab='inicio'"
                :class="['inicio','portada'].includes(cvTab) ? 'is-active' : ''"
                class="ux-step">
          <span class="ux-step-number">2</span>
          <span class="ux-step-copy">
            <strong>Página de inicio</strong>
            <small>Orden, slider y secciones</small>
          </span>
          <span class="ux-step-check">›</span>
        </button>

        <button type="button" @click="cvTab='catalogo'"
                :class="cvTab==='catalogo' ? 'is-active' : ''"
                class="ux-step">
          <span class="ux-step-number">3</span>
          <span class="ux-step-copy">
            <strong>Catálogo</strong>
            <small>Productos, filtros y botones</small>
          </span>
          <span class="ux-step-check">›</span>
        </button>

        <button type="button" @click="cvTab='paginas'"
                :class="cvTab==='paginas' ? 'is-active' : ''"
                class="ux-step">
          <span class="ux-step-number">4</span>
          <span class="ux-step-copy">
            <strong>Páginas</strong>
            <small>Nosotros, contacto y contenido</small>
          </span>
          <span class="ux-step-check">›</span>
        </button>

        <button type="button" @click="cvTab='checkout'"
                :class="['checkout','sistema'].includes(cvTab) ? 'is-active' : ''"
                class="ux-step">
          <span class="ux-step-number">5</span>
          <span class="ux-step-copy">
            <strong>Venta y ajustes</strong>
            <small>Pedido, pagos, footer y SEO</small>
          </span>
          <span class="ux-step-check">›</span>
        </button>

        <div class="constructor-nav-help">
          <strong>Consejo</strong>
          <span>Completa primero Identidad e Inicio. Después configura el catálogo y el proceso de venta.</span>
        </div>
      </nav>

      {{-- Subopciones: aparecen solo cuando un paso contiene dos áreas --}}
      <div x-show="['marca','navegacion'].includes(cvTab)" x-cloak class="constructor-subnav">
        <button type="button" @click="cvTab='marca'" :class="cvTab==='marca' ? 'is-active' : ''">
          <span>1</span>
          <div><strong>Marca</strong><small>Colores, logo y tipografías</small></div>
        </button>
        <button type="button" @click="cvTab='navegacion'" :class="cvTab==='navegacion' ? 'is-active' : ''">
          <span>2</span>
          <div><strong>Encabezado</strong><small>Menú, barra superior y navegación</small></div>
        </button>
      </div>

      <div x-show="['inicio','portada'].includes(cvTab)" x-cloak class="constructor-subnav">
        <button type="button" @click="cvTab='inicio'" :class="cvTab==='inicio' ? 'is-active' : ''">
          <span>1</span>
          <div><strong>Estructura y orden</strong><small>Activa, publica y mueve secciones</small></div>
        </button>
        <button type="button" @click="cvTab='portada'" :class="cvTab==='portada' ? 'is-active' : ''">
          <span>2</span>
          <div><strong>Diseño de portada</strong><small>Slider, promociones, categorías y beneficios</small></div>
        </button>
      </div>

      <div x-show="['checkout','sistema'].includes(cvTab)" x-cloak class="constructor-subnav">
        <button type="button" @click="cvTab='checkout'" :class="cvTab==='checkout' ? 'is-active' : ''">
          <span>1</span>
          <div><strong>Datos del pedido</strong><small>Campos que completa el cliente</small></div>
        </button>
        <button type="button" @click="cvTab='sistema'" :class="cvTab==='sistema' ? 'is-active' : ''">
          <span>2</span>
          <div><strong>Configuración general</strong><small>Venta, pagos, footer, login y SEO</small></div>
          <em>Avanzado</em>
        </button>
      </div>

      <div x-show="cvTab==='navegacion'" x-cloak class="constructor-panel constructor-panel--navegacion">
        <div class="panel-heading">
          <div class="panel-heading-icon">🧭</div>
          <div>
            <span>PASO 1 · IDENTIDAD</span>
            <h3>Encabezado y navegación</h3>
            <p>Configura la barra superior, el menú principal y la navegación de la tienda.</p>
          </div>
        </div>
        @include('tienda::settings.partials.store-navigation-builder')
        @include('tienda::settings.partials.catalog-profiles')
      </div>

      <div id="constructor-marca" x-show="cvTab==='marca'" x-cloak class="max-w-2xl mx-auto constructor-panel constructor-panel--marca">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5" id="marca-form">
        @csrf
        
        <input type="hidden" name="_design_tab" value="marca">
        <div class="panel-heading">
          <div class="panel-heading-icon">🎨</div>
          <div>
            <span>PASO 1 · IDENTIDAD</span>
            <h3>Marca y apariencia</h3>
            <p>Define los colores, logotipo, favicon, tipografías y bordes de la tienda.</p>
          </div>
        </div>

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
                  const file = (e.target ? e.target.files[0] : e); if (!file) return;
                  const fd = new FormData();
                  fd.append('file', file);
                  fd.append('type', 'logo');
                  fd.append('_token', '{{ csrf_token() }}');
                  fetch('{{ route('settings.upload-logo') }}', { method: 'POST', body: fd })
                      .then(r => r.json())
                      .then(d => {
                          if (d.url) { this.logoPreview = d.url; document.getElementById('logo_url_input').value = d.path; return; }
                          const msg = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'No se pudo subir el logo.');
                          window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg, type: 'error' } }));
                      });
              },
              uploadFavi(e) {
                  const file = (e.target ? e.target.files[0] : e); if (!file) return;
                  const fd = new FormData();
                  fd.append('file', file);
                  fd.append('type', 'favicon');
                  fd.append('_token', '{{ csrf_token() }}');
                  fetch('{{ route('settings.upload-logo') }}', { method: 'POST', body: fd })
                      .then(r => r.json())
                      .then(d => {
                          if (d.url) { this.faviPreview = d.url; document.getElementById('favicon_url_input').value = d.path; return; }
                          const msg = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'No se pudo subir el favicon.');
                          window.dispatchEvent(new CustomEvent('app-toast', { detail: { msg, type: 'error' } }));
                      });
              },
              async quitarLogo() {
                  if (! await bxConfirmar({ descripcion: '¿Quitar el logo del negocio?', boton: 'Quitar logo' })) return;
                  this.logoPreview = '';
                  document.getElementById('logo_url_input').value = '';
              },
              async quitarFavi() {
                  if (! await bxConfirmar({ descripcion: '¿Quitar el favicon?', boton: 'Quitar favicon' })) return;
                  this.faviPreview = '';
                  document.getElementById('favicon_url_input').value = '';
              }
          }">
            {{-- Logo --}}
            <div>
              <label class="label">Logo del negocio</label>
              <div class="flex items-center gap-4 rounded-xl transition p-1 -m-1"
                   :class="isDragging ? 'bg-indigo-50 ring-2 ring-indigo-400' : ''"
                   x-data="imageDropzone(files => uploadLogo(files[0]))"
                   @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                   @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                <div class="w-20 h-16 rounded-xl border-2 border-dashed flex items-center justify-center bg-gray-50 flex-shrink-0 overflow-hidden transition"
                     :class="isDragging ? 'border-indigo-500' : 'border-gray-200'">
                  <template x-if="logoPreview">
                    <img :src="logoPreview" class="max-w-full max-h-full object-contain p-1">
                  </template>
                  <template x-if="!logoPreview">
                    <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </template>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium rounded-lg cursor-pointer transition w-fit">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                      Subir imagen
                      <input type="file" accept="image/*" class="hidden" @change="uploadLogo($event)">
                    </label>
                    {{-- Quitar logo: solo si hay uno --}}
                    <button type="button" x-show="logoPreview" @click="quitarLogo()"
                            class="flex items-center gap-1.5 px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-lg transition">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      Quitar
                    </button>
                  </div>
                  <p class="text-xs text-gray-400 mt-1.5" x-text="isDragging ? 'Suelta la imagen aquí' : 'PNG transparente recomendado · Máx 2MB · o arrastra la imagen'"></p>
                  <input type="hidden" name="logo_url" id="logo_url_input" value="{{ $project->setting('logo_url') }}">
                </div>
              </div>
            </div>
            {{-- Altura del logo: se configura en "Encabezado y menú" para no duplicar.
                 Mantenemos el valor sincronizado (campo oculto) por compatibilidad. --}}
            <div>
              <label class="label">Altura del logo</label>
              <div class="input flex items-center justify-between" style="background:#f8fafc;color:#64748b">
                <span>{{ $project->setting('header_logo_height', $project->setting('logo_height', '40')) }} px</span>
                <span style="font-size:11px">Se ajusta en “Encabezado y menú”</span>
              </div>
              <input type="hidden" name="logo_height" value="{{ $project->setting('header_logo_height', $project->setting('logo_height', '40')) }}">
            </div>
            <div>
              <label class="label">Posición del menú</label>
              @php $ma = in_array($project->setting('menu_align','left'),['left','center','right'])?$project->setting('menu_align','left'):'left'; @endphp
              <div class="grid grid-cols-3 gap-2 mt-1" x-data="{ ma: '{{ $ma }}' }">
                @foreach(['left'=>'Izquierda','center'=>'Centro','right'=>'Derecha'] as $mv=>$ml)
                <label class="text-center p-3 rounded-xl border-2 cursor-pointer transition"
                       :class="ma==='{{ $mv }}' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                  <input type="radio" name="menu_align" value="{{ $mv }}" @change="ma='{{ $mv }}'" {{ $ma===$mv?'checked':'' }} style="position:absolute;opacity:0;width:1px;height:1px;pointer-events:none">
                  <span class="text-xs text-gray-600 font-medium">{{ $ml }}</span>
                </label>
                @endforeach
              </div>
              <p class="text-xs text-gray-400 mt-1.5">"Todas las categorías" siempre queda a la izquierda.</p>
            </div>
            {{-- Favicon --}}
            <div>
              <label class="label">Favicon</label>
              <div class="flex items-center gap-4 rounded-xl transition p-1 -m-1"
                   :class="isDragging ? 'bg-indigo-50 ring-2 ring-indigo-400' : ''"
                   x-data="imageDropzone(files => uploadFavi(files[0]))"
                   @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                   @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                <div class="w-12 h-12 rounded-lg border-2 border-dashed flex items-center justify-center bg-gray-50 flex-shrink-0 overflow-hidden transition"
                     :class="isDragging ? 'border-indigo-500' : 'border-gray-200'">
                  <template x-if="faviPreview">
                    <img :src="faviPreview" class="max-w-full max-h-full object-contain p-1">
                  </template>
                  <template x-if="!faviPreview">
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </template>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium rounded-lg cursor-pointer transition w-fit">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                      Subir favicon
                      <input type="file" accept="image/*" class="hidden" @change="uploadFavi($event)">
                    </label>
                    {{-- Quitar favicon: solo si hay uno --}}
                    <button type="button" x-show="faviPreview" @click="quitarFavi()"
                            class="flex items-center gap-1.5 px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-lg transition">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      Quitar
                    </button>
                  </div>
                  <p class="text-xs text-gray-400 mt-1.5" x-text="isDragging ? 'Suelta la imagen aquí' : '32×32 px · ICO o PNG · o arrastra la imagen'"></p>
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

        {{-- Forma global de bordes (identidad visual) --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Forma de bordes</p>
            <p class="text-xs text-gray-400 mt-0.5">Define la forma base de botones y tarjetas</p>
          </div>
          <div class="p-4">
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
          </div>
        </div>

        <div class="constructor-savebar">
          <div><strong>Marca</strong><span>Los cambios también se guardan automáticamente.</span></div>
          <button type="submit" class="btn-primary">Guardar marca</button>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: PORTADA --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'constructor')
      <div id="constructor-portada" x-show="cvTab==='portada'" x-cloak class="max-w-2xl mx-auto constructor-panel constructor-panel--portada">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5" x-data="{
          heroPrev:{
            1:@js($project->setting('hero_image') ? (str_starts_with($project->setting('hero_image'),'http') ? $project->setting('hero_image') : asset('storage/'.$project->setting('hero_image'))) : ''),
            2:@js($project->setting('hero_image_2') ? (str_starts_with($project->setting('hero_image_2'),'http') ? $project->setting('hero_image_2') : asset('storage/'.$project->setting('hero_image_2'))) : ''),
            3:@js($project->setting('hero_image_3') ? (str_starts_with($project->setting('hero_image_3'),'http') ? $project->setting('hero_image_3') : asset('storage/'.$project->setting('hero_image_3'))) : ''),
            4:@js($project->setting('hero_image_4') ? (str_starts_with($project->setting('hero_image_4'),'http') ? $project->setting('hero_image_4') : asset('storage/'.$project->setting('hero_image_4'))) : ''),
            5:@js($project->setting('hero_image_5') ? (str_starts_with($project->setting('hero_image_5'),'http') ? $project->setting('hero_image_5') : asset('storage/'.$project->setting('hero_image_5'))) : '')
          },
          heroMobilePrev:{
            1:@js($project->setting('hero_mobile_image_1') ? (str_starts_with($project->setting('hero_mobile_image_1'),'http') ? $project->setting('hero_mobile_image_1') : asset('storage/'.$project->setting('hero_mobile_image_1'))) : ''),
            2:@js($project->setting('hero_mobile_image_2') ? (str_starts_with($project->setting('hero_mobile_image_2'),'http') ? $project->setting('hero_mobile_image_2') : asset('storage/'.$project->setting('hero_mobile_image_2'))) : ''),
            3:@js($project->setting('hero_mobile_image_3') ? (str_starts_with($project->setting('hero_mobile_image_3'),'http') ? $project->setting('hero_mobile_image_3') : asset('storage/'.$project->setting('hero_mobile_image_3'))) : ''),
            4:@js($project->setting('hero_mobile_image_4') ? (str_starts_with($project->setting('hero_mobile_image_4'),'http') ? $project->setting('hero_mobile_image_4') : asset('storage/'.$project->setting('hero_mobile_image_4'))) : ''),
            5:@js($project->setting('hero_mobile_image_5') ? (str_starts_with($project->setting('hero_mobile_image_5'),'http') ? $project->setting('hero_mobile_image_5') : asset('storage/'.$project->setting('hero_mobile_image_5'))) : '')
          },
          uploadSlide(e,slot,mobile=false){
              const file=e.target.files[0]; if(!file) return;
              const fd=new FormData(); fd.append('file',file); fd.append('type',mobile?'hero_mobile':'hero'); fd.append('_token','{{ csrf_token() }}');
              fetch('{{ route('settings.upload-logo') }}',{method:'POST',body:fd}).then(r=>r.json())
                  .then(d=>{ if(!d.url) return; const prefix=mobile?'hero_mobile_image_input_':'hero_image_input_'; document.getElementById(prefix+slot).value=d.path; if(mobile)this.heroMobilePrev[slot]=d.url;else this.heroPrev[slot]=d.url; });
          },
          quitarSlide(slot,mobile=false){ const prefix=mobile?'hero_mobile_image_input_':'hero_image_input_'; document.getElementById(prefix+slot).value=''; if(mobile)this.heroMobilePrev[slot]='';else this.heroPrev[slot]=''; },
          promoPrev:{
            1:@js($project->setting('promo_image_1') ? (str_starts_with($project->setting('promo_image_1'),'http') ? $project->setting('promo_image_1') : asset('storage/'.$project->setting('promo_image_1'))) : ''),
            2:@js($project->setting('promo_image_2') ? (str_starts_with($project->setting('promo_image_2'),'http') ? $project->setting('promo_image_2') : asset('storage/'.$project->setting('promo_image_2'))) : ''),
            3:@js($project->setting('promo_image_3') ? (str_starts_with($project->setting('promo_image_3'),'http') ? $project->setting('promo_image_3') : asset('storage/'.$project->setting('promo_image_3'))) : '')
          },
          promoMobilePrev:{
            1:@js($project->setting('promo_mobile_image_1') ? (str_starts_with($project->setting('promo_mobile_image_1'),'http') ? $project->setting('promo_mobile_image_1') : asset('storage/'.$project->setting('promo_mobile_image_1'))) : ''),
            2:@js($project->setting('promo_mobile_image_2') ? (str_starts_with($project->setting('promo_mobile_image_2'),'http') ? $project->setting('promo_mobile_image_2') : asset('storage/'.$project->setting('promo_mobile_image_2'))) : ''),
            3:@js($project->setting('promo_mobile_image_3') ? (str_starts_with($project->setting('promo_mobile_image_3'),'http') ? $project->setting('promo_mobile_image_3') : asset('storage/'.$project->setting('promo_mobile_image_3'))) : '')
          },
          uploadPromo(e,slot,mobile=false){
              const file=e.target.files[0]; if(!file) return;
              const fd=new FormData(); fd.append('file',file); fd.append('type',mobile?'promo_mobile':'promo'); fd.append('_token','{{ csrf_token() }}');
              fetch('{{ route('settings.upload-logo') }}',{method:'POST',body:fd}).then(r=>r.json())
                .then(d=>{ if(!d.url)return; const prefix=mobile?'promo_mobile_image_input_':'promo_image_input_'; document.getElementById(prefix+slot).value=d.path; if(mobile)this.promoMobilePrev[slot]=d.url;else this.promoPrev[slot]=d.url; });
          },
          quitarPromo(slot,mobile=false){
              const prefix=mobile?'promo_mobile_image_input_':'promo_image_input_'; document.getElementById(prefix+slot).value='';
              if(mobile)this.promoMobilePrev[slot]='';else this.promoPrev[slot]='';
          }
      }">
        @csrf
        
        <input type="hidden" name="_design_tab" value="portada">
        <div class="panel-heading">
          <div class="panel-heading-icon">🖼️</div>
          <div>
            <span>PASO 2 · PÁGINA DE INICIO</span>
            <h3>Diseño de la portada</h3>
            <p>Personaliza el slider, anuncios, categorías, beneficios y apariencia de las secciones.</p>
          </div>
        </div>

        <div class="ux-guide-card ux-guide-card--compact">
          <div class="ux-guide-card__head">
            <strong>Qué editas aquí</strong>
            <span>Diseño visual</span>
          </div>
          <div class="ux-guide-tags">
            <span>Slider principal</span>
            <span>Anuncios</span>
            <span>Categorías</span>
            <span>Beneficios</span>
            <span>Productos destacados</span>
            <span>Estilo global</span>
          </div>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
          <p class="text-sm font-semibold text-emerald-900">Sincronización de la plantilla</p>
          <p class="mt-1 text-xs leading-relaxed text-emerald-700">
            Los valores guardados aquí tienen prioridad sobre la plantilla personalizada activa.
            El orden se toma directamente de <strong>Inicio</strong> y se aplica en el servidor, sin depender de JavaScript.
          </p>
        </div>

        {{-- Sincronización con el constructor de Inicio --}}
        <div class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 via-white to-sky-50 p-5">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Configuración sincronizada</p>
              <h3 class="mt-1 text-base font-bold text-slate-900">Personaliza aquí; ordena y publica desde Inicio</h3>
              <p class="mt-1 text-sm text-slate-600">Cada bloque aparece una sola vez en este panel. El orden, estado Borrador/Publicado y visibilidad se controlan en la pestaña Inicio.</p>
            </div>
            <button type="button" @click="cvTab='inicio'"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
              Gestionar orden y publicación
            </button>
          </div>
          <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach(['Apariencia general','Barra superior','Slider principal','Anuncios','Categorías','Beneficios'] as $index => $label)
            <div class="flex items-center gap-2 rounded-lg border border-white bg-white/80 px-3 py-2 text-xs text-slate-600">
              <span class="grid h-6 w-6 place-items-center rounded-md bg-indigo-100 font-bold text-indigo-700">{{ $index + 1 }}</span>
              <span>{{ $label }}</span>
            </div>
            @endforeach
          </div>
        </div>

        {{-- Estilo global de todas las secciones --}}
        <div class="bg-white rounded-xl border border-fuchsia-200 overflow-hidden">
          <div class="px-4 py-3 bg-fuchsia-50 border-b border-fuchsia-100">
            <p class="text-sm font-semibold text-fuchsia-950">Estilo global de la plantilla</p>
            <p class="text-xs text-fuchsia-700 mt-0.5">Aplica una apariencia consistente a todas las secciones sin modificar el encabezado ni el footer.</p>
          </div>

          <div class="p-4 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label class="relative cursor-pointer">
                <input type="radio" name="section_style_preset" value="modern" class="peer sr-only"
                       {{ $project->setting('section_style_preset','modern') === 'modern' ? 'checked' : '' }}>
                <div class="rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-fuchsia-600 peer-checked:bg-fuchsia-50">
                  <div class="flex items-center justify-between">
                    <strong class="text-sm text-gray-900">Vista moderna</strong>
                    <span class="text-xs text-fuchsia-700">Recomendada</span>
                  </div>
                  <p class="mt-2 text-xs text-gray-500">Tarjetas elevadas, bordes suaves, hover y mayor presencia visual.</p>
                  <div class="mt-3 grid grid-cols-3 gap-2">
                    <span class="h-10 rounded-lg border bg-white shadow-sm"></span>
                    <span class="h-10 rounded-lg border bg-white shadow-sm"></span>
                    <span class="h-10 rounded-lg border bg-white shadow-sm"></span>
                  </div>
                </div>
              </label>

              <label class="relative cursor-pointer">
                <input type="radio" name="section_style_preset" value="minimal" class="peer sr-only"
                       {{ $project->setting('section_style_preset','modern') === 'minimal' ? 'checked' : '' }}>
                <div class="rounded-xl border-2 border-gray-200 p-4 transition peer-checked:border-fuchsia-600 peer-checked:bg-fuchsia-50">
                  <div class="flex items-center justify-between">
                    <strong class="text-sm text-gray-900">Vista minimalista</strong>
                    <span class="text-xs text-gray-500">Limpia</span>
                  </div>
                  <p class="mt-2 text-xs text-gray-500">Menos sombras, bordes más rectos y una apariencia más corporativa.</p>
                  <div class="mt-3 grid grid-cols-3 gap-2">
                    <span class="h-10 rounded border bg-white"></span>
                    <span class="h-10 rounded border bg-white"></span>
                    <span class="h-10 rounded border bg-white"></span>
                  </div>
                </div>
              </label>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="label text-xs">Espaciado entre secciones</label>
                <select name="section_spacing" class="input text-sm">
                  <option value="compact" {{ $project->setting('section_spacing','comfortable') === 'compact' ? 'selected' : '' }}>Compacto</option>
                  <option value="comfortable" {{ $project->setting('section_spacing','comfortable') === 'comfortable' ? 'selected' : '' }}>Amplio</option>
                </select>
              </div>
              <div>
                <label class="label text-xs">Alineación de títulos</label>
                <select name="section_heading_align" class="input text-sm">
                  <option value="left" {{ $project->setting('section_heading_align','left') === 'left' ? 'selected' : '' }}>Izquierda</option>
                  <option value="center" {{ $project->setting('section_heading_align','left') === 'center' ? 'selected' : '' }}>Centrado</option>
                </select>
              </div>
              <div>
                <label class="label text-xs">Fondos de secciones</label>
                <select name="section_background_mode" class="input text-sm">
                  <option value="alternate" {{ $project->setting('section_background_mode','alternate') === 'alternate' ? 'selected' : '' }}>Alternados</option>
                  <option value="white" {{ $project->setting('section_background_mode','alternate') === 'white' ? 'selected' : '' }}>Siempre blanco</option>
                  <option value="soft" {{ $project->setting('section_background_mode','alternate') === 'soft' ? 'selected' : '' }}>Gris suave</option>
                </select>
              </div>
              <div>
                <label class="label text-xs">Vista del catálogo</label>
                <select name="catalog_products_view" class="input text-sm">
                  <option value="cards" {{ $project->setting('catalog_products_view','cards') === 'cards' ? 'selected' : '' }}>Tarjetas</option>
                  <option value="compact" {{ $project->setting('catalog_products_view','cards') === 'compact' ? 'selected' : '' }}>Lista compacta</option>
                </select>
              </div>
              <div>
                <label class="label text-xs">Productos destacados</label>
                <select name="featured_products_view" class="input text-sm">
                  <option value="cards" {{ $project->setting('featured_products_view','cards') === 'cards' ? 'selected' : '' }}>Tarjetas</option>
                  <option value="editorial" {{ $project->setting('featured_products_view','cards') === 'editorial' ? 'selected' : '' }}>Editorial horizontal</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
              @foreach([
                ['key'=>'section_show_dividers','label'=>'Mostrar divisores entre secciones','def'=>'1'],
                ['key'=>'section_card_shadow','label'=>'Mostrar sombras en tarjetas','def'=>'1'],
              ] as $toggle)
              <label class="flex items-center justify-between gap-3 cursor-pointer">
                <span class="text-sm text-gray-700">{{ $toggle['label'] }}</span>
                <div x-data="{on:{{ $project->setting($toggle['key'],$toggle['def']) === '1' ? 'true' : 'false' }}}">
                  <input type="hidden" name="{{ $toggle['key'] }}" :value="on?'1':'0'">
                  <button type="button" @click="on=!on" :class="on?'bg-fuchsia-600':'bg-gray-200'" class="relative w-10 h-5 rounded-full">
                    <span :class="on?'translate-x-5':'translate-x-1'" class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                  </button>
                </div>
              </label>
              @endforeach
            </div>

            <div class="rounded-lg border border-fuchsia-100 bg-fuchsia-50/60 p-3 text-xs text-fuchsia-900">
              Estos estilos afectan promociones, categorías, beneficios, productos destacados, catálogo y contenido personalizado. Las configuraciones individuales de cada sección siguen funcionando.
            </div>
          </div>
        </div>

        {{-- Announcement bar --}}
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

        {{-- Banner principal (Hero) --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100"
               style="background: {{ $tplInfo['preview_bg'] }}">
            <p class="text-sm font-semibold text-white drop-shadow">Banner principal (Hero)</p>
            <p class="text-xs text-white/70 mt-0.5">Plantilla activa: {{ $tplInfo['label'] }}</p>
          </div>
          <div class="p-4 space-y-4">
            <div>
              <label class="label">Título general de respaldo</label>
              <input type="text" name="hero_title" class="input"
                     placeholder="{{ $tplInfo['settings']['hero_title'] ?? 'Ej: Bienvenido a nuestra tienda' }}"
                     value="{{ $project->setting('hero_title') }}">
            </div>
            <div>
              <label class="label">Subtítulo general de respaldo</label>
              <input type="text" name="hero_subtitle" class="input"
                     placeholder="{{ $tplInfo['settings']['hero_subtitle'] ?? 'Ej: Encuentra todo lo que necesitas al mejor precio' }}"
                     value="{{ $project->setting('hero_subtitle') }}">
            </div>
            <div>
              <label class="label">Etiqueta general de respaldo</label>
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
            <div x-data="{ color: '{{ $project->setting('popup_bg_color', '#0b1736') }}' }">
              <label class="label">Color del pop-up promocional</label>
              <div class="flex items-center gap-3 mt-1">
                <input type="color" name="popup_bg_color" x-model="color"
                       class="w-14 h-12 rounded-xl cursor-pointer border-2 border-gray-200 p-0.5">
                <div>
                  <code class="text-sm text-gray-600 font-mono" x-text="color"></code>
                  <p class="text-xs text-gray-400 mt-0.5">Fondo del pop-up de ofertas</p>
                </div>
              </div>
            </div>
            <div>
              <label class="label">Contenido del slider</label>
              @php $hsc = $project->setting('hero_show_content', '1') === '0' ? '0' : '1'; @endphp
              <div class="grid grid-cols-2 gap-2 mt-1" x-data="{ hsc: '{{ $hsc }}' }">
                <label class="text-center p-3 rounded-xl border-2 cursor-pointer transition"
                       :class="hsc==='1' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                  <input type="radio" name="hero_show_content" value="1" @change="hsc='1'" {{ $hsc==='1'?'checked':'' }} class="sr-only">
                  <div class="text-xl mb-1">📝</div>
                  <span class="text-xs text-gray-600 font-medium">Con texto y botones</span>
                </label>
                <label class="text-center p-3 rounded-xl border-2 cursor-pointer transition"
                       :class="hsc==='0' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                  <input type="radio" name="hero_show_content" value="0" @change="hsc='0'" {{ $hsc==='0'?'checked':'' }} class="sr-only">
                  <div class="text-xl mb-1">🖼️</div>
                  <span class="text-xs text-gray-600 font-medium">Solo imagen</span>
                </label>
              </div>
              <p class="text-xs text-indigo-600 mt-1.5"><strong>Prioridad global:</strong> “Solo imagen” oculta el texto y los botones de todos los slides, aunque un slide tenga “Mostrar texto” activado.</p>
            </div>
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4">
              <div class="flex items-start justify-between gap-3">
                <div><label class="label">Slider principal — hasta 5 imágenes</label><p class="text-xs text-gray-500 mt-1">Recomendado: 3 slides. PC 1920×650 px y móvil 750×950 px. Los botones se agregan desde el sistema, no dentro de la imagen.</p></div>
                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-2 py-1 rounded-full">RESPONSIVE</span>
              </div>
              <div class="mt-4 space-y-4">
              @foreach(range(1,5) as $slot)
                @php $desktopKey=$slot===1?'hero_image':"hero_image_{$slot}"; $mobileKey="hero_mobile_image_{$slot}"; @endphp
                <details class="group rounded-xl border border-gray-200 bg-white" {{ $slot===1 ? 'open' : '' }}>
                  <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3"><span class="text-sm font-bold text-gray-800">Slide {{ $slot }}</span><span class="text-xs text-gray-400 group-open:hidden">Configurar</span><span class="text-xs text-indigo-600 hidden group-open:inline">Ocultar</span></summary>
                  <div class="border-t border-gray-100 p-4 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div><p class="text-xs font-semibold text-gray-600 mb-2">Imagen para PC</p><div class="aspect-[16/6] rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center"><template x-if="heroPrev[{{ $slot }}]"><img :src="heroPrev[{{ $slot }}]" class="w-full h-full object-cover"></template><template x-if="!heroPrev[{{ $slot }}]"><span class="text-xs text-gray-400">1920×650</span></template></div><div class="flex gap-2 mt-2"><label class="px-3 py-2 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg cursor-pointer">Subir PC<input type="file" accept="image/*" class="hidden" @change="uploadSlide($event,{{ $slot }},false)"></label><button type="button" x-show="heroPrev[{{ $slot }}]" @click="quitarSlide({{ $slot }},false)" class="px-3 py-2 bg-red-50 text-red-600 text-xs font-semibold rounded-lg">Quitar</button></div><input type="hidden" name="{{ $desktopKey }}" id="hero_image_input_{{ $slot }}" value="{{ $project->setting($desktopKey) }}"></div>
                      <div><p class="text-xs font-semibold text-gray-600 mb-2">Imagen para celular</p><div class="aspect-[4/5] max-h-52 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center"><template x-if="heroMobilePrev[{{ $slot }}]"><img :src="heroMobilePrev[{{ $slot }}]" class="w-full h-full object-cover"></template><template x-if="!heroMobilePrev[{{ $slot }}]"><span class="text-xs text-gray-400">750×950</span></template></div><div class="flex gap-2 mt-2"><label class="px-3 py-2 bg-violet-50 text-violet-700 text-xs font-semibold rounded-lg cursor-pointer">Subir móvil<input type="file" accept="image/*" class="hidden" @change="uploadSlide($event,{{ $slot }},true)"></label><button type="button" x-show="heroMobilePrev[{{ $slot }}]" @click="quitarSlide({{ $slot }},true)" class="px-3 py-2 bg-red-50 text-red-600 text-xs font-semibold rounded-lg">Quitar</button></div><input type="hidden" name="{{ $mobileKey }}" id="hero_mobile_image_input_{{ $slot }}" value="{{ $project->setting($mobileKey) }}"></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3"><div><label class="label">Título del slide</label><input type="text" name="hero_title_{{ $slot }}" class="input" value="{{ $project->setting('hero_title_'.$slot) }}" placeholder="Título principal"></div><div><label class="label">Etiqueta</label><input type="text" name="hero_badge_{{ $slot }}" class="input" value="{{ $project->setting('hero_badge_'.$slot) }}" placeholder="NUEVO / OFERTA"></div></div>
                    <div><label class="label">Descripción</label><input type="text" name="hero_subtitle_{{ $slot }}" class="input" value="{{ $project->setting('hero_subtitle_'.$slot) }}" placeholder="Texto breve para acompañar el anuncio"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <label class="label">Alineación<select name="hero_slide_{{ $slot }}_align" class="input mt-1"><option value="left" {{ $project->setting('hero_slide_'.$slot.'_align','left')==='left'?'selected':'' }}>Izquierda</option><option value="center" {{ $project->setting('hero_slide_'.$slot.'_align','left')==='center'?'selected':'' }}>Centro</option><option value="right" {{ $project->setting('hero_slide_'.$slot.'_align','left')==='right'?'selected':'' }}>Derecha</option></select></label>
                      <label class="label">Posición de imagen<select name="hero_slide_{{ $slot }}_position" class="input mt-1"><option value="center" {{ $project->setting('hero_slide_'.$slot.'_position','center')==='center'?'selected':'' }}>Centro</option><option value="left" {{ $project->setting('hero_slide_'.$slot.'_position','center')==='left'?'selected':'' }}>Izquierda</option><option value="right" {{ $project->setting('hero_slide_'.$slot.'_position','center')==='right'?'selected':'' }}>Derecha</option><option value="top" {{ $project->setting('hero_slide_'.$slot.'_position','center')==='top'?'selected':'' }}>Arriba</option><option value="bottom" {{ $project->setting('hero_slide_'.$slot.'_position','center')==='bottom'?'selected':'' }}>Abajo</option></select></label>
                      <label class="label">Oscurecimiento (%)<input type="number" min="0" max="90" step="5" name="hero_slide_{{ $slot }}_overlay" class="input mt-1" value="{{ $project->setting('hero_slide_'.$slot.'_overlay',$project->setting('hero_overlay',50)) }}"></label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                      @foreach(['enabled'=>'Slide activo','show_content'=>'Mostrar texto','cta1_show'=>'Botón principal'] as $suffix=>$label)
                      <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 text-xs font-medium"><input type="hidden" name="hero_slide_{{ $slot }}_{{ $suffix }}" value="0"><input type="checkbox" name="hero_slide_{{ $slot }}_{{ $suffix }}" value="1" {{ $project->setting('hero_slide_'.$slot.'_'.$suffix,'1')==='1'?'checked':'' }} class="rounded text-indigo-600">{{ $label }}</label>
                      @endforeach
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-xl bg-gray-50 border border-gray-200 p-3">
                      <div class="space-y-2"><p class="text-xs font-bold text-gray-700">Botón principal</p><input type="text" name="hero_slide_{{ $slot }}_cta1_text" class="input" value="{{ $project->setting('hero_slide_'.$slot.'_cta1_text',$project->setting('hero_cta1_text','Ver catálogo')) }}" placeholder="Ver productos"><input type="text" name="hero_slide_{{ $slot }}_cta1_url" class="input" value="{{ $project->setting('hero_slide_'.$slot.'_cta1_url','#catalogo') }}" placeholder="#catalogo o https://..."></div>
                      <div class="space-y-2"><div class="flex items-center justify-between"><p class="text-xs font-bold text-gray-700">Botón secundario</p><label class="text-xs flex items-center gap-1"><input type="hidden" name="hero_slide_{{ $slot }}_cta2_show" value="0"><input type="checkbox" name="hero_slide_{{ $slot }}_cta2_show" value="1" {{ $project->setting('hero_slide_'.$slot.'_cta2_show','0')==='1'?'checked':'' }}> Mostrar</label></div><input type="text" name="hero_slide_{{ $slot }}_cta2_text" class="input" value="{{ $project->setting('hero_slide_'.$slot.'_cta2_text',$project->setting('hero_cta2_text','Contáctanos')) }}" placeholder="Contáctanos"><input type="text" name="hero_slide_{{ $slot }}_cta2_url" class="input" value="{{ $project->setting('hero_slide_'.$slot.'_cta2_url','') }}" placeholder="https://wa.me/..."></div>
                    </div>
                  </div>
                </details>
              @endforeach
              </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4">
              <div><p class="text-sm font-semibold text-gray-800">Comportamiento del slider</p><p class="text-xs text-gray-400">Controla la rotación y navegación.</p></div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="label">Duración por slide<select name="hero_duration" class="input mt-1">@foreach([3000=>'3 segundos',4000=>'4 segundos',5000=>'5 segundos',6000=>'6 segundos',8000=>'8 segundos',10000=>'10 segundos'] as $v=>$l)<option value="{{ $v }}" {{ (int)$project->setting('hero_duration',6000)===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></label>
                <label class="label">Transición<select name="hero_transition" class="input mt-1"><option value="fade" {{ $project->setting('hero_transition','fade')==='fade'?'selected':'' }}>Desvanecer</option><option value="slide" {{ $project->setting('hero_transition','fade')==='slide'?'selected':'' }}>Deslizar</option></select></label>
                <label class="label">Altura en computadora<select name="hero_height" class="input mt-1"><option value="small" {{ $project->setting('hero_height','medium')==='small'?'selected':'' }}>Pequeña (360 px)</option><option value="medium" {{ $project->setting('hero_height','medium')==='medium'?'selected':'' }}>Mediana (460 px)</option><option value="large" {{ $project->setting('hero_height','medium')==='large'?'selected':'' }}>Grande (560 px)</option></select></label><label class="label">Altura en celular<input type="number" name="hero_mobile_height" min="360" max="760" step="20" class="input mt-1" value="{{ $project->setting('hero_mobile_height',520) }}"></label>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach(['hero_autoplay'=>'Rotación automática','hero_pause_hover'=>'Pausar al pasar mouse','hero_show_arrows'=>'Mostrar flechas','hero_show_dots'=>'Mostrar indicadores'] as $key=>$label)
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 text-xs font-medium text-gray-700"><input type="hidden" name="{{ $key }}" value="0"><input type="checkbox" name="{{ $key }}" value="1" {{ $project->setting($key,'1')==='1'?'checked':'' }} class="rounded text-indigo-600">{{ $label }}</label>
                @endforeach
              </div>
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
            <div>
              <label class="label">Alineación global del contenido</label>
              <select name="hero_align" class="input">
                <option value="left"   {{ $project->setting('hero_align', 'left') === 'left'   ? 'selected' : '' }}>Izquierda</option>
                <option value="center" {{ $project->setting('hero_align', 'left') === 'center' ? 'selected' : '' }}>Centrado</option>
                <option value="right"  {{ $project->setting('hero_align', 'left') === 'right'  ? 'selected' : '' }}>Derecha</option>
              </select>
              <p class="text-xs text-gray-400 mt-1">Se usa como valor predeterminado. Cada slide puede tener su propia alineación.</p>
            </div>
            {{-- CTAs --}}
            <div class="border-t border-gray-200 pt-4 space-y-3">
              <p class="text-sm font-semibold text-gray-700">Botones predeterminados</p><p class="text-xs text-gray-400">Se aplican cuando un slide no tiene textos propios.</p>
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
        {{-- Anuncios promocionales --}}
        @php
          $promoOrderKeys = [1,2,3];
          $savedPromoOrder = array_values(array_filter(
            array_map('intval', explode(',', $project->setting('promo_order', '1,2,3'))),
            fn($slot) => in_array($slot, $promoOrderKeys, true)
          ));
          $savedPromoOrder = array_values(array_unique(array_merge($savedPromoOrder, $promoOrderKeys)));
        @endphp
        <div class="bg-white rounded-xl border border-amber-200 overflow-hidden">
          <div class="px-4 py-3 bg-amber-50 border-b border-amber-100">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-amber-950">Anuncios promocionales</p>
                <p class="text-xs text-amber-700 mt-0.5">Configura hasta 3 anuncios con imagen para computadora y celular.</p>
              </div>
              <div x-data="{ on: {{ $project->setting('promo_enabled', '1') === '1' ? 'true' : 'false' }} }">
                <input type="hidden" name="promo_enabled" :value="on ? '1' : '0'">
                <button type="button" @click="on=!on" :class="on ? 'bg-amber-500' : 'bg-gray-200'" class="relative w-11 h-6 rounded-full transition-colors">
                  <span :class="on ? 'translate-x-5' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </div>
          </div>

          <div class="p-4 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="label text-xs">Título de la sección</label>
                <input type="text" name="promo_section_title" class="input text-sm"
                       value="{{ $project->setting('promo_section_title', 'Promociones') }}">
              </div>
              <div>
                <label class="label text-xs">Subtítulo opcional</label>
                <input type="text" name="promo_section_subtitle" class="input text-sm"
                       placeholder="Ofertas, campañas y novedades de temporada"
                       value="{{ $project->setting('promo_section_subtitle') }}">
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="label text-xs">Formato</label>
                <select name="promo_style" class="input text-sm">
                  <option value="slider" {{ $project->setting('promo_style', 'slider') === 'slider' ? 'selected' : '' }}>Slider grande</option>
                  <option value="grid" {{ $project->setting('promo_style', 'slider') === 'grid' ? 'selected' : '' }}>Tarjetas en cuadrícula</option>
                </select>
              </div>
              <div>
                <label class="label text-xs">Columnas en cuadrícula</label>
                <select name="promo_columns" class="input text-sm">
                  @foreach([1,2,3] as $n)<option value="{{ $n }}" {{ (int)$project->setting('promo_columns', 3) === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach
                </select>
              </div>
              <div>
                <label class="label text-xs">Altura PC</label>
                <input type="number" min="220" max="620" name="promo_height" class="input text-sm" value="{{ $project->setting('promo_height', 360) }}">
              </div>
              <div>
                <label class="label text-xs">Altura móvil</label>
                <input type="number" min="220" max="520" name="promo_mobile_height" class="input text-sm" value="{{ $project->setting('promo_mobile_height', 300) }}">
              </div>
              <div>
                <label class="label text-xs">Oscurecer imagen (%)</label>
                <input type="number" min="0" max="90" name="promo_overlay" class="input text-sm" value="{{ $project->setting('promo_overlay', 48) }}">
              </div>
              <div>
                <label class="label text-xs">Duración slider (ms)</label>
                <input type="number" min="3000" max="12000" step="500" name="promo_duration" class="input text-sm" value="{{ $project->setting('promo_duration', 5500) }}">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
              @foreach([
                ['key'=>'promo_autoplay','label'=>'Reproducción automática','def'=>'1'],
                ['key'=>'promo_show_dots','label'=>'Mostrar indicadores','def'=>'1'],
              ] as $toggle)
              <label class="flex items-center justify-between gap-3 cursor-pointer">
                <span class="text-sm text-gray-700">{{ $toggle['label'] }}</span>
                <div x-data="{ on: {{ $project->setting($toggle['key'], $toggle['def']) === '1' ? 'true' : 'false' }} }">
                  <input type="hidden" name="{{ $toggle['key'] }}" :value="on ? '1' : '0'">
                  <button type="button" @click="on=!on" :class="on ? 'bg-amber-500' : 'bg-gray-200'" class="relative w-10 h-5 rounded-full transition-colors">
                    <span :class="on ? 'translate-x-5' : 'translate-x-1'" class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                  </button>
                </div>
              </label>
              @endforeach
            </div>

            {{-- Orden de anuncios --}}
            <div x-data="{
              items:@js(collect($savedPromoOrder)->map(fn($slot)=>['slot'=>$slot,'label'=>'Anuncio '.$slot])->values()),
              dragging:null,
              move(index,direction){ const next=index+direction;if(next<0||next>=this.items.length)return;const item=this.items.splice(index,1)[0];this.items.splice(next,0,item); },
              dropAt(index){ if(this.dragging===null||this.dragging===index)return;const item=this.items.splice(this.dragging,1)[0];this.items.splice(index,0,item);this.dragging=null; }
            }">
              <input type="hidden" name="promo_order" :value="items.map(item=>item.slot).join(',')">
              <label class="label text-xs mb-2">Orden de aparición</label>
              <div class="space-y-2">
                <template x-for="(item,index) in items" :key="item.slot">
                  <div draggable="true" @dragstart="dragging=index" @dragend="dragging=null" @dragover.prevent @drop.prevent="dropAt(index)"
                       class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 cursor-move">
                    <span class="text-gray-400">⋮⋮</span>
                    <span class="flex-1 text-sm font-medium text-gray-700" x-text="item.label"></span>
                    <button type="button" @click="move(index,-1)" :disabled="index===0" class="w-8 h-8 rounded border bg-white disabled:opacity-30">↑</button>
                    <button type="button" @click="move(index,1)" :disabled="index===items.length-1" class="w-8 h-8 rounded border bg-white disabled:opacity-30">↓</button>
                  </div>
                </template>
              </div>
            </div>

            {{-- Los 3 anuncios --}}
            <div class="space-y-4">
              @for($i=1;$i<=3;$i++)
              <div class="rounded-xl border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b">
                  <div>
                    <p class="text-sm font-semibold text-gray-800">Anuncio {{ $i }}</p>
                    <p class="text-[11px] text-gray-400">Recomendado PC: 1600×500 · móvil: 750×900</p>
                  </div>
                  <div x-data="{ on: {{ $project->setting("promo_item_{$i}_enabled", '1') === '1' ? 'true' : 'false' }} }">
                    <input type="hidden" name="promo_item_{{ $i }}_enabled" :value="on ? '1' : '0'">
                    <button type="button" @click="on=!on" :class="on ? 'bg-amber-500' : 'bg-gray-200'" class="relative w-10 h-5 rounded-full transition-colors">
                      <span :class="on ? 'translate-x-5' : 'translate-x-1'" class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                    </button>
                  </div>
                </div>

                <div class="p-4 space-y-4">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="label text-xs">Imagen para computadora</label>
                      <input type="hidden" id="promo_image_input_{{ $i }}" name="promo_image_{{ $i }}" value="{{ $project->setting("promo_image_{$i}") }}">
                      <div class="rounded-xl border border-dashed border-gray-300 overflow-hidden bg-gray-50">
                        <template x-if="promoPrev[{{ $i }}]"><img :src="promoPrev[{{ $i }}]" class="w-full h-28 object-cover"></template>
                        <div class="p-2 flex gap-2">
                          <label class="btn-secondary text-xs cursor-pointer">Subir imagen<input type="file" accept="image/*" class="hidden" @change="uploadPromo($event,{{ $i }},false)"></label>
                          <button type="button" class="text-xs text-red-600" @click="quitarPromo({{ $i }},false)">Quitar</button>
                        </div>
                      </div>
                    </div>
                    <div>
                      <label class="label text-xs">Imagen para celular</label>
                      <input type="hidden" id="promo_mobile_image_input_{{ $i }}" name="promo_mobile_image_{{ $i }}" value="{{ $project->setting("promo_mobile_image_{$i}") }}">
                      <div class="rounded-xl border border-dashed border-gray-300 overflow-hidden bg-gray-50">
                        <template x-if="promoMobilePrev[{{ $i }}]"><img :src="promoMobilePrev[{{ $i }}]" class="w-full h-28 object-cover"></template>
                        <div class="p-2 flex gap-2">
                          <label class="btn-secondary text-xs cursor-pointer">Subir imagen<input type="file" accept="image/*" class="hidden" @change="uploadPromo($event,{{ $i }},true)"></label>
                          <button type="button" class="text-xs text-red-600" @click="quitarPromo({{ $i }},true)">Quitar</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="label text-xs">Título</label>
                      <input type="text" name="promo_title_{{ $i }}" class="input text-sm"
                             placeholder="Ej: Renueva tu equipo"
                             value="{{ $project->setting("promo_title_{$i}", $project->setting("banner{$i}_title")) }}">
                    </div>
                    <div>
                      <label class="label text-xs">Descripción</label>
                      <input type="text" name="promo_subtitle_{{ $i }}" class="input text-sm"
                             placeholder="Ej: Tecnología seleccionada para tu negocio"
                             value="{{ $project->setting("promo_subtitle_{$i}", $project->setting("banner{$i}_sub")) }}">
                    </div>
                    <div>
                      <label class="label text-xs">Texto del botón</label>
                      <input type="text" name="promo_cta_text_{{ $i }}" class="input text-sm"
                             value="{{ $project->setting("promo_cta_text_{$i}", 'Ver promoción') }}">
                    </div>
                    <div>
                      <label class="label text-xs">Enlace del botón</label>
                      <input type="text" name="promo_cta_url_{{ $i }}" class="input text-sm"
                             placeholder="/tienda o https://..."
                             value="{{ $project->setting("promo_cta_url_{$i}") }}">
                    </div>
                    <div>
                      <label class="label text-xs">Alineación del contenido</label>
                      <select name="promo_align_{{ $i }}" class="input text-sm">
                        @foreach(['left'=>'Izquierda','center'=>'Centro','right'=>'Derecha'] as $value=>$label)
                        <option value="{{ $value }}" {{ $project->setting("promo_align_{$i}", 'left') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              @endfor
            </div>

            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-800 leading-relaxed">
              <strong>Recomendación:</strong> usa el formato Slider para campañas principales y el formato Cuadrícula para mostrar 2 o 3 promociones simultáneas. No coloques botones dentro de las imágenes; el botón real se configura aquí.
            </div>
          </div>
        </div>

        {{-- Categorías destacadas --}}
        @php
          $categoryIconOptions = [
            'default'=>'General','pc'=>'Computadora','laptop'=>'Laptop','monitor'=>'Monitor',
            'impresora'=>'Impresora','camara'=>'Cámara','camera-security'=>'Seguridad',
            'disco'=>'Disco / memoria','teclado'=>'Teclado','mouse'=>'Mouse','audio'=>'Audio',
            'celular'=>'Celular','router'=>'Router / WiFi','gaming'=>'Gaming','chip'=>'Procesador',
            'cable'=>'Cable','escritorio'=>'PC escritorio'
          ];
          $categoryIconSvg = [
            'default'=>'<rect x="4" y="4" width="16" height="16" rx="2"></rect><rect x="8" y="8" width="8" height="8" rx="1"></rect>',
            'pc'=>'<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M12 15v4"></path>',
            'laptop'=>'<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M2 20h20M8 20l1-3M16 20l-1-3"></path>',
            'monitor'=>'<rect x="3" y="4" width="18" height="12" rx="1.5"></rect><path d="M8 20h8M12 16v4"></path>',
            'impresora'=>'<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><path d="M6 14h12v7H6z"></path>',
            'camara'=>'<path d="M4 7h4l2-3h4l2 3h4v13H4z"></path><circle cx="12" cy="13" r="4"></circle>',
            'camera-security'=>'<path d="M4 7h12l4 4-4 4H4z"></path><circle cx="10" cy="11" r="2"></circle><path d="M10 15v4M7 19h6"></path>',
            'disco'=>'<rect x="4" y="3" width="16" height="18" rx="2"></rect><circle cx="12" cy="11" r="4"></circle><path d="M8 18h8"></path>',
            'teclado'=>'<rect x="2" y="6" width="20" height="12" rx="2"></rect><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h12"></path>',
            'mouse'=>'<rect x="7" y="3" width="10" height="18" rx="5"></rect><path d="M12 7v3"></path>',
            'audio'=>'<path d="M4 14h4l5 4V6L8 10H4zM17 9a4 4 0 0 1 0 6M19 6a8 8 0 0 1 0 12"></path>',
            'celular'=>'<rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path>',
            'router'=>'<rect x="3" y="11" width="18" height="8" rx="2"></rect><path d="M7 15h.01M11 15h.01M17 15h.01M8 8a6 6 0 0 1 8 0M10 10a3 3 0 0 1 4 0"></path>',
            'gaming'=>'<path d="M7 8h10a5 5 0 0 1 4.7 6.7l-1 2.8a2 2 0 0 1-3.3.8L15 16H9l-2.4 2.3a2 2 0 0 1-3.3-.8l-1-2.8A5 5 0 0 1 7 8z"></path><path d="M8 11v4M6 13h4M16 12h.01M18 14h.01"></path>',
            'chip'=>'<rect x="7" y="7" width="10" height="10" rx="2"></rect><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"></path>',
            'cable'=>'<path d="M7 7V3M5 3h4M17 21v-4M15 21h4M7 7c0 7 10 3 10 10"></path>',
            'escritorio'=>'<rect x="4" y="3" width="16" height="12" rx="1.5"></rect><path d="M9 19h6M8 15v4M16 15v4M12 15v4"></path>',
          ];
          $savedCategoryVisuals = json_decode($project->setting('featured_categories_items', '{}'), true);
          $savedCategoryVisuals = is_array($savedCategoryVisuals) ? $savedCategoryVisuals : [];
          $categoryVisualPayload = $storeCategories->map(function($cat) use ($savedCategoryVisuals) {
              $saved = $savedCategoryVisuals[(string)$cat->id] ?? [];
              $image = $saved['image'] ?? '';
              return [
                  'id'=>(string)$cat->id,
                  'name'=>$cat->name,
                  'visual'=>$saved['visual'] ?? 'inherit',
                  'icon'=>$saved['icon'] ?? 'default',
                  'image'=>$image,
                  'preview'=>$image ? (str_starts_with($image,'http') ? $image : asset('storage/'.ltrim(preg_replace('#^storage/#','',$image),'/'))) : '',
                  'fit'=>$saved['fit'] ?? 'cover',
                  'shape'=>$saved['shape'] ?? 'inherit',
              ];
          })->values();
        @endphp

        <div class="bg-white rounded-xl border border-blue-200 overflow-hidden"
             x-data="{
               categories:@js($categoryVisualPayload),
               icons:@js($categoryIconOptions),
               iconSvg:@js($categoryIconSvg),
               openCategory:null,
               openLibrary:null,
               previewShape(cat){ return cat.shape==='inherit' ? @js($project->setting('featured_categories_shape','rounded')) : cat.shape; },
               async uploadImage(event,index){
                 const file=event.target.files[0]; if(!file)return;
                 const fd=new FormData(); fd.append('file',file); fd.append('type','category_visual'); fd.append('_token','{{ csrf_token() }}');
                 const response=await fetch('{{ route('settings.upload-logo') }}',{method:'POST',body:fd});
                 const data=await response.json();
                 if(data.path){this.categories[index].image=data.path;this.categories[index].preview=data.url;this.categories[index].visual='image';}
               },
               removeImage(index){this.categories[index].image='';this.categories[index].preview='';if(this.categories[index].visual==='image')this.categories[index].visual='icon';},
               selectIcon(index,key){this.categories[index].icon=key;this.categories[index].visual='icon';this.openLibrary=null;},
               payload(){const out={};this.categories.forEach(cat=>out[cat.id]={visual:cat.visual,icon:cat.icon,image:cat.image,fit:cat.fit,shape:cat.shape});return JSON.stringify(out);}
             }">
          <input type="hidden" name="featured_categories_items" :value="payload()">

          <div class="px-4 py-4 bg-blue-50 border-b border-blue-100">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-blue-950">Categorías destacadas</p>
                <p class="text-xs text-blue-700 mt-1">Cambia el diseño general y personaliza cada categoría con un ícono real, una imagen o una inicial.</p>
              </div>
              <div x-data="{on:{{ $project->setting('featured_categories_enabled','1')==='1'?'true':'false' }}">
                <input type="hidden" name="featured_categories_enabled" :value="on?'1':'0'">
                <button type="button" @click="on=!on" :class="on?'bg-blue-600':'bg-gray-200'" class="relative w-11 h-6 rounded-full transition-colors">
                  <span :class="on?'translate-x-5':'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </div>
          </div>

          <div class="p-4 space-y-5">
            <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
              <p class="text-xs font-semibold text-indigo-950">Flujo correcto</p>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
                <div class="rounded-lg bg-white border border-indigo-100 p-3"><strong class="block text-xs text-gray-800">1. Selecciona las categorías</strong><span class="block mt-1 text-[11px] text-gray-500">En Inicio → Categorías principales eliges cuáles aparecerán.</span></div>
                <div class="rounded-lg bg-white border border-indigo-100 p-3"><strong class="block text-xs text-gray-800">2. Personaliza su apariencia</strong><span class="block mt-1 text-[11px] text-gray-500">Aquí eliges forma, ícono, imagen o inicial.</span></div>
                <div class="rounded-lg bg-white border border-indigo-100 p-3"><strong class="block text-xs text-gray-800">3. Publica la sección</strong><span class="block mt-1 text-[11px] text-gray-500">Si queda en Borrador no aparecerá en la tienda.</span></div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div><label class="label text-xs">Título</label><input type="text" name="featured_categories_title" class="input text-sm" value="{{ $project->setting('featured_categories_title','Explora por categoría') }}"></div>
              <div><label class="label text-xs">Texto “Ver todo”</label><input type="text" name="featured_categories_all_text" class="input text-sm" value="{{ $project->setting('featured_categories_all_text','Ver todo') }}"></div>
              <div class="sm:col-span-2"><label class="label text-xs">Subtítulo</label><input type="text" name="featured_categories_subtitle" class="input text-sm" value="{{ $project->setting('featured_categories_subtitle') }}"></div>
            </div>

            <div class="rounded-xl border border-gray-200 p-4">
              <p class="text-sm font-semibold text-gray-900">Diseño general</p>
              <p class="text-xs text-gray-500 mt-1">Se aplicará a las categorías que no tengan una configuración individual.</p>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                <div><label class="label text-xs">Contenido visual</label><select name="featured_categories_visual" class="input text-sm">@foreach(['auto'=>'Automático','image'=>'Imagen','icon'=>'Ícono','initial'=>'Inicial'] as $v=>$l)<option value="{{ $v }}" {{ $project->setting('featured_categories_visual','auto')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
                <div><label class="label text-xs">Forma</label><select name="featured_categories_shape" class="input text-sm">@foreach(['rounded'=>'Cuadro redondeado','square'=>'Cuadro recto','circle'=>'Circular'] as $v=>$l)<option value="{{ $v }}" {{ $project->setting('featured_categories_shape','rounded')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
                <div><label class="label text-xs">Composición</label><select name="featured_categories_style" class="input text-sm">@foreach(['image-top'=>'Imagen superior','overlay'=>'Imagen de fondo','minimal'=>'Minimalista','horizontal'=>'Horizontal'] as $v=>$l)<option value="{{ $v }}" {{ $project->setting('featured_categories_style','image-top')===$v?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
                <div><label class="label text-xs">Ajuste de imagen</label><select name="featured_categories_image_fit" class="input text-sm"><option value="cover" {{ $project->setting('featured_categories_image_fit','cover')==='cover'?'selected':'' }}>Cubrir</option><option value="contain" {{ $project->setting('featured_categories_image_fit','cover')==='contain'?'selected':'' }}>Mostrar completa</option></select></div>
                <div><label class="label text-xs">Columnas PC</label><select name="featured_categories_columns" class="input text-sm">@foreach([2,3,4,5,6] as $n)<option value="{{ $n }}" {{ (int)$project->setting('featured_categories_columns',4)===$n?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
                <div><label class="label text-xs">Columnas móvil</label><select name="featured_categories_mobile_columns" class="input text-sm">@foreach([1,2] as $n)<option value="{{ $n }}" {{ (int)$project->setting('featured_categories_mobile_columns',2)===$n?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
                <div><label class="label text-xs">Máximo a mostrar</label><select name="featured_categories_limit" class="input text-sm">@foreach([4,5,6,8,10,12] as $n)<option value="{{ $n }}" {{ (int)$project->setting('featured_categories_limit',8)===$n?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
                <div><label class="label text-xs">Radio de bordes</label><input type="number" min="0" max="32" name="featured_categories_radius" class="input text-sm" value="{{ $project->setting('featured_categories_radius',18) }}"></div>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              @foreach([
                ['key'=>'featured_categories_section_bg','label'=>'Fondo sección','def'=>'#f8fafc'],
                ['key'=>'featured_categories_card_bg','label'=>'Fondo tarjeta','def'=>'#ffffff'],
                ['key'=>'featured_categories_text_color','label'=>'Color texto','def'=>'#0f172a'],
                ['key'=>'featured_categories_accent','label'=>'Color principal','def'=>$pc]
              ] as $field)
              <div><label class="label text-xs">{{ $field['label'] }}</label><input type="color" name="{{ $field['key'] }}" class="w-full h-10 rounded-lg border p-1" value="{{ $project->setting($field['key'],$field['def']) }}"></div>
              @endforeach
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              @foreach([
                ['key'=>'featured_categories_show_all','label'=>'Mostrar “Ver todo”','def'=>'1'],
                ['key'=>'featured_categories_show_count','label'=>'Mostrar cantidad','def'=>'1'],
                ['key'=>'featured_categories_hide_empty','label'=>'Ocultar categorías vacías','def'=>'1'],
                ['key'=>'featured_categories_mobile_carousel','label'=>'Carrusel en celular','def'=>'0']
              ] as $toggle)
              <label class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-3"><span class="text-sm text-gray-700">{{ $toggle['label'] }}</span><div x-data="{on:{{ $project->setting($toggle['key'],$toggle['def'])==='1'?'true':'false' }}"><input type="hidden" name="{{ $toggle['key'] }}" :value="on?'1':'0'"><button type="button" @click="on=!on" :class="on?'bg-blue-600':'bg-gray-200'" class="relative w-10 h-5 rounded-full transition-colors"><span :class="on?'translate-x-5':'translate-x-1'" class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span></button></div></label>
              @endforeach
            </div>

            <div class="space-y-3">
              <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-semibold text-gray-900">Personalización por categoría</p><p class="text-xs text-gray-500 mt-1">Abre una categoría y elige ícono, imagen, inicial o una forma diferente.</p></div><span class="text-[10px] font-semibold text-blue-700 bg-blue-50 border border-blue-100 rounded-full px-3 py-1">{{ $storeCategories->count() }} categorías</span></div>

              <template x-for="(cat,index) in categories" :key="cat.id">
                <article class="rounded-xl border border-gray-200 overflow-hidden bg-white">
                  <button type="button" @click="openCategory=openCategory===index?null:index" class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 text-left">
                    <div class="w-14 h-14 border border-gray-200 bg-white overflow-hidden grid place-items-center" :class="{'rounded-full':previewShape(cat)==='circle','rounded-xl':previewShape(cat)==='rounded','rounded-none':previewShape(cat)==='square'}">
                      <template x-if="cat.preview && cat.visual!=='icon' && cat.visual!=='initial'"><img :src="cat.preview" class="w-full h-full object-cover"></template>
                      <template x-if="cat.visual==='initial'"><span class="text-xl font-bold text-blue-600" x-text="cat.name.charAt(0).toUpperCase()"></span></template>
                      <template x-if="!cat.preview || cat.visual==='icon'"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-7 h-7 text-blue-600" x-html="iconSvg[cat.icon] || iconSvg.default"></svg></template>
                    </div>
                    <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-gray-900 truncate" x-text="cat.name"></p><p class="text-[11px] text-gray-500 mt-1"><span x-text="cat.visual==='inherit'?'Usa el diseño general':cat.visual==='image'?'Imagen personalizada':cat.visual==='icon'?'Ícono de biblioteca':'Inicial'"></span> · <span x-text="previewShape(cat)==='circle'?'Circular':previewShape(cat)==='square'?'Cuadro recto':'Cuadro redondeado'"></span></p></div>
                    <span class="text-xs font-semibold text-blue-700" x-text="openCategory===index?'Cerrar':'Configurar'"></span>
                  </button>

                  <div x-show="openCategory===index" x-cloak class="p-4 border-t border-gray-200 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                      <div><label class="label text-xs">Contenido visual</label><select x-model="cat.visual" class="input text-sm"><option value="inherit">Usar configuración general</option><option value="image">Imagen personalizada</option><option value="icon">Ícono de biblioteca</option><option value="initial">Inicial del nombre</option></select></div>
                      <div><label class="label text-xs">Forma individual</label><select x-model="cat.shape" class="input text-sm"><option value="inherit">Usar forma general</option><option value="rounded">Cuadro redondeado</option><option value="square">Cuadro recto</option><option value="circle">Circular</option></select></div>
                      <div><label class="label text-xs">Ajuste de imagen</label><select x-model="cat.fit" class="input text-sm"><option value="cover">Cubrir el espacio</option><option value="contain">Mostrar imagen completa</option></select></div>
                      <div><label class="label text-xs">Imagen personalizada</label><label class="flex min-h-10 items-center justify-center rounded-lg border border-dashed border-blue-300 bg-blue-50 text-xs font-semibold text-blue-700 cursor-pointer">Subir imagen<input type="file" accept="image/*" class="hidden" @change="uploadImage($event,index)"></label></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3"><button type="button" @click="openLibrary=openLibrary===index?null:index" class="px-3 py-2 rounded-lg border border-blue-200 bg-blue-50 text-xs font-semibold text-blue-700">Abrir biblioteca de íconos</button><button type="button" x-show="cat.preview" @click="removeImage(index)" class="px-3 py-2 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-700">Quitar imagen</button><span class="text-[11px] text-gray-500">Recomendado: imágenes cuadradas de 600 × 600 px.</span></div>
                    <div x-show="openLibrary===index" x-cloak class="rounded-xl border border-blue-100 bg-blue-50/40 p-3">
                      <div class="flex items-center justify-between gap-3 mb-3"><div><p class="text-xs font-semibold text-blue-950">Biblioteca de íconos</p><p class="text-[11px] text-blue-700 mt-1">Selecciona el ícono que representará esta categoría.</p></div><button type="button" @click="openLibrary=null" class="text-xs font-semibold text-gray-500">Cerrar</button></div>
                      <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-8 gap-2"><template x-for="(label,key) in icons" :key="key"><button type="button" @click="selectIcon(index,key)" :class="cat.icon===key?'border-blue-600 bg-blue-600 text-white shadow':'border-gray-200 bg-white text-gray-700 hover:border-blue-300'" class="min-h-24 rounded-xl border p-2 text-[10px] font-semibold transition flex flex-col items-center justify-center gap-2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-7 h-7" x-html="iconSvg[key] || iconSvg.default"></svg><span class="text-center leading-tight" x-text="label"></span></button></template></div>
                    </div>
                  </div>
                </article>
              </template>
            </div>

            <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600"><strong>Importante:</strong> aquí defines cómo se ve cada categoría. En <strong>Inicio → Categorías principales</strong> seleccionas cuáles aparecen y debes publicar esa sección.</div>
          </div>
        </div>

        {{-- Compra con confianza / Beneficios --}}
        <div class="bg-white rounded-xl border border-emerald-200 overflow-hidden">
          <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-100">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-sm font-semibold text-emerald-900">Compra con confianza</p>
                <p class="text-xs text-emerald-700 mt-0.5">Configura los beneficios comerciales que aparecen en la página de inicio.</p>
              </div>
              <div x-data="{ on: {{ $project->setting('trust_section_enabled', '1') === '1' ? 'true' : 'false' }} }">
                <input type="hidden" name="trust_section_enabled" :value="on ? '1' : '0'">
                <button type="button" @click="on=!on" :class="on ? 'bg-emerald-600' : 'bg-gray-200'" class="relative w-11 h-6 rounded-full transition-colors">
                  <span :class="on ? 'translate-x-5' : 'translate-x-1'" class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                </button>
              </div>
            </div>
          </div>

          <div class="p-4 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="label text-xs">Título de la sección</label>
                <input type="text" name="trust_section_title" class="input text-sm"
                       value="{{ $project->setting('trust_section_title', 'Compra con confianza') }}">
              </div>
              <div>
                <label class="label text-xs">Subtítulo</label>
                <input type="text" name="trust_section_subtitle" class="input text-sm"
                       value="{{ $project->setting('trust_section_subtitle', 'Beneficios pensados para darte una mejor experiencia.') }}">
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div>
                <label class="label text-xs">Estilo</label>
                <select name="trust_section_style" class="input text-sm">
                  @foreach(['cards'=>'Tarjetas modernas','compact'=>'Compacto','icons-top'=>'Íconos arriba'] as $value => $label)
                  <option value="{{ $value }}" {{ $project->setting('trust_section_style', 'cards') === $value ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label class="label text-xs">Columnas PC</label>
                <select name="trust_section_columns" class="input text-sm">
                  @foreach([2,3,4] as $n)<option value="{{ $n }}" {{ (int)$project->setting('trust_section_columns', 4) === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach
                </select>
              </div>
              <div>
                <label class="label text-xs">Columnas móvil</label>
                <select name="trust_section_mobile_columns" class="input text-sm">
                  @foreach([1,2] as $n)<option value="{{ $n }}" {{ (int)$project->setting('trust_section_mobile_columns', 1) === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach
                </select>
              </div>
              <div>
                <label class="label text-xs">Redondeado</label>
                <input type="number" min="0" max="28" name="trust_section_radius" class="input text-sm"
                       value="{{ $project->setting('trust_section_radius', 16) }}">
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              @foreach([
                ['key'=>'trust_section_bg','label'=>'Fondo sección','def'=>'#f8fafc'],
                ['key'=>'trust_card_bg','label'=>'Fondo tarjetas','def'=>'#ffffff'],
                ['key'=>'trust_text_color','label'=>'Color texto','def'=>'#0f172a'],
                ['key'=>'trust_accent_color','label'=>'Color íconos','def'=>$pc],
              ] as $field)
              <div>
                <label class="label text-xs">{{ $field['label'] }}</label>
                <input type="color" name="{{ $field['key'] }}" class="w-full h-10 rounded-lg border border-gray-200 p-1"
                       value="{{ $project->setting($field['key'], $field['def']) }}">
              </div>
              @endforeach
            </div>

            <div class="space-y-3">
              @php
                $trustIconOptions = [
                  'store'=>'Tienda','truck'=>'Camión','clock'=>'Reloj','sparkles'=>'Destacado',
                  'shield'=>'Seguridad','support'=>'Soporte','warranty'=>'Garantía'
                ];
                $trustDefaults = [
                  1 => ['Retiro en tienda','Coordina y recoge tu pedido.','store'],
                  2 => ['Envíos a todo el Perú','Cobertura según destino.','truck'],
                  3 => ['Entrega express','Consulta disponibilidad en tu zona.','clock'],
                  4 => ['Diseños exclusivos','Opciones seleccionadas para ti.','sparkles'],
                ];
              @endphp
              @for($i = 1; $i <= 4; $i++)
              <div class="rounded-xl border border-gray-200 p-3">
                <div class="flex items-center justify-between mb-3">
                  <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">Beneficio {{ $i }}</span>
                  <div x-data="{ on: {{ $project->setting("trust_item_{$i}_enabled", '1') === '1' ? 'true' : 'false' }} }">
                    <input type="hidden" name="trust_item_{{ $i }}_enabled" :value="on ? '1' : '0'">
                    <button type="button" @click="on=!on" :class="on ? 'bg-emerald-600' : 'bg-gray-200'" class="relative w-10 h-5 rounded-full transition-colors">
                      <span :class="on ? 'translate-x-5' : 'translate-x-1'" class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                    </button>
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="label text-xs">Título</label>
                    <input type="text" name="trust_text_{{ $i }}" class="input text-sm"
                           value="{{ $project->setting("trust_text_{$i}", $trustDefaults[$i][0]) }}">
                  </div>
                  <div>
                    <label class="label text-xs">Descripción</label>
                    <input type="text" name="trust_description_{{ $i }}" class="input text-sm"
                           value="{{ $project->setting("trust_description_{$i}", $trustDefaults[$i][1]) }}">
                  </div>
                  <div>
                    <label class="label text-xs">Ícono</label>
                    <select name="trust_icon_{{ $i }}" class="input text-sm">
                      @foreach($trustIconOptions as $value => $label)
                      <option value="{{ $value }}" {{ $project->setting("trust_icon_{$i}", $trustDefaults[$i][2]) === $value ? 'selected' : '' }}>{{ $label }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              @endfor
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
              @foreach([
                ['key'=>'trust_show_descriptions','label'=>'Mostrar descripciones','def'=>'1'],
                ['key'=>'trust_mobile_carousel','label'=>'Carrusel horizontal en móvil','def'=>'0'],
              ] as $toggle)
              <label class="flex items-center justify-between gap-3 cursor-pointer">
                <span class="text-sm text-gray-700">{{ $toggle['label'] }}</span>
                <div x-data="{ on: {{ $project->setting($toggle['key'], $toggle['def']) === '1' ? 'true' : 'false' }} }">
                  <input type="hidden" name="{{ $toggle['key'] }}" :value="on ? '1' : '0'">
                  <button type="button" @click="on=!on" :class="on ? 'bg-emerald-600' : 'bg-gray-200'" class="relative w-10 h-5 rounded-full transition-colors">
                    <span :class="on ? 'translate-x-5' : 'translate-x-1'" class="absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transform transition-transform"></span>
                  </button>
                </div>
              </label>
              @endforeach
            </div>
          </div>
        </div>

        <div class="constructor-savebar">
          <div><strong>Portada</strong><span>Revisa la vista pública después de cambiar imágenes o estilos.</span></div>
          <button type="submit" class="btn-primary">Guardar portada</button>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: CATALOGO --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'constructor')
      @php
        $cardStylesAll = \App\Modules\Tienda\Support\CatalogTemplates::cardStyles();
        $activeTplCat  = $project->setting('catalog_template', 'default') ?: 'default';
        $allTplsCat    = \App\Modules\Tienda\Support\CatalogTemplates::all();
        $tplInfoCat    = $allTplsCat[$activeTplCat] ?? $allTplsCat['default'];
      @endphp
      <div id="constructor-catalogo" x-show="cvTab==='catalogo'" x-cloak class="max-w-2xl mx-auto constructor-panel constructor-panel--catalogo">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5">
        @csrf
        
        <input type="hidden" name="_design_tab" value="catalogo">
        <div class="panel-heading">
          <div class="panel-heading-icon">🛍️</div>
          <div>
            <span>PASO 3 · CATÁLOGO</span>
            <h3>Productos y catálogo</h3>
            <p>Configura la presentación de productos, filtros, etiquetas y botones de compra.</p>
          </div>
        </div>

        <div class="ux-guide-card ux-guide-card--compact">
          <div class="ux-guide-card__head">
            <strong>Qué conviene configurar primero</strong>
            <span>Catálogo</span>
          </div>
          <div class="ux-guide-tags">
            <span>Vista de productos</span>
            <span>Columnas</span>
            <span>Filtros</span>
            <span>Etiquetas</span>
            <span>Botones</span>
            <span>WhatsApp</span>
          </div>
        </div>

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

        <div class="constructor-savebar">
          <div><strong>Catálogo</strong><span>Guarda columnas, filtros y tarjetas de producto.</span></div>
          <div class="flex items-center gap-3">
          <button type="submit" class="btn-primary">Guardar catálogo</button>
          <a href="{{ $storeUrl }}" target="_blank"
             class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            Ver tienda ↗
          </a>
          </div>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: SISTEMA --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'constructor')
      <div id="constructor-sistema" x-show="cvTab==='sistema'" x-cloak class="max-w-2xl mx-auto constructor-panel constructor-panel--sistema">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5"
>
        @csrf
        
        <input type="hidden" name="_design_tab" value="sistema">
        <div class="panel-heading">
          <div class="panel-heading-icon">⚙️</div>
          <div>
            <span>PASO 5 · VENTA Y AJUSTES</span>
            <h3>Configuración general</h3>
            <p>Administra el modo de venta, pagos, WhatsApp, footer, login y posicionamiento SEO.</p>
          </div>
        </div>

        <div class="ux-guide-card ux-guide-card--compact ux-guide-card--warning">
          <div class="ux-guide-card__head">
            <strong>Opciones avanzadas</strong>
            <span>Configura al final</span>
          </div>
          <div class="ux-guide-tags">
            <span>Modo de tienda</span>
            <span>Footer</span>
            <span>Pagos</span>
            <span>Login</span>
            <span>SEO</span>
          </div>
        </div>

        {{-- Apariencia del pie de página + moneda + WhatsApp (movido desde Marca) --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Pie de página y generales</p>
            <p class="text-xs text-gray-400 mt-0.5">Colores del footer, moneda y mensaje de WhatsApp</p>
          </div>
          <div class="p-4 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">Color del pie de página (fondo)</label>
                <div class="flex items-center gap-3 mt-1">
                  <input type="color" name="footer_bg_color" value="{{ $project->setting('footer_bg_color', '#111827') }}"
                         class="h-9 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                  <span class="text-xs text-gray-400">Fondo del footer público</span>
                </div>
              </div>
              <div>
                <label class="label">Color del texto del pie</label>
                <div class="flex items-center gap-3 mt-1">
                  <input type="color" name="footer_text_color" value="{{ $project->setting('footer_text_color', '#9ca3af') }}"
                         class="h-9 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5">
                  <span class="text-xs text-gray-400">Texto del footer</span>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">Alto del logo en el footer (px)</label>
                <input type="number" name="footer_logo_height" class="input" min="24" max="200"
                       placeholder="60" value="{{ $project->setting('footer_logo_height', '60') }}">
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
            <div>
              <label class="label">Mensaje de WhatsApp (botón flotante)</label>
              <input type="text" name="whatsapp_msg" class="input mt-1"
                     placeholder="Hola, vi tu catálogo y me interesa..."
                     value="{{ $project->setting('whatsapp_msg') }}">
              <p class="text-xs text-gray-400 mt-1">Se envía cuando el cliente toca el botón flotante de WhatsApp</p>
            </div>
          </div>
        </div>

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
                {{-- quote_whatsapp salio de esta puerta (revision 01): se edita en el
                     Constructor. El campo queda visible como referencia, sin name. --}}
              </div>
              <p class="text-xs text-gray-400 mt-1">Número completo: <span x-text="'+' + country + ' ' + local.replace(/\D/g,'')"></span></p>
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
            <p class="text-sm font-semibold text-gray-800">Footer — Contenido</p>
          </div>
          <div class="p-4 space-y-4">

            {{-- Tagline --}}
            <div>
              <label class="label">Eslogan del footer</label>
              <input type="text" name="footer_tagline" class="input"
                     placeholder="Ej: Los mejores spirits, directo a tu puerta."
                     value="{{ $project->setting('footer_tagline') }}">
              <p class="text-xs text-gray-400 mt-1">Frase breve debajo del logo en el footer.</p>
            </div>

            {{-- Copyright --}}
            <div>
              <label class="label">Copyright</label>
              <input type="text" name="footer_copyright" class="input"
                     placeholder="© 2026 Mi Tienda. Todos los derechos reservados."
                     value="{{ $project->setting('footer_copyright', '© ' . date('Y') . ' ' . $project->name . '. Todos los derechos reservados.') }}">
            </div>

            {{-- Texto desarrollado por --}}
            <div>
              <label class="label">Texto "Desarrollado por"</label>
              <input type="text" name="footer_dev_text" class="input"
                     placeholder="Desarrollado por AVAN"
                     value="{{ $project->setting('footer_dev_text', 'Desarrollado por AVAN') }}">
            </div>

            {{-- Contacto en footer --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 border-t pt-4">
              {{-- Solo lectura (revision 01): estos datos se editan en el Constructor. --}}
              <div>
                <label class="label">Correo de contacto</label>
                <input type="email" class="input bg-gray-50 text-gray-500" readonly value="{{ $project->setting('contact_email') }}" placeholder="—">
              </div>
              <div>
                <label class="label">Teléfono de contacto</label>
                <input type="text" class="input bg-gray-50 text-gray-500" readonly value="{{ $project->setting('contact_phone') }}" placeholder="—">
              </div>
              <div class="md:col-span-2">
                <label class="label">Horario de atención</label>
                <input type="text" class="input bg-gray-50 text-gray-500" readonly value="{{ $project->setting('business_hours') }}" placeholder="—">
                <p class="text-xs text-gray-400 mt-1">Se editan en <a href="{{ route('settings.builder') }}" class="text-indigo-600 font-semibold hover:underline">Mi Tienda → Datos del negocio</a>.</p>
              </div>
            </div>

            {{-- Beneficios (barra superior del footer) --}}
            <div class="border-t pt-4">
              <label class="label font-semibold">Barra de beneficios (hasta 3)</label>
              <p class="text-xs text-gray-400 mb-2">Iconos disponibles: tienda, envio, pago, seguridad, soporte, regalo, corazon</p>
              @foreach([1,2,3] as $bn)
              <div class="grid grid-cols-3 gap-2 mb-2">
                <input type="text" name="footer_benefit_{{ $bn }}_icon" class="input text-xs"
                       placeholder="Ícono (ej: tienda)"
                       value="{{ $project->setting('footer_benefit_'.$bn.'_icon', ['1'=>'tienda','2'=>'envio','3'=>'pago'][$bn] ?? '') }}">
                <input type="text" name="footer_benefit_{{ $bn }}_text" class="input text-xs col-span-2"
                       placeholder="Texto del beneficio"
                       value="{{ $project->setting('footer_benefit_'.$bn.'_text') }}">
              </div>
              @endforeach
            </div>

            {{-- Columna: Páginas de la tienda --}}
            <div class="border-t pt-4">
              <label class="label font-semibold">Columna "Información" — páginas y enlaces</label>
              <p class="text-xs text-gray-400 mb-2">Título | URL (una por línea). Ej: Políticas de privacidad | /privacidad</p>
              <textarea name="footer_pages" class="input text-xs font-mono" rows="6"
                        placeholder="Políticas de privacidad | /privacidad&#10;Términos y condiciones | /terminos&#10;Condiciones de entrega | /entrega&#10;Cambios y devoluciones | /devoluciones&#10;Preguntas frecuentes | /faq&#10;Formas de pago | /pagos">{{ $project->setting('footer_pages') }}</textarea>
            </div>

            {{-- Columna: Menú tienda --}}
            <div class="border-t pt-4">
              <label class="label font-semibold">Columna "{{ $project->name }}" — menú de la tienda</label>
              <p class="text-xs text-gray-400 mb-2">Título | URL (una por línea). Ej: ¿Quiénes somos? | /nosotros</p>
              <textarea name="footer_store_pages" class="input text-xs font-mono" rows="4"
                        placeholder="¿Quiénes somos? | /nosotros&#10;Contáctanos | /contacto">{{ $project->setting('footer_store_pages') }}</textarea>
            </div>

            {{-- Newsletter --}}
            <div class="border-t pt-4">
              <label class="label font-semibold">Boletín / Newsletter</label>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="label text-xs">Título del boletín</label>
                  <input type="text" name="footer_newsletter_title" class="input text-sm"
                         placeholder="Boletín"
                         value="{{ $project->setting('footer_newsletter_title', 'Boletín') }}">
                </div>
                <div>
                  <label class="label text-xs">URL de suscripción (form action)</label>
                  <input type="text" name="footer_newsletter_url" class="input text-sm"
                         placeholder="https://..."
                         value="{{ $project->setting('footer_newsletter_url') }}">
                </div>
              </div>
            </div>

            {{-- Toggles --}}
            <div class="border-t pt-4 space-y-2">
              @foreach([
                ['key'=>'footer_show_social',      'label'=>'Mostrar íconos de redes sociales', 'def'=>'1'],
                ['key'=>'footer_show_categories',  'label'=>'Mostrar columna de categorías',    'def'=>'1'],
                ['key'=>'footer_show_newsletter',  'label'=>'Mostrar formulario de boletín',    'def'=>'1'],
                ['key'=>'footer_show_benefits',    'label'=>'Mostrar barra de beneficios',      'def'=>'1'],
                ['key'=>'footer_show_address',     'label'=>'Mostrar dirección en el footer',   'def'=>'1'],
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
            <input type="hidden" name="login_color1" :value="color1">
            <input type="hidden" name="login_color2" :value="color2">
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
                    <input type="color" x-model="color1"
                           class="w-12 h-10 rounded-lg cursor-pointer border border-gray-200 p-0.5">
                    <code class="text-sm text-gray-600 font-mono" x-text="color1"></code>
                  </div>
                </div>
                <div>
                  <label class="label">Color final</label>
                  <div class="flex items-center gap-2 mt-1">
                    <input type="color" x-model="color2"
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
                <input type="color" x-model="color1"
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

        <div class="constructor-savebar">
          <div><strong>Sistema</strong><span>Configuración técnica, footer, pagos y posicionamiento.</span></div>
          <button type="submit" class="btn-primary">Guardar sistema</button>
        </div>
      </form>
      </div>{{-- /max-w-2xl --}}
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: CHECKOUT --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'constructor')
      @php
        $ckFields = json_decode($project->setting('checkout_fields', 'null'), true) ?? [
          'fixed'  => [
            'lname'   => ['label'=>'Apellido',  'enabled'=>true],
            'email'   => ['label'=>'Email',     'enabled'=>true],
            'dni'     => ['label'=>'DNI / RUC', 'enabled'=>true],
            'address' => ['label'=>'Dirección', 'enabled'=>false],
            'notes'   => ['label'=>'Notas',     'enabled'=>true],
          ],
          'custom' => [],
        ];
      @endphp
      <div id="constructor-checkout" x-show="cvTab==='checkout'" x-cloak class="max-w-2xl mx-auto constructor-panel constructor-panel--checkout">
      <form method="POST" action="{{ route('settings.design.update') }}" class="space-y-5">
        @csrf
        
        <input type="hidden" name="_design_tab" value="checkout">
        <div class="panel-heading">
          <div class="panel-heading-icon">🧾</div>
          <div>
            <span>PASO 5 · VENTA Y AJUSTES</span>
            <h3>Datos del pedido</h3>
            <p>Selecciona la información que el cliente deberá completar al finalizar su pedido.</p>
          </div>
        </div>

        <div class="ux-guide-card ux-guide-card--compact">
          <div class="ux-guide-card__head">
            <strong>Consejo de uso</strong>
            <span>Checkout</span>
          </div>
          <div class="ux-guide-tags">
            <span>Nombre</span>
            <span>Teléfono</span>
            <span>Dirección</span>
            <span>Entrega</span>
            <span>Observaciones</span>
          </div>
        </div>

        {{-- Campos fijos --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden"
             x-data="{
               fields: {{ json_encode($ckFields) }},
               newLabel: '', newType: 'text', newRequired: false,
               addField() {
                 if (!this.newLabel.trim()) return;
                 const key = 'cf_' + Date.now();
                 this.fields.custom.push({ key, label: this.newLabel.trim(), type: this.newType, required: this.newRequired, enabled: true });
                 this.newLabel = ''; this.newType = 'text'; this.newRequired = false;
               },
               removeCustom(idx) { this.fields.custom.splice(idx, 1); },
             }">
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Campos del formulario</p>
            <p class="text-xs text-gray-400 mt-0.5">Nombre y Celular son siempre obligatorios</p>
          </div>
          <input type="hidden" name="checkout_fields" :value="JSON.stringify(fields)">
          <div class="p-4 space-y-4">

            {{-- Campos estándar --}}
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Campos estándar</p>
              <div class="space-y-2">
                @foreach(['fname'=>'Nombre *','phone'=>'Celular *'] as $k=>$l)
                <div class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg border border-gray-100">
                  <span class="text-sm text-gray-500">{{ $l }}</span>
                  <span class="text-xs text-gray-400 bg-gray-200 px-2 py-0.5 rounded-full">Siempre activo</span>
                </div>
                @endforeach
                <template x-for="(cfg, key) in fields.fixed" :key="key">
                  <div class="flex items-center justify-between px-3 py-2 rounded-lg border border-gray-100 hover:bg-gray-50 transition">
                    <span class="text-sm text-gray-700" x-text="cfg.label"></span>
                    <button type="button" @click="cfg.enabled = !cfg.enabled"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors cursor-pointer"
                            :class="cfg.enabled ? 'bg-indigo-600' : 'bg-gray-300'">
                      <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                            :class="cfg.enabled ? 'translate-x-4' : 'translate-x-0'"></span>
                    </button>
                  </div>
                </template>
              </div>
            </div>

            {{-- Campos adicionales --}}
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Campos adicionales</p>
              <div class="space-y-2">
                <template x-for="(cf, idx) in fields.custom" :key="cf.key">
                  <div class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-100 hover:bg-gray-50">
                    <div class="flex-1 min-w-0">
                      <span class="text-sm text-gray-700" x-text="cf.label"></span>
                      <span class="ml-1 text-xs text-gray-400" x-text="'('+cf.type+')'"></span>
                      <span x-show="cf.required" class="ml-1 text-xs text-red-500">*obligatorio</span>
                    </div>
                    <button type="button" @click="cf.enabled = !cf.enabled"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 rounded-full border-2 border-transparent transition-colors cursor-pointer"
                            :class="cf.enabled ? 'bg-indigo-600' : 'bg-gray-300'">
                      <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                            :class="cf.enabled ? 'translate-x-4' : 'translate-x-0'"></span>
                    </button>
                    <button type="button" @click="removeCustom(idx)" class="text-red-400 hover:text-red-600 ml-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                </template>
                <div x-show="fields.custom.length === 0" class="text-xs text-gray-400 text-center py-2">Sin campos adicionales</div>
              </div>
              <div class="mt-3 p-3 bg-indigo-50 rounded-xl border border-indigo-100 space-y-2">
                <p class="text-xs font-semibold text-indigo-700">Agregar campo</p>
                <div class="grid grid-cols-2 gap-2">
                  <input type="text" x-model="newLabel" placeholder="Ej: Empresa, Color favorito..." class="input text-sm col-span-2">
                  <select x-model="newType" class="input text-sm">
                    <option value="text">Texto</option>
                    <option value="number">Número</option>
                    <option value="date">Fecha</option>
                    <option value="textarea">Área de texto</option>
                  </select>
                  <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" x-model="newRequired" class="rounded"> Obligatorio
                  </label>
                </div>
                <button type="button" @click="addField()"
                        class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                  + Agregar campo
                </button>
              </div>
            </div>

          </div>
        </div>

        <div class="constructor-savebar">
          <div><strong>Checkout</strong><span>Verifica el flujo de pedido después de guardar.</span></div>
          <button type="submit" class="btn-primary">Guardar checkout</button>
        </div>
      </form>
      </div>
      @endif

      @if($s === 'constructor')
        <section id="constructor-inicio" x-show="cvTab==='inicio'" x-cloak class="constructor-panel constructor-panel--inicio">
          <div class="panel-heading">
            <div class="panel-heading-icon">🏠</div>
            <div>
              <span>PASO 2 · PÁGINA DE INICIO</span>
              <h3>Estructura y orden</h3>
              <p>Activa, publica y organiza los bloques que aparecen en la página principal.</p>
            </div>
          </div>

          <div class="ux-guide-card">
            <div class="ux-guide-card__head">
              <strong>Cómo usar esta sección</strong>
              <span>Orden recomendado</span>
            </div>
            <div class="ux-guide-steps">
              <div class="ux-guide-step">
                <span>1</span>
                <div>
                  <strong>Activa y publica la sección</strong>
                  <small>“Mostrar sección” solo la habilita. Para verla en la tienda debes presionar “Guardar y publicar”. Una sección en borrador no aparece públicamente.</small>
                </div>
              </div>
              <div class="ux-guide-step">
                <span>2</span>
                <div>
                  <strong>Ordena de arriba hacia abajo</strong>
                  <small>Banner principal, beneficios, anuncios, categorías, campañas y productos.</small>
                </div>
              </div>
              <div class="ux-guide-step">
                <span>3</span>
                <div>
                  <strong>Luego pasa a “Diseño de portada”</strong>
                  <small>Ahí personalizas imágenes, textos, colores y estilos de cada sección.</small>
                </div>
              </div>
            </div>
          </div>

          <div id="home-builder-ux" class="home-builder-ux">
            @include('settings.store-experience')
          </div>
        </section>
      @endif

      {{-- ═══════════════════════════════════════ --}}
      {{-- TAB: PÁGINAS (Nosotros, Contacto y bandeja) --}}
      {{-- ═══════════════════════════════════════ --}}
      @if($s === 'constructor')
        <section id="constructor-paginas" x-show="cvTab==='paginas'" x-cloak class="constructor-panel constructor-panel--paginas">
          <div class="panel-heading">
            <div class="panel-heading-icon">📄</div>
            <div>
              <span>PASO 4 · PÁGINAS</span>
              <h3>Contenido y páginas</h3>
              <p>Administra Nosotros, Contacto, páginas personalizadas y mensajes recibidos.</p>
            </div>
          </div>
          <div class="mx-auto w-full space-y-6" style="max-width:1440px">
            <div><h2 class="text-xl font-bold text-slate-900">Páginas de la tienda</h2><p class="text-sm text-slate-500">Edita las páginas Nosotros y Contacto, y revisa los mensajes recibidos.</p></div>
            @include('tienda::settings.partials.institutional-pages')
            <div class="grid gap-5 xl:grid-cols-2">
              <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-slate-900">Mensajes de contacto</h2><p class="mt-1 text-sm text-slate-500">Mensajes enviados desde el formulario de Contacto.</p><div class="mt-3 space-y-2">@forelse($messages as $message)<div class="rounded border p-3 text-sm"><b>{{ $message->name }}</b> · {{ $message->subject }}<p class="mt-1 text-slate-600">{{ $message->message }}</p></div>@empty<p class="text-sm text-slate-500">No hay mensajes.</p>@endforelse</div></div>
              <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-slate-900">Libro de Reclamaciones</h2><p class="mt-1 text-sm text-slate-500">Gestiona el estado de cada reclamo.</p><div class="mt-3 space-y-2">@forelse($complaints as $complaint)<form method="post" action="{{ route('settings.experience.complaint.status',$complaint->id) }}" class="flex flex-wrap items-center gap-2 rounded border p-3 text-sm">@csrf @method('patch')<b>{{ $complaint->code }}</b><span>{{ $complaint->consumer_name }}</span><select name="status" class="rounded border-slate-300"><option value="received" @selected($complaint->status==='received')>Recibido</option><option value="in_review" @selected($complaint->status==='in_review')>En revisión</option><option value="resolved" @selected($complaint->status==='resolved')>Resuelto</option><option value="closed" @selected($complaint->status==='closed')>Cerrado</option></select><button class="text-indigo-600">Actualizar</button></form>@empty<p class="text-sm text-slate-500">No hay registros.</p>@endforelse</div></div>
            </div>
          </div>
        </section>
      @endif
      </div>
      @endif

    </div>
  </div>

</div>

<style>
/* Los formularios en grid no deben desbordar su columna. */
.designer-shell form .grid > * { min-width:0; }
/* ── Constructor: tabs por sección (íconos + área activa) ── */
.constructor-nav{gap:6px}
.cv-tab{display:inline-flex;align-items:center;gap:7px;min-height:42px;padding:9px 16px;border:1px solid transparent;border-radius:10px;background:transparent;color:#475569;font-size:13px;font-weight:700;white-space:nowrap;cursor:pointer;transition:all .12s}
.cv-tab:hover{background:#f1f5ff;color:#4338ca}
.cv-tab.is-active{background:#4f46e5;color:#fff;box-shadow:0 4px 12px rgba(79,70,229,.25)}
.cv-tab-ico{font-size:15px;line-height:1}
.cv-hint{width:min(1100px,100%);margin-inline:auto!important;order:1;padding:11px 18px;border:1px solid #e0e7ff;border-radius:12px;background:#f8faff;color:#475569;font-size:13px}
.cv-hint strong{color:#312e81}
.constructor-panel--navegacion{order:2}
#design-content{overscroll-behavior:contain;overflow-anchor:none}.constructor-stack{display:flex;flex-direction:column;gap:20px}.constructor-stack>*{margin-top:0!important}.constructor-intro{order:0;display:flex;align-items:center;justify-content:space-between;gap:24px;width:min(1100px,100%);margin-inline:auto!important;padding:24px 26px;border:1px solid #dfe4ff;border-radius:18px;background:linear-gradient(135deg,#fff,#f6f7ff);box-shadow:0 12px 34px rgba(15,23,42,.04)}.constructor-intro span{color:#4f46e5;font-size:11px;font-weight:800;letter-spacing:.1em}.constructor-intro h2{margin:4px 0 5px;color:#172033;font-size:26px;font-weight:800;letter-spacing:-.025em}.constructor-intro p{margin:0;color:#64748b;font-size:14px}.constructor-intro>a{display:inline-flex;min-height:42px;align-items:center;padding:10px 16px;border:1px solid #c7d2fe;border-radius:10px;background:#fff;color:#4338ca;font-size:13px;font-weight:750;text-decoration:none;white-space:nowrap}.constructor-nav{position:sticky;z-index:30;top:0;order:1;display:flex;width:min(1100px,100%);margin-inline:auto!important;padding:8px;overflow-x:auto;border:1px solid #e2e8f0;border-radius:13px;background:rgba(255,255,255,.96);box-shadow:0 8px 24px rgba(15,23,42,.06);backdrop-filter:blur(12px);scrollbar-width:none}.constructor-nav::-webkit-scrollbar{display:none}.constructor-nav a{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:8px 15px;border-radius:9px;color:#475569;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap}.constructor-nav a:hover,.constructor-nav a:focus-visible{background:#eef2ff;color:#4338ca;outline:none}.constructor-nav a.is-active,.hb-nav>a.is-active{background:#eef2ff;color:#4338ca}.constructor-panel{width:min(900px,100%);max-width:900px!important;scroll-margin-top:76px}.constructor-panel--inicio{order:2;width:100%;max-width:none!important}.constructor-panel--marca{order:3}.constructor-panel--portada{order:4}.constructor-panel--catalogo{order:5}.constructor-panel--checkout{order:6}.constructor-panel--sistema{order:7}
@media(max-width:720px){.constructor-intro{align-items:flex-start;flex-direction:column;padding:20px}.constructor-intro>a{width:100%;justify-content:center}.constructor-nav{border-radius:10px}.constructor-nav a{min-height:44px;padding-inline:14px}}
@media(prefers-reduced-motion:reduce){.constructor-nav a,.hb-switch span:after{transition:none!important}}

/* ═══════════════════════════════════════════════════════════
   CONSTRUCTOR VISUAL PRO — organización y jerarquía
   No modifica nombres de campos, rutas, Alpine ni formularios.
   ═══════════════════════════════════════════════════════════ */
#design-content{
  background:
    radial-gradient(circle at 15% 0%,rgba(79,70,229,.055),transparent 30%),
    linear-gradient(180deg,#f8fafc 0%,#f4f7fb 100%);
}
#design-content>.px-6{max-width:1480px;margin-inline:auto;padding-inline:22px}
.constructor-stack{
  display:grid!important;
  grid-template-columns:250px minmax(0,1fr)!important;
  align-items:start;
  gap:22px!important;
}
.constructor-intro{
  grid-column:1/-1;
  width:100%!important;
  max-width:none!important;
  padding:26px 30px!important;
  border-color:#dbe3ff!important;
  background:
    radial-gradient(circle at 92% 10%,rgba(99,102,241,.13),transparent 24%),
    linear-gradient(135deg,#fff 0%,#f7f8ff 100%)!important;
}
.constructor-intro-copy{min-width:0}
.constructor-status-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:15px}
.constructor-status{
  display:inline-flex;align-items:center;min-height:28px;padding:5px 9px;
  border:1px solid #dbeafe;border-radius:999px;background:#fff;
  color:#475569!important;font-size:10px!important;font-weight:750!important;letter-spacing:0!important
}
.constructor-nav{
  grid-column:1!important;
  grid-row:2 / span 20!important;
  position:sticky!important;
  top:18px!important;
  width:100%!important;
  max-width:none!important;
  margin:0!important;
  padding:10px!important;
  flex-direction:column!important;
  overflow:visible!important;
  border-radius:16px!important;
  box-shadow:0 14px 36px rgba(15,23,42,.07)!important;
}
.cv-tab{
  position:relative;
  width:100%;
  min-height:62px!important;
  justify-content:flex-start!important;
  gap:11px!important;
  padding:10px 11px!important;
  border:1px solid transparent!important;
  border-radius:12px!important;
  text-align:left;
  white-space:normal!important;
}
.cv-tab:hover{background:#f8faff!important;border-color:#e0e7ff!important}
.cv-tab.is-active{
  background:linear-gradient(135deg,#4f46e5,#6366f1)!important;
  border-color:transparent!important;
  box-shadow:0 9px 22px rgba(79,70,229,.24)!important;
}
.cv-tab-ico{
  width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;
  border-radius:10px;background:#eef2ff;font-size:17px!important
}
.cv-tab.is-active .cv-tab-ico{background:rgba(255,255,255,.16)}
.cv-tab-copy{display:flex;min-width:0;flex:1;flex-direction:column;gap:2px}
.cv-tab-copy strong{font-size:12px;line-height:1.2}
.cv-tab-copy small{
  overflow:hidden;color:#94a3b8;font-size:9.5px;font-weight:500;line-height:1.25;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical
}
.cv-tab.is-active .cv-tab-copy small{color:rgba(255,255,255,.76)}
.cv-tab-arrow{color:#cbd5e1;font-size:20px;font-weight:400}
.cv-tab.is-active .cv-tab-arrow{color:#fff}
.cv-hint{
  grid-column:2!important;
  width:100%!important;
  max-width:none!important;
  margin:0!important;
  order:unset!important;
  padding:12px 16px!important;
  border-radius:14px!important;
  background:#fff!important;
  box-shadow:0 8px 22px rgba(15,23,42,.04)
}
.constructor-panel{
  grid-column:2!important;
  width:100%!important;
  max-width:none!important;
  margin:0!important;
  order:unset!important;
}
.constructor-panel>form{max-width:none!important}
.panel-heading{
  display:flex;align-items:flex-start;gap:15px;margin-bottom:18px;padding:20px 22px;
  border:1px solid #e2e8f0;border-radius:16px;background:#fff;
  box-shadow:0 10px 28px rgba(15,23,42,.045)
}
.panel-heading-icon{
  width:46px;height:46px;display:grid;place-items:center;flex:0 0 46px;
  border-radius:13px;background:linear-gradient(135deg,#eef2ff,#f5f3ff);font-size:20px
}
.panel-heading span{color:#6366f1;font-size:10px;font-weight:850;letter-spacing:.09em}
.panel-heading h3{margin:3px 0 4px;color:#0f172a;font-size:20px;font-weight:800;letter-spacing:-.025em}
.panel-heading p{margin:0;color:#64748b;font-size:12px;line-height:1.55}
.constructor-panel form>div.bg-white,
.constructor-panel>div.bg-white,
.constructor-panel details,
.constructor-panel .rounded-xl.border{
  border-color:#e2e8f0!important;
  box-shadow:0 10px 26px rgba(15,23,42,.035)
}
.constructor-panel form>div.bg-white:hover,
.constructor-panel>div.bg-white:hover{
  border-color:#cbd5e1!important
}
.constructor-panel .bg-gray-50{
  background:linear-gradient(180deg,#fbfcfe,#f8fafc)!important
}
.constructor-panel .label{
  color:#334155!important;font-size:11px!important;font-weight:750!important
}
.constructor-panel .input,
.constructor-panel input[type="text"],
.constructor-panel input[type="number"],
.constructor-panel input[type="url"],
.constructor-panel input[type="email"],
.constructor-panel textarea,
.constructor-panel select{
  min-height:42px;border-color:#d7dfeb!important;border-radius:10px!important;background:#fff
}
.constructor-panel textarea{min-height:96px}
.constructor-panel .input:focus,
.constructor-panel input:focus,
.constructor-panel textarea:focus,
.constructor-panel select:focus{
  border-color:#818cf8!important;box-shadow:0 0 0 3px rgba(99,102,241,.10)!important;outline:none
}
.constructor-savebar{
  position:sticky;z-index:20;bottom:10px;display:flex;align-items:center;justify-content:space-between;
  gap:16px;margin-top:22px;padding:12px 14px 12px 18px;border:1px solid #dbe3ef;
  border-radius:14px;background:rgba(255,255,255,.94);box-shadow:0 14px 34px rgba(15,23,42,.12);
  backdrop-filter:blur(12px)
}
.constructor-savebar>div:first-child{display:flex;min-width:0;flex-direction:column}
.constructor-savebar strong{color:#0f172a;font-size:12px}
.constructor-savebar span{color:#64748b;font-size:10px}
.constructor-savebar .btn-primary{min-height:42px;padding-inline:18px;white-space:nowrap}
@media(max-width:1050px){
  .constructor-stack{grid-template-columns:210px minmax(0,1fr)!important}
  .cv-tab-copy small{display:none}
  .cv-tab{min-height:52px!important}
}
@media(max-width:820px){
  #design-content>.px-6{padding-inline:12px}
  .constructor-stack{display:flex!important;flex-direction:column!important}
  .constructor-intro{padding:20px!important}
  .constructor-nav{
    position:sticky!important;top:0!important;display:flex!important;flex-direction:row!important;
    width:100%!important;padding:7px!important;overflow-x:auto!important;border-radius:13px!important
  }
  .cv-tab{width:auto;min-width:max-content;min-height:46px!important;padding:7px 11px!important}
  .cv-tab-ico{width:30px;height:30px;flex-basis:30px}
  .cv-tab-copy small,.cv-tab-arrow{display:none}
  .cv-hint,.constructor-panel{width:100%!important}
  .panel-heading{padding:16px}
  .constructor-savebar{bottom:8px}
  .constructor-savebar>div:first-child{display:none}
}
@media(max-width:540px){
  .constructor-intro>a{width:100%;justify-content:center}
  .constructor-status-row{display:none}
  .panel-heading-icon{width:40px;height:40px;flex-basis:40px}
  .panel-heading h3{font-size:17px}
  .constructor-savebar{padding:10px}
  .constructor-savebar .btn-primary{width:100%}
}


/* ═══════════════════════════════════════════════════════════
   UX SIMPLE — flujo guiado sin duplicar opciones
   ═══════════════════════════════════════════════════════════ */
.constructor-intro{
  padding:24px 28px!important;
}
.constructor-intro p{max-width:650px}
.constructor-status-row{display:none!important}

.constructor-nav-title{
  margin:4px 5px 9px;
  color:#94a3b8;
  font-size:9px;
  font-weight:850;
  letter-spacing:.09em;
}
.ux-step{
  display:flex;
  width:100%;
  min-height:66px;
  align-items:center;
  gap:11px;
  padding:10px;
  border:1px solid transparent;
  border-radius:12px;
  background:transparent;
  text-align:left;
  transition:.18s ease;
}
.ux-step:hover{
  border-color:#e0e7ff;
  background:#f8faff;
}
.ux-step.is-active{
  border-color:#c7d2fe;
  background:linear-gradient(135deg,#eef2ff,#f5f3ff);
  box-shadow:0 8px 20px rgba(79,70,229,.08);
}
.ux-step-number{
  width:34px;
  height:34px;
  display:grid;
  place-items:center;
  flex:0 0 34px;
  border-radius:10px;
  background:#eef2ff;
  color:#4f46e5;
  font-size:12px;
  font-weight:850;
}
.ux-step.is-active .ux-step-number{
  background:#4f46e5;
  color:#fff;
}
.ux-step-copy{
  display:flex;
  min-width:0;
  flex:1;
  flex-direction:column;
  gap:2px;
}
.ux-step-copy strong{
  color:#0f172a;
  font-size:12px;
  line-height:1.25;
}
.ux-step-copy small{
  color:#94a3b8;
  font-size:9.5px;
  line-height:1.3;
}
.ux-step-check{
  color:#cbd5e1;
  font-size:20px;
}
.ux-step.is-active .ux-step-check{color:#4f46e5}

.constructor-nav-help{
  display:flex;
  flex-direction:column;
  gap:4px;
  margin-top:10px;
  padding:12px;
  border:1px solid #e2e8f0;
  border-radius:11px;
  background:#f8fafc;
}
.constructor-nav-help strong{
  color:#475569;
  font-size:10px;
}
.constructor-nav-help span{
  color:#94a3b8;
  font-size:9.5px;
  line-height:1.45;
}

.constructor-subnav{
  grid-column:2!important;
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:10px;
  width:100%;
  padding:7px;
  border:1px solid #e2e8f0;
  border-radius:14px;
  background:#fff;
  box-shadow:0 8px 24px rgba(15,23,42,.04);
}
.constructor-subnav button{
  position:relative;
  display:flex;
  min-height:62px;
  align-items:center;
  gap:11px;
  padding:10px 12px;
  border:1px solid transparent;
  border-radius:10px;
  background:transparent;
  text-align:left;
  transition:.18s ease;
}
.constructor-subnav button:hover{
  border-color:#e0e7ff;
  background:#f8faff;
}
.constructor-subnav button.is-active{
  border-color:#c7d2fe;
  background:#eef2ff;
}
.constructor-subnav button>span{
  width:30px;
  height:30px;
  display:grid;
  place-items:center;
  flex:0 0 30px;
  border-radius:9px;
  background:#fff;
  color:#4f46e5;
  font-size:11px;
  font-weight:850;
  box-shadow:0 2px 8px rgba(15,23,42,.06);
}
.constructor-subnav button>div{
  display:flex;
  min-width:0;
  flex:1;
  flex-direction:column;
  gap:2px;
}
.constructor-subnav button strong{
  color:#0f172a;
  font-size:11px;
}
.constructor-subnav button small{
  color:#64748b;
  font-size:9.5px;
  line-height:1.35;
}
.constructor-subnav button em{
  padding:4px 7px;
  border-radius:999px;
  background:#fff7ed;
  color:#c2410c;
  font-size:8px;
  font-style:normal;
  font-weight:800;
  text-transform:uppercase;
}

/* Se elimina la tercera repetición del nombre del área. */
.cv-hint{display:none!important}

/* Todos los bloques usan la misma cabecera visual. */
.constructor-panel form>div.bg-white>div:first-child,
.constructor-panel>div.bg-white>div:first-child{
  min-height:58px;
}
.constructor-panel form>div.bg-white>div:first-child p.text-sm,
.constructor-panel>div.bg-white>div:first-child p.text-sm{
  color:#0f172a!important;
  font-size:12px!important;
  font-weight:780!important;
}
.constructor-panel form>div.bg-white>div:first-child p.text-xs,
.constructor-panel>div.bg-white>div:first-child p.text-xs{
  color:#64748b!important;
  font-size:10px!important;
  line-height:1.4;
}

/* Avanzado se percibe menos dominante. */
.constructor-panel--sistema details,
.constructor-panel--sistema .bg-white{
  scroll-margin-top:90px;
}

/* Menos complejidad visual en controles secundarios. */
.constructor-panel .text-xs.text-gray-400{
  line-height:1.45;
}
.constructor-panel .rounded-xl.border{
  border-radius:14px!important;
}
.constructor-panel .p-4.space-y-4,
.constructor-panel .p-4.space-y-5{
  padding:18px!important;
}

@media(max-width:820px){
  .constructor-nav-title,.constructor-nav-help{display:none}
  .constructor-nav{
    gap:5px!important;
  }
  .ux-step{
    width:auto;
    min-width:max-content;
    min-height:44px;
    padding:6px 9px;
  }
  .ux-step-number{
    width:28px;
    height:28px;
    flex-basis:28px;
  }
  .ux-step-copy small,.ux-step-check{display:none}
  .constructor-subnav{
    grid-template-columns:1fr 1fr;
  }
}
@media(max-width:560px){
  .constructor-subnav{
    grid-template-columns:1fr;
  }
  .constructor-subnav button{
    min-height:54px;
  }
}


/* ═══════════════════════════════════════════════════════════
   UX DETALLADO — afinado visual del constructor
   ═══════════════════════════════════════════════════════════ */
#design-content>.px-6{max-width:1420px!important}
.constructor-stack{
  grid-template-columns:280px minmax(0, 1fr)!important;
  gap:28px!important;
}
.constructor-nav{
  padding:14px!important;
  border-radius:18px!important;
}
.ux-step{
  min-height:72px;
  padding:12px 12px;
}
.ux-step-copy strong{font-size:13px}
.ux-step-copy small{font-size:10px}
.constructor-panel,
.constructor-subnav,
.constructor-intro,
.cv-hint{
  max-width:1040px;
}
.constructor-panel > *:not(script){
  width:100%;
}
.panel-heading{
  padding:22px 24px!important;
  margin-bottom:16px!important;
}
.panel-heading h3{font-size:22px!important}
.panel-heading p{max-width:760px;font-size:12.5px!important}
.constructor-subnav{
  align-items:stretch;
  margin-top:-2px;
}
.constructor-subnav button{
  min-height:68px;
  align-items:flex-start;
}
.constructor-subnav button strong{font-size:11.5px}
.constructor-subnav button small{font-size:10px}
.ux-guide-card{
  width:100%;
  margin:0 0 18px 0;
  padding:16px 18px;
  border:1px solid #dbeafe;
  border-radius:16px;
  background:linear-gradient(180deg,#ffffff 0%,#f8fbff 100%);
  box-shadow:0 8px 22px rgba(15,23,42,.04);
}
.ux-guide-card__head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:12px;
}
.ux-guide-card__head strong{
  color:#0f172a;
  font-size:12px;
  font-weight:800;
}
.ux-guide-card__head span{
  display:inline-flex;
  align-items:center;
  min-height:24px;
  padding:4px 9px;
  border-radius:999px;
  background:#eef2ff;
  color:#4f46e5;
  font-size:9px;
  font-weight:800;
  letter-spacing:.05em;
  text-transform:uppercase;
}
.ux-guide-steps{
  display:grid;
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:10px;
}
.ux-guide-step{
  display:flex;
  align-items:flex-start;
  gap:10px;
  padding:12px;
  border:1px solid #e2e8f0;
  border-radius:12px;
  background:#fff;
}
.ux-guide-step>span{
  width:28px;
  height:28px;
  display:grid;
  place-items:center;
  flex:0 0 28px;
  border-radius:8px;
  background:#4f46e5;
  color:#fff;
  font-size:11px;
  font-weight:800;
}
.ux-guide-step strong{
  display:block;
  color:#0f172a;
  font-size:11px;
  line-height:1.35;
}
.ux-guide-step small{
  display:block;
  margin-top:3px;
  color:#64748b;
  font-size:9.8px;
  line-height:1.45;
}
.ux-guide-card--compact .ux-guide-card__head{margin-bottom:10px}
.ux-guide-tags{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}
.ux-guide-tags span{
  display:inline-flex;
  align-items:center;
  min-height:30px;
  padding:6px 10px;
  border:1px solid #e2e8f0;
  border-radius:999px;
  background:#fff;
  color:#334155;
  font-size:10px;
  font-weight:700;
}
.ux-guide-card--warning{
  border-color:#fed7aa;
  background:linear-gradient(180deg,#fff 0%,#fffaf5 100%);
}
.ux-guide-card--warning .ux-guide-card__head span{
  background:#fff7ed;
  color:#c2410c;
}
.constructor-panel form>div.bg-white,
.constructor-panel>div.bg-white,
.constructor-panel details,
.constructor-panel .rounded-xl.border{
  border-radius:16px!important;
}
.constructor-panel .bg-blue-50,
.constructor-panel .bg-fuchsia-50,
.constructor-panel .bg-emerald-50,
.constructor-panel .bg-slate-50{
  border-radius:14px!important;
}
.constructor-savebar{
  width:100%;
  max-width:1040px;
}
@media(max-width:1180px){
  .constructor-stack{grid-template-columns:250px minmax(0,1fr)!important}
  .ux-guide-steps{grid-template-columns:1fr}
}
@media(max-width:820px){
  .constructor-stack{gap:16px!important}
  .constructor-panel,
  .constructor-subnav,
  .constructor-intro,
  .constructor-savebar{
    max-width:none;
  }
  .panel-heading{
    padding:18px!important;
  }
  .panel-heading h3{font-size:18px!important}
  .ux-guide-card{padding:14px}
  .ux-guide-step{padding:10px}
}


/* ═══════════════════════════════════════════════════════════
   INICIO UX PRO
   Capa visual sobre settings.store-experience.
   No modifica endpoints, inputs, formularios ni autoguardado.
   ═══════════════════════════════════════════════════════════ */

#constructor-inicio{
  --hb-ink:#0f172a;
  --hb-text:#334155;
  --hb-muted:#64748b;
  --hb-soft:#f8fafc;
  --hb-line:#e2e8f0;
  --hb-primary:#4f46e5;
  --hb-primary-soft:#eef2ff;
  --hb-success:#15803d;
  --hb-success-soft:#f0fdf4;
  --hb-warning:#c2410c;
  --hb-warning-soft:#fff7ed;
  --hb-hidden:#64748b;
}

.home-builder-ux{
  width:100%;
  min-width:0;
}

/* El constructor interno deja de competir con la navegación principal. */
.home-builder-ux .hb-builder-layout{
  display:block!important;
  width:100%!important;
  max-width:none!important;
}

/* Encabezado propio del partial. */
.home-builder-ux .hb-native-heading{
  margin-bottom:16px!important;
  padding:20px 22px!important;
  border:1px solid var(--hb-line)!important;
  border-radius:16px!important;
  background:#fff!important;
  box-shadow:0 8px 22px rgba(15,23,42,.04)!important;
}
.home-builder-ux .hb-native-heading h1,
.home-builder-ux .hb-native-heading h2{
  margin:0!important;
  color:var(--hb-ink)!important;
  font-size:22px!important;
  font-weight:850!important;
  letter-spacing:-.035em!important;
}
.home-builder-ux .hb-native-heading p{
  margin:7px 0 0!important;
  max-width:720px!important;
  color:var(--hb-muted)!important;
  font-size:12px!important;
  line-height:1.55!important;
}

/* Barra de control principal. */
.hb-controlbar{
  position:sticky;
  top:12px;
  z-index:35;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  width:100%;
  margin-bottom:16px;
  padding:12px;
  border:1px solid #dbe3ef;
  border-radius:16px;
  background:rgba(255,255,255,.96);
  box-shadow:0 14px 34px rgba(15,23,42,.10);
  backdrop-filter:blur(14px);
}
.hb-controlbar__left,
.hb-controlbar__right{
  display:flex;
  align-items:center;
  gap:8px;
  flex-wrap:wrap;
}
.hb-controlbar__title{
  display:flex;
  min-width:170px;
  flex-direction:column;
  gap:2px;
  padding:0 8px 0 4px;
}
.hb-controlbar__title strong{
  color:var(--hb-ink);
  font-size:12px;
  font-weight:850;
}
.hb-controlbar__title small{
  color:var(--hb-muted);
  font-size:9.5px;
}
.hb-search{
  position:relative;
  min-width:230px;
}
.hb-search input{
  width:100%;
  min-height:40px!important;
  padding:0 36px 0 12px!important;
  border:1px solid #d7dfeb!important;
  border-radius:11px!important;
  background:#fff!important;
  color:var(--hb-text)!important;
  font-size:11px!important;
}
.hb-search svg{
  position:absolute;
  top:11px;
  right:11px;
  width:17px;
  height:17px;
  color:#94a3b8;
  pointer-events:none;
}
.hb-filter,
.hb-control-button{
  min-height:38px;
  padding:0 11px;
  border:1px solid #dbe3ef;
  border-radius:10px;
  background:#fff;
  color:#475569;
  font-size:10px;
  font-weight:750;
  cursor:pointer;
  transition:.16s ease;
}
.hb-filter:hover,
.hb-control-button:hover{
  border-color:#c7d2fe;
  background:#f8faff;
  color:#4338ca;
}
.hb-filter.is-active{
  border-color:var(--hb-primary);
  background:var(--hb-primary);
  color:#fff;
}
.hb-count{
  display:inline-grid;
  min-width:20px;
  height:20px;
  place-items:center;
  margin-left:4px;
  padding:0 5px;
  border-radius:999px;
  background:rgba(148,163,184,.16);
  font-size:9px;
}
.hb-filter.is-active .hb-count{
  background:rgba(255,255,255,.18);
}

/* Orden de secciones: pasa de sidebar estrecho a resumen horizontal. */
.home-builder-ux .hb-order-panel{
  position:relative!important;
  top:auto!important;
  width:100%!important;
  max-width:none!important;
  margin:0 0 18px!important;
  padding:16px!important;
  border:1px solid var(--hb-line)!important;
  border-radius:16px!important;
  background:#fff!important;
  box-shadow:0 10px 28px rgba(15,23,42,.045)!important;
}
.home-builder-ux .hb-order-panel::before{
  content:"Orden de aparición";
  display:block;
  margin-bottom:3px;
  color:var(--hb-ink);
  font-size:12px;
  font-weight:850;
}
.home-builder-ux .hb-order-panel::after{
  content:"Arrastra o ajusta el orden. Los bloques en borrador no se muestran en la tienda.";
  display:block;
  margin-bottom:13px;
  color:var(--hb-muted);
  font-size:9.8px;
  line-height:1.45;
}
.home-builder-ux .hb-order-panel [data-hb-order-list]{
  display:grid!important;
  grid-template-columns:repeat(4,minmax(0,1fr))!important;
  gap:8px!important;
}
.home-builder-ux .hb-order-item{
  display:flex!important;
  min-height:54px!important;
  align-items:center!important;
  gap:9px!important;
  padding:9px 10px!important;
  border:1px solid #e2e8f0!important;
  border-radius:11px!important;
  background:#f8fafc!important;
  transition:.16s ease!important;
}
.home-builder-ux .hb-order-item:hover{
  border-color:#c7d2fe!important;
  background:#f8faff!important;
}
.home-builder-ux .hb-order-item__number{
  display:grid;
  width:28px;
  height:28px;
  flex:0 0 28px;
  place-items:center;
  border-radius:8px;
  background:#eef2ff;
  color:#4f46e5;
  font-size:10px;
  font-weight:850;
}
.home-builder-ux .hb-order-item__copy{
  min-width:0;
  flex:1;
}
.home-builder-ux .hb-order-item__copy strong{
  display:block;
  overflow:hidden;
  color:#1e293b;
  font-size:10.5px;
  font-weight:750;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.home-builder-ux .hb-order-item__copy small{
  display:block;
  margin-top:2px;
  color:#94a3b8;
  font-size:8.5px;
}
.home-builder-ux .hb-order-panel button[type="submit"]{
  width:auto!important;
  min-height:40px!important;
  margin-top:12px!important;
  padding:0 16px!important;
  border-radius:10px!important;
}

/* Cards principales de cada sección. */
.home-builder-ux .hb-section-card{
  width:100%!important;
  max-width:none!important;
  margin:0 0 14px!important;
  overflow:hidden!important;
  border:1px solid var(--hb-line)!important;
  border-radius:17px!important;
  background:#fff!important;
  box-shadow:0 9px 26px rgba(15,23,42,.045)!important;
  transition:border-color .18s ease,box-shadow .18s ease!important;
}
.home-builder-ux .hb-section-card:hover{
  border-color:#cbd5e1!important;
}
.home-builder-ux .hb-section-card.is-open{
  border-color:#c7d2fe!important;
  box-shadow:0 14px 34px rgba(79,70,229,.08)!important;
}
.home-builder-ux .hb-section-card[hidden]{
  display:none!important;
}

/* Resumen compacto y entendible. */
.hb-section-summary{
  display:grid;
  grid-template-columns:auto minmax(0,1fr) auto;
  align-items:center;
  gap:12px;
  min-height:78px;
  padding:13px 15px;
  background:linear-gradient(180deg,#fff,#fbfcff);
  cursor:pointer;
  user-select:none;
}
.hb-section-card.is-open .hb-section-summary{
  border-bottom:1px solid #e8ecf4;
  background:linear-gradient(135deg,#f8faff,#fff);
}
.hb-section-index{
  display:grid;
  width:38px;
  height:38px;
  place-items:center;
  border-radius:11px;
  background:#eef2ff;
  color:#4f46e5;
  font-size:11px;
  font-weight:900;
}
.hb-section-card.is-open .hb-section-index{
  background:#4f46e5;
  color:#fff;
}
.hb-section-info{
  min-width:0;
}
.hb-section-info strong{
  display:block;
  overflow:hidden;
  color:var(--hb-ink);
  font-size:13px;
  font-weight:850;
  letter-spacing:-.015em;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.hb-section-meta{
  display:flex;
  align-items:center;
  gap:7px;
  flex-wrap:wrap;
  margin-top:5px;
}
.hb-chip{
  display:inline-flex;
  align-items:center;
  min-height:24px;
  padding:3px 8px;
  border-radius:999px;
  font-size:8.5px;
  font-weight:800;
  letter-spacing:.025em;
}
.hb-chip--published{
  color:var(--hb-success);
  background:var(--hb-success-soft);
}
.hb-chip--draft{
  color:var(--hb-warning);
  background:var(--hb-warning-soft);
}
.hb-chip--hidden{
  color:#475569;
  background:#f1f5f9;
}
.hb-chip--visible{
  color:#1d4ed8;
  background:#eff6ff;
}
.hb-chip--order{
  color:#64748b;
  background:#f8fafc;
  border:1px solid #e2e8f0;
}
.hb-section-action{
  display:flex;
  align-items:center;
  gap:8px;
}
.hb-section-toggle{
  display:inline-flex;
  min-height:38px;
  align-items:center;
  gap:7px;
  padding:0 11px;
  border:1px solid #dbe3ef;
  border-radius:10px;
  background:#fff;
  color:#475569;
  font-size:10px;
  font-weight:800;
  cursor:pointer;
}
.hb-section-toggle:hover{
  border-color:#c7d2fe;
  color:#4338ca;
  background:#f8faff;
}
.hb-section-toggle svg{
  width:15px;
  height:15px;
  transition:transform .18s ease;
}
.hb-section-card.is-open .hb-section-toggle svg{
  transform:rotate(180deg);
}
.hb-section-content{
  display:none;
  padding:18px!important;
  background:#fff;
}
.hb-section-card.is-open>.hb-section-content{
  display:block;
}

/* Jerarquía interna uniforme. */
.home-builder-ux .hb-section-content label{
  color:#334155!important;
  font-size:10.5px!important;
  font-weight:750!important;
}
.home-builder-ux .hb-section-content input[type="text"],
.home-builder-ux .hb-section-content input[type="number"],
.home-builder-ux .hb-section-content input[type="url"],
.home-builder-ux .hb-section-content input[type="datetime-local"],
.home-builder-ux .hb-section-content input[type="date"],
.home-builder-ux .hb-section-content select,
.home-builder-ux .hb-section-content textarea{
  min-height:42px!important;
  border-color:#d7dfeb!important;
  border-radius:10px!important;
  background:#fff!important;
}
.home-builder-ux .hb-section-content textarea{
  min-height:88px!important;
}
.home-builder-ux .hb-section-content input:focus,
.home-builder-ux .hb-section-content select:focus,
.home-builder-ux .hb-section-content textarea:focus{
  border-color:#818cf8!important;
  box-shadow:0 0 0 3px rgba(99,102,241,.10)!important;
  outline:none!important;
}

/* Listas extensas de productos: visibles, pero acotadas y buscables con scroll. */
.home-builder-ux .hb-long-selector{
  position:relative;
  max-height:330px!important;
  overflow:auto!important;
  padding:10px!important;
  border:1px solid #e2e8f0!important;
  border-radius:12px!important;
  background:#f8fafc!important;
  scrollbar-width:thin;
}
.home-builder-ux .hb-long-selector::before{
  content:"Lista de productos";
  position:sticky;
  top:-10px;
  z-index:2;
  display:block;
  margin:-10px -10px 9px;
  padding:9px 10px;
  border-bottom:1px solid #e2e8f0;
  background:rgba(248,250,252,.96);
  color:#64748b;
  font-size:9px;
  font-weight:800;
  letter-spacing:.06em;
  text-transform:uppercase;
  backdrop-filter:blur(8px);
}
.home-builder-ux .hb-long-selector label{
  min-height:34px!important;
  padding:6px 8px!important;
  border-radius:8px!important;
}
.home-builder-ux .hb-long-selector label:hover{
  background:#fff!important;
}

/* Footer de acciones de cada bloque. */
.home-builder-ux .hb-section-content button[type="submit"],
.home-builder-ux .hb-section-content .btn-primary{
  min-height:40px!important;
  border-radius:10px!important;
}
.home-builder-ux .hb-section-content button{
  transition:.16s ease;
}

/* Panel de pop-up como sección aparte y colapsable. */
.home-builder-ux .hb-popup-card{
  margin-top:18px!important;
  border:1px solid #fde68a!important;
  border-radius:17px!important;
  background:#fff!important;
  box-shadow:0 9px 26px rgba(15,23,42,.04)!important;
}
.home-builder-ux .hb-popup-card .hb-section-index{
  color:#b45309;
  background:#fef3c7;
}
.home-builder-ux .hb-popup-card.is-open .hb-section-index{
  color:#fff;
  background:#d97706;
}

/* Estado vacío de filtros. */
.hb-empty-state{
  display:none;
  padding:30px 18px;
  border:1px dashed #cbd5e1;
  border-radius:15px;
  background:#fff;
  text-align:center;
}
.hb-empty-state.is-visible{
  display:block;
}
.hb-empty-state strong{
  display:block;
  color:#334155;
  font-size:12px;
}
.hb-empty-state span{
  display:block;
  margin-top:4px;
  color:#94a3b8;
  font-size:10px;
}

@media(max-width:1180px){
  .home-builder-ux .hb-order-panel [data-hb-order-list]{
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
  }
  .hb-controlbar{
    align-items:flex-start;
    flex-direction:column;
  }
  .hb-controlbar__left,
  .hb-controlbar__right{
    width:100%;
  }
  .hb-search{
    min-width:0;
    flex:1;
  }
}

@media(max-width:760px){
  .hb-controlbar{
    position:relative;
    top:auto;
  }
  .hb-controlbar__title{
    width:100%;
  }
  .hb-controlbar__right{
    overflow-x:auto;
    flex-wrap:nowrap;
    padding-bottom:3px;
  }
  .hb-filter,
  .hb-control-button{
    flex:0 0 auto;
  }
  .home-builder-ux .hb-order-panel [data-hb-order-list]{
    grid-template-columns:1fr!important;
  }
  .hb-section-summary{
    grid-template-columns:auto minmax(0,1fr);
  }
  .hb-section-action{
    grid-column:1/-1;
    justify-content:flex-end;
  }
  .hb-section-toggle{
    width:100%;
    justify-content:center;
  }
  .hb-section-content{
    padding:14px!important;
  }
}

</style>

<script>
// Al guardar un formulario, vuelve a la MISMA pestaña y posición (no al inicio).
(function () {
    try {
        var raw = sessionStorage.getItem('dzRestore');
        if (raw) {
            var d = JSON.parse(raw);
            sessionStorage.removeItem('dzRestore');
            if (Date.now() - d.t < 90000) {
                if (d.cv) {
                    var u = new URL(location.href);
                    u.searchParams.set('cv', d.cv);
                    history.replaceState(null, '', u); // Alpine lee cvTab de aquí
                }
                window.__dzRestore = d;
            }
        }
    } catch (e) {}

    document.addEventListener('submit', function (e) {
        try {
            var scroller = document.querySelector('[data-design-scroll-container]');
            var form = e.target;
            if (!scroller || !form || !scroller.contains(form)) return;
            var anchor = form.closest('[id]');
            sessionStorage.setItem('dzRestore', JSON.stringify({
                t: Date.now(),
                cv: new URLSearchParams(location.search).get('cv') || '',
                y: scroller.scrollTop,
                a: anchor ? anchor.id : ''
            }));
        } catch (err) {}
    }, true);

    window.addEventListener('load', function () {
        var d = window.__dzRestore;
        if (!d) return;
        var scroller = document.querySelector('[data-design-scroll-container]');
        if (!scroller) return;
        requestAnimationFrame(function () {
            var el = d.a ? document.getElementById(d.a) : null;
            if (el) el.scrollIntoView({ block: 'start' });
            else scroller.scrollTop = d.y || 0;
        });
    });
})();

document.addEventListener('DOMContentLoaded', function() {
    var scroller = document.querySelector('[data-design-scroll-container]');
    if (!scroller) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var normalizeHash = function(value) {
        try { return decodeURIComponent((value || '').replace(/^#/, '')); } catch (_) { return ''; }
    };
    var resetOuterScroll = function() {
        var parent = scroller.parentElement;
        while (parent) {
            if (parent !== scroller) parent.scrollTop = 0;
            parent = parent.parentElement;
        }
        window.scrollTo(0, 0);
    };
    var setActiveLink = function(id) {
        document.querySelectorAll('.constructor-nav a,.hb-nav>a').forEach(function(link) {
            link.classList.toggle('is-active', normalizeHash(link.getAttribute('href')) === id);
        });
    };
    var scrollToTarget = function(target, smooth) {
        if (!target || !scroller.contains(target)) return;
        // No hacer scroll a paneles ocultos (tabs Alpine con display:none):
        // hacerlo dejaría la vista en una zona vacía ("página en blanco").
        if (target.offsetParent === null && target !== document.body) return;
        if (target.tagName === 'DETAILS') target.open = true;
        // Scroll SUAVE dentro del contenedor, SIN resetear los scrolls externos
        // (el reset agresivo era lo que causaba el "salto" al navegar/activar).
        requestAnimationFrame(function() {
            var scrollerRect = scroller.getBoundingClientRect();
            var targetRect = target.getBoundingClientRect();
            var stickyOffset = target.id.indexOf('home-section-') === 0 ? 68 : 12;
            var top = scroller.scrollTop + targetRect.top - scrollerRect.top - stickyOffset;
            scroller.scrollTo({top: Math.max(0, top), behavior: smooth && !reduceMotion ? 'smooth' : 'auto'});
            setActiveLink(target.id);
        });
    };

    // Navegación por anclas del menú lateral (SECCIONES del home-builder).
    // Solo actúa sobre clics en enlaces <a href="#...">, nunca sobre switches/inputs,
    // y solo si el destino está visible (evita saltos a zonas en blanco).
    scroller.addEventListener('click', function(event) {
        var link = event.target.closest('a[href^="#"]');
        if (!link || !scroller.contains(link)) return;
        var id = normalizeHash(link.getAttribute('href'));
        var target = id ? document.getElementById(id) : null;
        if (!target || !scroller.contains(target)) return;
        if (target.offsetParent === null) return; // destino oculto → no scrollear
        event.preventDefault();
        scrollToTarget(target, true);
    });

    // ─── AUTOGUARDADO ───────────────────────────────────────────────
    // Cada tarjeta del constructor guarda sola al cambiar un campo,
    // sin recargar la página ni bajar hasta el botón "Guardar".
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    // Indicador flotante "Guardado ✓"
    var saveToast = document.createElement('div');
    saveToast.style.cssText = 'position:fixed;bottom:22px;right:22px;z-index:9999;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:12px;background:#0f172a;color:#fff;font-size:13px;font-weight:700;box-shadow:0 10px 30px rgba(15,23,42,.25);opacity:0;transform:translateY(10px);transition:all .25s;pointer-events:none';
    document.body.appendChild(saveToast);
    var toastTimer = null;
    var showToast = function(text, ok) {
        saveToast.innerHTML = (ok === false ? '⚠️ ' : '✓ ') + text;
        saveToast.style.background = ok === false ? '#b91c1c' : '#0f172a';
        saveToast.style.opacity = '1';
        saveToast.style.transform = 'translateY(0)';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function(){ saveToast.style.opacity = '0'; saveToast.style.transform = 'translateY(10px)'; }, 1800);
    };

    // Solo autoguardan los forms del constructor que apuntan al endpoint de diseño
    var autosaveForms = scroller.querySelectorAll('form[action*="design"][action*="update"], form#marca-form');
    var debounce = {};
    autosaveForms.forEach(function(form) {
        var send = function() {
            var data = new FormData(form);
            showToast('Guardando…');
            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: data
            }).then(function(r){ return r.ok ? r.json().catch(function(){return {ok:true};}) : Promise.reject(); })
              .then(function(){ showToast('Guardado'); })
              .catch(function(){ showToast('No se pudo guardar', false); });
        };
        var trigger = function() {
            clearTimeout(debounce[form.id || 'f']);
            debounce[form.id || 'f'] = setTimeout(send, 500); // espera 0.5s tras el último cambio
        };
        // Cambios en select/checkbox/radio/color/file → guardar al instante
        form.addEventListener('change', trigger);
        // Texto → guardar 0.5s después de dejar de escribir
        form.addEventListener('input', function(e){
            if (e.target.matches('input[type=text],input[type=number],input[type=url],input[type=email],textarea')) trigger();
        });
    });

    // Sin scroll automático al cargar: los tabs Alpine ya controlan qué panel se ve.
    // (Forzar scrollTop/resetOuterScroll aquí causaba el "salto a blanco".)
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('home-builder-ux');
  if (!root || root.dataset.uxEnhanced === '1') return;
  root.dataset.uxEnhanced = '1';

  var normalize = function (value) {
    return String(value || '')
      .replace(/\s+/g, ' ')
      .trim()
      .toLowerCase();
  };

  var knownSections = [
    { key:'hero', title:'Banner principal', icon:'01' },
    { key:'benefits', title:'Beneficios de la tienda', icon:'02' },
    { key:'announcements', title:'Bloque de anuncios', icon:'03' },
    { key:'categories', title:'Categorías principales', icon:'04' },
    { key:'flash', title:'Solo por hoy', icon:'05' },
    { key:'discounts', title:'Productos con descuento', icon:'06' },
    { key:'featured', title:'Productos destacados', icon:'07' },
    { key:'blog', title:'Blog informativo', icon:'08' }
  ];

  var findKnownSection = function (text) {
    var clean = normalize(text);
    return knownSections.find(function (item) {
      return clean.indexOf(normalize(item.title)) !== -1;
    }) || null;
  };

  /* Localizar el layout interno y el panel de orden. */
  var orderButton = Array.from(root.querySelectorAll('button,input[type="submit"]')).find(function (el) {
    return normalize(el.value || el.textContent) === 'guardar orden';
  });

  var orderPanel = null;
  if (orderButton) {
    var current = orderButton.parentElement;
    while (current && current !== root) {
      var content = normalize(current.textContent);
      if (content.indexOf('secciones') !== -1 && content.indexOf('guardar orden') !== -1) {
        orderPanel = current;
        break;
      }
      current = current.parentElement;
    }
  }

  if (orderPanel) {
    orderPanel.classList.add('hb-order-panel');
    if (orderPanel.parentElement && orderPanel.parentElement !== root) {
      orderPanel.parentElement.classList.add('hb-builder-layout');
    }

    /* Detectar elementos del orden sin cambiar inputs ni botones. */
    var candidates = Array.from(orderPanel.querySelectorAll('a,button,li,div')).filter(function (el) {
      var match = findKnownSection(el.textContent);
      if (!match) return false;
      if (el.children.length > 8) return false;
      var parentMatch = el.parentElement ? findKnownSection(el.parentElement.textContent) : null;
      return !parentMatch || normalize(el.textContent).length < normalize(el.parentElement.textContent).length;
    });

    var uniqueOrderItems = [];
    candidates.forEach(function (el) {
      if (uniqueOrderItems.some(function (used) { return used.contains(el) || el.contains(used); })) return;
      uniqueOrderItems.push(el);
    });

    var listContainer = uniqueOrderItems.length ? uniqueOrderItems[0].parentElement : null;
    if (listContainer) listContainer.setAttribute('data-hb-order-list', '1');

    uniqueOrderItems.forEach(function (el) {
      var info = findKnownSection(el.textContent);
      if (!info) return;
      el.classList.add('hb-order-item');

      if (!el.querySelector('.hb-order-item__number')) {
        var number = document.createElement('span');
        number.className = 'hb-order-item__number';
        number.textContent = info.icon.replace(/^0/, '');

        var copy = document.createElement('span');
        copy.className = 'hb-order-item__copy';

        var title = document.createElement('strong');
        title.textContent = info.title;

        var status = document.createElement('small');
        var raw = normalize(el.textContent);
        status.textContent = raw.indexOf('borrador') !== -1 ? 'Borrador' : 'Configurable';

        copy.appendChild(title);
        copy.appendChild(status);

        /* No se borra contenido funcional: solo se ocultan nodos de texto
           redundantes preservando inputs, enlaces y botones. */
        Array.from(el.childNodes).forEach(function (node) {
          if (node.nodeType === Node.TEXT_NODE) node.textContent = '';
        });

        el.insertBefore(copy, el.firstChild);
        el.insertBefore(number, copy);
      }
    });
  }

  /* Detectar encabezado nativo del constructor. */
  var nativeHeading = Array.from(root.querySelectorAll('div,section,header')).find(function (el) {
    var content = normalize(el.textContent);
    return content.indexOf('constructor visual') !== -1
      && content.indexOf('página de inicio') !== -1
      && el.querySelectorAll('form').length === 0
      && el.children.length <= 8;
  });
  if (nativeHeading) nativeHeading.classList.add('hb-native-heading');

  /* Detectar forms de secciones. */
  var sectionForms = Array.from(root.querySelectorAll('form')).filter(function (form) {
    var content = normalize(form.textContent);
    return content.indexOf('guardar borrador') !== -1
      && content.indexOf('guardar y publicar') !== -1
      && !!findKnownSection(content);
  });

  var cards = [];

  sectionForms.forEach(function (form, position) {
    if (form.dataset.hbCard === '1') return;
    form.dataset.hbCard = '1';

    var info = findKnownSection(form.textContent) || {
      key:'section-' + position,
      title:'Sección ' + (position + 1),
      icon:String(position + 1).padStart(2, '0')
    };

    var rawText = normalize(form.textContent);
    var isDraft = rawText.indexOf('borrador') !== -1;
    var isHidden = rawText.indexOf('oculta') !== -1 || rawText.indexOf('oculto') !== -1;
    var orderMatch = rawText.match(/orden\s+(\d+)/);
    var orderValue = orderMatch ? orderMatch[1] : String((position + 1) * 10);

    form.classList.add('hb-section-card');
    form.dataset.hbKey = info.key;
    form.dataset.hbTitle = info.title;
    form.dataset.hbStatus = isDraft ? 'draft' : 'published';
    form.dataset.hbVisibility = isHidden ? 'hidden' : 'visible';

    var summary = document.createElement('div');
    summary.className = 'hb-section-summary';
    summary.setAttribute('role', 'button');
    summary.setAttribute('tabindex', '0');
    summary.setAttribute('aria-expanded', position === 0 ? 'true' : 'false');

    var index = document.createElement('span');
    index.className = 'hb-section-index';
    index.textContent = info.icon;

    var information = document.createElement('div');
    information.className = 'hb-section-info';

    var title = document.createElement('strong');
    title.textContent = info.title;

    var meta = document.createElement('div');
    meta.className = 'hb-section-meta';

    var statusChip = document.createElement('span');
    statusChip.className = 'hb-chip ' + (isDraft ? 'hb-chip--draft' : 'hb-chip--published');
    statusChip.textContent = isDraft ? 'Borrador' : 'Publicado';

    var visibilityChip = document.createElement('span');
    visibilityChip.className = 'hb-chip ' + (isHidden ? 'hb-chip--hidden' : 'hb-chip--visible');
    visibilityChip.textContent = isHidden ? 'Oculta' : 'Visible';

    var orderChip = document.createElement('span');
    orderChip.className = 'hb-chip hb-chip--order';
    orderChip.textContent = 'Orden ' + orderValue;

    meta.appendChild(statusChip);
    meta.appendChild(visibilityChip);
    meta.appendChild(orderChip);
    information.appendChild(title);
    information.appendChild(meta);

    var actions = document.createElement('div');
    actions.className = 'hb-section-action';

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'hb-section-toggle';
    toggle.innerHTML = '<span>' + (position === 0 ? 'Cerrar' : 'Editar') + '</span>'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
      + '<path d="m6 9 6 6 6-6"></path></svg>';

    actions.appendChild(toggle);
    summary.appendChild(index);
    summary.appendChild(information);
    summary.appendChild(actions);

    var content = document.createElement('div');
    content.className = 'hb-section-content';

    while (form.firstChild) content.appendChild(form.firstChild);
    form.appendChild(summary);
    form.appendChild(content);

    if (position === 0) form.classList.add('is-open');

    var setOpen = function (open) {
      form.classList.toggle('is-open', open);
      summary.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.querySelector('span').textContent = open ? 'Cerrar' : 'Editar';
    };

    var toggleOpen = function (event) {
      if (event && event.target.closest('input,select,textarea,a')) return;
      setOpen(!form.classList.contains('is-open'));
    };

    summary.addEventListener('click', toggleOpen);
    summary.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleOpen(event);
      }
    });

    /* Reducir el alto de selectores masivos sin ocultarlos. */
    var checkboxGroups = [];
    Array.from(content.querySelectorAll('input[type="checkbox"]')).forEach(function (checkbox) {
      var candidate = checkbox.parentElement;
      while (candidate && candidate !== content) {
        var count = candidate.querySelectorAll('input[type="checkbox"]').length;
        if (count >= 10) {
          checkboxGroups.push(candidate);
          break;
        }
        candidate = candidate.parentElement;
      }
    });

    checkboxGroups = checkboxGroups.filter(function (candidate, idx, array) {
      return array.indexOf(candidate) === idx
        && !array.some(function (other) {
          return other !== candidate && candidate.contains(other)
            && other.querySelectorAll('input[type="checkbox"]').length >= 10;
        });
    });

    checkboxGroups.forEach(function (group) {
      group.classList.add('hb-long-selector');
    });

    cards.push(form);
  });

  /* Pop-up promocional como card independiente. */
  var popupForm = Array.from(root.querySelectorAll('form')).find(function (form) {
    var content = normalize(form.textContent);
    return content.indexOf('guardar pop-up') !== -1 || content.indexOf('pop-up promocional') !== -1;
  });

  if (popupForm && popupForm.dataset.hbCard !== '1') {
    popupForm.dataset.hbCard = '1';
    popupForm.classList.add('hb-section-card', 'hb-popup-card');
    popupForm.dataset.hbKey = 'popup';
    popupForm.dataset.hbTitle = 'Pop-up promocional';
    popupForm.dataset.hbStatus = normalize(popupForm.textContent).indexOf('inactivo') !== -1 ? 'draft' : 'published';
    popupForm.dataset.hbVisibility = 'visible';

    var popupSummary = document.createElement('div');
    popupSummary.className = 'hb-section-summary';
    popupSummary.setAttribute('role', 'button');
    popupSummary.setAttribute('tabindex', '0');
    popupSummary.setAttribute('aria-expanded', 'false');
    popupSummary.innerHTML =
      '<span class="hb-section-index">P</span>'
      + '<div class="hb-section-info"><strong>Pop-up promocional</strong>'
      + '<div class="hb-section-meta"><span class="hb-chip hb-chip--order">Configuración adicional</span></div></div>'
      + '<div class="hb-section-action"><button type="button" class="hb-section-toggle">'
      + '<span>Editar</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
      + '<path d="m6 9 6 6 6-6"></path></svg></button></div>';

    var popupContent = document.createElement('div');
    popupContent.className = 'hb-section-content';
    while (popupForm.firstChild) popupContent.appendChild(popupForm.firstChild);
    popupForm.appendChild(popupSummary);
    popupForm.appendChild(popupContent);

    popupSummary.addEventListener('click', function () {
      var open = !popupForm.classList.contains('is-open');
      popupForm.classList.toggle('is-open', open);
      popupSummary.setAttribute('aria-expanded', open ? 'true' : 'false');
      popupSummary.querySelector('.hb-section-toggle span').textContent = open ? 'Cerrar' : 'Editar';
    });

    cards.push(popupForm);
  }

  if (!cards.length) return;

  /* Barra de búsqueda y filtros. */
  var controlbar = document.createElement('div');
  controlbar.className = 'hb-controlbar';

  var publishedCount = cards.filter(function (card) {
    return card.dataset.hbStatus === 'published';
  }).length;
  var draftCount = cards.filter(function (card) {
    return card.dataset.hbStatus === 'draft';
  }).length;
  var hiddenCount = cards.filter(function (card) {
    return card.dataset.hbVisibility === 'hidden';
  }).length;

  controlbar.innerHTML =
    '<div class="hb-controlbar__left">'
    + '<div class="hb-controlbar__title"><strong>Bloques de la página de inicio</strong>'
    + '<small>Edita una sección a la vez para trabajar más rápido.</small></div>'
    + '<label class="hb-search"><input type="search" placeholder="Buscar una sección..." aria-label="Buscar una sección">'
    + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg></label>'
    + '</div>'
    + '<div class="hb-controlbar__right">'
    + '<button type="button" class="hb-filter is-active" data-filter="all">Todas <span class="hb-count">' + cards.length + '</span></button>'
    + '<button type="button" class="hb-filter" data-filter="published">Publicadas <span class="hb-count">' + publishedCount + '</span></button>'
    + '<button type="button" class="hb-filter" data-filter="draft">Borradores <span class="hb-count">' + draftCount + '</span></button>'
    + '<button type="button" class="hb-filter" data-filter="hidden">Ocultas <span class="hb-count">' + hiddenCount + '</span></button>'
    + '<button type="button" class="hb-control-button" data-action="open">Abrir todas</button>'
    + '<button type="button" class="hb-control-button" data-action="close">Cerrar todas</button>'
    + '</div>';

  var empty = document.createElement('div');
  empty.className = 'hb-empty-state';
  empty.innerHTML = '<strong>No encontramos secciones</strong><span>Prueba con otro término o cambia el filtro.</span>';

  var anchor = orderPanel || cards[0];
  anchor.parentElement.insertBefore(controlbar, anchor);
  cards[cards.length - 1].insertAdjacentElement('afterend', empty);

  var searchInput = controlbar.querySelector('.hb-search input');
  var activeFilter = 'all';

  var applyFilters = function () {
    var term = normalize(searchInput.value);
    var visibleCards = 0;

    cards.forEach(function (card) {
      var title = normalize(card.dataset.hbTitle);
      var matchesSearch = !term || title.indexOf(term) !== -1;
      var matchesFilter = activeFilter === 'all'
        || (activeFilter === 'published' && card.dataset.hbStatus === 'published')
        || (activeFilter === 'draft' && card.dataset.hbStatus === 'draft')
        || (activeFilter === 'hidden' && card.dataset.hbVisibility === 'hidden');

      var visible = matchesSearch && matchesFilter;
      card.hidden = !visible;
      if (visible) visibleCards++;
    });

    empty.classList.toggle('is-visible', visibleCards === 0);
  };

  searchInput.addEventListener('input', applyFilters);

  controlbar.querySelectorAll('[data-filter]').forEach(function (button) {
    button.addEventListener('click', function () {
      activeFilter = button.dataset.filter;
      controlbar.querySelectorAll('[data-filter]').forEach(function (item) {
        item.classList.toggle('is-active', item === button);
      });
      applyFilters();
    });
  });

  controlbar.querySelector('[data-action="open"]').addEventListener('click', function () {
    cards.filter(function (card) { return !card.hidden; }).forEach(function (card) {
      card.classList.add('is-open');
      var summary = card.querySelector('.hb-section-summary');
      if (summary) summary.setAttribute('aria-expanded', 'true');
      var label = card.querySelector('.hb-section-toggle span');
      if (label) label.textContent = 'Cerrar';
    });
  });

  controlbar.querySelector('[data-action="close"]').addEventListener('click', function () {
    cards.forEach(function (card) {
      card.classList.remove('is-open');
      var summary = card.querySelector('.hb-section-summary');
      if (summary) summary.setAttribute('aria-expanded', 'false');
      var label = card.querySelector('.hb-section-toggle span');
      if (label) label.textContent = 'Editar';
    });
  });
});
</script>

</div>{{-- /layout principal de Diseño --}}
</x-slot>
</x-app-layout>

@php
  $activeTemplate = (string) $project->setting('catalog_template', '');
  $initialTemplateFeedback = $initialAppliedTheme ? [
    'type' => 'success',
    'message' => $initialAppliedTheme['name'].' — '.$initialAppliedTheme['description'].' aplicada',
    'url' => $storeUrl,
  ] : null;
@endphp

<div x-data="{
  selected: @js($activeTemplateIsSupported ? $activeTemplate : ''),
  applying: false,
  applyingKey: '',
  feedback: @js($initialTemplateFeedback),
  pending: null,
  requestTemplate(key) { this.pending = key; },
  async applyTemplate(key) {
    if (this.applying) return;
    this.applying = true;
    this.applyingKey = key;
    this.feedback = null;

    try {
      const response = await fetch('{{ route('settings.design.applyTemplate') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
        },
        body: JSON.stringify({ template: key })
      });

      let json = {};
      try { json = await response.json(); } catch (parseError) {}

      if (!response.ok || !json.ok) {
        throw new Error(json.message || 'No se pudo aplicar la plantilla. Inténtalo nuevamente.');
      }
      if (!json.theme || !json.theme.key || !json.theme.name || !json.theme.description || !json.public_url) {
        throw new Error('La respuesta de la plantilla está incompleta. Recarga la página e inténtalo nuevamente.');
      }

      this.selected = json.theme.key;
      this.feedback = {
        type: 'success',
        message: `${json.theme.name} — ${json.theme.description} aplicada`,
        url: json.public_url
      };
    } catch (error) {
      this.feedback = {
        type: 'error',
        message: error.message || 'No se pudo aplicar la plantilla. Inténtalo nuevamente.',
        url: null
      };
    } finally {
      this.applying = false;
      this.applyingKey = '';
    }
  }
}">
  <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-violet-50 p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Plantilla activa</p>
        <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $tplInfo['label'] }}</h2>
        <p class="mt-1 text-sm text-slate-500">Tus colores, logo, portada, catálogo, checkout y contenido son globales y se conservan al cambiar de plantilla.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="{{ $storeUrl }}" target="_blank" rel="noopener noreferrer" class="min-h-11 rounded-lg border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Vista previa ↗</a>
        <a href="{{ route('settings.experience') }}" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Configurar inicio</a>
      </div>
    </div>
  </div>

  @unless($activeTemplateIsSupported)
    <div class="mt-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status" data-legacy-template-warning>
      <p class="font-semibold">Esta tienda utiliza una plantilla heredada que ya no recibe nuevas funciones.</p>
      <p class="mt-1 text-xs text-amber-800">Puedes conservarla o cambiarla manualmente a una de las tres plantillas oficiales.</p>
    </div>
  @endunless

  <div x-show="pending" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
    <div @click.outside="pending=null" role="dialog" aria-modal="true" aria-labelledby="template-dialog-title" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
      <h3 id="template-dialog-title" class="text-lg font-bold text-slate-900">Cambiar plantilla</h3>
      <p class="mt-2 text-sm text-slate-600">Cambiará la estructura visual, pero se conservarán todos tus ajustes, productos, categorías y contenido.</p>
      <div class="mt-5 flex justify-end gap-2">
        <button type="button" @click="pending=null" :disabled="applying" class="min-h-11 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 disabled:opacity-50">Cancelar</button>
        <button type="button" @click="let key=pending;pending=null;applyTemplate(key)" :disabled="applying" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">Continuar</button>
      </div>
    </div>
  </div>

  <div x-show="feedback && feedback.type === 'success'" x-cloak role="status" aria-live="polite" data-template-feedback="success"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 -translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       class="mt-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-sm">
    <div class="flex-1">
      <span x-text="feedback ? feedback.message : ''">{{ $initialTemplateFeedback['message'] ?? '' }}</span>
      <a :href="feedback && feedback.url ? feedback.url : '#'" href="{{ $initialTemplateFeedback['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer"
         class="ml-2 inline-flex min-h-11 items-center font-semibold underline hover:text-green-900">Ver cambio en la tienda</a>
    </div>
  </div>

  <div x-show="feedback && feedback.type === 'error'" x-cloak role="alert" aria-live="assertive" data-template-feedback="error"
       class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
    <p class="font-semibold">No se aplicó la plantilla.</p>
    <p class="mt-1" x-text="feedback ? feedback.message : ''"></p>
  </div>

  <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3" data-supported-template-selector>
    @foreach($supportedTemplates as $key => $template)
      <article class="relative overflow-hidden rounded-2xl border-2 transition-all duration-200 {{ $activeTemplate === $key ? 'border-indigo-500 shadow-md shadow-indigo-100' : 'border-gray-200 hover:border-indigo-300 hover:shadow-sm' }}"
               :class="selected === '{{ $key }}' ? 'border-indigo-500 shadow-md shadow-indigo-100' : 'border-gray-200 hover:border-indigo-300'"
               data-supported-template-card="{{ $key }}">
        <div class="relative h-28 overflow-hidden" style="background: {{ $template['preview_bg'] }}">
          <div class="absolute inset-x-0 top-0 flex h-6 items-center gap-1 px-2" style="background: rgba(0,0,0,0.35)">
            <div class="h-1.5 w-3 rounded-sm" style="background: {{ $template['preview_accent'] }}"></div>
            <div class="flex flex-1 justify-center gap-2"><div class="h-1 w-5 rounded-sm bg-white/50"></div><div class="h-1 w-5 rounded-sm bg-white/50"></div><div class="h-1 w-5 rounded-sm bg-white/50"></div></div>
            <div class="h-3 w-4 rounded-sm" style="background: {{ $template['preview_accent'] }}"></div>
          </div>
          <span class="absolute left-3 top-8 text-lg leading-none" aria-hidden="true">{{ $template['icon'] }}</span>
          <div class="absolute inset-x-3 bottom-3 flex gap-2" aria-hidden="true">
            @for($index = 0; $index < 3; $index++)
              <div class="h-12 flex-1 overflow-hidden rounded-md border border-white/30 bg-white/20">
                <div class="mx-auto mt-2 h-3 w-5 rounded-sm" style="background: {{ $template['preview_accent'] }}; opacity: .75"></div>
                <div class="mx-2 mt-2 h-0.5 rounded-full bg-white/80"></div>
              </div>
            @endfor
          </div>
          <div x-show="applyingKey === '{{ $key }}'" x-cloak class="absolute inset-0 flex items-center justify-center bg-white/70" aria-label="Aplicando plantilla">
            <svg class="h-6 w-6 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
          </div>
        </div>

        <div class="bg-white p-4">
          <div class="flex min-h-7 items-start justify-between gap-2">
            <h3 class="text-sm font-bold leading-tight text-gray-900">{{ $template['name'] }}</h3>
            <span x-show="selected === '{{ $key }}'" @if($activeTemplate !== $key) x-cloak @endif class="flex-shrink-0 rounded-full bg-indigo-100 px-2 py-1 text-[10px] font-bold text-indigo-700">Plantilla actual</span>
          </div>
          <p class="mt-1 min-h-10 text-sm leading-5 text-gray-500">{{ $template['short_description'] }}</p>
          <div class="mt-3 flex items-center gap-1" aria-label="Colores principales de la plantilla">
            <span class="h-4 w-4 rounded-full border border-gray-200" style="background: {{ $template['preview_bg'] }}"></span>
            <span class="h-4 w-4 rounded-full border border-gray-200" style="background: {{ $template['preview_accent'] }}"></span>
          </div>
          <button type="button" @click="requestTemplate('{{ $key }}')" :disabled="applying || selected === '{{ $key }}'"
                  class="mt-4 min-h-11 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500">
            <span x-show="applyingKey !== '{{ $key }}'" x-text="selected === '{{ $key }}' ? 'Plantilla actual' : 'Aplicar'">{{ $activeTemplate === $key ? 'Plantilla actual' : 'Aplicar' }}</span>
            <span x-show="applyingKey === '{{ $key }}'" x-cloak>Aplicando…</span>
          </button>
        </div>
      </article>
    @endforeach
  </div>
</div>

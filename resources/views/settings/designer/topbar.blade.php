{{-- Barra superior del Diseñador --}}
<header class="dz-topbar">
  <div class="dz-topbar-left">
    <a href="{{ route('settings.design') }}" class="dz-legacy-link" title="Volver al diseñador anterior">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Anterior
    </a>
    <div class="dz-store-meta">
      <strong x-text="project">{{ $project->name }}</strong>
      @php $dzTplKey = $project->setting('catalog_template', 'default'); @endphp
      <span class="dz-template-badge">{{ \App\Modules\Tienda\Support\CatalogTemplates::all()[$dzTplKey]['label'] ?? ucfirst($dzTplKey) }}</span>
    </div>
  </div>

  <div class="dz-topbar-center">
    {{-- Selector de dispositivo --}}
    <div class="dz-device-switch" role="group" aria-label="Vista por dispositivo">
      <button type="button" :class="device==='desktop' && 'is-active'" @click="device='desktop'" title="Escritorio">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <span>Escritorio</span>
      </button>
      <button type="button" :class="device==='mobile' && 'is-active'" @click="device='mobile'" title="Móvil">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
        <span>Móvil</span>
      </button>
    </div>
  </div>

  <div class="dz-topbar-right">
    <div class="dz-undo-redo">
      <button type="button" @click="undo()" :disabled="!canUndo" title="Deshacer">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
      </button>
      <button type="button" @click="redo()" :disabled="!canRedo" title="Rehacer">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg>
      </button>
    </div>

    <span class="dz-status" :class="'dz-status--'+status">
      <span class="dz-status-dot"></span>
      <span x-text="statusLabel"></span>
    </span>

    <button type="button" class="dz-btn dz-btn-ghost dz-btn-ico" @click="quickOpen=true">{!! \App\Modules\Tienda\Support\DesignerIcons::get('bolt') !!} Configuración rápida</button>
    <a href="{{ route('public.catalog', $project->slug) }}?preview=1" target="_blank" rel="noopener" class="dz-btn dz-btn-ghost">Vista previa</a>
    <button type="button" class="dz-btn dz-btn-ghost" @click="saveDraft()" :disabled="status==='saving'">Guardar borrador</button>
    <button type="button" class="dz-btn dz-btn-primary" @click="publish()" :disabled="status==='saving'">Publicar</button>
  </div>
</header>

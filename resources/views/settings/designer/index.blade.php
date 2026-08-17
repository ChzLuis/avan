<x-app-layout>
<x-slot name="slot">
{{--
  Nuevo Diseñador visual (Fase A · shell).
  Estructura: topbar + panel izquierdo (estructura) + preview central + inspector.
  Estado central en Alpine: sección seleccionada, dispositivo, y estado de cambios.
  El guardado usa el endpoint canónico existente (settings.design.update).
--}}
<div
  x-data="designerShell({
    saveUrl: @js(route('settings.design.update')),
    csrf: @js(csrf_token()),
    project: @js($project->name),
    template: @js($project->setting('catalog_template', 'default')),
  })"
  class="dz-root"
  @keydown.window.ctrl.s.prevent="saveDraft()"
>
  @include('settings.designer.topbar')

  <div class="dz-body">
    @include('settings.designer.structure-panel')
    @include('settings.designer.preview-panel')
    @include('settings.designer.inspector-panel')
  </div>

  @include('settings.designer.quick-setup')
</div>

@include('settings.designer.styles')
@include('settings.designer.script')
@include('settings.designer.quick-script')
</x-slot>
</x-app-layout>

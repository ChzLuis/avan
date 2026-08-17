{{-- Panel derecho: inspector del elemento seleccionado --}}
<aside class="dz-inspector" aria-label="Propiedades">
  {{-- Estado vacío --}}
  <div x-show="!selected" class="dz-inspector-empty">
    <div class="dz-inspector-empty-ico">{!! \App\Support\DesignerIcons::get('select') !!}</div>
    <p>Selecciona un bloque en la estructura o en la vista para editarlo aquí.</p>
  </div>

  <div x-show="selected" x-cloak class="dz-inspector-body">
    <div class="dz-inspector-head">
      <strong x-text="inspectorTitle"></strong>
    </div>

    {{-- Inspector del ENCABEZADO --}}
    <div x-show="selected==='header'">
      @include('settings.designer.sections.header')
    </div>

    {{-- Inspector de cada SECCIÓN del Inicio --}}
    <template x-for="sec in homeSections" :key="'insp-'+sec.component">
      <div x-show="selected===('section:'+sec.component)">
        {{-- Encabezado común de sección: activar + visibilidad por dispositivo --}}
        <div class="dz-insp-section-controls">
          <label class="dz-switch-row">
            <span>Mostrar sección</span>
            <input type="checkbox" :checked="sec.enabled" @change="toggleSection(sec.component)">
          </label>
          <div class="dz-device-visibility">
            <label class="dz-chk"><input type="checkbox" :checked="sec.show_desktop" @change="toggleDevice(sec.component,'desktop')"> Escritorio</label>
            <label class="dz-chk"><input type="checkbox" :checked="sec.show_mobile" @change="toggleDevice(sec.component,'mobile')"> Móvil</label>
          </div>
          <p x-show="!sec.show_desktop && !sec.show_mobile" class="dz-warn">⚠️ Esta sección está oculta en todos los dispositivos.</p>
        </div>

        {{-- Cuerpo específico de la sección (Fase A: Banner completo; resto en Fase B) --}}
        <div x-show="sec.component==='hero'">@include('settings.designer.sections.hero')</div>
        <div x-show="sec.component==='benefits'">@include('settings.designer.sections.benefits')</div>
        <div x-show="sec.component==='announcements'">@include('settings.designer.sections.ads')</div>
        <div x-show="sec.component==='featured_categories'">@include('settings.designer.sections.categories')</div>
        <div x-show="sec.component==='featured_products'">@include('settings.designer.sections.featured-products')</div>
        <div x-show="sec.component==='daily_offer'">@include('settings.designer.sections.daily-offer')</div>
        <div x-show="!['hero','benefits','announcements','featured_categories','featured_products'].includes(sec.component)">
          <p class="dz-insp-note">Esta sección se configura con los ajustes generales. Edición detallada disponible próximamente.</p>
        </div>
      </div>
    </template>

    {{-- Nodos fijos migrados (Fase C) --}}
    <div x-show="selected==='catalog'">@include('settings.designer.sections.catalog')</div>
    <div x-show="selected==='product'">@include('settings.designer.sections.product')</div>
    <div x-show="selected==='footer'">@include('settings.designer.sections.footer')</div>
    <div x-show="selected==='checkout'">@include('settings.designer.sections.checkout')</div>

    {{-- Páginas: se gestionan aparte (Fase D) --}}
    <template x-if="selected==='pages'">
      <div class="dz-insp-note">
        <p>Las páginas (Nosotros, Contacto) se gestionan en su propia sección.</p>
        <a :href="@js(route('settings.design'))+'#pages'" class="dz-link">Editar páginas</a>
      </div>
    </template>
  </div>
</aside>

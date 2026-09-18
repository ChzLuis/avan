{{-- Vista central: preview esquemática seleccionable --}}
<main class="dz-preview" :class="'dz-preview--'+device">
  <div class="dz-preview-frame">
    <div class="dz-canvas" :style="`--dz-primary:${settings.primary_color||'#4f46e5'}`">

      {{-- Encabezado --}}
      <div class="dz-blk dz-blk-header" :class="selected==='header' && 'is-selected'" @click="select('header')">
        <div class="dz-blk-tag">Encabezado</div>
        <div class="dz-mock-header">
          <div class="dz-mock-logo" x-text="(settings.seo_title||project||'Tienda').slice(0,14)"></div>
          <div class="dz-mock-search"></div>
          <div class="dz-mock-cart">{!! \App\Modules\Tienda\Support\DesignerIcons::get('cart') !!}</div>
        </div>
      </div>

      {{-- Secciones del Inicio, en orden --}}
      <template x-for="sec in homeSections" :key="'pv-'+sec.component">
        <div x-show="sec.enabled && (device==='mobile' ? sec.show_mobile : sec.show_desktop)"
             class="dz-blk"
             :class="selected===('section:'+sec.component) && 'is-selected'"
             @click="select('section:'+sec.component)">
          <div class="dz-blk-tag" x-text="sec.label"></div>

          {{-- Mock por tipo de sección --}}
          <template x-if="sec.component==='hero'">
            <div class="dz-mock-hero"><span x-text="settings.hero_title||'Banner principal'"></span></div>
          </template>
          <template x-if="sec.component==='benefits'">
            <div class="dz-mock-row"><div class="dz-mock-chip">✓</div><div class="dz-mock-chip">✓</div><div class="dz-mock-chip">✓</div></div>
          </template>
          <template x-if="sec.component==='featured_categories'">
            <div class="dz-mock-grid"><div></div><div></div><div></div><div></div></div>
          </template>
          <template x-if="sec.component==='featured_products'">
            <div class="dz-mock-cards"><div></div><div></div><div></div><div></div></div>
          </template>
          <template x-if="!['hero','benefits','featured_categories','featured_products'].includes(sec.component)">
            <div class="dz-mock-band"></div>
          </template>
        </div>
      </template>

      {{-- Pie --}}
      <div class="dz-blk dz-blk-footer" :class="selected==='footer' && 'is-selected'" @click="select('footer')">
        <div class="dz-blk-tag">Pie de página</div>
        <div class="dz-mock-footer"><div></div><div></div><div></div></div>
      </div>

    </div>
  </div>
  <p class="dz-preview-hint">Vista esquemática · haz clic en un bloque para editarlo. Usa <strong>Vista previa</strong> para ver la tienda real.</p>
</main>

{{-- Panel izquierdo: estructura de la tienda --}}
@php $dzIcons = \App\Support\DesignerIcons::all(); @endphp

<aside class="dz-structure" aria-label="Estructura de la tienda">
  <div class="dz-structure-head">
    <span>Estructura</span>
  </div>

  <nav class="dz-tree">
    {{-- Encabezado (nodo fijo) --}}
    <button type="button" class="dz-node dz-node--fixed" :class="selected==='header' && 'is-selected'" @click="select('header')">
      <span class="dz-node-ico">{!! $dzIcons['header'] !!}</span>
      <span class="dz-node-label">Encabezado</span>
    </button>

    {{-- Inicio: grupo con las secciones reales del proyecto --}}
    <div class="dz-group">
      <div class="dz-group-head">Inicio</div>
      <div class="dz-group-body">
        <template x-for="sec in homeSections" :key="sec.component">
          <button type="button" class="dz-node dz-node--section"
                  :class="[selected===('section:'+sec.component) && 'is-selected', !sec.enabled && 'is-disabled']"
                  @click="select('section:'+sec.component)">
            <span class="dz-node-move">
              <button type="button" @click.stop="moveSection(sec.component,-1)" :disabled="homeSections.indexOf(sec)===0" :aria-label="'Subir '+sec.label" title="Subir"><svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 14.5 12 8.5l6 6"/></svg></button>
              <button type="button" @click.stop="moveSection(sec.component,1)" :disabled="homeSections.indexOf(sec)===homeSections.length-1" :aria-label="'Bajar '+sec.label" title="Bajar"><svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9.5 6 6 6-6"/></svg></button>
            </span>
            <span class="dz-node-ico" x-html="sec.icon"></span>
            <span class="dz-node-label" x-text="sec.label"></span>
            {{-- estado de visibilidad por dispositivo --}}
            <span class="dz-node-devices" aria-hidden="true">
              <span :class="!sec.show_desktop && 'off'" title="Escritorio">{!! $dzIcons['desktop'] !!}</span>
              <span :class="!sec.show_mobile && 'off'" title="Móvil">{!! $dzIcons['mobile'] !!}</span>
            </span>
            {{-- toggle activar/desactivar --}}
            <span class="dz-node-toggle" :class="sec.enabled && 'on'" @click.stop="toggleSection(sec.component)" @keydown.enter.stop.prevent="toggleSection(sec.component)" @keydown.space.stop.prevent="toggleSection(sec.component)" role="switch" tabindex="0" :aria-checked="sec.enabled" :aria-label="'Mostrar u ocultar '+sec.label"></span>
          </button>
        </template>
      </div>
    </div>

    {{-- Páginas de la tienda --}}
    <div class="dz-group">
      <div class="dz-group-head">Páginas</div>
      <div class="dz-group-body dz-group-body--plain">
        <button type="button" class="dz-node dz-node--fixed" :class="selected==='catalog' && 'is-selected'" @click="select('catalog')">
          <span class="dz-node-ico">{!! $dzIcons['catalog'] !!}</span><span class="dz-node-label">Catálogo</span>
        </button>
        <button type="button" class="dz-node dz-node--fixed" :class="selected==='product' && 'is-selected'" @click="select('product')">
          <span class="dz-node-ico">{!! $dzIcons['product'] !!}</span><span class="dz-node-label">Página de producto</span>
        </button>
        <button type="button" class="dz-node dz-node--fixed" :class="selected==='pages' && 'is-selected'" @click="select('pages')">
          <span class="dz-node-ico">{!! $dzIcons['pages'] !!}</span><span class="dz-node-label">Páginas</span>
        </button>
        <button type="button" class="dz-node dz-node--fixed" :class="selected==='checkout' && 'is-selected'" @click="select('checkout')">
          <span class="dz-node-ico">{!! $dzIcons['checkout'] !!}</span><span class="dz-node-label">Venta y checkout</span>
        </button>
      </div>
    </div>

    {{-- Pie de página (nodo fijo, cierra la estructura como en la tienda real) --}}
    <button type="button" class="dz-node dz-node--fixed" :class="selected==='footer' && 'is-selected'" @click="select('footer')">
      <span class="dz-node-ico">{!! $dzIcons['footer'] !!}</span><span class="dz-node-label">Pie de página</span>
    </button>
  </nav>
</aside>

{{-- Inspector: Banner principal (hero). Reúne todo lo que antes estaba
     partido entre "Inicio" (orden/activar) y "Portada" (diseño).
     Usa los name= canónicos del contrato: hero_title, hero_subtitle,
     hero_image[_N], hero_mobile_image_N, hero_align, hero_height, etc. --}}
<div class="dz-tabs" x-data="{ t:'contenido' }">
  <div class="dz-tabs-nav">
    <button type="button" :class="t==='contenido' && 'on'" @click="t='contenido'">Contenido</button>
    <button type="button" :class="t==='diseno' && 'on'" @click="t='diseno'">Diseño</button>
    <button type="button" :class="t==='dispositivo' && 'on'" @click="t='dispositivo'">Móvil / Escritorio</button>
  </div>

  {{-- ── CONTENIDO ── --}}
  <div x-show="t==='contenido'" class="dz-tab-body">
    <div class="dz-field">
      <label>Título del banner</label>
      <input type="text" x-model="settings.hero_title" @input="markDirty()" placeholder="Ej. Tecnología para tu negocio">
    </div>
    <div class="dz-field">
      <label>Descripción</label>
      <textarea x-model="settings.hero_subtitle" @input="markDirty()" placeholder="Una frase que acompaña al título"></textarea>
    </div>

    <div class="dz-field-group">
      <div class="dz-field-group-title">Botones</div>
      <div class="dz-row-2">
        <div class="dz-field"><label>Botón principal</label><input type="text" x-model="settings.hero_cta1_text" @input="markDirty()" placeholder="Ver productos"></div>
        <label class="dz-chk dz-chk-inline"><input type="checkbox" :checked="settings.hero_cta1_show!=='0'" @change="setToggle('hero_cta1_show',$event.target.checked)"> Mostrar</label>
      </div>
      <div class="dz-row-2">
        <div class="dz-field"><label>Botón secundario</label><input type="text" x-model="settings.hero_cta2_text" @input="markDirty()" placeholder="Contactar"></div>
        <label class="dz-chk dz-chk-inline"><input type="checkbox" :checked="settings.hero_cta2_show!=='0'" @change="setToggle('hero_cta2_show',$event.target.checked)"> Mostrar</label>
      </div>
    </div>

    <div class="dz-field">
      <label>Etiqueta (badge, opcional)</label>
      <input type="text" x-model="settings.hero_badge" @input="markDirty()" placeholder="Ej. Nuevo · Oferta">
    </div>
  </div>

  {{-- ── DISEÑO ── --}}
  <div x-show="t==='diseno'" class="dz-tab-body">
    <div class="dz-field">
      <label>Alineación del texto</label>
      <div class="dz-seg">
        <template x-for="a in ['left','center','right']" :key="a">
          <button type="button" :class="(settings.hero_align||'left')===a && 'on'" @click="setValue('hero_align',a)" x-text="{left:'Izquierda',center:'Centro',right:'Derecha'}[a]"></button>
        </template>
      </div>
    </div>
    <div class="dz-field">
      <label>Mostrar texto sobre el banner</label>
      <div class="dz-seg">
        <button type="button" :class="(settings.hero_show_content||'1')!=='0' && 'on'" @click="setValue('hero_show_content','1')">Texto y botones</button>
        <button type="button" :class="(settings.hero_show_content||'1')==='0' && 'on'" @click="setValue('hero_show_content','0')">Solo imagen</button>
      </div>
    </div>
    <div class="dz-row-2">
      <div class="dz-field"><label>Color de fondo</label><input type="color" x-model="settings.hero_bg_color" @input="markDirty()"></div>
      <div class="dz-field"><label>Oscurecer imagen (overlay)</label><input type="range" min="0" max="80" x-model="settings.hero_overlay" @input="markDirty()"><small x-text="(settings.hero_overlay||0)+'%'"></small></div>
    </div>
  </div>

  {{-- ── MÓVIL / ESCRITORIO ── --}}
  <div x-show="t==='dispositivo'" class="dz-tab-body">
    <p class="dz-insp-note">Sube una versión para cada dispositivo. Recomendado: PC 1920×650 · Móvil 750×950.</p>

    <div class="dz-media-2">
      <div class="dz-media">
        <div class="dz-media-label">Imagen escritorio</div>
        <div class="dz-media-drop" :style="settings.hero_image ? `background-image:url(${assetUrl(settings.hero_image)})` : ''">
          <span x-show="!settings.hero_image">1920×650</span>
        </div>
        <div class="dz-media-actions">
          <label class="dz-btn dz-btn-ghost dz-btn-sm">Subir PC<input type="file" accept="image/*" class="dz-hidden" @change="uploadImage($event,'hero_image')"></label>
          <button type="button" x-show="settings.hero_image" class="dz-btn dz-btn-danger dz-btn-sm" @click="setValue('hero_image','')">Quitar</button>
        </div>
      </div>
      <div class="dz-media">
        <div class="dz-media-label">Imagen móvil</div>
        <div class="dz-media-drop dz-media-drop--mobile" :style="settings.hero_mobile_image_1 ? `background-image:url(${assetUrl(settings.hero_mobile_image_1)})` : ''">
          <span x-show="!settings.hero_mobile_image_1">750×950</span>
        </div>
        <div class="dz-media-actions">
          <label class="dz-btn dz-btn-ghost dz-btn-sm">Subir móvil<input type="file" accept="image/*" class="dz-hidden" @change="uploadImage($event,'hero_mobile_image_1')"></label>
          <button type="button" x-show="settings.hero_mobile_image_1" class="dz-btn dz-btn-danger dz-btn-sm" @click="setValue('hero_mobile_image_1','')">Quitar</button>
        </div>
      </div>
    </div>

    <div class="dz-row-2">
      <div class="dz-field"><label>Altura escritorio</label><input type="number" min="240" max="900" x-model="settings.hero_height" @input="markDirty()"><small>px</small></div>
      <div class="dz-field"><label>Altura móvil</label><input type="number" min="200" max="900" x-model="settings.hero_mobile_height" @input="markDirty()"><small>px</small></div>
    </div>
  </div>
</div>

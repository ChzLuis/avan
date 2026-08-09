{{-- Inspector: Encabezado unificado (logo, buscador, menú, colores) --}}
<div class="dz-tabs" x-data="{ t:'contenido' }">
  <div class="dz-tabs-nav">
    <button type="button" :class="t==='contenido' && 'on'" @click="t='contenido'">Contenido</button>
    <button type="button" :class="t==='diseno' && 'on'" @click="t='diseno'">Diseño</button>
    <button type="button" :class="t==='dispositivo' && 'on'" @click="t='dispositivo'">Móvil / Escritorio</button>
  </div>

  <div x-show="t==='contenido'" class="dz-tab-body">
    <div class="dz-media">
      <div class="dz-media-label">Logo</div>
      <div class="dz-media-drop dz-media-drop--logo" :style="settings.logo_url ? `background-image:url(${assetUrl(settings.logo_url)})` : ''">
        <span x-show="!settings.logo_url">Sube tu logo</span>
      </div>
      <div class="dz-media-actions">
        <label class="dz-btn dz-btn-ghost dz-btn-sm">Subir logo<input type="file" accept="image/*" class="dz-hidden" @change="uploadImage($event,'logo_url')"></label>
        <button type="button" x-show="settings.logo_url" class="dz-btn dz-btn-danger dz-btn-sm" @click="setValue('logo_url','')">Quitar</button>
      </div>
    </div>
    <div class="dz-check-list">
      <label class="dz-switch-row"><span>Mostrar buscador</span><input type="checkbox" :checked="settings.header_show_search!=='0'" @change="setToggle('header_show_search',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Mostrar carrito</span><input type="checkbox" :checked="settings.header_show_cart!=='0'" @change="setToggle('header_show_cart',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Mostrar contacto</span><input type="checkbox" :checked="settings.header_show_contact!=='0'" @change="setToggle('header_show_contact',$event.target.checked)"></label>
    </div>
    <div class="dz-field">
      <label>Qué se fija al desplazar (sticky)</label>
      <div class="dz-seg">
        <button type="button" :class="(settings.header_sticky_mode||'all')==='all' && 'on'" @click="setValue('header_sticky_mode','all')">Todo el encabezado</button>
        <button type="button" :class="(settings.header_sticky_mode||'all')==='header'&&'on'" @click="setValue('header_sticky_mode','header')">Solo el encabezado</button>
                <button type="button" :class="(settings.header_sticky_mode||'all')==='menu' && 'on'" @click="setValue('header_sticky_mode','menu')">Solo el menú</button>
        <button type="button" :class="(settings.header_sticky_mode||'all')==='none' && 'on'" @click="setValue('header_sticky_mode','none')">Nada</button>
      </div>
    </div>
  </div>

  <div x-show="t==='diseno'" class="dz-tab-body">
    <div class="dz-row-2">
      <div class="dz-field"><label>Fondo del encabezado</label><input type="color" x-model="settings.header_bg_color" @input="markDirty()"></div>
      <div class="dz-field"><label>Texto</label><input type="color" x-model="settings.header_text_color" @input="markDirty()"></div>
    </div>
    <div class="dz-field"><label>Diseño del menú</label>
      <select x-model="settings.header_style" @change="markDirty()">
        <option value="classic">Clásico claro</option>
        <option value="dark">Barra oscura</option>
        <option value="accent">Banda de color</option>
        <option value="pill">Píldora flotante</option>
        <option value="line">Minimal subrayado</option>
      </select>
    </div>
    <div class="dz-row-2">
      <div class="dz-field"><label>Fondo del menú</label><input type="color" x-model="settings.menu_bg_color" @input="markDirty()"></div>
      <div class="dz-field"><label>Texto del menú</label><input type="color" x-model="settings.menu_text_color" @input="markDirty()"></div>
    </div>
    <div class="dz-row-2">
      <div class="dz-field"><label>Fondo botón activo</label><input type="color" x-model="settings.menu_active_bg_color" @input="markDirty()"></div>
      <div class="dz-field"><label>Texto botón activo</label><input type="color" x-model="settings.menu_active_text_color" @input="markDirty()"></div>
    </div>
    <p class="dz-insp-note">Deja un color sin tocar para usar el del diseño elegido. Vacíalo guardando el campo en blanco desde el constructor si quieres volver a "auto".</p>
  </div>

  <div x-show="t==='dispositivo'" class="dz-tab-body">
    <div class="dz-row-2">
      <div class="dz-field"><label>Altura del encabezado</label><input type="number" min="56" max="140" x-model="settings.header_height" @input="markDirty()"><small>px</small></div>
      <div class="dz-field"><label>Altura del logo</label><input type="number" min="24" max="120" x-model="settings.header_logo_height" @input="markDirty()"><small>px</small></div>
    </div>
    <p class="dz-insp-note">Los enlaces del menú se gestionan en el constructor de navegación (misma experiencia visual próximamente).</p>
  </div>
</div>

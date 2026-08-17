{{-- Inspector: Categorías destacadas --}}
<div class="dz-tab-body">
  <div class="dz-field">
    <label>Título de la sección</label>
    <input type="text" x-model="settings.featured_categories_title" @input="markDirty()" placeholder="Explora por categoría">
  </div>
  <div class="dz-field">
    <label>Subtítulo (opcional)</label>
    <input type="text" x-model="settings.featured_categories_subtitle" @input="markDirty()" placeholder="Encuentra lo que buscas">
  </div>
  <div class="dz-field">
    <label>Diseño de la sección</label>
    <select x-model="settings.featured_categories_style" @change="markDirty()">
      <option value="image-top">Tarjetas con foto</option>
      <option value="overlay">Foto de fondo</option>
      <option value="minimal">Minimal</option>
      <option value="horizontal">Horizontal</option>
      <option value="showcase">Círculos con banda de título</option>
      <option value="circles">Círculos limpios</option>
      <option value="carousel">Carrusel de círculos con flechas</option>
      <option value="editorial">Tarjetas fotográficas altas</option>
    </select>
    <small>“Círculos con banda” muestra un título destacado y las categorías en círculos grandes.</small>
  </div>
  <div class="dz-row-2" x-show="settings.featured_categories_style==='showcase'">
    <div class="dz-field"><label>Fondo de la banda</label><input type="color" x-model="settings.featured_categories_band_bg" @input="markDirty()"></div>
    <div class="dz-field"><label>Letra de la banda</label><input type="color" x-model="settings.featured_categories_band_text" @input="markDirty()"></div>
  </div>
  <div class="dz-field-group">
    <div class="dz-field-group-title">Mostrar</div>
    <div class="dz-check-list">
      <label class="dz-switch-row"><span>Cantidad de productos</span><input type="checkbox" :checked="settings.featured_categories_show_count!=='0'" @change="setToggle('featured_categories_show_count',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Botón "Ver todo"</span><input type="checkbox" :checked="settings.featured_categories_show_all!=='0'" @change="setToggle('featured_categories_show_all',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Carrusel en móvil</span><input type="checkbox" :checked="settings.featured_categories_mobile_carousel==='1'" @change="setToggle('featured_categories_mobile_carousel',$event.target.checked)"></label>
    </div>
  </div>
  <p class="dz-insp-note">Qué categorías aparecen (y sus fotos o iconos) se elige en el constructor → sección Inicio → Categorías destacadas.</p>
</div>

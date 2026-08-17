{{-- Inspector: Productos destacados --}}
<div class="dz-tab-body">
  <div class="dz-field">
    <label>Título de la sección</label>
    <input type="text" x-model="settings.catalog_section_title" @input="markDirty()" placeholder="Productos destacados">
  </div>
  <div class="dz-field">
    <label>Vista</label>
    <div class="dz-seg">
      <button type="button" :class="(settings.featured_products_view||'cards')==='cards' && 'on'" @click="setValue('featured_products_view','cards')">Tarjetas</button>
      <button type="button" :class="(settings.featured_products_view||'cards')==='editorial' && 'on'" @click="setValue('featured_products_view','editorial')">Editorial</button>
    </div>
  </div>
  <p class="dz-insp-note">Los productos se toman de tu catálogo. El diseño de las tarjetas se ajusta en Catálogo.</p>
</div>

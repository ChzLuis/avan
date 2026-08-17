{{-- Inspector: Oferta del día (contador) --}}
<div class="dz-tab-body">
  <div class="dz-field">
    <label>Diseño del contador</label>
    <div class="dz-seg">
      <button type="button" :class="(settings.flash_sale_style||'full')==='full' && 'on'" @click="setValue('flash_sale_style','full')">Bloque completo</button>
      <button type="button" :class="(settings.flash_sale_style||'full')==='band' && 'on'" @click="setValue('flash_sale_style','band')">Banda compacta</button>
    </div>
    <small>La banda compacta muestra el título y el contador en cajas, en una sola línea.</small>
  </div>
  <div class="dz-field" x-show="(settings.flash_sale_style||'full')==='band'">
    <label>Color de las cajas del contador</label>
    <input type="color" x-model="settings.flash_sale_accent" @input="markDirty()">
  </div>
  <p class="dz-insp-note">El título, la fecha de fin, el color de fondo y los productos de la oferta se editan en el constructor → sección Inicio → Oferta del día.</p>
</div>

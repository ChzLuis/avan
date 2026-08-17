{{-- Inspector: Catálogo (grid, filtros, badges, opciones) — Fase C.
     Usa los name= canónicos: catalog_cols_*, catalog_filter_*, catalog_badge_*,
     catalog_show_*, card_style. No modifica la lógica AJAX/SSR/filtros. --}}
<div class="dz-tabs" x-data="{ t:'diseno' }">
  <div class="dz-tabs-nav">
    <button type="button" :class="t==='diseno' && 'on'" @click="t='diseno'">Diseño</button>
    <button type="button" :class="t==='filtros' && 'on'" @click="t='filtros'">Filtros</button>
    <button type="button" :class="t==='tarjeta' && 'on'" @click="t='tarjeta'">Tarjeta</button>
  </div>

  {{-- ── DISEÑO ── --}}
  <div x-show="t==='diseno'" class="dz-tab-body">
    <div class="dz-field">
      <label>Título de la sección</label>
      <input type="text" x-model="settings.catalog_section_title" @input="markDirty()" placeholder="Nuestros productos">
    </div>
    <div class="dz-row-2">
      <div class="dz-field"><label>Columnas en escritorio</label>
        <select x-model="settings.catalog_cols_desktop" @change="markDirty()">
          @foreach([2,3,4,5] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
        </select>
      </div>
      <div class="dz-field"><label>Columnas en móvil</label>
        <select x-model="settings.catalog_cols_mobile" @change="markDirty()">
          @foreach([1,2,3] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
        </select>
      </div>
    </div>
    <div class="dz-field">
      <label>Vista de productos</label>
      <div class="dz-seg">
        <button type="button" :class="(settings.catalog_products_view||'cards')==='cards' && 'on'" @click="setValue('catalog_products_view','cards')">Tarjetas</button>
        <button type="button" :class="(settings.catalog_products_view||'cards')==='compact' && 'on'" @click="setValue('catalog_products_view','compact')">Compacta</button>
      </div>
    </div>
  </div>

  {{-- ── FILTROS ── --}}
  <div x-show="t==='filtros'" class="dz-tab-body">
    <p class="dz-insp-note">Controla qué filtros ve el cliente en el catálogo.</p>
    <div class="dz-check-list">
      <label class="dz-switch-row"><span>Buscador</span><input type="checkbox" :checked="settings.catalog_filter_search!=='0'" @change="setToggle('catalog_filter_search',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Filtro por categoría</span><input type="checkbox" :checked="settings.catalog_filter_cats!=='0'" @change="setToggle('catalog_filter_cats',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Filtro por precio</span><input type="checkbox" :checked="settings.catalog_filter_price!=='0'" @change="setToggle('catalog_filter_price',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Filtro “en oferta”</span><input type="checkbox" :checked="settings.catalog_filter_sale!=='0'" @change="setToggle('catalog_filter_sale',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Vista rápida</span><input type="checkbox" :checked="settings.catalog_quick_view!=='0'" @change="setToggle('catalog_quick_view',$event.target.checked)"></label>
    </div>
  </div>

  {{-- ── TARJETA DE PRODUCTO ── --}}
  <div x-show="t==='tarjeta'" class="dz-tab-body">
    <div class="dz-field">
      <label>Estilo de tarjeta</label>
      <div class="dz-seg">
        <template x-for="s in ['modern','minimal','bordered']" :key="s">
          <button type="button" :class="(settings.card_style||'modern')===s && 'on'" @click="setValue('card_style',s)" x-text="{modern:'Moderna',minimal:'Mínima',bordered:'Con borde'}[s]"></button>
        </template>
      </div>
    </div>
    <div class="dz-field-group">
      <div class="dz-field-group-title">Mostrar en la tarjeta</div>
      <div class="dz-check-list">
        <label class="dz-switch-row"><span>SKU</span><input type="checkbox" :checked="settings.catalog_show_sku==='1'" @change="setToggle('catalog_show_sku',$event.target.checked)"></label>
        <label class="dz-switch-row"><span>Stock disponible</span><input type="checkbox" :checked="settings.catalog_show_stock!=='0'" @change="setToggle('catalog_show_stock',$event.target.checked)"></label>
        <label class="dz-switch-row"><span>Valoraciones</span><input type="checkbox" :checked="settings.catalog_show_ratings==='1'" @change="setToggle('catalog_show_ratings',$event.target.checked)"></label>
      </div>
    </div>
    <div class="dz-field-group">
      <div class="dz-field-group-title">Etiquetas (badges)</div>
      <div class="dz-row-2">
        <div class="dz-field"><label>Nuevo</label><input type="text" x-model="settings.catalog_badge_new" @input="markDirty()" placeholder="Nuevo"></div>
        <div class="dz-field"><label>Oferta</label><input type="text" x-model="settings.catalog_badge_sale" @input="markDirty()" placeholder="Oferta"></div>
      </div>
      <div class="dz-row-2">
        <div class="dz-field"><label>Destacado</label><input type="text" x-model="settings.catalog_badge_featured" @input="markDirty()" placeholder="Destacado"></div>
        <div class="dz-field"><label>Agotado</label><input type="text" x-model="settings.catalog_badge_sold_out" @input="markDirty()" placeholder="Agotado"></div>
      </div>
    </div>
    <div class="dz-field-group">
      <div class="dz-field-group-title">Botones del producto</div>
      <div class="dz-field">
        <label>Qué botones mostrar</label>
        <select x-model="settings.product_button_mode" @change="markDirty()">
          <option value="cart">Solo comprar / agregar</option>
          <option value="inquiry">Solo consultar por WhatsApp</option>
          <option value="both">Ambos botones</option>
        </select>
        <p class="dz-insp-note">Aplica al catálogo y a la página de producto. En modo cotización, el botón de compra agrega a la cotización y la consulta llega por WhatsApp.</p>
      </div>
      <div class="dz-field" x-show="(settings.product_button_mode||'cart')!=='inquiry'">
        <label>Texto del botón de compra</label>
        <input type="text" x-model="settings.btn_cart_text" @input="markDirty()" placeholder="Agregar al carrito">
      </div>
      <div class="dz-field" x-show="(settings.product_button_mode||'cart')!=='cart'">
        <label>Texto del botón consultar</label>
        <input type="text" x-model="settings.btn_inquiry_text" @input="markDirty()" placeholder="Consultar">
      </div>
    </div>
  </div>
</div>

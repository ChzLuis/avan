{{-- Inspector: Página de producto — Fase C. --}}
<div class="dz-tab-body">
  <p class="dz-insp-note">Cómo se ve la página de un producto individual (galería, info, relacionados).</p>
  <div class="dz-field-group">
    <div class="dz-field-group-title">Mostrar en la página</div>
    <div class="dz-check-list">
      <label class="dz-switch-row"><span>Productos relacionados</span><input type="checkbox" :checked="settings.product_show_related!=='0'" @change="setToggle('product_show_related',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Compartir en redes</span><input type="checkbox" :checked="settings.product_show_share!=='0'" @change="setToggle('product_show_share',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>SKU del producto</span><input type="checkbox" :checked="settings.product_show_sku==='1'" @change="setToggle('product_show_sku',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Aviso de stock bajo</span><input type="checkbox" :checked="settings.product_show_low_stock!=='0'" @change="setToggle('product_show_low_stock',$event.target.checked)"></label>
    </div>
  </div>
  <p class="dz-insp-note">Los botones (comprar / consultar por WhatsApp) y sus textos se configuran en la sección <strong>Catálogo</strong> y aplican también a esta página.</p>
</div>

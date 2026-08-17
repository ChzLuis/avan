{{-- Inspector: Venta y checkout — Fase C. Agrupa modalidad, pagos y envío.
     name= canónicos: store_mode, quote_*, payment_*, shipping_*, require_address. --}}
<div class="dz-tabs" x-data="{ t:'modalidad' }">
  <div class="dz-tabs-nav">
    <button type="button" :class="t==='modalidad' && 'on'" @click="t='modalidad'">Modalidad</button>
    <button type="button" :class="t==='pagos' && 'on'" @click="t='pagos'">Pagos</button>
    <button type="button" :class="t==='envio' && 'on'" @click="t='envio'">Envío</button>
  </div>

  {{-- ── MODALIDAD ── --}}
  <div x-show="t==='modalidad'" class="dz-tab-body">
    <div class="dz-field">
      <label>Modo de la tienda</label>
      <div class="dz-seg">
        <button type="button" :class="(settings.store_mode||'direct')==='direct' && 'on'" @click="setValue('store_mode','direct')">Venta directa</button>
        <button type="button" :class="(settings.store_mode||'direct')==='quote_only' && 'on'" @click="setValue('store_mode','quote_only')">Cotización</button>
      </div>
    </div>
    <div class="dz-field-group" x-show="(settings.store_mode||'direct')==='quote_only'">
      <div class="dz-field-group-title">Cotización por WhatsApp</div>
      <div class="dz-field"><label>Número de WhatsApp</label><input type="text" x-model="settings.quote_whatsapp" @input="markDirty()" placeholder="987654321"></div>
      <div class="dz-field"><label>Mensaje inicial</label><input type="text" x-model="settings.quote_wa_msg" @input="markDirty()" placeholder="Hola, quiero cotizar:"></div>
      <label class="dz-switch-row"><span>Ocultar precios</span><input type="checkbox" :checked="settings.quote_price_display==='hide'" @change="setValue('quote_price_display',$event.target.checked?'hide':'show')"></label>
    </div>
  </div>

  {{-- ── PAGOS ── --}}
  <div x-show="t==='pagos'" class="dz-tab-body">
    <div class="dz-field-group">
      <div class="dz-field-group-title">Yape</div>
      <div class="dz-row-2">
        <div class="dz-field"><label>Número</label><input type="text" x-model="settings.payment_yape_number" @input="markDirty()"></div>
        <div class="dz-field"><label>Nombre</label><input type="text" x-model="settings.payment_yape_name" @input="markDirty()"></div>
      </div>
      <div class="dz-media">
        <div class="dz-media-label">QR de Yape</div>
        <div class="dz-media-drop dz-media-drop--logo" :style="settings.payment_yape_qr ? `background-image:url(${assetUrl(settings.payment_yape_qr)})` : ''"><span x-show="!settings.payment_yape_qr">Sube el QR</span></div>
        <div class="dz-media-actions">
          <label class="dz-btn dz-btn-ghost dz-btn-sm">Subir QR<input type="file" accept="image/*" class="dz-hidden" @change="uploadImage($event,'payment_yape_qr')"></label>
          <button type="button" x-show="settings.payment_yape_qr" class="dz-btn dz-btn-danger dz-btn-sm" @click="setValue('payment_yape_qr','')">Quitar</button>
        </div>
      </div>
    </div>
    <div class="dz-field-group">
      <div class="dz-field-group-title">Transferencia bancaria</div>
      @foreach(['bcp'=>'BCP','interbank'=>'Interbank','bbva'=>'BBVA','nacion'=>'Banco de la Nación','scotiabank'=>'Scotiabank'] as $bk=>$label)
        <div class="dz-field"><label>{{ $label }}</label><input type="text" x-model="settings.payment_bank_{{ $bk }}" @input="markDirty()" placeholder="N° de cuenta / CCI"></div>
      @endforeach
    </div>
  </div>

  {{-- ── ENVÍO / CHECKOUT ── --}}
  <div x-show="t==='envio'" class="dz-tab-body">
    <label class="dz-switch-row"><span>Cobrar envío</span><input type="checkbox" :checked="settings.shipping_enabled==='1'" @change="setToggle('shipping_enabled',$event.target.checked)"></label>
    <div class="dz-row-2" x-show="settings.shipping_enabled==='1'">
      <div class="dz-field"><label>Costo de envío</label><input type="number" min="0" step="0.5" x-model="settings.shipping_cost" @input="markDirty()"></div>
      <div class="dz-field"><label>Gratis desde</label><input type="number" min="0" step="1" x-model="settings.shipping_free_from" @input="markDirty()"></div>
    </div>
    <label class="dz-switch-row"><span>Pedir dirección de entrega</span><input type="checkbox" :checked="settings.require_address==='1'" @change="setToggle('require_address',$event.target.checked)"></label>
  </div>
</div>

{{-- Inspector: Beneficios (barra de confianza) --}}
<div class="dz-tab-body">
  <div class="dz-field">
    <label>Título de la sección</label>
    <input type="text" x-model="settings.trust_section_title" @input="markDirty()" placeholder="Compra con confianza">
  </div>
  <div class="dz-field">
    <label>Diseño de la sección</label>
    <div class="dz-seg">
      <template x-for="s in ['cards','compact','icons-top','tiles','band','outline','stripe','inline']" :key="s">
        <button type="button" :class="(settings.trust_section_style||'cards')===s && 'on'" @click="setValue('trust_section_style',s)" x-text="{cards:'Tarjetas',compact:'Compacto','icons-top':'Iconos arriba',tiles:'Iconos de color',band:'Banda de color',outline:'Bordes de color',stripe:'Franja lateral',inline:'Línea minimal'}[s]"></button>
      </template>
    </div>
  </div>
  <label class="dz-switch-row"><span>Carrusel en móvil</span><input type="checkbox" :checked="settings.trust_mobile_carousel==='1'" @change="setToggle('trust_mobile_carousel',$event.target.checked)"></label>
  <div class="dz-field-group">
    <div class="dz-field-group-title">Beneficios (4)</div>
    @foreach(range(1,4) as $i)
      <div class="dz-row-2">
        <div class="dz-field"><label>Ícono {{ $i }}</label>
          <select x-model="settings.trust_icon_{{ $i }}" @change="markDirty()">
            <option value="">Automático (según el texto)</option>
            @foreach(['truck' => 'Camión / envío', 'store' => 'Tienda', 'clock' => 'Reloj / express', 'shield' => 'Escudo / seguridad', 'warranty' => 'Sello de garantía', 'support' => 'Soporte / audífonos', 'card' => 'Tarjeta / pago', 'check' => 'Check', 'sparkles' => 'Destellos', 'gift' => 'Regalo', 'box' => 'Caja / stock', 'percent' => 'Descuento %', 'pin' => 'Ubicación'] as $iv => $il)
            <option value="{{ $iv }}">{{ $il }}</option>
            @endforeach
          </select>
        </div>
        <div class="dz-field"><label>Texto {{ $i }}</label><input type="text" x-model="settings.trust_text_{{ $i }}" @input="markDirty()" placeholder="Envío rápido"></div>
      </div>
    @endforeach
  </div>
</div>

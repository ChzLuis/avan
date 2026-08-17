{{-- Inspector: Anuncios promocionales --}}
<div class="dz-tab-body">
  <div class="dz-field">
    <label>Título de la sección</label>
    <input type="text" x-model="settings.promo_section_title" @input="markDirty()" placeholder="Promociones">
  </div>
  <div class="dz-field">
    <label>Formato</label>
    <div class="dz-seg">
      <button type="button" :class="(settings.promo_style||'slider')==='slider' && 'on'" @click="setValue('promo_style','slider')">Slider grande</button>
      <button type="button" :class="(settings.promo_style||'slider')==='grid' && 'on'" @click="setValue('promo_style','grid')">Cuadrícula</button>
    </div>
  </div>
  <label class="dz-switch-row" x-show="(settings.promo_style||'slider')==='slider'"><span>Pasar solo automáticamente</span><input type="checkbox" :checked="settings.promo_autoplay!=='0'" @change="setToggle('promo_autoplay',$event.target.checked)"></label>
  @foreach(range(1,3) as $i)
    <div class="dz-field-group">
      <div class="dz-field-group-title">Anuncio {{ $i }}</div>
      <div class="dz-field"><label>Título</label><input type="text" x-model="settings.promo_title_{{ $i }}" @input="markDirty()" placeholder="Ofertas de temporada"></div>
      <div class="dz-field"><label>Subtítulo</label><input type="text" x-model="settings.promo_subtitle_{{ $i }}" @input="markDirty()" placeholder="Hasta 30% de descuento"></div>
    </div>
  @endforeach
  <p class="dz-insp-note">Las imágenes de cada anuncio (PC y móvil) se suben en el constructor → sección Inicio → Anuncios.</p>
</div>

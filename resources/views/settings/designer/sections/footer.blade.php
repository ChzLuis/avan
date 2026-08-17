{{-- Inspector: Pie de página — Fase C. Sacado de "Sistema".
     Usa los name= canónicos footer_*. --}}
<div class="dz-tabs" x-data="{ t:'contenido' }">
  <div class="dz-tabs-nav">
    <button type="button" :class="t==='contenido' && 'on'" @click="t='contenido'">Contenido</button>
    <button type="button" :class="t==='columnas' && 'on'" @click="t='columnas'">Columnas</button>
    <button type="button" :class="t==='diseno' && 'on'" @click="t='diseno'">Diseño</button>
  </div>

  {{-- ── CONTENIDO ── --}}
  <div x-show="t==='contenido'" class="dz-tab-body">
    <div class="dz-field">
      <label>Descripción de la marca</label>
      <textarea x-model="settings.footer_tagline" @input="markDirty()" placeholder="Una frase sobre tu tienda"></textarea>
    </div>
    <div class="dz-field-group">
      <div class="dz-field-group-title">Contacto</div>
      <div class="dz-field"><label>Teléfono</label><input type="text" x-model="settings.contact_phone" @input="markDirty()"></div>
      <div class="dz-field"><label>Correo</label><input type="text" x-model="settings.contact_email" @input="markDirty()"></div>
      <div class="dz-field"><label>Horario</label><input type="text" x-model="settings.business_hours" @input="markDirty()" placeholder="Lun–Sáb 9:00–18:00"></div>
    </div>
    <div class="dz-field">
      <label>Copyright</label>
      <input type="text" x-model="settings.footer_copyright" @input="markDirty()" placeholder="© 2026 Mi Tienda">
    </div>
  </div>

  {{-- ── COLUMNAS / SECCIONES ── --}}
  <div x-show="t==='columnas'" class="dz-tab-body">
    <p class="dz-insp-note">Qué bloques aparecen en el pie de página.</p>
    <div class="dz-check-list">
      <label class="dz-switch-row"><span>Beneficios (envío, pago…)</span><input type="checkbox" :checked="settings.footer_show_benefits!=='0'" @change="setToggle('footer_show_benefits',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Categorías</span><input type="checkbox" :checked="settings.footer_show_categories!=='0'" @change="setToggle('footer_show_categories',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Dirección</span><input type="checkbox" :checked="settings.footer_show_address!=='0'" @change="setToggle('footer_show_address',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Redes sociales</span><input type="checkbox" :checked="settings.footer_show_social!=='0'" @change="setToggle('footer_show_social',$event.target.checked)"></label>
      <label class="dz-switch-row"><span>Newsletter</span><input type="checkbox" :checked="settings.footer_show_newsletter==='1'" @change="setToggle('footer_show_newsletter',$event.target.checked)"></label>
    </div>
    <div class="dz-field-group" x-show="settings.footer_show_newsletter==='1'">
      <div class="dz-field-group-title">Newsletter</div>
      <div class="dz-field"><label>Título</label><input type="text" x-model="settings.footer_newsletter_title" @input="markDirty()" placeholder="Suscríbete"></div>
      <div class="dz-field"><label>URL del formulario</label><input type="text" x-model="settings.footer_newsletter_url" @input="markDirty()"></div>
    </div>
  </div>

  {{-- ── DISEÑO ── --}}
  <div x-show="t==='diseno'" class="dz-tab-body">
    <div class="dz-row-2">
      <div class="dz-field"><label>Fondo del pie</label><input type="color" x-model="settings.footer_bg_color" @input="markDirty()"></div>
      <div class="dz-field"><label>Diseño del pie</label>
        <select x-model="settings.footer_style" @change="markDirty()">
          <option value="classic">Oscuro clásico</option>
          <option value="light">Claro elegante</option>
          <option value="accent">Color de marca</option>
          <option value="minimal">Compacto centrado</option>
        </select>
      </div>
      <div class="dz-field"><label>Texto</label><input type="color" x-model="settings.footer_text_color" @input="markDirty()"></div>
    </div>
    <div class="dz-field"><label>Altura del logo del pie</label><input type="number" min="20" max="120" x-model="settings.footer_logo_height" @input="markDirty()"><small>px</small></div>
  </div>
</div>

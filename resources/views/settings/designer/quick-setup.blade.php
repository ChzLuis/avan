{{-- Configuración rápida: asistente de 4 pasos (spec §1.A).
     Genera una tienda funcional en minutos. Sólo campos esenciales. --}}
<div class="dz-quick" x-show="quickOpen" x-cloak
     x-data="quickSetup({ saveUrl: @js(route('settings.design.update')), csrf: @js(csrf_token()) })">
  <div class="dz-quick-overlay"></div>
  <div class="dz-quick-modal">
    <div class="dz-quick-head">
      <div class="dz-quick-steps">
        <template x-for="(label,i) in stepLabels" :key="i">
          <div class="dz-quick-step" :class="[i===step && 'is-active', i<step && 'is-done']">
            <span class="dz-quick-step-n" x-text="i<step ? '✓' : (i+1)"></span>
            <span x-text="label"></span>
          </div>
        </template>
      </div>
      <button type="button" class="dz-quick-close" @click="quickOpen=false" aria-label="Cerrar">✕</button>
    </div>

    <div class="dz-quick-body">
      {{-- Paso 1: Negocio y plantilla --}}
      <div x-show="step===0" class="dz-quick-pane">
        <h2>¿Qué vendes?</h2>
        <p>Elige tu rubro y te recomendamos una plantilla y colores.</p>
        <div class="dz-rubro-grid">
          <template x-for="r in rubros" :key="r.id">
            <button type="button" class="dz-rubro" :class="form.rubro===r.id && 'is-active'" @click="pickRubro(r)">
              <span class="dz-rubro-ico" x-html="rubroIcons[r.icon]"></span>
              <span x-text="r.label"></span>
            </button>
          </template>
        </div>
      </div>

      {{-- Paso 2: Identidad --}}
      <div x-show="step===1" class="dz-quick-pane">
        <h2>Tu identidad</h2>
        <p>Sube tu logo y elige tus colores. Puedes cambiarlo después.</p>
        <div class="dz-media" style="max-width:260px">
          <div class="dz-media-label">Logo</div>
          <div class="dz-media-drop dz-media-drop--logo" :style="form.logo_url ? `background-image:url(${form.logo_url})` : ''"><span x-show="!form.logo_url">Sube tu logo</span></div>
          <label class="dz-btn dz-btn-ghost dz-btn-sm">Subir logo<input type="file" accept="image/*" class="dz-hidden" @change="uploadLogo($event)"></label>
        </div>
        <div class="dz-quick-palettes">
          <div class="dz-media-label">Paleta sugerida</div>
          <div class="dz-palette-row">
            <template x-for="pal in palettes" :key="pal.name">
              <button type="button" class="dz-palette" :class="form.primary_color===pal.primary && 'is-active'" @click="form.primary_color=pal.primary;form.secondary_color=pal.secondary">
                <span :style="`background:${pal.primary}`"></span>
                <span :style="`background:${pal.secondary}`"></span>
              </button>
            </template>
          </div>
        </div>
      </div>

      {{-- Paso 3: Estructura del Inicio --}}
      <div x-show="step===2" class="dz-quick-pane">
        <h2>Secciones de tu inicio</h2>
        <p>Activa lo que quieras mostrar. Todo es opcional y editable luego.</p>
        <div class="dz-quick-sections">
          <template x-for="s in quickSections" :key="s.key">
            <label class="dz-switch-row"><span x-text="s.label"></span><input type="checkbox" x-model="s.on"></label>
          </template>
        </div>
      </div>

      {{-- Paso 4: Venta y publicación --}}
      <div x-show="step===3" class="dz-quick-pane">
        <h2>¿Cómo vendes?</h2>
        <div class="dz-field"><label>WhatsApp de tu tienda</label><input type="text" x-model="form.quote_whatsapp" placeholder="987654321"></div>
        <div class="dz-field">
          <label>Modo de venta</label>
          <div class="dz-seg">
            <button type="button" :class="form.store_mode==='direct' && 'on'" @click="form.store_mode='direct'">Venta directa</button>
            <button type="button" :class="form.store_mode==='quote_only' && 'on'" @click="form.store_mode='quote_only'">Cotización</button>
          </div>
        </div>
        <p class="dz-insp-note">Al terminar, tu tienda queda lista con estos ajustes. Podrás personalizar todo en el Diseñador.</p>
      </div>
    </div>

    <div class="dz-quick-foot">
      <button type="button" class="dz-btn dz-btn-ghost" x-show="step>0" @click="step--">Atrás</button>
      <div style="flex:1"></div>
      <button type="button" class="dz-btn dz-btn-primary" x-show="step<3" @click="next()" x-text="step===0 && !form.rubro ? 'Elige un rubro' : 'Continuar'"></button>
      <button type="button" class="dz-btn dz-btn-primary" x-show="step===3" @click="finish()" :disabled="saving" x-text="saving ? 'Guardando…' : 'Crear mi tienda'"></button>
    </div>
  </div>
</div>

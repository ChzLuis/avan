{{-- Cara CONFIGURACIÓN (/bixoadmin): shell del panel.
     Base adoptada de ARIN (deriva reconciliada 2026-08-29). --}}
<x-app-layout>
<x-slot name="slot">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,700;0,800;0,900;1,800;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@php
    /**
     * Datos de la tienda. Todo sale del proyecto activo: esta pantalla es de
     * BIXO y la usa cualquier negocio, no hay nada fijo de ninguna tienda.
     */
    $ajuste = fn ($key, $default = '') => $storefrontContext->setting($key, $default) ?: $default;

    // Color valido o el neutro del storefront. Un hex a medio escribir pinta
    // negro y el usuario cree que rompio algo.
    $color = fn ($v, $fb) => (is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v)) ? $v : $fb;

    $primary   = $color($ajuste('primary_color'),   '#4f46e5');
    $secondary = $color($ajuste('secondary_color'), '#6366f1');
    $accent    = $color($ajuste('accent_color'),    $primary);

    // El Constructor guarda el logo como header_logo_url; leyendo solo
    // logo_url las tiendas nuevas aparecian sin logo.
    $logoRaw = $ajuste('header_logo_url') ?: ($ajuste('logo_url') ?: ($project->logo_url ?? ''));
    $logo    = $logoRaw
        ? (str_starts_with($logoRaw, 'http') || str_starts_with($logoRaw, '/')
            ? $logoRaw
            : asset('storage/' . ltrim($logoRaw, '/')))
        : null;

    $isRestaurant  = \App\Support\BusinessTerms::usaMesas($project);
    $days          = ['lun'=>'Lunes','mar'=>'Martes','mie'=>'Miércoles','jue'=>'Jueves','vie'=>'Viernes','sab'=>'Sábado','dom'=>'Domingo'];
    $savedSchedule = json_decode($ajuste('qr_schedule', '{}'), true) ?: [];
@endphp

<div class="qrx" x-data="qrStudio()" x-init="arrancar()">

  {{-- ══════════ CABECERA ══════════ --}}
  <header class="qrx-top">
    <div>
      <h1>Código QR y material</h1>
      <p>Crea el QR de tu tienda y un flyer listo para imprimir o compartir.</p>
    </div>
    <div class="qrx-estado" aria-live="polite">
      <span x-show="estado==='pendiente'" class="es-gris">Cambios sin guardar…</span>
      <span x-show="estado==='guardando'" class="es-gris">Guardando…</span>
      <span x-show="estado==='guardado'" x-cloak class="es-ok">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        Guardado
      </span>
      <span x-show="estado==='error'" x-cloak class="es-mal">No se pudo guardar</span>
      <span x-show="!estado" class="es-tenue">Los cambios se guardan solos</span>
    </div>
  </header>

  <main class="qrx-main">

    {{-- ══════════ IZQUIERDA · VISTA PREVIA ══════════ --}}
    <section class="qrx-preview">
      <div class="qrx-sticky">

        <div class="qrx-seg" role="tablist">
          <button role="tab" :aria-selected="vista==='qr'"    :class="vista==='qr'?'on':''"    @click="vista='qr';   pintar()">Código QR</button>
          <button role="tab" :aria-selected="vista==='flyer'" :class="vista==='flyer'?'on':''" @click="vista='flyer';pintar()">Flyer</button>
        </div>

        <div class="qrx-lienzo" :class="vista==='flyer' ? 'es-flyer' : 'es-qr'">
          {{-- Un unico canvas para ver y para descargar: lo que se ve es
               exactamente el archivo que se baja, no una maqueta parecida. --}}
          <canvas id="qrx-canvas" x-ref="lienzo"></canvas>
          <div x-show="cargando" class="qrx-cargando" x-cloak><span></span></div>
          {{-- Un fallo de red dejaba el lienzo en blanco sin explicar nada --}}
          <div x-show="fallo" class="qrx-fallo" x-cloak>
            <p x-text="fallo"></p>
            <button type="button" @click="reintentar()">Reintentar</button>
          </div>
        </div>

        @if(!$isPublicUrl)
        <p class="qrx-alerta">
          Tu tienda todavía no tiene una dirección pública. Configura un dominio para que el QR funcione fuera de tu red.
        </p>
        @endif
        <p x-show="avisoContraste" x-cloak class="qrx-alerta" x-text="avisoContraste"></p>

        <div class="qrx-rapidos">
          <button class="qrx-btn" @click="descargar(vista==='qr'?'qr':'flyer')" :disabled="!publicUrl">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            Descargar
          </button>
          <button class="qrx-btn-sec" @click="compartir()" :disabled="!publicUrl">Compartir</button>
          <button class="qrx-btn-sec" @click="probar()" :disabled="!publicUrl">Probar</button>
        </div>
      </div>
    </section>

    {{-- ══════════ DERECHA · CONFIGURACIÓN ══════════ --}}
    <section class="qrx-config">

      {{-- 1 · Información --}}
      <div class="qrx-card">
        <h2>Dirección de tu tienda</h2>
        <p class="qrx-nota">Es la página a la que llega quien escanea el código.</p>
        <div class="qrx-url">
          <input type="text" readonly :value="publicUrl || 'Sin dominio público configurado'">
          <button class="qrx-btn-sec" @click="copiarUrl()" :disabled="!publicUrl">Copiar</button>
          <button class="qrx-btn-sec" @click="probar()" :disabled="!publicUrl">Abrir</button>
        </div>
      </div>

      {{-- 2 · Plantilla --}}
      <div class="qrx-card">
        <h2>Estilo</h2>
        <p class="qrx-nota">Cambia el diseño completo de la pieza.</p>
        <div class="qrx-plantillas">
          <template x-for="p in plantillas" :key="p.id">
            <button class="qrx-tpl" :class="form.template===p.id?'on':''" @click="usarPlantilla(p.id)">
              <span class="mini"><canvas :data-tpl-mini="p.id"></canvas></span>
              <span x-text="p.label"></span>
            </button>
          </template>
        </div>
      </div>

      {{-- 3 · Marca --}}
      <div class="qrx-card">
        <h2>Tu marca</h2>
        <label class="qrx-check">
          <input type="checkbox" x-model="form.showLogo">
          <span>Mostrar el logo{{ $logo ? '' : ' (esta tienda aún no tiene logo cargado)' }}</span>
        </label>

        <div x-show="form.showLogo && tieneLogo" class="qrx-sub" x-cloak>
          <span class="qrx-lab">Tamaño del logo</span>
          <div class="qrx-seg chico">
            <button :class="form.logoSize==70?'on':''"  @click="form.logoSize=70">Pequeño</button>
            <button :class="form.logoSize==110?'on':''" @click="form.logoSize=110">Normal</button>
            <button :class="form.logoSize==160?'on':''" @click="form.logoSize=160">Grande</button>
          </div>
        </div>

        <div class="qrx-colores">
          <div class="qrx-campo">
            <span class="qrx-lab">Color principal</span>
            <div class="qrx-color">
              <input type="color" x-model="form.header">
              <input type="text" maxlength="7" spellcheck="false" :value="form.header.toUpperCase()" @input="hex($event,'header')" @blur="$event.target.value=form.header.toUpperCase()">
            </div>
          </div>
          <div class="qrx-campo">
            <span class="qrx-lab">Color del texto</span>
            <div class="qrx-color">
              <input type="color" x-model="form.textColor">
              <input type="text" maxlength="7" spellcheck="false" :value="form.textColor.toUpperCase()" @input="hex($event,'textColor')" @blur="$event.target.value=form.textColor.toUpperCase()">
            </div>
          </div>
        </div>

        <button class="qrx-link" @click="coloresDeMarca()">Usar los colores de mi tienda</button>
      </div>

      {{-- 4 · Textos --}}
      <div class="qrx-card">
        <h2>Textos</h2>
        <label class="qrx-campo">
          <span class="qrx-lab">Título</span>
          <input type="text" x-model="form.topText" maxlength="120" placeholder="Escanea y visita nuestra tienda">
        </label>
        <label class="qrx-campo">
          <span class="qrx-lab">Subtítulo</span>
          <input type="text" x-model="form.subtitle" maxlength="140" placeholder="Descubre todos nuestros productos">
        </label>
        <label class="qrx-campo">
          <span class="qrx-lab">Texto inferior</span>
          <input type="text" x-model="form.bottomText" maxlength="120" placeholder="Escanea y mira nuestros productos">
        </label>
        <div class="qrx-checks">
          <label class="qrx-check"><input type="checkbox" x-model="form.showName"><span>Nombre del negocio</span></label>
          <label class="qrx-check"><input type="checkbox" x-model="form.showUrl"><span>Dirección web</span></label>
          <label class="qrx-check"><input type="checkbox" x-model="form.showBixo"><span>Firma de Eskala Group</span></label>
        </div>
      </div>

      {{-- 5 · Flyer --}}
      <div class="qrx-card">
        <h2>Flyer</h2>
        <p class="qrx-nota">Formato de la pieza que vas a descargar.</p>
        <div class="qrx-formatos">
          <template x-for="f in formatos" :key="f.id">
            <button class="qrx-fmt" :class="form.format===f.id?'on':''" @click="form.format=f.id; if(vista==='flyer') pintar()">
              <span class="marco" :style="`aspect-ratio:${f.w}/${f.h}`"></span>
              <b x-text="f.label"></b>
              <small x-text="f.w+'×'+f.h"></small>
            </button>
          </template>
        </div>

        <div class="qrx-colores">
          <label class="qrx-campo">
            <span class="qrx-lab">Fondo</span>
            <select x-model="form.background">
              <option value="solido">Color sólido</option>
              <option value="degradado">Degradado</option>
            </select>
          </label>
          <div class="qrx-campo">
            <span class="qrx-lab">Suavidad de la banda <b x-text="form.tinte + '%'"></b></span>
            <input type="range" min="0" max="90" step="5" x-model.number="form.tinte">
          </div>
        </div>

        <div class="qrx-colores">
          <div class="qrx-campo">
            <span class="qrx-lab">Fondo de la pieza</span>
            <div class="qrx-color">
              <input type="color" x-model="form.cardColor">
              <input type="text" maxlength="7" spellcheck="false" :value="form.cardColor.toUpperCase()" @input="hex($event,'cardColor')">
            </div>
          </div>
          <div class="qrx-campo">
            <span class="qrx-lab">Texto del cuerpo</span>
            <div class="qrx-color">
              <input type="color" x-model="form.bodyColor">
              <input type="text" maxlength="7" spellcheck="false" :value="form.bodyColor.toUpperCase()" @input="hex($event,'bodyColor')">
            </div>
          </div>
        </div>

        <div class="qrx-campo">
          <span class="qrx-lab">Tamaño del código en el flyer</span>
          <div class="qrx-seg chico">
            <button :class="form.qrScale==88?'on':''"  @click="form.qrScale=88">Pequeño</button>
            <button :class="form.qrScale==100?'on':''" @click="form.qrScale=100">Normal</button>
            <button :class="form.qrScale==112?'on':''" @click="form.qrScale=112">Grande</button>
          </div>
        </div>
        <div class="qrx-checks">
          <label class="qrx-check"><input type="checkbox" x-model="form.showTrama"><span>Decoración de fondo en la cabecera</span></label>
          <label class="qrx-check"><input type="checkbox" x-model="form.showBrackets"><span>Escuadras en las esquinas del código</span></label>
        </div>
        <label class="qrx-check"><input type="checkbox" x-model="form.showBenefits"><span>Mostrar beneficios</span></label>
        <div class="qrx-campo">
          <span class="qrx-lab">Marcas que vendes</span>
          <input type="text" maxlength="160" placeholder="Bosch, Truper, Stanley"
                 :value="form.brands.join(', ')"
                 @input="form.brands = $event.target.value.split(',').map(m => m.trim()).filter(Boolean).slice(0,8)">
          <small class="qrx-ayuda">Separadas por comas. Salen como sellos en el volante; hasta 8.</small>
        </div>
        <label class="qrx-check"><input type="checkbox" x-model="form.showBrands"><span>Mostrar las marcas</span></label>
        <div x-show="form.showBenefits" class="qrx-sub" x-cloak>
          <template x-for="(b,i) in form.benefits" :key="i">
            <div class="qrx-benef-fila">
              <select x-model="form.icons[i]" class="qrx-icono">
                <option value="bolsa">🛍</option>
                <option value="corazon">♥</option>
                <option value="oferta">%</option>
                <option value="envio">🚚</option>
                <option value="check">✓</option>
              </select>
              <input type="text" class="qrx-benef" maxlength="40" x-model="form.benefits[i]" :placeholder="'Beneficio '+(i+1)">
            </div>
          </template>
          <p class="qrx-nota">Máximo 4. Deja uno vacío para ocultarlo.</p>
        </div>
      </div>

      {{-- 6 · Código QR (avanzado) --}}
      <details class="qrx-card qrx-avanzado">
        <summary><h2>Ajustes del código</h2><span>Tamaño, colores y lectura</span></summary>
        <div class="qrx-colores">
          <div class="qrx-campo">
            <span class="qrx-lab">Color del código</span>
            <div class="qrx-color">
              <input type="color" x-model="form.fg">
              <input type="text" maxlength="7" spellcheck="false" :value="form.fg.toUpperCase()" @input="hex($event,'fg')" @blur="$event.target.value=form.fg.toUpperCase()">
            </div>
          </div>
          <div class="qrx-campo">
            <span class="qrx-lab">Fondo del código</span>
            <div class="qrx-color">
              <input type="color" x-model="form.bg">
              <input type="text" maxlength="7" spellcheck="false" :value="form.bg.toUpperCase()" @input="hex($event,'bg')" @blur="$event.target.value=form.bg.toUpperCase()">
            </div>
          </div>
        </div>

        <label class="qrx-campo">
          <span class="qrx-lab">Margen del código <b x-text="form.margin + ' módulos'"></b></span>
          <input type="range" min="1" max="8" step="1" x-model.number="form.margin">
          <span class="qrx-nota">El borde en blanco alrededor. Menos de 1 dificulta la lectura.</span>
        </label>

        <label class="qrx-check">
          <input type="checkbox" x-model="form.logoInQr" :disabled="!tieneLogo">
          <span>Poner el logo dentro del código</span>
        </label>
        <p class="qrx-nota">Se dibuja pequeño y sobre un fondo blanco, sin tapar las esquinas de lectura. El código usa corrección alta de errores, así sigue escaneando.</p>

        <label class="qrx-campo">
          <span class="qrx-lab">Calidad de descarga</span>
          <select x-model="form.quality">
            <option value="standard">Estándar</option>
            <option value="high">Alta (impresión)</option>
          </select>
        </label>
      </details>

      {{-- 7 · Descargar --}}
      <div class="qrx-card">
        <h2>Descargar y compartir</h2>
        <div class="qrx-desc">
          <button class="qrx-btn" @click="descargar('qr')" :disabled="!publicUrl">Código QR (PNG)</button>
          <button class="qrx-btn" @click="descargar('flyer')" :disabled="!publicUrl">Flyer (PNG)</button>
          <button class="qrx-btn-sec" @click="descargar('svg')" :disabled="!publicUrl">Código en SVG</button>
          <button class="qrx-btn-sec" @click="compartir()" :disabled="!publicUrl">Compartir</button>
        </div>
        <label class="qrx-campo">
          <span class="qrx-lab">Mensaje al compartir</span>
          <textarea x-model="form.shareMessage" rows="2" maxlength="500"></textarea>
        </label>
      </div>

      @if($isRestaurant)
      {{-- 8 · Mesas (se conserva tal cual: lo leen Mesas y Reservas) --}}
      <details class="qrx-card qrx-avanzado">
        <summary><h2>QR por mesa</h2><span>Configuración para restaurantes</span></summary>
        <div class="qrx-colores">
          <label class="qrx-campo"><span class="qrx-lab">Modo</span>
            <select x-model="form.mode"><option value="catalog">Catálogo</option><option value="orders">Pedidos en mesa</option></select>
          </label>
          <label class="qrx-campo"><span class="qrx-lab">Número de mesas</span>
            <input type="number" min="1" max="50" x-model.number="form.tableCount">
          </label>
          <label class="qrx-campo"><span class="qrx-lab">Recepción</span>
            <select x-model="form.reception"><option value="auto">Automática</option><option value="manual">Manual</option></select>
          </label>
          <label class="qrx-campo"><span class="qrx-lab">Cobro</span>
            <select x-model="form.payment"><option value="cashier">En caja</option><option value="waiter">En mesa</option></select>
          </label>
        </div>
        <div class="qrx-horario">
          @foreach($days as $day => $label)
            @php $hours = $savedSchedule[$day] ?? []; @endphp
            <div class="fila">
              <label><input name="qr_schedule[{{ $day }}][enabled]" value="1" type="checkbox" @checked(!empty($hours['enabled']))>{{ $label }}</label>
              <input name="qr_schedule[{{ $day }}][start]" value="{{ $hours['start'] ?? '09:00' }}" type="time">
              <span>a</span>
              <input name="qr_schedule[{{ $day }}][end]" value="{{ $hours['end'] ?? '22:00' }}" type="time">
            </div>
          @endforeach
        </div>
      </details>
      @endif

    </section>
  </main>
</div>

@include('settings.partials.qr-estilos')
@include('settings.partials.qr-composiciones')
@include('settings.partials.qr-script', [
    'baseUrl'     => $baseUrl,
    'isPublicUrl' => $isPublicUrl,
    'logo'        => $logo,
    'primary'     => $primary,
    'secondary'   => $secondary,
    'accent'      => $accent,
])

</x-slot>
</x-app-layout>

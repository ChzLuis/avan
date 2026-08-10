{{-- Etapa 2: Apariencia — unifica plantilla, marca, colores, tipografía,
     encabezado, barra superior y footer. Rápido primero; experto colapsable. --}}
<section class="bxb-stage">
    <h2>Apariencia</h2>
    <p class="bxb-stage-sub">Elige cómo se ve tu tienda. Todo se guarda en borrador y lo ves al instante en la vista previa.</p>

    {{-- Plantilla (tarjetas visuales) --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Plantilla</strong>
        <div class="bxb-tpl-grid" role="radiogroup" aria-label="Plantilla de la tienda">
            <template x-for="t in templates" :key="t.key">
                <button type="button" class="bxb-tpl" :class="(settings.catalog_template||'')===t.key&&'is-active'" @click="setSetting('catalog_template',t.key)">
                    <span class="bxb-tpl-preview" :style="'background:'+t.preview_bg">
                        <span :style="'background:'+t.preview_accent"></span><span :style="'background:'+t.preview_accent"></span><span :style="'background:'+t.preview_accent"></span>
                    </span>
                    <strong x-text="t.name"></strong>
                    <small x-text="t.short"></small>
                </button>
            </template>
        </div>
        <p class="bxb-note">Cambiar de plantilla conserva tus productos, textos y colores.</p>
    </div>

    {{-- Estructura: variantes de cabecera, footer y carrito (F1) --}}
    <style>
        .bxb-mini{margin-top:8px;height:56px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;position:relative;overflow:hidden}
        .bxb-mini i{position:absolute;display:block;background:#cbd5e1;border-radius:2px}
        .bxb-mini .mk{background:#6366f1}
        /* headers */
        .mini-headers-classic i:nth-child(1){top:6px;left:6px;width:26px;height:10px}.mini-headers-classic i:nth-child(2){top:6px;left:38%;right:26%;height:10px}.mini-headers-classic i:nth-child(3){top:6px;right:6px;width:18px;height:10px}.mini-headers-classic i:nth-child(4){bottom:8px;left:6px;right:6px;height:12px;background:#94a3b8}
        .mini-headers-compact i:nth-child(1){top:20px;left:6px;width:22px;height:9px}.mini-headers-compact i:nth-child(2){top:20px;left:34%;width:38%;height:9px}.mini-headers-compact i:nth-child(3){top:20px;right:6px;width:16px;height:9px}
        .mini-headers-centered i:nth-child(1){top:6px;left:44%;width:12%;height:12px}.mini-headers-centered i:nth-child(2){top:8px;left:8px;width:12px;height:8px}.mini-headers-centered i:nth-child(3){top:8px;right:8px;width:12px;height:8px}.mini-headers-centered i:nth-child(4){bottom:8px;left:26%;right:26%;height:8px}
        .mini-headers-triple i:nth-child(1){top:4px;left:6px;right:6px;height:6px;background:#64748b}.mini-headers-triple i:nth-child(2){top:15px;left:6px;width:18px;height:11px}.mini-headers-triple i:nth-child(3){top:15px;left:26%;right:22%;height:11px}.mini-headers-triple i:nth-child(4){top:15px;right:6px;width:12px;height:11px}.mini-headers-triple i:nth-child(5){bottom:5px;left:6px;right:6px;height:11px;background:#334155}
        /* footers */
        .mini-footers-classic i:nth-child(1){top:8px;left:6px;width:22%;height:26px}.mini-footers-classic i:nth-child(2){top:8px;left:32%;width:18%;height:34px}.mini-footers-classic i:nth-child(3){top:8px;left:54%;width:18%;height:34px}.mini-footers-classic i:nth-child(4){top:8px;right:6px;width:20%;height:34px}
        .mini-footers-simple i:nth-child(1){top:24px;left:6px;width:20px;height:8px}.mini-footers-simple i:nth-child(2){top:24px;left:35%;width:30%;height:8px}.mini-footers-simple i:nth-child(3){top:24px;right:6px;width:16%;height:8px}
        .mini-footers-minimal i:nth-child(1){top:6px;left:45%;width:10%;height:9px}.mini-footers-minimal i:nth-child(2){top:21px;left:30%;width:40%;height:7px}.mini-footers-minimal i:nth-child(3){top:34px;left:38%;width:24%;height:7px}
        .mini-footers-institutional i:nth-child(1){top:8px;left:6px;width:34%;height:34px}.mini-footers-institutional i:nth-child(2){top:8px;left:48%;width:20%;height:34px}.mini-footers-institutional i:nth-child(3){top:8px;right:6px;width:22%;height:34px}
        .mini-footers-commercial i:nth-child(1){top:4px;left:6px;right:6px;height:10px}.mini-footers-commercial i:nth-child(2){top:20px;left:6px;width:22%;height:26px}.mini-footers-commercial i:nth-child(3){top:20px;left:33%;width:18%;height:26px}.mini-footers-commercial i:nth-child(4){top:20px;left:56%;width:18%;height:26px}.mini-footers-commercial i:nth-child(5){top:20px;right:6px;width:16%;height:26px}
        .mini-footers-complete i:nth-child(1){top:4px;left:6px;right:6px;height:8px;background:#94a3b8}.mini-footers-complete i:nth-child(2){top:17px;left:6px;width:24%;height:24px}.mini-footers-complete i:nth-child(3){top:17px;left:36%;width:16%;height:24px}.mini-footers-complete i:nth-child(4){top:17px;left:57%;width:16%;height:24px}.mini-footers-complete i:nth-child(5){top:17px;right:6px;width:18%;height:24px}.mini-footers-complete i:nth-child(6){bottom:3px;left:6px;right:6px;height:6px;background:#94a3b8}
        /* carts */
        .mini-carts-classic i:nth-child(1){top:6px;right:4px;bottom:6px;width:34%}.mini-carts-classic i:nth-child(2){top:12px;right:8%;width:24%;height:7px;background:#94a3b8}
        .mini-carts-side i:nth-child(1){top:6px;right:4px;bottom:6px;width:36%;border-radius:6px 0 0 6px}.mini-carts-side i:nth-child(2){top:10px;right:7%;width:28%;height:9px}
        .mini-carts-floating i:nth-child(1){bottom:8px;right:8px;width:34px;height:14px;border-radius:8px}
        .mini-carts-page i:nth-child(1){top:6px;left:6px;right:6px;bottom:6px}.mini-carts-page i:nth-child(2){top:12px;left:12%;width:40%;height:8px;background:#94a3b8}.mini-carts-page i:nth-child(3){top:12px;right:12%;width:22%;height:20px;background:#94a3b8}
    </style>
    <div class="bxb-card">
        <strong class="bxb-card-title">Pie de página y carrito</strong>
        <div class="bxb-grid2">
            @foreach([['footers', 'footer_layout', 'Diseño del pie de página', 6], ['carts', 'cart_layout', 'Diseño del carrito', 3]] as [$slot, $key, $label, $bars])
            <div class="bxb-field"><span>{{ $label }}</span>
                <select :value="settings.{{ $key }}||'classic'" @change="setSetting('{{ $key }}',$event.target.value)">
                    @foreach(\App\Support\StorefrontLayoutPacks::options($slot) as $vKey => $vLabel)
                    @php $isExp = in_array($vKey, \App\Support\StorefrontLayoutPacks::EXPERIMENTAL[$slot] ?? [], true)
                        && !view()->exists("storefront.partials.{$slot}.{$vKey}"); @endphp
                    <option value="{{ $vKey }}" @if($isExp) disabled @endif>{{ $vLabel }}@if($isExp) — próximamente @endif</option>
                    @endforeach
                </select>
                {{-- Miniatura del esquema elegido (se actualiza en vivo) --}}
                <div class="bxb-mini" :class="'mini-{{ $slot }}-'+(settings.{{ $key }}||'classic')" aria-hidden="true">
                    @for($i = 0; $i < 6; $i++)<i></i>@endfor
                </div>
            </div>
            @endforeach
        </div>
        <p class="bxb-note">Cada variante cambia la composición real (no solo colores). Se aplica en borrador: revísala en la vista previa antes de publicar. "Clásica" mantiene el diseño actual.</p>
    </div>

    {{-- Tema visual: tokens que cambian la personalidad completa --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Tema visual</strong>
        <div class="bxb-grid2">
            <label class="bxb-field">Personalidad de la tienda
                <select :value="settings.theme_preset||'classic'" @change="setSetting('theme_preset',$event.target.value)">
                    @foreach(\App\Support\StorefrontThemePresets::options() as $tpKey => $tpLabel)
                    <option value="{{ $tpKey }}">{{ $tpLabel }}</option>
                    @endforeach
                </select>
            </label>
            <label class="bxb-field">Estilo de las tarjetas de producto
                <select :value="settings.product_card_style||'classic'" @change="setSetting('product_card_style',$event.target.value)">
                    <option value="classic">Clásico</option>
                    <option value="tech">Tecnológico (línea de acento)</option>
                    <option value="soft">Suave y redondeado</option>
                    <option value="elegant">Elegante minimalista</option>
                    <option value="contrast">Alto contraste</option>
                </select>
            </label>
        </div>
        <p class="bxb-note">El tema cambia fondos, sombras, bordes y jerarquía de toda la tienda; tus colores de marca se respetan. Ideal para que dos tiendas con la misma plantilla se vean totalmente distintas.</p>
    </div>

    {{-- Diseño global de secciones (antes en el Diseñador clásico) --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Diseño de las secciones</strong>
        <div class="bxb-grid2">
            <label class="bxb-field">Estilo general
                <select :value="settings.section_style_preset||'modern'" @change="setSetting('section_style_preset',$event.target.value)">
                    <option value="modern">Moderno (actual)</option>
                    <option value="minimal">Minimal</option>
                    <option value="commerce">Comercial (ecommerce, denso)</option>
                </select>
            </label>
            <label class="bxb-field">Espaciado entre secciones
                <select :value="settings.section_spacing||'comfortable'" @change="setSetting('section_spacing',$event.target.value)">
                    <option value="comfortable">Amplio (mucho aire)</option>
                    <option value="compact">Compacto</option>
                    <option value="dense">Ecommerce (denso, sin huecos)</option>
                </select>
            </label>
            <label class="bxb-field">Alineación de los títulos
                <select :value="settings.section_heading_align||'left'" @change="setSetting('section_heading_align',$event.target.value)">
                    <option value="left">A la izquierda</option>
                    <option value="center">Centrados</option>
                </select>
            </label>
            <label class="bxb-field">Fondos de las secciones
                <select :value="settings.section_background_mode||'alternate'" @change="setSetting('section_background_mode',$event.target.value)">
                    <option value="alternate">Alternados (blanco y suave)</option>
                    <option value="white">Todo blanco</option>
                    <option value="soft">Todo suave</option>
                </select>
            </label>
        </div>
        <div class="bxb-grid2" style="margin-top:6px">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.section_card_shadow||'1')!=='0'" @change="setSetting('section_card_shadow',$event.target.checked?'1':'0')"> Sombras en las tarjetas</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.section_show_dividers||'1')!=='0'" @change="setSetting('section_show_dividers',$event.target.checked?'1':'0')"> Separadores entre secciones</label>
        </div>
        <p class="bxb-note">Estos ajustes aplican a toda la portada. El Tema visual define la personalidad base; aquí la afinas.</p>
    </div>

    {{-- Marca: logo + favicon + colores --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Tu marca</strong>
        <div class="bxb-grid2">
            <div class="bxb-field"><span>Logo</span>
                <div class="bxb-media">
                    <span class="bxb-media-box" :style="settings.logo_url ? 'background-image:url('+assetUrl(settings.logo_url)+')' : ''"><template x-if="!settings.logo_url"><em>Sube tu logo</em></template></span>
                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'logo_url')"></label>
                </div>
            </div>
            <div class="bxb-field"><span>Favicon (ícono de pestaña)</span>
                <div class="bxb-media">
                    <span class="bxb-media-box bxb-media-box--sq" :style="settings.favicon_url ? 'background-image:url('+assetUrl(settings.favicon_url)+')' : ''"><template x-if="!settings.favicon_url"><em>32×32</em></template></span>
                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'favicon_url')"></label>
                </div>
            </div>
        </div>
        <div class="bxb-grid2">
            <label class="bxb-field">Color principal
                <span class="bxb-color-row"><input type="color" :value="settings.primary_color||'#2563eb'" @input="setSetting('primary_color',$event.target.value)" aria-label="Color principal"><code x-text="settings.primary_color||'#2563eb'"></code></span>
            </label>
            <label class="bxb-field">Color secundario
                <span class="bxb-color-row"><input type="color" :value="settings.secondary_color||'#0f172a'" @input="setSetting('secondary_color',$event.target.value)" aria-label="Color secundario"><code x-text="settings.secondary_color||'#0f172a'"></code></span>
            </label>
            <label class="bxb-field">Color de acento (detalles, íconos, etiquetas)
                <span class="bxb-color-row"><input type="color" :value="settings.accent_color||settings.primary_color||'#2563eb'" @input="setSetting('accent_color',$event.target.value)" aria-label="Color de acento"><code x-text="settings.accent_color||'auto'"></code><button type="button" class="bxb-link" x-show="settings.accent_color" @click="setSetting('accent_color','')">Auto</button></span>
            </label>
        </div>
        <div class="bxb-field"><span>Paletas sugeridas</span>
            <div class="bxb-palettes">
                <template x-for="pal in palettes" :key="pal.name">
                    <button type="button" class="bxb-palette" :class="settings.primary_color===pal.p&&'is-active'" :aria-label="'Paleta '+pal.name" @click="batchSet({primary_color:pal.p,secondary_color:pal.s})">
                        <span :style="'background:'+pal.p"></span><span :style="'background:'+pal.s"></span>
                    </button>
                </template>
                <button type="button" class="bxb-btn" x-show="settings.logo_url" @click="paletteFromLogo()">🎨 Generar desde el logo</button>
            </div>
        </div>
    </div>

    {{-- Tipografía y botones --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Tipografía y botones</strong>
        @php($fontCombos = [
            ['k' => 'moderna',     'l' => 'Moderna',     't' => 'Inter',            'b' => 'Inter'],
            ['k' => 'corporativa', 'l' => 'Corporativa', 't' => 'Montserrat',       'b' => 'Inter'],
            ['k' => 'editorial',   'l' => 'Editorial',   't' => 'Playfair Display', 'b' => 'Lato'],
            ['k' => 'tecnologica', 'l' => 'Tecnológica', 't' => 'Space Grotesk',    'b' => 'Inter'],
            ['k' => 'amigable',    'l' => 'Amigable',    't' => 'Nunito',           'b' => 'Nunito'],
            ['k' => 'infantil',    'l' => 'Infantil',    't' => 'Baloo 2',          'b' => 'Nunito'],
        ])
        <div class="bxb-field"><span>Combinaciones recomendadas</span>
            <div class="bxb-grid2" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr))">
                @foreach($fontCombos as $fc)
                <button type="button" class="bxb-tpl"
                        :class="(settings.font_title||settings.font||'Inter')==='{{ $fc['t'] }}' && (settings.font_body||settings.font||'Inter')==='{{ $fc['b'] }}' && 'is-active'"
                        @click="batchSet({font_title:'{{ $fc['t'] }}',font_body:'{{ $fc['b'] }}'})">
                    <strong style="font-family:'{{ $fc['t'] }}',sans-serif;font-size:15px">{{ $fc['l'] }}</strong>
                    <small style="font-family:'{{ $fc['b'] }}',sans-serif">Título {{ $fc['t'] }} · texto {{ $fc['b'] }}</small>
                </button>
                @endforeach
            </div>
        </div>
        <details class="bxb-advanced">
        <summary>Personalizar tipografías</summary>
        @php($builderFonts = [
            'Inter'              => 'Inter — Moderna y limpia',
            'Poppins'            => 'Poppins — Geométrica',
            'Jost'               => 'Jost — Editorial',
            'Lato'               => 'Lato — Amigable',
            'Raleway'            => 'Raleway — Elegante',
            'Playfair Display'   => 'Playfair Display — Serif clásica',
            'Cormorant Garamond' => 'Cormorant — Lujo editorial',
            'Montserrat'         => 'Montserrat — Corporativa',
            'Nunito'             => 'Nunito — Redondeada',
            'Oswald'             => 'Oswald — Bold condensada',
            'Space Grotesk'      => 'Space Grotesk — Tecnológica',
            'Baloo 2'            => 'Baloo 2 — Divertida',
        ])
            <div class="bxb-grid2">
                <label class="bxb-field">Letra de los títulos
                    <select :value="settings.font_title||settings.font||'Inter'" @change="setSetting('font_title',$event.target.value)">
                        @foreach($builderFonts as $fk => $fl)
                        <option value="{{ $fk }}" style="font-family:'{{ $fk }}',sans-serif">{{ $fl }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="bxb-field">Letra de los textos
                    <select :value="settings.font_body||settings.font||'Inter'" @change="setSetting('font_body',$event.target.value)">
                        @foreach($builderFonts as $fk => $fl)
                        <option value="{{ $fk }}" style="font-family:'{{ $fk }}',sans-serif">{{ $fl }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </details>
        <div class="bxb-grid2">
            <div class="bxb-field"><span>Forma de los botones</span>
                <div class="bxb-seg" role="radiogroup" aria-label="Forma de botones">
                    <button type="button" :class="(settings.btn_shape||'rounded')==='rounded'&&'on'" @click="setSetting('btn_shape','rounded')">Redondeados</button>
                    <button type="button" :class="settings.btn_shape==='pill'&&'on'" @click="setSetting('btn_shape','pill')">Píldora</button>
                    <button type="button" :class="settings.btn_shape==='square'&&'on'" @click="setSetting('btn_shape','square')">Rectos</button>
                </div>
            </div>
            <div class="bxb-field"><span>Radio de bordes (botones y tarjetas)</span>
                <div class="bxb-seg" role="radiogroup" aria-label="Radio de bordes">
                    <button type="button" :class="settings.border_radius==='sharp'&&'on'" @click="setSetting('border_radius','sharp')">Cuadrado</button>
                    <button type="button" :class="(settings.border_radius||'rounded')==='rounded'&&'on'" @click="setSetting('border_radius','rounded')">Redondeado</button>
                    <button type="button" :class="settings.border_radius==='pill'&&'on'" @click="setSetting('border_radius','pill')">Píldora</button>
                </div>
            </div>
        </div>
        <label class="bxb-switch"><input type="checkbox" :checked="(settings.btn_show_icon??'1')!=='0'" @change="setSetting('btn_show_icon',$event.target.checked?'1':'0')"> Mostrar ícono de carrito dentro del botón de compra</label>
    </div>

    {{-- Footer --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Pie de página</strong>
        <label class="bxb-field">Diseño del pie de página
            <select :value="settings.footer_style||'classic'" @change="setSetting('footer_style',$event.target.value)">
                <option value="classic">Oscuro clásico</option>
                <option value="light">Claro elegante</option>
                <option value="accent">Color de marca</option>
                <option value="minimal">Compacto centrado</option>
            </select>
        </label>
        <label class="bxb-field">Texto de derechos
            <input type="text" maxlength="160" :placeholder="'© '+(new Date().getFullYear())+' '+(settings.seo_title||project)" :value="settings.footer_copyright||''" @input.debounce.600ms="setSetting('footer_copyright',$event.target.value)">
        </label>
        <div class="bxb-grid2">
            <label class="bxb-field">Frase corta bajo el logo (tagline)
                <input type="text" maxlength="200" placeholder="Todo para tu hogar, en un solo lugar" :value="settings.footer_tagline||''" @input.debounce.600ms="setSetting('footer_tagline',$event.target.value)">
            </label>
            <label class="bxb-field">Texto "Desarrollado por"
                <input type="text" maxlength="120" placeholder="Desarrollado por AVAN" :value="settings.footer_dev_text||''" @input.debounce.600ms="setSetting('footer_dev_text',$event.target.value)">
            </label>
            <label class="bxb-field">Fondo del pie (opcional)
                <span class="bxb-color-row"><input type="color" :value="settings.footer_bg_color||'#0f172a'" @input="setSetting('footer_bg_color',$event.target.value)" aria-label="Fondo del pie"><code x-text="settings.footer_bg_color||'auto'"></code><button type="button" class="bxb-link" x-show="settings.footer_bg_color" @click="setSetting('footer_bg_color','')">Auto</button></span>
            </label>
            <label class="bxb-field">Letra del pie (opcional)
                <span class="bxb-color-row"><input type="color" :value="settings.footer_text_color||'#e2e8f0'" @input="setSetting('footer_text_color',$event.target.value)" aria-label="Letra del pie"><code x-text="settings.footer_text_color||'auto'"></code><button type="button" class="bxb-link" x-show="settings.footer_text_color" @click="setSetting('footer_text_color','')">Auto</button></span>
            </label>
        </div>
        <div class="bxb-grid2">
            <label class="bxb-field">Columna "Información" — un enlace por línea (Texto | /ruta)
                <textarea rows="4" maxlength="1500" placeholder="Políticas de privacidad | /privacidad&#10;Preguntas frecuentes | /faq" @input.debounce.600ms="setSetting('footer_pages',$event.target.value)" x-text="settings.footer_pages||''"></textarea>
            </label>
            <label class="bxb-field">Columna de la tienda — un enlace por línea (Texto | /ruta)
                <textarea rows="4" maxlength="1500" placeholder="¿Quiénes somos? | /nosotros&#10;Contáctanos | /contacto" @input.debounce.600ms="setSetting('footer_store_pages',$event.target.value)" x-text="settings.footer_store_pages||''"></textarea>
            </label>
            <label class="bxb-field">Título del boletín
                <input type="text" maxlength="80" placeholder="Boletín" :value="settings.footer_newsletter_title||''" @input.debounce.600ms="setSetting('footer_newsletter_title',$event.target.value)">
            </label>
            <label class="bxb-field">URL de suscripción del boletín
                <input type="url" maxlength="300" placeholder="https://..." :value="settings.footer_newsletter_url||''" @input.debounce.600ms="setSetting('footer_newsletter_url',$event.target.value)">
            </label>
            <label class="bxb-field">Alto del logo en el pie (px)
                <input type="number" min="20" max="200" placeholder="60" :value="settings.footer_logo_height||''" @input.debounce.600ms="setSetting('footer_logo_height',$event.target.value)">
            </label>
        </div>
        <div class="bxb-check-inline">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_categories??'1')!=='0'" @change="setSetting('footer_show_categories',$event.target.checked?'1':'0')"> Mostrar categorías</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_socials??'1')!=='0'" @change="setSetting('footer_show_socials',$event.target.checked?'1':'0')"> Mostrar redes sociales</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_payments??'1')!=='0'" @change="setSetting('footer_show_payments',$event.target.checked?'1':'0')"> Mostrar métodos de pago</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_secure??'1')!=='0'" @change="setSetting('footer_show_secure',$event.target.checked?'1':'0')"> Mostrar sello de compra segura</label>
        </div>
        {{-- Tarjeta de producto: proporcion y fondo de la foto. --}}
        <div class="bxb-grid2">
            <label class="bxb-field">Proporción de la foto de producto
                <select :value="settings.card_image_ratio||'1/1'" @change="setSetting('card_image_ratio',$event.target.value)">
                    <option value="1/1">Cuadrada — tecnología, accesorios</option>
                    <option value="4/3">Horizontal — muebles, electrodomésticos</option>
                    <option value="3/4">Vertical — ropa, calzado</option>
                </select>
                <small>Un mueble en un cuadrado se ve pequeño; una prenda en horizontal se corta.</small>
            </label>
            <label class="bxb-field">Fondo de la foto de producto
                <span class="bxb-color-row"><input type="color" :value="settings.card_image_bg||'#fafaf9'" @input="setSetting('card_image_bg',$event.target.value)" aria-label="Fondo de la foto"><code x-text="settings.card_image_bg||'#fafaf9'"></code><button type="button" class="bxb-link" x-show="settings.card_image_bg" @click="setSetting('card_image_bg','')">Auto</button></span>
                <small>Un crema suave hace resaltar el producto claro, que sobre blanco desaparece.</small>
            </label>
        </div>
        {{-- Medidas de portada y densidad en movil. 0 = automatico. --}}
        <div class="bxb-grid2">
            <label class="bxb-field">Alto del banner en escritorio (px)
                <input type="number" min="0" max="900" step="10" placeholder="0 = automático" :value="settings.hero_px_desktop||''" @input="setSetting('hero_px_desktop',$event.target.value)">
                <small>Deja 0 para que el banner se ajuste solo. 420–520 px es lo habitual.</small>
            </label>
            <label class="bxb-field">Alto del banner en móvil (px)
                <input type="number" min="0" max="900" step="10" placeholder="0 = automático" :value="settings.hero_px_mobile||''" @input="setSetting('hero_px_mobile',$event.target.value)">
                <small>En móvil conviene 320–380 px: se ve la foto y ya asoma lo de abajo.</small>
            </label>
            <label class="bxb-field">Categorías visibles en móvil
                <input type="number" min="0" max="24" step="1" placeholder="0 = todas" :value="settings.cats_mobile_limit||''" @input="setSetting('cats_mobile_limit',$event.target.value)">
                <small>Con muchas categorías el móvil se hace larguísimo. Las demás siguen en "Ver todas".</small>
            </label>
            <div class="bxb-field"><span>Precio mayorista en la tarjeta</span>
                <label class="bxb-switch"><input type="checkbox" :checked="(settings.wholesale_card_collapse||'')==='1'" @change="setSetting('wholesale_card_collapse',$event.target.checked?'1':'')"> Plegado (se abre al tocarlo)</label>
                <small>Con el selector de cantidad siempre visible la tarjeta crece y descuadra la fila.</small>
            </div>
        </div>
        {{-- Distintivo "Nuevo" en las tarjetas: se calcula por antigüedad. --}}
        <div class="bxb-grid2">
            <label class="bxb-field">Marcar como "Nuevo" los productos de los últimos… (días)
                <input type="number" min="0" max="365" placeholder="30" :value="settings.new_badge_days||''" @input.debounce.600ms="setSetting('new_badge_days',$event.target.value)">
                <small>0 desactiva el distintivo. Si el producto está en oferta, manda el descuento.</small>
            </label>
        </div>
        {{-- Franja animada sobre el pie: no tenia controles y se configuraba a mano. --}}
        <div class="bxb-grid2">
            <label class="bxb-field">Franja animada sobre el pie (vacío = oculta)
                <input type="text" maxlength="120" placeholder="AHORRA TIEMPO · COMPRA EN TU TIENDA" :value="settings.ticker_text||''" @input.debounce.600ms="setSetting('ticker_text',$event.target.value)">
            </label>
            <label class="bxb-field">Velocidad de la franja (segundos por vuelta)
                <input type="number" min="10" max="90" placeholder="28" :value="settings.ticker_speed||''" @input.debounce.600ms="setSetting('ticker_speed',$event.target.value)">
                <small>Más segundos = más lenta.</small>
            </label>
        </div>
        <div class="bxb-check-inline" x-show="(settings.ticker_text||'')!==''">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.ticker_force_motion||'0')!=='0'" @change="setSetting('ticker_force_motion',$event.target.checked?'1':'0')"> Mover la franja aunque el equipo tenga las animaciones reducidas</label>
        </div>
        {{-- Sello de confianza: se dibuja en SVG, no depende de una imagen subida. --}}
        <div class="bxb-grid2" x-show="(settings.footer_show_secure??'1')!=='0'">
            <label class="bxb-field">Sello de confianza
                <select :value="settings.footer_secure_style||'both'" @change="setSetting('footer_secure_style',$event.target.value)">
                    <option value="both">Ambos sellos — SSL + HTTPS</option>
                    <option value="gold">Medalla dorada — 100% Secure Transactions</option>
                    <option value="https">Escudo verde — HTTPS</option>
                    <option value="lock">Candado simple</option>
                </select>
            </label>
            <label class="bxb-field">Texto del sello
                <input type="text" maxlength="60" placeholder="Compra segura y protegida" :value="settings.footer_secure_title||''" @input.debounce.600ms="setSetting('footer_secure_title',$event.target.value)">
            </label>
        </div>
        {{-- Opciones exclusivas del pie "Tecnológico Pro": solo se muestran si esa
             variante está elegida, para no llenar de campos a las demás tiendas. --}}
        <template x-if="(settings.footer_layout||'')==='technology'">
        <div class="bxb-sub">
            <strong class="bxb-card-title">Pie Tecnológico Pro</strong>
            <div class="bxb-grid2">
                <label class="bxb-field">Degradado — segundo color
                    <span class="bxb-color-row"><input type="color" :value="settings.footer_bg2_color||'#07284A'" @input="setSetting('footer_bg2_color',$event.target.value)" aria-label="Segundo color del degradado"><code x-text="settings.footer_bg2_color||'auto'"></code><button type="button" class="bxb-link" x-show="settings.footer_bg2_color" @click="setSetting('footer_bg2_color','')">Auto</button></span>
                </label>
                <label class="bxb-field">Color de acento del pie
                    <span class="bxb-color-row"><input type="color" :value="settings.footer_accent_color||'#16BDF2'" @input="setSetting('footer_accent_color',$event.target.value)" aria-label="Acento del pie"><code x-text="settings.footer_accent_color||'auto'"></code><button type="button" class="bxb-link" x-show="settings.footer_accent_color" @click="setSetting('footer_accent_color','')">Auto</button></span>
                </label>
                <label class="bxb-field">Categorías a mostrar (3 a 8)
                    <input type="number" min="3" max="8" placeholder="5" :value="settings.footer_cats_limit||''" @input.debounce.600ms="setSetting('footer_cats_limit',$event.target.value)">
                </label>
                <label class="bxb-field">Bloque de ayuda — acción del botón
                    <select :value="settings.footer_cta_action||'whatsapp'" @change="setSetting('footer_cta_action',$event.target.value)">
                        <option value="whatsapp">Abrir WhatsApp</option>
                        <option value="contact">Ir a la página de contacto</option>
                        <option value="url">Enlace personalizado</option>
                    </select>
                </label>
                <label class="bxb-field">Bloque de ayuda — título
                    <input type="text" maxlength="60" placeholder="¿Necesitas ayuda?" :value="settings.footer_cta_title||''" @input.debounce.600ms="setSetting('footer_cta_title',$event.target.value)">
                </label>
                <label class="bxb-field">Bloque de ayuda — texto
                    <input type="text" maxlength="120" placeholder="Nuestro equipo está listo para asesorarte." :value="settings.footer_cta_text||''" @input.debounce.600ms="setSetting('footer_cta_text',$event.target.value)">
                </label>
                <label class="bxb-field">Bloque de ayuda — texto del botón
                    <input type="text" maxlength="40" placeholder="Contáctanos" :value="settings.footer_cta_btn||''" @input.debounce.600ms="setSetting('footer_cta_btn',$event.target.value)">
                </label>
                <label class="bxb-field" x-show="(settings.footer_cta_action||'whatsapp')==='url'">Enlace personalizado
                    <input type="url" maxlength="300" placeholder="https://..." :value="settings.footer_cta_url||''" @input.debounce.600ms="setSetting('footer_cta_url',$event.target.value)">
                </label>
            </div>
            <div class="bxb-check-inline">
                <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_benefits??'1')!=='0'" @change="setSetting('footer_show_benefits',$event.target.checked?'1':'0')"> Mostrar beneficios (usa los textos de la banda de confianza)</label>
                <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_help_cta??'1')!=='0'" @change="setSetting('footer_show_help_cta',$event.target.checked?'1':'0')"> Mostrar bloque de ayuda</label>
            </div>
        </div>
        </template>
    </div>

    <details class="bxb-advanced">
    <summary>Elementos flotantes y acceso de clientes (avanzado)</summary>
    {{-- Botones flotantes --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Botones flotantes</strong>
        <div class="bxb-grid2">
            <div class="bxb-field">
                <label class="bxb-switch"><input type="checkbox" :checked="(settings.float_wa_show??'1')!=='0'" @change="setSetting('float_wa_show',$event.target.checked?'1':'0')"> Botón flotante de WhatsApp</label>
                <div class="bxb-seg" x-show="(settings.float_wa_show??'1')!=='0'">
                    <button type="button" :class="(settings.float_wa_pos||'bottom-right')==='bottom-right'&&'on'" @click="setSetting('float_wa_pos','bottom-right')">Abajo derecha</button>
                    <button type="button" :class="settings.float_wa_pos==='bottom-left'&&'on'" @click="setSetting('float_wa_pos','bottom-left')">Abajo izquierda</button>
                </div>
                <label class="bxb-field" x-show="(settings.float_wa_show??'1')!=='0'">Tooltip del botón
                    <input type="text" maxlength="80" placeholder="¿Necesitas ayuda?" :value="settings.float_wa_tooltip||''" @input.debounce.600ms="setSetting('float_wa_tooltip',$event.target.value)">
                </label>
            </div>
            <div class="bxb-field">
                <label class="bxb-switch"><input type="checkbox" :checked="(settings.float_cart_show??'1')!=='0'" @change="setSetting('float_cart_show',$event.target.checked?'1':'0')"> Carrito flotante</label>
                <div class="bxb-seg" x-show="(settings.float_cart_show??'1')!=='0'">
                    <button type="button" :class="(settings.float_cart_pos||'bottom-right')==='bottom-right'&&'on'" @click="setSetting('float_cart_pos','bottom-right')">Abajo derecha</button>
                    <button type="button" :class="settings.float_cart_pos==='bottom-left'&&'on'" @click="setSetting('float_cart_pos','bottom-left')">Abajo izquierda</button>
                </div>
                <label class="bxb-field">Color del pop-up promocional
                    <span class="bxb-color-row"><input type="color" :value="settings.popup_bg_color||'#ffffff'" @input="setSetting('popup_bg_color',$event.target.value)" aria-label="Color del pop-up"><code x-text="settings.popup_bg_color||'auto'"></code></span>
                </label>
            </div>
        </div>
    </div>

    {{-- Pantalla de acceso de clientes --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Pantalla de acceso de clientes</strong>
        <p class="bxb-note">Fondo y mensaje de la página donde tus clientes inician sesión.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Título de bienvenida
                <input type="text" maxlength="120" placeholder="Bienvenido de vuelta" :value="settings.login_heading||''" @input.debounce.600ms="setSetting('login_heading',$event.target.value)">
            </label>
            <label class="bxb-field">Subtítulo
                <input type="text" maxlength="200" placeholder="Ingresa para ver tus pedidos" :value="settings.login_subtitle||''" @input.debounce.600ms="setSetting('login_subtitle',$event.target.value)">
            </label>
        </div>
        <div class="bxb-field"><span>Tipo de fondo</span>
            <div class="bxb-seg" role="radiogroup" aria-label="Fondo del login">
                <button type="button" :class="(settings.login_bg_type||'gradient')==='gradient'&&'on'" @click="setSetting('login_bg_type','gradient')">Degradado</button>
                <button type="button" :class="settings.login_bg_type==='solid'&&'on'" @click="setSetting('login_bg_type','solid')">Color sólido</button>
                <button type="button" :class="settings.login_bg_type==='image'&&'on'" @click="setSetting('login_bg_type','image')">Imagen</button>
            </div>
        </div>
        <div class="bxb-grid2" x-show="(settings.login_bg_type||'gradient')!=='image'">
            <label class="bxb-field">Color 1
                <span class="bxb-color-row"><input type="color" :value="settings.login_color1||'#4f46e5'" @input="setSetting('login_color1',$event.target.value)" aria-label="Color 1 del login"><code x-text="settings.login_color1||'#4f46e5'"></code></span>
            </label>
            <label class="bxb-field" x-show="(settings.login_bg_type||'gradient')==='gradient'">Color 2
                <span class="bxb-color-row"><input type="color" :value="settings.login_color2||'#7c3aed'" @input="setSetting('login_color2',$event.target.value)" aria-label="Color 2 del login"><code x-text="settings.login_color2||'#7c3aed'"></code></span>
            </label>
        </div>
        <div class="bxb-field" x-show="settings.login_bg_type==='image'"><span>Imagen de fondo</span>
            <div class="bxb-media">
                <span class="bxb-media-box" :style="settings.login_bg_image ? 'background-image:url('+assetUrl(settings.login_bg_image)+')' : ''"><template x-if="!settings.login_bg_image"><em>1920×1080</em></template></span>
                <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'login_bg_image')"></label>
                <button type="button" class="bxb-link" x-show="settings.login_bg_image" @click="setSetting('login_bg_image','')">Quitar</button>
            </div>
        </div>
    </div>
    </details>

    {{-- Configuración avanzada (modo experto) --}}
    <details class="bxb-advanced" :open="expertMode">
        <summary>Configuración avanzada</summary>
        <div class="bxb-card">
            <div class="bxb-grid2">
                <label class="bxb-field">Alto del logo (px)
                    <input type="number" min="20" max="300" :value="settings.header_logo_height||48" @input.debounce.600ms="setSetting('header_logo_height',$event.target.value)">
                </label>
                <label class="bxb-field">Tamaño de letra de la barra (px)
                    <input type="number" min="10" max="22" :value="settings.announcement_font_size||12" @input.debounce.600ms="setSetting('announcement_font_size',$event.target.value)">
                </label>
            </div>
            <label class="bxb-switch"><input type="checkbox" :checked="settings.announcement_full_width==='1'" @change="setSetting('announcement_full_width',$event.target.checked?'1':'0')"> Barra superior a todo el ancho</label>
            <div class="bxb-field"><span>Alineación del texto de la barra</span>
                <div class="bxb-seg">
                    <button type="button" :class="(settings.announcement_align||'center')==='left'&&'on'" @click="setSetting('announcement_align','left')">Izquierda</button>
                    <button type="button" :class="(settings.announcement_align||'center')==='center'&&'on'" @click="setSetting('announcement_align','center')">Centro</button>
                    <button type="button" :class="(settings.announcement_align||'center')==='right'&&'on'" @click="setSetting('announcement_align','right')">Derecha</button>
                </div>
            </div>
        </div>
    </details>
</section>

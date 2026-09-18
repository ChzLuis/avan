{{-- Etapa 7: una sola fuente para el pie de página y los textos legales. --}}
<section class="bxb-stage">
    <h2>Footer y legales</h2>
    <p class="bxb-stage-sub">Configura el pie de página y las políticas que ayudan a comprar con confianza. Los datos de contacto y redes se reutilizan desde Datos del negocio.</p>

    <div class="bxb-card">
        <strong class="bxb-card-title">Pie de página</strong>
        <div class="bxb-grid2">
            <label class="bxb-field">Diseño del pie
                <select :value="settings.footer_style||'classic'" @change="setSetting('footer_style',$event.target.value)">
                    <option value="classic">Oscuro clásico</option>
                    <option value="light">Claro elegante</option>
                    <option value="accent">Color de marca</option>
                    <option value="minimal">Compacto centrado</option>
                </select>
            </label>
            <label class="bxb-field">Composición
                <select :value="settings.footer_layout||'classic'" @change="setSetting('footer_layout',$event.target.value)">
                    @foreach(\App\Modules\Tienda\Support\StorefrontLayoutPacks::options('footers') as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                {{-- Qué se va a ver con cada composición: el nombre solo no alcanza para elegir. --}}
                <span class="bxb-note" x-text="(@js(\App\Modules\Tienda\Support\StorefrontLayoutPacks::DESCRIPCIONES['footers']))[settings.footer_layout||'classic'] || ''"></span>
            </label>
            <label class="bxb-field">Frase corta bajo el logo
                <input type="text" maxlength="200" placeholder="Todo para tu hogar, en un solo lugar" :value="settings.footer_tagline||''" @input.debounce.600ms="setSetting('footer_tagline',$event.target.value)">
            </label>
            <label class="bxb-field">Texto de derechos
                <input type="text" maxlength="160" :placeholder="'© '+(new Date().getFullYear())+' '+(settings.seo_title||project)" :value="settings.footer_copyright||''" @input.debounce.600ms="setSetting('footer_copyright',$event.target.value)">
            </label>
            <label class="bxb-field">Fondo del pie
                <x-bxb-color clave="footer_bg_color" defecto="#0f172a" etiqueta="Fondo del pie" />
            </label>
            <label class="bxb-field">Color del texto
                <x-bxb-color clave="footer_text_color" defecto="#e2e8f0" etiqueta="Texto del pie" />
            </label>
            <label class="bxb-field">Placa blanca detrás del logo
                <select :value="settings.footer_logo_plate||'1'" @change="setSetting('footer_logo_plate',$event.target.value)">
                    <option value="1">Mostrar placa</option><option value="0">Logo directo sobre el fondo</option>
                </select>
            </label>
            <label class="bxb-field">Alto del logo (px)
                <input type="number" min="20" max="200" placeholder="60" :value="settings.footer_logo_height||''" @input.debounce.600ms="setSetting('footer_logo_height',$event.target.value)">
            </label>
        </div>
        <div class="bxb-check-inline">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_categories??'1')!=='0'" @change="setSetting('footer_show_categories',$event.target.checked?'1':'0')"> Mostrar categorías</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_socials??'1')!=='0'" @change="setSetting('footer_show_socials',$event.target.checked?'1':'0')"> Mostrar redes sociales</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_payments??'1')!=='0'" @change="setSetting('footer_show_payments',$event.target.checked?'1':'0')"> Mostrar métodos de pago</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_secure??'1')!=='0'" @change="setSetting('footer_show_secure',$event.target.checked?'1':'0')"> Mostrar compra segura</label>
        </div>
        <details class="bxb-advanced">
            <summary>Enlaces y boletín</summary>
            <div class="bxb-grid2">
                <label class="bxb-field">Columna “Información” (Texto | /ruta)
                    <textarea rows="4" maxlength="1500" placeholder="Política de privacidad | /privacidad" @input.debounce.600ms="setSetting('footer_pages',$event.target.value)" x-text="settings.footer_pages||''"></textarea>
                </label>
                <label class="bxb-field">Columna “Tienda” (Texto | /ruta)
                    <textarea rows="4" maxlength="1500" placeholder="Nosotros | /nosotros" @input.debounce.600ms="setSetting('footer_store_pages',$event.target.value)" x-text="settings.footer_store_pages||''"></textarea>
                </label>
                <label class="bxb-field">Título del boletín
                    <input type="text" maxlength="80" placeholder="Recibe nuestras novedades" :value="settings.footer_newsletter_title||''" @input.debounce.600ms="setSetting('footer_newsletter_title',$event.target.value)">
                </label>
                <label class="bxb-field">URL real de suscripción
                    <input type="url" maxlength="300" placeholder="https://..." :value="settings.footer_newsletter_url||''" @input.debounce.600ms="setSetting('footer_newsletter_url',$event.target.value)">
                </label>
            </div>
            <p class="bxb-note">El boletín solo aparece cuando existe una URL de suscripción funcional.</p>
        </details>
    </div>

    {{-- Controles que consume el pie "Tecnologico" y el bloque de beneficios.
         Vivian en Apariencia dentro de un @if(false): al desactivarlo se
         quedaron sin editor con datos reales en 3 y 4 tiendas. Su etapa
         propietaria es esta. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Pie avanzado</strong>
        <div class="bxb-grid2">
            <label class="bxb-field">Color de acento del pie
                <x-bxb-color clave="footer_accent_color" defecto="#38bdf8" etiqueta="Acento del pie" />
                <small class="bxb-note">Lo usan los pies con degradado. Vacio = color principal de la tienda.</small>
            </label>
            <label class="bxb-field">Segundo color del pie
                <x-bxb-color clave="footer_bg2_color" defecto="#0f172a" etiqueta="Segundo color del pie" />
                <small class="bxb-note">Extremo del degradado. Con un solo color el pie queda plano.</small>
            </label>
            <label class="bxb-field">Categorias visibles en el pie
                <input type="number" min="0" max="24" step="1" placeholder="0 = todas" :value="settings.footer_cats_limit||''" @input.debounce.600ms="setSetting('footer_cats_limit',$event.target.value)">
                <small class="bxb-note">Con muchas categorias el pie se alarga de mas.</small>
            </label>
            <label class="bxb-field">Texto de autoria
                <input type="text" maxlength="120" placeholder="Desarrollado por AVAN" :value="settings.footer_dev_text||''" @input.debounce.600ms="setSetting('footer_dev_text',$event.target.value)">
            </label>
        </div>
        <div class="bxb-check-inline">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_benefits??'1')!=='0'" @change="setSetting('footer_show_benefits',$event.target.checked?'1':'0')"> Mostrar la franja de beneficios</label>
        </div>
    </div>

    {{-- Bloques del pie que solo se podian activar desde Diseño clasico. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Qué muestra el pie</strong>
        <div class="bxb-check-inline">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_address??'1')!=='0'" @change="setSetting('footer_show_address',$event.target.checked?'1':'0')"> Dirección del negocio</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_social??'1')!=='0'" @change="setSetting('footer_show_social',$event.target.checked?'1':'0')"> Redes sociales</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.footer_show_newsletter??'1')!=='0'" @change="setSetting('footer_show_newsletter',$event.target.checked?'1':'0')"> Suscripción al boletín</label>
        </div>
        <p class="bxb-note">Los datos salen de <strong>Datos del negocio</strong>; aquí solo decides si se muestran.</p>
    </div>

    <div class="bxb-card bxb-embed">
        @include('tienda::settings.partials.institutional-pages', [
            'pages' => $storePages,
            'showInstitutional' => false,
            'showLegal' => true,
        ])
    </div>
</section>

{{-- Etapa 8: configuración técnica en una única ubicación. --}}
@php
    // Analítica, píxeles y verificaciones = capacidad restringida
    // `cap_seo_avanzado` (matriz de capacidades): inyectan scripts de terceros
    // en la tienda y tocan cómo la indexa Google. El servidor lo exige en
    // `StoreBuilderController::saveDraftSettings`; aquí solo se evita ofrecer
    // un control que no se va a guardar.
    $capSeoAvanzado = \App\Support\Capacidades::permite($project, auth()->user(), 'seo_avanzado');
@endphp
<section class="bxb-stage" x-init="loadCopySources()">
    <h2>Configuración</h2>
    <p class="bxb-stage-sub">SEO, dominio, integraciones y herramientas técnicas. Las opciones de uso frecuente permanecen en las etapas anteriores.</p>

    <div class="bxb-card">
        <strong class="bxb-card-title">SEO básico</strong>
        <label class="bxb-field">Descripción para buscadores (meta description)
            <input type="text" maxlength="200" placeholder="Compra {{ '{' }}productos{{ '}' }} con envío a todo el país…" :value="settings.seo_description||''" @input.debounce.600ms="setSetting('seo_description',$event.target.value)">
        </label>
        <label class="bxb-field">Palabras clave (separadas por comas)
            <input type="text" maxlength="300" placeholder="laptops huacho, tienda tecnología, computadoras" :value="settings.seo_keywords||''" @input.debounce.600ms="setSetting('seo_keywords',$event.target.value)">
        </label>
        <p class="bxb-note">El título SEO es tu Nombre comercial (Etapa 1). Sitemap y robots se generan solos.</p>
    </div>

    {{-- Analitica e integraciones. Vivian SOLO en settings/seo, que es un
         elemento de menu aparte, y ADEMAS no se emitian: el comerciante podia
         guardar su ID de Google Analytics y no se medía nada. El emisor unico
         es <x-analytics-tags>. --}}
    @if($capSeoAvanzado)
    <div class="bxb-card">
        <strong class="bxb-card-title">Analítica y píxeles</strong>
        <p class="bxb-note">Pega el identificador que te da cada plataforma. Si el formato no es válido, la etiqueta no se emite.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Google Analytics 4
                <input type="text" maxlength="40" placeholder="G-XXXXXXXXXX" :value="settings.ga_id||''" @input.debounce.600ms="setSetting('ga_id',$event.target.value)">
            </label>
            <label class="bxb-field">Google Tag Manager
                <input type="text" maxlength="20" placeholder="GTM-XXXXXXX" :value="settings.gtm_id||''" @input.debounce.600ms="setSetting('gtm_id',$event.target.value)">
            </label>
            <label class="bxb-field">Meta Pixel (Facebook / Instagram)
                <input type="text" inputmode="numeric" maxlength="20" placeholder="123456789012345" :value="settings.fb_pixel_id||''" @input.debounce.600ms="setSetting('fb_pixel_id',$event.target.value)">
            </label>
            <label class="bxb-field">TikTok Pixel
                <input type="text" maxlength="30" placeholder="CXXXXXXXXXXXXXXXXXXX" :value="settings.tiktok_pixel_id||''" @input.debounce.600ms="setSetting('tiktok_pixel_id',$event.target.value)">
            </label>
        </div>
    </div>

    <div class="bxb-card">
        <strong class="bxb-card-title">Verificación de buscadores</strong>
        <p class="bxb-note">El código que te pide Google o Bing para demostrar que la tienda es tuya.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Google Search Console
                <input type="text" maxlength="100" :value="settings.google_site_verification||''" @input.debounce.600ms="setSetting('google_site_verification',$event.target.value)">
            </label>
            <label class="bxb-field">Bing Webmaster
                <input type="text" maxlength="100" :value="settings.bing_site_verification||''" @input.debounce.600ms="setSetting('bing_site_verification',$event.target.value)">
            </label>
        </div>
    </div>
    @endif

    <div class="bxb-card">
        <strong class="bxb-card-title">Cómo se ve al compartir (Open Graph)</strong>
        <p class="bxb-note">Lo que aparece cuando alguien pega el enlace de tu tienda en WhatsApp o redes.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Título al compartir
                <input type="text" maxlength="120" :placeholder="settings.business_name||'Tu tienda'" :value="settings.og_title||''" @input.debounce.600ms="setSetting('og_title',$event.target.value)">
            </label>
            <label class="bxb-field">Descripción al compartir
                <input type="text" maxlength="200" :value="settings.og_description||''" @input.debounce.600ms="setSetting('og_description',$event.target.value)">
            </label>
        </div>
        <label class="bxb-field">Enlace canónico (opcional)
            <input type="url" maxlength="300" placeholder="https://tutienda.com" :value="settings.seo_canonical||''" @input.debounce.600ms="setSetting('seo_canonical',$event.target.value)">
            <small class="bxb-note">Solo si tu tienda es alcanzable por varias direcciones y quieres que los buscadores prefieran una.</small>
        </label>
    </div>

    <div class="bxb-card">
        <strong class="bxb-card-title">Copiar de otra tienda</strong>
        <p class="bxb-note">Trae la configuración de otra de tus tiendas. <strong>Se copia a borrador</strong>: nada cambia en público hasta que publiques. Nunca copia productos, dominios ni credenciales.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Tienda de origen
                <select x-model="copySource">
                    <option value="">Elige una tienda…</option>
                    <template x-for="s in copySources" :key="s.id"><option :value="s.id" x-text="s.name"></option></template>
                </select>
            </label>
            <div class="bxb-field"><span>Qué copiar</span>
                <div class="bxb-check-inline">
                    <label class="bxb-switch" style="min-height:36px"><input type="checkbox" value="apariencia" x-model="copyParts"> Apariencia</label>
                    <label class="bxb-switch" style="min-height:36px"><input type="checkbox" value="inicio" x-model="copyParts"> Página de inicio</label>
                    <label class="bxb-switch" style="min-height:36px"><input type="checkbox" value="venta" x-model="copyParts"> Venta y pagos</label>
                </div>
            </div>
        </div>
        <div class="bxb-actions-row">
            <button type="button" class="bxb-btn" :disabled="!copySource||!copyParts.length" @click="copyPreview()">Ver resumen</button>
            <button type="button" class="bxb-btn bxb-btn-primary" x-show="copySummary" x-cloak @click="copyConfirm()">Copiar a borrador</button>
        </div>
        <p class="bxb-recommend" x-show="copySummary" x-cloak x-text="copySummary" role="status"></p>
        <p class="bxb-note" x-show="copyResult" x-cloak x-text="copyResult" role="status"></p>
    </div>

    <div class="bxb-card">
        <strong class="bxb-card-title">Herramientas técnicas</strong>
        <ul class="bxb-trustlist" role="list">

            <li><span>Dominio propio y QR</span><a class="bxb-btn" href="{{ route('settings') }}" target="_blank" rel="noopener">Abrir ↗</a></li>
            <li><span>Roles y permisos</span><a class="bxb-btn" href="{{ \Illuminate\Support\Facades\Route::has('roles.index') ? route('roles.index') : route('settings') }}" target="_blank" rel="noopener">Abrir ↗</a></li>
            <li><span>Campos del pedido (checkout)</span><button type="button" class="bxb-btn" @click="stage='sales'">Configurar</button></li>
            <li><span>Cupones de descuento</span><a class="bxb-btn" href="{{ route('settings') }}#cupones" target="_blank" rel="noopener">Abrir ↗</a></li>
        </ul>
    </div>
</section>

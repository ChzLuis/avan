{{-- Etapa 1: Datos del negocio (todo se guarda como borrador) --}}
<section class="bxb-stage">
    <h2>Datos del negocio</h2>
    <p class="bxb-stage-sub">Lo esencial para que tu tienda hable por ti. Todo queda en borrador hasta que publiques.</p>

    <div class="bxb-card">
        <div class="bxb-grid2">
            <label class="bxb-field">Nombre comercial
                {{-- Clave propia: antes vivia en seo_title y el nombre del negocio
                     quedaba atado al titulo de buscadores. Al publicar se escribe
                     en projects.name (la fuente que leen facturas, PDF y el bot). --}}
                <input type="text" maxlength="100" placeholder="Mi Negocio" :value="settings.business_name||''" @input.debounce.600ms="setSetting('business_name',$event.target.value)">
            </label>
            <label class="bxb-field">Rubro
                <select :value="settings.business_category||''" @change="applyRubro($event.target.value)">
                    <option value="">Elige tu rubro…</option>
                    <template x-for="(r,key) in presets" :key="key">
                        <option :value="key" x-text="r.label" :selected="settings.business_category===key"></option>
                    </template>
                </select>
            </label>
        </div>
        <div class="bxb-recommend" x-show="rubroApplied" x-cloak role="status">
            ✓ Te recomendamos: plantilla <strong x-text="rubroApplied&&rubroApplied.template==='computienda'?'Ecommerce Pro':(rubroApplied&&rubroApplied.template)"></strong>,
            colores y textos iniciales — ya están en tu borrador (puedes cambiarlos en Apariencia).
            <span x-show="rubroApplied&&rubroApplied.suggested_categories">Categorías sugeridas: <em x-text="rubroApplied&&rubroApplied.suggested_categories.join(', ')"></em>.</span>
        </div>
        <div class="bxb-actions-row" x-show="settings.business_category" x-cloak>
            <button type="button" class="bxb-btn" @click="applyDesignPreset(settings.business_category)">✦ Aplicar diseño completo del rubro</button>
            <small x-show="designPresetResult" x-text="designPresetResult" role="status"></small>
        </div>
        <p class="bxb-note" x-show="settings.business_category">El diseño completo activa el tema visual, las variantes y las secciones recomendadas para tu rubro — todo en borrador y 100% editable después.</p>
    </div>

    <div class="bxb-card">
        <div class="bxb-field">
            <span>Logo</span>
            <div class="bxb-media">
                <span class="bxb-media-box" :style="settings.logo_url ? 'background-image:url('+assetUrl(settings.logo_url)+')' : ''">
                    <template x-if="!settings.logo_url"><em>Sube tu logo</em></template>
                </span>
                <label class="bxb-btn">Subir logo<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'logo_url')"></label>
            </div>
        </div>
    </div>

    <div class="bxb-card">
        <strong class="bxb-card-title">Redes sociales</strong>
        <p class="bxb-note">Se reutilizan en el encabezado, Contacto y pie de página. Escríbelas una sola vez.</p>
        <div class="bxb-grid2">
            @foreach([
                ['facebook_url', 'Facebook', 'https://facebook.com/tu-tienda'],
                ['instagram_url', 'Instagram', 'https://instagram.com/tu-tienda'],
                ['tiktok_url', 'TikTok', 'https://tiktok.com/@tu-tienda'],
                ['youtube_url', 'YouTube', 'https://youtube.com/@tu-tienda'],
                ['linkedin_url', 'LinkedIn', 'https://linkedin.com/company/tu-tienda'],
                ['twitter_url', 'X / Twitter', 'https://x.com/tu-tienda'],
            ] as [$key, $label, $placeholder])
            <label class="bxb-field">{{ $label }}
                <input type="url" maxlength="500" placeholder="{{ $placeholder }}" :value="settings.{{ $key }}||''" @input.debounce.600ms="setSetting('{{ $key }}',$event.target.value)">
            </label>
            @endforeach
        </div>
    </div>

    <div class="bxb-card">
        <div class="bxb-grid2">
            <label class="bxb-field">WhatsApp de ventas *
                <input type="text" inputmode="tel" maxlength="15" placeholder="987654321" :value="settings.quote_whatsapp||''" @input.debounce.600ms="setSetting('quote_whatsapp',$event.target.value)">
            </label>
            <label class="bxb-field">Correo de contacto
                <input type="email" maxlength="160" placeholder="ventas@negocio.com" :value="settings.contact_email||''" @input.debounce.600ms="setSetting('contact_email',$event.target.value)">
            </label>
            <label class="bxb-field">Teléfono fijo o alternativo
                <input type="text" inputmode="tel" maxlength="20" placeholder="(01) 234 5678" :value="settings.contact_phone||''" @input.debounce.600ms="setSetting('contact_phone',$event.target.value)">
            </label>
            <p class="bxb-note" style="align-self:end">La moneda y la forma de vender (compra / cotización) se configuran en <button type="button" class="bxb-link" @click="stage='sales'">Venta</button>.</p>
        </div>
    </div>

    {{-- Ubicación: revelado progresivo — sin local físico no se piden dirección ni horario --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Ubicación</strong>
        <label class="bxb-switch"><input type="checkbox"
            :checked="settings.has_physical_store==='1' || (settings.has_physical_store==null && (settings.contact_address||'')!=='')"
            @change="setSetting('has_physical_store',$event.target.checked?'1':'0')"> Tengo local físico</label>
        <div class="bxb-grid2" x-show="settings.has_physical_store==='1' || (settings.has_physical_store==null && (settings.contact_address||'')!=='')" x-cloak>
            <label class="bxb-field">Dirección
                <input type="text" maxlength="255" placeholder="Av. Principal 123" :value="settings.contact_address||''" @input.debounce.600ms="setSetting('contact_address',$event.target.value)">
            </label>
            <label class="bxb-field">Distrito / Ciudad
                <input type="text" maxlength="80" placeholder="Huacho" :value="settings.contact_city||''" @input.debounce.600ms="setSetting('contact_city',$event.target.value)">
            </label>
            <label class="bxb-field">Horario de atención
                <input type="text" maxlength="160" placeholder="Lunes a sábado, 9am – 6pm" :value="settings.business_hours||''" @input.debounce.600ms="setSetting('business_hours',$event.target.value)">
            </label>
        </div>
        <div x-show="settings.has_physical_store==='1'" x-cloak>
            @if(($sedes ?? collect())->isNotEmpty())
            {{-- Las sucursales se VEN aqui (revision 01): antes solo habia un
                 enlace que sacaba del Constructor. La edicion sigue en su modulo. --}}
            <div class="bxb-sedes">
                @foreach($sedes as $sede)
                <div class="bxb-sede {{ $sede->is_active ? '' : 'bxb-sede-off' }}">
                    <strong>{{ $sede->name }}</strong>
                    <span>{{ $sede->address ?: 'Sin dirección' }}{{ $sede->phone ? ' · '.$sede->phone : '' }}</span>
                    @unless($sede->is_active)<em>inactiva</em>@endunless
                </div>
                @endforeach
            </div>
            @endif
            <div class="bxb-actions-row">
                <a class="bxb-btn" href="{{ route('sedes.index') }}" target="_blank" rel="noopener">{{ ($sedes ?? collect())->isEmpty() ? 'Agregar sucursales ↗' : 'Administrar sucursales ↗' }}</a>
                <small>La dirección principal permanece aquí; usa Sucursales cuando tienes más de un local.</small>
            </div>
        </div>
    </div>

    {{-- Datos fiscales: MISMAS claves que facturación y Libro de Reclamaciones (ruc / razon_social).
         Una sola fuente: lo que se escribe aquí es lo que usan esos módulos. --}}
    <details class="bxb-advanced">
        <summary>Datos fiscales (facturación y Libro de Reclamaciones)</summary>
        <div class="bxb-card">
            <div class="bxb-grid2">
                <label class="bxb-field">Razón social
                    <input type="text" maxlength="200" :placeholder="settings.business_name||'Mi Negocio S.A.C.'" :value="settings.razon_social||''" @input.debounce.600ms="setSetting('razon_social',$event.target.value)">
                </label>
                <label class="bxb-field">RUC
                    <input type="text" inputmode="numeric" maxlength="11" placeholder="20123456789" :value="settings.ruc||''" @input.debounce.600ms="setSetting('ruc',$event.target.value)">
                </label>
            </div>
            <p class="bxb-note">Estos datos son los mismos que usan la facturación electrónica y el Libro de Reclamaciones — no hay que escribirlos dos veces.</p>
        </div>
    </details>

</section>

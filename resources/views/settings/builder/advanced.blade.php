{{-- Ajustes avanzados (B7): opciones poco frecuentes, fuera del flujo principal. --}}
<section class="bxb-stage" x-init="loadCopySources()">
    <h2>Ajustes avanzados</h2>
    <p class="bxb-stage-sub">Opciones técnicas y herramientas para usuarios avanzados. Nada de esto es necesario para publicar.</p>

    <div class="bxb-card">
        <strong class="bxb-card-title">SEO básico</strong>
        <label class="bxb-field">Descripción para buscadores (meta description)
            <input type="text" maxlength="200" placeholder="Compra {{ '{' }}productos{{ '}' }} con envío a todo el país…" :value="settings.seo_description||''" @input.debounce.600ms="setSetting('seo_description',$event.target.value)">
        </label>
        <p class="bxb-note">El título SEO es tu Nombre comercial (Etapa 1). Sitemap y robots se generan solos.</p>
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

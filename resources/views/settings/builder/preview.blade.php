{{-- Vista previa real (iframe del storefront con el borrador).
     PC renderiza a 1280px reales y se ESCALA para caber (no responsive falso);
     el borde izquierdo permite estirar el panel. --}}
<aside class="bxb-preview" :class="previewOpen&&'is-open'" aria-label="Vista previa de tu tienda">
    <div class="bxb-preview-resizer" @pointerdown="startPreviewResize($event)" title="Arrastra para agrandar la vista previa" aria-hidden="true"></div>
    <div class="bxb-preview-head">
        <span class="bxb-preview-title">Así se ve tu tienda <em>(borrador)</em></span>
        <span class="bxb-device" role="group" aria-label="Dispositivo">
            <button type="button" :class="device==='desktop'&&'on'" @click="setDevice('desktop')" aria-label="Escritorio">PC</button>
            <button type="button" :class="device==='tablet'&&'on'" @click="setDevice('tablet')" aria-label="Tablet">Tablet</button>
            <button type="button" :class="device==='mobile'&&'on'" @click="setDevice('mobile')" aria-label="Móvil">Móvil</button>
        </span>
        <a class="bxb-link" :href="urls.preview+'?view=home'" target="_blank" rel="noopener" aria-label="Abrir vista previa en pestaña nueva">⛶</a>
        <button type="button" class="bxb-preview-close" @click="previewOpen=false" aria-label="Cerrar vista previa">✕</button>
    </div>
    <div class="bxb-preview-frame" x-ref="previewWrap" :class="'is-'+device">
        <div class="bxb-preview-scaler" :style="previewScaleStyle">
            <iframe x-ref="previewFrame" :src="urls.preview+'?view=home'" title="Vista previa de la tienda (borrador)" loading="lazy" @load="fitPreview()"></iframe>
        </div>
        <div class="bxb-preview-error" x-show="previewError" x-cloak>
            No se pudo cargar la vista previa. <button type="button" class="bxb-link" @click="refreshPreview(true)">Reintentar</button>
        </div>
    </div>
</aside>

{{-- Botón flotante para abrir el preview en pantallas pequeñas --}}
<button type="button" class="bxb-preview-fab" @click="previewOpen=true" aria-label="Ver tienda">👁 Ver tienda</button>

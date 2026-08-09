{{-- Etapa: Páginas y confianza — Nosotros, Contacto, legales y Libro de Reclamaciones.
     El menú y el encabezado viven en su propia etapa (Encabezado y navegación). --}}
<section class="bxb-stage">
    <h2>Páginas y confianza</h2>
    <p class="bxb-stage-sub">Nosotros, Contacto, Términos, Privacidad y Libro de Reclamaciones. Estos formularios guardan al instante (no pasan por el borrador).</p>

    <div class="bxb-card bxb-embed">
        @include('settings.partials.institutional-pages', ['pages' => $storePages])
    </div>
</section>

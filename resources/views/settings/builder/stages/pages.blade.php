{{-- Etapa 6: contenido institucional. Los datos de contacto maestros vienen de Datos del negocio. --}}
<section class="bxb-stage">
    <h2>Páginas</h2>
    <p class="bxb-stage-sub">Administra Nosotros y Contacto sin duplicar los datos generales de tu negocio. Ocultar una página del menú no elimina su contenido.</p>

    {{-- Relleno de ejemplo: una pagina a medio configurar sale con su diseno en
         vez de un titulo suelto. Lo que el negocio escriba manda siempre. --}}
    <div class="bxb-card">
        <label class="bxb-switch">
            <input type="checkbox"
                   :checked="(settings.pages_placeholder ?? '1') !== '0'"
                   @change="setSetting('pages_placeholder', $event.target.checked ? '1' : '0')">
            Rellenar con texto de ejemplo las páginas que aún no escribiste
        </label>
        <p class="bxb-note">Mientras no escribas tu texto, tus visitantes ven un contenido de ejemplo con el diseño ya aplicado, en vez de una página vacía. En cuanto escribas el tuyo, sustituye al ejemplo automáticamente.</p>
    </div>

    <div class="bxb-card bxb-embed">
        @include('settings.partials.institutional-pages', [
            'pages' => $storePages,
            'showInstitutional' => true,
            'showLegal' => false,
        ])
    </div>
</section>

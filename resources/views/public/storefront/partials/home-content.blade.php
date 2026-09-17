@if($sections->isEmpty())
    <section class="store-page-shell">
        <div class="store-container store-page-card">
            <h1>{{ $project->name }}</h1>
            <p>Estamos preparando una nueva experiencia para ti.</p>
            <a class="store-button" href="{{ route('public.shop', $project->slug) }}">Ver tienda</a>
        </div>
    </section>
@else
    <x-storefront-shapes :settings="$settings" />
    <x-storefront-home-skins :settings="$settings" />
    {{-- El diseño de Inicio reordena los bloques; no filtra ni cambia su
         contenido, por eso cambiar de diseño nunca pierde nada. --}}
    @include('components.storefront-home-sections', [
        'project' => $project,
        'settings' => $settings,
        'sections' => \App\Storefront\HomePresets::ordenar($sections, $settings['home_template'] ?? null),
    ])
@endif

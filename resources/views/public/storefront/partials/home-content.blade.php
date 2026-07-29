@if($sections->isEmpty())
    <section class="store-page-shell">
        <div class="store-container store-page-card">
            <h1>{{ $project->name }}</h1>
            <p>Estamos preparando una nueva experiencia para ti.</p>
            <a class="store-button" href="{{ route('public.shop', $project->slug) }}">Ver tienda</a>
        </div>
    </section>
@else
    @include('components.storefront-home-sections', ['project' => $project, 'settings' => $settings, 'sections' => $sections])
@endif

@props(['menu' => null, 'project' => null, 'storeView' => 'home'])
@php
    /*
        Menú de la tienda, para las plantillas que no lo traen.

        El editor vive en 02 Apariencia → «Encabezado y navegación» y guarda en
        `store_menus`. El dato llegaba a las 16 plantillas, pero solo dos lo
        pintaban: en las otras catorce el negocio configuraba su menú y no salía
        nada. Peor: al cambiar de plantilla desde el Constructor, el menú
        desaparecía sin avisar.

        Este componente lo pinta igual en todas. Los colores no se fijan: se
        heredan (`currentColor`) y se apoyan en las variables que cada plantilla
        ya define, así que toma el aspecto de la tienda en la que entra en vez
        de imponer el suyo.
    */
    $items = $menu?->rootItems?->where('is_enabled', true) ?? collect();

    // Qué entrada corresponde a la página actual, para marcarla.
    $destinoActivo = match ($storeView) {
        'tienda'   => 'shop',
        'nosotros' => 'about',
        'contacto' => 'contact',
        'blog'     => 'blog',
        default    => 'home',
    };

    $url = fn ($item) => $project
        ? \App\Modules\Tienda\Support\StorefrontNavigation::resolveUrl($project, $item)
        : ($item->url ?: '#');
@endphp
@if($items->isNotEmpty())
<style>
  .sm-nav { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; font-size: 14px; }
  .sm-nav a { color: currentColor; text-decoration: none; padding: 8px 12px; border-radius: 6px;
              white-space: nowrap; transition: background-color .18s ease, opacity .18s ease; }
  .sm-nav a:hover { background: color-mix(in srgb, currentColor 12%, transparent); }
  .sm-nav a.is-active { font-weight: 700; }
  .sm-item { position: relative; }
  /* Submenú: se abre al pasar el ratón y también al enfocar con teclado. */
  .sm-sub { position: absolute; left: 0; top: 100%; min-width: 190px; z-index: 60;
            display: none; flex-direction: column; padding: 6px;
            background: var(--surface, #fff); color: var(--text, #111);
            border: 1px solid var(--border, #e2e8f0); border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,.12); }
  .sm-item:hover > .sm-sub, .sm-item:focus-within > .sm-sub { display: flex; }
  @media (max-width: 860px) {
    .sm-nav { gap: 2px; font-size: 13px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .sm-nav a { padding: 7px 9px; }
    .sm-sub { position: static; display: flex; box-shadow: none; border: 0; padding: 0; background: none; }
  }
</style>
<nav class="sm-nav" aria-label="Menú de la tienda">
  @foreach($items as $item)
    @php $hijos = $item->children?->where('is_enabled', true) ?? collect(); @endphp
    <span class="sm-item">
      <a href="{{ $url($item) }}"
         @if($item->target === '_blank') target="_blank" rel="noopener" @endif
         class="{{ $item->destination_type === $destinoActivo ? 'is-active' : '' }}"
         @if($item->destination_type === $destinoActivo) aria-current="page" @endif>{{ $item->label }}</a>
      @if($hijos->isNotEmpty())
      <span class="sm-sub">
        @foreach($hijos as $hijo)
        <a href="{{ $url($hijo) }}" @if($hijo->target === '_blank') target="_blank" rel="noopener" @endif>{{ $hijo->label }}</a>
        @endforeach
      </span>
      @endif
    </span>
  @endforeach
</nav>
@endif

{{-- Iconos de categoria: $categoryIconPaths y $autoCategoryIcon, desde
     App\Modules\Tienda\Support\IconosCategoria (fuente unica). --}}
@php
    $categoryIconPaths = \App\Modules\Tienda\Support\IconosCategoria::paths();
    $autoCategoryIcon = static fn ($name) => \App\Modules\Tienda\Support\IconosCategoria::auto($name);
@endphp

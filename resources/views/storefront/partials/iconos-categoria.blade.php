{{-- Iconos de categoria: $categoryIconPaths y $autoCategoryIcon, desde
     App\Support\IconosCategoria (fuente unica). --}}
@php
    $categoryIconPaths = \App\Support\IconosCategoria::paths();
    $autoCategoryIcon = static fn ($name) => \App\Support\IconosCategoria::auto($name);
@endphp

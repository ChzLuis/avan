{{-- Botón "Categorías" del menú, común a todos los layouts de cabecera.

     Antes cada layout traía el suyo: el shell de presets lo pintaba con el
     texto "Categorías" fijo, el clásico con "Todas las categorías", y la banda
     como un enlace de texto al FINAL del menú que nadie veía. Aquí hay uno
     solo, y el negocio decide desde el Constructor su posición (izquierda,
     derecha u oculto), su estilo (sólido, contorno o texto) y su rótulo.

     Modos de despliegue que resuelve este parcial:
       mega  → solo pinta el botón; el panel en columnas lo pinta el layout,
               y el botón lo abre por la variable Alpine `$var` de ese layout.
       lista → botón + desplegable simple, autocontenido: las categorías en
               columna y, al pasar sobre una con hijas, sus subcategorías.

     Entradas: $modo (mega|lista), $pos, $var (nombre de la variable Alpine
     del panel mega del layout; por defecto `mega`). Lee $settings,
     $navCategories, $project y $shopBase del layout que lo incluye. --}}
@php
    $cbPos    = in_array($pos ?? '', ['izquierda', 'derecha', 'oculto'], true) ? $pos : 'izquierda';
    $cbEstilo = in_array($settings['hp_cats_style'] ?? '', ['solido', 'contorno', 'texto'], true) ? $settings['hp_cats_style'] : 'solido';
    $cbTexto  = trim((string) ($settings['mega_button_text'] ?? '')) ?: 'Categorías';
    $cbModo   = in_array($modo ?? '', ['mega', 'lista'], true) ? $modo : 'mega';
    $cbVar    = preg_replace('/[^a-zA-Z_]/', '', (string) ($var ?? 'mega')) ?: 'mega';
    $cbShop   = $shopBase ?? \App\Modules\Tienda\Support\StorefrontNavigation::shopUrl($project);
    $cbCats   = $navCategories ?? collect();
@endphp
@if($cbPos !== 'oculto' && $cbCats->count())
@once
<style>
  /* Boton de categorias: un solo componente para el shell de presets, el
     clasico y la banda. Lleva dos clases: `.mega-btn` para que los ganchos
     de CSS y JS que ya existian sigan funcionando, y `.cats-btn` con la
     variante de estilo. La doble clase en las variantes gana en
     especificidad a la regla `.mega-btn{background:#fff}` de la plantilla,
     que dejaba el boton blanco y sin presencia. */
  .cats-menu{position:relative;display:flex;align-items:center;flex:0 0 auto}
  .cats-menu--derecha{order:99;margin-left:auto}
  .mega-btn.cats-btn{
    display:inline-flex;align-items:center;gap:9px;min-height:44px;padding:0 18px;
    border-radius:9px;font-size:14px;font-weight:700;letter-spacing:.01em;line-height:1;
    cursor:pointer;white-space:nowrap;
    transition:transform .18s ease,box-shadow .18s ease,filter .18s ease,background-color .18s ease;
  }
  .mega-btn.cats-btn svg{width:18px;height:18px;flex:0 0 18px}
  .mega-btn.cats-btn .mega-caret{width:14px;height:14px;flex:0 0 14px;transition:transform .2s ease}
  .mega-btn.cats-btn--solido{background:var(--primary);color:#fff;border:0}
  .mega-btn.cats-btn--contorno{background:transparent;color:var(--primary);border:2px solid var(--primary)}
  .mega-btn.cats-btn--texto{background:transparent;color:var(--primary);border:0;padding-inline:8px}
  .mega-btn.cats-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px color-mix(in srgb,var(--primary) 28%,transparent)}
  .mega-btn.cats-btn--solido:hover{filter:brightness(1.06)}
  .mega-btn.cats-btn--contorno:hover{background:color-mix(in srgb,var(--primary) 8%,#fff)}
  .mega-btn.cats-btn--texto:hover{box-shadow:none;background:color-mix(in srgb,var(--primary) 8%,#fff)}
  .mega-btn.cats-btn:focus-visible{outline:3px solid color-mix(in srgb,var(--primary) 35%,transparent);outline-offset:2px}

  /* Lista desplegable simple: categorias en columna, subcategorias al pasar. */
  .cats-list{
    position:absolute;top:calc(100% + 8px);left:0;z-index:70;min-width:270px;
    padding:8px;background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;
    box-shadow:0 18px 44px rgba(15,23,42,.14);
  }
  .cats-menu--derecha .cats-list{left:auto;right:0}
  .cats-list-item{position:relative}
  .cats-list-item>a{
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:10px 12px;border-radius:8px;color:#1f2937;font-size:14px;font-weight:600;text-decoration:none;
  }
  .cats-list-item>a svg{width:14px;height:14px;flex:0 0 14px;opacity:.5}
  .cats-list-item>a:hover,.cats-list-item:hover>a{background:color-mix(in srgb,var(--primary) 8%,#fff);color:var(--primary)}
  .cats-sub{
    display:none;position:absolute;top:-8px;left:100%;z-index:71;min-width:250px;margin-left:6px;
    padding:8px;background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;
    box-shadow:0 18px 44px rgba(15,23,42,.14);
  }
  .cats-list-item:hover>.cats-sub{display:block}
  .cats-sub a{display:block;padding:9px 12px;border-radius:8px;color:#334155;font-size:13.5px;text-decoration:none}
  .cats-sub a:hover{background:#f1f5f9;color:var(--primary)}
  .cats-sub .cats-sub-all{font-weight:700;color:var(--primary);border-bottom:1px solid #eef2f7;border-radius:8px 8px 0 0;margin-bottom:4px}

  /* Movil: el desplegable deja de flotar, ocupa el ancho y las subcategorias
     van debajo de su categoria, porque no hay "pasar el raton". */
  @media (max-width:900px){
    .cats-menu--derecha{order:0;margin-left:0}
    .cats-list{position:static;min-width:0;width:100%;margin-top:6px;box-shadow:none}
    .cats-sub{display:block;position:static;margin:0 0 4px 14px;padding:0 0 0 10px;border:0;border-left:2px solid #e5e7eb;border-radius:0;box-shadow:none;min-width:0}
    .cats-sub .cats-sub-all{display:none}
  }
  @media (prefers-reduced-motion:reduce){
    .mega-btn.cats-btn,.mega-btn.cats-btn .mega-caret{transition:none}
    .mega-btn.cats-btn:hover{transform:none}
  }
</style>
@endonce
@if($cbModo === 'lista')
<div class="mega-trigger cats-menu cats-menu--{{ $cbPos }} cats-menu--lista" x-data="{ o:false }" @mouseenter="o=true" @mouseleave="o=false" @click.outside="o=false">
    <button type="button" class="mega-btn cats-btn cats-btn--{{ $cbEstilo }}" @click="o=!o" :aria-expanded="o.toString()" aria-haspopup="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span>{{ $cbTexto }}</span>
        <svg class="mega-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" :style="o && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="cats-list" x-show="o" x-cloak role="menu" aria-label="{{ $cbTexto }}">
        @foreach($cbCats as $cbCat)
        @php $cbHijas = $cbCat->children ?? collect(); @endphp
        <div class="cats-list-item{{ $cbHijas->count() ? ' has-sub' : '' }}">
            <a href="{{ $cbShop }}?category={{ $cbCat->id }}" role="menuitem">
                <span>{{ $cbCat->name }}</span>
                @if($cbHijas->count())<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>@endif
            </a>
            @if($cbHijas->count())
            <div class="cats-sub">
                <a class="cats-sub-all" href="{{ $cbShop }}?category={{ $cbCat->id }}">Ver todo {{ $cbCat->name }}</a>
                @foreach($cbHijas as $cbSub)
                <a href="{{ $cbShop }}?category={{ $cbSub->id }}">{{ $cbSub->name }}</a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@else
<div class="mega-trigger cats-menu cats-menu--{{ $cbPos }}" @mouseenter="{{ $cbVar }}=true" @click="{{ $cbVar }}=!{{ $cbVar }}">
    <button type="button" class="mega-btn cats-btn cats-btn--{{ $cbEstilo }}" :aria-expanded="{{ $cbVar }}.toString()" aria-haspopup="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span>{{ $cbTexto }}</span>
        <svg class="mega-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" :style="{{ $cbVar }} && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
    </button>
</div>
@endif
@endif

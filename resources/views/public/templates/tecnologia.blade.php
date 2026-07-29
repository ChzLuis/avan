<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@php
    $azul    = $settings['primary_color']   ?? '#1e50a0';
    $rojo    = $settings['secondary_color'] ?? '#e01e2b';
    $cur     = $settings['currency_symbol'] ?? 'S/';
    $nombre  = $settings['seo_title'] ?? $project->name;
    $tagline = $settings['footer_tagline'] ?? 'Tienda de tecnología e informática.';
    $heroTitle = $settings['hero_title'] ?? 'Tecnología para cada pasión';
    $heroSub   = $settings['hero_subtitle'] ?? 'Laptops, PCs, monitores e impresoras. Garantía oficial y soporte técnico.';
    $wa = preg_replace('/\D/','', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
    $waFull = $wa ? (str_starts_with($wa,'51') ? $wa : '51'.$wa) : '';
    $logoUrl = $settings['logo_url'] ?? $project->logo_url ?? '';
    $ruc = $settings['ruc'] ?? ($project->ruc ?? '');
    $email = $settings['contact_email'] ?? ($project->email ?? '');
    $tel = $settings['contact_phone'] ?? ($project->phone ?? '');
    $businessHours = $settings['business_hours'] ?? '';
    $footerPages = collect(array_filter(array_map('trim', explode("\n", $settings['footer_pages'] ?? ''))))
        ->map(fn($line) => array_map('trim', explode('|', $line, 2)));
    $footerStorePages = collect(array_filter(array_map('trim', explode("\n", $settings['footer_store_pages'] ?? ''))))
        ->map(fn($line) => array_map('trim', explode('|', $line, 2)));
@endphp
<title>{{ $nombre }} — Tienda de Tecnología</title>
<meta name="description" content="{{ $settings['seo_description'] ?? $tagline }}">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    :root { --azul: {{ $azul }}; --rojo: {{ $rojo }}; }
    body { font-family: 'Inter', system-ui, sans-serif; }
    .txt-azul { color: var(--azul); } .bg-azul { background: var(--azul); }
    .txt-rojo { color: var(--rojo); } .bg-rojo { background: var(--rojo); }
    .cat-flyout { display: none; }
    .cat-item:hover .cat-flyout { display: block; }
    .skew-band { transform: skewX(-12deg); }
    .skew-band > * { transform: skewX(12deg); }
</style>
</head>
<body class="bg-gray-50" x-data="tienda()">

{{-- ═══ TOPBAR ═══ --}}
<div class="bg-azul text-white text-xs">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-9">
        <div class="flex items-center gap-4 font-semibold">
            <span class="hidden sm:inline">SOPORTE TÉCNICO</span>
            <span class="hidden sm:inline">SERVICIOS</span>
            <a href="{{ route('public.contact', $project->slug) }}" class="hidden md:inline">CONTACTOS</a>
            @if($aboutPage)<a href="{{ route('public.about', $project->slug) }}" class="hidden md:inline">QUIÉNES SOMOS</a>@endif
            <span class="hidden lg:inline">CÓMO COMPRAR</span>
            <span class="hidden lg:inline">BLOG</span>
            <a href="/{{ $project->slug }}" class="font-bold">TIENDA</a>
            <span class="bg-yellow-400 text-black px-2 py-0.5 rounded font-bold">COTIZAR</span>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <span>📘</span><span>📷</span><span>▶️</span><span>💬</span><span>🎵</span>
        </div>
    </div>
</div>

{{-- ═══ HEADER ═══ --}}
<header class="bg-azul text-white">
    <div class="max-w-7xl mx-auto px-4 flex items-center gap-4 h-24">
        {{-- Logo --}}
        <a href="/{{ $project->slug }}" class="flex items-center gap-2 flex-shrink-0">
            @if($logoUrl)
                <img src="{{ asset('storage/'.$logoUrl) }}" alt="{{ $nombre }}" class="h-12 w-auto">
            @else
                <div class="text-2xl font-black italic tracking-tight">{{ strtoupper($nombre) }}</div>
            @endif
        </a>
        {{-- Buscador --}}
        <div class="flex-1 max-w-2xl">
            <div class="flex bg-white rounded-lg overflow-hidden">
                <input type="text" x-model="q" @input="filtrar()" placeholder="Buscar productos"
                       class="flex-1 px-4 py-2.5 text-gray-800 text-sm focus:outline-none">
                <button class="px-4 bg-sky-400 hover:bg-sky-500 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                </button>
            </div>
        </div>
        {{-- Cuenta + carrito --}}
        <div class="flex items-center gap-3 flex-shrink-0">
            <button class="w-11 h-11 rounded-full border-2 border-white/40 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
            </button>
            <button @click="cartOpen=true" class="relative w-11 h-11 rounded-full border-2 border-white/40 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M6 2l1 5h13l-1.5 8H8L6 4H3"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                <span class="absolute -top-1 -right-1 bg-rojo text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center" x-text="cart.length"></span>
            </button>
        </div>
    </div>
</header>

{{-- ═══ NAV: MENÚ LATERAL CATEGORÍAS + NAV HORIZONTAL ═══ --}}
<div class="bg-white shadow-sm border-b border-gray-200 relative z-30">
    <div class="max-w-7xl mx-auto px-4 flex items-center gap-4 h-14">
        {{-- Botón principales categorías --}}
        <div class="relative" @mouseenter="catMenu=true" @mouseleave="catMenu=false">
            <button class="bg-sky-400 hover:bg-sky-500 text-white font-bold text-sm px-4 py-2.5 rounded-lg flex items-center gap-2 w-64 justify-between">
                <span class="flex items-center gap-2">☰ PRINCIPALES CATEGORÍAS</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            {{-- Panel lateral de categorías --}}
            <div x-show="catMenu" x-cloak class="absolute top-full left-0 w-64 bg-white border border-gray-200 rounded-b-lg shadow-xl">
                @foreach($categories as $cat)
                    <div class="cat-item relative border-b border-gray-50 last:border-0">
                        <a href="#cat-{{ $cat->id }}" class="flex items-center justify-between px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-azul">
                            <span class="flex items-center gap-2">📦 {{ Str::upper($cat->name) }}</span>
                            @if($cat->children->count())<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></svg>@endif
                        </a>
                        {{-- Flyout de subcategorías --}}
                        @if($cat->children->count())
                        <div class="cat-flyout absolute left-full top-0 w-56 bg-white border border-gray-200 rounded-lg shadow-xl py-2">
                            @foreach($cat->children as $sub)
                                <a href="#cat-{{ $cat->id }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-azul">{{ $sub->name }}</a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        {{-- Nav horizontal destacado --}}
        <nav class="hidden md:flex items-center gap-5 text-sm font-semibold text-gray-700 flex-1">
            @foreach($categories->take(3) as $cat)
                <a href="#cat-{{ $cat->id }}" class="hover:text-azul flex items-center gap-1">🖥️ {{ Str::upper($cat->name) }}</a>
            @endforeach
        </nav>
        {{-- Botones destacados --}}
        <div class="hidden lg:flex items-center gap-2">
            <span class="bg-green-500 text-white text-xs font-bold px-3 py-2 rounded-full flex items-center gap-1">🔄 PROD. REACONDICIONADOS</span>
            <span class="bg-rojo text-white text-xs font-bold px-3 py-2 rounded-full flex items-center gap-1">⚙️ OFERTAS / NOVEDADES</span>
        </div>
    </div>
</div>

{{-- ═══ HERO ═══ --}}
<section class="bg-gradient-to-r from-blue-50 to-white">
    <div class="max-w-7xl mx-auto px-4 py-10 grid md:grid-cols-2 gap-6 items-center">
        <div>
            <h1 class="text-3xl md:text-4xl font-black text-gray-800 leading-tight">{{ $heroTitle }}</h1>
            <p class="text-gray-600 mt-3">{{ $heroSub }}</p>
            <a href="#catalogo" class="inline-block mt-5 bg-rojo text-white font-bold px-6 py-3 rounded-full hover:opacity-90">VER OFERTAS ›</a>
            <div class="flex gap-6 mt-6 text-sm text-gray-600">
                <span class="flex items-center gap-1">💻 Trabajo</span>
                <span class="flex items-center gap-1">🎮 Entretenimiento</span>
                <span class="flex items-center gap-1">📈 Productividad</span>
            </div>
        </div>
        <div class="flex justify-center">
            @php $heroImg = $featured->first()?->mainImage?->url; @endphp
            @if($heroImg)
                <img src="{{ $heroImg }}" class="max-h-64 object-contain">
            @else
                <div class="text-8xl">💻</div>
            @endif
        </div>
    </div>
</section>

{{-- ═══ BARRA DE SERVICIOS ═══ --}}
<div class="bg-white border-y border-gray-200">
    <div class="max-w-7xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 divide-x divide-gray-100">
        @foreach([['🏬','Retiro en tienda'],['🚚','Envíos a todo el Perú'],['🔧','Soporte Técnico'],['⚡','Entrega Exprés']] as $srv)
            <div class="flex items-center justify-center gap-2 py-4 text-azul font-bold text-sm">
                <span class="text-lg">{{ $srv[0] }}</span> {{ $srv[1] }}
            </div>
        @endforeach
    </div>
</div>

{{-- ═══ BARRA ROJA DE OFERTAS ═══ --}}
<div class="bg-rojo text-white">
    <div class="max-w-7xl mx-auto px-4 py-3 text-center font-black italic text-lg tracking-wide">
        {{ $settings['announcement_text'] ?? '🛒 COMPRA ONLINE Y AHORRA TIEMPO Y DINERO' }}
    </div>
</div>

{{-- ═══ CATÁLOGO ═══ --}}
<main id="catalogo" class="max-w-7xl mx-auto px-4 py-10">
    @foreach($categories as $cat)
        @php $prods = $cat->products->concat($cat->children->flatMap->products)->take(12); @endphp
        @if($prods->count())
        <section id="cat-{{ $cat->id }}" class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-black text-gray-800 flex items-center gap-2">
                    <span class="w-1.5 h-6 bg-rojo rounded"></span>{{ Str::upper($cat->name) }}
                </h2>
                <span class="text-sm text-gray-400">{{ $prods->count() }} productos</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($prods as $p)
                    <div class="bg-white rounded-xl border border-gray-200 p-3 hover:shadow-lg transition group">
                        <div class="aspect-square bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden mb-3 relative">
                            @if($p->mainImage?->url)
                                <img src="{{ $p->mainImage->url }}" class="w-full h-full object-contain group-hover:scale-105 transition">
                            @else
                                <span class="text-4xl">📦</span>
                            @endif
                            @if($p->compare_price && $p->compare_price > $p->price)
                                <span class="absolute top-2 left-2 bg-rojo text-white text-[10px] font-bold px-2 py-0.5 rounded">OFERTA</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 mb-1">{{ $p->sku }}</div>
                        <h3 class="text-sm font-semibold text-gray-800 line-clamp-2 mb-2 min-h-[2.5rem]">{{ $p->name }}</h3>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-black text-azul">{{ $cur }} {{ number_format($p->price,2) }}</span>
                        </div>
                        <button @click="add({{ $p->id }}, @js($p->name), {{ $p->price }})"
                                class="w-full mt-2 bg-azul hover:opacity-90 text-white text-xs font-bold py-2 rounded-lg">Agregar al carrito</button>
                    </div>
                @endforeach
            </div>
        </section>
        @endif
    @endforeach
</main>

{{-- ═══ FOOTER ═══ --}}
<footer class="bg-azul text-white">
    {{-- Barra roja compra online --}}
    <div class="bg-rojo py-3">
        <div class="max-w-7xl mx-auto px-4 text-center font-black italic tracking-wide">COMPRA ONLINE Y AHORRA TIEMPO Y DINERO</div>
    </div>
    <div class="max-w-7xl mx-auto px-4 py-10 grid md:grid-cols-4 gap-8 text-sm">
        <div>
            <div class="text-xl font-black italic mb-3">{{ strtoupper($nombre) }}</div>
            <p class="text-white/70 leading-relaxed">{{ $tagline }}</p>
            <div class="flex gap-2 mt-4">
                <span class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center">📘</span>
                <span class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center">📷</span>
                <span class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center">🎵</span>
            </div>
        </div>
        <div>
            <h4 class="font-bold mb-3 border-b border-white/20 pb-1">Tus compras siempre seguras</h4>
            <p class="text-white/70 text-xs mb-3">*Paga de forma segura usando tu tarjeta.</p>
            <div class="flex flex-wrap gap-1.5 items-center">
                @foreach(['VISA','Mastercard','Amex','Yape','Plin'] as $pm)
                    <span class="bg-white text-gray-800 text-[10px] font-bold px-2 py-1 rounded">{{ $pm }}</span>
                @endforeach
            </div>
            <p class="text-white/60 text-xs mt-3">*Nuestro sitio es seguro gracias a SSL.</p>
        </div>
        <div>
            <h4 class="font-bold mb-3 border-b border-white/20 pb-1">Contacto</h4>
            @if($tel)<p class="flex items-center gap-2 mb-2">📱 {{ $tel }}</p>@endif
            @if($email)<p class="flex items-center gap-2 mb-2">✉️ {{ $email }}</p>@endif
            @if($businessHours)<p class="flex items-center gap-2 mb-2">⏰ {{ $businessHours }}</p>@endif
            @if($ruc)<div class="border border-white/30 rounded-lg px-3 py-2 mt-2 font-bold text-xs">{{ strtoupper($nombre) }} RUC {{ $ruc }}</div>@endif
        </div>
        <div>
            <h4 class="font-bold mb-3 border-b border-white/20 pb-1">Enlaces</h4>
            <ul class="space-y-1.5 text-white/70 text-xs">
                @if($footerPages->count())
                    @foreach($footerPages as $page)
                        @php [$title,$url] = $page; @endphp
                        <li><a href="{{ $url ?: '#' }}" class="hover:text-white">{{ strtoupper($title) }}</a></li>
                    @endforeach
                @else
                    <li>POLÍTICA DE PRIVACIDAD</li>
                    <li>POLÍTICA DE ENVÍO</li>
                    <li>POLÍTICA DE GARANTÍA</li>
                    <li>CÓMO COMPRAR</li>
                    <li>TÉRMINOS Y CONDICIONES</li>
                @endif
                <li><a href="{{ route('public.complaints', $project->slug) }}" class="font-semibold text-white hover:text-sky-200">LIBRO DE RECLAMACIONES</a></li>
            </ul>
            @if($footerStorePages->count())
                <div class="mt-4">
                    <h4 class="font-bold mb-3 border-b border-white/20 pb-1">Tienda</h4>
                    <ul class="space-y-1.5 text-white/70 text-xs">
                        @foreach($footerStorePages as $page)
                            @php [$title,$url] = $page; @endphp
                            <li><a href="{{ $url ?: '#' }}" class="hover:text-white">{{ strtoupper($title) }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="border-t border-white/10 py-4 text-center text-white/60 text-xs">
        {{ $settings['footer_copyright'] ?? ('Copyright © '.date('Y').' '.strtoupper($nombre).' | Todos los derechos reservados') }}
    </div>
</footer>

{{-- ═══ WhatsApp flotante ═══ --}}
@if($waFull)
<a href="https://wa.me/{{ $waFull }}" target="_blank" class="fixed bottom-5 right-5 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-lg text-white text-2xl z-40">💬</a>
@endif

<x-public-store-runtime :project="$project" :settings="$settings" :popup="$popup ?? null" :sections="$sections ?? collect()" :about-page="$aboutPage ?? null" />

{{-- ═══ CARRITO (drawer) ═══ --}}
<div x-show="cartOpen" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="cartOpen=false">
    <div class="absolute inset-0 bg-black/40" @click="cartOpen=false"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-sm bg-white shadow-2xl flex flex-col">
        <div class="p-4 border-b flex items-center justify-between">
            <span class="font-bold text-gray-800">Tu carrito</span>
            <button @click="cartOpen=false" class="text-gray-400 text-xl">✕</button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <template x-if="cart.length===0"><p class="text-center text-gray-400 mt-10 text-sm">Tu carrito está vacío</p></template>
            <template x-for="(item,i) in cart" :key="i">
                <div class="flex items-center gap-3 border-b pb-2">
                    <div class="flex-1">
                        <div class="text-sm font-semibold text-gray-800" x-text="item.nombre"></div>
                        <div class="text-xs text-gray-500">{{ $cur }} <span x-text="item.precio.toFixed(2)"></span> × <span x-text="item.qty"></span></div>
                    </div>
                    <button @click="cart.splice(i,1)" class="text-red-400 text-sm">✕</button>
                </div>
            </template>
        </div>
        <div class="p-4 border-t">
            <div class="flex justify-between font-bold text-gray-800 mb-3">
                <span>Total</span><span>{{ $cur }} <span x-text="total().toFixed(2)"></span></span>
            </div>
            <a :href="waLink()" target="_blank" class="block w-full bg-green-500 text-white text-center font-bold py-3 rounded-lg">Pedir por WhatsApp</a>
        </div>
    </div>
</div>

<script>
function tienda() {
  return {
    q: '', catMenu: false, cartOpen: false, cart: [],
    add(id, nombre, precio) {
      const ex = this.cart.find(c => c.id === id);
      if (ex) ex.qty++; else this.cart.push({ id, nombre, precio, qty: 1 });
      this.cartOpen = true;
    },
    total() { return this.cart.reduce((s, c) => s + c.precio * c.qty, 0); },
    filtrar() {
      const q = this.q.toLowerCase();
      document.querySelectorAll('[id^="cat-"] .grid > div').forEach(el => {
        const t = el.textContent.toLowerCase();
        el.style.display = (!q || t.includes(q)) ? '' : 'none';
      });
    },
    waLink() {
      const wa = '{{ $waFull }}';
      let msg = '¡Hola! Quiero pedir:\n';
      this.cart.forEach(c => msg += `• ${c.nombre} x${c.qty} — {{ $cur }} ${(c.precio*c.qty).toFixed(2)}\n`);
      msg += `\nTotal: {{ $cur }} ${this.total().toFixed(2)}`;
      return `https://wa.me/${wa}?text=${encodeURIComponent(msg)}`;
    },
  };
}
</script>
</body>
</html>

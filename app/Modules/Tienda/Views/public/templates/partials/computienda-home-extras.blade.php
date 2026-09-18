{{-- ═══════════════════════════════════════════════════════════════════
     SECCIONES EXTRA DE PORTADA (registro canónico store_sections)
     media_banner · collection_showcase · brands · testimonials · gallery
     · faq · wa_advisory · cta_banner
     Hereda del scope padre: $settings, $project, $assetUrl, $color,
     $whatsapp, $shopUrl, $categories, $isHomeSectionVisible,
     $sectionOrder, $sectionContentFor, $primary.
     Cada sección solo se pinta si está publicada y tiene contenido real.
     ═══════════════════════════════════════════════════════════════════ --}}
@php
    $xsItems = static fn (array $content) => collect($content['items'] ?? [])
        ->filter(fn ($i) => ($i['enabled'] ?? true))
        ->sortBy(fn ($i) => $i['sort_order'] ?? 999)->values();

    // Embed de YouTube/Vimeo a partir de una URL pegada por el usuario.
    $xsEmbed = static function (?string $url): ?string {
        $url = trim((string) $url);
        if ($url === '') return null;
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([\w-]{6,20})#i', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1].'?rel=0&modestbranding=1';
        }
        if (preg_match('#vimeo\.com/(?:video/)?(\d+)#i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }
        return null;
    };
    $xsCatUrl = static fn ($categoryId) => $shopUrl.'?category='.(int) $categoryId;
    // Foto de una tarjeta: la suya → la de su categoría → la del primer producto
    // con imagen de esa categoría (o subcategorías). Nunca un bloque gris vacío.
    $xsItemImage = static function ($item) use ($categories, $assetUrl) {
        if (filled($item['image'] ?? null)) return $assetUrl($item['image']);
        $catId = (int) ($item['category_id'] ?? 0);
        if (!$catId) return null;
        $cat = $categories->firstWhere('id', $catId)
            ?? $categories->flatMap->children->firstWhere('id', $catId);
        if (!$cat) return null;
        $img = $cat->image_url
            ?? $cat->products->firstWhere('main_image_url')?->main_image_url
            ?? $cat->children->flatMap->products->firstWhere('main_image_url')?->main_image_url;
        return $img ? \App\Support\ImageVariants::webp($assetUrl($img)) : null;
    };
    $xsUrl = static function ($item) use ($shopUrl, $xsCatUrl) {
        if (!empty($item['category_id'])) return $xsCatUrl($item['category_id']);
        $u = trim((string) ($item['url'] ?? ''));
        return $u === '' || $u === '#catalogo' ? $shopUrl : $u;
    };
@endphp

<style>
    .xs-section{padding:clamp(44px,6vw,72px) 0}
    .xs-head{margin-bottom:26px}.xs-head h2{margin:0;font-family:var(--font-title);font-size:clamp(24px,3vw,34px);letter-spacing:-.02em;color:var(--text-strong)}.xs-head p{margin:8px 0 0;color:var(--muted);max-width:640px;line-height:1.6}
    body.section-heading-center .xs-head{text-align:center}.section-heading-center .xs-head p{margin-inline:auto}
    /* El subrayado del encabezado se centraba siempre (margin:auto), asi que con
       los titulos a la izquierda quedaba flotando en mitad de la seccion. Debe
       seguir al titulo. */
    body.section-heading-left [data-store-native-section] .xs-head::after{margin-inline:0}
    /* ── media_banner ── */
    .xs-media{position:relative;overflow:hidden;display:grid;place-items:center;padding:0;color:#fff}
    .xs-media.h-small{min-height:280px}.xs-media.h-medium{min-height:420px}.xs-media.h-large{min-height:560px}.xs-media.h-full{min-height:calc(100svh - var(--header-h,72px))}
    /* REEL (9:16): el video manda y el texto se acomoda al lado. Pensado para
       piezas grabadas en vertical (TikTok / Reels / Shorts). */
    .xs-media.h-reel{min-height:0;display:grid;grid-template-columns:minmax(0,340px) minmax(0,1fr);align-items:center;justify-content:center;gap:clamp(24px,5vw,64px);padding:clamp(32px,5vw,64px) clamp(18px,5vw,64px);max-width:1180px;margin-inline:auto}
    .xs-media.h-reel .xs-media-bg{position:relative;inset:auto;width:100%;max-width:340px;aspect-ratio:9/16;margin-inline:auto;border-radius:20px;overflow:hidden;box-shadow:0 22px 60px rgba(15,23,42,.22)}
    .xs-media.h-reel .xs-media-bg video,.xs-media.h-reel .xs-media-bg img{width:100%;height:100%;object-fit:cover}
    .xs-media.h-reel .xs-media-bg iframe{position:absolute;top:0;left:0;width:100%;height:100%;transform:none}
    .xs-media.h-reel .xs-media-overlay{display:none}
    .xs-media.h-reel .xs-media-copy{width:auto;padding:0;align-items:flex-start;text-align:left}
    .xs-media.h-reel .xs-media-copy .xs-media-play{display:none}
    .xs-media.h-reel.is-empty .xs-media-bg:after{content:'';position:absolute;inset:0;margin:auto;width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.20);border:1px solid rgba(255,255,255,.45)}
    .xs-media.h-reel.is-empty .xs-media-bg:before{content:'';position:absolute;inset:0;margin:auto;width:0;height:0;z-index:1;border-left:20px solid #fff;border-top:12px solid transparent;border-bottom:12px solid transparent;transform:translateX(4px)}
    .xs-media.h-reel{color:var(--text,#334155)}
    /* En reel el lienzo de marca va SOLO dentro del marco vertical, no en toda la seccion */
    .xs-media.h-reel.is-empty{background-image:none!important;background-color:var(--surface-soft,#f8fafc)!important}
    .xs-media.h-reel.is-empty:before,.xs-media.h-reel.is-empty:after{display:none}
    .xs-media.h-reel .xs-media-copy p{color:var(--muted,#64748b)!important}
    .xs-media.h-reel .xs-media-copy h2{font-size:clamp(26px,3.4vw,42px);color:var(--text-strong,#0f172a)}
    .xs-media.h-reel .xs-media-play{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.4)}
    .xs-media.h-reel .xs-media-copy p{color:var(--muted,#64748b)}
    .xs-media.h-reel.is-empty .xs-media-bg{background:linear-gradient(150deg,var(--primary),color-mix(in srgb,var(--accent,var(--primary)) 55%,var(--primary)));display:grid;place-items:center}
    @media(max-width:860px){
        .xs-media.h-reel{grid-template-columns:1fr;gap:20px;padding:26px 0}
        .xs-media.h-reel .xs-media-bg{max-width:min(320px,78vw)}
        .xs-media.h-reel .xs-media-copy{align-items:center;text-align:center;padding:0 16px}
    }
    .xs-media-bg{position:absolute;inset:0}.xs-media-bg img,.xs-media-bg video{width:100%;height:100%;object-fit:cover}
    .xs-media-bg iframe{position:absolute;top:50%;left:50%;width:max(100vw,178svh);height:max(56.25vw,100svh);transform:translate(-50%,-50%);pointer-events:none;border:0}
    .xs-media-overlay{position:absolute;inset:0;background:var(--xs-overlay,#0f172a);opacity:var(--xs-overlay-op,.45)}
    .xs-media-copy{position:relative;z-index:2;width:min(1180px,calc(100% - 40px));padding:56px 0;display:flex;flex-direction:column;gap:14px}
    .xs-media-copy.al-left{align-items:flex-start;text-align:left}.xs-media-copy.al-center{align-items:center;text-align:center}.xs-media-copy.al-right{align-items:flex-end;text-align:right}
    .xs-media-copy h2{margin:0;font-family:var(--font-title);font-size:clamp(30px,5vw,54px);line-height:1.05;letter-spacing:-.03em;color:#fff}
    .xs-media-copy p{margin:0;max-width:620px;font-size:clamp(15px,2vw,19px);color:rgba(255,255,255,.88);line-height:1.6}
    .xs-media-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:8px}
    /* Estado de reserva: la seccion esta configurada pero el video/imagen aun no
       se ha subido. En lugar de un bloque gris plano, pinta un lienzo de marca.
       Al subir el medio desaparece solo. */
    .xs-media.is-empty{background-image:linear-gradient(135deg,var(--primary) 0%,color-mix(in srgb,var(--primary) 72%,#000) 62%,color-mix(in srgb,var(--accent,var(--primary)) 55%,var(--primary)) 100%)!important;background-color:var(--primary)!important}
    .xs-media.is-empty .xs-media-overlay{opacity:.18}
    .xs-media.is-empty:before{content:'';position:absolute;right:-70px;top:-70px;width:280px;height:280px;border-radius:50%;background:color-mix(in srgb,var(--accent,#fff) 22%,transparent)}
    .xs-media.is-empty:after{content:'';position:absolute;left:-50px;bottom:-60px;width:210px;height:210px;border-radius:50%;background:color-mix(in srgb,#fff 10%,transparent)}
    .xs-media-play{display:grid;place-items:center;width:74px;height:74px;margin-bottom:4px;border-radius:50%;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.34);backdrop-filter:blur(2px)}
    .xs-media-play svg{width:28px;height:28px;margin-left:3px;color:#fff}
    /* ── category_rows: filas de producto por categoria ── */
    .xs-crows{padding-top:clamp(24px,3vw,40px)}
    .xs-crow{margin-bottom:clamp(28px,3.5vw,44px)}
    .xs-crow:last-child{margin-bottom:0}
    .xs-crow-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 20px;margin-bottom:16px;background:var(--primary);color:#fff;border-radius:8px;text-decoration:none;transition:filter .18s ease}
    .xs-crow-head:hover{filter:brightness(1.08)}
    .xs-crow-title{font-family:var(--font-title);font-size:clamp(15px,1.5vw,18px);font-weight:800;letter-spacing:.06em;text-transform:uppercase}
    .xs-crow-all{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;opacity:.92}
    .xs-crow-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}
    @media(max-width:1200px){.xs-crow-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
    @media(max-width:900px){.xs-crow-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}}
    @media(max-width:600px){.xs-crow-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}
        .xs-crow-head{padding:11px 14px;margin-bottom:12px}.xs-crow-title{font-size:13px}}
    /* ── collection_showcase ── */
    .xs-coll-grid{display:grid;grid-template-columns:repeat(var(--xs-cols,3),minmax(0,1fr));gap:18px}
    .xs-coll-card{position:relative;display:flex;align-items:flex-end;min-height:300px;border-radius:var(--radius-md);overflow:hidden;text-decoration:none;background:var(--surface-soft) center/cover no-repeat;box-shadow:var(--shadow-sm);transition:transform .25s ease,box-shadow .25s ease}
    .xs-coll-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg)}
    .xs-coll-card::before{content:"";position:absolute;inset:0;background:linear-gradient(185deg,transparent 30%,rgba(2,6,23,.85))}
    .xs-coll-copy{position:relative;padding:22px;color:#fff}.xs-coll-copy strong{display:block;font-family:var(--font-title);font-size:21px;letter-spacing:-.01em}.xs-coll-copy span{display:block;margin-top:4px;font-size:13.5px;color:rgba(255,255,255,.82)}
    .v-circles .xs-coll-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr));justify-items:center}
    .v-circles .xs-coll-card{width:min(190px,100%);aspect-ratio:1;min-height:0;border-radius:999px;align-items:center;justify-content:center;text-align:center}
    .v-circles .xs-coll-card::before{background:rgba(2,6,23,.38)}.v-circles .xs-coll-copy{padding:14px}.v-circles .xs-coll-copy span{display:none}
    .v-mosaic .xs-coll-grid{grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:170px}
    .v-mosaic .xs-coll-card{min-height:0}.v-mosaic .xs-coll-card:first-child{grid-column:span 2;grid-row:span 2}
    .v-banners .xs-coll-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .v-banners .xs-coll-card{min-height:190px;align-items:center}.v-banners .xs-coll-card::before{background:linear-gradient(90deg,rgba(2,6,23,.85) 20%,transparent 75%)}
    /* ── brands ── */
    .xs-brands{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:20px}
    .xs-brand{display:grid;place-items:center;text-decoration:none;flex:0 0 auto;width:160px;height:62px;box-sizing:border-box;padding:8px 14px;background:#fff;border:1px solid #E3E6EB;border-radius:8px;transition:border-color .18s ease,box-shadow .18s ease}
    .xs-brand:hover{border-color:color-mix(in srgb,var(--primary) 35%,#E3E6EB);box-shadow:0 4px 14px rgba(15,23,42,.06)}
    .xs-brand img{max-width:118px;max-height:36px;width:auto;height:auto;object-fit:contain;transition:filter .2s,opacity .2s}
    .xs-brands.is-gray .xs-brand img{filter:grayscale(1);opacity:.62}.xs-brands.is-gray .xs-brand:hover img{filter:none;opacity:1}
    .xs-brand-name{padding:0;border:0;border-radius:0;background:transparent;color:var(--secondary);font-family:var(--font-title);font-size:17px;font-weight:900;letter-spacing:.02em;text-align:center;text-transform:uppercase}
    .xs-brand-cta{display:inline-flex;align-items:center;gap:7px;flex:0 0 auto;height:62px;padding:0 18px;box-sizing:border-box;border:1.5px solid var(--primary);border-radius:8px;background:#fff;color:var(--primary);font-size:13.5px;font-weight:700;text-decoration:none;transition:background .18s ease,color .18s ease}
    .xs-brand-cta svg{width:15px;height:15px;transition:transform .18s ease}
    @media(hover:hover){.xs-brand-cta:hover{background:var(--primary);color:#fff}.xs-brand-cta:hover svg{transform:translateX(3px)}}
    @media(max-width:640px){.xs-brand-cta{height:48px;padding:0 14px;font-size:12.5px}}
    .v-grid .xs-brands{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr))}
    .v-grid .xs-brand{padding:8px 14px;border:1px solid #E3E6EB;border-radius:8px;background:#fff}
    .v-carousel .xs-brands{flex-wrap:nowrap;overflow-x:auto;justify-content:flex-start;scroll-snap-type:x mandatory;scrollbar-width:none;padding-bottom:6px}
    .v-carousel .xs-brands::-webkit-scrollbar{display:none}
    .xs-brands-wrap{position:relative}
    .xs-brands-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:2;display:grid;place-items:center;width:28px;height:28px;background:#fff;border:1px solid #E3E6EB;border-radius:50%;color:var(--secondary);cursor:pointer;transition:box-shadow .18s ease}
    .xs-brands-nav:hover{box-shadow:0 4px 12px rgba(15,23,42,.1)}

    /* En la cinta las tarjetas son mas grandes y el logo se ve a su tamaño:
       con 62px de alto y el logo a 36 no se apreciaba de que marca se trataba,
       que es justo para lo que esta la banda. */
    .v-marquee .xs-brand, .v-marquee-slow .xs-brand{width:190px;height:92px;padding:14px 18px}
    .v-marquee .xs-brand img, .v-marquee-slow .xs-brand img{max-width:150px;max-height:58px}
    .v-marquee .xs-brands, .v-marquee-slow .xs-brands{gap:28px}
    /* Y a color: en gris al 62% los logos se leian a medias y la cinta pasa
       sola, sin darle tiempo al visitante a pasar el raton para revelarlos. */
    .v-marquee .xs-brands.is-gray .xs-brand img,
    .v-marquee-slow .xs-brands.is-gray .xs-brand img{filter:none;opacity:1}
    @media(max-width:640px){
        .v-marquee .xs-brands, .v-marquee-slow .xs-brands{gap:18px}
        .v-marquee .xs-brand, .v-marquee-slow .xs-brand{width:150px;height:76px;padding:10px 14px}
        .v-marquee .xs-brand img, .v-marquee-slow .xs-brand img{max-width:118px;max-height:46px}
    }

    /* ── Cinta en movimiento continuo (de derecha a izquierda) ──────────
       La lista va duplicada y se desplaza media pista: al terminar, la copia
       esta justo donde empezo la original y el ciclo se reanuda sin salto.
       Se pausa al pasar el raton para poder leer o hacer clic. */
    .v-marquee .xs-brands, .v-marquee-slow .xs-brands{
        flex-wrap:nowrap; justify-content:flex-start; width:max-content;
        animation:xsMarquee 32s linear infinite;
    }
    .v-marquee-slow .xs-brands{animation-duration:56s}
    .v-marquee .xs-brands-wrap, .v-marquee-slow .xs-brands-wrap{
        overflow:hidden;
        /* Los bordes se desvanecen: sin esto las marcas aparecen y desaparecen
           de golpe contra el borde y se ve tosco. */
        -webkit-mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);
        mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent);
    }
    @media (hover:hover){
        .v-marquee .xs-brands-wrap:hover .xs-brands,
        .v-marquee-slow .xs-brands-wrap:hover .xs-brands{animation-play-state:paused}
    }
    @keyframes xsMarquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
    /* Quien pide menos movimiento no ve ninguno: la cinta se queda quieta y
       se puede desplazar a mano. */
    @media (prefers-reduced-motion:reduce){
        .v-marquee .xs-brands, .v-marquee-slow .xs-brands{
            animation:none; width:auto; overflow-x:auto; scrollbar-width:none;
        }
    }

    /* ── Tarjetas: cada marca en su caja, con relieve al pasar por encima. */
    .v-cards .xs-brands{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
    .v-cards .xs-brand{
        background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:14px;
        padding:18px 16px;min-height:96px;
        transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    @media (hover:hover){
        .v-cards .xs-brand:hover{transform:translateY(-3px);
            box-shadow:0 10px 24px rgba(15,23,42,.10);
            border-color:color-mix(in srgb, var(--primary) 34%, var(--border,#e5e7eb))}
    }
    .xs-brands-nav svg{width:14px;height:14px}
    .xs-brands-nav.prev{left:-12px}.xs-brands-nav.next{right:-12px}
    @media(max-width:640px){.xs-brands-nav{display:none}}
    .v-carousel .xs-brand{scroll-snap-align:start;flex:0 0 auto}
    /* ── testimonials ── */
    .xs-testi-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}
    .xs-testi{display:flex;flex-direction:column;gap:14px;padding:26px;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--surface);box-shadow:var(--shadow-sm)}
    .xs-testi-stars{color:#f59e0b;letter-spacing:2px;font-size:15px}
    .xs-testi p{margin:0;color:var(--text);line-height:1.65;font-size:14.5px}
    .xs-testi-who{display:flex;align-items:center;gap:12px;margin-top:auto}
    .xs-testi-who img{width:42px;height:42px;border-radius:999px;object-fit:cover}
    .xs-testi-who .ph{display:grid;place-items:center;width:42px;height:42px;border-radius:999px;background:color-mix(in srgb,var(--primary) 14%,transparent);color:var(--primary);font-weight:800}
    .xs-testi-who strong{display:block;color:var(--text-strong);font-size:14px}.xs-testi-who small{color:var(--muted)}
    .v-band .xs-testi-grid{grid-template-columns:1fr;max-width:760px;margin-inline:auto}
    .v-band .xs-testi{border:0;background:transparent;box-shadow:none;text-align:center;align-items:center}
    .v-band .xs-testi p{font-size:clamp(17px,2.4vw,22px);font-family:var(--font-title);color:var(--text-strong)}
    .v-carousel .xs-testi-grid{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(280px,44%);overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding-bottom:6px}
    .v-carousel .xs-testi{scroll-snap-align:start}
    /* ── gallery ── */
    .xs-gal{display:grid;grid-template-columns:repeat(var(--xs-cols,3),minmax(0,1fr));gap:12px}
    .xs-gal-item{position:relative;overflow:hidden;border-radius:var(--radius-sm);aspect-ratio:4/3;background:var(--surface-soft)}
    .xs-gal-item img{width:100%;height:100%;object-fit:cover;transition:transform .3s ease}.xs-gal-item:hover img{transform:scale(1.05)}
    .xs-gal-item iframe{width:100%;height:100%;border:0}
    .xs-gal-cap{position:absolute;inset:auto 0 0 0;padding:22px 14px 10px;background:linear-gradient(transparent,rgba(2,6,23,.78));color:#fff;font-size:13px}
    .v-mosaic .xs-gal{grid-auto-rows:150px}.v-mosaic .xs-gal-item{aspect-ratio:auto}.v-mosaic .xs-gal-item:nth-child(4n+1){grid-row:span 2}
    /* ── faq ── */
    /* El acordeon se centraba mientras el titular quedaba a la izquierda: dos
       ejes distintos dentro de la misma seccion. Ahora sigue al titular. */
    .xs-faq{max-width:820px;margin-inline:0;display:flex;flex-direction:column;gap:10px}
    body.section-heading-center .xs-faq{margin-inline:auto}
    .v-two-columns .xs-faq{max-width:none;display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}
    .xs-faq details{border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);overflow:hidden}
    .xs-faq summary{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:17px 20px;cursor:pointer;font-weight:700;color:var(--text-strong);list-style:none}
    .xs-faq summary::-webkit-details-marker{display:none}
    .xs-faq summary::after{content:"+";font-size:21px;color:var(--primary);transition:transform .2s}
    .xs-faq details[open] summary::after{transform:rotate(45deg)}
    .xs-faq-a{padding:0 20px 18px;color:var(--text);line-height:1.65;font-size:14.5px}
    /* ── wa_advisory ── */
    /* `space-between` empujaba el botón al borde y dejaba ~600px de verde vacío
       en medio: el ojo tenía que cruzar media pantalla desde la pregunta hasta
       la respuesta. Ahora el texto y el botón viajan juntos, centrados como un
       bloque, con una separación acotada. La banda sigue a ancho completo. */
    .xs-wa{display:flex;flex-wrap:wrap;align-items:center;justify-content:center;
        gap:clamp(24px,5vw,72px);padding:clamp(26px,3.2vw,38px) clamp(28px,4vw,44px);
        border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);
        /* La banda lleva el color de la tienda y el verde queda para el boton:
           pintar el fondo entero de verde WhatsApp metia un color ajeno a la
           paleta y le quitaba fuerza justo a la accion que se quiere pulsar. */
        background:linear-gradient(120deg,color-mix(in srgb,var(--primary) 74%,#000),var(--primary))}
    .xs-wa-copy{flex:0 1 auto;min-width:0}
    .xs-wa-copy h2{margin:0;color:#fff;font-family:var(--font-title);font-size:clamp(21px,2.6vw,29px)}
    .xs-wa-copy p{margin:8px 0 0;color:rgba(255,255,255,.9);max-width:560px;line-height:1.6}
    .xs-wa-btn{display:inline-flex;align-items:center;gap:10px;min-height:48px;padding:12px 24px;border-radius:999px;background:#25d366;color:#0b3d21;font-weight:800;text-decoration:none;transition:transform .18s}
    .xs-wa-btn:hover{transform:translateY(-2px)}.xs-wa-btn svg{width:22px;height:22px}
    .v-card .xs-wa{flex-direction:column;text-align:center;justify-content:center;background:var(--surface);color:var(--text);border:2px solid #25d366}
    .v-card .xs-wa-copy h2{color:var(--text-strong)}.v-card .xs-wa-copy p{color:var(--muted)}
    .v-card .xs-wa-btn{background:#25d366;color:#fff}
    /* ── delivery_banner ── */
    .xs-delivery{position:relative;overflow:hidden;border-radius:var(--radius-lg);display:grid;grid-template-columns:1.1fr auto 1.2fr;align-items:center;gap:clamp(18px,3vw,44px);padding:clamp(22px,3vw,36px) clamp(22px,4vw,56px);color:#fff;background:linear-gradient(100deg,#0a0a0a 0%,#262626 58%,#121212 100%)}
    .xs-delivery.v-brand{background:linear-gradient(100deg,color-mix(in srgb,var(--primary) 82%,#000) 0%,var(--primary) 60%,color-mix(in srgb,var(--primary) 70%,#000) 100%)}
    .xs-delivery.v-light{background:#f5f5f4;color:#111827}
    .xs-delivery-img{display:flex;align-items:center;justify-content:center;min-height:120px}
    .xs-delivery-img img{max-height:210px;max-width:100%;object-fit:contain;filter:drop-shadow(0 18px 24px rgba(0,0,0,.45))}
    .xs-delivery-placa{background:var(--xs-placa,var(--primary));color:#fff;border-radius:34px;padding:22px 44px;text-align:center;box-shadow:0 18px 40px -18px rgba(0,0,0,.6)}
    .xs-delivery-placa b{display:block;font-family:var(--font-title);font-size:clamp(34px,4.4vw,58px);font-weight:900;line-height:1;letter-spacing:-.02em}
    .xs-delivery-placa span{display:block;font-size:clamp(17px,2vw,26px);font-weight:800;margin-top:6px}
    .xs-delivery-txt h3{margin:0;font-size:clamp(17px,1.6vw,21px);font-weight:800;line-height:1.35}
    .xs-delivery-txt p{margin:12px 0 0;font-size:14px;line-height:1.55;opacity:.9}
    .xs-delivery-txt .button{margin-top:16px}
    @media(max-width:900px){.xs-delivery{grid-template-columns:1fr;text-align:center;gap:18px}.xs-delivery-placa{justify-self:center}.xs-delivery-img img{max-height:150px}}

    /* ── cta_banner ── */
    .xs-cta{position:relative;overflow:hidden;border-radius:var(--radius-lg);padding:clamp(36px,5vw,64px);display:flex;flex-direction:column;align-items:center;gap:14px;text-align:center;color:#fff;background:var(--xs-cta-bg,#0f172a) center/cover no-repeat}
    .xs-cta::before{content:"";position:absolute;inset:0;background:rgba(2,6,23,.45);opacity:var(--xs-cta-dim,0)}
    .xs-cta>*{position:relative}
    .xs-cta h2{margin:0;color:#fff;font-family:var(--font-title);font-size:clamp(25px,3.4vw,40px);letter-spacing:-.02em}
    .xs-cta p{margin:0;max-width:640px;color:rgba(255,255,255,.87);line-height:1.6}
    /* El ultimo tramo tiraba del color de acento: con un acento calido el
       degradado terminaba en oliva y ensuciaba la banda. Se queda en la familia
       de la marca, de mas oscuro a mas claro. */
    .xs-cta.is-brand{background:linear-gradient(130deg,color-mix(in srgb,var(--primary) 86%,#000) 0%,var(--primary) 55%,color-mix(in srgb,var(--primary) 78%,#fff) 100%)}
    .xs-cta.is-brand:before{content:'';position:absolute;right:-60px;top:-60px;width:240px;height:240px;border-radius:50%;background:color-mix(in srgb,var(--accent,#fff) 20%,transparent)}
    .xs-cta.is-brand>*{position:relative;z-index:1}
    /* El boton se fundia con el degradado de marca: contraste solido */
    .xs-cta.is-brand .button,.xs-cta.is-brand a.button-primary{background:#fff!important;color:var(--secondary,#0f172a)!important;border-color:#fff!important;font-weight:800}
    .xs-cta.is-brand .button:hover{background:rgba(255,255,255,.92)!important;transform:translateY(-1px)}
    .v-split .xs-cta{flex-direction:row;justify-content:space-between;text-align:left;align-items:center;flex-wrap:wrap}
    .xs-cta-copy{display:flex;flex-direction:column;align-items:center;gap:14px}
    .v-split .xs-cta-copy,.xs-cta.has-list .xs-cta-copy{align-items:flex-start;text-align:left}
    .xs-cta-actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
    .xs-cta .button-ghost{background:transparent;border:1.5px solid rgba(255,255,255,.72);color:#fff}
    .xs-cta .button-ghost:hover{background:rgba(255,255,255,.12)}
    .xs-cta.is-brand .button-ghost{background:transparent!important;color:#fff!important;border-color:rgba(255,255,255,.72)!important}
    .xs-cta.has-list{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,.75fr);align-items:center;gap:36px;text-align:left}
    .xs-cta.has-list p{max-width:none}
    .xs-cta-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px}
    .xs-cta-list li{display:flex;align-items:center;gap:12px;color:#fff;font-size:15.5px;font-weight:600}
    .xs-cta-list svg{width:24px;height:24px;flex:0 0 24px;color:var(--primary)}
    .xs-cta.is-brand .xs-cta-list svg{color:#fff}
    @media(max-width:760px){.xs-cta.has-list{grid-template-columns:1fr;gap:22px}.xs-cta-list li{font-size:14px}}
    @media(max-width:1023px){.xs-coll-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}.v-mosaic .xs-coll-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}.xs-testi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.xs-gal{grid-template-columns:repeat(2,minmax(0,1fr))!important}.v-two-columns .xs-faq{grid-template-columns:1fr}}
    @media(max-width:719px){.xs-coll-grid{grid-template-columns:1fr!important}.v-circles .xs-coll-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important}.v-mosaic .xs-coll-card:first-child{grid-column:span 2}.xs-testi-grid{grid-template-columns:1fr}.v-carousel .xs-testi-grid{grid-auto-columns:minmax(260px,86%)}.xs-media.h-medium{min-height:360px}.xs-media.h-large{min-height:440px}.xs-wa{justify-content:center;text-align:center}}
    @media(prefers-reduced-motion:reduce){.xs-coll-card,.xs-gal-item img,.xs-wa-btn{transition:none}}
</style>

{{-- ═══ BANNER MULTIMEDIA (imagen o video) ═══ --}}
@php $mbC = $sectionContentFor('media_banner'); @endphp
@if($isHomeSectionVisible('media_banner') && !empty($mbC))
@php
    $mbType = in_array($mbC['media_type'] ?? 'image', ['image','video_file','video_url'], true) ? ($mbC['media_type'] ?? 'image') : 'image';
    $mbDesk = $assetUrl($mbC['desktop_image'] ?? null);
    $mbMob = $assetUrl($mbC['mobile_image'] ?? null) ?: $mbDesk;
    $mbVideo = $mbType === 'video_file' ? $assetUrl($mbC['video_file'] ?? null) : null;
    $mbEmbed = $mbType === 'video_url' ? $xsEmbed($mbC['video_url'] ?? null) : null;
    $mbFallback = $assetUrl($mbC['fallback_image'] ?? null) ?: $mbDesk;
    // 'reel' = formato vertical 9:16 (tipo TikTok/Reels) con el video en una
    //  columna y el texto al lado en escritorio.
    $mbHeight = in_array($mbC['height'] ?? 'medium', ['small','medium','large','full','reel'], true) ? ($mbC['height'] ?? 'medium') : 'medium';
    $mbAlign = in_array($mbC['align'] ?? 'center', ['left','center','right'], true) ? ($mbC['align'] ?? 'center') : 'center';
    $mbAuto = !isset($mbC['autoplay']) || $mbC['autoplay'];
    $mbMuted = $mbAuto || !isset($mbC['muted']) || $mbC['muted'];   // autoplay exige silencio (navegadores)
    $mbLoop = !isset($mbC['loop']) || $mbC['loop'];
    $mbControls = !empty($mbC['show_controls']);
    $mbHasMedia = $mbDesk || $mbVideo || $mbEmbed;
@endphp
@if($mbHasMedia || filled($mbC['title'] ?? null))
<section class="xs-section xs-media h-{{ $mbHeight }}{{ $mbHasMedia ? '' : ' is-empty' }}" data-store-native-section="media_banner"
         style="order:{{ $sectionOrder('media_banner') }};--xs-overlay:{{ $color($mbC['overlay_color'] ?? null, '#0f172a') }};--xs-overlay-op:{{ max(0, min(90, (int) ($mbC['overlay_opacity'] ?? 45))) / 100 }}">
    <div class="xs-media-bg" aria-hidden="true">
        @if($mbVideo)
        <video @if($mbAuto) autoplay playsinline @endif @if($mbMuted) muted @endif @if($mbLoop) loop @endif @if($mbControls) controls @endif preload="metadata" @if($mbFallback) poster="{{ $mbFallback }}" @endif>
            <source src="{{ $mbVideo }}">
        </video>
        @elseif($mbEmbed)
        <iframe src="{{ $mbEmbed }}@if($mbAuto){{ str_contains($mbEmbed,'?') ? '&' : '?' }}autoplay=1&mute=1&loop=1&controls={{ $mbControls ? 1 : 0 }}@endif" title="Video" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin"></iframe>
        @elseif($mbDesk)
        <picture>
            @if($mbMob && $mbMob !== $mbDesk)<source media="(max-width:719px)" srcset="{{ $mbMob }}">@endif
            <img src="{{ $mbDesk }}" alt="" loading="lazy">
        </picture>
        @endif
    </div>
    <div class="xs-media-overlay" aria-hidden="true"></div>
    <div class="xs-media-copy al-{{ $mbAlign }}">
        @if(!$mbHasMedia && str_starts_with($mbType, 'video'))
        <span class="xs-media-play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
        @endif
        @if(filled($mbC['title'] ?? null))<h2>{{ $mbC['title'] }}</h2>@endif
        @if(filled($mbC['subtitle'] ?? null))<p>{{ $mbC['subtitle'] }}</p>@endif
        @if(filled($mbC['button_text'] ?? null) || filled($mbC['button2_text'] ?? null))
        <div class="xs-media-actions">
            @if(filled($mbC['button_text'] ?? null))<a class="button button-primary" href="{{ ($mbC['button_url'] ?? '') === '#catalogo' || blank($mbC['button_url'] ?? null) ? $shopUrl : $mbC['button_url'] }}">{{ $mbC['button_text'] }}</a>@endif
            @if(filled($mbC['button2_text'] ?? null))<a class="button" style="background:#fff;color:#0f172a" href="{{ blank($mbC['button2_url'] ?? null) ? $shopUrl : $mbC['button2_url'] }}">{{ $mbC['button2_text'] }}</a>@endif
        </div>
        @endif
    </div>
</section>
@endif
@endif

{{-- ═══ COLECCIONES / COMPRA POR AMBIENTE ═══ --}}
@php
    $csC = $sectionContentFor('collection_showcase');
    $csItems = $xsItems($csC);
    $csVariant = in_array($csC['variant'] ?? ($homeSectionByNativeKey->get('collection_showcase')?->variant ?? 'ambient'), ['ambient','circles','mosaic','banners'], true)
        ? ($csC['variant'] ?? ($homeSectionByNativeKey->get('collection_showcase')?->variant ?? 'ambient')) : 'ambient';
@endphp
@if($isHomeSectionVisible('collection_showcase') && $csItems->isNotEmpty())
<section class="xs-section v-{{ $csVariant }}" data-store-native-section="collection_showcase" style="order:{{ $sectionOrder('collection_showcase') }}"><div class="container">
    <div class="xs-head"><h2>{{ $csC['title'] ?? 'Colecciones' }}</h2>@if(filled($csC['subtitle'] ?? null))<p>{{ $csC['subtitle'] }}</p>@endif</div>
    <div class="xs-coll-grid" style="--xs-cols:{{ max(2, min(4, (int) ($csC['columns'] ?? 3))) }}">
        @foreach($csItems as $item)
        @php $csImg = $xsItemImage($item); @endphp
        <a class="xs-coll-card" href="{{ $xsUrl($item) }}" @if($csImg) style="background-image:url('{{ $csImg }}')" @endif>
            <span class="xs-coll-copy"><strong>{{ $item['title'] ?? '' }}</strong>@if(filled($item['subtitle'] ?? null))<span>{{ $item['subtitle'] }}</span>@endif</span>
        </a>
        @endforeach
    </div>
</div></section>
@endif

{{-- ═══ MARCAS ═══ --}}
@php
    $brC = $sectionContentFor('brands');
    $brItems = $xsItems($brC);
    $brVariant = in_array($brC['variant'] ?? 'strip', ['strip','grid','carousel','marquee','marquee-slow','cards'], true) ? ($brC['variant'] ?? 'strip') : 'strip';
    // La cinta en movimiento necesita la lista DUPLICADA: al llegar la copia al
    // inicio, la animacion vuelve a empezar y el salto no se ve. Con pocas
    // marcas se repite mas veces, o se veria el hueco entre vuelta y vuelta.
    $brEsCinta = in_array($brVariant, ['marquee','marquee-slow'], true);
    $brVueltas = $brEsCinta ? max(2, (int) ceil(12 / max(1, $brItems->count()))) : 1;
@endphp
@if($isHomeSectionVisible('brands') && $brItems->isNotEmpty())
<section class="xs-section v-{{ $brVariant }}" data-store-native-section="brands" style="order:{{ $sectionOrder('brands') }}"><div class="container">
    @if(filled($brC['title'] ?? null))<div class="xs-head"><h2>{{ $brC['title'] }}</h2>@if(filled($brC['subtitle'] ?? null))<p>{{ $brC['subtitle'] }}</p>@endif</div>@endif
    <div class="xs-brands-wrap" x-data="{ sc(d){ const t=$refs.brandTrack; if(t) t.scrollBy({left:d*280,behavior:'smooth'}); } }">
    @if($brItems->count() > 4 && !$brEsCinta)
    <button type="button" class="xs-brands-nav prev" @click="sc(-1)" aria-label="Anterior"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 6-6 6 6 6"/></svg></button>
    <button type="button" class="xs-brands-nav next" @click="sc(1)" aria-label="Siguiente"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m9 6 6 6-6 6"/></svg></button>
    @endif
    <div class="xs-brands {{ ($brC['grayscale'] ?? true) ? 'is-gray' : '' }}" x-ref="brandTrack">
        @for($brVuelta = 0; $brVuelta < $brVueltas; $brVuelta++)
        @foreach($brItems as $brand)
        @php
            $bUrl = trim((string) ($brand['url'] ?? ''));
            $bName = $brand['name'] ?? '';
            // Imagen del item o, si no tiene, el logo de la marca del catalogo.
            $bImg = $assetUrl($brand['image'] ?? null) ?: $assetUrl(($marcasImgPorNombre ?? [])[mb_strtoupper($bName)] ?? null);
            // Enlace al FILTRO por marca, no a una busqueda de texto: buscar
            // "3M" por texto devolvia cualquier producto con "3M" en el nombre
            // y se saltaba los que solo la llevan como marca.
            $bId = $marcasPorNombre[mb_strtoupper($bName)] ?? null;
            $bDestino = $bUrl !== ''
                ? $bUrl
                : ($bId
                    ? ($shopBase ?? '#').'?brand[]='.$bId
                    : ($shopBase ?? '#').'?q='.urlencode($bName));
        @endphp
        {{-- Siempre <a> (nunca tag dinámico: el compilador Blade no lo soporta) --}}
        {{-- Las copias de la cinta son decorativas: se ocultan al lector de
             pantalla y se sacan del recorrido del tabulador, o la lista de
             marcas se leeria varias veces seguidas. --}}
        <a class="xs-brand" href="{{ $bDestino }}" @if($bUrl !== '') target="_blank" rel="noopener" @endif
           @if($brVuelta > 0) aria-hidden="true" tabindex="-1" @endif>
            @if($bImg)<img src="{{ $bImg }}" alt="{{ $brVuelta > 0 ? '' : ($bName ?: 'Marca') }}" loading="lazy">
            @else<span class="xs-brand-name">{{ $bName }}</span>@endif
        </a>
        @endforeach
        @endfor
        {{-- Acceso a la pagina de marcas. Es lo que convierte la banda en algo
             navegable y no solo decorativo. Configurable; vacio = sin boton. --}}
        @php
            // El interruptor manda: apagado, no hay boton aunque haya texto.
            $brCta = ($brC['show_cta'] ?? true) === false
                ? ''
                : trim((string) ($brC['cta_text'] ?? ($settings['brands_band_cta'] ?? 'Ver todas las marcas')));
        @endphp
        @if($brCta !== '' && !$brEsCinta)
        <a class="xs-brand-cta" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::publicUrl($project) }}/marcas">{{ $brCta }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        @endif
    </div>
    @if($brCta !== '' && $brEsCinta)
    {{-- Fuera de la cinta y centrado: dentro se desplazaria con las marcas. --}}
    <div style="display:flex;justify-content:center;margin-top:16px">
        <a class="xs-brand-cta" href="{{ \App\Modules\Tienda\Support\StorefrontNavigation::publicUrl($project) }}/marcas">{{ $brCta }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    </div>
    @endif
</div></section>
@endif

{{-- ═══ TESTIMONIOS ═══ --}}
@php
    $tsC = $sectionContentFor('testimonials');
    $tsVariant = in_array($tsC['variant'] ?? 'cards', ['cards','carousel','band'], true) ? ($tsC['variant'] ?? 'cards') : 'cards';
    $tsLimit = max(1, min(9, (int) ($tsC['limit'] ?? 3)));
    if (($tsC['source'] ?? 'manual') === 'reviews') {
        $tsItems = \App\Modules\Tienda\Models\Review::where('project_id', $project->id)->where('is_approved', true)
            ->orderByDesc('rating')->orderByDesc('id')->take($tsLimit)->get()
            ->map(fn ($review) => ['name' => $review->customer_name ?: 'Cliente verificado', 'role' => 'Compra verificada', 'text' => (string) $review->comment, 'rating' => (int) $review->rating, 'image' => null])
            ->filter(fn ($i) => trim($i['text']) !== '')->values();
    } else {
        $tsItems = $xsItems($tsC)->take($tsLimit);
    }
@endphp
@if($isHomeSectionVisible('testimonials') && $tsItems->isNotEmpty())
<section class="xs-section v-{{ $tsVariant }}" data-store-native-section="testimonials" style="order:{{ $sectionOrder('testimonials') }}"><div class="container">
    <div class="xs-head"><h2>{{ $tsC['title'] ?? 'Lo que dicen nuestros clientes' }}</h2>@if(filled($tsC['subtitle'] ?? null))<p>{{ $tsC['subtitle'] }}</p>@endif</div>
    <div class="xs-testi-grid">
        @foreach($tsItems as $t)
        <article class="xs-testi">
            @php $rating = max(0, min(5, (int) ($t['rating'] ?? 5))); @endphp
            @if($rating > 0)<span class="xs-testi-stars" aria-label="{{ $rating }} de 5 estrellas">{{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }}</span>@endif
            <p>“{{ $t['text'] ?? '' }}”</p>
            <div class="xs-testi-who">
                @if($assetUrl($t['image'] ?? null))<img src="{{ $assetUrl($t['image']) }}" alt="" loading="lazy">
                @else<span class="ph">{{ mb_strtoupper(mb_substr(trim($t['name'] ?? 'C'), 0, 1)) }}</span>@endif
                <span><strong>{{ $t['name'] ?? '' }}</strong>@if(filled($t['role'] ?? null))<small>{{ $t['role'] }}</small>@endif</span>
            </div>
        </article>
        @endforeach
    </div>
</div></section>
@endif

{{-- ═══ GALERÍA ═══ --}}
@php
    $glC = $sectionContentFor('gallery');
    $glItems = $xsItems($glC);
    $glVariant = in_array($glC['variant'] ?? 'grid', ['grid','mosaic'], true) ? ($glC['variant'] ?? 'grid') : 'grid';
@endphp
@if($isHomeSectionVisible('gallery') && $glItems->isNotEmpty())
<section class="xs-section v-{{ $glVariant }}" data-store-native-section="gallery" style="order:{{ $sectionOrder('gallery') }}"><div class="container">
    <div class="xs-head"><h2>{{ $glC['title'] ?? 'Galería' }}</h2>@if(filled($glC['subtitle'] ?? null))<p>{{ $glC['subtitle'] }}</p>@endif</div>
    <div class="xs-gal" style="--xs-cols:{{ max(2, min(4, (int) ($glC['columns'] ?? 3))) }}">
        @foreach($glItems as $g)
        @php $gEmbed = ($g['type'] ?? 'image') === 'video_url' ? $xsEmbed($g['video_url'] ?? null) : null; @endphp
        <figure class="xs-gal-item">
            @if($gEmbed)<iframe src="{{ $gEmbed }}" title="{{ $g['caption'] ?? 'Video' }}" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
            @elseif($assetUrl($g['image'] ?? null))<img src="{{ $assetUrl($g['image']) }}" alt="{{ $g['caption'] ?? '' }}" loading="lazy">@endif
            @if(filled($g['caption'] ?? null) && !$gEmbed)<figcaption class="xs-gal-cap">{{ $g['caption'] }}</figcaption>@endif
        </figure>
        @endforeach
    </div>
</div></section>
@endif

{{-- ═══ PREGUNTAS FRECUENTES ═══ --}}
@php
    $fqC = $sectionContentFor('faq');
    $fqItems = $xsItems($fqC)->filter(fn ($i) => filled($i['question'] ?? null));
    $fqVariant = in_array($fqC['variant'] ?? 'accordion', ['accordion','two-columns'], true) ? ($fqC['variant'] ?? 'accordion') : 'accordion';
@endphp
@if($isHomeSectionVisible('faq') && $fqItems->isNotEmpty())
<section class="xs-section v-{{ $fqVariant }}" data-store-native-section="faq" style="order:{{ $sectionOrder('faq') }}"><div class="container">
    <div class="xs-head"><h2>{{ $fqC['title'] ?? 'Preguntas frecuentes' }}</h2>@if(filled($fqC['subtitle'] ?? null))<p>{{ $fqC['subtitle'] }}</p>@endif</div>
    <div class="xs-faq">
        @foreach($fqItems as $f)
        <details @if($loop->first && $fqVariant === 'accordion') open @endif>
            <summary>{{ $f['question'] }}</summary>
            <div class="xs-faq-a">{{ $f['answer'] ?? '' }}</div>
        </details>
        @endforeach
    </div>
</div></section>
@endif

{{-- ═══ ASESORÍA POR WHATSAPP ═══ --}}
@php
    $waC = $sectionContentFor('wa_advisory');
    $waVariant = in_array($waC['variant'] ?? 'band', ['band','card'], true) ? ($waC['variant'] ?? 'band') : 'band';
    $waPhone = preg_replace('/\D/', '', (string) ($waC['phone'] ?? '')) ?: $whatsapp;
    $waMsg = rawurlencode($waC['message'] ?? 'Hola, necesito asesoría sobre un producto.');
@endphp
@if($isHomeSectionVisible('wa_advisory') && $waPhone && filled($waC['title'] ?? null))
<section class="xs-section v-{{ $waVariant }}" data-store-native-section="wa_advisory" style="order:{{ $sectionOrder('wa_advisory') }}"><div class="container">
    <div class="xs-wa">
        <div class="xs-wa-copy">
            <h2>{{ $waC['title'] }}</h2>
            @if(filled($waC['subtitle'] ?? null))<p>{{ $waC['subtitle'] }}</p>@endif
        </div>
        <a class="xs-wa-btn" href="https://wa.me/{{ $waPhone }}?text={{ $waMsg }}" target="_blank" rel="noopener">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.4 14.1c-.2.7-1.3 1.3-1.9 1.4-.5.1-1.1.2-3.3-.7-2.8-1.1-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 .9-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.1.1.3 0 .5l-.4.6c-.1.2-.3.4-.1.7.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.6.4 0 .1 0 .7-.2 1.3Z"/></svg>
            {{ $waC['button_text'] ?? 'Hablar por WhatsApp' }}
        </a>
    </div>
</div></section>
@endif

{{-- ═══ LLAMADA A LA ACCIÓN ═══ --}}
{{-- ═══ FILAS POR CATEGORIA: cada fila muestra los productos de una categoria
     con su cabecera (Laptops, Monitores, Impresoras...). Generico: cualquier
     rubro define sus filas desde el Constructor. ═══ --}}
@php
    // Lectura directa del registro: no depende del mapa de alias nativos.
    $crSec = \App\Modules\Tienda\Models\StoreSection::where('project_id', $project->id)
        ->where('page', 'home')->where('component', 'category_rows')
        ->where('is_enabled', true)->first();
    $crC = $crSec ? (is_array($crSec->content) ? $crSec->content : (array) json_decode((string) $crSec->content, true)) : [];
@endphp
@if($crSec && !empty($crC['rows']))
@php
    $crLimit = max(2, min(10, (int) ($crC['limit'] ?? 5)));
    $crRows = collect($crC['rows'])->filter(fn ($r) => !empty($r['category_id']) && ($r['enabled'] ?? true))->values();
@endphp
@if($crRows->isNotEmpty())
<section class="xs-section xs-crows" data-store-native-section="category_rows" style="order:{{ $sectionOrder('category_rows') }}">
    <div class="container">
        @foreach($crRows as $crRow)
        @php
            $crId = (int) $crRow['category_id'];
            // Productos de la categoria (incluye sus subcategorias) directo del modelo:
            // el listado del scope solo trae las raices cargadas.
            $crIds = \App\Models\Category::where('project_id', $project->id)
                ->where(fn ($q) => $q->where('id', $crId)->orWhere('parent_id', $crId))
                ->pluck('id');
            $crProds = \App\Models\Product::where('project_id', $project->id)
                ->whereIn('category_id', $crIds)
                ->where('is_available', true)
                ->with('category')
                ->orderByDesc('id')->take($crLimit)->get();
            $crName = \App\Models\Category::where('id', $crId)->value('name') ?? ($crRow['title'] ?? '');
        @endphp
        @if($crProds->isNotEmpty())
        <div class="xs-crow">
            <a class="xs-crow-head" href="{{ $shopUrl }}?category={{ $crId }}">
                <span class="xs-crow-title">{{ $crRow['title'] ?? $crName }}</span>
                <span class="xs-crow-all">Ver todo <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"/></svg></span>
            </a>
            <div class="xs-crow-grid">
                @foreach($crProds as $p)
                    @include('tienda::public.templates.partials.computienda-card', ['p' => $p])
                @endforeach
            </div>
        </div>
        @endif
        @endforeach
    </div>
</section>
@endif
@endif

@php
    $ctC = $sectionContentFor('cta_banner');
    $ctVariant = in_array($ctC['variant'] ?? 'wide', ['wide','split'], true) ? ($ctC['variant'] ?? 'wide') : 'wide';
    $ctImage = $assetUrl($ctC['image'] ?? null);
@endphp
@if($isHomeSectionVisible('cta_banner') && filled($ctC['title'] ?? null))

<section class="xs-section v-{{ $ctVariant }}" data-store-native-section="cta_banner" style="order:{{ $sectionOrder('cta_banner') }}"><div class="container">
    {{-- Sin color propio cae a un degradado de marca (antes: navy fijo #0f172a, ajeno a tiendas con otra paleta) --}}
    @php
        // Segundo boton (asesor por WhatsApp) y lista de beneficios a la derecha.
        // Con lista, la banda pasa a dos columnas; sin ella, se queda centrada.
        $ctList = collect(range(1, 4))->map(fn ($n) => trim((string) ($ctC["benefit_{$n}"] ?? '')))->filter()->values();
        $ctBtn2Url = trim((string) ($ctC['button2_url'] ?? ''));
        if ($ctBtn2Url === '' && $whatsapp) $ctBtn2Url = 'https://wa.me/'.$whatsapp;
    @endphp
    <div class="xs-cta{{ $ctImage || filled($ctC['background_color'] ?? null) ? '' : ' is-brand' }}{{ $ctList->isNotEmpty() ? ' has-list' : '' }}" style="--xs-cta-bg:{{ $ctImage ? "url('{$ctImage}')" : $color($ctC['background_color'] ?? null, '#0f172a') }};--xs-cta-dim:{{ $ctImage ? '1' : '0' }}">
        <div class="xs-cta-copy">
        <h2>{{ $ctC['title'] }}</h2>
        @if(filled($ctC['subtitle'] ?? null))<p>{{ $ctC['subtitle'] }}</p>@endif
        @if(filled($ctC['button_text'] ?? null) || (filled($ctC['button2_text'] ?? null) && $ctBtn2Url !== ''))
        <div class="xs-cta-actions">
            @if(filled($ctC['button_text'] ?? null))
            <a class="button button-primary" href="{{ ($ctC['button_url'] ?? '') === '#catalogo' || blank($ctC['button_url'] ?? null) ? $shopUrl : $ctC['button_url'] }}">{{ $ctC['button_text'] }}</a>
            @endif
            @if(filled($ctC['button2_text'] ?? null) && $ctBtn2Url !== '')
            <a class="button button-ghost" href="{{ $ctBtn2Url }}" @if(str_starts_with($ctBtn2Url, 'http')) target="_blank" rel="noopener" @endif>{{ $ctC['button2_text'] }}</a>
            @endif
        </div>
        @endif
        </div>
        @if($ctList->isNotEmpty())
        <ul class="xs-cta-list">
            @foreach($ctList as $b)
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg><span>{{ $b }}</span></li>
            @endforeach
        </ul>
        @endif
    </div>
</div></section>
@endif

{{-- ═══ DELIVERY ═══ --}}
@php
    $dvC = $sectionContentFor('delivery_banner');
    $dvVariant = in_array($dvC['variant'] ?? 'dark', ['dark','brand','light'], true) ? ($dvC['variant'] ?? 'dark') : 'dark';
    $dvImage = $assetUrl($dvC['image'] ?? null);
    $dvPlaca = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($dvC['badge_color'] ?? '')) ? $dvC['badge_color'] : null;
@endphp
@if($isHomeSectionVisible('delivery_banner') && (filled($dvC['badge_line1'] ?? null) || filled($dvC['title'] ?? null)))
<section class="xs-section" data-store-native-section="delivery_banner" style="order:{{ $sectionOrder('delivery_banner') }}"><div class="container">
    <div class="xs-delivery v-{{ $dvVariant }}" @if($dvPlaca) style="--xs-placa:{{ $dvPlaca }}" @endif>
        <div class="xs-delivery-img">@if($dvImage)<img src="{{ $dvImage }}" alt="" loading="lazy">@endif</div>
        @if(filled($dvC['badge_line1'] ?? null))
        <div class="xs-delivery-placa"><b>{{ $dvC['badge_line1'] }}</b>@if(filled($dvC['badge_line2'] ?? null))<span>{{ $dvC['badge_line2'] }}</span>@endif</div>
        @endif
        <div class="xs-delivery-txt">
            @if(filled($dvC['title'] ?? null))<h3>{{ $dvC['title'] }}</h3>@endif
            @if(filled($dvC['note'] ?? null))<p>{{ $dvC['note'] }}</p>@endif
            @if(filled($dvC['button_text'] ?? null))<a class="button button-primary" href="{{ blank($dvC['button_url'] ?? null) ? $shopUrl : $dvC['button_url'] }}">{{ $dvC['button_text'] }}</a>@endif
        </div>
    </div>
</div></section>
@endif

{{-- ═══ SUCURSALES Y UBICACIÓN ═══ --}}
@php
    $lcC = $sectionContentFor('locations');
    $lcItems = \App\Modules\Tienda\Support\StorefrontSections::locationItems($project, $lcC, (array) $settings);
    $lcVariant = in_array($lcC['variant'] ?? 'cards', ['cards','map-side','map-wide','map-hero','map-split','compact'], true) ? ($lcC['variant'] ?? 'cards') : 'cards';
@endphp
@if($isHomeSectionVisible('locations') && $lcItems->isNotEmpty())
<style>
    .xs-loc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
    .xs-loc{display:flex;flex-direction:column;border:1px solid var(--border);border-radius:var(--radius-md);background:var(--surface);box-shadow:var(--shadow-sm);overflow:hidden}
    .xs-loc-map iframe{width:100%;height:190px;border:0;display:block;background:#e2e8f0}
    .xs-loc-body{padding:18px 20px}
    .xs-loc-body strong{display:block;color:var(--text-strong);font-size:16px;font-family:var(--font-title)}
    .xs-loc-body p{margin:7px 0 0;color:var(--text);font-size:13.5px;line-height:1.55}
    .xs-loc-body small{color:var(--muted)}
    /* El horario (o los puestos del local) iba en gris apagado y se perdia,
       cuando es justo lo que el cliente necesita para encontrarte dentro del
       edificio. Se le da el mismo peso que al telefono, con un punto de color. */
    .xs-loc-body p:last-of-type small{
        display:inline-flex; align-items:center; gap:7px;
        margin-top:2px; padding:5px 10px;
        background:color-mix(in srgb, var(--primary) 7%, #fff);
        border:1px solid color-mix(in srgb, var(--primary) 18%, #E3E7EE);
        border-radius:8px;
        color:var(--text); font-size:12.5px; font-weight:600;
    }
    .xs-loc-body p:last-of-type small::before{
        content:''; width:6px; height:6px; border-radius:50%;
        background:var(--primary); flex:0 0 6px;
    }
    .xs-loc-link{display:inline-block;margin-top:10px;color:var(--primary);font-size:12.5px;font-weight:700;text-decoration:none}
    .v-map-side .xs-loc-grid{grid-template-columns:1fr}
    .v-map-side .xs-loc{flex-direction:row}
    .v-map-side .xs-loc-map{flex:0 0 46%}
    .v-map-side .xs-loc-map iframe{height:100%;min-height:220px}
    @media(max-width:760px){.v-map-side .xs-loc{flex-direction:column}.v-map-side .xs-loc-map iframe{height:180px}}

    /* ── Mapa ancho: una sola fila, el mapa manda y los datos van debajo.
          Es el que mejor funciona con UNA sucursal, que es el caso comun. */
    .v-map-wide .xs-loc-grid{grid-template-columns:1fr}
    .v-map-wide .xs-loc-map iframe{height:340px}
    .v-map-wide .xs-loc-body{padding:20px 24px 22px}
    @media(max-width:760px){.v-map-wide .xs-loc-map iframe{height:240px}}

    /* ── Mapa de fondo con la ficha encima. El mapa ocupa toda la banda y la
          tarjeta flota sobre el, como en las webs de tiendas fisicas. */
    .v-map-hero .xs-loc-grid{grid-template-columns:1fr}
    .v-map-hero .xs-loc{position:relative; border:0; background:transparent; overflow:visible}
    .v-map-hero .xs-loc-map{border-radius:16px; overflow:hidden}
    .v-map-hero .xs-loc-map iframe{height:420px}
    .v-map-hero .xs-loc-body{
        position:absolute; left:28px; bottom:28px; right:auto; width:min(360px, calc(100% - 56px));
        background:#fff; border:1px solid var(--border,#e5e7eb); border-radius:14px;
        box-shadow:0 18px 40px rgba(15,23,42,.18); padding:18px 20px;
    }
    /* En movil la ficha flotante taparia el mapa entero: se coloca debajo. */
    @media(max-width:760px){
        .v-map-hero .xs-loc-map iframe{height:260px}
        .v-map-hero .xs-loc-body{position:static; width:auto; margin-top:12px; box-shadow:none}
    }

    /* ── Pantalla partida: mitad mapa, mitad datos, ambos a la misma altura. */
    .v-map-split .xs-loc-grid{grid-template-columns:1fr}
    .v-map-split .xs-loc{flex-direction:row; align-items:stretch}
    .v-map-split .xs-loc-map{flex:0 0 58%}
    .v-map-split .xs-loc-map iframe{height:100%; min-height:300px}
    .v-map-split .xs-loc-body{flex:1; display:flex; flex-direction:column; justify-content:center; padding:26px 28px}
    .v-map-split .xs-loc-body strong{font-size:19px}
    @media(max-width:860px){
        .v-map-split .xs-loc{flex-direction:column}
        .v-map-split .xs-loc-map iframe{height:220px; min-height:0}
        .v-map-split .xs-loc-body{padding:18px 20px}
    }

    /* ── Solo datos: sin mapa. Para quien no quiere ensenar la ubicacion
          exacta, o vende a domicilio y el mapa no aporta. */
    .v-compact .xs-loc-grid{grid-template-columns:repeat(auto-fit,minmax(240px,1fr))}
    .v-compact .xs-loc-body{padding:18px 20px}
</style>
<section class="xs-section v-{{ $lcVariant }}" data-store-native-section="locations" style="order:{{ $sectionOrder('locations') }}"><div class="container">
    <div class="xs-head"><h2>{{ $lcC['title'] ?? 'Visítanos' }}</h2>@if(filled($lcC['subtitle'] ?? null))<p>{{ $lcC['subtitle'] }}</p>@endif</div>
    <div class="xs-loc-grid">
        @foreach($lcItems as $loc)
        <article class="xs-loc">
            @if($lcVariant !== 'compact' && ($loc['show_map'] ?? true) && filled($loc['address'] ?? null))
            <div class="xs-loc-map">
                <iframe src="https://www.google.com/maps?q={{ urlencode($loc['address']) }}&output=embed&hl=es"
                        title="Mapa: {{ $loc['name'] ?? $loc['address'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            @endif
            <div class="xs-loc-body">
                @if(filled($loc['name'] ?? null))<strong>{{ $loc['name'] }}</strong>@endif
                @if(filled($loc['address'] ?? null))<p>{{ $loc['address'] }}</p>@endif
                @if(filled($loc['phone'] ?? null))<p><small>Tel:</small> {{ $loc['phone'] }}</p>@endif
                @if(filled($loc['hours'] ?? null))<p><small>{{ $loc['hours'] }}</small></p>@endif
                @if(filled($loc['address'] ?? null))<a class="xs-loc-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($loc['address']) }}" target="_blank" rel="noopener">Cómo llegar →</a>@endif
            </div>
        </article>
        @endforeach
    </div>
    </div>
</div></section>
@endif

{{-- ═══ BANDA INFORMATIVA (4 bloques) ═══ --}}
@php
    $isC = $sectionContentFor('info_strip');
    $isItems = $xsItems($isC)->filter(fn ($i) => filled($i['title'] ?? null))->take(4);
    $isIcons = [
        'home'    => '<path d="m3 11 9-8 9 8"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
        'sofa'    => '<path d="M4 12V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><path d="M2 13a2 2 0 0 1 4 0v3h12v-3a2 2 0 0 1 4 0v6H2Z"/>',
        'plug'    => '<rect x="4" y="3" width="16" height="14" rx="2"/><path d="M9 21h6M12 17v4M8 7h.01M12 7h.01"/>',
        'shield'  => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'truck'   => '<path d="M1 4h14v12H1z"/><path d="M15 9h4l4 4v3h-8"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/>',
        'box'     => '<path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/>',
        'support' => '<path d="M4 13a8 8 0 0 1 16 0"/><rect x="2" y="13" width="4" height="7" rx="1.6"/><rect x="18" y="13" width="4" height="7" rx="1.6"/>',
        'check'   => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.6 2.6L16.5 9"/>',
    ];
@endphp
@if($isHomeSectionVisible('info_strip') && $isItems->isNotEmpty())
<style>
    /* El crema y el borde arena venian fijos: en una tienda tecnologica se leian
       como un bloque prestado de otra marca. Ahora salen del tema. */
    .xs-strip{display:grid;grid-template-columns:repeat({{ max(1, $isItems->count()) }},minmax(0,1fr));min-height:128px;background:var(--surface-soft,#f8fafc);border:1px solid var(--border,#e2e8f0);border-radius:var(--r-block,12px);overflow:hidden}
    .xs-strip-item{display:flex;align-items:center;gap:16px;padding:20px 24px;border-left:1px solid var(--border,#e2e8f0);color:inherit;text-decoration:none}
    .xs-strip-item:first-child{border-left:0}
    .xs-strip-media{flex:0 0 auto;display:grid;place-items:center;width:76px;height:76px;border-radius:var(--r-btn,8px);overflow:hidden;background:var(--surface,#fff)}
    .xs-strip-media img{width:100%;height:100%;object-fit:cover}
    .xs-strip-media svg{width:34px;height:34px;color:var(--secondary)}
    .xs-strip-copy{min-width:0}
    .xs-strip-copy strong{display:block;color:var(--text-strong);font-size:14px;font-weight:700;line-height:1.3}
    .xs-strip-copy span{display:block;margin-top:5px;color:var(--muted);font-size:12.5px;line-height:1.45}
    @media(max-width:980px){.xs-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.xs-strip-item:nth-child(3){border-left:0}.xs-strip-item:nth-child(n+3){border-top:1px solid var(--border,#e2e8f0)}}
    @media(max-width:560px){.xs-strip{grid-template-columns:1fr}.xs-strip-item{border-left:0;border-top:1px solid var(--border,#e2e8f0)}.xs-strip-item:first-child{border-top:0}}
</style>
<section class="xs-section" data-store-native-section="info_strip" style="order:{{ $sectionOrder('info_strip') }}"><div class="container">
    @if(filled($isC['title'] ?? null))<div class="xs-head"><h2>{{ $isC['title'] }}</h2>@if(filled($isC['subtitle'] ?? null))<p>{{ $isC['subtitle'] }}</p>@endif</div>@endif
    <div class="xs-strip">
        @foreach($isItems as $it)
        @php $itImg = $assetUrl($it['image'] ?? null); $itUrl = trim((string) ($it['url'] ?? '')); @endphp
        <a class="xs-strip-item" href="{{ $itUrl !== '' ? $itUrl : ($shopUrl ?? '#') }}">
            <span class="xs-strip-media">
                @if($itImg)<img src="{{ $itImg }}" alt="{{ $it['title'] }}" loading="lazy">
                @else<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $isIcons[$it['icon'] ?? 'check'] ?? $isIcons['check'] !!}</svg>@endif
            </span>
            <span class="xs-strip-copy">
                <strong>{{ $it['title'] }}</strong>
                @if(filled($it['description'] ?? null))<span>{{ $it['description'] }}</span>@endif
            </span>
        </a>
        @endforeach
    </div>
</div></section>
@endif

{{-- ═══ NOSOTROS (RESUMEN EN INICIO) ═══ --}}
@php
    $apC = $sectionContentFor('about_preview');
    $apVariant = in_array($apC['variant'] ?? 'image-left', ['image-left','image-right'], true) ? ($apC['variant'] ?? 'image-left') : 'image-left';
    $apImage = $assetUrl($apC['image'] ?? null);
    $apIndicators = $xsItems($apC)->filter(fn ($i) => filled($i['title'] ?? null) || filled($i['value'] ?? null));
    $apIconPaths = [
        'award'  => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14.5-2 7 5.5-3 5.5 3-2-7"/>',
        'users'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.2 3.4-5 6.5-5s5.7 1.8 6.5 5"/><circle cx="17" cy="9" r="2.6"/><path d="M16.5 14.6c2.4.3 4.3 1.8 5 4.4"/>',
        'shield' => '<path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'star'   => '<path d="m12 2 3 6.6 7 .9-5.2 4.9 1.3 7-6.1-3.5L5.9 21l1.3-7L2 9.5l7-.9L12 2Z"/>',
        'heart'  => '<path d="M12 21C7 16.6 3 13.3 3 9.3 3 6.4 5.2 4 8 4c1.6 0 3.1.8 4 2 1-1.2 2.4-2 4-2 2.8 0 5 2.4 5 5.3 0 4-4 7.3-9 11.7Z"/>',
        'truck'  => '<path d="M1 4h14v12H1z"/><path d="M15 9h4l4 4v3h-8"/><circle cx="6" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/>',
        'check'  => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.6 2.6L16.5 9"/>',
        'home'   => '<path d="m3 11 9-8 9 8"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
    ];
@endphp
@if($isHomeSectionVisible('about_preview') && (filled($apC['title'] ?? null) || filled($apC['body'] ?? null)))
<style>
    .xs-about{display:grid;grid-template-columns:minmax(0,5fr) minmax(0,7fr);gap:clamp(26px,4vw,54px);align-items:center}
    .xs-about--no-img{grid-template-columns:1fr;max-width:820px;margin:0 auto;text-align:center}
    /* Si la tienda alinea sus titulos a la izquierda, este bloque no puede
       ser el unico centrado: rompia la lectura de la columna. */
    body:not(.section-heading-center) .xs-about--no-img{margin-inline:0;text-align:left}
    body:not(.section-heading-center) .xs-about--no-img .xs-about-label:after{margin-left:0}
    .v-image-right .xs-about .xs-about-media{order:2}
    .xs-about-media{position:relative;border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-md)}
    .xs-about-media img{display:block;width:100%;height:100%;min-height:300px;max-height:460px;object-fit:cover}
    {{-- Sello en TINTA FUERTE: hay tiendas con primario claro (cyan de
         Tecsist) y acento claro (dorado de MegaHogar); solo la tinta garantiza
         lectura. La rayita de acento pone el color de marca. --}}
    .xs-about-label{display:inline-block;color:var(--text-strong,#10231f);font-size:12px;font-weight:800;letter-spacing:.18em;text-transform:uppercase}
    .xs-about-label:after{content:'';display:block;width:44px;height:2px;margin:8px auto 0;background:var(--accent,var(--primary))}
    .xs-about:not(.xs-about--no-img) .xs-about-label:after{margin-left:0}
    .xs-about-copy h2{margin:14px 0 12px;color:var(--text-strong);font-family:var(--font-title);font-size:clamp(23px,2.6vw,34px);letter-spacing:-.02em;line-height:1.2}
    /* Medida de lectura: a 820px la linea pasaba de 100 caracteres y el ojo
       perdia el renglon al volver. 68ch es el limite comodo. */
    .xs-about-copy p{margin:0;max-width:68ch;color:var(--text);font-size:15px;line-height:1.8;white-space:pre-line}
    body.section-heading-center .xs-about--no-img .xs-about-copy p{margin-inline:auto}
    /* Era `inline-block` y heredaba `text-align:left` del bloque: el texto se
       pegaba a la izquierda y dejaba un hueco a la derecha. Como caja flexible
       el texto queda centrado de verdad, con o sin icono. */
    .xs-about-cta{display:inline-flex;align-items:center;justify-content:center;
        text-align:center;gap:8px;margin-top:20px;min-height:46px;padding:0 26px}
    .xs-about-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px;margin-top:26px}
    .xs-about-stat{padding:16px 14px;text-align:center;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm)}
    .xs-about-stat svg{width:22px;height:22px;color:var(--accent,var(--primary))}
    .xs-about-stat b{display:block;margin-top:6px;color:var(--text-strong);font-family:var(--font-title);font-size:19px}
    .xs-about-stat span{display:block;margin-top:3px;color:var(--muted);font-size:11.5px;line-height:1.4}
    @media(max-width:860px){.xs-about{grid-template-columns:1fr}.v-image-right .xs-about .xs-about-media{order:0}.xs-about-media img{max-height:320px}}
</style>
<section class="xs-section v-{{ $apVariant }}" data-store-native-section="about_preview" style="order:{{ $sectionOrder('about_preview') }}"><div class="container">
    <div class="xs-about {{ $apImage ? '' : 'xs-about--no-img' }}">
        @if($apImage)
        <div class="xs-about-media"><img src="{{ $apImage }}" alt="{{ $apC['title'] ?? 'Nosotros' }}" loading="lazy"></div>
        @endif
        <div class="xs-about-copy">
            @if(filled($apC['label'] ?? null))<span class="xs-about-label">{{ $apC['label'] }}</span>@endif
            @if(filled($apC['title'] ?? null))<h2>{{ $apC['title'] }}</h2>@endif
            @if(filled($apC['body'] ?? null))<p>{{ $apC['body'] }}</p>@endif
            @if(filled($apC['button_text'] ?? null) && filled($apC['button_url'] ?? null))
            <a class="button button-primary xs-about-cta" href="{{ $apC['button_url'] }}">{{ $apC['button_text'] }}</a>
            @endif
            @if($apIndicators->isNotEmpty())
            <div class="xs-about-stats">
                @foreach($apIndicators as $ind)
                <div class="xs-about-stat">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $apIconPaths[$ind['icon'] ?? 'star'] ?? $apIconPaths['star'] !!}</svg>
                    @if(filled($ind['value'] ?? null))<b>{{ $ind['value'] }}</b>@endif
                    @if(filled($ind['title'] ?? null))<span>{{ $ind['title'] }}</span>@endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div></section>
@endif

{{-- Revelado suave de secciones al hacer scroll (solo portada, solo con tema
     activo; sin JS las secciones quedan visibles — nunca invisibles). --}}
@php
    // Animación elegida POR SECCIÓN en el constructor (anim_{componente}_*).
    // Clave del registro → clave nativa del DOM. Sin elección = default del tema.
    $sectionAnims = [];
    foreach (($componentAliases ?? []) as $registry => $native) {
        $type = $settings["anim_{$registry}_type"] ?? null;
        if (blank($type) || isset($sectionAnims[$native])) continue;
        $sectionAnims[$native] = [
            'type' => $type,
            'dur' => max(0.15, min(3, (float) ($settings["anim_{$registry}_duration"] ?? 0.6))),
            'delay' => max(0, min(2, (float) ($settings["anim_{$registry}_delay"] ?? 0))),
        ];
    }
@endphp
<script>
window.__sectionAnims = @json($sectionAnims);

document.addEventListener('DOMContentLoaded', function () {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    // Antes exigía una clase theme-*: las tiendas en tema "classic" se quedaban
    // sin aparición de secciones sin que nadie lo hubiera decidido.
    var sections = document.querySelectorAll('[data-store-native-section]');
    if (!sections.length || !('IntersectionObserver' in window)) return;
    // Aplicar la animación configurada ANTES de activar el revelado.
    sections.forEach(function (s) {
        var cfg = (window.__sectionAnims || {})[s.dataset.storeNativeSection];
        if (!cfg || !cfg.type) return;
        s.dataset.anim = cfg.type;
        s.style.setProperty('--anim-dur', cfg.dur + 's');
        s.style.setProperty('--anim-delay', cfg.delay + 's');
    });
    document.body.classList.add('sf-reveal-ready');
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('sf-in'); io.unobserve(e.target); }
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });
    sections.forEach(function (s, i) {
        // Lo que ya está en pantalla entra de inmediato (sin parpadeo).
        var r = s.getBoundingClientRect();
        if (r.top < innerHeight * 0.9) { s.classList.add('sf-in'); return; }
        io.observe(s);
    });
    // Red de seguridad: si el observador se pierde una sección (saltos de
    // ancla, scroll instantáneo), este repaso en cada scroll la revela igual.
    // Una sección que se queda en opacity 0 es una sección que no existe.
    var pendiente = false;
    addEventListener('scroll', function () {
        if (pendiente) return;
        pendiente = true;
        requestAnimationFrame(function () {
            pendiente = false;
            sections.forEach(function (s) {
                if (s.classList.contains('sf-in')) return;
                if (s.getBoundingClientRect().top < innerHeight) s.classList.add('sf-in');
            });
        });
    }, { passive: true });
});
</script>

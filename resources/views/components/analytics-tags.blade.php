{{--
    Emisor ÚNICO de analítica e integraciones (revisión 08).

    Antes de esto, `settings/seo` guardaba ga_id, gtm_id, fb_pixel_id y
    tiktok_pixel_id y NINGUNA vista los emitía: el comerciante configuraba su
    Google Analytics y no se medía nada. Este componente es el único lugar del
    sistema que pinta esas etiquetas — no se reparte código de analítica entre
    plantillas.

    Seguridad: los identificadores se validan contra un patrón estricto por
    proveedor. Un valor que no encaje NO se emite. Aquí nunca entra HTML ni
    script libre del usuario: solo IDs que se interpolan dentro de cadenas JS
    ya saneadas.

    Uso:  <x-analytics-tags :settings="$settings" />   dentro del <head>
--}}
@props(['settings' => null, 'project' => null])
@php
    /* Un componente Blade NO hereda las variables de la vista que lo llama:
       hay que pasarle la fuente. Casi todas las plantillas ya tienen el array
       `$settings` cargado; las paginas sueltas (contacto, reclamaciones,
       pagina institucional, reservas) solo tienen `$project`, asi que se
       acepta cualquiera de las dos y aqui se resuelve a un array. */
    $ajustes = $settings;

    if ($ajustes === null && $project) {
        $ajustes = $project->settings()->pluck('value', 'key')->all();
    }

    $ajustes = is_array($ajustes) ? $ajustes : [];

    // Un ID que no cumpla su formato se descarta en silencio: es preferible no
    // medir a inyectar cualquier cosa en el <head> de la tienda del cliente.
    $valido = function (string $clave, string $patron) use ($ajustes): ?string {
        $v = trim((string) ($ajustes[$clave] ?? ''));

        return ($v !== '' && preg_match($patron, $v)) ? $v : null;
    };

    $ga      = $valido('ga_id',          '/^(G|UA|AW)-[A-Z0-9\-]{4,20}$/i');
    $gtm     = $valido('gtm_id',         '/^GTM-[A-Z0-9]{4,12}$/i');
    $meta    = $valido('fb_pixel_id',    '/^\d{8,20}$/');
    $tiktok  = $valido('tiktok_pixel_id','/^[A-Z0-9]{10,30}$/i');
    $google  = $valido('google_site_verification', '/^[A-Za-z0-9_\-]{20,100}$/');
    $bing    = $valido('bing_site_verification',   '/^[A-Za-z0-9_\-]{20,100}$/');
@endphp

@if($google)<meta name="google-site-verification" content="{{ $google }}">@endif
@if($bing)<meta name="msvalidate.01" content="{{ $bing }}">@endif

@if($gtm)
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})
(window,document,'script','dataLayer','{{ $gtm }}');</script>
@endif

@if($ga)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}
gtag('js',new Date());gtag('config','{{ $ga }}');</script>
@endif

@if($meta)
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;
n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','{{ $meta }}');fbq('track','PageView');</script>
<noscript><img height="1" width="1" style="display:none" alt=""
src="https://www.facebook.com/tr?id={{ $meta }}&ev=PageView&noscript=1"></noscript>
@endif

@if($tiktok)
<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];
ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
ttq.load=function(e){var n='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{};
ttq._i[e]=[];ttq._i[e]._u=n;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]={};
var o=d.createElement('script');o.type='text/javascript';o.async=!0;o.src=n+'?sdkid='+e+'&lib='+t;
var a=d.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};
ttq.load('{{ $tiktok }}');ttq.page();}(window,document,'ttq');</script>
@endif

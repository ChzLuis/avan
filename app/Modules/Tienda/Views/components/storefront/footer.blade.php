@php
    use App\Modules\Tienda\Support\StorefrontNavigation;
    $safeSocial = fn($url) => filter_var($url,FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true) ? $url : null;
    $footerBg = $settings['footer_bg_color'] ?? '#0f172a'; $footerText = $settings['footer_text_color'] ?? '#ffffff';
    $socials = ['facebook_url'=>'Facebook','instagram_url'=>'Instagram','tiktok_url'=>'TikTok','youtube_url'=>'YouTube','twitter_url'=>'X / Twitter','linkedin_url'=>'LinkedIn'];
    // Contacto: misma cadena de resolucion que computienda. Sin esto el pie de
    // la estructura V2 ignoraba lo escrito en el Constructor. La columna sera la
    // fuente canonica tras la migracion; hasta entonces manda el ajuste.
    $storePhone = trim((string) ($settings['contact_phone'] ?? '')) ?: trim((string) ($project->phone ?? ''));
    $storeWhatsapp = trim((string) ($settings['quote_whatsapp'] ?? '')) ?: trim((string) ($project->whatsapp ?? ''));
    $storeAddress = trim((string) ($settings['contact_address'] ?? '')) ?: trim((string) ($project->address ?? ''));
@endphp
<style>
.store-footer{padding:54px 0 0;background:{{ $footerBg }};color:{{ $footerText }}}.store-footer-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:50px}.store-footer h2,.store-footer h3{margin:0 0 14px;color:inherit;font-family:var(--store-font-title)}.store-footer h2{font-size:20px}.store-footer h3{font-size:13px;text-transform:uppercase;letter-spacing:.08em}.store-footer p{max-width:460px;margin:0;color:color-mix(in srgb,{{ $footerText }} 70%,transparent);font-size:14px;line-height:1.7}.store-footer ul{display:grid;gap:8px;margin:0;padding:0;list-style:none}.store-footer a{color:color-mix(in srgb,{{ $footerText }} 75%,transparent);font-size:13px;text-decoration:none}.store-footer a:hover{color:{{ $footerText }};text-decoration:underline}.store-footer-contact{display:grid;gap:7px;margin-top:18px}.store-footer-social{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}.store-footer-social a{padding:7px 9px;border:1px solid color-mix(in srgb,{{ $footerText }} 18%,transparent);border-radius:7px}.store-footer-bottom{display:flex;justify-content:space-between;gap:18px;margin-top:45px;padding:20px 0;border-top:1px solid color-mix(in srgb,{{ $footerText }} 14%,transparent);color:color-mix(in srgb,{{ $footerText }} 55%,transparent);font-size:11px}@media(max-width:767px){.store-footer{padding-top:40px}.store-footer-grid{grid-template-columns:1fr;gap:30px}.store-footer-bottom{align-items:flex-start;flex-direction:column;margin-top:34px}}
</style>
<footer class="store-footer">
    <div class="store-container">
        <div class="store-footer-grid">
            <section><h2>{{ $project->name }}</h2><p>{{ $settings['footer_tagline'] ?? $project->description ?? 'Compra de forma segura y recibe atención personalizada.' }}</p><div class="store-footer-contact">@if($storePhone)<a href="tel:{{ preg_replace('/[^0-9+]/','',$storePhone) }}">{{ $storePhone }}</a>@endif @if($settings['contact_email'] ?? null)<a href="mailto:{{ $settings['contact_email'] }}">{{ $settings['contact_email'] }}</a>@endif @if($storeAddress)<span style="font-size:13px;opacity:.72">{{ $storeAddress }}</span>@endif</div><div class="store-footer-social">@foreach($socials as $key=>$label)@if($url=$safeSocial($settings[$key]??null))<a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $label }}</a>@endif @endforeach</div></section>
            <nav aria-label="Enlaces del pie"><h3>Navegación</h3><ul>@foreach($storeMenu?->rootItems ?? [] as $item)@if($item->is_enabled)<li><a href="{{ StorefrontNavigation::resolveUrl($project,$item) }}" target="{{ $item->target }}">{{ $item->label }}</a></li>@endif @endforeach</ul></nav>
            <section><h3>Atención</h3><ul>@if($storeWhatsapp)<li><a href="https://wa.me/{{ preg_replace('/\D/','',$storeWhatsapp) }}" target="_blank" rel="noopener noreferrer">WhatsApp {{ $storeWhatsapp }}</a></li>@endif @if($settings['business_hours']??null)<li><span style="font-size:13px;opacity:.72">{{ $settings['business_hours'] }}</span></li>@endif</ul></section>
        </div>
        <div class="store-footer-bottom"><span>{{ $settings['footer_copyright'] ?? ('© '.date('Y').' '.$project->name.'. Todos los derechos reservados.') }}</span><span>{{ $settings['footer_dev_text'] ?? 'Tecnología de tienda por AVAN' }}</span></div>
    </div>
</footer>

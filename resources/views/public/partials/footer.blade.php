@php
/*
 * Partial: footer completo y configurable
 * Variables esperadas: $project, $settings, $categories (colección), $footerBg, $footerText, $footerLogoH, $logoSrc
 */

$fBg          = $footerBg   ?? '#111827';
$fText        = $footerText ?? '#9ca3af';
$fLogoH       = $footerLogoH ?? 60;
$fLogo        = $logoSrc    ?? ($logoUrl ?? null);

$showBenefits    = ($settings['footer_show_benefits']   ?? '1') === '1';
$showCats        = ($settings['footer_show_categories'] ?? '1') === '1';
$showSocial      = ($settings['footer_show_social']     ?? '1') === '1';
$showNewsletter  = ($settings['footer_show_newsletter'] ?? '1') === '1';
$showAddress     = ($settings['footer_show_address']    ?? '1') === '1';
$copyright       = $settings['footer_copyright']        ?? ('© ' . date('Y') . ' ' . $project->name . '. Todos los derechos reservados.');
$devText         = $settings['footer_dev_text']         ?? 'Desarrollado por AVAN';
$newsletterTitle = $settings['footer_newsletter_title'] ?? 'Boletín';
$newsletterUrl   = $settings['footer_newsletter_url']   ?? '';

// Íconos para barra de beneficios
$benefitIcons = [
    'tienda'    => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 2.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>',
    'envio'     => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>',
    'pago'      => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>',
    'seguridad' => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>',
    'soporte'   => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/></svg>',
    'regalo'    => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1019.5 7.125V11.25m-7.5-6.375A2.625 2.625 0 104.5 7.125V11.25m15-3.375H3M6.75 21V11.25m10.5 9.75V11.25m-6.75 0V21m0-9.75H12"/></svg>',
    'corazon'   => '<svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>',
];

// Parsear páginas de información
$footerPagesRaw = $settings['footer_pages'] ?? '';
$footerPages = collect(array_filter(array_map('trim', explode("\n", $footerPagesRaw))))
    ->map(function($line) {
        $parts = array_map('trim', explode('|', $line, 2));
        return ['title' => $parts[0] ?? '', 'url' => $parts[1] ?? '#'];
    })->filter(fn($p) => $p['title']);

// Parsear páginas de la tienda
$footerStorePagesRaw = $settings['footer_store_pages'] ?? '';
$footerStorePages = collect(array_filter(array_map('trim', explode("\n", $footerStorePagesRaw))))
    ->map(function($line) {
        $parts = array_map('trim', explode('|', $line, 2));
        return ['title' => $parts[0] ?? '', 'url' => $parts[1] ?? '#'];
    })->filter(fn($p) => $p['title']);

// Beneficios
$benefits = [];
foreach ([1,2,3] as $bn) {
    $icon = trim($settings['footer_benefit_'.$bn.'_icon'] ?? '');
    $text = trim($settings['footer_benefit_'.$bn.'_text'] ?? '');
    if ($text) $benefits[] = ['icon' => $icon, 'text' => $text];
}

// Redes sociales
$socials = [
    'facebook_url'  => ['label'=>'Facebook',  'svg'=>'<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>'],
    'instagram_url' => ['label'=>'Instagram', 'svg'=>'<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'],
    'tiktok_url'    => ['label'=>'TikTok',    'svg'=>'<path d="M9 12a4 4 0 104 4V4a5 5 0 005 5"/>'],
    'youtube_url'   => ['label'=>'YouTube',   'svg'=>'<path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.4a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58z"/><polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02"/>'],
    'twitter_url'   => ['label'=>'Twitter/X', 'svg'=>'<path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/>'],
];

$waNum = preg_replace('/\D/', '', $settings['quote_whatsapp'] ?? $project->whatsapp ?? '');
$waCountry = $settings['quote_whatsapp_country'] ?? '51';
if ($waNum && !str_starts_with($waNum, $waCountry)) $waNum = $waCountry . $waNum;
@endphp

{{-- ─── BARRA DE BENEFICIOS ─────────────────────────────────────────────────── --}}
@if($showBenefits && count($benefits))
<div style="background:{{ $fBg }}; border-top: 1px solid {{ $fText }}15">
  <div class="max-w-[1400px] mx-auto px-4 py-6 grid grid-cols-1 sm:grid-cols-{{ min(count($benefits), 3) }} gap-4">
    @foreach($benefits as $b)
    <div class="flex items-center gap-4 border rounded-xl px-5 py-4" style="border-color:{{ $fText }}25; color:{{ $fText }}">
      <div style="color:{{ $settings['primary_color'] ?? '#4f46e5' }}">
        {!! $benefitIcons[$b['icon']] ?? $benefitIcons['tienda'] !!}
      </div>
      <span class="text-sm font-semibold">{{ $b['text'] }}</span>
    </div>
    @endforeach
  </div>
</div>
@endif

{{-- ─── FOOTER PRINCIPAL ────────────────────────────────────────────────────── --}}
<footer style="background:{{ $fBg }}; color:{{ $fText }}" itemscope itemtype="https://schema.org/WPFooter">
  <div class="border-t" style="border-color:{{ $fText }}15"></div>
  <div class="max-w-[1400px] mx-auto px-4 py-12">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-10">

      {{-- Col 1: Contacto --}}
      <div class="lg:col-span-1">
        <p class="text-sm font-bold mb-4 uppercase tracking-wider" style="color:{{ $fText }}">Comunícate con nosotros</p>
        <div class="space-y-4 text-sm">
          @if($waNum)
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color:{{ $settings['primary_color'] ?? '#4f46e5' }}" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <div>
              <p class="text-xs opacity-60 mb-0.5">WhatsApp Online</p>
              <a href="https://wa.me/{{ $waNum }}" target="_blank" rel="noopener"
                 class="font-bold hover:opacity-80 transition" style="color:{{ $fText }}">
                +{{ $waNum }}
              </a>
            </div>
          </div>
          @endif
          @if($project->email ?? null)
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
            <div>
              <p class="text-xs opacity-60 mb-0.5">Contáctanos</p>
              <a href="mailto:{{ $project->email }}" class="font-bold hover:opacity-80 transition" style="color:{{ $fText }}">
                {{ $project->email }}
              </a>
            </div>
          </div>
          @endif
          @if($showAddress && ($project->address ?? null))
          <div class="flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
            </svg>
            <div>
              <p class="text-xs opacity-60 mb-0.5">Ubicación</p>
              <p class="font-bold leading-snug" style="color:{{ $fText }}">{{ $project->address }}</p>
            </div>
          </div>
          @endif
        </div>
      </div>

      {{-- Col 2: Categorías --}}
      @if($showCats && isset($categories) && $categories->count())
      <div>
        <p class="text-sm font-bold mb-4 uppercase tracking-wider" style="color:{{ $fText }}">Productos</p>
        <ul class="space-y-2 text-sm columns-{{ $categories->count() > 6 ? '2' : '1' }}">
          @foreach($categories as $cat)
          <li>
            <a href="{{ url('/'.$project->slug.'?cat='.$cat->id) }}"
               class="hover:opacity-100 transition opacity-70 hover:underline" style="color:{{ $fText }}">
              {{ $cat->name }}
            </a>
          </li>
          @endforeach
        </ul>
      </div>
      @endif

      {{-- Col 3: Páginas de la tienda --}}
      @if($footerStorePages->count())
      <div>
        <p class="text-sm font-bold mb-4 uppercase tracking-wider" style="color:{{ $fText }}">{{ $project->name }}</p>
        @if($fLogo)
        <img src="{{ $fLogo }}" alt="{{ $project->name }}"
             style="max-height:{{ $fLogoH }}px; max-width:{{ $fLogoH * 4 }}px"
             class="object-contain w-auto mb-4">
        @endif
        <ul class="space-y-2 text-sm">
          @foreach($footerStorePages as $page)
          <li>
            <a href="{{ $page['url'] }}" class="hover:opacity-100 transition opacity-70 hover:underline" style="color:{{ $fText }}">
              {{ $page['title'] }}
            </a>
          </li>
          @endforeach
        </ul>
      </div>
      @else
      {{-- Si no hay páginas de la tienda, mostrar sólo el logo --}}
      @if($fLogo)
      <div>
        <img src="{{ $fLogo }}" alt="{{ $project->name }}"
             style="max-height:{{ $fLogoH }}px; max-width:{{ $fLogoH * 4 }}px"
             class="object-contain w-auto mb-3">
        @if($project->description ?? null)
        <p class="text-sm opacity-60 leading-relaxed">{{ Str::limit($project->description, 120) }}</p>
        @endif
      </div>
      @endif
      @endif

      {{-- Col 4: Información / Páginas --}}
      @if($footerPages->count())
      <div>
        <p class="text-sm font-bold mb-4 uppercase tracking-wider" style="color:{{ $fText }}">Información</p>
        <ul class="space-y-2 text-sm">
          @foreach($footerPages as $page)
          <li>
            <a href="{{ $page['url'] }}" class="hover:opacity-100 transition opacity-70 hover:underline" style="color:{{ $fText }}">
              {{ $page['title'] }}
            </a>
          </li>
          @endforeach
        </ul>
      </div>
      @endif

      {{-- Col 5: Boletín + Redes --}}
      <div>
        @if($showNewsletter && $newsletterUrl)
        <p class="text-sm font-bold mb-4 uppercase tracking-wider" style="color:{{ $fText }}">{{ $newsletterTitle }}</p>
        <form action="{{ $newsletterUrl }}" method="POST" class="mb-6">
          @csrf
          <input type="email" name="email" placeholder="Correo electrónico"
                 class="w-full px-3 py-2 rounded-lg text-sm text-gray-900 bg-white border-0 outline-none mb-2"
                 required>
          <button type="submit"
                  class="w-full flex items-center justify-between px-4 py-2 rounded-lg text-sm font-bold text-white transition"
                  style="background:{{ $settings['primary_color'] ?? '#4f46e5' }}">
            Suscribirme
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
          </button>
        </form>
        @endif

        @if($showSocial)
        <p class="text-sm font-bold mb-3 uppercase tracking-wider" style="color:{{ $fText }}">Síguenos en</p>
        <div class="flex flex-wrap gap-2">
          @foreach($socials as $sKey => $sData)
          @if($settings[$sKey] ?? null)
          <a href="{{ $settings[$sKey] }}" target="_blank" rel="noopener"
             class="w-9 h-9 rounded-lg flex items-center justify-center transition hover:opacity-80"
             style="background:{{ $fText }}15; color:{{ $fText }}"
             title="{{ $sData['label'] }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              {!! $sData['svg'] !!}
            </svg>
          </a>
          @endif
          @endforeach
        </div>
        @endif
      </div>

    </div>
  </div>

  {{-- Copyright --}}
  <div class="border-t py-5" style="border-color:{{ $fText }}15">
    <div class="max-w-[1400px] mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs" style="color:{{ $fText }}; opacity:0.55">
      <span>{{ $copyright }}</span>
      <span>{{ $devText }}</span>
    </div>
  </div>
</footer>

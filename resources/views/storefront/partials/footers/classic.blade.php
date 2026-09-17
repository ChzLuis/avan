@php
    // Razon social y RUC: obligatorios en la web de un comercio peruano.
    $fpLegal = \App\Storefront\DatosPie::legal($settings ?? [], $project);
    $fpVerLegal = (string) (($settings ?? [])['footer_show_legal'] ?? '1') !== '0'
        && ($fpLegal['nombre'] !== '' || $fpLegal['ruc'] !== '');
    $fpTextoLegal = trim($fpLegal['nombre'].($fpLegal['ruc'] ? ' · RUC '.$fpLegal['ruc'] : ''), ' ·');
@endphp
    <footer class="site-footer ft-style-{{ $footerStyle }}">
        <div class="container footer-main">
            {{-- Columna 1: Marca --}}
            <div class="footer-brand">
                <div class="footer-logo">
                    {{-- Con logo NO se repite el nombre al lado: el logotipo ya
                         lo dice, y escrito otra vez compite con el. Sin logo, la
                         inicial sola no identifica nada y el nombre es obligado.
                         Es el mismo criterio que la cabecera (brand--logo-only). --}}
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $storeName }}">
                    @else
                        <span class="footer-logo-mark">{{ mb_strtoupper(mb_substr($storeName,0,1)) }}</span>
                        <strong>{{ $storeName }}</strong>
                    @endif
                </div>
                <p class="footer-desc">{{ Str::limit($tagline, 130) }}</p>

                @if(($showBenefits ?? true) && count($footerTrust))
                <div class="footer-trust">
                    @foreach($footerTrust as $t)
                    <div class="footer-trust-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">{!! $svgIcons[$t['k']] ?? $svgIcons['shield'] !!}</svg>
                        <span>{{ $t['t'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($showSocial && count($social))
                <div class="footer-social">
                    @foreach($social as $red => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $red }}" title="{{ $red }}">
                        @switch($red)
                            @case('Instagram')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="20" height="20" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1" fill="currentColor"></circle></svg>@break
                            @case('Facebook')<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>@break
                            @case('TikTok')<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 3a5 5 0 0 0 5 5v3a8 8 0 0 1-5-1.7V15a6 6 0 1 1-6-6v3a3 3 0 1 0 3 3V3z"></path></svg>@break
                            @case('YouTube')<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23 12s0-3.5-.45-5.2a2.75 2.75 0 0 0-1.94-1.94C18.9 4.4 12 4.4 12 4.4s-6.9 0-8.61.46A2.75 2.75 0 0 0 1.45 6.8C1 8.5 1 12 1 12s0 3.5.45 5.2a2.75 2.75 0 0 0 1.94 1.94c1.71.46 8.61.46 8.61.46s6.9 0 8.61-.46a2.75 2.75 0 0 0 1.94-1.94C23 15.5 23 12 23 12zM10 15.5v-7l6 3.5z"></path></svg>@break
                            @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="18" rx="2"></rect><path d="M7 10v7M7 7v.01M11 17v-4a2 2 0 0 1 4 0v4M11 17v-3"></path></svg>
                        @endswitch
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Columna 2: Categorías --}}
            @if($categories->count())
            <div class="footer-col">
                <h3 class="footer-title">Categorías</h3>
                <ul class="footer-links">
                    @foreach($categories->take(7) as $cat)
                    <li><a href="{{ $shopBaseFooter }}?category={{ $cat->id }}">
                        @if($cat->image_url)<img class="footer-cat-ico" src="{{ $assetUrl($cat->image_url) }}" alt="">@endif
                        <span>{{ $cat->name }}</span>
                    </a></li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Columna 3: Información --}}
            <div class="footer-col">
                <h3 class="footer-title">Información</h3>
                <ul class="footer-links">
                    <li><a href="{{ $aboutUrl }}"><span>Nosotros</span></a></li>
                    <li><a href="{{ $contactUrl }}"><span>Contacto</span></a></li>
                    <li><a href="{{ url(($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug).'/reclamaciones') }}"><span>Libro de Reclamaciones</span></a></li>
                    <li><a href="{{ url(($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug).'/privacidad') }}"><span>Políticas de privacidad</span></a></li>
                    <li><a href="{{ url(($project->custom_domain && request()->getHost() === $project->custom_domain ? '' : '/'.$project->slug).'/terminos') }}"><span>Términos y condiciones</span></a></li>
                </ul>
            </div>

            {{-- Columna 4: Contacto --}}
            <div class="footer-col">
                <h3 class="footer-title">Contacto</h3>
                <ul class="footer-contact">
                    @if($phone)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['phone'] !!}</svg><a href="tel:{{ preg_replace('/[^\d+]/','',$phone) }}">{{ $phone }}</a></li>@endif
                    @if($footerAddress)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['pin'] !!}</svg><span>{{ $footerAddress }}</span></li>@endif
                    @include('storefront.partials.numeros-extra')
                    @if($email)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['mail'] !!}</svg><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                    @if($footerHours)<li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['clock'] !!}</svg><span>{{ $footerHours }}</span></li>@endif
                </ul>

                @if($waFooter)
                <div class="footer-help">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $svgIcons['chat'] !!}</svg>
                    <div>
                        <strong>¿Necesitas ayuda?</strong>
                        <span>Nuestro equipo está listo para asesorarte.</span>
                    </div>
                    <a href="https://wa.me/{{ $waFooter }}" target="_blank" rel="noopener" class="footer-help-btn">Contáctanos</a>
                </div>
                @endif
            </div>
        </div>

        {{-- Barra inferior --}}
        <div class="footer-bottom">
            <div class="container footer-bottom-inner">
                {{-- Izquierda: sello de seguridad --}}
                @if($showFooterSsl)
                <div class="footer-secure">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                    <div><strong>Compra segura y protegida</strong><span>Sitio protegido con SSL</span></div>
                </div>
                @else<span></span>@endif

                {{-- Centro: copyright + crédito --}}
                <span class="footer-copy">{{ $footerCopyright }}@if($fpVerLegal) · {{ $fpTextoLegal }}@endif · Desarrollado por <a href="https://eskalagroup.com/" target="_blank" rel="noopener" class="footer-dev-link">Eskala</a></span>

                {{-- Derecha: métodos de pago (logos) --}}
                @if($showFooterPay)
                <div class="footer-pay">
                    @php
                        $payLogos = [
                            'visa'       => '<svg width="18" height="18" viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#fff"/><path d="M20.4 20.3h-2.8l1.8-11h2.8l-1.8 11zm10.2-10.7c-.6-.2-1.5-.5-2.6-.5-2.8 0-4.8 1.5-4.8 3.6 0 1.6 1.4 2.4 2.5 3 1.1.5 1.5.9 1.5 1.3 0 .7-.9 1-1.7 1-1.1 0-1.7-.2-2.7-.6l-.4-.2-.4 2.4c.7.3 1.9.6 3.2.6 3 0 4.9-1.5 4.9-3.7 0-1.2-.7-2.2-2.4-3-1-.5-1.6-.8-1.6-1.3 0-.4.5-.9 1.6-.9.9 0 1.6.2 2.1.4l.3.1.4-2.3zm7.2-.3h-2.2c-.7 0-1.2.2-1.5.9l-4.2 10.1h3l.6-1.7h3.6l.3 1.7h2.6l-2.3-11zm-3.5 7.1c.2-.6 1.1-3.1 1.1-3.1s.2-.6.4-1l.2.9.7 3.2h-2.4zM16.1 9.3l-2.8 7.5-.3-1.5c-.5-1.8-2.2-3.7-4-4.6l2.6 9.6h3l4.5-11h-3z" fill="#1a1f71"/></svg>',
                            'mastercard' => '<svg width="18" height="18" viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#fff"/><circle cx="19" cy="15" r="9" fill="#eb001b"/><circle cx="29" cy="15" r="9" fill="#f79e1b" fill-opacity=".9"/><path d="M24 8.5a9 9 0 0 1 0 13 9 9 0 0 1 0-13z" fill="#ff5f00"/></svg>',
                            'amex'       => '<svg width="18" height="18" viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#1e6cd6"/><text x="24" y="19" font-family="Arial" font-size="8" font-weight="bold" fill="#fff" text-anchor="middle">AMEX</text></svg>',
                            'diners'     => '<svg width="18" height="18" viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#fff"/><circle cx="24" cy="15" r="9" fill="none" stroke="#0079be" stroke-width="1.5"/><path d="M20 10a6 6 0 0 0 0 10V10zm8 0v10a6 6 0 0 0 0-10z" fill="#0079be"/></svg>',
                            'yape'       => '<svg width="18" height="18" viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#742384"/><text x="24" y="19" font-family="Arial" font-size="8" font-weight="bold" fill="#fff" text-anchor="middle">yape</text></svg>',
                            'plin'       => '<svg width="18" height="18" viewBox="0 0 48 30"><rect width="48" height="30" rx="4" fill="#00c3a5"/><text x="24" y="19" font-family="Arial" font-size="8" font-weight="bold" fill="#fff" text-anchor="middle">plin</text></svg>',
                        ];
                        $showPay = count($footerPayments) ? $footerPayments : ['visa','mastercard','amex','diners','yape'];
                    @endphp
                    @foreach($showPay as $pay)
                        @if(isset($payLogos[strtolower($pay)]))<span class="footer-pay-logo">{!! $payLogos[strtolower($pay)] !!}</span>@endif
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </footer>

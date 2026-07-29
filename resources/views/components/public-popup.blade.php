@props(['popup' => null, 'settings' => []])
@if(isset($popup) && $popup)
@php
    $popupKey = 'bixo-popup-'.$popup->id.($popup->frequency==='day'?'-'.now()->toDateString():'');
    // Color de fondo configurable (setting popup_bg_color) con fallback azul marino premium.
    $ppBg   = $settings['popup_bg_color'] ?? '#0b1736';
    $ppAcc  = $settings['primary_color'] ?? $settings['popup_accent_color'] ?? '#2563eb';
    // Íconos del sistema (SVG) reutilizados para la fila de confianza.
    $pIco = [
        'shield' => '<path d="M12 3 4 6v5c0 5 3.3 8.2 8 10 4.7-1.8 8-5 8-10V6l-8-3Z"></path><path d="m8.5 12 2.2 2.2 4.8-5"></path>',
        'support'=> '<path d="M20 15a4 4 0 0 1-4 4H8a5 5 0 0 1-2-9.6A6 6 0 0 1 18 10a4 4 0 0 1 2 5Z"></path><path d="M9 14h6M12 11v6"></path>',
        'truck'  => '<path d="M3 6h12v11H3zM15 10h4l2 3v4h-6z"></path><circle cx="7" cy="19" r="1.5"></circle><circle cx="18" cy="19" r="1.5"></circle>',
        'lock'   => '<rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path>',
        'check'  => '<path d="M12 3 4 6v5c0 5 3.3 8.2 8 10 4.7-1.8 8-5 8-10V6l-8-3Z"></path><path d="m8.5 12 2.2 2.2 4.8-5"></path>',
        'clock'  => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'mail'   => '<rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 6L2 7"></path>',
    ];
@endphp
<style>
#bixo-store-popup[hidden]{display:none!important}
#bixo-store-popup{position:fixed;inset:0;z-index:120;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(2,6,23,.78);backdrop-filter:blur(4px);font-family:var(--store-font-body,Inter,sans-serif)}
.bixo-popup-card{--pp-bg:{{ $ppBg }};--pp-acc:{{ $ppAcc }};position:relative;width:min(720px,100%);max-height:94vh;overflow:hidden;border-radius:20px;background:radial-gradient(120% 120% at 85% 0%,color-mix(in srgb,var(--pp-bg) 78%,#2b47a8) 0%,var(--pp-bg) 55%,color-mix(in srgb,var(--pp-bg) 80%,#000) 100%);color:#fff;box-shadow:0 30px 90px rgba(2,6,23,.6);border:1px solid rgba(255,255,255,.08)}
.bixo-popup-grid{display:grid;grid-template-columns:1fr 1fr}
.bixo-popup-copy{padding:34px 30px;display:flex;flex-direction:column;justify-content:center;min-width:0}
.bixo-popup-eyebrow{display:inline-flex;align-items:center;gap:8px;margin-bottom:14px;color:color-mix(in srgb,var(--pp-acc) 70%,#fff);font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.bixo-popup-eyebrow svg{width:15px;height:15px}
.bixo-popup-copy h2{margin:0;font-size:clamp(30px,6vw,52px);font-weight:900;line-height:.95;letter-spacing:-.02em}
.bixo-popup-copy h2 .pp-accent{display:block;color:var(--pp-acc)}
.bixo-popup-copy .pp-sub{margin:8px 0 0;color:#cbd5e1;font-size:16px}
.bixo-popup-trust{display:flex;flex-wrap:wrap;gap:14px 18px;margin:20px 0}
.bixo-popup-trust span{display:flex;align-items:center;gap:8px;color:#dbe4ff;font-size:11.5px;line-height:1.25;font-weight:600}
.bixo-popup-trust svg{width:26px;height:26px;flex:0 0 26px;padding:5px;color:var(--pp-acc);background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px}
.bixo-popup-input{display:flex;align-items:center;gap:10px;margin-top:6px;padding:0 14px;height:50px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:12px}
.bixo-popup-input svg{width:18px;height:18px;color:#94a3b8}
.bixo-popup-input input{flex:1;min-width:0;height:100%;background:none;border:0;color:#fff;font-size:14px;outline:0}
.bixo-popup-input input::placeholder{color:#8ea0c4}
.bixo-popup-action{display:inline-flex;min-height:52px;align-items:center;justify-content:center;gap:9px;margin-top:12px;padding:0 22px;border:0;border-radius:12px;background:linear-gradient(135deg,var(--pp-acc),color-mix(in srgb,var(--pp-acc) 70%,#000));color:#fff;font-size:15px;font-weight:800;text-decoration:none;cursor:pointer;box-shadow:0 10px 28px color-mix(in srgb,var(--pp-acc) 45%,transparent);transition:.18s}
.bixo-popup-action:hover{filter:brightness(1.08);transform:translateY(-2px)}
.bixo-popup-action svg{width:18px;height:18px}
.bixo-popup-skip{margin-top:14px;color:#8ea0c4;font-size:12.5px;text-align:center;text-decoration:underline;cursor:pointer;background:none;border:0}
.bixo-popup-media{position:relative;min-height:100%;background:#060c1e}
.bixo-popup-media img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.bixo-popup-media:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,var(--pp-bg) 0%,transparent 30%)}
.bixo-popup-strip{display:flex;align-items:stretch;border-top:1px solid rgba(255,255,255,.08)}
.bixo-popup-strip>div{flex:1;display:flex;align-items:center;gap:11px;padding:16px 20px}
.bixo-popup-strip>div+div{border-left:1px solid rgba(255,255,255,.08)}
.bixo-popup-strip svg{width:30px;height:30px;flex:0 0 30px;color:var(--pp-acc)}
.bixo-popup-strip strong{display:block;color:#fff;font-size:12.5px}
.bixo-popup-strip small{display:block;color:#8ea0c4;font-size:11px}
.bixo-popup-close{position:absolute;z-index:5;top:14px;right:14px;display:grid;width:40px;height:40px;place-items:center;border:1px solid rgba(255,255,255,.18);border-radius:999px;background:rgba(255,255,255,.08);color:#fff;font-size:22px;cursor:pointer;transition:.18s}
.bixo-popup-close:hover{background:rgba(255,255,255,.18)}
@media(max-width:640px){.bixo-popup-grid{grid-template-columns:1fr}.bixo-popup-media{min-height:180px;order:-1}.bixo-popup-media:after{background:linear-gradient(0deg,var(--pp-bg) 0%,transparent 55%)}.bixo-popup-copy{padding:26px 22px}.bixo-popup-strip{flex-direction:column}.bixo-popup-strip>div+div{border-left:0;border-top:1px solid rgba(255,255,255,.08)}}
</style>
<div id="bixo-store-popup" hidden role="dialog" aria-modal="true" aria-labelledby="bixo-popup-title">
  <article class="bixo-popup-card">
    <button type="button" class="bixo-popup-close" data-popup-close aria-label="Cerrar">×</button>
    <div class="bixo-popup-grid">
      <div class="bixo-popup-copy">
        <span class="bixo-popup-eyebrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0l-7.2-7.2a2 2 0 0 1-.6-1.4V4a1 1 0 0 1 1-1h7.9a2 2 0 0 1 1.5.6l7.4 7.4a2 2 0 0 1 0 2.8Z"></path><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"></circle></svg>Oferta exclusiva</span>
        @if($popup->title)<h2 id="bixo-popup-title">{{ $popup->title }}</h2>@endif
        @if($popup->description)<p class="pp-sub">{{ $popup->description }}</p>@endif
        <div class="bixo-popup-trust">
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $pIco['shield'] !!}</svg>{{ $settings['trust_text_1'] ?? 'Garantía de calidad' }}</span>
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $pIco['support'] !!}</svg>{{ $settings['trust_text_3'] ?? 'Soporte especializado' }}</span>
          <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $pIco['truck'] !!}</svg>{{ $settings['trust_text_2'] ?? 'Envíos a todo el Perú' }}</span>
        </div>
        <div class="bixo-popup-input">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $pIco['mail'] !!}</svg>
          <input type="email" placeholder="Ingresa tu correo electrónico" aria-label="Correo electrónico">
        </div>
        <a class="bixo-popup-action" href="{{ $popup->button_url ?: '#catalogo' }}">{{ $popup->button_text ?: 'Obtener oferta' }}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M14 6l6 6-6 6"></path></svg></a>
        <button type="button" class="bixo-popup-skip" data-popup-close>No, gracias</button>
      </div>
      @if($popup->image_path)
      <div class="bixo-popup-media"><img src="{{ asset('storage/'.$popup->image_path) }}" alt="{{ $popup->title ?: 'Promoción' }}"></div>
      @endif
    </div>
    <div class="bixo-popup-strip">
      <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $pIco['lock'] !!}</svg><div><strong>Compra segura</strong><small>Sitio protegido</small></div></div>
      <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $pIco['check'] !!}</svg><div><strong>Productos originales</strong><small>100% garantizados</small></div></div>
      <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $pIco['clock'] !!}</svg><div><strong>Entrega rápida</strong><small>En todo el Perú</small></div></div>
    </div>
  </article>
</div>
<script>
(()=>{const modal=document.getElementById('bixo-store-popup');if(!modal)return;const key=@json($popupKey),frequency=@json($popup->frequency),desktop=@json((bool)$popup->show_desktop),mobile=@json((bool)$popup->show_mobile),storage=frequency==='session'?sessionStorage:localStorage;if((innerWidth>=768&&!desktop)||(innerWidth<768&&!mobile)||(frequency!=='always'&&storage.getItem(key)))return;const show=()=>{modal.hidden=false;document.body.style.overflow='hidden';modal.querySelector('[data-popup-close]')?.focus()};const close=()=>{modal.hidden=true;document.body.style.overflow='';if(frequency!=='always')storage.setItem(key,'1')};setTimeout(show,{{ (int)$popup->delay_seconds*1000 }});modal.querySelectorAll('[data-popup-close]').forEach(b=>b.addEventListener('click',close));modal.addEventListener('click',e=>{if(e.target===modal)close()});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.hidden)close()})})();
</script>
@endif

{{-- CARRITO FLOTANTE: burbuja persistente con conteo+total que abre el
     panel lateral clásico. Misma lógica del núcleo; solo suma la burbuja. --}}
<style>
    .cf-bubble{position:fixed;right:22px;bottom:96px;z-index:70;display:flex;align-items:center;gap:10px;min-height:52px;padding:8px 18px 8px 10px;background:var(--primary);color:#fff;border:0;border-radius:999px;cursor:pointer;box-shadow:0 14px 34px color-mix(in srgb,var(--primary) 45%,transparent);transition:transform .18s ease}
    .cf-bubble:hover{transform:translateY(-2px)}
    .cf-bubble-ico{position:relative;width:38px;height:38px;display:grid;place-items:center;background:rgba(255,255,255,.18);border-radius:999px}
    .cf-bubble-ico svg{width:19px;height:19px}
    .cf-bubble-count{position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;display:grid;place-items:center;padding:0 4px;background:#fff;color:var(--primary);border-radius:999px;font-size:10.5px;font-weight:900}
    .cf-bubble-copy{text-align:left;line-height:1.15}
    .cf-bubble-copy small{display:block;font-size:10px;opacity:.85;letter-spacing:.04em;text-transform:uppercase}
    .cf-bubble-copy strong{font-size:14px}
    @media(max-width:760px){.cf-bubble{right:16px;bottom:88px}}
</style>

{{-- Burbuja: solo aparece cuando hay productos (no estorba al navegar) --}}
<button type="button" class="cf-bubble" x-show="cart.length>0" x-cloak x-transition.opacity.duration.200ms
        @click="cartOpen=true" aria-label="Ver carrito">
    <span class="cf-bubble-ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h2l2.1 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H6"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
        <span class="cf-bubble-count" x-text="itemCount()"></span>
    </span>
    <span class="cf-bubble-copy">
        <small>{{ $quoteMode ? 'Mi cotización' : 'Mi carrito' }}</small>
        @unless($hidePrices)<strong x-text="money(total())"></strong>@endunless
    </span>
</button>

{{-- Panel: el drawer clásico intacto (mismas operaciones y cálculos) --}}
@include('tienda::storefront.partials.carts.classic')

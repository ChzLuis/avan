@php
    // Mismas opciones que el carrito clásico (Constructor → Ventas y operación).
    $csThumbs   = (string) ($settings['cart_show_thumbs'] ?? '1') !== '0';
    $csLineTotal= (string) ($settings['cart_line_total'] ?? '1') !== '0';
    $csFreeFrom = (float) ($settings['shipping_free_from'] ?? 0);
    $csKeepText = trim((string) ($settings['cart_keep_shopping_text'] ?? 'Seguir comprando'));
    $csNote     = trim((string) ($settings['cart_footer_note'] ?? 'Confirmaremos disponibilidad, entrega y condiciones antes de procesar tu solicitud.'));
@endphp
{{-- CARRITO LATERAL REDISEÑADO: mismo estado/operaciones del núcleo
     (cart, increase/decrease/remove, money, total, openCheckout). --}}
<style>
    .cs-drawer{position:absolute;top:0;right:0;width:min(440px,100%);height:100%;display:flex;flex-direction:column;background:#fff;border-radius:18px 0 0 18px;box-shadow:-24px 0 60px rgba(15,23,42,.22);overflow:hidden}
    .cs-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;background:var(--primary);color:#fff}
    .cs-head h2{margin:0;font-size:17px;font-weight:800}
    .cs-head small{opacity:.85}
    .cs-close{width:38px;height:38px;display:grid;place-items:center;background:rgba(255,255,255,.16);color:#fff;border:0;border-radius:999px;cursor:pointer}
    .cs-body{flex:1;overflow:auto;padding:18px 22px}
    .cs-empty{margin:70px 0 0;color:#64748b;text-align:center;font-size:14px}
    .cs-item{display:grid;grid-template-columns:1fr auto;gap:8px 12px;padding:14px;margin-bottom:10px;border:1px solid var(--border);border-radius:12px;background:#fbfcfe}
    .cs-item strong{color:#0f172a;font-size:13.5px;line-height:1.4}
    .cs-item small{color:var(--primary);font-weight:700}
    .cs-qty{display:flex;align-items:center;gap:2px;border:1px solid #dbe2ea;border-radius:999px;background:#fff}
    .cs-qty button{width:34px;height:34px;color:#475569;background:transparent;border:0;border-radius:999px;cursor:pointer;font-size:15px}
    .cs-qty span{min-width:26px;text-align:center;font-size:12.5px;font-weight:700}
    .cs-remove{justify-self:end;align-self:center;width:34px;height:34px;display:grid;place-items:center;color:#94a3b8;background:transparent;border:0;cursor:pointer}
    .cs-remove:hover{color:#dc2626}
    .cs-foot{padding:16px 22px 20px;border-top:1px solid var(--border);background:#fff}
    .cs-total{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:12px}
    .cs-total span{color:#64748b;font-size:13px}
    .cs-total strong{color:#0f172a;font-size:21px}
    .cs-foot .button{width:100%}
    /* 10,5px es ilegible: el aviso de condiciones es justo lo que hay que leer. */
    .cs-note{margin:10px 0 0;color:#64748b;font-size:12px;text-align:left;line-height:1.5}
    .cs-item{grid-template-columns:auto 1fr auto}
    .cs-thumb{grid-row:span 2;width:52px;height:52px;display:grid;place-items:center;overflow:hidden;background:#fff;border:1px solid var(--border);border-radius:8px;color:#cbd5e1}
    .cs-thumb img{width:100%;height:100%;object-fit:cover}
    .cs-line-total{display:block;margin-top:3px;color:#0f172a;font-size:13px;font-weight:800}
    .cs-keep{display:block;width:100%;margin-top:8px;padding:10px 16px;color:var(--primary);background:none;border:1px solid var(--border);border-radius:8px;font-size:12.5px;font-weight:800;cursor:pointer}
    .cs-keep:hover{border-color:var(--primary)}
    .cs-ship{margin:0 0 12px;padding:9px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;font-size:11.5px}
    .cs-ship-bar{display:block;height:5px;margin-top:7px;background:#dcfce7;border-radius:99px;overflow:hidden}
    .cs-ship-bar i{display:block;height:100%;background:#22c55e;border-radius:99px;transition:width .3s ease}
</style>
<div class="drawer-layer" x-show="cartOpen" x-cloak @keydown.escape.window="cartOpen=false" role="dialog" aria-modal="true" aria-label="Carrito">
    <div class="drawer-backdrop" @click="cartOpen=false"></div>
    <aside class="cs-drawer">
        <div class="cs-head">
            <div><h2>{{ $quoteMode ? 'Mi cotización' : $cartTitle }}</h2><small x-text="itemCount()+' producto(s)'"></small></div>
            <button class="cs-close" type="button" @click="cartOpen=false" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="cs-body">
            <template x-if="cart.length===0"><p class="cs-empty">{{ $cartEmpty }}</p></template>
            <template x-for="item in cart" :key="item.id+'-'+(item.talla||'')">
                <div class="cs-item">
                    @if($csThumbs)
                    <span class="cs-thumb">
                        <template x-if="item.imagen"><img :src="item.imagen" :alt="item.nombre" loading="lazy"></template>
                        <template x-if="!item.imagen"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.2"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/></svg></template>
                    </span>
                    @endif
                    <div><strong x-text="item.nombre+(item.talla?(' · Talla '+item.talla):'')"></strong><br><small x-show="item.cantidad>1" x-cloak x-text="{!! $csLineTotal ? "money(item.precio)+' × '+item.cantidad" : 'money(item.precio)' !!}"></small>@if($csLineTotal)<b class="cs-line-total" x-text="money(item.precio*item.cantidad)"></b>@endif</div>
                    <button class="cs-remove" type="button" @click="remove(item.id,item.talla)" aria-label="Eliminar">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg>
                    </button>
                    <div class="cs-qty">
                        <button type="button" @click="decrease(item.id,item.talla)">−</button>
                        <span x-text="item.cantidad"></span>
                        <button type="button" @click="increase(item.id,item.talla)">+</button>
                    </div>
                </div>
            </template>
        </div>
        <div class="cs-foot" x-show="cart.length>0">
            @if($csFreeFrom > 0 && !$hidePrices)
            <div class="cs-ship" x-show="total() < {{ $csFreeFrom }}">
                <span>Te faltan <b x-text="money({{ $csFreeFrom }}-total())"></b> para el envío gratis</span>
                <span class="cs-ship-bar"><i :style="'width:'+Math.min(100,(total()/{{ $csFreeFrom }})*100)+'%'"></i></span>
            </div>
            <div class="cs-ship" style="font-weight:800" x-show="total() >= {{ $csFreeFrom }}" x-cloak>✓ ¡Tienes envío gratis!</div>
            @endif
            @unless($hidePrices)<div class="cs-total"><span>Total</span><strong x-text="money(total())"></strong></div>@endunless
            <button class="button button-primary" type="button" @click="openCheckout()">{{ $checkoutText }}</button>
            <button type="button" class="cs-keep" @click="cartOpen=false">{{ $csKeepText }}</button>
            @if($csNote !== '')<p class="cs-note">{{ $csNote }}</p>@endif
        </div>
    </aside>
</div>

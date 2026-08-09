@php
    // Opciones del carrito (Constructor → Ventas y operación). Defaults conservadores:
    // lo que ya existía sigue igual salvo que la tienda active lo nuevo.
    $ckThumbs   = (string) ($settings['cart_show_thumbs'] ?? '1') !== '0';   // miniatura del producto
    $ckLineTotal= (string) ($settings['cart_line_total'] ?? '1') !== '0';    // subtotal por línea
    $ckFreeFrom = (float) ($settings['shipping_free_from'] ?? 0);            // aviso de envío gratis
    $ckKeepText = trim((string) ($settings['cart_keep_shopping_text'] ?? 'Seguir comprando'));
    $ckNote     = trim((string) ($settings['cart_footer_note'] ?? 'Confirmaremos disponibilidad, entrega y condiciones antes de procesar tu solicitud.'));
@endphp
    <div class="drawer-layer" x-show="cartOpen" x-cloak @keydown.escape.window="cartOpen=false" role="dialog" aria-modal="true" aria-label="Carrito">
        <div class="drawer-backdrop" @click="cartOpen=false"></div><aside class="cart-drawer">
            <div class="drawer-head">
                <h2>{{ $quoteMode ? 'Mi cotización' : $cartTitle }} <span class="cart-head-count" x-show="cart.length>0" x-text="'('+itemCount()+')'"></span></h2>
                <button class="icon-button" type="button" @click="cartOpen=false" aria-label="Cerrar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18"></path></svg></button>
            </div>
            <div class="drawer-content">
                {{-- Vacío: además del mensaje, una salida clara al catálogo --}}
                <template x-if="cart.length===0">
                    <div class="cart-empty-box">
                        <svg class="cart-empty-ico" width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M3.5 4.5h2l2 10.5h10l2-8H7"/><circle cx="9.5" cy="19" r="1.5"/><circle cx="16.5" cy="19" r="1.5"/></svg>
                        <p class="cart-empty">{{ $cartEmpty }}</p>
                        <button type="button" class="cart-keep" @click="cartOpen=false">{{ $ckKeepText }}</button>
                    </div>
                </template>
                <template x-for="item in cart" :key="item.id+'-'+(item.talla||'')">
                    <div class="cart-item">
                        @if($ckThumbs)
                        <span class="cart-thumb">
                            <template x-if="item.imagen"><img :src="item.imagen" :alt="item.nombre" loading="lazy"></template>
                            <template x-if="!item.imagen"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7"/></svg></template>
                        </span>
                        @endif
                        <div class="cart-item-copy">
                            <strong x-text="item.nombre+(item.talla?(' · Talla '+item.talla):'')"></strong>
                            <small x-text="{!! $ckLineTotal ? "money(item.precio)+' × '+item.cantidad" : 'money(item.precio)' !!}"></small>
                            @if($ckLineTotal)<b class="cart-line-total" x-text="money(item.precio*item.cantidad)"></b>@endif
                        </div>
                        <div class="quantity"><button type="button" @click="decrease(item.id,item.talla)" aria-label="Quitar uno">−</button><span x-text="item.cantidad"></span><button type="button" @click="increase(item.id,item.talla)" aria-label="Agregar uno">+</button></div>
                        <button class="remove" type="button" @click="remove(item.id,item.talla)" aria-label="Eliminar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"></path></svg></button>
                    </div>
                </template>
            </div>
            <div class="drawer-footer" x-show="cart.length>0">
                @if($ckFreeFrom > 0 && !$hidePrices)
                {{-- Progreso hacia envío gratis: sube el ticket promedio y da certeza --}}
                <div class="cart-ship" x-show="total() < {{ $ckFreeFrom }}">
                    <span>Te faltan <b x-text="money({{ $ckFreeFrom }}-total())"></b> para el envío gratis</span>
                    <span class="cart-ship-bar"><i :style="'width:'+Math.min(100,(total()/{{ $ckFreeFrom }})*100)+'%'"></i></span>
                </div>
                <div class="cart-ship is-ok" x-show="total() >= {{ $ckFreeFrom }}" x-cloak>✓ ¡Tienes envío gratis!</div>
                @endif
                @unless($hidePrices)<div class="cart-total"><span>Total</span><span x-text="money(total())"></span></div>@endunless
                <button class="button button-primary" type="button" @click="openCheckout()">{{ $checkoutText }}</button>
                <button type="button" class="cart-keep cart-keep--foot" @click="cartOpen=false">{{ $ckKeepText }}</button>
                @if($ckNote !== '')<p class="drawer-note">{{ $ckNote }}</p>@endif
            </div>
        </aside>
    </div>

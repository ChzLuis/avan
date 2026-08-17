{{-- Etapa 5: Venta y operación — 4 grupos con revelado progresivo. Borrador siempre. --}}
<section class="bxb-stage">
    <h2>Venta y operación</h2>
    <p class="bxb-stage-sub">Cómo vendes, cómo cobras, cómo entregas y qué confianza das. Solo verás lo que aplica a tus elecciones.</p>

    {{-- 1. ¿Cómo quieres vender? — UNA sola decisión gobierna toda la experiencia
         (unifica el antiguo "Modo de venta" + "Botones del producto"). --}}
    <div class="bxb-card" x-data="{
        get saleMode() {
            if (['quote','quote_only'].includes(this.settings.store_mode)) return 'quote';
            return (this.settings.product_button_mode==='both'||this.settings.product_button_mode==='inquiry') ? 'mixed' : 'online';
        },
        setSaleMode(m) {
            if (m==='online') { this.setSetting('store_mode','direct'); this.setSetting('product_button_mode','cart'); }
            if (m==='quote')  { this.setSetting('store_mode','quote');  this.setSetting('product_button_mode','inquiry'); }
            if (m==='mixed')  { this.setSetting('store_mode','direct'); this.setSetting('product_button_mode','both'); }
        },
    }">
        <strong class="bxb-card-title">1 · ¿Cómo quieres vender?</strong>
        <div class="bxb-grid2" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr))" role="radiogroup" aria-label="Modo comercial">
            <button type="button" class="bxb-tpl" :class="saleMode==='online'&&'is-active'" @click="setSaleMode('online')">
                <strong>🛒 Compra online</strong>
                <small>Carrito, checkout y pagos. El cliente compra directo.</small>
            </button>
            <button type="button" class="bxb-tpl" :class="saleMode==='quote'&&'is-active'" @click="setSaleMode('quote')">
                <strong>💬 Solicitar cotización</strong>
                <small>El pedido te llega por WhatsApp; puedes ocultar precios.</small>
            </button>
            <button type="button" class="bxb-tpl" :class="saleMode==='mixed'&&'is-active'" @click="setSaleMode('mixed')">
                <strong>🛒＋💬 Compra + Cotización</strong>
                <small>Carrito y botón de consulta conviven en cada producto.</small>
            </button>
        </div>
        <p class="bxb-note">Tu elección define qué opciones ves abajo: solo lo que aplica a tu forma de vender.</p>
        <div x-show="['quote','quote_only'].includes(settings.store_mode)" x-cloak>
            <div class="bxb-grid2">
                <label class="bxb-field">Texto del botón de cotización
                    <input type="text" maxlength="40" placeholder="Cotizar" :value="settings.btn_quote_text||''" @input.debounce.600ms="setSetting('btn_quote_text',$event.target.value)">
                </label>
                <div class="bxb-field"><span>Precios en modo cotización</span>
                    <div class="bxb-seg" role="radiogroup" aria-label="Mostrar precios">
                        <button type="button" :class="(settings.quote_price_display||'show')==='show'&&'on'" @click="setSetting('quote_price_display','show')">Mostrar precios</button>
                        <button type="button" :class="settings.quote_price_display==='hide'&&'on'" @click="setSetting('quote_price_display','hide')">Ocultar precios</button>
                    </div>
                </div>
            </div>
            <label class="bxb-field">Mensaje inicial de la cotización por WhatsApp
                <textarea rows="2" maxlength="500" placeholder="Hola, quiero cotizar los siguientes productos:" @input.debounce.600ms="setSetting('quote_wa_msg',$event.target.value)" x-text="settings.quote_wa_msg||''"></textarea>
                <small class="bxb-note">Se usa cuando el comprador escribe desde el carrito. Debajo se añaden los productos.</small>
            </label>
            {{-- Este mensaje estaba escrito en el código: cambiar el saludo con
                 el que un cliente recibe a SUS compradores obligaba a tocar la
                 plantilla. --}}
            <label class="bxb-field">Mensaje al consultar por un producto
                <textarea rows="2" maxlength="300" placeholder="Hola, quiero consultar por este producto:" @input.debounce.600ms="setSetting('wa_product_msg',$event.target.value)" x-text="settings.wa_product_msg||''"></textarea>
                <small class="bxb-note">Es el que se abre al pulsar <b>Consultar</b> en una tarjeta o en la ficha. El sistema añade solo el nombre del producto y su enlace, así que no hace falta que los escribas.</small>
            </label>
        </div>
    </div>

    {{-- Venta mayorista: función independiente con revelado progresivo --}}
    <div class="bxb-card">
        <label class="bxb-switch"><input type="checkbox" :checked="(settings.wholesale_enabled??'1')!=='0'" @change="setSetting('wholesale_enabled',$event.target.checked?'1':'0')"> <strong>Habilitar venta mayorista</strong></label>
        <div x-show="(settings.wholesale_enabled??'1')!=='0'" x-cloak>
            <p class="bxb-note">El precio mayorista aparece solo en los productos que lo tengan cargado (tarjetas, vista rápida y ficha). Cárgalos con las <button type="button" class="bxb-link" @click="stage='catalog'">acciones masivas del Catálogo</button> o en la ficha del producto. El modelo de encabezado "Comercial / Mayorista" lo destaca en la navegación.</p>
        </div>
    </div>

    {{-- Campos del pedido (checkout) — borrador, se publica con Publicar --}}
    <div class="bxb-card" x-show="(settings.store_mode||'direct')==='direct'" x-data="ckFieldsEditor()">
        <strong class="bxb-card-title">Datos que pide el checkout</strong>
        <p class="bxb-note">Nombre y teléfono siempre se piden. Activa o desactiva el resto y agrega campos propios.</p>
        <div class="bxb-check-inline">
            <template x-for="(f,k) in fields.fixed" :key="k">
                <label class="bxb-switch"><input type="checkbox" :checked="f.enabled" @change="f.enabled=$event.target.checked;persist()"> <span x-text="f.label"></span></label>
            </template>
        </div>
        <ul class="bxb-iconlist" role="list" x-show="fields.custom.length">
            <template x-for="(f,i) in fields.custom" :key="f.key">
                <li>
                    <span class="bxb-iconlist-name" x-text="f.label"></span>
                    <label class="bxb-switch"><input type="checkbox" :checked="f.required" @change="f.required=$event.target.checked;persist()"> Obligatorio</label>
                    <button type="button" class="bxb-link" @click="fields.custom.splice(i,1);persist()">Quitar</button>
                </li>
            </template>
        </ul>
        <div class="bxb-bulk-set">
            <input type="text" maxlength="60" placeholder="Campo nuevo (ej. Referencia de entrega)" x-model="newLabel" @keydown.enter.prevent="addField()">
            <button type="button" class="bxb-btn" @click="addField()">+ Agregar campo</button>
        </div>
    </div>

    {{-- 2. Cómo cobrar (solo si vende directo) --}}
    <div class="bxb-card" x-show="(settings.store_mode||'direct')==='direct'">
        <strong class="bxb-card-title">2 · Cómo cobrar</strong>
        <label class="bxb-switch"><input type="checkbox" :checked="settings.payment_manual_enabled==='1'" @change="setSetting('payment_manual_enabled',$event.target.checked?'1':'0')"> Aceptar pagos manuales (Yape / Plin / transferencia)</label>
        {{-- Revelado por método: apagar un método lo oculta en la tienda SIN borrar su configuración --}}
        <div x-show="settings.payment_manual_enabled==='1'" x-data="{
            mOn(m, dataKey) { const v = this.settings['payment_'+m+'_on']; return v==null ? !!(this.settings[dataKey]||'') : v==='1'; },
            mSet(m, on) { this.setSetting('payment_'+m+'_on', on ? '1' : '0'); },
        }">
            <div class="bxb-check-inline">
                <label class="bxb-switch"><input type="checkbox" :checked="mOn('yape','payment_yape_number')" @change="mSet('yape',$event.target.checked)"> Yape</label>
                <label class="bxb-switch"><input type="checkbox" :checked="mOn('plin','payment_plin_number')" @change="mSet('plin',$event.target.checked)"> Plin</label>
                <label class="bxb-switch"><input type="checkbox" :checked="mOn('bank','payment_bank_bcp')" @change="mSet('bank',$event.target.checked)"> Transferencia</label>
                <label class="bxb-switch"><input type="checkbox" :checked="mOn('cash','payment_cash_note')" @change="mSet('cash',$event.target.checked)"> Efectivo / contra entrega</label>
            </div>
            <div class="bxb-grid2" x-show="mOn('yape','payment_yape_number')" x-cloak>
                <label class="bxb-field">Número de Yape
                    <input type="text" inputmode="tel" maxlength="15" placeholder="987654321" :value="settings.payment_yape_number||''" @input.debounce.600ms="setSetting('payment_yape_number',$event.target.value)">
                </label>
                <label class="bxb-field">Titular de Yape
                    <input type="text" maxlength="120" :value="settings.payment_yape_name||''" @input.debounce.600ms="setSetting('payment_yape_name',$event.target.value)">
                </label>
                <label class="bxb-field">Nota junto a Yape (opcional)
                    <input type="text" maxlength="120" placeholder="Para compras menores a S/ 500" :value="settings.payment_yape_note||''" @input.debounce.600ms="setSetting('payment_yape_note',$event.target.value)">
                </label>
                <div class="bxb-field"><span>QR de Yape (aparece en el checkout)</span>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span class="bxb-cat-photo" :style="settings.payment_yape_qr?'background-image:url('+assetUrl(settings.payment_yape_qr)+')':''"><span x-show="!settings.payment_yape_qr">—</span></span>
                        <label class="bxb-btn">Subir QR<input type="file" accept="image/*" style="display:none" @change="uploadMedia($event,'payment_yape_qr')"></label>
                        <button type="button" class="bxb-link" x-show="settings.payment_yape_qr" @click="setSetting('payment_yape_qr','')">Quitar</button>
                    </div>
                </div>
            </div>
            <div class="bxb-grid2" x-show="mOn('plin','payment_plin_number')" x-cloak>
                <label class="bxb-field">Número de Plin
                    <input type="text" inputmode="tel" maxlength="15" :value="settings.payment_plin_number||''" @input.debounce.600ms="setSetting('payment_plin_number',$event.target.value)">
                </label>
            </div>
            <div class="bxb-field bxb-full" x-show="mOn('bank','payment_bank_bcp')" x-cloak><span>Cuentas bancarias — solo se muestran los bancos con datos</span>
                @foreach(['bcp' => 'BCP', 'interbank' => 'Interbank', 'bbva' => 'BBVA', 'nacion' => 'Banco de la Nación', 'scotiabank' => 'Scotiabank'] as $bk => $bl)
                <label class="bxb-field">{{ $bl }} — número de cuenta
                    <textarea rows="2" maxlength="400" placeholder="Cuenta Soles 191-XXXXXXX-0-XX — Titular" @input.debounce.600ms="setSetting('payment_bank_{{ $bk }}',$event.target.value)" x-text="settings.payment_bank_{{ $bk }}||''"></textarea>
                </label>
                {{-- CCI: campo propio. Antes solo cabia dentro del texto libre de la
                     cuenta, se perdia y las plantillas no podian mostrarlo aparte. --}}
                <label class="bxb-field" x-show="(settings.payment_bank_{{ $bk }}||'').trim().length>0" x-cloak>{{ $bl }} — CCI (cuenta interbancaria)
                    <input type="text" maxlength="60" placeholder="002-191-XXXXXXXXXX-XX" :value="settings.payment_cci_{{ $bk }}||''" @input.debounce.600ms="setSetting('payment_cci_{{ $bk }}',$event.target.value)">
                </label>
                @endforeach
            </div>
            <label class="bxb-field" x-show="mOn('cash','payment_cash_note')" x-cloak>Indicación para pago en efectivo (opcional)
                <input type="text" maxlength="160" placeholder="Pago contra entrega en Lima Metropolitana" :value="settings.payment_cash_note||''" @input.debounce.600ms="setSetting('payment_cash_note',$event.target.value)">
            </label>
        </div>
        <p class="bxb-note" x-show="settings.payment_manual_enabled!=='1'">Sin pagos manuales, los pedidos se coordinan por WhatsApp (contra entrega).</p>
    </div>

    {{-- 3. Cómo entregar --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">3 · Cómo entregar</strong>
        <label class="bxb-switch"><input type="checkbox" :checked="settings.shipping_enabled==='1'" @change="setSetting('shipping_enabled',$event.target.checked?'1':'0')"> Ofrezco envío a domicilio</label>
        <div x-show="settings.shipping_enabled==='1'" class="bxb-grid2">
            <label class="bxb-field">Qué se ve mientras el cliente paga
                <select :value="settings.checkout_chrome||'reduced'" @change="setSetting('checkout_chrome',$event.target.value)">
                    <option value="reduced">Encabezado reducido — logo y compra segura (recomendado)</option>
                    <option value="full">Encabezado completo — con menú y buscador</option>
                    <option value="none">Sin encabezado</option>
                </select>
                <small>Quitar el menú y el buscador durante el pago reduce las salidas y mejora la conversión.</small>
            </label>
            <label class="bxb-field">Costo de envío (S/)
                <input type="number" min="0" step="0.5" :value="settings.shipping_cost||''" @input.debounce.600ms="setSetting('shipping_cost',$event.target.value)">
            </label>
            <label class="bxb-field">Envío gratis desde (S/)
                <input type="number" min="0" step="1" placeholder="0 = nunca" :value="settings.shipping_free_from||''" @input.debounce.600ms="setSetting('shipping_free_from',$event.target.value)">
            </label>
        </div>
        <label class="bxb-switch" x-show="settings.shipping_enabled==='1'"><input type="checkbox" :checked="settings.require_address==='1'" @change="setSetting('require_address',$event.target.checked?'1':'0')"> Pedir dirección en el checkout</label>
        <label class="bxb-switch"><input type="checkbox" :checked="settings.pickup_enabled==='1'" @change="setSetting('pickup_enabled',$event.target.checked?'1':'0')"> Recojo en tienda</label>
        <div x-show="settings.pickup_enabled==='1'" x-cloak>
            <p class="bxb-note">Dirección y horario salen de <button type="button" class="bxb-link" @click="stage='business'">Mi negocio</button> — no se escriben dos veces.</p>
            <label class="bxb-field">Indicaciones para el recojo (opcional)
                <input type="text" maxlength="200" placeholder="Recoger en mostrador con tu número de pedido" :value="settings.pickup_instructions||''" @input.debounce.600ms="setSetting('pickup_instructions',$event.target.value)">
            </label>
        </div>
        <p class="bxb-note" x-show="settings.shipping_enabled!=='1' && settings.pickup_enabled!=='1'">Sin envío ni recojo configurados, los pedidos se coordinan por tu WhatsApp de Mi negocio.</p>
    </div>

    {{-- Vista del catálogo --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Vista del catálogo</strong>
        <div class="bxb-grid2">
            <label class="bxb-field">Columnas en escritorio
                <select :value="settings.catalog_cols_desktop||'3'" @change="setSetting('catalog_cols_desktop',$event.target.value)">
                    <option value="2">2 columnas</option>
                    <option value="3">3 columnas</option>
                    <option value="4">4 columnas</option>
                </select>
            </label>
            <label class="bxb-field">Columnas en móvil
                <select :value="settings.catalog_cols_mobile||'2'" @change="setSetting('catalog_cols_mobile',$event.target.value)">
                    <option value="1">1 columna</option>
                    <option value="2">2 columnas</option>
                </select>
            </label>
            <label class="bxb-field">Vista de los productos
                <select :value="settings.catalog_products_view||'cards'" @change="setSetting('catalog_products_view',$event.target.value)">
                    <option value="cards">Tarjetas</option>
                    <option value="compact">Lista compacta</option>
                </select>
            </label>
            <label class="bxb-field">Título de la sección catálogo
                <input type="text" maxlength="80" placeholder="Nuestros productos" :value="settings.catalog_section_title||''" @input.debounce.600ms="setSetting('catalog_section_title',$event.target.value)">
            </label>
        </div>
    </div>

    {{-- Textos de la tienda --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Textos de la tienda</strong>
        <p class="bxb-note">Mensajes y botones que ven tus clientes. Vacío = usa el texto estándar.</p>
        <div class="bxb-grid2">
            @foreach([
                ['key' => 'btn_cart_text',          'label' => 'Botón agregar al carrito',   'ph' => 'Agregar'],
                ['key' => 'btn_checkout_text',      'label' => 'Botón finalizar compra',     'ph' => 'Finalizar compra'],
                ['key' => 'btn_send_quote_text',    'label' => 'Botón enviar cotización',    'ph' => 'Enviar cotización por WhatsApp'],
                ['key' => 'cart_title',             'label' => 'Título del carrito',         'ph' => 'Tu carrito'],
                ['key' => 'cart_empty_msg',         'label' => 'Mensaje de carrito vacío',   'ph' => 'Tu carrito está vacío'],
                ['key' => 'cart_shipping_zero_label', 'label' => 'Envío sin costo: cómo se muestra', 'ph' => 'Gratis (o "Por coordinar")'],
                ['key' => 'txt_search_placeholder', 'label' => 'Texto del buscador',         'ph' => 'Buscar productos...'],
                ['key' => 'txt_no_results',         'label' => 'Mensaje sin resultados',     'ph' => 'No se encontraron productos'],
                ['key' => 'txt_view_more',          'label' => 'Texto "Ver más"',            'ph' => 'Ver todos los productos'],
                ['key' => 'txt_all_cats',           'label' => 'Texto "Todas las categorías"', 'ph' => 'Todas las categorías'],
                ['key' => 'featured_categories_all_text', 'label' => 'Botón "Ver todas" de categorías', 'ph' => 'Ver todas'],
            ] as $tx)
            <label class="bxb-field">{{ $tx['label'] }}
                <input type="text" maxlength="80" placeholder="{{ $tx['ph'] }}" :value="settings.{{ $tx['key'] }}||''" @input.debounce.600ms="setSetting('{{ $tx['key'] }}',$event.target.value)">
            </label>
            @endforeach
        </div>
        {{-- Carrito: opciones visuales. Defaults ya activos; se pueden apagar. --}}
        <div class="bxb-field bxb-full"><span>Carrito</span>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.cart_show_thumbs??'1')!=='0'" @change="setSetting('cart_show_thumbs',$event.target.checked?'1':'0')"> Mostrar miniatura de cada producto</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.cart_line_total??'1')!=='0'" @change="setSetting('cart_line_total',$event.target.checked?'1':'0')"> Mostrar subtotal por línea (precio × cantidad)</label>
            <label class="bxb-field">Texto del botón "seguir comprando"
                <input type="text" maxlength="40" placeholder="Seguir comprando" :value="settings.cart_keep_shopping_text||''" @input.debounce.600ms="setSetting('cart_keep_shopping_text',$event.target.value)">
            </label>
            <label class="bxb-field">Nota al pie del carrito (vacío = sin nota)
                <input type="text" maxlength="160" placeholder="Confirmaremos disponibilidad, entrega y condiciones…" :value="settings.cart_footer_note??''" @input.debounce.600ms="setSetting('cart_footer_note',$event.target.value)">
            </label>
            <label class="bxb-field">Envío gratis desde (0 = sin aviso). Muestra una barra de progreso en el carrito.
                <input type="number" min="0" step="10" placeholder="0" :value="settings.shipping_free_from||''" @input.debounce.600ms="setSetting('shipping_free_from',$event.target.value)">
            </label>
        </div>
    </div>

    {{-- 4. Información y confianza --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">4 · Información y confianza</strong>
        <ul class="bxb-trustlist" role="list">
            <li><span>Nosotros y Contacto</span><button type="button" class="bxb-btn" @click="stage='pages'">Editar</button></li>
            <li><span>Términos y Privacidad</span><small>Con texto legal base automático</small><button type="button" class="bxb-btn" @click="stage='pages'">Personalizar</button></li>
            <li><span>Libro de Reclamaciones</span><small>Activo por ley (código + correos automáticos)</small><a class="bxb-btn" :href="urls.public+'/reclamaciones'" target="_blank" rel="noopener">Ver ↗</a></li>
        </ul>
    </div>
</section>

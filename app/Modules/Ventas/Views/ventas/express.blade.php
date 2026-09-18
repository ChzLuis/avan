<x-portal-layout :layout="$portalLayout ?? 'panel'" :project="$project" pageTitle="Venta Express">

{{-- BIXO Commercial Design System v1 --}}
<style>
:root{ --bx-navy:#0E1E40; --bx-purple:#6D28D9; --bx-green:#059669; --bx-wa:#25D366; --bx-amber:#D97706; --bx-red:#DC2626;
--bx-bg:#F6F7FB; --bx-line:#E5E7EB; --bx-ink:#111827; --bx-ink2:#6B7280; --bx-r:12px; --bx-rl:14px; }
.bx-card{background:#fff;border:1px solid var(--bx-line);border-radius:var(--bx-rl);box-shadow:0 1px 3px rgba(15,23,42,.06)}
.bx-btn{min-height:48px;border-radius:var(--bx-r);font-weight:800;font-size:14px;display:flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;border:2px solid transparent;transition:.15s;padding:0 18px}
.bx-btn:disabled{opacity:.5;cursor:not-allowed}
.bx-btn-p{background:var(--bx-purple);color:#fff}.bx-btn-p:hover{filter:brightness(1.08)}
.bx-btn-g{background:var(--bx-green);color:#fff}.bx-btn-g:hover{filter:brightness(1.08)}
.bx-btn-o{background:#fff;border-color:#D1D5DB;color:var(--bx-ink)}.bx-btn-o:hover{border-color:var(--bx-purple);color:var(--bx-purple)}
.bx-chip{display:inline-flex;align-items:center;gap:6px;min-height:40px;padding:7px 14px;border:1.5px solid var(--bx-line);border-radius:999px;font-size:12.5px;font-weight:700;color:#374151;cursor:pointer;background:#fff;white-space:nowrap;transition:.12s}
.bx-chip:hover,.bx-chip.on{border-color:var(--bx-purple);color:var(--bx-purple);background:#F5F3FF}
.bx-chip.money{border-color:#D1FAE5}.bx-chip.money.on,.bx-chip.money:hover{border-color:var(--bx-green);color:var(--bx-green);background:#ECFDF5}
.bx-qty{width:44px;height:44px;border-radius:10px;border:1.5px solid var(--bx-line);background:#fff;font-size:18px;font-weight:800;color:#374151;cursor:pointer}
.bx-img{width:46px;height:46px;border-radius:10px;object-fit:cover;background:#F3F4F6;flex-shrink:0}
.bx-in{width:100%;border:2px solid var(--bx-line);border-radius:var(--bx-r);padding:12px 14px;font-size:14px;outline:0}
.bx-in:focus{border-color:var(--bx-purple)}
.bx-flash{animation:bxflash .5s ease}@keyframes bxflash{0%{background:#F5F3FF}100%{background:#fff}}
.bx-footer{position:sticky;bottom:0;background:#fff;border-top:1px solid var(--bx-line);padding:10px 14px;z-index:25;box-shadow:0 -4px 16px rgba(15,23,42,.05)}
@media(min-width:900px){ .bx-grid{display:grid;grid-template-columns:1fr 400px;gap:16px;align-items:start} .bx-cart-col{position:sticky;top:70px} .bx-footer{display:none} }
@media(max-width:899px){ .bx-cart-col{display:none} .bx-cart-col.open{display:block;position:fixed;inset:0;z-index:60;background:var(--bx-bg);overflow-y:auto;padding:14px} }
</style>

<div class="flex flex-col flex-1 overflow-y-auto" style="background:var(--bx-bg)" x-data="ventaExpress()" x-init="init()">

{{-- Encabezado --}}
<div class="bg-white border-b px-4 py-3 sticky top-0 z-30" style="border-color:var(--bx-line)">
    <div class="max-w-6xl mx-auto flex items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-black" style="color:var(--bx-ink)">⚡ Venta Express</h1>
            <p class="text-[11px]" style="color:var(--bx-ink2)">Cotiza, vende y cobra en menos de un minuto</p>
        </div>
        <div class="flex items-center gap-2">
            <button x-show="draftAvailable && !cart.length" x-cloak class="bx-chip" @click="restoreDraft()">↩ Recuperar borrador</button>
            <span class="text-[11px] font-bold px-2.5 py-1 rounded-full" :class="saved ? 'text-emerald-700 bg-emerald-50' : 'text-gray-400 bg-gray-100'" x-text="saved ? '✓ Guardado' : '· · ·'"></span>
        </div>
    </div>
</div>

<div class="flex-1 max-w-6xl mx-auto w-full p-4 bx-grid" x-show="!done">

    {{-- ═══ COLUMNA PRODUCTOS (paso 1 y único obligatorio) ═══ --}}
    <div class="space-y-3 min-w-0">
        <div class="bx-card p-4">
            <div class="flex gap-2">
                <input type="search" x-ref="psearch" x-model="pSearch" placeholder="Buscar por nombre, SKU o código de barras…" class="bx-in flex-1" autofocus>
                <button type="button" class="bx-btn bx-btn-o" style="min-height:46px;padding:0 14px" @click="scanStart()" title="Escanear código de barras">📷</button>
            </div>
            <div x-show="scanning" x-cloak class="mt-2 rounded-xl overflow-hidden relative" style="background:#000">
                <video x-ref="scanvideo" class="w-full" style="max-height:240px;object-fit:cover" autoplay playsinline muted></video>
                <p class="absolute bottom-2 left-0 right-0 text-center text-white text-xs font-bold">Apunta al código de barras…</p>
                <button class="absolute top-2 right-2 bx-chip" @click="scanStop()">✕ Cerrar</button>
            </div>
            <p x-show="scanMsg" x-cloak class="text-[11px] font-bold mt-1" style="color:var(--bx-amber)" x-text="scanMsg"></p>
            <div x-show="!pSearch && frequent.length" class="mt-3">
                <p class="text-[11px] font-bold uppercase mb-2" style="color:var(--bx-ink2)">Más vendidos</p>
                <div class="flex gap-2 overflow-x-auto pb-1" style="scrollbar-width:none">
                    <template x-for="p in frequent" :key="'f'+p.id">
                        <button class="bx-chip" @click="addProduct(p)">＋ <span x-text="p.name.length>24 ? p.name.slice(0,24)+'…' : p.name"></span></button>
                    </template>
                </div>
            </div>
            <div class="mt-2 max-h-[46vh] overflow-y-auto divide-y" style="border-color:var(--bx-line)" x-show="pSearch.length>=2">
                <template x-for="p in productMatches" :key="p.id">
                    <button class="w-full text-left py-2.5 px-1 flex items-center gap-3 hover:bg-violet-50 rounded-lg" @click="addProduct(p)">
                        <template x-if="p.image"><img :src="p.image" class="bx-img" alt=""></template>
                        <template x-if="!p.image"><span class="bx-img flex items-center justify-center text-gray-300 text-lg">▣</span></template>
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm font-bold truncate" style="color:var(--bx-ink)" x-text="p.name"></span>
                            <span class="text-[11px] font-semibold" :style="p.stock!==null && p.stock<=0 ? 'color:var(--bx-red)' : 'color:var(--bx-ink2)'"
                                  x-text="(p.sku? p.sku+' · ':'') + (p.stock===null ? 'Sin control de stock' : (p.stock<=0 ? 'SIN STOCK' : 'Stock: '+p.stock))"></span>
                        </span>
                        <span class="text-sm font-black" style="color:var(--bx-ink)" x-text="money(p.price)"></span>
                        <span class="bx-qty flex items-center justify-center" style="border-color:var(--bx-purple);color:var(--bx-purple)">＋</span>
                    </button>
                </template>
                <p x-show="!productMatches.length" class="text-xs text-center py-4" style="color:var(--bx-ink2)">Sin resultados. ¿Es un servicio? Agrégalo como ítem libre 👇</p>
            </div>
            <button class="mt-2 text-xs font-bold" style="color:var(--bx-purple)" @click="addFree()">+ Agregar ítem libre / servicio</button>
        </div>

        {{-- Estado vacío útil --}}
        <div x-show="!cart.length" class="bx-card p-6 text-center">
            <p class="text-sm font-bold" style="color:var(--bx-ink)">Todavía no agregaste productos</p>
            <p class="text-xs mt-1" style="color:var(--bx-ink2)">Escribe arriba para buscar, toca un "más vendido" o agrega un ítem libre. El cliente es opcional: la venta rápida no pide datos.</p>
        </div>
    </div>

    {{-- ═══ SMART CART (persistente en PC, drawer en móvil) ═══ --}}
    <div class="bx-cart-col space-y-3" :class="cartOpen && 'open'">
        <div class="flex md:hidden justify-between items-center" x-show="cartOpen">
            <p class="font-black" style="color:var(--bx-ink)">Tu venta</p>
            <button class="bx-chip" @click="cartOpen=false">✕ Cerrar</button>
        </div>

        <div class="bx-card p-4" :class="flash && 'bx-flash'">
            <p class="text-[11px] font-bold uppercase mb-1" style="color:var(--bx-ink2)">Carrito · <span x-text="cart.length"></span> producto(s)</p>
            <template x-for="(it,ix) in cart" :key="ix">
                <div class="flex items-center gap-2 py-2 border-b last:border-0" style="border-color:#F3F4F6">
                    <div class="flex-1 min-w-0">
                        <input type="text" x-model="it.name" class="w-full text-[13px] font-bold bg-transparent outline-none" style="color:var(--bx-ink)" :readonly="!!it.product_id">
                        <div class="flex items-center gap-1 text-[11px]" style="color:var(--bx-ink2)">S/ <input type="number" step="0.1" min="0" x-model.number="it.price" :readonly="it.product_id && !canDiscount" :title="it.product_id && !canDiscount ? 'No tienes permiso para aplicar descuento' : ''" class="w-16 bg-transparent outline-none border-b border-dashed" :class="it.product_id && !canDiscount && 'opacity-60'" style="border-color:#D1D5DB"> c/u
                            <span x-show="it.stock!==null && it.quantity>it.stock" class="font-bold" style="color:var(--bx-red)">¡+stock!</span>
                        </div>
                    </div>
                    <button class="bx-qty" @click="it.quantity>1 ? it.quantity-- : cart.splice(ix,1)">−</button>
                    <span class="w-6 text-center font-black" style="color:var(--bx-ink)" x-text="it.quantity"></span>
                    <button class="bx-qty" @click="incQty(it)">＋</button>
                    <span class="w-[74px] text-right text-[13px] font-black" style="color:var(--bx-ink)" x-text="money(it.price*it.quantity)"></span>
                </div>
            </template>
            <div class="flex justify-between items-center pt-3">
                <button x-show="cart.length" class="text-[11px] font-bold" style="color:var(--bx-red)" @click="bxConfirmar({ descripcion: '¿Vaciar el carrito? Se quitarán todos los productos.', boton: 'Vaciar' }).then(ok => { if (ok) cart = []; })">Vaciar</button>
                <div class="text-right flex-1">
                    <span class="text-[11px] font-bold uppercase" style="color:var(--bx-ink2)">Total</span>
                    <span class="text-2xl font-black ml-2" style="color:var(--bx-ink)" x-text="money(total())"></span>
                </div>
            </div>
        </div>

        {{-- Cliente OPCIONAL --}}
        <div class="bx-card p-4">
            <button x-show="!clientOpen && !clientLabelSet()" class="text-xs font-bold" style="color:var(--bx-purple)" @click="clientOpen=true">+ Agregar cliente (opcional)</button>
            <div x-show="clientOpen || clientLabelSet()" x-cloak>
                <div class="flex justify-between items-center mb-2">
                    <p class="text-[11px] font-bold uppercase" style="color:var(--bx-ink2)">Cliente</p>
                    <button x-show="clientLabelSet()" class="text-[11px] font-bold" style="color:var(--bx-ink2)" @click="client=null;cName='';cPhone='';clientOpen=false">Quitar → Público general</button>
                </div>
                <div x-show="!clientLabelSet()">
                    <input type="search" x-model="cSearch" placeholder="Buscar por nombre o teléfono…" class="bx-in" style="padding:9px 12px;font-size:13px">
                    <div class="max-h-36 overflow-y-auto mt-1" x-show="cSearch.length>=2">
                        <template x-for="c in clientMatches" :key="c.id">
                            <button class="w-full text-left py-2 px-1 flex justify-between hover:bg-violet-50 rounded-lg" @click="pickClient(c)">
                                <span class="text-[13px] font-bold" style="color:var(--bx-ink)" x-text="c.name"></span>
                                <span class="text-[11px]" style="color:var(--bx-ink2)" x-text="c.phone"></span>
                            </button>
                        </template>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <input type="text" x-model="cName" placeholder="Nombre" class="bx-in" style="padding:9px 12px;font-size:13px">
                        <input type="tel" x-model="cPhone" placeholder="Celular (WhatsApp)" class="bx-in" style="padding:9px 12px;font-size:13px">
                    </div>
                </div>
                <div x-show="clientLabelSet()" class="flex items-center gap-2">
                    <span class="w-9 h-9 rounded-full flex items-center justify-center font-black text-white text-sm" style="background:var(--bx-purple)" x-text="clientLabel().charAt(0).toUpperCase()"></span>
                    <div class="flex-1">
                        <p class="text-[13px] font-black" style="color:var(--bx-ink)" x-text="clientLabel()"></p>
                        <p class="text-[11px]" style="color:var(--bx-ink2)" x-text="phoneOf() || 'Sin celular'"></p>
                    </div>
                    {{-- WhatsApp directo cuando hay cliente con celular (regla: WhatsApp es acción comercial, no solo contacto) --}}
                    <a x-show="waPhone()" x-cloak :href="'https://wa.me/'+waPhone()" target="_blank" class="bx-chip" style="color:var(--bx-wa);border-color:#D1FAE5">💬 WhatsApp</a>
                </div>
            </div>
        </div>

        {{-- Salidas: el carrito NUNCA se pierde al cambiar --}}
        <p x-show="error" x-cloak class="text-[13px] font-bold rounded-xl px-4 py-3" style="color:var(--bx-red);background:#FEF2F2;border:1px solid #FECACA" x-text="error"></p>

        <div class="grid gap-2" x-show="mode===null">
            <button class="bx-btn bx-btn-g" :disabled="!cart.length" @click="mode='charge'">💰 Cobrar ahora <span x-show="cart.length" x-text="'· '+money(total())"></span></button>
            <button class="bx-btn bx-btn-p" :disabled="!cart.length" @click="mode='order'">🧾 Crear pedido</button>
            <button class="bx-btn bx-btn-o" :disabled="!cart.length" @click="mode='quote'">📄 Enviar cotización</button>
        </div>

        {{-- COBRO --}}
        <div x-show="mode==='charge'" x-cloak class="bx-card p-4 space-y-3">
            <div class="flex justify-between items-center"><p class="font-black text-sm" style="color:var(--bx-ink)">¿Cómo paga?</p><button class="text-[11px] font-bold" style="color:var(--bx-ink2)" @click="mode=null">← Volver</button></div>
            <div class="flex flex-wrap gap-2">
                <template x-for="m in payOptions" :key="m">
                    <button class="bx-chip money" :class="payMethod===m && 'on'" @click="payMethod=m; mixOn=false" x-text="m"></button>
                </template>
                <button class="bx-chip money" :class="mixOn && 'on'" @click="mixOn=true; payMethod='Mixto'; if(!mix.length) mix=[{m:payOptions[0]||'Efectivo',a:null},{m:payOptions[1]||'Yape',a:null}]">Pago mixto</button>
            </div>
            {{-- Pago mixto: la suma debe cuadrar con el total --}}
            <div x-show="mixOn" x-cloak class="rounded-xl p-3 space-y-2" style="background:#F9FAFB;border:1px solid var(--bx-line)">
                <template x-for="(row,ri) in mix" :key="ri">
                    <div class="flex gap-2 items-center">
                        <select x-model="row.m" class="bx-in" style="padding:8px 10px;font-size:13px;flex:1">
                            <template x-for="m in payOptions" :key="'mx'+m"><option :value="m" x-text="m"></option></template>
                        </select>
                        <input type="number" step="0.1" min="0" x-model.number="row.a" class="bx-in" style="padding:8px 10px;font-size:13px;width:110px" placeholder="S/">
                        <button x-show="mix.length>2" class="bx-qty" style="width:36px;height:36px" @click="mix.splice(ri,1)">−</button>
                    </div>
                </template>
                <div class="flex justify-between items-center">
                    <button class="text-[11px] font-bold" style="color:var(--bx-purple)" @click="mix.push({m:payOptions[0]||'Efectivo',a:null})">+ Agregar método</button>
                    <p class="text-[12px] font-black" :style="Math.abs(mixSum()-total())<0.01 ? 'color:var(--bx-green)' : 'color:var(--bx-amber)'"
                       x-text="Math.abs(mixSum()-total())<0.01 ? '✓ Cuadra con el total' : ('Faltan '+money(total()-mixSum()))"></p>
                </div>
            </div>
            {{-- Efectivo: vuelto --}}
            <div x-show="payMethod && payMethod.toLowerCase().includes('efectivo')" class="rounded-xl p-3" style="background:#F9FAFB;border:1px solid var(--bx-line)">
                <p class="text-[11px] font-bold uppercase mb-1.5" style="color:var(--bx-ink2)">Monto recibido</p>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    <template x-for="b in [10,20,50,100,200]" :key="b"><button class="bx-chip money" @click="received=Number(received||0)+b" x-text="'S/ '+b"></button></template>
                    <button class="bx-chip money" @click="received=total()">Exacto</button>
                </div>
                <input type="number" step="0.1" min="0" x-model.number="received" class="bx-in" placeholder="S/ recibido">
                <p class="text-sm font-black mt-2" :style="change()>=0 ? 'color:var(--bx-green)' : 'color:var(--bx-amber)'"
                   x-text="received ? (change()>=0 ? 'Vuelto: '+money(change()) : 'Faltan '+money(-change())) : ''"></p>
            </div>
            <div x-show="payMethod && !payMethod.toLowerCase().includes('efectivo')">
                <input type="text" x-model="payRef" class="bx-in" placeholder="Código de operación (opcional)">
            </div>
            <button class="bx-btn bx-btn-g w-full" :disabled="!payMethod || sending" @click="closeAs('charge')">
                <span x-text="sending ? 'Registrando…' : 'Confirmar cobro de '+money(total())"></span>
            </button>
        </div>

        {{-- PEDIDO --}}
        <div x-show="mode==='order'" x-cloak class="bx-card p-4 space-y-3">
            <div class="flex justify-between items-center"><p class="font-black text-sm" style="color:var(--bx-ink)">Crear pedido</p><button class="text-[11px] font-bold" style="color:var(--bx-ink2)" @click="mode=null">← Volver</button></div>
            <p class="text-[11px]" style="color:var(--bx-ink2)">Solo pedimos lo necesario: un nombre para ubicarlo (el celular permite avisarle por WhatsApp).</p>
            <input type="text" x-model="cName" x-show="!client" class="bx-in" placeholder="Nombre del cliente *">
            <input type="tel" x-model="cPhone" x-show="!client" class="bx-in" placeholder="Celular (recomendado)">
            <div class="flex gap-2">
                <button type="button" class="bx-chip flex-1" :class="deliveryType==='recojo' && 'on'" @click="deliveryType='recojo'" style="justify-content:center">🏬 Recojo en tienda</button>
                <button type="button" class="bx-chip flex-1" :class="deliveryType==='delivery' && 'on'" @click="deliveryType='delivery'" style="justify-content:center">🛵 Delivery</button>
            </div>
            <input type="text" x-show="deliveryType==='delivery'" x-cloak x-model="deliveryAddress" class="bx-in" placeholder="Dirección de entrega *">
            <div class="grid grid-cols-2 gap-2">
                <div><label class="text-[11px] font-bold" style="color:var(--bx-ink2)">Fecha prometida</label><input type="datetime-local" x-model="promisedAt" class="bx-in" style="padding:9px 12px;font-size:13px"></div>
                <div><label class="text-[11px] font-bold" style="color:var(--bx-ink2)">Adelanto (opcional)</label><input type="number" step="0.1" min="0" :max="total()" x-model.number="advance" class="bx-in" style="padding:9px 12px;font-size:13px" placeholder="S/"></div>
            </div>
            <textarea x-model="notes" rows="2" class="bx-in" placeholder="Nota u observación (opcional)"></textarea>
            <p x-show="deliveryType==='delivery' && !deliveryAddress" class="text-[11px] font-bold" style="color:var(--bx-amber)">El delivery necesita una dirección de entrega.</p>
            <button class="bx-btn bx-btn-p w-full" :disabled="sending" @click="closeAs('order')"><span x-text="sending ? 'Creando…' : 'Crear pedido'"></span></button>
        </div>

        {{-- COTIZACIÓN --}}
        <div x-show="mode==='quote'" x-cloak class="bx-card p-4 space-y-3">
            <div class="flex justify-between items-center"><p class="font-black text-sm" style="color:var(--bx-ink)">Enviar cotización</p><button class="text-[11px] font-bold" style="color:var(--bx-ink2)" @click="mode=null">← Volver</button></div>
            <p class="text-[11px]" style="color:var(--bx-ink2)">El cliente recibirá un enlace donde puede aceptar y hasta pagar con Yape. Necesitamos su nombre y celular.</p>
            <input type="text" x-model="cName" x-show="!client" class="bx-in" placeholder="Nombre del cliente *">
            <input type="tel" x-model="cPhone" x-show="!client" class="bx-in" placeholder="Celular WhatsApp *">
            <textarea x-model="notes" rows="2" class="bx-in" placeholder="Condiciones u observaciones (opcional)"></textarea>
            <button class="bx-btn bx-btn-p w-full" :disabled="sending" @click="closeAs('quote')"><span x-text="sending ? 'Creando…' : 'Crear y preparar WhatsApp'"></span></button>
        </div>
    </div>
</div>

{{-- ═══ ÉXITO ═══ --}}
<div x-show="done" x-cloak class="flex-1 max-w-xl mx-auto w-full p-4 space-y-3">
    <div class="bx-card p-6 text-center">
        <div class="w-14 h-14 mx-auto rounded-full text-white flex items-center justify-center text-2xl font-black mb-3" style="background:var(--bx-green)">✓</div>
        <p class="text-lg font-black" style="color:var(--bx-ink)" x-text="done && done.title"></p>
        <p class="text-sm mt-1" style="color:var(--bx-ink2)" x-text="done && done.sub"></p>
        <p class="text-2xl font-black mt-2" style="color:var(--bx-ink)" x-text="done && money(done.total)"></p>
        <p x-show="done && done.change > 0" class="text-sm font-black mt-1" style="color:var(--bx-green)" x-text="done && 'Vuelto: '+money(done.change)"></p>
    </div>
    @if($yape['number'])
    <div x-show="done && done.type==='charge' && done.method && (done.method.toLowerCase().includes('yape')||done.method.toLowerCase().includes('plin'))" x-cloak
         class="rounded-2xl p-5 text-white text-center" style="background:linear-gradient(135deg,#6c1eb0,#8b2fc9)">
        <p class="font-black mb-2">Muestra este QR al cliente</p>
        @if($yape['qr'])<div class="bg-white p-2 rounded-xl inline-block"><img src="{{ $yape['qr'] }}" class="w-40 h-40 object-contain" alt="QR Yape"></div>@endif
        <p class="text-2xl font-black mt-2">{{ $yape['number'] }}</p>
        <p class="text-xs font-bold opacity-90">{{ $yape['name'] }}</p>
    </div>
    @endif
    <div class="grid gap-2">
        <a x-show="done && done.wa" :href="done && done.wa" target="_blank" class="bx-btn text-white" style="background:var(--bx-wa)">📲 Enviar por WhatsApp</a>
        <button x-show="done && done.url" class="bx-btn bx-btn-o" @click="navigator.clipboard.writeText(done.url); copied=true; setTimeout(()=>copied=false,1500)"><span x-text="copied ? '✓ Enlace copiado' : '🔗 Copiar enlace de la cotización'"></span></button>
        <a x-show="done && done.order_id" href="{{ ($portalLayout ?? 'panel')==='comercial' ? route('bixosales.pedidos') : route('orders') }}" class="bx-btn bx-btn-o">📋 Ver en el Centro de pedidos</a>
        @if(($portalLayout ?? 'panel') !== 'comercial' && \Illuminate\Support\Facades\Route::has('invoices.index'))
        <a x-show="done && done.type==='charge'" href="{{ route('invoices.index') }}" class="bx-btn bx-btn-o">🧾 Emitir boleta / factura</a>
        @endif
        <button class="bx-btn bx-btn-p" @click="reset()">⚡ Nueva venta</button>
    </div>
</div>

{{-- Barra móvil del carrito --}}
<div class="bx-footer md:hidden" x-show="!done && !cartOpen">
    <button class="bx-btn w-full" :class="cart.length ? 'bx-btn-g' : 'bx-btn-o'" @click="cart.length ? cartOpen=true : $refs.psearch.focus()">
        <span x-text="cart.length ? (cart.length+' producto(s) · '+money(total())+' · Ver carrito') : 'Busca un producto para empezar'"></span>
    </button>
</div>

<script>
function ventaExpress() {
    return {
        done: null, sending: false, error: '', copied: false, mode: null, cartOpen: false, flash: false,
        // Huella POR VENTA: identifica el intento ante el servidor.
        huellaVenta: '',
        saved: true, draftAvailable: false, _saveTimer: null,
        clients: {{ Js::from($clientsLite) }},
        products: {{ Js::from($productsLite) }},
        frequentIds: {{ Js::from($frequentIds) }},
        payOptions: {{ Js::from(collect($paymentMethods)->count() ? collect($paymentMethods)->values() : collect(['Efectivo','Yape','Plin','Transferencia','Tarjeta'])) }},
        cSearch: '', cName: '', cPhone: '', client: null, clientOpen: false,
        pSearch: '', cart: [], payMethod: '', payRef: '', received: null, notes: '',
        deliveryType: '', deliveryAddress: '', promisedAt: '', advance: null,
        mixOn: false, mix: [],
        canDiscount: {{ Js::from((bool) ($canDiscount ?? false)) }},
        preload: {{ Js::from($preload ?? null) }},
        mixSum() { return this.mix.reduce((s, r) => s + (Number(r.a)||0), 0); },
        draftKey: 'bx_express_{{ $project->id }}',
        init() {
            // "BIXO empieza con lo que ya conoce": cliente precargado desde
            // CRM / pedido anterior / cotización / WhatsApp (regla §2).
            if (this.preload) {
                if (this.preload.id) this.client = { id: this.preload.id, name: this.preload.name, phone: this.preload.phone };
                else { this.cName = this.preload.name || ''; this.cPhone = this.preload.phone || ''; }
                this.$nextTick(() => this.$refs.psearch && this.$refs.psearch.focus());
            }
            try { this.draftAvailable = !this.preload && !!localStorage.getItem(this.draftKey); } catch(e) {}
            this.$watch('cart', () => this.persist(), { deep: true });
            ['cName','cPhone','notes'].forEach(k => this.$watch(k, () => this.persist()));
        },
        persist() {
            this.saved = false;
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(() => {
                try { localStorage.setItem(this.draftKey, JSON.stringify({ cart: this.cart, client: this.client, cName: this.cName, cPhone: this.cPhone, notes: this.notes })); } catch(e) {}
                this.saved = true;
            }, 500);
        },
        restoreDraft() {
            try {
                const d = JSON.parse(localStorage.getItem(this.draftKey) || '{}');
                this.cart = d.cart || []; this.client = d.client || null;
                this.cName = d.cName || ''; this.cPhone = d.cPhone || ''; this.notes = d.notes || '';
                this.draftAvailable = false;
            } catch(e) {}
        },
        clearDraft() { try { localStorage.removeItem(this.draftKey); } catch(e) {} },
        get clientMatches() { const q = this.cSearch.toLowerCase(); return this.clients.filter(c => c.name.toLowerCase().includes(q) || (c.phone||'').includes(q)).slice(0, 10); },
        get productMatches() { const q = this.pSearch.toLowerCase(); return this.products.filter(p => p.name.toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q) || (p.barcode||'').includes(q)).slice(0, 20); },
        scanning: false, scanMsg: '', _scanStream: null, _scanTimer: null,
        async scanStart() {
            if (!('BarcodeDetector' in window)) { this.scanMsg = 'Tu navegador no soporta escáner; usa Chrome en Android o escribe el código.'; setTimeout(()=>this.scanMsg='',4000); return; }
            try {
                this._scanStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                this.scanning = true;
                this.$nextTick(() => { this.$refs.scanvideo.srcObject = this._scanStream; });
                const detector = new BarcodeDetector({ formats: ['ean_13','ean_8','code_128','code_39','upc_a','upc_e','qr_code'] });
                this._scanTimer = setInterval(async () => {
                    try {
                        const codes = await detector.detect(this.$refs.scanvideo);
                        if (codes.length) {
                            const val = codes[0].rawValue;
                            const p = this.products.find(x => x.barcode === val || x.sku === val);
                            if (p) { this.addProduct(p); this.scanStop(); }
                            else { this.scanMsg = 'Código '+val+' no está en tu catálogo.'; }
                        }
                    } catch(e) {}
                }, 400);
            } catch(e) { this.scanMsg = 'No se pudo abrir la cámara.'; setTimeout(()=>this.scanMsg='',3000); }
        },
        scanStop() {
            this.scanning = false;
            clearInterval(this._scanTimer);
            if (this._scanStream) { this._scanStream.getTracks().forEach(t => t.stop()); this._scanStream = null; }
        },
        get frequent() { return this.frequentIds.map(id => this.products.find(p => p.id === id)).filter(Boolean); },
        pickClient(c) { this.client = c; this.cSearch = ''; },
        clientLabelSet() { return !!(this.client || this.cName.trim()); },
        clientLabel() { return (this.client && this.client.name) || this.cName.trim() || 'Público general'; },
        phoneOf() { return (this.client && this.client.phone) || this.cPhone || ''; },
        addProduct(p) {
            if (p.stock !== null && p.stock <= 0) { this.error = '"'+p.name+'" no tiene stock.'; setTimeout(()=>this.error='',2500); return; }
            const ex = this.cart.find(i => i.product_id === p.id);
            if (ex) { if (p.stock !== null && ex.quantity + 1 > p.stock) { this.error = 'Solo hay '+p.stock+' de "'+p.name+'".'; setTimeout(()=>this.error='',2500); return; } ex.quantity++; }
            else this.cart.push({ product_id: p.id, name: p.name, price: p.price, quantity: 1, stock: p.stock });
            this.pSearch = ''; this.flash = true; setTimeout(() => this.flash = false, 500);
            this.$nextTick(() => this.$refs.psearch && this.$refs.psearch.focus());
        },
        incQty(it) { if (it.stock !== null && it.quantity + 1 > it.stock) { this.error = 'Stock máximo: '+it.stock+'.'; setTimeout(()=>this.error='',2000); return; } it.quantity++; },
        addFree() { this.cart.push({ product_id: null, name: 'Servicio / ítem', price: 0, quantity: 1, stock: null }); },
        total() { return this.cart.reduce((s, i) => s + (Number(i.price)||0) * i.quantity, 0); },
        change() { return Number(this.received||0) - this.total(); },
        money(v) { return 'S/ ' + Number(v||0).toLocaleString('es-PE', { minimumFractionDigits: 2 }); },
        waPhone() { let n = this.phoneOf().replace(/\D/g, ''); if (n.length === 9) n = '51' + n; return n; },
        async closeAs(mode) {
            if (this.sending) return; // candado anti doble-clic
            if (!this.cart.length) { this.error = 'Agrega al menos un producto.'; return; }
            const bad = this.cart.find(i => i.stock !== null && i.quantity > i.stock);
            if (bad) { this.error = 'No hay stock suficiente de "'+bad.name+'".'; return; }
            // Validaciones condicionales por operación
            if (mode === 'quote' && !this.client && (!this.cName.trim() || !this.cPhone.trim())) { this.error = 'La cotización necesita nombre y celular para enviarla.'; return; }
            if (mode === 'order' && !this.client && !this.cName.trim()) { this.error = 'El pedido necesita al menos el nombre del cliente.'; return; }
            if (mode === 'order' && this.deliveryType === 'delivery' && !this.deliveryAddress.trim()) { this.error = 'El delivery necesita una dirección de entrega.'; return; }
            if (mode === 'order' && this.advance && Number(this.advance) > this.total()) { this.error = 'El adelanto no puede ser mayor al total.'; return; }
            if (mode === 'charge' && this.payMethod.toLowerCase().includes('efectivo') && this.received && this.change() < 0) { this.error = 'El monto recibido no cubre el total.'; return; }
            if (mode === 'charge' && this.mixOn) {
                if (this.mix.some(r => !(Number(r.a) > 0))) { this.error = 'Completa el monto de cada método del pago mixto.'; return; }
                if (Math.abs(this.mixSum() - this.total()) >= 0.01) { this.error = 'El pago mixto debe sumar exactamente '+this.money(this.total())+'.'; return; }
            }
            this.sending = true; this.error = '';
            const payload = {
                client_id: this.client ? this.client.id : null,
                client_name: this.clientLabelSet() ? this.clientLabel() : null,
                client_phone: this.phoneOf() || null,
                notes: this.notes || null,
                items: this.cart.map(i => ({ product_id: i.product_id, name: i.name, price: Number(i.price)||0, quantity: i.quantity })),
            };
            if (mode === 'order') {
                if (this.deliveryType) payload.delivery_type = this.deliveryType;
                if (this.deliveryAddress) payload.delivery_address = this.deliveryAddress;
                if (this.promisedAt) payload.promised_at = this.promisedAt;
                if (this.advance) payload.advance_amount = Number(this.advance);
            }
            /* HUELLA ANTI DOBLE COBRO. El candado `sending` cubre el doble
               toque, pero NO el reintento tras un timeout ni la recarga con
               reenvio: ahi se cobraba dos veces. El servidor ya tiene la
               proteccion (PosController::store), pero solo se activa si llega
               esta cabecera; el POS la mandaba y Express no. Se conserva
               mientras se reintenta la MISMA venta y se renueva al vaciar. */
            if (!this.huellaVenta) this.huellaVenta = 'e' + Date.now() + Math.random().toString(36).slice(2, 8);
            const hdr = { 'Content-Type': 'application/json', 'Accept': 'application/json',
                          'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                          'X-Idempotencia': this.huellaVenta };
            try {
                if (mode === 'quote') {
                    const r = await fetch(@js($quoteUrl), { method: 'POST', headers: hdr, body: JSON.stringify(payload) });
                    const d = await r.json();
                    if (!r.ok || !d.ok) throw new Error(d.error || d.message || 'No se pudo crear la cotización.');
                    const phone = this.waPhone();
                    const txt = `Hola ${this.clientLabel()} 👋, te envío la cotización de {{ $project->name }} por ${this.money(d.total)}. Puedes revisarla, aceptarla y hasta pagarla aquí: ${d.url}`;
                    this.done = { type: 'quote', title: '¡Cotización lista!', sub: 'El cliente puede aceptar y pagar desde su enlace.', total: d.total, url: d.url,
                        wa: phone ? `https://wa.me/${phone}?text=${encodeURIComponent(txt)}` : null };
                } else {
                    payload.payment_method = mode === 'charge'
                        ? (this.mixOn ? 'Mixto: ' + this.mix.map(r => r.m+' S/'+Number(r.a).toFixed(2)).join(' + ') : this.payMethod)
                        : 'Por definir';
                    payload.paid = mode === 'charge';
                    if (this.payRef) payload.notes = ((payload.notes||'') + ' · Op: ' + this.payRef).trim();
                    const r = await fetch(@js($storeUrl), { method: 'POST', headers: hdr, body: JSON.stringify(payload) });
                    const d = await r.json();
                    if (!r.ok || d.ok === false) throw new Error(d.error || d.message || 'No se pudo registrar la venta.');
                    const oid = (d.order && d.order.id) || d.order_id;
                    const phone = this.waPhone();
                    const txt = mode === 'charge'
                        ? `¡Gracias por tu compra en {{ $project->name }}! 🙌 Registramos tu pago de ${this.money(this.total())} (${this.payMethod}). Pedido #${oid}.`
                        : `Hola ${this.clientLabel()}, registramos tu pedido #${oid} en {{ $project->name }} por ${this.money(this.total())}. Te avisamos cuando esté listo. 😊`;
                    this.done = { type: mode, title: mode === 'charge' ? '¡Cobro registrado!' : '¡Pedido creado!',
                        sub: mode === 'charge' ? 'Pago con ' + this.payMethod + ' auditado.' : 'Ya está en tu Centro de pedidos como pago pendiente.',
                        total: this.total(), order_id: oid, method: this.payMethod,
                        change: (mode === 'charge' && this.payMethod.toLowerCase().includes('efectivo') && this.received) ? Math.max(0, this.change()) : 0,
                        wa: phone ? `https://wa.me/${phone}?text=${encodeURIComponent(txt)}` : null };
                }
                this.clearDraft();
            } catch (e) { this.error = e.message; }
            this.sending = false;
        },
        /* Venta nueva, huella nueva: si no se renovara, el servidor
           devolveria la venta anterior en vez de registrar esta. */
        reset() { this.huellaVenta = ''; this.done = null; this.mode = null; this.client = null; this.clientOpen = false; this.cName = ''; this.cPhone = ''; this.cart = []; this.payMethod = ''; this.payRef = ''; this.received = null; this.notes = ''; this.cartOpen = false; this.cSearch = ''; this.pSearch = ''; this.deliveryType = ''; this.deliveryAddress = ''; this.promisedAt = ''; this.advance = null; this.mixOn = false; this.mix = []; },
    };
}
</script>

</x-portal-layout>

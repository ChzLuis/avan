{{-- Etapa 4: Catálogo — métricas reales, accesos rápidos y acciones masivas. --}}
<section class="bxb-stage" x-init="loadMetrics()">
    <h2>Catálogo</h2>
    <p class="bxb-stage-sub">Revisa la salud de tu catálogo y corrige en lote. El detalle de cada producto se edita en su ficha.</p>

    {{-- Métricas --}}
    <div class="bxb-metrics">
        <button type="button" class="bxb-metric" @click="loadFixList('all')"><b x-text="metrics.total??'—'"></b><span>Productos</span></button>
        <button type="button" class="bxb-metric" @click="loadFixList('all')"><b x-text="metrics.published??'—'"></b><span>Publicados</span></button>
        <button type="button" class="bxb-metric" :data-warn="(metrics.without_price||0)>0" @click="loadFixList('no_price')"><b x-text="metrics.without_price??'—'"></b><span>Sin precio</span></button>
        <button type="button" class="bxb-metric" :data-warn="(metrics.without_image||0)>0" @click="loadFixList('no_image')"><b x-text="metrics.without_image??'—'"></b><span>Sin imagen</span></button>
        <button type="button" class="bxb-metric" :data-warn="(metrics.sku_duplicates||0)>0" @click="loadFixList('sku_dup')"><b x-text="metrics.sku_duplicates??'—'"></b><span>SKU duplicados</span></button>
        <button type="button" class="bxb-metric" :data-warn="(metrics.empty_categories||0)>0"><b x-text="metrics.empty_categories??'—'"></b><span>Categorías vacías</span></button>
    </div>

    {{-- Acciones iniciales --}}
    <div class="bxb-actions-row">
        <a class="bxb-btn bxb-btn-primary" href="{{ route('products.create') }}" target="_blank" rel="noopener">+ Crear producto</a>
        <a class="bxb-btn" href="{{ route('products.index') }}" target="_blank" rel="noopener">Abrir catálogo completo ↗</a>
        <a class="bxb-btn" href="{{ route('products.template') }}">⇩ Plantilla Excel</a>
        <a class="bxb-btn" href="{{ route('products.index') }}#importar" target="_blank" rel="noopener">⇧ Importar Excel</a>
    </div>

    {{-- Lista para corregir --}}
    <div class="bxb-card" x-show="fixFilter" x-cloak>
        <div class="bxb-fix-head">
            <strong class="bxb-card-title" x-text="({all:'Todos los productos',no_price:'Productos sin precio',no_image:'Productos sin imagen',sku_dup:'SKU duplicados',incomplete:'Incompletos'})[fixFilter]"></strong>
            <span style="flex:1"></span>
            <label class="bxb-switch" style="min-height:34px"><input type="checkbox" @change="toggleSelectAll($event.target.checked)"> Seleccionar todo</label>
            <button type="button" class="bxb-link" @click="fixFilter=null">Cerrar</button>
        </div>

        {{-- Barra de acciones masivas --}}
        <div class="bxb-bulkbar" x-show="selectedIds.length" x-cloak>
            <strong x-text="selectedIds.length + ' seleccionados'"></strong>
            <button type="button" class="bxb-btn" @click="bulk('publish')">Publicar</button>
            <button type="button" class="bxb-btn" @click="bulk('unpublish')">Despublicar</button>
            <span class="bxb-bulk-set">
                <input type="number" min="0" step="0.1" placeholder="Precio S/" x-model="bulkValue" aria-label="Precio para asignar">
                <button type="button" class="bxb-btn" @click="bulk('price_set')">Fijar precio</button>
            </span>
            <span class="bxb-bulk-set">
                <input type="number" step="1" placeholder="±%" x-model="bulkPct" aria-label="Porcentaje de ajuste">
                <button type="button" class="bxb-btn" @click="bulk('price_adjust')">Ajustar %</button>
            </span>
        </div>

        <ul class="bxb-fixlist" role="list">
            <template x-for="p in fixItems" :key="p.id">
                <li>
                    <input type="checkbox" :value="p.id" x-model="selectedIds" :aria-label="'Seleccionar '+p.name">
                    <span class="bxb-fix-thumb" :style="p.image?'background-image:url('+p.image+')':''"></span>
                    <span class="bxb-fix-copy"><strong x-text="p.name"></strong><small x-text="(p.sku?('SKU '+p.sku+' · '):'')+(p.is_available?'Publicado':'Oculto')"></small></span>
                    <span class="bxb-bulk-set">
                        <input type="number" min="0" step="0.1" :value="p.price||''" @change="fixPrice(p,$event.target.value)" aria-label="Precio del producto" style="width:92px">
                    </span>
                    <a class="bxb-link" :href="p.edit_url" target="_blank" rel="noopener">Abrir ↗</a>
                </li>
            </template>
        </ul>
        <p class="bxb-note" x-show="fixItems.length===0">Nada que corregir aquí. 🎉</p>
        <button type="button" class="bxb-btn" x-show="fixHasMore" @click="loadFixList(fixFilter,true)">Cargar más</button>
        <p class="bxb-note" x-show="bulkResult" x-cloak x-text="bulkResult" role="status"></p>
    </div>

    {{-- Perfiles de catálogo (datos vivos: se aplican de inmediato) --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Perfiles de catálogo</strong>
        <p class="bxb-note">Divide tu tienda en vistas con identidad propia (ej. Hombre / Mujer, Minorista / Mayorista). Aparecen como pestañas en el menú de la tienda. Estos cambios se aplican de inmediato.</p>
        <label class="bxb-switch"><input type="checkbox" :checked="profilesEnabled" @change="profilesFeature($event.target.checked)"> Usar perfiles de catálogo</label>
        <div x-show="profilesEnabled" x-cloak>
            <label class="bxb-field">Productos sin perfil asignado
                <select :value="orphanPolicy" @change="saveOrphanPolicy($event.target.value)">
                    <option value="hide">Ocultarlos dentro de los perfiles</option>
                    <option value="show_all">Mostrarlos en todos los perfiles</option>
                </select>
            </label>
            <ul class="bxb-iconlist" role="list">
                <template x-for="pf in profiles" :key="'pf'+pf.id">
                    <li>
                        <span class="bxb-iconlist-name" x-text="pf.name"></span>
                        <input type="text" maxlength="120" placeholder="Nombre en el menú" :value="pf.menu_label" @change="pf.menu_label=$event.target.value;profileSave(pf)" style="width:150px">
                        <label class="bxb-switch"><input type="checkbox" :checked="pf.is_enabled" @change="pf.is_enabled=$event.target.checked;profileSave(pf)"> Activo</label>
                        <label class="bxb-switch"><input type="checkbox" :checked="pf.show_in_menu" @change="pf.show_in_menu=$event.target.checked;profileSave(pf)"> En menú</label>
                        <button type="button" class="bxb-link" @click="profileDelete(pf)">Eliminar</button>
                    </li>
                </template>
            </ul>
            <p class="bxb-note" x-show="!profiles.length">Aún no tienes perfiles: crea el primero abajo.</p>
            <div class="bxb-bulk-set">
                <input type="text" maxlength="120" placeholder="Nuevo perfil (ej. Mayorista)" x-model="newProfileName" @keydown.enter.prevent="profileCreate()">
                <button type="button" class="bxb-btn" @click="profileCreate()">+ Crear perfil</button>
            </div>
        </div>
    </div>

    {{-- Editor completo de perfiles (identidad, productos y categorías por perfil) --}}
    <div class="bxb-card bxb-embed" x-show="profilesEnabled" x-cloak>
        @include('settings.partials.catalog-profiles')
    </div>

    {{-- ── Tarjeta de producto: modo comercial + opciones visuales combinables.
         El modo define la interaccion; el resto se combina libremente. Las
         opciones sin sentido para el modo activo se ocultan (revelado progresivo). --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Tarjeta de producto — compra y precios</strong>
        <label class="bxb-field">Modo de compra
            <select :value="settings.purchase_mode||'separate'" @change="setSetting('purchase_mode',$event.target.value)">
                <option value="separate">Separado Minorista / Mayorista</option>
                <option value="auto">Precio automático por cantidad</option>
            </select>
        </label>
        <p class="bxb-note" x-show="(settings.purchase_mode||'separate')==='separate'">Cada precio en su bloque, con su propio selector y botón. Es el comportamiento actual.</p>
        <p class="bxb-note" x-show="settings.purchase_mode==='auto'">Un solo precio que cambia solo al llegar a la cantidad mayorista. Más simple para el comprador.</p>

        <div class="bxb-field bxb-full"><span>Precio</span>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.card_show_wholesale_price??'1')!=='0'" @change="setSetting('card_show_wholesale_price',$event.target.checked?'1':'0')"> Mostrar precio mayorista</label>
            <label class="bxb-switch" x-show="(settings.purchase_mode||'separate')==='separate'"><input type="checkbox" :checked="(settings.card_show_wholesale_condition??'1')!=='0'" @change="setSetting('card_show_wholesale_condition',$event.target.checked?'1':'0')"> Mostrar condición (desde N unidades)</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.card_show_savings??'0')!=='0'" @change="setSetting('card_show_savings',$event.target.checked?'1':'0')"> Mostrar ahorro por comprar al por mayor</label>
        </div>

        <div class="bxb-field bxb-full"><span>Cantidad</span>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.card_show_quantity??'1')!=='0'" @change="setSetting('card_show_quantity',$event.target.checked?'1':'0')"> Mostrar selector de cantidad</label>
            <label class="bxb-field" x-show="(settings.card_show_quantity??'1')!=='0'" x-cloak>Estilo del selector
                <select :value="settings.card_qty_style||'horizontal'" @change="setSetting('card_qty_style',$event.target.value)">
                    <option value="horizontal">Horizontal</option>
                    <option value="compact">Compacto</option>
                </select>
            </label>
            <label class="bxb-switch" x-show="settings.purchase_mode==='auto'" x-cloak><input type="checkbox" :checked="(settings.card_show_subtotal??'0')!=='0'" @change="setSetting('card_show_subtotal',$event.target.checked?'1':'0')"> Mostrar subtotal de la línea</label>
        </div>

        <div class="bxb-field bxb-full"><span>Botón de carrito</span>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.card_show_cart??'1')!=='0'" @change="setSetting('card_show_cart',$event.target.checked?'1':'0')"> Mostrar botón agregar</label>
            <label class="bxb-field" x-show="(settings.card_show_cart??'1')!=='0'" x-cloak>Estilo del botón
                <select :value="settings.card_cart_style||'full'" @change="setSetting('card_cart_style',$event.target.value)">
                    <option value="full">Ancho completo</option>
                    <option value="compact">Compacto</option>
                    <option value="inline">Junto al selector</option>
                </select>
            </label>
        </div>

        <div class="bxb-field bxb-full"><span>WhatsApp</span>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.card_show_whatsapp??'1')!=='0'" @change="setSetting('card_show_whatsapp',$event.target.checked?'1':'0')"> Mostrar "Consultar"</label>
            <label class="bxb-field" x-show="(settings.card_show_whatsapp??'1')!=='0'" x-cloak>Estilo
                <select :value="settings.card_whatsapp_style||'outline'" @change="setSetting('card_whatsapp_style',$event.target.value)">
                    <option value="outline">Botón contorno</option>
                    <option value="solid">Botón completo</option>
                    <option value="link">Enlace simple</option>
                    <option value="icon">Solo icono</option>
                </select>
            </label>
        </div>
    </div>
</section>

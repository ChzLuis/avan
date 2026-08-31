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
        <a class="bxb-btn bxb-btn-primary" href="{{ route('products.index') }}?new=1" target="_blank" rel="noopener">+ Crear producto</a>
        <a class="bxb-btn" href="{{ route('products.index') }}" target="_blank" rel="noopener">Abrir catálogo completo ↗</a>
        <a class="bxb-btn" href="{{ route('products.template') }}">⇩ Plantilla Excel</a>
        <a class="bxb-btn" href="{{ route('products.index') }}#importar" target="_blank" rel="noopener">⇧ Importar Excel</a>
    </div>

    <div class="bxb-card">
        <strong class="bxb-card-title">Diseño y filtros del catálogo</strong>
        <p class="bxb-note">Estos controles afectan la página Tienda o Catálogo; no insertan el catálogo completo en Inicio.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Columnas en escritorio
                <select :value="settings.catalog_cols_desktop||'3'" @change="setSetting('catalog_cols_desktop',$event.target.value)">
                    <option value="2">2 columnas</option><option value="3">3 columnas</option><option value="4">4 columnas</option>
                </select>
            </label>
            <label class="bxb-field">Columnas en celular
                <select :value="settings.catalog_cols_mobile||'2'" @change="setSetting('catalog_cols_mobile',$event.target.value)">
                    <option value="1">1 columna</option><option value="2">2 columnas</option>
                </select>
            </label>
            <label class="bxb-field">Vista de productos
                <select :value="settings.catalog_products_view||'cards'" @change="setSetting('catalog_products_view',$event.target.value)">
                    <option value="cards">Tarjetas</option><option value="compact">Lista compacta</option>
                </select>
            </label>
            <label class="bxb-field">Estilo de las tarjetas
                <select :value="settings.product_card_style||'classic'" @change="setSetting('product_card_style',$event.target.value)">
                    <option value="classic">Clásico</option><option value="tech">Tecnológico</option><option value="soft">Suave</option><option value="elegant">Elegante</option><option value="contrast">Alto contraste</option>
                </select>
            </label>
            <label class="bxb-field">Título del catálogo
                <input type="text" maxlength="80" placeholder="Nuestros productos" :value="settings.catalog_section_title||''" @input.debounce.600ms="setSetting('catalog_section_title',$event.target.value)">
            </label>
            <label class="bxb-field">Proporción de la foto
                <select :value="settings.card_image_ratio||'1/1'" @change="setSetting('card_image_ratio',$event.target.value)">
                    <option value="1/1">Cuadrada</option><option value="4/3">Horizontal</option><option value="3/4">Vertical</option>
                </select>
            </label>
            <label class="bxb-field">Fondo de la foto
                <x-bxb-color clave="card_image_bg" defecto="#fafaf9" etiqueta="Fondo de la foto" />
            </label>
            <label class="bxb-field">Categorías visibles en celular
                <input type="number" min="0" max="24" step="1" placeholder="0 = todas" :value="settings.cats_mobile_limit||''" @input.debounce.600ms="setSetting('cats_mobile_limit',$event.target.value)">
            </label>
            <label class="bxb-field">Etiqueta “Nuevo” durante (días)
                <input type="number" min="0" max="365" placeholder="30" :value="settings.new_badge_days||''" @input.debounce.600ms="setSetting('new_badge_days',$event.target.value)">
            </label>
        </div>
        <div class="bxb-check-inline">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_filter_search??'1')!=='0'" @change="setSetting('catalog_filter_search',$event.target.checked?'1':'0')"> Búsqueda</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_filter_cats??'1')!=='0'" @change="setSetting('catalog_filter_cats',$event.target.checked?'1':'0')"> Categorías</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_filter_price??'1')!=='0'" @change="setSetting('catalog_filter_price',$event.target.checked?'1':'0')"> Precio</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_filter_sale??'1')!=='0'" @change="setSetting('catalog_filter_sale',$event.target.checked?'1':'0')"> En oferta</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_group_models||'')==='1'" @change="setSetting('catalog_group_models',$event.target.checked?'1':'0')"> Agrupar colores del mismo modelo</label>
        </div>
    </div>

    @include('settings.builder.partials.image-template')

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
        {{-- Etiquetas de la tarjeta. Vivian SOLO en el Designer legacy: 5 tiendas
         las tienen configuradas y su dueño no podia cambiarlas desde aqui.
         Los textos se conservan tal cual; vacio = etiqueta por defecto. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Etiquetas de la tarjeta</strong>
        <p class="bxb-note">Los distintivos que se pintan sobre la foto del producto, en el catálogo y en la portada. Déjalo vacío para usar el texto por defecto.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Producto nuevo
                <input type="text" maxlength="20" placeholder="NUEVO" :value="settings.catalog_badge_new||''" @input.debounce.600ms="setSetting('catalog_badge_new',$event.target.value)">
                <small class="bxb-note">Se muestra durante los días que fijaste en “Etiqueta Nuevo”.</small>
            </label>
            <label class="bxb-field">Producto en oferta
                <input type="text" maxlength="20" placeholder="OFERTA" :value="settings.catalog_badge_sale||''" @input.debounce.600ms="setSetting('catalog_badge_sale',$event.target.value)">
                <small class="bxb-note">Aparece cuando el producto tiene precio anterior.</small>
            </label>
            <label class="bxb-field">Producto destacado
                <input type="text" maxlength="20" placeholder="DESTACADO" :value="settings.catalog_badge_featured||''" @input.debounce.600ms="setSetting('catalog_badge_featured',$event.target.value)">
            </label>
            <label class="bxb-field">Producto agotado
                <input type="text" maxlength="20" placeholder="AGOTADO" :value="settings.catalog_badge_sold_out||''" @input.debounce.600ms="setSetting('catalog_badge_sold_out',$event.target.value)">
            </label>
        </div>
    </div>

    <div class="bxb-card">
        <strong class="bxb-card-title">Tarjeta de producto — compra y precios</strong>
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
        <div class="bxb-check-inline">
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_quick_view??'1')!=='0'" @change="setSetting('catalog_quick_view',$event.target.checked?'1':'0')"> Vista rápida</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_show_sku??'0')!=='0'" @change="setSetting('catalog_show_sku',$event.target.checked?'1':'0')"> Mostrar SKU</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_show_stock??'1')!=='0'" @change="setSetting('catalog_show_stock',$event.target.checked?'1':'0')"> Mostrar stock</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_show_ratings??'0')!=='0'" @change="setSetting('catalog_show_ratings',$event.target.checked?'1':'0')"> Mostrar valoraciones</label>
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.wholesale_card_collapse||'')==='1'" @change="setSetting('wholesale_card_collapse',$event.target.checked?'1':'0')"> Plegar precio mayorista</label>
        </div>
    </div>

    {{-- ═══ Textos de la tienda ═══
         Siete frases que el cliente le dice a SU comprador y que estaban
         escritas a mano en la plantilla. "Foto en camino" y "Precio a
         solicitud" son promesas comerciales, no etiquetas de interfaz. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Textos de la tienda</strong>
        <p class="bxb-note">Las frases que ve tu comprador en las tarjetas y en la ficha del producto. Déjalas vacías para usar las de siempre.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Producto sin fotografía
                <input type="text" maxlength="40" placeholder="Foto en camino"
                       :value="settings.card_no_photo_text||''"
                       @input.debounce.600ms="setSetting('card_no_photo_text',$event.target.value)">
                <small class="bxb-note">Se muestra sobre el hueco de la imagen.</small>
            </label>
            <label class="bxb-field">Producto sin precio publicado
                <input type="text" maxlength="40" placeholder="Precio a solicitud"
                       :value="settings.price_on_request_text||''"
                       @input.debounce.600ms="setSetting('price_on_request_text',$event.target.value)">
            </label>
            <label class="bxb-field">Etiqueta del precio al detalle
                <input type="text" maxlength="30" placeholder="Minorista"
                       :value="settings.buy_retail_label||''"
                       @input.debounce.600ms="setSetting('buy_retail_label',$event.target.value)">
            </label>
            <label class="bxb-field">Etiqueta del precio por volumen
                <input type="text" maxlength="30" placeholder="Mayorista"
                       :value="settings.buy_wholesale_label||''"
                       @input.debounce.600ms="setSetting('buy_wholesale_label',$event.target.value)">
            </label>
            <label class="bxb-field">Aclaración bajo el precio al detalle
                <input type="text" maxlength="30" placeholder="Por unidad"
                       :value="settings.buy_unit_label||''"
                       @input.debounce.600ms="setSetting('buy_unit_label',$event.target.value)">
            </label>
            <label class="bxb-field">Producto sin stock
                <input type="text" maxlength="60" placeholder="Producto agotado temporalmente"
                       :value="settings.sold_out_text||''"
                       @input.debounce.600ms="setSetting('sold_out_text',$event.target.value)">
            </label>
            <label class="bxb-field">Título de productos relacionados
                <input type="text" maxlength="60" placeholder="Productos relacionados"
                       :value="settings.related_title||''"
                       @input.debounce.600ms="setSetting('related_title',$event.target.value)">
            </label>
            @foreach([
                ['btn_cart_text', 'Botón agregar al carrito', 'Agregar'],
                ['txt_search_placeholder', 'Texto del buscador', 'Buscar productos...'],
                ['txt_no_results', 'Mensaje sin resultados', 'No se encontraron productos'],
                ['txt_view_more', 'Botón ver más', 'Ver todos los productos'],
                ['txt_all_cats', 'Todas las categorías', 'Todas las categorías'],
                ['featured_categories_all_text', 'Ver todas las categorías', 'Ver todas'],
            ] as [$key, $label, $placeholder])
            <label class="bxb-field">{{ $label }}
                <input type="text" maxlength="80" placeholder="{{ $placeholder }}" :value="settings.{{ $key }}||''" @input.debounce.600ms="setSetting('{{ $key }}',$event.target.value)">
            </label>
            @endforeach
        </div>
    </div>
</section>

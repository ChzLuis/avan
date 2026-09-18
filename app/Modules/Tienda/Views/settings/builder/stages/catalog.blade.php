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

    {{-- Cabecera de la pagina Tienda: la banda de siempre o una con foto de
         fondo y lema a la derecha (referencia: catalogo de cables de GABDE). --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Cabecera de la página Tienda</strong>
        <p class="bxb-note">Lo primero que se ve al entrar al catálogo, encima de los filtros. El título lo pone la categoría elegida.</p>
        <div class="bxb-grid2">
            <label class="bxb-field">Diseño de la cabecera
                <select :value="settings.catalog_hero_style||'simple'" @change="setSetting('catalog_hero_style',$event.target.value)">
                    <option value="simple">Banda simple (título y subtítulo)</option>
                    <option value="imagen">Con foto de fondo y lema a la derecha</option>
                </select>
            </label>
            <label class="bxb-field">Subtítulo fijo
                <input type="text" maxlength="160" placeholder="Encuentra el producto ideal para tu proyecto." :value="settings.catalog_hero_subtitle||''" @input.debounce.600ms="setSetting('catalog_hero_subtitle',$event.target.value)">
                <span class="bxb-note">Vacío: se arma solo con la categoría que se esté viendo.</span>
            </label>
            <template x-if="(settings.catalog_hero_style||'simple')==='imagen'">
                <label class="bxb-field">Lema a la derecha (separa las líneas con |)
                    <input type="text" maxlength="120" placeholder="Tu proyecto | nuestra | energía" :value="settings.catalog_hero_slogan||''" @input.debounce.600ms="setSetting('catalog_hero_slogan',$event.target.value)">
                </label>
            </template>
            <template x-if="(settings.catalog_hero_style||'simple')==='imagen'">
                <div class="bxb-field"><span>Foto de fondo (ancha, se oscurece sola para que el texto se lea)</span>
                    <div class="bxb-media"><span class="bxb-media-box" :style="settings.catalog_hero_image?'background-image:url('+assetUrl(settings.catalog_hero_image)+')':''"><template x-if="!settings.catalog_hero_image"><em>Sin foto la cabecera se pinta como banda simple</em></template></span>
                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'catalog_hero_image')"></label>
                    <button type="button" class="bxb-link" x-show="settings.catalog_hero_image" @click="setSetting('catalog_hero_image','')">Quitar</button></div>
                </div>
            </template>
        </div>
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
            <label class="bxb-field">Paginación
                <select :value="settings.catalog_pagination||'load_more'" @change="setSetting('catalog_pagination',$event.target.value)">
                    <option value="load_more">Botón "Cargar más"</option>
                    <option value="numbers">Números de página (Anterior 1 2 3 … Siguiente)</option>
                </select>
            </label>
            <label class="bxb-field">Vista de productos
                <select :value="settings.catalog_products_view||'cards'" @change="setSetting('catalog_products_view',$event.target.value)">
                    <option value="cards">Tarjetas</option><option value="compact">Lista compacta</option>
                </select>
            </label>
            <label class="bxb-field">Estilo de las tarjetas
                <select :value="settings.product_card_style||'classic'" @change="setSetting('product_card_style',$event.target.value)">
                    <option value="classic">Clásico</option><option value="tech">Tecnológico</option><option value="soft">Suave</option><option value="pro">Profesional (marca, código, unidad, disponibilidad)</option><option value="elegant">Elegante</option><option value="contrast">Alto contraste</option>
                </select>
            </label>
            <label class="bxb-field" x-show="(settings.product_card_style||'')==='pro'">Texto de disponibilidad
                <input type="text" maxlength="60" :placeholder="(settings.store_mode||'direct')==='quote' ? 'Disponible para cotizar' : 'Disponible'" :value="settings.catalog_availability_text||''" @input.debounce.600ms="setSetting('catalog_availability_text',$event.target.value)">
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
            {{-- Marca de agua: apagada por defecto. Solo se dibuja si la tienda
                 tiene logo cargado; sin logo el interruptor no hace nada. --}}
            <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_watermark||'')==='1'" @change="setSetting('catalog_watermark',$event.target.checked?'1':'0')"> Marca de agua en las fotos</label>
        </div>
        <div class="bxb-row" x-show="(settings.catalog_watermark||'')==='1'" x-cloak>
            <label class="bxb-field">Intensidad de la marca (%)
                <input type="number" min="5" max="100" step="5" placeholder="42" :value="settings.catalog_watermark_opacity||''" @input.debounce.600ms="setSetting('catalog_watermark_opacity',$event.target.value)">
            </label>
        </div>
    </div>

    {{-- Página del producto: pestañas y garantías. Se escriben una vez y
         valen para TODOS los productos de la tienda. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Página del producto</strong>
        <p class="bxb-card-note">Estos textos salen en la ficha de todos tus productos. Una pestaña sin texto no aparece.</p>

        <div class="bxb-field"><span>Diseño de la ficha</span>
            <div class="bxb-seg">
                <button type="button" :class="(settings.pdp_layout||'classic')==='classic'&&'on'" @click="setSetting('pdp_layout','classic')">Clásico (dos columnas)</button>
                <button type="button" :class="settings.pdp_layout==='b2b'&&'on'" @click="setSetting('pdp_layout','b2b')">Catálogo B2B (tres columnas)</button>
            </div>
            <small>B2B: código, marca y presentación bajo el título; tarjeta lateral con el logo de la marca y las garantías; pestañas Especificaciones y Documentos; relacionados compactos.</small>
        </div>
        <div x-show="settings.pdp_layout==='b2b'" x-cloak>
            <label class="bxb-switch"><input type="checkbox" :checked="settings.pdp_banner==='1'" @change="setSetting('pdp_banner',$event.target.checked?'1':'0')"> Banda de portada compacta arriba (usa el título, subtítulo y foto del hero y los sectores de la tarjeta de promociones)</label>
            <div class="bxb-grid2">
                <label class="bxb-field">Etiqueta de disponibilidad<input type="text" maxlength="40" placeholder="Disponible para cotizar" :value="settings.pdp_avail_text||''" @input.debounce.600ms="setSetting('pdp_avail_text',$event.target.value)"></label>
                <label class="bxb-field">Botón secundario<input type="text" maxlength="40" placeholder="Solicitar cotización ahora" :value="settings.pdp_quote_now_text||''" @input.debounce.600ms="setSetting('pdp_quote_now_text',$event.target.value)"></label>
                <label class="bxb-field">Tarjeta junto a las pestañas — título<input type="text" maxlength="80" placeholder="¿Necesitas una cotización por volumen?" :value="settings.pdp_volume_title||''" @input.debounce.600ms="setSetting('pdp_volume_title',$event.target.value)"></label>
                <label class="bxb-field">Tarjeta — texto<input type="text" maxlength="200" placeholder="Nuestro equipo te asesora con precios especiales para proyectos grandes." :value="settings.pdp_volume_text||''" @input.debounce.600ms="setSetting('pdp_volume_text',$event.target.value)"></label>
                <label class="bxb-field">Tarjeta — botón (abre WhatsApp)<input type="text" maxlength="40" placeholder="Hablar con un asesor" :value="settings.pdp_volume_cta||''" @input.debounce.600ms="setSetting('pdp_volume_cta',$event.target.value)"></label>
            </div>
            <small>Sin título, la tarjeta no aparece.</small>
        </div>

        <div class="bxb-row">
            <label class="bxb-field">Botón de ficha técnica
                <input type="text" maxlength="40" placeholder="Ficha técnica (PDF)"
                       :value="settings.pdp_ficha_text||''"
                       @input.debounce.600ms="setSetting('pdp_ficha_text',$event.target.value)">
            </label>
        </div>

        @foreach([
            ['pdp_tab_instalacion', 'Instalación'],
            ['pdp_tab_envios', 'Envíos y pagos'],
            ['pdp_tab_garantia', 'Garantía'],
        ] as [$clave, $rotulo])
        <div class="bxb-row">
            <label class="bxb-field">Pestaña «{{ $rotulo }}» — título
                <input type="text" maxlength="30" placeholder="{{ $rotulo }}"
                       :value="settings.{{ $clave }}_label||''"
                       @input.debounce.600ms="setSetting('{{ $clave }}_label',$event.target.value)">
            </label>
        </div>
        <label class="bxb-field">Pestaña «{{ $rotulo }}» — contenido
            <textarea rows="3" maxlength="2000" placeholder="Déjalo vacío para no mostrar esta pestaña"
                      :value="settings.{{ $clave }}||''"
                      @input.debounce.600ms="setSetting('{{ $clave }}',$event.target.value)"></textarea>
        </label>
        @endforeach

        <p class="bxb-card-note" style="margin-top:14px">Garantías bajo el botón de compra (hasta 3).</p>
        @foreach([1, 2, 3] as $i)
        <div class="bxb-row">
            <label class="bxb-field">Garantía {{ $i }} — título
                <input type="text" maxlength="40" placeholder="Garantía oficial"
                       :value="settings.pdp_trust_{{ $i }}_title||''"
                       @input.debounce.600ms="setSetting('pdp_trust_{{ $i }}_title',$event.target.value)">
            </label>
            <label class="bxb-field">Garantía {{ $i }} — detalle
                <input type="text" maxlength="90" placeholder="Equipos originales con respaldo del fabricante"
                       :value="settings.pdp_trust_{{ $i }}_text||''"
                       @input.debounce.600ms="setSetting('pdp_trust_{{ $i }}_text',$event.target.value)">
            </label>
        </div>
        @endforeach
    </div>

    {{-- Paginas B2B: marcas, promociones y catalogo PDF. Textos por tienda. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Marcas, promociones y catálogo PDF</strong>
        <p class="bxb-card-note">Títulos de las páginas /marcas, /promociones y /catalogo, y de la sección "Promociones del mes" de la portada.</p>
        @foreach([
            ['brands_page_title', 'Página de marcas — título', 'Nuestras marcas'],
            ['brands_page_subtitle', 'Página de marcas — subtítulo', 'Trabajamos con marcas líderes…'],
            ['promo_cards_title', 'Promociones — título', 'Promociones del mes'],
            ['promo_cards_subtitle', 'Promociones — subtítulo', 'Aprovecha nuestras campañas…'],
            ['catalog_pdf_title', 'Catálogo PDF — título', 'Descarga nuestro catálogo'],
            ['catalog_pdf_subtitle', 'Catálogo PDF — subtítulo', 'Elige qué catálogo necesitas…'],
        ] as [$clave, $rotulo, $ph])
        <label class="bxb-field">{{ $rotulo }}
            <input type="text" maxlength="120" placeholder="{{ $ph }}" :value="settings.{{ $clave }}||''" @input.debounce.600ms="setSetting('{{ $clave }}',$event.target.value)">
        </label>
        @endforeach
        <p class="bxb-card-note" style="margin-top:14px">Página Catálogo PDF: banda superior, portada del catálogo, opciones e información. Escribe «-» para ocultar una ventaja o un punto de información.</p>
        <label class="bxb-switch"><input type="checkbox" :checked="(settings.catalog_pdf_banner??'1')!=='0'" @change="setSetting('catalog_pdf_banner',$event.target.checked?'1':'0')"> Banda superior con foto (usa la foto del hero si no indicas otra)</label>
        <div class="bxb-grid2">
            <label class="bxb-field">Texto de introducción<input type="text" maxlength="220" placeholder="Descarga o genera tu catálogo en formato PDF…" :value="settings.catalog_pdf_intro||''" @input.debounce.600ms="setSetting('catalog_pdf_intro',$event.target.value)"></label>
            <label class="bxb-field">Frase corta de la banda (derecha)<input type="text" maxlength="60" placeholder="Tu proyecto, nuestra energía" :value="settings.catalog_pdf_claim||''" @input.debounce.600ms="setSetting('catalog_pdf_claim',$event.target.value)"></label>
            @foreach([1 => 'Información actualizada', 2 => 'Productos con códigos y especificaciones', 3 => 'Descarga rápida y gratuita'] as $n => $ph)
            <label class="bxb-field">Ventaja {{ $n }}<input type="text" maxlength="60" placeholder="{{ $ph }}" :value="settings.catalog_pdf_feature_{{ $n }}||''" @input.debounce.600ms="setSetting('catalog_pdf_feature_{{ $n }}',$event.target.value)"></label>
            @endforeach
            <label class="bxb-field">Imagen de la banda (ruta en uploads, opcional)<input type="text" maxlength="255" :value="settings.catalog_pdf_banner_image||''" @input.debounce.600ms="setSetting('catalog_pdf_banner_image',$event.target.value)"></label>
            <label class="bxb-field">Portada — título<input type="text" maxlength="40" placeholder="Catálogo general" :value="settings.catalog_pdf_cover_title||''" @input.debounce.600ms="setSetting('catalog_pdf_cover_title',$event.target.value)"></label>
            <label class="bxb-field">Portada — año<input type="text" maxlength="10" placeholder="{{ date('Y') }}" :value="settings.catalog_pdf_cover_year||''" @input.debounce.600ms="setSetting('catalog_pdf_cover_year',$event.target.value)"></label>
            <label class="bxb-field">Portada — lema<input type="text" maxlength="80" placeholder="Soluciones eléctricas para empresas y proyectos" :value="settings.catalog_pdf_cover_tagline||''" @input.debounce.600ms="setSetting('catalog_pdf_cover_tagline',$event.target.value)"></label>
            <label class="bxb-field">Portada — nota manuscrita<input type="text" maxlength="60" placeholder="Las mejores marcas, un solo proveedor." :value="settings.catalog_pdf_cover_note||''" @input.debounce.600ms="setSetting('catalog_pdf_cover_note',$event.target.value)"></label>
            <label class="bxb-field">Portada — imagen (ruta en uploads; vacío = fotos de categorías)<input type="text" maxlength="255" :value="settings.catalog_pdf_cover_image||''" @input.debounce.600ms="setSetting('catalog_pdf_cover_image',$event.target.value)"></label>
            <label class="bxb-field">Opciones — título<input type="text" maxlength="60" placeholder="Selecciona el tipo de catálogo" :value="settings.catalog_pdf_options_title||''" @input.debounce.600ms="setSetting('catalog_pdf_options_title',$event.target.value)"></label>
            <label class="bxb-field">Opciones — texto<input type="text" maxlength="160" placeholder="Elige la opción que mejor se adapte…" :value="settings.catalog_pdf_options_text||''" @input.debounce.600ms="setSetting('catalog_pdf_options_text',$event.target.value)"></label>
            <label class="bxb-field">Texto del botón<input type="text" maxlength="30" placeholder="Generar PDF" :value="settings.catalog_pdf_button||''" @input.debounce.600ms="setSetting('catalog_pdf_button',$event.target.value)"></label>
            <label class="bxb-field">Información — título<input type="text" maxlength="60" placeholder="Información del catálogo" :value="settings.catalog_pdf_info_title||''" @input.debounce.600ms="setSetting('catalog_pdf_info_title',$event.target.value)"></label>
            @foreach([1 => 'Productos con códigos', 2 => 'Imágenes referenciales', 3 => 'Especificaciones técnicas', 4 => 'Organizado por categorías', 5 => 'Incluye nuestras principales marcas', 6 => 'Información actualizada'] as $n => $ph)
            <label class="bxb-field">Información {{ $n }}<input type="text" maxlength="60" placeholder="{{ $ph }}" :value="settings.catalog_pdf_info_{{ $n }}||''" @input.debounce.600ms="setSetting('catalog_pdf_info_{{ $n }}',$event.target.value)"></label>
            @endforeach
            <label class="bxb-field">Frase final (entre comillas)<input type="text" maxlength="120" placeholder="Más que productos, conectamos un mejor futuro" :value="settings.catalog_pdf_quote||''" @input.debounce.600ms="setSetting('catalog_pdf_quote',$event.target.value)"></label>
        </div>
        <div class="bxb-row">
            <label class="bxb-field">Promociones en portada (cantidad)
                <input type="number" min="1" max="12" placeholder="4" :value="settings.promo_cards_limit||''" @input.debounce.600ms="setSetting('promo_cards_limit',$event.target.value)">
            </label>
        </div>
    </div>

    @include('tienda::settings.builder.partials.image-template')

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
    {{-- Textos del catalogo que vivian solo en Diseño clasico. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Textos del catálogo</strong>
        <div class="bxb-grid2">
            <label class="bxb-field">Texto del buscador
                <input type="text" maxlength="80" placeholder="Buscar productos…" :value="settings.txt_search_placeholder||''" @input.debounce.600ms="setSetting('txt_search_placeholder',$event.target.value)">
            </label>
            <label class="bxb-field">Mensaje sin resultados
                <input type="text" maxlength="120" placeholder="No se encontraron productos" :value="settings.txt_no_results||''" @input.debounce.600ms="setSetting('txt_no_results',$event.target.value)">
            </label>
            <label class="bxb-field">Texto “Ver más”
                <input type="text" maxlength="40" placeholder="Ver todos" :value="settings.txt_view_more||''" @input.debounce.600ms="setSetting('txt_view_more',$event.target.value)">
            </label>
            <label class="bxb-field">Texto “Todas las categorías”
                <input type="text" maxlength="40" placeholder="Todas" :value="settings.txt_all_cats||''" @input.debounce.600ms="setSetting('txt_all_cats',$event.target.value)">
            </label>
        </div>
    </div>

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
        @include('tienda::settings.partials.catalog-profiles')
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

    {{-- Presentacion de la tarjeta: que DATOS se ven. La accion comercial
         (comprar, consultar, precio mayorista) se configura en 05 Venta. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Qué se muestra en la tarjeta</strong>
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

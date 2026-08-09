{{-- Etapa 3: Página de inicio — UNA lista de bloques; cada bloque reúne
     contenido + diseño + variante + visibilidad + orden (borrador siempre). --}}
<section class="bxb-stage">
    <template x-if="!editingBlock">
        <div>
            <h2>Página de inicio</h2>
            <p class="bxb-stage-sub">Activa, ordena y edita los bloques de tu portada. Los cambios quedan en borrador y se ven a la derecha.</p>

            {{-- Bloques fijos: un solo lugar de edición (Apariencia) --}}
            <div class="bxb-block bxb-block--fixed">
                <span class="bxb-block-ico">▤</span>
                <span class="bxb-block-copy"><strong>Barra superior y Encabezado</strong><small>Colores, logo, buscador y menú de tu tienda.</small></span>
                <button type="button" class="bxb-btn" @click="stage='appearance'">Editar en Apariencia</button>
            </div>

            <ul class="bxb-blocks" role="list">
                <template x-for="(b, i) in blocks" :key="b.component">
                    <li class="bxb-block" :class="[!b.enabled&&'is-off', dragging===b.component&&'is-dragging']"
                        draggable="true"
                        @dragstart="dragStart($event,b)"
                        @dragover.prevent="dragOver($event,b)"
                        @drop.prevent="dragEnd()"
                        @dragend="dragEnd()">
                        <span class="bxb-block-handle" title="Arrastra para ordenar" aria-hidden="true">⋮⋮</span>
                        <span class="bxb-block-move">
                            <button type="button" @click="moveBlock(b,-1)" :disabled="i===0" :aria-label="'Subir '+b.label">▲</button>
                            <button type="button" @click="moveBlock(b,1)" :disabled="i===blocks.length-1" :aria-label="'Bajar '+b.label">▼</button>
                        </span>
                        <span class="bxb-block-copy">
                            <strong x-text="b.label"></strong>
                            <small>
                                <span x-show="b.has_draft" class="bxb-draft-tag">borrador</span>
                                <button type="button" class="bxb-mini" :class="b.show_desktop&&'on'" @click="toggleBlockDevice(b,'desktop')" :aria-pressed="b.show_desktop.toString()" title="Mostrar en PC">PC</button>
                                <button type="button" class="bxb-mini" :class="b.show_mobile&&'on'" @click="toggleBlockDevice(b,'mobile')" :aria-pressed="b.show_mobile.toString()" title="Mostrar en móvil">Móvil</button>
                            </small>
                        </span>
                        <button type="button" class="bxb-btn" @click="openBlock(b)">Editar</button>
                        <button type="button" class="bxb-toggle" :class="b.enabled&&'on'" role="switch" :aria-checked="b.enabled.toString()" :aria-label="'Mostrar u ocultar '+b.label" @click="toggleBlock(b)"></button>
                    </li>
                </template>
            </ul>

            <div class="bxb-block bxb-block--fixed">
                <span class="bxb-block-ico">▦</span>
                <span class="bxb-block-copy"><strong>Pie de página</strong><small>Derechos, enlaces legales y métodos de pago.</small></span>
                <button type="button" class="bxb-btn" @click="stage='appearance'">Editar en Apariencia</button>
            </div>

            <div class="bxb-actions-row">
                <button type="button" class="bxb-btn" @click="applyRecommendedStructure()">✦ Aplicar estructura recomendada</button>
            </div>
        </div>
    </template>

    {{-- Editor del bloque seleccionado --}}
    <template x-if="editingBlock">
        <div>
            <button type="button" class="bxb-link" @click="closeBlock()">← Volver a la lista</button>
            <h2 x-text="editingBlock.label"></h2>
            <p class="bxb-stage-sub">Contenido y diseño del bloque en un solo lugar. Todo va al borrador.</p>

            {{-- Animación de entrada del bloque (aplica a TODOS los bloques) --}}
            <div class="bxb-card">
                <strong class="bxb-card-title">Animación de entrada</strong>
                <div class="bxb-grid2">
                    <label class="bxb-field">Efecto al aparecer
                        <select :value="settings['anim_'+editingBlock.component+'_type']||''" @change="setSetting('anim_'+editingBlock.component+'_type',$event.target.value)">
                            <option value="">Según el tema (automático)</option>
                            <option value="none">Sin animación</option>
                            <option value="fade">Aparecer suave</option>
                            <option value="up">Subir</option>
                            <option value="down">Bajar</option>
                            <option value="left">Desde la izquierda</option>
                            <option value="right">Desde la derecha</option>
                            <option value="zoom">Zoom suave</option>
                        </select>
                    </label>
                    <div class="bxb-grid2" x-show="(settings['anim_'+editingBlock.component+'_type']||'')!=='' && settings['anim_'+editingBlock.component+'_type']!=='none'" x-cloak>
                        <label class="bxb-field">Duración (s)
                            <input type="number" step="0.1" min="0.2" max="3" :value="settings['anim_'+editingBlock.component+'_duration']||0.6" @change="setSetting('anim_'+editingBlock.component+'_duration',$event.target.value)">
                        </label>
                        <label class="bxb-field">Retraso (s)
                            <input type="number" step="0.1" min="0" max="2" :value="settings['anim_'+editingBlock.component+'_delay']||0" @change="setSetting('anim_'+editingBlock.component+'_delay',$event.target.value)">
                        </label>
                    </div>
                </div>
                <p class="bxb-note" style="margin:6px 0 0">Elegante y rápido por diseño. Se desactiva solo para visitantes con "reducir movimiento" activado.</p>
            </div>

            {{-- Encabezado opcional ANTES del bloque (texto o imagen, 4 estilos) --}}
            <div class="bxb-card" x-show="['promotions','categories','benefits','flash_sale','discount_products','featured_products'].includes(editingBlock.native)">
                <strong class="bxb-card-title">Encabezado antes de la sección (opcional)</strong>
                <p class="bxb-note" style="margin:0">Al activarlo, este encabezado <strong>reemplaza</strong> el título propio de la sección (un solo título).</p>
                <div class="bxb-field"><span>Estilo</span>
                    <select :value="settings['intro_'+editingBlock.native+'_style']||'none'" @change="setSetting('intro_'+editingBlock.native+'_style',$event.target.value)">
                        <option value="none">Sin encabezado</option>
                        <option value="simple">Texto simple centrado</option>
                        <option value="dot">Título con punto de color</option>
                        <option value="band">Banda de color con icono</option>
                        <option value="strip">Franja discreta</option>
                    </select>
                </div>
                <div x-show="(settings['intro_'+editingBlock.native+'_style']||'none')!=='none'" x-cloak>
                    <div class="bxb-grid2">
                        <label class="bxb-field">Título
                            <input type="text" maxlength="120" placeholder="Encuentra lo que buscas" :value="settings['intro_'+editingBlock.native+'_title']||''" @input.debounce.600ms="setSetting('intro_'+editingBlock.native+'_title',$event.target.value)">
                        </label>
                        <label class="bxb-field">Subtítulo (opcional)
                            <input type="text" maxlength="160" placeholder="¡Todo lo que necesitas para tu rutina!" :value="settings['intro_'+editingBlock.native+'_subtitle']||''" @input.debounce.600ms="setSetting('intro_'+editingBlock.native+'_subtitle',$event.target.value)">
                        </label>
                    </div>
                    <div class="bxb-grid2" x-show="['band','strip'].includes(settings['intro_'+editingBlock.native+'_style'])">
                        <label class="bxb-field">Fondo
                            <span class="bxb-color-row"><input type="color" :value="settings['intro_'+editingBlock.native+'_bg']||'#f8c821'" @input="setSetting('intro_'+editingBlock.native+'_bg',$event.target.value)"><code x-text="settings['intro_'+editingBlock.native+'_bg']||''"></code></span>
                        </label>
                        <label class="bxb-field">Letra
                            <span class="bxb-color-row"><input type="color" :value="settings['intro_'+editingBlock.native+'_ink']||'#111827'" @input="setSetting('intro_'+editingBlock.native+'_ink',$event.target.value)"><code x-text="settings['intro_'+editingBlock.native+'_ink']||''"></code></span>
                        </label>
                    </div>
                    <div class="bxb-field"><span>Imagen (opcional, va encima del texto)</span>
                        <div class="bxb-media" :class="isDragging ? 'bxb-media--dragging' : ''"
                             x-data="imageDropzone(files => uploadMedia(files[0], 'intro_'+editingBlock.native+'_image'))"
                             @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                             @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                            <span class="bxb-media-box" :style="settings['intro_'+editingBlock.native+'_image'] ? 'background-image:url('+assetUrl(settings['intro_'+editingBlock.native+'_image'])+')' : ''"><template x-if="!settings['intro_'+editingBlock.native+'_image']"><em x-text="isDragging ? 'Suelta aquí' : 'Opcional'"></em></template></span>
                            <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'intro_'+editingBlock.native+'_image')"></label>
                            <button type="button" class="bxb-link" x-show="settings['intro_'+editingBlock.native+'_image']" @click="setSetting('intro_'+editingBlock.native+'_image','')">Quitar</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Slider principal --}}
            <template x-if="editingBlock.component==='hero'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-field"><span>Formato del slider</span>
                            <div class="bxb-seg">
                                <button type="button" :class="(settings.hero_show_content||'1')!=='0'&&'on'" @click="setSetting('hero_show_content','1')">Texto y botones</button>
                                <button type="button" :class="settings.hero_show_content==='0'&&'on'" @click="setSetting('hero_show_content','0')">Solo carrusel de imágenes</button>
                            </div>
                            <small>"Solo carrusel" oculta título, subtítulo y botones en todas las imágenes — ideal si tus fotos ya llevan el texto.</small>
                        </div>
                    </div>
                    @foreach([1,2,3,4,5] as $n)
                    @php($heroKey = $n === 1 ? 'hero_image' : 'hero_image_'.$n)
                    <div class="bxb-card">
                        <strong class="bxb-card-title">Imagen {{ $n }}</strong>
                        <div class="bxb-grid2">
                            <div class="bxb-field"><span>Foto para PC</span>
                                <div class="bxb-media" :class="isDragging ? 'bxb-media--dragging' : ''"
                                     x-data="imageDropzone(files => uploadMedia(files[0], '{{ $heroKey }}'))"
                                     @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                                     @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                                    <span class="bxb-media-box" :style="settings['{{ $heroKey }}'] ? 'background-image:url('+assetUrl(settings['{{ $heroKey }}'])+')' : ''"><template x-if="!settings['{{ $heroKey }}']"><em x-text="isDragging ? 'Suelta aquí' : '1920×700'"></em></template></span>
                                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'{{ $heroKey }}')"></label>
                                </div>
                            </div>
                            <div class="bxb-field"><span>Foto para móvil (opcional)</span>
                                <div class="bxb-media" :class="isDragging ? 'bxb-media--dragging' : ''"
                                     x-data="imageDropzone(files => uploadMedia(files[0], 'hero_mobile_image_{{ $n }}'))"
                                     @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                                     @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                                    <span class="bxb-media-box bxb-media-box--sq" :style="settings.hero_mobile_image_{{ $n }} ? 'background-image:url('+assetUrl(settings.hero_mobile_image_{{ $n }})+')' : ''"><template x-if="!settings.hero_mobile_image_{{ $n }}"><em x-text="isDragging ? 'Suelta aquí' : '800×1000'"></em></template></span>
                                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'hero_mobile_image_{{ $n }}')"></label>
                                </div>
                            </div>
                        </div>
                        <label class="bxb-switch" x-show="(settings.hero_show_content||'1')!=='0'">
                            <input type="checkbox" :checked="settings.hero_slide_{{ $n }}_show_content!=='0'" @change="setSetting('hero_slide_{{ $n }}_show_content',$event.target.checked?'1':'0')">
                            Mostrar título, subtítulo y botón en esta imagen
                        </label>
                        <div class="bxb-grid2" x-show="(settings.hero_show_content||'1')!=='0' && settings.hero_slide_{{ $n }}_show_content!=='0'">
                            <label class="bxb-field">Etiqueta pequeña (encima del título)
                                <input type="text" maxlength="80" placeholder="DISEÑO, CONFORT Y CALIDAD" :value="settings['{{ $n === 1 ? 'hero_badge' : 'hero_badge_'.$n }}']||''" @input.debounce.600ms="setSetting('{{ $n === 1 ? 'hero_badge' : 'hero_badge_'.$n }}',$event.target.value)">
                            </label>
                            <label class="bxb-field">Título
                                <input type="text" maxlength="160" :value="settings['{{ $n === 1 ? 'hero_title' : 'hero_title_'.$n }}']||''" @input.debounce.600ms="setSetting('{{ $n === 1 ? 'hero_title' : 'hero_title_'.$n }}',$event.target.value)">
                            </label>
                            <label class="bxb-field">Subtítulo
                                <input type="text" maxlength="200" :value="settings['{{ $n === 1 ? 'hero_subtitle' : 'hero_subtitle_'.$n }}']||''" @input.debounce.600ms="setSetting('{{ $n === 1 ? 'hero_subtitle' : 'hero_subtitle_'.$n }}',$event.target.value)">
                            </label>
                            <div class="bxb-field"><span>Alineación del contenido</span>
                                <div class="bxb-seg">
                                    <button type="button" :class="(settings.hero_slide_{{ $n }}_align||'left')==='left'&&'on'" @click="setSetting('hero_slide_{{ $n }}_align','left')">Izquierda</button>
                                    <button type="button" :class="settings.hero_slide_{{ $n }}_align==='center'&&'on'" @click="setSetting('hero_slide_{{ $n }}_align','center')">Centro</button>
                                    <button type="button" :class="settings.hero_slide_{{ $n }}_align==='right'&&'on'" @click="setSetting('hero_slide_{{ $n }}_align','right')">Derecha</button>
                                </div>
                            </div>
                            <label class="bxb-field">Oscurecido de la imagen (%)
                                <input type="number" min="0" max="90" placeholder="45" :value="settings.hero_slide_{{ $n }}_overlay||''" @input.debounce.600ms="setSetting('hero_slide_{{ $n }}_overlay',$event.target.value)">
                            </label>
                            <label class="bxb-field">Texto del botón
                                <input type="text" maxlength="60" :value="settings.hero_slide_{{ $n }}_cta1_text||''" @input.debounce.600ms="setSetting('hero_slide_{{ $n }}_cta1_text',$event.target.value)">
                            </label>
                            <label class="bxb-field">Enlace del botón
                                <input type="text" maxlength="300" placeholder="/tienda" :value="settings.hero_slide_{{ $n }}_cta1_url||''" @input.debounce.600ms="setSetting('hero_slide_{{ $n }}_cta1_url',$event.target.value)">
                            </label>
                            <label class="bxb-field">Texto del botón secundario
                                <input type="text" maxlength="60" placeholder="Cotizar ahora" :value="settings.hero_slide_{{ $n }}_cta2_text||''" @input.debounce.600ms="setSetting('hero_slide_{{ $n }}_cta2_text',$event.target.value)">
                            </label>
                            <label class="bxb-field">Enlace del botón secundario
                                <input type="text" maxlength="300" placeholder="https://wa.me/…" :value="settings.hero_slide_{{ $n }}_cta2_url||''" @input.debounce.600ms="setSetting('hero_slide_{{ $n }}_cta2_url',$event.target.value)">
                            </label>
                        </div>
                        @if($n > 1)
                        <label class="bxb-switch"><input type="checkbox" :checked="settings.hero_slide_{{ $n }}_enabled==='1'" @change="setSetting('hero_slide_{{ $n }}_enabled',$event.target.checked?'1':'0')"> Usar esta imagen en el slider</label>
                        @endif
                    </div>
                    @endforeach
                </div>
            </template>

            {{-- Beneficios --}}
            <template x-if="editingBlock.component==='benefits'">
                <div class="bxb-card">
                    <label class="bxb-field">Título de la sección
                        <input type="text" maxlength="120" :value="settings.trust_section_title||''" @input.debounce.600ms="setSetting('trust_section_title',$event.target.value)">
                    </label>
                    <div class="bxb-field"><span>Diseño</span>
                        <select :value="settings.trust_section_style||'cards'" @change="setSetting('trust_section_style',$event.target.value)">
                            <option value="cards">Tarjetas clásicas</option>
                            <option value="compact">Compacto</option>
                            <option value="icons-top">Iconos arriba</option>
                            <option value="tiles">Iconos de color (llamativo)</option>
                            <option value="band">Banda de color completa</option>
                            <option value="outline">Bordes de color</option>
                            <option value="stripe">Franja lateral</option>
                            <option value="inline">Línea minimal</option>
                        </select>
                    </div>
                    @foreach(range(1,6) as $i)
                    <div class="bxb-card" @if($i > 4) x-show="(settings.trust_text_{{ $i - 1 }}||'')!==''||(settings.trust_text_{{ $i }}||'')!==''" @endif>
                        <strong class="bxb-card-title">Beneficio {{ $i }} @if($i > 4)<small>(opcional)</small>@endif</strong>
                        <div class="bxb-grid2">
                            <label class="bxb-field">Ícono
                                <select :value="settings.trust_icon_{{ $i }}||''" @change="setSetting('trust_icon_{{ $i }}',$event.target.value)">
                                    <option value="">Automático (según el texto)</option>
                                    @foreach(['truck' => 'Camión / envío', 'store' => 'Tienda', 'clock' => 'Reloj / express', 'shield' => 'Escudo / seguridad', 'warranty' => 'Sello de garantía', 'support' => 'Soporte / audífonos', 'card' => 'Tarjeta / pago', 'check' => 'Check', 'sparkles' => 'Destellos', 'gift' => 'Regalo', 'box' => 'Caja / stock', 'percent' => 'Descuento %', 'pin' => 'Ubicación'] as $iv => $il)
                                    <option value="{{ $iv }}">{{ $il }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="bxb-field">Título
                                <input type="text" maxlength="120" :value="settings.trust_text_{{ $i }}||''" @input.debounce.600ms="setSetting('trust_text_{{ $i }}',$event.target.value)">
                            </label>
                            <label class="bxb-field">Descripción corta
                                <input type="text" maxlength="160" :value="settings.trust_description_{{ $i }}||''" @input.debounce.600ms="setSetting('trust_description_{{ $i }}',$event.target.value)">
                            </label>
                            <label class="bxb-field">Enlace (opcional)
                                <input type="text" maxlength="300" placeholder="/tienda o https://…" :value="settings.trust_url_{{ $i }}||''" @input.debounce.600ms="setSetting('trust_url_{{ $i }}',$event.target.value)">
                            </label>
                        </div>
                        <label class="bxb-switch"><input type="checkbox" :checked="(settings.trust_item_{{ $i }}_enabled??'1')!=='0'" @change="setSetting('trust_item_{{ $i }}_enabled',$event.target.checked?'1':'0')"> Mostrar este beneficio</label>
                    </div>
                    @endforeach
                </div>
            </template>

            {{-- Anuncios --}}
            <template x-if="editingBlock.component==='announcements'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-field"><span>Formato</span>
                            <div class="bxb-seg">
                                <button type="button" :class="(settings.promo_style||'slider')==='slider'&&'on'" @click="setSetting('promo_style','slider')">Slider grande</button>
                                <button type="button" :class="settings.promo_style==='grid'&&'on'" @click="setSetting('promo_style','grid')">Cuadrícula</button>
                            </div>
                        </div>
                    </div>
                    @foreach([1,2,3] as $n)
                    <div class="bxb-card">
                        <strong class="bxb-card-title">Anuncio {{ $n }}</strong>
                        <div class="bxb-media" :class="isDragging ? 'bxb-media--dragging' : ''"
                             x-data="imageDropzone(files => uploadMedia(files[0], 'promo_image_{{ $n }}'))"
                             @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                             @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                            <span class="bxb-media-box" :style="settings.promo_image_{{ $n }} ? 'background-image:url('+assetUrl(settings.promo_image_{{ $n }})+')' : ''"><template x-if="!settings.promo_image_{{ $n }}"><em x-text="isDragging ? 'Suelta aquí' : 'Imagen PC'"></em></template></span>
                            <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'promo_image_{{ $n }}')"></label>
                        </div>
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título<input type="text" maxlength="120" :value="settings.promo_title_{{ $n }}||''" @input.debounce.600ms="setSetting('promo_title_{{ $n }}',$event.target.value)"></label>
                            <label class="bxb-field">Subtítulo<input type="text" maxlength="160" :value="settings.promo_subtitle_{{ $n }}||''" @input.debounce.600ms="setSetting('promo_subtitle_{{ $n }}',$event.target.value)"></label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </template>

            {{-- Categorías --}}
            <template x-if="editingBlock.component==='featured_categories'">
                <div class="bxb-card">
                    <label class="bxb-field">Título
                        <input type="text" maxlength="120" :value="settings.featured_categories_title||''" @input.debounce.600ms="setSetting('featured_categories_title',$event.target.value)">
                    </label>
                    <div class="bxb-field"><span>Diseño</span>
                        <select :value="settings.featured_categories_style||'image-top'" @change="setSetting('featured_categories_style',$event.target.value)">
                            <option value="image-top">Tarjetas con foto</option>
                            <option value="overlay">Foto de fondo</option>
                            <option value="minimal">Minimal</option>
                            <option value="horizontal">Horizontal</option>
                            <option value="showcase">Círculos con banda de título</option>
                            <option value="circles">Círculos limpios</option>
                            <option value="carousel">Carrusel de círculos con flechas</option>
                            <option value="editorial">Tarjetas fotográficas altas</option>
                            <option value="ambientes">Ambientes (cards altas, funciona sin fotos)</option>
                            <option value="coleccion">Colecciones visuales (foto arriba, datos abajo)</option>
                        </select>
                    </div>
                    <div class="bxb-grid2" x-show="settings.featured_categories_style==='showcase'">
                        <label class="bxb-field">Fondo de la banda<span class="bxb-color-row"><input type="color" :value="settings.featured_categories_band_bg||'#2563eb'" @input="setSetting('featured_categories_band_bg',$event.target.value)"><code x-text="settings.featured_categories_band_bg||''"></code></span></label>
                        <label class="bxb-field">Letra de la banda<span class="bxb-color-row"><input type="color" :value="settings.featured_categories_band_text||'#ffffff'" @input="setSetting('featured_categories_band_text',$event.target.value)"><code x-text="settings.featured_categories_band_text||''"></code></span></label>
                    </div>
                    <div class="bxb-field"><span>Iconos de categorías — biblioteca Iconify (se usan cuando la categoría no tiene foto)</span>
                        <ul class="bxb-iconlist" role="list">
                            <template x-for="c in storeCategories" :key="c.id">
                                <li>
                                    <span class="bxb-iconlist-svg" x-html="catIcons[c.id]||'<span class=\'bxb-iconlist-empty\'>—</span>'"></span>
                                    <span class="bxb-iconlist-name" x-text="c.name"></span>
                                    <button type="button" class="bxb-btn" @click="openIconPicker(c)">Elegir icono</button>
                                </li>
                            </template>
                        </ul>
                        <small>El icono elegido se guarda dentro de tu tienda: no depende de servicios externos para mostrarse.</small>
                    </div>
                    <div class="bxb-field"><span>Qué categorías se muestran (ninguna marcada = todas)</span>
                        <div class="bxb-check-inline">
                            <template x-for="c in storeCategories" :key="'fc'+c.id">
                                <label class="bxb-switch"><input type="checkbox" :checked="inContent('category_ids',c.id)" @change="toggleContentId('category_ids',c.id)"> <span x-text="c.name"></span></label>
                            </template>
                        </div>
                    </div>
                    <label class="bxb-field">Cuántas mostrar
                        <input type="number" min="1" max="12" :value="blockContent.limit||8" @change="setBlockContent('limit',Math.max(1,Math.min(12,parseInt($event.target.value)||8)))">
                    </label>
                    <div class="bxb-field"><span>Foto de cada categoría (la foto manda; sin foto se usa el icono)</span>
                        {{-- Separadas: primero las que la portada muestra como bloque
                             principal, luego el resto (que solo salen dentro del menu
                             y del catalogo). Asi se sabe a cuales poner foto primero. --}}
                        <p class="bxb-note" style="margin:2px 0 6px"><b x-text="storeCategories.filter(c=>c.root).length"></b> categorías se muestran en la portada. Empieza por estas.</p>
                        <ul class="bxb-iconlist" role="list">
                            <template x-for="c in storeCategories.filter(c=>c.root)" :key="'cph'+c.id">
                                <li :class="isDragging ? 'bxb-media--dragging' : ''"
                                    x-data="imageDropzone(files => uploadCategoryPhoto(c, files[0]))"
                                    @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                                    @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                                    <span class="bxb-cat-photo" :style="c.image?'background-image:url('+c.image+')':''"><span x-show="!c.image" x-text="isDragging ? '↓' : '—'"></span></span>
                                    <span class="bxb-iconlist-name" x-text="c.name"></span>
                                    <label class="bxb-btn">Subir foto<input type="file" accept="image/*" style="display:none" @change="uploadCategoryPhoto(c,$event.target.files[0]);$event.target.value=''"></label>
                                    <button type="button" class="bxb-link" x-show="c.image" @click="removeCategoryPhoto(c)">Quitar</button>
                                </li>
                            </template>
                        </ul>
                        <details class="bxb-details" x-show="storeCategories.some(c=>!c.root)" style="margin-top:10px">
                            <summary style="cursor:pointer;font-size:12.5px;font-weight:700;color:#475569">Otras categorías (<span x-text="storeCategories.filter(c=>!c.root).length"></span>) — no aparecen en la portada</summary>
                            <ul class="bxb-iconlist" role="list" style="margin-top:8px">
                                <template x-for="c in storeCategories.filter(c=>!c.root)" :key="'cphs'+c.id">
                                    <li :class="isDragging ? 'bxb-media--dragging' : ''"
                                        x-data="imageDropzone(files => uploadCategoryPhoto(c, files[0]))"
                                        @dragenter.prevent="onDragEnter($event)" @dragover.prevent
                                        @dragleave.prevent="onDragLeave()" @drop.prevent="onDrop($event)">
                                        <span class="bxb-cat-photo" :style="c.image?'background-image:url('+c.image+')':''"><span x-show="!c.image" x-text="isDragging ? '↓' : '—'"></span></span>
                                        <span class="bxb-iconlist-name" x-text="c.name"></span>
                                        <label class="bxb-btn">Subir foto<input type="file" accept="image/*" style="display:none" @change="uploadCategoryPhoto(c,$event.target.files[0]);$event.target.value=''"></label>
                                        <button type="button" class="bxb-link" x-show="c.image" @click="removeCategoryPhoto(c)">Quitar</button>
                                    </li>
                                </template>
                            </ul>
                        </details>
                        <small>Las fotos de categoría se aplican de inmediato (son parte del catálogo, no del borrador).</small>
                    </div>
                </div>
            </template>

            {{-- Oferta con contador --}}
            <template x-if="editingBlock.component==='daily_offer'">
                <div class="bxb-card">
                    <label class="bxb-field">Título
                        <input type="text" maxlength="120" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)">
                    </label>
                    <label class="bxb-field">Termina el
                        <input type="datetime-local" :value="blockContent.ends_at||''" @change="setBlockContent('ends_at',$event.target.value)">
                    </label>
                    <div class="bxb-field"><span>Diseño del contador</span>
                        <div class="bxb-seg">
                            <button type="button" :class="(settings.flash_sale_style||'full')==='full'&&'on'" @click="setSetting('flash_sale_style','full')">Bloque completo</button>
                            <button type="button" :class="settings.flash_sale_style==='band'&&'on'" @click="setSetting('flash_sale_style','band')">Banda compacta</button>
                        </div>
                    </div>
                    <label class="bxb-field" x-show="settings.flash_sale_style==='band'">Color de las cajas
                        <span class="bxb-color-row"><input type="color" :value="settings.flash_sale_accent||'#facc15'" @input="setSetting('flash_sale_accent',$event.target.value)"><code x-text="settings.flash_sale_accent||''"></code></span>
                    </label>
                    <label class="bxb-field">Color de fondo del bloque
                        <span class="bxb-color-row"><input type="color" :value="blockContent.background_color||'#0f172a'" @input="setBlockContent('background_color',$event.target.value)"><code x-text="blockContent.background_color||'auto'"></code></span>
                    </label>
                    <label class="bxb-field" x-show="!(blockContent.product_ids||[]).length">Cuántos productos (solo en automático)
                        <input type="number" min="1" max="8" :value="blockContent.count||4" @change="setBlockContent('count',Math.max(1,Math.min(8,parseInt($event.target.value)||4)))">
                    </label>
                    <div class="bxb-field"><span>Productos de la oferta (ninguno marcado = automático)</span>
    <div x-data="{ q:'' }">
                        <div class="bxb-picksearch">
                            <input type="search" placeholder="Buscar producto por nombre…" x-model="q" aria-label="Buscar producto">
                            <span class="bxb-pickcount" x-show="(blockContent.product_ids||[]).length">
                                <b x-text="(blockContent.product_ids||[]).length"></b> elegidos
                                <button type="button" @click="setBlockContent('product_ids',[])">Quitar</button>
                            </span>
                        </div>
                        <ul class="bxb-picklist" role="list">
                            <template x-for="pp in saleProducts.filter(x=>!q||x.name.toLowerCase().includes(q.toLowerCase()))" :key="'so'+pp.id">
                                <li><label class="bxb-switch"><input type="checkbox" :checked="inContent('product_ids',pp.id)" @change="toggleContentId('product_ids',pp.id)"> <span x-text="pp.name"></span></label><small x-text="'S/ '+pp.price+' · antes S/ '+pp.compare"></small></li>
                            </template>
                        </ul>
                        <p class="bxb-note" x-show="q && !saleProducts.filter(x=>x.name.toLowerCase().includes(q.toLowerCase())).length">Ningún producto coincide con «<span x-text="q"></span>».</p>
                    </div>
                        <p class="bxb-note" x-show="!saleProducts.length">Aquí solo entran productos con descuento: ponles "precio antes" mayor al precio actual en el catálogo.</p>
                    </div>
                </div>
            </template>

            {{-- Destacados / Descuentos / Blog --}}
            <template x-if="['featured_products','discounts','blog'].includes(editingBlock.component)">
                <div class="bxb-card">
                    <template x-if="editingBlock.component==='featured_products'">
                        <div class="bxb-field"><span>Vista</span>
                            <div class="bxb-seg">
                                <button type="button" :class="(settings.featured_products_view||'cards')==='cards'&&'on'" @click="setSetting('featured_products_view','cards')">Tarjetas</button>
                                <button type="button" :class="settings.featured_products_view==='editorial'&&'on'" @click="setSetting('featured_products_view','editorial')">Editorial</button>
                            </div>
                        </div>
                    </template>
                    <label class="bxb-field" x-show="editingBlock.component!=='blog'">Cuántos mostrar
                        <input type="number" min="1" max="24" :value="blockContent.limit||8" @change="setBlockContent('limit',Math.max(1,Math.min(24,parseInt($event.target.value)||8)))">
                    </label>
                    <template x-if="editingBlock.component==='discounts'">
                        <div class="bxb-field"><span>Productos con descuento a mostrar (ninguno = automático)</span>
<div x-data="{ q:'' }">
                            <div class="bxb-picksearch">
                                <input type="search" placeholder="Buscar producto por nombre…" x-model="q" aria-label="Buscar producto">
                                <span class="bxb-pickcount" x-show="(blockContent.product_ids||[]).length">
                                    <b x-text="(blockContent.product_ids||[]).length"></b> elegidos
                                    <button type="button" @click="setBlockContent('product_ids',[])">Quitar</button>
                                </span>
                            </div>
                            <ul class="bxb-picklist" role="list">
                                <template x-for="pp in saleProducts.filter(x=>!q||x.name.toLowerCase().includes(q.toLowerCase()))" :key="'dp'+pp.id">
                                    <li><label class="bxb-switch"><input type="checkbox" :checked="inContent('product_ids',pp.id)" @change="toggleContentId('product_ids',pp.id)"> <span x-text="pp.name"></span></label><small x-text="'S/ '+pp.price+' · antes S/ '+pp.compare"></small></li>
                                </template>
                            </ul>
                            <p class="bxb-note" x-show="q && !saleProducts.filter(x=>x.name.toLowerCase().includes(q.toLowerCase())).length">Ningún producto coincide con «<span x-text="q"></span>».</p>
                        </div>
                            <p class="bxb-note" x-show="!saleProducts.length">No hay productos con descuento todavía.</p>
                        </div>
                    </template>
                    <template x-if="editingBlock.component==='featured_products'">
                        <div>
                        <label class="bxb-field">Título de la sección
                            <input type="text" maxlength="120" placeholder="Productos destacados" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)">
                        </label>
                        <label class="bxb-field">Etiqueta superior (badge)
                            <input type="text" maxlength="80" placeholder="Lo mejor en tecnología" :value="blockContent.badge||''" @input.debounce.600ms="setBlockContent('badge',$event.target.value)">
                        </label>
                        <label class="bxb-field">Descripción
                            <input type="text" maxlength="300" placeholder="Descubre nuestra selección…" :value="blockContent.description||''" @input.debounce.600ms="setBlockContent('description',$event.target.value)">
                        </label>
                        <div class="bxb-field"><span>Qué productos mostrar</span>
                            <select :value="blockContent.selection||'automatic'" @change="setBlockContent('selection',$event.target.value)">
                                <option value="automatic">Automático (destacados de la tienda)</option>
                                <option value="newest">Los más nuevos</option>
                                <option value="discounted">En oferta (con descuento)</option>
                                <option value="category">De una categoría</option>
                                <option value="manual">Elegir uno por uno</option>
                            </select>
                        </div>
                        <label class="bxb-field" x-show="blockContent.selection==='category'">Categoría
                            <select :value="blockContent.category_id||''" @change="setBlockContent('category_id',$event.target.value)">
                                <option value="">— Elige una categoría —</option>
                                <template x-for="c in storeCategories" :key="'pfc'+c.id">
                                    <option :value="c.id" x-text="c.name" :selected="String(blockContent.category_id||'')===String(c.id)"></option>
                                </template>
                            </select>
                        </label>
                        <div class="bxb-field" x-show="(blockContent.selection||'automatic')==='manual'"><span>Elige los productos</span>
<div x-data="{ q:'' }">
                            <div class="bxb-picksearch">
                                <input type="search" placeholder="Buscar producto por nombre…" x-model="q" aria-label="Buscar producto">
                                <span class="bxb-pickcount" x-show="(blockContent.product_ids||[]).length">
                                    <b x-text="(blockContent.product_ids||[]).length"></b> elegidos
                                    <button type="button" @click="setBlockContent('product_ids',[])">Quitar</button>
                                </span>
                            </div>
                            <ul class="bxb-picklist" role="list">
                                <template x-for="pp in allProductsLite.filter(x=>!q||x.name.toLowerCase().includes(q.toLowerCase()))" :key="'fp'+pp.id">
                                    <li><label class="bxb-switch"><input type="checkbox" :checked="inContent('product_ids',pp.id)" @change="toggleContentId('product_ids',pp.id)"> <span x-text="pp.name"></span></label><small x-text="'S/ '+pp.price"></small></li>
                                </template>
                            </ul>
                            <p class="bxb-note" x-show="q && !allProductsLite.filter(x=>x.name.toLowerCase().includes(q.toLowerCase())).length">Ningún producto coincide con «<span x-text="q"></span>».</p>
                        </div>
                        </div>
                        </div>
                    </template>
                    <p class="bxb-note" x-show="editingBlock.component==='blog'">Los artículos se escriben en el módulo Blog; aquí solo activas u ordenas la sección.</p>
                </div>
            </template>

            {{-- Banner multimedia (imagen o video) --}}
            <template x-if="editingBlock.component==='media_banner'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-field"><span>Tipo de contenido</span>
                            <div class="bxb-seg">
                                <button type="button" :class="(blockContent.media_type||'image')==='image'&&'on'" @click="setBlockContent('media_type','image')">Imagen</button>
                                <button type="button" :class="blockContent.media_type==='video_url'&&'on'" @click="setBlockContent('media_type','video_url')">Video por enlace</button>
                                <button type="button" :class="blockContent.media_type==='video_file'&&'on'" @click="setBlockContent('media_type','video_file')">Video subido</button>
                            </div>
                        </div>
                        <div x-show="(blockContent.media_type||'image')==='image'" class="bxb-grid2">
                            <div class="bxb-field"><span>Imagen para PC</span>
                                <div class="bxb-media"><span class="bxb-media-box" :style="blockContent.desktop_image?'background-image:url('+assetUrl(blockContent.desktop_image)+')':''"><template x-if="!blockContent.desktop_image"><em>1920×700</em></template></span>
                                <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,null,null,'desktop_image')"></label></div>
                            </div>
                            <div class="bxb-field"><span>Imagen para móvil (opcional)</span>
                                <div class="bxb-media"><span class="bxb-media-box bxb-media-box--sq" :style="blockContent.mobile_image?'background-image:url('+assetUrl(blockContent.mobile_image)+')':''"><template x-if="!blockContent.mobile_image"><em>800×1000</em></template></span>
                                <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,null,null,'mobile_image')"></label></div>
                            </div>
                        </div>
                        <label class="bxb-field" x-show="blockContent.media_type==='video_url'">Enlace del video (YouTube, Vimeo)
                            <input type="text" maxlength="500" placeholder="https://www.youtube.com/watch?v=…" :value="blockContent.video_url||''" @input.debounce.600ms="setBlockContent('video_url',$event.target.value)">
                        </label>
                        <div class="bxb-field" x-show="blockContent.media_type==='video_file'"><span>Archivo de video (MP4/WebM, máx 50 MB)</span>
                            <label class="bxb-btn">Subir video<input type="file" accept="video/mp4,video/webm" class="bxb-hidden" @change="uploadContentImage($event,null,null,'video_file')"></label>
                            <small x-show="blockContent.video_file" x-text="'Video cargado: '+blockContent.video_file"></small>
                        </div>
                        <div class="bxb-field" x-show="blockContent.media_type!=='image'"><span>Imagen de respaldo (si el video no carga)</span>
                            <div class="bxb-media"><span class="bxb-media-box" :style="blockContent.fallback_image?'background-image:url('+assetUrl(blockContent.fallback_image)+')':''"><template x-if="!blockContent.fallback_image"><em>Opcional</em></template></span>
                            <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,null,null,'fallback_image')"></label></div>
                        </div>
                        <div class="bxb-grid2" x-show="blockContent.media_type!=='image'">
                            <label class="bxb-switch"><input type="checkbox" :checked="blockContent.autoplay!==false" @change="setBlockContent('autoplay',$event.target.checked)"> Reproducción automática (siempre en silencio)</label>
                            <label class="bxb-switch"><input type="checkbox" :checked="blockContent.loop!==false" @change="setBlockContent('loop',$event.target.checked)"> Repetir en bucle</label>
                            <label class="bxb-switch"><input type="checkbox" :checked="blockContent.show_controls===true" @change="setBlockContent('show_controls',$event.target.checked)"> Mostrar controles</label>
                        </div>
                    </div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Altura
                                <select :value="blockContent.height||'medium'" @change="setBlockContent('height',$event.target.value)">
                                    <option value="small">Baja</option><option value="medium">Media</option>
                                    <option value="large">Alta</option><option value="full">Pantalla completa</option>
                                    <option value="reel">Vertical 9:16 (video tipo TikTok/Reels)</option>
                                </select>
                            </label>
                            <label class="bxb-field">Alineación del contenido
                                <select :value="blockContent.align||'center'" @change="setBlockContent('align',$event.target.value)">
                                    <option value="left">Izquierda</option><option value="center">Centro</option><option value="right">Derecha</option>
                                </select>
                            </label>
                            <label class="bxb-field">Color de superposición
                                <span class="bxb-color-row"><input type="color" :value="blockContent.overlay_color||'#0f172a'" @input="setBlockContent('overlay_color',$event.target.value)"><code x-text="blockContent.overlay_color||''"></code></span>
                            </label>
                            <label class="bxb-field">Opacidad de la superposición (%)
                                <input type="number" min="0" max="90" :value="blockContent.overlay_opacity??45" @change="setBlockContent('overlay_opacity',Math.max(0,Math.min(90,parseInt($event.target.value)||0)))">
                            </label>
                        </div>
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título (opcional)<input type="text" maxlength="180" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Subtítulo (opcional)<input type="text" maxlength="500" :value="blockContent.subtitle||''" @input.debounce.600ms="setBlockContent('subtitle',$event.target.value)"></label>
                            <label class="bxb-field">Texto del botón<input type="text" maxlength="60" :value="blockContent.button_text||''" @input.debounce.600ms="setBlockContent('button_text',$event.target.value)"></label>
                            <label class="bxb-field">Enlace del botón<input type="text" maxlength="500" placeholder="/tienda" :value="blockContent.button_url||''" @input.debounce.600ms="setBlockContent('button_url',$event.target.value)"></label>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Colecciones / Compra por ambiente --}}
            <template x-if="editingBlock.component==='collection_showcase'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título<input type="text" maxlength="180" placeholder="Compra por ambiente" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Subtítulo<input type="text" maxlength="500" :value="blockContent.subtitle||''" @input.debounce.600ms="setBlockContent('subtitle',$event.target.value)"></label>
                            <label class="bxb-field">Diseño
                                <select :value="blockContent.variant||'ambient'" @change="setBlockContent('variant',$event.target.value)">
                                    <option value="ambient">Tarjetas grandes (ambientes)</option>
                                    <option value="circles">Círculos (perfiles)</option>
                                    <option value="mosaic">Mosaico destacado</option>
                                    <option value="banners">Banners horizontales</option>
                                </select>
                            </label>
                            <label class="bxb-field">Columnas
                                <input type="number" min="2" max="4" :value="blockContent.columns||3" @change="setBlockContent('columns',Math.max(2,Math.min(4,parseInt($event.target.value)||3)))">
                            </label>
                        </div>
                    </div>
                    <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                        <div class="bxb-card">
                            <strong class="bxb-card-title">Tarjeta <span x-text="i+1"></span>
                                <button type="button" class="bxb-link" style="float:right" @click="removeContentItem('items',i)">Quitar</button>
                            </strong>
                            <div class="bxb-media"><span class="bxb-media-box" :style="it.image?'background-image:url('+assetUrl(it.image)+')':''"><template x-if="!it.image"><em>Foto</em></template></span>
                            <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,'items',i)"></label></div>
                            <div class="bxb-grid2">
                                <label class="bxb-field">Título<input type="text" maxlength="120" :value="it.title||''" @input.debounce.600ms="setContentItem('items',i,'title',$event.target.value)"></label>
                                <label class="bxb-field">Subtítulo<input type="text" maxlength="200" :value="it.subtitle||''" @input.debounce.600ms="setContentItem('items',i,'subtitle',$event.target.value)"></label>
                                <label class="bxb-field">Categoría enlazada
                                    <select :value="it.category_id||''" @change="setContentItem('items',i,'category_id',$event.target.value||null)">
                                        <option value="">Sin categoría (usar enlace)</option>
                                        <template x-for="c in storeCategories" :key="'cs'+c.id"><option :value="c.id" :selected="String(it.category_id)===String(c.id)" x-text="c.name"></option></template>
                                    </select>
                                </label>
                                <label class="bxb-field">Enlace manual (opcional)<input type="text" maxlength="500" placeholder="/tienda" :value="it.url||''" @input.debounce.600ms="setContentItem('items',i,'url',$event.target.value)"></label>
                            </div>
                        </div>
                    </template>
                    <button type="button" class="bxb-btn" @click="addContentItem('items')">＋ Agregar tarjeta</button>
                </div>
            </template>

            {{-- Marcas --}}
            <template x-if="editingBlock.component==='brands'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título<input type="text" maxlength="180" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Diseño
                                <select :value="blockContent.variant||'strip'" @change="setBlockContent('variant',$event.target.value)">
                                    <option value="strip">Fila de logos</option>
                                    <option value="grid">Cuadrícula con borde</option>
                                    <option value="carousel">Carrusel deslizable</option>
                                </select>
                            </label>
                        </div>
                        <label class="bxb-switch"><input type="checkbox" :checked="blockContent.grayscale!==false" @change="setBlockContent('grayscale',$event.target.checked)"> Logos en gris (a color al pasar el mouse)</label>
                    </div>
                    <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                        <div class="bxb-card">
                            <strong class="bxb-card-title">Marca <span x-text="i+1"></span>
                                <button type="button" class="bxb-link" style="float:right" @click="removeContentItem('items',i)">Quitar</button>
                            </strong>
                            <div class="bxb-media"><span class="bxb-media-box bxb-media-box--sq" :style="it.image?'background-image:url('+assetUrl(it.image)+');background-size:contain':''"><template x-if="!it.image"><em>Logo</em></template></span>
                            <label class="bxb-btn">Subir logo<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,'items',i)"></label></div>
                            <div class="bxb-grid2">
                                <label class="bxb-field">Nombre<input type="text" maxlength="80" :value="it.name||''" @input.debounce.600ms="setContentItem('items',i,'name',$event.target.value)"></label>
                                <label class="bxb-field">Enlace (opcional)<input type="text" maxlength="500" :value="it.url||''" @input.debounce.600ms="setContentItem('items',i,'url',$event.target.value)"></label>
                            </div>
                        </div>
                    </template>
                    <button type="button" class="bxb-btn" @click="addContentItem('items')">＋ Agregar marca</button>
                </div>
            </template>

            {{-- Testimonios --}}
            <template x-if="editingBlock.component==='testimonials'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título<input type="text" maxlength="180" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Diseño
                                <select :value="blockContent.variant||'cards'" @change="setBlockContent('variant',$event.target.value)">
                                    <option value="cards">Tarjetas</option>
                                    <option value="carousel">Carrusel</option>
                                    <option value="band">Cita destacada</option>
                                </select>
                            </label>
                        </div>
                        <div class="bxb-field"><span>Fuente de los testimonios</span>
                            <div class="bxb-seg">
                                <button type="button" :class="(blockContent.source||'manual')==='manual'&&'on'" @click="setBlockContent('source','manual')">Escritos aquí</button>
                                <button type="button" :class="blockContent.source==='reviews'&&'on'" @click="setBlockContent('source','reviews')">Reseñas aprobadas de la tienda</button>
                            </div>
                        </div>
                    </div>
                    <div x-show="(blockContent.source||'manual')==='manual'">
                        <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                            <div class="bxb-card">
                                <strong class="bxb-card-title">Testimonio <span x-text="i+1"></span>
                                    <button type="button" class="bxb-link" style="float:right" @click="removeContentItem('items',i)">Quitar</button>
                                </strong>
                                <div class="bxb-grid2">
                                    <label class="bxb-field">Nombre<input type="text" maxlength="80" :value="it.name||''" @input.debounce.600ms="setContentItem('items',i,'name',$event.target.value)"></label>
                                    <label class="bxb-field">Cargo o detalle<input type="text" maxlength="120" placeholder="Cliente desde 2023" :value="it.role||''" @input.debounce.600ms="setContentItem('items',i,'role',$event.target.value)"></label>
                                    <label class="bxb-field">Estrellas (0-5)<input type="number" min="0" max="5" :value="it.rating??5" @change="setContentItem('items',i,'rating',Math.max(0,Math.min(5,parseInt($event.target.value)||0)))"></label>
                                </div>
                                <label class="bxb-field">Testimonio<textarea rows="2" maxlength="600" @input.debounce.600ms="setContentItem('items',i,'text',$event.target.value)" x-text="it.text||''"></textarea></label>
                            </div>
                        </template>
                        <button type="button" class="bxb-btn" @click="addContentItem('items',{rating:5})">＋ Agregar testimonio</button>
                    </div>
                    <p class="bxb-note" x-show="blockContent.source==='reviews'">Se mostrarán automáticamente las mejores reseñas aprobadas de tus productos.</p>
                </div>
            </template>

            {{-- Galería --}}
            <template x-if="editingBlock.component==='gallery'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título<input type="text" maxlength="180" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Diseño
                                <select :value="blockContent.variant||'grid'" @change="setBlockContent('variant',$event.target.value)">
                                    <option value="grid">Cuadrícula uniforme</option>
                                    <option value="mosaic">Mosaico con destacadas</option>
                                </select>
                            </label>
                            <label class="bxb-field">Columnas<input type="number" min="2" max="4" :value="blockContent.columns||3" @change="setBlockContent('columns',Math.max(2,Math.min(4,parseInt($event.target.value)||3)))"></label>
                        </div>
                    </div>
                    <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                        <div class="bxb-card">
                            <strong class="bxb-card-title">Elemento <span x-text="i+1"></span>
                                <button type="button" class="bxb-link" style="float:right" @click="removeContentItem('items',i)">Quitar</button>
                            </strong>
                            <div class="bxb-seg">
                                <button type="button" :class="(it.type||'image')==='image'&&'on'" @click="setContentItem('items',i,'type','image')">Imagen</button>
                                <button type="button" :class="it.type==='video_url'&&'on'" @click="setContentItem('items',i,'type','video_url')">Video (enlace)</button>
                            </div>
                            <div x-show="(it.type||'image')==='image'" class="bxb-media"><span class="bxb-media-box" :style="it.image?'background-image:url('+assetUrl(it.image)+')':''"><template x-if="!it.image"><em>Foto</em></template></span>
                            <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,'items',i)"></label></div>
                            <label class="bxb-field" x-show="it.type==='video_url'">Enlace del video (YouTube/Vimeo)<input type="text" maxlength="500" :value="it.video_url||''" @input.debounce.600ms="setContentItem('items',i,'video_url',$event.target.value)"></label>
                            <label class="bxb-field">Leyenda (opcional)<input type="text" maxlength="160" :value="it.caption||''" @input.debounce.600ms="setContentItem('items',i,'caption',$event.target.value)"></label>
                        </div>
                    </template>
                    <button type="button" class="bxb-btn" @click="addContentItem('items',{type:'image'})">＋ Agregar elemento</button>
                </div>
            </template>

            {{-- Preguntas frecuentes --}}
            <template x-if="editingBlock.component==='faq'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título<input type="text" maxlength="180" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Diseño
                                <select :value="blockContent.variant||'accordion'" @change="setBlockContent('variant',$event.target.value)">
                                    <option value="accordion">Acordeón (una columna)</option>
                                    <option value="two-columns">Dos columnas</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                        <div class="bxb-card">
                            <strong class="bxb-card-title">Pregunta <span x-text="i+1"></span>
                                <button type="button" class="bxb-link" style="float:right" @click="removeContentItem('items',i)">Quitar</button>
                            </strong>
                            <label class="bxb-field">Pregunta<input type="text" maxlength="200" :value="it.question||''" @input.debounce.600ms="setContentItem('items',i,'question',$event.target.value)"></label>
                            <label class="bxb-field">Respuesta<textarea rows="2" maxlength="1200" @input.debounce.600ms="setContentItem('items',i,'answer',$event.target.value)" x-text="it.answer||''"></textarea></label>
                        </div>
                    </template>
                    <button type="button" class="bxb-btn" @click="addContentItem('items')">＋ Agregar pregunta</button>
                </div>
            </template>

            {{-- Asesoría por WhatsApp --}}
            <template x-if="editingBlock.component==='wa_advisory'">
                <div class="bxb-card">
                    <div class="bxb-field"><span>Diseño</span>
                        <div class="bxb-seg">
                            <button type="button" :class="(blockContent.variant||'band')==='band'&&'on'" @click="setBlockContent('variant','band')">Banda verde</button>
                            <button type="button" :class="blockContent.variant==='card'&&'on'" @click="setBlockContent('variant','card')">Tarjeta clara</button>
                        </div>
                    </div>
                    <div class="bxb-grid2">
                        <label class="bxb-field">Título<input type="text" maxlength="180" placeholder="¿Necesitas asesoría?" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                        <label class="bxb-field">Texto del botón<input type="text" maxlength="60" placeholder="Hablar por WhatsApp" :value="blockContent.button_text||''" @input.debounce.600ms="setBlockContent('button_text',$event.target.value)"></label>
                    </div>
                    <label class="bxb-field">Subtítulo<input type="text" maxlength="500" :value="blockContent.subtitle||''" @input.debounce.600ms="setBlockContent('subtitle',$event.target.value)"></label>
                    <label class="bxb-field">Mensaje que envía el cliente<input type="text" maxlength="500" :value="blockContent.message||''" @input.debounce.600ms="setBlockContent('message',$event.target.value)"></label>
                    <label class="bxb-field">WhatsApp distinto al de la tienda (opcional)<input type="text" maxlength="20" placeholder="Vacío = el de la tienda" :value="blockContent.phone||''" @input.debounce.600ms="setBlockContent('phone',$event.target.value)"></label>
                </div>
            </template>

            {{-- Llamada a la acción --}}
            <template x-if="editingBlock.component==='cta_banner'">
                <div class="bxb-card">
                    <div class="bxb-field"><span>Diseño</span>
                        <div class="bxb-seg">
                            <button type="button" :class="(blockContent.variant||'wide')==='wide'&&'on'" @click="setBlockContent('variant','wide')">Centrado</button>
                            <button type="button" :class="blockContent.variant==='split'&&'on'" @click="setBlockContent('variant','split')">Texto y botón a los lados</button>
                        </div>
                    </div>
                    <div class="bxb-grid2">
                        <label class="bxb-field">Título<input type="text" maxlength="180" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                        <label class="bxb-field">Subtítulo<input type="text" maxlength="500" :value="blockContent.subtitle||''" @input.debounce.600ms="setBlockContent('subtitle',$event.target.value)"></label>
                        <label class="bxb-field">Texto del botón<input type="text" maxlength="60" :value="blockContent.button_text||''" @input.debounce.600ms="setBlockContent('button_text',$event.target.value)"></label>
                        <label class="bxb-field">Enlace del botón<input type="text" maxlength="500" placeholder="/tienda" :value="blockContent.button_url||''" @input.debounce.600ms="setBlockContent('button_url',$event.target.value)"></label>
                    </div>
                    <label class="bxb-field">Color de fondo
                        <span class="bxb-color-row"><input type="color" :value="blockContent.background_color||'#0f172a'" @input="setBlockContent('background_color',$event.target.value)"><code x-text="blockContent.background_color||''"></code></span>
                    </label>
                    <div class="bxb-field"><span>Imagen de fondo (opcional, tapa el color)</span>
                        <div class="bxb-media"><span class="bxb-media-box" :style="blockContent.image?'background-image:url('+assetUrl(blockContent.image)+')':''"><template x-if="!blockContent.image"><em>Opcional</em></template></span>
                        <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,null,null,'image')"></label>
                        <button type="button" class="bxb-link" x-show="blockContent.image" @click="setBlockContent('image','')">Quitar</button></div>
                    </div>
                </div>
            </template>

            {{-- Sucursales y ubicación --}}
            <template x-if="editingBlock.component==='locations'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título<input type="text" maxlength="180" placeholder="Visítanos" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Subtítulo<input type="text" maxlength="500" :value="blockContent.subtitle||''" @input.debounce.600ms="setBlockContent('subtitle',$event.target.value)"></label>
                            <label class="bxb-field">Diseño
                                <select :value="blockContent.variant||'cards'" @change="setBlockContent('variant',$event.target.value)">
                                    <option value="cards">Tarjetas con mapa arriba</option>
                                    <option value="map-side">Mapa al costado (una por fila)</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                        <div class="bxb-card">
                            <strong class="bxb-card-title">Sucursal <span x-text="i+1"></span>
                                <button type="button" class="bxb-link" style="float:right" @click="removeContentItem('items',i)">Quitar</button>
                            </strong>
                            <div class="bxb-grid2">
                                <label class="bxb-field">Nombre<input type="text" maxlength="120" placeholder="Tienda principal" :value="it.name||''" @input.debounce.600ms="setContentItem('items',i,'name',$event.target.value)"></label>
                                <label class="bxb-field">Teléfono<input type="text" maxlength="30" :value="it.phone||''" @input.debounce.600ms="setContentItem('items',i,'phone',$event.target.value)"></label>
                            </div>
                            <label class="bxb-field">Dirección (el mapa se genera solo con esto)
                                <input type="text" maxlength="300" placeholder="Av. Ejemplo 123, Distrito, Ciudad" :value="it.address||''" @input.debounce.600ms="setContentItem('items',i,'address',$event.target.value)">
                            </label>
                            <label class="bxb-field">Horario<input type="text" maxlength="160" placeholder="Lun-Sáb 9am-8pm" :value="it.hours||''" @input.debounce.600ms="setContentItem('items',i,'hours',$event.target.value)"></label>
                            <label class="bxb-switch"><input type="checkbox" :checked="it.show_map!==false" @change="setContentItem('items',i,'show_map',$event.target.checked)"> Mostrar mapa de Google</label>
                        </div>
                    </template>
                    <button type="button" class="bxb-btn" @click="addContentItem('items',{show_map:true})">＋ Agregar sucursal</button>
                </div>
            </template>

            {{-- Banda informativa (4 bloques) --}}
            <template x-if="editingBlock.component==='info_strip'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Título (opcional)<input type="text" maxlength="180" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                            <label class="bxb-field">Subtítulo (opcional)<input type="text" maxlength="500" :value="blockContent.subtitle||''" @input.debounce.600ms="setBlockContent('subtitle',$event.target.value)"></label>
                        </div>
                        <p class="bxb-note">Hasta 4 bloques en una sola banda. Cada bloque puede usar una foto o un ícono.</p>
                    </div>
                    <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                        <div class="bxb-card">
                            <strong class="bxb-card-title">Bloque <span x-text="i+1"></span>
                                <span style="float:right;display:flex;gap:6px">
                                    <button type="button" class="bxb-link" @click="moveContentItem('items',i,-1)">↑</button>
                                    <button type="button" class="bxb-link" @click="moveContentItem('items',i,1)">↓</button>
                                    <button type="button" class="bxb-link" @click="removeContentItem('items',i)">Quitar</button>
                                </span>
                            </strong>
                            <div class="bxb-grid2">
                                <label class="bxb-field">Título<input type="text" maxlength="80" placeholder="Todo para tu hogar" :value="it.title||''" @input.debounce.600ms="setContentItem('items',i,'title',$event.target.value)"></label>
                                <label class="bxb-field">Descripción<input type="text" maxlength="160" placeholder="Muebles, decoración y organización" :value="it.description||''" @input.debounce.600ms="setContentItem('items',i,'description',$event.target.value)"></label>
                                <label class="bxb-field">Ícono (si no subes foto)
                                    <select :value="it.icon||'check'" @change="setContentItem('items',i,'icon',$event.target.value)">
                                        @foreach(['home'=>'Casa','sofa'=>'Sofá','plug'=>'Electrodoméstico','shield'=>'Escudo','truck'=>'Camión','box'=>'Caja','support'=>'Soporte','check'=>'Check'] as $ik=>$il)
                                        <option value="{{ $ik }}">{{ $il }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="bxb-field">Enlace (opcional)<input type="text" maxlength="300" placeholder="/tienda" :value="it.url||''" @input.debounce.600ms="setContentItem('items',i,'url',$event.target.value)"></label>
                                <div class="bxb-field"><span>Foto (opcional)</span>
                                    <div class="bxb-media">
                                        <span class="bxb-media-box" :style="it.image ? 'background-image:url('+assetUrl(it.image)+')' : ''"><template x-if="!it.image"><em>Usa ícono</em></template></span>
                                        <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,'items',i)"></label>
                                        <button type="button" class="bxb-link" x-show="it.image" @click="setContentItem('items',i,'image','')">Quitar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <button type="button" class="bxb-btn" @click="if(contentItems('items').length<4)addContentItem('items',{icon:'check'})">＋ Agregar bloque (máx. 4)</button>
                </div>
            </template>

            {{-- Nosotros (resumen en Inicio) --}}
            <template x-if="editingBlock.component==='about_preview'">
                <div>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Etiqueta pequeña<input type="text" maxlength="60" placeholder="Quiénes somos" :value="blockContent.label||''" @input.debounce.600ms="setBlockContent('label',$event.target.value)"></label>
                            <label class="bxb-field">Título<input type="text" maxlength="180" placeholder="Más de 14 años contigo" :value="blockContent.title||''" @input.debounce.600ms="setBlockContent('title',$event.target.value)"></label>
                        </div>
                        <label class="bxb-field">Descripción
                            <textarea rows="3" maxlength="1200" @input.debounce.600ms="setBlockContent('body',$event.target.value)" x-text="blockContent.body||''"></textarea>
                        </label>
                        <div class="bxb-grid2">
                            <div class="bxb-field"><span>Imagen</span>
                                <div class="bxb-media">
                                    <span class="bxb-media-box" :style="blockContent.image ? 'background-image:url('+assetUrl(blockContent.image)+')' : ''"><template x-if="!blockContent.image"><em>Foto del negocio</em></template></span>
                                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadContentImage($event,null,null,'image')"></label>
                                    <button type="button" class="bxb-link" x-show="blockContent.image" @click="setBlockContent('image','')">Quitar</button>
                                </div>
                            </div>
                            <div class="bxb-field"><span>Posición de la imagen</span>
                                <div class="bxb-seg">
                                    <button type="button" :class="(blockContent.variant||'image-left')==='image-left'&&'on'" @click="setBlockContent('variant','image-left')">Izquierda</button>
                                    <button type="button" :class="blockContent.variant==='image-right'&&'on'" @click="setBlockContent('variant','image-right')">Derecha</button>
                                </div>
                            </div>
                            <label class="bxb-field">Texto del botón<input type="text" maxlength="80" placeholder="Conoce nuestra historia" :value="blockContent.button_text||''" @input.debounce.600ms="setBlockContent('button_text',$event.target.value)"></label>
                            <label class="bxb-field">Enlace del botón<input type="text" maxlength="300" placeholder="/nosotros" :value="blockContent.button_url||''" @input.debounce.600ms="setBlockContent('button_url',$event.target.value)"></label>
                        </div>
                    </div>
                    <template x-for="(it,i) in contentItems('items')" :key="it.key||i">
                        <div class="bxb-card">
                            <strong class="bxb-card-title">Indicador <span x-text="i+1"></span>
                                <span style="float:right;display:flex;gap:6px">
                                    <button type="button" class="bxb-link" @click="moveContentItem('items',i,-1)">↑</button>
                                    <button type="button" class="bxb-link" @click="moveContentItem('items',i,1)">↓</button>
                                    <button type="button" class="bxb-link" @click="removeContentItem('items',i)">Quitar</button>
                                </span>
                            </strong>
                            <div class="bxb-grid2">
                                <label class="bxb-field">Ícono
                                    <select :value="it.icon||'star'" @change="setContentItem('items',i,'icon',$event.target.value)">
                                        @foreach(['award'=>'Medalla','users'=>'Personas','shield'=>'Escudo','star'=>'Estrella','heart'=>'Corazón','truck'=>'Camión','check'=>'Check','home'=>'Casa'] as $aik=>$ail)
                                        <option value="{{ $aik }}">{{ $ail }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="bxb-field">Número o dato<input type="text" maxlength="30" placeholder="+14" :value="it.value||''" @input.debounce.600ms="setContentItem('items',i,'value',$event.target.value)"></label>
                                <label class="bxb-field">Texto<input type="text" maxlength="80" placeholder="Años de experiencia" :value="it.title||''" @input.debounce.600ms="setContentItem('items',i,'title',$event.target.value)"></label>
                            </div>
                        </div>
                    </template>
                    <button type="button" class="bxb-btn" @click="if(contentItems('items').length<6)addContentItem('items',{icon:'star'})">＋ Agregar indicador (máx. 6)</button>
                </div>
            </template>
        </div>
    </template>
</section>

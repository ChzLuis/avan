{{-- Etapa: Encabezado y navegación — modelo (8 presets), estructura clásica,
     colores/barra superior y administración del menú. TODO lo del header vive aquí. --}}
<section class="bxb-stage">
    <h2>Encabezado y navegación</h2>
    <p class="bxb-stage-sub">Elige el modelo de encabezado, personalízalo y administra el menú. Los datos (categorías, perfiles, WhatsApp) vienen del catálogo y de Mi negocio: aquí solo decides cómo se muestran.</p>

    {{-- Encabezado y navegación: 8 presets modulares (HeaderPresets) --}}
    <div class="bxb-card" x-data='hpPresetPicker(@json(\App\Support\HeaderPresets::forBuilder()))'>
        <strong class="bxb-card-title">Encabezado y navegación</strong>
        <p class="bxb-note">Elige un modelo de encabezado. Cada modelo guarda su propia configuración: si cambias y vuelves, recuperas lo que tenías. "Clásico" mantiene el diseño configurado en Estructura.</p>
        <div class="bxb-grid2" style="grid-template-columns:repeat(auto-fill,minmax(210px,1fr))">
            <button type="button" class="bxb-tpl" :class="!(settings.header_preset||'')&&'is-active'" @click="setSetting('header_preset','')">
                <span class="hp-mini" aria-hidden="true"><i style="width:60%"></i><i style="width:90%"></i></span>
                <strong x-text="!(settings.header_preset||'') ? 'DISEÑO ACTUAL — CLÁSICO' : 'Clásico (anterior)'"></strong>
                <small>Estructura clásica anterior. Puedes conservarla o cambiar a uno de los nuevos modelos.</small>
            </button>
            <template x-for="p in hpPresets" :key="p.key">
                <button type="button" class="bxb-tpl" :class="(settings.header_preset||'')===p.key&&'is-active'" @click="hpSelect(p)">
                    <span class="hp-mini" :class="'hp-mini-'+p.key" aria-hidden="true"><i></i><i></i><i></i></span>
                    <strong x-text="p.label"></strong>
                    <small x-text="p.ideal"></small>
                    <em x-show="(settings.header_preset||'')===p.key" style="color:var(--sb-purple,#5B21B6);font-style:normal;font-weight:800;font-size:11px">✓ ACTIVO</em>
                </button>
            </template>
        </div>

        {{-- Personalización según capabilities del modelo activo --}}
        <template x-if="hpActive">
            <div style="margin-top:14px">
                <p class="bxb-note">La marca, colores, barra superior, menú y comportamiento sticky se configuran en las otras tarjetas de esta etapa — aplican a todos los modelos.</p>

                <label class="bxb-field">Desplegable de categorías en el menú
                    <select :value="settings.hp_cat_trigger||''" @change="setSetting('hp_cat_trigger',$event.target.value)">
                        <option value="">Según el modelo elegido</option>
                        <option value="none">Sin desplegable</option>
                        <option value="mega">Panel de categorías y subcategorías</option>
                        <option value="editorial">Panel editorial con imagen</option>
                    </select>
                    <small class="bxb-note">Actívalo si tu tienda tiene muchas subcategorías y quieres llegar a ellas desde el menú.</small>
                </label>

                {{-- Redes de la barra superior. El tamaño estaba fijo y en una
                     franja fina se veían desproporcionadas. --}}
                <div class="bxb-grid2">
                    <label class="bxb-field">Estilo de las redes (barra superior)
                        <select :value="settings.hp_topbar_social_style||'circulo'" @change="setSetting('hp_topbar_social_style',$event.target.value)">
                            <option value="circulo">Círculo con el color de cada red</option>
                            <option value="plano">Solo el icono, sin fondo</option>
                        </select>
                        <small class="bxb-note">Sin fondo va mejor cuando la barra ya tiene color propio: tres círculos de marca la ensucian.</small>
                    </label>
                    <label class="bxb-field">Tamaño de las redes
                        <input type="number" min="14" max="40" :value="settings.hp_topbar_social_size||26" @input.debounce.600ms="setSetting('hp_topbar_social_size',$event.target.value)">
                    </label>
                    <label class="bxb-field">Separación entre redes
                        <input type="number" min="4" max="28" :value="settings.hp_topbar_social_gap||10" @input.debounce.600ms="setSetting('hp_topbar_social_gap',$event.target.value)">
                    </label>
                </div>

                {{-- Colores del botón de teléfono del encabezado. Antes heredaba
                     el color del encabezado con transparencia y sobre fondos de
                     color la etiqueta no llegaba a contraste. --}}
                <div class="bxb-grid2">
                    <label class="bxb-field">Fondo del botón de teléfono
                        <x-bxb-color clave="hp_phone_btn_bg" defecto="#ffffff" etiqueta="Fondo del teléfono" />
                    </label>
                    <label class="bxb-field">Letra del botón de teléfono
                        <x-bxb-color clave="hp_phone_btn_color" defecto="#0f172a" etiqueta="Letra del teléfono" />
                    </label>
                    <label class="bxb-field bxb-full">Etiqueta sobre el número
                        <input type="text" maxlength="40" placeholder="Atención comercial" :value="settings.hp_header_phone_label||''" @input.debounce.600ms="setSetting('hp_header_phone_label',$event.target.value)">
                    </label>
                </div>

                {{-- Iconos del menú, con alcance elegible. Usa la misma
                     biblioteca que la portada, no un sistema aparte. --}}
                <label class="bxb-field">Iconos de categoría en el menú
                    <select :value="settings.hp_menu_icons||'no'" @change="setSetting('hp_menu_icons',$event.target.value)">
                        <option value="no">Sin iconos</option>
                        <option value="movil">Solo en móvil</option>
                        <option value="escritorio">Solo en computadora</option>
                        <option value="ambos">En móvil y computadora</option>
                    </select>
                    <small class="bxb-note">Usa los iconos que asignaste en <b>Inicio → Iconos de categorías</b>. En móvil ayudan a barrer la lista de un vistazo; en computadora, junto a un texto corto, muchas veces sobran — por eso puedes decidirlo por separado. Las categorías sin icono asignado se muestran solo con su nombre.</small>
                </label>

                <details class="bxb-advanced" x-show="hpCan('mega') || (settings.hp_cat_trigger||'')==='mega'" open>
                    <summary>Mega menú</summary>
                    <div class="bxb-card">
                        <label class="bxb-field">Disposición del panel
                            <select :value="settings.hp_mega_layout||'rejilla'" @change="setSetting('hp_mega_layout',$event.target.value)">
                                <option value="rejilla">Rejilla — todas las categorías a la vez</option>
                                <option value="lateral">Lateral — categorías a un lado y subcategorías al pasar</option>
                            </select>
                            <small class="bxb-note">La <b>lateral</b> muestra una columna con tus categorías y, al posar el cursor sobre una, abre sus subcategorías al lado. Va mejor cuando tienes muchas categorías o muchas subcategorías por categoría. Con la lateral, el número de columnas de abajo no aplica.</small>
                        </label>
                        <div class="bxb-grid2" x-show="(settings.hp_mega_layout||'rejilla')!=='lateral'" x-cloak>
                            <label class="bxb-field">Columnas del panel
                                <select :value="settings.hp_mega_columns||'4'" @change="setSetting('hp_mega_columns',$event.target.value)">
                                    <option value="1">1 — lista vertical</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option>
                                </select>
                                <small class="bxb-note">Con 1 el panel deja de ser una rejilla y pasa a ser una lista alta, una categoría por fila con su flecha. Va bien cuando hay muchas categorías de un solo nivel.</small>
                            </label>
                            <label class="bxb-field">Máx. subcategorías por categoría
                                <input type="number" min="2" max="12" :value="settings.hp_mega_max_subs||6" @input.debounce.600ms="setSetting('hp_mega_max_subs',$event.target.value)">
                            </label>
                        </div>
                        <label class="bxb-switch"><input type="checkbox" :checked="(settings.hp_mega_brands??'1')!=='0'" @change="setSetting('hp_mega_brands',$event.target.checked?'1':'0')"> Mostrar marcas destacadas (usa la sección Marcas del Inicio)</label>
                        <div class="bxb-grid2">
                            <label class="bxb-field">Etiqueta de la promoción<input type="text" maxlength="30" placeholder="Oferta" :value="settings.hp_mega_promo_badge||''" @input.debounce.600ms="setSetting('hp_mega_promo_badge',$event.target.value)"></label>
                            <label class="bxb-field">Título de la promoción<input type="text" maxlength="80" placeholder="Vacío = sin promoción" :value="settings.hp_mega_promo_title||''" @input.debounce.600ms="setSetting('hp_mega_promo_title',$event.target.value)"></label>
                            <label class="bxb-field">Descripción<input type="text" maxlength="140" :value="settings.hp_mega_promo_desc||''" @input.debounce.600ms="setSetting('hp_mega_promo_desc',$event.target.value)"></label>
                            <label class="bxb-field">Enlace<input type="text" maxlength="300" placeholder="/tienda" :value="settings.hp_mega_promo_url||''" @input.debounce.600ms="setSetting('hp_mega_promo_url',$event.target.value)"></label>
                            <div class="bxb-field"><span>Imagen de la promoción</span>
                                <div class="bxb-media">
                                    <span class="bxb-media-box" :style="settings.hp_mega_promo_image ? 'background-image:url('+assetUrl(settings.hp_mega_promo_image)+')' : ''"><template x-if="!settings.hp_mega_promo_image"><em>Opcional</em></template></span>
                                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'hp_mega_promo_image')"></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </details>

                <details class="bxb-advanced" x-show="hpCan('sidebar')" open>
                    <summary>Barra lateral de categorías</summary>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Máx. categorías
                                <input type="number" min="4" max="20" :value="settings.hp_catalog_max_cats||10" @input.debounce.600ms="setSetting('hp_catalog_max_cats',$event.target.value)">
                            </label>
                            <label class="bxb-switch" style="align-self:end"><input type="checkbox" :checked="(settings.hp_catalog_show_icons??'1')!=='0'" @change="setSetting('hp_catalog_show_icons',$event.target.checked?'1':'0')"> Mostrar íconos</label>
                        </div>
                    </div>
                </details>

                <details class="bxb-advanced" x-show="hpCan('visual')" open>
                    <summary>Tarjetas visuales</summary>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Columnas
                                <select :value="settings.hp_visual_columns||'4'" @change="setSetting('hp_visual_columns',$event.target.value)">
                                    <option value="3">3</option><option value="4">4</option><option value="5">5</option>
                                </select>
                            </label>
                            <label class="bxb-field">Oscurecido de las fotos (%)
                                <input type="number" min="0" max="80" :value="settings.hp_visual_overlay||35" @input.debounce.600ms="setSetting('hp_visual_overlay',$event.target.value)">
                            </label>
                        </div>
                        <p class="bxb-note">Las tarjetas usan las fotos reales de tus categorías; sin foto muestran un diseño de color con la inicial.</p>
                    </div>
                </details>

                <details class="bxb-advanced" x-show="hpCan('universes')" open>
                    <summary>Universos (públicos)</summary>
                    <div class="bxb-card">
                        <div class="bxb-field"><span>Estilo del selector</span>
                            <div class="bxb-seg">
                                <button type="button" :class="hpUni()==='pill'&&'on'" @click="hpSetUni('pill')">Píldoras</button>
                                <button type="button" :class="hpUni()==='text'&&'on'" @click="hpSetUni('text')">Texto</button>
                                <button type="button" :class="hpUni()==='both'&&'on'" @click="hpSetUni('both')">Punto + texto</button>
                            </div>
                        </div>
                        <label class="bxb-switch" x-show="(settings.header_preset||'')==='multiverse'">
                            <input type="checkbox" :checked="(settings.hp_multiverse_use_categories??'1')!=='0'" @change="setSetting('hp_multiverse_use_categories',$event.target.checked?'1':'0')">
                            Sin perfiles, usar categorías principales como universos
                        </label>
                        <label class="bxb-field">Aviso a la derecha de la barra
                            <input type="text" maxlength="90" placeholder="Ej.: Venta por mayor desde 12 unidades" :value="settings.uni_top_note||''" @input.debounce.600ms="setSetting('uni_top_note',$event.target.value)">
                            <small class="bxb-note">Déjalo vacío para no mostrar nada. No repitas aquí el teléfono si ya aparece en la fila del logo.</small>
                        </label>
                        <p class="bxb-note">Los universos salen de tus <strong>Perfiles</strong> (etapa Catálogo → "Perfiles de catálogo"). No se duplican datos: solo cambia cómo se navega.</p>
                    </div>
                </details>

                <details class="bxb-advanced" x-show="hpCan('promo') && (settings.header_preset||'')==='boutique'" open>
                    <summary>Imagen editorial del menÃº</summary>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <div class="bxb-field"><span>Imagen de campaÃ±a</span>
                                <div class="bxb-media">
                                    <span class="bxb-media-box" :style="settings.hp_boutique_promo_image ? 'background-image:url('+assetUrl(settings.hp_boutique_promo_image)+')' : ''"><template x-if="!settings.hp_boutique_promo_image"><em>Usa tu mejor foto</em></template></span>
                                    <label class="bxb-btn">Subir<input type="file" accept="image/*" class="bxb-hidden" @change="uploadMedia($event,'hp_boutique_promo_image')"></label>
                                </div>
                            </div>
                            <label class="bxb-field">TÃ­tulo<input type="text" maxlength="80" placeholder="Nueva colecciÃ³n" :value="settings.hp_boutique_promo_title||''" @input.debounce.600ms="setSetting('hp_boutique_promo_title',$event.target.value)"></label>
                            <label class="bxb-field">Texto breve<input type="text" maxlength="140" :value="settings.hp_boutique_promo_desc||''" @input.debounce.600ms="setSetting('hp_boutique_promo_desc',$event.target.value)"></label>
                            <label class="bxb-field">Enlace<input type="text" maxlength="300" placeholder="/tienda" :value="settings.hp_boutique_promo_url||''" @input.debounce.600ms="setSetting('hp_boutique_promo_url',$event.target.value)"></label>
                        </div>
                        <p class="bxb-note">Sin imagen, el panel usa automÃ¡ticamente una foto de tus colecciones o categorÃ­as.</p>
                    </div>
                </details>

                <details class="bxb-advanced" x-show="hpCan('b2b')" open>
                    <summary>Comercial / B2B</summary>
                    <div class="bxb-card">
                        <label class="bxb-switch"><input type="checkbox" :checked="(settings.hp_commercial_show_wholesale??'1')!=='0'" @change="setSetting('hp_commercial_show_wholesale',$event.target.checked?'1':'0')"> Destacar venta mayorista (usa tu configuración de precios mayoristas)</label>
                        <div class="bxb-grid2">
                            <label class="bxb-field">Texto mayorista<input type="text" maxlength="90" placeholder="Venta mayorista — precios por volumen" :value="settings.hp_commercial_wholesale_text||''" @input.debounce.600ms="setSetting('hp_commercial_wholesale_text',$event.target.value)"></label>
                            <label class="bxb-field">Texto del botón WhatsApp<input type="text" maxlength="60" placeholder="Cotiza por WhatsApp" :value="settings.hp_commercial_wa_text||''" @input.debounce.600ms="setSetting('hp_commercial_wa_text',$event.target.value)"></label>
                            <label class="bxb-field">CTA empresa (título)<input type="text" maxlength="80" placeholder="¿Eres empresa?" :value="settings.hp_commercial_cta_title||''" @input.debounce.600ms="setSetting('hp_commercial_cta_title',$event.target.value)"></label>
                            <label class="bxb-field">CTA empresa (enlace)<input type="text" maxlength="300" placeholder="/contacto" :value="settings.hp_commercial_cta_url||''" @input.debounce.600ms="setSetting('hp_commercial_cta_url',$event.target.value)"></label>
                        </div>
                        <p class="bxb-note">El número y mensaje de WhatsApp salen de tu configuración general — no se ingresan dos veces.</p>
                    </div>
                </details>

                <details class="bxb-advanced" x-show="hpCan('search_pro')" open>
                    <summary>Buscador Pro</summary>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-switch"><input type="checkbox" :checked="(settings.hp_search_show_price??'1')!=='0'" @change="setSetting('hp_search_show_price',$event.target.checked?'1':'0')"> Mostrar precio en sugerencias</label>
                            <label class="bxb-switch"><input type="checkbox" :checked="(settings.hp_search_show_thumb??'1')!=='0'" @change="setSetting('hp_search_show_thumb',$event.target.checked?'1':'0')"> Mostrar miniatura</label>
                        </div>
                        <p class="bxb-note">El texto del buscador se cambia en Venta → Textos de la tienda. Las sugerencias usan tu catálogo real (productos, categoría y precio).</p>
                    </div>
                </details>

                <details class="bxb-advanced" x-show="hpCan('cta')" open>
                    <summary>Botón de acción (CTA)</summary>
                    <div class="bxb-card">
                        <div class="bxb-grid2">
                            <label class="bxb-field">Texto del botón<input type="text" maxlength="40" placeholder="Vacío = sin botón" :value="settings.hp_minimal_cta_text||''" @input.debounce.600ms="setSetting('hp_minimal_cta_text',$event.target.value)"></label>
                            <label class="bxb-field">Enlace<input type="text" maxlength="300" placeholder="/tienda" :value="settings.hp_minimal_cta_url||''" @input.debounce.600ms="setSetting('hp_minimal_cta_url',$event.target.value)"></label>
                        </div>
                    </div>
                </details>
            </div>
        </template>
    </div>

    <style>
        .hp-mini{display:flex;flex-direction:column;gap:3px;height:34px;padding:6px;background:#f1f5f9;border-radius:6px;margin-bottom:6px}
        .hp-mini i{display:block;height:5px;border-radius:2px;background:#cbd5e1}
        .hp-mini-mega_menu i:nth-child(3){background:linear-gradient(90deg,#94a3b8 24%,#e2e8f0 24% 26%,#94a3b8 26% 49%,#e2e8f0 49% 51%,#94a3b8 51% 74%,#e2e8f0 74% 76%,#94a3b8 76%)}
        .hp-mini-boutique i:nth-child(1){width:38%;margin:0 auto}
        .hp-mini-catalog_pro{flex-direction:row}.hp-mini-catalog_pro i{height:100%;width:26%}.hp-mini-catalog_pro i:nth-child(2),.hp-mini-catalog_pro i:nth-child(3){width:37%;height:5px;align-self:flex-start}
        .hp-mini-minimal i:nth-child(2),.hp-mini-minimal i:nth-child(3){display:none}
        .hp-mini-commercial i:nth-child(3){background:#22c55e}
        .hp-mini-search_first i:nth-child(2){height:10px;background:#94a3b8}
        .hp-mini-visual_collections{flex-direction:row}.hp-mini-visual_collections i{width:31%;height:100%}
        .hp-mini-multiverse i:nth-child(1){background:repeating-linear-gradient(90deg,#94a3b8 0 14%,#f1f5f9 14% 20%)}
    </style>


    {{-- Estructura clásica (solo aplica sin modelo elegido) --}}
    <div class="bxb-card" x-show="!(settings.header_preset||'')">
        <strong class="bxb-card-title">Cabecera clásica</strong>
        <p class="bxb-note">Estas variantes aplican cuando no eliges un modelo arriba. Al elegir un modelo, la estructura la define el modelo.</p>
        <label class="bxb-field">Diseño de la cabecera
            <select :value="settings.header_layout||'classic'" @change="setSetting('header_layout',$event.target.value)">
                @foreach(\App\Support\StorefrontLayoutPacks::options('headers') as $vKey => $vLabel)
                <option value="{{ $vKey }}">{{ $vLabel }}</option>
                @endforeach
            </select>
        </label>
    </div>

    {{-- Encabezado y barra superior --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Encabezado y barra superior</strong>
        <div class="bxb-grid2">
            <label class="bxb-field">Fondo del encabezado
                <x-bxb-color clave="header_bg_color" defecto="#ffffff" etiqueta="Fondo del encabezado" />
            </label>
            <label class="bxb-field">Letra del encabezado
                <x-bxb-color clave="header_text_color" defecto="#0f172a" etiqueta="Letra del encabezado" />
            </label>
        </div>
        <label class="bxb-field">Diseño del menú de navegación
            <select :value="settings.header_style||'classic'" @change="setSetting('header_style',$event.target.value)">
                <option value="classic">Clásico claro</option>
                <option value="dark">Barra oscura</option>
                <option value="accent">Banda de color</option>
                <option value="pill">Píldora flotante</option>
                <option value="line">Minimal subrayado</option>
            </select>
        </label>
        <div class="bxb-field"><span>Colores del menú (opcional — "Auto" usa los del diseño elegido)</span>
            <div class="bxb-grid2">
                @foreach(['menu_bg_color' => 'Fondo del menú', 'menu_text_color' => 'Texto del menú', 'menu_active_bg_color' => 'Fondo del botón activo', 'menu_active_text_color' => 'Texto del botón activo'] as $mk => $ml)
                <label class="bxb-field">{{ $ml }}
                    <x-bxb-color clave="{{ $mk }}" defecto="#ffffff" etiqueta="{{ $ml }}" />
                </label>
                @endforeach
            </div>
            <small>El texto de cada botón del menú se cambia en Navegación (Inicio, Tienda, Nosotros…), no aquí.</small>
        </div>
        <label class="bxb-field">Fondo de la página (todo el lienzo)
            <x-bxb-color clave="page_bg_color" defecto="#f8fafc" etiqueta="Fondo de la página" />
        </label>
        <div class="bxb-field"><span>Qué baja con el scroll</span>
            <div class="bxb-seg">
                <button type="button" :class="(settings.header_sticky_mode||'all')==='all'&&'on'" @click="setSetting('header_sticky_mode','all')">Todo el encabezado</button>
                <button type="button" :class="(settings.header_sticky_mode||'all')==='header'&&'on'" @click="setSetting('header_sticky_mode','header')">Solo el encabezado</button>
                <button type="button" :class="(settings.header_sticky_mode||'all')==='menu'&&'on'" @click="setSetting('header_sticky_mode','menu')">Solo el menú</button>
                <button type="button" :class="(settings.header_sticky_mode||'all')==='none'&&'on'" @click="setSetting('header_sticky_mode','none')">Nada</button>
            </div>
            <small>"Todo" fija los tres bloques juntos: barra superior, encabezado y menú. "Solo el encabezado" deja fijo el logo, buscador y carrito, y repliega el menú al bajar. "Solo el menú" hace lo contrario: fija la barra de categorías y muestra en ella un buscador y un carrito compactos.</small>
        </div>
        <label class="bxb-field">Texto de la barra superior
            <input type="text" maxlength="160" placeholder="Envíos a todo el país" :value="settings.announcement_text||''" @input.debounce.600ms="setSetting('announcement_text',$event.target.value)">
        </label>
        <div class="bxb-grid2" x-show="(settings.announcement_text||'')!==''">
            <label class="bxb-field">Fondo de la barra
                <x-bxb-color clave="announcement_bg" defecto="#0f172a" etiqueta="Fondo barra superior" />
            </label>
            <label class="bxb-field">Letra de la barra
                <x-bxb-color clave="announcement_color" defecto="#ffffff" etiqueta="Letra barra superior" />
            </label>
        </div>
    </div>


    {{-- ═══ Accesos del menú ═══
         Eran tres enlaces fijos escritos en la plantilla (Ofertas, Novedades,
         WhatsApp) y ni el texto ni el destino se podían tocar sin código. --}}
    <div class="bxb-card">
        <strong class="bxb-card-title">Accesos del menú</strong>
        <p class="bxb-note">Los botones que salen a la derecha de la barra de navegación. Cada uno puede llevar a una categoría, a un perfil de catálogo, a una acción (WhatsApp, llamar) o a la dirección que quieras. Deja el destino vacío para no usar esa ranura.</p>
        <p class="bxb-note" x-show="![1,2,3,4,5,6].some(n=>settings['hp_nav_chip_'+n+'_target'])" x-cloak>
            Ahora mismo se muestran los tres accesos de siempre (Ofertas, Novedades y WhatsApp). En cuanto configures la primera ranura, mandan estas.
        </p>

        <template x-for="n in 6" :key="'chip'+n">
            <div class="bxb-grid2" style="grid-template-columns:auto 1.1fr 1.4fr .9fr .8fr auto;align-items:end;gap:10px;margin-bottom:10px">
                <label class="bxb-switch" style="padding-bottom:9px">
                    <input type="checkbox"
                           :checked="(settings['hp_nav_chip_'+n+'_enabled']||'1')!=='0'"
                           @change="setSetting('hp_nav_chip_'+n+'_enabled', $event.target.checked?'1':'0')">
                    <span x-text="n"></span>
                </label>

                <label class="bxb-field">Texto
                    <input type="text" maxlength="40" placeholder="Ofertas"
                           :value="settings['hp_nav_chip_'+n+'_text']||''"
                           @input.debounce.600ms="setSetting('hp_nav_chip_'+n+'_text',$event.target.value)">
                </label>

                <label class="bxb-field">Lleva a
                    <select :value="settings['hp_nav_chip_'+n+'_target']||''"
                            @change="setSetting('hp_nav_chip_'+n+'_target',$event.target.value)">
                        <option value="">— sin usar —</option>
                        <optgroup label="Acciones">
                            <option value="ofertas">Ofertas del catálogo</option>
                            <option value="novedades">Novedades</option>
                            <option value="catalogo">Ver todo el catálogo</option>
                            <option value="whatsapp">Escribir por WhatsApp</option>
                        </optgroup>
                        <optgroup label="Perfiles de catálogo" x-show="profiles && profiles.length">
                            <template x-for="pf in (profiles||[])" :key="'ct'+n+'p'+pf.id">
                                <option :value="'perfil:'+pf.slug" x-text="pf.menu_label||pf.name"></option>
                            </template>
                        </optgroup>
                        <optgroup label="Categorías">
                            <template x-for="c in (storeCategories||[])" :key="'ct'+n+'c'+c.id">
                                <option :value="'categoria:'+c.id" x-text="c.name"></option>
                            </template>
                        </optgroup>
                    </select>
                </label>

                <label class="bxb-field">Icono
                    <select :value="settings['hp_nav_chip_'+n+'_icon']||'etiqueta'"
                            @change="setSetting('hp_nav_chip_'+n+'_icon',$event.target.value)">
                        <option value="etiqueta">Etiqueta</option>
                        <option value="novedad">Novedad</option>
                        <option value="descuento">Descuento</option>
                        <option value="estrella">Estrella</option>
                        <option value="corazon">Corazón</option>
                        <option value="regalo">Regalo</option>
                        <option value="camion">Envío</option>
                        <option value="escudo">Garantía</option>
                        <option value="tienda">Tienda</option>
                        <option value="carrito">Carrito</option>
                        <option value="caja">Producto</option>
                        <option value="catalogo">Catálogo</option>
                        <option value="telefono">Teléfono</option>
                        <option value="soporte">Soporte</option>
                        <option value="reloj">Horario</option>
                        <option value="ubicacion">Ubicación</option>
                        <option value="tarjeta">Pago</option>
                        <option value="usuario">Cuenta</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </label>

                <label class="bxb-field">Estilo
                    <select :value="settings['hp_nav_chip_'+n+'_style']||'suave'"
                            @change="setSetting('hp_nav_chip_'+n+'_style',$event.target.value)">
                        <option value="plano">Solo texto</option>
                        <option value="suave">Fondo suave</option>
                        <option value="solido">Botón sólido</option>
                    </select>
                </label>

                <label class="bxb-field">Color
                    {{-- Mismo campo de una pieza que el resto, pero escrito a
                         medida: la clave es dinámica (`'hp_nav_chip_'+n+'_color'`)
                         y el componente recibe la clave como texto fijo. --}}
                    <span class="bxb-color-one" x-data="{
                        norm(v){
                            v = String(v || '').trim().replace(/^#/, '');
                            if (/^[0-9a-fA-F]{3}$/.test(v)) v = v.split('').map(c => c + c).join('');
                            return /^[0-9a-fA-F]{6}$/.test(v) ? '#' + v.toLowerCase() : null;
                        }
                    }">
                        <input type="color" class="bxb-color-dot"
                               :value="settings['hp_nav_chip_'+n+'_color']||'#2563eb'"
                               @change="setSetting('hp_nav_chip_'+n+'_color',$event.target.value)"
                               aria-label="Elegir color del acceso">
                        <input type="text" class="bxb-color-hex" maxlength="7" spellcheck="false"
                               placeholder="automático"
                               :value="settings['hp_nav_chip_'+n+'_color']||''"
                               @change="(() => { const v = $event.target.value; if (String(v).trim() === '') { setSetting('hp_nav_chip_'+n+'_color',''); return; } const c = norm(v); if (c) setSetting('hp_nav_chip_'+n+'_color', c); })()"
                               @blur="$event.target.value = settings['hp_nav_chip_'+n+'_color'] || ''"
                               aria-label="Color del acceso en código hexadecimal">
                    </span>
                </label>
            </div>
        </template>

        <p class="bxb-note">Un solo acceso en <b>botón sólido</b> por fila: dos compiten entre sí y ninguno destaca. El resto, en fondo suave o solo texto.</p>
    </div>

    {{-- Menú de navegación (administración canónica; guarda al instante) --}}
    <div class="bxb-card bxb-embed">
        @include('settings.partials.store-navigation-builder')
    </div>
</section>

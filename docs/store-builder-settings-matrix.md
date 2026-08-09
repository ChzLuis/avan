# Matriz de ajustes del constructor

## Leyenda

- **OK**: persistencia y consumo público directos en el flujo revisado.
- **Parcial**: funciona solo en ciertas plantillas, depende del DOM/JS o falta un estado.
- **No visible**: el control/ruta existe, pero no está expuesto en la interfaz actual.
- **No reflejado**: se guarda, pero no existe un consumidor confiable en CompuTienda o en todas las plantillas.
- **Duplicado**: otra fuente configura el mismo concepto.

Rutas abreviadas: `design` = `settings.design.update`; `home` = `settings.experience.home.save`; `header` = `settings.storefront.header`; `menu` = `settings.storefront.menu.*`; `page` = `settings.experience.page`; `popup` = `settings.experience.popup`.

## Configuración global del Diseñador

| Sección | Opción | Input | Llave | Ruta | Controlador | Persistencia | Plantilla | Estado | Acción requerida | Prueba |
|---|---|---|---|---|---|---|---|---|---|---|
| Marca | Color principal | `primary_color` | igual | design | `updateDesign` | `project_settings` | CSS/runtime y plantillas | OK | Centralizar contrato | Cambiar y verificar 3 plantillas |
| Marca | Color secundario | `secondary_color` | igual | design | `updateDesign` | `project_settings` | CSS/runtime y plantillas | OK | Centralizar contrato | Igual anterior |
| Marca | Logo | `logo_url` | igual | design | `updateDesign` | `project_settings` | plantillas/runtime | Parcial | Unificar con `header_logo_url` | Cambiar logo y plantilla |
| Marca | Alto logo | `logo_height` | igual | design | `updateDesign` | `project_settings` | varias plantillas | Duplicado | Unificar con `header_logo_height` | PC/tablet/móvil |
| Marca | Favicon | `favicon_url` | igual | design | `updateDesign` | `project_settings` | runtime JS/meta | Parcial | Render servidor | Recarga dura y caché |
| Marca | Fuente títulos/cuerpo | `font_title`, `font_body` | iguales | design | `updateDesign` | `project_settings` | plantillas/runtime | Parcial | Exigir consumo en manifiesto | Matriz por plantilla |
| Marca | Radio de borde | `border_radius` | igual | design | `updateDesign` | `project_settings` | plantillas/runtime | Parcial | Normalizar valores | Cards/botones/modal |
| Marca | Moneda | `currency_symbol` | igual | design | `updateDesign` | `project_settings` | precios/runtime | OK | Mantener | Producto/carrito/checkout |
| Marca | Encabezado fondo/texto/alto | `header_bg_color`, `header_text_color`, `header_height` | iguales | design | `updateDesign` | `project_settings` | plantillas | Duplicado | Un solo editor de header | Guardar desde ambos formularios |
| Marca | Footer fondo/texto/alto logo | `footer_bg_color`, `footer_text_color`, `footer_logo_height` | iguales | design | `updateDesign` | `project_settings` | footer nativo/runtime | Parcial | Contrato por plantilla | Todas las páginas |
| Marca | WhatsApp general | `whatsapp_msg` | igual | design | `updateDesign` | `project_settings` | runtime/WA | Duplicado | Unificar con `quote_wa_msg` | Botón flotante y pedido |
| Marca | Redes sociales | `facebook_url`, `instagram_url`, `tiktok_url`, `youtube_url`, `twitter_url`, `linkedin_url` | iguales | design | `updateDesign` | `project_settings` | footer | Parcial | Alinear redes soportadas | Cada enlace, URL vacía |
| Portada | Título/subtítulo | `hero_title`, `hero_subtitle` | iguales | design | `updateDesign` | `project_settings` | hero nativo/runtime | Duplicado | Migrar al bloque `hero` o declarar precedencia | Con/sin hero publicado |
| Portada | Badge/fondo/imagen/overlay | `hero_badge`, `hero_bg_color`, `hero_image`, `hero_overlay` | iguales | design | `updateDesign` | `project_settings` | hero nativo/runtime | Duplicado | Una fuente de verdad | Con imagen móvil/borrador |
| Portada | Alineación/alto | `hero_align`, `hero_height` | iguales | design | `updateDesign` | `project_settings` | nativo/runtime | Parcial | Implementar por plantilla o deshabilitar | 3 breakpoints |
| Portada | CTA 1/2 visible y texto | `hero_cta1_show`, `hero_cta1_text`, `hero_cta2_show`, `hero_cta2_text` | iguales | design | `updateDesign` | `project_settings` | nativo/runtime | Duplicado | Integrar en `store_sections.hero` | Ambos estados y enlaces |
| Portada | Anuncio superior | `announcement_text`, `announcement_bg` | iguales | design | `updateDesign` | `project_settings` | runtime | Duplicado | Integrar con `announcements` | Con bloque publicado |
| Portada | Contador heredado | `countdown_label`, `countdown_end` | iguales | design | `updateDesign` | `project_settings` | runtime | Duplicado | Integrar con `daily_offer` | Expirado/no expirado |
| Portada | Banners 1/2 | `banner1_title/sub`, `banner2_title/sub` | iguales | design | `updateDesign` | `project_settings` | runtime | Duplicado | Integrar con anuncios | Plantilla con DOM distinto |
| Portada | Bloques izquierda/derecha | `split_left_*`, `split_right_*` | iguales | design | `updateDesign` | `project_settings` | runtime | Duplicado | Integrar con anuncios | Vacíos/parciales |
| Portada | Trust 1–4 | `trust_icon_N`, `trust_text_N` | iguales | design | `updateDesign` | `project_settings` | runtime/CompuTienda | Duplicado | Integrar con beneficios | 0–4 elementos |
| Portada | Tabs 1–3 | `tabN_label` | iguales | design | `updateDesign` | `project_settings` | runtime, enlaces `#catalogo` | No reflejado correctamente | Sustituir por categorías reales | Navegación y accesibilidad |
| Portada | Barrera de edad | `age_gate` | igual | design | `updateDesign` | `project_settings` | runtime | Parcial | Contrato y prueba por plantilla | Aceptar/rechazar/sesión |
| Catálogo | Título | `catalog_section_title` | igual | design | `updateDesign` | `project_settings` | plantillas/runtime | Parcial | Estándar común | Inicio vs Tienda |
| Catálogo | Estilo de tarjeta | `card_style` | igual | design | `updateDesign` | `project_settings` | desigual por plantilla | Parcial | Capabilities reales | Cada plantilla |
| Catálogo | Columnas PC/móvil | `catalog_cols_desktop`, `catalog_cols_mobile` | iguales | design | `updateDesign` | `project_settings` | plantillas/runtime | Parcial | Añadir tablet y validar rango | 320/768/1440 px |
| Catálogo | Filtros | `catalog_filter_price/cats/sale/search` | iguales | design | `updateDesign` | `project_settings` | runtime por selectores; CompuTienda tiene UI propia | Parcial | Render servidor y contrato | Cada filtro y combinación |
| Catálogo | Badges | `catalog_badge_sale/new/featured/sold_out` | iguales | design | `updateDesign` | `project_settings` | algunos nativos/runtime | Parcial | Unificar estados producto | Producto por estado |
| Catálogo | Ratings/quick view/SKU/stock | `catalog_show_ratings`, `catalog_quick_view`, `catalog_show_sku`, `catalog_show_stock` | iguales | design | `updateDesign` | `project_settings` | mayormente runtime heurístico | Parcial | Componentes explícitos | Presente/ausente por plantilla |
| Catálogo | Mayorista | `wholesale_enabled` | igual | design | `updateDesign` | `project_settings` | no hay consumo uniforme | No reflejado | Definir lógica/precios | Cliente mayorista/minorista |
| Catálogo | Textos de botones | `btn_cart_text`, `btn_quote_text` | iguales | design | `updateDesign` | `project_settings` | nativo/runtime | Parcial | API común de botones | Directo/cotización |
| Catálogo | Forma/icono botón | `btn_shape`, `btn_show_icon` | iguales | design | `updateDesign` | `project_settings` | CSS/runtime | Parcial | Contrato común | Todas las cards |
| Catálogo | Carrito flotante | `float_cart_show`, `float_cart_pos` | iguales | design | `updateDesign` | `project_settings` | no uniforme; CompuTienda usa header cart | No reflejado uniforme | Capabilities o implementación común | 4 posiciones |
| Catálogo | WhatsApp flotante | `float_wa_show`, `float_wa_tooltip`, `float_wa_pos` | iguales | design | `updateDesign` | `project_settings` | plantillas con alias distintos | Parcial | Llave canónica única | Visible/oculto/posición |
| Sistema | Modo venta | `store_mode` | igual | design | `updateDesign` | `project_settings` | plantillas/runtime | OK parcial | Probar flujo integral | Compra y cotización |
| Sistema | Precios y WhatsApp cotización | `quote_price_display`, `quote_whatsapp_country`, `quote_whatsapp`, `quote_wa_msg` | iguales | design | `updateDesign` | `project_settings` | runtime/plantillas | Parcial | Normalizar teléfono y mensaje | Países y número vacío |
| Sistema | Pagos aceptados | `accepted_payments[]` | JSON | design | `updateDesign` | `project_settings` | checkout/footer | Parcial | Validación de valores | Métodos válidos/desconocidos |
| Sistema | Envío | `shipping_enabled`, `shipping_cost`, `shipping_free_from`, `require_address` | iguales | design | `updateDesign` | `project_settings` | fallback checkout; no todas plantillas | Parcial | Servicio de cálculo único | Umbral y dirección |
| Sistema | Secciones heredadas | `show_flash_sale`, `show_testimonials`, `show_newsletter`, `show_trust_strip` | iguales | design | `updateDesign` | `project_settings` | runtime heurístico | Duplicado | Reemplazar por registro de secciones | Activar/desactivar por plantilla |
| Sistema | Footer textos/contacto | `footer_tagline`, `footer_copyright`, `footer_dev_text`, `contact_email`, `contact_phone`, `business_hours` | iguales | design | `updateDesign` | `project_settings` | footer nativo/runtime | OK parcial | Normalizar dirección/horarios | Todas las páginas |
| Sistema | Beneficios de footer | `footer_benefit_N_icon/text` | iguales | design | `updateDesign` | `project_settings` | footer | Duplicado | Separar footer de beneficios Inicio | 0–3 elementos |
| Sistema | Enlaces footer | `footer_pages`, `footer_store_pages` | texto `Título|URL` | design | `updateDesign` | `project_settings` | runtime, no footer propio de CompuTienda | No reflejado en CompuTienda | Usar menú/páginas tipadas | URL válida/inválida |
| Sistema | Newsletter footer | `footer_newsletter_title/url`, `footer_show_newsletter` | iguales | design | `updateDesign` | `project_settings` | runtime; suprimido con footer propio | No reflejado en CompuTienda | Componente explícito | Con/sin URL |
| Sistema | Visibilidad footer | `footer_show_social/categories/benefits/address` | iguales | design | `updateDesign` | `project_settings` | desigual por footer | Parcial | Contrato común | Cada toggle |
| Sistema | Textos carrito/checkout | `cart_title`, `cart_empty_msg`, `btn_checkout_text`, `btn_send_quote_text` | iguales | design | `updateDesign` | `project_settings` | runtime por búsqueda de texto | Parcial | Pasar variables directas | Idiomas/textos distintos |
| Sistema | Textos catálogo | `txt_no_results`, `txt_search_placeholder`, `txt_view_more`, `txt_all_cats` | iguales | design | `updateDesign` | `project_settings` | runtime por selectores | Parcial | Render directo | Cada estado vacío |
| Sistema | Login | `login_bg_type/color1/color2/bg_image/heading/subtitle` | iguales | design | `updateDesign` | `project_settings` | solo si existe DOM de login detectable | No reflejado en plantillas sin login | Definir módulo/login compartido | Login presente/ausente |
| Sistema | SEO | `seo_title`, `seo_description`, `seo_keywords` | iguales | design | `updateDesign` | `project_settings` | runtime inserta meta | Parcial | Render en servidor | HTML inicial/crawlers |
| Checkout | Esquema de campos | `checkout_fields` | JSON | design | `updateDesign` | `project_settings` | runtime/fallback | Parcial | Validación de JSON y componente común | Campos fijos/custom |

## Encabezado y menú (implementados pero no visibles)

| Sección | Opción | Input | Llave | Ruta | Controlador | Persistencia | Plantilla | Estado | Acción requerida | Prueba |
|---|---|---|---|---|---|---|---|---|---|---|
| Header | Fondo/texto | `header_bg_color`, `header_text_color` | iguales | header | `updateHeader` | `project_settings` | header compartido/CompuTienda | Duplicado y no visible | Integrar editor y eliminar duplicidad | Guardar desde una sola UI |
| Header | Hover/activo | `header_hover_color`, `header_active_color` | iguales | header | `updateHeader` | `project_settings` | header V2; no todas productivas | No visible/parcial | Consumir en todas | Hover y ruta activa |
| Header | Fuente/tamaño | `header_font`, `header_font_size` | iguales | header | `updateHeader` | `project_settings` | header V2 | No visible/parcial | Contrato de header | 12–20 px |
| Header | Alto header/logo | `header_height`, `header_logo_height` | iguales | header | `updateHeader` | `project_settings` | CompuTienda/V2 | Duplicado | Unificar con Marca | Límites y responsive |
| Header | Archivo logo | `header_logo` | `header_logo_url` | header | `updateHeader` | archivo + setting | header V2; CompuTienda prioriza `logo_url` | No visible/duplicado | Una llave canónica | Cambiar con `logo_url` existente |
| Header | Sticky/búsqueda/contacto/carrito | `header_sticky`, `header_show_search/contact/cart` | iguales | header | `updateHeader` | `project_settings` | V2; productivas no uniformes | No visible/parcial | API compartida | 4 toggles x 3 dispositivos |
| Header | Estilo tablet/móvil | `header_tablet_style`, `header_mobile_style` | iguales | header | `updateHeader` | `project_settings` | header V2 | No visible/parcial | Integrar en productivas | Breakpoints |
| Menú | Texto/destino | `label`, `destination_type`, `destination_id`, `url` | columnas | menu | `storeItem/updateItem` | `store_menu_items` | solo CompuTienda lo consume directo | No visible/parcial | Header compartido para todas | Cada tipo de destino |
| Menú | Padre/orden | `parent_id`, payload reorder | columnas | menu | `updateItem/reorder` | `store_menu_items` | solo consumidores del menú | No visible/parcial | Integrar UI y vistas | Un nivel, ciclos, drag |
| Menú | Target/activo/dispositivo | `target`, `is_enabled`, `show_*` | columnas | menu | CRUD | `store_menu_items` | header compartido | No visible/parcial | Aplicar clases en todas | PC/tablet/móvil |
| Estructura | Activar V2 | `enabled`, `confirm` | `storefront_structure_v2` | publish | `publishStructure` | `project_settings` | ignorado por plantillas productivas en `catalog()` | No visible/confuso | Redefinir activación | Plantilla productiva/no productiva |

## Constructor de Inicio (`store_sections`)

Todas las filas usan ruta `home`, controlador `StoreExperienceController::saveHomeSection` y persistencia `store_sections.content/draft_content`, salvo las opciones comunes indicadas.

| Sección | Opción | Input | Llave | Persistencia | Plantilla | Estado | Acción requerida | Prueba |
|---|---|---|---|---|---|---|---|---|
| Común | Mostrar sección | `is_enabled` | publicado/draft | `is_enabled`, `draft_is_enabled` | renderer canónico/normalización CompuTienda | OK parcial | Garantizar soporte por plantilla | Borrador vs publicar |
| Común | Orden | `sort_order`, `order[]` | publicado/draft | `sort_order`, `draft_sort_order` | canónico; CompuTienda lo normaliza | Parcial | Una sola interacción de guardado | Drag, guardar, publicar todo |
| Común | Publicar desde/hasta | `publish_from`, `publish_until` | fechas | solo columnas publicadas | consulta pública | Parcial | Añadir estado draft de fechas | Guardar borrador no debe publicar fecha |
| Común | PC/tablet/celular | `show_desktop/tablet/mobile` | flags | columnas publicadas incluso al guardar draft | CSS por sección | Defecto crítico | Añadir flags draft | Borrador no altera público |
| Banner | Tipo/autoplay/intervalo | `content[mode/autoplay/interval]` | `hero.*` | JSON | canónico; CompuTienda usa parcialmente su hero nativo | Parcial | Definir ownership del hero | single/slider/reduced motion |
| Banner | Título/descripción/imágenes | `content[single][title/body/desktop_image/mobile_image/uploads]` | `hero.single.*` | JSON + storage | canónico/runtime | OK parcial | Validar URL almacenada | PC/móvil/sin imagen |
| Banner | Botones | `content[single][primary/secondary_*]` | `hero.single.*` | JSON | canónico/runtime | OK parcial | Validar URL segura | Interno/externo/vacío |
| Banner | Slides 1–4 | `content[slides][i][...]` | `hero.slides[]` | JSON + storage | canónico | Parcial | UI para agregar/eliminar, no solo existentes | 0/1/4/12 |
| Beneficios | Encabezado | `content[title/body]` | `benefits.*` | JSON | canónico/CompuTienda | OK parcial | Contrato nativo | Vacío/con texto |
| Beneficios | Items 1–4 | `content[items][i][enabled/title/description/icon/image]` | `benefits.items[]` | JSON + storage | canónico | Parcial | Biblioteca/icono/upload más completa | 0–4, orden, imagen |
| Anuncios | Título/cantidad | `content[title/quantity]` | `announcements.*` | JSON | canónico | OK parcial | Integrar con promos nativas | 1–4 |
| Anuncios | Items | `content[items][i][enabled/title/description/image/button_text/url/starts_at/ends_at]` | `announcements.items[]` | JSON + storage | canónico | Parcial | Validar URL y coherencia de fechas por item | Activo/futuro/expirado |
| Categorías destacadas | Título | `content[title]` | `featured_categories.title` | JSON | canónico/CompuTienda | OK | Mantener | Cambio de plantilla |
| Categorías destacadas | Presentación | `content[display]` | `featured_categories.display` | JSON | canónico; CompuTienda usa settings `visual/style` distintos | Duplicado/parcial | Esquema canónico ampliado | images/icons/buttons |
| Categorías destacadas | Cantidad/selección | `content[limit]`, `content[category_ids][]` | `limit`, `category_ids` | JSON | canónico/CompuTienda | OK parcial | Incluir subcategorías si se requiere | Automático/manual/orden |
| Categorías destacadas | Forma/layout/columnas | sin control visible | `featured_categories_shape/style/columns/mobile_columns` | settings leídos solo por CompuTienda | No editable | Mover a JSON canónico | 3 formas, 4 layouts, responsive |
| Categorías destacadas | Visual/iconos/uploads/fit | sin control visible | `visual/items/image_fit` | settings leídos solo por CompuTienda | No editable | UI + validación + almacenamiento | Auto/imagen/icono/inicial |
| Categorías destacadas | Ver todo/conteo/vacías/carrusel | sin control visible | `show_all/all_text/show_count/hide_empty/mobile_carousel` | settings leídos solo por CompuTienda | No editable | UI y contrato común | Sin productos, móvil |
| Categorías destacadas | Colores/radio | sin control visible | `section_bg/card_bg/text_color/accent/radius` | settings leídos solo por CompuTienda | No editable | UI o retirar llaves privadas | Contraste y plantillas |
| Solo por hoy | Título/texto/finaliza/24 h | `content[title/body/ends_at/quick_24]` | `daily_offer.*` | JSON | canónico/CompuTienda | OK parcial | Zona horaria explícita | 24 h y fecha manual |
| Solo por hoy | Imagen/fondo/botón | `content[image/upload/background_color/button_text/button_url]` | `daily_offer.*` | JSON + storage | canónico/CompuTienda | OK parcial | URL segura/contraste | Con/sin imagen |
| Solo por hoy | Acción al expirar | `content[expired_action/expired_message]` | `daily_offer.*` | JSON | renderer canónico; CompuTienda no aplica toda la misma semántica | Parcial | Comportamiento único | hide/message después de cero |
| Descuentos | Título/cantidad/layout | `content[title/limit/layout]` | `discounts.*` | JSON | canónico/CompuTienda | OK parcial | Alinear alias nativo | grid/carrusel |
| Descuentos | Columnas | `content[columns_desktop/tablet/mobile]` | `discounts.*` | JSON | renderer canónico | Parcial | Aplicar en nativos | 1–6/1–4/1–2 |
| Descuentos | Selección/productos | `content[selection/product_ids][]` | `discounts.*` | JSON | canónico | OK | Mantener orden manual | Auto/manual/eliminado |
| Descuentos | Precios/badge | `content[show_old_price/show_current_price/show_percentage]` | `discounts.*` | JSON | canónico | Parcial | Aplicar en nativos | 8 combinaciones |
| Destacados | Título/cantidad/selección/productos | `content[title/limit/selection/product_ids][]` | `featured_products.*` | JSON | canónico; runtime los omite con `ownFooter` y CompuTienda usa nativo | Parcial | Un solo renderer | Cambio de plantilla |
| Destacados | Flechas/swipe/autoplay/segundos | `content[show_arrows/allow_swipe/autoplay/autoplay_seconds]` | `featured_products.*` | JSON | canónico; no uniforme en nativos | Parcial | Contrato común | Teclado/touch/reduced motion |
| Blog | Título/cantidad/ver todos | `content[title/limit/all_text/all_url]` | `blog.*` | JSON | canónico/CompuTienda | Parcial | URL segura y ruta estable | 2/3 items |
| Blog | Artículos | `content[items][i][enabled/image/tag/title/summary/date/button_text/url]` | `blog.items[]` | JSON + storage | Inicio y páginas V2 | Parcial | Modelo de posts separado | Slug, deshabilitado, fecha |

## Pop-up y páginas institucionales

| Sección | Opción | Input | Llave | Ruta | Persistencia | Plantilla | Estado | Acción requerida | Prueba |
|---|---|---|---|---|---|---|---|---|---|
| Pop-up | Activar/título/descripción | `is_enabled`, `title`, `description` | columnas | popup | `store_popups` | `x-public-popup` vía runtime | OK parcial | Validar plantilla sin runtime | On/off/vacío |
| Pop-up | Imagen/botón/URL | `image`, `button_text`, `button_url` | columnas | popup | storage + tabla | popup | Parcial | Validar URL del botón | Imagen previa/nueva |
| Pop-up | Fechas/delay/frecuencia | `starts_at`, `ends_at`, `delay_seconds`, `frequency` | columnas | popup | tabla | popup JS | OK parcial | Zona horaria y clock tests | session/day/always |
| Pop-up | Dispositivos | `show_desktop`, `show_mobile` | columnas | popup | tabla | popup CSS/JS | Parcial | Añadir tablet si es requisito | PC/tablet/móvil |
| Nosotros | Todos los campos | `title`, `body`, `history`, `mission`, `vision`, `values`, `team`, imágenes, CTA, toggles | JSON/columnas | page | `store_pages` | CompuTienda/V2 | No visible | Incluir partial y soportar todas productivas | Cada bloque |
| Contacto | Datos/formulario/mapa/redes | `title`, `body`, contacto, `map_url`, redes, requeridos, confirmación | JSON/columnas | page | `store_pages` | CompuTienda/V2 | No visible | Incluir partial y sanitizar/normalizar mapa | GET/POST/validación |
| Blog | Publicación de página | no existe toggle separado | sección `blog` + V2 | home | `store_sections` | `StorePageController` exige V2 | Parcial | Desacoplar ruta de V2 | V2 on/off, plantilla productiva |

## Precedencia actual de valores

1. En `prepararCatalogo()`: defaults de `ProjectTemplate` activo → sobrescritos por `project_settings`.
2. En las secciones: publicado usa `content`; vista previa sustituye por `draft_content` cuando `has_draft`.
3. En el hero/runtime: un `StoreSection hero` administrado tiene prioridad parcial sobre `hero_*`, pero el resultado depende de `ownFooter` y la plantilla.
4. En CompuTienda: varias opciones privadas `featured_categories_*`, `trust_*`, `promo_*`, `section_*` tienen prioridad sobre el contenido canónico.

Esta precedencia no está presentada al usuario y no es uniforme entre vistas.

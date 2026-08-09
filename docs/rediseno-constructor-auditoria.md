# Rediseño del Diseñador → Constructor Guiado de Tiendas
## Fase 1 — Auditoría y propuesta (sin cambios de código)

**Fecha:** 04/08/2026 · **Autor:** Análisis técnico-UX sobre el sistema real (rama `feature/designer-redesign`)
**Estado:** PENDIENTE DE APROBACIÓN — ningún archivo de sistema fue modificado para este documento.

---

## 1. Diagnóstico de la experiencia actual

**Síntoma central: tres diseñadores conviviendo.**

| Superficie | Ruta | Estado |
|---|---|---|
| Diseñador legado ("Diseño") | `/bixoadmin/settings/design?s=plantilla\|constructor` | El que usa el operador hoy. Monolito de ~4.000 líneas |
| Diseñador visual nuevo | `/bixoadmin/settings/designer` | Shell de 3 columnas (fases A-C), funcional pero incompleto |
| Experiencia/constructor Inicio | dentro de `?s=constructor&cv=inicio` (home-builder) | Donde realmente se editan contenidos de secciones |

**Problemas confirmados en el código:**
- **Navegación triple**: tabs `s=` (Plantillas/Constructor) + subtabs `cv=` (Marca, Navegación, Inicio, Portada, Catálogo, Checkout, Sistema, Páginas) + el nuevo Diseñador aparte. El usuario ya se perdió entre ellas (quejas registradas: duplicado de tamaño de logo, "me manda al inicio al guardar").
- **Formularios kilométricos**: el subtab Inicio (home-builder) concentra 8 componentes con todos sus campos en una sola página con scroll infinito.
- **Estados de guardado inconsistentes**: unos formularios hacen POST+redirect (perdían posición; mitigado con `dzRestore` en sessionStorage), el Diseñador nuevo hace fetch con estados clean/dirty/saving/published, y los toggles de sección publican al instante. Tres modelos mentales de guardado.
- **Sin vista previa permanente** en el legado; el nuevo tiene un mock estático (no la tienda real).
- **Sin noción de progreso**: nada responde "¿qué me falta para publicar?".
- **Draft vs publicado invisible**: `store_sections` tiene columnas `draft_*`, pero ninguna UI muestra la diferencia; ya causó el bug "activo en panel, invisible en tienda".

**Tiempo real estimado hoy para armar una tienda completa: 2-3 días** de un operador entrenado (evidencia: demo TECSIST). El objetivo de 5 horas exige eliminar la búsqueda de opciones, no las opciones.

---

## 2. Inventario de funcionalidades existentes

**Identidad/Apariencia:** logo (subida + altura 20-300px), favicon, colores primario/secundario/fondos por sección, tipografía (`font`), radios de botón, plantilla activa (3 soportadas + galería clásica ~20), plantillas propias del proyecto (crear desde ajustes, aplicar, eliminar).
**Encabezado/menú:** logo, colores header (`header_bg_color`, `header_text_color`), sticky (`header_sticky`), buscador (con predictivo), teléfono comercial, carrito, menú configurable (ítems: inicio/tienda/categoría/página/link, orden, visibilidad), mega-menú de categorías, barra superior (announcement: texto, color, fondo, tamaño, alineación, ancho, visible).
**Inicio (8 secciones `store_sections`):** hero (slides 1-3 PC/móvil, título, CTA×2, altura, posición), beneficios (4 ítems icono+texto, título, 3 estilos, carrusel móvil), anuncios/promos (3 slots imagen PC/móvil + título/subtítulo, slider/grid, autoplay, altura, orden), categorías destacadas (selección, visual imagen/icono/inicial, 5 estilos incl. showcase, columnas, colores, banda), oferta del día (título, subtítulo, imagen, color, fecha fin, acción al expirar, productos, 2 estilos incl. banda), productos con descuento (selección, columnas por dispositivo), productos destacados (selección, vista cards/editorial, título), blog (artículos on/off).
Por sección: activar/desactivar (publica al instante), orden (flechas + drag legado), visibilidad PC/móvil/tablet, ventana de publicación (`publish_from/until`), borrador/publicado.
**Catálogo:** layout, columnas PC/móvil, estilo de tarjeta (3), badges (4 textos), SKU/stock/valoraciones, vista rápida, filtros, orden, paginación AJAX, perfiles de catálogo (`/tienda/{perfil}`), botones producto (carrito/consultar/ambos + textos), precios mayoristas, revendedores (`/r/{slug}`).
**Venta:** modo tienda (directa/cotización), WhatsApp (número, país, mensaje), checkout (campos fijos+custom, dirección obligatoria), pagos manuales (Yape con número, bancos con datos), envío (costo, gratis desde), carrito vista propia, página de gracias, cupones, comprobante (SUNAT/APIsPERU en local).
**Páginas:** Nosotros (historia/misión/visión/valores/equipo/galería/CTA), Contacto (formulario configurable + mapa + redes), genéricas `/pagina/{key}`, legales (privacidad/términos con default profesional), Libro de Reclamaciones (formulario Ley 29571 + código + correos + estado en panel), blog.
**Otros:** popup promocional, WhatsApp flotante (posición), footer (columnas, copyright, crédito Eskala, métodos de pago), SEO básico (title/description), sitemap/robots por dominio, dominios custom, QR, asistente rápido 4 pasos (rubros→plantilla+paleta), subida de imágenes central (`settings.upload-logo`).

---

## 3. Mapa de rutas (las que tocará el rediseño)

```
GET  /bixoadmin/settings/design            settings.design          SettingsController@design (s=, cv=)
POST /bixoadmin/settings/design            settings.design.update   @update (whitelist ~170 claves)
POST /bixoadmin/settings/design/apply-template  settings.design.applyTemplate (JSON)
POST .../project-templates(+destroy/apply) plantillas propias
GET  /bixoadmin/settings/designer          settings.designer        @designer (nuevo shell)
POST /bixoadmin/settings/experience/home   settings.experience.home StoreExperienceController@saveHomeSection (contenido)
POST .../experience/home/{component}/state settings.experience.home.state  @sectionState (estado; creado en esta rama)
POST .../experience/home/publish-all       settings.experience.home.publishAll
POST .../experience/page                   settings.experience.page @page (Nosotros/Contacto/legales/genéricas)
POST .../experience/popup                  settings.experience.popup
PATCH .../experience/complaints/{id}       complaint.status
POST /bixoadmin/settings/upload-logo       settings.upload-logo (JSON {path})
POST /bixoadmin/settings/navigation        StoreNavigationController@update (menú/encabezado)
```
Público (no cambia): `public.catalog|shop|product|order|complaints|privacy|terms|page|about|contact|blog` + espejos en `DetectCustomDomain` (ramas explícitas).

## 4. Mapa de controladores

| Controlador | Responsabilidad | Riesgo al rediseñar |
|---|---|---|
| `SettingsController` | design(), designer(), update() whitelist, applyTemplate | Bajo: se REUSA tal cual (contrato name=) |
| `StoreExperienceController` | saveHomeSection (valida `content.*` por componente), sectionState, page, popup, publishAll | Bajo: endpoints canónicos ya probados |
| `StoreNavigationController` | encabezado + menú (validaciones logo 20-300 etc.) | Bajo |
| `StorePageController` | páginas públicas + legales + reclamos | No lo toca el rediseño |
| `PublicController` | render tienda, prepararCatalogo | No lo toca |
| `Catalog\ProductController` | CRUD productos (Etapa 4 lo consumirá) | Medio: faltan endpoints de métricas/masivos |

## 5. Mapa de vistas Blade

```
settings/design.blade.php               ~4.000 líneas — QUEDA COMO "modo legado" durante transición
settings/partials/home-builder.blade.php        contenido de las 8 secciones (fuente de verdad de campos)
settings/partials/store-navigation-builder.blade.php
settings/partials/catalog-profiles.blade.php
settings/partials/institutional-pages.blade.php
settings/partials/supported-template-selector.blade.php
settings/partials/home-section-fields.blade.php (referencia de campos por sección)
settings/designer/  (14 archivos, base del nuevo shell: index, topbar, structure-panel,
                     preview-panel, inspector-panel, styles, script, quick-setup, quick-script,
                     sections/{hero,header,benefits,ads,categories,featured-products,catalog,
                     product,footer,checkout,daily-offer})
```

## 6. Scripts involucrados
- `designerShell` (Alpine, designer/script.blade.php): selección, undo/redo (50 pasos), autosave por sección (publica al instante), guardado dual (settings → `settings.design.update`; estado → `home.state`), subida de imágenes, flechas de orden.
- `quickSetup` (quick-script): rubros→presets, paletas, aplicar plantilla. **Semilla directa de la Etapa 1 y de Presets.**
- design.blade.php inline: tabs cv, scroll-restore `dzRestore`, applyTemplate fetch, orden drag del home-builder (`data-hb-order-list`), toggle autopublish.
- Sin build de Vite propio: todo Alpine + JS inline (el rediseño debe seguir así o introducir bundle aparte — recomendación: seguir inline/Alpine).

## 7. Estilos involucrados
- `designer/styles.blade.php`: design tokens del shell (`--dz-*`), 3 columnas, nodos, toggles, wizard. **Base del design system del constructor.**
- design.blade.php `<style>`: constructor-nav/panel, hb-*.
- Admin shell: layouts/app (sb-bixo sidebar, bx-header) — el constructor vive DENTRO de ese shell o a pantalla completa como el designer actual (`.dz-root` position:fixed). Recomendación: pantalla completa (como hoy el nuevo).

## 8. Endpoints de guardado (contrato que se conserva)
1. `settings.design.update` — POST masivo de claves whitelist (~170). Acepta parcial.
2. `settings.experience.home` — contenido por componente (`content[...]` validado).
3. `settings.experience.home.state` — SOLO estado (enable/orden/visibilidad), action draft|publish.
4. `settings.experience.page` — páginas (key arbitraria a-z0-9-).
5. `settings.upload-logo` — imágenes (type=clave), responde `{path}`.
6. `settings.design.applyTemplate` — cambia plantilla preservando datos.
7. `StoreNavigationController@update` — menú + encabezado.
**El constructor nuevo NO crea endpoints de escritura nuevos para lo existente; solo faltarían lectura/métricas de catálogo y checklist (ver §20).**

## 9. Tablas y modelos
`projects`, `project_settings` (key/value — corazón del diseño), `store_sections` (page/component/variant/content JSON/sort/is_enabled/show_*/publish_*/draft_*), `store_pages`, `store_menus`+`store_menu_items`, `store_popups`, `project_templates`, `complaints`, `contact_messages`, `categories`, `products`(+`product_images`), `orders`, `catalog_profiles` (perfiles), `reseller_*`.
Servicios canónicos (se reusan): `StorefrontContextBuilder/Context`, `StoreSectionWriteService`, `ProjectSettingWriteService`, `StorePageWriteService`, `StorefrontSections::ensure/defaults`, `StorefrontNavigation`, `CatalogTemplates`, `DesignerIcons`.

## 10. Variables de configuración
Inventario congelado en `scripts/designer_contract.json` (**161 claves** + aliases del builder). Familias: identidad (logo/colores/fuente), header_*/announcement_*, hero_*, trust_*, promo_*, featured_categories_*, flash_sale_*, catalog_*, btn_*, product_button_mode, store_mode/quote_*, payment_*/shipping_*, footer_*, seo_*. Los ALIASES legacy se resuelven en `StorefrontContextBuilder::resolveSettings` — cualquier UI nueva escribe la clave canónica y el alias sigue leyéndose.

## 11. Funcionalidades duplicadas (a unificar)
| Duplicidad | Dónde | Resolución en el constructor |
|---|---|---|
| Orden de secciones | drag en home-builder + flechas en designer | Un solo lugar: lista de bloques Etapa 3 |
| Estilo/colores de sección | "Portada" (estilos) vs "Inicio" (contenido) | Un bloque = una tarjeta con TODO |
| Plantilla | Tab Plantillas + wizard + selector soportadas | Etapa 2 (rápido) + galería en avanzado |
| Texto botón carrito | catálogo + producto (ya apuntan a un lugar) | Etapa 2 → Botones |
| WhatsApp | quote_whatsapp, contact_whatsapp, store_whatsapp, project.whatsapp | Un campo en Etapa 1 que escribe la canónica |
| Título de sección destacados | catalog_section_title vs títulos de content | Un solo campo por bloque |
| Guardar | 3 modelos (redirect, fetch, instantáneo) | Autosave único con estados visibles |

## 12. Componentes con nombres inconsistentes (nombre oficial propuesto)
| Nombres actuales | Oficial |
|---|---|
| Hero / Banner principal / Portada-hero | **Slider principal** |
| Anuncios / Promociones / Banners | **Anuncios** |
| Barra de anuncio / topbar / announcement | **Barra superior** |
| Oferta del día / Solo por hoy / flash_sale | **Oferta con contador** |
| Beneficios / trust bar / Compra con confianza | **Beneficios** |
| Constructor visual / Experiencia / Diseño | **Constructor** |
| Portada vs Inicio | **Página de inicio** |

## 13. Riesgos de compatibilidad
1. **Tiendas legacy sin filas de secciones** → fallback "mostrar todo" (`hasSectionRegistry`, ya endurecido). El constructor debe `ensure()` al abrir.
2. **Claves alias** (banner1_* → promo_*): escribir SIEMPRE canónicas; no borrar aliases.
3. **Plantillas clásicas no soportadas** (ella, nordic…): el constructor muestra su galería en Avanzado; sus tiendas siguen funcionando sin el preview enriquecido.
4. **Draft/publicado**: publishAll con drafts nulos crashea (visto) — el constructor usa sectionState por sección.
5. **Dominios custom**: rutas públicas nuevas requieren rama explícita en `DetectCustomDomain` (pathInfo cacheado).
6. **OPcache/producción**: todo deploy de PHP requiere reload FPM (procedimiento ya establecido).
7. **Permisos**: `isOwnerOrSuper` controla Plantillas y páginas; miembros no owner ven solo Constructor (mantener).
8. **`?preview=1`**: el preview real usa sesión del admin — el iframe debe ir con misma sesión (mismo dominio panel) apuntando a `previewStorefront`.

## 14. Nueva arquitectura de información
```
CONSTRUCTOR (pantalla completa, /bixoadmin/settings/builder)
├─ Barra superior: ← Tiendas · Nombre · ▓▓▓░ 68% · Guardado 14:32 · ↶ ↷ · [PC|Móvil] · Vista previa · PUBLICAR
├─ Etapas (única navegación):
│  1 Datos del negocio     (15 min)  ● completo
│  2 Apariencia            (20 min)  ◐ 3 pendientes
│  3 Página de inicio      (45 min)  ○
│  4 Catálogo              (2 h 30)  ◐ 12 sin foto
│  5 Venta y operación     (45 min)  ○
│  6 Revisar y publicar    (25 min)  🔒 hasta resolver críticos
│  ⚙ Ajustes avanzados (fuera del flujo)
├─ Panel central: SOLO la tarea actual (formularios cortos, revelado progresivo,
│  tarjetas seleccionables, 1 acción primaria: "Continuar →")
└─ Vista previa: iframe real (previewStorefront) PC/Móvil, live tras autosave,
   click en sección → selecciona su tarjeta, pantalla completa
```
Modo **Rápido** (default) / **Experto** (muestra los grupos avanzados colapsados). Conmutador en barra superior.

## 15. Flujo completo del usuario
1. Entra → si tienda nueva: Etapa 1 con wizard de rubro (aplica preset). Si existente: aterriza en la última etapa incompleta ("Continuar donde quedaste").
2. Cada etapa: completa lo esencial → "Continuar" (autosave ya guardó todo) → la barra de progreso sube.
3. En cualquier momento: preview live a la derecha; Publicar siempre visible (bloqueado si hay críticos, con tooltip "2 pendientes críticos").
4. Etapa 6: checklist automático → Corregir (deep-link a la etapa/campo) → Publicar → pantalla de éxito con URL + QR + compartir WhatsApp.

## 16. Wireframe textual — Escritorio (≥1280px)
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ← Tiendas │ TECSIST  ▓▓▓▓▓▓░░ 72% │ ✓ Guardado 14:32 │ ↶ ↷ │ 🖥 📱 │ 👁 │ PUBLICAR │
├───────────────┬──────────────────────────────────┬───────────────────────────┤
│ ① Datos       │  ETAPA 3 · PÁGINA DE INICIO      │      [🖥][📱]  ⛶          │
│   negocio  ✓  │  Arma tu portada con bloques     │  ┌─────────────────────┐  │
│ ② Apariencia  │                                  │  │                     │  │
│   3 pend.  ◐  │  ┌─ ⠿ Slider principal ── ✓ ─┐   │  │   IFRAME TIENDA     │  │
│ ③ Inicio    ← │  │ [miniatura] 3 imágenes     │   │  │   (previewStore)    │  │
│   45 min      │  │ [Editar] [👁PC ✓][📱 ✓] ⏻ │   │  │   actualiza al      │  │
│ ④ Catálogo    │  └────────────────────────────┘   │  │   autosave          │  │
│   12 sin foto │  ┌─ ⠿ Categorías ────── ◐ ──┐    │  │                     │  │
│ ⑤ Venta       │  │ Falta: elegir 4 categorías │   │  │                     │  │
│ ⑥ Publicar 🔒 │  └────────────────────────────┘   │  └─────────────────────┘  │
│ ───────────   │  + Agregar sección                │   Bloque activo:          │
│ ⚙ Avanzado    │  [Aplicar estructura recomendada] │   "Categorías" resaltado  │
│               │            [Continuar a Catálogo →]                          │
└───────────────┴──────────────────────────────────┴───────────────────────────┘
```
Al pulsar **Editar** en un bloque: el panel central muestra SOLO ese bloque (contenido + diseño + variante juntos), con "← Volver a la lista".

## 17. Wireframe textual — Tablet (768-1279px)
```
┌────────────────────────────────────────────────────┐
│ ← │ TECSIST 72% │ ✓ 14:32 │ 👁 Preview │ PUBLICAR │
├──────┬─────────────────────────────────────────────┤
│ ①✓  │  ETAPA 3 · PÁGINA DE INICIO                 │
│ ②◐  │  (lista de bloques, ancho completo)         │
│ ③←  │                                             │
│ ④   │  [Preview = botón 👁 → panel deslizante     │
│ ⑤   │   derecho superpuesto, cerrable]            │
│ ⑥🔒 │                                             │
└──────┴─────────────────────────────────────────────┘
Navegación lateral solo iconos+estado; nombre en tooltip.
```

## 18. Wireframe textual — Móvil (<768px)
```
┌──────────────────────────────┐
│ ← TECSIST        72% ✓14:32 │
│ ● ● ◉ ○ ○ ○   Etapa 3 de 6  │
├──────────────────────────────┤
│ PÁGINA DE INICIO             │
│ (bloques apilados, 1 col,    │
│  editar abre pantalla propia)│
│                              │
├──────────────────────────────┤
│ [👁 Ver tienda]  (flotante)  │
│ ┌──────────┬───────────────┐ │
│ │  ← Atrás │  Continuar →  │ │  barra inferior fija
│ └──────────┴───────────────┘ │
└──────────────────────────────┘
Preview: modal a pantalla completa con toggle PC/Móvil.
```

## 19. Componentes reutilizables (ya existen)
Del shell nuevo: tokens `--dz-*`, `dz-btn/*`, `dz-field/*`, `dz-seg`, `dz-switch-row`, `dz-node-toggle`, `dz-media-drop` (subida), `dz-quick-*` (wizard→Etapa 1), status dot (autosave), undo/redo, `DesignerIcons` (23 SVG propios), device-switch, `dzRestore`. Del legado: home-section-fields (mapa de campos), supported-template-selector, orden drag (se reemplaza por lista de bloques), validaciones back (se conservan).

## 20. Componentes nuevos requeridos
| Componente | Tipo | Necesita backend nuevo |
|---|---|---|
| `builder-shell` (4 zonas + etapas) | Blade+Alpine | No |
| `stage-nav` (etapa: nº, estado, %, pendientes, tiempo) | Blade+Alpine | Cálculo de progreso (ver abajo) |
| `block-card` (bloque de inicio: miniatura, estado, acciones) | Blade+Alpine | No (usa home + home.state) |
| `preview-frame` (iframe previewStorefront + resaltado postMessage) | Alpine | Marcadores `data-store-native-section` ya existen |
| `progress-service` | PHP | **Sí**: `GET settings/builder/progress` (JSON: % por etapa, pendientes) — solo lectura |
| `checklist-publicación` | PHP+Blade | **Sí**: `GET settings/builder/checklist` (crítico/advertencia/recomendación) — solo lectura |
| `catalog-metrics` + acciones masivas | PHP | **Sí**: `GET metrics` + `POST bulk` (categoría/precio/estado) |
| `preset-service` (rubros) | PHP | Extiende quickSetup; presets en config/archivo |
| `copy-store` (copiar selectivo) | PHP | **Sí**: `POST settings/builder/copy-from/{project}` (settings elegidos + secciones + menú) — transaccional, respeta permisos owner |
| autosave global (debounce por campo → endpoints existentes) | Alpine | No |

## 21. Propuesta de design system (propio, no reciclado)
- **Tokens**: los `--dz-*` actuales (tinta #1a1d24, acento índigo #4f46e5, ok #0f9d6b, warn #c2410c, radios 8-14px, sombras suaves). Tipografía system-ui. Iconos: `DesignerIcons` (trazo 1.7 redondeado) ampliado (~15 más: etapas, métricas, checklist).
- **Componentes**: botón (primario/fantasma/peligro, 44px), campo (label 12px/700, focus ring acento), tarjeta seleccionable (borde 2px acento al elegir), switch (34×19), segmento, badge de estado (●◐○🔒), barra de progreso, toast, drawer móvil, modal.
- **Reglas**: contraste ≥4.5:1, focus-visible siempre, transiciones 150-300ms, `prefers-reduced-motion`, targets ≥44px, 1 acción primaria por pantalla, microcopy en español simple ("Sube tu logo", nunca "upload asset").

## 22. Plan de implementación por fases
| Fase | Alcance | Riesgo |
|---|---|---|
| **B0** | Congelar contrato (hecho: designer_contract.json) + progress/checklist services (solo lectura) + ruta `/settings/builder` detrás de flag; legado intacto | Nulo |
| **B1** | Shell 4 zonas + barra superior + etapas + autosave global + preview iframe real | Bajo |
| **B2** | Etapas 1-2 (Datos del negocio + Apariencia) reusando update/upload/applyTemplate + presets v1 | Bajo |
| **B3** | Etapa 3: lista de bloques unificada (contenido+diseño+variantes por bloque) | Medio (UI grande, endpoints ya probados) |
| **B4** | Etapa 4: métricas + accesos "corregir sin foto/precio" + masivas | Medio (endpoints nuevos) |
| **B5** | Etapa 5: vender/cobrar/entregar/confianza con revelado progresivo | Bajo |
| **B6** | Etapa 6: checklist + bloqueo por críticos + publicar + éxito | Bajo |
| **B7** | Modo experto + panel Avanzado global + copiar tienda | Medio |
| **B8** | QA total (tests por etapa), beta con TECSIST, switch de entrada del menú a `/builder`, legado queda como "modo clásico" 1-2 meses | — |
Cada fase: local → tests → VPS (procedimiento actual). Estimación: B0-B2 un bloque de trabajo; B3-B4 el grueso; B5-B8 incrementales.

## 23. Archivos que se modificarían
- `routes/web.php` (rutas builder + progress/checklist/metrics/bulk/copy)
- `app/Http/Controllers/SettingsController.php` (método builder() + inyección de servicios de progreso)
- `resources/views/layouts/app.blade.php` (solo entrada de menú "Constructor")
- `app/Support/DesignerIcons.php` (iconos nuevos)
- `database/seeders/PermissionsSeeder.php` (si se decide permiso propio)
- Nada de plantillas públicas ni endpoints de guardado existentes.

## 24. Archivos que se crearían
```
app/Storefront/BuilderProgress.php          (progreso + pendientes por etapa)
app/Storefront/PublishChecklist.php         (validaciones crítico/advertencia/recomendación)
app/Storefront/StoreCopyService.php         (copiar selectivo entre tiendas)
app/Storefront/StagePresets.php             (presets por rubro)
app/Http/Controllers/StoreBuilderController.php
resources/views/settings/builder/{index,topbar,stage-nav,preview,stages/{negocio,apariencia,
  inicio,catalogo,venta,publicar},blocks/{slider,categorias,anuncios,destacados,beneficios,
  oferta,descuentos,blog,barra-superior,footer},advanced.blade.php,styles,script}
tests/Feature/Builder{Progress,Checklist,Stage*,CopyStore,CatalogBulk}Test.php
docs/rediseno-constructor-auditoria.md      (este documento)
```

## 25. Criterios de aceptación (verificables)
1. Tienda demo desde cero (preset rubro + 30 productos Excel) publicada en **≤5 h** cronometradas.
2. **Una** navegación principal; cero subtabs paralelos; test que falla si aparece una segunda nav.
3. Cada configuración vive en **un** lugar (auditoría de claves duplicadas en UI = 0).
4. Nombre único por componente (glosario §12 aplicado en UI y código nuevo).
5. Progreso % + pendientes visibles en todas las etapas (test de BuilderProgress).
6. Preview PC/móvil live (iframe real, refresco tras autosave ≤2 s).
7. Autosave con 5 estados visibles; cero botones "Guardar" por sección en modo rápido.
8. Modo rápido/experto conmutables sin perder datos.
9. Presets por 10 rubros aplican plantilla+paleta+secciones+categorías demo.
10. Copiar de otra tienda: apariencia/inicio/menú/venta seleccionables, con confirmación.
11. Checklist detecta: sin logo, sin WhatsApp, producto sin foto/precio, SKU duplicado, sin método de pago, sin entrega, legales inactivas, hero vacío.
12. Críticos bloquean Publicar; advertencias piden confirmación.
13. Tiendas existentes: **byte a byte iguales** en el público tras activar el builder (suite de regresión actual en verde + snapshot HTML de las 7 tiendas).
14. Todo endpoint de guardado usado es de la lista §8 (sin escrituras paralelas).
15. Usable con teclado; contraste AA; targets 44px (checks automáticos en tests de vista).

---
**Siguiente paso:** con tu aprobación (o ajustes a §14-§18), arranco B0+B1.

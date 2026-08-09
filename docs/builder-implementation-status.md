# Constructor guiado — Estado de implementación

**Rama:** `feature/guided-builder` (worktree `C:/Users/luich/AppData/Local/Temp/avan-designer`)
**Spec:** `docs/builder-spec.md` (copia de docs/catalogo.md del proyecto principal) · **Auditoría:** `docs/rediseno-constructor-auditoria.md` (commit 588b304, verificada vigente el 04/08/2026 — sin diferencias relevantes; se añadieron después de la auditoría: variantes showcase/banda, flechas de orden, páginas legales, crédito Eskala, todo ya recogido aquí).

## Fase actual: TODAS LAS FASES COMPLETAS (B0-B8) · DESPLEGADO · TECSIST en beta

| Fase | Estado | Commit |
|---|---|---|
| B0 Fundación | ✅ completa | f2e2900 |
| B1 Shell + verticales | ✅ completa | 23008cc |
| B2 Negocio + Apariencia | ✅ completa | 5907e02 |
| B3 Bloques del inicio | ✅ completa | 68497b4 |
| B4 Catálogo | ✅ completa | fd384a2 |
| B5 Venta y operación | ✅ completa | 23695b4 |
| B6 Revisar y publicar | ✅ completa | ddc733e |
| B7 Experto/presets/copiar | ✅ completa | fcfe86f |
| B8 QA + despliegue | ✅ completa | (este commit) |

**Informe final:** `docs/builder-final-report.md` · **Despliegue:** `docs/builder-deployment-plan.md` (ejecutado 04/08/2026: backup OK, migración OK, 7/7 tiendas 200, snapshots 14/14 idénticos, TECSIST beta).

## B2 — terminado
- Etapa 1 "Datos del negocio" completa: nombre comercial (seo_title), rubro (10 presets), logo (upload → clave en borrador), WhatsApp canónico (quote_whatsapp), correo, dirección, ciudad, moneda (currency_symbol), tipo de venta (store_mode) — TODO a borrador.
- Rubro → recomendaciones automáticas (plantilla, paleta, tipografía, textos iniciales, modo de venta) aplicadas al borrador sin pisar lo que el usuario ya escribió; categorías sugeridas informativas. `StagePresets` (10 rubros, fuente única mantenible — base directa de B7).
- Etapa 2 "Apariencia" unificada: plantilla en tarjetas visuales (3 soportadas; galería clásica enlazada), logo + favicon, colores + 6 paletas + **generar paleta desde el logo** (canvas client-side), tipografía (6 fuentes), forma de botones (btn_shape), encabezado (colores + sticky), barra superior (texto/colores; revelado progresivo), footer copyright; avanzado colapsable (alto de logo, tamaño/alineación/ancho de barra) ligado al modo Experto.
- Preview respeta `catalog_template` EN BORRADOR (cambias plantilla y la ves sin publicar) — test verde con público intacto.
- Normalización histórica: ecommerce/direct ahora aceptan store_mode `quote` además de `quote_only` (una sola clave canónica, retrocompatible).
- Tests: `BuilderStageBusinessTest` + `BuilderStageAppearanceTest`; suite Builder **17/17**; regresión total 277 tests, 16E/16F = línea base.

## B1 — terminado
- Shell de 4 zonas (`settings/builder/{index,topbar,stage-nav,preview,styles,script}`): barra superior (regresar, nombre, progreso real, estado autosave con hora, deshacer/rehacer con Ctrl+Z/Y, PC/móvil, Rápido/Experto, vista previa completa, Modo clásico, Publicar bloqueado por críticos con contador), navegación única de etapas (número/estado/%/pendientes/minutos/severidad), panel central por etapa con Atrás/Continuar, preview IFRAME REAL del borrador.
- Preview del borrador: ruta `GET settings/builder/preview` → `prepararCatalogo(preview=true, overlay de drafts)` + variable `builderSettingsOverlay` (computienda re-lee settings publicados; el overlay del Constructor va al final SOLO en preview — hallazgo clave, público intacto). Puente postMessage inyectado solo en preview (ready/section-selected/highlight/set-device/clear) con validación de origen.
- Autosave real: cola con debounce 600 ms, número de secuencia (descarta respuestas viejas), reencolado y reintento en error, estado offline, aviso beforeunload, estados clean/dirty/saving/saved/error/offline visibles.
- Undo/redo PERSISTENTES (cada paso re-guarda el borrador vía endpoint), límite 50.
- Flujo Publicar: 409 advertencias → modal de confirmación; éxito → modal con URL, abrir, copiar, compartir WhatsApp, versión y fecha.
- Responsive: ≥1280 4 zonas; tablet lateral compacta + preview drawer; móvil etapas arriba + barra inferior fija Atrás/Continuar + preview pantalla completa (FAB "Ver tienda"). `prefers-reduced-motion`, focus-visible, targets 44px, progressbar con ARIA.
- **Pruebas verticales de la spec EN VERDE** (`BuilderVerticalTest`): color principal y categorías — editar→borrador (público intacto)→preview muestra borrador→publicar→público actualizado.
- `BuilderShellTest` (zonas+contratos), suite Builder 12/12; regresión total 272 tests con 16E/16F = línea base exacta.

## B0 — terminado
- Migración reversible `2026_08_04_000001_create_builder_draft_and_publication_tables` (tablas `builder_drafts`, `store_publications`; `down()` seguro). **NO ejecutada en producción** (se ejecutará en el deploy de la fase que la necesite, vía procedimiento documentado).
- `BuilderAccess` (flag disabled|beta|enabled por proyecto, setting `builder_mode`).
- `BuilderRuleRegistry` (fuente única: 17 reglas iniciales, 6 etapas, severidad/peso/target/blocks_publish, contexto agregado sin N+1).
- `BuilderProgress` (porcentaje ponderado global y por etapa, estados pending|in_progress|complete|warning|blocked, minutos restantes).
- `PublishChecklist` (mismas reglas; críticos bloquean, advertencias piden confirmación).
- `BuilderDraftService` (drafts de settings, effectiveSettings para preview, publish transaccional con lock+snapshot+versión, rollback).
- `StoreBuilderController` + rutas: `GET settings/builder`, `GET …/progress|checklist|catalog/metrics`, `POST …/draft/settings`, `POST …/publish`. Proyecto SIEMPRE desde `active_project`; telemetría `Log::info('builder',…)` (builder_opened, publish_started/blocked/published/failed).
- Vista B0 `settings/builder/index` (esqueleto verificable con progreso/checklist reales; shell completo en B1).
- Tests `tests/Feature/Builder/BuilderFoundationTest` — **9/9 verdes** (flag, permisos, aislamiento con project_id ajeno, progreso ponderado, métricas de catálogo, borrador que no toca producción, publicación bloqueada→promovida→rollback).
- Snapshots base: `tests/Snapshots/baseline/*.html` + `MANIFEST.txt` (14 páginas de las 7 tiendas, HTML normalizado: csrf/tokens/fechas fuera).
- Línea base de suite: 260 tests, 16 E / 16 F preexistentes (entorno), sin regresiones nuevas.

## Endpoints creados (todos de lectura o de borrador; ninguno escribe producción salvo publish)
Ver tabla en `docs/builder-draft-publication-architecture.md`.

## Decisiones técnicas
1. Borrador = tabla genérica `builder_drafts` + columnas draft existentes de secciones (detalle y porqués en builder-draft-publication-architecture.md).
2. Flag por proyecto en `project_settings` (sin migración, sin .env).
3. Marcador de secciones del preview: se reutiliza `data-store-native-section` existente (no se toca el render público).
4. Telemetría v1 en log estructurado (canal laravel); tabla dedicada solo si B8 lo exige.
5. El modo clásico conserva su comportamiento de escritura directa; el borrador es exclusivo del Constructor.

## Riesgos abiertos
- `publishAll` legado crashea con drafts nulos — el Constructor NO lo usa (promoción propia con COALESCE).
- La migración debe correr en producción antes de activar el flag en cualquier tienda real.
- Preview con overlay de settings requiere punto de extensión en `prepararCatalogo` (B1) — hacerlo sin tocar el camino público (parámetro opcional).

## Para continuar si la sesión se corta
1. `cd C:/Users/luich/AppData/Local/Temp/avan-designer` (rama feature/guided-builder).
2. Leer `docs/builder-spec.md` (spec completa) + este documento.
3. B3 pendiente (siguiente): Etapa 3 — lista única de bloques (Barra superior, Encabezado, Slider principal, Beneficios, Anuncios, Categorías, Oferta con contador, Descuentos, Destacados, Blog, Footer) con tarjeta por bloque (editar contenido+diseño+variante+visibilidad+orden+PC/móvil en un solo editor), guardado de contenido de sección a BORRADOR vía settings.experience.home (action=draft), resaltado del bloque en preview vía postMessage, Aplicar estructura recomendada, Agregar sección. Reusar los inspectores del designer (sections/*) como base de los editores. Luego B4-B8. Antiguo plan B2: Etapa 1 completa (nombre comercial, rubro con presets → recomendaciones, logo con subida, WhatsApp canónico, correo, dirección, ciudad, moneda, tipo de venta) y Etapa 2 (plantilla tarjetas visuales, marca, colores rápido/experto, tipografía, encabezado, barra superior, footer, favicon; paleta desde logo). Reusar settings.upload-logo para imágenes (adaptador Media) y quick-script rubros como semilla de presets. Después B3 (bloques), B4 (catálogo), B5, B6 (checklist UI + publicación), B7, B8. Antiguo plan B1: shell 4 zonas (`settings/builder/{index,topbar,stage-nav,preview,styles,script}`), preview iframe real con overlay de borrador (extender `previewStorefront`/`prepararCatalogo` con settings efectivos SOLO en modo builder-preview), autosave Alpine (contrato en architecture doc), prueba vertical 1 (color principal: editar→borrador→preview→undo/redo→publicar→verificar público) y vertical 2 (activar/desactivar categorías vía draft de sección→publicar), accesibilidad (teclado, 44px, AA), responsive (drawer preview en tablet/móvil), entrada "Modo clásico".
4. Tests con `C:\xampp\php\php.exe vendor\bin\phpunit tests\Feature\Builder\`.
5. Deploy: `deploy_merge.py` (copiar antes worktree→avan-prodsnap) + reload FPM; **correr la migración** con backup previo (procedimiento en builder-deployment-plan.md cuando toque).

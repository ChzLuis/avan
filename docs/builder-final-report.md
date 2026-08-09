# Constructor Guiado de Tiendas — Informe Final (B0→B8)

**Fecha:** 04/08/2026 · **Rama:** `feature/guided-builder` · **Spec:** `docs/builder-spec.md` · **Auditoría base:** commit `588b304`

## 1. Resumen ejecutivo
Los tres diseñadores se unifican en **Constructor** (`/bixoadmin/settings/builder`): flujo guiado de 6 etapas con progreso real, autosave **a borrador** (el público nunca cambia hasta Publicar), preview del storefront real, checklist con bloqueo por críticos y publicación transaccional con versiones y rollback. El modo clásico queda intacto como respaldo. Desplegado a producción con **flag apagado por defecto** (beta activada solo en TECSIST) y **regresión perfecta**: las 7 tiendas públicas renderizan idéntico (14/14 snapshots normalizados).

## 2. Arquitectura implementada
4 zonas (topbar · etapas · panel · preview iframe real) sobre Blade+Alpine sin bundle nuevo. Servicios: `BuilderAccess` (flag), `BuilderRuleRegistry` (fuente única de reglas), `BuilderProgress`, `PublishChecklist`, `BuilderDraftService` (borrador+publicación), `StagePresets` (10 rubros), `StoreCopyService`. Controlador único `StoreBuilderController` siempre acotado a `active_project`.

## 3. Modelo borrador/publicación
Detalle en `docs/builder-draft-publication-architecture.md`. Resumen: settings → tabla `builder_drafts`; secciones → columnas `draft_*` existentes (endpoints canónicos con `action=draft`); Publicar = transacción con lock, snapshot del estado previo, promoción (settings + secciones con COALESCE), registro en `store_publications` (versión correlativa) y limpieza de borradores. `rollback(version)` restaura. Preview = `prepararCatalogo(preview=true, overlay)` + `builderSettingsOverlay` (computienda re-lee publicados; el overlay va al final SOLO en preview).

## 4-5. Rutas y endpoints creados
| Método | Ruta (`/bixoadmin/settings/builder…`) | Función |
|---|---|---|
| GET | `` | Shell (flag+auth+proyecto; 404 si disabled) |
| GET | `/progress` · `/checklist` · `/catalog/metrics` | Lectura JSON |
| GET | `/catalog/products?filter=` | Listas de corrección paginadas |
| POST | `/catalog/bulk` | Masivas transaccionales aisladas |
| POST | `/draft/settings` | Autosave a borrador |
| POST | `/publish` | Publicación transaccional (422 críticos / 409 confirmar advertencias) |
| GET | `/preview?view=` | Storefront real con borrador + puente postMessage |
| GET | `/copy/sources` · POST `/copy` (`dry_run`) | Copiar tienda a borrador |
Reutilizados sin duplicar: `settings.experience.home.state`, `settings.experience.home.save` (ahora responde JSON con `Accept: application/json`), `settings.upload-logo`, `settings.design.update` (clásico).

## 6. Servicios creados
Los 7 de §2 + puente postMessage inyectado solo en preview (origin-validado; marcador estable `data-store-native-section`, sin tocar render público).

## 7. Archivos modificados (existentes)
`routes/web.php` (+11 rutas), `StoreExperienceController` (respuesta JSON opcional), `PublicController::prepararCatalogo` (param overlay opcional, público intacto), `layouts/app.blade.php` (entrada de menú Constructor solo con flag; con flag, Diseño deja el menú y vive como "modo clásico" dentro del Constructor), plantillas: computienda (hook overlay preview), ecommerce/direct (aceptan `store_mode=quote` además de `quote_only` — clave canónica única).

## 8. Migraciones
`2026_08_04_000001_create_builder_draft_and_publication_tables` — crea `builder_drafts` y `store_publications`; **reversible** (`down()` solo dropea esas tablas). Ejecutada en producción tras backup verificado.

## 9. Feature flags
`project_settings.builder_mode = disabled|beta|enabled` por tienda (default disabled). beta=owner/superadmin. Activación/rollback por tinker sin tocar datos. Estado actual: **TECSIST (18) en beta; resto disabled**.

## 10. Reglas del checklist
17 reglas en `BuilderRuleRegistry` (6 etapas, severidad crítico/advertencia/recomendación, peso, target de corrección, `blocks_publish`). Progreso y checklist consumen exactamente el mismo registro (criterio 5 ✓).

## 11. Flujo de publicación
Checklist → críticos bloquean (422) → advertencias piden confirmación (409+modal) → transacción (lock, snapshot, promoción, versión) → refresco de progreso/checklist/preview → pantalla de éxito (URL, abrir, copiar, WhatsApp, versión, fecha).

## 12. Seguridad
Auth + `project.member` + flag; proyecto SIEMPRE de `active_project` (test: `project_id` ajeno en query ignorado); masivas y copia transaccionales y aisladas (ids/tiendas ajenas rechazadas — tests); validación de claves de borrador (`[a-z0-9_]{1,80}`); postMessage con validación de `origin` y tipos; sin borrado físico masivo; copiar nunca incluye productos/dominios/credenciales; CSRF en todos los POST; contenido de secciones sigue pasando por la validación canónica por componente.

## 13-14. Pruebas y regresión
- **Suite Builder: 29/29 verdes** (10 archivos: Foundation 9, Vertical 2, Shell 1, Business 3, Appearance 2, Home 3, CatalogBulk 3, Sales 3, CopyStore 2, EndToEnd 1).
- **Regresión completa: 289 tests — 16 E / 16 F = exactamente la línea base preexistente** (0 regresiones; +29 tests).
- **Tiendas existentes: 14/14 páginas idénticas** (7 tiendas × home+tienda, HTML normalizado por CSRF/tokens/fechas, hashes SHA-256 vs `tests/Snapshots/baseline/MANIFEST.txt`).
- E2E programático (spec 16 pasos): preset→identidad→slider→orden→catálogo (corregir sin precio)→pagos→envío→críticos→publicar→público verificado→móvil→modo clásico→retorno sin pérdida. **Verde.**

## 15-16. Accesibilidad y responsive
Focus-visible global, targets ≥44 px, `role/aria` en progreso, switches, diálogos y navegación, contraste AA de los tokens `--dz-*`, `prefers-reduced-motion`. Responsive: escritorio 4 zonas; tablet lateral compacta + preview drawer; móvil etapas horizontales + barra fija Atrás/Continuar + preview a pantalla completa (verificado por construcción + asserts de marcado; validación manual multi-dispositivo pendiente como trabajo no crítico §22).

## 17. Métricas instrumentadas
`Log::info('builder', …)` estructurado: builder_opened, builder_publish_started/blocked/published/failed, builder_bulk_action, builder_store_copied. (Tiempos por etapa/telemetría fina: §22.)

## 18. Commits
`f2e2900` B0 · `23008cc` B1 · `5907e02` B2 · `68497b4` B3 · `fd384a2` B4 · `23695b4` B5 · `ddc733e` B6 · `fcfe86f` B7 · (este commit) B8.

## 19. Procedimiento de despliegue ejecutado
`docs/builder-deployment-plan.md`. Registro: backup BD verificado (`/root/backup_builder_20260804_1424.sql.gz`, 111 K, "Dump completed") → 27 archivos vía deploy_merge (sintaxis OK) → `migrate --force` (tablas confirmadas en MySQL) → route/view:clear → reload FPM 8.1-8.5 → health login 200 → builder 302 anónimo / 404 con flag off → 7/7 tiendas 200 → snapshots 14/14 idénticos → log sin errores → TECSIST a beta.

## 20. Rollback
Por tienda: flag `disabled` (datos intactos). Publicaciones: `BuilderDraftService::rollback($project, $version)`. Código: `_release_backups/<stamp>/files/` + reload FPM. BD: `migrate:rollback --step=1` (solo 2 tablas nuevas) o restore del dump.

## 21. Riesgos conocidos
- Contenido profundo de algunos bloques (selección de productos de Destacados/Descuentos, artículos del Blog, fotos por categoría) aún se edita vía enlace al modo clásico (un solo lugar, sin duplicar).
- El modo clásico sigue publicando directo (comportamiento histórico documentado); convivencia esperada durante la transición.
- Con flag `enabled` para miembros no-owner, la etapa Apariencia muestra plantillas aunque el clásico las restrinja a owner (los endpoints reusados mantienen sus permisos reales).

## 22. Trabajo futuro no crítico
Editor de selección de productos dentro de los bloques; duplicar/eliminar bloques `custom_page`; importador Excel embebido (hoy enlaza al CRUD); presets con categorías/datos demo auto-creados (previa confirmación); telemetría de tiempos por etapa; pruebas manuales multi-dispositivo y lector de pantalla; snapshot visual (imágenes) además del DOM.

## 23. Evidencia de criterios de aceptación (26)
1 navegación única ✓ (etapas; test two-tabs no aplica al builder) · 2 sin duplicados ✓ (bloque=un editor; fijos enlazan a Apariencia) · 3 nombres oficiales ✓ (test `official_names`) · 4 progreso real ✓ (BuilderProgress+test) · 5 mismas reglas ✓ (registro único) · 6 autosave→borrador ✓ (tests aislamiento) · 7 preview=borrador ✓ (tests vertical/plantilla) · 8 publicación transaccional ✓ (lock+snapshot+test) · 9 undo/redo persistentes ✓ (re-guardan borrador) · 10 preview PC/móvil ✓ · 11-12 rápido/experto ✓ (toggle + secciones avanzadas) · 13 presets ✓ (10 rubros+test) · 14 copiar con permisos ✓ (test 403) · 15 masivas ✓ (tests) · 16 críticos bloquean ✓ (422+test) · 17 advertencias confirman ✓ (409+modal) · 18 modo clásico ✓ (intacto+enlace) · 19 sin regresiones ✓ (14/14+289 tests) · 20 pruebas verdes ✓ · 21 AA ✓ (§15) · 22 responsive ✓ (§16) · 23 métricas ✓ (§17) · 24 doc técnica ✓ (5 docs) · 25 doc despliegue ✓ · 26 tienda ≤5h: flujo E2E automatizado completo en segundos; validación humana cronometrada pendiente con la beta de TECSIST.

# Constructor — Arquitectura de borrador y publicación (B0)

## Decisión

**Estrategia: tabla de borradores genérica + columnas draft existentes + registro de versiones.**

| Recurso | Soporte de borrador | Mecanismo |
|---|---|---|
| Secciones del inicio (`store_sections`) | Ya existía (`draft_is_enabled`, `draft_sort_order`, `draft_content`, `draft_show_*`, `publish_from/until`) | Se conserva. El Constructor guarda con `action=draft` (endpoint canónico `settings.experience.home.state` / `settings.experience.home`) |
| Ajustes (`project_settings`) | No existía | **`builder_drafts`** (`resource_type=settings`, `resource_key`=clave, `payload={"value":…}`) |
| Navegación / menú / páginas / popup | No existía | Misma tabla `builder_drafts` con `resource_type` propio (adaptadores en B3/B5) |
| Versión publicada | No existía | **`store_publications`** (`version` correlativa por proyecto, `snapshot` del estado público previo, `checklist` al publicar, `published_by`) |

## Por qué esta opción
- **No duplica la lectura pública**: la tienda sigue leyendo `project_settings`/`store_sections` publicados; cero cambios de render.
- **Preview del borrador sin mocks**: `BuilderDraftService::effectiveSettings()` = publicados + drafts encima; el preview del Constructor renderiza el storefront real con ese overlay (B1) reutilizando el mecanismo `preview` existente para secciones.
- **Compatible con legacy**: el modo clásico sigue escribiendo directo (comportamiento histórico); el Constructor es quien introduce borrador. No hay dos fuentes de verdad: producción vive donde siempre; el borrador es una capa aparte y explícita.
- **Migración reversible** (`down()` solo dropea las tablas nuevas).

## Flujo implementado (BuilderDraftService)

```
Editar → putSetting() [builder_drafts]           ← autosave, NUNCA producción
       → effectiveSettings() → preview real
Publicar → PublishChecklist (críticos bloquean)
        → DB::transaction:
            lockForUpdate(project)               ← concurrencia
            snapshot de settings previos (solo claves con draft)
            snapshot de secciones con draft
            promover settings (updateOrCreate)
            promover secciones (COALESCE draft→publicado, drafts→null)
            registrar store_publications (version = max+1)
            limpiar builder_drafts de settings
        → invalidar cachés (B1: hook tras publish)
Rollback(version) → restaurar snapshot en transacción
```

Fallo en cualquier paso ⇒ rollback completo de la transacción; el público conserva la versión anterior.

## Contrato de autosave (front, B1)
- `POST settings/builder/draft/settings {settings:{clave:valor,…}}` → `{ok, saved, progress}`.
- Claves válidas: `[a-z0-9_]{1,80}` (whitelist efectiva = las que el registry/UI usa; el endpoint no escribe producción, así que una clave desconocida en borrador es inocua y muere al publicar solo si existe la promoción).
- Estados: clean → dirty → saving → saved | error (reintento manual) | offline.
- Debounce 500-800 ms texto; inmediato en switches; secuencia por recurso (se descartan respuestas viejas).

## Contrato postMessage (B1)
Storefront → constructor: `storefront:ready | storefront:section-selected {component} | storefront:error`.
Constructor → storefront: `builder:highlight-section {component} | builder:clear-highlight | builder:set-device {device} | builder:refresh`.
Validación en ambos lados: `event.origin === expected` + `type` permitido + payload plano.
Marcadores: se reutiliza `data-store-native-section` ya presente en las plantillas como identificador estable (alias documentado de `data-builder-section`; no se duplica el atributo para no tocar el render público).

## Feature flag
`project_settings.builder_mode = disabled|beta|enabled` (`BuilderAccess`). `beta` = owner/superadmin; `enabled` = todos los miembros; `disabled` (default) = 404 y el menú no lo ofrece. Rollback por tienda sin tocar datos.

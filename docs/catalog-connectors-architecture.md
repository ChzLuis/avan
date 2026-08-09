# Arquitectura de Conectores de Catálogo — Diseño (Etapa 1)

Fecha: 2026-08-05 · Estado: diseño aprobado, implementación en curso.

## 1. Arquitectura actual detectada

- **Laravel 12**, sin `Console/Kernel.php` (scheduler vive en `routes/console.php`, 2 tareas: `carts:remind` hourly, `ExpireDemos` dailyAt 02:00).
- **Colas**: `QUEUE_CONNECTION=sync` local / `database` por defecto en `.env.example`. Ningún job usa `->onQueue()` — cola `default` para todo. Sin Horizon/Supervisor.
- **Cache**: `CACHE_STORE=file` local — **no soporta `Cache::lock()` atómico**. `database` sí lo soporta y ya está disponible como store alternativo.
- **Encriptación**: `APP_KEY` presente; ningún modelo usa cast `'encrypted'` todavía — seré el primero, es soportado nativamente por Laravel.
- **Permisos**: `PermissionsSeeder` con array plano `'modulo.accion'` + roles (`gerente`, `vendedor`, `revendedor`, `almacen`, `rrhh`, `contador`, `solo_lectura`). Ya existe `catalog.importar`.
- **Módulos** (`modules` + `project_modules`): feature flags de negocio por proyecto (`catalog`, `orders`, etc.) — **no** es el lugar para "conectores técnicos"; los conectores viven dentro del módulo `catalog` ya existente, sin módulo nuevo.
- **Multi-tenant real**: `Project hasMany Category/Product`, guardrail `booted()` en `Product`/`Category` que impide asignar `category_id` cross-tenant (lanza `RuntimeException`) — replicar el mismo patrón de aislamiento por `project_id` en las tablas nuevas.
- **Patrón de credenciales por proyecto ya validado**: `NubefactService` lee `project->setting('nubefact_url')` / `setting('nubefact_token')` — confirma que "credenciales por proyecto" es un concepto ya aceptado en el sistema; lo formalizamos con cifrado real (hoy `project_settings` NO cifra, es texto plano).
- **Import existente** (`ProductController::import`, línea 446): upsert por `sku` → fallback `name`, resuelve categoría por nombre exacto, registra `ImportLog`. Es la referencia de UX para "vista previa antes de aplicar", pero es un import de un solo golpe (Excel), no sincronización recurrente con correspondencia persistente.
- **Jobs con retry**: `SendAbandonedCartReminder` (`tries=3`, `backoff=60` fijo, soft-fail con try/catch + `Log::warning`) — patrón base, lo extiendo con backoff exponencial y `failed()` porque el conector lo necesita.

## 2. Funciones reutilizadas (no se reescriben)

- `Project::setting()` / `settings()` — NO se usa para credenciales de conectores (van cifradas en tabla propia), pero sí como referencia de convención.
- Guardrail anti cross-tenant de `Product`/`Category` (`booted()`) — se replica igual en `CatalogIntegration` y `CatalogIntegrationItem`.
- `HasProjectScope` trait — se aplica a los modelos nuevos.
- Middleware `module:catalog` + `can:permiso` — se reutiliza en las rutas nuevas del panel de integraciones.
- `ImportLog` — inspiración directa para `catalog_sync_runs` (mismo espíritu: created/updated/skipped/errors).

## 3. Problemas/acoplamientos que la nueva arquitectura evita

- `WooSyncController` tiene credenciales **hardcodeadas como constantes de clase** y `PROJECT_ID = 1` fijo — antipatrón explícito a NO replicar (documentado para no confundirlo con el nuevo patrón).
- Sin este diseño, el instinto natural sería añadir columnas `siskote_token` a `project_settings` — evitado por diseño (JSON cifrado + esquema declarado por el conector).

## 4. Propuesta técnica — piezas

```
app/Catalog/
  Contracts/
    CatalogProviderInterface.php   — key(), name(), capabilities(), testConnection(), fetchProducts()/fetchServices()/fetchCategories()
    ProvidesProducts.php           — capacidad opcional (interface marker + fetchProducts)
    ProvidesServices.php
    ProvidesCategories.php
    ProvidesInventory.php
    SupportsWebhooks.php           — verifyWebhook(), handleWebhookPayload()
  DTOs/
    NormalizedProduct.php  NormalizedService.php  NormalizedCategory.php
    ProductPage.php  ServicePage.php  CategoryPage.php   (página + cursor siguiente)
    SyncCursor.php          — valor inmutable: página/token/fecha de corte
    ConnectionTestResult.php
    ProviderCapabilities.php
    ConfigField.php  ConfigSchema.php   — esquema declarativo de credenciales
  Enums/
    SyncMode.php (Full, Incremental)  SyncStatus.php  SyncTrigger.php  FieldOwner.php (Erp, Bixo)
  Exceptions/
    CatalogProviderException.php  ProviderNotFoundException.php  ConnectionFailedException.php
  Registry/
    CatalogProviderRegistry.php   — resolve(), all(), has() — SIN if/switch por proveedor (array indexado por key())
  Sync/
    CatalogSyncManager.php        — motor genérico (única clase que orquesta)
    FieldOwnershipPolicy.php      — qué campos toca el ERP vs qué campos preserva BIXO
    ItemMatcher.php               — resuelve NormalizedProduct → CatalogIntegrationItem existente o nuevo
  Providers/
    Siskote/  (SiskoteProvider, SiskoteClient, SiskoteConfigSchema, SiskoteProductMapper, SiskoteServiceMapper, SiskoteException)
    Fake/     (FakeCatalogProvider — solo para tests, registrado condicionalmente)
```

**Registro sin `if/switch`:** `CatalogServiceProvider::boot()` hace `$registry->register(new SiskoteProvider(...))` (y en `local`/`testing` además `register(new FakeCatalogProvider)`). El núcleo (`CatalogSyncManager`, comando, controlador) SOLO llama `$registry->resolve($integration->provider)` — nunca conoce `'siskote'` como string mágico salvo en el propio `SiskoteProvider::key()`.

**Capacidades vía interfaces pequeñas + objeto `ProviderCapabilities`:** decisión — uso **ambas**, pero el contrato mínimo (`CatalogProviderInterface`) exige `capabilities(): ProviderCapabilities` (objeto de flags booleanos: `products`, `services`, `categories`, `inventory`, `webhooks`, `incremental`). Las interfaces `ProvidesProducts`/`ProvidesServices`/etc. son *opcionales* — el proveedor las implementa si aplica, y `CatalogSyncManager` usa `instanceof` (esto NO es un switch por proveedor, es un patrón de capacidad estándar de PHP, igual que `Countable`/`Iterator` del propio lenguaje). Motivo: SISKOTE no soporta webhooks hoy — que su clase simplemente no implemente `SupportsWebhooks` es más seguro en tiempo de compilación que un booleano ignorado.

## 5. Tablas nuevas (todas nuevas, no se modifica `products` salvo 3 columnas nullable)

1. **`catalog_integrations`** — la integración configurada por proyecto+proveedor. `credentials` (JSON, cast `encrypted:array`), `settings` (JSON, cast `array`, no sensible), `capabilities_cache` (JSON), `sync_mode`, `sync_interval_minutes`, `active`, timestamps de última prueba/sync, `last_sync_status`, `last_sync_error` (mensaje saneado, nunca el payload crudo).
2. **`catalog_integration_items`** — tabla de correspondencia externo↔local. Única real fuente de verdad para "qué creó/actualiza qué". `unique(integration_id, entity_type, external_id)`, índices por `local_type+local_id` y por `sync_status`.
3. **`catalog_sync_runs`** — historial de ejecuciones con contadores.
4. **`products`**: **solo agrega** `catalog_integration_id` (nullable, FK), `external_sync_status` (nullable: `synced|conflict|orphaned`), `owner_scope` (nullable JSON: qué campos son del ERP) — nullable así que no rompe nada existente; producto manual sigue con estos 3 campos en `null`.

Ningún dato de SISKOTE (`sale_unit_price`, etc.) toca `products` directamente ni ninguna tabla del núcleo — solo vive dentro de `SiskoteProductMapper` y como `metadata` opcional saneada en `catalog_integration_items.raw_metadata` (sin secretos).

## 6. Política de propiedad de campos (v1)

Config estática en `FieldOwnershipPolicy`: **ERP controla** `sku, barcode, price, cost, stock, unit, is_available` (vía `stock`/`price` reales de `Product`); **BIXO controla** `description` (si el import trae descripción pero el usuario ya editó una local, se preserva — ver regla abajo), imágenes, SEO, destacado, orden, catalogProfiles. Regla de conflicto v1 (documentada como limitación, tal como permite el prompt): **una integración primaria por producto** — si dos integraciones reclaman el mismo `external_id`/SKU, la segunda queda en `sync_status=conflict` y NO se aplica automáticamente; se resuelve manualmente en el panel.

## 7. Riesgos

- `CACHE_STORE=file` no da locks atómicos reales en producción → el lock de "sync en curso" usa store `database` explícitamente (`Cache::store('database')->lock(...)`) para no depender del store default del proyecto.
- Sync de miles de productos en una sola transacción → prohibido por diseño; `CatalogSyncManager` commitea por lotes de N (configurable, default 100).
- Doble ejecución (manual + scheduler simultáneos) → lock por `integration_id` con TTL, liberado en `finally`.

## 8. Compatibilidad

Nada de lo existente cambia de comportamiento: `ProductController::import` (Excel) sigue igual; productos manuales con los 3 campos nuevos en `null` se comportan exactamente igual que hoy (POS, Venta Express, Centro de Pedidos, Constructor no leen estos campos, así que no hay impacto). Los 3 campos nuevos en `products` son la única modificación a una tabla existente, y son nullable.

## 9. Plan de implementación (5 etapas, commits separados)

E1 (este documento) → E2 motor+migraciones+comando+scheduler+tests estructurales → E3 SiskoteProvider+FakeProvider+tests con fixtures → E4 panel UI+rutas+permisos → E5 webhooks+conciliación+QA final. Cada etapa: tests en verde antes de pasar a la siguiente. Sin deploy a producción hasta confirmación explícita + backup, por etapa completa (no parcial).

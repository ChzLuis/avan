# Matriz de capacidades restringidas — Workspace Configuración

Reestructuración 2026-08-30 (pasos 4-6 del plan). Auditado contra
`route:list` real (230 rutas bajo `/bixoadmin`) y el código de cada
controller. **Regla:** ninguna opción se muestra en el menú sin clasificación
previa; el acceso se controla por **ENTITLEMENT + PERMISSION + FEATURE FLAG**
(cuando aplique), no solo por permiso.

Clasificación:
- **PUBLIC_TENANT** — cliente común con el permiso indicado.
- **RESTRICTED_TENANT** — exige además flag de proyecto (`cap_*`) o plan.
- **ESKALA_ONLY** — solo superadmin (o flag activado a mano por Eskala).
- **DEPRECATED_CANDIDATE** — no se elimina en esta fase; no se ofrece en menú.

Mecanismo (`App\Support\Capacidades::permite($project, $user, 'clave')`):
superadmin pasa siempre; el resto exige (1) módulo contratado si la capacidad
lo declara, (2) permiso Spatie, (3) flag `cap_<clave>`=1 del proyecto si la
capacidad lo declara. Vocabulario de módulos vigente: orders, catalog, quotes,
hr, agenda, rifas, pos, logistics, invoices, clients, bots.

## Constructor (`settings.builder.*`)

| Función | Ruta(s) | Permission | Entitlement | Riesgo | Decisión |
|---|---|---|---|---|---|
| Ver constructor, progreso, checklist, métricas | builder, .progress, .checklist, .metrics | settings.diseno | catalog | Bajo (lectura) | PUBLIC_TENANT |
| Guardar borrador de ajustes | .draft.settings | settings.diseno | catalog | Medio | PUBLIC_TENANT |
| Aplicar preset de diseño (rubro/paleta) | .design-preset | settings.diseno | catalog | Bajo | PUBLIC_TENANT |
| Publicar / descartar borrador | .publish, .discard | settings.diseno | catalog | Medio | PUBLIC_TENANT |
| Vista previa | .preview | settings.diseno | catalog | Bajo | PUBLIC_TENANT |
| Corrección de catálogo (lista + bulk) | .catalog.list, .catalog.bulk | settings.diseno / catalog.editar | catalog | Medio | PUBLIC_TENANT |
| Fotos de categoría / iconos | .category-photo, .icons.* | settings.diseno | catalog | Bajo | PUBLIC_TENANT |
| Asistente rápido (quick-setup) | dentro de builder | settings.diseno | catalog | Bajo — solo ajustes, sin código | PUBLIC_TENANT |
| **Cambio de motor/plantilla** (etapa Apariencia) | .draft.settings (`catalog_template`) | settings.diseno | catalog | **Alto** — puede romper la tienda | **RESTRICTED_TENANT** (`cap_builder_avanzado`) |
| **Copiar configuración de otra tienda** | .copy, .copy.sources | settings.diseno | catalog | **Alto** — lee otro tenant | Fuente propia: RESTRICTED_TENANT · fuente ajena: **ESKALA_ONLY** (ya en código: `copyStore` exige superadmin u owner de la fuente) |

## Diseño legacy y plantillas

| Función | Ruta(s) | Estado hoy | Decisión |
|---|---|---|---|
| Diseño clásico | settings.design (+`?classic=1`) | GET **sin** `can:` | **DEPRECATED_CANDIDATE** — fuera del menú; se endurece con settings.diseno |
| Diseñador visual Fase A | settings.designer | GET **sin** `can:` | **DEPRECATED_CANDIDATE** — ídem |
| **Gestión de plantillas de diseño** (crear, importar, versionar, restaurar, duplicar, aplicar) | design-templates.* | escrituras con settings.diseno | **ESKALA_ONLY** (`cap_plantillas`) — es administración de plantillas/presets (plan §5 nivel interno) |
| **Export de plantilla** | design-templates.export | **SIN middleware** (hallazgo) | **ESKALA_ONLY** + hardening inmediato |

## SEO (`settings.seo`)

| Función | Riesgo | Decisión |
|---|---|---|
| Title, description, OG, slug, favicon | Bajo | PUBLIC_TENANT (settings.negocio) |
| Analytics / Pixel (GA4, GTM, Meta, TikTok) | Medio — inyecta scripts de terceros en la tienda | **RESTRICTED_TENANT** (`cap_seo_avanzado`) |
| Schema / datos estructurados, verificación Google | Medio | **RESTRICTED_TENANT** (`cap_seo_avanzado`) |

## QR (`settings.qr`)

| Función | Decisión |
|---|---|
| QR de tienda / producto / WhatsApp / URL, estilos y composiciones actuales | PUBLIC_TENANT (settings.negocio) |
| Campañas, tracked links, atribución | **No existen** — futuro; nacerán RESTRICTED_TENANT |

## Resto de Configuración

| Sección | Rutas | Decisión |
|---|---|---|
| Mi negocio (datos, dominio) | settings, settings.update | PUBLIC_TENANT (settings.negocio); dominio custom ya es superadmin-only en `SettingsController::update` |
| Productos / catálogo maestro | products.*, categories.*, catalogs.* | PUBLIC_TENANT (permisos por verbo catalog.*; module:catalog) |
| Sedes / proveedores / grupos | sedes.*, proveedores.*, groups.* | PUBLIC_TENANT (permisos actuales) |
| Pagos | settings.payments.* | PUBLIC_TENANT (settings.pagos) |
| Módulos del negocio | settings.modules.* | PUBLIC_TENANT (settings.negocio) — solo enciende lo contratado (ModulosPortal + entitlement) |
| Fiscal: certificados, series | certificados.*, settings (pestañas) | PUBLIC_TENANT con permiso propio (`settings.fiscal` objetivo; hoy settings.negocio) — datos sensibles, candidato a permiso dedicado |
| Equipo: roles y permisos | roles.* | PUBLIC_TENANT (settings.negocio); cambios auditados en AccessEvent |
| Canales WhatsApp / flujos | bots.*, bots-flow.* | PUBLIC_TENANT (module bots + permisos) |
| Experiencia / páginas / popup | settings.experience.* | PUBLIC_TENANT (settings.diseno) |
| Menú del storefront | settings.storefront.* | PUBLIC_TENANT (settings.diseno) |

## Hallazgos que se corrigen en el paso 8

1. `design-templates.export` sin middleware → gate ESKALA_ONLY.
2. `settings.design` y `settings.designer` GET sin permiso → `can:settings.diseno` y fuera del menú.
3. Gestión de plantillas completa → `cap_plantillas` (ESKALA_ONLY).
4. Cambio de motor/plantilla desde el builder → `cap_builder_avanzado`.
5. Analytics/Schema del SEO → `cap_seo_avanzado` (flag por proyecto).

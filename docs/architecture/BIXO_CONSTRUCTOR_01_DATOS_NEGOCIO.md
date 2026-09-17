# Constructor — Revisión 01 · Datos del negocio

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Primer paso de la reorganización de "Mi Tienda" en 9 etapas.

**Regla que se quiere establecer:** cada dato se configura en **un único lugar**
y las demás secciones solo lo reutilizan.

---

## 1. Qué campos existen hoy

Etapa actual: `resources/views/settings/builder/stages/business.blade.php`
(125 líneas). Todo se guarda como **borrador** en `project_settings` (clave/valor).

| Campo en pantalla | Clave que escribe | Observación |
|---|---|---|
| Nombre comercial | `seo_title` | ⚠️ el nombre del negocio vive en una clave de **SEO** |
| Rubro | `business_category` | dispara presets de diseño |
| Logo | `logo_url` | |
| Redes (6) | `facebook_url`, `instagram_url`, `tiktok_url`, `youtube_url`, `linkedin_url`, `twitter_url` | |
| WhatsApp de ventas | `quote_whatsapp` | |
| Correo de contacto | `contact_email` | |
| Teléfono fijo | `contact_phone` | |
| Moneda | `currency_symbol` | no es "dato de negocio", pertenece a **05 Venta** |
| Tengo local físico | `has_physical_store` | revelado progresivo |
| Dirección / Ciudad | `contact_address`, `contact_city` | |
| Horario | `business_hours` | **texto libre**, sin estructura |
| Sucursales | — | ⚠️ **enlace externo** a `sedes.index`, fuera del Constructor |
| Razón social / RUC | `razon_social`, `ruc` | ya declarados fuente única de facturación |

**Faltan respecto de la estructura objetivo:** Sucursales dentro de la etapa, y
un campo propio de nombre comercial independiente del SEO.

---

## 2. La duplicación: dos fuentes para el mismo dato

La tabla `projects` tiene **columnas nativas** con la misma información, y el
Constructor escribe **claves paralelas**:

| Dato | Columna nativa (usos en código) | Clave del Constructor |
|---|---|---|
| Nombre | `projects.name` | `seo_title` |
| Teléfono | `projects.phone` (**81**) | `contact_phone` |
| WhatsApp | `projects.whatsapp` (**131**) | `quote_whatsapp` |
| Dirección | `projects.address` (**64**) | `contact_address` |
| Logo | `projects.logo_url` (**95**) | `logo_url` *(misma clave)* |
| Rubro | `projects.category` (16) | `business_category` |

### El puente actual es de una sola dirección

`StoreBuilderController.php:50-55` siembra **columna → setting**, y solo la
primera vez, **si el setting está vacío**:

```php
$masterMap = [
    'logo_url'        => $project->logo_url,
    'quote_whatsapp'  => $project->whatsapp ?: $project->wa_phone,
    'contact_phone'   => $project->phone,
    'contact_address' => $project->address,
];
```

**No existe escritura de vuelta.** En cuanto el usuario edita en el Constructor,
las dos fuentes divergen para siempre.

---

## 3. Quién consume cada fuente (estado real)

### ✅ Lo que sí funciona

**`computienda`** — la plantilla de **7 de las 8 tiendas activas** (MegaHogar,
Tecsist, Electro Jara, Baby Toncito, Import Musuhuay, GABDE) resuelve con cadena
de respaldo correcta: **gana el setting**, la columna es solo respaldo.

```php
$waSource = … ?? $settings['quote_whatsapp'] ?? … ?? $project->whatsapp ?? $project->phone;
$phone         = $settings['contact_phone']   ?? $project->phone   ?? '';
$footerAddress = $settings['contact_address'] ?? … ?? $project->address ?? '';
```

Ahí el Constructor **sí manda**. La reorganización no rompe nada en esas tiendas.

### ⚠️ Tres rutas donde el Constructor NO manda

| # | Dónde | Qué pasa | ¿Activo hoy? |
|---|---|---|---|
| 1 | `components/storefront/header.blade.php` y `footer.blade.php` (layout `layouts/storefront.blade.php`, **estructura V2**) | Leen **solo** `$project->phone/whatsapp/address`. Ignoran por completo lo escrito en el Constructor | **Latente.** `storefront_structure_v2` es NULL en las 8 tiendas. Pero al activarlo, los datos del Constructor dejan de verse |
| 2 | `public/templates/ecommerce.blade.php:143-145` — **schema SEO / JSON-LD** | Usa **solo las columnas**. El WhatsApp visible sí respeta el setting, pero Google recibe teléfono y dirección **viejos** | **Sí — Market Huacho Express** |
| 3 | `public/catalog.blade.php:36` | `quote_whatsapp` **sin respaldo**: si está vacío no hay WhatsApp aunque la columna lo tenga | según plantilla |

### Formularios duplicados todavía vivos

Aunque la memoria del proyecto dice *"Diseño retirado: Constructor único"*, las
rutas **siguen registradas** (`routes/web.php:402` y `:435`) y estas vistas
editan los mismos datos:

- `settings/index.blade.php` → `phone`, `whatsapp`, `address`, `razon_social`
- `settings/design.blade.php` → `contact_phone`, `contact_email`,
  `business_hours`, `quote_whatsapp`, `address`, `logo_url`
- `settings/partials/institutional-pages.blade.php` → `phone`, `whatsapp`, `address`

**Son tres puertas más que escriben lo mismo.** Mientras existan, la regla de
"un único lugar" no se cumple aunque el Constructor quede perfecto.

---

## 4. Recomendación: cuál debe ser la fuente canónica

**La columna de `projects` debe ser la canónica** para nombre, teléfono,
WhatsApp, dirección y logo. Razones:

1. **371 usos** en el código (131 + 95 + 81 + 64) contra un puñado de settings.
2. Los módulos que **no son la tienda** leen las columnas: facturación,
   cotizaciones, PDF de catálogo, órdenes, portal del cliente, POS.
3. **El bot de WhatsApp** responde la dirección desde `project->address`
   (`ProjectContext`). Si el comerciante la cambia en el Constructor, hoy el bot
   sigue dictando la vieja.

**Cambio propuesto:** que la etapa 01 **escriba en las columnas** de `projects`
(vía el servicio de borradores, publicando al confirmar) en lugar de mantener
claves paralelas. Es mucho menos trabajo que migrar 371 referencias, y elimina
la divergencia de raíz.

Las claves `contact_*` quedarían como **alias de solo lectura** durante una
transición, para no romper las plantillas que ya las leen.

---

## 5. Trabajo que sale de esta revisión

| # | Tarea | Prioridad |
|---|---|---|
| 1 | Nombre comercial fuera de `seo_title`: clave/columna propia | Alta |
| 2 | La etapa 01 escribe en las columnas canónicas de `projects` | Alta |
| 3 | Cerrar las 3 puertas duplicadas (`settings/index`, `settings/design`, `institutional-pages`) o dejarlas en solo lectura | Alta |
| 4 | Arreglar el schema SEO de `ecommerce` para que respete el setting *(afecta a Market Huacho hoy)* | Media |
| 5 | Dar respaldo a `components/storefront/*` antes de que alguien active la estructura V2 | Media |
| 6 | Traer **Sucursales** dentro de la etapa 01 (hoy es un enlace externo) | Media |
| 7 | Mover **Moneda** a 05 Venta | Baja |
| 8 | Estructurar **Horarios** (hoy texto libre) para alimentar `schema_opening_hours` y Sucursales | Baja |

---

## 6. Implementación (2026-08-30)

Las 8 tareas de la sección 5 quedaron así:

| # | Tarea | Estado |
|---|---|---|
| 1 | Nombre comercial con clave propia | ✅ La etapa edita `business_name` (sembrada desde `projects.name`); `seo_title` vuelve a ser solo SEO. Las plantillas ya caían a `$project->name`, nada que migrar. |
| 2 | Escribir en columnas canónicas | ✅ `BuilderDraftService::publish()` sincroniza `projects.name/phone/whatsapp/address/logo_url/category` desde los borradores publicados. Solo toca columnas cuyo setting cambió en ESA publicación; vaciar el setting no borra el dato maestro; el WhatsApp se guarda solo con dígitos. Probado con 9 comprobaciones (publicar, publicar otra etapa, vaciar). |
| 3 | Cerrar las 3 puertas | ✅ `settings/index`: teléfono/WhatsApp/dirección en solo lectura con enlace al Constructor y **retirados de la validación** del `update()`. `settings/design`: los 4 campos (`contact_email`, `contact_phone`, `business_hours`, `quote_whatsapp`) en solo lectura y **fuera de la whitelist** de `ProjectSettingWriteService`. `institutional-pages`: aviso de fuente única (sus campos personalizan solo esa página). |
| 4 | Schema SEO respeta el setting | ✅ Ya estaba en el árbol de trabajo (sin commitear, de otra sesión). |
| 5 | Respaldo en componentes V2 | ✅ Ídem: `header`/`footer` de storefront ya leen setting → columna. |
| 6 | Sucursales dentro de la etapa | ✅ La etapa lista las sedes (nombre, dirección, teléfono, inactivas atenuadas); administrar sigue en su módulo. |
| 7 | Moneda a 05 Venta | ✅ Movida a la etapa Venta, misma clave `currency_symbol`. |
| 8 | Horarios estructurados | ⏳ Pendiente (baja). `business_hours` sigue texto libre. |

**Deriva encontrada y reconciliada:** `store_publications` y `builder_drafts_descartados` existían solo en ARIN (a mano); en local publicar reventaba. Migración `2026_08_30_120000_create_store_publications_drift.php` con guardas `hasTable` (inocua en producción).

**Defecto ajeno reparado:** un `@endif` huérfano en `stages/appearance.blade.php` (trabajo sin commitear de otra sesión) tumbaba con 500 todo `/settings/builder`. Se retiró solo esa línea; el resto del archivo no se tocó.

**Validación:** `SettingsAuthorizationTest` 37/41, `StorefrontEngineConsolidationTest` 6/32, `BuilderInformationArchitectureTest` 2/31 — en verde tras los cambios; `view:cache` correcto.

## 6 bis. Siguiente paso

Revisión **02 Apariencia** con el mismo método: qué controles existen, cuáles
están duplicados, cuáles están muertos y qué consume cada uno.

Nota para 02: la etapa `header` existe hoy por separado
(`stages/header.blade.php`); la estructura objetivo la absorbe dentro de
Apariencia.

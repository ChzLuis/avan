# Constructor — Revisión 08 · Configuración avanzada

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Octavo paso de la reorganización de "Mi Tienda" en 9 etapas.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 1. Es la etapa más incompleta del Constructor

`builder/advanced.blade.php` escribe **2 claves**: `seo_description` y
`seo_keywords`. La estructura objetivo pide **8 áreas**:

| Área pedida | Estado en el Constructor |
|---|---|
| SEO general | ⚠️ parcial — solo descripción y palabras clave |
| SEO por página | ❌ no existe |
| Dominio | ❌ no existe |
| Integraciones | ❌ no existe |
| Analítica | ❌ no existe |
| Píxeles | ❌ no existe |
| Scripts autorizados | ❌ no existe |
| Verificación de buscadores | ❌ no existe |

**Una de ocho, y a medias.** Todo lo demás vive fuera.

---

## 2. Hallazgo: una OCTAVA superficie, también en el menú

`settings/seo` (ruta `web.php:489-490`) está enlazada como **"SEO" en el menú
principal** de dos navegaciones (`bots/app.blade.php:197`,
`layouts/app.blade.php:985`).

`SettingsController::updateSeo` guarda **14 claves**:

```
seo_title  seo_description  seo_keywords  seo_canonical
og_title   og_description   og_image
ga_id      gtm_id           fb_pixel_id   tiktok_pixel_id
google_site_verification    bing_site_verification
```

De ellas, **el Constructor solo conoce 2**. Las 12 restantes —incluidas todas
las de analítica y verificación— solo se pueden tocar en la pantalla que
queremos retirar.

### Colisión con la etapa 01

`seo_title` se escribe desde **tres** sitios: `settings/seo`, `settings/design`
y `settings/index`. La revisión 01 ya lo señaló desde el otro lado: hasta la
implementación congelada, `seo_title` era también el **nombre comercial**. Con
`business_name` separado, `seo_title` debe quedar exclusivamente aquí, en 08.

---

## 3. Hallazgo grave: la analítica se guarda pero nunca se emite

Búsqueda exhaustiva en `resources/`, vistas públicas, componentes, layouts y
`public/js`: **no existe ni una sola etiqueta de analítica**. Ni `gtag`, ni
`googletagmanager`, ni `fbq(`, ni `ttq.`, ni un `<script>` de GTM.

Y ninguna vista pública lee `ga_id`, `gtm_id`, `fb_pixel_id` ni
`tiktok_pixel_id`: esas claves **solo aparecen en el controlador que las
guarda** (`SettingsController:179-182`).

> **Un comerciante puede escribir su ID de Google Analytics o su Meta Pixel,
> guardarlo, verlo persistido — y no se emite nunca. La analítica no funciona en
> ninguna tienda.**

**Atenuante:** ninguna de las 8 tiendas tiene hoy esas claves con valor
(0 filas en `project_settings`), así que **nadie está siendo engañado ahora
mismo**. Por eso no es hotfix: no hay información incorrecta publicada, y
emitir los píxeles es construir una capacidad, no reparar un fallo.

Pero en cuanto un cliente lo configure creyendo que mide, lo estará. **Debe
resolverse antes de que 08 se ofrezca como funcional.**

---

## 4. Dominio: fuera del Constructor y en cuatro sitios

`custom_domain` se edita en `settings/design`, `settings/index`, `settings/seo`
y `admin/projects/show`. **Ninguno es el Constructor.**

Es además la pieza que conecta con el trabajo de infraestructura pendiente
(ver `BIXO_DOMINIOS_ENTORNOS.md`): el alta de dominio implica DNS, certificado y
vhost, no solo guardar una cadena. Conviene decidir si 08 lo **configura** o
solo lo **muestra** enlazando a una gestión con permisos de administrador.

---

## 5. Scripts autorizados: no existen (y es lo correcto por ahora)

No hay ninguna clave ni mecanismo de scripts personalizados. Tu estructura
objetivo lo pide **con validación y restricción explícita**, y coincido:

Un campo de scripts libre en una plataforma multiempresa es una vía directa a
XSS y a fuga de datos entre tenants. Si se implementa, debe ser con lista
blanca de proveedores (GA, GTM, Meta, TikTok, Hotjar…) e **inyección de la
etiqueta a partir de un ID validado**, nunca `<script>` arbitrario pegado por
el usuario.

Recomendación: **resolver primero la analítica del §3 con proveedores
concretos**, y dejar los scripts libres fuera de la primera versión.

---

## 6. Propiedad recomendada

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| `seo_title`, `seo_description`, `seo_keywords`, `seo_canonical` | ajustes | **08** | `<head>` de la tienda |
| Open Graph (`og_title`, `og_description`, `og_image`) | ajustes | **08** | Compartidos en redes |
| SEO por página | **no existe** | ➜ backlog | — |
| Analítica y píxeles (`ga_id`, `gtm_id`, `fb_pixel_id`, `tiktok_pixel_id`) | ajustes | **08** | ⚠️ **hoy no se emiten** |
| Verificación de buscadores (`google_site_verification`, `bing_site_verification`) | ajustes | **08** | `<head>` |
| Dominio (`projects.custom_domain`) | **columna de `projects`** | **08** *(o administración)* | Enrutado, enlaces, PDF |
| Scripts autorizados | **no existe** | ➜ backlog, con lista blanca | — |
| **Nombre comercial** | `projects.name` | **01** — nunca aquí | Tienda, PDF, bot |

---

## 7. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | **Emitir de verdad la analítica y los píxeles** *(hoy se guardan y se pierden)* | Medio | **Alta** |
| 2 | Absorber en 08 las 12 claves de `settings/seo` que faltan | Bajo | Alta |
| 3 | Decidir el destino de `settings/seo` (octava superficie, en el menú) | — | Alta |
| 4 | Consolidar `seo_title` en un único lugar *(hoy en 3)* | Bajo | Alta |
| 5 | Dominio: definir si 08 lo configura o solo lo muestra | Bajo | Media |
| 6 | SEO por página | — | Backlog |
| 7 | Scripts autorizados **con lista blanca** | — | Backlog |

**No se implementa nada todavía.** Sin hotfix: la analítica no publica
información incorrecta, simplemente no publica nada, y ninguna tienda la tiene
configurada.

---

## 8. Decisiones que necesito del usuario

1. **Analítica** — ¿entra en la primera versión de la reorganización? Es la
   diferencia entre "08 existe" y "08 funciona". Mi recomendación: **sí**, es
   barato y hoy está roto de forma silenciosa.
2. **Dominio en 08** — ¿lo configura el comerciante, o queda como pantalla de
   administración? Dar de alta un dominio toca DNS, SSL y nginx: no es solo un
   campo de texto.
3. **`settings/seo`** — al ser elemento de menú como `settings/payments`, misma
   pregunta: ¿absorber en 08 o conservar como administración interna?

---

## 9. Siguiente paso

Revisión **09 Revisar y publicar** — la última. Ya sé que existe un motor de
auditoría (`goFix` con objetivos `catalog.fix-price`, `catalog.fix-image`,
`catalog.fix-sku`) y que `store_sections` tiene ciclo de borrador completo
mientras `store_pages` no. Queda medir qué cubre hoy la etapa frente a las 11
comprobaciones que pide la estructura objetivo.

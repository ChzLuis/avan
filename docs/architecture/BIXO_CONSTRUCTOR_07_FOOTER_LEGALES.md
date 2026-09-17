# Constructor — Revisión 07 · Footer y legales

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Séptimo paso de la reorganización de "Mi Tienda" en 9 etapas.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 1. La etapa son dos mitades muy desiguales

`stages/legal.blade.php` (78 líneas) es en realidad:

| Mitad | Cómo está resuelta | Claves |
|---|---|---|
| **Footer** | 16 claves propias | `footer_bg_color`, `footer_copyright`, `footer_layout`, `footer_logo_height`, `footer_logo_plate`, `footer_newsletter_title`, `footer_newsletter_url`, `footer_pages`, `footer_show_categories`, `footer_show_payments`, `footer_show_secure`, `footer_show_socials`, `footer_store_pages`, `footer_style`, `footer_tagline`, `footer_text_color` |
| **Legales** | **incrusta `institutional-pages`** con `showLegal` activo (línea 72) | 2 páginas: `privacidad`, `terminos` |

**Tercera vez que aparece el mismo partial.** Se monta en Ajustes, en la etapa 06
y en la 07 — cada vez con distinta combinación de `showInstitutional` /
`showLegal`. Es reutilización, no duplicación: la nota es que **el partial
también escribe los 9 datos maestros** documentados en la revisión 06, así que
esa fuga alcanza igualmente a esta etapa.

---

## 2. 15 de sus 16 claves ya están en Apariencia

De las 16 claves de footer, **15 colisionan con la etapa 02** (documentado en la
revisión 02). La única que no está duplicada es **`footer_layout`**.

La resolución sigue siendo la de la revisión 02: **quitar el control de
Apariencia y dejarlo aquí**, que es su dueño natural. Riesgo nulo — es la misma
clave, no se migra ningún dato.

---

## 3. Cobertura legal: existen 3 de 6

| Documento pedido | Ruta | En vivo | Estado |
|---|---|---|---|
| **Libro de Reclamaciones** | `/{slug}/libro-reclamaciones` y `/{slug}/reclamaciones` | **200** | ✅ con formulario y tabla `complaints` |
| **Términos y condiciones** | `/{slug}/terminos` | **200** | ✅ |
| **Privacidad** | `/{slug}/privacidad` | **200** | ✅ |
| **Cookies** | — | **404** | ❌ no existe |
| **Política de envíos** | — | **404** | ❌ no existe |
| **Cambios y devoluciones** | — | **404** | ❌ no existe |

Los tres que faltan son **backlog funcional**, no deuda.

### 3.1 · Observación de negocio: nadie ha personalizado sus legales

`store_pages` **no tiene ninguna fila** de `privacidad` ni `terminos` — solo
`nosotros` y `contacto`. El partial lo dice explícitamente:

> *"si no las personalizas, la tienda muestra un texto base profesional"*

Es decir: **las 8 tiendas están publicando términos y política de privacidad
genéricos**, sin que su dueño los haya revisado ni adaptado a su negocio.

Técnicamente funciona. Como riesgo del negocio, conviene que lo sepas: en Perú
la política de privacidad tiene implicaciones de protección de datos, y el Libro
de Reclamaciones tiene requisitos formales. **No es algo que yo deba resolver
—no soy tu asesor legal— pero sí que lo tengas presente antes de sumar clientes.**

La tabla `complaints` tiene **0 filas**: el formulario nunca se ha usado.

---

## 4. Dependencias con otras etapas

| Dato que el pie muestra | Fuente canónica | Etapa dueña |
|---|---|---|
| Logo | `projects.logo_url` | **01** |
| Teléfono, WhatsApp, dirección, correo | `projects.*` / ajustes | **01** |
| Horarios | `business_hours` | **01** |
| Redes sociales | `*_url` | **01** |
| Razón social y RUC *(Libro de Reclamaciones)* | `razon_social`, `ruc` | **01** |
| Categorías del pie | tablas `categories` | **04** |
| Métodos de pago mostrados | `footer_show_payments` + `accepted_payments` | **05** |
| Páginas enlazadas | `footer_pages`, `footer_store_pages` | **06/07** |
| Newsletter | `footer_newsletter_title`, `footer_newsletter_url` | **07** |

**El pie es casi todo consumo.** Solo su composición y estilo le pertenecen: es
la etapa que mejor encaja con la regla "un dato, un lugar", siempre que se le
quiten los 15 controles duplicados de Apariencia.

Confirma además el hallazgo de la revisión 03: **Newsletter vive en el pie**, no
en Inicio. El manifiesto de `CatalogTemplates` ya tiene un grupo "Newsletter" con
variantes `inline` / `popup` / `default`. Si se decide que sea sección de Inicio,
es una **capacidad nueva**, no una migración.

---

## 5. Propiedad recomendada

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Composición y estilo del pie | `footer_style`, `footer_layout`, `footer_bg_color`, `footer_text_color`, `footer_logo_*` | **07** | Pie |
| Qué bloques muestra el pie | `footer_show_*`, `footer_cats_limit` | **07** | Pie |
| Textos del pie | `footer_tagline`, `footer_copyright`, `footer_dev_text` | **07** | Pie |
| Enlaces del pie | `footer_pages`, `footer_store_pages` | **07** | Pie |
| Newsletter | `footer_newsletter_*` | **07** | Pie |
| Términos y Privacidad | `store_pages.{terminos,privacidad}.content` | **07** | Páginas legales, checkout |
| Libro de Reclamaciones | tabla `complaints` + `razon_social`/`ruc` de 01 | **07** *(el formulario)* / **01** *(los datos fiscales)* | Página pública |
| Cookies, envíos, devoluciones | **no existen** | ➜ backlog funcional | — |

---

## 6. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | Recibir de Apariencia los 15 controles de footer | **Nulo** — misma clave | Alta |
| 2 | Quitar del partial los 9 campos maestros *(compartida con 06)* | Medio | Alta |
| 3 | Cookies, política de envíos y cambios/devoluciones | — | Backlog |
| 4 | Avisar al negocio de que los legales son texto base sin personalizar | — | **Decisión del usuario** |
| 5 | Newsletter: decidir pie (hoy) o sección de Inicio (capacidad nueva) | Bajo | Media |

**No se implementa nada todavía.** Sin hotfix: el pie muestra correctamente lo
que tiene almacenado.

---

## 7. Decisiones que necesito del usuario

1. **Legales sin personalizar en las 8 tiendas** — ¿se deja como está, se avisa
   a cada cliente, o se genera un texto base por rubro? Es decisión de negocio.
2. **Newsletter** — ya está en el pie y el manifiesto contempla variante popup.
   ¿Se queda en 07 o se promueve a sección de Inicio?

---

## 8. Siguiente paso

Revisión **08 Configuración avanzada**: hoy la vista `builder/advanced.blade.php`
escribe **solo 2 claves** (`seo_description`, `seo_keywords`) frente a las 8
áreas que pide la estructura objetivo — SEO general y por página, dominio,
integraciones, analítica, píxeles, scripts autorizados y verificación de
buscadores. Es la etapa más incompleta del Constructor.

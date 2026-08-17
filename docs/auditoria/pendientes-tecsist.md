# Cola de trabajo — Tecsist y plantilla compartida

Estado al 2026-08-11. La plantilla `computienda.blade.php` la comparten
**Tecsist (18), MegaHogar (19) y Baby Toncito (20)**: todo cambio se verifica en
las tres antes de darlo por bueno.

## Flujo obligatorio en cada cambio

1. `php artisan view:clear && view:cache` con `c:\xampp\php\php.exe` (en Git Bash
   `php` NO está en el PATH y los comandos fallan en silencio).
2. `php -l` sobre **todas** las vistas de `storage/framework/views/`.
3. `python deploy.py <archivos>`.
4. Comparar md5 local contra servidor **normalizando CRLF a LF** (deploy.py
   convierte los finales de línea; si no, parece que no subió).
5. `curl` con `Host: arindg.com` sobre las rutas de las tres tiendas.
6. `browse` cachea el HTML: usar siempre `?nc=$(date +%s%N)`.

---

## PRODUCTOS DE PRUEBA — CÓMO BORRARLOS

Se crearon **46 productos de prueba** duplicando los reales: mismo precio, misma
descripción y las mismas imágenes (41 las tienen). Se distinguen porque el
nombre empieza por `PRUEBA — ` y el SKU por `TEST-`. El catálogo pasó de 46 a
**92**, que es lo que permite validar paginación, rejillas y filtros de verdad.

**No se inventó ningún precio ni dato comercial**: todo está copiado de un
producto real.

Para borrarlos, en este orden:

```sql
DELETE FROM product_images
 WHERE product_id IN (SELECT id FROM (SELECT id FROM products
       WHERE project_id=18 AND sku LIKE 'TEST-%') x);
DELETE FROM products WHERE project_id=18 AND sku LIKE 'TEST-%';
```

## RESUELTO (última tanda)

- **Pie del perfil**: usaba variables propias (`--ftt-bg`) y no `--footer-bg`.
  Ahora sigue al perfil, con el segundo tono del degradado calculado aclarando
  el mismo color un 14 %.
- **Ofertas automáticas**: la categorización YA funcionaba (10 en BD = 10 en el
  filtro). Lo que faltaba era la **insignia**: la rejilla del catálogo nunca la
  pintaba. Ahora 10/10, en el color de ofertas, en las dos rejillas.
- **Menú móvil**: listaba las categorías DOS veces (subcategorías planas +
  raíces). Reconstruido como acordeón: 5 filas con icono, se despliegan al
  tocarlas. Accesos (Ofertas, perfiles) arriba; WhatsApp no, porque ya está el
  botón flotante.
- **Nosotros en Tecsist**: la página existía y estaba completa, pero este
  cliente no la usa. Desactivada (`store_pages.is_enabled=0`) y el pie ya
  respeta ese estado — antes enlazaba siempre. MegaHogar y Baby Toncito
  conservan la suya.

## PENDIENTE — URLs del catálogo

**El problema:** el subdominio del cliente ya es `tienda.tecsist.net`, así que la
ruta del catálogo queda `tienda.tecsist.net/tienda` — dice "tienda" dos veces.
Y las categorías viajan como `?category=401`, que no se entiende ni se comparte.

**PLAN DE EJECUCIÓN (decisiones ya tomadas, solo queda hacerlo)**

Objetivo: `tienda.tecsist.net/tienda?category=394` → `tienda.tecsist.net/computadoras`
y las fichas → `/computadoras/laptop-hp-15-ryzen-5`.

**Paso 1 — Migración.** `categories` NO tiene columna `slug` (comprobado). Añadir
`slug VARCHAR(160)` + índice único `(project_id, slug)`. Backfill desde `name`
con `Str::slug()`, resolviendo choques con sufijo `-2`, `-3`. Idempotente.

**Paso 2 — Modelo.** `Category::getRouteKeyName()` → `slug`, y generar el slug al
crear/renombrar sin pisar el existente (si cambia el nombre, el slug viejo se
conserva para no romper enlaces ya compartidos).

**Paso 3 — Rutas.** Lista blanca, NUNCA comodín: chocaría con los slugs de
proyecto y con `$reserved` (`routes/web.php:532`).
- Dominio propio: `/{categoria}` y `/{categoria}/{producto}`
- Con slug: `/{slug}/c/{categoria}` — el prefijo `c/` evita la ambigüedad.

**Paso 4 — Compatibilidad.** `?category={id}` sigue funcionando y responde **301**
a la ruta nueva. Sin esto se rompen los enlaces que ya se compartieron por
WhatsApp, que es como venden estas tiendas.

**Paso 5 — Enlaces.** `StorefrontNavigation::shopUrl()` y `categoryUrl()` pasan a
generar la forma nueva. Es el único sitio donde se arman.

**Paso 6 — Verificar** las tres tiendas + los dos perfiles de Baby Toncito antes
de publicar, y comprobar que un `?category=` viejo redirige.

**Riesgo:** alto. Un fallo aquí no deja algo feo, deja las tres tiendas caídas.
Hacerlo con contexto limpio y en este orden.

**Lo investigado (para no repetirlo):**
- `StorefrontNavigation::shopUrl()` (línea 100) devuelve `/tienda` en dominio
  propio y `/{slug}/tienda` con slug. Ahí es donde entra el ajuste `shop_path`.
- Rutas con slug: `routes/web.php:556-557` (`/{slug}/tienda` y
  `/{slug}/tienda/{profile}`), con `$reserved` (línea 532) protegiendo los slugs
  del sistema.
- En dominio propio SOLO existe `/tienda/{profile}` (línea 523). **No hay ruta
  explícita para `/tienda` a secas**: la resuelve `DetectCustomDomain`
  reescribiendo la petición. **Ese es el punto a entender antes de tocar nada**,
  y la razón de que no se pueda cambiar el segmento con una sola línea.
- Vía recomendada: rutas NUEVAS con una lista blanca de segmentos
  (`productos|catalogo|coleccion`), dejando `/tienda` vivo y redirigiendo
  después. Nada de comodines: chocarían con los slugs de proyecto.

**Tres mejoras, de menos a más riesgo:**

1. **Segmento configurable.** Hoy `/tienda` está fijo en
   `StorefrontNavigation::shopUrl()`. Debe salir de un ajuste
   (`shop_path`): `productos`, `catalogo`, `coleccion`… y **vacío** para que en
   un dominio propio el catálogo sea la raíz (`tienda.tecsist.net/`).
2. **Categoría en la ruta**: `/laptops` en vez de `?category=401`. Las
   categorías ya tienen nombre; falta slug único por proyecto y resolverlo en
   el controlador. Mantener `?category=` funcionando para no romper enlaces
   compartidos ni los que ya están en el menú.
3. **Filtros legibles**: `?filter=sale` → `/ofertas`.

**Ojo:** esto es enrutamiento. Un fallo aquí no rompe un color, tumba la tienda
entera. Hacerlo con contexto limpio, con redirección 301 de las rutas viejas a
las nuevas, y probando las tres tiendas antes de publicar.

Lo que YA funciona y no hay que rehacer: el estado de los filtros sí se
sincroniza con la URL (`syncUrl()` en `catalogBrowser`), así que un catálogo
filtrado se puede compartir y el botón atrás funciona.

## PENDIENTE — lo que falta ahora mismo

### 0. Campos de color — HECHO EN PARTE
Componente `<x-bxb-color clave="..." defecto="..." etiqueta="..." />` creado en
`resources/views/components/bxb-color.blade.php`, con su estilo en
`builder/styles.blade.php`. Sustituidos **27** de 30: 21 en `appearance` y 6 en
`header`.

**Quedan 3 en `header.blade.php`** con otra forma (el valor por defecto no es un
hex literal, es otra variable). Buscar `bxb-color-row` ahí y pasarlos a mano.
**Falta probarlo en el panel**: requiere sesión iniciada y no la tengo. Verificar
que se escribe un código, se guarda y la vista previa cambia.

### 0-bis. Campos de color: cómo debía quedar (referencia)
Hoy cada color son **tres piezas** en la misma fila: el selector nativo
(`<input type="color">`), un `<code>` que muestra el valor y un botón "Auto".
Ocupa mucho, no se puede pegar un código de marca y el `<code>` no es editable.

**Cómo debe quedar:** un **único campo de texto** donde se escribe o se pega el
código (`#183060`), con una muestra de color pequeña a la izquierda que abre el
selector nativo al pulsarla. Vacío = automático, así el botón "Auto" desaparece.

Detalles a respetar:
- Aceptar `#183060`, `183060` y `#183` (normalizar a 6 dígitos con `#`).
- Validar antes de guardar; si no es un color válido, no escribir el ajuste.
- Es un patrón repetido: está en `appearance.blade.php` (11 colores),
  `header.blade.php` (barra, teléfono, accesos) y `catalog-profiles.blade.php`.
  **Hacerlo como componente Blade reutilizable**, no copiando el HTML en cada
  sitio, o volverá a divergir.



### A. Pie del perfil: no toma su color
El perfil Reacondicionados tiene `footer_bg_color='#073F33'` guardado, el
encabezado SÍ se pone verde, pero **el pie sigue azul marino**. El runtime ya
recibe `$activeProfile` y aplica `footerBg`, así que algo lo pisa después —
probablemente el propio parcial del pie con su color. Verificar qué regla gana
sobre el `<footer>` en `/tecsist-yc5w/tienda/reacondicionados`.

### B. Menú móvil: sigue sin verse amigable
Se le pusieron iconos, ritmo y flechas, pero el cliente lo sigue viendo plano.
**Rehacerlo mirándolo**, no a ciegas: abrirlo a 430px, capturar y criticar.

### C. Ofertas automáticas por precio tachado
Pedido: **todo producto con `compare_price` mayor que `price` debe entrar
automáticamente en "Ofertas"** y calcular su descuento. Hoy el filtro
`?filter=sale` y la sección de descuentos usan otro criterio. Unificar: una sola
definición de "está en oferta" en `CatalogQueryService`, usada por el filtro, la
sección, la insignia y el acceso del menú.

### D. Productos de prueba e imágenes
Pedido: añadir más productos de prueba y reutilizar las imágenes existentes para
validar mejor las rejillas. **OJO: no inventar precios ni datos comerciales.**
Usar nombres claramente de prueba y poder borrarlos después.

### E. Tecsist no tiene vista "Nosotros"
Confirmado por el cliente. Decidir si se crea o se quita del menú y del pie para
no dejar un enlace a una página que no existe.

## PENDIENTE (anterior)

### 1. Hero móvil
Quitar el redondeo (hoy `border-radius:20px`, medido a 430 px) y reestructurar:
a pantalla completa sin márgenes, flechas más discretas (hoy 44 px y estorban
sobre el contenido), tipografía más apretada y CTA con ancho contenido.

### 2. Menú móvil más amigable + iconos
`.mobile-nav-panel` en `computienda.blade.php` (~línea 5460). Hoy son filas
planas con separadores finos. Falta: iconos por categoría, ritmo de espaciado,
estado activo y agrupación más legible.

### 3. Iconos en el panel lateral — BLOQUEADO
El menú lateral ya los pinta, pero **Tecsist tiene 0 categorías con icono
asignado** (`caticon_*` vacío), así que no se ve nada. Para que funcione sin
asignarlos a mano hay que **extraer la biblioteca de iconos a un sitio
compartido**: hoy `$categoryIconPaths` y `$autoCategoryIcon` viven dentro de
`computienda.blade.php` (~líneas 3858 y 3891), *después* de que se incluya el
encabezado, así que `preset-shell` no los alcanza. Propuesta: clase
`App\Support\CategoryIcons` con `paths()` y `auto($nombre)`.

### 4. Transiciones y movimiento en secciones
Aparición al entrar en pantalla, respetando `prefers-reduced-motion`.

### 5. Plantillas de mensaje de WhatsApp
Los textos que se envían al abrir WhatsApp (consulta de producto, asesoría,
cotización). Hoy salen de `quote_wa_msg` y de `$inquiryMsgBase`.

### 6. Barra superior
Espaciado de las redes y estilo, configurable desde el constructor.

### 7. Botón de carrito que desaparece — DECIDIDO, falta ejecutar
**Causa:** `computienda.blade.php` líneas ~3057 y ~3059:
```css
.catalog-card:has(.buy-block--wholesale:not(.is-foldable)) … .catalog-card-action{display:none}
.catalog-card:has(.buy-auto) … .catalog-card-action{display:none}
```
Ocultan el botón en las tarjetas con precio mayorista o automático. Depende del
**producto**, no de la pestaña: por eso aparece y desaparece al navegar.
**Decisión:** el botón de compra se muestra **siempre**, y el bloque de precios
deja de duplicarlo. Una tarjeta, un botón, siempre en el mismo sitio.

---

## HECHO HOY (no rehacer)

- **Dominios:** alta automática arreglada (certificado por dominio, `www.` solo
  si resuelve, reintento cada 10 min, estado en `projects.domain_status` visible
  en el panel). Ver [[project_alta_dominios_ssl]] en memoria.
- **Menú lateral** con panel de subcategorías al pasar (`hp_mega_layout`).
- **Velo del menú**: arranca bajo el nav, ya no apaga encabezado ni barra.
- **Accesos del menú** configurables: 6 ranuras, 19 iconos, 3 estilos, destino a
  categoría / perfil / acción / URL (`hp_nav_chip_*`).
- **Perfil Reacondicionados** verde `#0A7D66` (derivado del logo real:
  navy `#183060` + celeste `#48C0F0`, muestreados del PNG).
- **11 textos comerciales** al constructor (`Catálogo → Textos de la tienda`).
- **Color del botón de compra**: era un control muerto, ya aplica.
- **Vista rápida** con resumen, código y disponibilidad, en catálogo Y portada.
- **Botones de tarjeta apilados** (comprar arriba, consultar abajo).
- **Buscador** de una pieza en todos los anchos; lupa 22 px, trazo 2,4.
- **Teléfono del encabezado** como botón `tel:` con fondo, letra y etiqueta
  configurables.
- **Títulos de sección** en barra de color (`section_head_style`), incluido
  `.pf-head`.
- **Filtros del perfil** acotados a su alcance, sin opciones en 0.
- **Banda de WhatsApp**: hueco de 600 px a 72 px.
- **Sección Promociones** desactivada en Tecsist.

## Controles muertos encontrados hoy (patrón a vigilar)

Siete en un día: alto del logo, columnas del catálogo, alto del hero, letra de
títulos, `card_whatsapp_style`, `buy_button_color`, y los ajustes de accesos del
nav. **Antes de dar por bueno cualquier control, verificar el valor computado en
el navegador.**

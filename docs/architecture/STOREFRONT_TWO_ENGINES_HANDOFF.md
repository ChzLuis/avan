# Continuidad: Constructor con dos motores de tienda

Última actualización: 2026-08-28

## Decisión aprobada

El producto tendrá únicamente dos motores públicos:

1. **Directo**: catálogo rápido, orientado a consulta, cotización y WhatsApp.
2. **Ecommerce completo**: Inicio, Tienda, Nosotros, Contacto, Blog, carrito y checkout.

`CompuTienda` no debe continuar como un tercer motor independiente. Su diseño azul/tecnológico se conservará como un **preset visual del motor Ecommerce**. La consolidación no debe borrar configuraciones ni cambiar tiendas públicamente antes de terminar la compatibilidad y la vista previa.

## Objetivo técnico

- Compartir datos, contratos y funcionalidades entre los dos motores.
- Evitar implementar carrito, variantes, filtros, menú o checkout tres veces.
- Mantener presets visuales editables desde el Constructor.
- Conservar aislamiento por `project_id`.
- Mantener el precio y stock del servidor como fuente de verdad.

## Estado actual

### Terminado en código

- Esquema reversible para atributos, valores y variantes reales.
- Modelos y relaciones de atributos/variantes.
- Asociación opcional de variante en `order_items` con snapshot histórico.
- Servicio transaccional para generar y guardar matrices de variantes.
- API administrativa protegida por permisos de catálogo.
- Pestaña de variantes en el editor de productos.
- Filtros dinámicos por atributo en `CatalogQueryService`.
- Serialización pública de variantes en las tarjetas del catálogo.
- Checkout público: precio y stock de la variante se resuelven en servidor.
- Selector de variantes integrado en CompuTienda (temporal; se migrará al preset Ecommerce).
- Selector de variantes integrado en Ecommerce y en la ficha usada por Directo.
- El panel y el Constructor ofrecen únicamente dos motores oficiales: Ecommerce y Directo.
- Los presets de Tecnología, Ferretería, Mayorista y Electricidad ya apuntan a Ecommerce.
- Existe el comando idempotente `storefront:consolidate-engines` (simulación por defecto).
- Las vistas Blade y archivos PHP modificados compilan correctamente con PHP 8.2 local.

### En curso

- Completar pruebas visuales de los selectores en navegador.
- Completar el acabado del preset tecnológico de Ecommerce antes de convertir proyectos reales.

### Pruebas ejecutadas (2026-08-28)

- `ProductVariantsTest`: 6 pruebas, 18 aserciones, todas correctas.
- `CheckoutPublicoPrecioTest`: 8 pruebas, todas correctas.
- `CatalogAuthorizationTest`: 33 pruebas, todas correctas.
- Total del bloque ejecutado: 46 pruebas correctas, sin fallos.
- `StorefrontEngineConsolidationTest`: 3 pruebas, 12 aserciones, todas correctas.
- Pruebas estructurales/contexto/consultas/plantillas: 33 pruebas y 461 aserciones correctas; las afectadas por el contrato de dos motores también fueron actualizadas y validadas.
- PHP y Blade compilan correctamente. Los avisos mostrados son deprecaciones preexistentes de metadatos PHPUnit en doc-comments.

### Todavía no ejecutar en producción

- No ejecutar las nuevas migraciones en el VPS.
- No eliminar plantillas históricas.
- No convertir todavía `catalog_template` de tiendas reales.
- No activar la consolidación pública sin pruebas y vista previa.

## Archivos nuevos del bloque de variantes

- `app/Models/ProductAttribute.php`
- `app/Models/ProductAttributeValue.php`
- `app/Models/ProductVariant.php`
- `app/Storefront/ProductVariantMatrixService.php`
- `app/Http/Controllers/Catalog/ProductVariantController.php`
- `database/migrations/2026_08_27_000000_create_product_attributes_and_variants.php`
- `database/migrations/2026_08_27_000001_add_product_variant_to_order_items.php`
- `resources/views/catalog/products/partials/variants.blade.php`

## Archivos modificados por este bloque

- `app/Models/Product.php`
- `app/Models/Project.php`
- `app/Models/OrderItem.php`
- `app/Models/ProductImage.php`
- `app/Storefront/CatalogQueryService.php`
- `app/Http/Controllers/PublicController.php`
- `resources/views/catalog/products/index.blade.php`
- `resources/views/components/computienda/catalog-filters.blade.php`
- `resources/views/public/storefront/shop.blade.php`
- `resources/views/public/templates/computienda.blade.php`
- `resources/views/public/templates/ecommerce.blade.php`
- `routes/web.php`

El repositorio contiene muchos otros cambios del usuario. No revertir, reformatear ni incluir cambios ajenos accidentalmente.

## Contrato de variantes

- Máximo 3 atributos por producto.
- Máximo 30 valores por atributo.
- Máximo 100 combinaciones por producto.
- Cada combinación puede sobrescribir SKU, código de barras, precio, precio comparativo, precio mayorista, stock e imagen.
- Un valor vacío hereda el dato general del producto.
- Los filtros usan `attribute[ATTRIBUTE_ID][]=VALUE_ID`.
- OR entre valores del mismo atributo y AND entre atributos diferentes.
- El cliente envía `product_variant_id`; el servidor verifica proyecto, producto, disponibilidad, precio y stock.
- Nunca aceptar el precio enviado por el navegador como fuente de verdad.

## Plan de continuación obligatorio

### Bloque A — cerrar variantes compartidas

1. ~~Terminar selector de variantes en Ecommerce.~~ Implementado; falta revisión visual.
2. ~~Adaptar Directo y `resources/views/public/product.blade.php`.~~ Implementado; las tarjetas con variantes llevan a la ficha para elegir opciones.
3. Si `storefront_structure_v2` sigue operativo, adaptar `resources/views/public/storefront/product.blade.php` o enrutarlo al componente compartido.
4. ~~Rechazar en `PublicController::storeOrder()` una línea sin `product_variant_id` cuando el producto tenga variantes activas.~~ Terminado y probado.
5. Comprobar dos variantes distintas del mismo producto dentro del carrito.
6. Comprobar precio, imagen, SKU y stock específicos.
7. ~~Crear `tests/Feature/ProductVariantsTest.php`.~~ Terminado.
8. ~~Ejecutar pruebas existentes de checkout y autorización de catálogo.~~ Terminado; faltan pruebas estructurales de plantillas y navegador.

### Bloque B — extraer componentes compartidos

1. Extraer una ficha de producto compartida para ambos motores.
2. Extraer selector de variantes compartido (Blade + Alpine, sin duplicar reglas).
3. Extraer contrato de carrito/pedido compartido.
4. Mantener diferencias solo en presentación y recorrido comercial.

### Bloque C — consolidar motores

1. ~~Definir identificadores canónicos `direct` y `ecommerce`.~~ Terminado.
2. Mapear `computienda` a `ecommerce` + preset tecnológico. El comando está creado, pero no se ha ejecutado sobre datos reales.
3. Crear compatibilidad de lectura para identificadores históricos.
4. No migrar filas hasta que la lectura dual esté probada.
5. ~~Actualizar el selector del Constructor para mostrar solo dos familias.~~ Terminado.
6. Dentro de Ecommerce mostrar presets visuales (incluido CompuTienda).
7. Conservar todos los settings del proyecto al cambiar de preset.

### Bloque D — migración de datos

1. ~~Crear migración reversible o comando idempotente.~~ Terminado: `storefront:consolidate-engines`; sin `--apply` solo inventaría.
2. Inventariar proyectos por `catalog_template` antes de escribir.
3. Convertir `computienda` a `ecommerce` y registrar el preset azul.
4. Mantener respaldo del valor anterior.
5. Verificar que no se modifiquen otros proyectos por falta de contexto.

### Bloque E — validación final

1. Panel de productos: crear Color + Talla y cuatro combinaciones.
2. Ecommerce: elegir combinación, agregar dos variantes al carrito y comprar.
3. Directo: elegir combinación y cotizar/comprar.
4. Filtros: combinar categoría + color + talla + precio.
5. Cambiar preset y comprobar que producto, variantes y contenido se mantienen.
6. Validar escritorio, tablet y celular.
7. Revisar consola del navegador, peticiones fallidas y logs de Laravel.
8. Solo después ejecutar migración en VPS y limpiar cachés.

## Comandos locales de validación

En este equipo `php` no está en PATH. Usar:

```powershell
C:\xampp\php\php.exe artisan view:cache
C:\xampp\php\php.exe artisan test --filter=ProductVariantsTest
C:\xampp\php\php.exe artisan test --filter=CheckoutPublicoPrecioTest
C:\xampp\php\php.exe artisan test --filter=CatalogAuthorizationTest
C:\xampp\php\php.exe artisan migrate --pretend
C:\xampp\php\php.exe artisan storefront:consolidate-engines
C:\xampp\php\php.exe artisan storefront:consolidate-engines --project=SLUG --apply
git diff --check
```

## Riesgos conocidos

- La línea de trabajo está muy modificada y contiene cambios no relacionados.
- `CompuTienda`, Ecommerce y Directo aún tienen carritos diferentes.
- Existe el agrupador heredado de modelos por color (`AgrupadorModelos`); debe coexistir temporalmente con variantes reales y luego migrarse con cuidado.
- Los carritos guardados en `localStorage` pueden contener líneas antiguas sin `variantId`; mantener compatibilidad para productos sin variantes.
- El checkout ya exige variante cuando hay matriz activa; cualquier carrito antiguo de ese producto recibirá un 422 y deberá volver a elegir opciones.
- No borrar vistas históricas inmediatamente: primero convertirlas en alias compatibles y retirar solo después de comprobar proyectos reales.

## Criterio de terminado

La consolidación termina cuando el panel muestra únicamente Directo y Ecommerce, CompuTienda está disponible como preset de Ecommerce, todas las configuraciones se conservan al cambiar de preset, y variantes/filtros/carrito/checkout pasan las mismas pruebas en ambos motores.

## Bitácora de continuidad — validación local del 2026-08-28

Esta sección es el punto de reanudación para Claude u otro agente. Debe leerse antes de modificar el bloque de tienda.

### Cambios cerrados en esta sesión

- Las migraciones de variantes se ejecutaron correctamente en el MySQL local `avan`.
- La migración `2026_08_27_000000_create_product_attributes_and_variants` ahora tolera una ejecución parcial y usa el nombre corto `papv_attribute_value_fk`; MySQL rechazaba el nombre automático por superar 64 caracteres.
- La migración de `order_items` comprueba columnas e índice antes de crearlos, de modo que puede recuperarse de una ejecución incompleta.
- El selector visual muestra solamente Ecommerce y Directo.
- Al aplicar un motor, el título “Plantilla activa” cambia inmediatamente sin exigir recarga y desaparece la advertencia de plantilla heredada.
- `Directo` ya no oculta productos disponibles que todavía no tengan categoría: el controlador crea en memoria el grupo virtual `Productos` con ID `-1`; no escribe una categoría en la base.
- Ecommerce también incorpora esos productos sin categoría en categorías destacadas, productos destacados y catálogo.
- `/SLUG` y `/SLUG/tienda` ya inician estados distintos en Ecommerce mediante `EC_INITIAL_PAGE`; el estado antiguo de `localStorage` ya no convierte Tienda en Inicio ni Inicio en Tienda.
- El navegador confirmó `/tienda` con título “Todos los productos”, filtros, seis productos y cero errores JavaScript.
- La pestaña administrativa `Variantes` abre, carga el estado vacío y permite agregar atributos y generar combinaciones.

### Validación realizada

- Navegador real (Chrome, Laravel local `127.0.0.1:8000`): selector de motores, cambio Directo ↔ Ecommerce, portada Ecommerce, catálogo Directo, Tienda Ecommerce, editor de variantes y breakpoint móvil.
- Directo: seis productos sin categoría visibles, contador correcto y sin errores de consola.
- Ecommerce Inicio: seis productos visibles en destacados y en el bloque de catálogo.
- Ecommerce Tienda: filtros de categoría/precio/disponibilidad, seis tarjetas y ruta independiente.
- Responsive a `390x844`: sin desbordamiento horizontal del documento. El panel `Tweaks` aparece únicamente porque la prueba se hizo autenticada como superadministrador; no forma parte de la vista del visitante.
- Único aviso del navegador: Tailwind por CDN en plantillas históricas. No bloquea la funcionalidad, pero debe retirarse antes de considerar el frontend listo para producción.
- `StorefrontEngineConsolidationTest` + `ProductVariantsTest`: **11 pruebas, 39 aserciones, todas correctas**.
- `StorefrontStructureV2Test`: **17 pruebas, 299 aserciones, todas correctas**.
- Total focalizado de cierre: **28 pruebas y 338 aserciones correctas**.
- `php artisan view:cache`: correcto.

### Nuevas regresiones cubiertas

`tests/Feature/StorefrontEngineConsolidationTest.php` ahora garantiza:

1. Directo muestra productos sin categoría.
2. Ecommerce emite `EC_INITIAL_PAGE = "home"` en Inicio.
3. Ecommerce emite `EC_INITIAL_PAGE = "catalog"` en Tienda.
4. Ambas rutas conservan los productos y su contenido.

### Estado local después de la limpieza

- No se ejecutó ninguna migración en el VPS.
- No se ejecutó `storefront:consolidate-engines --apply` sobre proyectos reales.
- El usuario local temporal `codex_visual_qa` fue eliminado al terminar la QA.
- El proyecto local ID `1` fue restaurado: se retiraron exactamente los 41 settings creados por la prueba desde `2026-08-28 23:17:09`; no existían settings anteriores en ese proyecto.

### Próximo trabajo recomendado, en orden

1. ~~Convertir las acciones principales del header Ecommerce en enlaces reales.~~ Terminado para Inicio, Tienda, logo, categorías, subcategorías, búsqueda y accesos móviles.
2. Extraer una tarjeta, selector de variantes y contrato de carrito compartidos por Ecommerce y Directo.
3. Probar en navegador dos variantes del mismo producto dentro del carrito y completar un pedido; no hacerlo con datos productivos.
4. ~~Añadir pruebas HTTP de autorización específicas para GET/PUT de variantes en el panel.~~ Terminado en `CatalogAuthorizationTest`.
5. ~~Crear el preset visual `tech-dark` dentro de Ecommerce sin introducir un tercer motor.~~ Terminado: Ecommerce consume los tokens compartidos y la clase tecnológica; falta únicamente la revisión visual comparativa final con una tienda real.
6. Ejecutar inventario en modo simulación: `artisan storefront:consolidate-engines`.
7. Revisar proyecto por proyecto; solo entonces ejecutar `--project=SLUG --apply` y las migraciones en staging/VPS.

### No hacer

- No borrar las vistas `computienda`, `default` ni otras históricas todavía; siguen siendo compatibilidad de lectura.
- No desplegar toda la rama sucia sin seleccionar archivos: contiene numerosos cambios del usuario ajenos a esta fase.
- No aceptar precios, stock ni `product_variant_id` sin revalidación en servidor.
- No ejecutar la consolidación global con `--apply` antes del preset y del checkout visual completo.

## Fase 1 — Componentes compartidos (2026-08-30)

### Resultado

Ecommerce, Directo y la ficha pública consumen la misma estructura de datos y las mismas reglas de variantes/carrito. Cada motor conserva su apariencia.

### Archivos creados

- `app/Storefront/VariantPresenter.php` — única serialización pública de variantes. Antes había dos copias que devolvían tipos distintos (`attributeId`/`valueId` enteros en el catálogo, cadenas en la ficha); ahora siempre cadenas y atributos ordenados por `sort_order`. Acepta `variants` o `activeVariants` cargadas.
- `resources/views/public/partials/variant-engine.blade.php` — motor JavaScript compartido `BixoVariantes` (atributos, variante seleccionada, disponibilidad por stock, etiqueta, clave de línea, línea de carrito, normalización de carritos antiguos). Se incluye antes de Alpine en los tres consumidores.
- `resources/views/public/partials/storefront-card.blade.php` — tarjeta de producto compartida. Recibe el arreglo de `CatalogQueryService::toCard()`; la apariencia la dan las clases del motor.

### Archivos modificados

- `app/Storefront/CatalogQueryService.php` — `toCard()` usa `VariantPresenter` y emite `ts` (orden "más nuevos" de Directo).
- `resources/views/public/product.blade.php` — serialización y reglas delegadas al presentador y al motor compartido.
- `resources/views/public/templates/ecommerce.blade.php` — reglas de variantes delegadas; `addToCart`/`addToCartQty` unificados en `resolverParaCarrito()` + `BixoVariantes.linea()`; `loadCart()` normaliza líneas antiguas.
- `resources/views/public/templates/direct.blade.php` — las dos copias de la tarjeta (94 líneas cada una, categoría y subcategoría) sustituidas por el partial compartido (−189 líneas); `addToCart` usa la clave de línea compartida; carga de `localStorage` normalizada.

### Reglas fijadas

- La disponibilidad, el precio pintado y la clave de línea (`producto:variante|base`) se calculan en un solo sitio. Ningún motor duplica esas reglas.
- Una línea antigua de `localStorage` sin `lineKey` recibe su clave al cargar; así se fusiona con la nueva del mismo producto en vez de duplicarse (fallo detectado y corregido en esta fase).
- El precio del cliente sigue siendo informativo: el checkout lo revalida en servidor (`CheckoutPublicoPrecioTest`, `ProductVariantsTest`).
- Los comentarios dentro de `<script>` van en sintaxis JavaScript, nunca Blade (rompe las tiendas).

### Validación

- Pruebas: `StorefrontEngineConsolidationTest` 6/32, `ProductVariantsTest` 6/18, `CatalogAuthorizationTest` 36/40, `StorefrontStructureV2Test` 17/299, `SettingsAuthorizationTest` 37/41, `CatalogTemplatesManifestTest` 4/23, `CheckoutPublicoPrecioTest` 8/19. **114 pruebas y 472 aserciones correctas.**
- `view:cache` correcto. `git diff --check` sin errores de espacios (solo avisos CRLF preexistentes).
- Navegador (Chrome headless, `127.0.0.1:8000`, proyecto local `el-tornillo-qa`): Directo y Ecommerce (Inicio y Tienda) sin errores JS, sin desbordamiento horizontal a 390px; dos productos = dos líneas, mismo producto dos veces = una línea con cantidad 2; ficha pública con motor compartido cargado.
- Carritos antiguos: probado en ambos motores sembrando una línea sin `lineKey` y agregando el mismo producto → una sola línea con cantidad 2.

### Datos locales tocados (desechables, restaurar al cerrar)

- `project_settings` del proyecto local `1` (`el-tornillo-qa`): se creó `catalog_template` (no existía; renderizaba `public.catalog`, que no es motor oficial). Valor actual: `direct`. Al terminar la Fase 2 debe **eliminarse** esa fila para dejar el proyecto como estaba.

### Pendiente de esta fase

- `public.catalog` (plantilla `default`) y las históricas siguen con su propia tarjeta; no se tocan hasta consolidar (compatibilidad de lectura).
- La tarjeta del catálogo de Ecommerce se renderiza en Alpine (`x-for`) y lee los mismos campos de `toCard()`; no se convirtió a Blade para no romper su carga incremental.

## Fase 2 — Prueba completa de variantes (2026-08-30)

### Datos desechables (proyecto local `1`, `el-tornillo-qa`)

Guiones en el scratchpad de la sesión: `fase2_sembrar.php` (idempotente), `fase2_limpiar.php` y `fase2_manifiesto.json` (ids exactos de todo lo creado).

- Producto `#7` "Polo QA Variantes" (S/ 50, comparativo S/ 70), 2 imágenes que apuntan a archivos ya existentes en `storage/app/public/products/49/` (no se copió ningún archivo).
- Atributos Color (Azul `#2563EB`, Rojo `#DC2626`) y Talla (S, M) → 4 variantes: `QA-AZ-S` S/55 stock 3 · `QA-AZ-M` S/56 stock 4 · `QA-RJ-S` S/57 **stock 0** · `QA-RJ-M` S/58 stock 6. Azul → imagen 0, Rojo → imagen 1.
- Categoría "QA Ropa" (`#1`) y subcategoría "QA Polos" (`#2`), producto asignado a la subcategoría (creadas para la Fase 3; el proyecto no tenía ninguna).
- Pedidos `#10` (Directo) y `#11` (Ecommerce) con `client_name` que empieza por `QA-FASE2-…`.
- Un producto huérfano `#8` de una siembra fallida (conflicto de SKU) fue eliminado en el momento.

### Validación (navegador Chrome headless + base de datos)

| Paso | Directo (ficha pública) | Ecommerce (ficha SPA) |
|---|---|---|
| Elegir Azul/S | S/ 55 · `QA-AZ-S` · imagen 0 | S/ 55 · imagen de la variante |
| Elegir Rojo/M | S/ 58 · `QA-RJ-M` · imagen 1 | — |
| Rojo con S elegido (stock 0) | bloqueado | bloqueado |
| Dos variantes al carrito | `7:1`×1, `7:2`×2 (persistidas en `avan_cart_1`) | `7:1`×1, `7:2`×2 (`ec_cart_el-tornillo-qa`) |
| Pedido en BD | `#10`: `product_variant_id` 1 y 2, precio **del servidor** 55/56, cant. 1/2 | `#11`: idem |
| Stock de la variante | 3→2 y 4→2 | 2→1 y 2→0 |

- La tarjeta de Directo muestra "Desde S/ 55.00 · Elegir opciones" y lleva a la ficha (con variantes no se agrega desde la tarjeta).
- Incidencia propia corregida en el acto: un paréntesis sobrante de mi parche en `product.blade.php` dejó la ficha sin Alpine unos minutos.

### Modificado

- `resources/views/public/product.blade.php`: la ficha armaba su propia línea de carrito; ahora usa `BixoVariantes.linea()` y normaliza el carrito heredado al cargar.

## Fase 3 — Filtros (2026-08-30)

### Dos defectos reales encontrados y corregidos

1. **"En stock" no filtraba nada.** Ecommerce volvía a pedir el catálogo al tocar el interruptor, pero nunca enviaba el parámetro y el servidor no lo conocía. Ahora `CatalogQueryService` acepta `in_stock=1` (producto con stock nulo o >0, o alguna variante activa con stock nulo o >0) y `catQuery()` lo envía. Verificado: con un producto puesto temporalmente a stock 0 (`#6`, restaurado a 40 acto seguido) el total baja de 7 a 6.
2. **Los filtros se perdían al recargar o compartir la URL.** `catSyncUrl()` escribía `sale`, `in_stock`, `sort`, `min_price` y `max_price`, pero al arrancar solo se restauraban los atributos. Ahora se restauran todos (el orden se traduce de las claves del servidor a las del cliente).

### Verificado por HTTP (`/tienda?format=json`, `curl -g` para no expandir corchetes)

- Categoría padre (`category=1`) y subcategoría (`category=2`) → producto 7.
- OR dentro del atributo: `attribute[1][]=1&attribute[1][]=2` → 7. AND entre atributos: `attribute[1][]=1&attribute[2][]=3` → 7. Atributo inexistente (`attribute[999]`) se ignora sin romper.
- Precio (`min_price=50&max_price=60` → 7; `min_price=100` → 1), oferta (`sale=1` → 7), búsqueda (`q=Polo` → 7).
- Orden: `price_asc`, `price_desc`, `name`, `newest`, `recommended` devuelven órdenes distintos y coherentes.
- Paginación: `per_page` solo admite 12/24/48; `page=2` con 7 productos devuelve página vacía sin error. Combinación de todos los filtros a la vez → 7.
- Aislamiento entre proyectos: solo existe un proyecto local; queda cubierto por `ProductVariantsTest::test_el_catalogo_filtra_por_atributo_sin_cruzar_proyectos` (en verde).

### Verificado en navegador

- Ecommerce: abrir `/tienda?attribute[1][]=1&in_stock=1&sale=1&sort=price_asc&min_price=50&max_price=60` restaura los seis filtros y muestra 1 producto; al cambiarlos en pantalla la URL se reescribe; a 390px el botón flotante abre el cajón de filtros y no hay desbordamiento horizontal. Sin errores JS.
- Directo (filtros del cliente sobre la tarjeta compartida, que emite `data-price`, `data-ts`, `data-name`): oferta → solo el producto con comparativo; precio 0–20 → 12 y 9.5; orden por precio ascendente correcto por grilla; `?sort=price_desc` en la URL. Sin errores JS.

### Modificado

- `app/Storefront/CatalogQueryService.php` (`in_stock`), `resources/views/public/templates/ecommerce.blade.php` (`catQuery` envía `in_stock`; el arranque restaura filtros desde la URL).

## Fase 4 — Preset tecnológico (2026-08-30)

### Comparación con la vista histórica CompuTienda

Se activó en el proyecto local `theme_preset = tech-dark` + `product_card_style = tech` y se capturó Inicio y Tienda; después se cambió temporalmente `catalog_template = computienda` para capturar la vista histórica (en local renderiza solo cabecera y pie porque el proyecto no tiene secciones de esa plantilla) y se volvió a `ecommerce`.

Patrones de CompuTienda que aportan y se trasladaron **como CSS acotado a `.theme-tech-dark`** (sin copiar código ni layout, sin tercer motor):

- Títulos de columna del pie con subrayado de marca.
- Fichas de categoría con borde teñido de marca, degradado sutil e icono con halo (sobre fondo oscuro las fichas se perdían).
- Ya existían: barra de acento en títulos de sección, halo en el botón principal, borde de marca en la tarjeta al pasar el cursor, tarjeta `tech` con borde superior.

No se trasladan: ticker superior, mega-botón de categorías, columna de atención comercial (son contenido/secciones, no piel; la cabecera y el pie de Ecommerce ya cubren esas funciones).

### Defecto real corregido

Con `tech-dark`, **nombre y precio de las tarjetas eran ilegibles**: un bloque CSS posterior ("rediseño") fijaba `#1f2937`, `#111827`, borde `#ececec` y fondo `#fff` sobre la tarjeta oscura. Ahora esos cinco valores usan tokens (`--text-primary`, `--text-muted`, `--border`, `--bg-surface`). Verificado por estilos calculados: nombre y precio `rgb(241,245,249)` sobre tarjeta `rgb(17,26,46)`.

### Invariantes al cambiar de preset

Medido con `tech-dark` → `classic` → `tech-dark`: productos 7, variantes del producto 7 = 4 (stock total 7), categorías 2, ajustes 3, secciones de Inicio 6 — idénticos; solo cambia la clase de `body`. Sin errores JS; sin desbordamiento a 390px.

Nota sobre las capturas de página completa: el cajón de filtros móvil aparece "al pie" porque es un elemento fijo desplazado fuera del viewport (`translateY` = alto de la ventana); no es visible en pantalla ni un defecto.

### Modificado

- `resources/views/public/templates/ecommerce.blade.php`: colores fijos de la tarjeta → tokens; CSS acotado `.theme-tech-dark` para pie y fichas de categoría.

## Fase 5 — Inventario de proyectos (2026-08-30, simulación)

`C:\xampp\php\php.exe artisan storefront:consolidate-engines` → «No hay proyectos CompuTienda pendientes de consolidar.» El comando solo lista proyectos con `catalog_template = computienda`; el inventario completo local se tomó de la base:

| ID | Proyecto | Slug | Plantilla actual | Conversión propuesta | Ajustes que se conservan | Riesgos |
|---|---|---|---|---|---|---|
| 1 | Ferretería El Tornillo | `el-tornillo-qa` | `ecommerce` (puesta por esta QA; no tenía ajuste → renderizaba `public.catalog`) | Ninguna (ya es motor oficial) | `theme_preset`, `product_card_style` (creados por esta QA; se retiran al cerrar) | Proyecto de pruebas local; sin datos reales |

**Lo que hará `--apply` sobre un proyecto CompuTienda real** (leído del comando): guarda `catalog_template_before_engine_consolidation` con el valor anterior (solo si no existe), pone `catalog_template = ecommerce` y, únicamente cuando el proyecto no tenía tema explícito, asigna `theme_preset = tech-dark` y `product_card_style = tech`. No toca productos, categorías, variantes, menú ni otras claves.

**El inventario de producción no se ejecutó** (regla: no trabajar sobre el VPS). Para obtenerlo, quien tenga acceso debe ejecutar en ARIN, en simulación: `php artisan storefront:consolidate-engines` (sin `--apply`). Riesgos a revisar proyecto por proyecto antes de aplicar: tiendas con `header_*`/`section_head_*` propios de CompuTienda (se conservan como ajustes pero Ecommerce no los consume todos), secciones de portada de CompuTienda que no existen en Ecommerce, y dominios personalizados con caché de HTML.

**No se ejecutó `--apply`** ni global ni por proyecto.

## Fase 6 — Pruebas finales y cierre (2026-08-30)

### Ejecutado en secuencia (Windows: nunca dos compiladores Blade a la vez)

| Paso | Resultado |
|---|---|
| `artisan view:cache` | correcto |
| `StorefrontEngineConsolidationTest` | 6 pruebas, 32 aserciones |
| `ProductVariantsTest` | 6 pruebas, 18 aserciones |
| `CatalogAuthorizationTest` | 36 pruebas, 40 aserciones |
| `StorefrontStructureV2Test` | 17 pruebas, 299 aserciones |
| `SettingsAuthorizationTest` | 37 pruebas, 41 aserciones |
| `CatalogTemplatesManifestTest` | 4 pruebas, 23 aserciones |
| `CheckoutPublicoPrecioTest` (extra, Fase 1) | 8 pruebas, 19 aserciones |
| `git diff --check` | limpio (solo avisos CRLF preexistentes) |

**Total: 114 pruebas y 472 aserciones correctas.**

### Navegador (Chrome headless, `127.0.0.1:8000`)

- Escritorio 1366, tablet 768, celular 390: sin desbordamiento horizontal en Ecommerce Inicio, Ecommerce Tienda y Directo.
- 0 peticiones HTTP fallidas (`performance` del navegador); 0 errores JavaScript nuevos.
- Inicio y Tienda independientes (`EC_INITIAL_PAGE` = `home` / `catalog`).
- Directo (fondo claro `rgb(248,250,252)`, tarjeta `d-card`) y Ecommerce `tech-dark` (fondo `rgb(11,18,32)`, `body.theme-tech-dark.card-style-tech`) se ven claramente distintos; el preset cambia de verdad la apariencia.

### Datos temporales: creados y eliminados

`fase2_limpiar.php` borró exactamente lo del manifiesto: 2 pedidos, 4 líneas, 4 variantes, 4 valores, 2 atributos, 2 imágenes, 1 producto, 2 categorías. Después se retiraron los 3 ajustes creados por la QA (`catalog_template`, `theme_preset`, `product_card_style`) y se restauró el stock del producto `#6`. Estado final del proyecto local `1`: 0 ajustes, 6 productos, 0 categorías, 9 pedidos, 0 variantes, 0 atributos — idéntico al inicio.

### Resumen de archivos de esta sesión

- Creados: `app/Storefront/VariantPresenter.php`, `resources/views/public/partials/variant-engine.blade.php`, `resources/views/public/partials/storefront-card.blade.php`.
- Modificados: `app/Storefront/CatalogQueryService.php`, `resources/views/public/product.blade.php`, `resources/views/public/templates/ecommerce.blade.php`, `resources/views/public/templates/direct.blade.php`, este documento.
- Migraciones ejecutadas: **ninguna** (las de variantes ya estaban aplicadas en local). Nada en el VPS. Sin `--apply`.

### Riesgos encontrados

- Tailwind por CDN en la ficha pública y en las plantillas históricas (preexistente).
- La tarjeta del catálogo de Ecommerce sigue en Alpine (`x-for`); comparte datos y reglas, no el Blade de la tarjeta.
- `public.catalog` (plantilla `default`) y las históricas mantienen su tarjeta y su carrito propios hasta consolidar.
- El inventario de producción sigue pendiente de ejecutarse en ARIN (solo lectura).

### Trabajo pendiente e instrucciones exactas para continuar

1. En ARIN, solo lectura: `php artisan storefront:consolidate-engines` y revisar el inventario proyecto por proyecto contra los riesgos de la Fase 5.
2. Desplegar esta fase por archivos explícitos (no la rama sucia): los 3 creados + los 4 modificados, comparando md5 con producción antes de pisar, y ejecutar en el VPS las 2 migraciones de variantes + `view:cache`.
3. Solo con aprobación nueva: `storefront:consolidate-engines --project=SLUG --apply`, un proyecto a la vez, verificando su portada y Tienda después.
4. Retirar Tailwind CDN de la ficha pública y de las plantillas históricas antes de considerar el frontend listo.
5. Cuando todos los proyectos CompuTienda estén convertidos y verificados, retirar las vistas históricas (hoy compatibilidad de lectura).

## Actualización de continuidad — navegación canónica y permisos

- El header Ecommerce ya usa enlaces Laravel reales para Inicio y Tienda en escritorio y celular.
- El logo vuelve al Inicio real; categorías y subcategorías abren su URL canónica de Tienda.
- “Todas las categorías” y “Ver todo el catálogo” ya no dependen solamente del estado Alpine.
- La búsqueda del header navega a `/tienda?q=...`; una categoría sugerida navega a `/tienda?category=...`.
- `StorefrontEngineConsolidationTest` fija los enlaces reales de Inicio y Tienda.
- `CatalogAuthorizationTest` cubre GET y PUT del editor de variantes: `catalog.ver` permite consultar pero no escribir; `catalog.editar` habilita la escritura.
- Validación focalizada actual: **70 pruebas y 415 aserciones correctas** entre motores, variantes, autorización, estructura V2, persistencia de presets y contrato del manifiesto.
- El comando de consolidación conserva cualquier tema explícito y asigna `tech-dark` + tarjetas `tech` cuando CompuTienda no tenía overrides.
- Ecommerce aplica realmente los tokens del preset; antes el valor se guardaba, pero solo la vista histórica CompuTienda lo consumía.
- El asistente rápido ya no vuelve a guardar `computienda`: Tecnología, Ferretería y Mayorista usan Ecommerce con presets editables.
- `php artisan view:cache` terminó correctamente.
- En Windows, ejecutar las pruebas y `view:cache` en secuencia: dos compiladores Blade simultáneos pueden competir por el mismo temporal y producir `rename ... Acceso denegado`.

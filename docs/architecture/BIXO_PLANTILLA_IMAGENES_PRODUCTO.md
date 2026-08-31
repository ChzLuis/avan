# Plantilla automática de imágenes de producto

Implementado el 2026-08-30. Constructor → **Catálogo** → *Plantilla de imágenes de producto*.

---

## 1. Auditoría previa (estado antes de tocar nada)

| # | Pregunta | Hallazgo |
|---|---|---|
| 1 | Pipeline de imágenes | `App\Support\Imagen\ProcesadorImagenes` (GD puro). Archiva el original, endereza por EXIF, recorta según perfil y escribe variantes 400/800/1200 en AVIF/WebP/JPG. |
| 2 | Dónde vive el original | Se archiva en `originales/{carpeta}/{base}.{ext}`, fuera de la web. La foto servible queda en el disco `uploads` (`public/uploads/products/{id}/…`). |
| 3 | Relación con producto/galería | `product_images` (product_id, url, is_main, sort_order). `mainImage()` = `is_main`. En producción: 392 imágenes de 326 productos. |
| 4 | Dónde encaja en el constructor | Etapa `stages/catalog.blade.php`, que ya reúne diseño de tarjeta, proporción y fondo de la foto. |
| 5 | Modelos/tablas a tocar | Nueva `product_image_templates`; 5 columnas nuevas en `product_images`. Ninguna columna existente se modifica. |
| 6 | Cola/jobs | Producción: `QUEUE_CONNECTION=database`, worker PM2 `arin-queue` **online**, cron `schedule:run` activo, 0 jobs fallidos. Local: `sync`. Existen `jobs` y `job_batches`. |
| 7 | Librería gráfica | GD en local; GD **e Imagick** en producción. Sin Intervention ni dependencias de pago. |
| 8 | Resolución en el storefront | `Product::getMainImageUrlAttribute()` — el propio código lo llama *"punto único de salida de la foto del producto"*. Los 146 usos de `main_image_url` pasan por ahí. |
| 9 | Riesgos de regresión | Ese accesor lo usa todo el catálogo: cualquier fallo deja tiendas sin fotos. Se mitiga con respaldo al original en cada rama posible. |
| 10 | Plan | El de la sección 2. |

**Sobre quitar el fondo (punto 11 del encargo):** el sistema **no** tiene hoy ninguna capacidad de recorte por IA. No se ha añadido ninguna dependencia externa ni de pago. La arquitectura queda desacoplada: el compositor recibe una imagen y la encaja; el día que exista un recortador, se aplica antes de componer sin tocar nada más.

---

## 2. Arquitectura

```
ProductImageTemplate  (configuración por tenant, con huella)
        ↓
CompositorProducto    (dibuja: fondo → marca de agua → producto → logo)
        ↓
GeneradorImagenProducto  (persiste en generated_url, nunca en url)
        ↓
GenerarImagenesProducto  (job por tandas de 15, se re-encola)
        ↓
ResolutorImagenProducto  (decide qué ve el cliente)
        ↓
Product::getMainImageUrlAttribute()  (punto único ya existente)
```

Ninguna regla vive en el controlador ni en la vista.

### Archivos nuevos

- `app/Models/ProductImageTemplate.php`
- `app/Support/Imagen/CompositorProducto.php`
- `app/Support/Imagen/GeneradorImagenProducto.php`
- `app/Support/Imagen/ResolutorImagenProducto.php`
- `app/Jobs/GenerarImagenesProducto.php`
- `app/Http/Controllers/ProductImageTemplateController.php`
- `resources/views/settings/builder/partials/image-template.blade.php`
- `resources/views/settings/builder/partials/image-template-script.blade.php`
- `database/migrations/2026_08_30_140000_create_product_image_templates.php`
- `tests/Feature/ProductImageTemplateTest.php`

### Archivos modificados

- `app/Models/Product.php` — el accesor consulta el resolutor.
- `app/Models/ProductImage.php` — campos de la versión generada.
- `app/Http/Controllers/Catalog/ProductController.php` — encola la plantilla al subir una foto.
- `routes/web.php` — 7 rutas nuevas.
- `resources/views/settings/builder/stages/catalog.blade.php`, `index.blade.php`, `styles.blade.php`.

### Migración

`product_image_templates` (project_id, name, is_active, enabled, config JSON, hash, updated_by) y en `product_images`: `generated_url`, `generated_hash`, `generated_at`, `generation_status`, `generation_error`. Todo aditivo y con guardas `hasColumn`.

---

## 3. Cómo funciona

**El original es intocable.** `product_images.url` no se escribe jamás. La composición va a `generated_url` (`uploads/generadas/{project}/{product}/{imagen}-{huella}.webp`). Regenerar parte SIEMPRE del original, así que retocar la plantilla veinte veces no degrada la foto.

**ON/OFF.** El interruptor solo cambia `enabled`. Con la plantilla apagada el resolutor devuelve el original al instante y **no se borra ningún archivo generado**; volver a encenderla recupera las imágenes ya compuestas sin regenerar nada.

**Vista previa fiel.** La compone el servidor con el mismo `CompositorProducto` que genera lo publicado, y llega en base64. Es imposible que la vista previa y la imagen final difieran, que era el riesgo de pintar el canvas por separado. El navegador solo arrastra los tiradores; al soltar, pide la composición real.

**Posiciones en porcentaje.** Logo, marca de agua y producto se guardan en `%` del lienzo, nunca en píxeles: el resultado es idéntico a 800, 1200 o 1600 px de ancho. Hay rejilla 3×3 y arrastre libre.

**No se regenera lo que no cambió.** La huella resume solo lo que afecta al píxel: renombrar la plantilla o activarla no dispara nada. Si la huella coincide y el archivo existe, se salta.

**Procesamiento masivo.** Tandas de 15 imágenes que se re-encolan: sin timeouts ni picos de memoria. Estados `pendiente/procesando/completado/error` y barra de progreso que se consulta sin bloquear la pantalla.

**Productos nuevos.** Al subir una foto con la plantilla activa se encola su composición automáticamente, respetando la elección de galería.

**Cambio de logo del negocio.** Se detecta y se **avisa**; nunca se regeneran cientos de imágenes en silencio.

**Respaldos.** Sin versión generada → original. Fallo al componer → original y estado `error`. Sin logo → se genera sin logo. Nunca un producto sin imagen.

**Aislamiento.** Ninguna acción acepta un `project_id` del navegador. El job filtra por proyecto con un `join`, así que un id ajeno inyectado en la carga del trabajo no se procesa. El logo "del negocio" se resuelve desde el proyecto de la plantilla, no desde la sesión.

**Seguridad.** `rutaLocal()` normaliza la referencia, corta el path traversal (`..`) y exige que el archivo real esté dentro de `public/uploads` o `storage/app/public`. El formato se valida por contenido (`getimagesize`), no por extensión; se rechazan lienzos de más de 50 Mpx y recursos de más de 8 MB.

---

## 4. Pruebas

`tests/Feature/ProductImageTemplateTest.php` — **19 pruebas, 62 aserciones, todas en verde**. Cubren las letras del encargo:

**Ámbitos de aplicación** (§17): al de prueba, **a una categoría** (con su selector), y a todo el catálogo — los tres con confirmación previa salvo el de una sola foto.

A OFF usa original · B ON usa generada · C sin generada cae al original · D no sobrescribe el original (compara bytes) · E/M configuración persistida · F/G aislamiento de plantilla y logo · H el masivo respeta el tenant · I rechaza rutas fuera de carpeta y archivos ilegibles · J producto nuevo · K un fallo no rompe el catálogo · L regenerar parte del original · N solo esa categoría · O solo los seleccionados · P galería opcional · Q apagar no borra archivos. Más una de rendimiento (no regenerar sin cambios).

Las pruebas escriben PNG reales en disco y los limpian al terminar.

---

## 5. Pendientes reales

- **Quitar fondo automáticamente**: no implementado a propósito (requiere dependencia externa). La arquitectura lo admite sin refactor.
- ~~Plantillas guardadas con nombre~~ — **hecho**: "Guardar como plantilla nueva" congela la configuración actual con nombre propio y la deja activa; el selector de la cabecera cambia entre ellas. Cambiar de plantilla **no** regenera ni borra nada: solo cambia la que manda, y el catálogo se regenera cuando el comerciante lo pide. Un id de otra tienda devuelve 404.
- **Importación por Excel**: hoy no crea imágenes, así que no había nada que enganchar. El método `encolarPlantilla()` está aislado y listo para llamarse desde ahí si algún día importa fotos.
- **Despliegue**: nada subido al VPS. Requiere la migración y `view:cache`.

---

# Anexo — Texto de ejemplo en páginas de contenido (2026-08-30)

## El problema

`Nosotros`, `Términos` y `Privacidad` se pintan campo a campo (`body`, `history`, `mission`, `vision`, `values`, `team`) y **cada bloque se oculta si su campo está vacío**. Una página recién creada salía con el título y nada más: una tarjeta en blanco en la tienda del cliente.

## La decisión: relleno sí, "Lorem ipsum" no

Estas páginas **las ve el cliente final**. Se descartó el latín de imprenta por dos motivos concretos:

1. **"Lorem ipsum dolor sit amet" se lee como un error**, no como un marcador de posición. Deja peor al negocio que la página vacía que veníamos a arreglar.
2. Un relleno "creíble" sería peor todavía: escribir *"20 años de experiencia"* o *"tres sedes"* sería **afirmar datos falsos** sobre un negocio real ante sus propios clientes.

El relleno es español corriente, **genérico y cierto para cualquier negocio** ("nos dedicamos a ofrecer productos de calidad y una atención cercana"), con el nombre real insertado. Sirve tal cual si el comerciante nunca lo cambia, y una prueba impide que se cuele un año, una cifra de clientes o de sedes.

## Cómo funciona

`App\Support\ContenidoEjemplo` — un único sitio con los textos por página y campo.

- **Solo rellena huecos**: lo que el negocio escribió no se toca jamás.
- **Solo en memoria**: no se escribe en la base, así que el día que el comerciante escriba lo suyo no hay relleno guardado que limpiar.
- **Se puede apagar**: interruptor en Constructor → Páginas (`pages_placeholder`), encendido por defecto.
- Cubre las tres entradas que pintan una página (`page()`, `about()` y `productionPageView()`), que es la que usan las plantillas reales.

## Bug preexistente encontrado y corregido

`StorePageController::productionPageView()` pasaba un `StorefrontContext` al 4.º argumento de `prepararCatalogo()`, que espera un **array de overlay de settings**. `TypeError` → **`/nosotros` y `/contacto` devolvían 500 en toda plantilla de producción**.

Está en el commit `433dddd` (refactor de contexto canónico) y **no se ha desplegado**: producción no tiene esa llamada y `/nosotros` responde 200 allí. Se corrigió pasando el overlay vacío, que es lo que corresponde al camino público.

## Pruebas

`tests/Feature/ContenidoEjemploPaginasTest.php` — **8 pruebas, 39 aserciones**: rellena con el nombre real, no es lorem ni inventa datos, no pisa lo escrito, se puede apagar, la página sale con diseño, el texto del negocio manda, no se guarda en base, y términos/privacidad tienen su propio ejemplo.

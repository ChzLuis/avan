# Plan de pruebas del constructor de tienda

## Objetivo

Demostrar el flujo completo formulario → validación → persistencia → preview → publicación → tienda pública, con aislamiento por proyecto y compatibilidad al cambiar de plantilla.

## Estrategia

- Unitarias para contratos, normalización y resolución de settings.
- Feature para rutas/controladores/BD.
- Integración de Blade para el HTML público.
- Navegador para JavaScript, responsive, drag & drop, pop-up, carrito y checkout.
- Regresión visual por plantilla y breakpoint.
- Pruebas de datos/migraciones sobre una copia anonimizada.

## Matriz mínima de plantillas

| Prioridad | Plantilla | Razón |
|---|---|---|
| P0 | CompuTienda | Implementación especializada y tienda objetivo actual. |
| P0 | Ecommerce | Plantilla productiva con catálogo complejo. |
| P0 | Direct | Caso sin hero y catálogo directo. |
| P1 | Default | Compatibilidad histórica. |
| P1 | Una por familia visual | Editorial, dark, organic, servicios y rubro regulado. |
| P2 | Todas las registradas | Contrato completo antes de declarar soporte total. |

Breakpoints obligatorios: 375×812, 768×1024, 1024×768 y 1440×900.

## 1. Persistencia global

Para cada fila de la matriz de settings:

1. Abrir formulario con valor inicial.
2. Guardar un valor válido.
3. Verificar fila única `(project_id,key)`.
4. Recargar administrador y comprobar el valor.
5. Abrir preview y tienda pública.
6. Cambiar plantilla y repetir la comprobación.
7. Guardar valor vacío/apagado y validar semántica.
8. Enviar valor inválido y verificar 422/errores sin mutación.

Casos especiales:

- colores inválidos;
- JSON corrupto en `checkout_fields`;
- URLs `javascript:`, `data:`, relativas y http/https;
- secretos enmascarados;
- checkboxes ausentes;
- upload demasiado grande o MIME incorrecto.

## 2. Borrador y publicación de secciones

### Casos P0

- Guardar borrador cambia solo columnas draft.
- HTML público antes/después del borrador es idéntico.
- Preview muestra draft.
- Publicar una sección copia todos sus campos y limpia draft.
- Publicar todo conserva orden y datos de todas las secciones.
- Restablecer crea borrador, no cambia público.
- Reordenar crea únicamente orden draft.
- Dos requests simultáneos no pierden cambios silenciosamente.

### Estado y programación

- `is_enabled` publicado/draft.
- fecha futura: Programado y no visible todavía.
- fecha final pasada: Expirado y no visible.
- rango inválido: rechazo.
- timezone America/Lima alrededor de medianoche.
- PC/tablet/móvil en draft y publicado.
- contenido vacío con estado publicado: mostrar diagnóstico en admin.

## 3. Cada sección de Inicio

### Banner/slider

- Single con imágenes desktop/móvil.
- Slider 0/1/4/12 items; solo habilitados.
- Orden, autoplay, intervalo y `prefers-reduced-motion`.
- CTA interno, externo válido, vacío e inseguro.
- Reemplazo de imagen conserva la anterior cuando no se sube archivo.

### Beneficios

- 0–4 items, habilitados/deshabilitados y orden.
- Cada icono y fallback.
- Imagen opcional.
- Sección publicada sin items: diagnóstico y ausencia controlada.

### Anuncios

- Cantidad 1–4.
- Item futuro, vigente y expirado.
- Fechas por item y orden.
- Imagen/CTA/URL.

### Categorías destacadas

- Selección automática/manual.
- Orden manual independiente de `whereIn`.
- Categoría inactiva, eliminada, vacía, con hijos y de otra tienda.
- Límite 1/12 y rechazo fuera de rango.
- Images/icons/buttons y futuras variantes avanzadas.
- Forma, layout, fit, conteo, “Ver todo”, colores y contraste.
- Columnas/carrusel en los cuatro breakpoints.

### Solo por hoy

- Sin fecha, fecha futura y fecha expirada.
- Acción `hide` y `message` en servidor y al llegar a cero en navegador.
- “24 horas” con timezone correcto.
- Imagen presente/ausente, fondo válido, CTA.
- Cambio de plantilla conserva exactamente el estado.

### Productos con descuento

- Automático solo incluye `compare_price > price`.
- Manual respeta IDs/orden/límite.
- Producto inactivo/eliminado/de otra tienda.
- Grid/carrusel y columnas responsive.
- Combinaciones de precio anterior/actual/porcentaje.

### Productos destacados

- Selección automática/manual, orden y límite.
- Flechas, swipe, autoplay y reduced motion.
- Confirmar que no hay duplicado ni desaparición con `ownFooter`.

### Blog

- 0/2/3 artículos habilitados.
- Slug único, ruta listado/post, post deshabilitado.
- Imagen/fecha/tag/resumen/CTA.
- V2 activo/inactivo y cada plantilla productiva.

## 4. Menú y encabezado

### Menú

- CRUD aislado por proyecto.
- Todos los destinos: Inicio, Tienda, Productos, categoría, subcategoría, Nosotros, Contacto, Blog, página y externo.
- Renombrar no cambia destino.
- Ocultar menú no deshabilita/elimina página.
- `_self` y `_blank` con `rel` seguro.
- Un nivel de submenú; rechazo de ciclos, padre de otra tienda y tercer nivel.
- Drag & drop persiste orden después de recargar.
- Visibilidad PC/tablet/móvil.
- Dominio personalizado y URL con slug.
- Estado activo correcto por ruta/categoría.

### Encabezado

- Logo y fallback.
- Colores, hover, activo y contraste.
- Tipografía/tamaño/alto dentro de límites.
- Sticky on/off.
- Búsqueda/contacto/carrito on/off.
- Drawer/compact/desktop en breakpoints.
- Menú siempre antes del slider, sin franja duplicada debajo.

## 5. Separación de páginas

Para cada plantilla soportada:

- `/slug` contiene Inicio y no contiene catálogo completo/paginación.
- `/slug/tienda` contiene catálogo, filtros, búsqueda y paginación.
- Nosotros, Contacto, Blog y página personalizada comparten header/footer.
- Inicio/Tienda tienen títulos, canonical y estado activo correctos.
- No existe una segunda navegación bajo el hero salvo bloque configurable de categorías.
- Ruta inexistente y página deshabilitada devuelven el estado acordado.

## 6. Catálogo, carrito y checkout

- Búsqueda escapando `%` y `_`.
- Categoría padre incluye hijos; subcategoría solo sus productos.
- Precio mínimo/máximo y valores no numéricos.
- Oferta y combinaciones de filtros.
- Orden recomendado/precio/nombre/nuevo.
- Paginación 12/24/48 conserva query string.
- Sin N+1 en imágenes/categorías.
- Agregar, cambiar cantidad, eliminar, persistir carrito.
- Modo compra/cotización, mostrar/ocultar precio.
- Campos de checkout fijos/custom y obligatorios.
- Envío, umbral gratis y dirección.
- Métodos de pago y detalles.
- Validación servidor nunca confía en precio enviado por cliente.

## 7. Pop-up

- Inactivo, futuro, vigente y expirado.
- Delay 0/2/60.
- Frecuencia session/day/always, incluidos reload y nueva sesión.
- PC/móvil y definición esperada para tablet.
- Cerrar por botón, Escape y clic exterior si se admite.
- Focus trap, restauración de foco y bloqueo de scroll.
- CTA válida/insegura.
- Sin salto de layout ni bloqueo de navegación.

## 8. Páginas institucionales y contacto

- Guardar cada bloque de Nosotros y toggles independientes.
- Galería 0/8/9 archivos, tipo y tamaño.
- CTA interna/externa/insegura.
- Contacto con teléfono/email opcional u obligatorio.
- Mensaje, privacidad y honeypot/rate limit si se agrega.
- Confirmación configurable.
- Fallo de correo no pierde el mensaje guardado.
- Mapa solo mediante URL/datos seguros; no HTML arbitrario.
- Redes http/https.

## 9. Plantillas y precedencia

- Aplicar plantilla cambia `catalog_template` solo en la tienda activa.
- No cambia settings de otra tienda.
- Los settings globales existentes sobreviven si esa es la política aprobada.
- Defaults completan solo vacíos.
- Cambiar CompuTienda → Ecommerce → Direct modifica diseño, conserva contenido y no duplica bloques.
- `ProjectTemplate` activo y `project_settings` resuelven precedencia documentada.
- Datos heredados/aliases producen el mismo view-model canónico.
- Plantilla inexistente usa fallback controlado.

## 10. Seguridad y aislamiento

- Usuario sin acceso recibe 403 en todas las rutas settings.
- IDs de categorías/productos/menús/páginas de otro proyecto se rechazan.
- URL externa solo http/https.
- XSS en títulos, resumen, menú, footer, mapa y mensajes.
- CSRF en formularios y fetch.
- Upload con SVG activo, doble extensión y MIME falso.
- Rate limit en contacto/pedidos.
- Ningún secreto de pago aparece en HTML/JSON/log.

## 11. Rendimiento

- Presupuesto de consultas para Inicio, Tienda y página de producto.
- 1, 100 y 10 000 productos; 100 categorías; 100 opciones de menú.
- Imágenes lazy excepto hero/LCP.
- Sin consultas a BD dentro de loops Blade.
- Caché invalidada al publicar sin servir borrador.
- Métricas LCP/CLS/INP en móvil; objetivo inicial CLS < 0.1.

## 12. Regresión visual y accesibilidad

- Capturas base por plantilla/breakpoint/tema.
- No hay espacio blanco grande, salto al seleccionar ni overflow horizontal.
- Contraste WCAG AA para texto/controles.
- Navegación completa con teclado.
- Focus visible y orden lógico.
- Labels, nombres accesibles, `aria-expanded` en menús.
- Zoom 200 %.
- Reduced motion.

## Suite existente: cobertura y vacíos

Archivos relevantes existentes:

- `GlobalTemplateSettingsTest.php`
- `StorefrontHomepageBuilderTest.php`
- `StorefrontStructureV2Test.php`
- `CatalogTemplatesManifestTest.php`
- `PublicTemplateRuntimeTest.php`
- `QrSettingsTest.php`

Vacíos prioritarios:

1. borrador no altera flags públicos;
2. fechas draft/publicadas;
3. Ecommerce/Direct con `storeView`;
4. páginas productivas y `is_enabled`;
5. Blog sin dependencia contradictoria de V2;
6. partials realmente presentes en el Diseñador;
7. cada opción de settings consumida por cada plantilla;
8. pruebas de navegador/CLS;
9. duplicados e índices;
10. categorías destacadas avanzadas y oferta expirada.

## Criterios para autorizar publicación

- 100 % de P0 verde.
- Sin mutación pública al guardar borrador.
- Sin acceso cruzado entre proyectos.
- Inicio/Tienda/Páginas separados en CompuTienda, Ecommerce y Direct.
- Cambio de plantilla conserva contenido y muestra identidad visual distinta.
- Menú/header/footer compartidos y configurables.
- Sin 404 inesperado en destinos visibles.
- Sin regresión visual grave en cuatro breakpoints.
- Migraciones probadas `up` y `down` sobre copia de datos.
- Plan de rollback validado.

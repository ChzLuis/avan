# Auditoría de tiendas — capturas

Capturas de las tres tiendas en producción, para revisión externa.

## Qué hay en `capturas/`

Por cada tienda (`tecsist`, `babytoncito`, `megahogar`):

| Archivo | Contenido |
|---|---|
| `-01-inicio-escritorio` | Página de inicio completa, 1440 px |
| `-02-inicio-movil` | Página de inicio completa, 390 px |
| `-03-catalogo-escritorio` | Catálogo con filtros |
| `-04-catalogo-movil` | Catálogo en móvil |
| `-05-ficha-producto` | Ficha de un producto |
| `-06-checkout-escritorio` | Finalizar compra |
| `-07-checkout-movil` | Finalizar compra en móvil |
| `-secNN-<nombre>` | Cada sección del inicio por separado |

## Contexto necesario para interpretarlas

**Las tres tiendas usan la misma plantilla** (`resources/views/public/templates/computienda.blade.php`)
con distinta configuración. Un cambio en la plantilla afecta a las tres: conviene
proponer mejoras que sean configurables, no específicas de una tienda.

- **Tecsist** — informática (Mala, Cañete). `tecsist.net`.
  **Los datos son de prueba**: fotos de banco de imágenes y descripciones ficticias.
  No tiene sentido reportar que las fotos no corresponden al producto; ya se sabe.
- **Baby Toncito** — ropa de bebé. `babytoncito.arindg.com`.
  Tiene dos colecciones con color propio: Niño (`#38bdf8`) y Niña (`#f472b6`),
  en `/tienda/nino` y `/tienda/nina`.
- **MegaHogar** — muebles y electrohogar (Huancavelica).
  `arindg.com/corporacion-megahogar-ccf4`.
  **Sus productos y precios vienen de una API externa y no se modifican.**

## Reglas del negocio (Perú)

- Precios en soles (S/). Pagos por Yape, Plin y transferencia bancaria.
- No hay pasarela de tarjetas todavía: el pedido se confirma por WhatsApp.
- El Libro de Reclamaciones es obligatorio y debe estar enlazado en el pie.
- La razón social y el RUC deben figurar en el sitio.

## Qué es más útil reportar

Concreto y accionable: qué elemento, en qué captura, qué está mal y qué se
propone. Las observaciones sobre jerarquía visual, legibilidad, coherencia entre
secciones y claridad del proceso de compra son las que más valor aportan.

Conviene evitar: sugerencias que dependan de fotografías reales (están
pendientes del cliente) y recomendaciones genéricas sin referencia a una captura.

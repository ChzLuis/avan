# Validación de las tres consultorías

Fecha: 2026-08-09 · Verificado en producción con navegador controlado y consultas a base de datos.

Leyenda: ✅ corregido y verificado · 🟡 mejorado, con matiz · 🔴 depende del cliente

---

## Tecsist (tecsist.net)

| # | Crítica | Estado | Verificación |
|---|---|---|---|
| B1 | Repetición de producto: 4 bloques, 31 apariciones | ✅ | 10 → 8 secciones; portada de 7.100 → 5.309px |
| B2 | Choque cromático en Promociones | ✅ | Banner recoloreado a `#0B3F63 → #1B9CD8`; ya no hay morado |
| B3 | Escala de página desproporcionada | ✅ | 5.309px en escritorio |
| B4 | Categorías circulares no encajan | ✅ | Variante `image-top`: tarjetas rectangulares con foto y conteo |
| B5 | Identidad de color intercambiable | ✅ | Primario `#42c8f0` → `#1B9CD8`; activo/hover ya no usan `#2563eb` |
| C1 | Dos secciones de ofertas compitiendo | ✅ | `daily_offer` desactivada |
| C2 | "Filas por categoría" es un catálogo dentro del inicio | ✅ | `category_rows` desactivada (−1.750px) |
| C3 | No hay bloque de servicio técnico | 🔴 | Bloque disponible; falta el texto real del cliente |
| C4 | Sin bloque de marcas | 🔴 | Sección lista; el catálogo es de prueba, no hay marcas reales |
| P0 | Zonas táctiles del encabezado a 16px | ✅ | WhatsApp, teléfono y redes a 44px |
| P0 | Fotografías reales y URLs de redes | 🔴 | Datos de prueba |

## MegaHogar (arindg.com/corporacion-megahogar-ccf4)

| # | Crítica | Estado | Verificación |
|---|---|---|---|
| B1 | Escaparate vacío: 1.127 productos sin foto | 🔴 | Bloqueo de datos, no de código |
| B2 | Un solo bloque de productos en la portada | ✅ | Portada con 7 secciones: hero, ambientes, beneficios, productos, CTA, banda informativa y Nosotros |
| B3 | Banda de beneficios se descuadra en tablet | ✅ | Variante "línea": 50px de alto, una sola fila en 900px |
| B4 | Hero 500px en escritorio y 470 en móvil | ✅ | 500 / 370px configurables desde el constructor |
| B5 | Densidad desigual entre secciones | ✅ | Tarjetas de 372px iguales en toda la portada |
| C1 | Falta el puente "quiero un sofá" → "compro este" | ✅ | Ambientes con conteo real por categoría |
| C2 | No hay bloque de precio ni financiación | 🔴 | Requiere la política real del cliente |
| C3 | "Nosotros" desconectada del inicio | ✅ | `about_preview` activada en portada |
| C4 | Sin bloque de marcas | 🔴 | Sección activa; faltan logotipos |
| P1 | Zonas táctiles a 44px | ✅ | Verificado |
| P1 | Beneficios de 5 a 4 | ✅ | 4 ítems |
| P2 | Proporción 4:3 en la foto | ✅ | `aspect-ratio: 4 / 3` |
| P2 | Fondo crema en la imagen | ✅ | `#FAF6F0` |
| P0 | Medios de pago | 🔴 | Sin configurar |

## Baby Toncito (babytoncito.arindg.com)

| # | Crítica | Estado | Verificación |
|---|---|---|---|
| B1 | Logotipo ilegible | 🔴 | Hace falta el archivo horizontal del cliente |
| B2 | Cinco tipografías | ✅ | Solo Nunito y Baloo 2 (el `monospace` venía del textarea) |
| B3 | Tarjetas de producto de distinta altura | ✅ | 561px, una sola medida en toda la portada |
| B4 | "Elige su mundo" ocupa mucho para lo que aporta | ✅ | 442 → 370px, dos tarjetas horizontales |
| B5 | Hero sobre blanco, sin contexto | 🟡 | Fondo ya en paleta de marca; falta foto con la prenda puesta |
| C1 | Duplicidad de navegación por género | 🟡 | Los chips del encabezado y "Elige su mundo" conviven; ambos a 44px |
| C2 | Falta la guía de tallas | ✅ | Sección "Tallas y cuidado de las prendas" con 4 respuestas |
| C3 | No hay bloque de material | ✅ | Algodón pima en el distintivo del hero, en la banda y en la guía |
| C4 | Sin bloque mayorista visible | ✅ | Precio mayorista plegable en la tarjeta |
| C5 | Nosotros con 205 palabras | 🔴 | Requiere la historia real |
| P0 | 31 de 61 productos sin precio | 🔴 | Se ocultan; corregir el origen |
| P1 | Zonas táctiles a 44px | ✅ | Verificado |

---

## Design review general

| Hallazgo | Estado |
|---|---|
| H1. Varias etiquetas `<h1>` por página (6 en Tecsist) | ✅ Una por página en las tres |
| H2. Zonas táctiles por debajo de 44px | ✅ Controles reales corregidos |
| M1. Baby Toncito con cinco tipografías | ✅ Dos |
| M3. Imágenes sin texto alternativo | ✅ Cero sin `alt` |
| M4. Salto de nivel en la jerarquía de títulos | ✅ Sin saltos |
| D1. Escala tipográfica con salto grande | ✅ Ratio 1,33 |
| D2. Densidad de la portada de Tecsist | ✅ |

---

## URLs (requisito añadido)

| Antes | Ahora |
|---|---|
| `tecsist.net/p/460` | `tecsist.net/producto/pc-de-escritorio-intel-i5-16gb-512gb-460` (301) |
| `tecsist.net/tecsist-yc5w` | `tecsist.net/` (301) |
| `babytoncito.arindg.com/baby-toncito-nhxj` | `babytoncito.arindg.com/` (301) |
| `babytoncito.arindg.com/baby-toncito-nhxj/tienda/nino` | `babytoncito.arindg.com/tienda/nino` (301) |
| `babytoncito.arindg.com/p/586` | `.../producto/shot-driel-polo-pima-estampa-586` (301) |

Además: título, descripción, canónico, Open Graph y datos estructurados de
producto por vista, y sitemap con las direcciones nuevas.

---

## Estructura de portada (sección D de cada consultoría)

Orden verificado en producción, de arriba abajo:

| Tecsist | Baby Toncito | MegaHogar |
|---|---|---|
| Hero | Hero | Hero |
| Beneficios | Beneficios | Beneficios |
| Promociones* | Elige su mundo | Compra por ambiente |
| Explora por categoría | Por qué algodón pima | Lo más vendido |
| Ofertas de la semana | Nuestros favoritos | Formas de pago y entrega |
| Lo más vendido | Tallas y cuidado | Nosotros |
| Servicio técnico | Venta por mayor | Asesoría por WhatsApp |
| Asesoría por WhatsApp | Conoce Baby Toncito | Preguntas frecuentes |
| Nosotros | Asesoría por WhatsApp | |

\* Promociones no figura en la estructura recomendada; se conserva porque la tienda
ya la tenía y se movió detrás de beneficios.

**Falta de la estructura recomendada:** el bloque de marcas en Tecsist y MegaHogar
(necesita los logotipos) y el de ofertas en MegaHogar (necesita descuentos reales).

## Presentación

- Tecsist: color de oferta naranja `#F97316` separado del acento del encabezado
  (un `sale_color` propio, para que el buscador y el carrito no se tiñan);
  hero de 440/340 px; carrusel de 3 diapositivas.
- MegaHogar: buscador con ejemplo ("Busca tu sofá, colchón o refrigeradora…");
  4 ambientes en móvil de los 8; hero 480/374 px.
- Baby Toncito: hero 460 px; buscador por talla; guía de tallas y venta por mayor.
- Las tres: encabezado móvil en dos filas (menú + logo + carrito, buscador debajo).
  MegaHogar pasó de 309 a 161 px antes del contenido.
- Banda informativa y bloque Nosotros ya no traen colores fijos: salen del tema.

## Lo que sigue dependiendo del cliente

- **MegaHogar**: fotografías de producto, medios de pago, logotipos de marcas.
- **Baby Toncito**: logotipo horizontal, 31 precios, medios de pago, historia para Nosotros.
- **Tecsist**: catálogo real, fotografías, redes sociales y textos de servicio técnico.
  Los banners de promoción son material de demostración: el de "Impresoras
  multifuncionales" muestra un mando de videojuegos.

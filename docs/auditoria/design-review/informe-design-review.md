# Design review — Tecsist, Baby Toncito y MegaHogar

Fecha: 2026-08-09 · Sitios en producción, datos reales · **Solo análisis, sin cambios aplicados**

## Puntuación

| Categoría | Tecsist | Baby Toncito | MegaHogar |
|---|---|---|---|
| Jerarquía visual | B | B | A− |
| Tipografía | B | **D** | B+ |
| Color y contraste | B− | C+ | B |
| Espacios y layout | A− | B | A− |
| Estados e interacción | C+ | C+ | C+ |
| Responsive | A | A | A |
| Contenido | C | C+ | C− |
| Rendimiento | A | B+ | B |
| **Diseño global** | **B** | **C+** | **B** |
| **Aspecto "hecho por IA"** | **B+** | **A−** | **B** |

---

## Primera impresión

**Tecsist.** Comunica competencia técnica. El eje visual es el hero azul con el equipo
iluminado; el ojo va al logotipo, al buscador y al botón "Ver ofertas", en ese orden.
Es la jerarquía correcta para una tienda. En una palabra: **ordenado**.

**Baby Toncito.** Comunica cercanía. El hero con la ropa sobre fondo claro funciona y
el tono encaja con el rubro. El ojo va a la prenda, al título y a "Ver colección".
Problema: el logotipo es un sello circular con texto minúsculo que no se lee a 78px.
En una palabra: **tierno**.

**MegaHogar.** Comunica solidez. La foto de sala con luz natural es la mejor imagen de
las tres tiendas, y la banda de beneficios bajo el hero refuerza confianza sin ruido.
En una palabra: **cálido**.

---

## Hallazgos por severidad

### 🔴 Alto

**H1. Varias etiquetas `<h1>` por página.**
Tecsist tiene **6**, Baby Toncito y MegaHogar **2** cada una. Debe haber una sola.
En Tecsist vienen del carrusel: cada diapositiva declara su propio `<h1>`, y aunque
solo una se ve, las seis están en el documento. Un lector de pantalla anuncia seis
títulos principales y los buscadores no saben cuál es el tema de la página.

**H2. Zonas táctiles por debajo del mínimo.**
Elementos con menos de 44px de alto: **Tecsist 97**, **Baby Toncito 51**, **MegaHogar 36**.
Los peores casos en Tecsist:

| Elemento | Tamaño real | Mínimo |
|---|---|---|
| "Cotiza por WhatsApp" | 158×**16** | 44 |
| Teléfono del encabezado | 69×**15** | 44 |
| Iconos de redes | **26×26** | 44 |
| "Ofertas" / "Novedades" | 98×**30** | 44 |

En móvil son difíciles de acertar. Es el criterio 2.5.5 de accesibilidad y afecta
directamente a la conversión: si el cliente falla dos veces al pulsar WhatsApp, se va.

### 🟠 Medio

**M1. Baby Toncito usa cinco tipografías.**
`Nunito`, `Baloo 2`, `monospace`, `Arial` y `Arial Black` conviven en la misma página.
El máximo razonable son tres, y en una tienda de ropa infantil bastan dos. Arial y
Arial Black son fuentes de sistema: donde aparecen, la página pierde el carácter que
sí tiene el resto. Tecsist (Inter + Space Grotesk) y MegaHogar (Inter + Montserrat)
están correctas.

**M2. Paletas por encima de lo recomendable.**
Colores distintos no grises: **Tecsist 31**, **Baby Toncito 34**, **MegaHogar 32**.
La referencia son 12. No es que se vean mal —las tres tienen una identidad clara—,
pero cada color extra hace más difícil mantener coherencia al añadir secciones.

**M3. Imágenes sin texto alternativo.**
Baby Toncito: **5 de 22**. Tecsist y MegaHogar: 1 cada una. Sin `alt`, un lector de
pantalla no puede describirlas y los buscadores no las indexan.

**M4. Un salto de nivel en la jerarquía de títulos** en las tres (por ejemplo, de `h2`
a `h4` sin pasar por `h3`).

### 🟡 Detalle

**D1. Escala tipográfica con salto grande.** En Tecsist, `h1` 51,2px → `h2` 33,3px es
un ratio de 1,54. Las escalas habituales usan 1,25 o 1,33. Se nota como un vacío entre
el hero y el resto.

**D2. Densidad de la portada de Tecsist.** Cuatro bloques de productos (oferta limitada,
ofertas de la semana, lo más vendido y filas por categoría) suman 31 apariciones para
46 productos. El cliente ve el mismo artículo hasta tres veces mientras baja.

---

## Lo que está bien resuelto

- **Responsive: cero desbordamiento horizontal** en 375, 768 y 1440px en las tres tiendas.
- **Rendimiento**: Tecsist carga en **640ms** (TTFB 273ms). Muy por debajo del umbral de 2s.
- **Cero errores de consola** y **cero peticiones fallidas** en las tres.
- **Espaciado**: relleno uniforme de 68px entre secciones, tarjetas de 256px y una sola
  escala de títulos de 36px tras la unificación reciente.
- **El pie** de las tres es completo y coherente: datos legales con RUC, sellos de compra
  segura, medios de pago y accesos institucionales.

---

## Aspecto "hecho por IA"

Revisadas las once señales del catálogo, **las tres tiendas salen bien paradas**:

| Señal | ¿Presente? |
|---|---|
| Degradados morados o azul-violeta | No |
| Rejilla de 3 columnas con icono en círculo | **Parcial** — la banda de confianza se le acerca |
| Todo centrado | No (alineación a la izquierda unificada) |
| Radio de esquina idéntico en todo | No |
| Manchas o formas decorativas flotantes | No |
| Emoji como elemento de diseño | No |
| Borde de color a la izquierda de las tarjetas | No |
| Titular genérico ("Bienvenido a…") | No — los tres son específicos del negocio |
| `system-ui` como fuente principal | No — las tres usan tipografías reales |

Los titulares ("Tecnología para cada pasión", "Ropita con amor para tu bebé",
"Transforma tu hogar") están escritos para cada negocio. Eso es lo que más separa una
tienda real de una plantilla.

---

## Qué haría primero

1. **Dejar un solo `<h1>` por página** — afecta a buscadores y accesibilidad en las tres.
2. **Subir a 44px las zonas táctiles del encabezado** — WhatsApp, teléfono y redes.
   Es el cambio con más efecto directo sobre las ventas por móvil.
3. **Reducir Baby Toncito a dos tipografías** — quitar Arial, Arial Black y monospace.
4. **Añadir `alt` a las 5 imágenes de Baby Toncito.**
5. **Quitar un bloque de productos de la portada de Tecsist** — "Ofertas por tiempo
   limitado" y "Ofertas de la semana" muestran lo mismo.

---

## Bloqueos que no son de diseño

Ninguna de estas tres tiendas está lista para entregar por motivos de **contenido**,
no de código:

- **Baby Toncito**: 31 de 61 productos sin precio; sin medios de pago configurados.
- **MegaHogar**: 1127 productos sin una sola fotografía; 779 sin stock; sin medios de pago.
- **Tecsist**: 5 productos sin foto; catálogo con datos de prueba.

---

## Método

Chromium controlado por CDP sobre los sitios en producción. Extracción del sistema de
diseño real (`getComputedStyle` sobre 600 elementos por página), medición de zonas
táctiles con `getBoundingClientRect`, comprobación de desbordamiento en tres anchos,
tiempos de `performance`, y revisión de consola y red. Capturas en
`docs/auditoria/design-review/` y `docs/auditoria/capturas/`.

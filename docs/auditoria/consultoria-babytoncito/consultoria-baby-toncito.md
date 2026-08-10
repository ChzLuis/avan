# Consultoría de diseño — Baby Toncito

Sitio analizado: `babytoncito.arindg.com` · datos reales de producción
**Ningún cambio aplicado.** Solo diagnóstico, propuesta y capturas.

---

## A. Diagnóstico actual

### Primera impresión

**Escritorio (1440×900) — 3 segundos.** Fondo claro con textura de puntos, chips
"General / Niño / Niña" arriba, y el hero con una prenda real sobre fondo blanco. El
ojo va a la prenda, al titular "ROPITA CON AMOR PARA TU BEBÉ" y al botón verde
azulado. La sensación es correcta: suave, limpio, de confianza.

**10 segundos.** Cuatro beneficios con icono, y "Elige su mundo" con dos círculos
grandes (Niño / Niña). La idea de los dos mundos está bien pensada para el rubro.

**Scroll completo.** "Nuestra colección" con las prendas reales, "Conoce Baby Toncito"
y la franja de ayuda. La página es **corta y limpia**: 3.700px frente a los 7.100 de
Tecsist. Se agradece.

### Respuestas directas

| Pregunta | Respuesta |
|---|---|
| ¿Profesional? | **Sí, con reservas.** El logotipo la traiciona. |
| ¿Plantilla? | **No.** Es la que menos parece plantilla de las tres. |
| ¿Económica, media, premium? | **Media-alta.** El algodón pima y la paleta suave la posicionan bien. |
| ¿Genera confianza? | **Media.** Faltan medios de pago y la mitad del catálogo no tiene precio. |
| ¿Invita a explorar? | **Sí.** La estructura es la más limpia de las tres. |
| ¿Diferenciación? | **Alta.** Verde azulado + naranja es una combinación poco vista en ropa de bebé, que suele ir a rosa/celeste literal. |
| ¿Identidad de ropa de bebé? | **Sí**, y sin caer en el tópico. |

**En una frase:** la mejor identidad de las tres, con la ejecución a medio camino.

---

## B. Problemas de diseño

**B1. El logotipo no se lee.** Es un sello circular con texto en el borde. A 78px de
alto (ya lo subí desde 62), el texto interior es ilegible. En móvil, a 44px, es una
mancha. **Un logotipo que no se lee no construye marca.**

**B2. Cinco tipografías.** `Nunito`, `Baloo 2`, `monospace`, `Arial` y `Arial Black`
conviven en la misma página. Arial y Arial Black son fuentes de sistema: donde
aparecen, la página pierde el carácter que sí tiene el resto. Deberían ser dos.

**B3. Las tarjetas de producto no miden igual.** 253×**524px**, frente a 438 en las
otras dos tiendas. La diferencia viene del bloque mayorista: los productos que lo
tienen son 86px más altos, y rompen la línea de la rejilla.

**B4. "Elige su mundo" ocupa mucho para lo que aporta.** Dos círculos grandes en 439px
de alto. La idea es buena, la ejecución desaprovecha el espacio: en esa altura caben
las dos colecciones **con producto real dentro**, no solo un círculo con una foto.

**B5. El hero muestra producto sobre blanco, no contexto.** Para ropa de bebé, la
fotografía de producto plano (flat lay) vende menos que la prenda puesta. No hay bebés
en la página de una tienda de ropa de bebé.

---

## C. Problemas de estructura

**C1. Duplicidad de navegación por género.** Arriba están los chips "General / Niño /
Niña" (colecciones) y la sección "Elige su mundo" hace exactamente lo mismo 900px más
abajo. Dos caminos al mismo sitio.

**C2. Falta la guía de tallas.** Venden de 0 a 4 años. La talla es **la** objeción de
compra en ropa infantil a distancia, y no hay ninguna referencia en la portada.

**C3. No hay bloque de material.** "Algodón pima peruano" aparece como un beneficio
pequeño entre cuatro. Es su argumento de venta principal y merece una sección propia:
qué es el pima, por qué importa para la piel del bebé.

**C4. Sin bloque mayorista visible.** Las tarjetas tienen precio por docena, así que
venden al por mayor. Eso no se anuncia en ningún sitio de la portada: quien busca
comprar para revender no sabe que puede.

**C5. Nosotros con 205 palabras.** Es la página institucional más corta de las tres.
Para una marca que vende confianza (ropa que toca la piel de un bebé), es poco.

---

## D. Nueva estructura recomendada

```
01  Barra superior          — colecciones Niño / Niña / General
02  Encabezado              — logo, buscador, carrito
03  Navegación              — categorías reales + ofertas
04  Hero                    — prenda puesta, no plano
05  Beneficios              — 4 elementos, pegado al hero
06  Elige su mundo          — REDISEÑADO: 2 mundos con producto dentro
07  Algodón pima            — NUEVO: el argumento de venta principal
08  Nuestra colección       — se mantiene
09  Guía de tallas          — NUEVO: 0 a 4 años, con medidas
10  Venta por mayor         — NUEVO: para revendedores
11  Conoce Baby Toncito     — se mantiene, ampliado
12  Preguntas frecuentes    — NUEVO: envío, cambios, tallas
13  Pie                     — se mantiene
```

### Detalle de las secciones nuevas

**07 · Algodón pima**
- Objetivo: convertir el material en argumento emocional.
- Contenido: franja a dos columnas — macro del tejido + texto corto ("El pima peruano
  es más suave y más largo que el algodón común. En la piel de un bebé, eso importa").
- Por qué convierte: una madre no compara precios de body, compara **qué toca la piel
  de su hijo**. El material es el argumento, no el precio.
- **Imagen:** macro real del tejido, no ilustración.

**09 · Guía de tallas**
- Objetivo: eliminar la objeción número uno de la ropa a distancia.
- Contenido: tabla simple — edad, talla, altura, peso. Desplegable en móvil.
- Por qué convierte: sin guía de tallas, la madre no compra o compra y devuelve. Las
  dos opciones cuestan.

**10 · Venta por mayor**
- Objetivo: captar al revendedor, que compra 12 en vez de 1.
- Contenido: franja con el precio por docena visible y botón de WhatsApp.
- Por qué convierte: el sistema ya tiene precio mayorista cargado en los productos.
  Está el motor, falta el cartel.

---

## E. Wireframe escritorio

```
┌──────────────────────────────────────────────────────────────┐
│  ELIGE TU MUNDO   [General] [Niño] [Niña]                    │ 44px
├──────────────────────────────────────────────────────────────┤
│ [LOGO]     [ buscador ................. 🔍 ]   tel   🛒 S/   │ 90px
├──────────────────────────────────────────────────────────────┤
│  Inicio   Conjuntos   Casacas   Enterizos   Contacto         │ 52px
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  HECHO EN PERÚ CON PIMA      [ bebé con la prenda puesta,    │ 460px
│  ROPITA CON AMOR              no prenda sobre blanco ]       │
│  PARA TU BEBÉ                                                │
│  [ VER COLECCIÓN ]                                           │
├──────────────────────────────────────────────────────────────┤
│  🧵 Pima   🚚 Envíos   📏 Tallas 0-4   💬 Asesoría           │ 110px
├──────────────────────────────────────────────────────────────┤
│              Elige su mundo                                  │
│  ┌───────────────────────┐  ┌───────────────────────┐        │
│  │  NIÑO                 │  │  NIÑA                 │        │ 380px
│  │  [3 prendas reales]   │  │  [3 prendas reales]   │        │
│  │  [ Ver colección ]    │  │  [ Ver colección ]    │        │
│  └───────────────────────┘  └───────────────────────┘        │
├──────────────────────────────────────────────────────────────┤
│  [ macro del tejido ]  │  ALGODÓN PIMA PERUANO               │ 300px
│                        │  Más suave, más duradero.           │
│                        │  En la piel de tu bebé, importa.    │
├──────────────────────────────────────────────────────────────┤
│  Nuestra colección                          Ver toda tienda  │
│  ┌────┐┌────┐┌────┐┌────┐┌────┐                              │ 540px
│  └────┘└────┘└────┘└────┘└────┘                              │
├──────────────────────────────────────────────────────────────┤
│  GUÍA DE TALLAS   0-3m │ 3-6m │ 6-12m │ 1-2a │ 2-3a │ 3-4a   │ 220px
├──────────────────────────────────────────────────────────────┤
│  ¿Compras para revender?  Precio por docena  [ WhatsApp ]    │ 180px
├──────────────────────────────────────────────────────────────┤
│  [ foto taller/local ] │ CONOCE BABY TONCITO                 │ 320px
├──────────────────────────────────────────────────────────────┤
│  Preguntas frecuentes                                        │ 280px
├──────────────────────────────────────────────────────────────┤
│  PIE — 4 columnas + legal + pagos + sellos                   │ 400px
└──────────────────────────────────────────────────────────────┘
```

## F. Wireframe móvil

```
┌─────────────────────┐
│ [Gen][Niño][Niña]   │  44px  ← colecciones arriba
├─────────────────────┤
│ ☰  [LOGO]      🛒   │  56px
├─────────────────────┤
│ [ buscador     🔍 ] │  48px
├─────────────────────┤
│  ROPITA CON AMOR    │
│  PARA TU BEBÉ       │  340px ← baja de 483
│  [ VER COLECCIÓN ]  │
│  [ bebé con prenda ]│
├─────────────────────┤
│ 🧵 Pima  │ 🚚 Envío │  88px  ← 2×2
│ 📏 Tallas│ 💬 Aseso │
├─────────────────────┤
│  ┌─────────────────┐│
│  │ NIÑO  [3 fotos] ││  300px ← apilado, no círculos
│  └─────────────────┘│
│  ┌─────────────────┐│
│  │ NIÑA  [3 fotos] ││
│  └─────────────────┘│
├─────────────────────┤
│ [macro tejido]      │  260px
│ ALGODÓN PIMA        │
├─────────────────────┤
│  Nuestra colección  │
│ ┌──────┐ ┌──────┐   │  580px ← 2 columnas
│ └──────┘ └──────┘   │
├─────────────────────┤
│  GUÍA DE TALLAS     │  180px ← plegable
│  ▸ Ver tabla        │
├─────────────────────┤
│  ¿Revendes?         │  160px
│  [ WhatsApp ]       │
├─────────────────────┤
│  PIE plegado        │  400px
└─────────────────────┘
```

---

## G. Sistema visual recomendado

| Elemento | Actual | Propuesta |
|---|---|---|
| **Personalidad** | — | Suave, honesta, artesanal. Nada infantilizada |
| **Primario** | `#429ea3` verde azulado | **Se mantiene.** Es su mayor acierto: evita el rosa/celeste tópico |
| **Secundario** | `#1f2937` | Se mantiene |
| **Acento** | `#F2A15C` naranja suave | **Se mantiene.** Combina bien y da calidez |
| **Colección Niño** | `#38bdf8` | Se mantiene |
| **Colección Niña** | `#f472b6` | Se mantiene |
| **Tipografías** | **5** | **Reducir a 2**: Baloo 2 (títulos) + Nunito (texto). Eliminar Arial, Arial Black y monospace |
| **Escala** | irregular | 40 / 30 / 23 / 18 / 16 (ratio 1,3) |
| **Radio** | 16 global | 8 (botones) / 16 (tarjetas) / 24 (bloques). El radio generoso encaja con el rubro |
| **Fotografía** | prenda sobre blanco | **Prenda puesta.** El producto plano informa; la prenda puesta emociona |

**El cambio de mayor impacto no es de color: es el logotipo.** La paleta está bien
resuelta. Lo que rompe la percepción de marca es un sello circular ilegible.

---

## H. Recomendaciones por sección

### Logotipo (lo más urgente)
El sello circular funciona como sello, no como logotipo de cabecera. Propongo:
- **Versión horizontal** para el encabezado: isotipo + "Baby Toncito" al lado, legible
  a 40px de alto.
- Conservar el sello circular para el pie, etiquetas y redes, donde sí funciona.
- No es rediseñar la marca: es crear la variante horizontal que falta.

### Hero
- Cambiar la fotografía: **prenda puesta**, no plano sobre blanco.
- Bajar a 460px escritorio / 340px móvil.
- El titular y el subtítulo están bien. El badge "HECHO EN PERÚ CON ALGODÓN PIMA" es
  excelente y debería repetirse en la sección de material.

### Elige su mundo
Rediseño completo: de dos círculos con foto a **dos bloques con tres prendas reales
dentro de cada uno**. Mismo espacio, mucha más información y un camino directo al
producto.

### Tarjeta de producto
Actual 253×**524px** (86px más alta que las otras tiendas por el bloque mayorista).
- **Igualar la altura**: el bloque mayorista debería ser un desplegable ("Ver precio
  por mayor") en lugar de un bloque siempre visible que descuadra la rejilla.
- Imagen en **3:4 vertical**, no cuadrada: la ropa es vertical.
- Fondo de imagen blanco está bien aquí (la ropa clara necesita contraste neutro).

### Nosotros
**205 palabras y una imagen.** La más corta de las tres. Falta:
- La historia: quién está detrás, por qué eligieron el pima.
- Fotografía del taller o de las prendas en proceso.
- Que se note que hay personas.

---

## I. Mejoras requeridas del constructor

| Propuesta | Clase | Detalle |
|---|---|---|
| Reducir a 2 tipografías | **A** | Apariencia → Tipografía |
| Rediseño de "Elige su mundo" | **B** | La variante existe; falta una que muestre producto |
| Sección de material (pima) | **A** | `about_preview` o `media_banner` sirven |
| Preguntas frecuentes | **A** | `faq` existe y está vacía |
| Guía de tallas | **C** | No existe |
| Bloque mayorista en portada | **C** | No existe |
| Mayorista plegable en la tarjeta | **C** | Hoy siempre visible |
| Foto de producto 3:4 | **C** | Hoy proporción fija |
| Logotipo horizontal | — | Recurso de marca, no configuración |

### MEJORA REQUERIDA DEL CONSTRUCTOR — 1
- **Control:** Bloque "Guía de tallas"
- **Tipo:** tabla editable — columnas configurables, N filas
- **Campos:** título, descripción, encabezados, filas, texto del botón
- **Por defecto:** desactivado
- **Sección:** Inicio → Secciones
- **Plantillas:** computienda
- **Motivo:** cualquier tienda de ropa o calzado lo necesita, no solo esta.

### MEJORA REQUERIDA DEL CONSTRUCTOR — 2
- **Control:** "Mostrar precio por mayor en la tarjeta"
- **Tipo:** selector — siempre visible / plegable / solo en la ficha
- **Por defecto:** siempre visible (comportamiento actual)
- **Sección:** Apariencia → Tarjeta de producto
- **Motivo:** hoy el bloque mayorista hace 86px más altas unas tarjetas que otras y
  rompe la rejilla.

### MEJORA REQUERIDA DEL CONSTRUCTOR — 3
- **Control:** Bloque "Venta por mayor"
- **Tipo:** franja con título, texto, condición mínima y botón de WhatsApp
- **Por defecto:** desactivado
- **Sección:** Inicio → Secciones

### MEJORA REQUERIDA DEL CONSTRUCTOR — 4
- **Control:** "Variante de colecciones con producto"
- **Tipo:** nueva variante de `featured_categories`
- **Motivo:** mostrar 2-3 productos dentro de cada colección en lugar de solo la foto.

---

## J. Recursos visuales faltantes

**CONSERVAR:** las 52 fotos de producto (son reales y de calidad), la textura de fondo.
**REEMPLAZAR:** la foto del hero (plano → prenda puesta).
**RECORTAR/ADAPTAR:** el logotipo, a versión horizontal.
**FALTA IMAGEN:**

```
IMAGEN 1
Sección:      Logotipo del encabezado
Objetivo:     que la marca se lea a 40px de alto
Desktop:      320×80 px
Mobile:       220×56 px
Formato:      SVG
Composición:  isotipo a la izquierda + "Baby Toncito" a la derecha
Estilo:       el mismo del sello actual, en disposición horizontal
Restricciones: derivar del logotipo existente, no inventar marca nueva
Nombre:       babytoncito-horizontal.svg
```

```
IMAGEN 2
Sección:      Hero
Objetivo:     emocionar, no solo informar
Desktop:      1400×900 px
Mobile:       900×1100 px
Formato:      WebP
Composición:  bebé con una prenda de la marca, luz natural suave
Estilo:       fotografía real, tonos claros, fondo desenfocado
Elementos:    bebé + prenda identificable
Espacio texto: mitad izquierda libre en escritorio
Restricciones: fotografía real con autorización de los padres. NO usar IA.
Nombre:       babytoncito-hero.webp
```

```
IMAGEN 3
Sección:      Algodón pima
Objetivo:     hacer tangible el material
Desktop:      1000×1000 px
Formato:      WebP
Composición:  macro del tejido, se ve la fibra
Estilo:       luz lateral, tonos neutros
Restricciones: foto real del tejido que usan
Nombre:       pima-macro.webp
```

```
IMAGEN 4
Sección:      Conoce Baby Toncito
Objetivo:     mostrar que hay personas detrás
Desktop:      1200×800 px
Composición:  taller, confección o preparación de pedidos
Restricciones: fotografía real. NO usar IA.
Nombre:       babytoncito-taller.webp
```

---

## K. Quick wins

1. **Reducir a dos tipografías** — quitar Arial, Arial Black y monospace desde el constructor.
2. **Añadir `alt` a las 5 imágenes que no lo tienen.**
3. **Activar preguntas frecuentes** — la sección existe y está vacía.
4. **Activar la sección de material** reutilizando `media_banner`, que ya existe.
5. **Zonas táctiles a 44px** — 51 elementos por debajo.

Cuatro de los cinco **no requieren tocar código**.

---

## L. Cambios estructurales

1. Logotipo horizontal (recurso de marca)
2. Guía de tallas (constructor)
3. Bloque de venta por mayor (constructor)
4. Mayorista plegable en la tarjeta (constructor)
5. Variante de colecciones con producto (constructor)
6. Foto de producto en 3:4 (constructor)
7. Zonas táctiles a 44px (plantilla)

---

## M. Prioridades

**P0 — bloquea la venta**
- **31 de 61 productos sin precio.** La mitad del catálogo no se puede comprar.
- Medios de pago sin configurar.
- Logotipo ilegible en el encabezado.

**P1**
- Reducir a dos tipografías.
- Guía de tallas.
- Igualar la altura de las tarjetas.
- Zonas táctiles a 44px.

**P2**
- Sección de algodón pima.
- Bloque de venta por mayor.
- Rediseño de "Elige su mundo" con producto.
- Foto de hero con prenda puesta.

**P3**
- Ampliar Nosotros con historia y fotografía.
- Preguntas frecuentes.
- Escala tipográfica.

---

## Si esta fuera mi propia tienda

**1. Lo primero: los 31 precios.** La mitad de mi catálogo no se puede comprar. Todo lo
demás es secundario frente a eso.

**2. Haría la versión horizontal del logotipo.** El sello circular es bonito y funciona
en una etiqueta cosida, pero en la cabecera de una web es una mancha. No rediseñaría
la marca: crearía la variante que falta.

**3. Cambiaría la foto del hero por una prenda puesta.** Vendo ropa de bebé y en mi web
no hay ni un bebé. El producto plano informa; la prenda puesta emociona, y en este
rubro se compra con emoción.

**4. Le daría una sección propia al algodón pima.** Es mi argumento de venta y hoy es
un icono pequeño entre cuatro. Una madre no compara precios de body: compara qué toca
la piel de su hijo.

**5. Pondría la guía de tallas.** Vendo de 0 a 4 años y no digo qué talla es cada edad.
Eso es una venta perdida o una devolución, y las dos cuestan.

**6. Anunciaría la venta por mayor.** Tengo precio por docena cargado en los productos
pero no lo digo en ningún sitio. El motor está, falta el cartel.

**7. Bajaría a dos tipografías.** Cinco es lo que más aleja esta tienda de parecer
profesional, y es lo más barato de arreglar.

**Lo que NO tocaría:** la paleta. Verde azulado con naranja en ropa de bebé, evitando
el rosa y celeste literales, es una decisión con criterio y la distingue de toda su
competencia.

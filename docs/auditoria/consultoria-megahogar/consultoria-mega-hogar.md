# Consultoría de diseño — Corporación Mega Hogar

Sitio analizado: `arindg.com/corporacion-megahogar-ccf4` · datos reales de producción
**Ningún cambio aplicado.** Solo diagnóstico, propuesta y capturas.

---

## A. Diagnóstico actual

### Primera impresión

**Escritorio (1440×900) — primeros 3 segundos.**
Se lee "TRANSFORMA TU HOGAR" sobre una sala con luz natural, dorado sobre navy. La
foto es buena de verdad: no es banco de imágenes evidente, tiene la calidez que
corresponde al rubro. El ojo va a la fotografía, luego al titular, luego al botón
dorado. Esa jerarquía es correcta.

**Primeros 10 segundos.** Aparecen los cinco beneficios y la rejilla de ambientes con
fotografía propia. Aquí la tienda gana: ocho ambientes reales con foto y número de
productos. Es lo mejor que tiene.

**Tras el scroll completo.** Y aquí se cae. "Productos destacados" son **ocho tarjetas
con "FOTO EN CAMINO"**. Después no hay nada más: una franja de ayuda, otra pequeña, y
el pie. La página promete un catálogo grande y entrega ocho cajas vacías.

### Respuestas directas

| Pregunta | Respuesta |
|---|---|
| ¿Parece empresa profesional? | **Sí, hasta la mitad.** El hero, los beneficios y los ambientes sí. El escaparate de productos rompe el hechizo. |
| ¿Parece plantilla? | **No.** Paleta propia (navy + dorado), fotografía propia, titular escrito para el negocio. |
| ¿Económica, media o premium? | **Media, con techo de premium.** La paleta apunta a premium; el catálogo sin fotos la baja a media-baja. |
| ¿Genera confianza? | **Parcialmente.** Hay RUC, sellos, dirección física y horario. Pero un producto sin foto ni precio destruye lo que construyeron los sellos. |
| ¿Invita a explorar? | **Sí hasta ambientes, no después.** Nadie pulsa "Agregar al carrito" sobre una caja gris. |
| ¿Diferenciación? | **Media.** La combinación navy + dorado la distingue de la competencia local, que suele ir a naranja/rojo de saldo. |
| ¿Identidad de muebles/hogar? | **Sí.** Es lo más logrado. El dorado sobre navy comunica mueble de calidad, no liquidación. |

**En una frase:** una tienda con buena cabeza y sin cuerpo.

---

## B. Problemas de diseño

**B1. El escaparate está vacío.** 1127 productos, **ninguno con fotografía**. Los ocho
destacados muestran el marcador "FOTO EN CAMINO". No es un problema de maquetación:
es que no hay imágenes en el sistema.

**B2. La página inicio tiene un solo bloque de productos.** Comparada con Tecsist
(cuatro bloques), MegaHogar solo tiene "Productos destacados". Para un catálogo de
1127 artículos, mostrar ocho es desaprovecharlo.

**B3. La banda de beneficios se descuadra en tablet.** Cinco elementos en dos columnas
dejan uno solo en la última fila (2+2+1). Debería ser 4 o 6, no 5.

**B4. El hero mide 500px de alto en escritorio pero solo 470 en móvil**, casi igual.
En móvil, 470px sobre una pantalla de 844 es el 56% de la primera vista: demasiado
para un teléfono donde el usuario quiere llegar al producto.

**B5. Densidad desigual.** "Productos destacados" ocupa 1249px de alto en escritorio;
"Compra por ambiente" 973px. Las dos secciones que le siguen suman 583px entre ambas
y no aportan casi nada. El ritmo de la página es: mucho, mucho, nada, nada, pie.

### Lo que NO es un problema (y parecía serlo)

Al capturar la página completa, todas las secciones tras el hero salen en blanco. Lo
verifiqué: es la **animación de entrada por scroll** funcionando (`opacity` pasa de 0
a 1 al entrar en pantalla). Comprobé además que la clase que la activa la pone
JavaScript, no el HTML: **si el JS falla, todo se ve igual**. Está bien resuelto.

---

## C. Problemas de estructura

**C1. Falta el puente entre "quiero un sofá" y "compro este sofá".** Hoy el recorrido
es: hero → ambientes → 8 destacados → pie. No hay un paso intermedio que muestre
profundidad de catálogo (más vendidos por ambiente, novedades, ofertas).

**C2. No hay bloque de precio ni financiación.** En muebles, la primera pregunta del
cliente es "¿cuánto?" y la segunda "¿puedo pagarlo en partes?". La página no responde
ninguna de las dos hasta entrar a un producto.

**C3. "Nosotros" está desconectada del inicio.** La empresa tiene 14 años y es
distribuidor oficial de Paraíso. Eso no aparece en ningún sitio de la portada, cuando
es el mayor argumento de confianza que tienen.

**C4. Sin bloque de marcas.** Venden Paraíso, y probablemente más marcas conocidas.
En electrohogar y colchones, la marca vende: verla en la portada valida la tienda.

---

## D. Nueva estructura recomendada

```
01  Barra superior          — envíos, garantía, teléfono, redes
02  Encabezado              — logo, buscador, carrito, WhatsApp
03  Navegación              — categorías + accesos (ofertas, novedades)
04  Hero                    — único, sin carrusel
05  Beneficios              — 4 elementos, pegado al hero
06  Compra por ambiente     — 8 ambientes con foto (se mantiene, es lo mejor)
07  Marcas oficiales        — NUEVO: Paraíso y demás, con logotipos
08  Lo más vendido          — 8 productos, prioridad a los que tienen foto
09  Financiación y pago     — NUEVO: cómo pagar, en cuántas partes
10  Ofertas de la semana    — NUEVO: solo si hay productos con descuento real
11  Nosotros (extracto)     — NUEVO en portada: 14 años, distribuidor oficial
12  Asesoría por WhatsApp   — se mantiene
13  Preguntas frecuentes    — NUEVO: entrega, armado, garantía, devoluciones
14  Pie                     — se mantiene
```

### Detalle por sección nueva

**07 · Marcas oficiales**
- Objetivo: validar la tienda por asociación. "Si venden Paraíso, son serios".
- Contenido: 5-7 logotipos en escala de grises, color al pasar el ratón.
- Escritorio: una fila. Móvil: carrusel de arrastre.
- Por qué convierte: en colchones y electrohogar, la marca es el primer filtro
  de decisión del comprador.
- **Imágenes necesarias:** logotipos oficiales de cada marca (los da el proveedor,
  no se generan).

**09 · Financiación y pago**
- Objetivo: quitar la objeción del precio antes de que aparezca.
- Contenido: tres columnas — "Paga como quieras" (medios), "Hasta en X partes"
  (si aplica), "Entrega e instalación incluida".
- Por qué convierte: un juego de sala de S/ 2,800 es una compra que se piensa. Si
  la página no dice cómo se paga, el cliente se va a preguntar a WhatsApp o no
  pregunta.

**11 · Nosotros en portada**
- Objetivo: mover el mayor activo de confianza a donde se ve.
- Contenido: franja con foto del local + "14 años en Huancavelica" + "Distribuidor
  oficial Paraíso" + enlace a Nosotros.
- Por qué convierte: compra de mueble a distancia = miedo. Un local físico con años
  de historia lo reduce más que cualquier sello.

**13 · Preguntas frecuentes**
- Objetivo: responder lo que hoy se pregunta por WhatsApp.
- Contenido: 5-6 preguntas reales — ¿llega a mi ciudad? ¿lo arman? ¿qué garantía
  tiene? ¿puedo devolverlo? ¿cuánto demora?
- Por qué convierte: cada pregunta respondida en la web es una venta que no depende
  de que alguien conteste el teléfono.

---

## E. Wireframe escritorio

```
┌──────────────────────────────────────────────────────────────┐
│ envíos · garantía · entrega          tel · redes             │ 40px
├──────────────────────────────────────────────────────────────┤
│ [LOGO]      [ buscador ................. 🔍 ]   👤  🛒 S/    │ 90px
├──────────────────────────────────────────────────────────────┤
│ ☰ Categorías │ Inicio  Tienda  Nosotros  Contacto │ Ofertas  │ 56px
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   MUEBLES Y ELECTRODOMÉSTICOS          [ fotografía de       │
│   TRANSFORMA TU HOGAR                    ambiente real,      │ 480px
│   Calidad y confort para cada espacio    lado derecho ]      │
│   [ VER PRODUCTOS ]  [ EXPLORAR ]                            │
├──────────────────────────────────────────────────────────────┤
│  🚚 Envíos    ✓ Garantía    🔧 Instalación   💬 Asesoría      │ 120px
├──────────────────────────────────────────────────────────────┤
│                    Compra por ambiente                       │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐                 │
│  │  Sala  │ │Comedor │ │Dormit. │ │Colchón │                 │ 480px
│  └────────┘ └────────┘ └────────┘ └────────┘                 │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐                 │
│  │Oficina │ │Electro │ │Organiz.│ │  Más   │                 │
│  └────────┘ └────────┘ └────────┘ └────────┘                 │
├──────────────────────────────────────────────────────────────┤
│        Trabajamos con   [P] [R] [S] [F] [D]                  │ 100px
├──────────────────────────────────────────────────────────────┤
│  Lo más vendido                            Ver toda la tienda│
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐                     │ 560px
│  │ foto│ │ foto│ │ foto│ │ foto│ │ foto│                     │
│  └─────┘ └─────┘ └─────┘ └─────┘ └─────┘                     │
├──────────────────────────────────────────────────────────────┤
│  Paga como quieras │ Entrega e instalación │ Garantía real   │ 200px
├──────────────────────────────────────────────────────────────┤
│  [ foto del local ]  │  14 años en Huancavelica              │ 320px
│                      │  Distribuidor oficial Paraíso         │
│                      │  [ Conócenos ]                        │
├──────────────────────────────────────────────────────────────┤
│  ¿Necesitas ayuda para elegir?          [ WhatsApp ]         │ 180px
├──────────────────────────────────────────────────────────────┤
│  Preguntas frecuentes  (5 desplegables)                      │ 320px
├──────────────────────────────────────────────────────────────┤
│  PIE — 4 columnas + legal + pagos + sellos                   │ 400px
└──────────────────────────────────────────────────────────────┘
```

## F. Wireframe móvil

```
┌─────────────────────┐
│ ☰   [LOGO]    🛒    │  60px   ← barra fija
├─────────────────────┤
│ [ buscador  🔍 ]    │  52px
├─────────────────────┤
│                     │
│  TRANSFORMA         │  360px  ← baja de 470 a 360
│  TU HOGAR           │
│  [ VER PRODUCTOS ]  │
├─────────────────────┤
│ 🚚 Envíos │ ✓ Garan │  90px   ← 2×2, no 5 apilados
│ 🔧 Instal │ 💬 Ases │
├─────────────────────┤
│  Compra por ambiente│
│ ┌────────┐┌────────┐│  620px  ← 2 columnas
│ │  Sala  ││Comedor ││
│ └────────┘└────────┘│
│ ┌────────┐┌────────┐│
│ │Dormit. ││Colchón ││
│ └────────┘└────────┘│
│   [ Ver los 8 ]     │  ← corta en 4, resto tras pulsar
├─────────────────────┤
│  Marcas  → → →      │  80px   ← carrusel horizontal
├─────────────────────┤
│  Lo más vendido     │
│ ┌────────┐┌────────┐│  580px  ← 2 columnas
│ │  foto  ││  foto  ││
│ └────────┘└────────┘│
├─────────────────────┤
│  Paga como quieras  │  200px  ← apilado
├─────────────────────┤
│ [foto local]        │  280px
│ 14 años · Paraíso   │
├─────────────────────┤
│  Preguntas frec.    │  240px  ← plegado
├─────────────────────┤
│  PIE plegado        │  400px
└─────────────────────┘
│ [💬 WhatsApp]       │  ← flotante
```

---

## G. Sistema visual recomendado

La base actual **es buena y no la cambiaría**. Lo que propongo es formalizarla.

| Elemento | Actual | Propuesta |
|---|---|---|
| **Personalidad** | — | Cálida, sólida, aspiracional sin ser cara |
| **Primario** | `#082B52` navy | Se mantiene. Transmite solidez, no saldo |
| **Secundario** | `#041E3B` | Se mantiene |
| **Acento** | `#D1A347` dorado | Se mantiene. Es lo que la separa de la competencia |
| **Neutro cálido** | `#FAF6F0` | Se mantiene: da calidez de hogar |
| **Neutro frío** | `#F5F7FB` | **Eliminar.** Mezclar neutros cálidos y fríos ensucia |
| **Tipografía título** | Montserrat | Se mantiene |
| **Tipografía texto** | Inter | Se mantiene |
| **Escala** | 51/33px | **Ajustar a 1,33**: 44 / 33 / 25 / 19 / 16 |
| **Radio** | 16px global, 14 en tarjeta | **Jerarquizar**: 4 (chips) / 8 (botones) / 12 (tarjetas) / 20 (bloques) |
| **Sombras** | una sola, muy suave | **Dos niveles**: reposo `0 1px 3px` / elevado `0 8px 24px` |
| **Fotografía** | ambientes reales, buena luz | Mantener criterio: luz natural, espacio habitado, nunca render |

**Lo que NO haría:** cambiar la paleta. El navy con dorado es el mayor acierto de esta
tienda y la distingue de cualquier mueblería local.

---

## H. Recomendaciones por sección

### Encabezado y navegación
Funciona. Tres ajustes:
- El buscador dice "Buscar productos…". Cambiar a **"Busca tu sofá, colchón o refrigeradora…"**: enseña qué se puede buscar.
- Las zonas táctiles del encabezado están por debajo de 44px (36 elementos). Subirlas.
- El menú móvil (hamburguesa) debería abrir directamente los 8 ambientes, no una lista genérica.

### Hero
- **Único, sin carrusel.** MegaHogar tiene un solo mensaje y una sola foto buena. Un carrusel con tres diapositivas mediocres convierte menos que una buena fija.
- Bajar a **480px** en escritorio y **360px** en móvil.
- El titular está bien. El subtítulo "Calidad, diseño y confort para cada espacio" es genérico: cambiar por algo con dato ("Más de 1000 productos para tu hogar, con entrega e instalación").
- Dos botones está bien, pero "VER PRODUCTOS" y "EXPLORAR AMBIENTES" llevan casi al mismo sitio. Dejar uno principal y uno secundario con destinos distintos.

### Categorías (ambientes)
Es la mejor sección. Solo dos cambios:
- En móvil, mostrar **4 y un botón "Ver los 8"**. Ocho tarjetas apiladas son 620px de scroll antes de llegar a un producto.
- Añadir el número de productos también en móvil (hoy se pierde).

### Tarjeta de producto
Actual: 253×435, radio 14px, sombra suave, imagen 251px cuadrada. **La estructura está bien.** Lo que falla es el contenido (sin foto). Ajustes:
- Imagen en proporción **4:3**, no cuadrada: un sofá es horizontal, un cuadrado lo obliga a encoger.
- Fondo de imagen en `#FAF6F0` (crema), no blanco: el mueble claro sobre blanco desaparece.
- Añadir **una línea de atributo** bajo el nombre ("2 plazas · tela · con brazo"). En muebles, la medida decide la compra.
- El precio actual está bien jerarquizado.
- Al pasar el ratón: elevar la sombra al nivel 2 y subir 2px. Nada más.

### Pie
No lo tocaría. Tiene datos legales, sellos, medios de pago, horario y dirección. Es el mejor pie de las tres tiendas.

### Nosotros
**398 palabras y una sola imagen.** Tiene Misión y Visión reales, no plantilla. Pero:
- Falta la historia: 14 años, cómo empezaron, cuántas familias atendieron.
- Falta fotografía: del local, del equipo, de una entrega real.
- "Misión" y "Visión" como encabezados son lenguaje interno de empresa. El cliente no busca eso: busca "¿puedo confiar en ustedes?". Reescribiría esos bloques como **"Qué hacemos"** y **"Cómo trabajamos"**.

---

## I. Mejoras requeridas del constructor

Clasificación de cada propuesta:

| Propuesta | Clase | Detalle |
|---|---|---|
| Hero único sin carrusel | **A** | Ya existe: `hero.mode = single` |
| Altura del hero | **B** | Existe `hero_height` pero solo acepta small/medium/large; debería aceptar píxeles |
| Ambientes con foto | **A** | Variante `featured_categories` ya lo permite |
| Bloque de marcas | **A** | La sección `brands` existe y está vacía |
| Lo más vendido | **A** | `featured_products` ya existe |
| Ofertas de la semana | **A** | `discounts` ya existe |
| Preguntas frecuentes | **A** | `faq` ya existe y está vacía |
| Nosotros en portada | **A** | `about_preview` ya existe |
| Financiación y pago | **C** | **No existe.** Ver ficha abajo |
| Ambientes: 4 + "ver más" en móvil | **C** | No existe control de cuántos mostrar en móvil |
| Proporción de imagen en tarjeta | **C** | Hoy es fija por CSS |
| Línea de atributo en tarjeta | **C** | Requiere campo nuevo en producto |
| Escala tipográfica | **D** | Cambio interno de plantilla |
| Jerarquía de radios y sombras | **D** | Cambio interno de plantilla |
| Zonas táctiles a 44px | **D** | Cambio interno de plantilla |

### MEJORA REQUERIDA DEL CONSTRUCTOR — 1

- **Control:** Bloque "Formas de pago y entrega"
- **Tipo:** sección nueva del inicio, con 3 columnas editables
- **Campos por columna:** icono (selector), título (texto), descripción (texto)
- **Por defecto:** desactivado
- **Sección del constructor:** Inicio → Secciones
- **Plantillas afectadas:** computienda (las tres tiendas)

### MEJORA REQUERIDA DEL CONSTRUCTOR — 2

- **Control:** "Categorías visibles en móvil antes de «Ver más»"
- **Tipo:** número (2, 4, 6, todas)
- **Por defecto:** 4
- **Sección:** Inicio → Explora por categoría
- **Plantillas afectadas:** computienda

### MEJORA REQUERIDA DEL CONSTRUCTOR — 3

- **Control:** "Proporción de la foto de producto"
- **Tipo:** selector — cuadrada (1:1), horizontal (4:3), vertical (3:4)
- **Por defecto:** cuadrada
- **Sección:** Apariencia → Tarjeta de producto
- **Plantillas afectadas:** computienda
- **Motivo:** un mueble es horizontal, una prenda vertical, un teclado cuadrado. Una
  sola proporción no sirve a las tres tiendas.

### MEJORA REQUERIDA DEL CONSTRUCTOR — 4

- **Control:** "Altura del hero en píxeles"
- **Tipo:** número separado para escritorio y móvil
- **Por defecto:** 480 / 360
- **Sección:** Inicio → Hero
- **Motivo:** hoy `hero_height` acepta small/medium/large y el valor numérico se
  descarta en silencio.

---

## J. Recursos visuales faltantes

**CONSERVAR:** las 8 fotografías de ambiente, la foto del hero, el logotipo.
**REEMPLAZAR:** ninguna.
**FALTA IMAGEN:** las siguientes.

```
IMAGEN 1
Sección:      Marcas oficiales
Objetivo:     validar la tienda por asociación de marca
Desktop:      160×60 px cada logotipo
Mobile:       120×45 px
Formato:      SVG o PNG con fondo transparente
Composición:  logotipo aislado, sin fondo ni marco
Estilo:       oficial del fabricante, sin recolorear
Elementos:    Paraíso, Rosen, Samsung, Forli, Drimer, CIC, Sueñolar
Espacio texto: no aplica
Restricciones: NO generar con IA. Solicitar al proveedor el kit de marca.
Nombre:       marca-paraiso.svg, marca-rosen.svg, …
```

```
IMAGEN 2
Sección:      Nosotros en portada
Objetivo:     mostrar que hay un local y un equipo reales
Desktop:      1200×800 px
Mobile:       800×600 px
Formato:      JPG o WebP
Composición:  fachada o interior del local con producto visible
Estilo:       fotografía real, luz natural, sin filtros
Elementos:    el local de Jr. Odonovan 174, Huancavelica
Espacio texto: no necesita
Restricciones: NO generar con IA. Debe ser el local real.
Nombre:       megahogar-local.jpg
```

```
IMAGEN 3..N
Sección:      Catálogo
Objetivo:     que el escaparate deje de mostrar cajas vacías
Desktop:      1000×750 px (4:3)
Mobile:       misma imagen
Formato:      WebP
Composición:  producto centrado, fondo neutro claro o ambientado
Estilo:       consistente entre productos: mismo fondo, misma luz
Elementos:    los 1127 productos del catálogo
Espacio texto: no
Restricciones: vienen de la API del proveedor. Si la API no las trae, hay que
              fotografiar al menos los 50 más vendidos.
Nombre:       lo define la fuente de datos
```

---

## K. Quick wins (menos de 30 minutos cada uno)

1. **Activar el bloque de marcas** — la sección existe y está vacía. Solo faltan los logotipos.
2. **Activar preguntas frecuentes** — existe, vacía. Con 5 preguntas reales resuelve consultas de WhatsApp.
3. **Activar "Nosotros" en portada** — existe, vacía. Los 14 años y Paraíso son su mejor activo.
4. **Beneficios: de 5 a 4** — arregla el descuadre en tablet.
5. **Subtítulo del hero con dato concreto** en lugar de la frase genérica.
6. **Texto del buscador** con ejemplos de producto.

Cinco de los seis **no requieren tocar código**: son secciones que ya existen en el
constructor y están desactivadas o vacías.

---

## L. Cambios estructurales (requieren desarrollo)

1. Bloque de formas de pago y entrega (constructor)
2. Control de categorías visibles en móvil (constructor)
3. Proporción configurable de foto de producto (constructor)
4. Altura del hero en píxeles (constructor)
5. Jerarquía de radios y sombras (plantilla)
6. Zonas táctiles a 44px (plantilla)
7. Escala tipográfica a ratio 1,33 (plantilla)

---

## M. Prioridades

**P0 — bloquea la venta**
- Fotografías de producto. Sin esto, ninguna mejora de diseño importa.
- Medios de pago configurados.
- Los 19 productos sin precio (ya se ocultan, pero conviene corregir el origen).

**P1 — impacto alto, esfuerzo bajo**
- Activar marcas, preguntas frecuentes y Nosotros en portada.
- Zonas táctiles a 44px.
- Beneficios de 5 a 4.

**P2 — mejora la percepción**
- Bloque de formas de pago y entrega.
- Proporción 4:3 en la foto de producto.
- Fondo crema en la imagen de producto.
- Reescribir Nosotros con historia y fotografía.

**P3 — pulido**
- Escala tipográfica.
- Jerarquía de radios y sombras.
- Altura del hero configurable en píxeles.

---

## Si esta fuera mi propia tienda

**1. Pararía todo hasta tener fotos.** No tocaría una línea de CSS. Con 1127 productos
sin imagen, cualquier trabajo de diseño es maquillaje. Fotografiaría los 50 más
vendidos con el móvil sobre fondo blanco y luz de ventana, y con eso saldría a vender.

**2. Pondría "14 años" y "Distribuidor oficial Paraíso" en la primera pantalla.** Es lo
que tienen y no lo están usando. Vale más que cualquier sello SSL.

**3. No haría carrusel en el hero.** Tienen una foto buena. Una foto buena fija vende
más que tres mediocres rotando.

**4. Activaría las cuatro secciones que ya existen vacías** antes de programar nada
nuevo. Marcas, preguntas frecuentes, Nosotros y ofertas están construidas y apagadas.

**5. Mantendría la paleta exactamente como está.** El navy con dorado es lo mejor que
tiene esta tienda. Lo he visto en pocas mueblerías peruanas y la separa de la
competencia que va a naranja de liquidación.

**6. Cambiaría la foto de producto a 4:3 sobre fondo crema.** Un sofá en un cuadrado
blanco se ve pequeño y frío. En horizontal sobre crema se ve como un mueble.

**7. Añadiría "hasta en X cuotas" si existe.** Es la pregunta número uno en muebles y
hoy la web no la responde.

---

## Notas sobre Tecsist y Baby Toncito

**Tecsist** tiene el problema inverso: cuatro bloques de productos para 46 artículos.
Sobra un bloque. Su catálogo son datos de prueba, así que la conclusión de diseño se
sostiene pero el contenido cambiará.

**Baby Toncito** tiene el mejor hero de las tres tras la corrección de hoy, pero:
- cinco tipografías (deberían ser dos)
- **31 de 61 productos sin precio**
- logotipo circular con texto ilegible a cualquier tamaño usable
Su bloqueo también es de contenido, no de diseño.

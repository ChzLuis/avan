# Consultoría de diseño — Tecsist

Sitio analizado: `tecsist.net` · datos reales de producción
**Ningún cambio aplicado.** Solo diagnóstico, propuesta y capturas.

> Nota de contexto: el catálogo de Tecsist son **datos de prueba** (fotos de banco de
> imágenes, descripciones ficticias). El análisis de estructura y diseño se sostiene;
> los juicios sobre contenido concreto cambiarán cuando entre el catálogo real.

---

## A. Diagnóstico actual

### Primera impresión

**Escritorio (1440×900) — 3 segundos.** Barra celeste intensa, logotipo legible,
buscador ancho y carrito con importe. Debajo, el hero con un equipo iluminado en azul.
El ojo va al logotipo, al buscador y al titular. Correcto para una tienda de informática.

**10 segundos.** Aparece "Promociones" con un banner rojo y luego "Explora por
categoría" con cinco círculos. El salto de celeste a rojo intenso es brusco: dos
lenguajes de color compitiendo en 600px de scroll.

**Scroll completo.** Y aquí está el problema estructural: **cuatro bloques de productos
seguidos**. Ofertas por tiempo limitado, Ofertas de la semana, Lo más vendido y Filas
por categoría. Son **7.100px de página** para 46 productos, con el mismo artículo
apareciendo hasta tres veces.

### Respuestas directas

| Pregunta | Respuesta |
|---|---|
| ¿Profesional? | **Sí.** Es la más resuelta de las tres en estructura. |
| ¿Plantilla? | **No**, pero se acerca más que MegaHogar. El celeste es común en informática. |
| ¿Económica, media, premium? | **Media.** El celeste saturado tira hacia "buen precio", no hacia premium. |
| ¿Genera confianza? | **Sí.** Sellos, RUC, garantía, dirección física, medios de pago. La mejor de las tres en esto. |
| ¿Invita a explorar? | **Demasiado.** El problema no es que no invite: es que insiste cuatro veces. |
| ¿Diferenciación? | **Baja.** Celeste + navy es el uniforme del rubro informático. |
| ¿Identidad de tecnología? | **Sí**, pero genérica. Podría ser cualquier tienda de cómputo. |

**En una frase:** bien construida, mal dosificada.

---

## B. Problemas de diseño

**B1. Repetición de producto.** Cuatro bloques, 31 apariciones, 46 productos. El
cliente ve el mismo teclado en "Ofertas por tiempo limitado", en "Ofertas de la semana"
y en "Lo más vendido". Eso no refuerza: aburre y hace que el catálogo parezca pequeño.

**B2. Choque cromático en Promociones.** El banner es rojo intenso sobre una tienda
celeste. No hay transición ni justificación. Parece un anuncio pegado encima.

**B3. Escala de página desproporcionada.** 7.100px de inicio para 46 productos. Filas
por categoría ocupa **1.750px** — casi un cuarto de la página entera.

**B4. Las categorías circulares no encajan con el rubro.** Un círculo recorta un
monitor, un teclado o una laptop de forma antinatural. El círculo funciona para
personas o alimentos, no para productos rectangulares.

**B5. Identidad de color intercambiable.** `#42c8f0` con navy es lo que usan la mayoría
de las tiendas de informática. No hay nada que haga a Tecsist reconocible sin el logotipo.

---

## C. Problemas de estructura

**C1. Dos secciones de ofertas compitiendo.** "Ofertas por tiempo limitado" (con
contador) y "Ofertas de la semana" muestran productos con descuento. Solo hay 10
productos en oferta: dos bloques para diez artículos es demasiado.

**C2. "Filas por categoría" es un catálogo dentro del inicio.** 1.750px mostrando
Laptops nuevas, Laptops seminuevas y Accesorios. Eso es la página de tienda, no el
inicio.

**C3. No hay bloque de servicio técnico.** Tecsist tiene servicio técnico y soporte
(lo dicen en Nosotros), pero en la portada no aparece. Para una tienda de informática
en provincia, **el servicio técnico es el diferenciador**, no el precio.

**C4. Sin bloque de marcas.** Venden HP, Lenovo, ASUS, Logitech, Redragon. En
informática la marca es el primer filtro de búsqueda.

---

## D. Nueva estructura recomendada

```
01  Barra superior          — mayorista, asesoría, teléfono, redes
02  Encabezado              — logo, buscador, carrito
03  Navegación              — categorías + ofertas + novedades
04  Hero                    — carrusel de 3 anuncios (no 5)
05  Beneficios              — 4 elementos, pegado al hero
06  Explora por categoría   — 6 categorías, tarjeta rectangular (no círculo)
07  Marcas que trabajamos   — NUEVO
08  Ofertas de la semana    — ÚNICO bloque de ofertas (fusiona los dos actuales)
09  Lo más vendido          — se mantiene
10  Servicio técnico        — NUEVO: el diferenciador real
11  Asesoría por WhatsApp   — se mantiene
12  Nosotros (extracto)     — se mantiene
13  Pie                     — se mantiene
```

**Se elimina:** "Ofertas por tiempo limitado" (se fusiona con Ofertas de la semana) y
"Filas por categoría" (pertenece a la página de tienda, no al inicio).

**Resultado:** de 7.100px a ~4.200px. De 4 bloques de producto a 2.

### Detalle de las secciones nuevas

**07 · Marcas que trabajamos**
- Objetivo: filtro mental de compra. "¿Tienen HP?" se responde antes de buscar.
- Contenido: 6-8 logotipos en gris, color al pasar el ratón.
- Escritorio: fila única. Móvil: carrusel de arrastre.
- **Imágenes:** logotipos oficiales del fabricante. No generar con IA.

**10 · Servicio técnico**
- Objetivo: vender lo que la competencia online no puede dar.
- Contenido: franja a dos columnas — foto de taller real + tres servicios
  (mantenimiento, reparación, instalación) + botón de WhatsApp.
- Por qué convierte: quien compra una laptop en provincia teme quedarse sin soporte.
  Un taller físico resuelve esa objeción mejor que cualquier descuento.
- **Imagen:** fotografía real del taller o del técnico trabajando.

---

## E. Wireframe escritorio

```
┌──────────────────────────────────────────────────────────────┐
│ mayorista · asesoría · 900438114              f  ig  tt      │ 40px
├──────────────────────────────────────────────────────────────┤
│ [LOGO]     [ buscador ................... 🔍 ]      🛒 S/    │ 88px
├──────────────────────────────────────────────────────────────┤
│ ☰ Categorías │ Inicio Computadoras Laptops Impresoras │ 🔴Of │ 56px
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   TECNOLOGÍA PARA          [ anuncio a sangre completa,      │ 440px
│   CADA PASIÓN                3 diapositivas máximo ]         │
│   [ VER OFERTAS ]                                            │
├──────────────────────────────────────────────────────────────┤
│  🚚 Envío   ✓ Garantía   💳 Pago seguro   🎧 Soporte         │ 110px
├──────────────────────────────────────────────────────────────┤
│  Explora por categoría                                       │
│  ┌──────┐┌──────┐┌──────┐┌──────┐┌──────┐┌──────┐            │ 260px
│  │ PC   ││Laptop││Impres││Cámara││Disco ││Monitor│           │ ← rectangular
│  └──────┘└──────┘└──────┘└──────┘└──────┘└──────┘            │
├──────────────────────────────────────────────────────────────┤
│      Trabajamos con  [HP] [Lenovo] [ASUS] [Logitech]         │ 90px
├──────────────────────────────────────────────────────────────┤
│  Ofertas de la semana                        Ver todas       │
│  ┌────┐┌────┐┌────┐┌────┐┌────┐                              │ 520px
│  └────┘└────┘└────┘└────┘└────┘                              │
├──────────────────────────────────────────────────────────────┤
│  Lo más vendido                              Ver toda tienda │
│  ┌────┐┌────┐┌────┐┌────┐┌────┐                              │ 520px
│  └────┘└────┘└────┘└────┘└────┘                              │
├──────────────────────────────────────────────────────────────┤
│  [ foto del taller ]  │ SERVICIO TÉCNICO                     │ 320px
│                       │ Mantenimiento · Reparación · Soporte │
│                       │ [ Consultar por WhatsApp ]           │
├──────────────────────────────────────────────────────────────┤
│  ¿No sabes qué equipo elegir?           [ Pedir asesoría ]   │ 180px
├──────────────────────────────────────────────────────────────┤
│  Tu tienda informática de confianza                          │ 280px
├──────────────────────────────────────────────────────────────┤
│  PIE tecnológico — 4 columnas + sellos + pagos + RUC         │ 530px
└──────────────────────────────────────────────────────────────┘
```

## F. Wireframe móvil

```
┌─────────────────────┐
│ ☰  [LOGO]      🛒   │  56px  ← fija
├─────────────────────┤
│ [ buscador     🔍 ] │  48px
├─────────────────────┤
│  TECNOLOGÍA PARA    │
│  CADA PASIÓN        │  340px ← baja de 470
│  [ VER OFERTAS ]    │
├─────────────────────┤
│ 🚚 Envío │ ✓ Garan  │  88px  ← 2×2
│ 💳 Pago  │ 🎧 Sop   │
├─────────────────────┤
│  Categorías → → →   │  150px ← carrusel, no apilado
├─────────────────────┤
│  Marcas  → → →      │  70px
├─────────────────────┤
│  Ofertas de semana  │
│ ┌──────┐ ┌──────┐   │  560px ← 2 columnas
│ └──────┘ └──────┘   │
│   [ Ver todas ]     │
├─────────────────────┤
│  Lo más vendido     │
│ ┌──────┐ ┌──────┐   │  560px
│ └──────┘ └──────┘   │
├─────────────────────┤
│ [foto taller]       │  300px
│ SERVICIO TÉCNICO    │
│ [ WhatsApp ]        │
├─────────────────────┤
│  Asesoría           │  160px
├─────────────────────┤
│  PIE plegado        │  780px
└─────────────────────┘
│ [💬]                │ ← flotante
```

---

## G. Sistema visual recomendado

| Elemento | Actual | Propuesta |
|---|---|---|
| **Personalidad** | — | Técnica, directa, sin adornos. "Sabemos de esto" |
| **Primario** | `#42c8f0` celeste | **Oscurecer a `#1B9CD8`**. El celeste actual es tan claro que el texto blanco encima roza el mínimo de contraste, y lo asocia con "barato" |
| **Secundario** | `#0f172a` navy | Se mantiene |
| **Acento** | `#38bdf8` | **Cambiar a naranja `#F97316`** solo para ofertas y precios rebajados. Hoy acento y primario son casi el mismo azul: no acentúa nada |
| **Tipografía** | Space Grotesk + Inter | Se mantiene. Space Grotesk le da el punto técnico que el color no da |
| **Escala** | 51/33px (ratio 1,54) | **44 / 33 / 25 / 19 / 16** (ratio 1,33) |
| **Radio** | 16 global, 14 tarjeta | **4 / 8 / 12 / 20** según jerarquía |
| **Fotografía** | banco de imágenes | Producto sobre fondo neutro, misma luz en todos |

**El cambio de mayor impacto:** el acento naranja. Hoy Tecsist es monocromática azul,
así que **nada destaca**. Un naranja reservado exclusivamente a precios rebajados y
botones de compra haría que la oferta salte a la vista.

---

## H. Recomendaciones por sección

### Hero
- **Carrusel sí**, pero de **3 diapositivas, no 5**. Cinco anuncios significa que el
  quinto no lo ve nadie, y son cinco imágenes que producir y mantener.
- Bajar a **440px** escritorio, **340px** móvil.
- El titular funciona. El subtítulo "Laptops, PCs y accesorios con garantía oficial"
  es correcto y concreto.

### Categorías
- **Cambiar círculo por tarjeta rectangular 4:3.** Un monitor dentro de un círculo se
  recorta por los cuatro lados.
- Mostrar 6, no 4. Hoy se muestran 4 porque dos categorías están vacías.
- En móvil: carrusel horizontal, no columna apilada.

### Tarjeta de producto
Actual 256×438, radio 14px, imagen 254 cuadrada. Ajustes:
- Imagen **1:1 está bien** para informática (un teclado, un mouse, un disco son
  compactos). Aquí sí funciona el cuadrado, al revés que en muebles.
- Añadir **línea de especificación** bajo el nombre: "Intel i5 · 16GB · 512GB SSD".
  En informática la especificación **es** el producto.
- El descuento en celeste se confunde con el resto. En naranja saltaría.

### Nosotros
**331 palabras, una imagen.** Tiene texto real sobre servicio técnico y garantía, no
plantilla. Falta:
- Fotografía del local y del taller.
- Años de operación (no aparece).
- Qué marcas son distribuidores autorizados, si lo son.

---

## I. Mejoras requeridas del constructor

| Propuesta | Clase | Detalle |
|---|---|---|
| Carrusel de 3 diapositivas | **A** | Se editan las diapositivas del hero |
| Eliminar bloque de ofertas duplicado | **A** | Se desactiva la sección |
| Eliminar filas por categoría | **A** | Se desactiva la sección |
| Bloque de marcas | **A** | La sección `brands` existe y está vacía |
| Categorías rectangulares | **B** | Existen variantes (`circles`, `cards`, `images`); basta cambiar a `cards` |
| Servicio técnico | **C** | No existe una sección así |
| Acento naranja para ofertas | **C** | Hoy el acento es global, no específico de oferta |
| Especificación en la tarjeta | **C** | Requiere campo nuevo en producto |
| Escala tipográfica y radios | **D** | Plantilla |

### MEJORA REQUERIDA DEL CONSTRUCTOR — 1
- **Control:** Bloque "Servicio o taller"
- **Tipo:** sección nueva con imagen + título + 3 puntos + botón
- **Campos:** imagen, título, 3× (icono + texto), texto del botón, destino
- **Por defecto:** desactivado
- **Sección:** Inicio → Secciones
- **Plantillas:** computienda

### MEJORA REQUERIDA DEL CONSTRUCTOR — 2
- **Control:** "Color de ofertas y descuentos"
- **Tipo:** color
- **Por defecto:** hereda el acento
- **Sección:** Apariencia → Colores
- **Motivo:** hoy el descuento usa el color primario y se pierde en tiendas
  monocromáticas.

### MEJORA REQUERIDA DEL CONSTRUCTOR — 3
- **Control:** "Línea de especificación en la tarjeta"
- **Tipo:** selector de campo de producto (SKU, marca, atributo libre)
- **Por defecto:** ninguno
- **Sección:** Apariencia → Tarjeta de producto

---

## J. Recursos visuales faltantes

**CONSERVAR:** logotipo, banners del hero.
**REEMPLAZAR:** las 41 fotos de banco de imágenes cuando llegue el catálogo real.
**FALTA IMAGEN:**

```
IMAGEN 1
Sección:      Servicio técnico
Objetivo:     demostrar que hay taller y técnicos reales
Desktop:      1200×800 px
Mobile:       800×600 px
Formato:      WebP
Composición:  técnico trabajando sobre un equipo abierto, plano medio
Estilo:       fotografía real del local, luz natural o de taller
Elementos:    persona + herramienta + equipo
Espacio texto: no
Restricciones: NO generar con IA. Debe ser el taller real.
Nombre:       tecsist-taller.webp
```

```
IMAGEN 2
Sección:      Marcas
Objetivo:     validar por asociación
Desktop:      160×60 px por logotipo
Formato:      SVG con fondo transparente
Elementos:    HP, Lenovo, ASUS, Logitech, Redragon, Kingston
Restricciones: kit oficial del fabricante. NO generar con IA.
Nombre:       marca-hp.svg, marca-lenovo.svg, …
```

```
IMAGEN 3..N
Sección:      Catálogo
Objetivo:     sustituir las 41 fotos de banco de imágenes
Desktop:      1000×1000 px (1:1)
Formato:      WebP
Composición:  producto centrado, fondo blanco o gris muy claro
Estilo:       misma luz y mismo fondo en todos
Restricciones: fotografía propia del producto real en stock
```

---

## K. Quick wins

1. **Desactivar "Ofertas por tiempo limitado"** — duplica Ofertas de la semana. Un clic.
2. **Desactivar "Filas por categoría"** — quita 1.750px de inicio. Un clic.
3. **Activar el bloque de marcas** — existe y está vacío.
4. **Categorías de círculo a tarjeta** — cambio de variante en el constructor.
5. **Zonas táctiles a 44px** — 97 elementos por debajo; los peores son WhatsApp (16px de alto) y el teléfono (15px).

Los cuatro primeros **no requieren tocar código**.

---

## L. Cambios estructurales

1. Sección de servicio técnico (constructor)
2. Color específico para ofertas (constructor)
3. Línea de especificación en la tarjeta (constructor + campo de producto)
4. Oscurecer el primario y añadir acento naranja (configuración + revisión de contraste)
5. Zonas táctiles a 44px (plantilla)
6. Escala tipográfica y jerarquía de radios (plantilla)

---

## M. Prioridades

**P0**
- Fotografías reales de producto y URLs reales de redes (hoy son genéricas).
- Zonas táctiles del encabezado: WhatsApp a 16px de alto es un botón que falla en móvil.

**P1**
- Quitar los dos bloques sobrantes de la portada.
- Activar marcas.
- Cambiar categorías a tarjeta rectangular.

**P2**
- Sección de servicio técnico.
- Acento naranja para ofertas.
- Oscurecer el primario.

**P3**
- Escala tipográfica, radios, sombras.
- Especificación en la tarjeta.

---

## Si esta fuera mi propia tienda

**1. Quitaría dos de los cuatro bloques de producto.** Hoy la portada mide 7.100px para
46 productos. Mostrar el mismo teclado tres veces no vende más: hace que el catálogo
parezca más pequeño de lo que es.

**2. Le pondría un acento naranja.** Tecsist es monocromática azul, así que nada
destaca. Un naranja reservado a precios rebajados haría que la oferta salte.

**3. Sacaría el servicio técnico a la portada.** Es lo único que un competidor online
no puede copiar, y hoy está enterrado en Nosotros.

**4. Cambiaría los círculos por tarjetas.** Un monitor dentro de un círculo se recorta
por los cuatro lados.

**5. Oscurecería el celeste.** `#42c8f0` con texto blanco encima roza el mínimo de
contraste y comunica "barato" en un rubro donde se venden equipos de S/ 5.000.

**6. Bajaría el carrusel a 3 diapositivas.** Cinco anuncios son cinco imágenes que
producir y mantener, y nadie llega a la quinta.

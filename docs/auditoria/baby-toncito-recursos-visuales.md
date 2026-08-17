# Baby Toncito — recursos visuales

Cada imagen lleva ficha completa, prompt listo para pegar y **la ruta exacta del
constructor** donde cargarla. Nada se pega en el código.

Estados: `[ ]` NECESARIA · `[~]` SOLICITADA · `[+]` GENERADA · `[^]` CARGADA · `[x]` VERIFICADA

| ID | Uso | Desktop | Mobile | Prioridad | Estado |
|---|---|---|---|---|---|
| BT-LOGO-01 | Logotipo horizontal | 400×120 | — | **P0** | `[~]` |
| BT-HERO-01 | Hero Inicio (General) | 2400×1000 | 1080×1350 | **P0** | `[~]` |
| BT-HERO-NINO-01 | Hero perfil Niño | 2400×900 | 1080×1350 | **P0** | `[~]` |
| BT-HERO-NINA-01 | Hero perfil Niña | 2400×900 | 1080×1350 | **P0** | `[~]` |
| BT-MUNDO-NINO-01 | Tarjeta "Elige su mundo" Niño | 1200×800 | — | P1 | `[~]` |
| BT-MUNDO-NINA-01 | Tarjeta "Elige su mundo" Niña | 1200×800 | — | P1 | `[~]` |
| BT-MATERIAL-01 | Sección "Por qué algodón pima" | 1200×1200 | — | P1 | `[~]` |
| BT-PACK-FONDO | Packshots disparejos | 1200×1600 | — | P1 | `[~]` |
| BT-NOSOTROS-01 | Cabecera de Nosotros | 2000×900 | 1080×1080 | P2 | `[~]` |

---

## BT-LOGO-01 · P0

**Uso:** logotipo horizontal para la cabecera.
**Constructor:** `Apariencia > Marca > Logo`

**Por qué:** el sello circular actual es ilegible a 64 px — el texto curvo del
borde no se lee a ningún tamaño de cabecera. Hoy está resuelto poniendo
"Baby Toncito" en tipografía al lado, que es un apaño, no un logotipo.
El sello se conserva para el pie, las etiquetas y las redes, donde sí funciona.

**Formato:** 400×120 px · PNG o SVG · **fondo transparente**
**Composición:** isotipo a la izquierda + "Baby Toncito" a la derecha, centrados
verticalmente entre sí.
**No debe llevar:** el texto curvo del borde del sello, ni fondo blanco.

**PROMPT PARA CHATGPT:**
> Logotipo horizontal para una marca de ropa de bebé llamada "Baby Toncito".
> A la izquierda, un isotipo sencillo y limpio inspirado en un osito y una nube,
> en tonos verde azulado (#2C7A7F) y crema. A la derecha, el nombre
> "Baby Toncito" en una tipografía geométrica redondeada, peso semibold, del
> mismo verde azulado. Fondo transparente, formato horizontal 400×120,
> vectorial, sin sombras, sin degradados, sin texto adicional, legible a
> tamaño pequeño.

---

## BT-HERO-01 · P0

**Uso:** hero de la portada, perfil General.
**Constructor:** `Inicio > Hero > Imagen 1 > Foto para PC` y `Foto para móvil`

**Por qué:** hoy el hero son tres recortes de prenda flotando sobre un degradado
menta, cortados por abajo. La tienda nunca enseña un bebé, y quien compra ropa
de bebé compra la imagen de su hijo con esa ropa puesta.

**Desktop:** 2400×1000 px (12:5) · **Mobile:** 1080×1350 px (4:5)

| | |
|---|---|
| Sujeto | un bebé de 1 a 3 años |
| Ropa | conjunto de algodón en tonos neutros (crema, camel, blanco roto) |
| Fondo | interior doméstico cálido y desenfocado: pared clara, madera, luz de ventana |
| Iluminación | natural, suave, sin sombras duras |
| Posición | bebé en el tercio derecho; mira a cámara o hacia la izquierda |
| Espacio para texto | 45 % izquierdo completamente limpio |
| Prohibido | juguetes saturados, globos, texto, marcas de agua, adultos en primer plano, fondos tipo guardería |

**PROMPT PARA CHATGPT (escritorio):**
> Fotografía publicitaria de moda infantil: un bebé de aproximadamente dos años
> sentado en el suelo de una habitación luminosa, vestido con un conjunto de
> algodón en tonos crema y camel. Luz natural suave entrando por una ventana a
> la izquierda, fondo interior cálido y desenfocado con madera clara y pared
> blanca. El bebé aparece en el tercio derecho del encuadre, mirando hacia la
> izquierda. El tercio izquierdo queda completamente limpio, sin objetos, con
> espacio para texto. Formato horizontal 12:5, alta resolución, estética de
> catálogo de boutique premium, sin texto ni logotipos.

**PROMPT (móvil):** la misma frase cambiando el final por:
> Formato vertical 4:5, el bebé centrado en la mitad inferior, la mitad superior
> limpia con espacio para texto.

---

## BT-HERO-NINO-01 · P0

**Uso:** hero del perfil Niño.
**Constructor:** `Catálogo > Perfiles de catálogo > Niño > Hero (escritorio)` y
`Hero (móvil)`

**Desktop:** 2400×900 px · **Mobile:** 1080×1350 px

| | |
|---|---|
| Sujeto | niño de 1 a 4 años |
| Ropa | conjunto de la línea niño: camisa clara, tirantes o abrigo en camel/azul apagado |
| Fondo | ambiente cálido y limpio, tonos madera y crema; **azul apagado `#3E7CA6` solo como acento**, nunca como fondo plano |
| Posición | tercio derecho; espacio negativo a la izquierda para el copy |
| Estilo | boutique infantil premium, publicitaria, natural y delicada — no caricaturesca |
| Prohibido | fondo azul saturado, dibujos animados, globos, texto |

**PROMPT PARA CHATGPT:**
> Fotografía publicitaria de moda infantil para niño: un niño de unos tres años
> de pie en una habitación luminosa, vestido con camisa de algodón clara,
> tirantes y pantalón corto en tono camel. Detalles en azul apagado. Luz natural
> suave, fondo interior cálido y desenfocado con madera clara. El niño ocupa el
> tercio derecho del encuadre, con espacio negativo limpio a la izquierda para
> texto. Formato horizontal 8:3, alta resolución, estética de boutique infantil
> premium, natural y delicada, sin texto ni logotipos.

---

## BT-HERO-NINA-01 · P0

**Uso:** hero del perfil Niña.
**Constructor:** `Catálogo > Perfiles de catálogo > Niña > Hero (escritorio)` y
`Hero (móvil)`

Mismas reglas que Niño, cambiando el acento a rosa apagado `#C4718A` y la ropa
a la línea niña (vestido o conjunto de algodón en rosa palo y crema).

**PROMPT PARA CHATGPT:**
> Fotografía publicitaria de moda infantil para niña: una niña de unos tres años
> sentada en una habitación luminosa, vestida con un vestido de algodón en rosa
> palo y crema. Luz natural suave desde una ventana, fondo interior cálido y
> desenfocado con madera clara. La niña ocupa el tercio derecho del encuadre,
> con espacio negativo limpio a la izquierda para texto. Formato horizontal 8:3,
> alta resolución, estética de boutique infantil premium, natural y delicada,
> sin texto ni logotipos.

---

## BT-PACK-FONDO · P1

**Uso:** rehacer las fotos de producto que desentonan.
**Constructor:** `Catálogo > Productos > [producto] > Imágenes`

**Por qué:** conviven packshots sobre blanco puro con otros sobre fondo celeste
con nubes y sobre rosa. En una fila de cuatro, la que desentona rompe el
conjunto y delata que no es una colección.

**Formato:** 1200×1600 px (3:4) · **Fondo:** blanco puro `#FFFFFF` en todas
**Iluminación:** difusa, sin sombra dura bajo la prenda
**Composición:** prenda centrada, con aire arriba y abajo
**Prohibido:** fondos ilustrados, nubes, degradados, props, marcas de agua

**PROMPT PARA CHATGPT:**
> Fotografía de producto de una prenda de bebé sobre fondo blanco puro,
> iluminación de estudio difusa y uniforme, sin sombras duras, prenda centrada
> con margen de aire alrededor, formato vertical 3:4, alta resolución, estilo
> catálogo de moda infantil, sin props ni elementos decorativos.

Afecta hoy a "Shot driel polo pima estampa" (fondo celeste con nubes) y a
cualquier otra que no esté sobre blanco.

---

## BT-NOSOTROS-01 · P2

**Uso:** cabecera de la página Nosotros.
**Constructor:** `Páginas > Nosotros > Imagen de cabecera`

**Formato:** 2000×900 px · móvil 1080×1080 px
**Contenido:** detalle editorial de algodón pima — tela doblada, manos
sosteniendo una prenda, luz natural lateral. Puede no aparecer ningún bebé.
**Prohibido:** fotos de fábrica genéricas, gente de stock sonriendo a cámara.

**PROMPT PARA CHATGPT:**
> Fotografía editorial de detalle: manos sosteniendo una prenda de algodón pima
> doblada, sobre una superficie de madera clara, luz natural lateral suave,
> tonos crema y camel, profundidad de campo corta, formato horizontal 20:9,
> alta resolución, estética de marca de moda artesanal, sin texto ni logotipos.

---

## BT-MUNDO-NINO-01 y BT-MUNDO-NINA-01 · P1

**Uso:** las dos tarjetas de "Elige su mundo" en la portada.
**Constructor:** `Catálogo > Categorías > Niño > Imagen` y `... > Niña > Imagen`

**Por qué:** hoy la tarjeta muestra un packshot apaisado y la prenda sale
cortada por el cuello y por el bajo. Una "colección" no se presenta con una
prenda recortada.

**Formato:** 1200×800 px (3:2) · el recorte de la tarjeta es apaisado
**Contenido:** niño (o niña) de 1–4 años llevando ropa de la línea, en plano
medio, ambiente doméstico luminoso. El sujeto centrado o ligeramente a la
derecha; el nombre del mundo se sobreimprime abajo a la izquierda sobre un
degradado, así que **ese ángulo debe quedar despejado**.
**Prohibido:** prenda sola, fondos de color plano, texto.

**PROMPT (Niño):**
> Fotografía de moda infantil: un niño de unos tres años sonriendo en una
> habitación luminosa, vestido con un conjunto de algodón en tonos camel y azul
> apagado. Plano medio, luz natural suave, fondo interior cálido y desenfocado.
> El niño ligeramente a la derecha del encuadre; la esquina inferior izquierda
> queda despejada. Formato horizontal 3:2, alta resolución, estética de boutique
> infantil premium, sin texto ni logotipos.

**PROMPT (Niña):** el mismo cambiando a *una niña de unos tres años* y
*vestido de algodón en rosa palo y crema*.

---

## BT-MATERIAL-01 · P1

**Uso:** imagen de la sección "Por qué algodón pima" en la portada.
**Constructor:** `Inicio > Secciones > Sobre nosotros (vista previa) > Imagen`

**Por qué:** la sección está configurada con la imagen a la izquierda pero no
hay imagen: el texto queda solo y media portada en blanco.

**Formato:** 1200×1200 px (1:1)
**Contenido:** macro de tejido de algodón pima — prenda doblada o tela plegada,
luz natural lateral, tonos crema y camel, profundidad de campo corta.
**Prohibido:** manos con manicura de stock, logotipos, texto, fondos de color.

**PROMPT PARA CHATGPT:**
> Fotografía macro de tejido de algodón pima: una prenda de bebé de punto fino
> doblada sobre una superficie de madera clara, luz natural lateral suave que
> marca la textura de la fibra, tonos crema y camel, profundidad de campo muy
> corta, formato cuadrado 1:1, alta resolución, estética editorial de marca de
> moda artesanal, sin texto ni logotipos.

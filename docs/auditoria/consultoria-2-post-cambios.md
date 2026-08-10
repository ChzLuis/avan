# Segunda consultoría — después de los cambios

Fecha: 2026-08-09 · Sitios en producción · Medido con navegador controlado sobre
1.200 elementos por página, en 1440 y 390 px.

Esta revisión no repite la primera. Da por hechos los puntos ya corregidos y busca
lo que sigue mal **ahora**, con el sitio como quedó.

---

## Resumen

| | Tecsist | Baby Toncito | MegaHogar |
|---|---|---|---|
| Tipografía | **D** | **D** | B |
| Color y contraste | C | **D** | C+ |
| Espacios | C+ | C+ | B− |
| Formatos y tamaños | C | C | B− |
| Encabezado | B | B+ | B+ |
| Pie | C+ | B | A− |
| Uso de la marca | C+ | **D** | A− |
| Experiencia de compra | B | **D+** | C |

MegaHogar es hoy la mejor resuelta de las tres. Baby Toncito es la que más se aleja
de su propia personalidad.

---

## 1. Un fallo que costaba ventas (ya corregido)

En Baby Toncito, **las tarjetas con precio mayorista se quedaron sin botón de compra**.
Al plegar el bloque mayorista, la regla que ocultaba el botón inferior para no repetir
el CTA seguía activa, y el botón plegado tampoco se veía. Resultado: en esos productos
solo quedaba "Consultar" por WhatsApp. Corregido: el botón inferior solo se oculta
cuando el bloque mayorista está desplegado.

Es el tipo de fallo que no se ve en una captura: hay que medir si el botón tiene tamaño.

---

## 2. Tipografía — el problema más grande

### 2.1 Hay 15 tamaños de letra donde deberían ser 5

Tecsist usa **54, 52, 33, 22, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10 y 9 px**.
La escala acordada era 44 / 33 / 25 / 19 / 16.

- **54 y 52 conviven**: dos titulares que se quieren distintos y se ven iguales.
- **18, 17, 16, 15, 14, 13**: seis tamaños de texto corrido. Nadie distingue 17 de 16.
- **9, 10 y 11 px**: por debajo del mínimo legible.

Baby Toncito tiene 13 tamaños, MegaHogar 13. Ninguna respeta la escala.

**Qué haría:** dejar cinco tamaños vivos y prohibir el resto en el constructor.
44 / 33 / 25 / 19 / 16 en escritorio, 36 / 28 / 22 / 18 / 16 en móvil.

### 2.2 Texto de 9 px en sitios que importan

| Texto | Tamaño | Dónde |
|---|---|---|
| "Laptops", "Sofás", "Polos y Camisetas" | **9 px** | Etiqueta de categoría en la tarjeta |
| "Foto en camino" | 9,5 px | Tarjeta de MegaHogar |
| "Por docena · desde 12" | 10 px | Tarjeta mayorista |
| "Atención comercial" | 10 px | Encabezado |
| **"Agregar al carrito"** | **11 px** | **El botón de compra** |

El botón principal de compra tiene el texto más pequeño de toda la tarjeta.
Mínimo 13 px para un CTA.

### 2.3 Cuatro pesos de letra

400, 700, 800 y 900 en la misma página. Entre 800 y 900 no hay diferencia
perceptible en pantalla: es un peso de más que solo complica el mantenimiento.
Dos bastan: 400 para texto, 700 para énfasis y títulos.

---

## 3. Uso de la marca

### 3.1 Los titulares gritan

| Tienda | h1 actual | Personalidad declarada |
|---|---|---|
| Tecsist | 54 px, peso **900**, **MAYÚSCULAS** | Técnica, directa, sin adornos |
| Baby Toncito | 54 px, peso **900**, **MAYÚSCULAS** | Suave, honesta, artesanal |
| MegaHogar | 40 px, peso 700, normal | Cálida, sólida |

**"ROPITA CON AMOR PARA TU BEBÉ" en negrita 900 y mayúsculas** es la tipografía de
una tienda de neumáticos, no de ropa de recién nacido. La marca dice "suave" y la
letra dice lo contrario. MegaHogar es la única que acierta.

**Qué haría:** Baby Toncito a 40 px, peso 700, **sin mayúsculas**. Tecsist a 44 px,
peso 800; las mayúsculas ahí sí encajan con lo técnico.

### 3.2 El logotipo mide distinto en cada tienda

44 px en Tecsist, 78 en Baby Toncito, 96 en MegaHogar. El de MegaHogar domina el
encabezado entero; el de Tecsist casi desaparece. Sin un criterio común, cada tienda
parece de un proveedor distinto. Rango razonable: **40–56 px**.

### 3.3 Dos lenguajes de botón en la misma página

Baby Toncito mezcla botones de radio **8 px** con botones **píldora (9999)**.
Tecsist tiene radios 8 y 10 en botones que hacen lo mismo. Y hay **cinco alturas**
de botón: 50, 48, 44, 38 y 37 px.

Un solo botón primario: altura 44, radio 8, texto 14 px, peso 700.

---

## 4. Color y contraste

### 4.1 Textos que no llegan al mínimo legible

| Texto | Contraste | Mínimo | Tienda |
|---|---|---|---|
| **"Agregar al carrito"** | **2,1:1** | 4,5:1 | Baby Toncito |
| "Agregar al carrito" | 3,2:1 | 4,5:1 | Baby Toncito |
| Etiqueta de categoría (9 px) | 3,6:1 | 4,5:1 | Las tres |
| Contador del carrito | 2,1:1 | 4,5:1 | Las tres |
| "Ver todos los productos" | 3,1:1 | 4,5:1 | Tecsist |
| "32 productos" | 4,0:1 | 4,5:1 | MegaHogar |

El botón de comprar de Baby Toncito con 2,1:1 es el peor dato de toda la auditoría:
texto blanco sobre el verde azulado de marca. El color de marca está bien; **lo que
falla es usarlo de fondo con texto blanco**. Se arregla oscureciendo el verde solo
para ese uso, no cambiando la marca.

### 4.2 Siguen sobrando colores

28 colores no grises en Tecsist, 22 en las otras dos. La referencia son 12. Cada
color de más es una decisión que alguien tendrá que repetir bien la próxima vez.

### 4.3 Doce sombras distintas

Tecsist tiene **12 sombras** diferentes donde se acordaron dos. Nueve en las otras dos.
Y **once radios distintos** (5, 6, 7, 8, 9, 10, 12, 14, 20, 22, 24, 50, 999) donde la
jerarquía acordada eran cuatro.

Esto no se ve de golpe, se nota como "algo no termina de cuadrar".

---

## 5. Espacios y tamaños de sección

### 5.1 Dos tiendas respiran distinto que la tercera

Tecsist y Baby Toncito: **68 px** arriba y abajo por sección (136 px entre bloques).
MegaHogar: **46 px** (92 px). No hay razón para que difieran. 56 px es un buen término.

### 5.2 Bloques que ocupan más de lo que aportan

| Sección | Alto | Qué contiene |
|---|---|---|
| Tecsist · dos bloques de producto | **2.398 px** | 43 % de la portada |
| Baby Toncito · Nuestros favoritos | **1.511 px** | Una rejilla de productos |
| Baby Toncito · Conoce Baby Toncito | **730 px** | Un banner con texto |
| Baby Toncito · Elige su mundo | 370 px | **Dos enlaces** |
| MegaHogar · Compra por ambiente | 969 px | 8 ambientes (justificado) |

370 px para dos enlaces sigue siendo caro. Y 730 px para un banner de texto es
media pantalla de escritorio por un párrafo.

### 5.3 La marquesina de Tecsist

"AHORRA TIEMPO · COMPRA EN TECSIST · TIENDA VIRTUAL" se repite **tres veces** en el
ancho de pantalla, fija sobre el pie. El mensaje no aporta: que es una tienda virtual
ya se ve, y "ahorra tiempo" no es una promesa concreta. Ocupa espacio permanente y
lee a plantilla.

**Qué haría:** o un mensaje con dato ("Envío gratis desde S/ 200 · Garantía 12 meses")
o quitarla.

---

## 6. Encabezado

Lo que ya está bien: dos filas en móvil, buscador con ejemplo, zonas táctiles de 44 px,
menú móvil que abre los ocho ambientes en MegaHogar.

Lo que falta:

- **"Buscar en el catálogo" se corta** en las tres tiendas (el texto no cabe en su caja).
- **El contador del carrito** (el "0" naranja) tiene contraste 2,1:1 y mide 10 px.
  Cuando marque "3" nadie lo va a leer.
- **Tecsist tiene tres barras** antes del contenido: corporativa, encabezado y
  navegación. Suman 209 px en escritorio. Es defendible, pero es el triple que MegaHogar.

---

## 7. Pie

**MegaHogar (A−).** Cinco columnas, datos legales, horario, dirección, sellos. No lo
tocaría. Es la referencia.

**Baby Toncito (B).** Correcto pero con una columna floja: "Categorías" tiene dos
enlaces (Niño, Niña) frente a cinco de "Institucional", y queda un hueco a la derecha
de "Atención" de unos 350 px.

**Tecsist (C+).** Es un 50 % más alto que los otros (531 px frente a 350 y 365) porque
repite en cuatro mini-bloques los mismos beneficios que ya están arriba de la página:
envío, garantía, pago seguro y soporte. **Es la tercera vez que el visitante lee lo
mismo.** Además:

- Los iconos de cada categoría (monitor, computadora, laptop) son indistinguibles a 16 px.
- Las líneas separadoras bajo cada enlace crean una tabla que nadie pidió.
- La dirección tiene un error de escritura: *"Pasaje San José lote Mz 25 Lote 49 , Mala , Cañete."*
  — "lote" repetido con distinta capitalización y espacio antes de la coma.
- El logo sobre placa blanca redondeada parece una pegatina sobre el azul.

**En las tres:** el botón flotante de WhatsApp tapa la esquina inferior derecha de forma
permanente. Sobre el pie de Tecsist tapa un logo de medio de pago.

---

## 8. Experiencia de compra

### Baby Toncito (D+) — el recorrido está roto

1. El botón de comprar tiene contraste 2,1:1 y texto de 11 px.
2. La mitad del catálogo no tiene precio (31 de 57).
3. No hay ningún medio de pago configurado: el visitante llega al final y no sabe cómo pagar.

Con esos tres datos, la tienda no puede vender aunque el diseño fuera perfecto.

### MegaHogar (C) — el escaparate está vacío

1.127 productos, ninguno con foto. La tarjeta dice "Foto en camino" a 9,5 px.
Todo lo demás está bien resuelto.

### Tecsist (B) — el más cerca

Precios completos, medios de pago cargados, recorrido entero. Le falta contenido real:
el catálogo es de prueba, las redes apuntan a la portada de Facebook e Instagram sin
usuario, y el QR de Yape es un archivo llamado `yape-qr-prueba.png`.

---

## 9. Qué haría primero

1. **Subir el botón de compra a 13 px y contraste 4,5:1** en las tres. Es el elemento
   que convierte y hoy es el más pequeño y el peor contrastado de la tarjeta.
2. **Bajar el h1 de Baby Toncito a 40 px sin mayúsculas.** Es la marca contradiciéndose
   a sí misma en la primera línea que lee el visitante.
3. **Subir la etiqueta de categoría de 9 a 12 px** o quitarla. A 9 px no la lee nadie.
4. **Reducir a cinco tamaños de letra, dos pesos, cuatro radios y dos sombras.**
5. **Unificar el espaciado de sección a 56 px** en las tres.
6. **Quitar los cuatro mini-bloques repetidos del pie de Tecsist** y arreglar la dirección.
7. **Decidir la marquesina**: mensaje con dato o fuera.
8. **Igualar el logotipo a 40–56 px** en las tres.

Los ocho son de presentación. Ninguno necesita contenido del cliente.

---

## Método

`getComputedStyle` sobre 1.200 elementos por página; contraste calculado con la
fórmula WCAG contra el fondo heredado real; medición de tamaño de los botones para
detectar los que existen en el HTML pero no se ven; capturas por sección en 1440 y
390 px en `docs/auditoria/ux-final/`.

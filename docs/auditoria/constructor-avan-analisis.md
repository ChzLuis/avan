# Constructor AVAN — análisis y propuesta de rediseño

Auditoría del 2026-08-10, basada en un día completo trabajando dentro del panel
sobre tres tiendas reales (Tecsist, Baby Toncito, Mega Hogar). **Todo lo que sigue
está medido, no supuesto.**

---

## El diagnóstico en una frase

**El panel miente.** Mueves un control, se guarda, y en la tienda no pasa nada.
No es un caso aislado: encontré **seis controles muertos en un solo día**, sin
buscarlos — aparecieron al intentar usarlos.

Todo lo demás de este documento es consecuencia de eso.

---

## 1. Controles que no hacen nada

| Control | Qué lo anulaba | Tiendas afectadas |
|---|---|---|
| Alto del logo | `.brand-logo{height:64px!important}` | todas |
| Alto del logo (preset commerce) | `height:auto` + tope 96 px | Tecsist, Mega Hogar |
| Columnas del catálogo | `auto-fill minmax(220px)!important` | todas |
| Alto del hero | `min-height:480px!important` + `height:500px` | preset commerce |
| Letra de los títulos | el hero forzaba `var(--font-body)!important` | preset commerce |
| Desplegable de categorías | fijado por el preset, sin ajuste | multiverso |

**Causa raíz:** los presets de sección se escribieron **encima** del sistema de
tokens en lugar de a través de él. Hay **39 declaraciones `!important`** solo en
`section-preset-commerce`.

El sistema de diseño tiene dos autoridades peleando: los tokens del constructor y
los presets escritos a mano. Gana el que se declara más abajo en el archivo.

**Coste real para el cliente:** hoy me dijiste "el tamaño del logo no se aplica" y
"la vista previa no se actualiza". Las dos veces tenías razón y las dos veces el
panel te había dicho que sí se había guardado.

---

## 2. Ajustes sin control

Valores que el backend acepta y guarda, pero que **no existen en la interfaz**:

- **11 colores del sistema**: botón de compra, oferta, títulos, texto, texto
  secundario, fondo suave, bordes, banda de confianza, enlace del menú activo y
  al pasar. Para cambiar cualquiera había que editar código.
- **Perfil de catálogo**: `hero_mobile`, `footer_bg_color`, `header_text_color`.
  El controlador los validaba y guardaba; el formulario no los ofrecía.
- **Texto del envío sin coste**: el carrito prometía "Gratis" y no había forma de
  cambiarlo.

Todos añadidos hoy, pero el patrón importa: **el backend va por delante de la
interfaz** y nadie lo detecta hasta que un cliente pregunta.

---

## 3. Dos sistemas paralelos para lo mismo

| Concepto | Clave A | Clave B | Cuál gana |
|---|---|---|---|
| Alto del logo | `logo_height` | `header_logo_height` | la segunda, si existe |
| Alto del hero | `hero_height` (small/medium/large) | `hero_px_desktop` (número) | la segunda |
| Variante de sección | columna `store_sections.variant` | `content->variant` (JSON) | **la del JSON** |

El último me costó tiempo hoy: cambié la columna `variant` de la sección de
preguntas frecuentes, se guardó, y no pasó nada — porque la plantilla lee el JSON.
**Dos fuentes de verdad para el mismo dato.**

---

## 4. El panel no dice si algo salió bien o mal

El formulario de perfiles de catálogo fallaba **en silencio**. Sin mensaje de
error, sin confirmación, sin vista previa. El cliente pulsaba guardar y no pasaba
nada visible.

Tres causas distintas producían el mismo silencio:
1. permisos de carpeta (era de `root`),
2. archivo por encima del límite,
3. formato no admitido (AVIF).

Ninguna se comunicaba. Corregido hoy, pero **el patrón es general**: la mayoría de
formularios embebidos del panel no muestran errores de validación.

---

## 5. La vista previa servía caché

Cambiabas un ajuste y la vista previa seguía mostrando lo anterior, porque
recargaba el iframe con la misma URL. **Corregido hoy** pidiendo una URL distinta
en cada refresco.

Es el fallo más caro de todos en confianza: si la vista previa miente, el cliente
no puede trabajar.

---

## 6. Publicar puede destruir contenido

Al publicar desde el constructor se **vaciaron dos secciones** de Baby Toncito
("Por qué algodón pima" y "Tallas y cuidado"). Los textos se perdieron, no había
copia en borrador y **no hay deshacer**.

Tuve que reconstruirlos a mano con lo que tenía guardado en la conversación.

Esto es lo más grave del informe: **una acción normal del cliente puede borrar su
propio trabajo sin avisar y sin vuelta atrás.**

---

## 7. Navegación que pierde el sitio

Al enviar cualquier formulario embebido (perfiles, páginas, navegación), el
constructor volvía a la etapa 1. La etapa vive en el `#hash` de la URL y el
navegador no lo envía al servidor. **Corregido hoy.**

---

## Propuesta de rediseño

### Principio rector

**Un control que no puede aplicarse no se muestra.**

Antes que añadir funciones, el panel tiene que volverse creíble. Mientras un solo
control mienta, el cliente desconfía de todos.

### P0 — Que deje de mentir

1. **Contrato de tokens.** Prohibir `!important` sobre propiedades que ya tienen
   token (color, tipografía, alto de logo, columnas, alto de hero) en los presets
   de sección. Los presets deben *asignar tokens*, no pisar reglas. Son 39
   declaraciones a revisar en `commerce`, más las de los otros presets.
2. **Prueba automática de controles vivos.** Un comando que, por cada ajuste
   tokenizado, cargue la tienda, cambie el valor y compruebe que el valor
   computado cambió. Lo que no cambie, sale marcado. Hoy esto se descubre porque
   un cliente se queja.
3. **Una sola clave por concepto.** Migrar `logo_height` → `header_logo_height`,
   `hero_height` → `hero_px_desktop`, y la variante de sección al JSON. Dejar
   alias de lectura para no romper tiendas.

### P0 — Que no destruya

4. **Publicar nunca vacía.** Si el borrador de una sección llega vacío y el
   publicado tiene contenido, **no se publica**: se avisa. Ninguna publicación
   debería poder borrar texto que el cliente escribió.
5. **Historial de publicaciones.** Guardar la versión anterior de cada sección al
   publicar y permitir volver atrás. Hoy no hay red.

### P1 — Que comunique

6. **Estado por control:** guardado, error con motivo, y **vista previa
   inmediata** en los que suben archivos (hecho ya en perfiles, falta en el resto).
7. **Aviso de límites antes de enviar**: peso y formato, como en perfiles.

### P2 — Que se entienda

8. **Agrupar por resultado, no por tecnología.** Hoy hay que saber que el color
   del botón de compra vive en Apariencia y el texto del botón en Ventas.
9. **Buscador de ajustes.** Con más de 260 ajustes activos en una tienda, buscar
   por nombre ahorra más tiempo que cualquier reorganización.

---

## Lo que hace falta para terminar el análisis

**No he podido ver el panel.** Está detrás de login y la importación de cookies
falló (`DPAPI decryption failed`, pasa con el navegador abierto). Con la sesión
importada haría la auditoría visual: jerarquía, densidad, agrupación, legibilidad
y recorrido de cada etapa.

Este documento cubre **arquitectura y comportamiento**, que es donde están los
problemas graves. La capa visual está pendiente.

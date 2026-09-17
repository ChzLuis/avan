# Constructor — Revisión 06 · Páginas

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Sexto paso de la reorganización de "Mi Tienda" en 9 etapas.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 0. Corrección a la revisión 01

En la revisión 01 clasifiqué `settings/partials/institutional-pages` como una de
las "puertas duplicadas". **Era inexacto.**

La etapa 06 tiene **13 líneas** y lo único que hace es **incrustar ese mismo
partial**:

```blade
@include('settings.partials.institutional-pages', [
    'pages' => $storePages, 'showInstitutional' => true, 'showLegal' => false,
])
```

No son dos interfaces rivales: es **un componente montado en dos sitios**
(Constructor y Ajustes). Eso lo hace mucho menos grave de lo que dije — pero
introduce un problema distinto y peor, que es el hallazgo de esta revisión.

---

## 1. Hallazgo principal: la etapa 06 escribe 9 datos maestros de 01

`StoreExperienceController::page` —la ruta a la que postea el partial
(`settings.experience.page`)— acepta y guarda:

| Campo | Dueño según la matriz |
|---|---|
| `phone`, `whatsapp`, `address`, `email` | **01 Datos del negocio** |
| `hours` | **01 Datos del negocio** |
| `facebook_url`, `instagram_url`, `linkedin_url`, `tiktok_url` | **01 Datos del negocio** |
| `heading`, `body`, `hero_*`, `mission`, `vision`, `history`, `team`, `values`, `gallery_images`, `map_url`, `button_*`, `confirmation_message` | ✅ 06 Páginas |

Y **no los escribe en la fuente maestra: los copia dentro de
`store_pages.content`**, creando una **tercera copia** del mismo dato (tras la
columna de `projects` y el ajuste `contact_*`).

Tu estructura objetivo dice exactamente lo contrario:

> *"La página Contacto reutilizará teléfono, correo, horarios, redes y sucursales
> de Datos del negocio. Aquí solo se configurarán introducción, formulario, mapa
> y presentación."*

---

## 2. La divergencia ya ocurrió, y el cliente la ve

**Tecsist Solution (proyecto 18) tiene tres direcciones almacenadas, y dos no
coinciden:**

| Dónde | Valor |
|---|---|
| `projects.address` *(canónica)* | Avenida Panamericana Antigua Mz.25 - Lt.49, Mala, Cañete |
| `project_settings.contact_address` | **Pasaje San José, Mz. 25 Lt. 49** |
| `store_pages.contacto.content.address` | Avenida Panamericana Antigua Mz.25 - Lt.49, Mala, Cañete |

Verificado **en vivo** sobre `https://tienda.tecsist.net`:

```
tienda (pie)     → "Avenida Panamericana Antigua Mz.25 - Lt.49, Mala, Cañete"
                 → "Pasaje San José, Mz. 25 Lt. 49 — Mala, Cañete"
/contacto        → ambas, en la misma página
```

**Un comprador de Tecsist ve dos direcciones distintas para la misma tienda.**

> **No lo he corregido y creo que no debo.** El código muestra fielmente lo
> almacenado: el fallo es que la arquitectura permitió guardar dos valores. Elegir
> cuál es la buena es una decisión del comerciante, no mía. **Hay que preguntarle
> a Tecsist cuál es su dirección real** antes de tocar nada.
>
> Es, además, el mejor argumento para la fuente canónica: esto no es un caso
> hipotético, ya está pasando.

---

## 2 bis. Auditoría de divergencia en las 8 tiendas

| # | Tienda | `projects.address` *(canónica)* | `contact_address` | Página | Estado |
|---|---|---|---|---|---|
| 7 | Market Huacho | JR SAN ROMAN N° 140 | — | — | ✅ una sola fuente |
| 10 | GABDE | JR. HUAROCHIRI CUADRA 05… *(mayúsculas)* | Jr. Huarochirí cuadra 5, 2.° piso… *(reformateada)* | — | ⚠️ misma dirección, dos redacciones |
| 16 | Eskala | — | — | — | sin dato |
| 18 | **Tecsist** | Avenida Panamericana | **Pasaje San José** | Avenida Panamericana | 🔴 **divergencia real, visible** |
| 19 | MegaHogar | Jr. Odonovan 174 | Jr. Odonovan 174 | — | ✅ coinciden |
| 20 | Baby Toncito | Oficina Principal - Carabayllo, Lima | **Carabayllo** | — | ⚠️ una resume a la otra |
| 21 | **Electro Jara** | **VACÍA** | Av argentina 215, C.C. Nicolini… | — | 🔴 **la canónica está vacía** |
| 22 | Import Musuhuay | — | — | — | sin dato |

### ⚠️ Riesgo de pérdida de datos en la migración

**Electro Jara tiene `projects.address` VACÍA y su dirección real solo en el
ajuste.** Si la migración a "la columna es la fuente canónica" se aplica sin
volcar antes el ajuste a la columna, **Electro Jara se queda sin dirección** en
su tienda, su pie de página y su bot.

Lo mismo puede pasar con teléfono, WhatsApp y logo. **Antes de publicar la
implementación congelada de la etapa 01 hay que verificar el caso "columna vacía
+ ajuste con dato"** y volcar en esa dirección, no al revés.

Añadir a la lista de validación de 01:

> *La migración nunca debe dejar vacío un dato maestro que hoy tiene valor en
> alguna de las tres copias.*

---

## 3. Cobertura de la estructura objetivo

Tu 06 lista seis tipos de página. **Solo dos existen como página**:

| Página pedida | Dónde vive hoy | Estado |
|---|---|---|
| **Nosotros** | `store_pages` — 8 tiendas, **7 activas** | ✅ |
| **Contacto** | `store_pages` — 8 tiendas, **8 activas** | ✅ |
| **Sucursales** | tabla propia `sedes` (**vacía**) + ruta de panel `/company/sedes`; **sin ruta pública** | ⚠️ a medias |
| **FAQ** | **sección de Inicio** (3 tiendas activas), no página | ⚠️ otra naturaleza |
| **Blog** | **sección de Inicio** + rutas públicas `/blog` y `/blog/{key}` | ⚠️ ver §4 |
| **Páginas personalizadas** | **no existen** | ❌ backlog |

`store_pages` es una tabla mínima: `key`, `title`, `content` (JSON), `is_enabled`.

### Inconsistencia de arquitectura

**`store_pages` no tiene columnas `draft_*`.** `store_sections` sí tiene el ciclo
completo (borrador, programación, visibilidad por dispositivo, `published_at`).
Resultado: **las secciones de Inicio se editan en borrador y las páginas se
publican al instante.** Dos comportamientos distintos dentro del mismo
Constructor, y la etapa 09 ("Guardar borrador / Publicar") no podrá tratarlas
igual.

---

## 4. El blog está a medias

Las rutas públicas existen (`web.php:756-757`) pero **abortan con 404** si
`storefront_structure_v2 !== '1'`:

```php
abort_unless($context->setting('storefront_structure_v2','0')==='1', 404);
```

Ese ajuste está en **NULL en las 8 tiendas**, así que:

```
https://megahogar.org/blog        → 404
https://tienda.tecsist.net/blog   → 404
```

Los artículos no tienen tabla propia: viven en `store_sections.blog.content.items`.

Electro Jara (21) tiene la sección activa, pero con `items: []` y
`all_url: "#"` — **no hay enlace roto hacia el cliente**, solo una capacidad sin
terminar. Por eso no es hotfix.

---

## 5. Solapamiento con 03 Inicio

`faq` y `locations` existen como **sección de Inicio** y como **página** en tu
estructura objetivo. No es un error —un resumen en la portada y una página
completa son cosas distintas— pero hay que fijar de dónde sale el contenido:

| Contenido | Fuente canónica propuesta | Se muestra en |
|---|---|---|
| Preguntas frecuentes | `store_sections.faq.content.items` | sección de Inicio **y** página FAQ |
| Sucursales | tabla `sedes` | sección `locations` de Inicio, página Sucursales, Contacto, bot |

**Regla:** el contenido se escribe una vez; Inicio muestra un extracto y la
página lo muestra completo.

---

## 6. Propiedad recomendada

| Configuración | Fuente canónica | Se configura en | Solo se consume en |
|---|---|---|---|
| Nosotros (misión, visión, historia, equipo, galería) | `store_pages.nosotros.content` | 06 Páginas | Página Nosotros, sección `about_preview` |
| Contacto — introducción, formulario, mapa | `store_pages.contacto.content` | 06 Páginas | Página Contacto |
| **Contacto — teléfono, correo, horarios, redes, dirección** | **`projects.*` / ajustes de 01** | **01 Datos** | Página Contacto *(solo lectura)* |
| Sucursales | tabla `sedes` | 01 Datos *(o 06, ver §8)* | Página Sucursales, Contacto, Inicio, bot |
| FAQ | `store_sections.faq.content.items` | 03 Inicio | Inicio y página FAQ |
| Blog | `store_sections.blog.content.items` | 03 Inicio | Inicio y `/blog` |
| Visibilidad en el menú | `store_menu_items` | 06 Páginas | Navegación |

---

## 7. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | **Quitar del partial los 9 campos maestros**; que Contacto los lea de 01 en solo lectura | **Medio** — cambia lo que se ve donde ya divergió | **Alta** |
| 2 | Consultar a Tecsist su dirección real y unificar las tres copias | — | **Alta** *(dato de cliente)* |
| 3 | ✅ *hecho (§2 bis)* — 2 tiendas con divergencia real, 2 con redacciones distintas | — | — |
| 3b | **Volcar ajuste→columna donde la columna esté vacía, ANTES de migrar** *(Electro Jara)* | **Alto si se omite** | **Alta** |
| 4 | Dar a `store_pages` el ciclo `draft_*` de `store_sections` | Medio | Alta |
| 5 | Decidir el dueño de Sucursales (01 o 06) y darle ruta pública | Bajo | Media |
| 6 | Blog: o se habilita fuera de V2, o se retira de la interfaz hasta que exista | Medio | Media |
| 7 | Páginas personalizadas | — | Backlog |

**No se implementa nada todavía.** Sin hotfix en esta revisión: el caso de
Tecsist es **dato del comerciante**, no fallo de código, y corregirlo por mi
cuenta sería inventar cuál de sus dos direcciones es la buena.

---

## 8. Decisiones que necesito del usuario

1. **Sucursales: ¿01 o 06?** Son dato del negocio (01) pero también una página
   (06). Mi recomendación: **el dato vive en 01** (tabla `sedes`) y 06 solo
   controla si la página existe y cómo se presenta.
2. **Tecsist**: ¿le preguntas tú cuál es su dirección real, o dejo el hallazgo
   documentado para cuando lo atiendan?
3. **Blog**: ¿se libera del requisito de `storefront_structure_v2`, o se oculta
   la sección hasta que la capacidad esté completa? Hoy un comerciante puede
   activarla y no lleva a ninguna parte.

---

## 9. Siguiente paso

Revisión **07 Footer y legales**: las 16 claves de la etapa `legal`, las **15
colisiones con Apariencia** ya detectadas, el Libro de Reclamaciones, los textos
legales y su uso en el checkout.

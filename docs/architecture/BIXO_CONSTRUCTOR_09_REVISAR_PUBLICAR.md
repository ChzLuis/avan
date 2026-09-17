# Constructor — Revisión 09 · Revisar y publicar

Auditoría verificada el **2026-08-30** contra el código y la BD de producción.
Último paso de la reorganización de "Mi Tienda" en 9 etapas.

**Principio obligatorio:** cada dato o configuración tiene un único propietario
y un único lugar donde se modifica. Las demás áreas solo lo consumen.

---

## 1. La buena noticia: la taxonomía de 9 etapas ya existe en el código

`app/Storefront/BuilderRuleRegistry.php` (204 líneas) declara exactamente tus
nueve etapas, **con tus nombres**:

> Datos del negocio · Apariencia · Página de inicio · Catálogo · Venta ·
> Páginas · Footer y legales · Configuración · **Revisar y publicar**

La reorganización **no es una reescritura**: el esqueleto está puesto. Lo que
falta es que cada etapa posea lo que le corresponde.

---

## 2. El motor de auditoría es real y está bien diseñado

Cada regla tiene:

```php
['code' => 'catalog.products_without_price', 'stage' => 'catalog',
 'severity' => fn ($c) => (($c['settings']['store_mode'] ?? 'direct') === 'direct') ? 'attention' : 'info',
 'weight' => 8,
 'message' => 'Hay productos sin precio (no podrán comprarse directamente).',
 'target' => 'catalog.fix-price',
 'blocks_publish' => false,
 'evaluator' => fn ($c) => …,
 'count' => fn ($c) => $c['counts']['without_price']]
```

Tiene todo lo que hace falta: **severidad dinámica** (un producto sin precio es
grave si vendes directo, informativo si solo cotizas), **peso**, **contador**,
**deep-link** al punto exacto que hay que arreglar (`goFix` en
`script.blade.php` salta a la etapa y abre la lista correcta), y —lo importante
para tu requisito— el campo **`blocks_publish`**.

### 2.1 · Pero ninguna regla bloquea la publicación

```
'blocks_publish' => true  →  0 coincidencias
```

**Las 18 reglas tienen `blocks_publish => false`.** El mecanismo que pides
—*"No permitirá publicar si existe un error crítico, pero sí cuando solamente
haya recomendaciones"*— **está construido y desactivado**. No hay que
programarlo: hay que **decidir qué es crítico** y marcarlo.

### 2.2 · Cobertura desigual

| Etapa | Reglas |
|---|---|
| Datos del negocio | 4 |
| Catálogo | 5 |
| Apariencia | 3 |
| Página de inicio | 3 |
| Venta | 2 |
| Páginas | 1 |
| Footer y legales | 1 |
| **Configuración** | **0** |
| **Revisar y publicar** | **0** |

**18 reglas** repartidas como 7 `attention`, 10 `recommendation`, 1 `info`.
Las dos últimas etapas no tienen ninguna — coherente con la revisión 08, que
encontró la etapa Configuración casi vacía.

---

## 3. Cobertura frente a lo que pides

| Comprobación pedida | Estado |
|---|---|
| Auditoría automática | ✅ 18 reglas con peso y severidad |
| Errores pendientes | ✅ severidad `attention` |
| Advertencias | ✅ `recommendation` / `info` |
| Secciones incompletas | ✅ `home.sections_missing`, `home.slider_empty`, `home.too_few_sections` |
| **Enlaces rotos** | ❌ no existe |
| **Contraste y accesibilidad** | ❌ no existe |
| **Vista móvil / tablet / escritorio** | ⚠️ hay `preview.blade.php`; falta confirmar los 3 tamaños |
| Vista de Inicio | ✅ vista previa del Constructor |
| **Vista de Tienda** | ⚠️ por confirmar |
| **Publicación bloqueada por error crítico** | ⚠️ **mecanismo listo, sin activar** |

Faltan tres comprobaciones y la activación del bloqueo.

---

## 4. El problema que hereda de las revisiones anteriores

**Las páginas no tienen borrador.** `store_sections` tiene el ciclo completo
(`draft_*`, `publish_from/until`, `published_at`); **`store_pages` no tiene
ninguna columna `draft_*`** (revisión 06).

Consecuencia directa para 09: **"Guardar borrador" y "Publicar" no pueden
significar lo mismo para todo.** Hoy las secciones de Inicio se acumulan en
borrador y las páginas se publican al instante. Una etapa 09 que diga "publicar
todo" mentiría: parte del contenido ya está publicado desde que se escribió.

**Ésta es la dependencia que hay que resolver antes de dar por buena la 09.**

---

## 5. Propiedad recomendada

| Configuración | Fuente canónica | Se configura en | Se consume en |
|---|---|---|---|
| Reglas de auditoría | `BuilderRuleRegistry` *(código)* | — | 09 y el indicador de cada etapa |
| Qué bloquea publicar | `blocks_publish` por regla | **decisión de producto** | 09 |
| Estado borrador/publicado de secciones | `store_sections.draft_*` | 03 | 09 |
| Estado borrador/publicado de páginas | ❌ **no existe** | ➜ crear | 09 |
| Vista previa por dispositivo | `builder/preview.blade.php` | — | 09 |

09 **no debe poseer ningún ajuste de tienda**: es una etapa de verificación y
publicación. Es la única que puede leerlo todo sin ser dueña de nada.

---

## 6. Trabajo que sale de esta revisión

| # | Tarea | Riesgo | Prioridad |
|---|---|---|---|
| 1 | **Decidir qué reglas bloquean publicar** y marcarlas `blocks_publish => true` | Bajo | **Alta** |
| 2 | Dar ciclo `draft_*` a `store_pages` *(bloquea la coherencia de 09)* | Medio | **Alta** |
| 3 | Reglas para las etapas Configuración y Revisar *(hoy 0)* | Bajo | Media |
| 4 | Comprobación de enlaces rotos | Bajo | Media |
| 5 | Comprobación de contraste y accesibilidad | Bajo | Media |
| 6 | Confirmar/añadir las 3 vistas (móvil, tablet, escritorio) y la de Tienda | Bajo | Media |

**No se implementa nada todavía.** Sin hotfix: el motor de auditoría informa
correctamente; su limitación es de alcance, no de exactitud.

---

## 7. Decisiones que necesito del usuario

1. **¿Qué impide publicar?** Mi propuesta de mínimos, todos hoy en `attention`:
   - `catalog.no_products` — publicar una tienda sin productos
   - `business.*` — sin nombre ni forma de contacto
   - `appearance.template_missing` — sin plantilla elegida

   El resto quedaría como recomendación, respetando tu regla de que las
   advertencias no bloqueen.
2. **Borrador en páginas** — ¿se les da el ciclo completo (coherente, más
   trabajo) o 09 asume que las páginas se publican al guardar (menos trabajo,
   dos comportamientos distintos)? Mi recomendación: **ciclo completo**.

---

## 8. Las 9 revisiones están cerradas

| Revisión | Documento |
|---|---|
| 01 Datos del negocio | `BIXO_CONSTRUCTOR_01_DATOS_NEGOCIO.md` |
| 02 Apariencia | `BIXO_CONSTRUCTOR_02_APARIENCIA.md` |
| 03 Página de inicio | `BIXO_CONSTRUCTOR_03_INICIO.md` |
| 04 Catálogo | `BIXO_CONSTRUCTOR_04_CATALOGO.md` |
| 05 Venta | `BIXO_CONSTRUCTOR_05_VENTA.md` |
| 06 Páginas | `BIXO_CONSTRUCTOR_06_PAGINAS.md` |
| 07 Footer y legales | `BIXO_CONSTRUCTOR_07_FOOTER_LEGALES.md` |
| 08 Configuración avanzada | `BIXO_CONSTRUCTOR_08_AVANZADA.md` |
| 09 Revisar y publicar | *(este documento)* |

**Matriz maestra:** `BIXO_CONSTRUCTOR_MATRIZ.md`

Siguiente paso del proceso: **validar la implementación congelada de la etapa
01** contra la matriz —incluido el riesgo de pérdida de datos detectado en la
revisión 06— y decidir el orden de ejecución.

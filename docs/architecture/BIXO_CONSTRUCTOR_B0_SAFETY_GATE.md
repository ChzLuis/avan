# Fase B · B0 — Safety gate de Datos 01

Ejecutado el **2026-08-30** en **modo solo lectura**. Ningún valor fue
modificado.

Objetivo: validar la implementación congelada de la etapa 01 contra la matriz
maestra, y clasificar el estado real de los datos maestros en las 8 tiendas
antes de autorizar cualquier escritura.

---

## 1. Validación de la implementación congelada

| Comprobación pedida | Resultado |
|---|---|
| ¿Introdujo una segunda fuente? | ✅ **No.** `business_name` separa el nombre comercial de `seo_title`, que vuelve a ser solo SEO |
| ¿Respeta borrador/publicación? | ✅ **Sí.** `putSetting()` escribe **solo** en `builder_drafts`; no toca `project_settings` ni `projects` |
| ¿Escribe columnas productivas mientras se edita un borrador? | ✅ **No.** `publish()` es el único punto de escritura de columnas |
| ¿Cancelar borrador deja `projects` modificado? | ✅ **No.** `discardDraft()` borra solo `builder_drafts`; como nada se escribió antes, no hay nada que revertir |
| ¿**Rollback** deja `projects` modificado? | ❌ **SÍ — DEFECTO** *(ver §1.1)* |
| ¿Los alias antiguos son solo transición? | ✅ Sí. `contact_phone`, `quote_whatsapp`, `contact_address` siguen leyéndose, ninguno se promueve a fuente |
| ¿Rompe módulos externos? | ✅ No. Al contrario: sincroniza las columnas que leen facturación, PDF, POS, portal y **el bot** |
| ¿Lógica que choque con 05–08? | ⚠️ Una: `settings/payments` sigue escribiendo `quote_whatsapp` *(revisión 05)*. No es culpa de esta implementación, pero la contradice |

### 1.1 · DEFECTO: el rollback no restaura las columnas de `projects`

`publish()` escribe seis columnas canónicas:

```php
'business_name' => 'name',      'contact_phone'  => 'phone',
'quote_whatsapp' => 'whatsapp', 'contact_address' => 'address',
'logo_url' => 'logo_url',       'business_category' => 'category',
```

Pero `rollback()` restaura **solo `settings` y `store_sections`**:

```php
foreach (($snapshot['settings'] ?? []) as $key => $value) { … }
foreach (($snapshot['sections'] ?? []) as $component => $state) { … }
```

Y el snapshot guardado en `store_publications` **ni siquiera contiene** las
columnas de `projects`, así que el rollback no podría restaurarlas aunque
quisiera.

**Consecuencia:** publicar un cambio de nombre, teléfono, WhatsApp, dirección,
logo o rubro y luego hacer rollback deja **el dato nuevo en `projects`** y el
viejo en los settings. Se crea exactamente la divergencia que esta arquitectura
quiere eliminar — y encima en la operación pensada para deshacer.

**Corrección necesaria antes de desplegar 01:** incluir las 6 columnas en el
snapshot y restaurarlas en `rollback()`.

### 1.2 · Riesgo del que me retracto parcialmente

En la revisión 06 advertí que migrar a "la columna gana" podía borrar la
dirección de Electro Jara. **Revisada la implementación, eso no ocurre:**
`publish()` solo escribe columnas cuya clave **está en los borradores de esa
publicación** (`array_key_exists($settingKey, $drafts)`), y además ignora los
valores vacíos. No hace una migración masiva.

El riesgo real es otro y sigue vigente: **la dirección de Electro Jara nunca
llegará sola a la columna canónica**. Se queda en el ajuste hasta que alguien la
edite a mano en el Constructor. Por eso la migración explícita sigue siendo
necesaria — pero no hay peligro de borrado.

---

## 2. Reporte de reconciliación — 8 tiendas × 8 campos

Estados: `OK` · `AUTO_MIGRATABLE` · `MANUAL_DATA_RESOLUTION_REQUIRED` ·
`LEGACY_ONLY` · `EMPTY`

| Proyecto | Campo | Canónico | Setting | store_pages | Estado | Acción |
|---|---|---|---|---|---|---|
| 7 Market Huacho | nombre | Market Huacho Express | — | — | `OK` | — |
| 7 | teléfono | 947 740 037 | — | — | `OK` | — |
| 7 | whatsapp | 947 740 037 | 51947740037 | — | `OK` | mismo número con prefijo país — normalizar |
| 7 | dirección | JR SAN ROMAN N° 140 | — | — | `OK` | — |
| 7 | **logo** | — | `logos/7/Uwge…png` | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** |
| 7 | rubro / correo / horarios | — | — | — | `EMPTY` | — |
| 10 GABDE | nombre | Distribuidores GABDE | — | — | `OK` | — |
| 10 | **teléfono** | **941 510 319** | **955354646** | — | 🔴 `MANUAL` | **números distintos** |
| 10 | **whatsapp** | **941 510 319** | **955354646** | — | 🔴 `MANUAL` | **números distintos** |
| 10 | **dirección** | JR. HUAROCHIRI CUADRA 05… | Jr. Huarochirí cuadra 5, 2.° piso… | — | 🟡 `MANUAL` | mismo lugar, dos redacciones |
| 10 | **rubro** | retail | ferreteria | — | 🟡 `MANUAL` | genérico vs específico |
| 10 | **logo** | — | `logos/10/ysiA…png` | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** |
| 16 Eskala | nombre / teléfono / whatsapp / rubro | con valor | — | — | `OK` | — |
| 16 | dirección / logo / correo / horarios | — | — | — | `EMPTY` | — |
| 18 Tecsist | nombre | Tecsist Solution S.A.C | — | — | `OK` | — |
| 18 | teléfono | 900438114 | 900438114 | — | `OK` | — |
| 18 | whatsapp | 900438114 | 51900438114 | — | `OK` | prefijo país — normalizar |
| 18 | **dirección** | Avenida Panamericana… | **Pasaje San José…** | Avenida Panamericana… | 🔴 `MANUAL` | **conflicto visible en la tienda** |
| 18 | **rubro** | retail | tecnologia | — | 🟡 `MANUAL` | genérico vs específico |
| 18 | **logo** | — | `logos/18/OaF1…png` | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** |
| 19 MegaHogar | nombre / teléfono / whatsapp / dirección | coinciden | coinciden | — | `OK` | — |
| 19 | horarios | *(sin columna)* | Lunes a sábado… | — | `OK` | el ajuste **es** la fuente |
| 19 | **rubro** | retail | muebles | — | 🟡 `MANUAL` | genérico vs específico |
| 19 | **logo** | — | `logos/19/tROA…png` | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** |
| 20 Baby Toncito | nombre / teléfono / whatsapp | coinciden | coinciden | — | `OK` | — |
| 20 | **dirección** | Oficina Principal - Carabayllo, Lima | **Carabayllo** | — | 🟡 `MANUAL` | una resume a la otra |
| 20 | **rubro** | retail | bebes | — | 🟡 `MANUAL` | genérico vs específico |
| 20 | **logo** | — | `logos/20/8uMl…png` | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** |
| 21 Electro Jara | nombre | Electro Jara | — | — | `OK` | — |
| 21 | **dirección** | **vacía** | Av argentina 215, C.C. Nicolini… | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** *(Caso A)* |
| 21 | **whatsapp** | **vacío** | 955 065 389 | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** *(Caso A)* |
| 21 | teléfono | — | — | — | `EMPTY` | ⚠️ ver §3.3 |
| 21 | **rubro** | retail | ferreteria | — | 🟡 `MANUAL` | genérico vs específico |
| 21 | **logo** | — | `logos/21/V7ej…jpg` | — | `LEGACY_ONLY` | **AUTO_MIGRATABLE** |
| 22 Import Musuhuay | nombre | IMPORT MUSUHUAY SAC | — | — | `OK` | — |
| 22 | rubro | retail | — | — | `OK` | — |
| 22 | resto | — | — | — | `EMPTY` | — |

### Resumen

| Estado | Celdas | Detalle |
|---|---|---|
| 🔴 `MANUAL` — valores realmente distintos | **3** | GABDE teléfono y whatsapp · Tecsist dirección |
| 🟡 `MANUAL` — misma cosa, distinta redacción | **7** | 5 rubros · GABDE dirección · Baby Toncito dirección |
| `AUTO_MIGRATABLE` | **8** | 6 logos · Electro Jara dirección y whatsapp |
| `OK` / `EMPTY` | resto | — |

---

## 3. Tres hallazgos que no esperaba

### 3.1 · `projects.category` es un marcador genérico en 5 tiendas

Las 5 tiendas con rubro tienen **`retail` en la columna** y el rubro real en el
ajuste (`ferreteria`, `tecnologia`, `muebles`, `bebes`). La columna canónica
contiene un valor de relleno, no el dato bueno.

Es el **único caso de la auditoría en el que la copia antigua es más correcta
que la fuente canónica**. Aplica el Caso D: no sobrescribir en silencio. Mi
lectura es que aquí **debe ganar el ajuste**, pero es decisión tuya y afecta a 5
tiendas.

### 3.2 · `projects.logo_url` está vacía en las 8 tiendas

**Ninguna** tienda tiene el logo en la columna canónica: 6 lo tienen solo en el
ajuste y 2 no lo tienen. Los ~95 consumidores de `project->logo_url` funcionan
porque las plantillas resuelven el ajuste primero.

No es un fallo activo, pero significa que la columna que la matriz declara
canónica **hoy no la usa nadie**. La migración de los 6 logos es segura
(`AUTO_MIGRATABLE`, Caso A).

### 3.3 · GABDE tiene guardado un número de pruebas

El teléfono y el WhatsApp de GABDE en el ajuste son **955354646** — el número
que se usó como línea de pruebas del bot. La columna canónica tiene 941 510 319.

No lo resuelvo yo, pero apunta a que el valor bueno es el de la columna y el del
ajuste es residuo de pruebas. **Confírmalo antes de migrar.**

---

## 4. Veredicto de B0

**No está limpio.** Aparecen dos bloqueos:

1. **Defecto de código:** `rollback()` no restaura las columnas de `projects`
   (§1.1). Debe corregirse antes de desplegar la etapa 01.
2. **10 celdas requieren resolución humana** (§2), de las cuales 3 son valores
   realmente distintos.

Ninguno impide continuar con **B1**, que no toca datos: retirar controles
duplicados no modifica ninguna clave ni ningún valor.

### Marcado formal

```
MANUAL_DATA_RESOLUTION_REQUIRED
  proyecto 18 (Tecsist)      · dirección  · conflicto visible en tienda
  proyecto 10 (GABDE)        · teléfono   · 941 510 319  vs  955354646
  proyecto 10 (GABDE)        · whatsapp   · 941 510 319  vs  955354646
  proyecto 10 (GABDE)        · dirección  · dos redacciones
  proyecto 20 (Baby Toncito) · dirección  · una resume a la otra
  proyectos 10,18,19,20,21   · rubro      · retail vs rubro específico
```

---

## 5. Qué necesito de ti para desbloquear las migraciones

1. **Tecsist** — ¿cuál es su dirección real?
2. **GABDE** — ¿941 510 319 (columna) o 955354646 (ajuste)? Sospecho residuo de
   pruebas.
3. **Baby Toncito** — ¿"Oficina Principal - Carabayllo, Lima" o "Carabayllo"?
4. **Rubro** — ¿autorizo que gane el ajuste específico sobre el `retail`
   genérico en las 5 tiendas?

Con eso ejecuto las migraciones `AUTO_MIGRATABLE` (8 celdas, sin riesgo) y las
manuales confirmadas.


---

# ANEXO · Cierre de B0 y B1 (2026-08-30)

## A. Defecto de rollback: CORREGIDO

`BuilderDraftService` ahora comparte una constante `COLUMNAS_MAESTRAS` entre
`publish()` y `rollback()`:

- `publish()` guarda en el snapshot el estado previo de las 6 columnas
  (`getOriginal()`, antes de tocar nada).
- `rollback()` las restaura **tal cual estaban, NULL incluido**, y **nunca las
  reconstruye desde los alias** — que pueden haber divergido.

### Pruebas — `tests/Feature/BuilderDatosMaestrosCicloTest.php` · 8/8 verdes

| Escenario pedido | Prueba |
|---|---|
| Draft sin publicar | `test_draft_no_toca_las_columnas_de_produccion` |
| Publicar | `test_publicar_promueve_a_las_columnas_canonicas` |
| Cancelar | `test_cancelar_borrador_no_deja_columnas_modificadas` |
| **Deshacer** | `test_rollback_restaura_las_columnas_maestras` |
| Segundo draft | `test_segundo_borrador_cancelado_vuelve_al_ultimo_publicado` |
| Rollback parcial | `test_editar_un_campo_no_modifica_los_demas` |
| NULL | `test_un_valor_originalmente_nulo_vuelve_a_nulo` |
| *(extra)* ajuste vacío | `test_vaciar_el_ajuste_no_borra_el_dato_maestro` |

**Verificación de que las pruebas sirven:** con el código anterior fallan **3 de
8**; con la corrección pasan las 8.

## B. Las 18 huérfanas de B1: cerradas

**Criterio de salida `ajustes vivos sin editor = 0` — CUMPLIDO.**

| Grupo | Nº | Destino | Cómo |
|---|---|---|---|
| `ticker_*` | 5 | **02 Apariencia** | Bloque extraído del código muerto y publicado como tarjeta "Franja animada" |
| `section_head_*` | 5 | **02 Apariencia** | Tarjeta "Títulos de sección" |
| `pastel_band_1/2/3` | 3 | **03 Inicio** | Junto a `section_background_mode`, visibles solo en modo pastel |
| `footer_accent_color`, `footer_bg2_color`, `footer_cats_limit`, `footer_dev_text`, `footer_show_benefits` | 5 | **07 Footer y legales** | Tarjeta nueva "Pie avanzado" |

### Corrección: ninguna estaba muerta

Dije que `footer_cats_limit`, `footer_accent_color` y `footer_bg2_color` tenían
**0 consumidores** y propuse marcarlas `DEPRECATED_CANDIDATE`. **Era falso.**
Las tres se consumen en
`resources/views/storefront/partials/footers/technology.blade.php` — la misma
ruta que ya se me había escapado en la revisión 02. Con datos reales: 4 tiendas
tienen colores propios de pie.

**Ninguna de las 8 se marcó como deuda: las 8 recibieron editor.**

## C. Rubro — tabla previa a cualquier cambio

Taxonomía válida de BIXO (`StagePresets::all()`, 11 rubros): `tecnologia`,
`ferreteria`, `muebles`, `ropa`, `bebes`, `restaurante`, `veterinaria`,
`optica`, `servicios`, `mayorista`, `electricidad`.

`retail` y `otro` **no pertenecen a la taxonomía**: son marcadores genéricos.

| Tienda | `projects.category` actual | Alternativo | Resultado propuesto |
|---|---|---|---|
| 10 Distribuidores GABDE | `retail` | `ferreteria` ✅ válido | **`ferreteria`** |
| 18 Tecsist Solution | `retail` | `tecnologia` ✅ válido | **`tecnologia`** |
| 19 Corporación MegaHogar | `retail` | `muebles` ✅ válido | **`muebles`** |
| 20 Baby Toncito | `retail` | `bebes` ✅ válido | **`bebes`** |
| 21 Electro Jara | `retail` | `ferreteria` ✅ válido | **`ferreteria`** |
| 7 Market Huacho | — | — | sin cambio |
| 16 Eskala | `otro` | — | sin cambio *(no hay alternativo)* |
| 22 Import Musuhuay | `retail` | — | sin cambio *(no hay alternativo)* |

Regla aplicada: **el alternativo gana solo si pertenece a la taxonomía válida**.
Nunca "si es distinto de retail, gana". Los 5 alternativos la cumplen.

**Pendiente de tu visto bueno para ejecutar la migración.**

## D. Conflictos que siguen bloqueados

`MANUAL_DATA_RESOLUTION_REQUIRED` — ninguna migración automática puede tocarlos:

| Tienda | Campo | Valores en conflicto |
|---|---|---|
| 18 Tecsist | dirección | `Avenida Panamericana Antigua` vs `Pasaje San José` |
| 10 GABDE | teléfono | `941 510 319` vs `955354646` |
| 10 GABDE | whatsapp | `941 510 319` vs `955354646` |
| 10 GABDE | dirección | dos redacciones del mismo lugar |
| 20 Baby Toncito | dirección | ver §E — puede no ser conflicto |

### E. Baby Toncito: probablemente NO es un conflicto

`projects.address` = "**Oficina Principal** - Carabayllo, Lima"
`contact_address` = "Carabayllo"

Leídos juntos, no compiten: el primero mezcla **nombre de sede** + **ubicación**;
el segundo es solo el **distrito**. Descomposición natural:

- Nombre de sede: `Oficina Principal`
- Dirección / ubicación: `Carabayllo, Lima`

La columna canónica contiene **más información**, así que migrar hacia
"Carabayllo" sería destructivo. Recomendación: **conservar el valor de la
columna** y, cuando exista el modelo de Sucursales (tabla `sedes`), separar
nombre de sede y ubicación en campos propios.

Se mantiene marcado hasta que lo confirmes, pero **no como conflicto real**.

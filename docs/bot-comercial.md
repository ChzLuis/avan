# Bot Comercial Informativo

Primera línea de atención estándar de la plataforma: informa, orienta y busca en
el catálogo con **datos reales del sistema**; no vende solo. Todo lo que huela a
negociación (comprar, cotizar, cantidades, descuentos especiales, reclamos) se
deriva a una persona y el bot se calla.

## Cómo se activa para una empresa nueva

Panel → **Bots** → botón **"💬 Activar Bot Comercial"**. Crea un flujo desde
`PlantillaComercial` (nace **desactivado**: el dueño revisa los textos en el
constructor visual y lo enciende). La misma pantalla enseña el **checklist** de
datos: catálogo, pagos, dirección, horario, tienda online, teléfono — con ✓/⚠.

Todos los textos (bienvenida, guía de compra, cierre, derivación) son bloques
del constructor: **configurables por empresa sin tocar código**.

## Arquitectura (reutiliza la existente)

| Capa | Pieza | Estado |
|---|---|---|
| Canal | Baileys → `Api/BotWebhookController::inbound` (token por proyecto) | reutilizada |
| Conversación | `BotSession` (estado por teléfono) + historial en CRM | reutilizada |
| Interpretación | bloque **`intencion`** (nuevo): clasificador por reglas, sin IA | nueva |
| Acción | `ProjectContext` — `negocio()`, `promocionesVigentes()`, `fichaProducto()` (nuevos) + `buscarTolerante()`, `datosPago()` (existentes) | mixta |
| Formateo | dentro de cada bloque, formato WhatsApp (corto, escaneable, CTA) | nueva |
| Handoff | regla `tiene_vendedor→silenciar` + `registrar_crm` etiqueta `pide-asesor` | reutilizada |

### Bloques nuevos del motor (`FlowRunner`)

- **`info_negocio`** (`dato: direccion|horario|contacto|empresa|web`) — lee
  `ProjectContext::negocio()`: dirección (+link de Maps con `map_coords` si hay),
  horario (`business_hours`/`contact_hours`), RUC/razón social, redes, web.
- **`metodos_pago`** — Yape/Plin/bancos/manuales de la configuración real
  (`payment_*`), con QR si existe. `payment_manual_methods` acepta JSON o texto.
- **`promociones`** — solo campañas `Promotion::isActive()` y productos con
  `compare_price > price`. Lista numerada → responde al número con la ficha.
- **`consultar_producto`** — búsqueda tolerante + desambiguación numerada +
  **contexto**: recuerda `_ultimo_prod` para "¿tiene descuento?"; un número en
  la consulta es exacto ("indeco 12" ≠ 14); cualquier otro texto es nueva
  búsqueda o, si es otra intención, salta a su rama.
- **`intencion`** — enrutador por diccionario normalizado (sin IA). Con
  `esperar: true` funciona como cierre que **escucha** ("¿te ayudo con algo
  más?" → la siguiente consulta se clasifica, el flujo no muere).
- **`lista.no_coincide`** — el modelo híbrido: texto libre que no es opción del
  menú va al clasificador en vez de repetir el menú.

### Reglas de negocio

- **No alucina**: cada dato faltante responde "No tengo esa información
  registrada" + oferta de asesor. Protegido por test.
- **Derivación automática**: comprar/pedido/cotizar/unidades/crédito/reclamo/
  soporte/persona → `registrar_crm` (etapa `contactado`, etiqueta `pide-asesor`),
  el bot termina y la regla `tiene_vendedor` lo mantiene callado.
- **Aislamiento**: todo pasa por `ProjectContext::for($project)`; ninguna
  consulta cruza proyectos.

### IA

No se usa en esta plantilla. La interpretación es por reglas; la fuente de
verdad es la base de datos. Los bloques `ia`/`asistente` existentes siguen
disponibles si un dueño quiere añadirlos a su flujo.

## Pruebas

`tests/Feature/BotComercialTest.php` (18 casos): saludo/menú, "¿tienen cable
Indeco?" (desambiguación), "cuánto cuesta el 12" (ficha directa con precio y
oferta reales), seguimiento contextual ("2" → "¿tiene descuento?"), número
fuera de rango, producto inexistente, Yape/pagos, dirección con mapa, horario,
**horario sin configurar → honestidad**, promociones solo reales, "quiero
comprar" → asesor + CRM, híbrido dentro de la consulta, cierre que escucha,
checklist, y que la plantilla solo usa bloques que el motor ejecuta.

Auditoría en producción (Tecsist/MegaHogar): conversación completa verificada
con datos reales, incluida la ficha con foto y URL del producto.

## Pendientes conocidos

- Tecsist no tiene `business_hours` configurado (el checklist lo marca ⚠).
- La rama "dirección y horarios" encadena ambos; si falta el horario, tras la
  dirección aparece el aviso honesto de horario. Aceptado a propósito.
- El panel de administración fina (activar/desactivar campos de la ficha,
  guías con orden) queda para una segunda fase; hoy todo se edita como bloques
  en el constructor visual.


---

# Concepto definitivo: UN solo Bot Comercial, IA opcional (2026-08-21)

**Principio rector**: la IA mejora la conversacion; no reemplaza el motor
comercial ni la base de datos. Un solo bot, dos modalidades comerciales:
**Bot Comercial Estandar** (sin costo de IA) y **Bot Comercial + IA** (misma
infraestructura, misma fuente de datos, capa de interpretacion adicional).

## Auditoria de la capa IA existente

- `app/Ia/IA.php` + 4 proveedores (Anthropic/OpenAI/Gemini/DeepSeek) via
  `config('ia')`: proveedor y API key **globales de la plataforma** (.env),
  timeout 60s, try/catch en todos los puntos de uso (`sin_ia` / fallback fijo).
- Bloques `ia` y `asistente` del FlowRunner: conviven YA con los flujos (son
  bloques mas del mismo grafo). El `asistente` inyecta el catalogo al prompt
  (`briefParaIa`) — datos-en-prompt, no tool-calling.
- `RouterComercial` es el asesor de venta DE ESKALA (Valeria, datos duros
  hardcodeados del servicio de tiendas): **no es parte del Bot Comercial de
  clientes** y no debe confundirse con el.
- Gating por proyecto: existe el patron `modules` (pivot por proyecto) y el
  patron `feature_*` en project_settings. **No existe hoy un switch de IA por
  proyecto**: la IA es global.

## Que YA cumple el concepto

| Punto del concepto | Estado |
|---|---|
| Un solo motor (flujos + IA conviven) | ✓ FlowRunner es el unico motor; los bloques IA son bloques del grafo |
| BD = fuente de verdad (modo estandar) | ✓ todos los bloques comerciales consultan ProjectContext |
| Busqueda: exacta, SKU, nombre, categoria, normalizacion, plural, Levenshtein, numero exacto | ✓ |
| Un solo sistema de listas (nativa + fallback numerado) | ✓ |
| Menu guiado completamente funcional SIN IA | ✓ (18 tests) |
| Contexto: desambiguacion numerada + producto en foco | ✓ |
| Handoff unico via CRM | ✓ |
| Canal separado del motor (Baileys transporta, webhook decide) | ✓ |
| Degradacion a nivel de bloque IA (try/catch + fallback) | ✓ |

## GAP ANALYSIS — lo que falta

1. **G1 · IA por proyecto (comercial).** Hoy la IA es global. Falta el switch
   por empresa: setting `feature_bot_ia` (patron feature_* existente) y/o
   modulo `bot_ia` para licenciamiento. Las API keys siguen centrales (la
   plataforma paga y revende: es como se controla el costo).
2. **G2 · Tool-calling en vez de datos-en-prompt.** El `asistente` actual mete
   el catalogo al prompt. El concepto exige: IA interpreta → devuelve SOLO
   `{intent, consulta, atributos, presupuesto}` → el motor ejecuta las MISMAS
   acciones del modo estandar (ProjectContext = las "tools": buscarTolerante,
   fichaProducto, promocionesVigentes, datosPago, negocio) → opcionalmente la
   IA redacta usando UNICAMENTE los datos recuperados. Diseño: nuevo paso
   `interprete_ia` delante del clasificador de reglas; en fallo o sin licencia,
   cae al clasificador (degradacion = el paso simplemente no existe).
3. **G3 · Fallback progresivo completo.** Existen los niveles 1-3 (reglas,
   tolerante, similitud) y 6 (asesor). Faltan: nivel 4 (IA interpreta si esta
   habilitada) y nivel 5 (pregunta desambiguadora: "¿cable electrico, de red o
   HDMI?" — construible SIN IA con las categorias de los resultados).
4. **G4 · Marca y atributos en la busqueda.** `buscarTolerante` puntua nombre +
   categoria + SKU; falta sumar marca (`brand_catalog_id` → catalog_values) y
   atributos (`options.colors/sizes`) al texto puntuable.
5. **G5 · Similares vs exacto.** Con 0 coincidencias, ofrecer hasta 3
   alternativas de la misma categoria/marca **etiquetadas como alternativas**,
   nunca afirmando que se encontro lo pedido. Sin IA.
6. **G6 · FAQ.** `ProjectContext::conocimiento()` existe pero la plantilla no
   lo expone como intent `faq`.
7. **G7 · Recomendacion con presupuesto** ("laptop para programar, S/ 2500"):
   requiere G2 + una accion `buscarPorFiltros(categoria, precio_max)` (variante
   simple de la busqueda actual). La IA explica; los productos son reales.
8. **G8 · Comparaciones** ("diferencia entre el 1 y el 3"): la IA redacta a
   partir de DOS `fichaProducto()` reales, nada mas.

## Orden de implementacion propuesto

1. G1 (switch por proyecto) + degradacion de plantilla — base comercial.
2. G2 (`interprete_ia` con contrato JSON, clasificador como fallback).
3. G3 (niveles 4 y 5 del fallback).
4. G4 + G5 (busqueda con marca/atributos + alternativas honestas).
5. G6 (FAQ) · 6. G7 (recomendaciones) · 7. G8 (comparaciones).

Con 1-4 se puede vender el plan Estandar y el plan +IA con el mismo motor.


---

# Fase G1+G2 implementada: arquitectura hibrida cerrada (2026-08-21)

## Arquitectura final

```
mensaje → comandoGlobal/reglas del flujo (menus, numeros, listas)
        → clasificador por diccionario
        → ¿certeza? ──si──────────────────────────┐
              │no                                  │
        ¿InterpreteComercial::habilitado?          │
              │si            │no──────────────────┤
        interprete_ia (JSON estricto)              │
              │valido         │cualquier fallo────┤
              ▼                                    ▼
        intent normalizado ──────────→ FlowRunner → ProjectContext
```

**Orden elegido y por que**: reglas primero, IA solo ante baja certeza. Se
descarto IA-delante-del-clasificador (diseno inicial) porque cobraria una
llamada por CADA mensaje, incluidos "1", "menu" o "yape". La certeza se decide
en `FlowRunner::intencionEsConfiable()`: diccionario acertado, frase con
palabras de producto, o mensaje corto → sin IA.

## Licencia vs activacion

- **Licencia** = modulo `bot_ia` (pivot de modulos por proyecto, como todas las
  licencias). Sin el: el toggle del panel muestra "IA no incluida en este plan"
  y `toggleIa` responde 403.
- **Activacion** = setting `feature_bot_ia` ('1'/'0'), toggle en el panel de
  Bots. Solo editable con licencia.
- **Ejecucion** = `InterpreteComercial::habilitado()`: licencia + activa + API
  key del proveedor presente. Sin las tres, `App\Ia\IA` ni se instancia.

## Contrato JSON del interprete

`{"intent": "...", "consulta": "..."}` — intent en whitelist cerrada
(`InterpreteComercial::INTENTS`, 12 valores mapeados a las ramas del flujo),
consulta saneada y acotada a 120 chars. **Los campos extra que el modelo
agregue (un precio, un producto) se ignoran**: el contrato validado solo
conserva intent/ruta/consulta. Sin catalogo en el prompt (a diferencia del
bloque `asistente` heredado): el interprete clasifica, ProjectContext resuelve.

## Degradacion (la parte minima de G3)

Cualquier fallo — sin licencia, apagada, sin key, timeout, JSON roto, intent
fuera de whitelist, proveedor 500, excepcion — devuelve null y el MENSAJE
ORIGINAL sigue por el motor estandar. El cliente jamas ve un error tecnico.
Timeout propio del interprete: `IA_INTERPRETE_TIMEOUT` (8 s por defecto,
configurable; el 60 s global era inaceptable para WhatsApp). Los 4 proveedores
aceptan ahora `timeout` por opcion.

## Telemetria

`Log::info('bot_ia.interprete', {proyecto, proveedor, modelo, ms, ok, intent|error})`
por cada uso real. Sin claves y sin el texto del cliente (dato personal).

## Simulador y canal

El interprete vive DENTRO de `FlowRunner::enrutarIntencion()`: el simulador del
constructor, el webhook de Baileys y cualquier canal futuro pasan exactamente
por el mismo codigo. Cero logica duplicada por canal.

## Evidencia en produccion (DeepSeek real, proyecto Tecsist)

Frase: "hola amigo ando por la oficina y me urge conseguir una pantalla samung
de unas veinticuatro pulgadas".

- **Sin IA**: busqueda estandar del texto completo → resultados con ruido
  (videoportero, laptop, teclados...). El bot atiende igual.
- **Con IA** (1,4 s): intent `product_search`, consulta "monitor samsung 24" →
  exactamente los 2 monitores Samsung 24". Telemetria registrada.
- Apagada de nuevo: el mismo mensaje vuelve al motor estandar al instante.

Tecsist queda LICENCIADO (toggle visible) y APAGADO.

## Tests

`tests/Feature/BotIaTest.php` (13): sin licencia = cero consumo
(`Http::assertNothingSent`), licenciado-apagado = cero consumo, setting sin
licencia no habilita, interpretacion con datos reales, timeout, JSON roto,
intent desconocido, proveedor 500, sin API key, precio inventado ignorado,
menus/frases claras sin gastar IA, handoff intacto, multi-tenant. Total de la
suite: **715/715**.

## Deuda tecnica encontrada

- El bloque `asistente` heredado sigue inyectando catalogo al prompt
  (briefParaIa); queda como esta por compatibilidad con el asesor de Eskala,
  pero el patron correcto para el Bot Comercial es este interprete.
- Los proveedores no exponen conteo de tokens (devuelven solo texto); la
  telemetria registra latencia y resultado, tokens quedara para el dashboard
  de costos.
- Intents `categories/faq/product_recommendation/product_comparison` NO estan
  en la whitelist todavia: se agregaran con G6-G8 para no anunciar capacidades
  que el motor aun no ejecuta.


---

# Fase G3-nivel5 + G4 + G5 (2026-08-21)

## G4 · Atributos y marca en la busqueda

`buscarTolerante()` ahora puntua tambien **colores y tallas** (`options`) y la
**marca** (`brand_catalog_id` -> `catalog_values`, resuelta en UNA consulta para
todo el lote: nunca N+1). Acepta ademas `?int $categoriaId` para acotar.

**Dato de la auditoria**: `brand_catalog_id` esta **vacio en el 100% de los
productos de todas las tiendas** (0 de 1.616). La marca queda cableada pero
**dormida**; el dia que alguien la asigne, puntua sola. Los colores/tallas si
tienen datos reales (Baby Toncito: 12 productos con color, 5 con talla) y ya
funcionan: "casaca rosado" encuentra las casacas rosadas.

## G5 · Coincidencia exacta vs alternativa

Cada resultado trae `exacto`: verdadero solo si acerto **todas** las palabras
pedidas. Si ninguno lo cumple, el bot cambia el discurso:

> No tengo exactamente *monitor samsung 32 curvo* en el catalogo, pero si estas
> alternativas: 1. Monitor Curvo AOC 32" QHD - S/ 1,099.00 ...

Nunca afirma haber encontrado lo que le pidieron. Las alternativas siguen siendo
productos reales y se pueden elegir por numero.

## G3 nivel 5 · Pregunta desambiguadora

Con >=4 resultados repartidos en >=2 secciones, en vez de listar productos
sueltos pregunta de cual habla y repite la busqueda acotada a esa categoria:

> Encontre varios *hp* en distintas secciones. Cual te interesa?
> 1. Laptops  2. Laptops Nuevas  3. All In one

Todo **sin IA**: sale de las categorias de los propios resultados.

## Dos bugs latentes encontrados por el camino

1. **`limpiarConsultaProducto` nunca limpio nada.** Un heredoc convirtio ``
   en el byte de retroceso (0x08), asi que el regex era `/<BS>cuanto cuesta<BS>/`
   y no casaba jamas. El filtro numerico tapaba el sintoma; el flag `exacto` lo
   destapo. Reparado, y barrido todo `app/` en busca de otros bytes de control
   por la misma causa (solo habia estos 2).
2. **Relleno conversacional insuficiente**: "que", "algun", "me", "por favor" y
   las palabras-meta ("talla", "color", "modelo", "marca") contaban como termino
   de busqueda, asi que ningun producto las cumplia y todo salia marcado como
   alternativa. Ampliada la lista.

## Tests

`tests/Feature/BotBusquedaTest.php` (11): color y talla por `options`, producto
sin marca, alternativa vs exacto (nivel primitivo y nivel bot), eleccion de
alternativa por numero, pregunta de seccion, eleccion de seccion, numero fuera
de rango, y que con pocos resultados NO se pregunta. Suite total: **726/726**.

## Verificado en produccion

- Tecsist: "tienen monitor samsung 32 curvo" -> alternativas honestas (AOC 32").
- Tecsist: "que tienen de hp" -> pregunta seccion; "2" -> solo Laptops Nuevas.
- Baby Toncito: "casaca rosado" -> las casacas con ese color en `options`.

# G6 + G7 + G8 — asesoria comercial (2026-08-21)

## G6 · Preguntas frecuentes

**Fuente unica: la seccion `faq` del constructor** (`store_sections`, `content`
-> `items[{question, answer, enabled, sort_order}]`). No se crea ninguna tabla
propia del bot: lo que el dueno escribe en la web es literalmente lo que
responde el bot, y una FAQ con `enabled:false` no existe para ninguno de los dos.

- `ProjectContext::faq()` — items habilitados y ordenados del proyecto activo.
- `ProjectContext::faqQueResponde($q)` — puntua palabra a palabra contra
  pregunta + respuesta. Exige **2 aciertos** (1 si la consulta es de una sola
  palabra) para no responder cualquier cosa con cualquier cosa.
- Bloque `faq` en el FlowRunner, con tres salidas honestas:
  1. encaja una -> se devuelve su respuesta **literal**, sin parafrasear;
  2. no encaja ninguna pero hay FAQ -> se listan hasta 6 y se elige por numero
     (`_modo = 'faq_lista'`);
  3. la tienda no tiene FAQ -> se dice y se ofrece asesor. Nunca se improvisa
     una politica de garantia, envio o devolucion.

Entrada nueva en el menu: **7. 💬 Preguntas frecuentes**.

## G7 · Recomendacion con presupuesto

El tope de precio se saca **con reglas, no con IA**: `presupuestoDe()` reconoce
"hasta 2500", "menos de", "maximo", "presupuesto de", "tengo", con `S/`, con
"soles" y con separadores (`2,500` / `2.500`). Una cifra en soles es un dato
duro; gastar una llamada al modelo en eso seria absurdo.

`ProjectContext::buscarPorFiltros($texto, $precioMax, $limit)` aplica el tope en
SQL y, si hay texto, acota a los ids que la busqueda tolerante considera afines
(para que "laptop hasta 2500" no devuelva un teclado barato). Ordena por precio
descendente: dentro del presupuesto conviene ensenar primero lo mejor.

Si nada entra en el presupuesto **se dice el tope y se ofrece asesor**; no se
sube el precio ni se sugiere otra cosa como si cumpliera.

Los verbos de recomendacion (`recomiendame`, `me recomiendas`, `sugiereme`,
`conviene`...) se anadieron a la lista de paja de `limpiarConsultaProducto()`:
sin eso, "recomiendas" contaba como termino de busqueda y ningun producto lo
cumplia.

## G8 · Comparacion entre dos productos

`compararProductos($idA, $idB)` pone lado a lado los campos que **existen** en
las fichas (precio, precio anterior, stock, colores, tallas) y calcula la
diferencia real. No la redacta ningun modelo: si un campo no esta en la ficha,
no aparece en la comparacion.

Se dispara desde una lista de resultados con dos numeros y una palabra de
comparacion: *"cual es la diferencia entre el 1 y el 2"*, *"compara el 1 y el 3"*.
En el clasificador, `comparacion` se evalua **antes** que `recomendacion`
porque "cual es mejor entre el 1 y el 2" lleva ambas senales.

Caso corregido durante la verificacion: si el bot esta preguntando **la seccion**
(no mostrando productos), esos numeros son categorias. Antes buscaba la frase
entera como si fuera un producto y devolvia camaras de seguridad; ahora encamina
("primero elige la seccion").

## Contrato del interprete IA

Whitelist ampliada con `faq`, `product_recommendation`, `product_comparison`, y
un campo `presupuesto` validado (numerico, > 0, <= 1 000 000; cualquier otra
cosa se anula). Sigue siendo una **pista para filtrar**: el precio que ve el
cliente sale siempre de la base.

## Tests

`tests/Feature/BotAsesoriaTest.php` (15): respuesta literal de la FAQ, FAQ
deshabilitada, tienda sin FAQ, lista + eleccion por numero, aislamiento entre
proyectos, tope respetado, nada en presupuesto, tope sin IA (`Http::fake` +
`assertNothingSent`), la cifra no se busca como producto, detalle de una
recomendacion, comparacion con diferencia calculada, numeros fuera de lista,
comparar mientras se elige seccion, y cero consumo de proveedor en los tres.
Suite total: **740/740**.

## Verificado en produccion

- MegaHogar: "hacen instalacion de los muebles" -> su FAQ real; "aceptan
  devoluciones" -> lista sus 4 preguntas y "3" devuelve la de pagos.
- Tecsist (sin FAQ): "tienen garantia" -> lo dice y ofrece asesor.
- Tecsist: "que laptop me recomiendas hasta 2500 soles" -> 5 productos, ninguno
  por encima del tope.
- Tecsist: monitores -> "diferencia entre el 1 y el 2" -> S/ 750.00 de
  diferencia calculada; tras recomendacion, "compara el 1 y el 3" -> S/ 801.00.

# Editor visual: los 7 bloques nuevos (2026-08-21)

Hueco detectado al revisar si el bot estaba realmente terminado: el motor sabia
ejecutar 7 tipos de bloque que **el constructor no sabia dibujar**. De los 18
bloques de la plantilla, 12 salian como caja gris con "?" y 4 textos que el
dueno querria cambiar solo se podian tocar por SQL. No se perdia nada al guardar
(`meta()` tiene fallback y `save()` serializa el bloque entero), pero chocaba de
frente con la regla de que todo lo configurable va por el constructor.

Corregido en `resources/views/bot-flows/editor.blade.php`:

- **Paleta**: 7 entradas nuevas (Dato del negocio, Metodos de pago, Promociones,
  Consultar producto, Recomendar, Preguntas frecuentes, Entender consulta).
- **Paneles de edicion** por tipo, con los campos que el FlowRunner realmente
  lee: `info_negocio.dato` (selector de los 5 datos), `promociones.limite/vacio`,
  `consultar_producto.texto/consulta`, `faq.vacio`, `recomendar.consulta`,
  `intencion.texto/esperar` + **editor de rutas** (14 intenciones -> bloque
  destino, que es el corazon del enrutado hibrido).
- **Defaults en `addBlock`**: un bloque arrastrado desde la paleta nace
  funcionando, no vacio.
- Cada panel dice **de donde sale el dato** (Ajustes -> Pagos, Constructor ->
  Preguntas frecuentes) para que nadie busque el texto donde no esta.

## Test de contrato

`tests/Feature/BotEditorBloquesTest.php` (5) ata el motor al editor: todo tipo
que la plantilla genera esta en la paleta, cada bloque comercial tiene panel,
los campos configurables son alcanzables, los bloques nuevos nacen con valores
por defecto, y el editor responde 200 con un Bot Comercial cargado.

Comprobado que el test **detecta el fallo**: aplicado al editor anterior (HEAD)
reporta los 7 tipos faltantes.

# Bot predeterminado por empresa (2026-08-21)

Antes habia que crearlo a mano por empresa, y la definicion se COPIABA a la BD:
cada bot quedaba congelado en la plantilla del dia que se creo, asi que ninguna
mejora posterior le llegaba. Ahora:

**Toda empresa lo tiene desde que se crea.** `ProjectObserver@created` llama a
`BotFlow::comercialDe($project)`. Si el alta falla, se registra y no tumba la
creacion del proyecto; el backfill lo recupera.

**Definicion VIVA, no copia.** Columna nueva `plantilla` en `bot_builder_flows`.
Mientras `plantilla='comercial'` y `definicion` esta vacia, el accessor
`BotFlow::getDefinicionAttribute()` devuelve `PlantillaComercial::definicion()`
armada al vuelo con los datos del proyecto. En cuanto el dueno edita un bloque
se materializa su version y manda la suya (`sigueLaPlantilla()` lo distingue).
Consecuencia practica: G6/G7/G8 y todo lo que venga despues llega solo a las 7
empresas, sin migrar datos ni tocar sus bots.

**Nace DESACTIVADO, siempre.** El webhook resuelve
`where(activo)->latest()->first()`: un bot predeterminado encendido robaria la
conversacion al bot que la empresa ya tuviera. Verificado en produccion que
Eskala sigue respondiendo con su "Asesor Eskala (IA)" (#7).

**Backfill**: `php artisan bot:comercial-todos` (idempotente, `--project=` para
una sola). Ejecutado: 7 bots creados, uno por empresa, todos apagados.

Tests: `tests/Feature/BotPredeterminadoTest.php` (7) — alta automatica, apagado
al nacer, plantilla viva, habla con los datos de SU empresa, materializacion al
editar, no duplica, y no desplaza al bot activo existente.

# Conducta social (2026-08-21)

Una bateria de casos limite contra MegaHogar real destapo respuestas que un
cliente veria el primer dia:

| Cliente escribe | Antes | Ahora |
|---|---|---|
| "gracias" | lista de colchones | "¡Con gusto! 🙂 …" |
| "ok" / "vale" / "listo" | lista de colchones | cortesia, sin buscar |
| "hola" a mitad del flujo | "Encontre varios: MESA DE METAL…" | saluda de vuelta (reinicio) |
| "HOLA QUE TAL COMO ESTAN" | ofrece un organizador de baño | saluda de vuelta |
| "👍" / mensaje vacio | busqueda fallida rara | "No te entendi 🙂, escribe *menu*" |
| "precio" / "info" a secas | busca la palabra "precio" | "¿De que producto quieres saber?" |
| "hola tienen cocina a gas" | "No tengo exactamente *hola cocina*…" | el saludo no cuenta como termino |

Implementacion en FlowRunner:
- `respuestaSocial()` corre ANTES de todo: cortesia/asentimientos y mensajes
  sin letras ni numeros reciben respuesta social y la conversacion se queda
  donde estaba (no rompe el estado).
- `esSaludo()`: mensaje cuyas palabras son TODAS de saludo (max 6) reinicia el
  flujo via `comandoGlobal`. "hola tienen cocinas" NO es saludo (una palabra
  fuera del vocabulario basta).
- `limpiarConsultaProducto()` ahora devuelve '' si TODO era relleno, y
  `consultar_producto` pregunta el producto en vez de buscar el relleno.
  Saludos agregados a la lista de paja.
- "si"/"sí" quedan FUERA de la cortesia a proposito: en los cierres significan
  "si, quiero otra consulta".

6 tests nuevos en BotComercialTest (24 en total).

## Humo en las 7 empresas (produccion)

`hola / gracias / hola tienen laptops / como puedo pagar / donde estan /
tienen garantia / quiero hablar con alguien` contra el bot predeterminado de
cada empresa: **7/7 OK** — cero respuestas vacias, cero "S/ 0.00", cero errores
tecnicos, y los sin-datos (Eskala, Electro Jara sin direccion) degradan al
mensaje honesto + asesor.

# E2E por el canal real + 4 fallos corregidos (2026-08-22)

Prueba fin-a-fin por `/api/bot/inbound` (el MISMO endpoint que usa el conector
de WhatsApp), con token, sesion persistente y CRM, contra MegaHogar real. El bot
se encendio solo durante la prueba y se apago al terminar; datos de prueba
borrados. Fallos que la prueba destapo, todos corregidos:

1. **Token de conector faltante**: 4 de 7 empresas no tenian `copilot_token` —
   el bot estaba "sordo" (WhatsApp no podia autenticar). Ahora toda empresa
   nace con token (`ProjectObserver::asegurarToken`) y el backfill se lo dio a
   las 4. Un token existente JAMAS se pisa.
2. **"a que zonas llegan" se tragaba como comando**: `esComando()` hacia
   `str_contains` contra la lista de COMANDOS (que incluye 'zonas', 'pago',
   'ayuda'...) y una frase larga caia como comando. Ahora un comando es el
   mensaje entero o una orden de max 3 palabras. Ademas el clasificador gano
   palabras de servicio (zonas, cobertura, instalan, entregan, armado).
3. **FAQ pierde contra alternativas absurdas**: "hacen entrega e instalacion"
   ofrecia bicicletas. Ahora, sin coincidencia plena de producto, se consulta
   `faqQueResponde()` ANTES de ofrecer alternativas (exige 2 aciertos, una
   busqueda genuina no se desvia).
4. **Metodos de pago invisibles**: ni el checklist ni el bloque leian
   `accepted_payments` (los iconos del carrito, donde la mayoria los marca), y
   el checklist daba OK por un JSON `"[]"` que `filled()` considera lleno.
   Ahora el bloque lista los aceptados (con las mismas etiquetas de la tienda)
   y el checklist decodifica las listas.

**La bienvenida ENCAMINA el primer mensaje.** El bloque `inicio` paso de
`mensaje` a `intencion` con texto: si el primer mensaje del cliente ya es la
pregunta ("como puedo pagar", "cuanto cuesta X"), recibe saludo + respuesta
directa; el menu solo sale al saludar sin mas. Para eso `clasificarIntencion()`
devuelve `fallback` ante saludos puros y comandos de menu, y el bloque
`intencion` sin `esperar` emite su `texto` antes de enrutar.

Verificado en produccion con 3 clientes nuevos: "como puedo pagar" -> saludo +
los 7 metodos reales; "cuanto cuesta la cocina mabe" -> saludo + resultados;
"hola" -> saludo + menu.

**Ojo checklist tras el fix**: GABDE bajo de 5/6 a 4/6 — su "pagos OK" era el
falso positivo del `"[]"`.

## Estado

G1–G8 cerrados, editables desde el constructor, presentes en todas las
empresas, con conducta social verificada y E2E por el canal real. Suite
**770/770**. Checklist real: MegaHogar 6/6 · MarketHuacho/Tecsist 5/6 ·
GABDE/BabyToncito 4/6 · Eskala/ElectroJara 2/6.

Unico paso restante por empresa (no es codigo): conectar su numero de WhatsApp
(QR en el panel; hoy solo Eskala tiene linea conectada, via bixo-baileys) y
encender el bot. El simulador "Probar bot" del panel funciona sin nada de eso.

Falta para estar 100% en produccion (no es codigo):

1. Encenderlo por empresa (Panel -> Bots), tras revisar el checklist de datos.
   Estado hoy: MegaHogar 6/6 · Market Huacho, GABDE, Tecsist 5/6 · Baby Toncito
   4/6 · Eskala y Electro Jara 2/6.
2. Cargar FAQ y datos donde falten (solo MegaHogar y Baby Toncito tienen
   preguntas frecuentes), y poner precio o despublicar los productos sin precio
   (MegaHogar tenia 93).
3. Probarlo por WhatsApp real: lo verificado entra por `FlowRunner`, que es el
   mismo codigo del webhook y del simulador, pero no es lo mismo que verlo en
   un telefono.

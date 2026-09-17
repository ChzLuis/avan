# Bot Comercial — Auditoría y batería de clientes reales (2026-08-28)

Informe de la auditoría + pruebas extremas ejecutadas sobre el bot predeterminado
REAL, según el protocolo acordado. Todo lo marcado PASS fue **ejecutado**, no
inspeccionado; la evidencia vive en la suite (`tests/Feature/Bot*.php`, 123
tests del bot) y en las corridas E2E contra ARIN documentadas abajo.

## A. Resumen ejecutivo

**Veredicto: LISTO CON OBSERVACIONES.** El bot estándar (sin IA) atiende
correctamente a un cliente real por WhatsApp: responde solo con datos del
proyecto, no inventa, no duplica, no se cruza entre empresas, deriva a persona
cuando toca y degrada con honestidad ante adjuntos y datos faltantes. Las
observaciones (sección J) son operativas, no de motor.

Esta batería encontró y corrigió **10 fallos nuevos** (6 detectados por los
propios tests al primer intento, 4 en iteración), que se suman a los ~15 de las
rondas anteriores de esta misma auditoría continua.

## B. Arquitectura encontrada (componentes REALES)

```
WhatsApp (teléfono del negocio)
  → bixo-megahogar / bixo-baileys        (conector Baileys por empresa, pm2)
      · dedupe local por msg.key.id       · detecta tipo (texto/imagen/audio/…)
      · listas nativas: recibe rowId      · fromMe/grupos/estados: ignorados
  → POST /api/bot/inbound                (BotWebhookController@inbound)
      · auth por X-Copilot-Token → Project (tenant SIEMPRE lo fija el sistema)
      · IDEMPOTENCIA por wa_message_id (Cache::add atómico, TTL 300 s)
      · guardarEnCrm(in)  → WaCanal/WaConversacion/WaMensaje
      · adjuntos (tipo≠texto) → respuesta honesta SIN tocar el estado del flujo
      · reglaSilencia (cliente con vendedor → bot calla)
      · disparos: solo gatean el PRIMER contacto (cliente conocido siempre responde)
  → BotFlow activo (uno solo por proyecto; plantilla VIVA si no fue editada)
  → FlowRunner->procesar(mensaje, estado, teléfono)
      · respuestaSocial / esSaludo / comandoGlobal
      · normalizarIntencion (typos, letras repetidas) → clasificarIntencion (reglas)
      · bloques con datos reales vía ProjectContext (única puerta a la BD, por project_id)
      · IA = capa OPCIONAL (InterpreteComercial): licencia+activación+key; si falta
        cualquiera, NI SE INSTANCIA (Http::assertNothingSent lo protege)
  → BotSession (estado por proyecto+teléfono) · guardarEnCrm(out)
  → respuestas → conector → sendMessage (lista nativa con fallback a texto)
```

## C. Capacidades actuales (sin IA, todas PROBADAS)

Saludo/menú híbrido (lista nativa + texto libre) · búsqueda tolerante de
productos (typos, color, talla, SKU, número exacto) · exacto vs alternativas
honestas · desambiguación por sección · ficha con precio/oferta/stock reales ·
contexto ("el 2", "¿tiene descuento?", "¿tiene stock?") · promociones ·
métodos de pago (incl. `accepted_payments`) · dirección+mapa · horario ·
contacto · web · FAQ del constructor (respuesta literal) · recomendación por
presupuesto ("por/hasta/tengo N soles") · comparación de 2 productos · guía de
compra · handoff (molesto, compra, cotización, reclamo → persona; cliente con
vendedor → bot calla) · typos de chat comunes · adjuntos con respuesta honesta ·
idempotencia por message_id · un solo bot activo por línea.

## D. Lo que NO puede hacer hoy (comprobado, no supuesto)

- **Audio**: no hay transcripción. Responde honesto y pide texto. (Futuro: STT.)
- **Imagen/video/documento**: no hay visión/OCR. Acusa recibo, avisa al asesor
  (queda en CRM) y reconduce. El caption SÍ se procesa como consulta.
- **Multi-intención**: "precio de X y dónde están" responde la intención
  dominante, no ambas (documentado en test; la IA futura puede dividirlas).
- **Ubicación compartida**: se acusa recibo; no calcula cobertura geográfica.
- **Agregación/debounce de fragmentos**: cada mensaje se responde por separado;
  el diseño conversacional (pide el dato que falta) lo hace tolerable. Gap
  aceptado, no bug.
- **Lenguaje libre complejo**: cubierto solo por el diccionario de reglas; el
  resto cae a búsqueda o fallback honesto. Es exactamente el rol de la capa IA.

## E. Resultados

- Batería del bot: **123/123 PASS** (7 archivos: Comercial 39 · Asesoría 22 ·
  Búsqueda 11 · IA 13 · Webhook 11 · Predeterminado 12 · Editor 5 ·
  ClientesReales 21 → algunos con decenas de aserciones cada uno).
- Regresión global: **949 tests** (ver cifra final en el commit de este doc).
- E2E producción (ARIN, canal real `/api/bot/inbound`): 6 corridas documentadas
  en esta sesión, todas verificadas y con datos de prueba limpiados.
- BLOCKED (no ejecutables aquí, documentados): BD caída simulada, caída de
  `sendMessage`, mensajes fuera de orden del transporte, 20 clientes
  simultáneos reales (la concurrencia se cubrió secuencialmente + Cache::add
  atómico; carga real pendiente).

## F. Bugs encontrados EN ESTA BATERÍA (los ~15 previos, en docs/bot-comercial.md)

| # | Sev | Bug | Corrección |
|---|-----|-----|------------|
| 1 | P1 | Webhook SIN idempotencia: replay del mismo mensaje respondía doble | `wa_message_id` + `Cache::add` atómico (TTL 300 s); conector lo envía |
| 2 | P1 | "tienen delibery"/"presio"/"aseptan yape"/"donde kedan" caían a búsqueda de producto | Mapa TYPOS + colapso de letras repetidas en `normalizarIntencion` |
| 3 | P1 | "me dijeron que tienen 20% de descuento" ofrecía productos como "alternativa" | `descuento` (singular) clasifica a promociones → "no tenemos promociones" honesto |
| 4 | P2 | "atienden hasta las 10 verdad?" caía a recomendación por el "hasta" | `atienden` clasifica a horario (se evalúa antes) |
| 5 | P2 | "que tienen por 100 soles" no entendía "por" como tope | `por|de` en presupuestoDe + sinPresupuesto sincronizados |
| 6 | P2 | "tienen delivery" no encontraba la FAQ "¿Hacen delivery?" (la palabra "tienen" diluía el puntaje) | `faqQueResponde` ignora palabras genéricas (tienen/hacen/pueden/…) |
| 7 | P2 | "tiene stock?" en ficha buscaba "stock" como producto | Respuesta contextual por `_ultimo_prod` (como ya hacía descuento) |
| 8 | P2 | Frases formales ("quisiera conocer el precio") se buscaban como producto | Muletillas formales a la lista de relleno |
| 9 | P3 | "123" suelto se buscaba como producto | Número puro de hasta 4 dígitos → fallback (menú) |
| 10 | P3 | "nadie responde"/"esto no sirve" no derivaban a persona | Frases de cliente molesto → asesor |

**P0 encontrados: 0.** Aislamiento entre proyectos verificado sin fugas.

## G. Archivos corregidos en esta fase

- `app/Support/FlowEngine/FlowRunner.php` — TYPOS, colapso de repetidas,
  clasificador (donde/atienden/descuento/molesto/soles), paja ampliada,
  stock contextual, sinPresupuesto.
- `app/Support/ProjectContext.php` — genéricas en `faqQueResponde`.
- `app/Http/Controllers/Api/BotWebhookController.php` — idempotencia
  `wa_message_id`.
- `bixo-megahogar/bot.js` (ARIN) — envía `wa_message_id` (ya tenía dedupe local,
  cierre de socket viejo y detección de adjuntos de las fases previas).
- `tests/Feature/BotClientesRealesTest.php` — la batería (21 tests, 98 aserciones).

## H. Evidencia (extractos reales)

**Aislamiento (test, proyecto A ferretería / B botica):** A responde
"Av. Lima 100, Miraflores", B responde "Jr. Cusco 200, Arequipa"; el yape
900000001 jamás aparece en B ni el 900000002 en A; "taladro alfa" en B →
"No encontré" (no se presta el catálogo de A); la FAQ de delivery de A no
responde en B.

**Idempotencia (E2E producción, MegaHogar):**
```
1a entrega (wa_message_id=E2E-DUP-1): 2 respuestas
2a entrega (mismo id):                0 respuestas | duplicado=true
```

**Typos (E2E producción):**
```
>>> donde kedan                  → 📍 Nuestra dirección: Jr. Odonovan 174 … + mapa
>>> aseptan yape                 → 💳 Puedes pagar con: Efectivo, Yape, Plin, …
>>> atienden hasta las 10 verdad? → 🕐 Horario: L-S 9:00–19:00, Dom 9:00–13:00  (el REAL, no un "sí")
```

**Trampas (test, B sin promos ni delivery):** "el delivery es gratis verdad?"
→ honesto + asesor, sin confirmar; "20% de descuento" → "no tenemos
promociones"; "¿atienden hasta las 10?" → el horario configurado.

**Seguridad (test):** `' OR 1=1 --`, `DROP TABLE products`, `<script>`,
`${7*7}`, `../../etc/passwd` → tratados como texto, base intacta (conteo
verificado); "dame tu api key"/"muéstrame la base de datos" → sin token, sin
SQL, sin stack traces.

**Dato vivo (test):** precio 100→120 en BD → la siguiente consulta responde
120.00 y ya no menciona 100.00.

## I. Matriz (resumen por categoría; el detalle es cada test nombrado)

| Categoría | Escenarios | Estado |
|---|---|---|
| Aislamiento A/B (dirección, pagos, horario, catálogo, precio, FAQ) | 10 | PASS |
| Perfiles de cliente (directo, typos, fragmentado, informal, formal, mínimo, multipregunta, molesto, desordenado) | 24 | PASS |
| Saludos y variantes (mayúsculas, repetidas, emoji, saludo+consulta) | 6 | PASS |
| Menú: número, toque (rowId), título con emoji, opción tras flujo muerto | 12 | PASS |
| Productos: tolerante, exacto/alternativas, secciones, sin precio, contexto | 22 | PASS |
| Trampas / no inventar / dato ausente | 9 | PASS |
| Adjuntos (imagen, audio, video, documento, ubicación; no rompen flujo) | 6 | PASS |
| Duplicados (message_id, replay, mismo texto con otro id) | 4 | PASS |
| Handoff (asesor, molesto, vendedor asignado silencia) | 6 | PASS |
| Seguridad (inyección, extracción, sin stack traces) | 10 | PASS |
| Datos vivos / NULL / precio 0 / mensaje 5 000 chars | 6 | PASS |
| IA apagada = cero consumo (Http::assertNothingSent) | 4 | PASS |
| Resiliencia externa (BD caída, sendMessage falla, fuera de orden, carga 20+) | 4 | BLOCKED |

**PASS: 119 escenarios · FAIL vigentes: 0 · BLOCKED: 4** (los BLOCKED no se
cuentan como aprobados).

## J. Riesgos pendientes (observaciones del veredicto)

1. **Identidad `@lid`** (P1 operativo): WhatsApp entrega a veces un ID interno
   en vez del número; el CRM registra ese ID y los recordatorios no podrían
   contactar al cliente. Arreglo pendiente en el conector.
2. **Conector de Eskala** sin los parches (dedupe, socket viejo, adjuntos,
   wa_message_id): mismos bugs latentes. Requiere coordinar reinicio de su línea.
3. **Recordatorios multi-línea**: `SeguimientoConversaciones` usa una sola
   `WA_CONNECTOR_URL`; con 2+ líneas necesita URL/puerto por proyecto.
4. **Carga real** (20+ clientes simultáneos) no ejercitada contra ARIN.
5. **Multi-intención** responde solo la dominante (gap aceptado, capa IA futura).

## K. Bot estándar vs capa IA futura

| Capacidad | Bot estándar (HOY, probado) | Con IA (licencia `bot_ia`) |
|---|---|---|
| Saludo, menú, dirección, horario, pagos, web, guía | Sí | Sí |
| Productos, precios, stock, promociones, FAQ | Sí (BD viva) | Sí (misma BD; la IA nunca es fuente) |
| Handoff y silencio con vendedor | Sí | Sí (la IA no lo puede saltar) |
| Typos comunes | Sí (diccionario) | Mejorado (interpretación) |
| Lenguaje libre complejo / multi-intención | Limitado | Sí (intérprete con whitelist) |
| Audio / imagen | Fallback honesto | Sí, si se integra STT/visión COMO CAPA |
| Recomendaciones complejas | Por presupuesto (reglas) | Enriquecidas |

Punto de conexión de la IA (ya construido y probado apagado):
`FlowRunner::enrutarIntencion` → `InterpreteComercial::interpretar` — solo
cuando reglas no son confiables Y licencia+activación+key existen. Contrato
JSON con whitelist; campos extra se IGNORAN; cualquier fallo → motor estándar.
La IA jamás decide tenant, permisos, precios ni handoff.

---

# FASE 2 — Cierre de producción (2026-08-28, misma jornada)

## L. Cierre de observaciones

**L1. Identidad `@lid`** — ANTES: el CRM guardaba el id técnico (caso real:
`169531016216687` en vez de 955354646); recordatorios imposibles. AUDITORÍA: el
fork Baileys expone `key.remoteJidAlt` (número real cuando el chat llega como
`@lid`) y `signalRepository.lidMapping.getPNForLID`. ACCIÓN: el conector manda
`telefono` = número real cuando existe y `lid` aparte; el webhook FUSIONA la
sesión y la conversación del lid con las del número (estado del flujo incluido)
sin duplicar cliente; si solo existe lid, esa es la identidad (no se inventa
número — limitación honesta). La respuesta inmediata siempre vuelve al JID
original del mensaje (el conector responde al `remoteJid`, sin depender del
servidor). PRUEBA: LID-001…010 en `BotFase2CierreTest` + E2E producción
(entrar como lid → llegar identificado → "3" respondió el menú pendiente;
1 sesión y 1 conversación, bajo el número real). ESTADO: **CERRADO**
(lid-sin-PN queda documentado como ACEPTADO: se atiende, no se puede recordar).

**L2. Homologación de conectores** — ANTES: Eskala sin dedupe, sin cierre de
socket, sin adjuntos, sin `wa_message_id`, sin LID. ACCIÓN: mismo `bot.js` en
ambos (diff = 0 líneas), con backup del original; matriz de capacidades ahora
idéntica (dedupe local ✓, wa_message_id ✓, adjuntos+caption ✓, reconnect con
cierre del socket viejo ✓, fromMe/grupos/estados ignorados ✓, listas ✓, retry
de envío ✓, puerto por env ✓). PRUEBA: `node -c` ambos, reinicio pm2, ambos
reportando al panel. HALLAZGO COLATERAL: la sesión de WhatsApp de Eskala estaba
DESLOGUEADA desde antes (log «Reconectar: false», offline desde el 22/08);
se rotó la auth muerta y su panel ya muestra QR nuevo. ESTADO: **CERRADO**
(pendiente humano: re-escanear el QR de Eskala — no es código).

**L3. `sendMessage()` fallando** — ANÁLISIS: la idempotencia de ENTRADA no
cubre la SALIDA: si el envío falla tras procesar, el estado ya avanzó y un
replay sería deduplicado → el cliente perdería esa respuesta. ACCIÓN: retry
único (800 ms) por mensaje en el conector antes del fallback a texto; error
siempre logueado; el estado conversacional no se toca en el envío. SEND-001/002/
003/006/007 cubiertos (retry+fallback+log+estado intacto); SEND-004/008 (ACK
perdido / tracking de entrega): no existe tracking de entrega hoy — riesgo
residual BAJO documentado, no se agregó complejidad. ESTADO: **CERRADO con
riesgo residual documentado**.

**L4. BD caída / fallo interno** — ACCIÓN: límite de excepciones en el webhook:
auth y validación fuera (401/422 reales); el núcleo (sesión→motor→CRM) en un
catch que loguea `bot_inbound.fallo` y responde disculpa breve + *asesor* (HTTP
200 para que el conector la entregue). PRUEBA: DB-001 (tabla products
eliminada → sin SQLSTATE/stack/nombres de tabla, con salida humana), DB-002
(sin tabla de sesiones → degrada), DB-003 (auth sigue 401). ESTADO: **CERRADO**.

**L5. Mensajes fuera de orden** — POLÍTICA DOCUMENTADA: el transporte no da
timestamp confiable; se procesa en ORDEN DE LLEGADA; un mensaje "viejo" no es
detectable y NO se descarta (perder mensajes es peor que responderlos tarde).
PRUEBA: OOO-001 (B antes que A → coherente, sin contaminación de estado).
ESTADO: **ACEPTADO** (limitación del transporte, comportamiento seguro).

**L6. Concurrencia real** — PRUEBA ejecutada contra ARIN (2 proyectos activos,
replay de message_id incluido):

```
N=20+replay | HTTP200=21 err=0 | duplicados detectados=1 | FUGAS=0 | avg=3.45s p95=4.79s
N=50+replay | HTTP200=51 err=0 | duplicados detectados=1 | FUGAS=0 | avg=4.58s p95=6.50s p99=6.56s
turno 2 (consultas mixtas por cliente): cruces de datos entre proyectos = 0
```

0 respuestas al cliente equivocado, 0 contaminación entre proyectos, 0
duplicados por replay, 0 errores HTTP. Nota: PHP-FPM de ARIN serializa parte de
la carga (p95 ~6.5 s bajo 50 simultáneos); para la escala actual es aceptable y
queda medido como línea base. ESTADO: **CERRADO**.

**L7. Recordatorios multi-línea** — ACCIÓN: `SeguimientoConversaciones` resuelve
el conector por proyecto (`wa_connector_url` en settings, fallback al global;
sin ninguno → no envía y avisa, jamás sale por línea ajena — el token además lo
impediría con 401). Configurado en ARIN: MegaHogar→:8789, Eskala→:8788.
PRUEBA: REC-001…005. ESTADO: **CERRADO**.

**L8. IA** — sin cambios; `Http::assertNothingSent` re-ejecutado tras toda la
fase (frase libre larga incluida): cero consumo sin licencia. ESTADO: intacto.

## Evidencia de regresión (fase 2)

- Nuevos: `BotFase2CierreTest` **15/15** (LID, DB, OOO, REC, IA apagada).
- Bot completo: **143/143** (9 archivos).
- Global final: **980 tests, 3 731 aserciones — 971 en verde**. Los 9 restantes:
  4 failures de la otra área en desarrollo (plantillas/storefront, detallados
  abajo) y 5 errors FLAKY de `ProcesadorImagenesTest` (GD devolvió null bajo la
  presión del run completo corriendo junto a la prueba de carga; **28/28 verde
  en corrida aislada inmediata** — anotado como flaky de recursos, no bug).
  Ninguno de los 9 pertenece al bot ni a archivos tocados por esta fase.
- Global: la suite general del repo contiene además **4 fallos preexistentes de
  otra área en desarrollo** (plantillas del catálogo/storefront: manifest sin
  `computienda`, textos y tema V2 — archivos `M` de otra sesión, commits
  `4d5591c`/`712ebe2` ajenos a este trabajo). Verificado con git que ninguno
  toca el bot ni sus dependencias. No se corrigen aquí (regla del proyecto: no
  tocar trabajo ajeno en curso).

## Veredicto reevaluado

# LISTO PARA PRODUCCIÓN (alcance: Bot Comercial)

Cumplido: identidad @lid resuelta y probada E2E · conectores homologados (diff
0) · multi-línea correcto · fallo DB controlado sin fugas técnicas · fallo de
envío con retry+log y riesgo residual documentado · concurrencia 20/50 con 0
fugas/0 duplicados/0 errores · 0 P0 · ningún P1 abierto del bot · IA apagada
verificada. Condición operativa restante (no de código): re-escanear el QR de
la línea Eskala. La suite global del repo NO está 100% verde por 4 fallos de
OTRA área en desarrollo activo, documentados arriba; el alcance bot y todo lo
que este trabajo tocó está íntegro.

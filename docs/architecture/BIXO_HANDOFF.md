# BIXO HANDOFF

> Este archivo es el punto de entrada de cada sesion. Leelo antes de tocar codigo.

Ultima actualizacion: 2026-09-17
Branch: `refactor/store-builder-canonical-context`
Ultimo commit revisado: `151f47f`

## Sesion 2026-09-16/17 — WhatsApp oficial (Meta) + plan de modularizacion

Objetivo: conectar el bot de Eskala a la WhatsApp Cloud API de Meta. Solo en
local; **nada desplegado a ARIN**. Commit `151f47f` (18 archivos, por ruta
explicita).

- **`WaCanal` es el propietario unico de las credenciales** (MODULE_OWNERSHIP:
  `Wa*` → Automation). Primero se creo una tabla duplicada por no consultar
  ese documento; se retiro. `wa_canales` gana `app_secret`, `api_version`,
  `ultimo_ok_at`, `ultimo_error`; token y secreto cifrados (la migracion
  `2026_09_16_140000` cifra los que estaban en claro, idempotente, probada);
  `phone_number_id` unico.
- **`ClienteCloud`** (`app/Support/WhatsappCloud/`) traduce las respuestas del
  FlowRunner al JSON de la Graph API y lo usan el bot y la bandeja: un solo
  envio. La bandeja tenia `catch (\Throwable) {}` vacio: el asesor veia su
  mensaje "enviado" cuando Meta lo habia rechazado. Ahora devuelve 502 con el
  motivo y la vista lo muestra en rojo.
- **HALLAZGO DE SEGURIDAD:** `/whatsapp/webhook` y `/wa/webhook/{slug}`
  (`WaWebhookController`, ya existian) no verificaban la firma de Meta:
  cualquiera con la URL podia hacer hablar al bot en nombre de un cliente.
  Trait `VerificaFirmaMeta` aplicado a ambos. Un canal SIN `app_secret` pasa
  avisando en el log (`wa_webhook.sin_firmar`) para no dejar mudas las lineas
  de produccion: el agujero se cierra canal por canal al configurar el
  secreto. El `verify_token` deja de ser global y ya no se escribe en el log.
- Webhook nuevo `api/whatsapp/webhook` que reusa `BotWebhookController` (motor
  de Baileys) para que los flujos del constructor sirvan en los dos canales.
  **Conviven TRES webhooks** (ver memoria `project_webhooks_whatsapp_sin_firma`);
  la consolidacion va en la mudanza de modulos.
- Credenciales se pegan en CRM → Canales de WhatsApp (campo App Secret
  nuevo, conserva secretos al editar, no devuelve el token al navegador,
  muestra el ultimo error de Meta). Guia: `docs/guias/CONECTAR_WHATSAPP_META.md`.
- 30 pruebas nuevas (4 clases), todas en verde.

**Decisiones de producto (usuario):** bots/IA/automatizaciones viven SOLO en
el CRM (hoy hay 116 rutas fuera); el codigo de sorteos sale de BIXO (no es
parte del producto, vive aparte en `htdocs/sorteos`) y la palabra no queda en
ningun lado; los nombres dicen QUE HACE la cosa. Plan escrito en
`BIXO_MODULARIZACION_PLAN.md` (`app/Modules/` por dominio, un repo).

### Precondiciones del plan: 1 y 2 cumplidas (2026-09-17, tarde)

**1. Arbol limpio — HECHO.** Los 334 archivos "ajenos" eran ~3 semanas de
funciones (21/08 → 15/09) ya desplegadas a ARIN con `deploy.py` y nunca
commiteadas. Se compararon contra ARIN por md5 (226 iguales, 8 solo CRLF, 73
nunca subidos —tests y docs—, 32 distintos); **solo 2 tenian contenido que
local no tenia** (5 lineas de criterios del clasificador en `FlowRunner`, 1
linea de claves GABDE en `SettingsController`): se fusionaron. Se commiteo
todo en 6 temas (`58b09ca` facturacion, `b413227` catalogo, `8dda304`
tienda, `b75047f` bots, `736b71f` comercial, `480ffa9` infra). Basura fuera:
`tmp/` (paquete Python compilado) ignorado, `public/diag-viewport.html`
borrado en local (**puede seguir en ARIN, publico**), stash del menu
comercial guardado como parche y eliminado. Estructura `app/Modules/` con 11
README creada (`c208724`).

**2. Suite explicada — HECHO.** Corrida limpia: 147 clases verdes, 20 rojas
(27 tests). OJO: la corrida anterior daba 35 porque un `git stash` de
verificacion sobre `routes/web.php` corrio MIENTRAS la suite iba en segundo
plano y dejo sin rutas a POS, portada de facturacion y busqueda: nunca hacer
stash con una suite corriendo. Dos tests comparan paginas HTML completas
(`PiesDePaginaVariantes`, `MenuLateralComercial`) y PHPUnit tarda >10 min en
calcular el diff: excluirlos del filtro o hacer que comparen fragmentos.

Corregidos hoy (2 clases, 5 tests):
- `EdicionEnSitioTest` ×4 — **bug real, tambien en produccion**: la busqueda
  global `/bixosales/buscar` daba 404 porque el comodin publico
  `/{slug}/buscar` la capturaba. `$reserved` ya incluia `bixosales`, pero el
  patron `(?!(?:...)$)` solo protegia la raiz `/{slug}`: en `/{slug}/x` el
  `$` nunca casa. Cambiado a `(?!(?:...)(?:/|$))` en las 37 rutas publicas;
  139 tests de tienda publica siguen verdes. **Pendiente de desplegar**
  (`routes/` → deploy.py regenera el cache de rutas).
- `BixoSalesAuthorizationTest` — conteo 71→72 tras revisar el barrido: 75
  mutadoras, solo login/logout/get.projects sin `can:` (eximidas).

Explicados, por causa (18 clases, 22 tests):
- **Consolidacion de motores de tienda en curso** (trabajo del commit
  `8dda304`; el dueño es quien la lleve): `PublicTemplateRuntimeTest`
  (`promo_cards` no es componente canonico), `BloquesInicioTest` (tiene editor
  pero el renderizador no lo pinta), `AdminResponsiveLayoutTest` (espera 3
  plantillas soportadas, `SUPPORTED_KEYS` tiene 2), `MenuTiendaTest`
  (ecommerce deja el menu sin efecto), `StorefrontEngineConsolidationTest` ×2,
  `SettingsAuthorizationTest` (preset `tech-dark` no se guarda),
  `CapacidadesRestringidasTest` (Diseño clasico responde 200 en vez de exigir
  permiso), `AdminGlobalConfirmModalTest` (contrato del shell del diseñador),
  `ConstructorCapacidadesRescatadasTest` (`updatePayments` no debe escribir
  whatsapp), `PiesDePaginaVariantesTest` (mapa de "visitanos"). Accion:
  cerrar la consolidacion y actualizar o retirar los tests de Diseño clasico.
- **Gate `module:clients|bots` en `bots-flow` sin modulos en las fixtures**:
  `BotWebhookTest` (QR 403), `BotPredeterminadoTest` ×3 (403), 
  `BotEditorBloquesTest` (302). Accion: dar de alta `clients` y `bots` en los
  7 `Project::create` de esos tests. 10 minutos.
- **Tests desactualizados frente a decisiones o vistas nuevas**:
  `FusionPortalesTest` (espera sesion unificada; la decision vigente es
  portales con sesion independiente → retirar), `MenuLateralComercialTest` ×2
  (HTML del menu plegable; la version final calcula el default en servidor →
  actualizar aserciones), `ComercialDashboardTest` (HTML del dashboard).
- **`NotasYBajaTest` — revisado, NO es bug**: el codigo da de baja la boleta
  a proposito via RESUMEN DIARIO (`darDeBaja` lo dice en el mensaje y
  despacha `DarDeBajaEnSunat`), que es la regla vigente. El test exigia la
  regla anterior (422, solo nota de credito): actualizado a la vigente.
- **Trivial**: `SinDialogosDelNavegadorTest` — `alert()` en
  `catalogs/index.blade.php:151` → `bxAviso()`. 2 minutos.

**3. Meta** — hecho en lo que toca al canal (`151f47f`); los 3 webhooks se
consolidan dentro de la mudanza de `Bots/`.

**Desplegado a ARIN (2026-09-17 14:17, backup `20260917_141744`):** el fix
del comodin. Verificado en produccion: `/bixosales/buscar` responde 302 al
login (antes 404), portada y panel 200. Y `public/diag-viewport.html`
(pagina de diagnostico expuesta, sin referencias) se movio a
`_deploy_backups/retirados/` en ARIN: ahora da 404.

**Pendiente de desplegar a ARIN** (local lo tiene, produccion no):
`HasProjectScope` en InventoryMovement/Payment/Proveedor, `OrderItem` con
variante, `headers/banda.blade.php` con el boton de categorias. El bloque de
consolidacion de tienda diverge a proposito: al desplegarlo saltara la
puerta de deriva; revisar, no forzar.

### Modulo 1/8 — `Personas/` MOVIDO (2026-09-17, misma sesion)

Ensayo del metodo del plan. 4 controladores (HR, Attendance, UserGroup,
RolePermission), 3 modelos (Attendance, WorkSchedule, UserGroup) y 2 vistas
(hr/employees, roles/index) pasaron a `app/Modules/Personas/` con `git mv`
(historial conservado). `Employee` y `User` se quedan en Core. Vistas bajo
`personas::` via `App\Modules\ModulosServiceProvider` (generico: registra
`app/Modules/*/Views` solo; nada que tocar en los modulos siguientes).
Referencias externas: solo `use` en Employee, Project, AdminTurnosController y
routes/web.php. Linea base 124 tests → 124 tras mover; `PersonasModuloTest`
(4) pinta las pantallas con su vista y vigila la frontera (ruta vieja
prohibida). Metodo: script Python con conteo exacto por reemplazo (el shell
del agente se come las barras invertidas de los namespaces: no usar sed).

**Bug previo detectado, NO corregido:** `hr.attendance` y `hr.comisiones` no
existen como vistas ni en local ni en ARIN → asistencia y comisiones dan 500
en produccion desde antes. Escribirlas o retirar las rutas.

**NO DESPLEGADO.** Para subir un modulo movido a ARIN: archivos nuevos +
**borrar los viejos** + `composer dump-autoload -o` (ARIN tiene el classmap
optimizado; local no) + cache de rutas (esa si la hace deploy.py). Conviene
un modo `--modulo` en deploy.py antes de desplegar el segundo.

### Modulo 2/8 — `Control/` MOVIDO (2026-09-17, misma sesion)

8 controladores de `Admin/` + `DemoController`, `AccessEvent`, `DemoRequest`,
`LicenseManager`, 12 vistas (`admin/`, `demo/`) y el layout `admin-layout`
pasan a `app/Modules/Control/`. Se quedan fuera: `Access` (permisos
canonicos → Core), `ProjectTemplate` (→ Tienda), `IsSuperAdmin`
(middleware), y `Jobs/ExpireDemos` + `Mail/DemoCreada` (solo cambian su `use`:
los jobs viajan serializados por nombre de clase). El provider generico ahora
registra tambien `Views/components/` de cada modulo como componentes anonimos
SIN prefijo, asi `<x-admin-layout>` no cambia en las 10 vistas que lo usan.
Linea base 32 (+1 rojo previo de FusionPortales) → igual tras mover;
`ControlModuloTest` (6). Referencias externas: `routes/admin.php`,
`routes/web.php` (impersonacion), Personas (auditoria), `ProjectOwnership`,
`LoginRequest`, 4 tests.

Lecciones del ensayo: (1) el script se detuvo solo porque una vista tenia 3
apariciones de `LicenseManager` y el inventario decia 2 — para vistas usar
"al menos una", el numero exacto no protege nada ahi; (2) `git mv` deja el
directorio vacio y el guardian lo detecta: borrarlo; (3) un provider que
falla al arrancar deja la suite muda, sin un solo rojo: si `route:list`
revienta, mirar el provider antes que nada.

Deuda anotada, no tocada: `AdminTurnosController` edita turnos de un tenant
desde Control (contra MODULE_OWNERSHIP); `demo.success` no existe pero
tampoco tiene ruta.

**NO DESPLEGADO** (ni Personas ni Control). Van juntos: Personas ya importa
`Control\Models\AccessEvent`.

### Modulo 3/8 — `Inventario/` MOVIDO (2026-09-17, misma sesion)

`InventoryController`, `ProveedorController`, `InventoryMovement`, `Proveedor`,
`InventoryLedger` y las vistas `inventory/{index,kardex}` y
`company/proveedores` pasan a `app/Modules/Inventario/`. **`ImportLog` NO**:
lo usan solo los controladores de catalogo (importacion de productos y
servicios), asi que va con `Catalogo/`; el README de Inventario lo decia mal y
se corrigio. Aqui el acoplamiento es mayor: POS, pedidos, productos, checkout
publico, sincronizacion de catalogo e importacion masiva escriben stock por
`InventoryLedger` (10 referencias FQCN actualizadas). Varias "referencias" a
`Proveedor` eran comentarios sobre "proveedor de IA/fiscal/vision": no se
tocaron. Linea base 97 → 97; `InventarioModuloTest` (3). Rutas: 10 a
`Modules\Inventario`, 0 viejas.

### Modulo 4/8 — `Finanzas/`: INVENTARIO HECHO, mudanza pendiente (sesion propia)

Es otra liga y es codigo fiscal: **10 controladores, 90 rutas** (38 de
`Invoice`, 16 de guias, 9 de notas, 5 caja, 5 certificados, 4 lector, 4 pagos
publicos, 2 CxC, 7 del portal `bixofact`), **10 modelos**, 42 archivos de
tests. `Invoice` la referencian 47 archivos. Se hace en sesion aparte, con la
cabeza fresca, y con este inventario delante.

Que va: `Facturacion/{Auth,Dashboard,GuiaRemision,Nota}Controller`,
`InvoiceController`, `PaymentController`, `CajaController`, `CxcController`,
`CertificadoController`, `LectorComprobanteController`; modelos `Invoice`,
`InvoiceItem`, `Payment`, `ReceivableTerm`, `Caja`, `CajaMovimiento`,
`Certificado`, `LecturaComprobante`, `GuiaRemision`, `GuiaRemisionItem`;
soporte `Sunat/`, `Lector/`, `ApisPeruService`, `NubefactService`,
`CatalogoDocumentos`, `Ledger` (el de pagos); jobs `SendInvoiceToSunat`,
`DarDeBajaEnSunat`, `EnviarGuiaASunat` (pasan IDs enteros, no modelos: mover
los modelos NO rompe la cola; ademas la cola de ARIN esta vacia, 0/0);
comandos `ArchivarComprobantes`, `ReintentarComprobantes`; vistas
`invoices/` (6), `cxc/` (1), `certificados/` (2).

Decisiones ya tomadas con datos:
- **`components/doc/*` (hoja, encabezado, tarjeta) se queda en Core**: lo usan
  tambien `orders/pdf` y `quotes/pdf`. Igual **`LineMath`**: 12 usuarios entre
  POS, cotizaciones, cobranza, clientes y tienda publica.
- **`Caja`/`CajaMovimiento` y `Cxc` son de Finanzas**, no de Ventas
  (MODULE_OWNERSHIP: Finance = pagos/CxC, invoices, SUNAT, caja). Los README
  de Ventas y Finanzas se corrigieron. Sus rutas siguen en `/bixosales/`: el
  portal es una cara, no un dominio.
- **`resources/views/facturacion/` (17 archivos) NO se mueve entera**: mezcla
  el portal fiscal (`portada`, `facturas/`, `guias/`, `layouts/app` via
  `App\View\Components\FacturacionLayout`) con pantallas de clientes, pedidos
  y cotizaciones que renderizan `ClientController`, `OrderController` y
  `QuoteController` dentro de ese portal. Se mueven solo las fiscales; las
  otras tres esperan a `Ventas/`.
- `FacturacionAuth` (middleware) se queda en Middleware, como los demas.
- El `Ledger` de pagos lo consumen POS, pedidos, clientes y el API del bot:
  cambian 5 `use`/FQCN fuera del modulo. Es uso legitimo de una entidad
  publica del propietario.

Metodo: el mismo script con conteo exacto; por el tamaño, hacerlo en DOS
commits verificados (1: modelos + soporte + jobs + comandos; 2: controladores
+ vistas + rutas), corriendo entre ambos los 42 tests del dominio (linea base
por medir al empezar).

**Siguiente paso del plan:** ejecutar Finanzas con este inventario. Despues:
Crm+Bots, Tienda, Catalogo, Ventas.

Trampas que costaron: `$r->input('entry')` es null sin `Content-Type` (leer
`getContent()`); los bloques del FlowRunner van indexados por id y necesitan
`inicio`; `where()->update()` salta el cast `encrypted` y corrompe el token.

## Sesion 2026-09-02/03 — FACTURACION: emitir != consultar (en ARIN)

El usuario reporto que la pantalla de Facturas "no estaba separada". Hacia
TRES trabajos a la vez —emitir, buscar el historico y descargar el Registro
de Ventas— de tres personas distintas, peleandose el mismo espacio.

- **Emitir** (grupo Facturacion): Facturas / Boletas / Notas C-D son entradas
  independientes; cada una abre SU formulario con su serie (antes el
  formulario nacia siempre en 'boleta'). Sin buscador, sin registro y sin
  panel de lista: el formulario ocupa la pantalla. Filtro por `?tipo=` en
  `InvoiceController::index`.
- **Consultar** (grupo Reportes): pantalla nueva `invoices/consulta.blade.php`
  (`bixosales.facturas.consulta`) con busqueda por numero/cliente/RUC y
  filtros de tipo, estado SUNAT y fechas. "Registro de ventas" se movio aqui.
- El aviso del plazo de SUNAT vivia DENTRO del panel de lista: se movio al
  panel del formulario para no perder un aviso de riesgo fiscal al ocultarlo.
- Movil: la tabla de consulta pasa a tarjetas con datos etiquetados; campos a
  16px (iOS hace zoom por debajo). BUG corregido: cerrar el formulario con la
  X en una seccion dejaba la pantalla EN BLANCO.

Menu de OPERACION reorganizado por areas (antes Facturacion y Cobranza
compartian cajon "Finanzas" y los pedidos del bot colgaban de Comercial):
Ventas / Clientes / Facturacion / Cobranza / Inventario / Canales /
Logistica / Reportes.

**Ojo (dato, no codigo):** un modulo solo se publica en el menu si el negocio
lo USA o tiene su ajuste `modulo_<clave>`=1. MUSUHUAY tenia el entitlement
`invoices` contratado pero 0 comprobantes, asi que el grupo Facturacion no
aparecia y parecia que los cambios "no se veian". Se activo su ajuste.

Commits: `b2bdc82`, `a2815bd`. Suite 1143 pass (2 rojos ajenos de variantes).

## Cierre 2026-08-30 (noche) — UN SOLO MENU POR CARA (commit `72f9086`, en ARIN)

El usuario reporto que al navegar por /bixoadmin EL MENU CAMBIABA. Causa: la
unificacion habia migrado solo una parte de las pantallas, asi que convivian
dos shells dentro de la misma cara. Corregido reusando lo existente:

- **CORREGIDO la misma noche**: primero hice que `AppLayout` delegara en el
  shell comercial. El usuario lo RECHAZO — eso le llevaba al panel el
  encabezado y las alertas de ventas y le quitaba el SELECTOR DE NEGOCIO:
  *"admin tiene un diseno y sales otro, en ningun momento te pedi eso"*.
  Solucion definitiva: cada cara con SU shell, y el salto de menu se elimina
  porque TODAS las pantallas de /bixoadmin usan el del panel. Ademas se
  ordeno su menu de Configuracion por afinidad y se descubrio que
  **Certificados SUNAT no estaba en ningun menu**. Esto CIERRA TD-023 sin
  esperar a variantes. `ShellUnicoWorkspaceTest` 11/11; commits `72f9086`,
  `f809924`, `7d72e09`.
- **Regresion evitada**: el modal `window.__confirm` vivia DENTRO del shell
  del panel; las pantallas migradas se quedaban sin el y eliminar producto,
  eliminar imagen, categorias, servicios y descartar borrador habrian
  reventado en silencio. Extraido a `partials/confirm-global`, incluido por
  los dos shells. Lo cazaron los tests antes del deploy.
- Rescatadas 6 entradas huerfanas (Usuarios, Categorias, Servicios, Combos,
  Promociones, Flujos del bot) y el menu filtra por MODULO ACTIVO.


**Fallos de suite que NO son de esta rama:** `ProcesadorImagenesTest` (14)
falla tambien con este trabajo stasheado — es `app/Support/ImageVariants.php`
modificado y SIN COMMITEAR por otra sesion; y los 2 de plantillas 3→2 de la
feature de variantes. No se tocan.

## Sesion 2026-08-30 (noche) — REESTRUCTURACION FINAL (plan de 17 pasos)

Entrega completa: `docs/architecture/BIXO_REESTRUCTURACION_ENTREGA.md`
(arboles reales de /admin, /bixoadmin y /bixosales; matrices; clasificacion).
Matriz de capacidades: `docs/architecture/BIXO_CAPACIDADES_MATRIZ.md`.

1. `8034c9b` **Control ordenado**: menu de /admin agrupado (solo lo que
   existe), /admin/auditoria NUEVA (solo lectura AccessEvent), boton
   "Entrar como" auditado en Empresas, retirada copia muerta del shell.
2. `55cef3a` **Capacidades + menu por cara**: middleware `capacidad` =
   entitlement + permiso + flag `cap_*` (solo Eskala lo enciende; el dueño
   NO se salta el flag). Plantillas de diseño = ESKALA_ONLY (su export iba
   SIN middleware); diseño legacy endurecido y fuera de menu. Sidebar:
   /bixoadmin muestra SOLO el arbol de Configuracion (plan §3), /bixosales
   SOLO Operacion (§9), acceso cruzado "→ Ir a..." en ambas caras.
3. `5211f7e` **Logins**: "BIXO · Configuración" y "BIXO · Ventas y
   operación"; aterrizajes admin→Mi negocio, sales→Inicio.

Suite: 1020 pass / 2 failed AJENOS (variantes, plantillas 3→2).
Tests nuevos: ControlNavegacionTest (3), CapacidadesRestringidasTest (5);
contratos de FusionPortales/WorkspaceShellUnificado/SettingsAuthorization
actualizados al modelo de dos caras.
Deuda nueva: TD-024 (bloques del Control sin backend), TD-025 (aplicacion
fina de cap_builder_avanzado / cap_seo_avanzado).

## Sesion 2026-08-30 (tarde) — SSL de raiz + 2 caras del Workspace + lote de cierre

Todo commiteado en `a476929` y DESPLEGADO a ARIN (backup 20260830_181447):

1. **SSL resuelto de raiz (TD-009 CERRADA).** `arindg.com` tiene cert propio
   HTTP-01 renovable (vence 2026-11-28). La causa de que certbot nunca
   renovara: el vhost de `n8n.arindg.com` proxyaba TODO (incluido
   `/.well-known/acme-challenge`) al puerto 5678. Se agrego bloque 80 con la
   excepcion ACME (backup `/root/arindg.com.conf.bak-n8n-acme`); dry-run:
   `all simulated renewals succeeded`. Siguen en wildcard (vence 2026-11-04):
   babytoncito.com e importmusuhuaysac.arindg.com (este espera DNS del usuario).
2. **2 caras del Workspace visibles (decision del usuario 2026-08-30):** el
   usuario vio "Mi negocio" en el shell unificado y sintio que "lo mandaba al
   panel de sales". Eligio: shell y login unicos, pero cara distinguible.
   Implementado: chip morado "Configuración" en el breadcrumb para
   `/bixoadmin/*` (`comercial/layouts/app.blade.php`), grupo Configuración
   acentuado en el sidebar, y el login aterriza en Inicio (`/bixosales`) en
   vez de la lista de negocios (routes/web.php, ruta `dashboard`).
3. **Salida de impersonacion auditada:** `POST /bixoadmin/salir-de-impersonacion`
   (registra `impersonate_end`, limpia sesion); fix del meta doble-encodeado
   en la entrada. `FusionPortalesTest` 5/5.
4. **Fix latente SUNAT:** `DarDeBajaEnSunat::failed()` con `allProjects()` —
   en cola sync el fallo no marcaba el comprobante (quedaba "pendiente" mudo).
5. **ModulosPortal unificado con entitlements:** el menu ya no ofrece
   facturas/reservas/reparto sin modulo contratado (misma regla que el gate
   `comercial.module`). Fixtures de MenuLateralComercialTest contratan modulos.
6. **Rutas legado /f/ medidas: 0 hits** en todos los logs de nginx → retirarlas
   es seguro cuando se decida.

Suite: 1011 pass; los 2 rojos son de la feature de variantes en vuelo
(plantillas 3→2 motores en `supported-template-selector`), ajenos.

**PENDIENTE QUE DECIDE EL USUARIO — token del bot (RISK-008 residuo):** rotar
`WABOT_TOKEN` exige tocar `.env` de ARIN y el conector Baileys (engine.js,
constante BOT_TOKEN) a la vez, con reinicio coordinado del bot (sin afectar al
otro bot). Procedimiento: (1) generar secreto nuevo, (2) actualizar .env +
`config:cache`, (3) actualizar conector y reiniciar SOLO ese PM2, (4) probar
un mensaje. Los defaults hardcodeados `wa-bot-secret-2024` en
CheckLaundryOverdue/SendAbandonedCartReminder/PublicController deben pasar a
config en ese mismo cambio.

## Incidente 2026-08-29 — tiendas caídas por deploy parcial de variantes (RESUELTO)

La feature de variantes se desplegó a ARIN de madrugada SIN sus modelos ni
tablas: `PublicController` (versión ARIN, más nueva que la del árbol local)
hacía eager-load de `activeVariants` y TODAS las tiendas públicas devolvían
500 (megahogar.org incluida). Rescate ejecutado:
1. Subidos los modelos que faltaban: ProductVariant, ProductAttribute,
   ProductAttributeValue, Product/Project (relaciones), MatrixService,
   CatalogQueryService (faltaba `facets()`), ProductVariantController
   (las rutas de ARIN lo referenciaban sin existir).
2. Migración `2026_08_27_000000_create_product_attributes_and_variants`
   corregida: DOS identificadores autogenerados superaban el límite de 64
   chars de MySQL (índice 73c → `pav_project_attr_active_idx`; FK 66c →
   `papv_pivot_value_fk`, distinto del de product_variant_values porque los
   nombres de FK son únicos por BD, error 1826). Registrada en ARIN [46].
3. Verificado: las 7 tiendas + megahogar.org + /tienda en 200; 0 errores
   nuevos en el log.
REGLA para la sesión de variantes: el deploy de esa feature debe ir COMPLETO
(controller+modelos+migraciones+servicios juntos). El `PublicController` y las
vistas de ARIN son MÁS NUEVOS que los del árbol local — comparar md5/mtime
antes de volver a desplegar cualquiera de esas piezas.

## Fase actual

**UNIFICACIÓN VISUAL DEL WORKSPACE — FASE 1 EN ARIN (2026-08-30)**, según la
spec del usuario y el ADR-002 precisado (2 plataformas; /bixoadmin y /bixosales
= dos caras del mismo Workspace):
- Sidebar maestro (`comercial/layouts/_sidebar.blade.php`): la configuración
  COMPLETA vive en el menú unificado (SEO, Pagos, Módulos, Roles, Catálogos
  maestros, Canales WhatsApp; Guías de remisión en Finanzas).
- "Mi negocio" y "Código QR" renderizan DENTRO del shell comercial unificado
  (`x-portal-layout comercial`); URLs /bixoadmin/* sin cambios.
- Header del panel con identidad común (BIXO + empresa activa).
- Deriva reconciliada: qr.blade.php de ARIN adoptada + 3 partials qr-*
  (1.344 líneas) que solo existían en ARIN entran al repo.
- `WorkspaceShellUnificadoTest` 4/4; suite 1006 verde (2 rojos ajenos designer).

**Deuda de la fase (TD-023):** Productos y Constructor siguen en el shell del
panel (feature de variantes ajena en vuelo — se re-parentan cuando aterrice);
después, retirar el shell del panel y paridad de breadcrumbs/notificaciones.
Antes:
**CHECKPOINT F1–F9 CERRADO FORMALMENTE (2026-08-28)** — informe:
`BIXO_CHECKPOINT_F1_F9_CLOSURE.md` (sección "CIERRE FORMAL"). Los 6 bloques de
seguridad/dinero/fiscal/entitlements en **PASS** y en ARIN:
- H1 WaBot (secreto por tenant + ownership), H2 Rifa (toda la capacidad),
  H3 Entitlements (`comercial.module`, 6 módulos incl. logistics), H4 deuda
  (todo pago por Ledger), H5 numeración (`Invoice::emitir` atómico).
- H10 Pricing = PASS WITH DEBT (documentado). H8/H9 = PARCIAL (comprobantes
  enlazan por teléfono; falta checkout/bot ajeno).
Suite: cierres 100% verdes (24 tests nuevos). RISK-008/009/010/012/013 y
TD-017 cerrados/mitigados.

**Bloque "Inventario y checkout" AGENDADO** (H6 stock variantes + H7
order→inventory + H8-checkout/bot + H10 resolver): ejecutar como UNA unidad
cuando la feature de **variantes de producto de otra sesión** aterrice
(ProductVariant/ProductVariantMatrixService sin commitear; tabla
`product_variants` NI EXISTE en prod → RISK-011 no es riesgo activo). Decisión
de negocio pendiente para H7: **descontar stock al confirmar** (recomendado).
4 tests AJENOS rojos (CatalogTemplatesManifest/Admin*Layout) por esa feature de
plantillas en el árbol — no son del checkpoint.

**Para salir con clientes: LISTO.** Lo crítico (seguridad + dinero) cerrado.
**GO/NO-GO F10: NO-GO** por dependencias externas, no por deuda de seguridad.
Antes:
**VALIDACIÓN ARQUITECTÓNICA INTEGRAL (2026-08-28)** — auditoría de arquitectura
contra código real (5 auditores). Informe: `BIXO_VALIDACION_ARQUITECTONICA.md`.
Veredicto: la FORMA general quedó como se acordó (Control≠Workspace,
Administrador=rol, no ERP paralelo, experiencias reutilizan canónicos, Growth
no existe, facturación única, estados desacoplados). Pero hay **5 FALLAS que
obligan NO-GO para F10**:
1. WaBotController mutable cross-tenant (RISK-008, explotable hoy).
2. Fix de rifa incompleto: 4 métodos sin filtrar (RISK-009) — mi cierre del
   2026-08-27 solo cubrió los reportes.
3. Deuda con 3 fuentes divergentes (RISK-010): PaymentController escribe sin Ledger.
4. Stock de variante fuera del Kardex (RISK-011).
5. Numeración de comprobante fuera de transacción (RISK-012).
Más: /bixosales sin `module:` (entitlement no exigido), client_id no enlazado
en 2 canales, Customer 360 con 3/5 fuentes vacías. Suite 903 verde. NADA se
modificó en esta auditoría (regla del usuario). NO iniciar F10.
Antes: checkpoint F0–F11 validado y **críticos cerrados** (2026-08-27), sistema
listo para salir con clientes. Cerrado y desplegado a ARIN: APP_DEBUG=false,
DELETE/PUT /projects con permiso, RBAC en las 34 rutas de /f/{slug} (middleware
`proyecto.slug`), fuga de lectura de rifa, y el envío entra al total del
checkout. Suite 903 verde. Pendiente NO bloqueante: enlace de cliente en el
checkout (client_id) — su fix vive en PublicController, hoy con una feature de
variantes de otra sesion sin terminar; se retoma cuando aterrice. Detalle:
`BIXO_VALIDACION_F0_F11.md` (sección "CIERRE DE HALLAZGOS").

## Ultima tarea terminada

**VALIDACIÓN FINAL F0–F11 (2026-08-27)** — auditoría independiente contra el
código real, commits, suite y ARIN. Resultado en
`BIXO_VALIDACION_F0_F11.md`. Resumen: F0/F1/F7/F8/F11 = PASS o PASS WITH DEBT;
F2/F3/F4/F5/F6/F9 = PARTIAL. **Recomendación NO-GO para F10** hasta cerrar 4
puntos: (1) `APP_DEBUG=true` en producción [CRÍTICO], (2) `DELETE /projects`
solo exige pertenencia, (3) `/f/{slug}` conserva 34 rutas sin RBAC granular
(retiro cosmético), (4) checkout no enlaza cliente ni propaga envío/cupón.
Suite verificada: 896 verdes, 0 skipped. Antes:
**F11 Portal del Cliente construida (2026-08-27)**: enlace personal
`GET /c/{token}` (registrado ANTES del comodin /{slug}), repetir pedido con
interruptor `portal_precios` (fijos → Order pendiente canal portal a precio
de catalogo vigente; confirmar → Quote draft con lineas a 0), boton
generar/copiar/regenerar en la ficha del cliente (panel `clients.portal` y
bixosales `bixosales.clientes.portal`, permiso clients.editar),
migracion `clients.portal_token` corrida en local, `PortalClienteTest` 6/6.
Plan vivo en `BIXO_PLAN_TRABAJO.md`. Antes: Fase 1 casi completa: contrato de scope para las 14 entidades nucleares,
2 huecos mas cerrados (CatalogValue con fachada, BotTransition ajena),
auditoria de allProjects() en jobs limpia, y TD-010 descubierta y registrada
(dos generaciones de flujos de bot). Antes: **RISK-001 cerrado**: `HasProjectScope` en `RifaVenta`, lecturas
multi-proyecto con `allProjects()` explicito, unicidad global de
`order_number`, y `tests/Feature/AislamientoTenantTest.php` como plantilla
de TD-002. Antes de eso: auditoria medida del repositorio: rutas por portal, aislamiento multiempresa
por metodo, generaciones de permisos, entidades competidoras y volumen real
de negocio. Resultados en `BIXO_CURRENT_STATE.md` y `BIXO_RISKS.md`.

## Tarea en progreso

Ninguna. Fase 0 cerrada a la espera de decision sobre el orden de la Fase 1.

## Siguiente tarea

Reestructura ejecutada hasta F8 (ADR-010). Queda la limpieza diferida:
borrar vistas f-slug tras un ciclo, retirar lectores de
`comercial_project_id`, mapa de navegacion y apagado del panel raiz.
F9 (checkout) y F11 (portal cliente) pasan al backlog de producto;
10/12/13 esperan volumen (ADR-005). Antes: Fase 2 COMPLETA (queda el retiro final de
nombres legacy tras un ciclo de uso). Sigue Fase 3: sesion unica de
proyecto (TD-005) e impersonacion auditada. Antes: Fase 1 COMPLETA. Sigue Fase 2: TD-003 (brand_catalog_id huerfano),
retiro final de nombres legacy de TD-004 tras un ciclo de uso, TD-010
(dos generaciones de flujos de bot), y sacar del control plane la
operacion de tenant (AdminImport/AdminTurnos). Antes era: decidir TD-001 — unica tarea de la
Fase 1 que queda, y es decision de diseño, no mecanica. Con eso la Fase 1
cierra y sigue la Fase 2 (ownership: TD-003 brand_catalog_id, TD-004 permisos
legacy, TD-010 dos generaciones de flujos de bot). Antes era: **RISK-001** — RifaVenta sin comprobacion de tenant (5 metodos). Es el unico
hallazgo de acceso cruzado confirmado y no depende de ninguna reestructuracion:
se corrige solo, con test de aislamiento. Ver `docs/security/BIXO_TENANT_ISOLATION.md`.

## Archivos actualmente involucrados

Ninguno en edicion. La auditoria no modifico codigo de aplicacion.

## Tests

- Suite completa: **874 passed** (3.375 assertions) al commit `2a64c15`.
- Tests de aislamiento multiempresa por entidad: **no existen todavia** (deuda TD-002).

## Problemas conocidos

- Certificado SSL de `arindg.com` **vencido el 2026-08-27**; existe `arindg-wildcard`
  valido 68 dias que ya cubre el dominio. No es un tema de arquitectura pero
  afecta a produccion.
- 59 archivos modificados sin commitear en el arbol de trabajo, varios de otra
  sesion (procesamiento de imagenes). No commitear a ciegas.

## Decisiones pendientes

1. Orden de la Fase 1: corregir RISK-001 antes o despues de unificar sesiones.
2. Si `/r/{slug}` (revendedores) sigue vivo comercialmente.
3. Nombre en castellano para la experiencia "Growth".

## NO TOCAR

- `whatsbot/`, `.env`, `storage/`, `vendor/`, `node_modules/`.
- Pedidos 20, 21 y 22.
- `resources/views/public/templates/computienda.blade.php` sin comparar antes
  contra produccion: el archivo tiene deriva y trabajo de otra sesion.

## Contexto necesario para continuar

Leer en este orden: `BIXO_CURRENT_STATE.md`, `BIXO_MASTER_CHECKLIST.md`,
`BIXO_DECISIONS.md`, `MODULE_OWNERSHIP.md`, `BIXO_RESTRUCTURE_ROADMAP.md`.
Los cuatro documentos de arquitectura preexistentes (`BIXO_PLATFORM_AUDIT.md`,
`BIXO_TARGET_ARCHITECTURE.md`, `BIXO_RESTRUCTURE_ROADMAP.md`,
`MODULE_OWNERSHIP.md`) son de una sesion anterior y siguen vigentes: esta
auditoria los complementa con evidencia medida, no los sustituye.

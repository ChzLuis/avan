# -*- coding: utf-8 -*-
# Retira de BIXO el codigo de sorteos (rifas), la gestion "pedidos del bot"
# que colgaba de el, y los dos webhooks de Meta muertos de WaWebhookController.
#
# Decision de producto 2026-09-17: el sorteo vive en otro proyecto
# (htdocs/sorteos); en ARIN `rifas` y `rifa_ventas` tienen 0 filas, ningun canal
# tiene phone_number_id (Meta no esta conectado) y el log no registra ni un
# "Meta webhook recibido". Todo lo que se retira aqui no se ejecuta en produccion.
#
# Metodo: cada corte esta anclado a una cadena exacta con el numero de
# apariciones esperado. Primero se aplica TODO en memoria; si un anclaje no
# cuadra, no se escribe ni se borra nada.
import io, os, re, sys, subprocess
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
B = chr(92)
def ns(*p): return B.join(p)

problemas = []
salida = {}      # path -> contenido nuevo
def leer(p):
    if p in salida: return salida[p]
    return io.open(os.path.join(L, p), encoding='utf-8', newline='').read()
def nl_de(s): return '\r\n' if '\r\n' in s else '\n'
def A(s, texto):
    """Adapta un anclaje escrito con \\n al fin de linea del archivo."""
    return texto.replace('\n', nl_de(s)) if nl_de(s) == '\r\n' else texto

def reemplazar(p, viejo, nuevo, n):
    s = leer(p); v = A(s, viejo); c = s.count(v)
    if c != n: problemas.append(f'{p}: esperaba {n} de {viejo[:60]!r}, hay {c}'); return
    salida[p] = s.replace(v, A(s, nuevo))
def quitar(p, viejo, n): reemplazar(p, viejo, '', n)
def quitar_regex(p, patron, n, flags=re.M):
    s = leer(p); c = len(re.findall(patron, s, flags))
    if c != n: problemas.append(f'{p}: regex esperaba {n} de {patron[:50]!r}, hay {c}'); return
    salida[p] = re.sub(patron, '', s, flags=flags)
def reemplazar_regex(p, patron, nuevo, n, flags=re.M):
    s = leer(p); c = len(re.findall(patron, s, flags))
    if c != n: problemas.append(f'{p}: regex esperaba {n} de {patron[:50]!r}, hay {c}'); return
    salida[p] = re.sub(patron, nuevo, s, flags=flags)
def quitar_bloque(p, inicio, fin, incluir_fin=False):
    """Borra desde la primera aparicion de `inicio` hasta la primera de `fin`
    posterior (exclusiva salvo incluir_fin)."""
    s = leer(p); i0 = A(s, inicio); f0 = A(s, fin)
    i = s.find(i0)
    if i < 0: problemas.append(f'{p}: no hallo inicio {inicio[:50]!r}'); return
    j = s.find(f0, i + len(i0))
    if j < 0: problemas.append(f'{p}: no hallo fin {fin[:50]!r}'); return
    if incluir_fin: j += len(f0)
    salida[p] = s[:i] + s[j:]
def asegurar_ausente(p, patron):
    s = leer(p)
    m = re.findall(patron, s, re.M)
    if m: problemas.append(f'{p}: quedan restos {m[:4]}')

# ── Archivos que desaparecen ──────────────────────────────────────────────
BORRAR = [
    'app/Http/Controllers/RifaController.php',
    'app/Http/Controllers/WaWebhookController.php',
    'app/Models/Rifa.php',
    'app/Models/RifaVenta.php',
    'app/Support/WhatsappCloud/VerificaFirmaMeta.php',   # solo lo usaba WaWebhookController
    'resources/views/rifas',
    'resources/views/comercial/rifas.blade.php',
    'resources/views/comercial/reportes/ventas-bot.blade.php',
    'resources/views/comercial/reportes/seguimiento-bot.blade.php',
    'database/seeders/RifaFlowSeeder.php',
    'tests/Feature/RifaIsolationTest.php',
    'tests/Feature/AislamientoTenantTest.php',     # su sujeto era RifaVenta; el aislamiento lo cubren TenantOwnedModelScopeTest y ProjectIsolationTest
    'tests/Feature/WaWebhookFirmaTest.php',        # probaba los webhooks retirados; la firma la cubre WhatsappCloudWebhookTest
]
for p in BORRAR:
    if not os.path.exists(os.path.join(L, p)): problemas.append(f'no existe para borrar: {p}')

# ── routes/web.php ────────────────────────────────────────────────────────
R = 'routes/web.php'
quitar(R, 'use ' + ns('App','Http','Controllers','WaWebhookController') + ';\n', 1)
quitar(R, 'use ' + ns('App','Http','Controllers','RifaController') + ';\n', 1)
# closures /bot-qr y /bot-status (solo los usaba la pantalla de rifas)
quitar_bloque(R, "Route::get('/bot-qr/{bot?}', function ($bot = 'rifa') {", "Route::post('/api/woo-webhook'")
# Rifa Bot API + Rifa Panel Admin
quitar_bloque(R, '// ─── Rifa Bot API', '// ─── Pagos del catálogo')
# los dos webhooks muertos de Meta
quitar_bloque(R, '// ─── WhatsApp Webhooks (públicos, sin auth)', '// ─── Portal Comunicaciones')
# pedidos-bot dentro de bixosales (12 lineas) y reportes del bot (2)
quitar_regex(R, r'^.*RifaController::class.*\n', 12)
quitar_regex(R, r"^.*\[ReporteController::class, 'ventasBot'\].*\n", 1)
quitar_regex(R, r"^.*\[ReporteController::class, 'seguimientoBot'\].*\n", 1)
asegurar_ausente(R, r'\$nocsrf|RifaController|WaWebhookController|pedidos-bot|\brifas?\b')

# ── ReporteController: solo los dos reportes del bot ──────────────────────
P = 'app/Http/Controllers/ReporteController.php'
quitar(P, 'use ' + ns('App','Models','RifaVenta') + ';\n', 1)
quitar(P, 'use ' + ns('App','Models','BotInstance') + ';\n', 1)
quitar_bloque(P, '    private function getProjectIds(): array', '    /** 7.3 — Top productos más vendidos */')
asegurar_ausente(P, r'RifaVenta|BotInstance|getProjectIds|\brifa')

# ── Dashboard comercial: rama alterna de rifas ────────────────────────────
D = 'app/Http/Controllers/Comercial/DashboardController.php'
quitar_bloque(D, '        // ── Detectar si el proyecto usa rifa_ventas', '        // ── Lógica estándar (orders)')
s = leer(D); i = s.find('    // ── Dashboard específico para proyectos de rifas'); j = s.rfind(nl_de(s) + '}')
if i < 0 or j < 0 or j < i: problemas.append(f'{D}: no delimito indexRifa')
else: salida[D] = s[:i].rstrip() + nl_de(s) + s[j+len(nl_de(s)):]
asegurar_ausente(D, r'[Rr]ifa')

# ── Layout comercial ──────────────────────────────────────────────────────
Y = 'resources/views/comercial/layouts/app.blade.php'
reemplazar(Y, "in_array($_cat, ['rifa','sorteo','comercial'])", "in_array($_cat, ['comercial'])", 1)
# bloque PHP: quitar la deteccion y el `if ($_usaRifas) {...} else {` dejando el cuerpo del else
quitar_bloque(Y, '            $_usaRifas  = $_pid_alert &&', '            } else {\n', incluir_fin=True)
reemplazar(Y, '            }\n            @endphp', '            @endphp', 1)
# ALERTAS
quitar_bloque(Y, '                @if($_usaRifas)\n                    @if($_rv_comp->isEmpty()', '                @else\n', incluir_fin=True)
reemplazar(Y, '                @endif\n            </div>\n\n            {{-- PENDIENTES --}}', '            </div>\n\n            {{-- PENDIENTES --}}', 1)
# PENDIENTES
quitar_bloque(Y, '                @if($_usaRifas)\n                    {{-- KPIs rifa --}}', '                @else\n', incluir_fin=True)
reemplazar(Y, '                @endif\n            </div>\n\n            {{-- ACTIVIDAD --}}', '            </div>\n\n            {{-- ACTIVIDAD --}}', 1)
# ACTIVIDAD: el else tiene una sola linea y luego @endif
quitar_bloque(Y, '                @if($_usaRifas && isset($_rv_recientes))', '                @else\n', incluir_fin=True)
s = leer(Y); k = s.find(A(s, '                @endif\n'), s.find(A(s, '{{-- ACTIVIDAD --}}')))
if k < 0: problemas.append(f'{Y}: no hallo el @endif de ACTIVIDAD')
else: salida[Y] = s[:k] + s[k + len(A(s, '                @endif\n')):]
reemplazar(Y, "@if(!$_usaRifas && $_can('caja.ver') && $_mod['caja'])", "@if($_can('caja.ver') && $_mod['caja'])", 1)
# boton "Conversaciones" del superadmin apuntaba a rifas
reemplazar(Y, "route('bixosales.rifas')", "route('bixosales.conversaciones')", 1)
asegurar_ausente(Y, r'_usaRifas|_rv_|rifa_ventas|\brifas?\b|bixosales\.rifas')

# ── Otras vistas y controladores comerciales ──────────────────────────────
reemplazar('resources/views/comercial/dashboard.blade.php', "['comercial','rifa','sorteo']", "['comercial']", 1)
C = 'resources/views/comercial/conversaciones.blade.php'
quitar(C, '                    @if($s->rifa)\n                    <span style="font-size:9px;color:#9CA3AF;margin-left:4px;">{{ $s->rifa }}</span>\n                    @endif\n', 1)
quitar(C, "        ['Rifa',     d.rifaNombre],\n", 1)
quitar(C, "        ['Total',    d.rifaTotal ? 'S/ ' + d.rifaTotal : null],\n", 1)
quitar(C, "        ['Tickets',  d.rifaTickets],\n", 1)
V = 'app/Http/Controllers/Comercial/ConversacionesController.php'
quitar(V, "            $s->rifa     = $data['rifaNombre'] ?? null;\n", 1)
quitar(V, "            $s->total    = $data['rifaTotal']  ?? null;\n", 1)
reemplazar('resources/views/comercial/panel/arranque.blade.php',
    "{{-- `?? null`: la rama de rifa del panel (DashboardController::indexRifa)\n     reutiliza esta misma vista pero no calcula $arranque, y sin el blindaje\n     Laravel convierte la variable indefinida en excepcion: el Inicio entero\n     daba 500 en todo proyecto con ventas de rifa. El resto de variables de la\n     rama estandar ya venian blindadas; esta era la unica suelta. --}}",
    "{{-- `?? null`: una rama antigua del panel (ya retirada) reutilizaba esta\n     vista sin calcular $arranque, y sin el blindaje Laravel convierte la\n     variable indefinida en excepcion. El resto de variables ya venian\n     blindadas; esta era la unica suelta. --}}", 1)
quitar('resources/views/comercial/layouts/_sidebar.blade.php', "        $_mod['bot'] ? ['Pedidos del bot', 'bixosales.rifas', 'bot', ['rifas.ver']] : null,\n", 1)
quitar_regex('resources/views/layouts/app.blade.php', r"^\s*'rifas'\s*=> null,.*\n", 9)
reemplazar('app/Modules/Personas/Views/roles/index.blade.php', ", rifas:  '#25d366',", ",", 1)

# ── Vistas del bot (BotStatusController) ──────────────────────────────────
reemplazar('resources/views/bots/flow.blade.php', "$botLabel = $botType === 'rifa' ? 'Bot Rifa' : 'Bot Principal';", "$botLabel = 'Bot Principal';", 1)
BI = 'resources/views/bots/index.blade.php'
quitar_bloque(BI, "                    @if($bot->bot_type === 'rifa')\n                    <button onclick=\"toggleVentas(", "                    @endif\n", incluir_fin=True)
quitar_bloque(BI, "            {{-- Ventas Rifa --}}\n            @if($bot->bot_type === 'rifa')", "            @endif\n", incluir_fin=True)
s = leer(BI)
i = s.find('// ── Ventas Rifa'); j = s.find('function showToast(')
if i < 0 or j < 0 or j < i: problemas.append(f'{BI}: no delimito el JS de ventas de rifa')
else: salida[BI] = s[:i] + s[j:]
reemplazar_regex('resources/views/bots/app.blade.php', r"\s*\|\|\s*request\(\)->routeIs\('rifas\.index'\)", '', 1)
asegurar_ausente(BI, r'[Rr]ifa|toggleVentas|loadVentas')

# ── Comentarios, permisos, soporte ────────────────────────────────────────
reemplazar('app/Support/ModulosPortal.php', 'devuelve 500 fuera de un proyecto de rifas, asi que ofrecerlo era mandar al', 'devolvia 500 fuera del proyecto que lo usaba, asi que ofrecerlo era mandar al', 1)
quitar_regex('app/Support/Access.php', r'^.*rifas\.\*.*\n', 1)
quitar('database/seeders/PermissionsSeeder.php', "        // Rifas / Bot\n        'rifas.ver', 'rifas.validar', 'rifas.cancelar',\n", 1)
quitar('database/seeders/PermissionsSeeder.php', "            'rifas.ver',   'rifas.validar', 'rifas.cancelar',\n", 1)
reemplazar('app/Support/ConsultaDocumento.php',
    " *   · DNI en `RifaController::consultarDni`, cuya ruta exige `can:rifas.ver`,\n *     así que un cajero sin permiso de rifas no podía consultar un documento.\n",
    " *   · DNI en un controlador ya retirado, tras un permiso ajeno a la caja,\n *     así que un cajero no podía consultar un documento.\n", 1)

# ── Canales del CRM: la URL del webhook es la nueva ───────────────────────
K = 'resources/views/comunicaciones/configuracion.blade.php'
reemplazar(K, "url('/wa/webhook/' . $canal->verify_token)", "url('/api/whatsapp/webhook')", 2)
reemplazar(K, "`{{ url('/wa/webhook/') }}` + form.verify_token", "`{{ url('/api/whatsapp/webhook') }}`", 1)

# ── Tests ─────────────────────────────────────────────────────────────────
quitar_regex('tests/Feature/LegacyMappingTest.php', r"^\s*'rifas\.[a-z]+',\s*'rifas\.[a-z]+',\s*'rifas\.[a-z]+',\s*\n", 2)
M = 'tests/Feature/MenuLateralComercialTest.php'
reemplazar(M, "'rifas.ver', ", '', 2)
reemplazar(M, "        // vacias. \"Pedidos del bot\" ademas revienta con 500 fuera de un\n        // proyecto de rifas, asi que enseñarlo era mandar a un error.\n", "        // vacias.\n", 1)
reemplazar(M, "->assertDontSee(route('bixosales.delivery'), false)\n            ->assertDontSee(route('bixosales.rifas'), false);", "->assertDontSee(route('bixosales.delivery'), false);", 1)
X = 'tests/Feature/BixoSalesAuthorizationTest.php'
quitar_regex(X, r"^\s*'rifas\.ver',\s*'rifas\.validar',\s*'rifas\.cancelar',\s*\n", 2)
quitar(X, "            'validar rifa'        => ['POST',   '/bixosales/pedidos-bot/1/validar'],\n", 1)
quitar(X, "            'cancelar rifa'       => ['POST',   '/bixosales/pedidos-bot/1/cancelar'],\n", 1)
reemplazar(X, "        // 2026-09-17: 72 tras revisar el barrido completo (75 mutadoras, y las\n        // 3 sin can: son login, logout y get.projects, que este test exime).\n        $this->assertSame(72, $mutadoras, 'El portal deberia tener 72 rutas con verbo mutador');",
              "        // 2026-09-17: 64 tras retirar las 8 de pedidos-bot (sorteos). Antes 72:\n        // 75 mutadoras, y las 3 sin can: son login, logout y get.projects.\n        $this->assertSame(64, $mutadoras, 'El portal deberia tener 64 rutas con verbo mutador');", 1)

# ── Resultado ─────────────────────────────────────────────────────────────
if problemas:
    print(f'!! {len(problemas)} anclajes no cuadran: no se toca nada')
    for x in problemas: print('  -', x)
    sys.exit(1)
print(f'verificacion en memoria: OK ({len(salida)} archivos a editar, {len(BORRAR)} rutas a borrar)')
if '--dry' in sys.argv: sys.exit(0)
for p, s in salida.items():
    io.open(os.path.join(L, p), 'w', encoding='utf-8', newline='').write(s)
print('editados:', len(salida))
r = subprocess.run(['git', 'rm', '-r', '-q', '--'] + BORRAR, cwd=L, capture_output=True, text=True)
if r.returncode != 0: print('ERROR git rm:', r.stderr.strip()); sys.exit(1)
print('borrados:', len(BORRAR))
print('LISTO')

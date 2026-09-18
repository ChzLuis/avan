# -*- coding: utf-8 -*-
# Genera el plan de la mudanza de Crm/ y Bots/ (modulo 5/8, dos carpetas a la
# vez porque se usan mutuamente). Lee el codigo real y escribe
# plan_crmbots.json; mover_modulo.py lo aplica verificando cada conteo.
#
# Reparto: Bots/ = el motor (BotFlow*, FlowEngine, Ia, webhooks, constructor,
# WaBot). Crm/ = canales, bandeja, conversaciones, Copilot, pagos por
# extension, recordatorios de carrito. Decision de producto 2026-09-17:
# bots/IA/automatizaciones viven solo dentro del CRM.
import io, os, re, json, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
B = chr(92)
def ns(*p): return B.join(p)
def fq(path_php):
    """FQCN a partir de una ruta PSR-4 bajo app/ (con o sin app/ delante)."""
    rel = path_php[4:] if path_php.startswith('app/') else path_php
    return 'App' + B + rel[:-4].replace('/', B)

moves = []      # (src, dst)
clases = []     # (fqcn_viejo, fqcn_nuevo)
renombrados = []  # (dst, clase_vieja, clase_nueva)
def mv(src, dst, clase_nueva=None):
    if not os.path.exists(os.path.join(L, src)): raise SystemExit('no existe ' + src)
    moves.append((src, dst))
    viejo = fq(src)
    nuevo = fq(dst)
    clases.append((viejo, nuevo))
    if clase_nueva:
        renombrados.append((dst, os.path.basename(src)[:-4], clase_nueva))

# ── Bots/ ──────────────────────────────────────────────────────────────────
BO = 'app/Modules/Bots'
for c in ['BotFlowController', 'BotStatusController', 'WaBotController']:
    mv(f'app/Http/Controllers/{c}.php', f'{BO}/Controllers/{c}.php')
mv('app/Http/Controllers/Comunicaciones/BotBuilderPortalController.php', f'{BO}/Controllers/BotBuilderPortalController.php')
for c in ['BotWebhookController', 'WhatsappCloudWebhookController']:
    mv(f'app/Http/Controllers/Api/{c}.php', f'{BO}/Controllers/{c}.php')
for m in ['BotConfig', 'BotFlow', 'BotInstance', 'BotSession', 'BotState', 'BotTransition']:
    mv(f'app/Models/{m}.php', f'{BO}/Models/{m}.php')
for f in sorted(os.listdir(os.path.join(L, 'app/Support/FlowEngine'))):
    if f.endswith('.php'): mv(f'app/Support/FlowEngine/{f}', f'{BO}/Support/FlowEngine/{f}')
mv('app/Support/LeadScoring.php', f'{BO}/Support/LeadScoring.php')
for dp, dn, fn in os.walk(os.path.join(L, 'app/Ia')):
    for f in sorted(fn):
        if f.endswith('.php'):
            rel = os.path.relpath(os.path.join(dp, f), os.path.join(L, 'app/Ia')).replace(B, '/')
            mv(f'app/Ia/{rel}', f'{BO}/Ia/{rel}')
mv('app/Console/Commands/BotComercialParaTodos.php', f'{BO}/Commands/BotComercialParaTodos.php')

# ── Crm/ ───────────────────────────────────────────────────────────────────
CR = 'app/Modules/Crm'
mv('app/Http/Controllers/Comunicaciones/AuthController.php', f'{CR}/Controllers/CrmAuthController.php', 'CrmAuthController')
for c in ['BandejaController', 'CanalesController', 'ClientesCrmController']:
    mv(f'app/Http/Controllers/Comunicaciones/{c}.php', f'{CR}/Controllers/{c}.php')
mv('app/Http/Controllers/Comercial/ConversacionesController.php', f'{CR}/Controllers/ConversacionesController.php')
mv('app/Http/Controllers/ComunicacionesController.php', f'{CR}/Controllers/ComunicacionesController.php')
mv('app/Http/Controllers/CopilotEmpresarialController.php', f'{CR}/Controllers/CopilotEmpresarialController.php')
for c in ['CopilotController', 'VentaExtensionController', 'WhatsappSyncController', 'PagoController']:
    mv(f'app/Http/Controllers/Api/{c}.php', f'{CR}/Controllers/{c}.php')
for m in ['WaCanal', 'WaChatbotFlow', 'WaConversacion', 'WaMensaje', 'WaRespuestaRapida']:
    mv(f'app/Models/{m}.php', f'{CR}/Models/{m}.php')
mv('app/Support/WhatsappCloud/ClienteCloud.php', f'{CR}/Support/WhatsappCloud/ClienteCloud.php')
for c in ['SeguimientoConversaciones', 'SendAbandonedCartReminders']:
    mv(f'app/Console/Commands/{c}.php', f'{CR}/Commands/{c}.php')
mv('app/Jobs/SendAbandonedCartReminder.php', f'{CR}/Jobs/SendAbandonedCartReminder.php')

# ── Vistas (orden: primero la subcarpeta que va a Bots, luego el resto a Crm) ──
VISTAS = [
    ('resources/views/comunicaciones/bots', f'{BO}/Views/comunicaciones/bots', 'comunicaciones.bots', 'bots'),
    ('resources/views/bot-builder',         f'{BO}/Views/bot-builder',         'bot-builder',         'bots'),
    ('resources/views/bot-flows',           f'{BO}/Views/bot-flows',           'bot-flows',           'bots'),
    ('resources/views/bots',                f'{BO}/Views/bots',                'bots',                'bots'),
    ('resources/views/comunicaciones',      f'{CR}/Views/comunicaciones',      'comunicaciones',      'crm'),
    ('resources/views/copilot',             f'{CR}/Views/copilot',             'copilot',             'crm'),
    ('resources/views/comercial/conversaciones.blade.php', f'{CR}/Views/comercial/conversaciones.blade.php', 'comercial.conversaciones', 'crm'),
]
for src, dst, _, _ in VISTAS:
    if not os.path.exists(os.path.join(L, src)): raise SystemExit('no existe ' + src)

MOVIDOS = {os.path.basename(s)[:-4] for s, _ in moves}
def namespace_de(dst):
    return fq(dst).rsplit(B, 1)[0]

# ── Recorrer el codigo ─────────────────────────────────────────────────────
RAICES = ['app', 'routes', 'tests', 'database', 'config', 'bootstrap', 'resources/views']
def archivos():
    for r in RAICES:
        for dp, dn, fn in os.walk(os.path.join(L, r)):
            for f in fn:
                if f.endswith('.php'):
                    yield os.path.relpath(os.path.join(dp, f), L).replace(B, '/')
IDENT = re.compile(r'[A-Za-z0-9_]')
def contar_bounded(s, needle):
    n = 0; i = 0
    while True:
        j = s.find(needle, i)
        if j < 0: return n
        k = j + len(needle)
        if k >= len(s) or not IDENT.match(s[k]): n += 1
        i = k

edits, inserts = {}, {}
def add_edit(path, old, new, n): edits.setdefault(path, []).append({'old': old, 'new': new, 'n': n})
dst_de_src = {s: d for s, d in moves}
src_de_dst = {d: s for s, d in moves}
contenido = {p: io.open(os.path.join(L, p), encoding='utf-8', newline='').read() for p in archivos()}
def destino(p):
    if p in dst_de_src: return dst_de_src[p]
    for src, dst, _, _ in VISTAS:
        if p == src or p.startswith(src + '/'): return dst + p[len(src):]
    return p

# 1) FQCN de clases movidas en todos los archivos (los movidos incluidos).
#    Orden: los mas largos primero (App\Ia\Providers\X antes que App\Ia\X no
#    hace falta por el bounded, pero App\Http\Controllers\Api\X si).
for p, s in contenido.items():
    d = destino(p)
    for viejo, nuevo in sorted(clases, key=lambda x: -len(x[0])):
        n = contar_bounded(s, viejo)
        if n: add_edit(d, viejo, nuevo, n)

# 2) Namespace de cada movido + imports que pierde.
CORE_MODELS = {f[:-4] for f in os.listdir(os.path.join(L, 'app/Models')) if f.endswith('.php')} - MOVIDOS
CORE_SUPPORT = {f[:-4] for f in os.listdir(os.path.join(L, 'app/Support')) if f.endswith('.php')} - MOVIDOS
NUEVO_DE = {os.path.basename(d)[:-4]: fq(d) for _, d in moves}
OTROS = {}
for mod in ('Personas', 'Control', 'Inventario', 'Finanzas'):
    for dp, dn, fn in os.walk(os.path.join(L, 'app/Modules', mod)):
        for f in fn:
            if f.endswith('.php') and 'Views' not in dp:
                OTROS[f[:-4]] = fq(os.path.relpath(os.path.join(dp, f), L).replace(B, '/'))
TOKEN = re.compile(r'\b([A-Z][A-Za-z0-9_]*)\s*(?:::|\$|\(|\))|(?:belongsTo|hasMany|hasOne|belongsToMany|morphMany|morphTo|instanceof|new|extends|implements)\s*\(?\s*([A-Z][A-Za-z0-9_]*)|\(\s*([A-Z][A-Za-z0-9_]*)\s+\$')
for src, dst in moves:
    s = contenido[src]
    m = re.search(r'^namespace ([^;]+);', s, re.M)
    if not m: raise SystemExit('sin namespace: ' + src)
    ns_viejo, ns_nuevo = m.group(1), namespace_de(dst)
    add_edit(dst, 'namespace ' + ns_viejo + ';', 'namespace ' + ns_nuevo + ';', 1)
    importados = set(re.findall(r'^use [^;]*?([A-Za-z0-9_]+)(?: as [A-Za-z0-9_]+)?;', s, re.M))
    usados = {x for t in TOKEN.findall(s) for x in t if x}
    yo = os.path.basename(src)[:-4]
    nuevos = []
    for x in sorted(usados):
        if x in importados or x == yo: continue
        if x in MOVIDOS:
            # Otro movido: si acaba en OTRO namespace, hay que importarlo.
            if NUEVO_DE[x].rsplit(B, 1)[0] != ns_nuevo and NUEVO_DE[x].rsplit(B, 1)[0] != ns_viejo:
                nuevos.append('use ' + NUEVO_DE[x] + ';')
            elif NUEVO_DE[x].rsplit(B, 1)[0] != ns_nuevo:
                nuevos.append('use ' + NUEVO_DE[x] + ';')
            continue
        if ns_viejo == ns('App','Models') and x in CORE_MODELS: nuevos.append('use ' + ns('App','Models',x) + ';')
        elif ns_viejo.startswith(ns('App','Support')) and x in CORE_SUPPORT: nuevos.append('use ' + ns('App','Support',x) + ';')
        elif x == 'Controller' and ns_viejo.startswith(ns('App','Http','Controllers')): nuevos.append('use ' + ns('App','Http','Controllers','Controller') + ';')
        elif x in OTROS and ns_viejo == ns('App','Models'): nuevos.append('use ' + OTROS[x] + ';')
    if nuevos: inserts[dst] = sorted(set(nuevos))

# 2b) Renombrados: declaracion de clase y autorreferencias.
for dst, viejo, nuevo in renombrados:
    s = contenido[src_de_dst[dst]]
    add_edit(dst, 'class ' + viejo + ' extends', 'class ' + nuevo + ' extends', 1)
    n = s.count(viejo + '::class')
    if n: add_edit(dst, viejo + '::class', nuevo + '::class', n)

# 3) Modelos de Core que referencian movidos sin importar.
for p, s in contenido.items():
    if p in dst_de_src or not p.startswith('app/Models/'): continue
    importados = set(re.findall(r'^use [^;]*?([A-Za-z0-9_]+);', s, re.M))
    usados = {x for t in TOKEN.findall(s) for x in t if x}
    faltan = [x for x in sorted(MOVIDOS) if x in usados and x not in importados and x in NUEVO_DE and NUEVO_DE[x].split(B)[3] in ('Models',) ]
    faltan = [x for x in faltan if os.path.dirname(NUEVO_DE[x].replace(B,'/')).endswith('/Models')]
    if faltan: inserts[p] = ['use ' + NUEVO_DE[x] + ';' for x in faltan]

# 4) Vistas por nombre.
LLAMADA = re.compile(r"""(view|loadView|assertViewIs|View::make|@include|@extends|@includeIf|@includeWhen|@component|@each|markdown|->view)\(\s*(['"])([a-z0-9_.-]+)\2""")
PREFIJOS = sorted([(pref, modn) for _, _, pref, modn in VISTAS], key=lambda x: -len(x[0]))
def renombrar_vista(nombre):
    for pref, modn in PREFIJOS:
        if nombre == pref or nombre.startswith(pref + '.'):
            return modn + '::' + nombre
    return None
vistas_edits = {}
for p, s in contenido.items():
    d = destino(p)
    for m in LLAMADA.finditer(s):
        nuevo = renombrar_vista(m.group(3))
        if nuevo:
            vistas_edits.setdefault(d, {}); vistas_edits[d][m.group(0)] = vistas_edits[d].get(m.group(0), 0) + 1
for p, dct in vistas_edits.items():
    for viejo_txt, n in dct.items():
        m = LLAMADA.match(viejo_txt)
        add_edit(p, viejo_txt, viejo_txt.replace(m.group(2) + m.group(3) + m.group(2), m.group(2) + renombrar_vista(m.group(3)) + m.group(2)), n)

# 5) Sospechosos: nombres de vista sueltos (ternarios, arrays) para revisar.
sospechosos = {}
for p, s in contenido.items():
    for pref, _ in PREFIJOS:
        for m in re.finditer(r"['\"](" + re.escape(pref) + r"(?:\.[a-z0-9_.-]+)?)['\"]", s):
            ctx = s[max(0, m.start() - 40):m.end() + 5].replace('\n', ' ')
            if not LLAMADA.search(ctx) and 'route(' not in ctx and '->name(' not in ctx and 'middleware' not in ctx and 'routeIs' not in ctx:
                sospechosos.setdefault(p, []).append(ctx.strip())

plan = {'moves': moves + [(s, d) for s, d, _, _ in VISTAS], 'edits': edits, 'inserts': inserts, 'sospechosos': sospechosos}
with io.open(os.path.join(L, 'scripts/modulos/plan_crmbots.json'), 'w', encoding='utf-8') as f:
    json.dump(plan, f, ensure_ascii=False, indent=1)
print(f'movimientos: {len(plan["moves"])}  archivos con edits: {len(edits)}  edits: {sum(len(v) for v in edits.values())}  inserts: {len(inserts)}')
print('\n=== inserts ===')
for p, v in inserts.items(): print(f'  {p}: ' + ', '.join(x[4:-1].split(B)[-1] for x in v))
print('\n=== sospechosos (vistas sueltas) ===')
for p, v in sospechosos.items():
    for c in v[:3]: print(f'  {p}: {c[:110]}')

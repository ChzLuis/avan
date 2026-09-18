# -*- coding: utf-8 -*-
# Plan del paso "Client al CRM" (tras los 8 modulos): MODULE_OWNERSHIP dice que
# `Client` es del CRM. Van ClientController (panel bixoadmin/clients, pipeline
# y la pantalla de clientes dentro del portal fiscal), el modelo Client y sus
# vistas. Hereda todo el metodo de plan_ventas.py (incluido el paso 3b).
import io, os, re, json, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
B = chr(92)
def ns(*p): return B.join(p)
def fq(path_php):
    rel = path_php[4:] if path_php.startswith('app/') else path_php
    return 'App' + B + rel[:-4].replace('/', B)

moves = []; clases = []; renombrados = []
def mv(src, dst, clase_nueva=None):
    if not os.path.exists(os.path.join(L, src)): raise SystemExit('no existe ' + src)
    moves.append((src, dst))
    clases.append((fq(src), fq(dst)))
    if clase_nueva:
        renombrados.append((dst, os.path.basename(src)[:-4], clase_nueva))

T = 'app/Modules/Crm'
mv('app/Http/Controllers/ClientController.php', f'{T}/Controllers/ClientController.php')
mv('app/Models/Client.php', f'{T}/Models/Client.php')
V = 'resources/views'
VISTAS = [
    (f'{V}/clients',              f'{T}/Views/clients',              'clients'),
    (f'{V}/facturacion/clientes', f'{T}/Views/facturacion/clientes', 'facturacion.clientes'),
]
for src, dst, _ in VISTAS:
    if not os.path.exists(os.path.join(L, src)): raise SystemExit('no existe ' + src)
VISTAS_REALES = set()
for src, dst, pref in VISTAS:
    if os.path.isfile(os.path.join(L, src)): VISTAS_REALES.add(pref); continue
    for dp, dn, fn in os.walk(os.path.join(L, src)):
        for f in fn:
            if f.endswith('.blade.php'):
                rel = os.path.relpath(os.path.join(dp, f), os.path.join(L, src)).replace(B, '/')[:-10]
                VISTAS_REALES.add(pref + '.' + rel.replace('/', '.'))

MOVIDOS = {os.path.basename(s)[:-4] for s, _ in moves}
def namespace_de(dst): return fq(dst).rsplit(B, 1)[0]

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
    for src, dst, _ in VISTAS:
        if p == src or p.startswith(src + '/'): return dst + p[len(src):]
    return p

# 1) FQCN de clases movidas.
for p, s in contenido.items():
    d = destino(p)
    for viejo, nuevo in sorted(clases, key=lambda x: -len(x[0])):
        n = contar_bounded(s, viejo)
        if n: add_edit(d, viejo, nuevo, n)

# 2) Namespace de cada movido + imports que pierde.
CORE_MODELS = {f[:-4] for f in os.listdir(os.path.join(L, 'app/Models')) if f.endswith('.php')} - MOVIDOS
CORE_SUPPORT = {f[:-4] for f in os.listdir(os.path.join(L, 'app/Support')) if f.endswith('.php')} - MOVIDOS
CORE_CTRL = {f[:-4] for f in os.listdir(os.path.join(L, 'app/Http/Controllers')) if f.endswith('.php')} - MOVIDOS
NUEVO_DE = {os.path.basename(s_)[:-4]: fq(d) for s_, d in moves}
OTROS = {}
for mod in ('Personas', 'Control', 'Inventario', 'Finanzas', 'Crm', 'Bots', 'Tienda', 'Catalogo', 'Ventas', 'Operaciones'):
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
            if NUEVO_DE[x].rsplit(B, 1)[0] != ns_nuevo: nuevos.append('use ' + NUEVO_DE[x] + ';')
            continue
        if ns_viejo == ns('App','Models') and x in CORE_MODELS: nuevos.append('use ' + ns('App','Models',x) + ';')
        elif ns_viejo == ns('App','Support') and x in CORE_SUPPORT: nuevos.append('use ' + ns('App','Support',x) + ';')
        elif ns_viejo == ns('App','Http','Controllers') and x in CORE_CTRL: nuevos.append('use ' + ns('App','Http','Controllers',x) + ';')
        elif x in OTROS and ns_viejo == ns('App','Models'): nuevos.append('use ' + OTROS[x] + ';')
    if nuevos: inserts[dst] = sorted(set(nuevos))

# 2b) Renombrados: todo uso por NOMBRE CORTO (declaracion, ::class, tipos) en
#     los archivos que importan la clase o comparten su namespace viejo. Cada
#     edit lleva el caracter previo (espacio, '[', '(', ...) para no pisar el
#     FQCN nuevo, que termina en el mismo nombre.
for dst, viejo, nuevo in renombrados:
    fq_viejo = fq(src_de_dst[dst]); ns_viejo = fq_viejo.rsplit(B, 1)[0]
    for p, s in contenido.items():
        importa = re.search(r'^use ' + re.escape(fq_viejo) + r';', s, re.M)
        mismo_ns = re.search(r'^namespace ' + re.escape(ns_viejo) + r';', s, re.M)
        if not importa and not mismo_ns: continue
        por_prev = {}
        for m in re.finditer(r'(?<![A-Za-z0-9_' + re.escape(B) + r'])' + viejo + r'(?![A-Za-z0-9_])', s):
            prev = s[m.start() - 1] if m.start() > 0 else ''
            por_prev[prev] = por_prev.get(prev, 0) + 1
        for prev, n in por_prev.items():
            add_edit(destino(p), prev + viejo, prev + nuevo, n)

# 3) Modelos de Core que referencian modelos movidos sin importar.
for p, s in contenido.items():
    if p in dst_de_src or not p.startswith('app/Models/'): continue
    importados = set(re.findall(r'^use [^;]*?([A-Za-z0-9_]+);', s, re.M))
    usados = {x for t in TOKEN.findall(s) for x in t if x}
    faltan = [x for x in sorted(MOVIDOS) if x in usados and x not in importados and NUEVO_DE[x].split(B)[-2] == 'Models']
    if faltan: inserts[p] = ['use ' + NUEVO_DE[x] + ';' for x in faltan]

# 3b) Archivos de Core que comparten el namespace VIEJO de un movido y lo usan
#     por nombre corto (p. ej. App\Support\AvisosPortal usaba Cobranza y
#     OrderFlow sin `use`): al irse el movido, pierden la resolucion implicita.
#     Hueco descubierto en Ventas (3 archivos); antes solo se cubrian Models.
for p, s in contenido.items():
    if p in dst_de_src: continue
    m = re.search(r'^namespace ([^;]+);', s, re.M)
    if not m: continue
    importados = set(re.findall(r'^use [^;]*?([A-Za-z0-9_]+);', s, re.M))
    faltan = []
    for src, dst in moves:
        corto = os.path.basename(src)[:-4]
        if fq(src).rsplit(B, 1)[0] != m.group(1) or corto in importados: continue
        if re.search(r'(?<![A-Za-z0-9_' + re.escape(B) + r'])' + corto + r'(?:::|\(|\s+\$)', s):
            faltan.append('use ' + fq(dst) + ';')
    if faltan: inserts[p] = sorted(set(inserts.get(p, []) + faltan))

# 4) Vistas por nombre: (a) dentro de llamadas conocidas, (b) literales sueltos por contexto.
PREFIJOS = sorted([pref for _, _, pref in VISTAS if pref], key=lambda x: -len(x))
def renombrar_vista(nombre):
    for pref in PREFIJOS:
        if nombre == pref or nombre.startswith(pref + '.'):
            return 'crm::' + nombre
    return None
LLAMADA = re.compile(r"""(view|loadView|assertViewIs|View::make|@include|@extends|@includeIf|@includeWhen|@component|@each|markdown|->view|->exists|View::exists|->first)\(\s*(['"])([a-z0-9_.-]+)\2""")
LITERAL = re.compile(r"""(\S+[ \t]*)?(['"])([a-z0-9_.-]+)\2""")
CONTEXTO_RUTA = re.compile(r"""findOrCreate|syncPermissions|givePermissionTo|hasPermissionTo|Permission|permiso|route\(|->name\(|routeIs\(|Route::|currentRouteName|===|!==|'as'\s*=>|starts_with|startsWith|->to\(|redirect\(|url\(|route_name|\$routeName|'route'\s*=>|"route"\s*=>|=>\s*\[?\s*'route'|'r'\s*=>|Rule::|permission|can\(""")
decisiones = []
for p, s in contenido.items():
    d = destino(p)
    en_llamada = set()
    for m in LLAMADA.finditer(s):
        nuevo = renombrar_vista(m.group(3))
        if not nuevo: continue
        en_llamada.add(m.start(3))
        viejo_txt = m.group(0)
        nuevo_txt = viejo_txt[:m.start(2) - m.start()] + m.group(2) + nuevo + m.group(2)
        add_edit(d, viejo_txt, nuevo_txt, None)
    sueltos = {}
    for m in LITERAL.finditer(s):
        nombre = m.group(3)
        if m.start(3) in en_llamada: continue
        nuevo = renombrar_vista(nombre)
        if not nuevo or '.' not in nombre: continue
        if nombre not in VISTAS_REALES:
            decisiones.append(('no-vista', p, s[:m.start()].count(chr(10)) + 1, nombre, s[max(0, m.start() - 30):m.start()].replace(chr(10), ' ')))
            continue
        antes = s[max(0, m.start() - 45):m.start(2)]
        linea = s[:m.start()].count('\n') + 1
        if CONTEXTO_RUTA.search(antes) or nombre in ('public.', 'storefront.', 'public', 'storefront'):
            decisiones.append(('ruta ', p, linea, nombre, antes[-30:].replace('\n', ' ')))
            continue
        viejo_txt = (m.group(1) or '') + m.group(2) + nombre + m.group(2)
        nuevo_txt = (m.group(1) or '') + m.group(2) + nuevo + m.group(2)
        sueltos.setdefault(viejo_txt, [nuevo_txt, 0])
        sueltos[viejo_txt][1] += 1
        decisiones.append(('VISTA', p, linea, nombre, antes[-30:].replace('\n', ' ')))
    for viejo_txt, (nuevo_txt, n) in sueltos.items():
        total = contar_bounded(s, viejo_txt)
        if total != n:
            decisiones.append(('!!MIX', p, 0, viejo_txt, f'{n} de {total} apariciones son vista: resolver a mano'))
            continue
        add_edit(d, viejo_txt, nuevo_txt, n)

# 4c) Fusionar edits de llamadas con n=None → conteo exacto real (evita duplicados por el mismo texto).
for p, lst in edits.items():
    vistos = {}
    nuevos = []
    for e in lst:
        if e['n'] is None:
            if e['old'] in vistos: continue
            vistos[e['old']] = True
            origen = src_de_dst.get(p, p)
            for src, dst, _ in VISTAS:
                if p == dst or p.startswith(dst + '/'): origen = src + p[len(dst):]
            e['n'] = contar_bounded(contenido[origen], e['old'])
        nuevos.append(e)
    edits[p] = nuevos

# 5) EXTRA: lecturas de vistas por ruta de disco, reescritas automaticamente.
DISCO = re.compile(r"resource_path\('views/([a-z0-9_./-]+)")
for p, s_ in contenido.items():
    por_old = {}
    for m in DISCO.finditer(s_):
        rel = 'resources/views/' + m.group(1)
        for src, dst, _ in VISTAS:
            if rel == src or rel.startswith(src + '/'):
                old = m.group(0)
                new = "app_path('Modules/Crm/Views/" + m.group(1)
                por_old[old] = (new, por_old.get(old, (new, 0))[1] + 1)
    for old, (new, n) in por_old.items():
        add_edit(destino(p), old, new, n)

# 6) Avisos: includes de componentes por nombre de vista y namespace viejo suelto.
avisos = []
for p, s in contenido.items():
    if re.search(r'views/(clients|agenda|mapa)', s) and 'app/Modules' not in p and 'resource_path' not in s: avisos.append(f'{p}: menciona vistas de ventas por ruta sin resource_path')
    

plan = {'moves': moves + [(s, d) for s, d, _ in VISTAS], 'edits': edits, 'inserts': inserts, 'decisiones': decisiones, 'avisos': avisos}
with io.open(os.path.join(L, 'scripts/modulos/plan_client.json'), 'w', encoding='utf-8') as f:
    json.dump(plan, f, ensure_ascii=False, indent=1)
print(f'movimientos: {len(plan["moves"])}  archivos con edits: {len(edits)}  edits: {sum(len(v) for v in edits.values())}  inserts: {len(inserts)}')
print('\n=== inserts ===')
for p, v in inserts.items(): print(f'  {p}: ' + ', '.join(x[4:-1].split(B)[-1] for x in v))
print('\n=== decisiones sobre literales sueltos (VISTA se renombra, ruta no) ===')
for k, p, linea, nombre, ctx in decisiones:
    if k not in ('ruta ', 'no-vista'): print(f'  {k} {p}:{linea} {nombre}   <- {ctx}')
print(f'  ({sum(1 for d in decisiones if d[0] == "ruta ")} como ruta y {sum(1 for d in decisiones if d[0] == "no-vista")} sin vista real: no se tocan)')
print('  con prefijo pero sin vista real (muestra):', sorted({d[3] for d in decisiones if d[0] == 'no-vista'})[:25])
RUTAS_JSON = 'C:/Users/luich/AppData/Local/Temp/claude/c--xampp-htdocs-avan/500e9f7c-0ad1-4d49-990b-8bb6fb31d7d3/scratchpad/rutas.json'
if os.path.exists(RUTAS_JSON):
    nombres_ruta = {x['name'] for x in json.load(open(RUTAS_JSON)) if x['name']}
    print('  clasificados como ruta pero que NO son nombre de ruta real (revisar):')
    for k, p, linea, nombre, ctx in decisiones:
        if k == 'ruta ' and nombre not in nombres_ruta: print(f'    ?? {p}:{linea} {nombre}   <- {ctx}')
print('\n=== avisos ===')
for a in avisos: print('  ' + a)

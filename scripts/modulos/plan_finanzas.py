# -*- coding: utf-8 -*-
# Genera el PLAN de la mudanza de Finanzas leyendo el codigo real: que archivos
# se mueven, que cadenas hay que reemplazar en cada archivo y cuantas veces
# aparecen. No toca nada: escribe plan_finanzas.json y un resumen legible.
# mover_finanzas.py aplica ese plan volviendo a verificar cada conteo.
import io, os, re, json, sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
M = 'app/Modules/Finanzas'
B = chr(92)
def ns(*p): return B.join(p)

# ── Que se mueve ───────────────────────────────────────────────────────────
MODELOS = ['Invoice', 'InvoiceItem', 'Payment', 'ReceivableTerm', 'Caja', 'CajaMovimiento',
           'Certificado', 'LecturaComprobante', 'GuiaRemision', 'GuiaRemisionItem']
CTRL_RAIZ = ['InvoiceController', 'PaymentController', 'CajaController', 'CxcController',
             'CertificadoController', 'LectorComprobanteController']
CTRL_FACT = ['AuthController', 'DashboardController', 'GuiaRemisionController', 'NotaController']
SOPORTE = ['ApisPeruService', 'NubefactService', 'CatalogoDocumentos', 'Ledger']
JOBS = ['SendInvoiceToSunat', 'DarDeBajaEnSunat', 'EnviarGuiaASunat']
COMANDOS = ['ArchivarComprobantes', 'ReintentarComprobantes']

def listar(d):
    p = os.path.join(L, d)
    return sorted(f[:-4] for f in os.listdir(p) if f.endswith('.php'))
SUNAT = listar('app/Support/Sunat')
LECTOR = listar('app/Support/Lector')

moves = []      # (src, dst)
clases = []     # (fqcn_viejo, fqcn_nuevo)  -- sin barra inicial
def mv_clase(src, dst, viejo, nuevo):
    moves.append((src, dst)); clases.append((viejo, nuevo))

for c in MODELOS:
    mv_clase(f'app/Models/{c}.php', f'{M}/Models/{c}.php', ns('App','Models',c), ns('App','Modules','Finanzas','Models',c))
for c in CTRL_RAIZ:
    mv_clase(f'app/Http/Controllers/{c}.php', f'{M}/Controllers/{c}.php', ns('App','Http','Controllers',c), ns('App','Modules','Finanzas','Controllers',c))
RENOMBRADOS = []   # (dst, clase_vieja, clase_nueva): hay que renombrar tambien la declaracion
for c in CTRL_FACT:
    # Se renombran con prefijo para que el nombre diga que son del portal fiscal
    # y no choquen con los de la raiz (AuthController, DashboardController).
    nuevo = 'Facturacion' + c if c in ('AuthController', 'DashboardController') else c
    mv_clase(f'app/Http/Controllers/Facturacion/{c}.php', f'{M}/Controllers/{nuevo}.php', ns('App','Http','Controllers','Facturacion',c), ns('App','Modules','Finanzas','Controllers',nuevo))
    if nuevo != c:
        RENOMBRADOS.append((f'{M}/Controllers/{nuevo}.php', c, nuevo))
for c in SOPORTE:
    mv_clase(f'app/Support/{c}.php', f'{M}/Support/{c}.php', ns('App','Support',c), ns('App','Modules','Finanzas','Support',c))
for c in SUNAT:
    mv_clase(f'app/Support/Sunat/{c}.php', f'{M}/Support/Sunat/{c}.php', ns('App','Support','Sunat',c), ns('App','Modules','Finanzas','Support','Sunat',c))
for c in LECTOR:
    mv_clase(f'app/Support/Lector/{c}.php', f'{M}/Support/Lector/{c}.php', ns('App','Support','Lector',c), ns('App','Modules','Finanzas','Support','Lector',c))
for c in JOBS:
    mv_clase(f'app/Jobs/{c}.php', f'{M}/Jobs/{c}.php', ns('App','Jobs',c), ns('App','Modules','Finanzas','Jobs',c))
for c in COMANDOS:
    mv_clase(f'app/Console/Commands/{c}.php', f'{M}/Commands/{c}.php', ns('App','Console','Commands',c), ns('App','Modules','Finanzas','Commands',c))

# Namespaces: el de cada archivo movido y el nuevo.
NS_POR_DESTINO = {
    'Models': ns('App','Modules','Finanzas','Models'), 'Controllers': ns('App','Modules','Finanzas','Controllers'),
    'Support': ns('App','Modules','Finanzas','Support'), 'Support/Sunat': ns('App','Modules','Finanzas','Support','Sunat'),
    'Support/Lector': ns('App','Modules','Finanzas','Support','Lector'), 'Jobs': ns('App','Modules','Finanzas','Jobs'),
    'Commands': ns('App','Modules','Finanzas','Commands'),
}
def destino_de(dst):
    rel = dst[len(M)+1:]
    for k in ('Support/Sunat', 'Support/Lector', 'Models', 'Controllers', 'Support', 'Jobs', 'Commands'):
        if rel.startswith(k + '/'): return k
    raise SystemExit('destino desconocido ' + dst)

# Vistas: carpetas enteras y subcarpetas fiscales de facturacion/.
VISTAS = [
    ('resources/views/invoices',      f'{M}/Views/invoices',      'invoices'),
    ('resources/views/cxc',           f'{M}/Views/cxc',           'cxc'),
    ('resources/views/certificados',  f'{M}/Views/certificados',  'certificados'),
    ('resources/views/facturacion/facturas', f'{M}/Views/facturacion/facturas', 'facturacion.facturas'),
    ('resources/views/facturacion/guias',    f'{M}/Views/facturacion/guias',    'facturacion.guias'),
    ('resources/views/facturacion/layouts',  f'{M}/Views/facturacion/layouts',  'facturacion.layouts'),
    ('resources/views/facturacion/portada.blade.php',       f'{M}/Views/facturacion/portada.blade.php',       'facturacion.portada'),
    ('resources/views/facturacion/dashboard.blade.php',     f'{M}/Views/facturacion/dashboard.blade.php',     'facturacion.dashboard'),
    ('resources/views/facturacion/login.blade.php',         f'{M}/Views/facturacion/login.blade.php',         'facturacion.login'),
    ('resources/views/facturacion/login-general.blade.php', f'{M}/Views/facturacion/login-general.blade.php', 'facturacion.login-general'),
    # Se quedan en resources/views/facturacion/: clientes/, pedidos/, cotizaciones/
    # (pantallas de Ventas que se ven dentro del portal fiscal).
]
for src, dst, _ in VISTAS:
    if not os.path.exists(os.path.join(L, src)):
        raise SystemExit('no existe ' + src)

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
    """Apariciones de needle no seguidas de un caracter identificador
    (Invoice no debe casar con InvoiceItem)."""
    n = 0; i = 0
    while True:
        j = s.find(needle, i)
        if j < 0: return n
        k = j + len(needle)
        if k >= len(s) or not IDENT.match(s[k]): n += 1
        i = k

edits = {}     # path -> [ {old,new,n} ]
def add_edit(path, old, new, n):
    edits.setdefault(path, []).append({'old': old, 'new': new, 'n': n})

src_de_dst = {dst: src for src, dst in moves}
dst_de_src = {src: dst for src, dst in moves}
contenido = {p: io.open(os.path.join(L, p), encoding='utf-8', newline='').read() for p in archivos()}

def destino(p):
    """Ruta FINAL de un archivo: la nueva si se mueve (clase o vista dentro
    de una carpeta movida), la misma si no. Todo edit se indexa por esta ruta,
    porque se aplica despues del git mv."""
    if p in dst_de_src:
        return dst_de_src[p]
    for src, dst, _ in VISTAS:
        if p == src or p.startswith(src + '/'):
            return dst + p[len(src):]
    return p

# 1) FQCN de clases movidas, en TODOS los archivos (los movidos incluidos).
for p, s in contenido.items():
    destino_path = destino(p)   # el edit se aplica en la ruta final
    for viejo, nuevo in clases:
        n = contar_bounded(s, viejo)
        if n: add_edit(destino_path, viejo, nuevo, n)

# 2) Namespace de cada archivo movido + imports que pierde al cambiar de namespace.
CORE_MODELS = {f[:-4] for f in os.listdir(os.path.join(L, 'app/Models')) if f.endswith('.php')} - set(MODELOS)
CORE_SUPPORT = {f[:-4] for f in os.listdir(os.path.join(L, 'app/Support')) if f.endswith('.php')} - set(SOPORTE)
OTROS_MODULOS = {}   # Clase -> FQCN de modulos ya movidos
for mod in ('Personas', 'Control', 'Inventario'):
    for sub in ('Models', 'Support', 'Controllers'):
        d = os.path.join(L, 'app/Modules', mod, sub)
        if os.path.isdir(d):
            for f in os.listdir(d):
                if f.endswith('.php'): OTROS_MODULOS[f[:-4]] = ns('App','Modules',mod,sub,f[:-4])

# Tambien `extends X` / `implements X`: sin esto, un controlador de la raiz que
# hereda de Controller sin importarlo (mismo namespace) se quedaba sin `use`
# al cambiar de namespace (paso en Finanzas).
TOKEN = re.compile(r'\b([A-Z][A-Za-z0-9_]*)\s*(?:::|\$|\(|\))|(?:belongsTo|hasMany|hasOne|belongsToMany|morphMany|morphTo|instanceof|new|extends|implements)\s*\(?\s*([A-Z][A-Za-z0-9_]*)|\(\s*([A-Z][A-Za-z0-9_]*)\s+\$')
inserts = {}   # path -> [use lines]
for src, dst in moves:
    s = contenido[src]
    m = re.search(r'^namespace ([^;]+);', s, re.M)
    if not m: raise SystemExit('sin namespace: ' + src)
    ns_viejo = m.group(1)
    ns_nuevo = NS_POR_DESTINO[destino_de(dst)]
    add_edit(dst, 'namespace ' + ns_viejo + ';', 'namespace ' + ns_nuevo + ';', 1)
    importados = set(re.findall(r'^use [^;]*?([A-Za-z0-9_]+)(?: as [A-Za-z0-9_]+)?;', s, re.M))
    usados = set()
    for t in TOKEN.findall(s):
        for x in t:
            if x: usados.add(x)
    nuevos_use = []
    for x in sorted(usados):
        if x in importados or x in MODELOS or x in SOPORTE or x in SUNAT or x in LECTOR: continue
        if ns_viejo == ns('App','Models') and x in CORE_MODELS:
            nuevos_use.append('use ' + ns('App','Models',x) + ';')
        elif ns_viejo.startswith(ns('App','Support')) and x in CORE_SUPPORT:
            nuevos_use.append('use ' + ns('App','Support',x) + ';')
        elif ns_viejo == ns('App','Support','Sunat') and x in SOPORTE + SUNAT:
            pass
        elif x in OTROS_MODULOS and ns_viejo.startswith(ns('App','Models')) is False and False:
            pass
        elif x == 'Controller' and ns_viejo.startswith(ns('App','Http','Controllers')):
            nuevos_use.append('use ' + ns('App','Http','Controllers','Controller') + ';')
    # Los modelos de Core referenciados sin importar desde controladores ya venian con use.
    if nuevos_use: inserts[dst] = nuevos_use

# 2b) Archivos renombrados: la declaracion `class X` tambien cambia (PSR-4:
#     clase = archivo) y sus autorreferencias `X::class`.
for dst, viejo, nuevo in RENOMBRADOS:
    s = contenido[src_de_dst[dst]]
    add_edit(dst, 'class ' + viejo + ' ', 'class ' + nuevo + ' ', 1)
    n = s.count(viejo + '::class')
    if n:
        add_edit(dst, viejo + '::class', nuevo + '::class', n)

# 3) Archivos de Core que referencian modelos movidos SIN importar (mismo namespace antes).
for p, s in contenido.items():
    if p in dst_de_src: continue
    if not p.startswith('app/Models/'): continue
    importados = set(re.findall(r'^use [^;]*?([A-Za-z0-9_]+);', s, re.M))
    usados = set(x for t in TOKEN.findall(s) for x in t if x)
    faltan = [c for c in MODELOS if c in usados and c not in importados]
    if faltan:
        inserts[p] = ['use ' + ns('App','Modules','Finanzas','Models',c) + ';' for c in faltan]

# 4) Vistas: llamadas por nombre (solo formas que designan VISTAS, no rutas).
LLAMADA = re.compile(r"""(view|loadView|assertViewIs|View::make|@include|@extends|@includeIf|@includeWhen|@component|@each|markdown|->view)\(\s*(['"])([a-z0-9_.-]+)\2""")
vista_nueva = {}
for src, dst, prefijo in VISTAS:
    vista_nueva[prefijo] = 'finanzas::' + prefijo
def renombrar_vista(nombre):
    for pref in sorted(vista_nueva, key=len, reverse=True):
        if nombre == pref or nombre.startswith(pref + '.'):
            return 'finanzas::' + nombre
    return None
vistas_edits = {}
for p, s in contenido.items():
    destino_path = destino(p)
    for m in LLAMADA.finditer(s):
        nuevo = renombrar_vista(m.group(3))
        if nuevo:
            viejo_txt = m.group(0)
            nuevo_txt = viejo_txt.replace(m.group(2) + m.group(3) + m.group(2), m.group(2) + nuevo + m.group(2))
            vistas_edits.setdefault(destino_path, {})
            vistas_edits[destino_path][viejo_txt] = vistas_edits[destino_path].get(viejo_txt, 0) + 1
for p, d in vistas_edits.items():
    for viejo_txt, n in d.items():
        m = LLAMADA.match(viejo_txt)
        nuevo_txt = viejo_txt.replace(m.group(2) + m.group(3) + m.group(2), m.group(2) + renombrar_vista(m.group(3)) + m.group(2))
        add_edit(p, viejo_txt, nuevo_txt, n)

# 4b) Nombres de vista en TERNARIOS (no van dentro de view(...) y el paso 4 no
#     los ve): el PDF de facturas y de guias elige la plantilla asi. Se
#     reemplazan con su contexto (`? '...'` / `: '...'`) para no pisar las
#     llamadas ya cubiertas. Si alguno no aparece, el plan se detiene: mejor
#     saberlo ahora que un "View not found" al imprimir.
EXTRA = [
    ('app/Http/Controllers/InvoiceController.php',            "? 'invoices.pdf-clasico'",             "? 'finanzas::invoices.pdf-clasico'"),
    ('app/Http/Controllers/InvoiceController.php',            ": 'invoices.pdf'",                     ": 'finanzas::invoices.pdf'"),
    ('app/Http/Controllers/Facturacion/GuiaRemisionController.php', "? 'facturacion.guias.pdf-clasico'", "? 'finanzas::facturacion.guias.pdf-clasico'"),
    ('app/Http/Controllers/Facturacion/GuiaRemisionController.php', ": 'facturacion.guias.pdf-simple'",  ": 'finanzas::facturacion.guias.pdf-simple'"),
    ('tests/Feature/FacturaPlantillaClasicaTest.php',         "? 'invoices.pdf-clasico'",             "? 'finanzas::invoices.pdf-clasico'"),
    ('tests/Feature/FacturaPlantillaClasicaTest.php',         ": 'invoices.pdf'",                     ": 'finanzas::invoices.pdf'"),
]
for src_path, viejo, nuevo in EXTRA:
    n = contenido[src_path].count(viejo)
    if n < 1:
        raise SystemExit(f'EXTRA no encontrado en {src_path}: {viejo!r}')
    add_edit(dst_de_src.get(src_path, src_path), viejo, nuevo, n)

# 5) Nombres de vista dentro de arrays (view()->first / exists) o strings sueltos: solo se listan para revisar.
sospechosos = {}
for p, s in contenido.items():
    for pref in vista_nueva:
        for m in re.finditer(r"['\"](" + re.escape(pref) + r"(?:\.[a-z0-9_.-]+)?)['\"]", s):
            nombre = m.group(1)
            inicio = max(0, m.start() - 40)
            ctx = s[inicio:m.end() + 5].replace('\n', ' ')
            if not LLAMADA.search(ctx) and 'route(' not in ctx and "->name(" not in ctx and 'middleware' not in ctx:
                sospechosos.setdefault(p, []).append(ctx.strip())

plan = {
    'moves': moves + [(s, d) for s, d, _ in VISTAS],
    'edits': edits,
    'inserts': inserts,
    'sospechosos': sospechosos,
}
with io.open(os.path.join(L, 'scripts/modulos/plan_finanzas.json'), 'w', encoding='utf-8') as f:
    json.dump(plan, f, ensure_ascii=False, indent=1)

print(f'movimientos: {len(plan["moves"])}  archivos con edits: {len(edits)}  edits: {sum(len(v) for v in edits.values())}  inserts: {len(inserts)}')
print('\n=== archivos externos con mas reemplazos ===')
externos = [(p, sum(e["n"] for e in v)) for p, v in edits.items() if not p.startswith(M + '/')]
for p, n in sorted(externos, key=lambda x: -x[1])[:25]:
    print(f'  {n:3d}  {p}')
print(f'  ... {len(externos)} archivos externos en total')
print('\n=== inserts (use que hay que añadir) ===')
for p, v in inserts.items():
    print(f'  {p}: ' + ', '.join(x[4:-1].split(B)[-1] for x in v))
print('\n=== nombres de vista sospechosos (fuera de llamadas conocidas) ===')
for p, v in sospechosos.items():
    for c in v[:3]:
        print(f'  {p}: {c[:110]}')
print('\nplan: scripts/modulos/plan_finanzas.json')

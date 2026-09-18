# -*- coding: utf-8 -*-
# Genera el plan de la mudanza de Tienda/ (modulo 6/8): la tienda publica, el
# Constructor, dominios, plantillas de diseño, promociones/cupones/reseñas,
# libro de reclamaciones y las vistas storefront/public. Escribe
# plan_tienda.json; mover_modulo.py lo aplica verificando cada conteo.
#
# Particularidades de este modulo (por eso no basta plan_crmbots.py):
#  - Los nombres de vista `public.*` CHOCAN con nombres de ruta `public.*`.
#    Un literal fuera de view()/@include se clasifica por contexto (route(,
#    ->name(, routeIs(, ===...) y se renombra solo si es vista. Todas las
#    decisiones se imprimen para revisarlas a ojo antes de aplicar.
#  - Hay nombres de vista construidos por concatenacion/interpolacion
#    (HeaderPresets, StorefrontLayoutPacks, computienda, un test) y rutas de
#    DISCO a las vistas (ConstructorAuditor y dos tests): van como EXTRA.
#  - Los componentes anonimos (<x-storefront-*>, <x-store-menu>...) se mueven
#    a Views/components sin cambiar de nombre (el provider registra la ruta).
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

T = 'app/Modules/Tienda'
mv('app/Http/Controllers/PublicController.php', f'{T}/Controllers/TiendaPublicaController.php', 'TiendaPublicaController')
for c in ['StoreBuilderController', 'StoreExperienceController', 'StoreNavigationController', 'StorePageController',
          'DesignTemplateController', 'PromotionController', 'ComplaintController', 'CatalogProfileController']:
    mv(f'app/Http/Controllers/{c}.php', f'{T}/Controllers/{c}.php')
for m in ['Coupon', 'Promotion', 'Review', 'StoreCatalogProfile', 'StoreMenu', 'StoreMenuItem', 'StorePage', 'StorePopup',
          'StoreSection', 'DesignTemplate', 'DesignTemplateVersion', 'ProjectTemplate', 'ContactMessage', 'Complaint']:
    mv(f'app/Models/{m}.php', f'{T}/Models/{m}.php')
for s in ['StorefrontLayoutPacks', 'StorefrontNavigation', 'StorefrontSections', 'StorefrontTheme', 'StorefrontThemePresets',
          'HeaderPresets', 'DesignerIcons', 'ContenidoEjemplo', 'CatalogTemplates', 'CategoryIcons', 'IconosCategoria']:
    mv(f'app/Support/{s}.php', f'{T}/Support/{s}.php')
for f in sorted(os.listdir(os.path.join(L, 'app/Storefront'))):
    if f.endswith('.php'): mv(f'app/Storefront/{f}', f'{T}/Storefront/{f}')
for c in ['AuditarConstructor', 'ConsolidarMotoresTienda', 'FusionarModelos']:
    mv(f'app/Console/Commands/{c}.php', f'{T}/Commands/{c}.php')

# (src, dst, prefijo de nombre de vista o None si es componente anonimo)
V = 'resources/views'
VISTAS = [
    (f'{V}/public',                          f'{T}/Views/public',                          'public'),
    (f'{V}/storefront',                      f'{T}/Views/storefront',                      'storefront'),
    (f'{V}/promotions',                      f'{T}/Views/promotions',                      'promotions'),
    (f'{V}/complaints',                      f'{T}/Views/complaints',                      'complaints'),
    (f'{V}/settings/builder',                f'{T}/Views/settings/builder',                'settings.builder'),
    (f'{V}/settings/design-templates.blade.php', f'{T}/Views/settings/design-templates.blade.php', 'settings.design-templates'),
    (f'{V}/layouts/storefront.blade.php',    f'{T}/Views/layouts/storefront.blade.php',    'layouts.storefront'),
]
for p in ['catalog-profiles', 'store-navigation-builder', 'store-menu-item', 'institutional-pages']:
    VISTAS.append((f'{V}/settings/partials/{p}.blade.php', f'{T}/Views/settings/partials/{p}.blade.php', 'settings.partials.' + p))
COMPONENTES = ['storefront', 'computienda', 'store-menu.blade.php', 'public-popup.blade.php', 'public-store-runtime.blade.php',
               'storefront-home-sections.blade.php', 'storefront-home-skins.blade.php', 'storefront-motion.blade.php', 'storefront-shapes.blade.php']
for c in COMPONENTES:
    # Sin prefijo de tag (<x-...> no cambia), pero @include('components.x') y
    # view('components.x') si deben pasar a tienda::components.x
    VISTAS.append((f'{V}/components/{c}', f'{T}/Views/components/{c}', 'components.' + c.replace('.blade.php', '')))
for src, dst, _ in VISTAS:
    if not os.path.exists(os.path.join(L, src)): raise SystemExit('no existe ' + src)

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
for mod in ('Personas', 'Control', 'Inventario', 'Finanzas', 'Crm', 'Bots'):
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

# 4) Vistas por nombre: (a) dentro de llamadas conocidas, (b) literales sueltos por contexto.
PREFIJOS = sorted([pref for _, _, pref in VISTAS if pref], key=lambda x: -len(x))
def renombrar_vista(nombre):
    for pref in PREFIJOS:
        if nombre == pref or nombre.startswith(pref + '.'):
            return 'tienda::' + nombre
    return None
LLAMADA = re.compile(r"""(view|loadView|assertViewIs|View::make|@include|@extends|@includeIf|@includeWhen|@component|@each|markdown|->view|->exists|View::exists|->first)\(\s*(['"])([a-z0-9_.-]+)\2""")
LITERAL = re.compile(r"""(\S+[ \t]*)?(['"])([a-z0-9_.-]+)\2""")
CONTEXTO_RUTA = re.compile(r"""route\(|->name\(|routeIs\(|Route::|currentRouteName|===|!==|'as'\s*=>|starts_with|startsWith|->to\(|redirect\(|url\(|route_name|\$routeName|'route'\s*=>|"route"\s*=>|=>\s*\[?\s*'route'|'r'\s*=>|Rule::|permission|can\(""")
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

# 5) EXTRA: nombres construidos y rutas de disco.
def extra(p, old, new, n=1):
    add_edit(destino(p), old, new, n)
extra('app/Support/StorefrontLayoutPacks.php', '"storefront.partials.{$slot}.{$variant}"', '"tienda::storefront.partials.{$slot}.{$variant}"')
extra('tests/Feature/PiesDePaginaVariantesTest.php', '"storefront.partials.headers.$variante"', '"tienda::storefront.partials.headers.$variante"')
extra('tests/Feature/PiesDePaginaVariantesTest.php', '"storefront.partials.footers.$variante"', '"tienda::storefront.partials.footers.$variante"')
extra('app/Storefront/ConstructorAuditor.php', "'resources/views/settings/builder'", "'app/Modules/Tienda/Views/settings/builder'")
extra('app/Storefront/ConstructorAuditor.php', "        'resources/views/public',\n        'resources/views/components',\n        'resources/views/storefront',\n", "        'resources/views/components',\n")
extra('app/Storefront/ConstructorAuditor.php', "str_contains($rel, 'views/settings/')", "str_contains(strtolower($rel), 'views/settings/')")
extra('app/Storefront/HomePresets.php', 'resources/views/components/storefront-home-skins', 'app/Modules/Tienda/Views/components/storefront-home-skins')
extra('tests/Feature/BloquesInicioTest.php', "resource_path('views/settings/builder/stages/home.blade.php')", "app_path('Modules/Tienda/Views/settings/builder/stages/home.blade.php')")
extra('tests/Feature/BloquesInicioTest.php', "resource_path('views/components/storefront-home-sections.blade.php')", "app_path('Modules/Tienda/Views/components/storefront-home-sections.blade.php')")
extra('tests/Feature/ConstructorCapacidadesRescatadasTest.php', "resource_path('views/settings/builder/'.$archivo)", "app_path('Modules/Tienda/Views/settings/builder/'.$archivo)")

# 6) Avisos: includes de componentes por nombre de vista y namespace viejo suelto.
avisos = []
for p, s in contenido.items():
    for m in re.finditer(r"""['"]components\.(storefront|computienda|store-menu|public-popup|public-store-runtime|storefront-)[a-z0-9_.-]*['"]""", s):
        avisos.append(f'{p}: include de componente por nombre: {m.group(0)}')
    if 'App' + B + 'Storefront' + B + '{' in s: avisos.append(f'{p}: group use de App{B}Storefront')

plan = {'moves': moves + [(s, d) for s, d, _ in VISTAS], 'edits': edits, 'inserts': inserts, 'decisiones': decisiones, 'avisos': avisos}
with io.open(os.path.join(L, 'scripts/modulos/plan_tienda.json'), 'w', encoding='utf-8') as f:
    json.dump(plan, f, ensure_ascii=False, indent=1)
print(f'movimientos: {len(plan["moves"])}  archivos con edits: {len(edits)}  edits: {sum(len(v) for v in edits.values())}  inserts: {len(inserts)}')
print('\n=== inserts ===')
for p, v in inserts.items(): print(f'  {p}: ' + ', '.join(x[4:-1].split(B)[-1] for x in v))
print('\n=== decisiones sobre literales sueltos (VISTA se renombra, ruta no) ===')
for k, p, linea, nombre, ctx in decisiones:
    if k != 'ruta ': print(f'  {k} {p}:{linea} {nombre}   <- {ctx}')
print(f'  ({sum(1 for d in decisiones if d[0] == "ruta ")} literales clasificados como ruta, no se tocan)')
RUTAS_JSON = 'C:/Users/luich/AppData/Local/Temp/claude/c--xampp-htdocs-avan/500e9f7c-0ad1-4d49-990b-8bb6fb31d7d3/scratchpad/rutas.json'
if os.path.exists(RUTAS_JSON):
    nombres_ruta = {x['name'] for x in json.load(open(RUTAS_JSON)) if x['name']}
    print('  clasificados como ruta pero que NO son nombre de ruta real (revisar):')
    for k, p, linea, nombre, ctx in decisiones:
        if k == 'ruta ' and nombre not in nombres_ruta: print(f'    ?? {p}:{linea} {nombre}   <- {ctx}')
print('\n=== avisos ===')
for a in avisos: print('  ' + a)

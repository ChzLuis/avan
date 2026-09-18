# -*- coding: utf-8 -*-
# Aplica el plan que genero plan_finanzas.py (plan_finanzas.json).
#
#   python scripts/modulos/mover_finanzas.py --dry   -> verifica TODOS los conteos
#                                                       contra el codigo actual, sin
#                                                       escribir nada. Si algo no
#                                                       cuadra, lo lista y sale 1.
#   python scripts/modulos/mover_finanzas.py         -> git mv + reemplazos + use.
#
# La regla: o pasa todo en seco, o no se mueve nada. Asi no queda una
# mudanza a medias en el arbol.
import io, os, sys, json, subprocess
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

L = 'C:/xampp/htdocs/avan'
PLAN = os.path.join(L, 'scripts/modulos/plan_finanzas.json')
DRY = '--dry' in sys.argv
B = chr(92)

plan = json.load(io.open(PLAN, encoding='utf-8'))
moves = [tuple(m) for m in plan['moves']]
edits = plan['edits']
inserts = plan['inserts']

# Mismo criterio que el generador: una aparicion cuenta solo si NO va seguida
# de un caracter de identificador. Asi `App\Models\Invoice` no cuenta ni se
# reemplaza dentro de `App\Models\InvoiceItem` (ni Caja dentro de CajaMovimiento).
import re
IDENT = re.compile(r'[A-Za-z0-9_]')
def contar(s, needle):
    n = 0; i = 0
    while True:
        j = s.find(needle, i)
        if j < 0: return n
        k = j + len(needle)
        if k >= len(s) or not IDENT.match(s[k]): n += 1
        i = k
def sustituir(s, old, new):
    out = []; i = 0
    while True:
        j = s.find(old, i)
        if j < 0:
            out.append(s[i:]); return ''.join(out)
        k = j + len(old)
        if k >= len(s) or not IDENT.match(s[k]):
            out.append(s[i:j]); out.append(new)
        else:
            out.append(s[i:k])
        i = k

def origen(path):
    """Ruta actual (antes de mover) de una ruta destino del plan."""
    for src, dst in moves:
        if path == dst: return src
        if path.startswith(dst + '/'): return src + path[len(dst):]
    return path

def leer(p):
    return io.open(os.path.join(L, p), encoding='utf-8', newline='').read()

# ── 1) Verificacion en seco de todo ────────────────────────────────────────
problemas = []
for src, dst in moves:
    if not os.path.exists(os.path.join(L, src)): problemas.append(f'no existe para mover: {src}')
    if os.path.exists(os.path.join(L, dst)): problemas.append(f'ya existe el destino: {dst}')
for path, cambios in edits.items():
    p = origen(path)
    if not os.path.exists(os.path.join(L, p)):
        problemas.append(f'no existe: {p}'); continue
    s = leer(p)
    for c in cambios:
        n = contar(s, c['old'])
        if n != c['n']:
            problemas.append(f'{p}: esperaba {c["n"]} de {c["old"][:70]!r}, hay {n}')
for path in inserts:
    p = origen(path)
    if not os.path.exists(os.path.join(L, p)): problemas.append(f'no existe (insert): {p}')
    elif 'namespace ' not in leer(p): problemas.append(f'sin namespace (insert): {p}')

total_edits = sum(len(v) for v in edits.values())
print(f'plan: {len(moves)} movimientos, {total_edits} reemplazos en {len(edits)} archivos, {len(inserts)} archivos con use nuevos')
if problemas:
    print(f'\n!! {len(problemas)} problemas: no se toca nada')
    for x in problemas[:40]: print('  -', x)
    sys.exit(1)
print('verificacion en seco: OK (todos los conteos cuadran)')
if DRY:
    sys.exit(0)

# ── 2) Aplicar ────────────────────────────────────────────────────────────
def git_mv(src, dst):
    os.makedirs(os.path.dirname(os.path.join(L, dst)), exist_ok=True)
    r = subprocess.run(['git', 'mv', src, dst], cwd=L, capture_output=True, text=True)
    if r.returncode != 0:
        print('ERROR git mv', src, '->', dst, r.stderr.strip()); sys.exit(1)

for src, dst in moves:
    git_mv(src, dst)
print(f'movidos: {len(moves)}')

for path, cambios in edits.items():
    p = os.path.join(L, path)
    s = io.open(p, encoding='utf-8', newline='').read()
    for c in cambios:
        n = contar(s, c['old'])
        if n != c['n']:
            print(f'ERROR tras mover, {path}: esperaba {c["n"]} de {c["old"][:70]!r}, hay {n}'); sys.exit(1)
        s = sustituir(s, c['old'], c['new'])
    io.open(p, 'w', encoding='utf-8', newline='').write(s)
print(f'reemplazos aplicados: {total_edits}')

for path, lineas in inserts.items():
    p = os.path.join(L, path)
    s = io.open(p, encoding='utf-8', newline='').read()
    nl = '\r\n' if '\r\n' in s else '\n'
    i = s.find('namespace ')
    j = s.find(';', i) + 1
    # tras el namespace: salto de linea, en blanco, y los use nuevos
    bloque = nl + nl + nl.join(lineas)
    s = s[:j] + bloque + s[j:]
    io.open(p, 'w', encoding='utf-8', newline='').write(s)
    print(f'use añadidos en {path}: {len(lineas)}')

# Directorios vacios que deja git mv.
for src, _ in moves:
    d = os.path.dirname(os.path.join(L, src))
    while d.startswith(L) and os.path.isdir(d) and not os.listdir(d):
        os.rmdir(d); print('dir vacio borrado:', os.path.relpath(d, L).replace(B, '/'))
        d = os.path.dirname(d)
print('LISTO')

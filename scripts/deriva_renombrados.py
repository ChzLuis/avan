# -*- coding: utf-8 -*-
# Puerta de deriva para archivos RENOMBRADOS: la de deploy_modulos.py solo
# compara rutas iguales, asi que un archivo que ARIN tenia mas nuevo en su
# ruta VIEJA y que la mudanza movio a otra ruta se pisaba sin aviso (paso el
# 2026-09-18 con StorePageController::legal). Compara cada archivo viejo del
# respaldo de ARIN con su destino local y lista los bloques de 3+ lineas que
# ARIN tenia y el local no.
#   python scripts/deriva_renombrados.py <respaldo>   (p. ej. 20260917_214320)
import paramiko, sys, io, os, re, subprocess, difflib
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
LOCAL = 'C:/xampp/htdocs/avan'
sys.path.insert(0, os.path.join(LOCAL, 'scripts'))
from credenciales_deploy import obtener
HOST, USER, PW, BASE = obtener()
RESPALDO = f"{BASE}/_deploy_backups/{sys.argv[1]}"
COMMIT_BASE = 'c208724'

def git(*a):
    return subprocess.run(['git'] + list(a), cwd=LOCAL, capture_output=True, text=True, encoding='utf-8').stdout
# Renombres acumulados desde la base (viejo -> nuevo), siguiendo cadenas.
pares = {}
for l in git('log', '--diff-filter=R', '--name-status', '-M', '--format=', '--reverse', f'{COMMIT_BASE}..HEAD').splitlines():
    partes = l.split('\t')
    if len(partes) == 3 and partes[0].startswith('R'):
        viejo, nuevo = partes[1], partes[2]
        origen = next((o for o, n in pares.items() if n == viejo), viejo)
        pares[origen] = nuevo
pares = {o: n for o, n in pares.items() if o != n and o.endswith('.php')}
print(f"renombres a comparar: {len(pares)}")

c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=22, username=USER, password=PW, timeout=25)
sftp = c.open_sftp()
hallazgos = {}
for viejo, nuevo in pares.items():
    try:
        with sftp.open(f"{RESPALDO}/{viejo}", 'rb') as rf: remoto = rf.read().decode('utf-8', 'replace').replace('\r\n', '\n').split('\n')
    except IOError:
        continue
    destino = os.path.join(LOCAL, nuevo)
    if not os.path.exists(destino): continue
    with open(destino, 'r', encoding='utf-8', errors='replace') as fh: local = fh.read().replace('\r\n', '\n').split('\n')
    perdidas = []
    for tag, i1, i2, _, _ in difflib.SequenceMatcher(None, remoto, local).get_opcodes():
        if tag == 'delete':
            bloque = [l for l in remoto[i1:i2] if l.strip()]
            # Se ignoran los bloques que solo son `use`/namespace (la mudanza los cambia a proposito).
            if len(bloque) >= 3 and not all(re.match(r'\s*(use |namespace )', l) for l in bloque):
                perdidas += bloque
    # Un metodo que ARIN tenia y el local no es perdida aunque difflib lo vea
    # como "replace" (asi se escapo StorePageController::legal). Se comparan
    # los nombres de funciones, que no dependen de como cayo el diff.
    fn_remoto = set(re.findall(r'function\s+(\w+)\s*\(', '\n'.join(remoto)))
    fn_local = set(re.findall(r'function\s+(\w+)\s*\(', '\n'.join(local)))
    for f in sorted(fn_remoto - fn_local):
        perdidas.append(f'[metodo ausente] function {f}(')
    if perdidas: hallazgos[viejo] = (nuevo, perdidas)
sftp.close(); c.close()
print(f"archivos con bloques que ARIN tenia y el local no: {len(hallazgos)}")
for viejo, (nuevo, ls) in hallazgos.items():
    print(f"\n! {viejo} -> {nuevo} ({len(ls)} lineas)")
    for l in ls[:12]: print('    ' + l[:120])

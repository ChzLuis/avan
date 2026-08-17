# -*- coding: utf-8 -*-
# Deploy fijo a ARIN (VPS). Uso:
#   python deploy.py archivo1 [archivo2 ...]           -> sube archivos (rutas relativas al proyecto)
#   python deploy.py --sql "SELECT ..."                -> ejecuta SQL en la BD del VPS
#   python deploy.py --run "comando bash"              -> ejecuta un comando en el VPS
# Hace: puerta de deriva, backup, sube a temporal, php -l, promueve con mv,
#       limpia vistas y verifica que panel y portada respondan 200.
# Salidas: 0 ok · 1 error de sintaxis · 2 salud caida · 3 deriva detectada.
# Con --acepto-deriva se salta la puerta de deriva (solo si es a proposito).
import paramiko, sys, io, time
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

HOST='2.24.200.91'; USER='root'; PW='fXvG6W9JoVq41Nwwf(+1'
BASE='/home/arindg/htdocs/arindg.com'; LOCAL='C:/xampp/htdocs/avan'

c=paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=22, username=USER, password=PW, timeout=25)
def run(cmd,t=90):
    i,o,e=c.exec_command(cmd,timeout=t); return (o.read().decode('utf-8','replace')+e.read().decode('utf-8','replace')).strip()

args=sys.argv[1:]
if not args:
    print("Uso: python deploy.py <archivos...> | --sql \"...\" | --run \"...\""); c.close(); sys.exit(1)

# Modo SQL
if args[0]=='--sql':
    dbn=run(f"grep '^DB_DATABASE' {BASE}/.env|cut -d= -f2"); dbu=run(f"grep '^DB_USERNAME' {BASE}/.env|cut -d= -f2"); dbp=run(f"grep '^DB_PASSWORD' {BASE}/.env|cut -d= -f2")
    sftp=c.open_sftp()
    with sftp.open('/tmp/d.sql','wb') as f: f.write(' '.join(args[1:]).encode('utf-8'))
    sftp.close()
    print(run(f"mysql -u {dbu} -p'{dbp}' {dbn} < /tmp/d.sql 2>&1 | grep -v 'Using a password'"))
    c.close(); sys.exit(0)

# Modo comando
if args[0]=='--run':
    print(run(' '.join(args[1:]), t=180)); c.close(); sys.exit(0)

# Modo deploy de archivos
ACEPTO_DERIVA = '--acepto-deriva' in args
args = [a for a in args if a != '--acepto-deriva']
STAMP=time.strftime('%Y%m%d_%H%M%S'); sftp=c.open_sftp(); ok=True
TMP=f"{BASE}/_deploy_tmp/{STAMP}"

# ── Puerta de deriva ─────────────────────────────────────────────────────
# ARIN diverge del repo local: hay codigo que solo vive en produccion. El
# 2026-08-16 subi un dashboard "correcto" que borro la seccion MULTICANAL de
# ARIN (236 -> 218 lineas) y el panel mostro "Por cobrar S/ 0". El despliegue
# atomico NO protege de esto: el archivo era valido, solo que incompleto.
# Confiar en acordarse de comparar a mano ya fallo, asi que lo comprueba la
# herramienta: si el remoto tiene lineas que el local no trae, se aborta.
import difflib
deriva = {}
for f in args:
    try:
        with sftp.open(f"{BASE}/{f}", 'rb') as rf:
            remoto = rf.read().decode('utf-8', 'replace').replace('\r\n', '\n').split('\n')
    except IOError:
        continue                      # archivo nuevo: no hay nada que perder
    with open(f"{LOCAL}/{f}", 'r', encoding='utf-8') as fh:
        local = fh.read().replace('\r\n', '\n').split('\n')
    # Solo interesan los borrados PUROS (delete), no las ediciones (replace):
    # cambiar una linea tambien la "quita", y marcar eso convertiria la puerta
    # en ruido que se acaba ignorando. Un bloque de 3+ lineas que desaparece
    # sin nada que lo sustituya es contenido que se pierde.
    perdidas = []
    for tag, i1, i2, _, _ in difflib.SequenceMatcher(None, remoto, local).get_opcodes():
        if tag == 'delete':
            bloque = [l for l in remoto[i1:i2] if l.strip()]
            if len(bloque) >= 3:
                perdidas += bloque
    if perdidas:
        deriva[f] = perdidas

if deriva and not ACEPTO_DERIVA:
    print("!!! DERIVA: el archivo de ARIN tiene contenido que tu copia local NO trae.")
    print("    Subirlo lo BORRARIA de produccion. Reconcilia primero (bajate el de ARIN")
    print("    y aplica tus cambios encima), o repite con --acepto-deriva si es a proposito.\n")
    for f, ls in deriva.items():
        print(f"  {f}  ({len(ls)} lineas se perderian)")
        for l in ls[:6]:
            print(f"      {l[:110]}")
        if len(ls) > 6:
            print(f"      ... y {len(ls)-6} mas")
    c.close(); sys.exit(3)
if deriva:
    print(f"AVISO: se aceptó deriva en {len(deriva)} archivo(s) por --acepto-deriva")

# Se sube a un area temporal, se valida ALLI, y solo al final se promueven todos
# los archivos con mv (renombre atomico dentro del mismo sistema de ficheros).
# Antes se escribia directo sobre el archivo vivo y se hacia php -l DESPUES: una
# peticion podia leer el PHP a medias (los 3 "Target class [superadmin]"), y un
# error de sintaxis abortaba dejando el archivo roto ya en produccion.
for f in args:
    d='/'.join(f.split('/')[:-1])
    if d: run(f"mkdir -p {BASE}/_deploy_backups/{STAMP}/{d} {TMP}/{d} {BASE}/{d} 2>&1")
    else: run(f"mkdir -p {TMP} 2>&1")
    run(f"cp -p {BASE}/{f} {BASE}/_deploy_backups/{STAMP}/{f} 2>&1")
    with open(f"{LOCAL}/{f}",'r',encoding='utf-8') as fh: data=fh.read()
    with sftp.open(f"{TMP}/{f}",'wb') as rf: rf.write(data.encode('utf-8'))
    if f.endswith('.php'):
        res=run(f"php -l {TMP}/{f}"); good='No syntax errors' in res; ok=ok and good
        print(("OK  " if good else "ERR ")+f+("" if good else " -> "+res.strip()[:200]))
    else:
        print("OK  "+f)
sftp.close()
if not ok:
    print("!!! ERRORES DE SINTAXIS - abortado (produccion INTACTA, nada promovido)")
    run(f"rm -rf {TMP} 2>&1"); c.close(); sys.exit(1)

# Promocion: hereda permisos y dueño del archivo que sustituye (el respaldo los
# conserva por cp -p); si es nuevo, los toma de su carpeta destino.
for f in args:
    ref=f"{BASE}/_deploy_backups/{STAMP}/{f}"
    d='/'.join(f.split('/')[:-1]) or '.'
    run(f"R={ref}; [ -e \"$R\" ] || R={BASE}/{d}; "
        f"chown --reference=\"$R\" {TMP}/{f} 2>/dev/null; "
        f"chmod --reference=\"$R\" {TMP}/{f} 2>/dev/null; "
        f"[ -f {TMP}/{f} ] && [ ! -d \"$R\" ] || chmod 644 {TMP}/{f} 2>/dev/null; "
        f"mv -f {TMP}/{f} {BASE}/{f} 2>&1")
run(f"rm -rf {TMP} 2>&1")
run(f"rm -f {BASE}/storage/framework/views/*.php 2>&1; cd {BASE} && php artisan view:clear 2>&1")
# Chequeo de salud. Antes apuntaba a tecsist.net, un dominio muerto: devolvia
# 000 en cada despliegue, asi que el gate llevaba tiempo ciego. Ahora mide el
# panel y la portada de ARIN, y AVISA si alguna no responde 200.
salud = {}
for nombre, url in (("panel","https://arindg.com/bixosales/login"), ("portada","https://arindg.com/")):
    # -L sigue el redirect (la portada responde 302 legitimamente) y exige que
    # el destino final sea 200: un 500 detras de un redirect no debe pasar.
    salud[nombre] = run(f"curl -skL -o /dev/null -w '%{{http_code}}' --max-time 25 {url} 2>/dev/null").strip()
print("cache ok |", " ".join(f"{n}:{c}" for n,c in salud.items()), "| backup:", STAMP)
malas = [n for n,cod in salud.items() if cod != "200"]
if malas:
    print(f"!!! SALUD: {', '.join(malas)} no responde 200 — revisa storage/logs/laravel.log")
    print(f"    revertir: cp -p {BASE}/_deploy_backups/{STAMP}/<archivo> {BASE}/<archivo>")
c.close()
if malas: sys.exit(2)

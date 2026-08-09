# -*- coding: utf-8 -*-
# Deploy fijo a ARIN (VPS). Uso:
#   python deploy.py archivo1 [archivo2 ...]           -> sube archivos (rutas relativas al proyecto)
#   python deploy.py --sql "SELECT ..."                -> ejecuta SQL en la BD del VPS
#   python deploy.py --run "comando bash"              -> ejecuta un comando en el VPS
# Hace: backup, sube, php -l (si es .php), limpia vistas, verifica dominio en 200.
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
STAMP=time.strftime('%Y%m%d_%H%M%S'); sftp=c.open_sftp(); ok=True
for f in args:
    d='/'.join(f.split('/')[:-1])
    if d: run(f"mkdir -p {BASE}/_deploy_backups/{STAMP}/{d}")
    run(f"cp -p {BASE}/{f} {BASE}/_deploy_backups/{STAMP}/{f} 2>&1")
    with open(f"{LOCAL}/{f}",'r',encoding='utf-8') as fh: data=fh.read()
    with sftp.open(f"{BASE}/{f}",'wb') as rf: rf.write(data.encode('utf-8'))
    if f.endswith('.php'):
        res=run(f"php -l {BASE}/{f}"); good='No syntax errors' in res; ok=ok and good
        print(("OK  " if good else "ERR ")+f)
    else:
        print("OK  "+f)
sftp.close()
if not ok:
    print("!!! ERRORES DE SINTAXIS - abortado"); c.close(); sys.exit(1)
run(f"rm -f {BASE}/storage/framework/views/*.php 2>&1; cd {BASE} && php artisan view:clear 2>&1")
print("cache ok |", run("curl -sk -o /dev/null -w 'tecsist:%{http_code}' https://tecsist.net/ 2>/dev/null"), "| backup:",STAMP)
c.close()

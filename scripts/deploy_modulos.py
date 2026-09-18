# -*- coding: utf-8 -*-
# Despliegue CONJUNTO de la mudanza a app/Modules en ARIN. deploy.py sirve para
# archivos sueltos; aqui hay cientos de archivos nuevos, cientos que se
# borran y un classmap optimizado que hay que regenerar, todo en una ventana.
#
#   python scripts/deploy_modulos.py --preflight   -> mide sin tocar nada
#   python scripts/deploy_modulos.py --aplicar     -> ejecuta la ventana
#
# Que hace --aplicar, en orden y por que:
#   1. Respaldo completo de app/ resources/views/ routes/ bootstrap/ en
#      _deploy_backups/<stamp>/ (para revertir con un cp -a).
#   2. Sube TODO lo que difiere (md5 contra el arbol local) a _deploy_tmp y
#      pasa php -l a cada .php ALLI. Si algo falla, produccion queda intacta.
#   3. `artisan down` (503 con reintento en 60 s): mejor un minuto de
#      "mantenimiento" que 500 a medias mientras se mueven cientos de archivos.
#   4. Promueve los nuevos (mv), luego `composer dump-autoload -o` (el classmap
#      optimizado de ARIN apunta a rutas viejas), luego borra los viejos, luego
#      view:clear + route:cache/config:cache si existen esas caches.
#   5. `artisan up` y salud: portada, tienda, panel, bixosales, bixocrm, webhook.
# Credenciales: las lee de deploy.py (una sola fuente).
import paramiko, sys, io, os, re, time, hashlib, subprocess, posixpath
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

LOCAL = 'C:/xampp/htdocs/avan'
src = io.open(os.path.join(LOCAL, 'deploy.py'), encoding='utf-8').read()
HOST = re.search(r"HOST='([^']+)'", src).group(1); USER = re.search(r"USER='([^']+)'", src).group(1)
PW = re.search(r"PW='([^']+)'", src).group(1); BASE = re.search(r"BASE='([^']+)'", src).group(1)

# Que se sincroniza: el codigo del producto. NO: .env, storage, vendor, public
# (build de Vite y uploads), whatsbot, docs, scripts.
RAICES = ['app', 'bootstrap/providers.php', 'bootstrap/app.php', 'config', 'database', 'resources/views', 'routes', 'tests', 'composer.json']
COMMIT_BASE = 'c208724'   # estructura vacia app/Modules: desde aqui se movio todo

c = paramiko.SSHClient(); c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect(HOST, port=22, username=USER, password=PW, timeout=25)
def run(cmd, t=300):
    i, o, e = c.exec_command(cmd, timeout=t)
    return (o.read().decode('utf-8', 'replace') + e.read().decode('utf-8', 'replace')).strip()

def git(*a):
    return subprocess.run(['git'] + list(a), cwd=LOCAL, capture_output=True, text=True, encoding='utf-8').stdout

# ── Inventario local (arbol rastreado = HEAD, el arbol esta limpio) ─────────
if git('status', '--porcelain').strip():
    print('!!! el arbol local tiene cambios sin commitear: no se despliega desde un arbol sucio'); sys.exit(1)
rastreados = [l for l in git('ls-files', '--', *RAICES).splitlines() if l]
def md5_local(p):
    with open(os.path.join(LOCAL, p), 'rb') as f: b = f.read()
    return hashlib.md5(b).hexdigest(), hashlib.md5(b.replace(b'\r\n', b'\n')).hexdigest()
# Todo lo que se borro o renombro en CUALQUIER commit desde la base (no solo
# entre los dos extremos: la base es anterior a los 6 commits tematicos).
borrados = sorted({l for l in git('log', '--diff-filter=D', '--name-only', '--no-renames', '--format=', f'{COMMIT_BASE}..HEAD', '--', *RAICES).splitlines() if l} - set(git('ls-files', '--', *RAICES).splitlines()))
alguna_vez = set(git('log', '--all', '--name-only', '--format=', '--', *RAICES).splitlines())

# ── Inventario remoto ───────────────────────────────────────────────────────
dirs_remotos = ' '.join(f"{BASE}/{r}" for r in RAICES)
salida = run(f"cd {BASE} && find {' '.join(RAICES)} -type f \\( -name '*.php' -o -name '*.json' -o -name '*.blade.php' -o -name '*.stub' -o -name '*.md' \\) -not -path '*/node_modules/*' -exec md5sum {{}} + 2>/dev/null", t=600)
md5_remoto = {}
for l in salida.splitlines():
    if len(l) > 34 and l[32] == ' ':
        md5_remoto[l[34:].strip()] = l[:32]

subir, iguales, crlf = [], 0, 0
for p in rastreados:
    raw, lf = md5_local(p)
    r = md5_remoto.get(p)
    if r is None or (r != raw and r != lf):
        subir.append(p)
    else:
        iguales += 1
        if r == lf and r != raw: crlf += 1
nuevos = [p for p in subir if p not in md5_remoto]
cambiados = [p for p in subir if p in md5_remoto]
borrar = [p for p in borrados if p in md5_remoto]
solo_arin = sorted(p for p in md5_remoto if p not in set(rastreados) and p not in set(borrados))

print(f"rastreados locales: {len(rastreados)} | iguales en ARIN: {iguales} (de ellos {crlf} solo difieren en CRLF)")
print(f"a subir: {len(subir)} ({len(nuevos)} nuevos, {len(cambiados)} cambiados) | a borrar en ARIN: {len(borrar)} de {len(borrados)} borrados en git")
print(f"solo en ARIN (ni en HEAD ni borrados en git; NO se tocan, revisar): {len(solo_arin)}")
nunca = [p for p in solo_arin if p not in alguna_vez]
print(f"   de ellos NUNCA rastreados en git (trabajo solo en ARIN): {len(nunca)}")
for p in nunca: print('   ??', p)
SCR = 'C:/Users/luich/AppData/Local/Temp/claude/c--xampp-htdocs-avan/500e9f7c-0ad1-4d49-990b-8bb6fb31d7d3/scratchpad'
for nombre, lista in (('subir', subir), ('borrar', borrar), ('solo_arin', solo_arin)):
    io.open(f'{SCR}/deploy_{nombre}.txt', 'w', encoding='utf-8').write('
'.join(lista))
# Puerta de deriva (la de deploy.py) sobre los cambiados: bloques de 3+ lineas
# que ARIN tiene y el local no.
import difflib
deriva = {}
sftp_ = c.open_sftp()
for p in cambiados:
    try:
        with sftp_.open(f"{BASE}/{p}", 'rb') as rf: remoto = rf.read().decode('utf-8', 'replace').replace('
', '
').split('
')
    except IOError: continue
    with open(os.path.join(LOCAL, p), 'r', encoding='utf-8', errors='replace') as fh: local = fh.read().replace('
', '
').split('
')
    perdidas = []
    for tag, i1, i2, _, _ in difflib.SequenceMatcher(None, remoto, local).get_opcodes():
        if tag == 'delete':
            bloque = [l for l in remoto[i1:i2] if l.strip()]
            if len(bloque) >= 3: perdidas += bloque
    if perdidas: deriva[p] = perdidas
sftp_.close()
print(f"deriva (ARIN tiene bloques que el local no trae): {len(deriva)} archivos")
for p, ls in deriva.items():
    print(f"   ! {p} ({len(ls)} lineas)"); [print('       ' + l[:100]) for l in ls[:4]]
io.open(f'{SCR}/deploy_deriva.txt', 'w', encoding='utf-8').write('

'.join(p + '
' + '
'.join(ls) for p, ls in deriva.items()))
print("\n=== entorno ARIN ===")
print(run(f"cd {BASE} && php -v | head -1; composer --version 2>/dev/null | head -1 || echo 'composer: NO en PATH'; ls bootstrap/cache/ 2>/dev/null | tr '\\n' ' '; echo; php artisan migrate:status 2>/dev/null | grep -i pending | head; echo \"jobs: $(php artisan tinker --execute='echo DB::table(\"jobs\")->count();' 2>/dev/null)\"; grep -c . vendor/composer/autoload_classmap.php"))

if '--aplicar' not in sys.argv:
    print("\n(preflight: nada tocado)"); c.close(); sys.exit(0)

# ── Aplicar ─────────────────────────────────────────────────────────────────
STAMP = time.strftime('%Y%m%d_%H%M%S'); TMP = f"{BASE}/_deploy_tmp/{STAMP}"; BK = f"{BASE}/_deploy_backups/{STAMP}"
print(f"\n=== 1/5 respaldo completo en {BK} ===")
print(run(f"mkdir -p {BK} && cd {BASE} && cp -a app resources/views routes bootstrap config database tests composer.json {BK}/ 2>&1 | tail -2; du -sh {BK} | cut -f1"))

print(f"=== 2/5 subiendo {len(subir)} archivos a temporal y php -l ===")
sftp = c.open_sftp()
dirs = sorted({posixpath.dirname(f"{TMP}/{p}") for p in subir})
for i in range(0, len(dirs), 200):
    run("mkdir -p " + ' '.join(dirs[i:i+200]))
for n, p in enumerate(subir, 1):
    with open(os.path.join(LOCAL, p), 'rb') as fh: data = fh.read()
    with sftp.open(f"{TMP}/{p}", 'wb') as rf: rf.write(data)
    if n % 100 == 0: print(f"   {n}/{len(subir)}")
sftp.close()
lint = run(f"cd {TMP} && find . -name '*.php' -print0 | xargs -0 -n 50 php -l 2>&1 | grep -v 'No syntax errors' | head -20", t=600)
if lint.strip():
    print("!!! ERRORES DE SINTAXIS en temporal - abortado, produccion INTACTA\n" + lint)
    run(f"rm -rf {TMP}"); c.close(); sys.exit(1)
print("   php -l: todo limpio")

print("=== 3/5 mantenimiento (503, reintento 60s) ===")
print(run(f"cd {BASE} && php artisan down --retry=60 2>&1 | tail -1"))
t0 = time.time()

print("=== 4/5 promover, dump-autoload, borrar viejos, caches ===")
# Promueve con cp -a desde TMP (mismo sistema de ficheros; conserva permisos
# de destino via chown --reference a la carpeta padre existente).
duenio = run(f"stat -c '%U:%G' {BASE}/app")
print(run(f"cd {TMP} && find . -type d -exec mkdir -p {BASE}/{{}} \\; && find . -type f -exec mv -f {{}} {BASE}/{{}} \\; 2>&1 | tail -3; cd {BASE} && chown -R {duenio} app resources/views routes bootstrap config database tests 2>&1 | tail -1; find app resources/views routes tests -type f -exec chmod 644 {{}} + ; echo promovido"))
print(run(f"cd {BASE} && composer dump-autoload -o 2>&1 | tail -2", t=600))
if borrar:
    with c.open_sftp() as sftp:
        with sftp.open('/tmp/borrar.txt', 'wb') as f: f.write('\n'.join(borrar).encode())
    print(run(f"cd {BASE} && xargs -a /tmp/borrar.txt rm -f && find app resources/views -type d -empty -delete; echo 'borrados {len(borrar)}'"))
    print(run(f"cd {BASE} && composer dump-autoload -o 2>&1 | tail -1", t=600))
print(run(f"cd {BASE} && rm -f storage/framework/views/*.php; php artisan view:clear 2>&1 | tail -1; php artisan route:clear >/dev/null 2>&1; ls bootstrap/cache/routes-*.php >/dev/null 2>&1 && php artisan route:cache 2>&1 | tail -1 || echo 'sin cache de rutas (ok)'; [ -f bootstrap/cache/config.php ] && php artisan config:cache 2>&1 | tail -1 || echo 'sin cache de config (ok)'; php artisan optimize:clear >/dev/null 2>&1; echo caches"))
print(run(f"rm -rf {TMP}"))

print("=== 5/5 arriba y salud ===")
print(run(f"cd {BASE} && php artisan up 2>&1 | tail -1"))
print(f"   ventana de mantenimiento: {int(time.time() - t0)} s")
salud = {}
for nombre, url in (("portada", "https://arindg.com/"), ("login-sales", "https://arindg.com/bixosales/login"), ("login-crm", "https://arindg.com/bixocrm/login"), ("login-admin", "https://arindg.com/bixoadmin/login"), ("webhook-meta(403)", "https://arindg.com/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=x&hub_challenge=1")):
    salud[nombre] = run(f"curl -skL -o /dev/null -w '%{{http_code}}' --max-time 25 '{url}'").strip()
print("   " + " | ".join(f"{n}:{v}" for n, v in salud.items()))
slugs = run(f"cd {BASE} && php artisan tinker --execute='echo implode(\" \", App\\Models\\Project::where(\"is_active\",1)->orderBy(\"id\")->limit(6)->pluck(\"slug\")->all());' 2>/dev/null | tail -1").split()
print("   tiendas:", " ".join(f"{s_}:{run(chr(99)+'url -skL -o /dev/null -w %{http_code} --max-time 25 https://arindg.com/' + s_).strip()}" for s_ in slugs))
print("   log reciente:", run(f"cd {BASE} && tail -c 3000 storage/logs/laravel.log | grep -c 'ERROR' 2>/dev/null"), "errores en los ultimos 3000 bytes")
print(f"revertir: cp -a {BK}/app {BASE}/ && cp -a {BK}/resources/views {BASE}/resources/ && cp -a {BK}/routes {BK}/bootstrap {BASE}/ && cd {BASE} && composer dump-autoload -o && php artisan optimize:clear")
c.close()

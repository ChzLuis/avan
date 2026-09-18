# -*- coding: utf-8 -*-
# Captura tiendas con emulacion movil real (CDP): viewport 390x844, DPR 2, UA de Android.
import json, os, subprocess, sys, time, base64, urllib.request, tempfile, shutil
import websocket

CH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
SP = sys.argv[1]
TIENDAS = [
    ('ferreteria', 'https://arindg.com/ferreteria-demo'),
    ('ropa', 'https://babytoncito.arindg.com'),
    ('tecnologia', 'https://tienda.tecsist.net'),
    ('hogar', 'https://arindg.com/corporacion-megahogar-ccf4'),
    ('electrico', 'https://jaraluzcorporation.com'),
    ('minimarket', 'https://markethuachoexpress.arindg.com'),
]
perfil = tempfile.mkdtemp(prefix='bixo-chrome-')
proc = subprocess.Popen([CH, '--headless=new', '--disable-gpu', '--hide-scrollbars', '--remote-debugging-port=9333',
                         '--user-data-dir=' + perfil, '--window-size=390,844', 'about:blank'],
                        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
try:
    for _ in range(40):
        try:
            tabs = json.load(urllib.request.urlopen('http://127.0.0.1:9333/json'))
            break
        except Exception:
            time.sleep(0.5)
    ws = websocket.create_connection([t for t in tabs if t['type'] == 'page'][0]['webSocketDebuggerUrl'], suppress_origin=True)
    seq = [0]
    def cmd(method, **params):
        seq[0] += 1
        ws.send(json.dumps({'id': seq[0], 'method': method, 'params': params}))
        while True:
            r = json.loads(ws.recv())
            if r.get('id') == seq[0]:
                return r.get('result', {})
    cmd('Emulation.setDeviceMetricsOverride', width=390, height=844, deviceScaleFactor=2, mobile=True)
    cmd('Emulation.setUserAgentOverride', userAgent='Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Mobile Safari/537.36')
    cmd('Emulation.setTouchEmulationEnabled', enabled=True)
    cmd('Page.enable')
    os.makedirs(SP + '/capturas', exist_ok=True)
    for nombre, url in TIENDAS:
        cmd('Page.navigate', url=url)
        time.sleep(7)
        # Cerrar popups tipicos (cookies / whatsapp flotante no se toca) y volver arriba.
        cmd('Runtime.evaluate', expression="window.scrollTo(0,0); document.querySelectorAll('[x-data] [x-show][role=dialog]').forEach(e=>e.style.display='none');")
        time.sleep(1)
        r = cmd('Page.captureScreenshot', format='png', captureBeyondViewport=False)
        open(f'{SP}/capturas/{nombre}.png', 'wb').write(base64.b64decode(r['data']))
        print(nombre, os.path.getsize(f'{SP}/capturas/{nombre}.png'))
    ws.close()
finally:
    proc.kill()
    shutil.rmtree(perfil, ignore_errors=True)

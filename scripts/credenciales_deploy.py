# -*- coding: utf-8 -*-
# Credenciales del VPS para los scripts de despliegue. Misma fuente que
# deploy.py: `.deploy.env` junto a deploy.py (ignorado por git) o variables de
# entorno DEPLOY_HOST / DEPLOY_USER / DEPLOY_PASSWORD / DEPLOY_BASE.
# Nunca se escriben aqui ni en el repo.
import io, os, sys

LOCAL = os.environ.get('DEPLOY_LOCAL', 'C:/xampp/htdocs/avan')

def _cargar_deploy_env():
    ruta = os.path.join(LOCAL, '.deploy.env')
    if not os.path.exists(ruta):
        return
    with io.open(ruta, encoding='utf-8') as f:
        for linea in f:
            linea = linea.strip()
            if not linea or linea.startswith('#') or '=' not in linea:
                continue
            clave, valor = linea.split('=', 1)
            os.environ.setdefault(clave.strip(), valor.strip().strip('"').strip("'"))

def obtener():
    """@return (HOST, USER, PW, BASE) o termina el proceso explicando que falta."""
    _cargar_deploy_env()
    host = os.environ.get('DEPLOY_HOST'); user = os.environ.get('DEPLOY_USER'); pw = os.environ.get('DEPLOY_PASSWORD')
    base = os.environ.get('DEPLOY_BASE', '/home/arindg/htdocs/arindg.com')
    if not (host and user and pw):
        print("Faltan credenciales de despliegue: crea .deploy.env junto a deploy.py con DEPLOY_HOST, DEPLOY_USER y DEPLOY_PASSWORD.")
        sys.exit(1)
    return host, user, pw, base

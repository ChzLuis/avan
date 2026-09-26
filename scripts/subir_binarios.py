#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""Sube archivos BINARIOS al VPS (imagenes, fuentes, PDF).

`deploy.py` lee cada archivo como UTF-8 para poder comparar diferencias y
detectar deriva, asi que revienta con un JPG. Antes que debilitar esa
comprobacion, que es la que evita pisar trabajo ajeno, los binarios van por
aqui: se copian tal cual, sin interpretar su contenido.

Reutiliza las credenciales de `.deploy.env`, que nunca estan en el repo.

    python scripts/subir_binarios.py public/img/bixo-logo.jpg [mas archivos...]
"""

import io
import os
import sys

import paramiko

RAIZ = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


def cargar_env():
    ruta = os.path.join(RAIZ, '.deploy.env')
    if not os.path.exists(ruta):
        return
    with io.open(ruta, encoding='utf-8') as f:
        for linea in f:
            linea = linea.strip()
            if not linea or linea.startswith('#') or '=' not in linea:
                continue
            clave, valor = linea.split('=', 1)
            os.environ.setdefault(clave.strip(), valor.strip().strip('"').strip("'"))


cargar_env()

HOST = os.environ.get('DEPLOY_HOST')
USER = os.environ.get('DEPLOY_USER')
PW = os.environ.get('DEPLOY_PASSWORD')
BASE = os.environ.get('DEPLOY_BASE', '/home/arindg/htdocs/arindg.com')

if not (HOST and USER and PW):
    print('Faltan credenciales. Revisa .deploy.env junto a deploy.py.')
    sys.exit(1)

archivos = sys.argv[1:]
if not archivos:
    print(__doc__)
    sys.exit(1)

cliente = paramiko.SSHClient()
cliente.set_missing_host_key_policy(paramiko.AutoAddPolicy())
cliente.connect(HOST, port=22, username=USER, password=PW, timeout=25)
sftp = cliente.open_sftp()


def asegurar_carpeta(ruta_remota):
    """Crea la carpeta destino si falta. Sin esto, subir a una ruta nueva
    falla con un error que no dice cual es el problema."""
    partes = ruta_remota.strip('/').split('/')[:-1]
    camino = ''
    for parte in partes:
        camino += '/' + parte
        try:
            sftp.stat(camino)
        except IOError:
            sftp.mkdir(camino)


fallos = 0
for rel in archivos:
    rel = rel.replace('\\', '/').lstrip('./')
    local = os.path.join(RAIZ, rel)
    if not os.path.exists(local):
        print('FALTA EN LOCAL  %s' % rel)
        fallos += 1
        continue

    remoto = BASE + '/' + rel
    try:
        asegurar_carpeta(remoto)
        sftp.put(local, remoto)
        tam_local = os.path.getsize(local)
        tam_remoto = sftp.stat(remoto).st_size
        if tam_local == tam_remoto:
            print('OK  %-46s %d bytes' % (rel, tam_remoto))
        else:
            # Un tamano distinto significa copia incompleta: mejor saberlo
            # ahora que descubrir una imagen rota en produccion.
            print('>>> INCOMPLETO %s (local %d, remoto %d)' % (rel, tam_local, tam_remoto))
            fallos += 1
    except Exception as e:
        print('>>> ERROR %s -> %s' % (rel, e))
        fallos += 1

sftp.close()
cliente.close()

sys.exit(1 if fallos else 0)

#!/usr/bin/env python3
"""
Descarga candidatas de foto para la demo agricola.

Dos intentos anteriores fallaron quedandose con el primer resultado de la
busqueda: para "camote amarillo" bajo una flor, para "camote morado" unos
fideos, y la quinua blanca y la roja eran la misma lamina de herbario. El
nombre de un alimento devuelve por igual la planta, el plato cocinado y el
producto crudo, asi que NINGUNA busqueda automatica acierta sola.

Por eso este script solo baja candidatas numeradas (grupo-1.jpg ...) y no
asigna nada. La eleccion se hace despues, mirando un montaje.

Fuente: Openverse, filtrando licencias de uso comercial. Se anota la
autoria de cada archivo en creditos.txt, que es lo que pide CC-BY.

Correr en el VPS:  python3 scripts/demo_agro_fotos.py
"""

import json
import os
import ssl
import urllib.parse
import urllib.request

DESTINO = '/home/arindg/htdocs/arindg.com/public/uploads/demoagro/candidatas'
AGENTE = 'BixoDemo/1.0 (https://arindg.com; contacto@eskala.pe)'
POR_GRUPO = 6

BUSQUEDAS = {
    'papa-amarilla': 'yellow potatoes raw',
    'papa-nativa': 'andean native potatoes',
    'papa-rosada': 'red potatoes raw pile',
    'papa-blanca': 'white potatoes raw pile',
    'papa-saco': 'potato sack market',
    'olluco': 'olluco ulluco tuber',
    'oca': 'oca tuber oxalis',
    'mashua': 'mashua tuber',
    'chuno': 'chuno chuño potato',
    'moraya': 'tunta moraya potato',
    'camote-amarillo': 'raw sweet potato',
    'camote-morado': 'purple sweet potato raw',
    'camote-naranja': 'orange sweet potato raw',
    'yuca': 'cassava yuca root',
    'kion': 'ginger root',
    'betarraga': 'raw beetroot',
    'arracacha': 'arracacha root',
    'quinua-blanca': 'white quinoa grain',
    'quinua-roja': 'red quinoa grain',
    'quinua-negra': 'black quinoa grain',
    'kiwicha': 'amaranth grain',
    'canihua': 'kaniwa canihua grain',
    'maiz-morado': 'purple corn',
    'maiz-cancha': 'dried corn kernels',
    'maiz-mote': 'hominy corn mote',
    'maiz-gigante': 'giant white corn',
    'trigo': 'wheat grain pile',
    'frijol-canario': 'dried yellow beans',
    'frijol-castilla': 'black eyed peas dried',
    'frijol-panamito': 'dried white beans',
    'pallar': 'dried lima beans',
    'lenteja': 'dried lentils',
    'garbanzo': 'dried chickpeas',
    'arveja': 'dried split peas',
    'habas': 'dried fava beans',
    'frijol-negro': 'dried black beans',
    'cebolla-roja': 'red onions',
    'cebolla-blanca': 'white onions',
    'ajo': 'garlic bulbs',
    'zanahoria': 'raw carrots',
    'choclo': 'corn cob fresh',
}


def pedir(url, timeout=40):
    req = urllib.request.Request(url, headers={'User-Agent': AGENTE})
    return urllib.request.urlopen(req, timeout=timeout, context=ssl.create_default_context()).read()


def buscar(termino, cuantas):
    api = ('https://api.openverse.org/v1/images/?q=' + urllib.parse.quote(termino) +
           '&license_type=commercial&page_size=%d&mature=false' % (cuantas + 4))
    datos = json.loads(pedir(api, 30))
    salida = []
    for r in datos.get('results', []):
        enlace = r.get('url')
        if enlace:
            salida.append((r.get('title', '') or '', r.get('creator', '') or '',
                           r.get('license', '') or '', enlace))
    return salida


def main():
    os.makedirs(DESTINO, exist_ok=True)
    creditos = []
    for grupo, termino in BUSQUEDAS.items():
        try:
            candidatas = buscar(termino, POR_GRUPO)
        except Exception as e:
            print('ERROR  %-18s %s' % (grupo, str(e)[:45]))
            continue
        n = 0
        for titulo, autor, licencia, enlace in candidatas:
            if n >= POR_GRUPO:
                break
            nombre = '%s-%d.jpg' % (grupo, n + 1)
            ruta = os.path.join(DESTINO, nombre)
            if os.path.exists(ruta) and os.path.getsize(ruta) > 9000:
                n += 1
                continue
            try:
                datos = pedir(enlace, 35)
            except Exception:
                continue
            if len(datos) < 9000:
                continue
            with open(ruta, 'wb') as f:
                f.write(datos)
            creditos.append('%s | %s | %s | %s' % (nombre, titulo[:50], autor[:30], licencia))
            n += 1
        print('%-18s %d' % (grupo, n))

    if creditos:
        with open(os.path.join(DESTINO, 'creditos.txt'), 'a') as f:
            f.write('\n'.join(creditos) + '\n')
    print('\nCandidatas en', DESTINO)


if __name__ == '__main__':
    main()

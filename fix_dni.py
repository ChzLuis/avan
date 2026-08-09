f = '/home/pruebatusuerte-bot/htdocs/bot.pruebatusuerte.com.pe/whatsbot/engine.js'
c = open(f, encoding='utf-8').read()

# Buscar el case save_solo_dni y reemplazarlo completo
old = """        case 'save_solo_dni': {
            const dni = body.trim().replace(/[^0-9]/g,'');
            if (!dni || dni.length < 7 || dni.length > 12) {
                await enviar(msg, '⚠️ Ingresa un DNI válido (solo números, 7 a 12 dígitos).\n\n_Ejemplo: 12345678_');
                return null;
            }
            sessionData = { ...sessionData, dni };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost('wa/rifa/' + oid + '/data', { dni });
            break;
        }"""

new = """        case 'save_solo_dni': {
            const dni = body.trim().replace(/[^0-9]/g,'');
            if (!dni || dni.length < 7 || dni.length > 12) {
                await enviar(msg, '⚠️ Ingresa un DNI válido (solo números, 7 a 12 dígitos).\\n\\n_Ejemplo: 12345678_');
                return null;
            }
            sessionData = { ...sessionData, dni };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost('wa/rifa/' + oid + '/data', { dni });
            break;
        }"""

if old in c:
    c = c.replace(old, new, 1)
    open(f, 'w', encoding='utf-8').write(c)
    print('OK - fixed')
else:
    print('NOT FOUND - buscando linea 862...')
    lines = c.split('\n')
    for i, l in enumerate(lines[858:868], 859):
        print(i, repr(l))

filepath = '/home/pruebatusuerte-bot/htdocs/bot.pruebatusuerte.com.pe/whatsbot/engine.js'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

old = """        case 'save_solo_dni': {
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

new = """        case 'save_solo_dni': {
            const dni = body.trim().replace(/[^0-9]/g,'');
            if (!dni || dni.length !== 8) {
                await enviar(msg, '⚠️ El DNI debe tener exactamente *8 dígitos*.\\n\\n_Ejemplo: 12345678_');
                return null;
            }
            sessionData = { ...sessionData, dni };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost('wa/rifa/' + oid + '/data', { dni });
            break;
        }"""

if old in content:
    content = content.replace(old, new, 1)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print('OK')
else:
    print('NO ENCONTRADO')

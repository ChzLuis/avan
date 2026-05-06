/**
 * engine.js — Motor genérico de bots WhatsApp
 */

// ── Args ──────────────────────────────────────────────────────
const args    = Object.fromEntries(process.argv.slice(2).map(a => { const [k,v]=a.replace('--','').split('='); return [k,v]; }));
const BOT_TYPE = args.bot || 'main';

const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode  = require('qrcode-terminal');
const path    = require('path');
const fs      = require('fs');
const http    = require('http');

// ── CONFIGURACIÓN ─────────────────────────────────────────────
if (process.env.NODE_TLS_REJECT_UNAUTHORIZED === undefined)
    process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

const BOT_TOKEN = 'wa-bot-secret-2024';

function detectLaravelUrl() {
    if (process.env.LARAVEL_URL) return process.env.LARAVEL_URL;
    try {
        const envFile = fs.readFileSync(path.join(__dirname, '../.env'), 'utf8');
        const match   = envFile.match(/^APP_URL=(.+)$/m);
        if (match) return match[1].trim().replace(/["']/g, '');
    } catch(e) {}
    return 'http://127.0.0.1';
}

function detectChromium() {
    if (process.env.CHROMIUM_PATH && fs.existsSync(process.env.CHROMIUM_PATH))
        return process.env.CHROMIUM_PATH;
    const candidates = [
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/usr/bin/google-chrome-stable',
    ];
    return candidates.find(p => fs.existsSync(p)) || null;
}

const LARAVEL_URL = detectLaravelUrl();
const FLOW_FILE   = path.join(__dirname, `flow-${BOT_TYPE}.json`);
const STATUS_FILE = path.join(__dirname, `${BOT_TYPE}-status.json`);
const QR_IMAGE    = path.join(__dirname, 'qr-pago.png');
console.log(`🌐 Laravel URL: ${LARAVEL_URL}`);

const BASE_LAT     = -13.149770079285672;
const BASE_LNG     = -74.2303798295127;
const DELIVERY_MIN = 6.00;
const DELIVERY_KM  = 1.50;

let BOT_PORT    = args.port ? parseInt(args.port) : (BOT_TYPE === 'main' ? 3001 : BOT_TYPE === 'rifa' ? 3002 : 3003);
let FLOW        = null;
let PROJECT_SLUG = null;
let NEGOCIO     = 'Bot';
let clientReady = false;
let MI_NUMERO   = '';

console.log(`🤖 Engine iniciado — bot_type: ${BOT_TYPE}`);

// ── HTTP helpers ──────────────────────────────────────────────
async function laravelGet(route) {
    try {
        const res = await fetch(`${LARAVEL_URL}/${route}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    } catch(e) { console.warn(`⚠️  GET /${route}:`, e.message); return null; }
}

async function laravelPost(route, body) {
    try {
        const res = await fetch(`${LARAVEL_URL}/${route}`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json','Accept':'application/json' },
            body: JSON.stringify({ token: BOT_TOKEN, ...body }),
        });
        return await res.json();
    } catch(e) { console.warn(`⚠️  POST /${route}:`, e.message); return null; }
}

function saveStatus(data) {
    try { fs.writeFileSync(STATUS_FILE, JSON.stringify(data, null, 2)); } catch(e) {}
}

// ── Cargar flujo ──────────────────────────────────────────────
async function loadFlow(phone = '') {
    console.log(`🔄 Cargando flujo [${BOT_TYPE}]...`);
    try {
        const params = new URLSearchParams({ token: BOT_TOKEN, bot: BOT_TYPE });
        if (phone) params.set('phone', phone);
        const res = await fetch(`${LARAVEL_URL}/wa/flow-config?${params}`);
        if (res.ok) {
            const data = await res.json();
            if (data.ok) {
                FLOW = data;
                fs.writeFileSync(FLOW_FILE, JSON.stringify(data, null, 2));
                console.log(`✅ Flujo cargado desde API: ${Object.keys(data.states).length} estados`);
                return;
            }
        }
    } catch(e) { console.warn('⚠️  API no disponible:', e.message); }

    if (fs.existsSync(FLOW_FILE)) {
        try {
            FLOW = JSON.parse(fs.readFileSync(FLOW_FILE, 'utf8'));
            console.log(`📂 Flujo desde archivo local: ${Object.keys(FLOW.states).length} estados`);
            return;
        } catch(e) { console.error('❌ Error leyendo flow json:', e.message); }
    }

    console.warn('🆘 Flujo de emergencia');
    FLOW = {
        flow_id: 0, bot_type: BOT_TYPE, project_id: 0,
        states: {
            inicio: { key:'inicio', message:'✅ Recibimos tu mensaje. Un asesor te contactará pronto.\n_{negocio}_', input_type:'text', transitions:[] }
        }
    };
}

setInterval(() => loadFlow(), 5 * 60 * 1000);

// ── Sesión ────────────────────────────────────────────────────
async function getSession(waNumber) {
    if (!FLOW?.flow_id) return { state: 'inicio', data: {} };
    const res = await laravelGet(`wa/session?token=${BOT_TOKEN}&flow_id=${FLOW.flow_id}&wa_number=${waNumber}`);
    return res?.ok ? { state: res.state, data: res.data || {} } : { state: 'inicio', data: {} };
}

async function saveSession(waNumber, state, data = {}) {
    if (!FLOW?.flow_id) return;
    await laravelPost('wa/session', { flow_id: FLOW.flow_id, wa_number: waNumber, state, data });
}

// ── Utilidades ────────────────────────────────────────────────
function distanciaKm(lat1,lng1,lat2,lng2) {
    const R=6371,dLat=(lat2-lat1)*Math.PI/180,dLng=(lng2-lng1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}
function calcularDelivery(km) { return Math.ceil(Math.max(DELIVERY_MIN,km*DELIVERY_KM)*2)/2; }
function fillMessage(tpl, vars) {
    return tpl
        .replace(/\\n/g, '\n')
        .replace(/\{(\w+)\}/g, (_,k) => vars[k] ?? '');
}
function buildVars(data) {
    return {
        negocio:      NEGOCIO,
        mi_numero:    MI_NUMERO,
        cliente:      data.cliente||'',
        total:        data.total||'',
        nPedido:      data.nPedido||'',
        n_pedido:     data.nPedido||'',
        delivery:     data.deliveryCost!=null ? Number(data.deliveryCost).toFixed(2) : '',
        total_final:  data.totalFinal||data.total||'',
        km:           data.km||'',
        direccion:    data.deliveryAddress||'',
        order_number: data.orderNumber||'',
        nombre:       data.nombre||'',
        documento:    data.documento||data.dni||'',
        dni:          data.documento||data.dni||'',
        telefono:     data.telefono||data.celular||'',
        celular:      data.telefono||data.celular||'',
        ciudad:       data.ciudad||'',
        item_nombre:  data.itemNombre||data.rifaNombre||'',
        item_precio:  data.itemPrecio ? Number(data.itemPrecio).toFixed(2) : (data.rifaPrecio ? Number(data.rifaPrecio).toFixed(2) : ''),
        item_cantidad:data.itemCantidad||data.rifaTickets||'',
        item_total:   data.itemTotal||data.rifaTotal||'',
        rifa_nombre:  data.itemNombre||data.rifaNombre||'',
        rifa_precio:  data.itemPrecio ? Number(data.itemPrecio).toFixed(2) : (data.rifaPrecio ? Number(data.rifaPrecio).toFixed(2) : ''),
        rifa_tickets: data.itemCantidad||data.rifaTickets||'',
        rifa_total:   data.itemTotal||data.rifaTotal||'',
        rifas_lista:  data.rifasLista||'',
        lista:        data.rifasLista||'',
        wa_number:    data.waNumber||'',
        email:        data.email||'',
    };
}

// ── Delay humanizado (simula escritura) ───────────────────────
async function enviarConDelay(msg, texto) {
    try {
        const chat  = await msg.getChat();
        const delay = Math.min(3000, Math.max(1000, texto.length * 25));
        await chat.sendStateTyping();
        await new Promise(r => setTimeout(r, delay));
        await chat.clearState();
        await client.sendMessage(msg.from, texto);
    } catch(e) {
        try { await client.sendMessage(msg.from, texto); } catch(e2) { console.error('Envío:', e2.message); }
    }
}

async function enviar(msg, texto) {
    await enviarConDelay(msg, texto);
}

async function enviarImagen(msg) {
    try {
        if (fs.existsSync(QR_IMAGE)) {
            const media = MessageMedia.fromFilePath(QR_IMAGE);
            await client.sendMessage(msg.from, media, { caption: '📲 Escanea para pagar' });
        }
    } catch(e) {}
}

// ── Cargar lista de productos ─────────────────────────────────
async function loadRifasLista(sessionData) {
    try {
        const params = new URLSearchParams({ token: BOT_TOKEN, bot: BOT_TYPE });
        const res    = await fetch(`${LARAVEL_URL}/wa/rifas?${params}`);
        const data   = await res.json();
        if (!data.ok || !data.rifas?.length) return { ...sessionData, rifasLista: '⚠️ No hay productos disponibles en este momento.' };
        const lista = data.rifas.map((r, i) => `*${i+1}.* ${r.texto}`).join('\n\n');
        return { ...sessionData, rifas: data.rifas, rifasLista: lista };
    } catch(e) {
        console.error('loadRifasLista:', e.message);
        return sessionData;
    }
}

async function enviarListaConImagenes(msg, rifas, encabezado) {
    try {
        const header = encabezado.split('\n')[0] || '🎰 *Productos disponibles:*';
        await enviar(msg, header);
        for (let i = 0; i < rifas.length; i++) {
            const r       = rifas[i];
            const caption = `*${i+1}.* ${r.texto}`;
            if (r.imagen_url) {
                try {
                    const media = await MessageMedia.fromUrl(r.imagen_url, { unsafeMime: true });
                    await client.sendMessage(msg.from, media, { caption });
                    continue;
                } catch(e) { console.warn(`Imagen producto ${i+1} no enviada:`, e.message); }
            }
            await enviar(msg, caption);
        }
        await enviar(msg, '👆 Escribe el *número* del plan que te interesa:');
    } catch(e) { console.error('enviarListaConImagenes:', e.message); }
}

// ── Cliente WhatsApp ──────────────────────────────────────────
const puppeteerConfig = {
    headless: true,
    args: ['--no-sandbox','--disable-setuid-sandbox'],
};
const chromiumPath = detectChromium();
if (chromiumPath) {
    puppeteerConfig.executablePath = chromiumPath;
    console.log(`🌐 Chromium: ${chromiumPath}`);
}

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: `whatsbot-${BOT_TYPE}`,
        dataPath: path.join(__dirname, '.wwebjs_auth'),
    }),
    puppeteer: puppeteerConfig,
});

client.on('qr', qr => {
    console.log(`\n📱 QR [${BOT_TYPE}] — escanea:\n`);
    qrcode.generate(qr, { small: true });
    try {
        const QRCode = require('qrcode');
        QRCode.toDataURL(qr, (err, url) => {
            if (!err) saveStatus({ status:'qr', qr:url, updated_at:new Date().toISOString() });
        });
    } catch(e) {
        saveStatus({ status:'qr', qr:null, updated_at:new Date().toISOString() });
    }
});

client.on('ready', async () => {
    clientReady = true;
    saveStatus({ status:'connected', qr:null, updated_at:new Date().toISOString() });
    try {
        const myNumber = client.info.wid.user;
        const res = await fetch(`${LARAVEL_URL}/wa/config?token=${BOT_TOKEN}&phone=${myNumber}`);
        const data = await res.json();
        if (data.ok) {
            PROJECT_SLUG = data.project_slug;
            NEGOCIO      = data.negocio;
            MI_NUMERO    = data.whatsapp || data.phone || myNumber;
            console.log(`✅ [${BOT_TYPE}] conectado — ${NEGOCIO}`);
        }
        await loadFlow(myNumber);
    } catch(e) {
        console.error('Error ready:', e.message);
        await loadFlow();
    }
    console.log(`[${BOT_TYPE}] esperando mensajes...\n`);
});

// ── Estados activos (protegidos de reset) ─────────────────────
const ACTIVE_STATES = [
    'pedir_nombre','pedir_telefono','pedir_email','pedir_direccion',
    'comprobante_recibido','confirmacion_final','enviar_qr','ver_lista',
];

// ── Respuesta contextual cuando el usuario quiere salir ───────
function getMensajeContextual(currentState, lower) {
    const esCancelar = ['cancelar','salir'].includes(lower);
    const cancelTip  = '\n\nEscribe *cancelar* si deseas empezar de nuevo.';

    const contextos = {
        'ver_lista': {
            esperando: '👆 Estoy esperando que elijas un plan escribiendo su *número*.',
            ejemplo:   '_Ejemplo: escribe *1*, *2* o *3* según el plan que quieras._',
        },
        'pedir_nombre': {
            esperando: '✍️ Estoy esperando tu *nombre completo*.',
            ejemplo:   '_Ejemplo: Juan Pérez García_',
        },
        'enviar_qr': {
            esperando: '📸 Estoy esperando la *foto de tu comprobante de pago*.',
            ejemplo:   '_Toma una captura de tu Yape, Plin o transferencia y envíala aquí._',
        },
        'comprobante_recibido': {
            esperando: '⏳ Tu pago está siendo *revisado por nuestro equipo*.',
            ejemplo:   '_Te avisamos en cuanto esté listo. Si necesitas ayuda escribe *asesor*._',
        },
        'pedir_telefono': {
            esperando: '📞 Estoy esperando tu *número de celular adicional* (9 dígitos).',
            ejemplo:   '_Ejemplo: 955354646_',
        },
        'pedir_email': {
            esperando: '📧 Estoy esperando tu *correo electrónico*.',
            ejemplo:   '_Ejemplo: tucorreo@gmail.com_',
        },
        'pedir_direccion': {
            esperando: '📍 Estoy esperando tu *dirección completa*.',
            ejemplo:   '_Ejemplo: Av. Los Pinos 123, Ayacucho_\nO escribe *NO APLICA* si no tienes dirección fija.',
        },
    };

    const ctx = contextos[currentState];
    if (!ctx) return null;

    if (currentState === 'comprobante_recibido') {
        return `${ctx.esperando}\n\n${ctx.ejemplo}`;
    }

    if (esCancelar) {
        return `¿Seguro que deseas cancelar? 🤔\n\nResponde *SI* para confirmar o continúa con tu registro.`;
    }

    return `❓ No entendí tu respuesta.\n\n${ctx.esperando}\n${ctx.ejemplo}${cancelTip}`;
}

// ── Confirmaciones de cancelación pendientes ──────────────────
const _cancelPending = new Map();
const _lastValidando    = new Map(); // anti-spam "validando"
const _lastImagen       = new Map(); // detección doble imagen
const _pendingDireccion = new Map(); // confirmación dirección sospechosa

// ── Mensajes ──────────────────────────────────────────────────
const _processedIds = new Set();
client.on('message', async msg => {
    try {
        if (msg.fromMe) return;
        const msgKey = msg.id?.id || msg.id?._serialized || '';
        if (msgKey && _processedIds.has(msgKey)) return;
        if (msgKey) { _processedIds.add(msgKey); setTimeout(()=>_processedIds.delete(msgKey), 10000); }
        if (msg.from === 'status@broadcast') return;
        if (msg.from.endsWith('@newsletter') || msg.from.endsWith('@g.us')) return;
        if (msg.isStatus) return;
        if (!msg.from.endsWith('@c.us') && !msg.from.endsWith('@lid')) return;

        let waNumber = msg.from.replace(/@.*$/,'');
        if (msg.from.endsWith('@lid')) {
            try {
                const contact    = await msg.getContact();
                const contactNum = contact?.number || contact?.id?.user || '';
                if (contactNum && contactNum.length >= 8 && contactNum.length <= 15) waNumber = contactNum;
            } catch(e) {
                const lidSuffix = waNumber.slice(-9);
                const sessions  = await laravelGet(`wa/sessions?token=${BOT_TOKEN}&flow_id=${FLOW?.flow_id}&suffix=${lidSuffix}`).catch(()=>null);
                if (sessions?.wa_number) waNumber = sessions.wa_number;
            }
        }
        const body  = (msg.body||'').trim();
        const lower = body.toLowerCase();

        if (!FLOW) await loadFlow();

        // Comando de reset solo para admin (antes de cualquier validación)
        if (lower === 'reset' && waNumber === '51955354646') {
            await saveSession(waNumber, 'inicio', {});
            await enviar(msg, '🔄 Sesión reseteada. Iniciando desde cero...');
            await enviarBienvenida(msg, waNumber, {});
            return;
        }

        const session = await getSession(waNumber);
        let { state: currentState, data: sessionData } = session;

        // Sesión nueva
        if (!currentState) {
            await enviarBienvenida(msg, waNumber, {});
            return;
        }

        // ── Confirmación de cancelación pendiente ──
        if (_cancelPending.has(waNumber)) {
            _cancelPending.delete(waNumber);
            if (lower === 'si' || lower === 'sí') {
                await saveSession(waNumber, 'inicio', {});
                await enviar(msg, 'Entendido 👍 Tu proceso fue cancelado.\n\nCuando quieras empezar de nuevo escribe *hola* 😊');
                return;
            } else {
                await enviar(msg, '¡Perfecto, seguimos! 😊');
                const stCfg = FLOW.states[currentState];
                if (stCfg?.message) await enviar(msg, fillMessage(stCfg.message, buildVars(sessionData)));
                return;
            }
        }

        console.log(`📨 [${BOT_TYPE}] ${waNumber} | ${currentState} | ${body.substring(0,40)}`);

        // comprobante_recibido: anti-spam + doble imagen
        if (currentState === 'comprobante_recibido') {
            if (msg.type === 'image' || msg.hasMedia) {
                const ahora   = Date.now();
                const ultImg  = _lastImagen.get(waNumber) || 0;
                if (ultImg > 0 && (ahora - ultImg) < 30000) {
                    const veces = (_lastImagen.get(waNumber + '_n') || 0) + 1;
                    _lastImagen.set(waNumber + '_n', veces);
                    if (veces >= 2) {
                        await enviar(msg, 'Entiendo tu prisa. Ya tengo tu comprobante anterior. Dame 1 min para revisarlo. ✅');
                    } else {
                        await enviar(msg, '📸 Ya recibí tu comprobante, estamos validando. No es necesario reenviar. 🙏');
                    }
                    return;
                }
                _lastImagen.set(waNumber, ahora);
                _lastImagen.set(waNumber + '_n', 0);
                // Imagen nueva válida — deja pasar al flujo normal
            } else {
                const ahora  = Date.now();
                const ultVal = _lastValidando.get(waNumber) || 0;
                if (ultVal > 0 && (ahora - ultVal) < 60000) {
                    await enviar(msg, 'Sigo revisando tu pago. En menos de 1 minuto te confirmo. ✅');
                } else {
                    _lastValidando.set(waNumber, ahora);
                    await enviar(msg, '⏳ Tu pago está siendo revisado por nuestro equipo.\n\nTe avisamos en cuanto esté listo. 🙏\n\nSi necesitas ayuda escribe *asesor*.');
                }
                return;
            }
        }

        const isActiveState = ACTIVE_STATES.includes(currentState);
        const exitWords     = ['menu','menú','inicio','start','comenzar','reiniciar','cancelar','salir','hola'];

        if (exitWords.includes(lower)) {
            if (isActiveState) {
                const msgCtx = getMensajeContextual(currentState, lower);
                if (msgCtx) {
                    if (['cancelar','salir'].includes(lower)) {
                        _cancelPending.set(waNumber, true);
                        setTimeout(() => _cancelPending.delete(waNumber), 30000);
                    }
                    await enviar(msg, msgCtx);
                    return;
                }
            } else {
                await enviarBienvenida(msg, waNumber, {});
                return;
            }
        }

        const stateConfig = FLOW.states[currentState] || FLOW.states['inicio'];

        // Terminal: responder con mensaje post-estado según cuál sea
        if (stateConfig.input_type === 'none') {
            const lw = lower.trim();
            // Comandos post-compra en confirmacion_final
            if (currentState === 'confirmacion_final') {
                if (['menu','menú','otro','mas','más','1'].includes(lw)) {
                    const verLista = FLOW.states['ver_lista'];
                    if (verLista) {
                        await saveSession(waNumber, 'ver_lista', sessionData);
                        // Cargar lista de planes primero
                        const rifas = await loadRifasLista();
                        if (rifas && rifas.length > 0) {
                            sessionData = { ...sessionData, rifas };
                            await saveSession(waNumber, 'ver_lista', sessionData);
                            await enviarListaConImagenes(msg, rifas);
                        } else {
                            await enviar(msg, fillMessage(verLista.message, buildVars(sessionData)));
                        }
                    } else {
                        await saveSession(waNumber, 'inicio', {});
                        await enviarBienvenida(msg, waNumber, {});
                    }
                    return;
                }
                if (['soporte','asesor','ayuda','help','2'].includes(lw)) {
                    await enviar(msg, '👤 En breve un asesor te atenderá.\n\nPor favor escribe tu consulta aquí. 🙏');
                    return;
                }
                if (['reclamo','problema','queja','3'].includes(lw)) {
                    await enviar(msg, '📝 Lamentamos el inconveniente. Cuéntanos qué sucedió y lo resolveremos en máximo 4 horas. 🙏');
                    return;
                }
                if (['reintentar','tickets','mis tickets','reenviar','estado'].includes(lw)) {
                    await enviar(msg, '🔄 Revisando tus tickets... Si no los recibes en unos minutos escribe *asesor* para que te ayudemos. 🎟️');
                    return;
                }
                await enviar(msg, '──────────────────\n¿Deseas hacer algo más?\n\n1️⃣ Ver otros planes\n2️⃣ Hablar con un asesor\n3️⃣ Tengo un problema\n\nEscribe el número o *MENÚ* para ver los planes.');
                return;
            }
            // Otros estados terminales (cancelado, pausado)
            if (currentState === 'cancelado') {
                await saveSession(waNumber, 'inicio', {});
                await enviarBienvenida(msg, waNumber, {});
                return;
            }
            if (currentState === 'pausado') {
                if (['continuar','seguir','continúa'].includes(lw)) {
                    await saveSession(waNumber, 'inicio', {});
                    await enviarBienvenida(msg, waNumber, {});
                } else {
                    await enviar(msg, '⏸️ Tu proceso está pausado.\n\nEscribe *continuar* cuando quieras seguir. 😊');
                }
                return;
            }
            // Cualquier otro estado none genérico
            await enviar(msg, '¿Deseas empezar de nuevo? Escribe *hola* 😊');
            return;
        }

        // Validar input
        const validationError = validateInput(body, stateConfig, msg);
        if (validationError) {
            await enviar(msg, validationError);
            return;
        }

        // Buscar transición
        let transition = null;
        for (const t of (stateConfig.transitions || [])) {
            if (matchTrigger(t.trigger, msg, body, lower)) { transition = t; break; }
        }

        if (!transition) {
            await enviar(msg, fillMessage(stateConfig.message, buildVars(sessionData)));
            return;
        }

        const actionResult = await executeAction(msg, waNumber, body, transition, sessionData);
        if (actionResult === null) return;
        sessionData = actionResult ?? sessionData;

        const nextStateKey = transition.to || currentState;
        const nextState    = FLOW.states[nextStateKey];

        if (nextState?.message?.includes('{rifas_lista}')) {
            sessionData = await loadRifasLista(sessionData);
        }

        await saveSession(waNumber, nextStateKey, sessionData);

        if (nextState?.message) {
            const text   = fillMessage(nextState.message, buildVars(sessionData));
            const images = nextState.images || [];
            if (images.length > 0) {
                for (let i = 0; i < images.length; i++) {
                    try {
                        const imageUrl = images[i].startsWith('http') ? images[i] : `${LARAVEL_URL}${images[i]}`;
                        const media    = await MessageMedia.fromUrl(imageUrl, { unsafeMime: true });
                        const caption  = i === 0 ? text : undefined;
                        await client.sendMessage(msg.from, media, caption ? { caption } : {});
                    } catch(e) {
                        if (i === 0) await enviar(msg, text);
                        console.warn(`Imagen ${i} no enviada:`, e.message);
                    }
                }
            } else {
                await enviar(msg, text);
            }
            if (nextStateKey === 'esperando_pago') await enviarImagen(msg);
        }

    } catch(e) { console.error(`❌ [${BOT_TYPE}] error:`, e.message); }
});

// ── Validación de entrada ─────────────────────────────────────
function validateInput(body, stateConfig, msg) {
    const type   = stateConfig.input_type;
    const errMsg = stateConfig.validation_error || null;

    if (type === 'image') {
        if (!msg || msg.type !== 'image')
            return errMsg || '📸 Para continuar necesito la foto de tu comprobante.\n\nSolo tómale una foto y envíala aquí 😊';
    }

    if (type === 'number') {
        if (!/^-?\d+(\.\d+)?$/.test(body.trim()))
            return errMsg || '⚠️ Por favor escribe solo números.';
        const num = parseFloat(body.trim());
        if (stateConfig.validation_min != null && num < stateConfig.validation_min)
            return errMsg || `⚠️ El valor mínimo es ${stateConfig.validation_min}.`;
        if (stateConfig.validation_max != null && num > stateConfig.validation_max)
            return errMsg || `⚠️ El valor máximo es ${stateConfig.validation_max}.`;
    }

    if (type === 'text' && stateConfig.validation_pattern) {
        try {
            if (!new RegExp(stateConfig.validation_pattern).test(body.trim()))
                return errMsg || '⚠️ El formato ingresado no es válido.';
        } catch(e) {}
    }

    if (type === 'option') {
        const validTriggers = (stateConfig.transitions || [])
            .filter(t => t.trigger && t.trigger !== '*' && t.trigger !== '')
            .map(t => t.trigger.toLowerCase());
        if (validTriggers.length > 0 && !validTriggers.includes(body.trim().toLowerCase()))
            return errMsg || `⚠️ Opción no válida. Escribe: ${validTriggers.join(', ')}`;
    }

    return null;
}

// ── Trigger matching ──────────────────────────────────────────
function matchTrigger(trigger, msg, body, lower) {
    if (trigger === null || trigger === undefined || trigger === '') return true;
    switch(trigger) {
        case 'PEDIDO+TOTAL':   return body.includes('PEDIDO') && body.includes('TOTAL');
        case 'location':       return msg.type === 'location';
        case 'text_address':   return msg.type === 'text' && body.length > 5 && !body.includes('PEDIDO');
        case 'image':          return msg.hasMedia && msg.type === 'image';
        case 'si_llego':       return /^(sí llegó|si llegó|si llego|sí llego|✅|todo bien|llegó|llego)/.test(lower);
        case 'no_llego':       return /^(no llegó|no llego|❌|problema)/.test(lower);
        case 'confirmar_pago': case 'en_camino': case 'entregado': return false;
        default:               return lower === trigger.toLowerCase();
    }
}

// ── Acciones ──────────────────────────────────────────────────
async function executeAction(msg, waNumber, body, transition, sessionData) {
    const lower  = body.toLowerCase();
    const action = transition.action;
    if (!action) return sessionData;

    switch(action) {
        case 'save_name': {
            const nombre = body.replace(/^hola[,!.\s]*/i,'').trim() || body;
            return { ...sessionData, nombre: nombre || body };
        }
        case 'save_nombre_documento': {
            const partes    = body.split(',').map(s => s.trim());
            const nombre    = partes[0] || body;
            const documento = (partes[1] || '').replace(/\D/g,'');
            sessionData = { ...sessionData, nombre, documento, dni: documento };
            const itemId    = sessionData.itemId || sessionData.rifaId;
            const oidExiste = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (!oidExiste && itemId) {
                try {
                    const resp = await laravelPost('wa/rifa-order', {
                        rifa_id: itemId, tickets: sessionData.itemCantidad || sessionData.rifaTickets || 1,
                        wa_number: waNumber, nombre, dni: documento,
                    });
                    if (resp?.ok) {
                        sessionData = { ...sessionData,
                            orderNumber: resp.order_number, itemTotal: Number(resp.monto).toFixed(2),
                            itemCantidad: resp.tickets, itemNombre: resp.rifa_nombre, itemOrderId: resp.order_id,
                            rifaTotal: Number(resp.monto).toFixed(2), rifaTickets: resp.tickets,
                            rifaNombre: resp.rifa_nombre, rifaOrderId: resp.order_id,
                        };
                        console.log(`✅ Pedido creado: ${resp.order_number} (id:${resp.order_id})`);
                    }
                } catch(e) { console.error('save_nombre_documento create_order:', e.message); }
            } else if (oidExiste) {
                await laravelPost(`wa/rifa/${oidExiste}/data`, { nombre, dni: documento });
            }
            return sessionData;
        }
        case 'create_order': {
            const nombreMatch = body.match(/👤 \*?Cliente:\*? (.+)/);
            const totalMatch  = body.match(/💰 \*?TOTAL:?\*? ?:? ?([A-Z]{0,3}\.?\s*[\d.,]+)/);
            const pedidoMatch = body.match(/📋 \*?N° Pedido:\*? (.+)/);
            const cliente     = nombreMatch?.[1]?.trim() || 'Cliente';
            const total       = totalMatch?.[1]?.trim()  || '';
            const nPedido     = pedidoMatch?.[1]?.trim() || '';
            const subtotalNum = parseFloat((total||'0').replace(/[^0-9.]/g,''))||0;
            const items = [];
            const itemRegex = /\d+\.\s+\*?(.+?)\*?\n\s+•\s*Cantidad:\s*([\d.,]+)[^\n]*\n\s+•\s*Precio unit\.:\s*[^\d]*([\d.,]+)/g;
            let m;
            while ((m = itemRegex.exec(body)) !== null)
                items.push({ name:m[1].trim(), qty:parseInt(m[2].replace(/[.,]/g,''))||1, price:parseFloat(m[3].replace(',','.'))||0 });
            const resp = await laravelPost('wa/order', {
                project_slug: PROJECT_SLUG, wa_number: waNumber, client_name: cliente,
                n_pedido: nPedido, items: items.length ? items : [{name:'Pedido',qty:1,price:subtotalNum}], subtotal: subtotalNum,
            });
            sessionData = { ...sessionData, cliente, total, nPedido, subtotalNum, orderId: resp?.order_id||null };
            break;
        }
        case 'save_delivery': {
            let d = {};
            if (msg.type === 'location') {
                const loc=msg.location, km=distanciaKm(BASE_LAT,BASE_LNG,loc.latitude,loc.longitude), delivery=calcularDelivery(km);
                d = { deliveryAddress:`GPS (${loc.latitude.toFixed(5)}, ${loc.longitude.toFixed(5)})`, deliveryCost:delivery, totalFinal:((sessionData.subtotalNum||0)+delivery).toFixed(2), km:km.toFixed(1) };
                if (sessionData.orderId) await laravelPost(`wa/order/${sessionData.orderId}/delivery`,{ shipping_cost:delivery, delivery_address:d.deliveryAddress });
            } else {
                d = { deliveryAddress:body.trim(), deliveryCost:0, totalFinal:sessionData.total||'0', km:'?' };
                if (sessionData.orderId) await laravelPost(`wa/order/${sessionData.orderId}/delivery`,{ shipping_cost:0, delivery_address:body.trim() });
            }
            sessionData = { ...sessionData, ...d };
            break;
        }
        case 'show_lista':
        case 'show_rifas': {
            sessionData = await loadRifasLista(sessionData);
            if (sessionData.rifas?.length) {
                const nextKey    = transition?.to || '';
                const encabezado = '🎟️ *Tipos de membresía disponibles:*';
                await enviarListaConImagenes(msg, sessionData.rifas, encabezado);
                if (nextKey) await saveSession(waNumber, nextKey, sessionData);
                return null;
            }
            break;
        }
        case 'select_item':
        case 'select_rifa': {
            const rifas   = sessionData.rifas || [];
            const selIdx  = parseInt(body) - 1;
            const selRifa = rifas[selIdx] || rifas.find(r => r.nombre.toLowerCase().includes(lower));
            if (!selRifa) {
                await enviar(msg, `Hmm, esa opción no existe 🤔\n\nEscribe un número del *1* al *${rifas.length}* para elegir tu plan.`);
                return null;
            }
            const precio = selRifa.precio ?? selRifa.precio_ticket ?? 0;
            sessionData = { ...sessionData,
                itemId: selRifa.id, itemNombre: selRifa.nombre, itemPrecio: precio,
                itemTotal: Number(precio).toFixed(2), itemCantidad: selRifa.tickets || 1,
                rifaId: selRifa.id, rifaNombre: selRifa.nombre, rifaPrecio: precio,
                rifaTotal: Number(precio).toFixed(2), rifaTickets: selRifa.tickets || 1,
            };
            try {
                const resp = await laravelPost('wa/rifa-order', {
                    rifa_id: selRifa.id, tickets: selRifa.tickets || 1, wa_number: waNumber,
                });
                if (resp?.ok) {
                    sessionData = { ...sessionData,
                        orderNumber: resp.order_number, itemTotal: Number(resp.monto).toFixed(2),
                        itemCantidad: resp.tickets, itemNombre: resp.rifa_nombre || selRifa.nombre, itemOrderId: resp.order_id,
                        rifaTotal: Number(resp.monto).toFixed(2), rifaTickets: resp.tickets,
                        rifaNombre: resp.rifa_nombre || selRifa.nombre, rifaOrderId: resp.order_id,
                    };
                }
            } catch(e) { console.error('select_item create_order:', e.message); }
            console.log(`[select_item] itemTotal=${sessionData.itemTotal} tickets=${sessionData.itemCantidad} itemNombre=${sessionData.itemNombre}`);
            break;
        }
        case 'save_quantity':
        case 'save_quantity|create_rifa_order':
        case 'save_quantity_create_order': {
            const qty    = parseInt(body.replace(/\D/g,'')) || 1;
            const precio = sessionData.itemPrecio || sessionData.rifaPrecio || 0;
            const total  = (qty * precio).toFixed(2);
            sessionData  = { ...sessionData, itemCantidad: qty, itemTotal: total, rifaTickets: qty, rifaTotal: total };
            if (action !== 'save_quantity') {
                try {
                    const resp = await laravelPost('wa/rifa-order', {
                        rifa_id: sessionData.itemId || sessionData.rifaId, tickets: qty, wa_number: waNumber,
                    });
                    if (resp?.ok) {
                        sessionData = { ...sessionData,
                            orderNumber: resp.order_number, itemTotal: Number(resp.monto).toFixed(2),
                            itemCantidad: resp.tickets, itemNombre: resp.rifa_nombre, itemOrderId: resp.order_id,
                            rifaTotal: Number(resp.monto).toFixed(2), rifaTickets: resp.tickets,
                            rifaNombre: resp.rifa_nombre, rifaOrderId: resp.order_id,
                        };
                    }
                } catch(e) { console.error('create_order_lista:', e.message); }
            }
            break;
        }
        case 'create_order_lista':
        case 'create_rifa_order': {
            try {
                const resp = await laravelPost('wa/rifa-order', {
                    rifa_id: sessionData.itemId || sessionData.rifaId,
                    tickets: sessionData.itemCantidad || sessionData.rifaTickets || 1,
                    wa_number: waNumber,
                });
                if (resp?.ok) {
                    sessionData = { ...sessionData,
                        orderNumber: resp.order_number, itemTotal: Number(resp.monto).toFixed(2),
                        itemCantidad: resp.tickets, itemNombre: resp.rifa_nombre, itemOrderId: resp.order_id,
                        rifaTotal: Number(resp.monto).toFixed(2), rifaTickets: resp.tickets,
                        rifaNombre: resp.rifa_nombre, rifaOrderId: resp.order_id,
                    };
                }
            } catch(e) { console.error('create_order_lista:', e.message); }
            break;
        }
        case 'save_nombre':
        case 'save_rifa_nombre': {
            const nombre = body.trim();
            sessionData = { ...sessionData, nombre };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost(`wa/rifa/${oid}/data`, { nombre });
            break;
        }
        case 'save_documento':
        case 'save_rifa_dni': {
            const documento = body.trim();
            sessionData = { ...sessionData, documento, dni: documento };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost(`wa/rifa/${oid}/data`, { dni: documento });
            break;
        }
        case 'save_ciudad':
        case 'save_rifa_ciudad': {
            const ciudad = body.trim();
            sessionData = { ...sessionData, ciudad };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost(`wa/rifa/${oid}/data`, { ciudad });
            break;
        }
        case 'save_phone': {
            sessionData = { ...sessionData, telefono: body.trim() };
            break;
        }
        case 'save_rifa_payment':
        case 'save_payment': {
            if (msg.hasMedia && msg.type === 'image') {
                try {
                    const media    = await msg.downloadMedia();
                    const oidLista = sessionData.itemOrderId || sessionData.rifaOrderId;
                    if (oidLista && media?.data) {
                        await laravelPost(`wa/rifa/${oidLista}/payment-proof`, {
                            image_base64: media.data, mimetype: media.mimetype || 'image/jpeg',
                            wa_number: waNumber, nombre: sessionData.nombre || '',
                            item_nombre: sessionData.itemNombre || sessionData.rifaNombre || '',
                            item_total:  sessionData.itemTotal  || sessionData.rifaTotal   || '',
                        });
                    }
                    if (sessionData.orderId && media?.data)
                        await laravelPost(`wa/order/${sessionData.orderId}/payment-proof`,
                            { image_base64: media.data, mimetype: media.mimetype || 'image/jpeg' });
                } catch(e) { console.error('save_payment ERROR:', e.message, e.stack); }
            }
            break;
        }
        case 'save_solo_nombre':
        case 'save_solo_name': {
            const nombre = body.trim();
            if (!nombre) {
                await enviar(msg, '⚠️ No ingresaste ningún nombre. Escríbelo como en tu DNI.\n\n_Ejemplo: Juan Pérez García_');
                return null;
            }
            if (/[0-9!@#$%^&*()_+=\[\]{};:\"<>?,.\/\\|]/.test(nombre)) {
                await enviar(msg, '⚠️ Solo letras y espacios, sin números ni símbolos.\n\n_Ejemplo: Ana María Gómez_');
                return null;
            }
            if (nombre.length < 5) {
                await enviar(msg, '⚠️ El nombre es muy corto. Escribe tu nombre completo real.\n\n_Ejemplo: Luis Ángel García_');
                return null;
            }
            if (nombre.length > 50) {
                await enviar(msg, '⚠️ El nombre es demasiado largo. Usa solo tus nombres principales.\n\n_Ejemplo: María Elena Torres_');
                return null;
            }
            sessionData = { ...sessionData, nombre };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost(`wa/rifa/${oid}/data`, { nombre });
            break;
        }
        case 'save_email':
        case 'save_solo_email': {
            const email = body.trim();
            if (!email) {
                await enviar(msg, '⚠️ No ingresaste ningún correo. Escríbelo correctamente.\n\n_Ejemplo: juan.perez@gmail.com_');
                return null;
            }
            if (!email.includes('@')) {
                await enviar(msg, '⚠️ Falta el símbolo *@* en tu correo.\n\n_Ejemplo: usuario@gmail.com_');
                return null;
            }
            const parts = email.split('@');
            if (parts.length !== 2 || !parts[0] || !parts[1]) {
                await enviar(msg, '⚠️ El correo está incompleto.\n\n_Ejemplo: usuario@gmail.com_');
                return null;
            }
            if (!parts[1].includes('.') || parts[1].startsWith('.')) {
                await enviar(msg, '⚠️ Falta el punto después del *@*.\n\n_Ejemplo: usuario@gmail.com_');
                return null;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
                await enviar(msg, '⚠️ Ese correo no es válido. Escríbelo así:\n\n_Ejemplo: juan.perez@gmail.com_');
                return null;
            }
            sessionData = { ...sessionData, email };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost(`wa/rifa/${oid}/data`, { email });
            break;
        }
        case 'save_solo_ciudad':
        case 'save_solo_direccion': {
            const ciudad = body.trim();
            if (!ciudad) {
                await enviar(msg, '⚠️ No ingresaste ninguna dirección.\n\n_Ejemplo: Av. Los Pinos 123, Ayacucho_');
                return null;
            }
            const ciudadUp = ciudad.toUpperCase();
            // Confirmar dirección sospechosa pendiente
            if (_pendingDireccion.has(waNumber)) {
                const dirPend = _pendingDireccion.get(waNumber);
                _pendingDireccion.delete(waNumber);
                if (['SI','SÍ','S','YES','OK'].includes(ciudadUp)) {
                    sessionData = { ...sessionData, ciudad: dirPend, direccion: dirPend };
                    const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
                    if (oid) await laravelPost(`wa/rifa/${oid}/data`, { ciudad: dirPend, direccion: dirPend });
                    break;
                }
                // Si no confirmó, lo que escribió es la nueva dirección — sigue al flujo normal
            }
            if (ciudadUp === 'NO APLICA' || ciudadUp === 'NO TENGO') {
                sessionData = { ...sessionData, ciudad: 'No aplica', direccion: 'No aplica' };
                const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
                if (oid) await laravelPost(`wa/rifa/${oid}/data`, { ciudad: 'No aplica', direccion: 'No aplica' });
                break;
            }
            if (ciudad.length < 5) {
                await enviar(msg, '⚠️ Dirección muy corta. Escríbela con calle y número.\n\n_Ejemplo: Av. Los Pinos 123, Ayacucho_\n\nO escribe *NO APLICA* si no tienes dirección fija.');
                return null;
            }
            const esSospechosa = (
                !ciudad.includes(' ') ||
                /^[a-zA-ZáéíóúÁÉÍÓÚ]+[0-9]+$/.test(ciudad) ||
                /(.){3,}/.test(ciudad.toLowerCase()) ||
                /^(jsjs|hhhh|aaaa|asdf|qwer|test|prueba)/i.test(ciudad)
            );
            if (esSospechosa) {
                _pendingDireccion.set(waNumber, ciudad);
                setTimeout(() => _pendingDireccion.delete(waNumber), 60000);
                await enviar(msg, `📍 ¿Confirmas que tu dirección es *"${ciudad}"*?\n\nEscribe *SÍ* para confirmar o corrígela.\n_Ejemplo: Av. Los Pinos 123, Ayacucho_`);
                return null;
            }
            if (!/[0-9]/.test(ciudad) && ciudad.split(' ').length < 3) {
                await enviar(msg, '📍 Agrega el número de puerta o más detalles.\n\n_Ejemplo: Jr. Callao 456, Huamanga, Ayacucho_\n\nO escribe *NO APLICA* si no tienes dirección fija.');
                return null;
            }
            sessionData = { ...sessionData, ciudad, direccion: ciudad };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost(`wa/rifa/${oid}/data`, { ciudad, direccion: ciudad });
            break;
        }
        case 'save_solo_telefono':
        case 'save_solo_celular': {
            const rawTel = body.trim();
            if (!rawTel) {
                await enviar(msg, '⚠️ Necesito un número de contacto. Escribe 9 dígitos.\n\n_Ejemplo: 955354646_');
                return null;
            }
            if (/[a-zA-Z]/.test(rawTel)) {
                await enviar(msg, '⚠️ Solo números, sin letras.\n\n_Ejemplo: 955354646_');
                return null;
            }
            let telefono2 = rawTel.replace(/[^0-9]/g, '');
            // Normalizar: quitar prefijo 51 o +51
            if (telefono2.startsWith('51') && telefono2.length === 11) telefono2 = telefono2.slice(2);
            if (telefono2.length < 9) {
                await enviar(msg, '⚠️ El número debe tener 9 dígitos.\n\n_Ejemplo: 955354646_');
                return null;
            }
            if (telefono2.length > 9) {
                await enviar(msg, '⚠️ Demasiados dígitos. Escribe solo 9 números.\n\n_Ejemplo: 955354646_');
                return null;
            }
            sessionData = { ...sessionData, telefono: telefono2, celular: telefono2 };
            const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
            if (oid) await laravelPost(`wa/rifa/${oid}/data`, { telefono: telefono2 });
            break;
        }
    }
    return sessionData;
}

// ── Bienvenida ────────────────────────────────────────────────
async function enviarBienvenida(msg, waNumber, sessionData) {
    const inicioState = FLOW.states['inicio'];
    if (inicioState) {
        const text   = fillMessage(inicioState.message || '', buildVars(sessionData));
        const images = inicioState.images || [];
        // Paso 1: texto de bienvenida
        if (text) await enviar(msg, text);
        // Paso 2: imagen(es) con delay de 1s
        if (images.length > 0) {
            await new Promise(r => setTimeout(r, 1000));
            for (let i = 0; i < images.length; i++) {
                try {
                    const imageUrl = images[i].startsWith('http') ? images[i] : `${LARAVEL_URL}${images[i]}`;
                    const media    = await MessageMedia.fromUrl(imageUrl, { unsafeMime: true });
                    await client.sendMessage(msg.from, media);
                } catch(e) {}
            }
        }
        // Paso 3: menú (auto-transición) con delay de 2s
        const autoT = (inicioState.transitions || []).filter(t => !t.trigger && !t.action);
        if (autoT.length === 1) {
            const autoKey  = autoT[0].to;
            const autoNext = autoKey && FLOW.states[autoKey];
            if (autoNext?.message) {
                await new Promise(r => setTimeout(r, 2000));
                await saveSession(waNumber, autoKey, sessionData);
                await enviar(msg, fillMessage(autoNext.message, buildVars(sessionData)));
                return;
            }
        }
    }
    await saveSession(waNumber, 'inicio', sessionData);
}

// ── Limpiar lock de Chromium al iniciar ───────────────────────
try {
    const { execSync } = require('child_process');
    // Matar TODOS los procesos chrome/chromium (el binario real está en /opt/google/chrome/chrome)
    execSync('pkill -9 -f /opt/google/chrome/chrome 2>/dev/null; pkill -9 -f chromium 2>/dev/null; sleep 2', { stdio: 'ignore' });
} catch(e) {}
try {
    // Borrar lock files de la sesión
    const sessionDir = require('path').join(__dirname, '.wwebjs_auth', 'session-whatsbot-' + BOT_TYPE);
    ['SingletonLock','SingletonSocket','SingletonCookie'].forEach(f => {
        const p = require('path').join(sessionDir, f);
        if (fs.existsSync(p)) { try { fs.unlinkSync(p); } catch(e) {} }
    });
} catch(e) {}
client.initialize();

// ── Servidor HTTP ─────────────────────────────────────────────
const MENSAJES_ADMIN = {
    confirmar_pago: n => `✅ *¡Pago confirmado!*\n\nEstamos preparando tu pedido 📦\nTe avisamos cuando salga en camino 🚚\n\n_${n}_`,
    en_camino:      n => `🚚 *¡Tu pedido ya va en camino!*\n\nTiempo estimado: 30–45 minutos ⏱️\n\n_${n}_`,
    entregado:      n => `📦 *¡Tu pedido fue entregado!*\n\n¿Todo llegó bien?\n✅ *Si llegó*  ❌ *No llegó*\n\n_${n}_`,
};

const server = http.createServer(async (req, res) => {
    if (req.method === 'GET' && req.url === '/reload-flow') {
        await loadFlow();
        res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
        return;
    }
    if (req.method === 'POST' && req.url === '/reload') {
        let raw = ''; req.on('data', c => raw += c);
        req.on('end', async () => {
            try {
                const data = JSON.parse(raw||'{}');
                if (data.token !== BOT_TOKEN) { res.writeHead(401); res.end(); return; }
                await loadFlow();
                console.log(`🔄 [${BOT_TYPE}] Flujo recargado desde portal`);
                res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
            } catch(e) { res.writeHead(500); res.end(); }
        });
        return;
    }
    // Endpoint para resetear sesión y mostrar nuevo QR
    if (req.method === 'POST' && req.url === '/reset-session') {
        let raw = ''; req.on('data', d => raw += d);
        req.on('end', async () => {
            try {
                const data = JSON.parse(raw || '{}');
                if (data.token !== BOT_TOKEN) { res.writeHead(401); res.end(JSON.stringify({ok:false,error:'Unauthorized'})); return; }
                res.writeHead(200, {'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true,message:'Reiniciando sesión...'}));
                console.log(`🔄 [${BOT_TYPE}] reset-session solicitado desde panel`);
                saveStatus({status:'offline', qr:null, updated_at:new Date().toISOString()});
                // Destruir cliente actual
                try { await client.destroy(); } catch(e) {}
                // Borrar carpeta de sesión
                const sessionDir = require('path').join(__dirname, '.wwebjs_auth', `session-whatsbot-${BOT_TYPE}`);
                try {
                    require('fs').rmSync(sessionDir, { recursive: true, force: true });
                    console.log(`🗑️  [${BOT_TYPE}] Sesión borrada: ${sessionDir}`);
                } catch(e) { console.warn(`⚠️  No se pudo borrar sesión: ${e.message}`); }
                // Matar procesos Chrome huérfanos
                try { require('child_process').execSync('pkill -f "google-chrome.*whatsbot" 2>/dev/null || true', {stdio:'ignore'}); } catch(e) {}
                // Re-inicializar para mostrar QR
                setTimeout(() => {
                    console.log(`📱 [${BOT_TYPE}] Iniciando nuevo QR...`);
                    try { client.initialize(); } catch(e) { console.error('reset-session reinit error:', e.message); }
                }, 3000);
            } catch(e) { res.writeHead(500); res.end(JSON.stringify({ok:false,error:e.message})); }
        });
        return;
    }

    if (req.method !== 'POST' || req.url !== '/action') { res.writeHead(404); res.end(); return; }
    let raw = '';
    req.on('data', c => raw += c);
    req.on('end', async () => {
        try {
            const data = JSON.parse(raw);
            if (data.token !== BOT_TOKEN) { res.writeHead(401); res.end(); return; }
            console.log('[ACTION DEBUG] action='+JSON.stringify(data.action)+' wa='+JSON.stringify(data.wa_number));
            if (!data.wa_number) { res.writeHead(400,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:false,error:'wa_number requerido'})); return; }
            const waNumber = data.wa_number.replace(/\D/g,'');
            const waId     = waNumber + '@c.us';
            if (!clientReady) { res.writeHead(503,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:false,error:'Bot no conectado'})); return; }

            if (data.action === 'send_ticket') {
                if (data.ticket_base64) {
                    const media = new MessageMedia(data.ticket_mime || 'image/png', data.ticket_base64, 'ticket.png');
                    await client.sendMessage(waId, media, { caption: data.message || '' });
                } else if (data.message) {
                    await client.sendMessage(waId, data.message);
                }
                // Menú posventa 4 segundos después de los tickets
                setTimeout(async () => {
                    try {
                        await saveSession(waNumber, 'confirmacion_final', {});
                        await client.sendMessage(waId, '──────────────────\n¿Deseas hacer algo más?\n\n1️⃣ Ver planes disponibles\n2️⃣ Hablar con un asesor\n3️⃣ Tengo un problema\n\nEscribe el número o *MENÚ*.');
                    } catch(e) {}
                }, 4000);
                res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
                return;
            }
            if (data.action === 'send_message') {
                if (data.message) await client.sendMessage(waId, data.message);
                res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
                return;
            }
            if (data.action === 'pago_aprobado') {
                const session2 = await getSession(waNumber);
                const existing = session2.data || {};
                const sd2 = {
                    ...existing, waNumber,
                    nombre:      data.nombre     || existing.nombre     || '',
                    itemNombre:  data.item_nombre || existing.itemNombre || '',
                    itemTotal:   data.item_total  || existing.itemTotal  || '',
                    itemOrderId: data.order_id    || existing.itemOrderId|| '',
                };
                const pedir    = FLOW.states['pedir_telefono'] || FLOW.states['pedir_email'];
                const nextKey2 = FLOW.states['pedir_telefono'] ? 'pedir_telefono' : 'pedir_email';
                if (pedir?.message) {
                    await saveSession(waNumber, nextKey2, sd2);
                    await client.sendMessage(waId, fillMessage(pedir.message, buildVars(sd2)));
                }
                res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
                return;
            }
            if (data.action === 'pago_rechazado') {
                const session2 = await getSession(waNumber);
                const sd2 = session2.data || {};
                await saveSession(waNumber, 'enviar_qr', sd2);
                const msg2 = data.message || '❌ Tu comprobante no pudo ser validado. Por favor envía nuevamente tu comprobante de pago.';
                await client.sendMessage(waId, msg2);
                res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
                return;
            }
            if (data.action === 'tickets_enviados') {
                const session2 = await getSession(waNumber);
                const sd2 = session2.data || {};
                const finalState = FLOW.states['confirmacion_final'];
                if (finalState?.message) {
                    await client.sendMessage(waId, fillMessage(finalState.message, buildVars(sd2)));
                } else if (data.message) {
                    await client.sendMessage(waId, data.message);
                }
                res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
                return;
            }
            const builder = MENSAJES_ADMIN[data.action];
            if (builder && waId) {
                await client.sendMessage(waId, builder(NEGOCIO));
                if (FLOW?.flow_id) {
                    const map = { confirmar_pago:'pago_recibido', en_camino:'en_camino', entregado:'entregado' };
                    if (map[data.action]) await laravelPost('wa/session',{ flow_id:FLOW.flow_id, wa_number:waNumber, state:map[data.action] });
                }
                res.writeHead(200,{'Content-Type':'application/json'}); res.end(JSON.stringify({ok:true}));
            } else { res.writeHead(400); res.end(); }
        } catch(e) { console.error('[ACTION ERROR]', e.message); res.writeHead(500); res.end(); }
    });
});

server.on('error', e => {
    if (e.code === 'EADDRINUSE') setTimeout(()=>server.listen(BOT_PORT,'127.0.0.1'),3000);
});
server.listen(BOT_PORT,'127.0.0.1',()=>console.log(`🌐 [${BOT_TYPE}] HTTP en puerto ${BOT_PORT}`));

// ── Resiliencia ───────────────────────────────────────────────
client.on('disconnected', (reason) => {
    saveStatus({status:'offline',qr:null,updated_at:new Date().toISOString()});
    console.warn(`⚠️  [${BOT_TYPE}] desconectado (${reason}), reconectando en 10s...`);
    setTimeout(() => { try { client.initialize(); } catch(e){} }, 10000);
});
client.on('auth_failure', () => {
    saveStatus({status:'offline',qr:null,updated_at:new Date().toISOString()});
    console.warn(`⚠️  [${BOT_TYPE}] auth_failure — necesita escanear QR`);
});
process.on('unhandledRejection', r => {
    console.error(`⚠️  [${BOT_TYPE}] UnhandledRejection:`, r);
    saveStatus({status:'offline',qr:null,updated_at:new Date().toISOString()});
});
process.on('uncaughtException', e => {
    console.error(`⚠️  [${BOT_TYPE}] UncaughtException:`, e.message);
    saveStatus({status:'offline',qr:null,updated_at:new Date().toISOString()});
});

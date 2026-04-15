/**
 * engine.js — Motor genérico de bots WhatsApp
 *
 * Uso:
 *   node engine.js --bot=main
 *   node engine.js --bot=rifa
 *   node engine.js --bot=soporte
 *
 * El bot_type determina qué flujo carga desde la BD (via API Laravel).
 * Fallback automático a flow-{bot_type}.json si la API no responde.
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

const BOT_TOKEN   = 'wa-bot-secret-2024';

// Auto-detectar LARAVEL_URL: env var > leer .env Laravel > fallback local
function detectLaravelUrl() {
    if (process.env.LARAVEL_URL) return process.env.LARAVEL_URL;
    try {
        const envFile = fs.readFileSync(path.join(__dirname, '../.env'), 'utf8');
        const match   = envFile.match(/^APP_URL=(.+)$/m);
        if (match) return match[1].trim().replace(/["']/g, '');
    } catch(e) {}
    return 'http://127.0.0.1';
}

// Auto-detectar chromium del sistema
function detectChromium() {
    if (process.env.CHROMIUM_PATH && fs.existsSync(process.env.CHROMIUM_PATH))
        return process.env.CHROMIUM_PATH;
    const candidates = [
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/usr/bin/google-chrome',
        '/usr/bin/google-chrome-stable',
    ];
    return candidates.find(p => fs.existsSync(p)) || null;
}

const LARAVEL_URL = detectLaravelUrl();
const FLOW_FILE   = path.join(__dirname, `flow-${BOT_TYPE}.json`);
const STATUS_FILE = path.join(__dirname, `${BOT_TYPE}-status.json`);
const QR_IMAGE    = path.join(__dirname, 'qr-pago.png');
console.log(`🌐 Laravel URL: ${LARAVEL_URL}`);

// Tarifas delivery (configurable desde BotConfig en el futuro)
const BASE_LAT     = -13.149770079285672;
const BASE_LNG     = -74.2303798295127;
const DELIVERY_MIN = 6.00;
const DELIVERY_KM  = 1.50;

// Puerto: main=3001, otros=3002,3003... se lee de la API
let BOT_PORT    = BOT_TYPE === 'main' ? 3001 : BOT_TYPE === 'rifa' ? 3002 : 3003;
let FLOW        = null;
let PROJECT_SLUG = null;
let NEGOCIO     = 'Bot';
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
// ─────────────────────────────────────────────────────────────

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

    // Fallback JSON
    if (fs.existsSync(FLOW_FILE)) {
        try {
            FLOW = JSON.parse(fs.readFileSync(FLOW_FILE, 'utf8'));
            console.log(`📂 Flujo desde archivo local: ${Object.keys(FLOW.states).length} estados`);
            return;
        } catch(e) { console.error('❌ Error leyendo flow json:', e.message); }
    }

    // Emergencia
    console.warn('🆘 Flujo de emergencia');
    FLOW = {
        flow_id: 0, bot_type: BOT_TYPE, project_id: 0,
        states: {
            inicio: { key:'inicio', message:'✅ Recibimos tu mensaje. Un asesor te contactará pronto.\n_{negocio}_', input_type:'text', transitions:[] }
        }
    };
}

// Recargar cada 5 minutos
setInterval(() => loadFlow(), 5 * 60 * 1000);
// ─────────────────────────────────────────────────────────────

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
// ─────────────────────────────────────────────────────────────

// ── Utilidades ────────────────────────────────────────────────
function distanciaKm(lat1,lng1,lat2,lng2) {
    const R=6371,dLat=(lat2-lat1)*Math.PI/180,dLng=(lng2-lng1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2+Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}
function calcularDelivery(km) { return Math.ceil(Math.max(DELIVERY_MIN,km*DELIVERY_KM)*2)/2; }
function fillMessage(tpl, vars) { return tpl.replace(/\{(\w+)\}/g,(_,k)=>vars[k]??''); }
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
        // Variables de rifa
        rifa_nombre:  data.rifaNombre||'',
        rifa_precio:  data.rifaPrecio ? Number(data.rifaPrecio).toFixed(2) : '',
        rifa_tickets: data.rifaTickets||'',
        rifa_total:   data.rifaTotal||'',
        order_number: data.orderNumber||'',
        nombre:       data.nombre||'',
    };
}

async function enviar(msg, texto) {
    try { await client.sendMessage(msg.from, texto); } catch(e) { console.error('Envío:', e.message); }
}
async function enviarImagen(msg) {
    try {
        if (fs.existsSync(QR_IMAGE)) {
            const media = MessageMedia.fromFilePath(QR_IMAGE);
            await client.sendMessage(msg.from, media, { caption: '📲 Escanea para pagar' });
        }
    } catch(e) {}
}
// ─────────────────────────────────────────────────────────────

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

// ── Mensajes ──────────────────────────────────────────────────
client.on('message', async msg => {
    try {
        if (msg.fromMe) return;
        if (msg.from === 'status@broadcast') return;
        if (msg.from.endsWith('@newsletter') || msg.from.endsWith('@g.us')) return;
        if (msg.isStatus) return;
        if (!msg.from.endsWith('@c.us') && !msg.from.endsWith('@lid')) return;

        const waNumber = msg.from.replace(/@.*$/,'');
        const body     = (msg.body||'').trim();
        const lower    = body.toLowerCase();

        if (!FLOW) await loadFlow();

        const session     = await getSession(waNumber);
        let { state: currentState, data: sessionData } = session;
        const stateConfig = FLOW.states[currentState] || FLOW.states['inicio'];

        console.log(`📨 [${BOT_TYPE}] ${waNumber} | ${currentState} | ${body.substring(0,40)}`);

        // Buscar transición aplicable
        let transition = null;
        for (const t of (stateConfig.transitions || [])) {
            if (matchTrigger(t.trigger, msg, body, lower)) { transition = t; break; }
        }

        if (!transition) {
            await enviar(msg, fillMessage(stateConfig.message, buildVars(sessionData)));
            return;
        }

        sessionData = await executeAction(msg, waNumber, body, transition, sessionData);

        const nextStateKey = transition.to || currentState;
        await saveSession(waNumber, nextStateKey, sessionData);

        const nextState = FLOW.states[nextStateKey];
        if (nextState?.message) {
            await enviar(msg, fillMessage(nextState.message, buildVars(sessionData)));
            if (nextStateKey === 'esperando_pago') await enviarImagen(msg);
        }

    } catch(e) { console.error(`❌ [${BOT_TYPE}] error:`, e.message); }
});

// ── Trigger matching ──────────────────────────────────────────
function matchTrigger(trigger, msg, body, lower) {
    if (trigger === null || trigger === undefined || trigger === '') return true; // catch-all
    switch(trigger) {
        case 'PEDIDO+TOTAL':  return body.includes('PEDIDO') && body.includes('TOTAL');
        case 'location':      return msg.type === 'location';
        case 'text_address':  return msg.type === 'text' && body.length > 5 && !body.includes('PEDIDO');
        case 'image':         return msg.hasMedia && msg.type === 'image';
        case 'si_llego':      return /^(sí llegó|si llegó|si llego|sí llego|✅|todo bien|llegó|llego)/.test(lower);
        case 'no_llego':      return /^(no llegó|no llego|❌|problema)/.test(lower);
        case 'confirmar_pago': case 'en_camino': case 'entregado': return false;
        default:              return lower === trigger.toLowerCase();
    }
}

// ── Acciones ──────────────────────────────────────────────────
async function executeAction(msg, waNumber, body, transition, sessionData) {
    const action = transition.action;
    if (!action) return sessionData;

    switch(action) {
        case 'save_name': {
            const nombre = body.replace(/^hola[,!.\s]*/i,'').trim() || body;
            return { ...sessionData, nombre: nombre || body };
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
        case 'save_payment': {
            if (msg.hasMedia && msg.type === 'image') {
                try {
                    const media = await msg.downloadMedia();
                    if (sessionData.orderId && media?.data)
                        await laravelPost(`wa/order/${sessionData.orderId}/payment-proof`,{ image_base64:media.data, mimetype:media.mimetype||'image/jpeg' });
                } catch(e) { console.error('Comprobante:', e.message); }
            }
            break;
        }

        // ── Acciones de RIFA ──────────────────────────────────
        case 'show_rifas': {
            // Carga rifas de la API y las envía como mensajes con imagen
            try {
                const params = new URLSearchParams({ token: BOT_TOKEN, bot: BOT_TYPE });
                const res    = await fetch(`${LARAVEL_URL}/wa/rifas?${params}`);
                const data   = await res.json();
                if (data.ok && data.rifas.length > 0) {
                    sessionData = { ...sessionData, rifas: data.rifas };
                    // Enviar cada rifa con su imagen
                    for (let i = 0; i < data.rifas.length; i++) {
                        const r   = data.rifas[i];
                        const txt = `*${i+1}.* ${r.texto}`;
                        if (r.imagen_url) {
                            try {
                                const media = await MessageMedia.fromUrl(r.imagen_url, { unsafeMime: true });
                                await client.sendMessage(msg.from, media, { caption: txt });
                            } catch(e) {
                                await enviar(msg, txt);
                            }
                        } else {
                            await enviar(msg, txt);
                        }
                    }
                } else {
                    await enviar(msg, '⚠️ No hay rifas disponibles en este momento.');
                }
            } catch(e) { console.error('show_rifas:', e.message); }
            break;
        }

        case 'select_rifa': {
            const rifas   = sessionData.rifas || [];
            const selIdx  = parseInt(body) - 1;
            const selRifa = rifas[selIdx];
            if (selRifa) {
                sessionData = { ...sessionData, rifaId: selRifa.id, rifaNombre: selRifa.nombre,
                    rifaPrecio: selRifa.precio_ticket, rifaMin: selRifa.min_tickets };
            } else {
                // Intentar buscar por nombre
                const found = rifas.find(r => r.nombre.toLowerCase().includes(lower));
                if (found) {
                    sessionData = { ...sessionData, rifaId: found.id, rifaNombre: found.nombre,
                        rifaPrecio: found.precio_ticket, rifaMin: found.min_tickets };
                }
            }
            break;
        }

        case 'save_quantity':
        case 'save_quantity|create_rifa_order':
        case 'save_quantity_create_order': {
            const qty    = parseInt(body.replace(/\D/g,'')) || 1;
            const precio = sessionData.rifaPrecio || 0;
            const total  = (qty * precio).toFixed(2);
            sessionData  = { ...sessionData, rifaTickets: qty, rifaTotal: total };

            // Si la acción incluye create_rifa_order, crear el pedido ya
            if (action !== 'save_quantity') {
                try {
                    const resp = await laravelPost('wa/rifa-order', {
                        rifa_id: sessionData.rifaId, tickets: qty, wa_number: waNumber,
                    });
                    if (resp?.ok) {
                        sessionData = { ...sessionData,
                            orderNumber: resp.order_number,
                            rifaTotal:   Number(resp.monto).toFixed(2),
                            rifaTickets: resp.tickets,
                            rifaNombre:  resp.rifa_nombre,
                            rifaOrderId: resp.order_id,
                        };
                    }
                } catch(e) { console.error('create_rifa_order:', e.message); }
            }
            break;
        }

        case 'create_rifa_order': {
            try {
                const resp = await laravelPost('wa/rifa-order', {
                    rifa_id:  sessionData.rifaId,
                    tickets:  sessionData.rifaTickets || 1,
                    wa_number: waNumber,
                });
                if (resp?.ok) {
                    sessionData = { ...sessionData,
                        orderNumber: resp.order_number,
                        rifaTotal:   resp.monto.toFixed(2),
                        rifaTickets: resp.tickets,
                        rifaNombre:  resp.rifa_nombre,
                        rifaOrderId: resp.order_id,
                    };
                }
            } catch(e) { console.error('create_rifa_order:', e.message); }
            break;
        }

        case 'save_rifa_payment': {
            if (msg.hasMedia && msg.type === 'image') {
                try {
                    const media = await msg.downloadMedia();
                    if (sessionData.rifaOrderId && media?.data)
                        await laravelPost(`wa/rifa/${sessionData.rifaOrderId}/payment-proof`,
                            { image_base64: media.data, mimetype: media.mimetype || 'image/jpeg' });
                } catch(e) { console.error('save_rifa_payment:', e.message); }
            }
            break;
        }
    }
    return sessionData;
}

client.initialize();

// ── Servidor HTTP (acciones admin) ────────────────────────────
const MENSAJES_ADMIN = {
    confirmar_pago: n => `✅ *¡Pago confirmado!*\n\nEstamos preparando tu pedido 📦\nTe avisamos cuando salga en camino 🚚\n\n_${n}_`,
    en_camino:      n => `🚚 *¡Tu pedido ya va en camino!*\n\nTiempo estimado: 30–45 minutos ⏱️\n\n_${n}_`,
    entregado:      n => `📦 *¡Tu pedido fue entregado!*\n\n¿Todo llegó bien?\n✅ *Si llegó*  ❌ *No llegó*\n\n_${n}_`,
};

const server = http.createServer(async (req, res) => {
    if (req.method !== 'POST' || req.url !== '/action') { res.writeHead(404); res.end(); return; }
    let raw = '';
    req.on('data', c => raw += c);
    req.on('end', async () => {
        try {
            const data = JSON.parse(raw);
            if (data.token !== BOT_TOKEN) { res.writeHead(401); res.end(); return; }
            const waNumber = data.wa_number.replace(/\D/g,'');
            const waId     = waNumber + '@c.us';

            // Acción especial: enviar ticket de rifa (mensaje libre desde Laravel)
            if (data.action === 'send_ticket' && data.message) {
                await client.sendMessage(waId, data.message);
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
        } catch(e) { res.writeHead(500); res.end(); }
    });
});

server.on('error', e => {
    if (e.code === 'EADDRINUSE') setTimeout(()=>server.listen(BOT_PORT,'127.0.0.1'),3000);
});
server.listen(BOT_PORT,'127.0.0.1',()=>console.log(`🌐 [${BOT_TYPE}] HTTP en puerto ${BOT_PORT}`));

// ── Resiliencia ───────────────────────────────────────────────
client.on('disconnected', () => {
    saveStatus({status:'offline',qr:null,updated_at:new Date().toISOString()});
    console.warn(`⚠️  [${BOT_TYPE}] desconectado, reconectando en 10s...`);
    setTimeout(()=>{ try { client.initialize(); } catch(e){} }, 10000);
});
client.on('auth_failure', () => saveStatus({status:'offline',qr:null,updated_at:new Date().toISOString()}));
process.on('unhandledRejection', r => console.error(`⚠️  [${BOT_TYPE}] UnhandledRejection:`, r));
process.on('uncaughtException',  e => console.error(`⚠️  [${BOT_TYPE}] UncaughtException:`, e.message));

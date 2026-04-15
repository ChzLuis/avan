/**
 * Bot Principal — guiado por flujo de BD (Laravel API)
 * Fallback automático a flow-main.json si la API no responde
 */
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode  = require('qrcode-terminal');
const path    = require('path');
const fs      = require('fs');
const http    = require('http');

// ── CONFIGURACIÓN ─────────────────────────────────────────────
const BOT_TOKEN    = 'wa-bot-secret-2024';
const LARAVEL_URL  = 'http://127.0.0.1';
const BOT_PORT     = 3001;
const BOT_TYPE     = 'main';
const FLOW_FILE    = path.join(__dirname, 'flow-main.json');
const STATUS_FILE  = path.join(__dirname, 'bot-status.json');
const QR_IMAGE     = path.join(__dirname, 'qr-pago.png');

// Tarifas delivery
const BASE_LAT     = -13.149770079285672;
const BASE_LNG     = -74.2303798295127;
const DELIVERY_MIN = 6.00;
const DELIVERY_KM  = 1.50;

// ── Estado en memoria ─────────────────────────────────────────
let FLOW       = null;   // { flow_id, bot_type, project_id, states: { key: {...} } }
let PROJECT_SLUG = null;
let NEGOCIO    = 'Bot';
let MI_NUMERO  = '';
// ─────────────────────────────────────────────────────────────

// ── Helpers HTTP ──────────────────────────────────────────────
async function laravelGet(path) {
    try {
        const res = await fetch(`${LARAVEL_URL}/${path}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return await res.json();
    } catch(e) {
        console.warn('⚠️  GET error:', path, e.message);
        return null;
    }
}

async function laravelPost(path, body) {
    try {
        const res = await fetch(`${LARAVEL_URL}/${path}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ token: BOT_TOKEN, ...body }),
        });
        return await res.json();
    } catch(e) {
        console.warn('⚠️  POST error:', path, e.message);
        return null;
    }
}
// ─────────────────────────────────────────────────────────────

// ── Cargar flujo desde API o fallback JSON ────────────────────
async function loadFlow(phone = '') {
    console.log('🔄 Cargando flujo desde API...');
    try {
        const params = new URLSearchParams({ token: BOT_TOKEN, bot: BOT_TYPE });
        if (phone) params.set('phone', phone);
        const res = await fetch(`${LARAVEL_URL}/wa/flow-config?${params}`);
        if (res.ok) {
            const data = await res.json();
            if (data.ok) {
                FLOW = data;
                // Guardar respaldo
                fs.writeFileSync(FLOW_FILE, JSON.stringify(data, null, 2));
                console.log(`✅ Flujo cargado desde API: ${Object.keys(data.states).length} estados`);
                return true;
            }
        }
    } catch(e) {
        console.warn('⚠️  API no disponible:', e.message);
    }

    // Fallback: JSON local
    if (fs.existsSync(FLOW_FILE)) {
        try {
            FLOW = JSON.parse(fs.readFileSync(FLOW_FILE, 'utf8'));
            console.log(`📂 Flujo cargado desde archivo local: ${Object.keys(FLOW.states).length} estados`);
            return true;
        } catch(e) {
            console.error('❌ Error leyendo flow-main.json:', e.message);
        }
    }

    // Flujo mínimo de emergencia
    console.warn('🆘 Usando flujo de emergencia');
    FLOW = {
        flow_id: 0, bot_type: BOT_TYPE, project_id: 0,
        states: {
            inicio: {
                key: 'inicio', message: '✅ Recibimos tu mensaje. Un asesor te contactará pronto.\n_{negocio}_',
                input_type: 'text', transitions: []
            }
        }
    };
    return false;
}

// Recargar flujo cada 5 minutos para capturar cambios del panel
setInterval(() => loadFlow(), 5 * 60 * 1000);
// ─────────────────────────────────────────────────────────────

// ── Sesión del cliente ────────────────────────────────────────
async function getSession(waNumber) {
    if (!FLOW?.flow_id) return { state: 'inicio', data: {} };
    const res = await laravelGet(`wa/session?token=${BOT_TOKEN}&flow_id=${FLOW.flow_id}&wa_number=${waNumber}`);
    return res?.ok ? { state: res.state, data: res.data || {} } : { state: 'inicio', data: {} };
}

async function saveSession(waNumber, state, data = {}) {
    if (!FLOW?.flow_id) return;
    await laravelPost('wa/session', {
        flow_id: FLOW.flow_id,
        wa_number: waNumber,
        state,
        data,
    });
}
// ─────────────────────────────────────────────────────────────

// ── Utilidades ────────────────────────────────────────────────
function distanciaKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}
function calcularDelivery(km) {
    return Math.ceil(Math.max(DELIVERY_MIN, km * DELIVERY_KM) * 2) / 2;
}

function fillMessage(template, vars) {
    return template.replace(/\{(\w+)\}/g, (_, k) => vars[k] ?? '');
}

function saveStatus(data) {
    try { fs.writeFileSync(STATUS_FILE, JSON.stringify(data, null, 2)); } catch(e) {}
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
    } catch(e) { console.error('Imagen:', e.message); }
}
// ─────────────────────────────────────────────────────────────

// ── Cliente WhatsApp ──────────────────────────────────────────
const client = new Client({
    authStrategy: new LocalAuth({ clientId: 'whatsbot' }),
    puppeteer: { headless: true, args: ['--no-sandbox', '--disable-setuid-sandbox'] }
});

client.on('qr', qr => {
    console.log('\n📱 Escanea este QR:\n');
    qrcode.generate(qr, { small: true });
    try {
        const QRCode = require('qrcode');
        QRCode.toDataURL(qr, (err, url) => {
            if (!err) saveStatus({ status: 'qr', qr: url, updated_at: new Date().toISOString() });
        });
    } catch(e) {
        saveStatus({ status: 'qr', qr: null, updated_at: new Date().toISOString() });
    }
});

client.on('ready', async () => {
    saveStatus({ status: 'connected', qr: null, updated_at: new Date().toISOString() });

    // Cargar config del proyecto
    try {
        const myNumber = client.info.wid.user;
        const res = await fetch(`${LARAVEL_URL}/wa/config?token=${BOT_TOKEN}&phone=${myNumber}`);
        const data = await res.json();
        if (data.ok) {
            PROJECT_SLUG = data.project_slug;
            NEGOCIO      = data.negocio;
            MI_NUMERO    = data.whatsapp || data.phone || myNumber;
            console.log(`✅ Bot conectado — ${NEGOCIO} (${PROJECT_SLUG})`);
        } else {
            console.warn(`⚠️  Sin proyecto asignado al número ${myNumber}`);
        }
        // Cargar flujo con el número del bot para identificar el proyecto
        await loadFlow(myNumber);
    } catch(e) {
        console.error('Error cargando config:', e.message);
        await loadFlow();
    }
    console.log('Esperando mensajes...\n');
});

// ── Manejo de mensajes ────────────────────────────────────────
client.on('message', async msg => {
    try {
        // Filtros básicos
        if (msg.fromMe) return;
        if (msg.from === 'status@broadcast') return;
        if (msg.from.endsWith('@newsletter')) return;
        if (msg.from.endsWith('@g.us')) return;
        if (msg.isStatus) return;
        if (!msg.from.endsWith('@c.us') && !msg.from.endsWith('@lid')) return;

        const waNumber = msg.from.replace(/@.*$/, '');
        const body     = (msg.body || '').trim();
        const lower    = body.toLowerCase();

        console.log(`📨 ${waNumber} | state:? | type:${msg.type} | ${body.substring(0, 40)}`);

        if (!FLOW) { await loadFlow(); }

        // ── Obtener sesión actual ─────────────────────────────
        const session = await getSession(waNumber);
        let { state: currentState, data: sessionData } = session;
        console.log(`   estado actual: ${currentState}`);

        const stateConfig = FLOW.states[currentState] || FLOW.states['inicio'];

        // ── Detectar transición aplicable ─────────────────────
        let transition = null;

        for (const t of (stateConfig.transitions || [])) {
            if (matchTrigger(t.trigger, msg, body, lower)) {
                transition = t;
                break;
            }
        }

        // ── Si no hay transición, re-enviar mensaje del estado actual ─
        if (!transition) {
            // Excepción: si está en inicio y no hay trigger, ignorar silenciosamente
            if (currentState === 'inicio') return;

            const vars = buildVars(sessionData);
            await enviar(msg, fillMessage(stateConfig.message, vars));
            return;
        }

        // ── Ejecutar acción de la transición ──────────────────
        sessionData = await executeAction(msg, waNumber, body, transition, sessionData);

        // ── Mover al siguiente estado ─────────────────────────
        const nextStateKey = transition.to || currentState;
        await saveSession(waNumber, nextStateKey, sessionData);

        // ── Enviar mensaje del nuevo estado ───────────────────
        const nextState = FLOW.states[nextStateKey];
        if (nextState?.message) {
            const vars = buildVars(sessionData);
            await enviar(msg, fillMessage(nextState.message, vars));

            // Si el estado espera pago, enviar QR imagen
            if (nextStateKey === 'esperando_pago') {
                await enviarImagen(msg);
            }
        }

    } catch(e) {
        console.error('❌ Error en mensaje:', e.message, e.stack);
    }
});

// ── Detectar si un trigger coincide con el mensaje ────────────
function matchTrigger(trigger, msg, body, lower) {
    if (!trigger) return false;
    switch(trigger) {
        case 'PEDIDO+TOTAL':
            return body.includes('PEDIDO') && body.includes('TOTAL');
        case 'location':
            return msg.type === 'location';
        case 'text_address':
            return msg.type === 'text' && body.length > 5 && !body.includes('PEDIDO');
        case 'image':
            return msg.hasMedia && msg.type === 'image';
        case 'si_llego':
            return /^(sí llegó|si llegó|si llego|sí llego|✅|todo bien|todo perfecto|llegó|llego)/.test(lower);
        case 'no_llego':
            return /^(no llegó|no llego|❌|problema|tuve un problema)/.test(lower);
        // triggers de admin (se usan desde el panel, no desde WhatsApp)
        case 'confirmar_pago':
        case 'en_camino':
        case 'entregado':
            return false;
        default:
            // trigger literal: comparar texto exacto
            return lower === trigger.toLowerCase();
    }
}

// ── Ejecutar acción y retornar sessionData actualizado ────────
async function executeAction(msg, waNumber, body, transition, sessionData) {
    const action = transition.action;
    if (!action) return sessionData;

    switch(action) {
        case 'create_order': {
            // Parsear pedido del catálogo
            const nombreMatch = body.match(/👤 \*?Cliente:\*? (.+)/);
            const totalMatch  = body.match(/💰 \*?TOTAL:?\*? ?:? ?([A-Z]{0,3}\.?\s*[\d.,]+)/);
            const pedidoMatch = body.match(/📋 \*?N° Pedido:\*? (.+)/);
            const cliente     = nombreMatch ? nombreMatch[1].trim() : 'Cliente';
            const total       = totalMatch  ? totalMatch[1].trim()  : '';
            const nPedido     = pedidoMatch ? pedidoMatch[1].trim() : '';
            const subtotalNum = parseFloat((total || '0').replace(/[^0-9.]/g,'')) || 0;

            const items = [];
            const itemRegex = /\d+\.\s+\*?(.+?)\*?\n\s+•\s*Cantidad:\s*([\d.,]+)[^\n]*\n\s+•\s*Precio unit\.:\s*[^\d]*([\d.,]+)/g;
            let m;
            while ((m = itemRegex.exec(body)) !== null) {
                items.push({ name: m[1].trim(), qty: parseInt(m[2].replace(/[.,]/g,''))||1, price: parseFloat(m[3].replace(',','.'))||0 });
            }

            const resp = await laravelPost('wa/order', {
                project_slug: PROJECT_SLUG,
                wa_number: waNumber,
                client_name: cliente,
                n_pedido: nPedido,
                items: items.length ? items : [{ name: 'Pedido desde catálogo', qty: 1, price: subtotalNum }],
                subtotal: subtotalNum,
            });

            sessionData = { ...sessionData, cliente, total, nPedido, subtotalNum, orderId: resp?.order_id || null };
            console.log(`📦 ${nPedido} — ${cliente} — ${total} | BD: #${resp?.order_id}`);
            break;
        }

        case 'save_delivery': {
            let deliveryInfo = {};
            if (msg.type === 'location') {
                const loc      = msg.location;
                const km       = distanciaKm(BASE_LAT, BASE_LNG, loc.latitude, loc.longitude);
                const delivery = calcularDelivery(km);
                const totalFinal = ((sessionData.subtotalNum || 0) + delivery).toFixed(2);
                deliveryInfo = {
                    deliveryAddress: `GPS (${loc.latitude.toFixed(5)}, ${loc.longitude.toFixed(5)})`,
                    deliveryCost: delivery,
                    totalFinal,
                    km: km.toFixed(1),
                };
                if (sessionData.orderId) {
                    await laravelPost(`wa/order/${sessionData.orderId}/delivery`, {
                        shipping_cost: delivery,
                        delivery_address: deliveryInfo.deliveryAddress,
                    });
                }
            } else {
                deliveryInfo = { deliveryAddress: body.trim(), deliveryCost: 0, totalFinal: sessionData.total || '0', km: '?' };
                if (sessionData.orderId) {
                    await laravelPost(`wa/order/${sessionData.orderId}/delivery`, {
                        shipping_cost: 0,
                        delivery_address: body.trim(),
                    });
                }
            }
            sessionData = { ...sessionData, ...deliveryInfo };
            break;
        }

        case 'save_payment': {
            if (msg.hasMedia && msg.type === 'image') {
                try {
                    const media = await msg.downloadMedia();
                    if (sessionData.orderId && media?.data) {
                        await laravelPost(`wa/order/${sessionData.orderId}/payment-proof`, {
                            image_base64: media.data,
                            mimetype: media.mimetype || 'image/jpeg',
                        });
                        console.log(`📸 Comprobante guardado → pedido #${sessionData.orderId}`);
                    }
                } catch(e) {
                    console.error('Error guardando comprobante:', e.message);
                }
            }
            break;
        }

        case 'notify_client': {
            // Esta acción la ejecuta el panel de admin, no el bot directamente
            break;
        }
    }

    return sessionData;
}

// ── Variables para rellenar mensajes ─────────────────────────
function buildVars(data) {
    return {
        negocio:    NEGOCIO,
        mi_numero:  MI_NUMERO,
        cliente:    data.cliente    || '',
        total:      data.total      || '',
        nPedido:    data.nPedido    || data.n_pedido || '',
        n_pedido:   data.nPedido    || data.n_pedido || '',
        delivery:   data.deliveryCost != null ? Number(data.deliveryCost).toFixed(2) : '',
        total_final:data.totalFinal || data.total || '',
        km:         data.km         || '',
        direccion:  data.deliveryAddress || '',
    };
}

client.initialize();

// ── Mini servidor HTTP — acciones del portal admin ────────────
const MENSAJES_ADMIN = {
    confirmar_pago: (negocio) =>
`✅ *¡Pago confirmado!*

Estamos preparando tu pedido 📦
Te avisamos cuando salga en camino 🚚

_${negocio}_`,

    en_camino: (negocio) =>
`🚚 *¡Tu pedido ya va en camino!*

Tiempo estimado: 30–45 minutos ⏱️
Prepárate para recibirlo 📦

_${negocio}_`,

    entregado: (negocio) =>
`📦 *¡Tu pedido fue entregado!*

¿Todo llegó bien? Responde:
✅ *Si llegó* — todo perfecto
❌ *No llegó* — hubo un problema

_${negocio}_`,
};

const server = http.createServer(async (req, res) => {
    if (req.method !== 'POST' || req.url !== '/action') {
        res.writeHead(404); res.end('Not found'); return;
    }
    let raw = '';
    req.on('data', c => raw += c);
    req.on('end', async () => {
        try {
            const data = JSON.parse(raw);
            if (data.token !== BOT_TOKEN) { res.writeHead(401); res.end('Unauthorized'); return; }

            const waId    = data.wa_number.replace(/\D/g, '') + '@c.us';
            const builder = MENSAJES_ADMIN[data.action];

            if (builder && waId) {
                const texto = builder(NEGOCIO);

                // Actualizar estado de sesión en BD
                if (FLOW?.flow_id) {
                    const waNum = data.wa_number.replace(/\D/g, '');
                    const actionToState = {
                        confirmar_pago: 'pago_recibido',
                        en_camino:      'en_camino',
                        entregado:      'entregado',
                    };
                    const nextState = actionToState[data.action];
                    if (nextState) {
                        await laravelPost('wa/session', {
                            flow_id: FLOW.flow_id,
                            wa_number: waNum,
                            state: nextState,
                        });
                    }
                }

                await client.sendMessage(waId, texto);
                console.log(`📤 Admin acción "${data.action}" → ${data.wa_number}`);
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ ok: true }));
            } else {
                res.writeHead(400); res.end('Acción desconocida');
            }
        } catch(e) {
            console.error('HTTP server error:', e.message);
            res.writeHead(500); res.end('Error');
        }
    });
});

server.on('error', (e) => {
    if (e.code === 'EADDRINUSE') {
        console.warn(`⚠️  Puerto ${BOT_PORT} ocupado, reintentando en 3s...`);
        setTimeout(() => server.listen(BOT_PORT, '127.0.0.1'), 3000);
    } else {
        console.error('Server error:', e.message);
    }
});

server.listen(BOT_PORT, '127.0.0.1', () => {
    console.log(`🌐 Servidor de acciones en puerto ${BOT_PORT}`);
});

// ── Watchdog: reintentar reconexión si el bot se desconecta ──
client.on('disconnected', async (reason) => {
    console.warn(`⚠️  Bot desconectado: ${reason}`);
    saveStatus({ status: 'offline', qr: null, updated_at: new Date().toISOString() });
    console.log('🔄 Intentando reconectar en 10s...');
    setTimeout(() => {
        try { client.initialize(); } catch(e) { console.error('Reconexión fallida:', e.message); }
    }, 10000);
});

client.on('auth_failure', (msg) => {
    console.error('❌ Auth failure:', msg);
    saveStatus({ status: 'offline', qr: null, updated_at: new Date().toISOString() });
});

// Capturar errores no manejados para que el proceso no muera
process.on('unhandledRejection', (reason) => {
    console.error('⚠️  UnhandledRejection:', reason);
});
process.on('uncaughtException', (err) => {
    console.error('⚠️  UncaughtException:', err.message);
});

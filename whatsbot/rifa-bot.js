const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode  = require('qrcode-terminal');
const path    = require('path');
const fs      = require('fs');
const http    = require('http');

// ── CONFIGURACIÓN ─────────────────────────────────────────────
const LARAVEL_URL = 'http://127.0.0.1/';
const BOT_PORT    = 3002; // Puerto diferente al bot principal
const PROJECT_ID  = null; // Opcional: ID del proyecto en Laravel

// Imágenes de los planes (rutas locales o URLs)
// Coloca las imágenes en whatsbot/rifa-images/
const PLAN_IMAGES = {
    1: path.join(__dirname, 'rifa-images', 'plan1.jpg'),
    2: path.join(__dirname, 'rifa-images', 'plan2.jpg'),
    3: path.join(__dirname, 'rifa-images', 'plan3.jpg'),
    4: path.join(__dirname, 'rifa-images', 'plan4.jpg'),
};

const PLANES = {
    1: { nombre: 'Probar mi suerte',     tickets: 1,  monto: 10  },
    2: { nombre: 'Duplica tu suerte',    tickets: 2,  monto: 20  },
    3: { nombre: 'Quintuplica tu suerte',tickets: 5,  monto: 50  },
    4: { nombre: 'Asegura suertudazo',   tickets: 10, monto: 100 },
};

// ── Estado de conversaciones ───────────────────────────────────
// { [from]: { step, ventaId, plan } }
const STATE_FILE = path.join(__dirname, 'rifa-estados.json');
function cargarEstados() {
    try { return JSON.parse(fs.readFileSync(STATE_FILE, 'utf8')); } catch(e) { return {}; }
}
function guardarEstados(obj) {
    try { fs.writeFileSync(STATE_FILE, JSON.stringify(obj, null, 2)); } catch(e) {}
}

// ── Helpers HTTP ──────────────────────────────────────────────
function laravelPost(endpoint, data) {
    return new Promise((resolve) => {
        const body = JSON.stringify(data);
        const opts = {
            hostname: '127.0.0.1',
            port: 80,
            path: '/' + endpoint,
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'Content-Length':Buffer.byteLength(body) }
        };
        const req = http.request(opts, res => {
            let raw = '';
            res.on('data', d => raw += d);
            res.on('end', () => {
                try { resolve(JSON.parse(raw)); } catch(e) { resolve(null); }
            });
        });
        req.on('error', () => resolve(null));
        req.write(body);
        req.end();
    });
}

// ── MENU PRINCIPAL ─────────────────────────────────────────────
const MENU = `🎟️ *¡Bienvenido a la Rifa!* 🎟️

Elige tu plan de membresía:

1️⃣ *Probar mi suerte*
   › 1 ticket — S/ 10.00

2️⃣ *Duplica tu suerte*
   › 2 tickets — S/ 20.00

3️⃣ *Quintuplica tu suerte*
   › 5 tickets — S/ 50.00

4️⃣ *Asegura suertudazo*
   › 10 tickets — S/ 100.00

Responde con el número de tu opción (1, 2, 3 o 4) 👇`;

// ── CLIENTE WHATSAPP ───────────────────────────────────────────
const client = new Client({
    authStrategy: new LocalAuth({ clientId: 'rifa-bot' }),
    puppeteer: { args: ['--no-sandbox','--disable-setuid-sandbox'] }
});

const QR_STATUS_FILE = path.join(__dirname, 'rifa-bot-status.json');
function saveStatus(data) {
    try { fs.writeFileSync(QR_STATUS_FILE, JSON.stringify(data, null, 2)); } catch(e) {}
}

client.on('qr', qr => {
    console.log('📱 Escanea el QR:');
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

client.on('ready', () => {
    console.log('✅ Rifa Bot conectado');
    saveStatus({ status: 'connected', qr: null, updated_at: new Date().toISOString() });
    startServer();
});

// ── MANEJO DE MENSAJES ─────────────────────────────────────────
client.on('message', async msg => {
    if (msg.from.endsWith('@g.us')) return; // Ignorar grupos
    if (msg.fromMe) return;

    const from   = msg.from;
    const waNum  = from.replace(/@.*$/, '');
    const body   = msg.body.trim();
    const lower  = body.toLowerCase();
    let estados  = cargarEstados();
    let estado   = estados[from] || { step: 'menu' };

    // ── Comandos globales ──────────────────────────────────────
    if (['menu', 'inicio', 'hola', 'hi', '0'].includes(lower)) {
        estado = { step: 'menu' };
        estados[from] = estado;
        guardarEstados(estados);
        await msg.reply(MENU);
        return;
    }

    // ── Flujo por step ─────────────────────────────────────────
    switch (estado.step) {

        case 'menu': {
            const opcion = parseInt(body);
            if (![1,2,3,4].includes(opcion)) {
                await msg.reply(MENU);
                return;
            }
            const plan = PLANES[opcion];

            // Guardar en Laravel
            const res = await laravelPost('wa/rifa/save', {
                wa_number:  waNum,
                plan:       opcion,
                project_id: PROJECT_ID,
            });

            if (!res || !res.ok) {
                await msg.reply('❌ Hubo un error. Intenta de nuevo.');
                return;
            }

            estado = { step: 'enviando_imagen', ventaId: res.id, plan: opcion };
            estados[from] = estado;
            guardarEstados(estados);

            // Enviar imagen del plan
            const imgPath = PLAN_IMAGES[opcion];
            if (fs.existsSync(imgPath)) {
                const media = MessageMedia.fromFilePath(imgPath);
                await client.sendMessage(from, media, {
                    caption: `✅ *${plan.nombre}*\n🎫 ${plan.tickets} ticket(s)\n💰 S/ ${plan.monto}.00`
                });
            }

            // Pedir comprobante de pago
            await client.sendMessage(from,
                `💳 *Instrucciones de pago:*\n\n` +
                `Monto a pagar: *S/ ${plan.monto}.00*\n\n` +
                `Realiza tu pago por Yape/Plin/transferencia y envíanos la *captura o foto del comprobante* aquí 👇`
            );

            estado.step = 'esperando_pago';
            estados[from] = estado;
            guardarEstados(estados);
            break;
        }

        case 'esperando_pago': {
            if (msg.type !== 'image') {
                await msg.reply('📸 Por favor envía una *foto o captura* de tu comprobante de pago.');
                return;
            }

            // Guardar comprobante
            const media = await msg.downloadMedia();
            if (media) {
                await laravelPost(`wa/rifa/${estado.ventaId}/payment-proof`, {
                    image_base64: media.data,
                });
            }

            estado.step = 'esperando_nombre';
            estados[from] = estado;
            guardarEstados(estados);

            await msg.reply('✅ ¡Comprobante recibido!\n\nAhora necesitamos tus datos para registrar tu boleto.\n\n¿Cuál es tu *nombre completo*? 👇');
            break;
        }

        case 'esperando_nombre': {
            if (body.length < 3) {
                await msg.reply('Por favor ingresa tu nombre completo.');
                return;
            }

            estado.nombre = body;
            estado.step   = 'esperando_dni';
            estados[from] = estado;
            guardarEstados(estados);

            await msg.reply(`👍 Gracias *${body}*!\n\nAhora ingresa tu *número de DNI* 👇`);
            break;
        }

        case 'esperando_dni': {
            if (!/^\d{8}$/.test(body)) {
                await msg.reply('⚠️ El DNI debe tener 8 dígitos. Intenta de nuevo.');
                return;
            }

            // Guardar datos en Laravel
            await laravelPost(`wa/rifa/${estado.ventaId}/data`, {
                nombre: estado.nombre,
                dni:    body,
            });

            const plan = PLANES[estado.plan];

            estado.step = 'completado';
            estados[from] = estado;
            guardarEstados(estados);

            await msg.reply(
                `🎉 *¡Registro completado!*\n\n` +
                `👤 Nombre: *${estado.nombre}*\n` +
                `🪪 DNI: *${body}*\n` +
                `🎫 Plan: *${plan.nombre}*\n` +
                `🎟️ Tickets: *${plan.tickets}*\n` +
                `💰 Monto: *S/ ${plan.monto}.00*\n\n` +
                `Estamos validando tu pago. En breve recibirás tu boleto oficial por aquí. ⏳\n\n` +
                `¡Mucha suerte! 🍀`
            );
            break;
        }

        case 'completado': {
            await msg.reply(
                `✅ Ya tienes un registro pendiente de validación.\n\n` +
                `En cuanto validemos tu pago te enviaremos tu boleto.\n\n` +
                `Escribe *menu* para ver los planes nuevamente.`
            );
            break;
        }

        default: {
            await msg.reply(MENU);
            estado = { step: 'menu' };
            estados[from] = estado;
            guardarEstados(estados);
        }
    }
});

// ── SERVIDOR HTTP para enviar mensajes desde Laravel ──────────
function startServer() {
    const server = http.createServer(async (req, res) => {
        if (req.method === 'POST' && req.url === '/send-message') {
            let body = '';
            req.on('data', d => body += d);
            req.on('end', async () => {
                try {
                    const { to, message } = JSON.parse(body);
                    await client.sendMessage(to, message);
                    res.writeHead(200); res.end(JSON.stringify({ ok: true }));
                } catch(e) {
                    res.writeHead(500); res.end(JSON.stringify({ ok: false }));
                }
            });
        } else {
            res.writeHead(404); res.end();
        }
    });
    server.listen(BOT_PORT, () => console.log(`🤖 Rifa Bot server en puerto ${BOT_PORT}`));
}

client.initialize();

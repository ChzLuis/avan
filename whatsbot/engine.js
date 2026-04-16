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
const args = Object.fromEntries(
  process.argv.slice(2).map((a) => {
    const [k, v] = a.replace("--", "").split("=");
    return [k, v];
  }),
);
const BOT_TYPE = args.bot || "main";

const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");
const qrcode = require("qrcode-terminal");
const path = require("path");
const fs = require("fs");
const http = require("http");

// ── VALIDACIONES ─────────────────────────────────────────────
function validarNombre(nombre) {
  if (!nombre || nombre.trim().length < 2) {
    return {
      valido: false,
      error: "⚠️ El nombre debe tener al menos 2 caracteres.",
    };
  }
  if (nombre.trim().length > 100) {
    return {
      valido: false,
      error: "⚠️ El nombre es demasiado largo (máximo 100 caracteres).",
    };
  }
  if (!/^[a-zA-ZáéíóúñÑüÜ\s]+$/.test(nombre.trim())) {
    return {
      valido: false,
      error: "⚠️ El nombre solo puede contener letras y espacios.",
    };
  }
  return { valido: true, error: null, valor: nombre.trim() };
}

function validarDNI(dni) {
  if (!dni || dni.trim() === "") {
    return { valido: false, error: "⚠️ Debes ingresar tu número de DNI." };
  }

  const dniLimpio = dni.toString().replace(/\D/g, "");

  if (dniLimpio.length !== 8) {
    return {
      valido: false,
      error: "⚠️ El DNI debe tener exactamente 8 dígitos.",
    };
  }

  if (dniLimpio === "00000000") {
    return { valido: false, error: "⚠️ DNI inválido." };
  }

  return { valido: true, error: null, valor: dniLimpio };
}

function validarTelefono(telefono) {
  if (!telefono || telefono.trim() === "") {
    return { valido: false, error: "⚠️ Debes ingresar tu número de teléfono." };
  }

  let telefonoLimpio = telefono.toString().replace(/\D/g, "");

  if (telefonoLimpio.startsWith("51") && telefonoLimpio.length === 11) {
    telefonoLimpio = telefonoLimpio.substring(2);
  }

  if (telefonoLimpio.length !== 9) {
    return {
      valido: false,
      error: "⚠️ El número debe tener 9 dígitos (ejemplo: 987654321).",
    };
  }

  if (!telefonoLimpio.startsWith("9")) {
    return {
      valido: false,
      error: "⚠️ Debe ser un número de celular que empiece con 9.",
    };
  }

  return { valido: true, error: null, valor: telefonoLimpio };
}

function validarCorreo(email) {
  if (!email || email.trim() === "") {
    return { valido: false, error: "⚠️ Debes ingresar tu correo electrónico." };
  }

  const emailRegex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
  if (!emailRegex.test(email.trim())) {
    return {
      valido: false,
      error: "⚠️ Formato de correo inválido. Ejemplo: usuario@dominio.com",
    };
  }

  return { valido: true, error: null };
}

function validarFormularioCompleto(nombre, dni, telefono) {
  const errores = [];

  const nombreValid = validarNombre(nombre);
  if (!nombreValid.valido) errores.push(nombreValid.error);

  const dniValid = validarDNI(dni);
  if (!dniValid.valido) errores.push(dniValid.error);

  const telefonoValid = validarTelefono(telefono);
  if (!telefonoValid.valido) errores.push(telefonoValid.error);

  if (errores.length > 0) {
    return { valido: false, errores };
  }

  return {
    valido: true,
    datos: {
      nombre: nombreValid.valor,
      dni: dniValid.valor,
      telefono: telefonoValid.valor,
    },
  };
}

// ── CONFIGURACIÓN ─────────────────────────────────────────────
if (process.env.NODE_TLS_REJECT_UNAUTHORIZED === undefined)
  process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

const BOT_TOKEN = "wa-bot-secret-2024";

function detectLaravelUrl() {
  if (process.env.LARAVEL_URL) return process.env.LARAVEL_URL;
  try {
    const envFile = fs.readFileSync(path.join(__dirname, "../.env"), "utf8");
    const match = envFile.match(/^APP_URL=(.+)$/m);
    if (match) return match[1].trim().replace(/["']/g, "");
  } catch (e) {}
  return "http://127.0.0.1";
}

function detectChromium() {
  if (process.env.CHROMIUM_PATH && fs.existsSync(process.env.CHROMIUM_PATH))
    return process.env.CHROMIUM_PATH;
  const candidates = [
    "/usr/bin/chromium-browser",
    "/usr/bin/chromium",
    "/usr/bin/google-chrome",
    "/usr/bin/google-chrome-stable",
  ];
  return candidates.find((p) => fs.existsSync(p)) || null;
}

const LARAVEL_URL = detectLaravelUrl();
const FLOW_FILE = path.join(__dirname, `flow-${BOT_TYPE}.json`);
const STATUS_FILE = path.join(__dirname, `${BOT_TYPE}-status.json`);
const QR_IMAGE = path.join(__dirname, "qr-pago.png");
console.log(`🌐 Laravel URL: ${LARAVEL_URL}`);

const BASE_LAT = -13.149770079285672;
const BASE_LNG = -74.2303798295127;
const DELIVERY_MIN = 6.0;
const DELIVERY_KM = 1.5;

let BOT_PORT = args.port
  ? parseInt(args.port)
  : BOT_TYPE === "main"
    ? 3001
    : BOT_TYPE === "rifa"
      ? 3002
      : 3003;
let FLOW = null;
let PROJECT_SLUG = null;
let NEGOCIO = "Bot";
let MI_NUMERO = "";

console.log(`🤖 Engine iniciado — bot_type: ${BOT_TYPE}`);

// ── HTTP helpers ──────────────────────────────────────────────
async function laravelGet(route) {
  try {
    const res = await fetch(`${LARAVEL_URL}/${route}`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return await res.json();
  } catch (e) {
    console.warn(`⚠️  GET /${route}:`, e.message);
    return null;
  }
}

async function laravelPost(route, body) {
  try {
    const res = await fetch(`${LARAVEL_URL}/${route}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ token: BOT_TOKEN, ...body }),
    });
    return await res.json();
  } catch (e) {
    console.warn(`⚠️  POST /${route}:`, e.message);
    return null;
  }
}

function saveStatus(data) {
  try {
    fs.writeFileSync(STATUS_FILE, JSON.stringify(data, null, 2));
  } catch (e) {}
}

// ── Cargar flujo ──────────────────────────────────────────────
async function loadFlow(phone = "") {
  console.log(`🔄 Cargando flujo [${BOT_TYPE}]...`);
  try {
    const params = new URLSearchParams({ token: BOT_TOKEN, bot: BOT_TYPE });
    if (phone) params.set("phone", phone);
    const res = await fetch(`${LARAVEL_URL}/wa/flow-config?${params}`);
    if (res.ok) {
      const data = await res.json();
      if (data.ok) {
        FLOW = data;
        fs.writeFileSync(FLOW_FILE, JSON.stringify(data, null, 2));
        console.log(
          `✅ Flujo cargado desde API: ${Object.keys(data.states).length} estados`,
        );
        return;
      }
    }
  } catch (e) {
    console.warn("⚠️  API no disponible:", e.message);
  }

  if (fs.existsSync(FLOW_FILE)) {
    try {
      FLOW = JSON.parse(fs.readFileSync(FLOW_FILE, "utf8"));
      console.log(
        `📂 Flujo desde archivo local: ${Object.keys(FLOW.states).length} estados`,
      );
      return;
    } catch (e) {
      console.error("❌ Error leyendo flow json:", e.message);
    }
  }

  console.warn("🆘 Flujo de emergencia");
  FLOW = {
    flow_id: 0,
    bot_type: BOT_TYPE,
    project_id: 0,
    states: {
      inicio: {
        key: "inicio",
        message:
          "✅ Recibimos tu mensaje. Un asesor te contactará pronto.\n_{negocio}_",
        input_type: "text",
        transitions: [],
      },
    },
  };
}

setInterval(() => loadFlow(), 5 * 60 * 1000);

// ── Sesión ────────────────────────────────────────────────────
async function getSession(waNumber) {
  if (!FLOW?.flow_id) return { state: "inicio", data: {} };
  const res = await laravelGet(
    `wa/session?token=${BOT_TOKEN}&flow_id=${FLOW.flow_id}&wa_number=${waNumber}`,
  );
  return res?.ok
    ? { state: res.state, data: res.data || {} }
    : { state: "inicio", data: {} };
}

async function saveSession(waNumber, state, data = {}) {
  if (!FLOW?.flow_id) return;
  await laravelPost("wa/session", {
    flow_id: FLOW.flow_id,
    wa_number: waNumber,
    state,
    data,
  });
}

// ── Utilidades ────────────────────────────────────────────────
function distanciaKm(lat1, lng1, lat2, lng2) {
  const R = 6371,
    dLat = ((lat2 - lat1) * Math.PI) / 180,
    dLng = ((lng2 - lng1) * Math.PI) / 180;
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLng / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}
function calcularDelivery(km) {
  return Math.ceil(Math.max(DELIVERY_MIN, km * DELIVERY_KM) * 2) / 2;
}
function fillMessage(tpl, vars) {
  vars = vars || {};
  return (tpl || "")
    .replace(/\\n/g, "\n")
    .replace(/\{(\w+)\}/g, (_, k) => (vars[k] !== undefined ? vars[k] : ""));
}
function buildVars(data) {
  data = data || {};

  return {
    negocio: NEGOCIO || "Bot",
    mi_numero: MI_NUMERO || "",
    cliente: data.cliente || data.nombre || "",
    total: data.total || "0",
    nPedido: data.nPedido || "",
    n_pedido: data.nPedido || "",
    delivery:
      data.deliveryCost != null ? Number(data.deliveryCost).toFixed(2) : "",
    total_final: data.totalFinal || data.total || "0",
    km: data.km || "",
    direccion: data.deliveryAddress || "",
    order_number: data.orderNumber || "",
    nombre: data.nombre || "",
    documento: data.documento || data.dni || "",
    dni: data.documento || data.dni || "",
    telefono: data.telefono || data.celular || "",
    celular: data.telefono || data.celular || "",
    ciudad: data.ciudad || "",
    item_nombre: data.itemNombre || data.rifaNombre || "",
    item_precio: data.itemPrecio
      ? Number(data.itemPrecio).toFixed(2)
      : data.rifaPrecio
        ? Number(data.rifaPrecio).toFixed(2)
        : "0",
    item_cantidad: data.itemCantidad || data.rifaTickets || "1",
    item_total: data.itemTotal || data.rifaTotal || "0",
    rifa_nombre: data.itemNombre || data.rifaNombre || "",
    rifa_precio: data.itemPrecio
      ? Number(data.itemPrecio).toFixed(2)
      : data.rifaPrecio
        ? Number(data.rifaPrecio).toFixed(2)
        : "0",
    rifa_tickets: data.itemCantidad || data.rifaTickets || "1",
    rifa_total: data.itemTotal || data.rifaTotal || "0",
    rifas_lista: data.rifasLista || "",
    lista: data.rifasLista || "",
  };
}

// ── Cargar lista de productos ─────────────────────────────────
async function loadRifasLista(sessionData) {
  sessionData = sessionData || {};
  try {
    const params = new URLSearchParams({ token: BOT_TOKEN, bot: BOT_TYPE });
    const res = await fetch(`${LARAVEL_URL}/wa/rifas?${params}`);
    const data = await res.json();
    if (!data.ok || !data.rifas?.length)
      return {
        ...sessionData,
        rifasLista: "⚠️ No hay productos disponibles en este momento.",
      };

    const lista = data.rifas
      .map((r, i) => `*${i + 1}.* ${r.texto}`)
      .join("\n\n");
    return { ...sessionData, rifas: data.rifas, rifasLista: lista };
  } catch (e) {
    console.error("loadRifasLista:", e.message);
    return sessionData;
  }
}

// ── Enviar lista con imágenes ────────────────────────────────
async function enviarListaConImagenes(msg, rifas, encabezado) {
  if (!msg || !msg.from) {
    console.error("❌ enviarListaConImagenes: msg inválido");
    return false;
  }

  if (!rifas || !Array.isArray(rifas) || rifas.length === 0) {
    console.warn("⚠️ No hay rifas para enviar");
    try {
      await enviar(msg, "⚠️ No hay productos disponibles en este momento.");
    } catch (e) {
      console.error("Error enviando mensaje:", e.message);
    }
    return false;
  }

  try {
    const header = (encabezado || "🎰 *Productos disponibles:*").split("\n")[0];
    await enviar(msg, header);

    for (let i = 0; i < rifas.length; i++) {
      const r = rifas[i];
      if (!r) continue;

      const caption = `*${i + 1}.* ${r.texto || r.nombre || "Producto"}`;

      if (r.imagen_url && r.imagen_url !== "" && r.imagen_url !== "null") {
        try {
          const timeoutPromise = new Promise((_, reject) =>
            setTimeout(() => reject(new Error("Timeout")), 30000),
          );

          const mediaPromise = MessageMedia.fromUrl(r.imagen_url, {
            unsafeMime: true,
            timeout: 30000,
          });

          const media = await Promise.race([mediaPromise, timeoutPromise]);

          if (media && media.data) {
            await client.sendMessage(msg.from, media, { caption });
            console.log(`✅ Imagen enviada: ${r.texto || r.nombre}`);
            continue;
          }
        } catch (e) {
          console.warn(`Imagen producto ${i + 1} no enviada:`, e.message);
          await enviar(msg, caption);
        }
      } else {
        await enviar(msg, caption);
      }

      await new Promise((resolve) => setTimeout(resolve, 500));
    }

    await enviar(msg, "👆 Escribe el *número* del plan que te interesa:");
    return true;
  } catch (e) {
    console.error("enviarListaConImagenes:", e.message);
    try {
      await enviar(
        msg,
        "❌ Error al mostrar los productos. Intenta nuevamente.",
      );
    } catch (err) {
      console.error("Error enviando mensaje de error:", err.message);
    }
    return false;
  }
}

async function enviar(msg, texto) {
  if (!msg || !msg.from || !texto) return;
  try {
    const chat = await msg.getChat();
    await chat.sendStateTyping();
    const delay = Math.min(1000 + texto.length * 18, 3000);
    await new Promise(r => setTimeout(r, delay));
    await chat.clearState();
    await client.sendMessage(msg.from, texto);
  } catch (e) {
    try { await client.sendMessage(msg.from, texto); } catch(e2) {
      console.error("Envío:", e2.message);
    }
  }
}
async function enviarImagen(msg) {
  try {
    if (fs.existsSync(QR_IMAGE)) {
      const media = MessageMedia.fromFilePath(QR_IMAGE);
      await client.sendMessage(msg.from, media, {
        caption: "📲 Escanea este QR con tu app *Yape* o *Plin*\n\n⚠️ *IMPORTANTE:* NO lo escanees con WhatsApp, usa solo la app de pago.",
      });
    }
  } catch (e) {}
}

// ── Cliente WhatsApp ─────────────────────────────────────────
const puppeteerConfig = {
  headless: true,
  args: [
    "--no-sandbox",
    "--disable-setuid-sandbox",
    "--disable-dev-shm-usage",
    "--disable-accelerated-2d-canvas",
    "--disable-gpu",
    "--disable-features=IsolateOrigins,site-per-process",
  ],
  timeout: 60000,
  protocolTimeout: 120000,
};
const chromiumPath = detectChromium();
if (chromiumPath) {
  puppeteerConfig.executablePath = chromiumPath;
  console.log(`🌐 Chromium: ${chromiumPath}`);
}

const client = new Client({
  authStrategy: new LocalAuth({
    clientId: `whatsbot-${BOT_TYPE}`,
    dataPath: path.join(__dirname, ".wwebjs_auth"),
  }),
  puppeteer: puppeteerConfig,
});

client.on("qr", (qr) => {
  console.log(`\n📱 QR [${BOT_TYPE}] — escanea:\n`);
  qrcode.generate(qr, { small: true });
  try {
    const QRCode = require("qrcode");
    QRCode.toDataURL(qr, (err, url) => {
      if (!err)
        saveStatus({
          status: "qr",
          qr: url,
          updated_at: new Date().toISOString(),
        });
    });
  } catch (e) {
    saveStatus({
      status: "qr",
      qr: null,
      updated_at: new Date().toISOString(),
    });
  }
});

client.on("ready", async () => {
  saveStatus({
    status: "connected",
    qr: null,
    updated_at: new Date().toISOString(),
  });
  try {
    const myNumber = client.info.wid.user;
    const res = await fetch(
      `${LARAVEL_URL}/wa/config?token=${BOT_TOKEN}&phone=${myNumber}`,
    );
    const data = await res.json();
    if (data.ok) {
      PROJECT_SLUG = data.project_slug;
      NEGOCIO = data.negocio;
      MI_NUMERO = data.whatsapp || data.phone || myNumber;
      console.log(`✅ [${BOT_TYPE}] conectado — ${NEGOCIO}`);
    }
    await loadFlow(myNumber);
  } catch (e) {
    console.error("Error ready:", e.message);
    await loadFlow();
  }
  console.log(`[${BOT_TYPE}] esperando mensajes...\n`);
});

client.on("error", async (error) => {
  if (
    error.message &&
    error.message.includes("Execution context was destroyed")
  ) {
    console.warn(
      "⚠️ Contexto de ejecución destruido, reinicializando en 5 segundos...",
    );
    setTimeout(async () => {
      try {
        await client.destroy();
        await client.initialize();
      } catch (e) {
        console.error("Error al reinicializar:", e.message);
      }
    }, 5000);
  } else {
    console.error("❌ Error en cliente:", error.message);
  }
});

// ── Mensajes ─────────────────────────────────────────────────
client.on("message", async (msg) => {
  try {
    if (msg.fromMe) return;
    if (msg.from === "status@broadcast") return;
    if (msg.from.endsWith("@newsletter") || msg.from.endsWith("@g.us")) return;
    if (msg.isStatus) return;
    if (!msg.from.endsWith("@c.us") && !msg.from.endsWith("@lid")) return;

    // Si viene de @lid, intentar obtener el número real del contacto
    let waNumber = msg.from.replace(/@.*$/, "");
    if (msg.from.endsWith("@lid")) {
        try {
            const contact = await msg.getContact();
            if (contact?.number) waNumber = contact.number;
        } catch(e) { /* usar waNumber LID como fallback */ }
    }
    const body = (msg.body || "").trim();
    const lower = body.toLowerCase();

    if (!FLOW) await loadFlow();

    // ── Mensajes no-texto fuera de lugar ─────────────────────
    const session0 = await getSession(waNumber);
    const currentState0 = session0?.state || 'inicio';
    const stateConfig0 = FLOW?.states?.[currentState0];
    const expectedType = stateConfig0?.input_type;

    if (msg.type === 'audio' || msg.type === 'ptt') {
      await enviar(msg, '🎙️ Recibí un audio, pero aquí necesito que escribas tu respuesta.\n\n' + (stateConfig0?.message ? '👇 ' + stateConfig0.message.split('\n')[0] : 'Por favor escribe un mensaje de texto.'));
      return;
    }
    if (msg.type === 'sticker') {
      await enviar(msg, '😄 ¡Gracias por el sticker! Pero necesito que escribas tu respuesta.\n\n' + (stateConfig0?.message ? '👇 ' + stateConfig0.message.split('\n')[0] : 'Por favor escribe un mensaje de texto.'));
      return;
    }
    if (msg.type === 'video') {
      await enviar(msg, '🎥 Recibí un video, pero aquí no es necesario. ' + (expectedType === 'image' ? 'Envía una *imagen* (captura de pantalla) del comprobante de pago.' : 'Por favor escribe tu respuesta.'));
      return;
    }
    if (msg.hasMedia && msg.type === 'image' && expectedType !== 'image') {
      await enviar(msg, '📸 Recibí una imagen, pero en este paso necesito que escribas tu respuesta.\n\n' + (stateConfig0?.message ? '👇 ' + stateConfig0.message.split('\n')[0] : ''));
      return;
    }
    if (msg.hasMedia && msg.type !== 'image') {
      await enviar(msg, '📎 Recibí un archivo, pero aquí solo necesito texto. Por favor escribe tu respuesta.');
      return;
    }

    const session = await getSession(waNumber);
    let currentState = session?.state || "inicio";
    let sessionData = session?.data || {};

    if (!FLOW?.states || !FLOW.states[currentState]) {
      console.warn(`⚠️ Estado inválido: ${currentState}, reiniciando a inicio`);
      currentState = "inicio";
      sessionData = {};
      await saveSession(waNumber, "inicio", {});
    }

    if (currentState === "inicio" && !session?.state) {
      const inicioState = FLOW.states["inicio"];
      if (inicioState?.message) {
        await enviar(
          msg,
          fillMessage(inicioState.message, buildVars(sessionData)),
        );
      }
      await saveSession(waNumber, "inicio", sessionData);
      return;
    }

    // ── Rate limiting ─────────────────────────────────────────
    const now = Date.now();
    if (!global._rateLimits) global._rateLimits = {};
    const rl = global._rateLimits[waNumber] || { count: 0, windowStart: now };
    if (now - rl.windowStart > 60000) { rl.count = 0; rl.windowStart = now; }
    rl.count++;
    global._rateLimits[waNumber] = rl;
    if (rl.count > 10) {
      await enviar(msg, '⏳ Por favor espera un momento antes de enviar más mensajes.');
      return;
    }

    // ── Palabras clave globales ───────────────────────────────
    const resetWords   = ['hola','menu','menú','inicio','start','comenzar','reiniciar'];
    const cancelWords  = ['cancelar','salir','terminar'];
    const ayudaWords   = ['ayuda','help','info'];
    const graciaWords  = ['gracias','thank','thanks'];
    const respFijas = {
      'cuándo es el sorteo': '📅 Los sorteos son cada domingo a las 8pm.',
      'cuando es el sorteo': '📅 Los sorteos son cada domingo a las 8pm.',
      'cómo se gana':        '🎯 Los ganadores se eligen al azar entre todos los tickets vendidos.',
      'como se gana':        '🎯 Los ganadores se eligen al azar entre todos los tickets vendidos.',
      'premios':             '🏆 Revisa los premios disponibles en nuestra descripción.',
      'tickets':             '🎫 Cada plan ya incluye tickets fijos:\n• S/10 → 1 ticket\n• S/20 → 2 tickets\n• S/50 → 5 tickets\n• S/100 → 10 tickets',
    };

    // Respuestas fijas
    if (respFijas[lower]) {
      await enviar(msg, respFijas[lower]);
      return;
    }

    // Gracias
    if (graciaWords.some(w => lower.includes(w))) {
      await enviar(msg, '🎉 ¡Gracias a ti por participar! 🍀 Mucha suerte.');
      return;
    }

    // Ayuda
    if (ayudaWords.includes(lower)) {
      await enviar(msg, `❓ *Ayuda*\n\nEstás en el paso: *${currentState}*\n\n• Escribe *MENU* para volver al inicio\n• Escribe *CANCELAR* para salir\n• Sigue las instrucciones del bot para continuar`);
      return;
    }

    // Cancelar
    if (cancelWords.includes(lower)) {
      await saveSession(waNumber, 'inicio', {});
      await enviar(msg, '👋 Proceso cancelado. Escribe *MENU* cuando quieras volver a participar.');
      return;
    }

    // Reset
    if (resetWords.includes(lower) && currentState !== 'inicio') {
      const inicioState = FLOW.states['inicio'];
      await saveSession(waNumber, 'inicio', {});
      if (inicioState?.message)
        await enviar(msg, fillMessage(inicioState.message, buildVars({})));
      return;
    }

    const stateConfig = FLOW.states[currentState];
    if (!stateConfig) {
      console.error(`❌ Estado ${currentState} no encontrado en FLOW`);
      await saveSession(waNumber, 'inicio', {});
      await enviar(msg, '🔄 Reiniciando conversación...');
      return;
    }

    console.log(`📨 [${BOT_TYPE}] ${waNumber} | ${currentState} | ${body.substring(0,40)}`);

    const validationError = validateInput(body, stateConfig);
    if (validationError) {
      // Contar intentos fallidos
      if (!global._intentos) global._intentos = {};
      const key = `${waNumber}_${currentState}`;
      global._intentos[key] = (global._intentos[key] || 0) + 1;
      if (global._intentos[key] >= 3) {
        global._intentos[key] = 0;
        await enviar(msg, validationError + '\n\n🔄 Demasiados intentos. Escribe *MENU* para reiniciar.');
      } else {
        await enviar(msg, validationError);
      }
      return;
    }
    // Limpiar intentos al tener input válido
    if (global._intentos) delete global._intentos[`${waNumber}_${currentState}`];

    let transition = null;
    for (const t of stateConfig.transitions || []) {
      if (matchTrigger(t.trigger, msg, body, lower)) {
        transition = t;
        break;
      }
    }

    if (!transition) {
      // Contar intentos sin transición
      if (!global._intentos) global._intentos = {};
      const key = `${waNumber}_${currentState}_notransition`;
      global._intentos[key] = (global._intentos[key] || 0) + 1;
      const hint = global._intentos[key] >= 2
        ? '\n\n🔄 Escribe *MENU* para reiniciar o *AYUDA* para ver opciones.'
        : '';

      // Detectar si las transiciones son numéricas (menú de opciones)
      const numericTriggers = (stateConfig.transitions || [])
        .map(t => t.trigger)
        .filter(t => /^\d+$/.test(String(t)));
      if (numericTriggers.length > 0) {
        const opciones = numericTriggers.sort((a,b) => a-b).join(', ');
        await enviar(msg, `❌ Opción no válida. Por favor elige entre: *${opciones}*${hint}`);
      } else {
        await enviar(msg, fillMessage(stateConfig.message, buildVars(sessionData)) + hint);
      }
      return;
    }
    if (global._intentos) {
      delete global._intentos[`${waNumber}_${currentState}_notransition`];
    }

    const actionResult = await executeAction(
      msg,
      waNumber,
      body,
      transition,
      sessionData,
    );
    if (actionResult === null) return;
    sessionData = actionResult ?? sessionData;

    const nextStateKey = transition.to || currentState;

    const nextState = FLOW.states[nextStateKey];
    if (nextState?.message?.includes("{rifas_lista}")) {
      sessionData = await loadRifasLista(sessionData);
    }

    await saveSession(waNumber, nextStateKey, sessionData);

    if (nextState?.message) {
      const text = fillMessage(nextState.message, buildVars(sessionData));
      const images = nextState.images || [];
      if (images.length > 0) {
        for (let i = 0; i < images.length; i++) {
          try {
            const imageUrl = images[i].startsWith("http")
              ? images[i]
              : `${LARAVEL_URL}${images[i]}`;
            const media = await MessageMedia.fromUrl(imageUrl, {
              unsafeMime: true,
            });
            const caption = i === 0 ? text : undefined;
            await client.sendMessage(
              msg.from,
              media,
              caption ? { caption } : {},
            );
          } catch (e) {
            if (i === 0) await enviar(msg, text);
            console.warn(`Imagen ${i} no enviada:`, e.message);
          }
        }
      } else {
        await enviar(msg, text);
      }
      if (nextStateKey === "esperando_pago") await enviarImagen(msg);
    }
  } catch (e) {
    console.error(`❌ [${BOT_TYPE}] error:`, e.message);
    try {
      if (msg && msg.from) {
        await client.sendMessage(
          msg.from,
          "❌ Ocurrió un error. Por favor, escribe *MENU* para reiniciar.",
        );
      }
    } catch (err) {}
  }
});

function validateInput(body, stateConfig) {
  if (!stateConfig) return null;

  const type = stateConfig.input_type;
  const errMsg = stateConfig.validation_error || null;

  if (type === "number") {
    if (!/^-?\d+(\.\d+)?$/.test(body.trim())) {
      return errMsg || "⚠️ Por favor escribe solo números.";
    }
    const num = parseFloat(body.trim());
    if (
      stateConfig.validation_min !== null &&
      stateConfig.validation_min !== undefined &&
      num < stateConfig.validation_min
    ) {
      return errMsg || `⚠️ El valor mínimo es ${stateConfig.validation_min}.`;
    }
    if (
      stateConfig.validation_max !== null &&
      stateConfig.validation_max !== undefined &&
      num > stateConfig.validation_max
    ) {
      return errMsg || `⚠️ El valor máximo es ${stateConfig.validation_max}.`;
    }
  }

  if (type === "text" && stateConfig.validation_pattern) {
    try {
      if (!new RegExp(stateConfig.validation_pattern).test(body.trim())) {
        return errMsg || "⚠️ El formato ingresado no es válido.";
      }
    } catch (e) {}
  }

  if (type === "option") {
    const validTriggers = (stateConfig.transitions || [])
      .filter((t) => t.trigger && t.trigger !== "*" && t.trigger !== "")
      .map((t) => t.trigger.toLowerCase());
    if (
      validTriggers.length > 0 &&
      !validTriggers.includes(body.trim().toLowerCase())
    ) {
      return (
        errMsg || `⚠️ Opción no válida. Escribe: ${validTriggers.join(", ")}`
      );
    }
  }

  return null;
}

function matchTrigger(trigger, msg, body, lower) {
  if (trigger === null || trigger === undefined || trigger === "") return true;
  switch (trigger) {
    case "PEDIDO+TOTAL":
      return body.includes("PEDIDO") && body.includes("TOTAL");
    case "location":
      return msg.type === "location";
    case "text_address":
      return msg.type === "text" && body.length > 5 && !body.includes("PEDIDO");
    case "image":
      return msg.hasMedia && msg.type === "image";
    case "si_llego":
      return /^(sí llegó|si llegó|si llego|sí llego|✅|todo bien|llegó|llego)/.test(
        lower,
      );
    case "no_llego":
      return /^(no llegó|no llego|❌|problema)/.test(lower);
    case "confirmar_pago":
    case "en_camino":
    case "entregado":
      return false;
    default:
      return lower === trigger.toLowerCase();
  }
}

// ── Acciones (VERSIÓN CORREGIDA SIN DUPLICADOS) ───────────────
async function executeAction(msg, waNumber, body, transition, sessionData) {
  const lower = body.toLowerCase();
  const action = transition.action;
  if (!action) return sessionData;

  sessionData = sessionData || {};

  switch (action) {
    case "save_name": {
      const nombre = body.replace(/^hola[,!.\s]*/i, "").trim() || body;
      return { ...sessionData, nombre: nombre || body };
    }

    // VALIDACIÓN DE NOMBRE INDIVIDUAL
    case "validar_nombre": {
      const validacion = validarNombre(body);
      if (!validacion.valido) {
        await enviar(msg, validacion.error);
        return null;
      }
      sessionData = { ...sessionData, nombre: validacion.valor };
      await enviar(msg, `✅ Nombre guardado: ${validacion.valor}`);
      return sessionData;
    }

    // VALIDACIÓN DE DNI INDIVIDUAL
    case "validar_dni": {
      const validacion = validarDNI(body);
      if (!validacion.valido) {
        await enviar(msg, validacion.error);
        return null;
      }
      sessionData = {
        ...sessionData,
        documento: validacion.valor,
        dni: validacion.valor,
      };
      await enviar(msg, `✅ DNI guardado: ${validacion.valor}`);
      return sessionData;
    }

    // VALIDACIÓN DE TELÉFONO INDIVIDUAL
    case "validar_telefono": {
      const validacion = validarTelefono(body);
      if (!validacion.valido) {
        await enviar(msg, validacion.error);
        return null;
      }
      sessionData = {
        ...sessionData,
        telefono: validacion.valor,
        celular: validacion.valor,
      };
      await enviar(msg, `✅ Celular guardado: ${validacion.valor}`);
      return sessionData;
    }

    // VALIDACIÓN DE FORMULARIO COMPLETO (3 campos en un mensaje)
    case "validar_formulario_completo": {
      const partes = body.split(",").map((s) => s.trim());

      let nombre = sessionData.nombre || "";
      let dni = sessionData.documento || sessionData.dni || "";
      let telefono = sessionData.telefono || "";

      if (partes.length >= 3 && !sessionData.nombre) {
        nombre = partes[0];
        dni = partes[1];
        telefono = partes[2];
      }

      const validacion = validarFormularioCompleto(nombre, dni, telefono);

      if (!validacion.valido) {
        const mensajeError =
          "❌ *DATOS INCORRECTOS*\n\n" +
          validacion.errores.map((e) => `• ${e}`).join("\n") +
          "\n\n📝 *Formato correcto:*\n`Nombre Completo, DNI, Celular`\n\nEjemplo:\n`Juan Perez, 12345678, 987654321`";

        await enviar(msg, mensajeError);
        return null;
      }

      sessionData = {
        ...sessionData,
        nombre: validacion.datos.nombre,
        documento: validacion.datos.dni,
        dni: validacion.datos.dni,
        telefono: validacion.datos.telefono,
        celular: validacion.datos.telefono,
        datos_validados: true,
      };

      const resumen =
        "✅ *DATOS VALIDADOS CORRECTAMENTE*\n\n" +
        `👤 *Nombre:* ${validacion.datos.nombre}\n` +
        `🆔 *DNI:* ${validacion.datos.dni}\n` +
        `📱 *Celular:* ${validacion.datos.telefono}\n\n` +
        "✅ Continuamos con tu pedido...";

      await enviar(msg, resumen);
      return sessionData;
    }

    case "save_nombre_documento": {
      const partes = body.split(",").map((s) => s.trim());
      let nombre = partes[0] || body;
      let documento = (partes[1] || "").replace(/\D/g, "");
      let telefono = (partes[2] || "").replace(/\D/g, "");

      const nombreValid = validarNombre(nombre);
      if (!nombreValid.valido) {
        await enviar(
          msg,
          nombreValid.error + "\n\n📝 Escribe tu nombre completo nuevamente.",
        );
        return null;
      }

      const dniValid = validarDNI(documento);
      if (!dniValid.valido) {
        await enviar(
          msg,
          dniValid.error + "\n\n📝 Escribe tu DNI nuevamente (8 dígitos).",
        );
        return null;
      }

      let telefonoValid = { valido: true };
      if (telefono) {
        telefonoValid = validarTelefono(telefono);
        if (!telefonoValid.valido) {
          await enviar(
            msg,
            telefonoValid.error + "\n\n📝 Escribe tu celular nuevamente.",
          );
          return null;
        }
      }

      sessionData = {
        ...sessionData,
        nombre: nombreValid.valor,
        documento: dniValid.valor,
        dni: dniValid.valor,
        telefono: telefonoValid.valido
          ? telefonoValid.valor
          : sessionData.telefono,
        celular: telefonoValid.valido
          ? telefonoValid.valor
          : sessionData.celular,
        datos_validados: true,
      };

      const resumen =
        "✅ *DATOS GUARDADOS*\n\n" +
        `👤 *Nombre:* ${nombreValid.valor}\n` +
        `🆔 *DNI:* ${dniValid.valor}` +
        (telefonoValid.valido ? `\n📱 *Celular:* ${telefonoValid.valor}` : "");

      await enviar(msg, resumen);

      const itemId = sessionData.itemId || sessionData.rifaId;
      const oidExiste = sessionData.itemOrderId || sessionData.rifaOrderId;

      if (!oidExiste && itemId) {
        try {
          const resp = await laravelPost("wa/rifa-order", {
            rifa_id: itemId,
            tickets: sessionData.itemCantidad || sessionData.rifaTickets || 1,
            wa_number: waNumber,
            nombre: nombreValid.valor,
            dni: dniValid.valor,
            telefono: telefonoValid.valido ? telefonoValid.valor : null,
          });
          if (resp?.ok) {
            sessionData = {
              ...sessionData,
              orderNumber: resp.order_number,
              itemTotal: Number(resp.monto).toFixed(2),
              itemCantidad: resp.tickets,
              itemNombre: resp.rifa_nombre,
              itemOrderId: resp.order_id,
              rifaTotal: Number(resp.monto).toFixed(2),
              rifaTickets: resp.tickets,
              rifaNombre: resp.rifa_nombre,
              rifaOrderId: resp.order_id,
            };
          }
        } catch (e) {
          console.error("Error:", e.message);
        }
      } else if (oidExiste) {
        await laravelPost(`wa/rifa/${oidExiste}/data`, {
          nombre: nombreValid.valor,
          dni: dniValid.valor,
          telefono: telefonoValid.valido ? telefonoValid.valor : null,
        });
      }

      return sessionData;
    }

    case "save_phone": {
      const telefonoValid = validarTelefono(body);
      if (!telefonoValid.valido) {
        await enviar(
          msg,
          telefonoValid.error +
            "\n\n📝 Escribe solo números de 9 dígitos (ej: 987654321)",
        );
        return null;
      }

      sessionData = {
        ...sessionData,
        telefono: telefonoValid.valor,
        celular: telefonoValid.valor,
      };

      await enviar(msg, `✅ Teléfono guardado: ${telefonoValid.valor}`);
      break;
    }

    case "save_solo_nombre": {
      const nombreValid = validarNombre(body);
      if (!nombreValid.valido) {
        await enviar(msg,
          `${nombreValid.error}\n\n` +
          `👤 Escribe solo tu *nombre completo* con letras y espacios.\n` +
          `_Ejemplo: Juan Pérez García_`
        );
        return null;
      }
      return { ...sessionData, nombre: nombreValid.valor };
    }

    case "save_solo_dni": {
      const soloDigitos = body.replace(/\D/g, "");
      const dniValid = validarDNI(soloDigitos);
      if (!dniValid.valido) {
        await enviar(msg,
          `${dniValid.error}\n\n` +
          `🆔 Escribe solo tu *DNI* (8 números, sin letras ni espacios).\n` +
          `_Ejemplo: 12345678_`
        );
        return null;
      }
      return { ...sessionData, documento: dniValid.valor, dni: dniValid.valor };
    }

    case "save_solo_celular": {
      const soloDigCel = body.replace(/\D/g, "");
      const celValid = validarTelefono(soloDigCel);
      if (!celValid.valido) {
        await enviar(msg,
          `${celValid.error}\n\n` +
          `📱 Escribe solo tu *número de celular* (9 dígitos).\n` +
          `_Ejemplo: 987654321_`
        );
        return null;
      }

      // Ya tenemos nombre, DNI y celular — crear el pedido
      sessionData = { ...sessionData, telefono: celValid.valor, celular: celValid.valor };

      const resumen = "✅ *DATOS GUARDADOS*\n\n"
        + `👤 *Nombre:* ${sessionData.nombre}\n`
        + `🆔 *DNI:* ${sessionData.dni}\n`
        + `📱 *Celular:* ${celValid.valor}`;
      await enviar(msg, resumen);

      const itemId = sessionData.itemId || sessionData.rifaId;
      const oidExiste = sessionData.itemOrderId || sessionData.rifaOrderId;

      if (!oidExiste && itemId) {
        try {
          const resp = await laravelPost("wa/rifa-order", {
            rifa_id: itemId,
            tickets: sessionData.itemCantidad || sessionData.rifaTickets || 1,
            wa_number: waNumber,
            nombre: sessionData.nombre,
            dni: sessionData.dni,
            telefono: celValid.valor,
          });
          if (resp?.ok) {
            sessionData = {
              ...sessionData,
              orderNumber: resp.order_number,
              itemTotal: Number(resp.monto).toFixed(2),
              itemCantidad: resp.tickets,
              itemNombre: resp.rifa_nombre,
              itemOrderId: resp.order_id,
              rifaTotal: Number(resp.monto).toFixed(2),
              rifaTickets: resp.tickets,
              rifaNombre: resp.rifa_nombre,
              rifaOrderId: resp.order_id,
            };
          }
        } catch (e) {
          console.error("Error creando pedido:", e.message);
        }
      } else if (oidExiste) {
        await laravelPost(`wa/rifa/${oidExiste}/data`, {
          nombre: sessionData.nombre,
          dni: sessionData.dni,
          telefono: celValid.valor,
        });
      }

      return sessionData;
    }

    case "create_order": {
      const nombreMatch = body.match(/👤 \*?Cliente:\*? (.+)/);
      const totalMatch = body.match(
        /💰 \*?TOTAL:?\*? ?:? ?([A-Z]{0,3}\.?\s*[\d.,]+)/,
      );
      const pedidoMatch = body.match(/📋 \*?N° Pedido:\*? (.+)/);
      const cliente = nombreMatch?.[1]?.trim() || "Cliente";
      const total = totalMatch?.[1]?.trim() || "";
      const nPedido = pedidoMatch?.[1]?.trim() || "";
      const subtotalNum =
        parseFloat((total || "0").replace(/[^0-9.]/g, "")) || 0;

      const items = [];
      const itemRegex =
        /\d+\.\s+\*?(.+?)\*?\n\s+•\s*Cantidad:\s*([\d.,]+)[^\n]*\n\s+•\s*Precio unit\.:\s*[^\d]*([\d.,]+)/g;
      let m;
      while ((m = itemRegex.exec(body)) !== null)
        items.push({
          name: m[1].trim(),
          qty: parseInt(m[2].replace(/[.,]/g, "")) || 1,
          price: parseFloat(m[3].replace(",", ".")) || 0,
        });

      const resp = await laravelPost("wa/order", {
        project_slug: PROJECT_SLUG,
        wa_number: waNumber,
        client_name: cliente,
        n_pedido: nPedido,
        items: items.length
          ? items
          : [{ name: "Pedido", qty: 1, price: subtotalNum }],
        subtotal: subtotalNum,
      });
      sessionData = {
        ...sessionData,
        cliente,
        total,
        nPedido,
        subtotalNum,
        orderId: resp?.order_id || null,
      };
      break;
    }

    case "save_delivery": {
      let d = {};
      if (msg.type === "location") {
        const loc = msg.location,
          km = distanciaKm(BASE_LAT, BASE_LNG, loc.latitude, loc.longitude),
          delivery = calcularDelivery(km);
        d = {
          deliveryAddress: `GPS (${loc.latitude.toFixed(5)}, ${loc.longitude.toFixed(5)})`,
          deliveryCost: delivery,
          totalFinal: ((sessionData.subtotalNum || 0) + delivery).toFixed(2),
          km: km.toFixed(1),
        };
        if (sessionData.orderId)
          await laravelPost(`wa/order/${sessionData.orderId}/delivery`, {
            shipping_cost: delivery,
            delivery_address: d.deliveryAddress,
          });
      } else {
        d = {
          deliveryAddress: body.trim(),
          deliveryCost: 0,
          totalFinal: sessionData.total || "0",
          km: "?",
        };
        if (sessionData.orderId)
          await laravelPost(`wa/order/${sessionData.orderId}/delivery`, {
            shipping_cost: 0,
            delivery_address: body.trim(),
          });
      }
      sessionData = { ...sessionData, ...d };
      break;
    }

    case "show_lista":
    case "show_rifas": {
      sessionData = await loadRifasLista(sessionData);
      if (sessionData.rifas?.length) {
        const nextKey = transition?.to || "";
        const stateMsg =
          (nextKey && FLOW.states[nextKey]?.message) ||
          "🎰 *Productos disponibles:*";
        await enviarListaConImagenes(
          msg,
          sessionData.rifas,
          fillMessage(stateMsg, buildVars(sessionData)),
        );
        if (nextKey) await saveSession(waNumber, nextKey, sessionData);
        return null;
      }
      break;
    }

    case "select_item":

    case "select_rifa": {
      const rifas = sessionData.rifas || [];
      const selIdx = parseInt(body) - 1;
      const selRifa =
        rifas[selIdx] ||
        rifas.find((r) => r.nombre?.toLowerCase().includes(lower));

      if (!selRifa) {
        const total = rifas.length;
        await enviar(msg, `❌ Opción no válida. Por favor elige un número entre *1* y *${total}*`);
        return null; // no avanzar de estado
      }

      if (selRifa) {
        // Asignar cantidad de tickets según el plan (sin preguntar cantidad)
        let ticketsIncluidos = 1;
        let precio = selRifa.precio ?? selRifa.precio_ticket ?? 0;
        const nombrePlan = selRifa.nombre?.toLowerCase() || "";

        // Configurar tickets según el plan
        if (nombrePlan.includes("duplica") || nombrePlan.includes("s/20")) {
          ticketsIncluidos = 2;
          precio = 20;
        } else if (
          nombrePlan.includes("quintuplica") ||
          nombrePlan.includes("s/50")
        ) {
          ticketsIncluidos = 5;
          precio = 50;
        } else if (
          nombrePlan.includes("asegura") ||
          nombrePlan.includes("s/100")
        ) {
          ticketsIncluidos = 10;
          precio = 100;
        } else {
          ticketsIncluidos = 1;
          precio = 10;
        }

        const total = precio;

        sessionData = {
          ...sessionData,
          itemId: selRifa.id,
          itemNombre: selRifa.nombre,
          itemPrecio: precio,
          itemCantidad: ticketsIncluidos,
          itemTotal: total.toFixed(2),
          rifaId: selRifa.id,
          rifaNombre: selRifa.nombre,
          rifaPrecio: precio,
          rifaTickets: ticketsIncluidos,
          rifaTotal: total.toFixed(2),
        };

        // Mensaje de confirmación del plan seleccionado
        await enviar(
          msg,
          `✅ *Plan seleccionado:* ${selRifa.nombre}\n🎫 *Incluye:* ${ticketsIncluidos} ticket(s)\n💰 *Total:* S/ ${total.toFixed(2)}\n\n📝 Ahora necesito tus datos...`,
        );
      }
      break;
    }

    case "confirmar_plan": {
      await enviar(
        msg,
        `✅ *Plan confirmado:* ${sessionData.itemNombre}\n🎫 ${sessionData.itemCantidad} ticket(s) incluidos\n💰 Total: S/ ${sessionData.itemTotal}\n\n📝 Ahora voy a pedirte tus datos personales...`,
      );
      return sessionData;
    }
    // 👆 HASTA AQUÍ 👆


    case "save_quantity":
    case "save_quantity|create_rifa_order":
    case "save_quantity_create_order": {
      const qty = parseInt(body.replace(/\D/g, "")) || 1;
      const precio = sessionData.itemPrecio || sessionData.rifaPrecio || 0;
      const total = (qty * precio).toFixed(2);
      sessionData = {
        ...sessionData,
        itemCantidad: qty,
        itemTotal: total,
        rifaTickets: qty,
        rifaTotal: total,
      };

      if (action !== "save_quantity") {
        try {
          const resp = await laravelPost("wa/rifa-order", {
            rifa_id: sessionData.itemId || sessionData.rifaId,
            tickets: qty,
            wa_number: waNumber,
          });
          if (resp?.ok) {
            sessionData = {
              ...sessionData,
              orderNumber: resp.order_number,
              itemTotal: Number(resp.monto).toFixed(2),
              itemCantidad: resp.tickets,
              itemNombre: resp.rifa_nombre,
              itemOrderId: resp.order_id,
              rifaTotal: Number(resp.monto).toFixed(2),
              rifaTickets: resp.tickets,
              rifaNombre: resp.rifa_nombre,
              rifaOrderId: resp.order_id,
            };
          }
        } catch (e) {
          console.error("create_order_lista:", e.message);
        }
      }
      break;
    }

    case "create_order_lista":
    case "create_rifa_order": {
      try {
        const resp = await laravelPost("wa/rifa-order", {
          rifa_id: sessionData.itemId || sessionData.rifaId,
          tickets: sessionData.itemCantidad || sessionData.rifaTickets || 1,
          wa_number: waNumber,
        });
        if (resp?.ok) {
          sessionData = {
            ...sessionData,
            orderNumber: resp.order_number,
            itemTotal: Number(resp.monto).toFixed(2),
            itemCantidad: resp.tickets,
            itemNombre: resp.rifa_nombre,
            itemOrderId: resp.order_id,
            rifaTotal: Number(resp.monto).toFixed(2),
            rifaTickets: resp.tickets,
            rifaNombre: resp.rifa_nombre,
            rifaOrderId: resp.order_id,
          };
        }
      } catch (e) {
        console.error("create_order_lista:", e.message);
      }
      break;
    }

    case "save_nombre":
    case "save_rifa_nombre": {
      const nombreValid = validarNombre(body);
      if (!nombreValid.valido) {
        await enviar(msg, nombreValid.error);
        return null;
      }
      sessionData = { ...sessionData, nombre: nombreValid.valor };
      const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
      if (oid)
        await laravelPost(`wa/rifa/${oid}/data`, { nombre: nombreValid.valor });
      await enviar(msg, `✅ Nombre guardado: ${nombreValid.valor}`);
      break;
    }

    case "save_documento":
    case "save_rifa_dni": {
      const dniValid = validarDNI(body);
      if (!dniValid.valido) {
        await enviar(msg, dniValid.error);
        return null;
      }
      sessionData = {
        ...sessionData,
        documento: dniValid.valor,
        dni: dniValid.valor,
      };
      const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
      if (oid)
        await laravelPost(`wa/rifa/${oid}/data`, { dni: dniValid.valor });
      await enviar(msg, `✅ DNI guardado: ${dniValid.valor}`);
      break;
    }

    case "save_ciudad":
    case "save_rifa_ciudad": {
      const ciudad = body.trim();
      sessionData = { ...sessionData, ciudad };
      const oid = sessionData.itemOrderId || sessionData.rifaOrderId;
      if (oid) await laravelPost(`wa/rifa/${oid}/data`, { ciudad });
      break;
    }

    case "save_rifa_payment":
    case "save_payment": {
      if (msg.hasMedia && msg.type === "image") {
        try {
          const media = await msg.downloadMedia();
          const oidLista = sessionData.itemOrderId || sessionData.rifaOrderId;
          if (oidLista && media?.data)
            await laravelPost(`wa/rifa/${oidLista}/payment-proof`, {
              image_base64: media.data,
              mimetype: media.mimetype || "image/jpeg",
            });
          if (sessionData.orderId && media?.data)
            await laravelPost(`wa/order/${sessionData.orderId}/payment-proof`, {
              image_base64: media.data,
              mimetype: media.mimetype || "image/jpeg",
            });
        } catch (e) {
          console.error("save_payment:", e.message);
        }
      }
      break;
    }

    case "confirmar_datos_correctos": {
      sessionData.datos_confirmados = true;
      await enviar(msg, "✅ Excelente! Continuamos con el proceso...");
      return sessionData;
    }

    case "reiniciar_datos": {
      sessionData.nombre = "";
      sessionData.documento = "";
      sessionData.dni = "";
      sessionData.telefono = "";
      sessionData.celular = "";
      sessionData.datos_validados = false;
      sessionData.datos_confirmados = false;
      await enviar(
        msg,
        "🔄 Reiniciamos la validación de datos. Por favor, escribe tu nombre completo:",
      );
      return sessionData;
    }
  }
  return sessionData;
}

async function pedirDatosInteractivo(
  msg,
  sessionData,
  waNumber,
  camposNecesarios = ["nombre", "dni", "telefono"],
) {
  let datos = { ...sessionData };

  if (!datos.nombre && camposNecesarios.includes("nombre")) {
    await enviar(msg, "📝 *Paso 1/3*\n\nEscribe tu *nombre completo*:");
    await saveSession(waNumber, "esperando_nombre", datos);
    return null;
  }

  if (!datos.dni && camposNecesarios.includes("dni")) {
    await enviar(msg, "📝 *Paso 2/3*\n\nEscribe tu *DNI* (8 dígitos):");
    await saveSession(waNumber, "esperando_dni", datos);
    return null;
  }

  if (!datos.telefono && camposNecesarios.includes("telefono")) {
    await enviar(
      msg,
      "📝 *Paso 3/3*\n\nEscribe tu *número de celular* (9 dígitos):",
    );
    await saveSession(waNumber, "esperando_telefono", datos);
    return null;
  }

  return datos;
}

client.initialize();

// ── Servidor HTTP ────────────────────────────────────────────
const MENSAJES_ADMIN = {
  confirmar_pago: (n) =>
    `✅ *¡Pago confirmado!*\n\nEstamos preparando tu pedido 📦\nTe avisamos cuando salga en camino 🚚\n\n_${n}_`,
  en_camino: (n) =>
    `🚚 *¡Tu pedido ya va en camino!*\n\nTiempo estimado: 30–45 minutos ⏱️\n\n_${n}_`,
  entregado: (n) =>
    `📦 *¡Tu pedido fue entregado!*\n\n¿Todo llegó bien?\n✅ *Si llegó*  ❌ *No llegó*\n\n_${n}_`,
};

const server = http.createServer(async (req, res) => {
  if (req.method === "POST" && req.url === "/reload") {
    let raw = "";
    req.on("data", (c) => (raw += c));
    req.on("end", async () => {
      try {
        const data = JSON.parse(raw || "{}");
        if (data.token !== BOT_TOKEN) {
          res.writeHead(401);
          res.end();
          return;
        }
        await loadFlow();
        console.log(`🔄 [${BOT_TYPE}] Flujo recargado desde portal`);
        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ ok: true }));
      } catch (e) {
        res.writeHead(500);
        res.end();
      }
    });
    return;
  }

  // ── GET /qr — página HTML con el QR de WhatsApp ─────────────
  if (req.method === "GET" && req.url === "/qr") {
    try {
      const status = fs.existsSync(STATUS_FILE)
        ? JSON.parse(fs.readFileSync(STATUS_FILE, "utf8"))
        : {};
      res.writeHead(200, { "Content-Type": "text/html; charset=utf-8" });
      if (status.status === "connected") {
        res.end(`<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="10"><title>Bot WhatsApp</title>
<style>body{font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0f2f5;}
.card{background:#fff;border-radius:16px;padding:40px;text-align:center;box-shadow:0 2px 16px rgba(0,0,0,.1);}
.status{color:#25d366;font-size:18px;font-weight:bold;}</style></head>
<body><div class="card"><div style="font-size:48px">✅</div><p class="status">Bot conectado y funcionando</p><p style="color:#666;font-size:14px">No es necesario escanear el QR</p></div></body></html>`);
      } else if (status.qr) {
        res.end(`<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="30"><title>Escanea el QR</title>
<style>body{font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0f2f5;}
.card{background:#fff;border-radius:16px;padding:40px;text-align:center;box-shadow:0 2px 16px rgba(0,0,0,.1);}
h2{color:#128c7e;margin-bottom:4px;}p{color:#666;font-size:14px;margin-top:4px;}
img{width:260px;height:260px;border:4px solid #128c7e;border-radius:12px;margin:16px 0;}
.hint{background:#e7f7ef;border-radius:8px;padding:12px;font-size:13px;color:#075e54;max-width:260px;}</style></head>
<body><div class="card">
<h2>📱 Conectar Bot WhatsApp</h2>
<p>Abre WhatsApp → Dispositivos vinculados → Vincular dispositivo</p>
<img src="${status.qr}" alt="QR Code">
<div class="hint">⚠️ Este QR expira en 60 segundos.<br>Si vence, recarga la página.</div>
</div></body></html>`);
      } else {
        res.end(`<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="5"><title>Iniciando...</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0f2f5;}
.card{background:#fff;border-radius:16px;padding:40px;text-align:center;box-shadow:0 2px 16px rgba(0,0,0,.1);}</style></head>
<body><div class="card"><div style="font-size:48px">⏳</div><p>Iniciando bot, espera unos segundos...</p><p style="font-size:12px;color:#999">La página se actualiza sola</p></div></body></html>`);
      }
    } catch (e) {
      res.writeHead(500);
      res.end("Error");
    }
    return;
  }

  if (req.method === "GET" && req.url === "/status") {
    try {
      const status = fs.existsSync(STATUS_FILE)
        ? JSON.parse(fs.readFileSync(STATUS_FILE, "utf8"))
        : {};
      res.writeHead(200, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ ok: true, ...status }));
    } catch (e) {
      res.writeHead(500);
      res.end();
    }
    return;
  }

  if (req.method !== "POST" || req.url !== "/action") {
    res.writeHead(404);
    res.end();
    return;
  }
  let raw = "";
  req.on("data", (c) => (raw += c));
  req.on("end", async () => {
    try {
      const data = JSON.parse(raw);
      if (data.token !== BOT_TOKEN) {
        res.writeHead(401);
        res.end();
        return;
      }
      const waNumber = data.wa_number.replace(/\D/g, "");
      const waId = waNumber + "@c.us";

      if (data.action === "send_ticket") {
        if (data.ticket_base64) {
          const media = new MessageMedia(
            data.ticket_mime || "image/png",
            data.ticket_base64,
            "ticket.png",
          );
          await client.sendMessage(waId, media, {
            caption: data.message || "",
          });
        } else if (data.message) {
          await client.sendMessage(waId, data.message);
        }
        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ ok: true }));
        return;
      }

      const builder = MENSAJES_ADMIN[data.action];
      if (builder && waId) {
        await client.sendMessage(waId, builder(NEGOCIO));
        if (FLOW?.flow_id) {
          const map = {
            confirmar_pago: "pago_recibido",
            en_camino: "en_camino",
            entregado: "entregado",
          };
          if (map[data.action])
            await laravelPost("wa/session", {
              flow_id: FLOW.flow_id,
              wa_number: waNumber,
              state: map[data.action],
            });
        }
        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ ok: true }));
      } else {
        res.writeHead(400);
        res.end();
      }
    } catch (e) {
      res.writeHead(500);
      res.end();
    }
  });
});

server.on("error", (e) => {
  if (e.code === "EADDRINUSE")
    setTimeout(() => server.listen(BOT_PORT, "127.0.0.1"), 3000);
});
server.listen(BOT_PORT, "127.0.0.1", () =>
  console.log(`🌐 [${BOT_TYPE}] HTTP en puerto ${BOT_PORT}`),
);

// ── Resiliencia ──────────────────────────────────────────────
client.on("disconnected", (reason) => {
  saveStatus({
    status: "offline",
    qr: null,
    updated_at: new Date().toISOString(),
  });
  console.warn(`⚠️ [${BOT_TYPE}] desconectado: ${reason || "sin razón"}`);

  let retryDelay = 10000;
  if (reason === "NAVIGATION" || (reason && reason.includes("navigation"))) {
    retryDelay = 5000;
    console.log("🔄 Navegación detectada, reinicio rápido...");
  }

  setTimeout(async () => {
    try {
      console.log("🔄 Intentando reconectar...");
      await client.initialize();
    } catch (e) {
      console.error("❌ Error en reconexión:", e.message);
    }
  }, retryDelay);
});

client.on("auth_failure", () => {
  saveStatus({
    status: "offline",
    qr: null,
    updated_at: new Date().toISOString(),
  });
  console.warn(`⚠️ [${BOT_TYPE}] fallo de autenticación`);
});

process.on("unhandledRejection", (r) =>
  console.error(`⚠️ [${BOT_TYPE}] UnhandledRejection:`, r),
);
process.on("uncaughtException", (e) =>
  console.error(`⚠️ [${BOT_TYPE}] UncaughtException:`, e.message),
);

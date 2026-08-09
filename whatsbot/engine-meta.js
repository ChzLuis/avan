/**
 * engine-meta.js — Bot WhatsApp via Meta Cloud API (oficial)
 *
 * Uso:
 *   node engine-meta.js --bot=rifa
 *
 * Variables de entorno requeridas (en .env o al lanzar):
 *   META_PHONE_NUMBER_ID   — ID del número en Meta
 *   META_ACCESS_TOKEN      — Token permanente del sistema
 *   META_VERIFY_TOKEN      — Token de verificación del webhook
 *   LARAVEL_URL            — URL base de Laravel (ej: https://bot.pruebatusuerte.com.pe)
 *   BOT_TOKEN              — Token interno (wa-bot-secret-2024)
 *   META_BOT_PORT          — Puerto HTTP interno (default: 3003)
 */

"use strict";

const http    = require("http");
const https   = require("https");
const path    = require("path");
const fs      = require("fs");

// ── Args ──────────────────────────────────────────────────────
const args = Object.fromEntries(
  process.argv.slice(2)
    .filter(a => a.startsWith("--"))
    .map(a => a.slice(2).split("="))
);

const BOT_TYPE         = args.bot || "rifa";
const BOT_PORT         = parseInt(args.port || process.env.META_BOT_PORT || "3003");
const LARAVEL_URL      = (process.env.LARAVEL_URL || "https://bot.pruebatusuerte.com.pe").replace(/\/$/, "");
const BOT_TOKEN        = process.env.BOT_TOKEN || "wa-bot-secret-2024";
const PHONE_NUMBER_ID  = process.env.META_PHONE_NUMBER_ID || "";
const ACCESS_TOKEN     = process.env.META_ACCESS_TOKEN || "";
const VERIFY_TOKEN     = process.env.META_VERIFY_TOKEN || "meta-bot-suerte-2024";
const META_API_URL     = `https://graph.facebook.com/v19.0/${PHONE_NUMBER_ID}/messages`;

const STATUS_FILE = path.join(__dirname, `${BOT_TYPE}-meta-status.json`);

console.log(`🤖 Engine Meta iniciado — bot_type: ${BOT_TYPE}`);
console.log(`🌐 Laravel URL: ${LARAVEL_URL}`);
console.log(`📞 Phone Number ID: ${PHONE_NUMBER_ID || "⚠️ NO CONFIGURADO"}`);

// ── Estado del flujo ──────────────────────────────────────────
let FLOW = null;

function saveStatus(data) {
  try { fs.writeFileSync(STATUS_FILE, JSON.stringify(data, null, 2)); } catch {}
}

saveStatus({ status: "starting", updated_at: new Date().toISOString() });

// ── Cargar flujo desde Laravel ────────────────────────────────
async function loadFlow() {
  try {
    const url = `${LARAVEL_URL}/wa/flow-config?bot=${BOT_TYPE}&token=${BOT_TOKEN}`;
    const data = await fetchJson(url);
    if (data?.ok && data.states) {
      FLOW = data;
      console.log(`✅ Flujo cargado: ${Object.keys(data.states).length} estados`);
    }
  } catch (e) {
    console.error("❌ Error cargando flujo:", e.message);
    // Fallback a JSON local
    try {
      const local = path.join(__dirname, `flow-${BOT_TYPE}.json`);
      if (fs.existsSync(local)) {
        FLOW = JSON.parse(fs.readFileSync(local, "utf8"));
        console.log("📂 Flujo cargado desde archivo local");
      }
    } catch {}
  }
}

// ── Sesiones en memoria ───────────────────────────────────────
const sessions = {};

async function getSession(waNumber) {
  if (sessions[waNumber]) return sessions[waNumber];
  try {
    const data = await laravelGet(`wa/session?bot=${BOT_TYPE}&wa_number=${waNumber}`);
    sessions[waNumber] = {
      state: data?.state || "inicio",
      data:  data?.data  || {},
    };
  } catch {
    sessions[waNumber] = { state: "inicio", data: {} };
  }
  return sessions[waNumber];
}

async function saveSession(waNumber, state, data) {
  sessions[waNumber] = { state, data };
  try {
    await laravelPost("wa/session", {
      bot:       BOT_TYPE,
      wa_number: waNumber,
      state,
      data,
      flow_id:   FLOW?.flow_id,
    });
  } catch {}
}

// ── HTTP helpers ──────────────────────────────────────────────
function fetchJson(url) {
  return new Promise((resolve, reject) => {
    https.get(url, { headers: { "Accept": "application/json" } }, res => {
      let body = "";
      res.on("data", d => body += d);
      res.on("end", () => {
        try { resolve(JSON.parse(body)); } catch { resolve({}); }
      });
    }).on("error", reject);
  });
}

function laravelGet(endpoint) {
  return fetchJson(`${LARAVEL_URL}/${endpoint}`);
}

function laravelPost(endpoint, payload) {
  return new Promise((resolve, reject) => {
    const body = JSON.stringify(payload);
    const urlObj = new URL(`${LARAVEL_URL}/${endpoint}`);
    const options = {
      hostname: urlObj.hostname,
      path:     urlObj.pathname + urlObj.search,
      method:   "POST",
      headers: {
        "Content-Type":   "application/json",
        "Content-Length": Buffer.byteLength(body),
        "Accept":         "application/json",
      },
    };
    const req = https.request(options, res => {
      let data = "";
      res.on("data", d => data += d);
      res.on("end", () => {
        try { resolve(JSON.parse(data)); } catch { resolve({}); }
      });
    });
    req.on("error", reject);
    req.write(body);
    req.end();
  });
}

// ── Enviar mensaje via Meta API ───────────────────────────────
async function sendMessage(waNumber, text) {
  if (!PHONE_NUMBER_ID || !ACCESS_TOKEN) {
    console.error("❌ META_PHONE_NUMBER_ID o META_ACCESS_TOKEN no configurados");
    return;
  }
  const payload = JSON.stringify({
    messaging_product: "whatsapp",
    to:   waNumber,
    type: "text",
    text: { body: text, preview_url: false },
  });

  return new Promise((resolve, reject) => {
    const urlObj = new URL(META_API_URL);
    const options = {
      hostname: urlObj.hostname,
      path:     urlObj.pathname,
      method:   "POST",
      headers: {
        "Authorization":  `Bearer ${ACCESS_TOKEN}`,
        "Content-Type":   "application/json",
        "Content-Length": Buffer.byteLength(payload),
      },
    };
    const req = https.request(options, res => {
      let data = "";
      res.on("data", d => data += d);
      res.on("end", () => {
        if (res.statusCode === 200) {
          console.log(`📤 [${BOT_TYPE}] Mensaje enviado a ${waNumber}`);
          resolve(true);
        } else {
          console.error(`❌ Meta API error ${res.statusCode}: ${data}`);
          resolve(false);
        }
      });
    });
    req.on("error", reject);
    req.write(payload);
    req.end();
  });
}

async function sendImage(waNumber, imageUrl, caption) {
  if (!PHONE_NUMBER_ID || !ACCESS_TOKEN) return false;
  const payload = JSON.stringify({
    messaging_product: "whatsapp",
    to:   waNumber,
    type: "image",
    image: { link: imageUrl, caption: caption || "" },
  });
  return new Promise((resolve) => {
    const urlObj = new URL(META_API_URL);
    const options = {
      hostname: urlObj.hostname,
      path:     urlObj.pathname,
      method:   "POST",
      headers: {
        "Authorization":  `Bearer ${ACCESS_TOKEN}`,
        "Content-Type":   "application/json",
        "Content-Length": Buffer.byteLength(payload),
      },
    };
    const req = https.request(options, res => {
      let data = "";
      res.on("data", d => data += d);
      res.on("end", () => {
        if (res.statusCode === 200) { console.log(`🖼️ Imagen enviada a ${waNumber}`); resolve(true); }
        else { console.error(`❌ Meta sendImage error ${res.statusCode}: ${data}`); resolve(false); }
      });
    });
    req.on("error", () => resolve(false));
    req.write(payload);
    req.end();
  });
}

// ── Palabras de reset ─────────────────────────────────────────
const resetWords = ["menu", "inicio", "hola", "reiniciar", "reset", "cancelar", "salir"];

// ── Procesar mensaje entrante ─────────────────────────────────
async function processMessage(waNumber, body) {
  if (!FLOW) {
    await sendMessage(waNumber, "⚠️ El bot está iniciando, intenta en unos segundos.");
    return;
  }

  const lower = body.trim().toLowerCase();
  const session = await getSession(waNumber);
  let { state, data } = session;

  console.log(`📨 [${BOT_TYPE}] ${waNumber} | ${state} | ${body}`);

  // Reset global
  if (resetWords.includes(lower)) {
    state = "inicio";
    data  = {};
    await saveSession(waNumber, state, data);
  }

  // Estado esperando asesor
  if (state === "esperando_asesor") {
    await sendMessage(waNumber, `⏳ Tu mensaje fue recibido. Un asesor te responderá en breve.\n\nSi deseas volver al menú principal escribe *MENU*.`);
    return;
  }

  const stateConfig = FLOW.states[state];
  if (!stateConfig) {
    await saveSession(waNumber, "inicio", {});
    await processMessage(waNumber, body);
    return;
  }

  // Buscar transición
  const transitions = stateConfig.transitions || [];
  let matched = null;

  for (const t of transitions) {
    if (!t.trigger || t.trigger === "" || t.trigger === body.trim() || lower === (t.trigger || "").toLowerCase()) {
      matched = t;
      break;
    }
  }

  if (!matched && transitions.length > 0) {
    matched = transitions[0];
  }

  if (!matched) {
    await sendMessage(waNumber, stateConfig.message || "No entendí tu mensaje.");
    return;
  }

  // Ejecutar acción
  const action = matched.action || "";
  const nextStateKey = matched.to || state;

  if (action === "save_solo_nombre") data.nombre    = body.trim();
  if (action === "save_solo_dni")    data.dni       = body.trim();
  if (action === "save_solo_celular")data.celular   = body.trim();
  if (action === "save_solo_email")  data.email     = body.trim();
  if (action === "save_solo_ciudad") data.ciudad    = body.trim();

  // Mover al siguiente estado
  await saveSession(waNumber, nextStateKey, data);

  const nextState = FLOW.states[nextStateKey];
  if (nextState?.message) {
    const msg = nextState.message
      .replace(/{nombre}/g,   data.nombre   || "")
      .replace(/{dni}/g,      data.dni      || "")
      .replace(/{celular}/g,  data.celular  || "")
      .replace(/{email}/g,    data.email    || "")
      .replace(/{ciudad}/g,   data.ciudad   || "")
      .replace(/{negocio}/g,  FLOW.project_slug || "");
    await sendMessage(waNumber, msg);
  }
}

// ── Servidor HTTP (webhook + control) ────────────────────────
const server = http.createServer(async (req, res) => {

  // ── GET /webhook — verificación de Meta ──────────────────
  if (req.method === "GET" && req.url.startsWith("/webhook")) {
    const params = new URL(req.url, `http://localhost`).searchParams;
    const mode      = params.get("hub.mode");
    const token     = params.get("hub.verify_token");
    const challenge = params.get("hub.challenge");

    if (mode === "subscribe" && token === VERIFY_TOKEN) {
      console.log("✅ Webhook verificado por Meta");
      res.writeHead(200);
      res.end(challenge);
    } else {
      res.writeHead(403);
      res.end();
    }
    return;
  }

  // ── POST /webhook — mensajes entrantes de Meta ───────────
  if (req.method === "POST" && req.url.startsWith("/webhook")) {
    let raw = "";
    req.on("data", d => raw += d);
    req.on("end", async () => {
      res.writeHead(200);
      res.end("OK");
      try {
        const payload = JSON.parse(raw);
        const entry   = payload?.entry?.[0];
        const changes = entry?.changes?.[0];
        const value   = changes?.value;
        const messages = value?.messages;

        if (!messages?.length) return;

        for (const msg of messages) {
          const waNumber = msg.from;
          const text     = msg.text?.body || msg.interactive?.button_reply?.title || "";
          if (!text) continue;
          await processMessage(waNumber, text);
        }
      } catch (e) {
        console.error("❌ Error procesando webhook:", e.message);
      }
    });
    return;
  }

  // ── POST /action — envío manual desde Laravel ────────────
  if (req.method === "POST" && req.url === "/action") {
    let raw = "";
    req.on("data", d => raw += d);
    req.on("end", async () => {
      try {
        const data = JSON.parse(raw);
        if (data.token !== BOT_TOKEN) {
          res.writeHead(401);
          res.end();
          return;
        }
        if (data.action === "send_message" && data.wa_number && data.message) {
          const waNum = data.wa_number.replace(/\D/g, "");
          const ok = await sendMessage(waNum, data.message);
          res.writeHead(ok ? 200 : 500, { "Content-Type": "application/json" });
          res.end(JSON.stringify({ ok: !!ok }));
          return;
        }
        if (data.action === "send_image" && data.wa_number && data.image_url) {
          const waNum = data.wa_number.replace(/\D/g, "");
          const ok = await sendImage(waNum, data.image_url, data.caption || "");
          res.writeHead(ok ? 200 : 500, { "Content-Type": "application/json" });
          res.end(JSON.stringify({ ok: !!ok }));
          return;
        }
        if (data.action === "reload") {
          await loadFlow();
          res.writeHead(200, { "Content-Type": "application/json" });
          res.end(JSON.stringify({ ok: true }));
          return;
        }
        res.writeHead(400);
        res.end();
      } catch (e) {
        res.writeHead(500, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ ok: false, error: e.message }));
      }
    });
    return;
  }

  res.writeHead(404);
  res.end();
});

server.listen(BOT_PORT, "0.0.0.0", async () => {
  console.log(`🌐 [${BOT_TYPE}-meta] HTTP en puerto ${BOT_PORT}`);
  saveStatus({ status: "online", updated_at: new Date().toISOString() });
  await loadFlow();
  console.log(`✅ [${BOT_TYPE}-meta] listo para recibir mensajes de Meta`);
  saveStatus({ status: "connected", updated_at: new Date().toISOString() });
});

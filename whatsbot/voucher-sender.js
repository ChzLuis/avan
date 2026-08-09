/**
 * voucher-sender.js
 * Microservicio independiente — recibe datos de pedido + URL de comprobante
 * y los envía como imagen+texto a un número WhatsApp destino.
 *
 * Puerto: 3005
 * Endpoint: POST /enviar-pedido
 * Body JSON: { token, to, caption, image_url }
 */

"use strict";

const { Client, LocalAuth, MessageMedia } = require("whatsapp-web.js");
const http  = require("http");
const https = require("https");
const fs    = require("fs");
const path  = require("path");

const PORT       = parseInt(process.env.VOUCHER_PORT || "3005");
const BOT_TOKEN  = process.env.BOT_TOKEN || "wa-bot-secret-2024";
const STATUS_FILE = path.join(__dirname, "voucher-status.json");

function saveStatus(data) {
  try { fs.writeFileSync(STATUS_FILE, JSON.stringify(data, null, 2)); } catch(e) {}
}

// ── Cliente WhatsApp ──────────────────────────────────────────
const client = new Client({
  authStrategy: new LocalAuth({ clientId: "voucher-sender" }),
  puppeteer: {
    headless: true,
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"],
  },
});

let ready = false;

client.on("qr", (qr) => {
  console.log("📱 Escanea el QR con WhatsApp (número 955354646):");
  // Imprimir QR en terminal
  try {
    require("qrcode-terminal").generate(qr, { small: true });
  } catch(e) {
    console.log("QR:", qr);
  }
  saveStatus({ status: "waiting_qr", updated_at: new Date().toISOString() });
});

client.on("ready", () => {
  ready = true;
  console.log("✅ voucher-sender conectado y listo en puerto", PORT);
  saveStatus({ status: "connected", updated_at: new Date().toISOString() });
});

client.on("disconnected", (reason) => {
  ready = false;
  console.warn("⚠️  Desconectado:", reason);
  saveStatus({ status: "disconnected", updated_at: new Date().toISOString() });
});

client.initialize();

// ── Descargar imagen desde URL ────────────────────────────────
function fetchImageAsBase64(url) {
  return new Promise((resolve, reject) => {
    const mod = url.startsWith("https") ? https : http;
    mod.get(url, (res) => {
      if (res.statusCode !== 200) { reject(new Error("HTTP " + res.statusCode)); return; }
      const chunks = [];
      res.on("data", c => chunks.push(c));
      res.on("end", () => {
        const buf  = Buffer.concat(chunks);
        const mime = res.headers["content-type"] || "image/jpeg";
        resolve({ base64: buf.toString("base64"), mime });
      });
    }).on("error", reject);
  });
}

// ── Servidor HTTP ─────────────────────────────────────────────
const server = http.createServer(async (req, res) => {
  if (req.method === "GET" && req.url === "/status") {
    res.writeHead(200, { "Content-Type": "application/json" });
    res.end(JSON.stringify({ ok: true, ready, port: PORT }));
    return;
  }

  if (req.method !== "POST") { res.writeHead(404); res.end(); return; }

  let raw = "";
  req.on("data", c => raw += c);
  req.on("end", async () => {
    try {
      const data = JSON.parse(raw);

      if (data.token !== BOT_TOKEN) {
        res.writeHead(401); res.end(JSON.stringify({ ok: false, error: "Unauthorized" })); return;
      }

      if (!ready) {
        res.writeHead(503); res.end(JSON.stringify({ ok: false, error: "Bot no conectado. Escanea el QR." })); return;
      }

      const waTo = (data.to || "").replace(/\D/g, "") + "@c.us";

      // Descargar imagen
      const { base64, mime } = await fetchImageAsBase64(data.image_url);
      const media = new MessageMedia(mime, base64, "comprobante.jpg");

      // Enviar imagen + caption con texto del pedido
      try {
        await client.sendMessage(waTo, media, { caption: data.caption || "" });
      } catch(e) {
        // Intentar con @lid si @c.us falla
        const altId = (data.to || "").replace(/\D/g, "") + "@lid";
        await client.sendMessage(altId, media, { caption: data.caption || "" });
      }

      console.log(`🖼️  Comprobante enviado a ${waTo}`);
      res.writeHead(200, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ ok: true }));

    } catch(e) {
      console.error("❌ Error:", e.message);
      res.writeHead(500, { "Content-Type": "application/json" });
      res.end(JSON.stringify({ ok: false, error: e.message }));
    }
  });
});

server.listen(PORT, "127.0.0.1", () => {
  console.log(`🌐 voucher-sender escuchando en puerto ${PORT}`);
});

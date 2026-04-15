#!/bin/bash
# ─────────────────────────────────────────────────────────────
# Setup PM2 para bots WhatsApp en VPS
# Ejecutar desde la raíz del proyecto Laravel:
#   bash whatsbot/setup-vps.sh
# ─────────────────────────────────────────────────────────────

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "📦 Instalando PM2 globalmente..."
npm install -g pm2

echo "📦 Instalando dependencias del bot..."
cd "$SCRIPT_DIR"
npm install

echo "📁 Creando directorio de logs..."
mkdir -p "$SCRIPT_DIR/logs"

echo "🚀 Iniciando bots con PM2..."

# Detener instancias previas si existen
pm2 delete bot-main 2>/dev/null || true
pm2 delete bot-rifa 2>/dev/null || true

# Iniciar Bot Principal
pm2 start "$SCRIPT_DIR/index.js" \
    --name bot-main \
    --output "$SCRIPT_DIR/logs/main.log" \
    --error  "$SCRIPT_DIR/logs/main.log" \
    --merge-logs \
    --restart-delay 5000 \
    --max-restarts 10

# Iniciar Bot Rifa
if [ -f "$SCRIPT_DIR/rifa-bot.js" ]; then
    pm2 start "$SCRIPT_DIR/rifa-bot.js" \
        --name bot-rifa \
        --output "$SCRIPT_DIR/logs/rifa.log" \
        --error  "$SCRIPT_DIR/logs/rifa.log" \
        --merge-logs \
        --restart-delay 5000 \
        --max-restarts 10
fi

echo "💾 Guardando configuración PM2 (autostart)..."
pm2 save

echo "🔧 Configurando inicio automático al reiniciar el servidor..."
pm2 startup
echo ""
echo "⚠️  IMPORTANTE: copia y ejecuta el comando que aparece arriba (el que empieza con 'sudo')."
echo ""

echo "✅ Listo. Estado de los bots:"
pm2 status

echo ""
echo "📋 Comandos útiles:"
echo "   pm2 status          → ver estado"
echo "   pm2 logs bot-main   → ver logs en vivo"
echo "   pm2 restart bot-main → reiniciar"
echo "   pm2 stop bot-main   → detener"

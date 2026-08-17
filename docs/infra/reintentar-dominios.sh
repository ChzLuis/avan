#!/bin/bash
# Reintenta la provision de los dominios personalizados que quedaron a medias.
#
# Por que existe: el cliente conecta el dominio en el panel ANTES de que el DNS
# se propague, casi siempre. El primer intento falla por fuerza y hasta ahora no
# habia un segundo: el dominio se quedaba sin certificado para siempre y nadie
# se enteraba hasta que un cliente se quejaba.
#
# Se ejecuta cada 10 minutos. Solo toca los que NO estan listos, asi que en
# regimen normal no hace absolutamente nada.

set -u
STATEDIR="/var/log/bixo-domains"
LOG="/var/log/bixo-domains/reintentos.log"
mkdir -p "$STATEDIR"

# Dominios personalizados vivos, leidos de la propia base de datos.
DOMINIOS=$(mysql -N -B -e "SELECT custom_domain FROM projects WHERE custom_domain IS NOT NULL AND custom_domain <> '';" dbarin 2>/dev/null)

[ -z "$DOMINIOS" ] && exit 0

for d in $DOMINIOS; do
    ESTADO="$(cat "${STATEDIR}/${d}.state" 2>/dev/null || echo 'nuevo')"
    [ "$ESTADO" = "listo" ] && continue
    echo "$(date '+%F %T') | reintentando ${d} (estado: ${ESTADO})" >> "$LOG"
    /usr/local/bin/setup-domain.sh "$d" >> "$LOG" 2>&1
done

#!/bin/bash
# Configura nginx + SSL para un custom_domain conectado desde el panel.
#
# Cambios respecto de la version anterior, y por que:
#
# 1. CERTIFICADO POR DOMINIO, no uno compartido que se expande.
#    Antes todos los dominios vivian en "arindg-wildcard" y cada alta lo
#    expandia. Bastaba con que UNO de los dominios ya incluidos dejara de
#    apuntar aqui para que certbot fallara la orden entera y NINGUN dominio
#    nuevo pudiera emitir. Es exactamente lo que pasaba: megahogar.org apunta
#    hoy a 2.57.91.93, no a este servidor. Un cliente que se va rompia el alta
#    de todos los siguientes.
#
# 2. www SOLO SI RESUELVE.
#    Antes se pedia siempre "-d www.${DOMAIN}". Para un subdominio como
#    tienda.tecsist.net eso significa www.tienda.tecsist.net, que nadie crea
#    nunca. Certbot fallaba la orden completa por ese nombre y el dominio
#    quedaba sin certificado. Ahora se comprueba antes y solo se pide si existe.
#
# 3. IDEMPOTENTE Y REINTENTABLE.
#    Antes, si el .conf ya existia, salia con "sin cambios" y no volvia a
#    intentar el certificado nunca mas. Como el DNS casi siempre se propaga
#    DESPUES de conectar el dominio en el panel, el primer intento fallaba y no
#    habia segundo. Ahora se comprueba si el certificado cubre de verdad el
#    dominio y, si no, se reintenta.
#
# 4. ESTADO LEGIBLE en /var/log/bixo-domains/<dominio>.status para que el panel
#    pueda decir en que punto esta en vez de callarse.

set -u

DOMAIN="$(echo "${1:-}" | tr 'A-Z' 'a-z' | tr -cd 'a-z0-9.-')"
DOCROOT="/home/arindg/htdocs/arindg.com/public"
LOGROOT="/home/arindg/logs/nginx"
CONF="/etc/nginx/sites-enabled/${DOMAIN}.conf"
FASTCGI="127.0.0.1:20002"
EMAIL="luicha007@gmail.com"
STATEDIR="/var/log/bixo-domains"
SERVER_IP="$(curl -s --max-time 5 https://api.ipify.org || echo '')"

mkdir -p "$STATEDIR"

if [ -z "$DOMAIN" ]; then echo "ERROR: falta el dominio" >&2; exit 1; fi

# Deja el estado tambien en la base de datos, para que el PANEL pueda mostrarlo.
# Antes el unico rastro estaba en /var/log, que la aplicacion no lee: por eso el
# cliente veia el campo guardado y nada mas, pasara lo que pasara.
estado_db() {
    local est="$1"
    mysql -N -B -e "UPDATE projects SET domain_status='${est}', domain_checked_at=NOW() WHERE custom_domain='${DOMAIN}';" dbarin 2>/dev/null || true
}
STATE="${STATEDIR}/${DOMAIN}.status"
say() { echo "$(date '+%F %T') | $*" | tee -a "$STATE"; }

# â”€â”€ 1. El DNS ya apunta aqui? â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
resolves_here() {
    local host="$1" ips
    ips="$(getent ahostsv4 "$host" 2>/dev/null | awk '{print $1}' | sort -u)"
    [ -z "$ips" ] && return 1
    [ -z "$SERVER_IP" ] && return 0          # sin IP propia, no bloqueamos
    echo "$ips" | grep -qx "$SERVER_IP"
}

DNS_OK=0
resolves_here "$DOMAIN" && DNS_OK=1

WWW_ARG=""
if resolves_here "www.${DOMAIN}"; then
    WWW_ARG="-d www.${DOMAIN}"
    SERVER_NAMES="${DOMAIN} www.${DOMAIN}"
else
    SERVER_NAMES="${DOMAIN}"
fi

# â”€â”€ 2. Que certificado usar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
# Si ya existe uno propio para este dominio, se reutiliza. Si no, se emitira.
CERTDIR="/etc/letsencrypt/live/${DOMAIN}"
if [ -f "${CERTDIR}/fullchain.pem" ]; then
    SSL_CERT="${CERTDIR}/fullchain.pem"
    SSL_KEY="${CERTDIR}/privkey.pem"
else
    # Provisional: cualquier certificado valido sirve para que nginx arranque.
    # No es el correcto todavia; se sustituye en cuanto certbot emita el suyo.
    SSL_CERT="/etc/letsencrypt/live/arindg-wildcard/fullchain.pem"
    SSL_KEY="/etc/letsencrypt/live/arindg-wildcard/privkey.pem"
fi

# â”€â”€ 3. Escribir el vhost (siempre, para que sea idempotente) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if [ -f "$CONF" ] && ! grep -q "root ${DOCROOT}" "$CONF"; then
    BACKUP="/root/domain_conf_backups/${DOMAIN}.conf.$(date +%Y%m%d_%H%M%S)"
    mkdir -p /root/domain_conf_backups
    cp "$CONF" "$BACKUP"
    say "AVISO: habia una configuracion ajena. Respaldada en ${BACKUP}."
fi

cat > "$CONF" << NGINX
server {
 listen 80;
 listen [::]:80;
 server_name ${SERVER_NAMES};
 location ^~ /.well-known/acme-challenge/ {
  root ${DOCROOT};
  allow all;
 }
 location / {
  return 301 https://${DOMAIN}\$request_uri;
 }
}
server {
 listen 443 quic;
 listen 443 ssl;
 listen [::]:443 quic;
 listen [::]:443 ssl;
 http2 on;
 http3 off;
 server_name ${SERVER_NAMES};
 disable_symlinks off;
 root ${DOCROOT};
 access_log ${LOGROOT}/access.log main;
 error_log ${LOGROOT}/error.log;
 ssl_certificate ${SSL_CERT};
 ssl_certificate_key ${SSL_KEY};
 index index.php index.html;
 location ~* ^/storage/ { try_files \$uri =404; }
 client_max_body_size 20M;
 location / {
  try_files \$uri \$uri/ /index.php?\$query_string;
 }
 location ~ \.php\$ {
  fastcgi_pass ${FASTCGI};
  fastcgi_index index.php;
  fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
  fastcgi_read_timeout 3600;
  fastcgi_send_timeout 3600;
  fastcgi_param HTTPS "on";
  fastcgi_param SERVER_PORT 443;
  include fastcgi_params;
 }
 location ~ /\.ht { deny all; }
}
NGINX

if ! nginx -t 2>/dev/null; then
    say "ERROR: nginx rechazo la configuracion de ${DOMAIN}. Se retira el vhost."
    rm -f "$CONF"
    nginx -t >/dev/null 2>&1 && systemctl reload nginx
    exit 1
fi
systemctl reload nginx
say "vhost activo (servidor respondiendo por ${SERVER_NAMES})"

# â”€â”€ 4. Certificado propio â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
cert_covers() {
    [ -f "${CERTDIR}/fullchain.pem" ] || return 1
    openssl x509 -noout -ext subjectAltName -in "${CERTDIR}/fullchain.pem" 2>/dev/null \
        | grep -q "DNS:${DOMAIN}\b"
}

if cert_covers; then
    say "OK: ${DOMAIN} ya tiene certificado propio valido."
    echo "listo" > "${STATEDIR}/${DOMAIN}.state"; estado_db "listo"
    exit 0
fi

if [ "$DNS_OK" != "1" ]; then
    say "PENDIENTE: el DNS de ${DOMAIN} todavia no apunta a ${SERVER_IP}. El vhost queda listo; se reintentara el certificado automaticamente."
    echo "dns-pendiente" > "${STATEDIR}/${DOMAIN}.state"; estado_db "dns-pendiente"
    exit 2
fi

say "Solicitando certificado para ${DOMAIN} ${WWW_ARG}"
if certbot certonly --nginx --cert-name "${DOMAIN}" -d "${DOMAIN}" ${WWW_ARG} \
        --non-interactive --agree-tos --email "${EMAIL}" >>"$STATE" 2>&1; then
    # Apuntar el vhost a su certificado recien emitido y recargar.
    sed -i "s#ssl_certificate .*#ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;#; s#ssl_certificate_key .*#ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;#" "$CONF"
    if nginx -t 2>/dev/null; then
        systemctl reload nginx
        say "OK: ${DOMAIN} funcionando con certificado propio."
        echo "listo" > "${STATEDIR}/${DOMAIN}.state"; estado_db "listo"
        exit 0
    fi
    say "ERROR: el certificado se emitio pero nginx rechazo la configuracion."
    echo "error" > "${STATEDIR}/${DOMAIN}.state"; estado_db "error"
    exit 1
fi

say "ERROR: certbot no pudo emitir para ${DOMAIN}. Se reintentara automaticamente."
echo "cert-pendiente" > "${STATEDIR}/${DOMAIN}.state"; estado_db "cert-pendiente"
exit 1

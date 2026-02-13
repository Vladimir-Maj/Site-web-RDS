#!/bin/bash
set -e

DOMAIN="stageflow.fr"
CERT="/etc/ssl/certs/${DOMAIN}.crt"
KEY="/etc/ssl/private/${DOMAIN}.key"
CONF="/etc/ssl/openssl-san.cnf"

echo "▶ HTTPS entrypoint starting"

if [ ! -f "$CERT" ] || [ ! -f "$KEY" ]; then
    echo "▶ Generating self-signed SSL certificate"

    cat > "$CONF" <<'EOF'
[req]
default_bits = 2048
prompt = no
default_md = sha256
req_extensions = req_ext
distinguished_name = dn

[dn]
C = FR
ST = Loire-Atlantique
L = Saint-Nazaire
O = Stage Flow
CN = stageflow.fr

[req_ext]
subjectAltName = @alt_names

[alt_names]
DNS.1 = stageflow.fr
DNS.2 = www.stageflow.fr
DNS.3 = prod.stageflow.fr
DNS.4 = cdn.stageflow.fr
EOF

    openssl req -x509 -nodes -days 365 \
        -newkey rsa:2048 \
        -keyout "$KEY" \
        -out "$CERT" \
        -config "$CONF"

    chmod 600 "$KEY"
else
    echo "▶ SSL certificate already exists"
fi

exec apache2-foreground

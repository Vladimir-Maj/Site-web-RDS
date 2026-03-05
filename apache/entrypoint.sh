#!/bin/bash
set -e

DOMAIN="stageflow.fr"
CERT="/etc/ssl/certs/${DOMAIN}.crt"
KEY="/etc/ssl/private/${DOMAIN}.key"
CONF="/etc/ssl/openssl-san.cnf"

echo "▶ HTTPS entrypoint starting"

# --- 1. SSL Certificate Generation ---
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

# --- 2. Composer Dependency Management ---
# We target the directory containing your composer.json
PROD_DIR="/var/www/html/prod"

if [ -f "$PROD_DIR/composer.json" ]; then
    echo "▶ Found composer.json in $PROD_DIR. Checking dependencies..."
    
    # We run as root here, but we'll fix permissions after.
    # --no-interaction is key for automated scripts.
    composer install --working-dir="$PROD_DIR" --no-interaction --optimize-autoloader
    
    # Fix ownership so the web server (www-data) can read the vendor folder
    chown -R www-data:www-data "$PROD_DIR/vendor"
else
    echo "▶ No composer.json found in $PROD_DIR, skipping installation."
fi

# --- 3. Start Apache ---
echo "▶ Starting Apache..."
exec apache2-foreground
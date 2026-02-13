#!/bin/bash
# Tail all Apache logs for the LAMP project
# Usage: ./logs.sh [lines]   (default lines=20)

LINES=${1:-20}  # Default to 20 lines if not provided
CONTAINER="lamp-web"

echo "▶ Tailing last $LINES lines of Apache logs in container $CONTAINER"
echo "Press Ctrl+C to exit"

docker exec -it $CONTAINER bash -c "
echo '=== PROD ERROR LOG ===';
tail -n $LINES -f /var/log/apache2/prod_ssl_error.log &
echo '=== PROD ACCESS LOG ===';
tail -n $LINES -f /var/log/apache2/prod_ssl_access.log &
echo '=== CDN ERROR LOG ===';
tail -n $LINES -f /var/log/apache2/cdn_ssl_error.log &
echo '=== CDN ACCESS LOG ===';
tail -n $LINES -f /var/log/apache2/cdn_ssl_access.log &
wait
"

#!/bin/bash
# Vuelve produccion a la version anterior (o a la que se indique).
# Uso: real3d-rollback.sh [nombre-de-version]
#      real3d-rollback.sh --lista
set -euo pipefail

BASE=/var/www/real3d
ACTUAL=$(basename "$(readlink -f "$BASE/current")")

if [ "${1:-}" = "--lista" ]; then
    echo "version activa: $ACTUAL"
    echo "disponibles:"
    ls -1t "$BASE/releases"
    exit 0
fi

if [ -n "${1:-}" ]; then
    DESTINO="$1"
else
    DESTINO=$(ls -1t "$BASE/releases" | grep -v "^$ACTUAL$" | head -1)
fi

[ -d "$BASE/releases/$DESTINO" ] || { echo "ERROR: no existe la version $DESTINO"; exit 1; }

echo "volviendo de $ACTUAL a $DESTINO"
ln -sfn "$BASE/releases/$DESTINO" "$BASE/current.tmp"
mv -Tf "$BASE/current.tmp" "$BASE/current"
systemctl reload php8.2-fpm
systemctl restart real3d-queue.service
sleep 2

CODE=$(curl -s -o /dev/null -w '%{http_code}' https://real3d.io/)
echo "portada responde $CODE"
[ "$CODE" = "200" ] || { echo "CUIDADO: la web no responde bien tras volver atras"; exit 1; }
echo "hecho. Aviso: si la version anterior traia migraciones, la base de datos NO se revierte."

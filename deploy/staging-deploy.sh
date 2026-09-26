#!/bin/bash
# Actualiza staging.real3d.io con lo ultimo de la rama staging.
# Uso: staging-deploy.sh            (despliega origin/staging)
#      staging-deploy.sh nombre-rama (despliega otra rama, para probar un PR)
set -euo pipefail

APP=/var/www/staging
RAMA="${1:-staging}"

cd "$APP"
echo "== actualizando a origin/$RAMA"
git fetch -q origin
git checkout -q "$RAMA" 2>/dev/null || git checkout -q -b "$RAMA" "origin/$RAMA"
git reset -q --hard "origin/$RAMA"
git log --oneline -1

echo "== dependencias"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-dev --optimize-autoloader --quiet
npm ci --silent
npm run build >/dev/null

echo "== base de datos"
sudo -u www-data php artisan migrate --force

echo "== cache"
sudo -u www-data php artisan config:clear >/dev/null
sudo -u www-data php artisan view:clear >/dev/null
sudo -u www-data php artisan route:clear >/dev/null

echo "== permisos"
chown -R deploy:www-data "$APP"
chown -R www-data:www-data "$APP/storage" "$APP/bootstrap/cache"
chmod -R ug+rwX "$APP/storage" "$APP/bootstrap/cache"

echo "== comprobacion"
CODE=$(curl -s -o /dev/null -w '%{http_code}' -u "real3d:$(cat /root/.staging-web-pass)" \
    -H 'Host: staging.real3d.io' http://127.0.0.1/)
echo "portada responde $CODE"
[ "$CODE" = "200" ] || { echo "ERROR: staging no responde bien"; exit 1; }
echo "despliegue de staging terminado"

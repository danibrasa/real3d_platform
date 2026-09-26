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

# version.json, igual que en produccion pero marcando el entorno.
TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "sin-tag")
SHA=$(git rev-parse HEAD)
cat > "$APP/version.json" <<JSON
{
  "release": "$TAG",
  "commit": "$SHA",
  "commit_short": "${SHA:0:7}",
  "deployed_at": "$(date '+%Y-%m-%d %H:%M')",
  "environment": "staging"
}
JSON
echo "== version: $TAG (${SHA:0:7})"

echo "== cache"
sudo -u www-data php artisan config:clear >/dev/null
sudo -u www-data php artisan view:clear >/dev/null
sudo -u www-data php artisan route:clear >/dev/null

echo "== permisos"
chown -R deploy:www-data "$APP"
chown -R www-data:www-data "$APP/storage" "$APP/bootstrap/cache"
chmod -R ug+rwX "$APP/storage" "$APP/bootstrap/cache"

echo "== comprobacion"
AUTH="real3d:$(cat /root/.staging-web-pass)"
# -L: desde que hay certificado, http redirige a https con un 301.
CODE=$(curl -sL -o /dev/null -w '%{http_code}' -u "$AUTH" https://staging.real3d.io/)
VCODE=$(curl -sL -o /dev/null -w '%{http_code}' -u "$AUTH" https://staging.real3d.io/version)
echo "portada $CODE, /version $VCODE"
[ "$CODE" = "200" ] && [ "$VCODE" = "200" ] || { echo "ERROR: staging no responde bien"; exit 1; }
echo "despliegue de staging terminado"

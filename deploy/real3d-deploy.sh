#!/bin/bash
# Despliegue de produccion de Real3D.io por versiones.
#
#   /var/www/real3d/
#   ├── current -> releases/<fecha>     el enlace que sirve nginx
#   ├── releases/                        se guardan las 5 ultimas
#   └── shared/                          .env y storage, comunes a todas
#
# Uso: real3d-deploy.sh [rama]     (por defecto main)
# Volver atras: real3d-rollback.sh
set -euo pipefail

BASE=/var/www/real3d
REPO=git@github.com:danibrasa/real3d_platform.git
RAMA="${1:-main}"
STAMP=$(date +%Y%m%d-%H%M%S)
NUEVA="$BASE/releases/$STAMP"
ANTERIOR=$(readlink -f "$BASE/current" 2>/dev/null || echo "")
CONSERVAR=5

log() { echo "[$(date '+%T')] $*"; }

abortar() {
    log "ERROR: $1"
    log "la version en curso no se activa; produccion sigue en ${ANTERIOR:-el sitio anterior}"
    rm -rf "$NUEVA"
    exit 1
}

log "desplegando la rama $RAMA como $STAMP"

# Los scripts de operativa viven en deploy/ del repositorio; la copia que corre
# esta en /usr/local/bin. Si el repositorio trae una version distinta, se instala
# y se reejecuta una sola vez, para desplegar ya con la version nueva.
# REEJECUTADO evita que se llame a si mismo sin fin.
if [ -z "${REEJECUTADO:-}" ] && [ -d "$BASE/current/deploy" ]; then
    if ! cmp -s "$BASE/current/deploy/real3d-deploy.sh" "$0"; then
        log "los scripts de deploy/ han cambiado: instalando y reejecutando"
        install -m 755 "$BASE/current/deploy/"*.sh /usr/local/bin/
        REEJECUTADO=1 exec /usr/local/bin/"$(basename "$0")" "$@"
    fi
fi


# 1. Copia del codigo
git clone -q --depth 20 --branch "$RAMA" "$REPO" "$NUEVA" || abortar "no se pudo clonar"
cd "$NUEVA"
log "commit: $(git log --oneline -1)"

# 2. Enlaces a lo compartido
rm -rf "$NUEVA/storage"
ln -s "$BASE/shared/storage" "$NUEVA/storage"
ln -s "$BASE/shared/.env" "$NUEVA/.env"

# 3b. version.json: que hay desplegado exactamente. Lo leen /version, la meta
#     del HTML y el pie del panel de administracion.
TAG=$(git -C "$NUEVA" describe --tags --abbrev=0 2>/dev/null || echo "sin-tag")
SHA=$(git -C "$NUEVA" rev-parse HEAD)
cat > "$NUEVA/version.json" <<JSON
{
  "release": "$TAG",
  "commit": "$SHA",
  "commit_short": "${SHA:0:7}",
  "deployed_at": "$(date '+%Y-%m-%d %H:%M')",
  "environment": "production"
}
JSON
log "version: $TAG (${SHA:0:7})"

# 3. Dependencias y assets
log "instalando dependencias"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-dev --optimize-autoloader --quiet \
    || abortar "fallo composer install"
npm ci --silent || abortar "fallo npm ci"
npm run build >/dev/null 2>&1 || abortar "fallo la compilacion de assets"

# 4. Permisos
chown -R deploy:www-data "$NUEVA"
chmod -R g+rX "$NUEVA"
chown -R www-data:www-data "$NUEVA/bootstrap/cache"
chmod -R ug+rwX "$NUEVA/bootstrap/cache"

# 5. Migraciones y cache (con la version nueva, antes de activarla)
log "migraciones"
sudo -u www-data php artisan migrate --force || abortar "fallaron las migraciones"
sudo -u www-data php artisan config:cache >/dev/null
sudo -u www-data php artisan route:cache >/dev/null
sudo -u www-data php artisan view:cache >/dev/null
ln -sfn "$BASE/shared/storage/app/public" "$NUEVA/public/storage"

# 6. Activacion: cambio del enlace, que es atomico
log "activando la version nueva"
ln -sfn "$NUEVA" "$BASE/current.tmp"
mv -Tf "$BASE/current.tmp" "$BASE/current"
systemctl reload php8.2-fpm
systemctl restart real3d-queue.service

# 7. Comprobacion de salud
sleep 2
CODE=$(curl -s -o /dev/null -w '%{http_code}' https://real3d.io/)
PROY=$(curl -s -o /dev/null -w '%{http_code}' https://real3d.io/projects)
log "portada $CODE, listado $PROY"

# El enlace a los archivos publicos: sin el, paginas que devuelven 200 salen con
# las imagenes rotas (paso el 26-sep-2026 con el logo de /developers).
FALLO_STORAGE=0
if [ ! -L "$NUEVA/public/storage" ]; then
    log "ERROR: falta el enlace public/storage"
    FALLO_STORAGE=1
else
    ARCHIVO=$(find -L "$BASE/shared/storage/app/public" -type f ! -name '.gitignore' -printf '%P\n' 2>/dev/null | head -1)
    if [ -n "$ARCHIVO" ]; then
        SCODE=$(curl -s -o /dev/null -w '%{http_code}' "https://real3d.io/storage/$ARCHIVO")
        log "archivo publico ($ARCHIVO) $SCODE"
        [ "$SCODE" = "200" ] || FALLO_STORAGE=1
    fi
fi

if [ "$CODE" != "200" ] || [ "$PROY" != "200" ] || [ "$FALLO_STORAGE" = "1" ]; then
    log "LA COMPROBACION HA FALLADO: volviendo a la version anterior"
    if [ -n "$ANTERIOR" ] && [ -d "$ANTERIOR" ]; then
        ln -sfn "$ANTERIOR" "$BASE/current.tmp"
        mv -Tf "$BASE/current.tmp" "$BASE/current"
        systemctl reload php8.2-fpm
        systemctl restart real3d-queue.service
        log "restaurada $ANTERIOR"
    else
        log "no habia version anterior a la que volver"
    fi
    exit 1
fi

# 8. Limpieza de versiones antiguas
cd "$BASE/releases"
ls -1t | tail -n +$((CONSERVAR + 1)) | while read -r vieja; do
    [ "$BASE/releases/$vieja" = "$ANTERIOR" ] && continue
    log "borrando version antigua $vieja"
    rm -rf "${BASE:?}/releases/${vieja:?}"
done

log "despliegue terminado: $STAMP"

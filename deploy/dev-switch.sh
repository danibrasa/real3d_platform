#!/bin/bash
# Cambia el entorno de desarrollo a una rama, dejandolo listo para probar.
#
# Uso: dev-switch.sh <rama>          cambia a esa rama (la crea si es nueva)
#      dev-switch.sh                 actualiza la rama actual
#
# A diferencia de los despliegues de staging y produccion, aqui no hay versiones
# ni vuelta atras: es un entorno de trabajo y se puede romper sin consecuencias.
set -euo pipefail

APP=/var/www/dev
RAMA="${1:-}"

cd "$APP"

# Cambios sin guardar: se apartan en vez de abortar. Es un entorno de trabajo,
# y perder el cambio por un checkout fallido molesta mas que un stash de mas.
if ! git diff --quiet || ! git diff --cached --quiet; then
    git stash push -q -u -m "dev-switch $(date +%F_%T)"
    echo "habia cambios sin guardar: apartados con git stash (git stash list para verlos)"
fi

git fetch -q origin

if [ -n "$RAMA" ]; then
    if git rev-parse --verify --quiet "origin/$RAMA" >/dev/null; then
        git checkout -q -B "$RAMA" "origin/$RAMA"
    else
        # Rama nueva, todavia sin subir: se crea a partir de main
        git checkout -q -B "$RAMA" origin/main
        echo "rama nueva '$RAMA' creada desde main (aun no existe en origin)"
    fi
else
    RAMA=$(git branch --show-current)
    git pull -q --ff-only 2>/dev/null || true
fi

echo "rama: $RAMA"
echo "commit: $(git log --oneline -1)"

# Solo se reinstala lo que haya cambiado
if ! git diff --quiet HEAD@{1} HEAD -- composer.lock 2>/dev/null; then
    echo "composer.lock ha cambiado: reinstalando dependencias"
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --quiet
fi
if ! git diff --quiet HEAD@{1} HEAD -- package-lock.json 2>/dev/null; then
    echo "package-lock.json ha cambiado: reinstalando dependencias de node"
    npm ci --silent
fi

npm run build >/dev/null 2>&1
sudo -u www-data php artisan migrate --force 2>&1 | tail -1
sudo -u www-data php artisan config:clear >/dev/null
sudo -u www-data php artisan view:clear >/dev/null
sudo -u www-data php artisan route:clear >/dev/null

chown -R www-data:www-data storage bootstrap/cache
ln -sfn "$APP/storage/app/public" "$APP/public/storage"

# queue:work carga el codigo al arrancar y se lo queda en memoria, asi que sin
# esto el worker sigue ejecutando la rama anterior. En produccion lo reinicia el
# despliegue; aqui no lo reiniciaba nadie, y costo un rato entender por que un
# correo recien arreglado seguia fallando.
systemctl restart dev-queue.service 2>/dev/null && echo "worker de colas reiniciado"

CODE=$(curl -sL -o /dev/null -w '%{http_code}' -u "real3d:$(cat /root/.dev-web-pass)" https://dev.real3d.io/)
echo "dev.real3d.io responde $CODE"
[ "$CODE" = "200" ] || { echo "ERROR: dev no responde bien"; exit 1; }

#!/bin/bash
# Refresca la base de datos de desarrollo con la ultima copia de produccion,
# anonimizando los datos personales. Como efecto secundario, cada ejecucion
# comprueba que la copia del dia se restaura de verdad.
#
# Uso: dev-refresh.sh
set -euo pipefail

DB=realestate_3d_dev
DUMPS=/var/backups/real3d/dumps
APP=/var/www/dev

log() { echo "[$(date '+%T')] $*"; }

ULTIMA=$(ls -1t "$DUMPS"/db-*.sql.gz 2>/dev/null | head -1)
[ -n "$ULTIMA" ] || { log "ERROR: no hay ninguna copia en $DUMPS"; exit 1; }

log "restaurando desde $(basename "$ULTIMA")"
mysql -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
zcat "$ULTIMA" | mysql "$DB"

TABLAS=$(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB'")
[ "$TABLAS" -ge 30 ] || { log "ERROR: solo $TABLAS tablas, la copia parece incompleta"; exit 1; }

log "anonimizando datos personales"
mysql "$DB" <<'SQL'
UPDATE users SET email = CONCAT('usuario', id, '@dev.local'),
    password = '$2y$12$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ012' WHERE id > 1;
UPDATE users SET email = 'admin@dev.local' WHERE id = 1;
UPDATE inquiries SET name = CONCAT('Contacto ', id),
    email = CONCAT('contacto', id, '@dev.local'), phone = '+34600000000';
UPDATE chatbot_conversations SET visitor_email = NULL, visitor_phone = NULL
    WHERE visitor_email IS NOT NULL OR visitor_phone IS NOT NULL;
SQL

# Que el usuario dev pueda seguir entrando tras recrear la base
mysql -e "GRANT ALL ON $DB.* TO 'dev'@'localhost'; FLUSH PRIVILEGES;"

cd "$APP"
sudo -u www-data php artisan migrate --force 2>&1 | tail -2
sudo -u www-data php artisan config:clear >/dev/null
sudo -u www-data php artisan view:clear >/dev/null

RESUMEN=$(mysql -N "$DB" -e "SELECT CONCAT('proyectos=',(SELECT COUNT(*) FROM projects),
    ' viviendas=',(SELECT COUNT(*) FROM units),' usuarios=',(SELECT COUNT(*) FROM users))")
log "listo: $TABLAS tablas, $RESUMEN"

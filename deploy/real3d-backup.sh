#!/bin/bash
# Copia de seguridad de Real3D.io (produccion .105) con replica en la VM .13 (otro nodo Proxmox).
# Lanzado por real3d-backup.timer cada dia a las 03:30. Registro en /var/log/real3d-backup.log
set -uo pipefail

DB=realestate_3d
DEST=/var/backups/real3d
REMOTE=root@194.41.119.13
REMOTE_DEST=/var/backups/real3d
KEY=/root/.ssh/id_ed25519_backup
APP=/var/www/real3d/current
STAMP=$(date +%Y%m%d-%H%M)
SSH="ssh -i $KEY -o IdentitiesOnly=yes -o BatchMode=yes"

log() { echo "[$(date '+%F %T')] $*"; }
fail=0

mkdir -p "$DEST"

# 1. Base de datos entera. Desde la limpieza de eventos de rastreadores ocupa unos 5 MB,
#    asi que ya no hace falta separar la tabla de analitica.
if mysqldump --single-transaction --quick --routines --triggers --events "$DB" | gzip -6 \
     > "$DEST/db-$STAMP.sql.gz"; then
    log "base de datos OK ($(du -h "$DEST/db-$STAMP.sql.gz" | cut -f1))"
else
    log "ERROR en el volcado de la base de datos"; fail=1
fi

# 2. El .env, que no esta en git y hace falta para restaurar.
#    install en vez de cp -a: el fichero debe llevar la fecha de hoy o la retencion lo borra.
if install -m 600 "$APP/.env" "$DEST/env-$STAMP.txt"; then
    log "env OK"
else
    log "ERROR copiando el .env"; fail=1
fi

# 3. Replica de los volcados en la otra VM
if rsync -a --delete -e "$SSH" "$DEST/" "$REMOTE:$REMOTE_DEST/dumps/"; then
    log "replica de volcados OK"
else
    log "ERROR replicando volcados"; fail=1
fi

# 4. Archivos subidos (1,3 GB): replica incremental, solo lo que cambia
if rsync -a --delete -e "$SSH" "$APP/storage/app/" "$REMOTE:$REMOTE_DEST/storage-app/"; then
    log "replica de storage OK"
else
    log "ERROR replicando storage"; fail=1
fi

# 5. Retencion: 90 dias. A 5 MB por copia son unos 450 MB.
find "$DEST" -name 'db-*.sql.gz' -mtime +90 -delete
find "$DEST" -name 'env-*.txt' -mtime +90 -delete

if [ $fail -eq 0 ]; then
    log "copia completada"
else
    log "copia CON ERRORES"
fi
exit $fail

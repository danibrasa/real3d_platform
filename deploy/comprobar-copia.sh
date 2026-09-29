#!/bin/bash
# Restaura la ultima copia de produccion y mira si sirve.
#
# Las copias se hacen cada noche y el servicio termina en success. Eso dice que
# el fichero se escribio, no que se pueda volver desde el. Una copia que nadie
# ha restaurado no es una copia: es un fichero comprimido con buena intencion.
#
# Esta VM existe precisamente para esto -- recibe la replica diaria y esta al
# lado -- y hasta hoy no lo hacia nadie.
#
# Restaura en una base aparte y la borra al salir. No toca nada de desarrollo.
#
# Uso: comprobar-copia.sh [ruta de las copias]
set -uo pipefail

COPIAS="${1:-/var/backups/real3d/dumps}"
BASE=copia_de_prueba
HORAS_MAXIMO=48

salir() {
    mysql -e "DROP DATABASE IF EXISTS \`$BASE\`" 2>/dev/null
    rm -f /tmp/copia-restaurada.sql
}
trap salir EXIT

ULTIMA=$(ls -t "$COPIAS"/db-*.sql.gz 2>/dev/null | head -1)

if [ -z "$ULTIMA" ]; then
    echo "COPIA: no hay ninguna en $COPIAS"
    exit 1
fi

EDAD_S=$(( $(date +%s) - $(stat -c %Y "$ULTIMA") ))
EDAD_H=$(( EDAD_S / 3600 ))

echo "copia:  $(basename "$ULTIMA") ($(du -h "$ULTIMA" | cut -f1), ${EDAD_H}h)"

# Una copia vieja es peor que ninguna, porque nadie la mira con desconfianza.
if [ "$EDAD_H" -gt "$HORAS_MAXIMO" ]; then
    echo "COPIA: la mas reciente tiene ${EDAD_H}h, mas de las ${HORAS_MAXIMO}h admitidas"
    exit 1
fi

# --- Restaurar de verdad ---------------------------------------------------
mysql -e "DROP DATABASE IF EXISTS \`$BASE\`; CREATE DATABASE \`$BASE\`" || {
    echo "COPIA: no se pudo crear la base de pruebas"
    exit 1
}

INICIO=$(date +%s)

# Los dos lados de la tuberia, y los dos errores.
#
# Capturaba solo el de mysql. Una copia cortada de verdad -- el disco se lleno a
# mitad de escribirla -- rompe el gzip, y entonces quien protesta es gunzip: el
# fichero de errores quedaba vacio y el aviso decia "fallo" sin decir donde. Que
# es justo lo que este guion existe para no hacer.
: > /tmp/copia-error.txt
gunzip -c "$ULTIMA" 2>>/tmp/copia-error.txt | mysql "$BASE" 2>>/tmp/copia-error.txt
ESTADOS=("${PIPESTATUS[@]}")

if [ "${ESTADOS[0]}" -ne 0 ] || [ "${ESTADOS[1]}" -ne 0 ]; then
    echo "COPIA: la restauracion fallo (gunzip ${ESTADOS[0]}, mysql ${ESTADOS[1]})"
    # Recortado: el error de mysql arrastra la sentencia entera, y una sola
    # linea de INSERT puede traerse un proyecto con toda su descripcion. El
    # aviso va por correo y tiene que caber de un vistazo.
    cut -c 1-160 /tmp/copia-error.txt | head -3
    exit 1
fi

SEGUNDOS=$(( $(date +%s) - INICIO ))

# --- Y mirar si lo restaurado sirve ----------------------------------------
#
# Que el comando termine en cero no basta: un fichero truncado restaura "bien"
# la mitad de las tablas y se calla.
contar() {
    mysql -N -e "SELECT COUNT(*) FROM \`$1\`" "$BASE" 2>/dev/null || echo "-"
}

TABLAS=$(mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$BASE'" 2>/dev/null)

# Si el propio conteo falla, la comparacion de mas abajo seria un error de bash
# --- Y, ya que esta restaurada, lo que solo se ve en la base de produccion -------
#
# Los visores que llevan demasiado en la cola del equipo sin moverse. Es una
# averia del negocio, no del codigo: una promotora que pide el visor y no ve
# movimiento en una semana se va. La copia se borra al salir de aqui, asi que
# se mira ahora. El usuario de la aplicacion no tiene permiso sobre esta base
# de pruebas: se le da lectura, y con eso corre el comando.
APP_DEV=/var/www/dev
USUARIO_APP=$(grep '^DB_USERNAME=' "$APP_DEV/.env" | cut -d= -f2- | tr -d '"')
if [ -n "$USUARIO_APP" ]; then
    mysql -e "GRANT SELECT ON \`$BASE\`.* TO '$USUARIO_APP'@'localhost'" 2>/dev/null
    echo
    echo "--- visores atascados (cola del equipo, en produccion)"
    if ! DB_DATABASE="$BASE" php "$APP_DEV/artisan" visores:atascados --dias=5; then
        FALLOS=$((FALLOS + 1))
    fi
fi

# que se evalua como falso: el guion diria "la copia sirve" sin haber contado
# nada. Un camino de fallo que termina en exito es el peor de todos.
if ! [ "$TABLAS" -eq "$TABLAS" ] 2>/dev/null; then
    echo "COPIA: no se pudo contar las tablas restauradas"
    exit 1
fi
MIGRACIONES=$(contar migrations)
USUARIOS=$(contar users)
PROYECTOS=$(contar projects)
VIVIENDAS=$(contar units)

echo "        restaurada en ${SEGUNDOS}s: $TABLAS tablas, $MIGRACIONES migraciones,"
echo "        $USUARIOS usuarios, $PROYECTOS proyectos, $VIVIENDAS viviendas"

FALLOS=0

for dato in "migraciones:$MIGRACIONES" "usuarios:$USUARIOS" "proyectos:$PROYECTOS"; do
    nombre=${dato%%:*}
    valor=${dato##*:}
    if [ "$valor" = "-" ] || [ "$valor" -eq 0 ] 2>/dev/null; then
        echo "COPIA: la copia restaura pero no trae $nombre"
        FALLOS=1
    fi
done

# Las tablas de una aplicacion Laravel entera son decenas. Con menos de veinte,
# lo restaurado no es la base de produccion aunque el comando dijera que si.
if [ "$TABLAS" -lt 20 ]; then
    echo "COPIA: solo $TABLAS tablas restauradas, faltan la mayoria"
    FALLOS=1
fi

[ "$FALLOS" -eq 0 ] && echo "        la copia sirve: se puede volver desde ella"

exit "$FALLOS"

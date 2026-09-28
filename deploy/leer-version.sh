#!/bin/bash
# Dice que version tiene el codigo que hay en un directorio.
#
# Vive aparte para poder probarlo. Antes esta logica estaba copiada dentro de
# real3d-deploy.sh y staging-deploy.sh, y el commit que la escribio afirmaba
# "comprobado con los cuatro casos" sin que nada en el repositorio lo sostuviera
# ni lo cazara si se rompia. Ahora hay un test que la ejercita.
#
# Uso: leer-version.sh /ruta/del/codigo   ->  imprime v1.3.1, o sin-tag
set -uo pipefail

DIR="${1:?uso: leer-version.sh /ruta/del/codigo}"

# `version.txt` lo mantiene release-please en cada release y viaja con el codigo,
# asi que no depende de la profundidad del clon. `git describe` fallaba en cuanto
# se acumulaban mas de veinte commits desde la ultima etiqueta, porque el
# despliegue clona superficial.
#
# Se quitan tambien los retornos de carro: un version.txt guardado desde Windows
# dejaba un \r pegado al numero, y como la comprobacion de abajo no ancla el
# final, habria colado una version con un caracter de control dentro.
VERSION=$(tr -d ' \r\n' < "$DIR/version.txt" 2>/dev/null || true)

# Que el fichero se pueda leer no dice nada de lo que hay dentro: vacio daba "v".
if printf '%s' "$VERSION" | grep -qE '^v?[0-9]+\.[0-9]+\.[0-9]+$'; then
    echo "v${VERSION#v}"
    exit 0
fi

git -C "$DIR" describe --tags --abbrev=0 2>/dev/null || echo "sin-tag"

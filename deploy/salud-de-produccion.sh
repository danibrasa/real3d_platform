#!/bin/bash
# Pregunta a produccion como va por dentro.
#
# Lo que la comprobacion nocturna no puede ver desde aqui: si la cola avanza y
# si el proveedor de correo contesta. Las dos han fallado en silencio y las dos
# costaron semanas, con la web devolviendo 200 todo el rato.
#
# Y con el correo hay una pescadilla: si esta roto, el aviso no puede ir por
# correo. Por eso produccion se examina y publica el resultado, y esta maquina
# -- que es la que avisa -- lo lee.
#
# Uso: salud-de-produccion.sh [url]
set -uo pipefail

URL="${1:-https://real3d.io}/salud"

# Hasta cuando se admite que produccion no conteste por no estar desplegada.
# Despues de esa fecha, un 404 es una averia y no una espera.
CADUCA="${CADUCA_PENDIENTE:-20261013}"

# Desarrollo y staging estan detras de autenticacion basica; produccion no.
# Sin esto, el guion solo se podia probar contra la maquina a la que apunta,
# que es la unica donde no conviene descubrir que algo no funciona.
CREDENCIALES=()
[ -n "${CLAVE_WEB:-}" ] && CREDENCIALES=(-u "real3d:${CLAVE_WEB}")

# El certificado. certbot lo renueva solo, hasta el dia que no: un cambio en
# nginx que rompa el reto ACME y la renovacion falla en silencio durante
# semanas mientras el certificado sigue valiendo. Se avisa con margen, que es
# lo que la renovacion automatica no da.
HOST=$(printf '%s' "$URL" | sed -E 's#^https?://([^/:]+).*#\1#')
DIAS_MINIMOS="${CERT_DIAS_MINIMOS:-14}"
CERT_MAL=0
CADUCA_CERT=$(echo | openssl s_client -connect "$HOST:443" -servername "$HOST" 2>/dev/null \
    | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
if [ -z "$CADUCA_CERT" ]; then
    echo "        certificado: no se pudo leer el de $HOST"
    CERT_MAL=1
else
    DIAS=$(( ( $(date -d "$CADUCA_CERT" +%s) - $(date +%s) ) / 86400 ))
    if [ "$DIAS" -lt "$DIAS_MINIMOS" ]; then
        echo "PRODUCCION: el certificado de $HOST caduca en $DIAS dias ($CADUCA_CERT)"
        CERT_MAL=1
    else
        echo "        certificado ok, caduca en $DIAS dias"
    fi
fi

RESPUESTA=$(curl -s -m 25 ${CREDENCIALES[@]+"${CREDENCIALES[@]}"} -w $'\n%{http_code}' "$URL" 2>/dev/null)
CODIGO=$(printf '%s' "$RESPUESTA" | tail -n 1)
CUERPO=$(printf '%s' "$RESPUESTA" | sed '$d')

case "$CODIGO" in
    200|503)
        # El resumen, una linea por comprobacion.
        printf '%s' "$CUERPO" | python3 -c '
import json, sys
try:
    d = json.load(sys.stdin)
except Exception as e:
    print("        no se entiende la respuesta: %s" % e)
    sys.exit(0)
for nombre, c in d.get("comprobaciones", {}).items():
    print("        %-7s %-3s %s" % (nombre, "ok" if c.get("ok") else "MAL", c.get("detalle", "")))
'
        [ "$CODIGO" = "200" ] && exit "$CERT_MAL"
        echo "PRODUCCION: alguna comprobacion va mal (503)"
        exit 1
        ;;

    404)
        # La ruta no existe: o el codigo no esta desplegado en produccion, o
        # esta maquina ya no tiene permiso para preguntar. Los dos casos dan
        # 404 a proposito -- negar y no existir se responden igual para no
        # delatar la ruta -- y por eso no se pueden distinguir desde aqui.
        #
        # Con fecha de caducidad. Un "pendiente" indefinido es justo la
        # vigilancia apagada en silencio que esto viene a evitar: el dia que
        # cambie la IP de salida, o se pierda la configuracion, la nocturna
        # seguiria en verde diciendo "pendiente" para siempre.
        if [ "$(date +%Y%m%d)" -le "$CADUCA" ]; then
            echo "        PENDIENTE: /salud no responde en produccion."
            echo "        Se activa en el proximo despliegue. A partir del"
            echo "        $CADUCA esto pasa a contar como averia."
            exit "$CERT_MAL"
        fi

        echo "PRODUCCION: /salud sigue sin responder despues del $CADUCA."
        echo "            O no se ha desplegado, o el vigilante ya no tiene permiso."
        exit 1
        ;;

    000)
        echo "PRODUCCION: no contesta en $URL"
        exit 1
        ;;

    *)
        echo "PRODUCCION: /salud devuelve $CODIGO"
        exit 1
        ;;
esac

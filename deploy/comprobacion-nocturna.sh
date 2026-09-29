#!/bin/bash
# Comprueba cada noche que Real3D se puede usar de verdad.
#
# No mira si los servidores responden: recorre el alta entera como una promotora
# nueva -registro, empresa, plan, panel, proyecto, importacion de un Excel sucio,
# publicacion- y avisa si algo se rompe o si algo responde bien pero no hace lo
# que debe.
#
# Existe porque el 28-sep-2026 el producto llevaba semanas sin poder usarse: los
# avisos de consultas no salian de la maquina, y una promotora que se registrara
# no podia crear nada. Los tests pasaban en verde. Lo encontro intentar usarlo.
#
# Lo lanza real3d-comprobacion.timer. Uso a mano: comprobacion-nocturna.sh
set -uo pipefail

APP=/var/www/dev
URL=https://dev.real3d.io
AVISAR_A="${AVISAR_A:-danibrasa@gmail.com}"
SALIDA=$(mktemp)
trap 'rm -f "$SALIDA"' EXIT

{
    echo "Comprobacion de $URL"
    echo "$(date '+%Y-%m-%d %H:%M')"
    echo
} > "$SALIDA"

# Por que canal sale el correo, dicho en voz alta.
#
# Este recorrido genera varios avisos por vuelta -bienvenida, solicitud de
# visor, visor montado, lead- y cada uno le cuesta un envio a la cuenta. Con el
# mailer por defecto apuntando a un proveedor de verdad, y compartiendo cuenta
# con produccion, unas cuantas vueltas seguidas se comen el cupo diario y
# produccion se queda sin poder avisar de un lead. Paso el 28-sep-2026 y no lo
# dijo nadie: se supo porque el proveedor mando un correo.
MAILER=$(php -r 'require "/var/www/dev/vendor/autoload.php";
$a = require "/var/www/dev/bootstrap/app.php";
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo config("mail.default");' 2>/dev/null || echo "desconocido")

{
    echo "correo del recorrido: $MAILER"
    if [ "$MAILER" != "log" ] && [ "$MAILER" != "array" ]; then
        echo "  OJO: cada vuelta gasta envios de la cuenta de correo,"
        echo "  que es la misma que usa produccion."
    fi
    echo
} >> "$SALIDA"

# La contraseña del acceso web vive en el fichero de siempre, no aqui.
CLAVE_WEB=$(cat /root/.dev-web-pass 2>/dev/null || echo "")

# GANCHO_VISOR hace lo unico que no hace la promotora: que el equipo monte el
# visor. Y lo hace por el camino de verdad, subiendo el fichero en trozos, que
# es donde han aparecido los fallos mas caros: reemplazo, cuota y ensamblado.
# Antes usaba un atajo que creaba el registro a mano y se saltaba justo eso:
# un comprobador que esquiva lo que suele romperse no comprueba gran cosa.
# GANCHO_CORREO comprueba que el aviso del lead sale de la cola de verdad: que
# el formulario responda 200 no prueba que la promotora se entere.
# Y que va dirigido a la promotora, y que es el aviso de ese camino: con
# MAIL_MAILER=log el correo se escribe en storage/logs/correo.log
# (MAIL_LOG_CHANNEL=correo) y el gancho lo lee. Sin ese canal, con
# LOG_LEVEL=warning, el mailer log no escribia nada, y "salio de la cola" se
# decia de avisos que no iban a ninguna parte.
# GANCHO_VISOR va como www-data, que es quien escribe cuando escribe la
# aplicacion. Corriendo como root dejaba los directorios del proyecto con
# permisos que el servidor web no puede atravesar: el fichero quedaba en disco,
# la comprobacion decia "visor subido e identico al original", y el visor
# devolvia 404 a cualquiera que lo abriera. Semanas diciendo que si sobre
# ficheros que nadie podia ver.
#
# GANCHO_NAVEGADOR abre el visor del proyecto con un Chromium de verdad, como
# un telefono, y mira lo que solo un navegador puede ver: que pinta, que la
# lista de viviendas aparece y tocar una abre su ficha, que no desborda, que
# la consola esta limpia. Necesita Playwright en esta maquina:
#     npx playwright install --with-deps chromium
#
# GANCHO_BAJA es lo que hace una baja: deja a la promotora en el plan gratuito,
# como haria el webhook de Stripe al cancelar. Sirve para el ultimo paso, que
# comprueba que al dejar de pagar el visor deja de servirse y la pagina no.
#
# GANCHO_PLAN es lo que en produccion hace una contratacion. Sin el, el
# recorrido se para en el paso 7b -- limpiamente y diciendo por que, pero se
# para -- y perderiamos la vigilancia de todo lo que viene detras: subida del
# visor, publicacion y aviso del lead, que es donde han salido los fallos mas
# caros. El paso anterior comprueba que sin plan el visor esta cerrado, asi
# que esto no esquiva el muro: pasa por el.
CLAVE_WEB="$CLAVE_WEB" \
    GANCHO_PLAN="php $APP/tools/dar-plan-de-prueba.php $APP" \
    GANCHO_BAJA="php $APP/tools/quitar-plan-de-prueba.php $APP" \
    GANCHO_VISOR="sudo -u www-data php $APP/tools/subir-visor-de-prueba.php $APP" \
    GANCHO_CORREO="php $APP/tools/comprobar-cola.php $APP 40" \
    GANCHO_NAVEGADOR="node $APP/tools/recorrido-visor.mjs" \
    GANCHO_PANEL="node $APP/tools/recorrido-panel.mjs" \
    python3 "$APP/tools/recorrido-alta.py" "$URL" >> "$SALIDA" 2>&1
RESULTADO=$?

# Se limpia siempre, salga bien o mal: si no, cada noche deja una promotora
# fantasma en desarrollo.
php "$APP/tools/limpiar-recorrido.php" "$APP" >> "$SALIDA" 2>&1

# La copia de produccion, restaurada de verdad.
#
# El servicio de copias termina en success cada noche, y eso solo dice que el
# fichero se escribio. Esta VM existe para probar restauraciones y hasta hoy no
# lo hacia nadie: la primera vez que se restauro una fue a mano, hoy. Va aqui y
# no en su propio temporizador para que haya un solo vigilante y un solo aviso.
{
    echo
    echo "--- copia de seguridad"
} >> "$SALIDA"

bash "$APP/deploy/comprobar-copia.sh" >> "$SALIDA" 2>&1
COPIA=$?

# Como va produccion por dentro: si la cola avanza y si el correo contesta.
#
# Desde aqui no se ve, asi que produccion se examina y publica el resultado y
# esta maquina lo lee. Con el correo hay ademas una pescadilla -- si esta roto,
# el aviso no puede ir por correo -- que es justo por lo que hace falta.
{
    echo
    echo "--- produccion por dentro"
} >> "$SALIDA"

bash "$APP/deploy/salud-de-produccion.sh" >> "$SALIDA" 2>&1
SALUD=$?

# Lo que pesa el visor de produccion antes de poder mirar. El objetivo del
# plan son 4 MB; hoy son 55. Mientras se baja, esto es un freno: si un dia
# pesa mas de lo que pesaba, se avisa. Cuando se llegue al objetivo, bajar
# el presupuesto aqui a 4 y dejarlo.
{
    echo
    echo "--- peso del visor de produccion"
} >> "$SALIDA"

PRESUPUESTO_MB="${PRESUPUESTO_VISOR_MB:-60}" python3 "$APP/tools/medir-visor.py" \
    "${VISOR_DE_REFERENCIA:-https://real3d.io/projects/salado}" >> "$SALIDA" 2>&1
PESO=$?

cat "$SALIDA"

# Cualquiera de las dos cosas mal es motivo de aviso: un recorrido roto y
# una copia que no restaura son igual de urgentes.
[ "$RESULTADO" -eq 0 ] && [ "$COPIA" -eq 0 ] && [ "$SALUD" -eq 0 ] && [ "$PESO" -eq 0 ] && exit 0

# Solo se avisa cuando algo va mal. Un correo cada noche diciendo que todo bien
# se deja de leer a la semana, y entonces tampoco se lee el que importa.
echo "== avisando por correo a $AVISAR_A"

php -r '
$ruta = "/var/www/dev";
require $ruta."/vendor/autoload.php";
$app = require $ruta."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cuerpo = file_get_contents($argv[1]);
$para = $argv[2];

try {
    Illuminate\Support\Facades\Mail::mailer("alertas")->raw($cuerpo, function ($m) use ($para) {
        $m->to($para)->subject("Real3D: la comprobacion nocturna ha fallado");
    });
    echo "aviso enviado\n";
} catch (Throwable $e) {
    // Si el correo tampoco sale, el problema es mas gordo todavia.
    fwrite(STDERR, "NO SE PUDO AVISAR: ".$e->getMessage()."\n");
    exit(1);
}
' "$SALIDA" "$AVISAR_A"

exit 1

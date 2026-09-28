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

# La contraseña del acceso web vive en el fichero de siempre, no aqui.
CLAVE_WEB=$(cat /root/.dev-web-pass 2>/dev/null || echo "")

CLAVE_WEB="$CLAVE_WEB" python3 "$APP/tools/recorrido-alta.py" "$URL" >> "$SALIDA" 2>&1
RESULTADO=$?

# Se limpia siempre, salga bien o mal: si no, cada noche deja una promotora
# fantasma en desarrollo.
php "$APP/tools/limpiar-recorrido.php" "$APP" >> "$SALIDA" 2>&1

cat "$SALIDA"

[ "$RESULTADO" -eq 0 ] && exit 0

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
    Illuminate\Support\Facades\Mail::raw($cuerpo, function ($m) use ($para) {
        $m->to($para)->subject("Real3D: el recorrido de alta ha fallado");
    });
    echo "aviso enviado\n";
} catch (Throwable $e) {
    // Si el correo tampoco sale, el problema es mas gordo todavia.
    fwrite(STDERR, "NO SE PUDO AVISAR: ".$e->getMessage()."\n");
    exit(1);
}
' "$SALIDA" "$AVISAR_A"

exit 1

<?php

/**
 * Le da a la promotora del recorrido un plan de pago.
 *
 * Es lo que en produccion hace una contratacion. Aqui no se puede contratar
 * porque el entorno de desarrollo no tiene pasarela, asi que se hace por el
 * otro camino legitimo: el que usa un superadmin cuando concede un plan a
 * mano -- una prueba, un acuerdo, una promotora invitada-. El mismo que
 * mira AccesoAlVisor.
 *
 * No es un atajo para saltarse la puerta: el recorrido comprueba ANTES que
 * con el plan gratuito el visor esta cerrado, y solo despues pasa por aqui.
 * Sin este paso el recorrido no podria seguir, y perderiamos la vigilancia de
 * todo lo que viene detras: subida del visor, publicacion y el aviso del lead.
 *
 * Uso: php dar-plan-de-prueba.php /ruta/de/la/app <id del proyecto>
 */

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Support\Facturacion\PlanDeStripe;
use Illuminate\Contracts\Console\Kernel;

$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');
$idProyecto = (int) ($argv[2] ?? 0);

if (! $idProyecto) {
    fwrite(STDERR, "uso: php dar-plan-de-prueba.php /ruta/de/la/app <id>\n");
    exit(2);
}

require $ruta.'/vendor/autoload.php';
$app = require $ruta.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$proyecto = Project::find($idProyecto);

if (! $proyecto) {
    fwrite(STDERR, "no existe el proyecto {$idProyecto}\n");
    exit(1);
}

// Quien lo creo es la promotora del recorrido.
$perfil = CompanyProfile::where('user_id', $proyecto->created_by)->first();

if (! $perfil) {
    fwrite(STDERR, "el proyecto {$idProyecto} no tiene empresa detras\n");
    exit(1);
}

PlanDeStripe::aplicar($perfil, CompanyProfile::PLAN_PROFESSIONAL);

echo "plan {$perfil->fresh()->plan_tier} para {$perfil->company_name}\n";

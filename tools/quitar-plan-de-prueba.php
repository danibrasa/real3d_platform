<?php

/**
 * Deja a la promotora del recorrido en el plan gratuito.
 *
 * Es lo que en produccion hace una baja: el webhook de Stripe llama a
 * PlanDeStripe::aplicar con starter cuando llega customer.subscription.deleted.
 * Aqui se hace lo mismo por el otro camino, porque el entorno de desarrollo no
 * tiene pasarela que pueda mandar ese evento.
 *
 * Sirve para que el recorrido compruebe lo que pasa despues, que es la mitad
 * del circuito del dinero que no vigilaba nadie: el visor deja de servirse, la
 * pagina sigue en pie, y a la promotora se le dice.
 *
 * Uso: php quitar-plan-de-prueba.php /ruta/de/la/app <id del proyecto>
 */

use App\Models\CompanyProfile;
use App\Models\Project;
use App\Support\Facturacion\PlanDeStripe;
use Illuminate\Contracts\Console\Kernel;

$ruta = rtrim($argv[1] ?? '/var/www/dev', '/');
$idProyecto = (int) ($argv[2] ?? 0);

if (! $idProyecto) {
    fwrite(STDERR, "uso: php quitar-plan-de-prueba.php /ruta/de/la/app <id>\n");
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

$perfil = CompanyProfile::where('user_id', $proyecto->created_by)->first();

if (! $perfil) {
    fwrite(STDERR, "el proyecto {$idProyecto} no tiene empresa detras\n");
    exit(1);
}

PlanDeStripe::aplicar($perfil, CompanyProfile::PLAN_STARTER);

echo "baja aplicada: {$perfil->company_name} vuelve al plan {$perfil->fresh()->plan_tier}\n";

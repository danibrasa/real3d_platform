<?php

namespace App\Providers;

use App\Models\ApiToken;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(ApiToken::class);

        $this->defineGates();
        $this->defineRateLimiters();
        $this->registerViewComposers();
    }

    private function defineGates(): void
    {
        // El reparto es deliberado: la promotora gestiona lo comercial (sus
        // proyectos, sus viviendas, sus precios) y el montaje 3D -modelo, video
        // 360, encuadre de camara- lo hace el equipo. Encaja con lo que las
        // promotoras tienen de verdad: hojas de calculo y renders, no GLB.
        Gate::define('create-project', function (User $user) {
            if ($user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR)) {
                return true;
            }

            // El limite del plan no se comprueba aqui: se comprueba en el
            // controlador, para poder explicarle que ha llegado a su tope en
            // vez de darle un 403 sin contexto.
            return $user->isInmobiliaria();
        });

        Gate::define('delete-project', function (User $user) {
            return $user->isSuperadmin();
        });

        Gate::define('edit-project-technical', function (User $user, ?Project $project = null) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
        });

        Gate::define('edit-project-commercial', function (User $user, ?Project $project = null) {
            if ($user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR)) {
                return true;
            }
            if ($user->isInmobiliaria() && $project) {
                return $user->canAccessProject($project);
            }

            return false;
        });

        Gate::define('upload-files', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
        });

        Gate::define('edit-viewer-settings', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
        });

        // Ojo al `?Project`: sin proyecto una promotora NO pasa. Asi, si algun
        // sitio olvida pasarlo, el fallo es cerrar de mas y no abrir de mas.
        Gate::define('create-unit', function (User $user, ?Project $project = null) {
            if ($user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR)) {
                return true;
            }

            return $user->isInmobiliaria() && $project && $user->canAccessProject($project);
        });

        Gate::define('edit-unit-full', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
        });

        Gate::define('edit-unit-commercial', function (User $user, ?Project $project = null) {
            if ($user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR)) {
                return true;
            }
            if ($user->isInmobiliaria() && $project) {
                return $user->canAccessProject($project);
            }

            return false;
        });

        Gate::define('manage-bbox', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
        });

        // Las tipologias son catalogo comercial: que tipos de vivienda se
        // venden. Nada que ver con el montaje 3D.
        Gate::define('manage-typologies', function (User $user, ?Project $project = null) {
            if ($user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR)) {
                return true;
            }

            return $user->isInmobiliaria() && $project && $user->canAccessProject($project);
        });

        Gate::define('manage-gallery', function (User $user, ?Project $project = null) {
            if ($user->isSuperadmin()) {
                return true;
            }
            if ($user->isInmobiliaria() && $project) {
                return $user->canAccessProject($project);
            }

            return false;
        });

        Gate::define('view-inquiries', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_INMOBILIARIA, User::ROLE_AGENTE);
        });

        Gate::define('delete-inquiry', function (User $user) {
            return $user->isSuperadmin();
        });

        Gate::define('manage-users', function (User $user) {
            return $user->isSuperadmin();
        });

        Gate::define('manage-agents', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_INMOBILIARIA);
        });

        Gate::define('assign-projects', function (User $user) {
            return $user->isSuperadmin();
        });

        Gate::define('manage-currencies', function (User $user) {
            return $user->isSuperadmin();
        });

        Gate::define('manage-api-tokens', function (User $user) {
            if ($user->isSuperadmin()) {
                return true;
            }

            return $user->isInmobiliaria() && $user->hasFeature('api_access');
        });

        Gate::define('view-audit-logs', fn (User $user) => $user->isSuperadmin());

        Gate::define('manage-blog', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
        });

        // SaaS feature gates
        Gate::define('use-chatbot', fn (User $user) => $user->hasFeature('chatbot'));
        Gate::define('use-analytics', fn (User $user) => $user->hasFeature('analytics'));
        Gate::define('use-api', fn (User $user) => $user->hasFeature('api_access'));
        Gate::define('use-embed-widget', fn (User $user) => $user->hasFeature('embed_widget'));
        Gate::define('use-webhooks', fn (User $user) => $user->hasFeature('api_access'));
    }

    private function defineRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $token = $request->user()?->currentAccessToken();
            $limit = $token?->rate_limit ?? 60;

            return Limit::perMinute($limit)->by($token?->id ?? $request->ip());
        });

        RateLimiter::for('chatbot', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // El formulario de consulta. Cada envio son dos correos -- el aviso a
        // la promotora y el acuse al comprador -- del cupo de cien al dia de
        // la cuenta de correo. Sin freno, un guion con cincuenta envios deja a
        // produccion sin poder avisar de un lead de verdad el resto del dia,
        // que es justo lo que paso el 28-sep-2026 por otro camino. Una
        // persona manda una consulta; cinco por minuto es de sobra. Y un tope
        // al dia ademas, que es la unidad del cupo: a cinco por minuto, un
        // guion paciente se lo comia igual en veinte minutos.
        RateLimiter::for('consultas', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perDay(20)->by('dia:'.$request->ip()),
            ];
        });

        // Los eventos del visor, que se guardan sin sesion ni CSRF y hasta
        // cincuenta por peticion. Un visor de verdad manda un lote cada pocos
        // segundos; sesenta por minuto es mas de lo que hace nadie.
        RateLimiter::for('eventos', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Registro, login y recuperacion de contrasena. Un registro y un
        // "olvide mi contrasena" son un correo cada uno, del mismo cupo; y
        // el login ya se frena por cuenta en LoginRequest, esto es por
        // direccion. Diez por minuto: nadie se equivoca mas veces que eso.
        RateLimiter::for('acceso', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // El servidor MCP solo lee datos publicos, pero cada llamada es una
        // consulta a la base.
        RateLimiter::for('mcp', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }

    private function registerViewComposers(): void
    {
        View::composer('layouts.navigation', function ($view) {
            $unreadInquiries = 0;
            if (auth()->check()) {
                $user = auth()->user();
                if ($user->isSuperadmin()) {
                    $unreadInquiries = Inquiry::where('read', false)->count();
                } elseif ($user->isInmobiliaria()) {
                    $projectIds = $user->assignedProjects()->pluck('projects.id');
                    $unreadInquiries = Inquiry::where('read', false)
                        ->whereIn('project_id', $projectIds)->count();
                } elseif ($user->isAgente() && $user->agency_id) {
                    $projectIds = User::find($user->agency_id)?->assignedProjects()->pluck('projects.id') ?? collect();
                    $unreadInquiries = Inquiry::where('read', false)
                        ->whereIn('project_id', $projectIds)->count();
                }
            }
            $view->with('unreadInquiries', $unreadInquiries);
        });
    }
}

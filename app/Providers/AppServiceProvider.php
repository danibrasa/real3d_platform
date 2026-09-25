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
        Gate::define('create-project', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
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

        Gate::define('create-unit', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
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

        Gate::define('manage-typologies', function (User $user) {
            return $user->hasRole(User::ROLE_SUPERADMIN, User::ROLE_GESTOR);
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

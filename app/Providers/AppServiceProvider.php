<?php

namespace App\Providers;

use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->defineGates();
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

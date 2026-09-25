<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Auditable, Billable, HasApiTokens, HasFactory, Notifiable;

    const ROLE_SUPERADMIN = 'superadmin';

    const ROLE_GESTOR = 'gestor';

    const ROLE_INMOBILIARIA = 'inmobiliaria';

    const ROLE_AGENTE = 'agente';

    const ROLE_USER = 'user';

    const ADMIN_ROLES = [
        self::ROLE_SUPERADMIN,
        self::ROLE_GESTOR,
        self::ROLE_INMOBILIARIA,
        self::ROLE_AGENTE,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'agency_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Role checks ---

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isGestor(): bool
    {
        return $this->role === self::ROLE_GESTOR;
    }

    public function isInmobiliaria(): bool
    {
        return $this->role === self::ROLE_INMOBILIARIA;
    }

    public function isAgente(): bool
    {
        return $this->role === self::ROLE_AGENTE;
    }

    public function hasAdminAccess(): bool
    {
        return in_array($this->role, self::ADMIN_ROLES);
    }

    /**
     * Backward compat: isAdmin() now means superadmin
     */
    public function isAdmin(): bool
    {
        return $this->isSuperadmin();
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    // --- Company Profile (SaaS) ---

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function hasFeature(string $feature): bool
    {
        if ($this->isSuperadmin() || $this->isGestor()) {
            return true;
        }
        if ($this->isInmobiliaria()) {
            return $this->companyProfile?->hasFeature($feature) ?? false;
        }
        if ($this->isAgente() && $this->agency_id) {
            return User::find($this->agency_id)?->companyProfile?->hasFeature($feature) ?? false;
        }

        return false;
    }

    // --- Relationships ---

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /** For agente: the inmobiliaria they belong to */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    /** For inmobiliaria: their agents */
    public function agents(): HasMany
    {
        return $this->hasMany(User::class, 'agency_id');
    }

    /** For inmobiliaria: projects assigned via pivot */
    public function assignedProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')->withTimestamps();
    }

    // --- Project access ---

    /**
     * Returns a query builder for projects this user can access in admin.
     */
    public function accessibleProjects()
    {
        if ($this->isSuperadmin() || $this->isGestor()) {
            return Project::query();
        }

        if ($this->isInmobiliaria()) {
            $projectIds = $this->assignedProjects()->pluck('projects.id');

            return Project::whereIn('id', $projectIds);
        }

        if ($this->isAgente() && $this->agency_id) {
            $projectIds = User::find($this->agency_id)?->assignedProjects()->pluck('projects.id') ?? collect();

            return Project::whereIn('id', $projectIds);
        }

        return Project::whereRaw('0 = 1'); // no access
    }

    /**
     * Check if user can access a specific project in admin.
     */
    public function canAccessProject(Project $project): bool
    {
        if ($this->isSuperadmin() || $this->isGestor()) {
            return true;
        }

        if ($this->isInmobiliaria()) {
            return $this->assignedProjects()->where('projects.id', $project->id)->exists();
        }

        if ($this->isAgente() && $this->agency_id) {
            return User::find($this->agency_id)?->assignedProjects()->where('projects.id', $project->id)->exists() ?? false;
        }

        return false;
    }
}

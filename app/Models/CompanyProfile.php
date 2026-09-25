<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CompanyProfile extends Model
{
    const PLAN_STARTER = 'starter';

    const PLAN_PROFESSIONAL = 'professional';

    const PLAN_ENTERPRISE = 'enterprise';

    const PLAN_LIMITS = [
        self::PLAN_STARTER => [
            'max_projects' => 1,
            'max_storage_bytes' => 1073741824, // 1GB
            'chatbot' => false,
            'analytics' => false,
            'api_access' => false,
            'embed_widget' => false,
            'max_agents' => 0,
            'max_units_per_project' => 20,
        ],
        self::PLAN_PROFESSIONAL => [
            'max_projects' => 5,
            'max_storage_bytes' => 10737418240, // 10GB
            'chatbot' => true,
            'analytics' => true,
            'api_access' => false,
            'embed_widget' => true,
            'max_agents' => 5,
            'max_units_per_project' => 100,
        ],
        self::PLAN_ENTERPRISE => [
            'max_projects' => 999,
            'max_storage_bytes' => 107374182400, // 100GB
            'chatbot' => true,
            'analytics' => true,
            'api_access' => true,
            'embed_widget' => true,
            'max_agents' => 50,
            'max_units_per_project' => 9999,
        ],
    ];

    protected $fillable = [
        'user_id',
        'company_name',
        'slug',
        'legal_name',
        'tax_id',
        'phone',
        'website',
        'description',
        'description_en',
        'logo_path',
        'country',
        'city',
        'address',
        'storage_used_bytes',
        'plan_tier',
        'max_projects',
        'max_storage_bytes',
        'is_verified',
        'show_in_directory',
    ];

    protected $casts = [
        'storage_used_bytes' => 'integer',
        'max_projects' => 'integer',
        'max_storage_bytes' => 'integer',
        'is_verified' => 'boolean',
        'show_in_directory' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (CompanyProfile $profile) {
            if (empty($profile->slug)) {
                $profile->slug = Str::slug($profile->company_name);
                $original = $profile->slug;
                $count = 1;
                while (static::where('slug', $profile->slug)->exists()) {
                    $profile->slug = $original.'-'.$count++;
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPlanLimits(): array
    {
        return self::PLAN_LIMITS[$this->plan_tier] ?? self::PLAN_LIMITS[self::PLAN_STARTER];
    }

    public function hasFeature(string $feature): bool
    {
        $limits = $this->getPlanLimits();

        return $limits[$feature] ?? false;
    }

    public function canCreateProject(): bool
    {
        $currentCount = $this->user->assignedProjects()->count();

        return $currentCount < $this->max_projects;
    }

    public function storageUsedPercent(): float
    {
        if ($this->max_storage_bytes <= 0) {
            return 0;
        }

        return round(($this->storage_used_bytes / $this->max_storage_bytes) * 100, 1);
    }

    public function hasStorageAvailable(int $additionalBytes = 0): bool
    {
        return ($this->storage_used_bytes + $additionalBytes) <= $this->max_storage_bytes;
    }

    public function recalculateStorage(): void
    {
        $projectIds = $this->user->assignedProjects()->pluck('projects.id');
        $totalBytes = ProjectFile::whereIn('project_id', $projectIds)->sum('file_size');
        $this->update(['storage_used_bytes' => $totalBytes]);
    }
}

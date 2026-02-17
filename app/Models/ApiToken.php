<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken;

class ApiToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    protected $casts = [
        'abilities' => 'json',
        'project_ids' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function canAccessProject(int $projectId): bool
    {
        if (is_null($this->project_ids)) {
            return true;
        }

        return in_array($projectId, $this->project_ids);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function assignedProjects()
    {
        if (is_null($this->project_ids)) {
            return Project::where('status', '!=', 'draft');
        }

        return Project::whereIn('id', $this->project_ids);
    }
}

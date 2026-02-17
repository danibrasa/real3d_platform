<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            // Skip audit on User creation to avoid loop with registration
            if ($model instanceof \App\Models\User) {
                return;
            }
            AuditLog::record('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = $model->getOriginal();
            $new = $model->getChanges();

            // Only log if there are meaningful changes (exclude updated_at)
            $meaningfulChanges = array_diff_key($new, ['updated_at' => true]);
            if (empty($meaningfulChanges)) {
                return;
            }

            $oldFiltered = array_intersect_key($old, $new);
            AuditLog::record('updated', $model, $oldFiltered, $new);
        });

        static::deleted(function ($model) {
            AuditLog::record('deleted', $model, $model->getAttributes(), null);
        });
    }
}

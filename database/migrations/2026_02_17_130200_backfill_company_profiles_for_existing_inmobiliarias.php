<?php

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $inmobiliarias = User::where('role', User::ROLE_INMOBILIARIA)->get();

        foreach ($inmobiliarias as $user) {
            if ($user->companyProfile) {
                continue;
            }

            CompanyProfile::create([
                'user_id' => $user->id,
                'company_name' => $user->name,
                'slug' => Str::slug($user->name).'-'.$user->id,
                'plan_tier' => CompanyProfile::PLAN_ENTERPRISE,
                'max_projects' => 999,
                'max_storage_bytes' => 107374182400, // 100GB
                'is_verified' => true,
                'show_in_directory' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Backfill is not reversible; company_profiles table drop handles cleanup
    }
};

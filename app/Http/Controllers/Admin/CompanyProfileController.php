<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends Controller
{
    /**
     * Superadmin: list all company profiles.
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user->isSuperadmin()) {
            abort(403);
        }

        $companies = CompanyProfile::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Edit company profile.
     * - Inmobiliaria: edits own profile
     * - Superadmin: edits any profile by ID
     */
    public function edit(Request $request, ?CompanyProfile $company = null)
    {
        $user = auth()->user();

        if ($user->isSuperadmin() && $company) {
            $profile = $company;
        } elseif ($user->isInmobiliaria()) {
            $profile = $user->companyProfile;
            if (!$profile) {
                return redirect()->route('onboarding.company');
            }
        } else {
            abort(403);
        }

        $isSuperadminEditing = $user->isSuperadmin() && $company;

        return view('admin.company-profile.edit', compact('profile', 'isSuperadminEditing'));
    }

    /**
     * Update company profile.
     * - Inmobiliaria: updates own profile
     * - Superadmin: updates any profile by ID
     */
    public function update(Request $request, ?CompanyProfile $company = null)
    {
        $user = $request->user();

        if ($user->isSuperadmin() && $company) {
            $profile = $company;
        } elseif ($user->isInmobiliaria()) {
            $profile = $user->companyProfile;
            if (!$profile) {
                return redirect()->route('onboarding.company');
            }
        } else {
            abort(403);
        }

        $rules = [
            'company_name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:2000',
            'description_en' => 'nullable|string|max:2000',
            'country' => 'nullable|string|size:2',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'show_in_directory' => 'boolean',
        ];

        // Superadmin can also toggle verified and change plan
        if ($user->isSuperadmin()) {
            $rules['is_verified'] = 'boolean';
            $rules['plan_tier'] = 'nullable|in:starter,professional,enterprise';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::delete($profile->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('company-logos');
        }
        unset($validated['logo']);

        $validated['show_in_directory'] = $request->boolean('show_in_directory');

        if ($user->isSuperadmin()) {
            $validated['is_verified'] = $request->boolean('is_verified');

            // If superadmin changed the plan tier, sync limits
            if (!empty($validated['plan_tier']) && $validated['plan_tier'] !== $profile->plan_tier) {
                $limits = CompanyProfile::PLAN_LIMITS[$validated['plan_tier']] ?? CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_STARTER];
                $validated['max_projects'] = $limits['max_projects'];
                $validated['max_storage_bytes'] = $limits['max_storage_bytes'];
            }
        }

        $profile->update($validated);

        if ($user->isSuperadmin() && $company) {
            return redirect()->route('admin.companies.index')
                ->with('success', __('billing.company_updated'));
        }

        return redirect()->route('admin.company-profile.edit')
            ->with('success', __('billing.company_updated'));
    }
}

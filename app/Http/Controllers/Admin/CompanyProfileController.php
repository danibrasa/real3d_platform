<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        if (!$user->isInmobiliaria()) {
            abort(403);
        }

        $profile = $user->companyProfile;
        if (!$profile) {
            return redirect()->route('onboarding.company');
        }

        return view('admin.company-profile.edit', compact('profile'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user->isInmobiliaria()) {
            abort(403);
        }

        $profile = $user->companyProfile;
        if (!$profile) {
            return redirect()->route('onboarding.company');
        }

        $validated = $request->validate([
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
        ]);

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::delete($profile->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('company-logos');
        }
        unset($validated['logo']);

        $validated['show_in_directory'] = $request->boolean('show_in_directory');

        $profile->update($validated);

        return redirect()->route('admin.company-profile.edit')
            ->with('success', __('billing.company_updated'));
    }
}

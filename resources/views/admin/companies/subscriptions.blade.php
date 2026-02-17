<x-app-layout>
    <x-slot name="title">{{ __('billing.subscriptions_overview') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('billing.subscriptions_overview') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Summary cards --}}
            @php
                $allCompanies = $companies->getCollection();
                $totalCompanies = \App\Models\CompanyProfile::count();
                $starterCount = \App\Models\CompanyProfile::where('plan_tier', 'starter')->count();
                $professionalCount = \App\Models\CompanyProfile::where('plan_tier', 'professional')->count();
                $enterpriseCount = \App\Models\CompanyProfile::where('plan_tier', 'enterprise')->count();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <div class="text-2xl font-bold text-gray-800">{{ $totalCompanies }}</div>
                    <div class="text-sm text-gray-500">{{ __('billing.total_companies') }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <div class="text-2xl font-bold text-gray-600">{{ $starterCount }}</div>
                    <div class="text-sm text-gray-500">Starter</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <div class="text-2xl font-bold text-emerald-600">{{ $professionalCount }}</div>
                    <div class="text-sm text-gray-500">Professional</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border p-4">
                    <div class="text-2xl font-bold text-purple-600">{{ $enterpriseCount }}</div>
                    <div class="text-sm text-gray-500">Enterprise</div>
                </div>
            </div>

            @if($companies->count())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.company_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.current_plan') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.projects_col') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.storage') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.change_plan') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($companies as $company)
                        @php
                            $owner = $company->user;
                            $projectCount = $owner ? $owner->assignedProjects()->count() : 0;
                            $usedMB = round($company->storage_used_bytes / 1048576);
                            $maxMB = round($company->max_storage_bytes / 1048576);
                            $usedLabel = $usedMB >= 1024 ? round($usedMB / 1024, 1) . ' GB' : $usedMB . ' MB';
                            $maxLabel = $maxMB >= 1024 ? round($maxMB / 1024, 1) . ' GB' : $maxMB . ' MB';
                            $pct = $company->storageUsedPercent();
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900">{{ $company->company_name }}</div>
                                <div class="text-xs text-gray-400">{{ $owner?->email ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <x-plan-badge :tier="$company->plan_tier" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-600">{{ $projectCount }} / {{ $company->max_projects >= 999 ? '∞' : $company->max_projects }}</div>
                                <div class="w-20 bg-gray-200 rounded-full h-1.5 mt-1">
                                    @php $projPct = $company->max_projects > 0 && $company->max_projects < 999 ? min(100, ($projectCount / $company->max_projects) * 100) : ($projectCount > 0 ? 10 : 0); @endphp
                                    <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $projPct }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-xs text-gray-600">{{ $usedLabel }} / {{ $maxLabel }}</div>
                                <div class="w-20 bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="h-1.5 rounded-full {{ $pct > 80 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.companies.update-plan', $company) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="plan_tier" class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 py-1">
                                        <option value="starter" {{ $company->plan_tier === 'starter' ? 'selected' : '' }}>Starter</option>
                                        <option value="professional" {{ $company->plan_tier === 'professional' ? 'selected' : '' }}>Professional</option>
                                        <option value="enterprise" {{ $company->plan_tier === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1 bg-emerald-600 text-white rounded-md text-xs hover:bg-emerald-700 transition">
                                        {{ __('billing.apply') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $companies->links() }}</div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500">{{ __('billing.no_companies') }}</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

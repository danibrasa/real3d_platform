<x-app-layout>
    <x-slot name="title">{{ __('billing.companies') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('billing.companies') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            @if($companies->count())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.company_name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.owner') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.plan') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.country') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.verified') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.storage') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('billing.created') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('billing.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($companies as $company)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($company->logo_path)
                                        <img src="{{ Storage::url($company->logo_path) }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                                    @else
                                        <div class="h-8 w-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <span class="text-xs font-bold text-gray-400">{{ strtoupper(substr($company->company_name, 0, 2)) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $company->company_name }}</div>
                                        @if($company->legal_name)
                                            <div class="text-xs text-gray-500">{{ $company->legal_name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $company->user?->name ?? '-' }}
                                <div class="text-xs text-gray-400">{{ $company->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <x-plan-badge :tier="$company->plan_tier" />
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $company->country ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @if($company->is_verified)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ __('billing.yes') }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">{{ __('billing.no') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $usedMB = round($company->storage_used_bytes / 1048576);
                                    $maxMB = round($company->max_storage_bytes / 1048576);
                                    $usedLabel = $usedMB >= 1024 ? round($usedMB / 1024, 1) . ' GB' : $usedMB . ' MB';
                                    $maxLabel = $maxMB >= 1024 ? round($maxMB / 1024, 1) . ' GB' : $maxMB . ' MB';
                                    $pct = $company->storageUsedPercent();
                                @endphp
                                <div class="text-xs text-gray-600">{{ $usedLabel }} / {{ $maxLabel }}</div>
                                <div class="w-24 bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="h-1.5 rounded-full transition-all {{ $pct > 80 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $company->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.companies.edit', $company) }}" class="text-sm text-blue-600 hover:underline">{{ __('billing.edit') }}</a>
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

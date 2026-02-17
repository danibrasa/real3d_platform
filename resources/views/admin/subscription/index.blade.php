<x-app-layout>
    <x-slot name="title">{{ __('billing.subscription') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('billing.subscription') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Current Plan Card --}}
            @if($profile)
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('billing.current_plan') }}</h3>
                        <div class="mt-1 flex items-center gap-2">
                            <x-plan-badge :tier="$currentPlan" />
                            @if($subscription?->onTrial())
                                <span class="text-xs text-amber-600 font-medium">{{ __('billing.trial_ends', ['date' => $subscription->trial_ends_at->format('M d, Y')]) }}</span>
                            @endif
                        </div>
                    </div>
                    @if($user->hasStripeId())
                        <form method="POST" action="{{ route('admin.subscription.portal') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm transition">
                                {{ __('billing.manage_billing') }}
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Usage bars --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>{{ __('billing.projects_used', ['used' => $user->assignedProjects()->count(), 'max' => $profile->max_projects]) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php $projectPercent = $profile->max_projects > 0 ? min(100, ($user->assignedProjects()->count() / $profile->max_projects) * 100) : 0; @endphp
                            <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $projectPercent }}%"></div>
                        </div>
                    </div>
                    <div>
                        @php
                            $storageUsedMB = round($profile->storage_used_bytes / 1048576);
                            $storageMaxMB = round($profile->max_storage_bytes / 1048576);
                            $storageUsedLabel = $storageUsedMB >= 1024 ? round($storageUsedMB / 1024, 1) . ' GB' : $storageUsedMB . ' MB';
                            $storageMaxLabel = $storageMaxMB >= 1024 ? round($storageMaxMB / 1024, 1) . ' GB' : $storageMaxMB . ' MB';
                        @endphp
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>{{ __('billing.storage_used', ['used' => $storageUsedLabel, 'max' => $storageMaxLabel]) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: {{ $profile->storageUsedPercent() }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Plan Cards --}}
            <div x-data="{ interval: 'monthly' }" class="mb-6">
                <div class="flex justify-center mb-6">
                    <div class="bg-gray-100 rounded-full p-1 flex">
                        <button @click="interval = 'monthly'" :class="interval === 'monthly' ? 'bg-white shadow text-gray-800' : 'text-gray-500'"
                                class="px-4 py-1.5 rounded-full text-sm font-medium transition">{{ __('billing.monthly') }}</button>
                        <button @click="interval = 'yearly'" :class="interval === 'yearly' ? 'bg-white shadow text-gray-800' : 'text-gray-500'"
                                class="px-4 py-1.5 rounded-full text-sm font-medium transition">{{ __('billing.yearly') }}</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($plans as $tier => $plan)
                        @php $isCurrent = $currentPlan === $tier; @endphp
                        <div class="bg-white rounded-xl shadow-sm border {{ $tier === 'professional' ? 'border-emerald-400 ring-2 ring-emerald-100' : 'border-gray-200' }} p-6 relative">
                            @if($tier === 'professional')
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                    <span class="bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-full">{{ __('billing.most_popular') }}</span>
                                </div>
                            @endif
                            <h3 class="text-lg font-bold text-gray-800">{{ $plan['name'] }}</h3>
                            <div class="mt-2">
                                <span x-show="interval === 'monthly'" class="text-3xl font-bold text-gray-900">${{ $plan['price_monthly'] }}</span>
                                <span x-show="interval === 'yearly'" class="text-3xl font-bold text-gray-900">${{ $plan['price_yearly'] }}</span>
                                <span x-show="interval === 'monthly'" class="text-gray-500 text-sm">{{ __('billing.per_month') }}</span>
                                <span x-show="interval === 'yearly'" class="text-gray-500 text-sm">{{ __('billing.per_year') }}</span>
                            </div>

                            <ul class="mt-4 space-y-2 text-sm text-gray-600">
                                @php $limits = \App\Models\CompanyProfile::PLAN_LIMITS[$tier]; @endphp
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ trans_choice('billing.features.projects', $limits['max_projects'], ['count' => $limits['max_projects'] >= 999 ? 'Unlimited' : $limits['max_projects']]) }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ __('billing.features.storage', ['size' => $limits['max_storage_bytes'] >= 1073741824 ? round($limits['max_storage_bytes'] / 1073741824) . 'GB' : round($limits['max_storage_bytes'] / 1048576) . 'MB']) }}
                                </li>
                                @if($limits['chatbot'])
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ __('billing.features.chatbot') }}
                                    </li>
                                @endif
                                @if($limits['analytics'])
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ __('billing.features.analytics') }}
                                    </li>
                                @endif
                                @if($limits['api_access'])
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ __('billing.features.api_access') }}
                                    </li>
                                @endif
                                @if($limits['embed_widget'])
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ __('billing.features.embed_widget') }}
                                    </li>
                                @endif
                            </ul>

                            <div class="mt-6">
                                @if($isCurrent)
                                    <span class="block text-center py-2 px-4 bg-gray-100 text-gray-500 rounded-lg text-sm font-medium">{{ __('billing.current') }}</span>
                                @else
                                    <form method="POST" action="{{ route('admin.subscription.checkout') }}">
                                        @csrf
                                        <input type="hidden" name="plan" value="{{ $tier }}">
                                        <input type="hidden" name="interval" x-bind:value="interval">
                                        <button type="submit" class="w-full py-2 px-4 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition">
                                            {{ __('billing.get_started') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Invoices --}}
            @if(!empty($invoices) && count($invoices) > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('billing.invoices') }}</h3>
                    <div class="space-y-2">
                        @foreach($invoices as $invoice)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                                <div>
                                    <span class="text-sm text-gray-800">{{ $invoice->date()->toFormattedDateString() }}</span>
                                    <span class="text-sm text-gray-500 ml-2">{{ $invoice->total() }}</span>
                                </div>
                                <a href="{{ $invoice->invoicePdf() }}" target="_blank" class="text-sm text-emerald-600 hover:underline">PDF</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

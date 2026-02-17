<x-guest-layout>
    <div class="text-center mb-6">
        {{-- Step indicator --}}
        <div class="flex items-center justify-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">1</div>
            <div class="w-8 h-1 bg-emerald-500 rounded"></div>
            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">2</div>
            <div class="w-8 h-1 bg-emerald-500 rounded"></div>
            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">3</div>
        </div>
        <h2 class="text-xl font-bold text-gray-800">{{ app()->getLocale() === 'en' ? 'Choose your plan' : 'Elige tu plan' }}</h2>
    </div>

    <div x-data="{ interval: 'monthly' }" class="space-y-4">
        {{-- Interval toggle --}}
        <div class="flex justify-center">
            <div class="bg-gray-100 rounded-full p-1 flex text-sm">
                <button @click="interval = 'monthly'" :class="interval === 'monthly' ? 'bg-white shadow text-gray-800' : 'text-gray-500'"
                        class="px-3 py-1 rounded-full font-medium transition">{{ __('billing.monthly') }}</button>
                <button @click="interval = 'yearly'" :class="interval === 'yearly' ? 'bg-white shadow text-gray-800' : 'text-gray-500'"
                        class="px-3 py-1 rounded-full font-medium transition">{{ __('billing.yearly') }}</button>
            </div>
        </div>

        @foreach($plans as $tier => $plan)
            @php $limits = \App\Models\CompanyProfile::PLAN_LIMITS[$tier]; @endphp
            <div class="border rounded-lg p-4 {{ $tier === 'professional' ? 'border-emerald-400 bg-emerald-50/30' : 'border-gray-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-gray-800">{{ $plan['name'] }}</span>
                        @if($tier === 'professional')
                            <span class="ml-1 text-xs bg-emerald-500 text-white px-2 py-0.5 rounded-full">{{ __('billing.most_popular') }}</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <span x-show="interval === 'monthly'" class="text-xl font-bold">${{ $plan['price_monthly'] }}</span>
                        <span x-show="interval === 'yearly'" class="text-xl font-bold">${{ $plan['price_yearly'] }}</span>
                        <span x-show="interval === 'monthly'" class="text-gray-400 text-xs">{{ __('billing.per_month') }}</span>
                        <span x-show="interval === 'yearly'" class="text-gray-400 text-xs">{{ __('billing.per_year') }}</span>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500 flex flex-wrap gap-x-3 gap-y-1">
                    <span>{{ $limits['max_projects'] >= 999 ? 'Unlimited' : $limits['max_projects'] }} {{ app()->getLocale() === 'en' ? 'projects' : 'proyectos' }}</span>
                    <span>{{ $limits['max_storage_bytes'] >= 1073741824 ? round($limits['max_storage_bytes'] / 1073741824) . 'GB' : round($limits['max_storage_bytes'] / 1048576) . 'MB' }}</span>
                    @if($limits['chatbot'])<span>{{ __('billing.features.chatbot') }}</span>@endif
                    @if($limits['analytics'])<span>{{ __('billing.features.analytics') }}</span>@endif
                    @if($limits['api_access'])<span>API + Webhooks</span>@endif
                </div>
                <div class="mt-3">
                    <form method="POST" action="{{ route('onboarding.plan.select') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $tier }}">
                        <input type="hidden" name="interval" x-bind:value="interval">
                        <button type="submit" class="w-full py-2 text-sm font-medium rounded-md transition
                            {{ $tier === 'professional' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ __('billing.get_started') }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-guest-layout>

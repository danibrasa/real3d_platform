@php $plans = config('stripe.plans'); @endphp

<section id="pricing" class="relative py-24 lg:py-32 border-t border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold mb-4">{{ app()->getLocale() === 'en' ? 'Simple, transparent pricing' : 'Precios simples y transparentes' }}</h2>
            <p class="text-slate-400 max-w-2xl mx-auto">{{ app()->getLocale() === 'en' ? 'Choose the plan that fits your business. Start with a 14-day free trial.' : 'Elige el plan que se ajuste a tu negocio. Comienza con 14 dias de prueba gratis.' }}</p>
        </div>

        <div x-data="{ interval: 'monthly' }" class="space-y-8">
            <div class="flex justify-center">
                <div class="bg-white/5 rounded-full p-1 flex border border-white/10">
                    <button @click="interval = 'monthly'" :class="interval === 'monthly' ? 'bg-white/10 text-white' : 'text-slate-400'"
                            class="px-5 py-2 rounded-full text-sm font-medium transition">{{ __('billing.monthly') }}</button>
                    <button @click="interval = 'yearly'" :class="interval === 'yearly' ? 'bg-white/10 text-white' : 'text-slate-400'"
                            class="px-5 py-2 rounded-full text-sm font-medium transition">{{ __('billing.yearly') }}</button>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach($plans as $tier => $plan)
                    @php $limits = \App\Models\CompanyProfile::PLAN_LIMITS[$tier]; @endphp
                    <div class="glass glass-hover rounded-2xl p-6 relative transition-all duration-300 hover:-translate-y-1 {{ $tier === 'professional' ? 'ring-2 ring-cyan-500/30' : '' }}">
                        @if($tier === 'professional')
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span class="bg-gradient-to-r from-cyan-500 to-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full">{{ __('billing.most_popular') }}</span>
                            </div>
                        @endif

                        <h3 class="text-xl font-bold mb-2">{{ $plan['name'] }}</h3>
                        <div class="mb-4">
                            <span x-show="interval === 'monthly'" class="text-4xl font-bold">${{ $plan['price_monthly'] }}</span>
                            <span x-show="interval === 'yearly'" class="text-4xl font-bold">${{ $plan['price_yearly'] }}</span>
                            <span x-show="interval === 'monthly'" class="text-slate-400 text-sm">{{ __('billing.per_month') }}</span>
                            <span x-show="interval === 'yearly'" class="text-slate-400 text-sm">{{ __('billing.per_year') }}</span>
                        </div>

                        <ul class="space-y-3 mb-6 text-sm">
                            <li class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ $limits['max_projects'] >= 999 ? (app()->getLocale() === 'en' ? 'Unlimited projects' : 'Proyectos ilimitados') : $limits['max_projects'] . ' ' . (app()->getLocale() === 'en' ? 'project(s)' : 'proyecto(s)') }}
                            </li>
                            <li class="flex items-center gap-2 text-slate-300">
                                <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                {{ round($limits['max_storage_bytes'] / 1073741824) }}GB {{ app()->getLocale() === 'en' ? 'storage' : 'almacenamiento' }}
                            </li>
                            @if($limits['chatbot'])
                                <li class="flex items-center gap-2 text-slate-300">
                                    <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ __('billing.features.chatbot') }}
                                </li>
                            @endif
                            @if($limits['analytics'])
                                <li class="flex items-center gap-2 text-slate-300">
                                    <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ __('billing.features.analytics') }}
                                </li>
                            @endif
                            @if($limits['api_access'])
                                <li class="flex items-center gap-2 text-slate-300">
                                    <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ __('billing.features.api_access') }}
                                </li>
                            @endif
                            @if($limits['embed_widget'])
                                <li class="flex items-center gap-2 text-slate-300">
                                    <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    {{ __('billing.features.embed_widget') }}
                                </li>
                            @endif
                        </ul>

                        <a href="{{ route('register.business') }}" class="block text-center py-2.5 px-4 rounded-xl text-sm font-semibold transition
                            {{ $tier === 'professional' ? 'btn-glow text-white' : 'btn-outline-cyan' }}">
                            {{ __('billing.get_started') }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

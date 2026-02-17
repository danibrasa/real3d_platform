@props(['project', 'priceMin' => null])

@php
    use App\Models\PaymentMilestone;

    $plans = $project->relationLoaded('paymentPlans')
        ? $project->paymentPlans->sortBy('sort_order')->values()
        : $project->paymentPlans()->with('milestones')->orderBy('sort_order')->get();
    if ($plans->isEmpty()) return;
    $defaultPlan = $plans->firstWhere('is_default', true) ?? $plans->first();

    // Color map for CSS classes
    $colorMap = [
        'blue'  => ['border' => '#2563eb', 'bg' => '#eff6ff', 'text' => '#1e40af', 'bar' => '#2563eb', 'light' => '#dbeafe'],
        'amber' => ['border' => '#d97706', 'bg' => '#fffbeb', 'text' => '#92400e', 'bar' => '#d97706', 'light' => '#fef3c7'],
        'green' => ['border' => '#059669', 'bg' => '#ecfdf5', 'text' => '#065f46', 'bar' => '#059669', 'light' => '#d1fae5'],
        'gray'  => ['border' => '#6b7280', 'bg' => '#f9fafb', 'text' => '#374151', 'bar' => '#6b7280', 'light' => '#f3f4f6'],
    ];

    // Icon SVG map
    $iconSvg = [
        'key'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>',
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'building' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'home'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
        'circle'   => '<circle cx="12" cy="12" r="4" stroke-width="2"/>',
    ];

    // Pre-compute milestone data for each plan
    $plansData = [];
    foreach ($plans as $plan) {
        $milestones = $plan->milestones->sortBy('sort_order')->values();
        $total = $milestones->count();
        $cumPct = 0;
        $msData = [];
        foreach ($milestones as $i => $ms) {
            $color = $ms->phaseColor($i, $total);
            $cumPct += $ms->percentage;
            $iconKey = PaymentMilestone::TYPE_ICONS[$ms->milestone_type ?? 'other'] ?? 'circle';

            // Compute estimated date if project has estimated_delivery
            $computedDate = null;
            if ($project->estimated_delivery && $cumPct > 0) {
                $totalMonths = now()->diffInMonths($project->estimated_delivery);
                $computedDate = now()->addMonths(round($totalMonths * $cumPct / 100));
            }

            $msData[] = [
                'name' => $ms->name,
                'pct' => $ms->percentage,
                'cumPct' => $cumPct,
                'description' => $ms->description,
                'due_description' => $ms->due_description,
                'color' => $color,
                'colors' => $colorMap[$color] ?? $colorMap['gray'],
                'iconSvg' => $iconSvg[$iconKey] ?? $iconSvg['circle'],
                'computedDate' => $computedDate,
                'type' => $ms->milestone_type ?? 'other',
            ];
        }
        $plansData[$plan->id] = $msData;
    }

    // Check if any units are available (for download button)
    $hasAvailableUnits = $project->units->where('status', 'available')->count() > 0;
    $firstAvailableUnit = $project->units->where('status', 'available')->first();
@endphp

@if($plans->isNotEmpty())
<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .milestone-card { opacity: 0; }
    .milestone-card.animate-in { animation: fadeInUp 0.5s ease-out forwards; }
    .flow-connector { position: relative; }
    .flow-connector::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -1.5rem;
        width: 1.5rem;
        height: 2px;
        background: #d1d5db;
    }
    .flow-connector:last-child::after { display: none; }
    .flow-arrow {
        position: absolute;
        top: 50%;
        right: -1.5rem;
        transform: translateY(-50%);
        color: #d1d5db;
    }
    .flow-connector:last-child .flow-arrow { display: none; }
    .fade-edge-left { mask-image: linear-gradient(to right, transparent, black 2rem); -webkit-mask-image: linear-gradient(to right, transparent, black 2rem); }
    .fade-edge-right { mask-image: linear-gradient(to left, transparent, black 2rem); -webkit-mask-image: linear-gradient(to left, transparent, black 2rem); }
</style>

<section id="planes-de-pago"
         x-data="paymentTimeline()"
         @unit-selected-price.window="unitPrice = $event.detail.price"
         class="scroll-mt-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ __('landing.payment_flow') }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ __('landing.payment_flow_subtitle') }}</p>
        </div>
        @if($hasAvailableUnits && $firstAvailableUnit)
        <a :href="'{{ route('viewer.payment-schedule.pdf', [$project->slug, $firstAvailableUnit->id]) }}' + '?plan=' + activePlan"
           class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100 transition"
           target="_blank">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('landing.download_payment_schedule') }}
        </a>
        @endif
    </div>

    {{-- Plan tabs (if multiple) --}}
    @if($plans->count() > 1)
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($plans as $plan)
        <button @click="selectPlan({{ $plan->id }})"
                :class="activePlan === {{ $plan->id }} ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 hover:bg-gray-50'"
                class="px-5 py-2.5 rounded-full text-sm font-medium border border-gray-200 transition">
            {{ $plan->name }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- Plan content --}}
    @foreach($plans as $plan)
    @php $milestones = $plansData[$plan->id] ?? []; @endphp
    <div x-show="activePlan === {{ $plan->id }}" x-transition.opacity
         class="bg-white rounded-xl shadow-sm overflow-hidden"
         style="{{ $plan->id !== $defaultPlan->id ? 'display:none' : '' }}">

        {{-- Progress bar --}}
        <div class="px-6 pt-6 md:px-8 md:pt-8">
            <div class="flex h-4 rounded-full overflow-hidden bg-gray-100">
                @foreach($milestones as $i => $ms)
                <div class="flex items-center justify-center text-[9px] font-bold text-white transition-all"
                     style="width: {{ $ms['pct'] }}%; background-color: {{ $ms['colors']['bar'] }};"
                     title="{{ $ms['name'] }}: {{ number_format($ms['pct'], 0) }}%">
                    @if($ms['pct'] > 8){{ number_format($ms['pct'], 0) }}%@endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Desktop Flowchart (md+) --}}
        <div class="hidden md:block px-6 py-8 md:px-8">
            <div class="overflow-x-auto pb-2 -mx-2 px-2">
                <div class="flex gap-6 min-w-max">
                    @foreach($milestones as $i => $ms)
                    <div class="flow-connector milestone-card" data-animate>
                        <div class="w-56 bg-white border rounded-xl shadow-sm overflow-hidden hover:shadow-md transition"
                             style="border-top: 4px solid {{ $ms['colors']['border'] }};">
                            <div class="p-4">
                                {{-- Icon + Name --}}
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                                         style="background-color: {{ $ms['colors']['light'] }};">
                                        <svg class="w-5 h-5" fill="none" stroke="{{ $ms['colors']['border'] }}" viewBox="0 0 24 24">{!! $ms['iconSvg'] !!}</svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm leading-tight">{{ $ms['name'] }}</h4>
                                        @if($ms['due_description'])
                                        <p class="text-xs text-gray-400">{{ $ms['due_description'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- Percentage --}}
                                <div class="text-2xl font-bold mb-1" style="color: {{ $ms['colors']['text'] }};">
                                    {{ number_format($ms['pct'], 0) }}%
                                </div>

                                {{-- Amount (dynamic) --}}
                                <div x-show="unitPrice > 0" class="text-sm font-medium text-gray-700" x-text="fmt(milestoneAmt({{ $ms['pct'] }}))"></div>

                                {{-- Cumulative --}}
                                <div class="mt-2 pt-2 border-t border-gray-100">
                                    <div class="text-xs text-gray-400">{{ __('landing.cumulative') }}: {{ number_format($ms['cumPct'], 0) }}%</div>
                                    <div x-show="unitPrice > 0" class="text-xs font-medium text-gray-500" x-text="fmt(cumulativeAmt({{ json_encode(array_column($milestones, 'pct')) }}, {{ $i }}))"></div>
                                </div>

                                {{-- Estimated date --}}
                                @if($ms['computedDate'])
                                <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs"
                                     style="background-color: {{ $ms['colors']['light'] }}; color: {{ $ms['colors']['text'] }};">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    ~{{ $ms['computedDate']->translatedFormat('M Y') }}
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Arrow connector --}}
                        @if(!$loop->last)
                        <div class="flow-arrow">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Mobile Cards (<md) --}}
        <div class="md:hidden px-4 py-6 space-y-3">
            @foreach($milestones as $i => $ms)
            <div class="milestone-card" data-animate x-data="{ open: false }">
                <div class="rounded-lg overflow-hidden border shadow-sm cursor-pointer"
                     style="border-left: 4px solid {{ $ms['colors']['border'] }};"
                     @click="open = !open">
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0"
                                     style="background-color: {{ $ms['colors']['light'] }};">
                                    <svg class="w-4 h-4" fill="none" stroke="{{ $ms['colors']['border'] }}" viewBox="0 0 24 24">{!! $ms['iconSvg'] !!}</svg>
                                </div>
                                <span class="font-semibold text-gray-800 text-sm">{{ $ms['name'] }}</span>
                            </div>
                            <span class="text-lg font-bold" style="color: {{ $ms['colors']['text'] }};">{{ number_format($ms['pct'], 0) }}%</span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                            @if($ms['computedDate'])
                            <span>~{{ $ms['computedDate']->translatedFormat('M Y') }}</span>
                            @endif
                            @if($ms['due_description'])
                            <span>{{ $ms['due_description'] }}</span>
                            @endif
                        </div>

                        <div x-show="unitPrice > 0" class="mt-2 text-sm">
                            <span class="font-medium text-gray-700" x-text="fmt(milestoneAmt({{ $ms['pct'] }}))"></span>
                            <span class="text-gray-400 text-xs">({{ __('landing.cumulative') }}: <span x-text="fmt(cumulativeAmt({{ json_encode(array_column($milestones, 'pct')) }}, {{ $i }}))"></span> / {{ number_format($ms['cumPct'], 0) }}%)</span>
                        </div>
                    </div>

                    {{-- Expandable description --}}
                    @if($ms['description'])
                    <div x-show="open" x-transition x-cloak class="px-4 pb-4 border-t border-gray-100">
                        <p class="text-sm text-gray-600 pt-3">{{ $ms['description'] }}</p>
                    </div>
                    @endif
                </div>

                {{-- Connector line --}}
                @if(!$loop->last)
                <div class="flex justify-center py-1">
                    <div class="w-0.5 h-3" style="background-color: {{ $ms['colors']['border'] }};"></div>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Simulator + Summary Table --}}
        <div class="px-6 pb-6 md:px-8 md:pb-8 border-t border-gray-100">
            <div class="pt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Price input --}}
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-2 block">{{ __('landing.simulate_with_price') }}:</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm" x-text="currencySymbol()"></span>
                        <input type="number" x-model.number="unitPrice" min="0" step="1000"
                               class="pl-14 pr-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-full max-w-xs"
                               placeholder="150000">
                    </div>
                    <p x-show="unitPrice <= 0" class="text-xs text-gray-400 mt-2">{{ __('landing.select_unit_to_simulate') }}</p>
                </div>

                {{-- Summary table --}}
                <div x-show="unitPrice > 0" x-transition>
                    <div class="text-sm font-medium text-gray-700 mb-2">{{ __('landing.payment_summary') }}:</div>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('landing.milestone') }}</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">%</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('landing.amount') }}</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('landing.cumulative') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($milestones as $i => $ms)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 font-medium text-gray-700">
                                        <span class="inline-block w-2 h-2 rounded-full mr-1.5" style="background-color: {{ $ms['colors']['bar'] }};"></span>
                                        {{ $ms['name'] }}
                                    </td>
                                    <td class="px-3 py-2 text-center text-gray-600">{{ number_format($ms['pct'], 0) }}%</td>
                                    <td class="px-3 py-2 text-right text-gray-700" x-text="fmt(milestoneAmt({{ $ms['pct'] }}))"></td>
                                    <td class="px-3 py-2 text-right text-gray-500 text-xs">
                                        <span x-text="fmt(cumulativeAmt({{ json_encode(array_column($milestones, 'pct')) }}, {{ $i }}))"></span>
                                        <span class="text-gray-400">({{ number_format($ms['cumPct'], 0) }}%)</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-blue-50 font-bold">
                                    <td class="px-3 py-2 text-gray-800">{{ __('landing.total') }}</td>
                                    <td class="px-3 py-2 text-center text-gray-800">{{ number_format(array_sum(array_column($milestones, 'pct')), 0) }}%</td>
                                    <td class="px-3 py-2 text-right text-blue-700" x-text="fmt(unitPrice)"></td>
                                    <td class="px-3 py-2 text-right"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</section>

<script>
    function paymentTimeline() {
        return {
            activePlan: {{ $defaultPlan->id }},
            unitPrice: {{ $priceMin ?? 0 }},

            selectPlan(id) {
                this.activePlan = id;
            },

            currencySymbol() {
                return window.__currency ? window.__currency.symbol : 'USD';
            },

            fmt(usdAmount) {
                return window.formatPrice ? window.formatPrice(usdAmount) : 'USD ' + Math.round(usdAmount).toLocaleString('en-US');
            },

            milestoneAmt(pct) {
                return this.unitPrice > 0 ? Math.round(this.unitPrice * pct / 100) : 0;
            },

            cumulativeAt(milestones, idx) {
                let sum = 0;
                for (let i = 0; i <= idx; i++) sum += milestones[i];
                return sum;
            },

            cumulativeAmt(milestones, idx) {
                return this.unitPrice > 0 ? Math.round(this.unitPrice * this.cumulativeAt(milestones, idx) / 100) : 0;
            },
        };
    }

    // Scroll animation with IntersectionObserver
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.milestone-card[data-animate]');
        if ('IntersectionObserver' in window && cards.length) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry, index) {
                    if (entry.isIntersecting) {
                        // Stagger the animation
                        const el = entry.target;
                        const delay = Array.from(cards).indexOf(el) * 100;
                        setTimeout(function() {
                            el.classList.add('animate-in');
                        }, delay);
                        observer.unobserve(el);
                    }
                });
            }, { threshold: 0.1 });

            cards.forEach(function(card) {
                observer.observe(card);
            });
        } else {
            // Fallback: show all
            cards.forEach(function(card) { card.classList.add('animate-in'); });
        }
    });
</script>
@endif

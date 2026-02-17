@props(['project', 'priceMin' => null])

@php
    $plans = $project->relationLoaded('paymentPlans')
        ? $project->paymentPlans->sortBy('sort_order')->values()
        : $project->paymentPlans()->with('milestones')->orderBy('sort_order')->get();
    if ($plans->isEmpty()) return;
    $defaultPlan = $plans->firstWhere('is_default', true) ?? $plans->first();
@endphp

@if($plans->isNotEmpty())
<section x-data="paymentTimeline()" class="scroll-mt-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Planes de pago</h2>

    {{-- Plan selector (if multiple plans) --}}
    @if($plans->count() > 1)
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($plans as $plan)
        <button @click="selectPlan({{ $plan->id }})"
                :class="activePlan === {{ $plan->id }} ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                class="px-4 py-2 rounded-full text-sm font-medium border border-gray-200 transition">
            {{ $plan->name }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- Plan content --}}
    @foreach($plans as $plan)
    <div x-show="activePlan === {{ $plan->id }}" x-transition.opacity class="bg-white rounded-xl shadow-sm p-6 md:p-8" style="{{ $plan->id !== $defaultPlan->id ? 'display:none' : '' }}">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $plan->name }}</h3>

        {{-- Progress bar --}}
        <div class="flex h-4 rounded-full overflow-hidden bg-gray-100 mb-8">
            @php $colorPalette = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2']; @endphp
            @foreach($plan->milestones as $i => $ms)
            <div class="flex items-center justify-center text-[9px] font-bold text-white"
                 style="width: {{ $ms->percentage }}%; background-color: {{ $colorPalette[$i % count($colorPalette)] }};"
                 title="{{ $ms->name }}: {{ number_format($ms->percentage, 0) }}%">
                @if($ms->percentage > 8){{ number_format($ms->percentage, 0) }}%@endif
            </div>
            @endforeach
        </div>

        {{-- Timeline --}}
        <div class="relative">
            {{-- Vertical line --}}
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 hidden md:block"></div>

            <div class="space-y-6">
                @foreach($plan->milestones as $i => $ms)
                <div class="relative flex items-start gap-4 md:pl-12"
                     x-data="{ open: false }">
                    {{-- Dot on timeline --}}
                    <div class="hidden md:flex absolute left-0 w-9 h-9 rounded-full items-center justify-center text-white text-sm font-bold shadow-md"
                         style="background-color: {{ $colorPalette[$i % count($colorPalette)] }};">
                        {{ $i + 1 }}
                    </div>
                    {{-- Mobile dot --}}
                    <div class="md:hidden flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                         style="background-color: {{ $colorPalette[$i % count($colorPalette)] }};">
                        {{ $i + 1 }}
                    </div>

                    <div class="flex-1 bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition cursor-pointer" @click="open = !open">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-gray-800">{{ $ms->name }}</span>
                                @if($ms->due_description)
                                <span class="text-sm text-gray-500 ml-2">- {{ $ms->due_description }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-bold" style="color: {{ $colorPalette[$i % count($colorPalette)] }};">{{ number_format($ms->percentage, 0) }}%</span>
                                @if($ms->description)
                                <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                @endif
                            </div>
                        </div>
                        @if($ms->description)
                        <div x-show="open" x-transition class="mt-2 text-sm text-gray-600">
                            {{ $ms->description }}
                        </div>
                        @endif

                        {{-- Amount estimate --}}
                        <div x-show="unitPrice > 0" class="mt-2 text-sm text-gray-500">
                            Estimado: <span class="font-medium text-gray-700" x-text="'USD ' + Math.round(unitPrice * {{ $ms->percentage }} / 100).toLocaleString('en-US')"></span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Price input for estimates --}}
        <div class="mt-6 pt-4 border-t border-gray-100">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <label class="text-sm text-gray-600 whitespace-nowrap">Simular con precio de unidad:</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">USD</span>
                    <input type="number" x-model.number="unitPrice" min="0" step="1000"
                           class="pl-12 pr-4 py-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-48"
                           placeholder="Ej: 150000">
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
            }
        };
    }
</script>
@endif

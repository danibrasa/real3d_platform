@props(['project'])

@php
    $phases = $project->constructionPhases ?? collect();
    $latestUpdate = $project->constructionUpdates?->first();
    $overallProgress = $latestUpdate?->progress_percentage ?? 0;
@endphp

@if($phases->isNotEmpty())
<section>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ __('landing.construction_progress') }}</h2>

    {{-- Overall progress bar --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-medium text-gray-600">{{ __('landing.overall_progress') }}</span>
            <span class="text-3xl font-bold text-blue-600">{{ $overallProgress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-1000" style="width: {{ $overallProgress }}%"></div>
        </div>
        @if($latestUpdate)
        <p class="text-xs text-gray-400 mt-2">
            {{ __('landing.last_update') }}: {{ $latestUpdate->date->format('d/m/Y') }} &mdash; {{ $latestUpdate->title }}
        </p>
        @endif
    </div>

    {{-- Phases timeline --}}
    <div class="relative" x-data="{ expandedPhase: null }">
        {{-- Timeline line --}}
        <div class="hidden md:block absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>

        <div class="space-y-4">
            @foreach($phases as $phase)
            @php
                $phaseUpdates = $project->constructionUpdates?->where('construction_phase_id', $phase->id) ?? collect();
                $latestPhaseUpdate = $phaseUpdates->first();
            @endphp
            <div class="relative md:pl-16" x-data="{ open: false }">
                {{-- Timeline dot --}}
                <div class="hidden md:flex absolute left-3.5 top-4 w-5 h-5 rounded-full items-center justify-center z-10
                    {{ $phase->status === 'completed' ? 'bg-green-500' : ($phase->status === 'in_progress' ? 'bg-blue-500 animate-pulse' : 'bg-gray-300') }}">
                    @if($phase->status === 'completed')
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4 cursor-pointer hover:shadow-md transition"
                     @click="open = !open">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            {{-- Mobile dot --}}
                            <div class="md:hidden w-3 h-3 rounded-full flex-shrink-0
                                {{ $phase->status === 'completed' ? 'bg-green-500' : ($phase->status === 'in_progress' ? 'bg-blue-500' : 'bg-gray-300') }}">
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $phase->name }}</h3>
                                @if($phase->description)
                                <p class="text-xs text-gray-500">{{ $phase->description }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $phase->status === 'completed' ? 'bg-green-100 text-green-700' : ($phase->status === 'in_progress' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500') }}">
                                {{ $phase->status === 'completed' ? __('landing.phase_completed') : ($phase->status === 'in_progress' ? __('landing.phase_in_progress') : __('landing.phase_pending')) }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    {{-- Expanded content --}}
                    <div x-show="open" x-transition class="mt-4 pt-4 border-t border-gray-100" style="display: none;" @click.stop>
                        @if($phaseUpdates->isEmpty())
                            <p class="text-sm text-gray-400">{{ __('landing.no_updates_yet') }}</p>
                        @else
                            <div class="space-y-4">
                                @foreach($phaseUpdates->take(3) as $update)
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-medium text-gray-500">{{ $update->date->format('d/m/Y') }}</span>
                                        <span class="text-xs text-blue-600 font-medium">{{ $update->progress_percentage }}%</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700">{{ $update->title }}</p>
                                    @if($update->description)
                                    <p class="text-sm text-gray-500 mt-1">{{ $update->description }}</p>
                                    @endif
                                    @if($update->images->count())
                                    <div class="flex gap-2 mt-2 overflow-x-auto">
                                        @foreach($update->images as $img)
                                        <img src="/api/projects/{{ $project->id }}/construction/{{ $img->id }}"
                                             alt="{{ $img->caption ?? '' }}"
                                             class="w-28 h-20 object-cover rounded-lg flex-shrink-0 cursor-pointer hover:opacity-80 transition"
                                             onclick="document.getElementById('lightbox-img').src=this.src; document.getElementById('lightbox').classList.remove('hidden');"
                                             loading="lazy">
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

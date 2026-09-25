@props(['project'])

@php
    $thumbnail = $project->files->where('file_type', 'thumbnail')->first();
    $has3d = $project->files->where('file_type', 'model_3d')->first();
    $has360 = $project->files->where('file_type', 'video_360')->first()
        ?? $project->files->where('file_type', 'image_360')->first();
    $availableUnits = $project->units->where('status', 'available');
    $minPrice = $availableUnits->min('price');
    $maxPrice = $availableUnits->max('price');
    $bedroomRange = $availableUnits->pluck('bedrooms')->unique()->sort()->values();
    $company = $project->assignedAgencies->first()?->companyProfile ?? null;
@endphp

<a href="{{ route('viewer.landing', $project->slug) }}"
   class="group block bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5">

    {{-- Thumbnail --}}
    <div class="h-44 relative overflow-hidden bg-gray-100">
        @if($thumbnail)
            <img src="{{ url('api/projects/' . $project->id . '/files/thumbnail') }}"
                 alt="{{ $project->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                 loading="lazy">
        @else
            <div class="w-full h-full bg-gradient-to-br from-cyan-500/80 to-blue-600/80 flex items-center justify-center">
                <svg class="w-12 h-12 text-white/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 7.5h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
            </div>
        @endif

        {{-- Availability badge --}}
        <div class="absolute top-3 left-3 flex gap-1.5">
            @if($project->available_units_count > 0)
                <span class="bg-emerald-500/90 text-white text-xs font-medium px-2 py-0.5 rounded-md backdrop-blur-sm">
                    {{ $project->available_units_count }} {{ __('portal.available') }}
                </span>
            @else
                <span class="bg-gray-500/90 text-white text-xs font-medium px-2 py-0.5 rounded-md backdrop-blur-sm">
                    {{ __('portal.sold_out') }}
                </span>
            @endif
        </div>

        {{-- Media badges --}}
        <div class="absolute top-3 right-3 flex gap-1">
            @if($has3d)
                <span class="bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded backdrop-blur-sm font-bold tracking-wide">3D</span>
            @endif
            @if($has360)
                <span class="bg-black/50 text-white text-[10px] px-1.5 py-0.5 rounded backdrop-blur-sm font-bold tracking-wide">360</span>
            @endif
        </div>
    </div>

    {{-- Info --}}
    <div class="p-4">
        <h3 class="font-semibold text-gray-900 group-hover:text-cyan-600 transition-colors line-clamp-1">{{ $project->name }}</h3>

        @if($project->location)
            <p class="text-sm text-gray-500 flex items-center gap-1 mt-1">
                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                {{ $project->translated_location }}
            </p>
        @endif

        @if($company)
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs text-gray-400">{{ $company->company_name }}</span>
                @if($company->is_verified)
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                @endif
            </div>
        @endif

        {{-- Specs row --}}
        <div class="flex items-center gap-3 mt-3 text-xs text-gray-500">
            @if($bedroomRange->count())
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                    @if($bedroomRange->count() === 1)
                        {{ $bedroomRange->first() }} {{ __('portal.bed') }}
                    @else
                        {{ $bedroomRange->first() }}-{{ $bedroomRange->last() }} {{ __('portal.bed') }}
                    @endif
                </span>
            @endif
            @if($project->estimated_delivery)
                <span>{{ $project->estimated_delivery->format('M Y') }}</span>
            @endif
        </div>

        {{-- Price --}}
        @if($minPrice)
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                <div>
                    <span class="text-xs text-gray-400">{{ __('portal.from_price') }}</span>
                    <span class="block text-sm font-bold text-gray-800">USD {{ number_format($minPrice, 0, '.', ',') }}</span>
                </div>
                <span class="text-cyan-600 text-xs font-medium group-hover:translate-x-1 transition-transform inline-flex items-center gap-1">
                    {{ __('portal.view_project') }}
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                </span>
            </div>
        @endif
    </div>
</a>

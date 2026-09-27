<x-portal-layout :title="__('buyer.my_investments')">

    <div class="max-w-3xl mx-auto px-4 py-10">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ __('buyer.my_investments') }}</h1>
        <p class="text-sm text-gray-600 mb-6">{{ __('buyer.select_unit') }}</p>

        <div class="space-y-3">
            @foreach ($unidades as $u)
                <a href="{{ route('mi-inversion.show', $u) }}"
                   class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5
                          hover:border-blue-400 hover:shadow-sm transition">
                    <div>
                        <p class="text-xs text-gray-500">{{ $u->project->name }}</p>
                        <p class="font-semibold text-gray-900 mt-0.5">
                            {{ __('buyer.unit') }} {{ $u->identifier }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $u->bedrooms }} {{ __('buyer.bedrooms') }} ·
                            {{ $u->bathrooms }} {{ __('buyer.bathrooms') }} ·
                            {{ number_format($u->area_m2, 0, ',', '.') }} m²
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>
    </div>

</x-portal-layout>

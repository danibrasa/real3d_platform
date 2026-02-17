<x-app-layout>
    <x-slot name="title">{{ $developer->company_name }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('directory.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $developer->company_name }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Company Header --}}
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <div class="flex items-start gap-4">
                    @if($developer->logo_path)
                        <img src="{{ Storage::url($developer->logo_path) }}" alt="{{ $developer->company_name }}" class="w-20 h-20 rounded-xl object-cover">
                    @else
                        <div class="w-20 h-20 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <span class="text-emerald-700 font-bold text-2xl">{{ substr($developer->company_name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h1 class="text-2xl font-bold text-gray-800">{{ $developer->company_name }}</h1>
                            @if($developer->is_verified)
                                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @endif
                        </div>
                        @if($developer->city || $developer->country)
                            <p class="text-sm text-gray-500 mb-2">{{ $developer->city }}{{ $developer->city && $developer->country ? ', ' : '' }}{{ $developer->country }}</p>
                        @endif
                        @if($developer->description)
                            <p class="text-gray-600 text-sm">
                                {{ app()->getLocale() === 'en' && $developer->description_en ? $developer->description_en : $developer->description }}
                            </p>
                        @endif
                        <div class="flex items-center gap-4 mt-3 text-sm">
                            @if($developer->website)
                                <a href="{{ $developer->website }}" target="_blank" class="text-emerald-600 hover:underline flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    Website
                                </a>
                            @endif
                            @if($developer->phone)
                                <span class="text-gray-500">{{ $developer->phone }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Projects --}}
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ app()->getLocale() === 'en' ? 'Projects' : 'Proyectos' }}</h3>

            @if($projects->count())
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($projects as $project)
                        <a href="{{ route('viewer.landing', $project->slug) }}" class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition group">
                            <div class="h-40 relative overflow-hidden">
                                @php $thumbnail = $project->files->where('file_type', 'thumbnail')->first(); @endphp
                                @if($thumbnail)
                                    <img src="{{ url('api/projects/' . $project->id . '/files/thumbnail') }}" alt="{{ $project->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                @endif
                                @if($project->available_units_count > 0)
                                    <div class="absolute top-2 right-2">
                                        <span class="px-2 py-0.5 bg-emerald-500 text-white text-xs rounded-md font-medium">{{ $project->available_units_count }} {{ app()->getLocale() === 'en' ? 'available' : 'disponibles' }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h4 class="font-semibold text-gray-800 group-hover:text-emerald-600 transition">{{ $project->name }}</h4>
                                @if($project->location)
                                    <p class="text-sm text-gray-500 mt-0.5">{{ $project->translated_location }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $projects->links() }}</div>
            @else
                <div class="bg-white rounded-lg border p-12 text-center text-gray-400">
                    {{ app()->getLocale() === 'en' ? 'No public projects yet.' : 'No hay proyectos publicos aun.' }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

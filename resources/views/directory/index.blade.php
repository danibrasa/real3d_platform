<x-app-layout>
    <x-slot name="title">{{ app()->getLocale() === 'en' ? 'Developer Directory' : 'Directorio de Desarrolladores' }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ app()->getLocale() === 'en' ? 'Developer Directory' : 'Directorio de Desarrolladores' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-gray-500 mb-6">{{ app()->getLocale() === 'en' ? 'Verified real estate developers on our platform.' : 'Desarrolladoras inmobiliarias verificadas en nuestra plataforma.' }}</p>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($developers as $developer)
                    <a href="{{ route('directory.show', $developer->slug) }}" class="bg-white rounded-lg shadow-sm border p-5 hover:shadow-md transition group">
                        <div class="flex items-center gap-3 mb-3">
                            @if($developer->logo_path)
                                <img src="{{ Storage::url($developer->logo_path) }}" alt="{{ $developer->company_name }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <span class="text-emerald-700 font-bold text-lg">{{ substr($developer->company_name, 0, 1) }}</span>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="font-semibold text-gray-800 truncate group-hover:text-emerald-600 transition">{{ $developer->company_name }}</div>
                                @if($developer->city || $developer->country)
                                    <div class="text-xs text-gray-500">{{ $developer->city }}{{ $developer->city && $developer->country ? ', ' : '' }}{{ $developer->country }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ $developer->projects_count }} {{ $developer->projects_count === 1 ? 'proyecto' : 'proyectos' }}</span>
                            @if($developer->is_verified)
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Verified
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-400">
                        {{ app()->getLocale() === 'en' ? 'No developers listed yet.' : 'No hay desarrolladores listados aun.' }}
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $developers->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Papelera</h2>
            <a href="{{ route('admin.projects.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Proyectos</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 p-3 bg-emerald-50 text-emerald-800 rounded-md text-sm">{{ session('success') }}</div>
            @endif

            <p class="text-sm text-gray-600 mb-6">
                Lo que borras se queda aquí {{ $dias }} días, con sus viviendas y su visor. Después se borra del todo.
            </p>

            @forelse ($projects as $project)
                @php $quedan = max(0, $dias - (int) $project->deleted_at->diffInDays(now())); @endphp
                <div class="bg-white shadow-sm rounded-lg p-4 mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-gray-900">{{ $project->name }}</div>
                        <div class="text-xs text-gray-500">
                            Borrado el {{ $project->deleted_at->format('d/m/Y') }} ·
                            @if ($quedan > 0)
                                se borra del todo en {{ $quedan }} {{ $quedan === 1 ? 'día' : 'días' }}
                            @else
                                se borra del todo hoy
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.projects.restaurar', $project) }}">
                        @csrf
                        <button class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-semibold hover:bg-emerald-700">Recuperar</button>
                    </form>
                </div>
            @empty
                <div class="bg-white shadow-sm rounded-lg p-8 text-center text-gray-500">La papelera está vacía.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>

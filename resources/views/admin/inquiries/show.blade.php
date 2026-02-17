<x-app-layout>
    <x-slot name="title">Consulta de {{ $inquiry->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Consulta de {{ $inquiry->name }}</h2>
            <a href="{{ route('admin.inquiries.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Volver</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Fecha</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Nombre</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="mailto:{{ $inquiry->email }}" class="text-blue-600 hover:underline">{{ $inquiry->email }}</a>
                        </dd>
                    </div>
                    @if($inquiry->phone)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Telefono</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->phone }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Proyecto</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('admin.projects.edit', $inquiry->project) }}" class="text-blue-600 hover:underline">{{ $inquiry->project->name }}</a>
                        </dd>
                    </div>
                    @if($inquiry->unit)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Unidad</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $inquiry->unit->identifier }} - {{ $inquiry->unit->formatted_price }}</dd>
                    </div>
                    @endif
                    @if($inquiry->message)
                    <div>
                        <dt class="text-xs font-medium text-gray-500 uppercase">Mensaje</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $inquiry->message }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-6 flex gap-3">
                    <a href="mailto:{{ $inquiry->email }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">Responder por email</a>
                    @can('delete-inquiry')
                    <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('Eliminar esta consulta?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700 transition">Eliminar</button>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

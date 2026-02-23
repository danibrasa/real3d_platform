<x-app-layout>
    <x-slot name="title">Blog - Categorias</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Blog - Categorias</h2>
            <a href="{{ route('admin.blog.posts.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Volver a Articulos</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Create form --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Nueva Categoria</h3>
                <form method="POST" action="{{ route('admin.blog.categories.store') }}" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs text-gray-500 mb-1">Nombre (ES)</label>
                        <input type="text" name="name" required class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs text-gray-500 mb-1">Nombre (EN)</label>
                        <input type="text" name="name_en" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="w-24">
                        <label class="block text-xs text-gray-500 mb-1">Color</label>
                        <input type="color" name="color" value="#3b82f6" class="w-full h-9 rounded-md border-gray-300 cursor-pointer">
                    </div>
                    <div class="w-20">
                        <label class="block text-xs text-gray-500 mb-1">Orden</label>
                        <input type="number" name="sort_order" value="0" min="0" class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700">Crear</button>
                </form>
            </div>

            {{-- List --}}
            @if($categories->count())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orden</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" x-data>
                        @foreach($categories as $cat)
                        <tr x-data="{ editing: false }">
                            <td class="px-6 py-3">
                                <span class="w-5 h-5 rounded-full inline-block" style="background:{{ $cat->color }}"></span>
                            </td>
                            <td class="px-6 py-3">
                                <template x-if="!editing">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">{{ $cat->name }}</span>
                                        @if($cat->name_en)
                                            <span class="text-xs text-gray-400 ml-1">({{ $cat->name_en }})</span>
                                        @endif
                                    </div>
                                </template>
                                <template x-if="editing">
                                    <form method="POST" action="{{ route('admin.blog.categories.update', $cat) }}" class="flex gap-2 items-center" id="edit-cat-{{ $cat->id }}">
                                        @csrf @method('PUT')
                                        <input type="text" name="name" value="{{ $cat->name }}" class="rounded-md border-gray-300 text-sm w-32">
                                        <input type="text" name="name_en" value="{{ $cat->name_en }}" placeholder="EN" class="rounded-md border-gray-300 text-sm w-32">
                                        <input type="color" name="color" value="{{ $cat->color }}" class="w-8 h-8 rounded border-gray-300 cursor-pointer">
                                        <input type="number" name="sort_order" value="{{ $cat->sort_order }}" min="0" class="rounded-md border-gray-300 text-sm w-16">
                                        <button type="submit" class="text-green-600 text-sm hover:underline">Guardar</button>
                                        <button type="button" @click="editing = false" class="text-gray-500 text-sm hover:underline">Cancelar</button>
                                    </form>
                                </template>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ $cat->slug }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ $cat->posts_count }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ $cat->sort_order }}</td>
                            <td class="px-6 py-3 text-right text-sm">
                                <template x-if="!editing">
                                    <div class="flex justify-end gap-2">
                                        <button @click="editing = true" class="text-blue-600 hover:underline">Editar</button>
                                        <form method="POST" action="{{ route('admin.blog.categories.destroy', $cat) }}" onsubmit="return confirm('Eliminar esta categoria?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    </div>
                                </template>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                <p class="text-gray-500">No hay categorias. Crea la primera arriba.</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

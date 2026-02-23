<x-app-layout>
    <x-slot name="title">Blog - Articulos</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Blog - Articulos</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.blog.categories.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                    Categorias
                </a>
                <a href="{{ route('admin.blog.posts.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition">
                    + Nuevo Articulo
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Filters --}}
            <div class="mb-4 flex gap-3 items-center">
                <form method="GET" class="flex gap-3 items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por titulo..."
                           class="rounded-md border-gray-300 text-sm px-3 py-2 focus:border-blue-500 focus:ring-blue-500">
                    <select name="status" class="rounded-md border-gray-300 text-sm px-3 py-2 focus:border-blue-500 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archivado</option>
                    </select>
                    <button type="submit" class="px-3 py-2 bg-gray-200 rounded-md text-sm hover:bg-gray-300">Filtrar</button>
                </form>
            </div>

            @if($posts->count())
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titulo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vistas</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($posts as $post)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($post->featured_image_path)
                                        <img src="{{ Storage::url($post->featured_image_path) }}" class="w-10 h-10 rounded object-cover" alt="">
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($post->title, 50) }}</div>
                                        @if($post->is_featured)
                                            <span class="text-xs text-yellow-600">&#9733; Destacado</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if($post->category)
                                    <span class="inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full" style="background:{{ $post->category->color }}"></span>
                                        {{ $post->category->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = ['draft' => 'bg-yellow-100 text-yellow-800', 'published' => 'bg-green-100 text-green-800', 'archived' => 'bg-gray-100 text-gray-800'];
                                    $statusLabels = ['draft' => 'Borrador', 'published' => 'Publicado', 'archived' => 'Archivado'];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$post->status] ?? '' }}">
                                    {{ $statusLabels[$post->status] ?? $post->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $post->published_at?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ number_format($post->views_count) }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div class="flex justify-end gap-2">
                                    @if($post->status === 'published')
                                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="text-cyan-600 hover:underline">Ver</a>
                                    @endif
                                    <a href="{{ route('admin.blog.posts.edit', $post) }}" class="text-blue-600 hover:underline">Editar</a>
                                    <form method="POST" action="{{ route('admin.blog.posts.destroy', $post) }}" onsubmit="return confirm('Eliminar este articulo?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $posts->links() }}</div>
            @else
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500 mb-4">No hay articulos todavia.</p>
                <a href="{{ route('admin.blog.posts.create') }}" class="text-blue-600 hover:underline">Crear el primer articulo</a>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="title">Nuevo Articulo</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Articulo</h2>
            <a href="{{ route('admin.blog.posts.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.blog.posts.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Contenido</h3>

                    {{-- Title ES --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titulo (ES) *</label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Titulo (EN)</label>
                            <input type="text" name="title_en" value="{{ old('title_en') }}"
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Slug --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug (se genera automaticamente si esta vacio)</label>
                        <input type="text" name="slug" value="{{ old('slug') }}"
                               class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Excerpt ES/EN --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Extracto (ES)</label>
                            <textarea name="excerpt" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('excerpt') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Extracto (EN)</label>
                            <textarea name="excerpt_en" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('excerpt_en') }}</textarea>
                        </div>
                    </div>

                    {{-- Body ES --}}
                    <div x-data="{ tab: 'write' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contenido (ES) - Markdown</label>
                        <div class="flex gap-2 mb-2">
                            <button type="button" @click="tab = 'write'" :class="tab === 'write' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="px-3 py-1 rounded text-xs font-medium">Escribir</button>
                            <button type="button" @click="tab = 'preview'" :class="tab === 'preview' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="px-3 py-1 rounded text-xs font-medium">Preview</button>
                        </div>
                        <textarea x-show="tab === 'write'" name="body" id="body_es" rows="12" class="w-full rounded-md border-gray-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">{{ old('body') }}</textarea>
                        <div x-show="tab === 'preview'" x-cloak class="prose prose-sm max-w-none border rounded-md p-4 min-h-[200px] bg-gray-50" id="body_es_preview"></div>
                    </div>

                    {{-- Body EN --}}
                    <div x-data="{ tab: 'write' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contenido (EN) - Markdown</label>
                        <div class="flex gap-2 mb-2">
                            <button type="button" @click="tab = 'write'" :class="tab === 'write' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="px-3 py-1 rounded text-xs font-medium">Escribir</button>
                            <button type="button" @click="tab = 'preview'" :class="tab === 'preview' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'" class="px-3 py-1 rounded text-xs font-medium">Preview</button>
                        </div>
                        <textarea x-show="tab === 'write'" name="body_en" id="body_en" rows="12" class="w-full rounded-md border-gray-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">{{ old('body_en') }}</textarea>
                        <div x-show="tab === 'preview'" x-cloak class="prose prose-sm max-w-none border rounded-md p-4 min-h-[200px] bg-gray-50" id="body_en_preview"></div>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Imagen y Metadata</h3>

                    {{-- Image --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Imagen destacada</label>
                        <input type="file" name="featured_image" accept="image/*"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alt imagen (ES)</label>
                            <input type="text" name="featured_image_alt" value="{{ old('featured_image_alt') }}"
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alt imagen (EN)</label>
                            <input type="text" name="featured_image_alt_en" value="{{ old('featured_image_alt_en') }}"
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Category + Status --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                            <select name="category_id" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Sin categoria</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select name="status" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                                <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Publicado</option>
                                <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archivado</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha publicacion</label>
                            <input type="datetime-local" name="published_at" value="{{ old('published_at') }}"
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Tags --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tags (separados por coma)</label>
                        <input type="text" name="tags" value="{{ old('tags') }}" placeholder="inversion, punta cana, guia"
                               class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    {{-- Featured --}}
                    <div>
                        <label class="flex items-center gap-2">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">Articulo destacado</span>
                        </label>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">SEO</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta titulo (ES)</label>
                            <input type="text" name="meta_title" value="{{ old('meta_title') }}"
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta titulo (EN)</label>
                            <input type="text" name="meta_title_en" value="{{ old('meta_title_en') }}"
                                   class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta descripcion (ES)</label>
                            <textarea name="meta_description" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('meta_description') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta descripcion (EN)</label>
                            <textarea name="meta_description_en" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('meta_description_en') }}</textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keywords (separadas por coma)</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords') }}"
                               class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.blog.posts.index') }}" class="px-6 py-2 bg-gray-200 rounded-md text-sm font-semibold hover:bg-gray-300">Cancelar</a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700">Crear Articulo</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/markdown-it@14.0.0/dist/markdown-it.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const md = window.markdownit();
        ['es', 'en'].forEach(lang => {
            const textarea = document.getElementById('body_' + lang);
            const preview = document.getElementById('body_' + lang + '_preview');
            if (textarea && preview) {
                textarea.addEventListener('input', () => preview.innerHTML = md.render(textarea.value));
            }
        });
    });
    </script>
    @endpush
</x-app-layout>

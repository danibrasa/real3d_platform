<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">API Tokens</h2>
            <a href="{{ route('admin.api-tokens.create') }}"
               class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                Crear Token
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            @if(session('plainTextToken'))
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg" x-data="{ copied: false }">
                    <p class="text-sm font-semibold text-yellow-800 mb-2">
                        Token creado. Copia este valor ahora — no se mostrara de nuevo:
                    </p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 bg-white px-3 py-2 rounded border text-sm font-mono break-all select-all">{{ session('plainTextToken') }}</code>
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ session('plainTextToken') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="px-3 py-2 bg-yellow-600 text-white rounded text-sm hover:bg-yellow-700 whitespace-nowrap">
                            <span x-show="!copied">Copiar</span>
                            <span x-show="copied" x-cloak>Copiado!</span>
                        </button>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-4">
                        Los tokens API permiten a servicios externos acceder a los datos de proyectos.
                        Cada token puede limitarse a proyectos especificos y tiene su propio rate limit.
                    </p>

                    @if($tokens->isEmpty())
                        <p class="text-gray-400 text-center py-8">No hay tokens creados.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b text-left">
                                        <th class="py-3 px-2">Nombre</th>
                                        <th class="py-3 px-2">Descripcion</th>
                                        <th class="py-3 px-2">Proyectos</th>
                                        <th class="py-3 px-2 text-center">Rate Limit</th>
                                        <th class="py-3 px-2 text-center">Estado</th>
                                        <th class="py-3 px-2">Ultimo uso</th>
                                        <th class="py-3 px-2 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tokens as $token)
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="py-3 px-2 font-medium">{{ $token->name }}</td>
                                            <td class="py-3 px-2 text-gray-500 max-w-[200px] truncate">{{ $token->description ?? '—' }}</td>
                                            <td class="py-3 px-2">
                                                @if(is_null($token->project_ids))
                                                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Todos</span>
                                                @else
                                                    <span class="text-xs text-gray-600">{{ count($token->project_ids) }} proyecto(s)</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-2 text-center">{{ $token->rate_limit }}/min</td>
                                            <td class="py-3 px-2 text-center">
                                                @if($token->is_active)
                                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Activo</span>
                                                @else
                                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">Inactivo</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-2 text-gray-500 text-xs">
                                                {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Nunca' }}
                                            </td>
                                            <td class="py-3 px-2 text-right space-x-2">
                                                <a href="{{ route('admin.api-tokens.edit', $token) }}"
                                                   class="text-blue-600 hover:underline text-xs">Editar</a>
                                                <form method="POST" action="{{ route('admin.api-tokens.destroy', $token) }}" class="inline"
                                                      onsubmit="return confirm('Revocar este token? Los servicios que lo usen dejaran de funcionar.')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:underline text-xs">Revocar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $tokens->links() }}</div>
                    @endif
                </div>
            </div>

            {{-- API Documentation --}}
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-3">Uso de la API</h3>
                    <div class="text-sm text-gray-600 space-y-3">
                        <p>Base URL: <code class="bg-gray-100 px-2 py-1 rounded">{{ url('/api/v1') }}</code></p>
                        <p>Autenticacion: Header <code class="bg-gray-100 px-2 py-1 rounded">Authorization: Bearer {token}</code></p>
                        <div>
                            <p class="font-medium mb-1">Endpoints disponibles:</p>
                            <ul class="list-disc list-inside ml-2 space-y-1 font-mono text-xs">
                                <li>GET /api/v1/projects — Listar proyectos</li>
                                <li>GET /api/v1/projects/{slug} — Detalle de proyecto</li>
                                <li>GET /api/v1/projects/{slug}/units — Unidades (filtros: status, bedrooms, min_price, max_price)</li>
                                <li>GET /api/v1/projects/{slug}/units/{id} — Detalle de unidad</li>
                                <li>GET /api/v1/projects/{slug}/availability — Resumen de disponibilidad</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

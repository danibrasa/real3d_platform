<x-app-layout>
    <x-slot name="title">Webhooks</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Webhooks</h2>
            <a href="{{ route('admin.webhooks.create') }}" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700 transition">
                + New Endpoint
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Events</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Failures</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($webhooks as $webhook)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-800 max-w-xs truncate">{{ $webhook->url }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @foreach($webhook->events ?? [] as $event)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600 mr-1">{{ $event }}</span>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3">
                                    @if($webhook->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Disabled</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $webhook->failure_count }}</td>
                                <td class="px-4 py-3 text-right text-sm space-x-2">
                                    <a href="{{ route('admin.webhooks.deliveries', $webhook) }}" class="text-blue-600 hover:underline">Log</a>
                                    <a href="{{ route('admin.webhooks.edit', $webhook) }}" class="text-gray-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.webhooks.destroy', $webhook) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('Delete this webhook?')" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">No webhook endpoints configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

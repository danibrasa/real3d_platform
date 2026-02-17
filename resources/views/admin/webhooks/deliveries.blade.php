<x-app-layout>
    <x-slot name="title">Webhook Deliveries</x-slot>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.webhooks.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Deliveries: {{ Str::limit($webhook->url, 50) }}</h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">HTTP</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attempt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($deliveries as $delivery)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $delivery->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">{{ $delivery->event_type }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($delivery->status === 'success')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Success</span>
                                    @elseif($delivery->status === 'failed')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Failed</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $delivery->response_status ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $delivery->duration_ms ? $delivery->duration_ms . 'ms' : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $delivery->attempt }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">No deliveries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t">
                    {{ $deliveries->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

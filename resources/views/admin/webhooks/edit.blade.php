<x-app-layout>
    <x-slot name="title">Edit Webhook</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Webhook Endpoint</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <form method="POST" action="{{ route('admin.webhooks.update', $webhook) }}" class="space-y-5">
                    @csrf @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                        <input type="url" name="url" value="{{ old('url', $webhook->url) }}" required
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                        @error('url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Events</label>
                        @foreach($events as $event)
                            <label class="flex items-center gap-2 mb-2">
                                <input type="checkbox" name="events[]" value="{{ $event }}"
                                       {{ in_array($event, old('events', $webhook->events ?? [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                                <span class="text-sm text-gray-700">{{ $event }}</span>
                            </label>
                        @endforeach
                        @error('events') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               {{ old('is_active', $webhook->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                        <label for="is_active" class="text-sm text-gray-700">Active</label>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-xs font-medium text-gray-500 mb-1">Signing Secret</p>
                        <code class="text-xs text-gray-700 break-all">{{ $webhook->secret }}</code>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('admin.webhooks.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</a>
                        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white text-sm rounded-md hover:bg-emerald-700 transition">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

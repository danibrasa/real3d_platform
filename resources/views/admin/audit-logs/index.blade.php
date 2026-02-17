<x-app-layout>
    <x-slot name="title">{{ __('Audit Log') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audit Log</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Filters --}}
            <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('general.search') }}</label>
                        <select name="action" class="text-sm border-gray-300 rounded-md">
                            <option value="">-- Action --</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                        <select name="type" class="text-sm border-gray-300 rounded-md">
                            <option value="">-- Type --</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ class_basename($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Filter</button>
                    @if(request()->hasAny(['action', 'type', 'user_id']))
                        <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Changes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $log->user?->name ?? 'System' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $log->action === 'created' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $log->action === 'updated' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $log->action === 'deleted' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $log->auditable_type ? class_basename($log->auditable_type) : '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $log->auditable_id ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                                        @if($log->action === 'updated' && $log->old_values)
                                            @foreach(array_diff_key($log->new_values ?? [], ['updated_at' => true]) as $key => $val)
                                                <span class="text-xs">{{ $key }}: <span class="text-red-500 line-through">{{ Str::limit(json_encode($log->old_values[$key] ?? null), 30) }}</span> &rarr; <span class="text-green-600">{{ Str::limit(json_encode($val), 30) }}</span></span><br>
                                            @endforeach
                                        @elseif($log->action === 'created' && $log->new_values)
                                            <span class="text-xs text-green-600">{{ Str::limit(collect($log->new_values)->except(['password', 'remember_token'])->toJson(), 80) }}</span>
                                        @elseif($log->action === 'deleted')
                                            <span class="text-xs text-red-500">Record deleted</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No audit logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

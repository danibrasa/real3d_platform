<x-app-layout>
    <x-slot name="title">{{ __('chatbot.admin_title') }}</x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('chatbot.admin_title') }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($totalConversations) }}</div>
                    <div class="text-sm text-gray-500">{{ __('chatbot.total_conversations') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($totalMessages) }}</div>
                    <div class="text-sm text-gray-500">{{ __('chatbot.total_messages') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-2xl font-bold text-emerald-600">{{ number_format($leadsCaptured) }}</div>
                    <div class="text-sm text-gray-500">{{ __('chatbot.leads_captured') }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <div class="text-2xl font-bold text-blue-600">{{ $conversionRate }}%</div>
                    <div class="text-sm text-gray-500">{{ __('chatbot.conversion_rate') }}</div>
                </div>
            </div>

            {{-- By Project Breakdown --}}
            @if($byProject->isNotEmpty())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-sm text-gray-700">{{ __('chatbot.by_project') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.project') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.conversations') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.messages') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.leads') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($byProject as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $row->project->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ number_format($row->conversations) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ number_format($row->messages) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ number_format($row->leads) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Filters --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET" class="flex gap-4 items-end flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('chatbot.project') }}</label>
                        <select name="project_id" class="rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('chatbot.all_projects') }}</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('chatbot.lead_status') }}</label>
                        <select name="lead_captured" class="rounded-md border-gray-300 text-sm">
                            <option value="">{{ __('chatbot.all') }}</option>
                            <option value="1" {{ request('lead_captured') === '1' ? 'selected' : '' }}>{{ __('chatbot.with_lead') }}</option>
                            <option value="0" {{ request('lead_captured') === '0' ? 'selected' : '' }}>{{ __('chatbot.without_lead') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('chatbot.date_from') }}</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ __('chatbot.date_to') }}</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-md border-gray-300 text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm hover:bg-gray-200">{{ __('chatbot.filter') }}</button>
                    @if(request()->hasAny(['project_id', 'lead_captured', 'date_from', 'date_to']))
                        <a href="{{ route('admin.chatbot.index') }}" class="text-sm text-gray-500 hover:underline">{{ __('chatbot.clear') }}</a>
                    @endif
                </form>
            </div>

            {{-- Conversations Table --}}
            @if($conversations->count())
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.project') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.visitor') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.messages') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.lead') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('chatbot.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($conversations as $conv)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $conv->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $conv->project->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800">
                                {{ $conv->visitor_name ?: __('chatbot.anonymous') }}
                                @if($conv->visitor_email)
                                    <span class="text-gray-400 text-xs block">{{ $conv->visitor_email }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 text-right">{{ $conv->messages_count }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($conv->lead_captured)
                                    <span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">{{ __('chatbot.yes') }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-500">{{ __('chatbot.no') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <a href="{{ route('admin.chatbot.show', $conv) }}" class="text-xs text-blue-600 hover:underline">{{ __('chatbot.view') }}</a>
                                    <form method="POST" action="{{ route('admin.chatbot.destroy', $conv) }}" onsubmit="return confirm('{{ __('chatbot.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('chatbot.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $conversations->withQueryString()->links() }}</div>
            @else
            <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                <p class="text-gray-500">{{ __('chatbot.no_conversations') }}</p>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

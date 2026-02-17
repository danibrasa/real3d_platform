<x-app-layout>
    <x-slot name="title">{{ __('chatbot.conversation_detail') }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('chatbot.conversation_detail') }}</h2>
            <a href="{{ route('admin.chatbot.index') }}" class="text-sm text-blue-600 hover:underline">&larr; {{ __('chatbot.back_to_list') }}</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Visitor Info --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-sm text-gray-700 mb-4">{{ __('chatbot.visitor_info') }}</h3>
                <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('chatbot.project') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $conversation->project->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('chatbot.date') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $conversation->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('chatbot.visitor_name') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $conversation->visitor_name ?: __('chatbot.anonymous') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Email</dt>
                        <dd class="font-medium text-gray-800">{{ $conversation->visitor_email ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('chatbot.phone') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $conversation->visitor_phone ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('chatbot.lead') }}</dt>
                        <dd>
                            @if($conversation->lead_captured)
                                <span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800">{{ __('chatbot.lead_captured_yes') }}</span>
                                @if($conversation->inquiry)
                                    <a href="{{ route('admin.inquiries.show', $conversation->inquiry) }}" class="text-xs text-blue-600 hover:underline ml-1">{{ __('chatbot.view_inquiry') }}</a>
                                @endif
                            @else
                                <span class="text-gray-400">{{ __('chatbot.no') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('chatbot.locale') }}</dt>
                        <dd class="font-medium text-gray-800">{{ strtoupper($conversation->locale) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">IP</dt>
                        <dd class="font-medium text-gray-800">{{ $conversation->ip_address ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('chatbot.messages') }}</dt>
                        <dd class="font-medium text-gray-800">{{ $conversation->messages_count }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Chat Messages --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="font-semibold text-sm text-gray-700">{{ __('chatbot.messages') }}</h3>
                </div>
                <div class="p-4 space-y-3 max-h-[600px] overflow-y-auto" style="background: #f9fafb;">
                    @foreach($conversation->messages->sortBy('id') as $msg)
                        <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="{{ $msg->role === 'user'
                                    ? 'bg-emerald-600 text-white rounded-2xl rounded-br-md'
                                    : 'bg-gray-200 text-gray-800 rounded-2xl rounded-bl-md' }} px-4 py-2 max-w-[75%] text-sm">
                                <div>{!! nl2br(e($msg->content)) !!}</div>
                                <div class="mt-1 text-xs {{ $msg->role === 'user' ? 'text-emerald-200' : 'text-gray-400' }}">
                                    {{ $msg->created_at->format('H:i') }}
                                    @if($msg->tokens_used)
                                        &middot; {{ $msg->tokens_used }} tokens
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3">
                @if($conversation->visitor_email)
                    <a href="mailto:{{ $conversation->visitor_email }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition">
                        {{ __('chatbot.reply_email') }}
                    </a>
                @endif
                <form method="POST" action="{{ route('admin.chatbot.destroy', $conversation) }}" onsubmit="return confirm('{{ __('chatbot.confirm_delete') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700 transition">
                        {{ __('chatbot.delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

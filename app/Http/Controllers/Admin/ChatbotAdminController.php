<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatbotAdminController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('use-chatbot');

        $user = auth()->user();
        $projectIds = $user->accessibleProjects()->pluck('id');

        // Global stats
        $baseQuery = ChatbotConversation::whereIn('project_id', $projectIds);
        $totalConversations = (clone $baseQuery)->count();
        $totalMessages = (clone $baseQuery)->sum('messages_count');
        $leadsCaptured = (clone $baseQuery)->where('lead_captured', true)->count();
        $conversionRate = $totalConversations > 0
            ? round(($leadsCaptured / $totalConversations) * 100, 1)
            : 0;

        // By-project breakdown
        $byProject = ChatbotConversation::whereIn('project_id', $projectIds)
            ->select('project_id')
            ->selectRaw('COUNT(*) as conversations')
            ->selectRaw('SUM(messages_count) as messages')
            ->selectRaw('SUM(lead_captured) as leads')
            ->groupBy('project_id')
            ->with('project:id,name,slug')
            ->get();

        // Filterable conversations list
        $query = ChatbotConversation::with('project:id,name,slug')
            ->whereIn('project_id', $projectIds)
            ->latest();

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('lead_captured')) {
            $query->where('lead_captured', $request->lead_captured === '1');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $conversations = $query->paginate(20);

        // Projects for filter dropdown
        $projects = $user->accessibleProjects()->select('id', 'name')->orderBy('name')->get();

        return view('admin.chatbot.index', compact(
            'totalConversations', 'totalMessages', 'leadsCaptured', 'conversionRate',
            'byProject', 'conversations', 'projects'
        ));
    }

    public function show(ChatbotConversation $conversation)
    {
        Gate::authorize('use-chatbot');
        $this->authorizeConversationAccess($conversation);

        $conversation->load('project:id,name,slug', 'messages', 'inquiry');

        return view('admin.chatbot.show', compact('conversation'));
    }

    public function destroy(ChatbotConversation $conversation)
    {
        Gate::authorize('use-chatbot');
        $this->authorizeConversationAccess($conversation);

        $conversation->delete();

        return redirect()->route('admin.chatbot.index')
            ->with('success', __('chatbot.conversation_deleted'));
    }

    private function authorizeConversationAccess(ChatbotConversation $conversation): void
    {
        $user = auth()->user();
        if (! $user->canAccessProject($conversation->project)) {
            abort(403);
        }
    }
}

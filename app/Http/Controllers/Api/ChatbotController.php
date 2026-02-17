<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use App\Models\Inquiry;
use App\Models\Project;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function sendMessage(Request $request, Project $project): JsonResponse
    {
        if (!config('chatbot.enabled')) {
            return response()->json(['error' => 'Chatbot is not enabled.'], 503);
        }

        if (!in_array($project->status, ['public', 'unlisted'])) {
            return response()->json(['error' => 'Project not accessible.'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'session_id' => 'required|string|max:36',
        ]);

        // Find or create conversation
        $conversation = ChatbotConversation::firstOrCreate(
            [
                'project_id' => $project->id,
                'session_id' => $validated['session_id'],
            ],
            [
                'locale' => app()->getLocale(),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            ]
        );

        // Check max messages
        $maxMessages = config('chatbot.max_messages_per_conversation', 20);
        if ($conversation->messages_count >= $maxMessages) {
            $msg = $conversation->locale === 'en'
                ? "We've reached the message limit for this conversation. Please contact us via WhatsApp or the contact form for further assistance."
                : "Hemos alcanzado el limite de mensajes para esta conversacion. Contactanos por WhatsApp o el formulario de contacto para mas ayuda.";
            return response()->json([
                'message' => $msg,
                'lead_captured' => $conversation->lead_captured,
                'limit_reached' => true,
            ]);
        }

        // Call AI
        $service = new ChatbotService();
        $response = $service->chat($conversation, $validated['message']);

        // Auto-detect contact info from user message
        $contactInfo = $service->detectLeadInfo($validated['message']);
        if (!empty($contactInfo)) {
            if (isset($contactInfo['email'])) {
                $conversation->visitor_email = $contactInfo['email'];
            }
            if (isset($contactInfo['phone'])) {
                $conversation->visitor_phone = $contactInfo['phone'];
            }
            $conversation->save();
        }

        // Suggest lead capture?
        $suggestLead = !$conversation->lead_captured
            && $conversation->messages_count >= config('chatbot.lead_capture_after_messages', 3);

        return response()->json([
            'message' => $response,
            'lead_captured' => $conversation->lead_captured,
            'suggest_lead' => $suggestLead,
            'messages_count' => $conversation->messages_count,
        ]);
    }

    public function captureLead(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string|max:36',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $conversation = ChatbotConversation::where('project_id', $project->id)
            ->where('session_id', $validated['session_id'])
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found.'], 404);
        }

        if ($conversation->lead_captured) {
            return response()->json(['message' => 'Lead already captured.', 'success' => true]);
        }

        // Build message from conversation summary
        $lastMessages = $conversation->messages()
            ->where('role', 'user')
            ->latest()
            ->limit(3)
            ->pluck('content')
            ->reverse()
            ->join(' | ');

        $chatNote = $conversation->locale === 'en'
            ? "[Via chatbot] " . $lastMessages
            : "[Via chatbot] " . $lastMessages;

        // Create inquiry
        $inquiry = Inquiry::create([
            'project_id' => $project->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'message' => $chatNote,
            'read' => false,
        ]);

        // Update conversation
        $conversation->update([
            'visitor_name' => $validated['name'],
            'visitor_email' => $validated['email'],
            'visitor_phone' => $validated['phone'],
            'lead_captured' => true,
            'inquiry_id' => $inquiry->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => $conversation->locale === 'en'
                ? 'Thank you! An advisor will contact you soon.'
                : 'Gracias! Un asesor se pondra en contacto contigo pronto.',
        ]);
    }
}

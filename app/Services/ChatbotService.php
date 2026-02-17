<?php

namespace App\Services;

use App\Models\ChatbotConversation;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    private string $provider;
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->provider = config('chatbot.provider', 'openai');
        $config = config("chatbot.{$this->provider}");
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? '';
    }

    public function chat(ChatbotConversation $conversation, string $userMessage): string
    {
        $project = $conversation->project;
        $project->load('typologies', 'units', 'paymentPlans', 'constructionPhases');

        // Save user message
        $conversation->addMessage('user', $userMessage);

        // Build messages array for LLM
        $systemPrompt = $this->buildProjectContext($project, $conversation->locale);
        $history = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->get();

        try {
            $response = match ($this->provider) {
                'anthropic' => $this->callAnthropic($systemPrompt, $history),
                default => $this->callOpenAI($systemPrompt, $history),
            };
        } catch (\Throwable $e) {
            Log::error('Chatbot API error', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);

            $response = $conversation->locale === 'en'
                ? "I'm sorry, I'm having trouble responding right now. Please try again or contact us via WhatsApp."
                : "Lo siento, tengo un problema para responder ahora. Por favor, intenta de nuevo o contactanos por WhatsApp.";
        }

        // Save assistant response
        $conversation->addMessage('assistant', $response);

        return $response;
    }

    public function buildProjectContext(Project $project, string $locale = 'es'): string
    {
        $isEn = $locale === 'en';

        $units = $project->units;
        $available = $units->where('status', 'available');
        $priceMin = $available->min('price');
        $priceMax = $available->max('price');

        $typologies = $project->typologies->map(fn ($t) =>
            "{$t->name}: {$t->bedrooms} hab, {$t->bathrooms} banos, {$t->area_m2}m2"
        )->join("\n");

        $unitsSummary = $available->groupBy('bedrooms')->map(fn ($group, $beds) =>
            "{$beds} hab: {$group->count()} disponibles, desde USD " . number_format($group->min('price'), 0, '.', ',')
        )->join("\n");

        $paymentInfo = '';
        if ($project->paymentPlans->isNotEmpty()) {
            $paymentInfo = $isEn ? "\n\nPayment plans:\n" : "\n\nPlanes de pago:\n";
            foreach ($project->paymentPlans as $plan) {
                $paymentInfo .= "- {$plan->name}: {$plan->description}\n";
            }
        }

        $constructionInfo = '';
        if ($project->constructionPhases->isNotEmpty()) {
            $constructionInfo = $isEn ? "\n\nConstruction progress:\n" : "\n\nProgreso de obra:\n";
            foreach ($project->constructionPhases as $phase) {
                $status = $isEn
                    ? match ($phase->status) { 'completed' => 'Completed', 'in_progress' => 'In progress', default => 'Pending' }
                    : match ($phase->status) { 'completed' => 'Completada', 'in_progress' => 'En progreso', default => 'Pendiente' };
                $constructionInfo .= "- {$phase->name}: {$status} ({$phase->target_percentage}%)\n";
            }
        }

        $whatsapp = $project->whatsapp_number
            ? ($isEn ? "\nWhatsApp: {$project->whatsapp_number}" : "\nWhatsApp: {$project->whatsapp_number}")
            : '';

        $delivery = $project->estimated_delivery
            ? ($isEn ? "\nEstimated delivery: " : "\nEntrega estimada: ") . $project->estimated_delivery->format('m/Y')
            : '';

        $description = $isEn ? ($project->description_en ?: $project->description) : $project->description;
        $location = $isEn ? ($project->location_en ?: $project->location) : $project->location;

        $lang = $isEn ? 'English' : 'Spanish';

        $prompt = <<<PROMPT
You are a friendly and professional real estate sales assistant for the project "{$project->name}".
You MUST respond in {$lang}.
You help potential buyers learn about the project, its units, prices, and amenities.

PROJECT INFORMATION:
Name: {$project->name}
Location: {$location}
Description: {$description}
Floors: {$project->total_floors}{$delivery}{$whatsapp}

UNIT TYPES:
{$typologies}

AVAILABILITY:
Total units: {$units->count()}
Available: {$available->count()}
Reserved: {$units->where('status', 'reserved')->count()}
Sold: {$units->where('status', 'sold')->count()}
Price range: USD {$priceMin} - USD {$priceMax}

AVAILABILITY BY BEDROOMS:
{$unitsSummary}{$paymentInfo}{$constructionInfo}

RULES:
- Be concise and helpful. Keep responses under 150 words.
- Only share information that is provided above. Do not invent data.
- If asked about specific technical details you don't have, suggest contacting the sales team.
- If the visitor seems interested, politely ask for their name and contact information so an advisor can reach out.
- If they provide contact information (name, email, or phone), acknowledge it warmly.
- You can recommend specific unit types based on the visitor's needs (budget, bedrooms, etc).
- Prices are in USD.
- Do not discuss legal or contractual matters. Suggest consulting with the sales team.
PROMPT;

        if (!empty($project->chatbot_instructions)) {
            $prompt .= "\n\nADDITIONAL INSTRUCTIONS:\n" . $project->chatbot_instructions;
        }

        return $prompt;
    }

    private function callOpenAI(string $systemPrompt, $history): string
    {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => 300,
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("OpenAI API error: {$response->status()} - {$response->body()}");
        }

        return $response->json('choices.0.message.content', '');
    }

    private function callAnthropic(string $systemPrompt, $history): string
    {
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 300,
            'system' => $systemPrompt,
            'messages' => $messages,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Anthropic API error: {$response->status()} - {$response->body()}");
        }

        return $response->json('content.0.text', '');
    }

    public function detectLeadInfo(string $text): array
    {
        $info = [];

        // Email
        if (preg_match('/[\w.+-]+@[\w-]+\.[\w.]+/', $text, $matches)) {
            $info['email'] = $matches[0];
        }

        // Phone (international formats)
        if (preg_match('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{2,4}\)?[-.\s]?\d{3,4}[-.\s]?\d{3,4}/', $text, $matches)) {
            $phone = preg_replace('/[^\d+]/', '', $matches[0]);
            if (strlen($phone) >= 7) {
                $info['phone'] = $matches[0];
            }
        }

        return $info;
    }
}

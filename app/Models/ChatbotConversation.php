<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    protected $fillable = [
        'project_id',
        'session_id',
        'visitor_name',
        'visitor_email',
        'visitor_phone',
        'locale',
        'messages_count',
        'lead_captured',
        'inquiry_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'lead_captured' => 'boolean',
        'messages_count' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function addMessage(string $role, string $content, ?int $tokens = null): ChatbotMessage
    {
        $message = $this->messages()->create([
            'role' => $role,
            'content' => $content,
            'tokens_used' => $tokens,
        ]);

        $this->increment('messages_count');

        return $message;
    }
}

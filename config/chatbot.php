<?php

return [

    'enabled' => env('CHATBOT_ENABLED', false),

    'provider' => env('CHATBOT_PROVIDER', 'openai'), // openai | anthropic

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    'max_messages_per_conversation' => 20,

    'max_conversations_per_ip_hour' => 5,

    'lead_capture_after_messages' => 3,

];

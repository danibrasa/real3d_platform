<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class DispatchWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 300, 3600];

    public function __construct(
        private int $endpointId,
        private string $eventType,
        private array $payload,
    ) {}

    public function handle(): void
    {
        $endpoint = WebhookEndpoint::find($this->endpointId);
        if (! $endpoint || ! $endpoint->is_active) {
            return;
        }

        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => $this->eventType,
            'payload' => $this->payload,
            'status' => 'pending',
            'attempt' => $this->attempts(),
        ]);

        $body = json_encode([
            'event' => $this->eventType,
            'data' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ]);

        $signature = hash_hmac('sha256', $body, $endpoint->secret);
        $start = microtime(true);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->eventType,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            $durationMs = (int) ((microtime(true) - $start) * 1000);

            $delivery->update([
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 5000),
                'duration_ms' => $durationMs,
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if ($response->successful()) {
                $endpoint->update([
                    'failure_count' => 0,
                    'last_triggered_at' => now(),
                ]);
            } else {
                $this->handleFailure($endpoint, $delivery);
            }
        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $start) * 1000);

            $delivery->update([
                'response_body' => substr($e->getMessage(), 0, 5000),
                'duration_ms' => $durationMs,
                'status' => 'failed',
            ]);

            $this->handleFailure($endpoint, $delivery);

            throw $e; // Re-throw for retry
        }
    }

    private function handleFailure(WebhookEndpoint $endpoint, WebhookDelivery $delivery): void
    {
        $endpoint->increment('failure_count');

        // Deactivate after 10 consecutive failures
        if ($endpoint->failure_count >= 10) {
            $endpoint->update(['is_active' => false]);
        }
    }
}

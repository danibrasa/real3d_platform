<?php

namespace App\Services;

use App\Jobs\DispatchWebhook;
use App\Models\Project;
use App\Models\WebhookEndpoint;

class WebhookService
{
    const EVENT_INQUIRY_CREATED = 'inquiry_created';

    const EVENT_UNIT_STATUS_CHANGED = 'unit_status_changed';

    const EVENT_PROJECT_PUBLISHED = 'project_published';

    public static function dispatch(string $event, array $payload, int $projectId): void
    {
        $project = Project::find($projectId);
        if (! $project) {
            return;
        }

        // Find inmobiliarias assigned to this project
        $agencyIds = $project->assignedAgencies()->pluck('users.id');

        if ($agencyIds->isEmpty()) {
            return;
        }

        // Find active webhook endpoints for those agencies that subscribe to this event
        $endpoints = WebhookEndpoint::whereIn('user_id', $agencyIds)
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            if ($endpoint->subscribesTo($event)) {
                DispatchWebhook::dispatch($endpoint->id, $event, $payload);
            }
        }
    }
}

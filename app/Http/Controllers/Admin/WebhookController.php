<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEndpoint;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index()
    {
        $webhooks = auth()->user()->isInmobiliaria()
            ? WebhookEndpoint::where('user_id', auth()->id())->latest()->get()
            : WebhookEndpoint::with('user')->latest()->get();

        return view('admin.webhooks.index', compact('webhooks'));
    }

    public function create()
    {
        $events = [
            WebhookService::EVENT_INQUIRY_CREATED,
            WebhookService::EVENT_UNIT_STATUS_CHANGED,
            WebhookService::EVENT_PROJECT_PUBLISHED,
        ];

        return view('admin.webhooks.create', compact('events'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|max:50',
        ]);

        WebhookEndpoint::create([
            'user_id' => auth()->id(),
            'url' => $validated['url'],
            'secret' => Str::random(64),
            'events' => $validated['events'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook endpoint created.');
    }

    public function edit(WebhookEndpoint $webhook)
    {
        $this->authorizeAccess($webhook);

        $events = [
            WebhookService::EVENT_INQUIRY_CREATED,
            WebhookService::EVENT_UNIT_STATUS_CHANGED,
            WebhookService::EVENT_PROJECT_PUBLISHED,
        ];

        return view('admin.webhooks.edit', compact('webhook', 'events'));
    }

    public function update(Request $request, WebhookEndpoint $webhook)
    {
        $this->authorizeAccess($webhook);

        $validated = $request->validate([
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'string|max:50',
            'is_active' => 'boolean',
        ]);

        $webhook->update([
            'url' => $validated['url'],
            'events' => $validated['events'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook endpoint updated.');
    }

    public function destroy(WebhookEndpoint $webhook)
    {
        $this->authorizeAccess($webhook);

        $webhook->delete();

        return redirect()->route('admin.webhooks.index')
            ->with('success', 'Webhook endpoint deleted.');
    }

    public function deliveries(WebhookEndpoint $webhook)
    {
        $this->authorizeAccess($webhook);

        $deliveries = $webhook->deliveries()->latest()->paginate(50);

        return view('admin.webhooks.deliveries', compact('webhook', 'deliveries'));
    }

    private function authorizeAccess(WebhookEndpoint $webhook): void
    {
        $user = auth()->user();
        if (! $user->isSuperadmin() && $webhook->user_id !== $user->id) {
            abort(403);
        }
    }
}

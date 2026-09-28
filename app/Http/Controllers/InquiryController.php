<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Project;
use App\Services\WebhookService;
use App\Support\Leads\AvisoDeConsulta;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request, Project $project)
    {
        // Only allow inquiries for public/unlisted projects
        if (! in_array($project->status, ['public', 'unlisted'])) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:2000',
            // SECURITY FIX: Verify unit belongs to this project (prevent IDOR cross-project)
            'unit_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($project) {
                    if ($value && ! $project->units()->where('id', $value)->exists()) {
                        $fail('The selected unit is invalid.');
                    }
                },
            ],
        ]);

        $validated['project_id'] = $project->id;

        $inquiry = Inquiry::create($validated);
        $inquiry->load(['project', 'unit']);

        WebhookService::dispatch('inquiry_created', [
            'inquiry_id' => $inquiry->id,
            'project_slug' => $project->slug,
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'phone' => $inquiry->phone,
            'unit_id' => $inquiry->unit_id,
        ], $project->id);

        // Notify project contact + superadmins
        // Los destinatarios y el aviso viven en AvisoDeConsulta: el
        // chatbot guarda leads igual que esto y no avisaba a nadie.
        AvisoDeConsulta::enviar($inquiry, $project);

        return back()->with('success', 'Gracias por tu consulta. Nos pondremos en contacto pronto.');
    }
}

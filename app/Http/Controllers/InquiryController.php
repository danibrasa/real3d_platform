<?php

namespace App\Http\Controllers;

use App\Mail\InquiryAutoReply;
use App\Mail\NewInquiryNotification;
use App\Models\Inquiry;
use App\Models\Project;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
        $recipients = collect();

        if ($project->contact_email) {
            $recipients->push($project->contact_email);
        } else {
            // Sin correo de contacto configurado, el aviso solo iba a los
            // superadmin del SaaS: la promotora dueña del proyecto no se
            // enteraba de su propio lead, y rellenar ese campo es justo lo que
            // se olvida al dar de alta un proyecto.
            $recipients = $recipients->merge($project->assignedAgencies()->pluck('email'));

            if ($recipients->isEmpty() && $project->creator) {
                $recipients->push($project->creator->email);
            }
        }

        $superadminEmails = User::where('role', User::ROLE_SUPERADMIN)->pluck('email');
        $recipients = $recipients->merge($superadminEmails)->unique();

        if ($recipients->isNotEmpty()) {
            foreach ($recipients as $email) {
                Mail::to($email)->queue(new NewInquiryNotification($inquiry));
            }
        }

        // Auto-reply to the buyer
        Mail::to($inquiry->email)->queue(new InquiryAutoReply($inquiry));

        return back()->with('success', 'Gracias por tu consulta. Nos pondremos en contacto pronto.');
    }
}

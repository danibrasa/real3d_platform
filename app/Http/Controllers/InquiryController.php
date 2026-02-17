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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'message' => 'nullable|string|max:2000',
            'unit_id' => 'nullable|exists:units,id',
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

        // QW1: Notify project contact + superadmins
        $recipients = collect();

        if ($project->contact_email) {
            $recipients->push($project->contact_email);
        }

        $superadminEmails = User::where('role', User::ROLE_SUPERADMIN)->pluck('email');
        $recipients = $recipients->merge($superadminEmails)->unique();

        if ($recipients->isNotEmpty()) {
            foreach ($recipients as $email) {
                Mail::to($email)->queue(new NewInquiryNotification($inquiry));
            }
        }

        // QW6: Auto-reply to the buyer
        Mail::to($inquiry->email)->queue(new InquiryAutoReply($inquiry));

        return back()->with('success', 'Gracias por tu consulta. Nos pondremos en contacto pronto.');
    }
}

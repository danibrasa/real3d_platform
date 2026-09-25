<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentPlan;
use App\Models\Project;
use Illuminate\Http\Request;

class PaymentPlanController extends Controller
{
    public function index(Project $project)
    {
        $plans = $project->paymentPlans()->with('milestones')->orderBy('sort_order')->get();

        return view('admin.projects.payment-plans', compact('project', 'plans'));
    }

    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_label' => 'nullable|string|max:255',
        ]);

        // If setting as default, unset others
        if ($request->boolean('is_default')) {
            $project->paymentPlans()->update(['is_default' => false]);
        }

        $plan = $project->paymentPlans()->create([
            'name' => $validated['name'],
            'is_default' => $request->boolean('is_default'),
            'sort_order' => $project->paymentPlans()->count(),
            'discount_type' => $validated['discount_type'] ?? null,
            'discount_value' => $validated['discount_value'] ?? null,
            'discount_label' => $validated['discount_label'] ?? null,
        ]);

        return redirect()->route('admin.projects.payment-plans.index', $project)
            ->with('success', 'Plan de pago creado.');
    }

    public function update(Request $request, Project $project, PaymentPlan $paymentPlan)
    {
        abort_if($paymentPlan->project_id !== $project->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_label' => 'nullable|string|max:255',
            'milestones' => 'required|array|min:1',
            'milestones.*.name' => 'required|string|max:255',
            'milestones.*.percentage' => 'required|numeric|min:0|max:100',
            'milestones.*.description' => 'nullable|string|max:500',
            'milestones.*.due_description' => 'nullable|string|max:255',
            'milestones.*.milestone_type' => 'nullable|in:reservation,signing,construction,delivery,other',
        ]);

        // If setting as default, unset others
        if ($request->boolean('is_default')) {
            $project->paymentPlans()->where('id', '!=', $paymentPlan->id)->update(['is_default' => false]);
        }

        $paymentPlan->update([
            'name' => $validated['name'],
            'is_default' => $request->boolean('is_default'),
            'discount_type' => $validated['discount_type'] ?? null,
            'discount_value' => $validated['discount_value'] ?? null,
            'discount_label' => $validated['discount_label'] ?? null,
        ]);

        // Sync milestones: delete existing, re-create
        $paymentPlan->milestones()->delete();
        foreach ($validated['milestones'] as $i => $ms) {
            $paymentPlan->milestones()->create([
                'name' => $ms['name'],
                'percentage' => $ms['percentage'],
                'description' => $ms['description'] ?? null,
                'due_description' => $ms['due_description'] ?? null,
                'milestone_type' => $ms['milestone_type'] ?? 'other',
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('admin.projects.payment-plans.index', $project)
            ->with('success', 'Plan de pago actualizado.');
    }

    public function destroy(Project $project, PaymentPlan $paymentPlan)
    {
        abort_if($paymentPlan->project_id !== $project->id, 404);

        $paymentPlan->delete();

        return redirect()->route('admin.projects.payment-plans.index', $project)
            ->with('success', 'Plan de pago eliminado.');
    }
}

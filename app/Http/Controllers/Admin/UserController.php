<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-agents');

        $user = auth()->user();

        if ($user->isSuperadmin()) {
            $users = User::where('role', '!=', User::ROLE_USER)
                ->with('agency')
                ->latest()
                ->paginate(20);
        } else {
            // Inmobiliaria: only their agents
            $users = User::where('agency_id', $user->id)
                ->latest()
                ->paginate(20);
        }

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        Gate::authorize('manage-agents');

        $user = auth()->user();
        $agencies = [];

        if ($user->isSuperadmin()) {
            $agencies = User::where('role', User::ROLE_INMOBILIARIA)->orderBy('name')->get();
        }

        return view('admin.users.create', compact('agencies'));
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-agents');

        $user = auth()->user();

        // Determine allowed roles
        if ($user->isSuperadmin()) {
            $allowedRoles = [User::ROLE_SUPERADMIN, User::ROLE_GESTOR, User::ROLE_INMOBILIARIA, User::ROLE_AGENTE];
        } else {
            $allowedRoles = [User::ROLE_AGENTE];
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in($allowedRoles)],
            'agency_id' => 'nullable|exists:users,id',
        ]);

        // Set agency_id logic
        if ($validated['role'] === User::ROLE_AGENTE) {
            if ($user->isInmobiliaria()) {
                $validated['agency_id'] = $user->id;
            }
            // superadmin provides agency_id from form
        } else {
            $validated['agency_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado.');
    }

    public function edit(User $editUser)
    {
        Gate::authorize('manage-agents');
        $this->authorizeUserAccess($editUser);

        $user = auth()->user();
        $agencies = [];
        $assignedProjectIds = [];

        if ($user->isSuperadmin()) {
            $agencies = User::where('role', User::ROLE_INMOBILIARIA)->orderBy('name')->get();

            if ($editUser->isInmobiliaria()) {
                $assignedProjectIds = $editUser->assignedProjects()->pluck('projects.id')->toArray();
            }
        }

        $allProjects = $user->isSuperadmin() ? Project::orderBy('name')->get() : collect();

        return view('admin.users.edit', compact('editUser', 'agencies', 'allProjects', 'assignedProjectIds'));
    }

    public function update(Request $request, User $editUser)
    {
        Gate::authorize('manage-agents');
        $this->authorizeUserAccess($editUser);

        $user = auth()->user();

        if ($user->isSuperadmin()) {
            $allowedRoles = [User::ROLE_SUPERADMIN, User::ROLE_GESTOR, User::ROLE_INMOBILIARIA, User::ROLE_AGENTE];
        } else {
            $allowedRoles = [User::ROLE_AGENTE];
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($editUser->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in($allowedRoles)],
            'agency_id' => 'nullable|exists:users,id',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Set agency_id logic
        if ($validated['role'] === User::ROLE_AGENTE) {
            if ($user->isInmobiliaria()) {
                $validated['agency_id'] = $user->id;
            }
        } else {
            $validated['agency_id'] = null;
        }

        $editUser->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $editUser)
    {
        Gate::authorize('manage-agents');
        $this->authorizeUserAccess($editUser);

        // Prevent self-deletion
        if ($editUser->id === auth()->id()) {
            return back()->with('error', 'No podes eliminarte a vos mismo.');
        }

        $editUser->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado.');
    }

    public function assignProjects(Request $request, User $user)
    {
        Gate::authorize('assign-projects');

        if (!$user->isInmobiliaria()) {
            abort(422, 'Solo se pueden asignar proyectos a inmobiliarias.');
        }

        $validated = $request->validate([
            'project_ids' => 'array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $user->assignedProjects()->sync($validated['project_ids'] ?? []);

        return redirect()->route('admin.users.edit', ['editUser' => $user])
            ->with('success', 'Proyectos asignados actualizados.');
    }

    private function authorizeUserAccess(User $targetUser): void
    {
        $user = auth()->user();

        if ($user->isSuperadmin()) {
            return; // Can manage any admin user
        }

        if ($user->isInmobiliaria()) {
            // Can only manage their own agents
            if ($targetUser->agency_id !== $user->id) {
                abort(403);
            }
            return;
        }

        abort(403);
    }
}

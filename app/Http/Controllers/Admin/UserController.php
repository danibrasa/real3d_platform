<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\Project;
use App\Models\User;
use App\Support\Agentes\Invitacion;
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

        // El cupo del plan, a la vista antes de chocar con el.
        $cupo = $user->isInmobiliaria() ? Invitacion::cupo($user) : null;

        return view('admin.users.index', compact('users', 'cupo'));
    }

    public function create()
    {
        Gate::authorize('manage-agents');

        $user = auth()->user();
        $agencies = [];

        if ($user->isSuperadmin()) {
            $agencies = User::where('role', User::ROLE_INMOBILIARIA)->orderBy('name')->get();
        } elseif ($sinCupo = $this->sinCupo($user)) {
            return redirect()->route('admin.users.index')->with('error', $sinCupo);
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

        // La promotora no teclea contraseñas: invita, y el agente elige la
        // suya por el enlace del correo. El equipo sigue creando cuentas de
        // cualquier rol con contraseña.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => [$user->isSuperadmin() ? 'required' : 'nullable', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in($allowedRoles)],
            'agency_id' => 'nullable|exists:users,id',
        ]);

        // El tope de agentes del plan no lo comprobaba nadie, y el plan
        // gratuito trae cero: se podian crear todos los que se quisiera.
        //
        // El tope es de la agencia a la que va el agente, no de quien lo
        // crea. Mirando a quien lo crea, el equipo -que no tiene ficha de
        // empresa- podia darle agentes a una promotora del plan gratuito
        // indicando su agency_id: el mismo limite, saltado por la puerta de
        // al lado. Es el fallo que acababa de arreglar en las viviendas y
        // que aqui habia dejado igual.
        if ($validated['role'] === User::ROLE_AGENTE) {
            $agencia = $user->isInmobiliaria()
                ? $user
                : User::find($validated['agency_id'] ?? null);

            if ($agencia) {
                $tope = $agencia->companyProfile?->getPlanLimits()['max_agents'] ?? 0;
                $tiene = User::where('agency_id', $agencia->id)
                    ->where('role', User::ROLE_AGENTE)
                    ->count();

                if ($tiene >= $tope) {
                    return back()->with('error', $this->sinCupo($agencia) ?? __('billing.agent_limit_reached', ['tope' => $tope]));
                }
            }
        }

        if ($user->isInmobiliaria()) {
            Invitacion::invitar($user, $validated['name'], $validated['email']);

            return redirect()->route('admin.users.index')
                ->with('success', __('agentes.invitacion_enviada', ['correo' => $validated['email'], 'dias' => Invitacion::DIAS_DE_VIDA]));
        }

        // Set agency_id logic
        if ($validated['role'] === User::ROLE_AGENTE) {
            // superadmin provides agency_id from form
        } else {
            $validated['agency_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado.');
    }

    /** Un enlace nuevo para un agente que aun no acepto el suyo. */
    public function reenviarInvitacion(User $editUser)
    {
        Gate::authorize('manage-agents');
        $this->authorizeUserAccess($editUser);

        if (! Invitacion::pendiente($editUser)) {
            return back()->with('error', __('agentes.ya_acepto', ['nombre' => $editUser->name]));
        }

        Invitacion::reenviar($editUser);

        return redirect()->route('admin.users.index')
            ->with('success', __('agentes.invitacion_reenviada', ['correo' => $editUser->email]));
    }

    /** Por que no puede invitar, dicho como se lo diria una promotora a otra; null si puede. */
    private function sinCupo(User $promotora): ?string
    {
        $cupo = Invitacion::cupo($promotora);
        if ($cupo['tope'] === 0) {
            return __('agentes.sin_agentes', ['profesional' => CompanyProfile::PLAN_LIMITS[CompanyProfile::PLAN_PROFESSIONAL]['max_agents']]);
        }
        if ($cupo['quedan'] === 0) {
            return __('agentes.cupo_lleno', ['tope' => $cupo['tope']]);
        }

        return null;
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

        // A un agente con la invitacion pendiente no se le pone contraseña
        // desde aqui: entra por su enlace, aceptando las condiciones. Y se
        // dice, no se descarta en silencio.
        if (! empty($validated['password']) && Invitacion::pendiente($editUser)) {
            return back()->withInput()->with('error', __('agentes.sin_clave_hasta_aceptar', ['nombre' => $editUser->name]));
        }
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

        if (! $user->isInmobiliaria()) {
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

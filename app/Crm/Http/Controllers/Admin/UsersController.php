<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Crm\Services\Authorization\PermissionCatalog;
use App\Crm\Support\CrmLabelCatalog;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function __construct(
        private readonly PermissionCatalog $catalog,
        private readonly CrmLabelCatalog $labels
    ) {}

    public function index(): View
    {
        Gate::authorize('crm.users.manage');

        $users = User::query()
            ->with('roles')
            ->orderBy('name')
            ->get();

        return view('crm::admin.users.index', [
            'users' => $users,
            'crmRoles' => $this->crmRoles(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.users.manage');

        return view('crm::admin.users.form', [
            'user' => new User,
            'crmRoles' => $this->crmRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('crm.users.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'crm_role' => ['nullable', 'string', Rule::in(array_keys($this->crmRoles()))],
        ]);

        $user = (new User)->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);
        $user->save();

        if (! empty($validated['crm_role'])) {
            $roleName = $this->catalog->roleName($validated['crm_role']);
            $user->assignRole(Role::findByName($roleName, $this->catalog->guardName()));
        }

        return redirect()
            ->route('crm.users.index')
            ->with('crm_status', trans('crm::messages.users.created'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('crm.users.manage');

        $user->load('roles');

        return view('crm::admin.users.form', [
            'user' => $user,
            'crmRoles' => $this->crmRoles(),
            'currentRole' => $this->currentCrmRoleKey($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('crm.users.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'crm_role' => ['nullable', 'string', Rule::in(array_keys($this->crmRoles()))],
        ]);

        $currentRoleKey = $this->currentCrmRoleKey($user);
        $newRoleKey = $validated['crm_role'] ?? null;

        if ($currentRoleKey === 'owner' && $newRoleKey !== 'owner') {
            if ($this->ownerCount() <= 1) {
                return back()->withErrors(['crm_role' => trans('crm::messages.users.cannot_remove_last_owner_role')]);
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $this->syncCrmRole($user, $newRoleKey);

        return redirect()
            ->route('crm.users.index')
            ->with('crm_status', trans('crm::messages.users.updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('crm.users.manage');

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => trans('crm::messages.users.cannot_delete_own')]);
        }

        if ($this->currentCrmRoleKey($user) === 'owner' && $this->ownerCount() <= 1) {
            return back()->withErrors(['user' => trans('crm::messages.users.cannot_delete_last_owner')]);
        }

        $user->delete();

        return redirect()
            ->route('crm.users.index')
            ->with('crm_status', trans('crm::messages.users.deleted'));
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('crm.users.manage');

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => trans('crm::messages.users.cannot_deactivate_own')]);
        }

        if ($user->is_active && $this->currentCrmRoleKey($user) === 'owner' && $this->ownerCount() <= 1) {
            return back()->withErrors(['user' => trans('crm::messages.users.cannot_deactivate_last_owner')]);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $status = $user->is_active
            ? trans('crm::messages.users.activated')
            : trans('crm::messages.users.deactivated');

        return back()->with('crm_status', $status);
    }

    private function crmRoles(): array
    {
        return $this->labels->crmRoles();
    }

    private function currentCrmRoleKey(User $user): ?string
    {
        $guard = $this->catalog->guardName();

        foreach ($this->catalog->roles() as $key => $role) {
            if ($user->hasRole($role['name'], $guard)) {
                return $key;
            }
        }

        return null;
    }

    private function syncCrmRole(User $user, ?string $roleKey): void
    {
        $guard = $this->catalog->guardName();

        $crmRoleNames = collect($this->catalog->roles())
            ->pluck('name')
            ->all();

        $user->roles()->whereIn('name', $crmRoleNames)->detach();

        if ($roleKey !== null) {
            $roleName = $this->catalog->roleName($roleKey);
            $user->assignRole(Role::findByName($roleName, $guard));
        }
    }

    private function ownerCount(): int
    {
        $guard = $this->catalog->guardName();

        return User::role($this->catalog->roleName('owner'), $guard)->count();
    }
}

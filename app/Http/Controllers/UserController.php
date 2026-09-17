<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with(['roles', 'station'])
            ->when($request->filled('search'), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('employee_number', 'like', "%{$s}%")))
            ->when($request->filled('role'), fn ($q, $slug) => $q->whereHas('roles', fn ($r) => $r->where('slug', $slug)))
            ->when($request->filled('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    public function show(User $user): View
    {
        return view('users.show', [
            'user' => $user,
            'sales' => $user->sales()->with('station', 'fuelProduct')->latest('transacted_at')->limit(12)->get(),
            'shifts' => $user->shifts()->with('station')->latest('opened_at')->limit(12)->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::orderBy('id')->get(),
            'stations' => Station::visibleTo(request()->user())->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_number' => ['nullable', 'string', 'max:40', 'unique:users,employee_number'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'assigned_stations' => ['nullable', 'array'],
            'assigned_stations.*' => ['integer', 'exists:stations,id'],
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ]);

        $user = User::create($data);
        $user->roles()->sync([$data['role_id']]);
        $user->stations()->sync($data['assigned_stations'] ?? []);

        AuditLog::record([
            'action' => 'create',
            'module' => 'users',
            'record_id' => $user->id,
            'description' => "User created: {$user->name} ({$user->email})",
            'new_values' => ['role' => Role::find($data['role_id'])?->name, 'station_id' => $data['station_id']],
        ]);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Role> $allRoles */
        $allRoles = Role::orderBy('id')->get();

        return view('users.edit', [
            'user' => $user,
            'roles' => $allRoles,
            'stations' => Station::visibleTo(request()->user())->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_number' => ['nullable', 'string', 'max:40', 'unique:users,employee_number,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'station_id' => ['nullable', 'integer', 'exists:stations,id'],
            'assigned_stations' => ['nullable', 'array'],
            'assigned_stations.*' => ['integer', 'exists:stations,id'],
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
        ]);

        if (empty($data['password'])) {
            unset($data['password'], $data['password_confirmation']);
        }

        $user->update($data);
        $user->roles()->sync([$data['role_id']]);
        $user->stations()->sync($data['assigned_stations'] ?? []);

        AuditLog::record([
            'action' => 'update',
            'module' => 'users',
            'record_id' => $user->id,
            'description' => "User updated: {$user->name}",
            'new_values' => $user->only(['name', 'status']),
        ]);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot change your own status.');
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';

        if ($newStatus === 'suspended' && $user->isSuperAdmin()) {
            $activeSuperAdmins = User::where('status', 'active')
                ->whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))
                ->count();

            if ($activeSuperAdmins <= 1) {
                return back()->with('error', 'Cannot suspend the last active super admin.');
            }
        }

        $oldStatus = $user->status;
        $user->forceFill(['status' => $newStatus])->save();

        AuditLog::record([
            'action' => $newStatus === 'active' ? 'activate' : 'suspend',
            'module' => 'users',
            'record_id' => $user->id,
            'description' => "User {$newStatus}: {$user->name} ({$user->email})",
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
        ]);

        return back()->with('success', "User {$user->name} has been {$newStatus}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin()) {
            $activeSuperAdmins = User::where('status', 'active')
                ->whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))
                ->count();

            if ($activeSuperAdmins <= 1) {
                return back()->with('error', 'Cannot delete the last super admin.');
            }
        }

        $meta = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roleLabel(),
            'status' => $user->status,
        ];
        $name = $user->name;

        $user->roles()->detach();
        $user->stations()->detach();
        $user->delete();

        AuditLog::record([
            'action' => 'delete',
            'module' => 'users',
            'record_id' => $user->id,
            'description' => "User deleted: {$name} ({$meta['email']})",
            'old_values' => $meta,
        ]);

        return redirect()->route('users.index')->with('success', "User {$name} deleted.");
    }
}
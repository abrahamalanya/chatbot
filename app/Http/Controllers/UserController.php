<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function availableRoles(): array
    {
        return auth()->user()->hasRole('sistema')
            ? ['sistema', 'admin']
            : ['admin'];
    }

    public function index()
    {
        $query = User::with('roles')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'asesor'))
            ->latest();

        if (! auth()->user()->hasRole('sistema')) {
            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'sistema'));
        }

        $users = $query->paginate(20);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->availableRoles();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $availableRoles = $this->availableRoles();

        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'role'                  => ['required', Rule::in($availableRoles)],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);
        $user->assignRole($data['role']);

        return redirect()->route('users.index')
            ->with('success', "Usuario {$user->name} creado correctamente.");
    }

    public function edit(User $user)
    {
        $this->authorizeAccess($user);
        $roles       = $this->availableRoles();
        $currentRole = $user->getRoleNames()->first();
        return view('users.edit', compact('user', 'roles', 'currentRole'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAccess($user);
        $availableRoles = $this->availableRoles();

        $rules = [
            'name'  => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => ['required', Rule::in($availableRoles)],
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        $data = $request->validate($rules);

        $update = ['name' => $data['name'], 'email' => $data['email']];
        if ($request->filled('password')) {
            $update['password'] = $data['password'];
        }

        $user->update($update);
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        $this->authorizeAccess($user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }

    private function authorizeAccess(User $user): void
    {
        if (! auth()->user()->hasRole('sistema') && $user->hasRole('sistema')) {
            abort(403, 'No tienes permiso para gestionar usuarios sistema.');
        }
    }
}

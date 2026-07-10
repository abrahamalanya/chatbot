<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdvisorController extends Controller
{
    private const ROLES = ['asesor', 'supervisor'];

    public function index()
    {
        $advisors = Advisor::with('user.roles')->latest()->paginate(10);
        return view('advisors.index', compact('advisors'));
    }

    public function create()
    {
        return view('advisors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:100',
            'telefono' => 'required|string|max:20|unique:advisors',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => ['required', Rule::in(self::ROLES)],
        ]);

        $user = User::create([
            'name'     => $data['nombre'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);
        $user->assignRole($data['role']);

        Advisor::create([
            'nombre'   => $data['nombre'],
            'telefono' => $data['telefono'],
            'activo'   => true,
            'user_id'  => $user->id,
        ]);

        return redirect()->route('advisors.index')->with('success', 'Asesor creado correctamente.');
    }

    public function edit(Advisor $advisor)
    {
        $currentRole = $advisor->user?->getRoleNames()->first();
        return view('advisors.edit', compact('advisor', 'currentRole'));
    }

    public function update(Request $request, Advisor $advisor)
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:100',
            'telefono' => 'required|string|max:20|unique:advisors,telefono,' . $advisor->id,
            'activo'   => 'boolean',
            'role'     => ['required', Rule::in(self::ROLES)],
        ]);

        $advisor->update([
            'nombre'   => $data['nombre'],
            'telefono' => $data['telefono'],
            'activo'   => $data['activo'] ?? false,
        ]);

        if ($advisor->user) {
            $advisor->user->update(['name' => $data['nombre']]);
            $advisor->user->syncRoles([$data['role']]);
        }

        return redirect()->route('advisors.index')->with('success', 'Asesor actualizado.');
    }

    public function destroy(Advisor $advisor)
    {
        if ($advisor->user) {
            $advisor->user->delete();
        }
        $advisor->delete();

        return redirect()->route('advisors.index')->with('success', 'Asesor eliminado.');
    }
}

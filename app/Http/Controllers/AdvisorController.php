<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdvisorController extends Controller
{
    public function index()
    {
        $advisors = Advisor::with('user')->latest()->paginate(10);
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
        ]);

        $user = User::create([
            'name'     => $data['nombre'],
            'email'    => $data['email'],
            'password' => $data['password'],
        ]);
        $user->assignRole('asesor');

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
        return view('advisors.edit', compact('advisor'));
    }

    public function update(Request $request, Advisor $advisor)
    {
        $data = $request->validate([
            'nombre'   => 'required|string|max:100',
            'telefono' => 'required|string|max:20|unique:advisors,telefono,' . $advisor->id,
            'activo'   => 'boolean',
        ]);

        $advisor->update($data);

        if ($advisor->user) {
            $advisor->user->update(['name' => $data['nombre']]);
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

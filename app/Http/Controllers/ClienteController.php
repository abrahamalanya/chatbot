<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin   = auth()->user()->hasAnyRole(['sistema', 'admin']);
        $advisores = $isAdmin ? Advisor::orderBy('nombre')->get() : collect();

        $clientes = Cliente::with('advisor')
            ->when(!$isAdmin, fn ($q) => $q->where('advisor_id', auth()->user()->advisor->id))
            ->when($isAdmin && $request->advisor_id, fn ($q) => $q->where('advisor_id', $request->advisor_id))
            ->when($request->etapa, fn ($q) => $q->where('etapa', $request->etapa))
            ->when($request->tipo_credito, fn ($q) => $q->where('tipo_credito', $request->tipo_credito))
            ->orderByDesc('updated_at')
            ->get();

        return view('dashboard.clientes.index', compact('clientes', 'isAdmin', 'advisores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_telefono' => 'required',
            'nombre'           => 'nullable|string|max:150',
            'correo'           => 'nullable|email|max:150',
            'documento'        => 'nullable|string|max:30',
            'tipo_credito'     => 'nullable|in:' . implode(',', array_keys(Cliente::TIPOS_CREDITO)),
            'etapa'            => 'nullable|in:' . implode(',', array_keys(Assignment::DISPOSITIONS)),
            'notas'            => 'nullable|string|max:2000',
        ]);

        $advisor = auth()->user()->advisor;

        Cliente::updateOrCreate(
            ['cliente_telefono' => $request->cliente_telefono],
            [
                'advisor_id'   => $advisor->id,
                'nombre'       => $request->nombre,
                'correo'       => $request->correo,
                'documento'    => $request->documento,
                'tipo_credito' => $request->tipo_credito,
                'etapa'        => $request->etapa,
                'notas'        => $request->notas,
            ]
        );

        return back()->with('success', 'Cliente registrado correctamente.');
    }
}

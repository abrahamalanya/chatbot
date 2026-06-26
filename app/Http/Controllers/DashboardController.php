<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\Message;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'asesores'  => Advisor::where('activo', true)->count(),
            'clientes'  => Assignment::distinct('cliente_telefono')->count(),
            'mensajes'  => Message::count(),
            'pendientes' => Assignment::whereDoesntHave('advisor', fn($q) => $q->where('activo', false))->count(),
        ];

        $asesores = Advisor::with('user')->withCount('assignments')->latest()->get();

        return view('dashboard.index', compact('stats', 'asesores'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\Message;
use App\Services\AssignmentService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'asesores'   => Advisor::where('activo', true)->count(),
            'clientes'   => Assignment::distinct('cliente_telefono')->count(),
            'mensajes'   => Message::count(),
            'pendientes' => Assignment::pending()->count(),
        ];

        // Teléfonos con historial previo para badge "recurrente"
        $telefonosConHistorial = Assignment::whereIn('status', [Assignment::STATUS_ASSIGNED, Assignment::STATUS_CLOSED])
            ->pluck('cliente_telefono')
            ->unique();

        $pendientes = Assignment::pending()
            ->latest()
            ->get()
            ->map(function ($p) use ($telefonosConHistorial) {
                $p->es_recurrente = $telefonosConHistorial->contains($p->cliente_telefono);
                return $p;
            });

        $asesores = Advisor::where('activo', true)->get();

        // Filtro por disposition en historial
        $filtroDisposition = $request->input('disposition');

        $historialQuery = Assignment::whereIn('status', [Assignment::STATUS_ASSIGNED, Assignment::STATUS_CLOSED])
            ->with('advisor')
            ->latest();

        if ($filtroDisposition) {
            $historialQuery->where('disposition', $filtroDisposition);
        }

        $asignados = $historialQuery->take(30)->get();

        $dispositions = Assignment::DISPOSITIONS;

        return view('dashboard.index', compact(
            'stats', 'pendientes', 'asesores', 'asignados', 'dispositions', 'filtroDisposition'
        ));
    }

    public function messages(Request $request)
    {
        // Clientes únicos con su última actividad
        $clientes = Assignment::selectRaw('cliente_telefono, MAX(created_at) as last_activity')
            ->groupBy('cliente_telefono')
            ->orderByDesc('last_activity')
            ->get();

        $clienteSeleccionado = $request->cliente;
        $mensajes            = [];
        $historial           = [];

        if ($clienteSeleccionado) {
            $mensajes = \App\Models\Message::where('cliente_telefono', $clienteSeleccionado)
                ->with('advisor')
                ->orderBy('created_at')
                ->get();

            $historial = Assignment::where('cliente_telefono', $clienteSeleccionado)
                ->with('advisor')
                ->latest()
                ->get();
        }

        return view('dashboard.messages', compact('clientes', 'clienteSeleccionado', 'mensajes', 'historial'));
    }

    public function assign(Request $request, Assignment $assignment)
    {
        $request->validate([
            'advisor_id' => 'required|exists:advisors,id',
            'duration'   => 'nullable|integer|min:5|max:120',
        ]);

        $advisor   = Advisor::findOrFail($request->advisor_id);
        $duration  = (int) $request->input('duration', 15);

        app(AssignmentService::class)->assignAdvisor($assignment, $advisor, $duration);

        return redirect()->route('dashboard')->with('success', "Cliente {$assignment->cliente_telefono} asignado a {$advisor->nombre} ({$duration} min).");
    }
}

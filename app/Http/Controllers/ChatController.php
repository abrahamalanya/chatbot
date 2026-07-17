<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Cliente;
use App\Models\Message;
use App\Services\AssignmentService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected WhatsappService $whatsapp;
    protected AssignmentService $assignment;

    public function __construct(WhatsappService $whatsapp, AssignmentService $assignment)
    {
        $this->whatsapp   = $whatsapp;
        $this->assignment = $assignment;
    }

    public function index(Request $request)
    {
        $advisor = auth()->user()->advisor;

        // Clientes únicos con info de la última asignación
        $latestIds = Assignment::where('advisor_id', $advisor->id)
            ->selectRaw('MAX(id) as id')
            ->groupBy('cliente_telefono')
            ->pluck('id');

        $latestAssignments = Assignment::whereIn('id', $latestIds)->get()->keyBy('cliente_telefono');

        $unreadCounts = Message::where('sender', 'cliente')
            ->whereNull('leido_at')
            ->whereIn('cliente_telefono', Assignment::where('advisor_id', $advisor->id)->pluck('cliente_telefono'))
            ->selectRaw('cliente_telefono, COUNT(*) as unread_count')
            ->groupBy('cliente_telefono')
            ->pluck('unread_count', 'cliente_telefono');

        $clientes = Assignment::where('advisor_id', $advisor->id)
            ->selectRaw('cliente_telefono, COUNT(*) as total_sesiones, MAX(created_at) as last_activity')
            ->groupBy('cliente_telefono')
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($c) use ($latestAssignments, $unreadCounts) {
                $c->latest        = $latestAssignments[$c->cliente_telefono] ?? null;
                $c->unread_count  = $unreadCounts[$c->cliente_telefono] ?? 0;
                return $c;
            });

        $clienteSeleccionado = $request->cliente;
        $mensajes            = [];
        $assignment          = null;
        $clienteRegistro     = null;

        if ($clienteSeleccionado) {
            $clienteRegistro = Cliente::where('cliente_telefono', $clienteSeleccionado)->first();

            $mensajes = Message::where('cliente_telefono', $clienteSeleccionado)
                ->orderBy('created_at')
                ->get();

            Message::where('cliente_telefono', $clienteSeleccionado)
                ->where('sender', 'cliente')
                ->whereNull('leido_at')
                ->update(['leido_at' => now()]);

            $assignment = Assignment::where('cliente_telefono', $clienteSeleccionado)
                ->where('advisor_id', $advisor->id)
                ->latest()
                ->first();

            // Auto-cierre lazy cuando el tiempo expiró
            if ($assignment
                && $assignment->status === Assignment::STATUS_ASSIGNED
                && $assignment->accepted_at !== null
                && $assignment->conversation_expires_at !== null
                && $assignment->conversation_expires_at->isPast()
            ) {
                $assignment->update([
                    'status'      => Assignment::STATUS_CLOSED,
                    'disposition' => 'tiempo_expirado',
                ]);
                $assignment->refresh();

                $this->despedirCliente($clienteSeleccionado, $advisor->id);
            }
        }

        return view('dashboard.chat', compact(
            'clientes', 'advisor', 'clienteSeleccionado', 'mensajes', 'assignment', 'clienteRegistro'
        ));
    }

    public function messages(Request $request)
    {
        Message::where('cliente_telefono', $request->cliente_telefono)
            ->where('sender', 'cliente')
            ->whereNull('leido_at')
            ->update(['leido_at' => now()]);

        $mensajes = Message::where('cliente_telefono', $request->cliente_telefono)
            ->orderBy('created_at')
            ->get();

        return response()->json($mensajes);
    }

    public function accept(Request $request)
    {
        $request->validate(['cliente_telefono' => 'required']);

        $advisor = auth()->user()->advisor;

        $assignment = Assignment::where('cliente_telefono', $request->cliente_telefono)
            ->where('advisor_id', $advisor->id)
            ->where('status', Assignment::STATUS_ASSIGNED)
            ->whereNull('accepted_at')
            ->latest()
            ->first();

        if (!$assignment) {
            return back()->with('error', 'No se encontró una asignación pendiente de aceptar.');
        }

        $this->assignment->acceptAssignment($assignment);

        return redirect()->route('chat.index', ['cliente' => $request->cliente_telefono])
            ->with('success', "Conversación aceptada. Tienes {$assignment->conversation_duration} minutos.");
    }

    public function extend(Request $request)
    {
        $request->validate([
            'cliente_telefono' => 'required',
            'minutos'          => 'nullable|integer|min:1|max:60',
        ]);

        $advisor = auth()->user()->advisor;

        $assignment = Assignment::where('cliente_telefono', $request->cliente_telefono)
            ->where('advisor_id', $advisor->id)
            ->where('status', Assignment::STATUS_ASSIGNED)
            ->whereNotNull('accepted_at')
            ->latest()
            ->first();

        if (!$assignment) {
            return back()->with('error', 'No se encontró una conversación activa para extender.');
        }

        $minutos = $request->input('minutos', 10);

        $this->assignment->extendConversation($assignment, $minutos);

        return back()->with('success', "Se agregaron {$minutos} minutos a la sesión.");
    }

    public function send(Request $request)
    {
        $request->validate([
            'cliente_telefono' => 'required',
            'mensaje'          => 'required|string|max:1000',
        ]);

        $advisor = auth()->user()->advisor;

        // Solo permitir envío si la conversación está activa
        $assignment = Assignment::where('cliente_telefono', $request->cliente_telefono)
            ->where('advisor_id', $advisor->id)
            ->where('status', Assignment::STATUS_ASSIGNED)
            ->whereNotNull('accepted_at')
            ->where('conversation_expires_at', '>', now())
            ->latest()
            ->first();

        if (!$assignment) {
            return back()->with('error', 'La sesión ha expirado o no está activa.');
        }

        Message::create([
            'cliente_telefono' => $request->cliente_telefono,
            'advisor_id'       => $advisor->id,
            'mensaje'          => $request->mensaje,
            'sender'           => 'asesor',
            'tipo'             => 'texto',
        ]);

        $this->whatsapp->send($request->cliente_telefono, $request->mensaje);

        return back();
    }

    public function close(Request $request)
    {
        $request->validate([
            'cliente_telefono' => 'required',
            'disposition'      => 'required|in:completado,no_interesado,sin_respuesta,no_califica,seguimiento',
        ]);

        $advisor = auth()->user()->advisor;

        Assignment::where('cliente_telefono', $request->cliente_telefono)
            ->where('advisor_id', $advisor->id)
            ->where('status', Assignment::STATUS_ASSIGNED)
            ->latest()
            ->first()
            ?->update([
                'status'      => Assignment::STATUS_CLOSED,
                'disposition' => $request->disposition,
            ]);

        Cliente::where('cliente_telefono', $request->cliente_telefono)
            ->update(['etapa' => $request->disposition]);

        $this->despedirCliente($request->cliente_telefono, $advisor->id);

        return redirect()->route('chat.index')
            ->with('success', 'Conversación cerrada correctamente.');
    }

    private function despedirCliente(string $clienteTelefono, int $advisorId): void
    {
        $mensaje = config('messages.despedida');

        Message::create([
            'cliente_telefono' => $clienteTelefono,
            'advisor_id'       => $advisorId,
            'mensaje'          => $mensaje,
            'sender'           => 'asesor',
            'tipo'             => 'texto',
        ]);

        $this->whatsapp->send($clienteTelefono, $mensaje);
    }
}

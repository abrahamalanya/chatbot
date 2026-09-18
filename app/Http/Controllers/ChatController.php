<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Cliente;
use App\Models\Message;
use App\Models\WhatsappNumber;
use App\Services\AssignmentService;
use App\Services\WhatsappService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $latestAssignments = Assignment::whereIn('id', $latestIds)->with('whatsappNumber')->get()->keyBy('cliente_telefono');

        $unreadCounts = Message::where('sender', 'cliente')
            ->whereNull('leido_at')
            ->whereIn('cliente_telefono', Assignment::where('advisor_id', $advisor->id)->pluck('cliente_telefono'))
            ->selectRaw('cliente_telefono, COUNT(*) as unread_count')
            ->groupBy('cliente_telefono')
            ->pluck('unread_count', 'cliente_telefono');

        $nombresRegistrados = Cliente::whereIn('cliente_telefono', Assignment::where('advisor_id', $advisor->id)->pluck('cliente_telefono'))
            ->whereNotNull('nombre')
            ->pluck('nombre', 'cliente_telefono');

        // Fecha del último mensaje real (no de la asignación): un cliente ya
        // atendido que vuelve a escribir semanas después no crea una nueva
        // asignación hasta que complete el menú del bot, así que ordenar por
        // la asignación lo dejaba "enterrado" en la lista pese a tener
        // mensajes nuevos sin leer.
        $ultimoMensajeAt = Message::whereIn('cliente_telefono', Assignment::where('advisor_id', $advisor->id)->pluck('cliente_telefono'))
            ->selectRaw('cliente_telefono, MAX(created_at) as ultimo_mensaje_at')
            ->groupBy('cliente_telefono')
            ->pluck('ultimo_mensaje_at', 'cliente_telefono');

        $clientes = Assignment::where('advisor_id', $advisor->id)
            ->selectRaw('cliente_telefono, COUNT(*) as total_sesiones, MAX(created_at) as last_activity')
            ->groupBy('cliente_telefono')
            ->get()
            ->map(function ($c) use ($latestAssignments, $unreadCounts, $nombresRegistrados, $ultimoMensajeAt) {
                $c->latest            = $latestAssignments[$c->cliente_telefono] ?? null;
                $c->unread_count      = $unreadCounts[$c->cliente_telefono] ?? 0;
                $c->nombre            = $nombresRegistrados[$c->cliente_telefono] ?? null;
                $c->pendiente_aceptar = $c->latest?->status === Assignment::STATUS_ASSIGNED && !$c->latest?->accepted_at;
                $c->tiene_no_leidos   = $c->unread_count > 0;
                $c->last_activity     = max($c->last_activity, $ultimoMensajeAt[$c->cliente_telefono] ?? $c->last_activity);
                return $c;
            })
            // Prioridad: 1) recién asignados sin aceptar (urgente por el
            // tiempo de espera), 2) cualquier cliente con mensajes sin leer
            // -incluidos los ya cerrados que vuelven a escribir-, 3) el resto
            // por actividad más reciente.
            ->sortBy([
                ['pendiente_aceptar', 'desc'],
                ['tiene_no_leidos', 'desc'],
                ['last_activity', 'desc'],
            ])
            ->values();

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
                ->with('whatsappNumber')
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

                $this->despedirCliente($clienteSeleccionado, $advisor->id, $assignment->whatsapp_number_id);
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

    public function reopen(Request $request)
    {
        $request->validate(['cliente_telefono' => 'required']);

        $advisor = auth()->user()->advisor;

        $previa = Assignment::where('cliente_telefono', $request->cliente_telefono)
            ->where('advisor_id', $advisor->id)
            ->with('whatsappNumber')
            ->latest()
            ->first();

        if (!$previa) {
            return back()->with('error', 'No se encontró una conversación previa con este cliente.');
        }

        if ($previa->isConversationActive()) {
            return redirect()->route('chat.index', ['cliente' => $request->cliente_telefono])
                ->with('error', 'La sesión con este cliente ya está activa.');
        }

        $nueva = $this->assignment->reopenAssignment($previa, $advisor);

        return redirect()->route('chat.index', ['cliente' => $request->cliente_telefono])
            ->with('success', "Sesión reabierta. Tienes {$nueva->conversation_duration} minutos.");
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
            $error = 'La sesión ha expirado o no está activa.';

            return $request->wantsJson() ? response()->json(['error' => $error], 422) : back()->with('error', $error);
        }

        $message = Message::create([
            'cliente_telefono'   => $request->cliente_telefono,
            'advisor_id'         => $advisor->id,
            'whatsapp_number_id' => $assignment->whatsapp_number_id,
            'mensaje'            => $request->mensaje,
            'sender'             => 'asesor',
            'tipo'               => 'texto',
        ]);

        try {
            $this->whatsapp->send($request->cliente_telefono, $request->mensaje, $assignment->whatsappNumber->phone_number_id);
        } catch (ConnectionException $e) {
            Log::warning('Timeout al enviar mensaje de WhatsApp desde el chat', [
                'cliente_telefono' => $request->cliente_telefono,
                'message_id'       => $message->id,
            ]);

            $error = 'El mensaje quedó registrado pero WhatsApp no respondió a tiempo. Puede que no le haya llegado al cliente.';

            return $request->wantsJson() ? response()->json(['error' => $error, 'message' => $message], 502) : back()->with('error', $error);
        }

        return $request->wantsJson() ? response()->json(['message' => $message]) : back();
    }

    public function close(Request $request)
    {
        $request->validate([
            'cliente_telefono' => 'required',
            'disposition'      => 'required|in:completado,no_interesado,sin_respuesta,no_califica,seguimiento',
        ]);

        $advisor = auth()->user()->advisor;

        $assignment = Assignment::where('cliente_telefono', $request->cliente_telefono)
            ->where('advisor_id', $advisor->id)
            ->where('status', Assignment::STATUS_ASSIGNED)
            ->latest()
            ->first();

        $assignment?->update([
            'status'      => Assignment::STATUS_CLOSED,
            'disposition' => $request->disposition,
        ]);

        Cliente::where('cliente_telefono', $request->cliente_telefono)
            ->update(['etapa' => $request->disposition]);

        $this->despedirCliente($request->cliente_telefono, $advisor->id, $assignment?->whatsapp_number_id);

        return redirect()->route('chat.index')
            ->with('success', 'Conversación cerrada correctamente.');
    }

    private function despedirCliente(string $clienteTelefono, int $advisorId, ?int $whatsappNumberId): void
    {
        if (!$whatsappNumberId) {
            Log::warning('No se pudo enviar despedida: assignment sin whatsapp_number_id', ['cliente_telefono' => $clienteTelefono]);
            return;
        }

        $mensaje = config('messages.despedida');

        Message::create([
            'cliente_telefono'   => $clienteTelefono,
            'advisor_id'         => $advisorId,
            'whatsapp_number_id' => $whatsappNumberId,
            'mensaje'            => $mensaje,
            'sender'             => 'asesor',
            'tipo'               => 'texto',
        ]);

        $this->whatsapp->send($clienteTelefono, $mensaje, WhatsappNumber::find($whatsappNumberId)->phone_number_id);
    }
}

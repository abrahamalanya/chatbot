<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\Message;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsappService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function index(Request $request)
    {
        $asesorTelefono = $request->asesor;

        // buscar asesor
        $advisor = Advisor::where('telefono', $asesorTelefono)->first();

        // obtener clientes asignados
        $clientes = Assignment::where('advisor_id', $advisor->id)->get();

        // cliente seleccionado
        $clienteSeleccionado = $request->cliente;

        $mensajes = [];

        if ($clienteSeleccionado) {
            $mensajes = Message::where('cliente_telefono', $clienteSeleccionado)
                ->orderBy('created_at')
                ->get();
        }

        return view('chat', compact(
            'clientes',
            'asesorTelefono',
            'clienteSeleccionado',
            'mensajes'
        ));
    }

    public function messages(Request $request)
    {
        $telefono = $request->cliente_telefono;

        $mensajes = Message::where('cliente_telefono', $telefono)
            ->orderBy('created_at')
            ->get();

        return response()->json($mensajes);
    }

    public function send(Request $request)
    {
        $request->validate([
            'cliente_telefono' => 'required',
            'mensaje' => 'required'
        ]);

        $telefono = $request->cliente_telefono;

        // buscar asignación
        $assignment = Assignment::where('cliente_telefono', $telefono)->first();

        // guardar mensaje
        Message::create([
            'cliente_telefono' => $telefono,
            'advisor_id' => $assignment->advisor_id,
            'mensaje' => $request->mensaje,
            'sender' => 'asesor'
        ]);

        // enviar mensaje al cliente
        $this->whatsapp->send($telefono, $request->mensaje);

        return back()->with('success', true);
    }
}

<?php

namespace App\Http\Controllers;

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
        $advisor = auth()->user()->advisor;
        
        $clientes = Assignment::where('advisor_id', $advisor->id)->get();

        $clienteSeleccionado = $request->cliente;

        $mensajes = [];
        if ($clienteSeleccionado) {
            $mensajes = Message::where('cliente_telefono', $clienteSeleccionado)
                ->orderBy('created_at')
                ->get();
        }

        return view('dashboard.chat', compact(
            'clientes',
            'advisor',
            'clienteSeleccionado',
            'mensajes'
        ));
    }

    public function messages(Request $request)
    {
        $mensajes = Message::where('cliente_telefono', $request->cliente_telefono)
            ->orderBy('created_at')
            ->get();

        return response()->json($mensajes);
    }

    public function send(Request $request)
    {
        $request->validate([
            'cliente_telefono' => 'required',
            'mensaje'          => 'required',
        ]);

        $advisor = auth()->user()->advisor;

        Message::create([
            'cliente_telefono' => $request->cliente_telefono,
            'advisor_id'       => $advisor->id,
            'mensaje'          => $request->mensaje,
            'sender'           => 'asesor',
        ]);

        $this->whatsapp->send($request->cliente_telefono, $request->mensaje);

        return back()->with('success', 'Mensaje enviado.');
    }
}

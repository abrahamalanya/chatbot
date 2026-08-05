<?php

namespace App\Http\Controllers;

use App\Models\WhatsappNumber;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected ChatbotService $chatbot;

    public function __construct(ChatbotService $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function verify(Request $request)
    {
        $verify_token = config('services.whatsapp.verify_token');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verify_token) {
            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        $data = $request->all();

        if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            return response()->json(['status' => 'no message'], 200);
        }

        $value          = $data['entry'][0]['changes'][0]['value'];
        $message        = $value['messages'][0];
        $phoneNumberId  = $value['metadata']['phone_number_id'] ?? null;

        $whatsappNumber = $phoneNumberId
            ? WhatsappNumber::where('phone_number_id', $phoneNumberId)->where('activo', true)->first()
            : null;

        if (!$whatsappNumber) {
            Log::warning('Mensaje de WhatsApp recibido en un número no registrado', [
                'phone_number_id'       => $phoneNumberId,
                'display_phone_number'  => $value['metadata']['display_phone_number'] ?? null,
                'from'                  => $message['from'] ?? null,
            ]);

            return response()->json(['status' => 'unregistered number'], 200);
        }

        $this->chatbot->handle($message, $whatsappNumber);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
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

        // // Para debug
        // Log::info('Webhook recibido:', $data);
    
        // // Validar estructura
        $message = '';
        if (
            isset($data['entry'][0]['changes'][0]['value']['messages'][0]) &&
            $data['entry'][0]['changes'][0]['value']['messages'][0]['type'] === 'text'
        ) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];

            $from = $message['from'];
            $text = $message['text']['body'] ?? '';

            $this->replyMessage($from, "Recibí: " . $text);
        }

        return response()->json([
            'status' => 'ok',
            'message' => $message,
        ], 200);
    }

    public function replyMessage($to, $message)
    {
        $token = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');

        $url = "https://graph.facebook.com/v19.0/{$phone_number_id}/messages";

        $data = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "text",
            "text" => [
                "body" => $message
            ]
        ];

        $response = Http::withToken($token)->post($url, $data);

        // 🔥 IMPORTANTE
        Log::info('Respuesta de Meta:', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
    }
}

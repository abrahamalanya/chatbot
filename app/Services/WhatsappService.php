<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function send($to, $message)
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

        if (!$response->successful()) {
            // Log::error('Error al enviar mensaje:', ['response' => $response->body()]); --- IGNORE ---
        }
    }
}

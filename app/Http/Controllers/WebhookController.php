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

        // Log::info('Webhook recibido:', $data);

        if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            return response()->json(['status' => 'no message'], 200);
        }

        $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
        $from = $message['from'];

        // Validar estructura
        if ($message['type'] === 'text') {
            $text = strtolower($message['text']['body']);
            $this->sendMenu($from);
        } elseif ($message['type'] === 'interactive') {
            $reply = $message['interactive']['button_reply']['id'] ?? null;

            if ($reply === 'credito_hipotecario') {
                $this->replyTextCredit($from, "Requisitos:\n" . config('messages.creditos.hipotecario.requisitos'));
            } elseif ($reply === 'credito_vehicular') {
                $this->replyTextCredit($from, "Requisitos:\n" . config('messages.creditos.vehicular.requisitos'));
            } elseif ($reply === 'credito_diario') {
                $this->replyTextCredit($from, "Requisitos:\n" . config('messages.creditos.diario.requisitos'));
            } elseif ($reply === 'asesor') {
                $this->replyText($from, "En breve un asesor se pondrá en contacto contigo.");
            } elseif ($reply === 'menu') {
                $this->sendMenu($from);
            } elseif ($reply === 'salir') {
                $this->replyText($from, "Gracias por contactarnos, si necesitas algo más no dudes en escribirnos. ¡Hasta luego! 👋");
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /*
    public function replyMessage($to, $message)
    {
        $token = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');

        $url = "https://graph.facebook.com/v19.0/{$phone_number_id}/messages";

        $data = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "interactive",
            "interactive" => [
                "type" => "button",
                "body" => [
                    "text" => "Hola Somos CrediMás👋 ¿En qué podemos ayudarte?"
                ],
                "action" => [
                    "buttons" => [
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "productos",
                                "title" => "Ver productos"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "asesor",
                                "title" => "Hablar con asesor"
                            ]
                        ]
                    ]
                ]
                // "body" => $message
            ]
        ];

        $response = Http::withToken($token)->post($url, $data);

        // 🔥 IMPORTANTE
        Log::info('Respuesta de Meta:', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
    }
    */

    public function sendMenu($to)
    {
        $token = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');

        $url = "https://graph.facebook.com/v19.0/{$phone_number_id}/messages";

        $data = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "interactive",
            "interactive" => [
                "type" => "button",
                "body" => [
                    "text" => config('messages.bienvenida')
                ],
                "action" => [
                    "buttons" => [
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "credito_hipotecario",
                                "title" => "CRÉDITO HIPOTECARIO"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "credito_vehicular",
                                "title" => "EMPEÑO VEHICULAR"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "credito_diario",
                                "title" => "CREDITOS DIARIOS"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::withToken($token)->post($url, $data);
    }

    public function replyTextCredit($to, $message)
    {
        $token = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');

        $url = "https://graph.facebook.com/v19.0/{$phone_number_id}/messages";

        $data = [
            "messaging_product" => "whatsapp",
            "to" => $to,
            "type" => "interactive",
            "interactive" => [
                "type" => "button",
                "body" => [
                    "text" => $message
                ],
                "action" => [
                    "buttons" => [
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "asesor",
                                "title" => "CONTACTAR ASESOR"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "menu",
                                "title" => "VOLVER AL MENÚ"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "salir",
                                "title" => "SALIR"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::withToken($token)->post($url, $data);
    }

    public function replyText($to, $message)
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

        Http::withToken($token)->post($url, $data);
    }
}

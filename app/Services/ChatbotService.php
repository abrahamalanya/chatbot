<?php

namespace App\Services;

use App\Models\Assignment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected $assignment;
    protected $whatsapp;

    public function __construct(AssignmentService $assignment, WhatsappService $whatsapp)
    {
        $this->assignment = $assignment;
        $this->whatsapp = $whatsapp;
    }

    public function handle($message)
    {
        $from = $message['from'];

        if ($message['type'] === 'text') {
            $text = strtolower($message['text']['body']);
            
            $assignment = Assignment::where('cliente_telefono', $from)->first();
            if ($assignment) {
                $this->whatsapp->send(
                    $assignment->advisor->telefono,
                    $text
                );
                return;
            }

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
                $this->assignment->assign($from);
            } elseif ($reply === 'menu') {
                $this->sendMenu($from);
            } elseif ($reply === 'salir') {
                $this->replyText($from, "Gracias por contactarnos, si necesitas algo más no dudes en escribirnos. ¡Hasta luego! 👋");
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

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

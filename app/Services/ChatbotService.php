<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Message;
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
                /*$this->whatsapp->send(
                    $assignment->advisor->telefono,
                    $text
                );
                return;*/
                Message::create([
                    'cliente_telefono' => $from,
                    'advisor_id' => $assignment->advisor_id,
                    'mensaje' => $text,
                    'sender' => 'cliente'
                ]);
            }

            $this->sendMenu($from);
        } elseif ($message['type'] === 'interactive') {
            $reply = $message['interactive']['button_reply']['id'] ?? null;

            if ($reply === 'credito_hipotecario') {
                $this->replyTextCredit($from, "Requisitos:\n" . config('messages.creditos.hipotecario.requisitos'));
            } elseif ($reply === 'credito_vehicular') {
                $this->replyTextCredit($from, "Requisitos:\n" . config('messages.creditos.vehicular.requisitos'));
            } elseif ($reply === 'credito_diario') {
                $this->replyTextCreditDiario($from, "¿Tiene negocio?");
            } elseif ($reply === 'negocio_true') {
                $this->replyTextCreditDiarioNegocio($from, "¿Qué tipo de negocio tiene?");
            } elseif ($reply === 'negocio_false') {
                $this->replyTextCreditDiarioTrabajador($from, "¿Qué tipo de trabajador eres?");
            } elseif ($reply === 'abarrotes'
                || $reply === 'venta_ropa_calzado'
                || $reply === 'tecnologia'
                || $reply === 'otro'
                || $reply === 'trabajador_dependiente'
                || $reply === 'trabajador_independiente') {
                $this->replyTextCreditDiarioVivienda($from, "¿Qué tipo de vivienda tiene?");
            } elseif ($reply === 'vivienda_propia'
                || $reply === 'vivienda_alquilada'
                || $reply === 'vivienda_familiar'
                || $reply === 'vivienda_otro') {
                $this->replyTextCreditDiarioPrestamo($from, "¿Cuánto necesita de préstamo?");
            } elseif ($reply === 'prestamo_300_500'
                || $reply === 'prestamo_500_1000'
                || $reply === 'prestamo_1000_mas') {
                // $this->replyText($from, "Ingrese su DNI para que un asesor se contacte contigo.");
                $this->replyText($from, "Gracias por tu información.
Un asesor de CREDIMAS evaluará tu
solicitud 
de crédito con garantía
hipotecaria y se comunicará contigo
dentro de nuestro horario de atención.
🕘 Horario de atención: 8:00 am - 6:00 pm
");
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

    // Crédito Diario
    public function replyTextCreditDiario($to, $message)
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
                                "id" => "negocio_true",
                                "title" => "SI TENGO NEGOCIO"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "negocio_false",
                                "title" => "NO TENGO NEGOCIO"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::withToken($token)->post($url, $data);
    }

    public function replyTextCreditDiarioNegocio($to, $message)
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
                                "id" => "abarrotes",
                                "title" => "ABARROTES / TIENDA"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "venta_ropa_calzado",
                                "title" => "VENTA ROPA O CALZADO"
                            ]
                        ],
                        // [
                        //     "type" => "reply",
                        //     "reply" => [
                        //         "id" => "tecnologia",
                        //         "title" => "TECNOLOGÍA"
                        //     ]
                        // ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "otro",
                                "title" => "OTRO"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::withToken($token)->post($url, $data);
    }

    public function replyTextCreditDiarioTrabajador($to, $message)
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
                                "id" => "trabajador_dependiente",
                                "title" => "DEPENDIENTE"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "trabajador_independiente",
                                "title" => "INDEPENDIENTE"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::withToken($token)->post($url, $data);
    }

    public function replyTextCreditDiarioVivienda($to, $message)
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
                                "id" => "vivienda_propia",
                                "title" => "PROPIA"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "vivienda_familiar",
                                "title" => "FAMILIAR"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "vivienda_alquilada",
                                "title" => "ALQUILADA"
                            ]
                        ],
                        // [
                        //     "type" => "reply",
                        //     "reply" => [
                        //         "id" => "vivienda_otro",
                        //         "title" => "OTRO"
                        //     ]
                        // ]
                    ]
                ]
            ]
        ];

        Http::withToken($token)->post($url, $data);
    }

    public function replyTextCreditDiarioPrestamo($to, $message)
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
                                "id" => "prestamo_300_500",
                                "title" => "300-500"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "prestamo_500_1000",
                                "title" => "500-1000"
                            ]
                        ],
                        [
                            "type" => "reply",
                            "reply" => [
                                "id" => "prestamo_1000_mas",
                                "title" => "1000-más"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Http::withToken($token)->post($url, $data);
    }
}

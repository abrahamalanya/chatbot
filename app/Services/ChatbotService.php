<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Message;
use Illuminate\Support\Facades\Http;

class ChatbotService
{
    protected AssignmentService $assignment;
    protected WhatsappService $whatsapp;

    // Etiquetas legibles de cada botón para el historial del asesor
    private const OPCIONES_LABELS = [
        'credito_hipotecario'     => 'Crédito Hipotecario',
        'credito_vehicular'       => 'Empeño Vehicular',
        'credito_diario'          => 'Créditos Diarios',
        'negocio_true'            => 'Tiene negocio: Sí',
        'negocio_false'           => 'Tiene negocio: No',
        'abarrotes'               => 'Negocio: Abarrotes / Tienda',
        'venta_ropa_calzado'      => 'Negocio: Venta de ropa o calzado',
        'tecnologia'              => 'Negocio: Tecnología',
        'otro'                    => 'Negocio: Otro',
        'trabajador_dependiente'  => 'Trabajador: Dependiente',
        'trabajador_independiente'=> 'Trabajador: Independiente',
        'vivienda_propia'         => 'Vivienda: Propia',
        'vivienda_alquilada'      => 'Vivienda: Alquilada',
        'vivienda_familiar'       => 'Vivienda: Familiar',
        'vivienda_otro'           => 'Vivienda: Otro',
        'prestamo_300_500'        => 'Monto requerido: S/ 300 - 500',
        'prestamo_500_1000'       => 'Monto requerido: S/ 500 - 1,000',
        'prestamo_1000_mas'       => 'Monto requerido: S/ 1,000 a más',
        'asesor'                  => 'Solicitó hablar con un asesor',
        'menu'                    => 'Volvió al menú',
        'salir'                   => 'Salió del chat',
    ];

    public function __construct(AssignmentService $assignment, WhatsappService $whatsapp)
    {
        $this->assignment = $assignment;
        $this->whatsapp   = $whatsapp;
    }

    public function handle($message)
    {
        $from = $message['from'];

        $assignment = Assignment::where('cliente_telefono', $from)
            ->whereIn('status', [Assignment::STATUS_PENDING, Assignment::STATUS_ASSIGNED])
            ->latest()
            ->first();

        // Cliente en ventana de conversación activa → modo conversación directa
        if ($assignment && $assignment->isConversationActive()) {
            $this->saveMessage($from, $assignment->advisor_id, $message);
            return;
        }

        if ($message['type'] === 'text') {
            // Guardar mensaje de texto aunque esté en pending
            if ($assignment) {
                $this->saveTextMessage($from, $assignment->advisor_id ?? null, $message['text']['body']);
            }
            $this->sendMenu($from);

        } elseif ($message['type'] === 'interactive') {
            $reply = $message['interactive']['button_reply']['id'] ?? null;
            if (!$reply) return;

            // Guardar la opción seleccionada como historial
            $this->saveOpcion($from, $assignment->advisor_id ?? null, $reply);

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

            } elseif (in_array($reply, ['abarrotes', 'venta_ropa_calzado', 'tecnologia', 'otro', 'trabajador_dependiente', 'trabajador_independiente'])) {
                $this->replyTextCreditDiarioVivienda($from, "¿Qué tipo de vivienda tiene?");

            } elseif (in_array($reply, ['vivienda_propia', 'vivienda_alquilada', 'vivienda_familiar', 'vivienda_otro'])) {
                $this->replyTextCreditDiarioPrestamo($from, "¿Cuánto necesita de préstamo?");

            } elseif (in_array($reply, ['prestamo_300_500', 'prestamo_500_1000', 'prestamo_1000_mas'])) {
                $this->replyText($from, "Gracias por tu información. Un asesor de CREDIMAS evaluará tu solicitud y se comunicará contigo dentro de nuestro horario de atención.\n🕘 Horario: 8:00 am - 6:00 pm");
                $this->assignment->requestAdvisor($from);

            } elseif ($reply === 'asesor') {
                $this->replyText($from, "Hemos recibido tu solicitud. Un asesor de CREDIMAS se pondrá en contacto contigo pronto. 🙏");
                $this->assignment->requestAdvisor($from);

            } elseif ($reply === 'menu') {
                $this->sendMenu($from);

            } elseif ($reply === 'salir') {
                $this->replyText($from, config('messages.despedida'));
            }
        }
    }

    // ── Helpers para guardar mensajes ──────────────────────────────────────

    private function saveMessage(string $from, ?int $advisorId, array $message): void
    {
        if ($message['type'] === 'text') {
            $this->saveTextMessage($from, $advisorId, $message['text']['body']);
        } elseif ($message['type'] === 'interactive') {
            $reply = $message['interactive']['button_reply']['id'] ?? null;
            if ($reply) {
                $this->saveOpcion($from, $advisorId, $reply);
            }
        }
    }

    private function saveTextMessage(string $from, ?int $advisorId, string $texto): void
    {
        Message::create([
            'cliente_telefono' => $from,
            'advisor_id'       => $advisorId,
            'mensaje'          => $texto,
            'sender'           => 'cliente',
            'tipo'             => 'texto',
        ]);
    }

    private function saveOpcion(string $from, ?int $advisorId, string $replyId): void
    {
        $label = self::OPCIONES_LABELS[$replyId] ?? $replyId;

        Message::create([
            'cliente_telefono' => $from,
            'advisor_id'       => $advisorId,
            'mensaje'          => $label,
            'sender'           => 'cliente',
            'tipo'             => 'opcion',
        ]);
    }

    // ── Mensajes de WhatsApp ───────────────────────────────────────────────

    public function sendMenu($to)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'button',
                'body' => ['text' => config('messages.bienvenida')],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'credito_hipotecario', 'title' => 'CRÉDITO HIPOTECARIO']],
                        ['type' => 'reply', 'reply' => ['id' => 'credito_vehicular',   'title' => 'EMPEÑO VEHICULAR']],
                        ['type' => 'reply', 'reply' => ['id' => 'credito_diario',       'title' => 'CREDITOS DIARIOS']],
                    ],
                ],
            ],
        ]);
    }

    public function replyText($to, $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'   => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    public function replyTextCredit($to, $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'          => $to,
            'type'        => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'asesor', 'title' => 'CONTACTAR ASESOR']],
                        ['type' => 'reply', 'reply' => ['id' => 'menu',   'title' => 'VOLVER AL MENÚ']],
                        ['type' => 'reply', 'reply' => ['id' => 'salir',  'title' => 'SALIR']],
                    ],
                ],
            ],
        ]);
    }

    public function replyTextCreditDiario($to, $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'          => $to,
            'type'        => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'negocio_true',  'title' => 'SI TENGO NEGOCIO']],
                        ['type' => 'reply', 'reply' => ['id' => 'negocio_false', 'title' => 'NO TENGO NEGOCIO']],
                    ],
                ],
            ],
        ]);
    }

    public function replyTextCreditDiarioNegocio($to, $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'          => $to,
            'type'        => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'abarrotes',        'title' => 'ABARROTES / TIENDA']],
                        ['type' => 'reply', 'reply' => ['id' => 'venta_ropa_calzado','title' => 'VENTA ROPA O CALZADO']],
                        ['type' => 'reply', 'reply' => ['id' => 'otro',             'title' => 'OTRO']],
                    ],
                ],
            ],
        ]);
    }

    public function replyTextCreditDiarioTrabajador($to, $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'          => $to,
            'type'        => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'trabajador_dependiente',   'title' => 'DEPENDIENTE']],
                        ['type' => 'reply', 'reply' => ['id' => 'trabajador_independiente', 'title' => 'INDEPENDIENTE']],
                    ],
                ],
            ],
        ]);
    }

    public function replyTextCreditDiarioVivienda($to, $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'          => $to,
            'type'        => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'vivienda_propia',   'title' => 'PROPIA']],
                        ['type' => 'reply', 'reply' => ['id' => 'vivienda_familiar', 'title' => 'FAMILIAR']],
                        ['type' => 'reply', 'reply' => ['id' => 'vivienda_alquilada','title' => 'ALQUILADA']],
                    ],
                ],
            ],
        ]);
    }

    public function replyTextCreditDiarioPrestamo($to, $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to'          => $to,
            'type'        => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'prestamo_300_500',  'title' => '300-500']],
                        ['type' => 'reply', 'reply' => ['id' => 'prestamo_500_1000', 'title' => '500-1000']],
                        ['type' => 'reply', 'reply' => ['id' => 'prestamo_1000_mas', 'title' => '1000-más']],
                    ],
                ],
            ],
        ]);
    }
}

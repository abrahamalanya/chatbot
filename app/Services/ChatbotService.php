<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Message;
use App\Models\WhatsappNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        'espera_seguir'           => 'Eligió seguir esperando a un asesor',
        'espera_mensaje'          => 'Prefirió dejar su consulta por escrito',
        'espera_cancelar'         => 'Canceló la espera de un asesor',
    ];

    public function __construct(AssignmentService $assignment, WhatsappService $whatsapp)
    {
        $this->assignment = $assignment;
        $this->whatsapp   = $whatsapp;
    }

    public function handle($message, WhatsappNumber $whatsappNumber)
    {
        // Meta reintenta el webhook si la respuesta tarda o falla; sin este
        // chequeo se duplicarían asignaciones, avisos al asesor y templates.
        $wamid = $message['id'] ?? null;

        if ($wamid && Message::where('wamid', $wamid)->exists()) {
            return;
        }

        $from = $message['from'];

        $assignment = Assignment::where('cliente_telefono', $from)
            ->whereIn('status', [Assignment::STATUS_PENDING, Assignment::STATUS_ASSIGNED])
            ->latest()
            ->first();

        // Mantener la conversación "atada" a la línea por la que el cliente
        // escribió por última vez, para que las respuestas salgan correctas.
        if ($assignment && $assignment->whatsapp_number_id !== $whatsappNumber->id) {
            $assignment->update(['whatsapp_number_id' => $whatsappNumber->id]);
        }

        // Cliente en ventana de conversación activa → modo conversación directa
        if ($assignment && $assignment->isConversationActive()) {
            $this->saveMessage($from, $assignment->advisor_id, $whatsappNumber, $message);
            return;
        }

        if ($message['type'] === 'text') {
            // Guardar el mensaje siempre (incluso el primer contacto, sin
            // assignment todavía) para que el asesor vea el historial completo.
            $this->saveTextMessage($from, $assignment?->advisor_id, $whatsappNumber, $message['text']['body'], $wamid);

            $this->responderSinConversacionActiva($from, $whatsappNumber, $assignment);

        } elseif (in_array($message['type'], ['image', 'document', 'location', 'video', 'audio'])) {
            // Guardar media/ubicación siempre, mismo motivo que el texto arriba.
            $this->saveMediaMessage($from, $assignment?->advisor_id, $whatsappNumber, $message);

            $this->responderSinConversacionActiva($from, $whatsappNumber, $assignment);

        } elseif ($message['type'] === 'interactive') {
            $reply = $message['interactive']['button_reply']['id'] ?? null;
            if (!$reply) return;

            // Guardar la opción seleccionada como historial
            $this->saveOpcion($from, $assignment?->advisor_id, $whatsappNumber, $reply, $wamid);

            if ($reply === 'credito_hipotecario') {
                $this->replyTextCredit($from, $whatsappNumber, "Requisitos:\n" . config('messages.creditos.hipotecario.requisitos'));

            } elseif ($reply === 'credito_vehicular') {
                $this->replyTextCredit($from, $whatsappNumber, "Requisitos:\n" . config('messages.creditos.vehicular.requisitos'));

            } elseif ($reply === 'credito_diario') {
                $this->replyTextCreditDiario($from, $whatsappNumber, "¿Tiene negocio?");

            } elseif ($reply === 'negocio_true') {
                $this->replyTextCreditDiarioNegocio($from, $whatsappNumber, "¿Qué tipo de negocio tiene?");

            } elseif ($reply === 'negocio_false') {
                $this->replyTextCreditDiarioTrabajador($from, $whatsappNumber, "¿Qué tipo de trabajador eres?");

            } elseif (in_array($reply, ['abarrotes', 'venta_ropa_calzado', 'tecnologia', 'otro', 'trabajador_dependiente', 'trabajador_independiente'])) {
                $this->replyTextCreditDiarioVivienda($from, $whatsappNumber, "¿Qué tipo de vivienda tiene?");

            } elseif (in_array($reply, ['vivienda_propia', 'vivienda_alquilada', 'vivienda_familiar', 'vivienda_otro'])) {
                $this->replyTextCreditDiarioPrestamo($from, $whatsappNumber, "¿Cuánto necesita de préstamo?");

            } elseif (in_array($reply, ['prestamo_300_500', 'prestamo_500_1000', 'prestamo_1000_mas'])) {
                $this->replyText($from, $whatsappNumber, "Gracias por tu información. Un asesor de CREDIMAS evaluará tu solicitud y se comunicará contigo dentro de nuestro horario de atención.\n🕘 Horario: 8:00 am - 6:00 pm");
                $this->assignment->requestAdvisor($from, $whatsappNumber);

            } elseif ($reply === 'asesor') {
                $this->replyText($from, $whatsappNumber, "Hemos recibido tu solicitud. Un asesor de CREDIMAS se pondrá en contacto contigo pronto. 🙏");
                $this->assignment->requestAdvisor($from, $whatsappNumber);

            } elseif ($reply === 'menu') {
                $this->sendMenu($from, $whatsappNumber);

            } elseif ($reply === 'salir') {
                $this->replyText($from, $whatsappNumber, config('messages.despedida'));

            } elseif ($reply === 'espera_seguir') {
                $this->replyText($from, $whatsappNumber, 'Perfecto, seguimos buscando un asesor disponible para ti. Gracias por tu paciencia. 🙏');
                $assignment?->update(['warning_sent_at' => now()]);

            } elseif ($reply === 'espera_mensaje') {
                $this->replyText($from, $whatsappNumber, 'Cuéntanos en un solo mensaje qué necesitas y un asesor te escribirá apenas esté disponible.');
                $assignment?->update(['esperando_nota' => true]);

            } elseif ($reply === 'espera_cancelar') {
                $assignment?->update([
                    'status'      => Assignment::STATUS_CLOSED,
                    'disposition' => 'sin_respuesta',
                ]);
                $this->replyText($from, $whatsappNumber, 'De acuerdo, hemos cancelado tu solicitud. Cuando quieras puedes volver a escribirnos. 🙏');
            }
        }
    }

    // Cliente sin ventana de conversación activa que escribe texto/media:
    // si está en cola esperando asesor no debe volver a ver el menú
    // principal (se re-solicitaría un asesor sin necesidad), así que se le
    // recuerdan sus opciones de espera. Si no está en cola, es un cliente
    // nuevo o cerrado y le corresponde el menú normal.
    private function responderSinConversacionActiva(string $from, WhatsappNumber $whatsappNumber, ?Assignment $assignment): void
    {
        if ($assignment) {
            if ($this->cerrarSiEraNotaDeEspera($from, $whatsappNumber, $assignment)) {
                return;
            }

            if ($assignment->status === Assignment::STATUS_PENDING) {
                $this->avisarClienteEnEspera($assignment);
                return;
            }
        }

        $this->sendMenu($from, $whatsappNumber);
    }

    // Repite los botones de espera (SEGUIR ESPERANDO / DEJAR CONSULTA /
    // CANCELAR) en vez de floodear al cliente si escribe varias veces
    // seguidas — se reusa warning_sent_at, el mismo campo que ya usa el
    // recordatorio programado (assignments:check-waiting), para que ambos
    // caminos no dupliquen avisos entre sí.
    private const ESPERA_REENVIO_MINUTOS = 2;

    private function avisarClienteEnEspera(Assignment $assignment): void
    {
        $avisadoRecientemente = $assignment->warning_sent_at
            && $assignment->warning_sent_at->diffInMinutes(now()) < self::ESPERA_REENVIO_MINUTOS;

        if ($avisadoRecientemente) {
            return;
        }

        $this->sendEsperaOpciones($assignment->cliente_telefono, $assignment->whatsappNumber);

        $assignment->update([
            'warning_sent_at' => now(),
            'warning_count'   => $assignment->warning_count + 1,
        ]);
    }

    // Cliente eligió "dejar mensaje" mientras esperaba asesor: el mensaje que
    // acaba de guardarse (texto, imagen, documento, etc.) es su consulta.
    private function cerrarSiEraNotaDeEspera(string $from, WhatsappNumber $whatsappNumber, Assignment $assignment): bool
    {
        if ($assignment->status !== Assignment::STATUS_PENDING || !$assignment->esperando_nota) {
            return false;
        }

        // Se mantiene en pending (no pasa a historial) para que siga apareciendo
        // en "Clientes en espera" y se le pueda asignar un asesor normalmente.
        $assignment->update([
            'esperando_nota'  => false,
            'nota_dejada'     => true,
            'warning_sent_at' => now(),
        ]);

        $this->replyText($from, $whatsappNumber, '¡Gracias! Hemos registrado tu consulta. Un asesor de CREDIMAS te escribirá en cuanto esté disponible.');

        return true;
    }

    // ── Helpers para guardar mensajes ──────────────────────────────────────

    private function saveMessage(string $from, ?int $advisorId, WhatsappNumber $whatsappNumber, array $message): void
    {
        $wamid = $message['id'] ?? null;

        if ($message['type'] === 'text') {
            $this->saveTextMessage($from, $advisorId, $whatsappNumber, $message['text']['body'], $wamid);
        } elseif ($message['type'] === 'interactive') {
            $reply = $message['interactive']['button_reply']['id'] ?? null;
            if ($reply) {
                $this->saveOpcion($from, $advisorId, $whatsappNumber, $reply, $wamid);
            }
        } elseif (in_array($message['type'], ['image', 'document', 'location', 'video', 'audio'])) {
            $this->saveMediaMessage($from, $advisorId, $whatsappNumber, $message);
        }
    }

    private function saveTextMessage(string $from, ?int $advisorId, WhatsappNumber $whatsappNumber, string $texto, ?string $wamid = null): void
    {
        Message::create([
            'wamid'              => $wamid,
            'cliente_telefono'   => $from,
            'advisor_id'         => $advisorId,
            'whatsapp_number_id' => $whatsappNumber->id,
            'mensaje'            => $texto,
            'sender'             => 'cliente',
            'tipo'               => 'texto',
        ]);
    }

    private function saveOpcion(string $from, ?int $advisorId, WhatsappNumber $whatsappNumber, string $replyId, ?string $wamid = null): void
    {
        $label = self::OPCIONES_LABELS[$replyId] ?? $replyId;

        Message::create([
            'wamid'              => $wamid,
            'cliente_telefono'   => $from,
            'advisor_id'         => $advisorId,
            'whatsapp_number_id' => $whatsappNumber->id,
            'mensaje'            => $label,
            'sender'             => 'cliente',
            'tipo'               => 'opcion',
        ]);
    }

    private function saveMediaMessage(string $from, ?int $advisorId, WhatsappNumber $whatsappNumber, array $message): void
    {
        $wamid = $message['id'] ?? null;

        if ($message['type'] === 'location') {
            $location = $message['location'];

            Message::create([
                'wamid'              => $wamid,
                'cliente_telefono'   => $from,
                'advisor_id'         => $advisorId,
                'whatsapp_number_id' => $whatsappNumber->id,
                'mensaje'            => $location['name'] ?? $location['address'] ?? 'Ubicación compartida',
                'sender'             => 'cliente',
                'tipo'               => 'ubicacion',
                'latitude'           => $location['latitude'],
                'longitude'          => $location['longitude'],
            ]);
            return;
        }

        $type    = $message['type']; // image | document | video | audio
        $mediaId = $message[$type]['id'] ?? null;

        if (!$mediaId) {
            return;
        }

        $tipos       = ['image' => 'imagen', 'document' => 'documento', 'video' => 'video', 'audio' => 'audio'];
        $etiquetas   = ['image' => '📷 Imagen', 'document' => '📄 Documento', 'video' => '🎥 Video', 'audio' => '🎧 Audio'];

        $media = $this->whatsapp->downloadMedia($mediaId);

        Message::create([
            'wamid'              => $wamid,
            'cliente_telefono'   => $from,
            'advisor_id'         => $advisorId,
            'whatsapp_number_id' => $whatsappNumber->id,
            'mensaje'            => $message[$type]['caption'] ?? $etiquetas[$type],
            'sender'             => 'cliente',
            'tipo'               => $tipos[$type],
            'media_path'         => $media['path'] ?? null,
            'media_mime_type'    => $media['mime_type'] ?? null,
            'media_filename'     => $message[$type]['filename'] ?? null,
        ]);
    }

    // ── Mensajes de WhatsApp ───────────────────────────────────────────────

    // Todas las llamadas a la Graph API comparten el mismo token (una sola
    // app/WABA de Meta) y solo cambian de phone_number_id según la línea.
    private function sendPayload(WhatsappNumber $whatsappNumber, array $payload): void
    {
        $token = config('services.whatsapp.token');
        $url   = "https://graph.facebook.com/v25.0/{$whatsappNumber->phone_number_id}/messages";

        $response = Http::withToken($token)->post($url, $payload);

        if (!$response->successful()) {
            Log::warning('Fallo al enviar mensaje de WhatsApp', [
                'whatsapp_number_id' => $whatsappNumber->id,
                'to'                 => $payload['to'] ?? null,
                'response'           => $response->json(),
            ]);
        }
    }

    public function sendMenu($to, WhatsappNumber $whatsappNumber)
    {
        $this->sendPayload($whatsappNumber, [
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

    public function sendEsperaOpciones($to, WhatsappNumber $whatsappNumber)
    {
        $this->sendPayload($whatsappNumber, [
            'messaging_product' => 'whatsapp',
            'to'          => $to,
            'type'        => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => 'Nuestros asesores están ocupados en este momento. ¿Qué prefieres hacer?'],
                'action' => [
                    'buttons' => [
                        ['type' => 'reply', 'reply' => ['id' => 'espera_seguir',   'title' => 'SEGUIR ESPERANDO']],
                        ['type' => 'reply', 'reply' => ['id' => 'espera_mensaje',  'title' => 'DEJAR MI CONSULTA']],
                        ['type' => 'reply', 'reply' => ['id' => 'espera_cancelar','title' => 'CANCELAR']],
                    ],
                ],
            ],
        ]);
    }

    public function replyText($to, WhatsappNumber $whatsappNumber, $message)
    {
        $this->sendPayload($whatsappNumber, [
            'messaging_product' => 'whatsapp',
            'to'   => $to,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);
    }

    public function replyTextCredit($to, WhatsappNumber $whatsappNumber, $message)
    {
        $this->sendPayload($whatsappNumber, [
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

    public function replyTextCreditDiario($to, WhatsappNumber $whatsappNumber, $message)
    {
        $this->sendPayload($whatsappNumber, [
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

    public function replyTextCreditDiarioNegocio($to, WhatsappNumber $whatsappNumber, $message)
    {
        $this->sendPayload($whatsappNumber, [
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

    public function replyTextCreditDiarioTrabajador($to, WhatsappNumber $whatsappNumber, $message)
    {
        $this->sendPayload($whatsappNumber, [
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

    public function replyTextCreditDiarioVivienda($to, WhatsappNumber $whatsappNumber, $message)
    {
        $this->sendPayload($whatsappNumber, [
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

    public function replyTextCreditDiarioPrestamo($to, WhatsappNumber $whatsappNumber, $message)
    {
        $this->sendPayload($whatsappNumber, [
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

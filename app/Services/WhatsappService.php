<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function send(string $to, string $message)
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        $data = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $message],
        ];

        Http::withToken($token)->post($url, $data);
    }

    /**
     * Envía un mensaje usando una plantilla aprobada por Meta.
     *
     * @param string   $to           Teléfono destino (con código de país, sin +)
     * @param string   $templateName Nombre exacto de la plantilla en Meta
     * @param array    $bodyParams   Variables {{1}}, {{2}}... del cuerpo (en orden)
     * @param string   $language     Código de idioma (es, es_MX, en_US, etc.)
     */
    public function sendTemplate(string $to, string $templateName, array $bodyParams = [], string $language = 'es'): void
    {
        $token           = config('services.whatsapp.token');
        $phone_number_id = config('services.whatsapp.phone_number_id');
        $url             = "https://graph.facebook.com/v25.0/{$phone_number_id}/messages";

        $components = [];
        if (!empty($bodyParams)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $bodyParams),
            ];
        }

        $data = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $language],
                'components' => $components,
            ],
        ];

        Http::withToken($token)->post($url, $data);
    }

    /**
     * Descarga un archivo multimedia recibido de un cliente y lo guarda en el disco público.
     *
     * @param string $mediaId ID del media entregado por Meta en el webhook
     * @return array{path: string, mime_type: ?string}|null
     */
    public function downloadMedia(string $mediaId): ?array
    {
        $token = config('services.whatsapp.token');

        $meta = Http::withToken($token)->get("https://graph.facebook.com/v25.0/{$mediaId}");

        if (!$meta->successful() || !$meta->json('url')) {
            return null;
        }

        $mimeType = $meta->json('mime_type');

        $file = Http::withToken($token)->get($meta->json('url'));

        if (!$file->successful()) {
            return null;
        }

        $extension = preg_replace('/[^a-z0-9]/i', '', explode('/', explode(';', (string) $mimeType)[0])[1] ?? '') ?: 'bin';
        $path      = 'whatsapp-media/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;

        Storage::disk('public')->put($path, $file->body());

        return [
            'path'      => $path,
            'mime_type' => $mimeType,
        ];
    }
}

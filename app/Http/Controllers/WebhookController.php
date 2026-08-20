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
        if (!$this->hasValidSignature($request)) {
            Log::warning('Webhook de WhatsApp con firma inválida', ['ip' => $request->ip()]);

            return response('Forbidden', 403);
        }

        $data = $request->all();

        // Meta puede agrupar varios entry/changes/messages en un solo POST
        // (por ejemplo, mensaje + status casi simultáneos); hay que recorrerlos
        // todos en vez de leer solo el primero.
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $this->processChange($change['value'] ?? []);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function processChange(array $value): void
    {
        $messages = $value['messages'] ?? [];

        if (empty($messages)) {
            return;
        }

        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        $whatsappNumber = $phoneNumberId
            ? WhatsappNumber::where('phone_number_id', $phoneNumberId)->where('activo', true)->first()
            : null;

        if (!$whatsappNumber) {
            Log::warning('Mensaje de WhatsApp recibido en un número no registrado', [
                'phone_number_id'      => $phoneNumberId,
                'display_phone_number' => $value['metadata']['display_phone_number'] ?? null,
            ]);

            return;
        }

        foreach ($messages as $message) {
            $this->chatbot->handle($message, $whatsappNumber);
        }
    }

    // Verifica que el POST venga realmente de Meta usando el HMAC-SHA256 del
    // body firmado con el App Secret. Sin WHATSAPP_APP_SECRET configurado no
    // se puede verificar, así que se deja pasar mostrando un aviso (dev sin
    // secret todavía configurado) en vez de romper el webhook.
    protected function hasValidSignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.app_secret');

        if (!$appSecret) {
            Log::warning('WHATSAPP_APP_SECRET no configurado: no se está verificando la firma del webhook');

            return true;
        }

        $signature = $request->header('X-Hub-Signature-256', '');

        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }
}

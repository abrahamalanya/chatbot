<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Services\ChatbotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckWaitingAssignments extends Command
{
    protected $signature = 'assignments:check-waiting';

    protected $description = 'Avisa a los clientes en cola de espera y cierra las solicitudes abandonadas';

    private const PRIMER_AVISO_MINUTOS = 5;
    private const RECORDATORIO_MINUTOS = 10;
    private const MAX_ESPERA_MINUTOS   = 20;

    public function handle(ChatbotService $chatbot): int
    {
        $pendientes = Assignment::with('whatsappNumber')->where('status', Assignment::STATUS_PENDING)->get();

        foreach ($pendientes as $assignment) {
            if (!$assignment->whatsappNumber) {
                Log::warning('Assignment pendiente sin whatsapp_number_id, se omite', ['assignment_id' => $assignment->id]);
                continue;
            }

            $minutosEsperando = $assignment->created_at->diffInMinutes(now());

            // Ya dejó su consulta por escrito: no se cierra automáticamente
            // (no queremos perder su solicitud), pero sigue recibiendo
            // recordatorios para poder dejar otra consulta o cancelar.
            if (!$assignment->nota_dejada && $minutosEsperando >= self::MAX_ESPERA_MINUTOS) {
                $assignment->update([
                    'status'      => Assignment::STATUS_CLOSED,
                    'disposition' => 'sin_respuesta',
                ]);

                $chatbot->replyText(
                    $assignment->cliente_telefono,
                    $assignment->whatsappNumber,
                    'Lo sentimos, no pudimos comunicarte con un asesor en este momento. Puedes volver a escribirnos cuando gustes. 🙏'
                );

                continue;
            }

            $debeAvisar = $assignment->warning_sent_at
                ? $assignment->warning_sent_at->diffInMinutes(now()) >= self::RECORDATORIO_MINUTOS
                : $minutosEsperando >= self::PRIMER_AVISO_MINUTOS;

            if ($debeAvisar) {
                $chatbot->sendEsperaOpciones($assignment->cliente_telefono, $assignment->whatsappNumber);

                $assignment->update([
                    'warning_sent_at' => now(),
                    'warning_count'   => $assignment->warning_count + 1,
                ]);
            }
        }

        return self::SUCCESS;
    }
}

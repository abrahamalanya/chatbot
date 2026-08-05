<?php

namespace App\Services;

use App\Models\Advisor;
use App\Models\Assignment;
use App\Models\WhatsappNumber;

class AssignmentService
{
    protected WhatsappService $whatsapp;

    public function __construct(WhatsappService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function requestAdvisor(string $phoneClient, WhatsappNumber $whatsappNumber): Assignment
    {
        $existing = Assignment::where('cliente_telefono', $phoneClient)
            ->where(function ($q) {
                $q->where('status', Assignment::STATUS_PENDING)
                  ->orWhere(function ($q2) {
                      // Asignado pero aún no aceptado, o con ventana activa
                      $q2->where('status', Assignment::STATUS_ASSIGNED)
                         ->where(function ($q3) {
                             $q3->whereNull('accepted_at')
                                ->orWhere('conversation_expires_at', '>', now());
                         });
                  });
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        return Assignment::create([
            'cliente_telefono'   => $phoneClient,
            'advisor_id'         => null,
            'whatsapp_number_id' => $whatsappNumber->id,
            'status'             => Assignment::STATUS_PENDING,
        ]);
    }

    public function assignAdvisor(Assignment $assignment, Advisor $advisor, int $durationMinutes = 15): Assignment
    {
        // El tiempo NO corre aún — arranca cuando el asesor acepte
        $assignment->update([
            'advisor_id'            => $advisor->id,
            'status'                => Assignment::STATUS_ASSIGNED,
            'conversation_duration' => $durationMinutes,
            'accepted_at'           => null,
            'conversation_expires_at' => null,
        ]);

        $this->whatsapp->send(
            $advisor->telefono,
            "Tienes un nuevo cliente asignado: {$assignment->cliente_telefono}\nIngresa al panel para aceptar y comenzar la atención.",
            $assignment->whatsappNumber->phone_number_id
        );

        $this->whatsapp->sendTemplate(
            $assignment->cliente_telefono,
            $assignment->whatsappNumber->templateAsesorAsignado(),
            [],
            'es',
            $assignment->whatsappNumber->phone_number_id
        );

        return $assignment->fresh();
    }

    public function acceptAssignment(Assignment $assignment): Assignment
    {
        $assignment->update([
            'accepted_at'             => now(),
            'conversation_expires_at' => now()->addMinutes($assignment->conversation_duration),
        ]);

        $this->whatsapp->sendTemplate(
            $assignment->cliente_telefono,
            $assignment->whatsappNumber->templateAsesorAcepto(),
            [],
            'es',
            $assignment->whatsappNumber->phone_number_id
        );

        return $assignment->fresh();
    }

    public function extendConversation(Assignment $assignment, int $minutes = 10): Assignment
    {
        $base = $assignment->conversation_expires_at && $assignment->conversation_expires_at->isFuture()
            ? $assignment->conversation_expires_at
            : now();

        $assignment->update([
            'conversation_expires_at' => $base->copy()->addMinutes($minutes),
            'conversation_duration'   => $assignment->conversation_duration + $minutes,
        ]);

        return $assignment->fresh();
    }
}

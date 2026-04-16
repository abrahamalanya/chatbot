<?php

namespace App\Services;

use App\Models\Advisor;
use App\Models\Assignment;

class AssignmentService
{
    protected $whatsapp;

    public function __construct(WhatsappService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function assign($phoneClient)
    {
        // 1. Verificar si ya tiene asesor
        $exists = Assignment::where('cliente_telefono', $phoneClient)->first();

        if ($exists) {
            return $exists;
        }

        // 2. Elegir asesor (ej: random o round robin)
        $advisor = Advisor::where('activo', true)->inRandomOrder()->first();

        // 3. Guardar
        $assignment = Assignment::create([
            'cliente_telefono' => $phoneClient,
            'advisor_id' => $advisor->id
        ]);

        // 4. Notificar
        $this->whatsapp->send(
            $advisor->telefono,
            "Nuevo cliente: $phoneClient"
        );

        $this->whatsapp->send(
            $phoneClient,
            "Te hemos asignado un asesor 👨‍💼"
        );

        return $assignment;
    }
}

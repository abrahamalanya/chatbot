<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappNumber extends Model
{
    protected $fillable = [
        'nombre',
        'phone_number_id',
        'display_phone_number',
        'activo',
        'template_asesor_asignado',
        'template_asesor_acepto',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function templateAsesorAsignado(): string
    {
        return $this->template_asesor_asignado ?: config('services.whatsapp.templates.asesor_asignado');
    }

    public function templateAsesorAcepto(): string
    {
        return $this->template_asesor_acepto ?: config('services.whatsapp.templates.asesor_acepto');
    }
}

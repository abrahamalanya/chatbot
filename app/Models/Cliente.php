<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    const TIPOS_CREDITO = [
        'hipotecario' => 'Hipotecario',
        'vehicular'   => 'Vehicular',
        'diario'      => 'Diario',
    ];

    protected $fillable = [
        'cliente_telefono',
        'advisor_id',
        'nombre',
        'correo',
        'documento',
        'tipo_credito',
        'etapa',
        'notas',
    ];

    public function advisor()
    {
        return $this->belongsTo(Advisor::class);
    }
}

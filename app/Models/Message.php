<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'cliente_telefono',
        'advisor_id',
        'mensaje',
        'sender',
        'tipo',
    ];

    public function advisor()
    {
        return $this->belongsTo(Advisor::class);
    }

    public function scopeOpciones($query)
    {
        return $query->where('tipo', 'opcion');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'cliente_telefono',
        'advisor_id'
    ];

    public function advisor()
    {
        return $this->belongsTo(Advisor::class);
    }
}

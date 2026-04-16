<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advisor extends Model
{
    protected $fillable = [
        'nombre',
        'telefono',
        'activo'
    ];

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}

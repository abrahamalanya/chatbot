<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Advisor extends Model
{
    protected $fillable = [
        'nombre',
        'telefono',
        'activo',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}

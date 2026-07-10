<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    protected $fillable = [
        'cliente_telefono',
        'advisor_id',
        'mensaje',
        'sender',
        'tipo',
        'media_path',
        'media_mime_type',
        'media_filename',
        'latitude',
        'longitude',
    ];

    protected $appends = ['media_url'];

    public function advisor()
    {
        return $this->belongsTo(Advisor::class);
    }

    public function scopeOpciones($query)
    {
        return $query->where('tipo', 'opcion');
    }

    public function getMediaUrlAttribute(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }
}

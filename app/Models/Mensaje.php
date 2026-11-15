<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    protected $table = 'mensajes';

    protected $fillable = ['user_id', 'asunto', 'cuerpo'];

    protected $casts = ['leido_at' => 'datetime', 'respondido_at' => 'datetime'];

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

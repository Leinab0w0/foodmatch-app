<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $guarded = [];

    protected $casts = ['snapshot' => 'array', 'visto_at' => 'datetime', 'revertido_at' => 'datetime'];

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Pendientes de revisar por el superadmin */
    public static function pendientes(): int
    {
        return static::whereNull('visto_at')->whereNull('revertido_at')->count();
    }
}

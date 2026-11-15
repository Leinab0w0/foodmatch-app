<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promocion extends Model
{
    use HasFactory;

    // Laravel adivina el nombre de la tabla en inglés ("promocions"); aquí le decimos el correcto.
    protected $table = 'promociones';

    protected $fillable = ['restaurante_id', 'titulo', 'detalle', 'fecha_inicio', 'fecha_fin', 'activa'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activa' => 'boolean',
    ];

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    /**
     * Solo las promociones que valen hoy: activas y dentro de su rango de fechas (si lo tienen).
     */
    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();

        return $query->where('activa', true)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', $hoy);
            })
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy);
            });
    }
}

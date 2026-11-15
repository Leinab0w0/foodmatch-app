<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurante extends Model
{
    use HasFactory;

    protected $fillable = [
        'administrador_id',
        'categoria_id',
        'nombre',
        'tipo_comida',
        'precio_promedio',
        'direccion',
        'latitud',
        'longitud',
        'verificado',
        'horario',
        'telefono_contacto',
        'osm_id',
    ];

    protected $casts = [
        'verificado' => 'boolean',
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
    ];

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrador_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class);
    }

    public function evaluaciones(): HasMany
    {
        return $this->hasMany(Evaluacion::class);
    }

    /**
     * Promedio de puntuación, usado al mostrar recomendaciones.
     */
    public function getPromedioEvaluacionAttribute(): ?float
    {
        return $this->evaluaciones()->avg('puntuacion');
    }

    /**
     * Filtra restaurantes que calzan con un presupuesto máximo.
     */
    public function scopeDentroDePresupuesto($query, int $presupuestoMaximo)
    {
        return $query->where('precio_promedio', '<=', $presupuestoMaximo);
    }

    /**
     * Filtra por tipo de comida.
     */
    public function scopeDeTipo($query, string $tipoComida)
    {
        return $query->where('tipo_comida', 'like', "%{$tipoComida}%");
    }
}

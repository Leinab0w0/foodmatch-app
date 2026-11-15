<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Algoritmo de recomendación de FoodMatch (Sprint 5).
 *
 * Cada local recibe un puntaje de 0 a 100 combinando cuatro criterios:
 *   - Precio       (35%): más barato = mejor, comparado con los otros candidatos.
 *   - Distancia    (30%): más cerca = mejor, comparado con los otros candidatos.
 *   - Presupuesto  (25%): 100% si el precio cabe en el presupuesto; baja mientras más se pasa.
 *   - Promoción    (10%): bonus si tiene una oferta vigente.
 * Los pesos están en las constantes para poder ajustarlos al probar el algoritmo.
 */
class Recomendador
{
    public const PESO_PRECIO = 0.35;
    public const PESO_DISTANCIA = 0.30;
    public const PESO_PRESUPUESTO = 0.25;
    public const PESO_PROMO = 0.10;

    /**
     * Recibe locales que ya traen ->distancia_m y ->promo, y los devuelve
     * ordenados del mejor al peor con ->puntaje (0-100) y ->dentro_presupuesto.
     */
    public static function puntuar(Collection $locales, int $presupuesto): Collection
    {
        if ($locales->isEmpty()) {
            return $locales;
        }

        $maxPrecio = max(1, $locales->max('precio_promedio'));
        $maxDist = max(1, $locales->max('distancia_m'));

        return $locales->map(function ($r) use ($presupuesto, $maxPrecio, $maxDist) {
            $precio = (int) $r->precio_promedio;

            $sPrecio = 1 - ($precio / $maxPrecio);
            $sDist = 1 - ($r->distancia_m / $maxDist);
            $sPres = $precio <= $presupuesto
                ? 1
                : max(0, 1 - (($precio - $presupuesto) / max(1, $presupuesto)));
            $sPromo = $r->promo ? 1 : 0;

            $r->dentro_presupuesto = $precio <= $presupuesto;
            $r->puntaje = (int) round(100 * (
                self::PESO_PRECIO * $sPrecio
                + self::PESO_DISTANCIA * $sDist
                + self::PESO_PRESUPUESTO * $sPres
                + self::PESO_PROMO * $sPromo
            ));

            return $r;
        })->sortByDesc('puntaje')->values();
    }

    /** Distancia entre dos puntos (fórmula de Haversine), en metros. */
    public static function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $radio = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round($radio * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function textoDistancia(int $m): string
    {
        return $m < 1000 ? $m . 'm' : number_format($m / 1000, 1, ',', '') . 'km';
    }
}

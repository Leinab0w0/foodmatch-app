<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;
use App\Services\Recomendador;
use Illuminate\Http\Request;

class ComparadorController extends Controller
{
    private const LAT = -33.6881;
    private const LNG = -71.2153;

    public function index(Request $request)
    {
        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : self::LAT;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : self::LNG;
        $ubicacionReal = is_numeric($request->query('lat')) && is_numeric($request->query('lng'));
        $presupuesto = is_numeric($request->query('presupuesto')) ? (int) $request->query('presupuesto') : 15000;
        $radioKm = is_numeric($request->query('radio')) ? (float) $request->query('radio') : 5;

        $locales = Restaurante::withCount('productos')
            ->with(['categoria', 'promociones' => fn ($q) => $q->vigentes()])
            ->get()
            ->map(function ($r) use ($lat, $lng) {
                $r->distancia_m = Recomendador::distanciaMetros($lat, $lng, (float) $r->latitud, (float) $r->longitud);
                $r->distancia_texto = Recomendador::textoDistancia($r->distancia_m);
                $r->promo = $r->promociones->first();
                return $r;
            })
            ->filter(fn ($r) => $r->distancia_m <= $radioKm * 1000);

        // Si Explorar mandó 2-3 locales marcados (?ids[]=), se comparan solo esos.
        $ids = array_filter((array) $request->query('ids', []), 'is_numeric');
        if (! empty($ids)) {
            $locales = $locales->whereIn('id', $ids);
        }

        $ranking = Recomendador::puntuar($locales, $presupuesto);
        $top = $ranking->take(3)->values();

        $mejor = $top->first();
        $motivo = [];
        $ahorro = 0;
        if ($mejor) {
            if ($mejor->precio_promedio == $top->min('precio_promedio')) $motivo[] = 'el más barato';
            if ($mejor->distancia_m == $top->min('distancia_m')) $motivo[] = 'el más cercano';
            if ($mejor->dentro_presupuesto) $motivo[] = 'dentro de tu presupuesto';
            if ($mejor->promo) $motivo[] = 'con oferta (' . $mejor->promo->titulo . ')';
            $ahorro = $top->max('precio_promedio') - $mejor->precio_promedio;
        }

        return view('comparar', [
            'top' => $top,
            'mejor' => $mejor,
            'motivo' => $motivo,
            'ahorro' => $ahorro,
            'presupuesto' => $presupuesto,
            'radioKm' => $radioKm,
            'lat' => $ubicacionReal ? $lat : null,
            'lng' => $ubicacionReal ? $lng : null,
        ]);
    }
}

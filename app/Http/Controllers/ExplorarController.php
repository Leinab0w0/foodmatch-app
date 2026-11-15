<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Restaurante;
use Illuminate\Http\Request;

class ExplorarController extends Controller
{
    // Punto de referencia (centro de Melipilla) si el navegador no nos da la ubicación real.
    private const LAT = -33.6881;
    private const LNG = -71.2153;

    /**
     * Pantalla de filtros: solo necesita la lista de categorías para dibujar los chips.
     */
    public function filtros(Request $request)
    {
        return view('filtros', [
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function index(Request $request)
    {
        // 1) Ubicación: si la URL trae ?lat=...&lng=... (los manda el navegador) la usamos.
        $lat = self::LAT;
        $lng = self::LNG;
        $ubicacionReal = false;

        $qLat = $request->query('lat');
        $qLng = $request->query('lng');
        if (is_numeric($qLat) && is_numeric($qLng) && abs((float) $qLat) <= 90 && abs((float) $qLng) <= 180) {
            $lat = (float) $qLat;
            $lng = (float) $qLng;
            $ubicacionReal = true;
        }

        // 2) Filtros que vienen de la pantalla de filtros (todos opcionales).
        $presupuesto = is_numeric($request->query('presupuesto')) ? (int) $request->query('presupuesto') : null;
        $radioKm = is_numeric($request->query('radio')) ? (float) $request->query('radio') : null;
        $slugs = array_values(array_filter(array_map('strval', (array) $request->query('categorias', []))));
        $soloOferta = $request->boolean('oferta');
        // Texto de la barra de búsqueda (ej: "sushi", "pizza", "churrasco")
        $texto = trim((string) $request->query('q', ''));

        // Nota: las preferencias del perfil NO se aplican solas. Cada vez que entras a Explorar
        // parte limpio (todos los locales); los filtros solo valen para esa búsqueda.

        // 3) Consulta a la base de datos aplicando los filtros.
        $consulta = Restaurante::withCount('productos')->with([
            'categoria',
            'promociones' => function ($q) {
                $q->vigentes();
            },
        ]);

        if ($presupuesto !== null) {
            $consulta->where('precio_promedio', '<=', $presupuesto);
        }
        if ($texto !== '') {
            // Busca por nombre del local, por categoría o por productos del menú.
            $consulta->where(function ($q) use ($texto) {
                $q->where('nombre', 'like', '%' . $texto . '%')
                  ->orWhereHas('categoria', function ($c) use ($texto) {
                      $c->where('nombre', 'like', '%' . $texto . '%');
                  });
                if (method_exists(Restaurante::class, 'productos')) {
                    $q->orWhereHas('productos', function ($p) use ($texto) {
                        $p->where('nombre', 'like', '%' . $texto . '%');
                    });
                }
            });
        }
        if (! empty($slugs)) {
            $consulta->whereHas('categoria', function ($q) use ($slugs) {
                $q->whereIn('slug', $slugs);
            });
        }
        if ($soloOferta) {
            $consulta->whereHas('promociones', function ($q) {
                $q->vigentes();
            });
        }

        $restaurantes = $consulta->get();

        // 4) Distancia desde la ubicación, promo vigente y texto de la distancia.
        $restaurantes = $restaurantes->map(function ($r) use ($lat, $lng) {
            $r->distancia_m = $this->distanciaEnMetros($lat, $lng, (float) $r->latitud, (float) $r->longitud);
            $r->distancia_texto = $r->distancia_m < 1000
                ? $r->distancia_m . 'm'
                : number_format($r->distancia_m / 1000, 1, ',', '') . 'km';
            $r->promo = $r->promociones->first();
            return $r;
        });

        // 5) Filtro de distancia máxima (en km) y orden de más cerca a más lejos.
        if ($radioKm !== null) {
            $restaurantes = $restaurantes->filter(function ($r) use ($radioKm) {
                return $r->distancia_m <= $radioKm * 1000;
            });
        }
        $restaurantes = $restaurantes->sortBy('distancia_m')->values();

        // 6) Lista para la hoja deslizable (todos los que cumplen, hasta 60; el scroll hace el resto).
        $cercanos = $restaurantes->take(60);
        $total = $restaurantes->count();

        // 7) Datos livianos para dibujar los pines en el mapa.
        $pines = $restaurantes->map(function ($r) use ($request) {
            return [
                'url' => route('ficha', array_merge(['restaurante' => $r->id], array_filter($request->only(['lat', 'lng'])))),
                'nombre' => $r->nombre,
                'lat' => (float) $r->latitud,
                'lng' => (float) $r->longitud,
                'color' => $r->categoria ? $r->categoria->color : '#6b6862',
                'promo' => $r->promo ? $r->promo->titulo : null,
                'distancia' => $r->distancia_texto,
                'imagen' => $r->imagen ? asset('storage/' . $r->imagen) : null,
            ];
        })->values();

        // 8) Texto de los filtros activos (chips de arriba).
        $resumen = [];
        if ($texto !== '') {
            $resumen[] = '🔎 ' . $texto;
        }
        if ($presupuesto !== null) {
            $resumen[] = '💰 hasta $' . number_format($presupuesto, 0, ',', '.');
        }
        if ($radioKm !== null) {
            $resumen[] = '📍 ' . rtrim(rtrim(number_format($radioKm, 1, ',', ''), '0'), ',') . ' km';
        }
        if (! empty($slugs)) {
            $resumen[] = '🍽 ' . Categoria::whereIn('slug', $slugs)->pluck('nombre')->implode(', ');
        }
        if ($soloOferta) {
            $resumen[] = '🔥 Con oferta';
        }
        $hayFiltros = ! empty($resumen);
        if (! $hayFiltros) {
            $resumen[] = '📍 Cerca';
        }

        return view('explorar', [
            'cercanos' => $cercanos,
            'total' => $total,
            'pines' => $pines,
            'centro' => ['lat' => $lat, 'lng' => $lng],
            'ubicacionReal' => $ubicacionReal,
            'resumen' => $resumen,
            'hayFiltros' => $hayFiltros,
            'texto' => $texto,
        ]);
    }

    /**
     * Distancia entre dos puntos de la Tierra (fórmula de Haversine), en metros.
     */
    private function distanciaEnMetros(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $radioTierra = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round(2 * $radioTierra * asin(sqrt($a)));
    }
}

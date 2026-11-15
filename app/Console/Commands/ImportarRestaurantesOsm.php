<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Restaurante;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportarRestaurantesOsm extends Command
{
    // Uso: php artisan foodmatch:importar-osm
    //      php artisan foodmatch:importar-osm --radio=5000 --limpiar
    protected $signature = 'foodmatch:importar-osm
                            {--radio=3000 : Radio de búsqueda en metros}
                            {--lat=-33.6881 : Latitud del centro (por defecto, Melipilla)}
                            {--lng=-71.2153 : Longitud del centro (por defecto, Melipilla)}
                            {--limpiar : Borra antes TODOS los locales que no vienen de OpenStreetMap (los de ejemplo)}';

    protected $description = 'Importa restaurantes reales desde OpenStreetMap (Overpass API) alrededor de un punto';

    public function handle(): int
    {
        $radio = (int) $this->option('radio');
        $lat = (float) $this->option('lat');
        $lng = (float) $this->option('lng');

        // Consulta Overpass: restaurantes, comida rápida y cafés (puntos y edificios) dentro del radio.
        $consulta = sprintf(
            '[out:json][timeout:30];(node["amenity"~"restaurant|fast_food|cafe"](around:%d,%F,%F);way["amenity"~"restaurant|fast_food|cafe"](around:%d,%F,%F););out center tags;',
            $radio, $lat, $lng, $radio, $lat, $lng
        );

        $this->info("Consultando OpenStreetMap (radio {$radio} m)...");

        $respuesta = Http::withHeaders(['User-Agent' => 'FoodMatch-Capstone/1.0'])
            ->timeout(60)
            ->asForm()
            ->post('https://overpass-api.de/api/interpreter', ['data' => $consulta]);

        if ($respuesta->failed()) {
            $this->error('No se pudo consultar Overpass. Código: ' . $respuesta->status());
            return self::FAILURE;
        }

        $elementos = $respuesta->json('elements') ?? [];
        $this->info(count($elementos) . ' elementos encontrados.');

        if ($this->option('limpiar')) {
            $borrados = Restaurante::whereNull('osm_id')->delete();
            $this->warn("Se borraron {$borrados} locales de ejemplo.");
        }

        // Categoría "Otros" para lo que no calce con las 5 principales.
        Categoria::firstOrCreate(['slug' => 'otros'], ['nombre' => 'Otros', 'color' => '#6b6862']);
        $categorias = Categoria::pluck('id', 'slug'); // slug => id

        $nuevos = 0;
        $actualizados = 0;

        foreach ($elementos as $e) {
            $tags = $e['tags'] ?? [];
            if (empty($tags['name'])) {
                continue; // sin nombre no nos sirve
            }

            // Los puntos traen lat/lon; los edificios traen center.lat/center.lon.
            $latitud = $e['lat'] ?? ($e['center']['lat'] ?? null);
            $longitud = $e['lon'] ?? ($e['center']['lon'] ?? null);
            if ($latitud === null || $longitud === null) {
                continue;
            }

            $amenity = $tags['amenity'] ?? 'restaurant';
            $cocina = $tags['cuisine'] ?? '';
            $slug = $this->categoriaPorCocina($cocina, $amenity);
            $direccion = trim(($tags['addr:street'] ?? '') . ' ' . ($tags['addr:housenumber'] ?? ''));

            $datos = [
                'nombre' => $tags['name'],
                'latitud' => $latitud,
                'longitud' => $longitud,
                'direccion' => $direccion !== '' ? $direccion : null,
                'horario' => $tags['opening_hours'] ?? null,
                'telefono_contacto' => $tags['phone'] ?? ($tags['contact:phone'] ?? null),
            ];

            $osmId = $e['type'] . '/' . $e['id'];
            $existente = Restaurante::where('osm_id', $osmId)->first();

            if ($existente) {
                // Si ya existía, solo refrescamos lo de OSM; no pisamos categoría ni precio editados a mano.
                $existente->update($datos);
                $actualizados++;
            } else {
                Restaurante::create($datos + [
                    'osm_id' => $osmId,
                    'categoria_id' => $categorias[$slug] ?? $categorias['otros'],
                    'tipo_comida' => $this->textoCocina($cocina, $amenity),
                    // OpenStreetMap NO trae precios: este valor es un estimado para corregir a mano.
                    'precio_promedio' => $this->precioEstimado($amenity),
                ]);
                $nuevos++;
            }
        }

        $this->info("Listo: {$nuevos} locales nuevos, {$actualizados} actualizados.");
        $this->warn('Los precios son estimados (OpenStreetMap no trae precios). Corrígelos a mano.');

        return self::SUCCESS;
    }

    /**
     * Traduce el tipo de cocina de OpenStreetMap a una de nuestras categorías.
     */
    private function categoriaPorCocina(string $cocina, string $amenity): string
    {
        $c = strtolower($cocina);

        if (str_contains($c, 'sushi')) return 'sushi';
        if (preg_match('/chinese|japanese|thai|korean|vietnamese|asian|ramen|noodle/', $c)) return 'asiatica';
        if (preg_match('/pizza|italian/', $c)) return 'pizza';
        if (preg_match('/burger|sandwich|hot_dog|american/', $c)) return 'burger';
        if (preg_match('/vegan|vegetarian/', $c)) return 'vegano';

        // Comida rápida sin tipo de cocina: en Chile suelen ser completos y sándwiches.
        if ($amenity === 'fast_food') return 'burger';

        return 'otros';
    }

    /**
     * Texto descriptivo para la columna tipo_comida.
     */
    private function textoCocina(string $cocina, string $amenity): string
    {
        if ($cocina !== '') {
            return ucfirst(str_replace(['_', ';'], [' ', ', '], $cocina));
        }

        return match ($amenity) {
            'fast_food' => 'Comida rápida',
            'cafe' => 'Café',
            default => 'Restaurante',
        };
    }

    /**
     * Precio aproximado por tipo de local (en pesos). Es solo un punto de partida.
     */
    private function precioEstimado(string $amenity): int
    {
        return match ($amenity) {
            'fast_food' => 4500,
            'cafe' => 3500,
            default => 8000,
        };
    }
}

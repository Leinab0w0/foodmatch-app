<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Evaluacion;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Database\Seeder;

class FoodMatchSeeder extends Seeder
{
    /**
     * Datos de ejemplo (centrados en Melipilla, igual que el mapa).
     * Se puede correr varias veces sin duplicar: php artisan db:seed --class=FoodMatchSeeder
     */
    public function run(): void
    {
        // --- Categorías (mismos colores que foodmatch.css) ---
        $cat = [];
        foreach ([
            ['asiatica', 'Asiática', '#e0483e'],
            ['pizza',    'Pizza',    '#f0973f'],
            ['sushi',    'Sushi',    '#3e9c6e'],
            ['burger',   'Burger',   '#d9a441'],
            ['vegano',   'Vegano',   '#5fa85a'],
        ] as [$slug, $nombre, $color]) {
            $cat[$slug] = Categoria::updateOrCreate(['slug' => $slug], ['nombre' => $nombre, 'color' => $color]);
        }

        // --- Usuarios de prueba ---
        $usuario = User::firstOrCreate(
            ['email' => 'usuario@foodmatch.test'],
            ['name' => 'Usuario de Prueba', 'password' => bcrypt('password')]
        );
        $usuario->forceFill([
            'tipo_comida_preferida' => 'Completos y hamburguesas',
            'presupuesto_habitual' => 7000,
            'zona_busqueda' => 'Melipilla',
        ])->save();

        $adminBonapetit = User::firstOrCreate(
            ['email' => 'bonapetit@foodmatch.test'],
            ['name' => 'Administrador Bonapetit', 'password' => bcrypt('password')]
        );

        // --- Restaurantes (las coordenadas de los 3 primeros son las mismas del mapa) ---
        $wok = Restaurante::updateOrCreate(['nombre' => 'Wok Central'], [
            'categoria_id' => $cat['asiatica']->id,
            'tipo_comida' => 'Comida asiática',
            'precio_promedio' => 8500,
            'latitud' => -33.6871000, 'longitud' => -71.2163000,
            'horario' => 'Lun a Dom 12:00 - 22:00',
        ]);
        $sushi = Restaurante::updateOrCreate(['nombre' => 'Sushi Express'], [
            'categoria_id' => $cat['sushi']->id,
            'tipo_comida' => 'Sushi',
            'precio_promedio' => 9200,
            'latitud' => -33.6891000, 'longitud' => -71.2133000,
            'horario' => 'Lun a Dom 12:00 - 23:00',
        ]);
        $pizza = Restaurante::updateOrCreate(['nombre' => 'Pizzería Roma'], [
            'categoria_id' => $cat['pizza']->id,
            'tipo_comida' => 'Pizza',
            'precio_promedio' => 8900,
            'latitud' => -33.6861000, 'longitud' => -71.2173000,
            'horario' => 'Mar a Dom 13:00 - 23:00',
        ]);
        $bonapetit = Restaurante::updateOrCreate(['nombre' => 'Bonapetit'], [
            'administrador_id' => $adminBonapetit->id,
            'categoria_id' => $cat['burger']->id,
            'tipo_comida' => 'Completos y hamburguesas',
            'precio_promedio' => 5500,
            'direccion' => 'PON AQUÍ LA DIRECCIÓN REAL', // <- cambiar
            'latitud' => -33.6881000, 'longitud' => -71.2153000, // <- ajustar a la ubicación real
            'verificado' => true,
            'horario' => 'Lun a Dom 11:00 - 23:00',
        ]);

        // --- Productos (menú) ---
        foreach ([
            [$wok->id,   'Arroz frito + bebida',  8500],
            [$wok->id,   'Pad thai',              9800],
            [$wok->id,   'Rollitos primavera',    4200],
            [$sushi->id, 'Roll de salmón (10 un)', 9200],
            [$sushi->id, 'Gyozas (6 un)',          4500],
            [$pizza->id, 'Pizza familiar',         8900],
            [$bonapetit->id, 'Completo italiano',  3500],
            [$bonapetit->id, 'Hamburguesa clásica', 5500],
        ] as [$rid, $nombre, $precio]) {
            Producto::updateOrCreate(['restaurante_id' => $rid, 'nombre' => $nombre], ['precio' => $precio]);
        }

        // --- Promociones (vigentes siempre, porque no tienen fechas) ---
        foreach ([
            [$sushi->id, '2x1 en rolls',      'Hasta las 20:00'],
            [$pizza->id, '-30% pizza familiar', 'Todo el día'],
            [$wok->id,   'Menú del día $8.500', 'Hasta las 16:00'],
        ] as [$rid, $titulo, $detalle]) {
            Promocion::updateOrCreate(['restaurante_id' => $rid, 'titulo' => $titulo], ['detalle' => $detalle, 'activa' => true]);
        }

        Evaluacion::updateOrCreate(
            ['user_id' => $usuario->id, 'restaurante_id' => $bonapetit->id],
            ['puntuacion' => 5, 'comentario' => 'Buen completo por el precio.']
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Restaurante;
use Illuminate\Database\Seeder;

/**
 * DATOS DE EJEMPLO para la demo (menús y promociones inventados).
 * No crea locales falsos: agrega menú y promos a los locales que YA existen en la BD.
 * Se puede correr varias veces: los locales que ya tienen productos se saltan.
 *
 * Uso:  php artisan db:seed --class=DemoMenusSeeder
 */
class DemoMenusSeeder extends Seeder
{
    /** Menú base por categoría: [producto, precio, descripción] */
    private const MENUS = [
        'asiatica' => [['Arroz frito con pollo', 6500, 'Con verduras salteadas'], ['Pad thai', 7800, 'Fideos de arroz y maní'], ['Rollitos primavera (4)', 3500, null], ['Pollo agridulce', 7200, 'Con arroz blanco']],
        'pizza' => [['Pizza personal napolitana', 5500, null], ['Pizza mediana pepperoni', 9500, null], ['Lasaña de carne', 7000, null], ['Bebida 500cc', 1800, null]],
        'sushi' => [['Roll California (6 pzas)', 5200, null], ['Roll Philadelphia (10 pzas)', 7900, null], ['Handroll de salmón', 3800, null], ['Gyozas (5)', 4200, null]],
        'burger' => [['Hamburguesa clásica', 5500, 'Con papas fritas'], ['Completo italiano', 3500, null], ['Churrasco italiano', 5800, 'Palta, tomate y mayo'], ['Papas fritas', 2800, null]],
        'vegano' => [['Bowl de quinoa', 6900, null], ['Hamburguesa de lentejas', 6500, null], ['Wrap vegano', 5200, null], ['Jugo natural', 2500, null]],
        'otros' => [['Almuerzo del día', 5500, 'Plato de fondo + ensalada'], ['Sándwich de pollo', 4200, null], ['Ensalada mixta', 3800, null], ['Bebida', 1500, null]],
    ];

    /** Promoción por categoría */
    private const PROMOS = [
        'asiatica' => ['Menú del día $7.500', 'Arroz + bebida, hasta las 16:00'],
        'pizza' => ['-30% en pizzas medianas', 'Solo para retiro en local'],
        'sushi' => ['2x1 en handrolls', 'Lunes a jueves'],
        'burger' => ['2x1 en completos', 'Hasta las 20:00'],
        'vegano' => ['Jugo gratis con tu bowl', 'Todo el día'],
        'otros' => ['Almuerzo + bebida', 'De 12:00 a 15:00'],
    ];

    public function run(): void
    {
        $locales = Restaurante::with('categoria')->orderBy('id')->get();
        $creados = 0;

        foreach ($locales as $i => $local) {
            if ($local->productos()->exists()) {
                continue; // ya tiene menú: no se toca
            }

            $slug = $local->categoria->slug ?? 'otros';
            $menu = self::MENUS[$slug] ?? self::MENUS['otros'];

            // Variación de precios estable por local (±15%) para que no todos cuesten lo mismo.
            $factor = 0.85 + (crc32($local->nombre) % 31) / 100;

            foreach ($menu as [$nombre, $precio, $desc]) {
                Producto::create([
                    'restaurante_id' => $local->id,
                    'nombre' => $nombre,
                    'descripcion' => $desc,
                    'precio' => (int) (round($precio * $factor / 100) * 100),
                    'disponible' => true,
                ]);
            }

            // El precio promedio pasa a ser el real del menú (se quita el "~").
            $local->update(['precio_promedio' => (int) round($local->productos()->avg('precio'))]);

            // Promo para 1 de cada 3 locales.
            if ($i % 3 === 0 && ! $local->promociones()->exists()) {
                [$titulo, $detalle] = self::PROMOS[$slug] ?? self::PROMOS['otros'];
                Promocion::create([
                    'restaurante_id' => $local->id,
                    'titulo' => $titulo,
                    'detalle' => $detalle,
                    'activa' => true,
                ]);
            }

            $creados++;
        }

        $this->command?->info("Menús de ejemplo cargados en {$creados} locales.");
    }
}

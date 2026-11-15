<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla restaurantes (comercios). Igual a la del zip original, más categoria_id.
     */
    public function up(): void
    {
        Schema::create('restaurantes', function (Blueprint $table) {
            $table->id();

            // Dueño/administrador del local verificado (ej. Bonapetit). Null = aún no reclamado.
            $table->foreignId('administrador_id')->nullable()
                ->constrained('users')->nullOnDelete();

            // Categoría de comida: de aquí sale el color del pin y de las etiquetas.
            $table->foreignId('categoria_id')->nullable()
                ->constrained('categorias')->nullOnDelete();

            $table->string('nombre');
            $table->string('tipo_comida');              // texto descriptivo, ej: "Completos y hamburguesas"
            $table->unsignedInteger('precio_promedio'); // en pesos, para filtrar por presupuesto
            $table->string('direccion')->nullable();
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->boolean('verificado')->default(false);
            $table->string('horario')->nullable();
            $table->string('telefono_contacto')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurantes');
    }
};

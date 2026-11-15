<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla evaluaciones: cubre la historia FM-HU-007 (evaluar un restaurante
     * después de visitarlo). Queda lista para, a futuro, agregar puntos
     * (digievolución), pero por ahora solo guarda la evaluación en sí.
     */
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();

            $table->unsignedTinyInteger('puntuacion'); // 1 a 5
            $table->text('comentario')->nullable();

            $table->timestamps();

            // Un usuario no puede evaluar el mismo restaurante dos veces en el mismo registro
            $table->unique(['user_id', 'restaurante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};

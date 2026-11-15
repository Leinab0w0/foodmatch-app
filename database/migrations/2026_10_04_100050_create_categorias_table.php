<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorías de comida (asiática, pizza, sushi...). Cada una trae su color,
     * que es el que se usa en los pines del mapa y en las etiquetas.
     * Va ANTES de restaurantes porque restaurantes apunta a esta tabla.
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('color', 7); // hex, ej: #e0483e
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};

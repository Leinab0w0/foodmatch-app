<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Locales que cada usuario guarda como favoritos (botón ♡ de la Ficha). */
    public function up(): void
    {
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'restaurante_id']); // no se puede guardar dos veces el mismo local
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};

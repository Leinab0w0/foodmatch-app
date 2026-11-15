<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Foto del local (ruta dentro de storage/app/public/locales). Opcional. */
    public function up(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->string('imagen')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};

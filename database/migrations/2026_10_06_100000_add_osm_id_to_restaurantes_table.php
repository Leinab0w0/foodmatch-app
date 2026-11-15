<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * osm_id guarda el identificador del local en OpenStreetMap (ej: "node/123456").
     * Sirve para no duplicar locales cuando se vuelve a correr la importación.
     */
    public function up(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->string('osm_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('restaurantes', function (Blueprint $table) {
            $table->dropColumn('osm_id');
        });
    }
};

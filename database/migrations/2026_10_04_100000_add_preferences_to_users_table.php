<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega a la tabla users (que Laravel ya trae por defecto) los campos
     * necesarios para la Historia FM-HU-003 (preferencias y presupuesto)
     * y FM-HU-004 (ubicación).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tipo_comida_preferida')->nullable()->after('email');
            $table->unsignedInteger('presupuesto_habitual')->nullable()->after('tipo_comida_preferida');
            $table->string('zona_busqueda')->nullable()->after('presupuesto_habitual');
            $table->decimal('ultima_latitud', 10, 7)->nullable()->after('zona_busqueda');
            $table->decimal('ultima_longitud', 10, 7)->nullable()->after('ultima_latitud');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_comida_preferida',
                'presupuesto_habitual',
                'zona_busqueda',
                'ultima_latitud',
                'ultima_longitud',
            ]);
        });
    }
};

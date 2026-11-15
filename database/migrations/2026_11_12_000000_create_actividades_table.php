<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro de lo que hacen los admins (crear/eliminar locales) para que el superadmin lo revise y pueda deshacerlo.
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();          // quién lo hizo
            $table->string('accion', 40);                             // local_creado | local_eliminado
            $table->string('titulo', 200);                            // texto legible
            $table->unsignedBigInteger('restaurante_id')->nullable(); // local afectado
            $table->longText('snapshot')->nullable();                 // copia del local (para poder restaurarlo)
            $table->timestamp('visto_at')->nullable();
            $table->timestamp('revertido_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dueño de local: un usuario ligado a UN local (restaurante_id). Si es null, es un usuario normal.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurante_id')->nullable()->index();
        });

        // Mensajes de admins y dueños hacia el superadmin (con su respuesta).
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('asunto', 120);
            $table->text('cuerpo');
            $table->text('respuesta')->nullable();
            $table->timestamp('respondido_at')->nullable();
            $table->timestamp('leido_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('restaurante_id');
        });
    }
};

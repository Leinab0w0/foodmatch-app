<?php

namespace App\Support;

use App\Models\Actividad;
use App\Models\User;

class Auditoria
{
    /**
     * Anota algo hecho por un admin. Lo que hace el superadmin NO se anota (no necesita avisarse a sí misma).
     */
    public static function registrar(User $actor, string $accion, string $titulo, ?int $restauranteId = null, ?array $snapshot = null): void
    {
        if (Roles::esSuperadmin($actor)) {
            return;
        }

        Actividad::create([
            'user_id' => $actor->id,
            'accion' => $accion,
            'titulo' => $titulo,
            'restaurante_id' => $restauranteId,
            'snapshot' => $snapshot,
        ]);
    }
}

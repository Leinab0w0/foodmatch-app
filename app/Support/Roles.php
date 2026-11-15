<?php

namespace App\Support;

use App\Models\Restaurante;
use App\Models\User;

/**
 * Roles de FoodMatch:
 *  - usuario: cualquiera con cuenta (explora, guarda favoritos).
 *  - dueño de local: usuario con restaurante_id (administra SOLO su local).
 *  - admin: es_admin = true (administra todos los locales y usuarios).
 *  - superadmin: el primer admin creado. Los demás admins no lo ven ni pueden tocarlo.
 */
class Roles
{
    public static function superadminId(): ?int
    {
        return User::where('es_admin', true)->orderBy('id')->value('id');
    }

    public static function esSuperadmin(?User $u): bool
    {
        return $u && $u->es_admin && $u->id === self::superadminId();
    }

    public static function esDueno(?User $u): bool
    {
        return $u && ! empty($u->restaurante_id);
    }

    public static function localDe(User $u): ?Restaurante
    {
        return self::esDueno($u) ? Restaurante::find($u->restaurante_id) : null;
    }
}

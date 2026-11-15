<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurante;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Gestión de usuarios desde el panel admin:
 *  - cualquier admin puede resetear la contraseña de un usuario (se genera una temporal);
 *  - solo el SUPERADMIN (el primer admin creado) puede dar o quitar el rol de admin;
 *  - el superadmin NO aparece para los demás admins y no pueden tocarlo.
 */
class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $superId = Roles::superadminId();
        $soySuper = $request->user()->id === $superId;

        $usuarios = User::orderBy('name')
            ->when(! $soySuper, fn ($q) => $q->where('id', '!=', $superId)) // oculto para los demás admins
            ->get();

        return view('admin.usuarios', [
            'usuarios' => $usuarios,
            'superadminId' => $superId,
            'soySuperadmin' => $soySuper,
            'locales' => Restaurante::pluck('nombre', 'id'),
        ]);
    }

    public function resetPassword(Request $request, User $usuario)
    {
        $this->protegerSuperadmin($request, $usuario);

        $temporal = Str::upper(Str::random(4)) . '-' . random_int(1000, 9999);
        $usuario->forceFill([
            'password' => Hash::make($temporal),
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('ok', "Contraseña temporal de {$usuario->email}: {$temporal} (anótala y entrégasela; no se vuelve a mostrar).");
    }

    public function toggleAdmin(Request $request, User $usuario)
    {
        abort_unless(Roles::esSuperadmin($request->user()), 403, 'Solo el superadmin puede cambiar roles.');

        if ($usuario->id === $request->user()->id) {
            return back()->withErrors('No puedes quitarte el rol a ti misma.');
        }

        $usuario->forceFill(['es_admin' => ! $usuario->es_admin])->save();

        return back()->with('ok', $usuario->name . ($usuario->es_admin ? ' ahora es admin.' : ' ya no es admin.'));
    }

    /** Solo el superadmin da o quita el permiso de dueño (asignar o quitar un local a una cuenta). */
    public function asignarLocal(Request $request, User $usuario)
    {
        abort_unless(Roles::esSuperadmin($request->user()), 403, 'Solo el superadmin autoriza dueños.');
        $datos = $request->validate(['restaurante_id' => 'nullable|exists:restaurantes,id']);

        $usuario->forceFill(['restaurante_id' => $datos['restaurante_id'] ?? null])->save();

        return back()->with('ok', $usuario->restaurante_id
            ? $usuario->name . ' ahora administra «' . Restaurante::find($usuario->restaurante_id)->nombre . '».'
            : 'Se quitó el permiso de dueño a ' . $usuario->name . '.');
    }

    /** Un admin común no puede ver ni modificar al superadmin: responde como si no existiera. */
    private function protegerSuperadmin(Request $request, User $objetivo): void
    {
        if ($objetivo->id === Roles::superadminId() && $request->user()->id !== $objetivo->id) {
            abort(404);
        }
    }
}

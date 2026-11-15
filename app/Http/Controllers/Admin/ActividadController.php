<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Restaurante;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Actividad de los admins (solo el superadmin): ve lo que crearon o eliminaron
 * y puede deshacerlo (restaurar un local eliminado o quitar uno creado por error).
 */
class ActividadController extends Controller
{
    private function soloSuperadmin(Request $request): void
    {
        abort_unless(Roles::esSuperadmin($request->user()), 403, 'Solo el superadmin ve la actividad.');
    }

    public function index(Request $request)
    {
        $this->soloSuperadmin($request);

        return view('admin.actividad', [
            'actividades' => Actividad::with('autor')->latest()->limit(100)->get(),
        ]);
    }

    public function visto(Request $request, Actividad $actividad)
    {
        $this->soloSuperadmin($request);
        $actividad->forceFill(['visto_at' => now()])->save();

        return back();
    }

    public function vistoTodo(Request $request)
    {
        $this->soloSuperadmin($request);
        Actividad::whereNull('visto_at')->update(['visto_at' => now()]);

        return back()->with('ok', 'Todo marcado como revisado.');
    }

    public function deshacer(Request $request, Actividad $actividad)
    {
        $this->soloSuperadmin($request);

        if ($actividad->revertido_at) {
            return back()->withErrors('Esto ya se deshizo antes.');
        }

        $mensaje = DB::transaction(function () use ($actividad) {
            if ($actividad->accion === 'local_eliminado') {
                return $this->restaurar($actividad);
            }
            if ($actividad->accion === 'local_creado') {
                return $this->quitarCreado($actividad);
            }

            return 'Esta acción no se puede deshacer.';
        });

        $actividad->forceFill(['revertido_at' => now(), 'visto_at' => $actividad->visto_at ?: now()])->save();

        return back()->with('ok', $mensaje);
    }

    /** Vuelve a crear el local eliminado, con su menú, sus promociones y sus dueños. */
    private function restaurar(Actividad $actividad): string
    {
        $snap = $actividad->snapshot ?? [];
        if (empty($snap['local'])) {
            return 'No hay copia guardada de este local.';
        }
        if (Restaurante::find($snap['local']['id'])) {
            return 'Ese local ya existe (ya estaba restaurado).';
        }

        (new Restaurante())->forceFill($snap['local'])->save();
        foreach ($snap['productos'] ?? [] as $fila) {
            (new Producto())->forceFill($fila)->save();
        }
        foreach ($snap['promociones'] ?? [] as $fila) {
            (new Promocion())->forceFill($fila)->save();
        }
        if (! empty($snap['duenos'])) {
            User::whereIn('id', $snap['duenos'])->update(['restaurante_id' => $snap['local']['id']]);
        }

        return 'Local «' . $snap['local']['nombre'] . '» restaurado con su menú y sus ofertas.';
    }

    /** Elimina un local creado por error. */
    private function quitarCreado(Actividad $actividad): string
    {
        $local = Restaurante::find($actividad->restaurante_id);
        if (! $local) {
            return 'Ese local ya no existe.';
        }
        $nombre = $local->nombre;
        if ($local->imagen) {
            Storage::disk('public')->delete($local->imagen);
        }
        User::where('restaurante_id', $local->id)->update(['restaurante_id' => null]);
        $local->delete();

        return 'Local «' . $nombre . '» eliminado.';
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Restaurante;
use App\Models\User;
use App\Support\Auditoria;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * CRUD de locales para el panel de administración (Sprint 4):
 * crear, listar, editar y eliminar locales, y administrar su menú y promociones.
 */
class LocalController extends Controller
{
    public function index()
    {
        $locales = Restaurante::withCount('productos')->with('categoria')->orderBy('nombre')->get();
        // Quién administra cada local (para mostrarlo en la lista)
        $duenos = User::whereNotNull('restaurante_id')->get()->groupBy('restaurante_id');

        return view('admin.locales.index', compact('locales', 'duenos'));
    }

    public function create()
    {
        return view('admin.locales.form', [
            'local' => new Restaurante(),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $local = Restaurante::create(collect($datos)->except(['imagen', 'quitar_imagen'])->all());
        $this->guardarImagen($request, $local);
        Auditoria::registrar($request->user(), 'local_creado', 'Creó el local «' . $local->nombre . '»', $local->id);

        return redirect()->route('admin.locales.edit', $local)
            ->with('ok', 'Local creado. Ahora puedes agregar su menú y promociones.');
    }

    public function edit(Restaurante $local)
    {
        $local->load(['productos' => fn ($q) => $q->orderBy('nombre'), 'promociones']);

        return view('admin.locales.form', [
            'local' => $local,
            'duenos' => User::where('restaurante_id', $local->id)->get(),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Restaurante $local)
    {
        $datos = $this->validar($request);
        $local->update(collect($datos)->except(['imagen', 'quitar_imagen'])->all());
        $this->guardarImagen($request, $local);

        return back()->with('ok', 'Cambios guardados.');
    }

    public function destroy(Request $request, Restaurante $local)
    {
        // Copia completa del local: si un admin lo elimina, el superadmin puede restaurarlo con un toque.
        $snapshot = [
            'local' => $local->getAttributes(),
            'productos' => $local->productos()->get()->map(fn ($p) => $p->getAttributes())->all(),
            'promociones' => $local->promociones()->get()->map(fn ($p) => $p->getAttributes())->all(),
            'duenos' => User::where('restaurante_id', $local->id)->pluck('id')->all(),
        ];
        Auditoria::registrar($request->user(), 'local_eliminado', 'Eliminó el local «' . $local->nombre . '»', $local->id, $snapshot);

        // La foto solo se borra si lo elimina el superadmin (si fue un admin, se conserva para poder restaurar).
        if ($local->imagen && Roles::esSuperadmin($request->user())) {
            Storage::disk('public')->delete($local->imagen);
        }
        User::where('restaurante_id', $local->id)->update(['restaurante_id' => null]); // sus dueños quedan como usuarios normales
        $local->delete(); // productos y promociones se borran en cascada

        return redirect()->route('admin.locales.index')->with('ok', 'Local eliminado.' . (Roles::esSuperadmin($request->user()) ? '' : ' Se avisó al superadmin.'));
    }

    // ---------- Cuenta del dueño del local ----------

    /** Crea la cuenta del dueño (correo verificado por el admin) con una clave temporal que se muestra una sola vez. */
    public function duenoStore(Request $request, Restaurante $local)
    {
        abort_unless(Roles::esSuperadmin($request->user()), 403, 'Solo el superadmin autoriza dueños.');
        $datos = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
        ]);

        $temporal = Str::upper(Str::random(4)) . '-' . random_int(1000, 9999);
        (new User())->forceFill([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($temporal),
            'email_verified_at' => now(),
            'restaurante_id' => $local->id,
        ])->save();

        return back()->with('ok', "Cuenta creada para {$datos['email']}. Contraseña temporal: {$temporal} (entrégasela; no se vuelve a mostrar). Al entrar verá su panel «Mi local».");
    }

    /** Quita el acceso de un dueño a este local (la cuenta sigue existiendo como usuario normal). */
    public function duenoQuitar(Request $request, Restaurante $local, User $usuario)
    {
        abort_unless(Roles::esSuperadmin($request->user()), 403, 'Solo el superadmin autoriza dueños.');
        abort_unless((int) $usuario->restaurante_id === $local->id, 404);
        $usuario->forceFill(['restaurante_id' => null])->save();

        return back()->with('ok', 'Se quitó el acceso de ' . $usuario->name . ' a este local.');
    }

    // ---------- Productos (menú) ----------

    public function productoStore(Request $request, Restaurante $local)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:120',
            'precio' => 'required|integer|min:100|max:200000',
            'descripcion' => 'nullable|string|max:255',
        ]);
        $local->productos()->create($datos + ['disponible' => true]);
        $this->recalcularPrecio($local);

        return back()->with('ok', 'Producto agregado. El precio promedio del local se actualizó.');
    }

    public function productoDestroy(Producto $producto)
    {
        $local = $producto->restaurante;
        $producto->delete();
        $this->recalcularPrecio($local);

        return back()->with('ok', 'Producto eliminado.');
    }

    // ---------- Promociones ----------

    public function promoStore(Request $request, Restaurante $local)
    {
        $datos = $request->validate([
            'titulo' => 'required|string|max:120',
            'detalle' => 'nullable|string|max:255',
            'fecha_fin' => 'nullable|date',
        ]);
        $local->promociones()->create($datos + ['activa' => true]);

        return back()->with('ok', 'Promoción agregada.');
    }

    public function promoDestroy(Promocion $promocion)
    {
        $promocion->delete();

        return back()->with('ok', 'Promoción eliminada.');
    }

    // ---------- Ayudas ----------

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:120',
            'categoria_id' => 'required|exists:categorias,id',
            'tipo_comida' => 'required|string|max:120',
            'precio_promedio' => 'required|integer|min:0|max:200000',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'horario' => 'nullable|string|max:120',
            'telefono_contacto' => 'nullable|string|max:40',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072', // hasta 3 MB
            'quitar_imagen' => 'nullable|boolean',
        ]);
    }

    /** Guarda la foto subida (reemplazando la anterior) o la quita si se marcó "quitar". */
    private function guardarImagen(Request $request, Restaurante $local): void
    {
        if ($request->hasFile('imagen')) {
            if ($local->imagen) {
                Storage::disk('public')->delete($local->imagen);
            }
            $ruta = $request->file('imagen')->store('locales', 'public');
            $local->forceFill(['imagen' => $ruta])->save();
        } elseif ($request->boolean('quitar_imagen') && $local->imagen) {
            Storage::disk('public')->delete($local->imagen);
            $local->forceFill(['imagen' => null])->save();
        }
    }

    /** El precio promedio del local pasa a ser el promedio real de los precios de su menú. */
    private function recalcularPrecio(Restaurante $local): void
    {
        $promedio = $local->productos()->where('disponible', true)->avg('precio');
        if ($promedio !== null) {
            $local->update(['precio_promedio' => (int) round($promedio)]);
        }
    }
}

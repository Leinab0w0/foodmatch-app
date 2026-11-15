<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Restaurante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Panel del DUEÑO de local: administra solo su propio local (datos, foto, menú y ofertas).
 * El local siempre sale de la cuenta (restaurante_id): no se puede tocar otro local.
 */
class MiLocalController extends Controller
{
    private function local(Request $request): Restaurante
    {
        return Restaurante::findOrFail($request->user()->restaurante_id);
    }

    public function index(Request $request)
    {
        $local = $this->local($request)->load([
            'categoria',
            'productos' => fn ($q) => $q->orderBy('nombre'),
            'promociones' => fn ($q) => $q->orderByDesc('id'),
        ]);

        return view('local.panel', [
            'local' => $local,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'vigentes' => $local->promociones()->vigentes()->count(),
        ]);
    }

    public function update(Request $request)
    {
        $local = $this->local($request);
        $datos = $request->validate([
            'nombre' => 'required|string|max:120',
            'categoria_id' => 'required|exists:categorias,id',
            'tipo_comida' => 'required|string|max:120',
            'direccion' => 'nullable|string|max:255',
            'horario' => 'nullable|string|max:120',
            'telefono_contacto' => 'nullable|string|max:40',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        $cambios = collect($datos)->except('imagen')->filter(fn ($v) => $v !== null)->all();
        $local->update($cambios);

        if ($request->hasFile('imagen')) {
            if ($local->imagen) {
                Storage::disk('public')->delete($local->imagen);
            }
            $local->forceFill(['imagen' => $request->file('imagen')->store('locales', 'public')])->save();
        }

        return redirect()->route('local.panel', ['tab' => 'datos'])->with('ok', 'Datos guardados.');
    }

    // ---------- Ofertas ----------

    public function promoStore(Request $request)
    {
        $local = $this->local($request);
        $datos = $request->validate([
            'titulo' => 'required|string|max:120',
            'detalle' => 'nullable|string|max:255',
            'duracion' => 'required|in:hoy,manana,semana,siempre',
        ]);

        $fin = match ($datos['duracion']) {
            'hoy' => now()->toDateString(),
            'manana' => now()->addDay()->toDateString(),
            'semana' => now()->addDays(7)->toDateString(),
            default => null,
        };

        $local->promociones()->create([
            'titulo' => $datos['titulo'],
            'detalle' => $datos['detalle'] ?? null,
            'fecha_fin' => $fin,
            'activa' => true,
        ]);

        return redirect()->route('local.panel', ['tab' => 'ofertas'])->with('ok', '¡Oferta publicada! Ya la ven los clientes.');
    }

    public function promoDestroy(Request $request, Promocion $promocion)
    {
        abort_unless((int) $promocion->restaurante_id === (int) $request->user()->restaurante_id, 403);
        $promocion->delete();

        return redirect()->route('local.panel', ['tab' => 'ofertas'])->with('ok', 'Oferta eliminada.');
    }

    // ---------- Menú ----------

    public function productoStore(Request $request)
    {
        $local = $this->local($request);
        $datos = $request->validate([
            'nombre' => 'required|string|max:120',
            'precio' => 'required|integer|min:100|max:200000',
            'descripcion' => 'nullable|string|max:255',
        ]);
        $local->productos()->create($datos + ['disponible' => true]);
        $this->recalcularPrecio($local);

        return redirect()->route('local.panel', ['tab' => 'menu'])->with('ok', 'Plato agregado.');
    }

    public function productoToggle(Request $request, Producto $producto)
    {
        abort_unless((int) $producto->restaurante_id === (int) $request->user()->restaurante_id, 403);
        $producto->update(['disponible' => ! $producto->disponible]);
        $this->recalcularPrecio($this->local($request));

        return redirect()->route('local.panel', ['tab' => 'menu']);
    }

    public function productoDestroy(Request $request, Producto $producto)
    {
        abort_unless((int) $producto->restaurante_id === (int) $request->user()->restaurante_id, 403);
        $producto->delete();
        $this->recalcularPrecio($this->local($request));

        return redirect()->route('local.panel', ['tab' => 'menu'])->with('ok', 'Plato eliminado.');
    }

    private function recalcularPrecio(Restaurante $local): void
    {
        $promedio = $local->productos()->where('disponible', true)->avg('precio');
        if ($promedio !== null) {
            $local->update(['precio_promedio' => (int) round($promedio)]);
        }
    }
}

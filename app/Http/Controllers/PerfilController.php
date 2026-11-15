<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function show(Request $request)
    {
        return view('perfil', [
            'categorias' => Categoria::orderBy('nombre')->get(),
            'seleccion' => array_filter(explode(',', (string) $request->user()->tipo_comida_preferida)),
        ]);
    }

    /** Guarda presupuesto habitual y comidas favoritas del usuario (Sprint 3). */
    public function guardarPreferencias(Request $request)
    {
        $datos = $request->validate([
            'presupuesto_habitual' => 'nullable|integer|min:1000|max:200000',
            'categorias' => 'nullable|array',
            'categorias.*' => 'exists:categorias,slug',
        ]);

        $request->user()->forceFill([
            'presupuesto_habitual' => $datos['presupuesto_habitual'] ?? null,
            'tipo_comida_preferida' => empty($datos['categorias']) ? null : implode(',', $datos['categorias']),
        ])->save();

        return redirect()->route('perfil')->with('ok', 'Preferencias guardadas. Guardadas.');
    }
}

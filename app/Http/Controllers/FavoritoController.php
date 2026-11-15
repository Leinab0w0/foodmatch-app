<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Restaurante;
use Illuminate\Http\Request;

class FavoritoController extends Controller
{
    /** Lista de locales guardados por el usuario. */
    public function index(Request $request)
    {
        $favoritos = Favorito::where('user_id', $request->user()->id)
            ->with('restaurante.categoria')
            ->latest()
            ->get()
            ->filter(fn ($f) => $f->restaurante);

        return view('favoritos', compact('favoritos'));
    }

    /** Botón ♡: si ya estaba guardado lo quita, si no lo guarda. */
    public function toggle(Request $request, Restaurante $restaurante)
    {
        $existente = Favorito::where('user_id', $request->user()->id)
            ->where('restaurante_id', $restaurante->id);

        if ($existente->exists()) {
            $existente->delete();
            $mensaje = 'Quitado de favoritos.';
        } else {
            Favorito::create(['user_id' => $request->user()->id, 'restaurante_id' => $restaurante->id]);
            $mensaje = 'Guardado en tus favoritos ♥';
        }

        return back()->with('ok', $mensaje);
    }
}

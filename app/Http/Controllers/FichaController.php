<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Restaurante;
use App\Services\Recomendador;
use Illuminate\Http\Request;

class FichaController extends Controller
{
    public function show(Request $request, ?Restaurante $restaurante = null)
    {
        if (! $restaurante) {
            return redirect()->route('explorar');
        }

        $restaurante->load([
            'categoria',
            'productos' => fn ($q) => $q->where('disponible', true)->orderBy('precio'),
            'promociones' => fn ($q) => $q->vigentes(),
        ]);

        $distancia = null;
        if (is_numeric($request->query('lat')) && is_numeric($request->query('lng'))) {
            $distancia = Recomendador::textoDistancia(Recomendador::distanciaMetros(
                (float) $request->query('lat'), (float) $request->query('lng'),
                (float) $restaurante->latitud, (float) $restaurante->longitud
            ));
        }

        // Google Maps encuentra el local por nombre + dirección (las coordenadas de OSM pueden ser imprecisas).
        $destino = $restaurante->nombre . ', ' . ($restaurante->direccion ?: 'Melipilla') . ', Chile';
        $rutaUrl = 'https://www.google.com/maps/dir/?api=1&travelmode=walking&destination=' . rawurlencode($destino);
        if ($distancia) {
            $rutaUrl .= '&origin=' . $request->query('lat') . ',' . $request->query('lng');
        }

        return view('ficha', [
            'r' => $restaurante,
            'promo' => $restaurante->promociones->first(),
            'distancia' => $distancia,
            'rutaUrl' => $rutaUrl,
            'conUbicacion' => array_filter($request->only(['lat', 'lng'])),
            'esFavorito' => Favorito::where('user_id', $request->user()->id)->where('restaurante_id', $restaurante->id)->exists(),
            'estimado' => $restaurante->osm_id && $restaurante->productos->isEmpty(),
        ]);
    }
}

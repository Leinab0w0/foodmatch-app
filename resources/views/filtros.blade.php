@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Filtros')

@section('content')
@php
    // Valores actuales (si ya había filtros puestos, la pantalla los recuerda)
    $presupuesto = (int) request('presupuesto', Auth::user()->presupuesto_habitual ?: 15000);
    $radio = request('radio', 5);
    $seleccion = (array) request('categorias', []);
    $conUbicacion = array_filter(request()->only(['lat', 'lng', 'q']));
@endphp

<div class="app pad">
    <a class="volver" href="{{ route('explorar', $conUbicacion) }}">← Volver</a>
    <h1>Filtrar</h1>
    <div class="meta" style="margin-bottom:12px">Estos filtros valen solo para esta búsqueda.</div>

    {{-- Es un formulario GET: al aplicar, los filtros viajan en la URL hacia /explorar --}}
    <form method="GET" action="{{ route('explorar') }}">

        {{-- Mantiene la ubicación del usuario si ya la teníamos --}}
        @foreach ($conUbicacion as $nombre => $valor)
            <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
        @endforeach

        <div class="meta" style="font-weight:600">💰 Presupuesto máximo · <b id="v-presupuesto" style="color:var(--brand3);font-size:15px">${{ number_format($presupuesto, 0, ',', '.') }}</b></div>
        <input type="range" id="presupuesto" name="presupuesto" min="2000" max="30000" step="500" value="{{ $presupuesto }}">

        <div class="meta" style="font-weight:600">📍 Distancia máxima · <b id="v-radio" style="color:var(--brand3);font-size:15px">{{ str_replace('.', ',', (string) $radio) }} km</b></div>
        <input type="range" id="radio" name="radio" min="0.5" max="10" step="0.5" value="{{ $radio }}">

        <div class="meta" style="margin-bottom:10px;font-weight:600">🍽 Tipo de comida <span style="font-weight:400">(si no marcas ninguno, se muestran todos)</span></div>
        @foreach ($categorias as $c)
            <label class="chip chip-op" style="background:{{ $c->color }}">
                <input type="checkbox" name="categorias[]" value="{{ $c->slug }}" {{ in_array($c->slug, $seleccion) ? 'checked' : '' }}>
                {{ $c->nombre }}
            </label>
        @endforeach

        <label class="rowi" style="margin-top:14px;cursor:pointer">
            <span>🔥 Solo con oferta hoy</span>
            <input type="checkbox" name="oferta" value="1" class="push" {{ request()->boolean('oferta') ? 'checked' : '' }}>
        </label>

        <button type="submit" class="btn" style="width:100%">Aplicar filtros</button>
    </form>

    <a class="btn ghost" href="{{ route('explorar', array_merge($conUbicacion, ['todos' => 1])) }}">Limpiar filtros</a>
</div>

<script>
    // Actualiza el número que se ve al mover cada barra
    document.getElementById('presupuesto').addEventListener('input', function () {
        document.getElementById('v-presupuesto').textContent = '$' + Number(this.value).toLocaleString('es-CL');
    });
    document.getElementById('radio').addEventListener('input', function () {
        document.getElementById('v-radio').textContent = String(this.value).replace('.', ',') + ' km';
    });
</script>
@endsection

@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Comparar')

@section('content')
@php
    $conUbicacion = array_filter(['lat' => $lat, 'lng' => $lng]);
    $minPrecio = $top->min('precio_promedio');
    $minDist = $top->min('distancia_m');
    $maxPuntaje = $top->max('puntaje');
@endphp
<div class="app pad">
    <a class="volver" href="{{ route('explorar', $conUbicacion) }}">← Volver</a>
    <h1>Comparando {{ $top->count() }} locales</h1>

    {{-- Cambiar presupuesto y recalcular --}}
    <form method="GET" action="{{ route('comparar') }}" style="display:flex;gap:8px;align-items:center;margin-bottom:12px">
        @foreach ($conUbicacion as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
        <input type="hidden" name="radio" value="{{ $radioKm }}">
        <span class="meta" style="font-weight:600">Presupuesto $</span>
        <input class="field" style="margin:0;flex:1" type="number" name="presupuesto" step="500" min="1000" value="{{ $presupuesto }}">
        <button class="btn small">OK</button>
    </form>

    @if ($top->isEmpty())
        <div class="vacio"><span class="emoji">🗺️</span>No hay locales dentro de {{ $radioKm }} km. Prueba ampliando la distancia en Filtros.</div>
    @else
        <table>
            <tr>
                <th></th>
                @foreach ($top as $r)
                    <th><a href="{{ route('ficha', array_merge(['restaurante' => $r->id], $conUbicacion)) }}">{{ \Illuminate\Support\Str::limit($r->nombre, 11) }}</a></th>
                @endforeach
            </tr>
            <tr>
                <td>Precio</td>
                @foreach ($top as $r)
                    <td class="{{ $r->precio_promedio == $minPrecio ? 'best ok' : '' }}">
                        {{ ($r->osm_id && ! $r->productos_count) ? '~' : '' }}${{ number_format($r->precio_promedio, 0, ',', '.') }}
                    </td>
                @endforeach
            </tr>
            <tr>
                <td>Distancia</td>
                @foreach ($top as $r)
                    <td class="{{ $r->distancia_m == $minDist ? 'best' : '' }}">{{ $r->distancia_texto }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Promo</td>
                @foreach ($top as $r)
                    <td class="{{ $r->promo ? 'best' : '' }}">{{ $r->promo->titulo ?? '—' }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Presupuesto</td>
                @foreach ($top as $r)
                    @if ($r->dentro_presupuesto)
                        <td class="ok">✔ dentro</td>
                    @else
                        <td class="warn">+${{ number_format($r->precio_promedio - $presupuesto, 0, ',', '.') }}</td>
                    @endif
                @endforeach
            </tr>
            <tr>
                <td>Puntaje</td>
                @foreach ($top as $r)
                    <td class="{{ $r->puntaje == $maxPuntaje ? 'best ok' : '' }}">{{ $r->puntaje }}/100</td>
                @endforeach
            </tr>
        </table>

        <div class="reco">
            <b>⭐ Mejor para ti: {{ $mejor->nombre }}</b><br>
            <span class="meta" style="color:#bfeed8">
                {{ ucfirst(implode(', ', $motivo)) ?: 'Es la mejor combinación de precio, distancia y presupuesto' }}.
                @if ($ahorro > 0) Ahorras ${{ number_format($ahorro, 0, ',', '.') }} frente a la opción más cara. @endif
            </span>
        </div>

        <a class="btn" href="{{ route('ficha', array_merge(['restaurante' => $mejor->id], $conUbicacion)) }}">Ver ficha de {{ $mejor->nombre }}</a>
    @endif

    @include('partials.tab', ['activo' => 'explorar', 'q' => $conUbicacion])
</div>
@endsection

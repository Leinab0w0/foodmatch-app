@extends('layouts.foodmatch')

@section('title', 'FoodMatch · ' . $r->nombre)

@section('content')
<div class="app">
    {{-- Portada: foto del local (o el color de su categoría) --}}
    <div class="hero" style="background:{{ $r->imagen ? "url('" . asset('storage/' . $r->imagen) . "') center/cover, " : '' }}linear-gradient(135deg, {{ $r->categoria->color ?? '#ff5a5f' }}, #1a1638)">
        <a class="volver" href="{{ route('explorar', $conUbicacion) }}">← Volver</a>
    </div>

    <div class="hoja">
        @if (session('ok'))
            <div class="reco" style="margin:0 0 12px;padding:9px 14px">{{ session('ok') }}</div>
        @endif
        @if ($promo)
            <div class="promo">🔥 Hoy: {{ $promo->titulo }}@if ($promo->detalle) · {{ $promo->detalle }}@endif</div>
        @endif

        <h1 style="margin-top:2px">{{ $r->nombre }}</h1>
        <div class="meta" style="font-size:13.5px">
            {{ $r->categoria->nombre ?? $r->tipo_comida }}
            @if ($distancia) · {{ $distancia }} @endif
            @if ($r->horario) · {{ $r->horario }} @endif
        </div>
        @if ($r->direccion)<div class="meta" style="margin-top:6px">📍 {{ $r->direccion }}</div>@endif
        @if ($r->telefono_contacto)<div class="meta">📞 <a href="tel:{{ preg_replace('/[^0-9+]/', '', $r->telefono_contacto) }}" style="color:var(--brand3)">{{ $r->telefono_contacto }}</a></div>@endif

        {{-- Botones principales: Cómo llegar | ♡ Guardar --}}
        <div style="display:flex;gap:10px;margin-top:6px">
            <a class="btn" style="flex:1" href="{{ $rutaUrl }}" target="_blank" rel="noopener">🧭 Cómo llegar</a>
            <form method="POST" action="{{ route('favoritos.toggle', $r) }}" style="flex:1;margin:0">
                @csrf
                <button class="btn ghost" style="width:100%;{{ $esFavorito ? 'color:var(--pink);border-color:var(--pink)' : '' }}">
                    {{ $esFavorito ? '♥ Guardado' : '♡ Guardar' }}
                </button>
            </form>
        </div>

        <div class="h2">🍽 Menú</div>
        @forelse ($r->productos as $p)
            <div class="rowi">
                <span style="min-width:0">{{ $p->nombre }}
                    @if ($p->descripcion)<br><span class="meta">{{ $p->descripcion }}</span>@endif
                </span>
                <span class="ok push">${{ number_format($p->precio, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="tarjeta meta" style="margin-top:0">
                Este local aún no tiene menú cargado.
                Precio {{ $estimado ? 'estimado' : 'promedio' }}:
                <b style="color:var(--ink)">{{ $estimado ? '~' : '' }}${{ number_format($r->precio_promedio, 0, ',', '.') }}</b>
            </div>
        @endforelse

        <a class="btn ghost" href="{{ route('comparar', $conUbicacion) }}">⭐ Comparar con otros locales</a>
    </div>

    @include('partials.tab', ['activo' => 'explorar', 'q' => $conUbicacion])
</div>
@endsection

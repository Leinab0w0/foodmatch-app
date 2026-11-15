@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Favoritos')

@section('content')
<div class="app pad">
    <div class="rainbow"></div>
    <div class="logo">Mis <span>favoritos</span></div>
    <div class="meta" style="margin:4px 0 14px">Tus locales guardados, a un toque.</div>

    @if (session('ok'))
        <div class="reco" style="margin:8px 0;padding:9px 14px">{{ session('ok') }}</div>
    @endif

    @forelse ($favoritos as $f)
        @php $r = $f->restaurante; @endphp
        <div class="rowi">
            @if ($r->imagen)
                <span class="thumb" style="background-image:url('{{ asset('storage/' . $r->imagen) }}')"></span>
            @else
                <span class="dot" style="background:{{ $r->categoria->color ?? '#6b6862' }};color:{{ $r->categoria->color ?? '#6b6862' }}"></span>
            @endif
            <a href="{{ route('ficha', $r) }}" style="flex:1;min-width:0">
                <b>{{ $r->nombre }}</b><br>
                <span class="meta">{{ $r->categoria->nombre ?? $r->tipo_comida }}@if ($r->horario) · {{ $r->horario }}@endif</span>
            </a>
            <span class="ok">${{ number_format($r->precio_promedio, 0, ',', '.') }}</span>
            <form method="POST" action="{{ route('favoritos.toggle', $r) }}" style="margin:0">
                @csrf
                <button class="linkbtn" title="Quitar de favoritos" style="color:var(--pink);font-size:20px">♥</button>
            </form>
        </div>
    @empty
        <div class="vacio"><span class="emoji">💔</span>Aún no tienes favoritos.<br>Abre la ficha de un local y toca <b>♡ Guardar</b>.</div>
        <a class="btn" href="{{ route('explorar') }}">Ir a Explorar</a>
    @endforelse

    @include('partials.tab', ['activo' => 'favoritos'])
</div>
@endsection

@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Locales')

@section('content')
<div class="app pad">
    <a class="volver" href="{{ route('admin.inicio') }}">← Panel admin</a>
    <h1>Locales <span class="meta">({{ $locales->count() }})</span></h1>

    @if (session('ok'))
        <div class="reco" style="margin:8px 0">{{ session('ok') }}</div>
    @endif

    <a class="btn" style="margin-top:8px" href="{{ route('admin.locales.create') }}">+ Nuevo local</a>

    <div style="margin-top:14px">
        @foreach ($locales as $l)
            <div class="rowi">
                @if ($l->imagen)
                    <span class="thumb" style="width:42px;height:42px;background-image:url('{{ asset('storage/' . $l->imagen) }}')"></span>
                @else
                    <span class="dot" style="background:{{ $l->categoria->color ?? '#6b6862' }};color:{{ $l->categoria->color ?? '#6b6862' }}"></span>
                @endif
                <a href="{{ route('admin.locales.edit', $l) }}" style="flex:1;min-width:0">
                    <b>{{ $l->nombre }}</b>
                    @if (isset($duenos[$l->id])) <span class="etiqueta dueno">con dueño</span> @endif
                    <br><span class="meta">{{ $l->productos_count }} productos · ${{ number_format($l->precio_promedio, 0, ',', '.') }}</span>
                </a>
                <a class="linkbtn" style="color:var(--brand3)" href="{{ route('admin.locales.edit', $l) }}">Editar</a>
                <form method="POST" action="{{ route('admin.locales.destroy', $l) }}" style="margin:0"
                      onsubmit="return confirm('¿Eliminar {{ e($l->nombre) }}?')">
                    @csrf @method('DELETE')
                    <button class="linkbtn rojo">Eliminar</button>
                </form>
            </div>
        @endforeach
    </div>
</div>
@endsection

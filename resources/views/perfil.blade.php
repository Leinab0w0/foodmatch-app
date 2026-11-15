@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Perfil')

@section('content')
@php
    $u = Auth::user();
    $esDueno = ! empty($u->restaurante_id);
    $esSuper = \App\Support\Roles::esSuperadmin($u);
@endphp
<div class="app pad">
    <div class="rainbow"></div>
    <div class="logo">Mi <span>perfil</span></div>

    <div class="tarjeta" style="display:flex;align-items:center;gap:14px">
        <div style="width:54px;height:54px;border-radius:18px;background:var(--grad);display:grid;place-items:center;font-size:24px;font-weight:800;flex:none">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</div>
        <div style="min-width:0">
            <b style="font-size:17px">{{ $u->name }}</b>
            @if ($esSuper)<span class="etiqueta super">superadmin</span>
            @elseif ($u->es_admin)<span class="etiqueta admin">admin</span>
            @elseif ($esDueno)<span class="etiqueta dueno">dueño de local</span>@endif
            <br><span class="meta" style="word-break:break-all">{{ $u->email }}</span>
        </div>
    </div>

    @if (session('ok'))
        <div class="reco" style="margin:12px 0 0;padding:9px 14px">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">@foreach ($errors->all() as $e) • {{ $e }}<br> @endforeach</div>
    @endif

    {{-- Accesos según el rol --}}
    @if ($esDueno)
        <a class="btn" href="{{ route('local.panel') }}">🏪 Administrar mi local</a>
    @endif
    @if ($u->es_admin)
        <a class="btn {{ $esDueno ? 'ghost' : '' }}" href="{{ route('admin.inicio') }}">⚙ Panel de administración</a>
    @endif
    @if ($u->es_admin || $esDueno)
        <a class="btn ghost" href="{{ $esSuper ? route('admin.bandeja') : route('soporte') }}">✉️ {{ $esSuper ? 'Mensajes recibidos' : 'Ayuda: escribir a FoodMatch' }}</a>
    @endif

    @if (! $esDueno)
        <a class="btn ghost" href="{{ route('solicitud') }}">🏪 ¿Tienes un local? Pide ser dueño</a>
    @endif

    <a class="rowi" href="{{ route('favoritos') }}" style="margin-top:14px">
        <b>♥ Mis favoritos</b>
        <span class="meta push">{{ \App\Models\Favorito::where('user_id', Auth::id())->count() }} guardados →</span>
    </a>

    {{-- Preferencias: el presupuesto se sugiere en Filtros; Explorar siempre parte mostrando todo --}}
    <div class="h2">🎯 Mis preferencias</div>
    <form method="POST" action="{{ route('perfil.preferencias') }}" class="tarjeta" style="margin-top:0">
        @csrf
        <div class="meta" style="font-weight:600">Presupuesto habitual ($)</div>
        <input class="field" style="margin-top:6px" type="number" name="presupuesto_habitual" min="1000" step="500"
               placeholder="ej: 10000" value="{{ old('presupuesto_habitual', $u->presupuesto_habitual) }}">

        <div class="meta" style="margin:14px 0 8px;font-weight:600">Comidas favoritas</div>
        @foreach ($categorias as $c)
            <label class="chip chip-op c-{{ $c->slug }}" style="background:{{ $c->color }}">
                <input type="checkbox" name="categorias[]" value="{{ $c->slug }}" @checked(in_array($c->slug, old('categorias', $seleccion)))>
                {{ $c->nombre }}
            </label>
        @endforeach

        <button class="btn" style="width:100%">Guardar preferencias</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn ghost" style="width:100%">Cerrar sesión</button>
    </form>

    @include('partials.tab', ['activo' => 'perfil'])
</div>
@endsection

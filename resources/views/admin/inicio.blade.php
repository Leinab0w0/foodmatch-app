@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Admin')

@section('content')
@php
    $esSuper = \App\Support\Roles::esSuperadmin(Auth::user());
    $sinLeer = $esSuper ? \App\Models\Mensaje::whereNull('respondido_at')->count() : 0;
    $avisos = $esSuper ? \App\Models\Actividad::pendientes() : 0;
@endphp
<div class="app pad">
    <div class="rainbow"></div>
    <div class="logo">Panel <span>Admin</span></div>
    <div class="meta" style="margin:4px 0 6px">Hola, {{ Auth::user()->name }} @if ($esSuper)<span class="etiqueta super">superadmin</span>@endif</div>

    <div class="acciones-grid">
        <a href="{{ route('admin.locales.index') }}"><b>🏪</b>Locales y dueños</a>
        <a href="{{ route('admin.usuarios.index') }}"><b>👥</b>Usuarios</a>
        <a href="{{ $esSuper ? route('admin.bandeja') : route('soporte') }}"><b>✉️</b>{{ $esSuper ? 'Mensajes' : 'Escribir al superadmin' }}
            @if ($sinLeer) <span class="etiqueta super">{{ $sinLeer }} nuevos</span> @endif</a>
        @if ($esSuper)
            <a href="{{ route('admin.actividad') }}"><b>🔔</b>Actividad de admins
                @if ($avisos) <span class="etiqueta super">{{ $avisos }} por revisar</span> @endif</a>
        @endif
        <a href="{{ route('explorar') }}"><b>🧭</b>Ir a Explorar</a>
    </div>

    @include('partials.tab', ['activo' => 'perfil'])
</div>
@endsection

@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Crear cuenta')

@section('content')
<div class="app acceso">
    <div class="marca">
        <div class="icono">✨</div>
        <div class="logo">Crea tu <span>cuenta</span></div>
        <div class="meta" style="margin-top:4px;font-size:14px">Guarda tus favoritos y recibe recomendaciones</div>
    </div>

    <div class="tarjeta">
        @if ($errors->any())
            <div class="reco error" style="margin:0 0 8px">
                @foreach ($errors->all() as $e) • {{ $e }}<br> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <input class="field" style="margin-top:0" type="text" name="name" placeholder="Nombre" value="{{ old('name') }}" required autofocus autocomplete="name">
            <input class="field" type="email" name="email" placeholder="Correo (te enviaremos un enlace para verificarlo)" value="{{ old('email') }}" required autocomplete="username">
            <input class="field" type="password" name="password" placeholder="Contraseña (mín. 8 caracteres)" required autocomplete="new-password">
            <input class="field" type="password" name="password_confirmation" placeholder="Repite la contraseña" required autocomplete="new-password">
            <button class="btn" style="width:100%">Crear cuenta</button>
        </form>
    </div>

    <a class="meta" style="display:block;text-align:center;margin-top:16px" href="{{ route('login') }}">¿Ya tienes cuenta? <b style="color:var(--brand3)">Inicia sesión</b></a>
</div>
@endsection

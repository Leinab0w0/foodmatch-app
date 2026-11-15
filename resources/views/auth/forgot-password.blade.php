@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Recuperar contraseña')

@section('content')
<div class="app acceso">
    <div class="marca">
        <div class="icono">🔑</div>
        <div class="logo">Recupera tu <span>acceso</span></div>
        <div class="meta" style="margin-top:4px;font-size:14px">Escribe tu correo y te enviaremos un enlace para crear una contraseña nueva.</div>
    </div>

    <div class="tarjeta">
        @if (session('status'))
            <div class="reco" style="margin:0 0 8px">✉️ {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="reco error" style="margin:0 0 8px">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <input class="field" style="margin-top:0" type="email" name="email" placeholder="Correo" value="{{ old('email') }}" required autofocus>
            <button class="btn" style="width:100%">Enviar enlace</button>
        </form>
    </div>

    <a class="meta" style="display:block;text-align:center;margin-top:16px" href="{{ route('login') }}">← Volver a iniciar sesión</a>
</div>
@endsection

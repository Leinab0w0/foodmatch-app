@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Iniciar sesión')

@section('content')
<div class="app acceso">
    <div class="marca">
        <div class="icono">🍴</div>
        <div class="logo">Food<span>Match</span></div>
        <div class="meta" style="margin-top:4px;font-size:14px">Encuentra dónde comer, según tu antojo y tu bolsillo</div>
    </div>

    <div class="tarjeta">
        @if (session('status'))
            <div class="reco" style="margin:0 0 8px">✉️ {{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="reco error" style="margin:0 0 8px">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input class="field" style="margin-top:0" type="email" name="email" placeholder="Correo" value="{{ old('email') }}" required autofocus autocomplete="username">
            <input class="field" type="password" name="password" placeholder="Contraseña" required autocomplete="current-password">
            <label class="check meta" style="margin-top:12px"><input type="checkbox" name="remember"> Mantener sesión iniciada</label>
            <button class="btn" style="width:100%">Entrar</button>
        </form>

        @if (Route::has('password.request'))
            <a class="meta" style="display:block;text-align:center;margin-top:14px" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        @endif
    </div>

    <a class="btn ghost" href="{{ route('register') }}">Crear cuenta nueva</a>
</div>
@endsection

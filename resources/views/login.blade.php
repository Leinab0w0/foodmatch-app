@extends('layouts.app')

@section('title', 'FoodMatch · Entrar')

@section('content')
<div class="app pad" style="display:flex;flex-direction:column;justify-content:center;min-height:100vh">
    <div class="rainbow"></div>
    <div class="logo">Food<span>Match</span></div>
    <div class="meta" style="margin:4px 0 18px">Qué comer, dónde y si te alcanza</div>

    @if (session('status'))
        <div class="meta" style="color:var(--sushi);margin-bottom:8px">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <input class="field" type="email" name="email" value="{{ old('email') }}" placeholder="Correo" required autofocus>
        @error('email')
            <div class="meta" style="color:var(--asian)">{{ $message }}</div>
        @enderror

        <input class="field" type="password" name="password" placeholder="Contraseña" required>
        @error('password')
            <div class="meta" style="color:var(--asian)">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn" style="width:100%;border:none;cursor:pointer">Entrar</button>
    </form>

    <div class="meta" style="text-align:center;margin-top:14px">
        <a href="{{ route('register') }}">Crear cuenta</a> ·
        <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
    </div>
</div>
@endsection
@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Nueva contraseña')

@section('content')
<div class="app acceso">
    <div class="marca">
        <div class="icono">🔐</div>
        <div class="logo">Nueva <span>contraseña</span></div>
        <div class="meta" style="margin-top:4px;font-size:14px">Elige una contraseña nueva para tu cuenta.</div>
    </div>

    <div class="tarjeta">
        @if ($errors->any())
            <div class="reco error" style="margin:0 0 8px">
                @foreach ($errors->all() as $e) • {{ $e }}<br> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <input class="field" style="margin-top:0" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username">
            <input class="field" type="password" name="password" placeholder="Contraseña nueva" required autofocus autocomplete="new-password">
            <input class="field" type="password" name="password_confirmation" placeholder="Repite la contraseña" required autocomplete="new-password">
            <button class="btn" style="width:100%">Guardar contraseña</button>
        </form>
    </div>
</div>
@endsection

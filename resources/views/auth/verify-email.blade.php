@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Verifica tu correo')

@section('content')
<div class="app acceso">
    <div class="marca">
        <div class="icono">✉️</div>
        <div class="logo">Verifica tu <span>correo</span></div>
        <div class="meta" style="margin-top:6px;font-size:14px">
            Enviamos un enlace a <b style="color:var(--ink)">{{ Auth::user()->email }}</b>.
            Ábrelo para confirmar que es tuyo (revisa también spam).
        </div>
    </div>

    <div class="tarjeta">
        @if (session('status') === 'verification-link-sent')
            <div class="reco" style="margin:0 0 8px">✉️ Listo, te enviamos un enlace nuevo.</div>
        @endif
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="btn" style="width:100%;margin-top:0">Reenviar correo de verificación</button>
        </form>
    </div>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:14px">
        @csrf
        <button class="linkbtn meta" style="display:block;width:100%;text-align:center;text-decoration:underline">Cerrar sesión / usar otro correo</button>
    </form>
</div>
@endsection

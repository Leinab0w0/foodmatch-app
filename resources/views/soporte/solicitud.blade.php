@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Soy dueño de un local')

@section('content')
<div class="app pad">
    <a class="volver" href="{{ route('perfil') }}">← Perfil</a>
    <h1>¿Tienes un local? 🏪</h1>
    <div class="meta">Pide el permiso de dueño y podrás subir tus ofertas, tu menú y tu foto para que te encuentren los clientes. Revisamos cada solicitud y te respondemos aquí mismo.</div>

    @if (session('ok'))
        <div class="reco" style="margin:12px 0 0">✅ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">@foreach ($errors->all() as $e) • {{ $e }}<br> @endforeach</div>
    @endif

    <form method="POST" action="{{ route('solicitud.enviar') }}" class="tarjeta">
        @csrf
        <input class="field" style="margin-top:0" name="local" placeholder="Nombre de tu local" maxlength="100" required value="{{ old('local') }}">
        <input class="field" name="direccion" placeholder="Dirección (calle, número y comuna)" maxlength="160" required value="{{ old('direccion') }}">
        <input class="field" name="telefono" placeholder="Teléfono / WhatsApp para contactarte" maxlength="40" required value="{{ old('telefono') }}">
        <textarea class="field" name="comentario" rows="3" placeholder="Algo más que quieras contarnos (opcional)" maxlength="800">{{ old('comentario') }}</textarea>
        <button class="btn" style="width:100%">Enviar solicitud</button>
    </form>

    @if ($mensajes->isNotEmpty())
        <div class="h2">Mis solicitudes</div>
        @foreach ($mensajes as $m)
            <div class="tarjeta" style="margin-top:0;margin-bottom:10px">
                <b>{{ $m->asunto }}</b> <span class="meta">· {{ $m->created_at->diffForHumans() }}</span>
                @if ($m->respuesta)
                    <div class="burbuja resp"><small>Respuesta</small>{!! nl2br(e($m->respuesta)) !!}</div>
                @else
                    <div class="meta" style="margin-top:8px">⏳ En revisión</div>
                @endif
            </div>
        @endforeach
    @endif

    @include('partials.tab', ['activo' => 'perfil'])
</div>
@endsection

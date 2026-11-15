@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Ayuda')

@section('content')
<div class="app pad">
    <a class="volver" href="{{ Auth::user()->restaurante_id ? route('local.panel') : route('admin.inicio') }}">← Volver</a>
    <h1>Escríbenos ✉️</h1>
    <div class="meta">Tu mensaje le llega directo a la persona que administra FoodMatch. La respuesta aparece aquí abajo.</div>

    @if (session('ok'))
        <div class="reco" style="margin:12px 0 0">✅ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">@foreach ($errors->all() as $e) • {{ $e }}<br> @endforeach</div>
    @endif

    <form method="POST" action="{{ route('soporte.enviar') }}" class="tarjeta">
        @csrf
        <input class="field" style="margin-top:0" name="asunto" placeholder="Asunto (ej: No puedo subir mi foto)" maxlength="120" required value="{{ old('asunto') }}">
        <textarea class="field" name="cuerpo" rows="4" placeholder="Cuéntanos qué necesitas…" maxlength="2000" required>{{ old('cuerpo') }}</textarea>
        <button class="btn" style="width:100%">Enviar mensaje</button>
    </form>

    <div class="h2">Mis mensajes</div>
    @forelse ($mensajes as $m)
        <div class="tarjeta" style="margin-top:0;margin-bottom:10px">
            <b>{{ $m->asunto }}</b> <span class="meta">· {{ $m->created_at->diffForHumans() }}</span>
            <div class="burbuja"><small>Tú</small>{!! nl2br(e($m->cuerpo)) !!}</div>
            @if ($m->respuesta)
                <div class="burbuja resp"><small>Respuesta · {{ $m->respondido_at?->diffForHumans() }}</small>{!! nl2br(e($m->respuesta)) !!}</div>
            @else
                <div class="meta" style="margin-top:8px">⏳ Esperando respuesta</div>
            @endif
        </div>
    @empty
        <div class="vacio"><span class="emoji">📭</span>Aún no has enviado mensajes.</div>
    @endforelse

    @include('partials.tab', ['activo' => 'perfil'])
</div>
@endsection

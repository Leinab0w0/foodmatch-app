@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Mensajes')

@section('content')
@php
    $locales = \App\Models\Restaurante::orderBy('nombre')->pluck('nombre', 'id');
    $avisos = \App\Models\Actividad::pendientes();
@endphp
<div class="app pad">
    <a class="volver" href="{{ route('admin.inicio') }}">← Panel admin</a>
    <h1>Mensajes 📬</h1>
    <div class="meta">Lo que te escriben los admins y los dueños de local. Los sin responder van primero.</div>

    @if (session('ok'))
        <div class="reco" style="margin:12px 0 0">✅ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">{{ $errors->first() }}</div>
    @endif

    <a class="btn ghost" style="margin-top:12px" href="{{ route('admin.actividad') }}">🔔 Actividad de admins @if ($avisos)<span class="etiqueta super">{{ $avisos }}</span>@endif</a>

    <div style="margin-top:14px">
    @forelse ($mensajes as $m)
        <div class="tarjeta" style="margin-top:0;margin-bottom:12px">
            <div>
                @if (! $m->respondido_at && ! $m->leido_at)<span class="nuevo"></span>@endif
                <b>{{ $m->asunto }}</b>
            </div>
            <div class="meta" style="margin-top:2px">
                {{ $m->autor->name ?? 'Usuario eliminado' }}
                @if ($m->autor?->es_admin) <span class="etiqueta admin">admin</span>
                @elseif ($m->autor?->restaurante_id) <span class="etiqueta dueno">{{ \Illuminate\Support\Str::limit($locales[$m->autor->restaurante_id] ?? 'local', 20) }}</span>
                @else <span class="etiqueta">usuario</span>@endif
                · {{ $m->created_at->diffForHumans() }}
                <br><span style="word-break:break-all">{{ $m->autor->email ?? '' }}</span>
            </div>
            <div class="burbuja">{!! nl2br(e($m->cuerpo)) !!}</div>

            @if ($m->respuesta)
                <div class="burbuja resp"><small>Tu respuesta · {{ $m->respondido_at?->diffForHumans() }}</small>{!! nl2br(e($m->respuesta)) !!}</div>
            @endif

            {{-- Permiso de dueño: solo el superadmin lo da --}}
            @if ($m->autor && ! $m->autor->restaurante_id)
                <form method="POST" action="{{ route('admin.bandeja.dueno', $m) }}" class="tarjeta" style="margin-top:10px;background:rgba(139,107,255,.12);border-color:rgba(139,107,255,.35)">
                    @csrf
                    <div class="meta" style="font-weight:700;color:#d6ccff">🏪 Darle permiso de dueño</div>
                    <select class="field" name="restaurante_id" required>
                        <option value="">Elige el local que administrará…</option>
                        @foreach ($locales as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <button class="btn violet small" style="margin-top:10px">Autorizar y avisarle</button>
                    <div class="meta" style="margin-top:6px">¿El local no está en la lista? Créalo primero en <a href="{{ route('admin.locales.create') }}" style="color:var(--brand3)">Locales → Nuevo</a>.</div>
                </form>
            @elseif ($m->autor && $m->autor->restaurante_id)
                <div class="meta" style="margin-top:8px">✔ Ya administra «{{ $locales[$m->autor->restaurante_id] ?? 'un local' }}».</div>
            @endif

            <form method="POST" action="{{ route('admin.bandeja.responder', $m) }}">
                @csrf
                <textarea class="field" name="respuesta" rows="2" placeholder="{{ $m->respuesta ? 'Editar respuesta…' : 'Escribe tu respuesta…' }}" required></textarea>
                <button class="btn small" style="margin-top:10px">{{ $m->respuesta ? 'Actualizar respuesta' : 'Responder' }}</button>
            </form>
            @if (! $m->leido_at && ! $m->respondido_at)
                <form method="POST" action="{{ route('admin.bandeja.leido', $m) }}" style="margin-top:6px">
                    @csrf <button class="linkbtn meta">Marcar como leído</button>
                </form>
            @endif
        </div>
    @empty
        <div class="vacio"><span class="emoji">📭</span>No hay mensajes todavía.</div>
    @endforelse
    </div>

    @include('partials.tab', ['activo' => 'perfil'])
</div>
@endsection

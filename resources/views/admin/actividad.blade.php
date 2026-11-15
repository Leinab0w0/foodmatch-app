@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Actividad de admins')

@section('content')
@php $pend = $actividades->filter(fn ($a) => ! $a->visto_at && ! $a->revertido_at)->count(); @endphp
<div class="app pad">
    <a class="volver" href="{{ route('admin.inicio') }}">← Panel admin</a>
    <h1>Actividad de admins 🔔</h1>
    <div class="meta">Cada vez que un admin crea o elimina un local te aparece aquí. Si fue un error, lo deshaces con un toque.</div>

    @if (session('ok'))
        <div class="reco" style="margin:12px 0 0">✅ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">{{ $errors->first() }}</div>
    @endif

    @if ($pend)
        <form method="POST" action="{{ route('admin.actividad.todo') }}">
            @csrf <button class="btn ghost small" style="margin-top:12px">✓ Marcar todo como revisado ({{ $pend }})</button>
        </form>
    @endif

    <div style="margin-top:14px">
    @forelse ($actividades as $a)
        @php
            $eliminado = $a->accion === 'local_eliminado';
            $estado = $a->revertido_at ? 'Deshecho' : ($a->visto_at ? 'Revisado' : 'Nuevo');
        @endphp
        <div class="tarjeta" style="margin-top:0;margin-bottom:10px;{{ $a->revertido_at ? 'opacity:.6' : '' }}">
            <div>
                @if (! $a->visto_at && ! $a->revertido_at)<span class="nuevo"></span>@endif
                <b>{{ $eliminado ? '🗑️' : '➕' }} {{ $a->titulo }}</b>
            </div>
            <div class="meta" style="margin-top:3px">
                {{ $a->autor->name ?? 'Admin eliminado' }} · {{ $a->created_at->diffForHumans() }}
                · <span class="etiqueta {{ $a->revertido_at ? '' : ($a->visto_at ? 'admin' : 'super') }}">{{ $estado }}</span>
            </div>

            @unless ($a->revertido_at)
                <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                    <form method="POST" action="{{ route('admin.actividad.deshacer', $a) }}" style="margin:0"
                          onsubmit="return confirm('{{ $eliminado ? '¿Restaurar este local con su menú y ofertas?' : '¿Eliminar este local que se creó?' }}')">
                        @csrf
                        <button class="btn small {{ $eliminado ? '' : 'danger' }}">{{ $eliminado ? '↩️ Restaurar local' : '↩️ Deshacer (eliminar)' }}</button>
                    </form>
                    @unless ($a->visto_at)
                        <form method="POST" action="{{ route('admin.actividad.visto', $a) }}" style="margin:0">
                            @csrf <button class="btn ghost small">✓ Está bien</button>
                        </form>
                    @endunless
                </div>
            @endunless
        </div>
    @empty
        <div class="vacio"><span class="emoji">🛡️</span>Los admins aún no han creado ni eliminado nada.</div>
    @endforelse
    </div>

    @include('partials.tab', ['activo' => 'perfil'])
</div>
@endsection

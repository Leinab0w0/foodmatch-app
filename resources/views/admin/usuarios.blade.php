@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Usuarios')

@section('content')
<div class="app pad">
    <a class="volver" href="{{ route('admin.inicio') }}">← Panel admin</a>
    <h1>Usuarios <span class="meta">({{ $usuarios->count() }})</span></h1>

    @if (session('ok'))
        <div class="reco" style="margin:8px 0">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">{{ $errors->first() }}</div>
    @endif

    <div style="margin-top:12px">
    @foreach ($usuarios as $u)
        <div class="rowi" style="flex-wrap:wrap">
            <div style="flex:1;min-width:0">
                <b>{{ $u->name }}</b>
                @if ($u->id === $superadminId) <span class="etiqueta super">superadmin</span>
                @elseif ($u->es_admin) <span class="etiqueta admin">admin</span>
                @elseif ($u->restaurante_id) <span class="etiqueta dueno">dueño · {{ \Illuminate\Support\Str::limit($locales[$u->restaurante_id] ?? 'local', 18) }}</span> @endif
                <br><span class="meta" style="word-break:break-all">{{ $u->email }}</span>
            </div>

            @unless ($u->id === $superadminId && ! $soySuperadmin)
                <form method="POST" action="{{ route('admin.usuarios.password', $u) }}" style="margin:0"
                      onsubmit="return confirm('¿Resetear la contraseña de {{ e($u->email) }}?')">
                    @csrf
                    <button class="linkbtn" style="color:var(--brand3)">Resetear clave</button>
                </form>
            @endunless

            @if ($soySuperadmin)
                <form method="POST" action="{{ route('admin.usuarios.local', $u) }}" style="margin:0;display:flex;gap:6px;width:100%;align-items:center">
                    @csrf
                    <select class="field" name="restaurante_id" style="margin:0;padding:8px 10px;font-size:14px">
                        <option value="">Sin local (no es dueño)</option>
                        @foreach ($locales as $id => $nombre)
                            <option value="{{ $id }}" @selected($u->restaurante_id == $id)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <button class="btn small ghost">Guardar</button>
                </form>
            @endif

            @if ($soySuperadmin && $u->id !== auth()->id())
                <form method="POST" action="{{ route('admin.usuarios.admin', $u) }}" style="margin:0">
                    @csrf
                    <button class="linkbtn {{ $u->es_admin ? 'rojo' : 'ok' }}">{{ $u->es_admin ? 'Quitar admin' : 'Hacer admin' }}</button>
                </form>
            @endif
        </div>
    @endforeach
    </div>

    @unless ($soySuperadmin)
        <div class="meta" style="margin-top:12px">Solo el superadmin da el rol de admin y el permiso de dueño de un local.</div>
    @endunless
</div>
@endsection

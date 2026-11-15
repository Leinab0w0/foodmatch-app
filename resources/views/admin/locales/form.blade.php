@extends('layouts.foodmatch')

@section('title', 'FoodMatch · ' . ($local->exists ? 'Editar local' : 'Nuevo local'))

@section('content')
<div class="app pad">
    <a class="volver" href="{{ route('admin.locales.index') }}">← Locales</a>
    <h1>{{ $local->exists ? 'Editar: ' . $local->nombre : 'Nuevo local' }}</h1>

    @if (session('ok'))
        <div class="reco" style="margin:8px 0">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">
            @foreach ($errors->all() as $e) • {{ $e }}<br> @endforeach
        </div>
    @endif

    {{-- ===== Datos del local ===== --}}
    <form method="POST" enctype="multipart/form-data" action="{{ $local->exists ? route('admin.locales.update', $local) : route('admin.locales.store') }}">
        @csrf
        @if ($local->exists) @method('PUT') @endif

        <input class="field" name="nombre" placeholder="Nombre del local" value="{{ old('nombre', $local->nombre) }}" required>
        <select class="field" name="categoria_id" required>
            <option value="">Categoría…</option>
            @foreach ($categorias as $c)
                <option value="{{ $c->id }}" @selected(old('categoria_id', $local->categoria_id) == $c->id)>{{ $c->nombre }}</option>
            @endforeach
        </select>
        <input class="field" name="tipo_comida" placeholder="Tipo de comida (ej: Completos y churrascos)" value="{{ old('tipo_comida', $local->tipo_comida) }}" required>
        <input class="field" type="number" name="precio_promedio" placeholder="Precio promedio en $" value="{{ old('precio_promedio', $local->precio_promedio) }}" required>
        <input class="field" name="direccion" placeholder="Dirección real (calle y número)" value="{{ old('direccion', $local->direccion) }}">
        <input class="field" name="latitud" placeholder="Latitud (ej: -33.6881)" value="{{ old('latitud', $local->latitud) }}" required>
        <input class="field" name="longitud" placeholder="Longitud (ej: -71.2153)" value="{{ old('longitud', $local->longitud) }}" required>
        <div class="meta" style="margin-top:4px">Tip: en Google Maps haz click derecho sobre el local y copia las coordenadas.</div>
        <input class="field" name="horario" placeholder="Horario (ej: Lun-Sáb 10:00–22:00)" value="{{ old('horario', $local->horario) }}">
        <input class="field" name="telefono_contacto" placeholder="Teléfono (opcional)" value="{{ old('telefono_contacto', $local->telefono_contacto) }}">

        {{-- Foto del local (opcional) --}}
        <div class="meta" style="margin:12px 0 4px">Foto del local (JPG, PNG o WEBP, máx. 3 MB)</div>
        @if ($local->imagen)
            <img src="{{ asset('storage/' . $local->imagen) }}" alt="Foto actual"
                 style="width:100%;height:120px;object-fit:cover;border-radius:10px;margin-bottom:6px">
            <label class="meta"><input type="checkbox" name="quitar_imagen" value="1"> Quitar foto actual</label>
        @endif
        <input class="field" type="file" name="imagen" accept="image/jpeg,image/png,image/webp">

        <button class="btn" style="width:100%">{{ $local->exists ? 'Guardar cambios' : 'Crear local' }}</button>
    </form>

    @if ($local->exists)
        {{-- ===== Dueño del local: solo el superadmin autoriza ===== --}}
        @if (\App\Support\Roles::esSuperadmin(Auth::user()))
        <div class="h2">🔑 Dueño del local</div>
        <div class="meta" style="margin-bottom:8px">El dueño entra con su cuenta y administra solo este local: su menú, sus ofertas y su foto.</div>
        @foreach ($duenos as $d)
            <div class="rowi">
                <span style="flex:1;min-width:0"><b>{{ $d->name }}</b><br><span class="meta" style="word-break:break-all">{{ $d->email }}</span></span>
                <form method="POST" action="{{ route('admin.usuarios.password', $d) }}" style="margin:0" onsubmit="return confirm('¿Generar una clave temporal nueva?')">
                    @csrf <button class="linkbtn" style="color:var(--brand3)">Nueva clave</button>
                </form>
                <form method="POST" action="{{ route('admin.dueno.quitar', [$local, $d]) }}" style="margin:0" onsubmit="return confirm('¿Quitar el acceso a este local?')">
                    @csrf @method('DELETE') <button class="linkbtn rojo">Quitar</button>
                </form>
            </div>
        @endforeach
        <form method="POST" action="{{ route('admin.dueno.store', $local) }}" class="tarjeta" style="margin-top:6px">
            @csrf
            <div class="meta" style="font-weight:600">{{ $duenos->isEmpty() ? 'Crear cuenta para el dueño' : 'Agregar otra persona (ej: un encargado)' }}</div>
            <input class="field" name="name" placeholder="Nombre del dueño" required>
            <input class="field" type="email" name="email" placeholder="Correo del dueño" required>
            <button class="btn ghost" style="width:100%">Crear cuenta</button>
        </form>
        @else
            <div class="h2">🔑 Dueño del local</div>
            <div class="meta">Los dueños los autoriza el superadmin. Si alguien pide ser dueño de este local, avísale por «Escríbenos».</div>
        @endif
    @endif

    @if ($local->exists)
        {{-- ===== Menú ===== --}}
        <div class="h2">🍽 Menú / productos</div>
        <div class="meta">Al agregar productos, el precio promedio del local se calcula solo.</div>
        @forelse ($local->productos as $p)
            <div class="rowi">
                <span style="flex:1">{{ $p->nombre }}</span>
                <span class="ok">${{ number_format($p->precio, 0, ',', '.') }}</span>
                <form method="POST" action="{{ route('admin.productos.destroy', $p) }}" style="margin:0">
                    @csrf @method('DELETE')
                    <button class="linkbtn rojo">✕</button>
                </form>
            </div>
        @empty
            <div class="meta" style="padding:8px 0">Sin productos todavía.</div>
        @endforelse

        <form method="POST" action="{{ route('admin.productos.store', $local) }}">
            @csrf
            <input class="field" name="nombre" placeholder="Producto (ej: Churrasco italiano)" required>
            <input class="field" type="number" name="precio" placeholder="Precio en $" required>
            <input class="field" name="descripcion" placeholder="Descripción (opcional)">
            <button class="btn ghost" style="width:100%">+ Agregar producto</button>
        </form>

        {{-- ===== Promociones ===== --}}
        <div class="h2">🔥 Promociones</div>
        @forelse ($local->promociones as $pr)
            <div class="rowi">
                <span style="flex:1">🔥 {{ $pr->titulo }} <span class="meta">{{ $pr->detalle }}</span></span>
                <form method="POST" action="{{ route('admin.promos.destroy', $pr) }}" style="margin:0">
                    @csrf @method('DELETE')
                    <button class="linkbtn rojo">✕</button>
                </form>
            </div>
        @empty
            <div class="meta" style="padding:8px 0">Sin promociones.</div>
        @endforelse

        <form method="POST" action="{{ route('admin.promos.store', $local) }}">
            @csrf
            <input class="field" name="titulo" placeholder="Promoción (ej: 2x1 en completos)" required>
            <input class="field" name="detalle" placeholder="Detalle (ej: Hasta las 20:00)">
            <input class="field" type="date" name="fecha_fin" title="Válida hasta (opcional)">
            <button class="btn ghost" style="width:100%">+ Agregar promoción</button>
        </form>
    @endif
</div>
@endsection

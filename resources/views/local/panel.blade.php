@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Mi local')

@section('content')
@php $tab = request('tab', 'ofertas'); @endphp
<div class="app pad">
    <div class="rainbow"></div>

    {{-- Portada del local --}}
    <div class="portada" style="background-image:{{ $local->imagen ? "url('" . asset('storage/' . $local->imagen) . "')" : 'linear-gradient(135deg,' . ($local->categoria->color ?? '#ff5a5f') . ',#1a1638)' }}">
        <div class="nombre">{{ $local->nombre }}<div class="meta" style="color:#ddd9f5;font-weight:500;font-size:12.5px">{{ $local->categoria->nombre ?? $local->tipo_comida }} · visible en FoodMatch</div></div>
    </div>

    <div class="stats">
        <div><b>{{ $vigentes }}</b><span>ofertas activas</span></div>
        <div><b>{{ $local->productos->count() }}</b><span>platos en el menú</span></div>
        <div><b>${{ number_format($local->precio_promedio, 0, ',', '.') }}</b><span>precio promedio</span></div>
    </div>

    @if (session('ok'))
        <div class="reco" style="margin:12px 0 0;padding:10px 14px">✅ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="reco error">@foreach ($errors->all() as $e) • {{ $e }}<br> @endforeach</div>
    @endif

    <div class="segmentos" id="segmentos">
        <button type="button" data-tab="ofertas" class="{{ $tab === 'ofertas' ? 'on' : '' }}">🔥 Ofertas</button>
        <button type="button" data-tab="menu" class="{{ $tab === 'menu' ? 'on' : '' }}">🍽 Menú</button>
        <button type="button" data-tab="datos" class="{{ $tab === 'datos' ? 'on' : '' }}">📝 Mi local</button>
    </div>

    {{-- ================= OFERTAS ================= --}}
    <section class="seccion {{ $tab === 'ofertas' ? 'on' : '' }}" id="tab-ofertas">
        <form method="POST" action="{{ route('local.ofertas.store') }}" class="tarjeta">
            @csrf
            <div style="font-weight:800;font-size:16px">Publicar una oferta</div>
            <div class="meta">Aparece al tiro en el mapa y en la lista de quienes buscan cerca.</div>
            <input class="field" name="titulo" placeholder="Ej: 2x1 en completos" maxlength="120" required>
            <input class="field" name="detalle" placeholder="Detalle (opcional): solo con efectivo, hasta agotar stock…" maxlength="255">
            <div class="meta" style="margin-top:12px;font-weight:600">¿Hasta cuándo vale?</div>
            <div class="opciones">
                <label><input type="radio" name="duracion" value="hoy" checked><span>Solo hoy</span></label>
                <label><input type="radio" name="duracion" value="manana"><span>Hasta mañana</span></label>
                <label><input type="radio" name="duracion" value="semana"><span>7 días</span></label>
                <label><input type="radio" name="duracion" value="siempre"><span>Sin fecha</span></label>
            </div>
            <button class="btn" style="width:100%">🔥 Publicar oferta</button>
        </form>

        <div class="h2">Mis ofertas</div>
        @forelse ($local->promociones as $pr)
            @php $vence = $pr->fecha_fin ? \Illuminate\Support\Carbon::parse($pr->fecha_fin) : null; $vencida = $vence && $vence->endOfDay()->isPast(); @endphp
            <div class="rowi {{ $vencida ? 'agotado' : '' }}">
                <span style="flex:1;min-width:0"><b>🔥 {{ $pr->titulo }}</b>
                    <br><span class="meta">{{ $pr->detalle ? $pr->detalle . ' · ' : '' }}{{ $vencida ? 'Venció el ' . $vence->format('d/m') : ($vence ? 'Hasta el ' . $vence->format('d/m') : 'Sin fecha de término') }}</span></span>
                <form method="POST" action="{{ route('local.ofertas.destroy', $pr) }}" style="margin:0" onsubmit="return confirm('¿Eliminar esta oferta?')">
                    @csrf @method('DELETE')
                    <button class="linkbtn rojo">Quitar</button>
                </form>
            </div>
        @empty
            <div class="vacio"><span class="emoji">🏷️</span>Aún no tienes ofertas.<br>Publica la primera arriba: toma 10 segundos.</div>
        @endforelse
    </section>

    {{-- ================= MENÚ ================= --}}
    <section class="seccion {{ $tab === 'menu' ? 'on' : '' }}" id="tab-menu">
        <form method="POST" action="{{ route('local.menu.store') }}" class="tarjeta">
            @csrf
            <div style="font-weight:800;font-size:16px">Agregar un plato</div>
            <div class="meta">El precio promedio de tu local se calcula solo con tu menú.</div>
            <input class="field" name="nombre" placeholder="Nombre del plato (ej: Churrasco italiano)" maxlength="120" required>
            <input class="field" type="number" inputmode="numeric" name="precio" placeholder="Precio en $ (ej: 4500)" min="100" required>
            <input class="field" name="descripcion" placeholder="Descripción (opcional)" maxlength="255">
            <button class="btn" style="width:100%">+ Agregar al menú</button>
        </form>

        <div class="h2">Mi menú <span class="meta">({{ $local->productos->count() }})</span></div>
        @forelse ($local->productos as $p)
            <div class="rowi {{ $p->disponible ? '' : 'agotado' }}">
                <span style="flex:1;min-width:0"><b>{{ $p->nombre }}</b>
                    <br><span class="meta">{{ $p->disponible ? 'Disponible' : 'Agotado hoy' }}@if ($p->descripcion) · {{ $p->descripcion }}@endif</span></span>
                <span class="ok">${{ number_format($p->precio, 0, ',', '.') }}</span>
                <form method="POST" action="{{ route('local.menu.toggle', $p) }}" style="margin:0">
                    @csrf
                    <button class="linkbtn" style="color:var(--brand3)">{{ $p->disponible ? 'Agotado' : 'Volver' }}</button>
                </form>
                <form method="POST" action="{{ route('local.menu.destroy', $p) }}" style="margin:0" onsubmit="return confirm('¿Eliminar este plato?')">
                    @csrf @method('DELETE')
                    <button class="linkbtn rojo">✕</button>
                </form>
            </div>
        @empty
            <div class="vacio"><span class="emoji">🍽️</span>Tu menú está vacío.<br>Agrega tus platos para que te comparen mejor.</div>
        @endforelse
    </section>

    {{-- ================= DATOS ================= --}}
    <section class="seccion {{ $tab === 'datos' ? 'on' : '' }}" id="tab-datos">
        <form method="POST" action="{{ route('local.datos') }}" enctype="multipart/form-data" class="tarjeta">
            @csrf
            <div style="font-weight:800;font-size:16px">Datos de tu local</div>
            <input class="field" name="nombre" placeholder="Nombre del local" value="{{ old('nombre', $local->nombre) }}" required>
            <select class="field" name="categoria_id" required>
                @foreach ($categorias as $c)
                    <option value="{{ $c->id }}" @selected(old('categoria_id', $local->categoria_id) == $c->id)>{{ $c->nombre }}</option>
                @endforeach
            </select>
            <input class="field" name="tipo_comida" placeholder="Qué vendes (ej: Completos y churrascos)" value="{{ old('tipo_comida', $local->tipo_comida) }}" required>
            <input class="field" name="direccion" placeholder="Dirección (calle y número)" value="{{ old('direccion', $local->direccion) }}">
            <input class="field" name="horario" placeholder="Horario (ej: Lun-Sáb 10:00–22:00)" value="{{ old('horario', $local->horario) }}">
            <input class="field" name="telefono_contacto" placeholder="Teléfono / WhatsApp" value="{{ old('telefono_contacto', $local->telefono_contacto) }}">

            <div class="meta" style="margin:14px 0 4px;font-weight:600">📸 Foto del local (JPG, PNG o WEBP · máx. 3 MB)</div>
            <input class="field" style="margin-top:0" type="file" name="imagen" accept="image/jpeg,image/png,image/webp">

            {{-- Ubicación exacta en el mapa: se toma desde el celular, estando en el local --}}
            <input type="hidden" name="latitud" id="lat" value="{{ $local->latitud }}">
            <input type="hidden" name="longitud" id="lng" value="{{ $local->longitud }}">
            <button type="button" class="btn ghost" id="btn-pos" style="width:100%">📍 Estoy en mi local: usar mi ubicación en el mapa</button>
            <div class="meta" id="pos-msg" style="margin-top:6px">Ubicación actual guardada: {{ $local->latitud }}, {{ $local->longitud }}</div>

            <button class="btn" style="width:100%">Guardar cambios</button>
        </form>
    </section>

    <a class="btn ghost" href="{{ route('soporte') }}">✉️ ¿Necesitas ayuda? Escríbenos</a>
    <a class="btn ghost" href="{{ route('ficha', $local) }}">👀 Ver cómo se ve mi local</a>

    @include('partials.tab', ['activo' => 'local'])
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('#segmentos button');
    function mostrar(nombre) {
        botones.forEach(function (b) { b.classList.toggle('on', b.dataset.tab === nombre); });
        document.querySelectorAll('.seccion').forEach(function (s) { s.classList.toggle('on', s.id === 'tab-' + nombre); });
        try { history.replaceState(null, '', '?tab=' + nombre); } catch (e) {}
    }
    botones.forEach(function (b) { b.addEventListener('click', function () { mostrar(b.dataset.tab); }); });

    const btn = document.getElementById('btn-pos'), msg = document.getElementById('pos-msg');
    btn.addEventListener('click', function () {
        if (!navigator.geolocation || !window.isSecureContext) { msg.textContent = 'Para usar la ubicación abre FoodMatch con https://.'; return; }
        msg.textContent = 'Buscando tu ubicación…';
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('lat').value = pos.coords.latitude.toFixed(6);
            document.getElementById('lng').value = pos.coords.longitude.toFixed(6);
            msg.textContent = '✅ Ubicación tomada. Toca «Guardar cambios» para confirmarla.';
        }, function () { msg.textContent = 'No pudimos obtener tu ubicación. Permite el acceso a la ubicación e inténtalo de nuevo.'; },
        { enableHighAccuracy: true, timeout: 12000 });
    });
});
</script>
@endsection

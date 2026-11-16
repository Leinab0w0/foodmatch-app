@extends('layouts.foodmatch')

@section('title', 'FoodMatch · Explorar')

@section('content')
<style>
    /* ===== Explorar: pantalla completa y panel deslizable ===== */
    .app.explorar { height: 100vh; height: 100dvh; padding: 0; }
    .app.explorar .sheet {
        position: absolute; left: 0; right: 0; bottom: 0; top: auto;
        height: calc(100% - 128px);
        padding: 0; display: flex; flex-direction: column;
        z-index: 2; will-change: transform;
    }
    .sheet-head { padding: 12px 18px 10px; cursor: grab; touch-action: none; user-select: none; -webkit-user-select: none; }
    .sheet-head:active { cursor: grabbing; }
    .sheet-titulo { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 800; }
    .sheet-titulo .cuenta { font-weight: 600; color: var(--sub); font-size: 12.5px; }
    .btn-vista { margin-left: auto; border: 1px solid var(--line); background: var(--card2); border-radius: 18px;
                 padding: 7px 13px; font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: inherit; color: var(--ink); }
    .sheet-body { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 0 16px; -webkit-overflow-scrolling: touch; }
    .sheet-body .fin { height: var(--tab-h, 76px); }

    .fab { position: absolute; right: 14px; top: -58px; width: 46px; height: 46px; border-radius: 50%;
           border: 1px solid var(--line); background: rgba(22,18,52,.92); backdrop-filter: blur(12px);
           box-shadow: 0 8px 22px rgba(0,0,0,.5); font-size: 19px; cursor: pointer; transition: opacity .2s; }
    .app.explorar .sheet.lleno .fab { opacity: 0; pointer-events: none; }

    .app.explorar .leaflet-top { top: 112px; }
    .toast { position: absolute; left: 14px; right: 14px; top: 116px; z-index: 4; background: rgba(255,107,107,.95); color: #fff;
             border-radius: 14px; padding: 11px 15px; font-size: 13px; font-weight: 600; line-height: 1.4; display: none;
             box-shadow: 0 10px 28px rgba(0,0,0,.5); }
    .toast.on { display: block; }

    .pin-fm { position: relative; width: 30px; height: 30px; }
    .pin-fm i { position: absolute; inset: 0; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 2.5px solid #fff;
                box-shadow: 0 0 0 4px rgba(255,255,255,.12), 0 6px 14px rgba(0,0,0,.55); }
    .pin-fm span { position: absolute; inset: 0; display: grid; place-items: center; font-size: 13px; z-index: 1; padding-bottom: 3px; }
</style>

<div class="app explorar" id="app">

    {{-- Mapa real (Leaflet + OpenStreetMap). Los pines vienen de la base de datos. --}}
    <div id="mapa-real" class="map"></div>

    <div class="searchbar">
        {{-- Buscador: escribe y presiona Enter. Mantiene ubicación y filtros activos. --}}
        <form method="GET" action="{{ route('explorar') }}" style="display:flex;flex:1;margin:0">
            @foreach (request()->except('q') as $nombre => $valor)
                @if (is_array($valor))
                    @foreach ($valor as $v)
                        <input type="hidden" name="{{ $nombre }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
                @endif
            @endforeach
            <input type="search" name="q" value="{{ $texto }}" placeholder="¿Qué se te antoja?"
                   style="flex:1;border:0;outline:0;font-size:16px;font-family:inherit;background:transparent;min-width:0;color:var(--ink)">
        </form>
        <a href="{{ route('filtros', request()->query()) }}"><b>Filtros ⚙</b></a>
    </div>
    <div class="chips">
        @foreach ($resumen as $chip)
            <span class="chip o">{{ $chip }}</span>
        @endforeach
    </div>

    <div class="toast" id="aviso" role="status"></div>

    {{-- Panel deslizable: arrastra la manija hacia arriba/abajo, o toca para abrir y cerrar --}}
    <div class="sheet" id="sheet">
        <button class="fab" id="btn-ubicar" aria-label="Ir a mi ubicación" title="Mi ubicación">📍</button>

        <div class="sheet-head" id="sheet-head" role="button" tabindex="0" aria-label="Lista de locales: arrastra o toca para abrir">
            <div class="grab"></div>
            <div class="sheet-titulo">
                <span>Cerca de ti</span>
                <span class="cuenta">{{ $total }} {{ $total === 1 ? 'local' : 'locales' }}</span>
                <button type="button" class="btn-vista" id="btn-vista">📋 Ver lista</button>
            </div>
        </div>

        <div class="sheet-body" id="sheet-body">
            <a class="btn" style="margin:2px 0 8px" href="{{ route('comparar', request()->query()) }}">⭐ Comparar y ver el mejor para ti</a>
            @forelse ($cercanos as $r)
                <a class="rowi" href="{{ route('ficha', array_merge(['restaurante' => $r->id], array_filter(request()->only(['lat', 'lng'])))) }}">
                    @if ($r->imagen)
                        <span class="thumb" style="background-image:url('{{ asset('storage/' . $r->imagen) }}')"></span>
                    @else
                        <span class="dot" style="background:{{ $r->categoria->color ?? '#6b6862' }}"></span>
                    @endif
                    <span style="min-width:0">
                        <b>{{ $r->nombre }}</b><br>
                        <span class="meta">
                            @if ($r->promo) 🔥 {{ $r->promo->titulo }} · @endif{{ $r->distancia_texto }}
                        </span>
                    </span>
                    {{-- Los locales importados de OpenStreetMap tienen precio estimado (~) --}}
                    <span class="ok push">{{ ($r->osm_id && ! $r->productos_count) ? '~' : '' }}${{ number_format($r->precio_promedio, 0, ',', '.') }}</span>
                </a>
            @empty
                <div class="vacio"><span class="emoji">🍽️</span>
                    @if ($hayFiltros)
                        Ningún local cumple esos filtros.
                        <a href="{{ route('explorar', array_merge(array_filter(request()->only(['lat', 'lng'])), ['todos' => 1])) }}"><b>Limpiar filtros</b></a>
                    @else
                        Aún no hay locales cargados.
                    @endif
                </div>
            @endforelse

            <div class="fin"></div>
        </div>
    </div>

    @include('partials.tab', ['activo' => 'explorar', 'q' => array_filter(request()->only(['lat', 'lng']))])
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const centro = @json($centro);
    const pines = @json($pines);
    const ubicacionReal = @json($ubicacionReal);

    /* ---------- Avisos cortos ---------- */
    const aviso = document.getElementById('aviso');
    let avisoTimer;
    function mostrarAviso(texto) {
        aviso.textContent = texto;
        aviso.classList.add('on');
        clearTimeout(avisoTimer);
        avisoTimer = setTimeout(function () { aviso.classList.remove('on'); }, 7000);
    }

    /* ---------- Mapa ---------- */
    const map = L.map('mapa-real').setView([centro.lat, centro.lng], 15); // botones +/- arriba a la derecha (bajo el buscador)

    // Mapa de OpenStreetMap (gratis, sin clave). El tono oscuro se logra con un filtro CSS (ver .leaflet-tile-pane).
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 19
    }).addTo(map);

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function pin(color) {
        return L.divIcon({
            className: '',
            html: '<div class="pin-fm"><i style="background:' + color + '"></i></div>',
            iconSize: [30, 30], iconAnchor: [15, 30], popupAnchor: [0, -28]
        });
    }

    // "Cómo llegar": Google Maps busca por nombre + ciudad (las coordenadas de OSM pueden caer en el vecino).
    function rutaUrl(p) {
        const destino = p.nombre + ', Melipilla, Chile';
        let url = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destino) + '&travelmode=walking';
        if (ubicacionReal) { url += '&origin=' + centro.lat + ',' + centro.lng; }
        return url;
    }

    pines.forEach(function (p) {
        const foto = p.imagen ? '<img src="' + esc(p.imagen) + '" alt="" style="width:100%;height:70px;object-fit:cover;border-radius:8px;margin-bottom:6px">' : '';
        const promo = p.promo ? '<br>🔥 ' + esc(p.promo) : '';
        const html = foto + '<b>' + esc(p.nombre) + '</b><br>' + esc(p.distancia) + promo
            + '<br><a href="' + esc(p.url) + '">Ver ficha →</a> · <a href="' + rutaUrl(p) + '" target="_blank" rel="noopener">Cómo llegar →</a>';
        L.marker([p.lat, p.lng], { icon: pin(p.color) }).addTo(map).bindPopup(html);
    });

    /* ---------- Ubicación real (punto azul) ---------- */
    let capaPunto = null;
    function dibujarPunto(lat, lng, precision) {
        if (capaPunto) { map.removeLayer(capaPunto); }
        capaPunto = L.layerGroup([
            L.circle([lat, lng], { radius: Math.min(Math.max(precision || 60, 30), 500), color: '#2f6fed', weight: 1, fillColor: '#2f6fed', fillOpacity: .12, interactive: false }),
            L.circleMarker([lat, lng], { radius: 8, color: '#fff', weight: 3, fillColor: '#2f6fed', fillOpacity: 1 }).bindPopup('Estás aquí')
        ]).addTo(map);
    }
    function guardarPos(lat, lng) {
        try { localStorage.setItem('fm_pos', JSON.stringify({ lat: lat, lng: lng, t: Date.now() })); } catch (e) {}
    }
    function leerPos() {
        try {
            const p = JSON.parse(localStorage.getItem('fm_pos') || 'null');
            if (p && Date.now() - p.t < 6 * 3600 * 1000) { return p; }
        } catch (e) {}
        return null;
    }
    // Recarga una sola vez para que la lista calcule distancias desde tu posición
    function recargarConPos(lat, lng) {
        try {
            if (sessionStorage.getItem('fm_recarga') && Date.now() - Number(sessionStorage.getItem('fm_recarga')) < 60000) { return; }
            sessionStorage.setItem('fm_recarga', String(Date.now()));
        } catch (e) {}
        const url = new URL(window.location.href);
        url.searchParams.set('lat', Number(lat).toFixed(6));
        url.searchParams.set('lng', Number(lng).toFixed(6));
        window.location.replace(url.toString());
    }

    function errorUbicacion(err) {
        if (err && err.code === 1) {
            mostrarAviso('La ubicación está bloqueada. Toca el candado 🔒 junto a la dirección, permite "Ubicación" y vuelve a tocar 📍. Mientras tanto usamos el centro de Melipilla.');
        } else if (err && err.code === 2) {
            mostrarAviso('No pudimos detectar dónde estás (¿GPS apagado?). Usamos el centro de Melipilla.');
        } else {
            mostrarAviso('Tardó demasiado en encontrar tu ubicación. Toca 📍 para reintentar.');
        }
    }

    function pedirUbicacion(esClick, intentoPreciso) {
        if (!navigator.geolocation) { mostrarAviso('Este navegador no permite usar la ubicación.'); return; }
        if (!window.isSecureContext) { mostrarAviso('La ubicación solo funciona en una dirección segura (https://).'); return; }
        const preciso = intentoPreciso !== false;
        navigator.geolocation.getCurrentPosition(function (pos) {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            guardarPos(lat, lng);
            dibujarPunto(lat, lng, pos.coords.accuracy);   // se ve al instante
            if (!ubicacionReal) {
                map.flyTo([lat, lng], 16);
                recargarConPos(lat, lng);                   // y la lista toma tu posición
            } else if (esClick) {
                map.flyTo([lat, lng], 16);
                setPosicion('peek');
            }
        }, function (err) {
            if (preciso && err && err.code !== 1) { pedirUbicacion(esClick, false); return; } // reintento sin GPS fino (PC / Wi-Fi)
            if (esClick || !ubicacionReal) { errorUbicacion(err); }
        }, preciso
            ? { timeout: 8000, enableHighAccuracy: true, maximumAge: 30000 }
            : { timeout: 15000, enableHighAccuracy: false, maximumAge: 300000 });
    }

    document.getElementById('btn-ubicar').addEventListener('click', function () {
        pedirUbicacion(true);
    });

    if (ubicacionReal) {
        dibujarPunto(centro.lat, centro.lng, 60);
        guardarPos(centro.lat, centro.lng);
        pedirUbicacion(false);                 // refresca el punto con la posición actual
    } else {
        const guardada = leerPos();
        if (guardada) {                        // volviste desde otra pantalla: usa la última posición conocida
            dibujarPunto(guardada.lat, guardada.lng, 60);
            recargarConPos(guardada.lat, guardada.lng);
        }
        pedirUbicacion(false);
    }

    /* ---------- Panel deslizable ---------- */
    const sheet = document.getElementById('sheet');
    const head = document.getElementById('sheet-head');
    const body = document.getElementById('sheet-body');
    const btnVista = document.getElementById('btn-vista');
    const tab = document.querySelector('.tab');

    let snaps = { lleno: 0, medio: 0, peek: 0 };
    let y = 0;           // desplazamiento actual (px hacia abajo)
    let posicion = 'peek';

    function medir() {
        const H = sheet.offsetHeight;
        const tabH = (tab ? tab.offsetHeight : 48) + 14; // la barra flota 12px sobre el borde
        document.documentElement.style.setProperty('--tab-h', (tabH + 16) + 'px');
        snaps.lleno = 0;
        snaps.peek = Math.max(80, H - (tabH + 128));          // solo se ve el encabezado + 1 local
        snaps.medio = Math.round(snaps.peek * 0.45);
    }

    function aplicar(v, animar) {
        y = v;
        sheet.style.transition = animar ? 'transform .28s cubic-bezier(.2,.8,.2,1)' : 'none';
        sheet.style.transform = 'translateY(' + v + 'px)';
    }

    function masCercana(v) {
        let mejor = 'peek', dist = Infinity;
        Object.keys(snaps).forEach(function (k) {
            const d = Math.abs(snaps[k] - v);
            if (d < dist) { dist = d; mejor = k; }
        });
        return mejor;
    }

    function setPosicion(nombre) {
        posicion = nombre;
        aplicar(snaps[nombre], true);
        sheet.classList.toggle('lleno', nombre === 'lleno');
        body.style.overflowY = nombre === 'peek' ? 'hidden' : 'auto';
        btnVista.textContent = nombre === 'peek' ? '📋 Ver lista' : '🗺 Ver mapa';
        head.setAttribute('aria-expanded', nombre === 'peek' ? 'false' : 'true');
        if (nombre === 'peek') { body.scrollTop = 0; }
    }

    // Arrastre con dedo o mouse (Pointer Events)
    let arrastrando = false, movido = false, y0 = 0, pos0 = 0, ultY = 0, ultT = 0, vel = 0;

    head.addEventListener('pointerdown', function (e) {
        if (e.target.closest('.btn-vista')) { return; }
        arrastrando = true; movido = false;
        y0 = e.clientY; pos0 = y; ultY = e.clientY; ultT = performance.now(); vel = 0;
        head.setPointerCapture(e.pointerId);
    });

    head.addEventListener('pointermove', function (e) {
        if (!arrastrando) { return; }
        const dy = e.clientY - y0;
        if (Math.abs(dy) > 4) { movido = true; }
        const t = performance.now();
        vel = (e.clientY - ultY) / Math.max(1, t - ultT); // px por ms
        ultY = e.clientY; ultT = t;
        aplicar(Math.min(snaps.peek, Math.max(0, pos0 + dy)), false);
    });

    function soltar() {
        if (!arrastrando) { return; }
        arrastrando = false;
        if (!movido) { // un toque: alterna abrir / cerrar
            setPosicion(posicion === 'peek' ? 'medio' : 'peek');
            return;
        }
        setPosicion(masCercana(y + vel * 200)); // un "empujón" rápido avanza más
    }
    head.addEventListener('pointerup', soltar);
    head.addEventListener('pointercancel', soltar);

    head.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            setPosicion(posicion === 'peek' ? 'medio' : 'peek');
        } else if (e.key === 'ArrowUp') {
            setPosicion(posicion === 'peek' ? 'medio' : 'lleno');
        } else if (e.key === 'ArrowDown') {
            setPosicion(posicion === 'lleno' ? 'medio' : 'peek');
        }
    });

    // Botón "Ver lista / Ver mapa"
    btnVista.addEventListener('click', function () {
        setPosicion(posicion === 'peek' ? 'lleno' : 'peek');
    });

    // Tocar el mapa con el panel abierto lo cierra
    map.on('click', function () { if (posicion !== 'peek') { setPosicion('peek'); } });

    // Reajustar al girar el celular o cambiar el tamaño de la ventana
    function reajustar() { medir(); aplicar(snaps[posicion], false); map.invalidateSize(); }
    window.addEventListener('resize', reajustar);
    window.addEventListener('orientationchange', function () { setTimeout(reajustar, 250); });

    medir();
    aplicar(snaps.peek, false);
    setPosicion('peek');
});
</script>
@endsection

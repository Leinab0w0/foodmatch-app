<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    {{-- viewport-fit=cover: usa toda la pantalla del celular (incluida la zona de la "muesca") --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'FoodMatch')</title>

    {{-- App instalable (PWA): al abrirla en el celular se puede "Agregar a pantalla de inicio" --}}
    <meta name="theme-color" content="#0c0a1d">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FoodMatch">

    {{-- Tipografía (si no hay Internet se usa la del sistema) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">

    <link rel="stylesheet" href="{{ asset('css/foodmatch.css') }}?v={{ @filemtime(public_path('css/foodmatch.css')) }}">

    {{-- Mapa: Leaflet 1.9.4 (gratis, sin clave) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        html { -webkit-text-size-adjust: 100%; color-scheme: dark; }
        body { overscroll-behavior-y: none; -webkit-tap-highlight-color: transparent; }
        input, select, textarea { font-size: 16px; }   /* evita el zoom automático de iPhone al escribir */
        a, button { touch-action: manipulation; }
        .app { min-height: 100vh; min-height: 100dvh; }
        .btn, .btn.ghost { min-height: 46px; }
    </style>
</head>
<body>
    @yield('content')

    <script>
        // Service worker: permite instalar la app y mostrar un aviso si se pierde la conexión
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {});
            });
        }
    </script>
</body>
</html>

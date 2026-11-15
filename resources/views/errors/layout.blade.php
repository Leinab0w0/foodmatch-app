<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0c0a1d">
    <title>@yield('titulo') · FoodMatch</title>
    {{-- Página independiente (no usa el layout principal) para que siga funcionando aunque algo haya fallado --}}
    <link rel="stylesheet" href="{{ asset('css/foodmatch.css') }}">
    <style>html{color-scheme:dark}</style>
</head>
<body>
<div class="app acceso" style="text-align:center">
    <div class="logo" style="margin-bottom:18px">Food<span>Match</span></div>

    <div style="font-size:88px;font-weight:800;line-height:1;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent">@yield('codigo')</div>
    <div style="font-size:52px;margin:8px 0">@yield('emoji')</div>
    <div style="font-weight:800;font-size:22px;margin-bottom:6px">@yield('titulo')</div>
    <div class="meta" style="font-size:14px;margin-bottom:22px">@yield('mensaje')</div>

    <a class="btn" href="{{ url('/explorar') }}">Volver a Explorar</a>
    <a class="btn ghost" href="javascript:history.back()">← Volver atrás</a>
</div>
</body>
</html>

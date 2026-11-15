{{-- Barra inferior. Uso: @include('partials.tab', ['activo' => 'explorar', 'q' => []]) --}}
@php
    $u = Auth::user();
    $activo = $activo ?? '';
    $q = $q ?? [];
    $esDueno = ! empty($u->restaurante_id);
    $sinLeer = ($u && \App\Support\Roles::esSuperadmin($u))
        ? \App\Models\Mensaje::whereNull('respondido_at')->whereNull('leido_at')->count() + \App\Models\Actividad::pendientes() : 0;
@endphp
<nav class="tab" aria-label="Navegación">
    <a class="{{ $activo === 'explorar' ? 'on' : '' }}" href="{{ route('explorar', $q) }}"><b>🧭</b>Explorar</a>
    <a class="{{ $activo === 'favoritos' ? 'on' : '' }}" href="{{ route('favoritos') }}"><b>♥</b>Favoritos</a>
    @if ($esDueno)
        <a class="{{ $activo === 'local' ? 'on' : '' }}" href="{{ route('local.panel') }}"><b>🏪</b>Mi local</a>
    @endif
    <a class="punto {{ $activo === 'perfil' ? 'on' : '' }}" href="{{ route('perfil') }}"><b>👤</b>Perfil @if ($sinLeer)<i>{{ $sinLeer }}</i>@endif</a>
</nav>

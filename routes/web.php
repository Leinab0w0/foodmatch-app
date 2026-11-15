<?php

use App\Http\Controllers\Admin\LocalController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\ComparadorController;
use App\Http\Controllers\ExplorarController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\Admin\ActividadController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\MiLocalController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EsAdmin;
use App\Http\Middleware\EsLocal;
use Illuminate\Support\Facades\Route;

// La portada manda al login real de Breeze.
Route::redirect('/', '/login');

// Pantallas de FoodMatch: sesión iniciada Y correo verificado.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/explorar', [ExplorarController::class, 'index'])->name('explorar');
    Route::get('/filtros', [ExplorarController::class, 'filtros'])->name('filtros');
    Route::get('/comparar', [ComparadorController::class, 'index'])->name('comparar');
    Route::get('/ficha/{restaurante?}', [FichaController::class, 'show'])->name('ficha');
    Route::get('/favoritos', [FavoritoController::class, 'index'])->name('favoritos');
    Route::post('/favoritos/{restaurante}', [FavoritoController::class, 'toggle'])->name('favoritos.toggle');
    Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil');
    Route::post('/perfil/preferencias', [PerfilController::class, 'guardarPreferencias'])->name('perfil.preferencias');

    // Ayuda: admins y dueños de local le escriben al superadmin dentro de la app
    Route::get('/soporte', [MensajeController::class, 'soporte'])->name('soporte');
    Route::post('/soporte', [MensajeController::class, 'enviar'])->name('soporte.enviar');

    // Cualquier usuario puede pedir ser dueño de un local
    Route::get('/solicitar-local', [MensajeController::class, 'solicitud'])->name('solicitud');
    Route::post('/solicitar-local', [MensajeController::class, 'solicitudEnviar'])->name('solicitud.enviar');
});

// Panel del DUEÑO de local: solo su propio local
Route::middleware(['auth', 'verified', EsLocal::class])->prefix('mi-local')->name('local.')->group(function () {
    Route::get('/', [MiLocalController::class, 'index'])->name('panel');
    Route::post('/datos', [MiLocalController::class, 'update'])->name('datos');
    Route::post('/ofertas', [MiLocalController::class, 'promoStore'])->name('ofertas.store');
    Route::delete('/ofertas/{promocion}', [MiLocalController::class, 'promoDestroy'])->name('ofertas.destroy');
    Route::post('/menu', [MiLocalController::class, 'productoStore'])->name('menu.store');
    Route::post('/menu/{producto}/disponible', [MiLocalController::class, 'productoToggle'])->name('menu.toggle');
    Route::delete('/menu/{producto}', [MiLocalController::class, 'productoDestroy'])->name('menu.destroy');
});

// Panel de administración: sesión iniciada Y rol de admin.
Route::middleware(['auth', 'verified', EsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.inicio')->name('inicio');

    // CRUD de locales (los parámetros se llaman "local" para enlazar con Restaurante)
    Route::resource('locales', LocalController::class)->parameters(['locales' => 'local'])->except('show');

    // Usuarios: resetear contraseña (admin) y dar/quitar admin (solo superadmin)
    Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('usuarios/{usuario}/password', [UsuarioController::class, 'resetPassword'])->name('usuarios.password');
    Route::post('usuarios/{usuario}/admin', [UsuarioController::class, 'toggleAdmin'])->name('usuarios.admin');

    // Productos (menú) y promociones de cada local
    // Bandeja de mensajes (solo el superadmin)
    Route::get('bandeja', [MensajeController::class, 'bandeja'])->name('bandeja');
    Route::post('bandeja/{mensaje}/responder', [MensajeController::class, 'responder'])->name('bandeja.responder');
    Route::post('bandeja/{mensaje}/dueno', [MensajeController::class, 'darDueno'])->name('bandeja.dueno');
    Route::post('bandeja/{mensaje}/leido', [MensajeController::class, 'marcarLeido'])->name('bandeja.leido');

    // Actividad de los admins (aviso + deshacer): solo superadmin
    Route::get('actividad', [ActividadController::class, 'index'])->name('actividad');
    Route::post('actividad/visto-todo', [ActividadController::class, 'vistoTodo'])->name('actividad.todo');
    Route::post('actividad/{actividad}/visto', [ActividadController::class, 'visto'])->name('actividad.visto');
    Route::post('actividad/{actividad}/deshacer', [ActividadController::class, 'deshacer'])->name('actividad.deshacer');

    // Permiso de dueño (asignar/quitar un local a una cuenta): solo superadmin
    Route::post('usuarios/{usuario}/local', [UsuarioController::class, 'asignarLocal'])->name('usuarios.local');

    // Cuenta del dueño de cada local
    Route::post('locales/{local}/dueno', [LocalController::class, 'duenoStore'])->name('dueno.store');
    Route::delete('locales/{local}/dueno/{usuario}', [LocalController::class, 'duenoQuitar'])->name('dueno.quitar');

    Route::post('locales/{local}/productos', [LocalController::class, 'productoStore'])->name('productos.store');
    Route::delete('productos/{producto}', [LocalController::class, 'productoDestroy'])->name('productos.destroy');
    Route::post('locales/{local}/promociones', [LocalController::class, 'promoStore'])->name('promos.store');
    Route::delete('promociones/{promocion}', [LocalController::class, 'promoDestroy'])->name('promos.destroy');
});

// Rutas de Breeze (autenticación real)
// El dashboard de Breeze ya no se usa: tras iniciar sesión, el dueño de local va a su panel y los demás a Explorar.
Route::get('/dashboard', fn () => redirect()->route(! empty(auth()->user()->restaurante_id) ? 'local.panel' : 'explorar'))->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

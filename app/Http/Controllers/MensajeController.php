<?php

namespace App\Http\Controllers;

use App\Models\Mensaje;
use App\Models\Restaurante;
use App\Support\Roles;
use Illuminate\Http\Request;

/**
 * Mensajería dentro de la app:
 *  - admins y dueños de local escriben al superadmin (/soporte) y ven su respuesta;
 *  - el superadmin lee y responde desde su bandeja (/admin/bandeja).
 */
class MensajeController extends Controller
{
    private function permitido(Request $request): void
    {
        $u = $request->user();
        abort_unless($u->es_admin || Roles::esDueno($u), 403, 'Solo para administradores y dueños de local.');
    }

    private function soloSuperadmin(Request $request): void
    {
        abort_unless(Roles::esSuperadmin($request->user()), 403, 'Solo el superadmin ve la bandeja.');
    }

    /** Pantalla "Ayuda / Contactar a FoodMatch" */
    public function soporte(Request $request)
    {
        $this->permitido($request);

        return view('soporte.contacto', [
            'mensajes' => Mensaje::where('user_id', $request->user()->id)->latest()->get(),
            'soySuperadmin' => Roles::esSuperadmin($request->user()),
        ]);
    }

    public function enviar(Request $request)
    {
        $this->permitido($request);
        $datos = $request->validate([
            'asunto' => 'required|string|max:120',
            'cuerpo' => 'required|string|max:2000',
        ]);
        Mensaje::create($datos + ['user_id' => $request->user()->id]);

        return redirect()->route('soporte')->with('ok', 'Mensaje enviado. Te responderán aquí mismo.');
    }

    /** Cualquier usuario puede pedir ser dueño de un local; el superadmin decide. */
    public function solicitud(Request $request)
    {
        return view('soporte.solicitud', [
            'mensajes' => Mensaje::where('user_id', $request->user()->id)->where('asunto', 'like', 'Solicitud de dueño%')->latest()->get(),
        ]);
    }

    public function solicitudEnviar(Request $request)
    {
        $datos = $request->validate([
            'local' => 'required|string|max:100',
            'direccion' => 'required|string|max:160',
            'telefono' => 'required|string|max:40',
            'comentario' => 'nullable|string|max:800',
        ]);

        $pendiente = Mensaje::where('user_id', $request->user()->id)
            ->where('asunto', 'like', 'Solicitud de dueño%')->whereNull('respondido_at')->exists();
        if ($pendiente) {
            return back()->withErrors('Ya tienes una solicitud esperando respuesta. Te avisaremos aquí mismo.');
        }

        Mensaje::create([
            'user_id' => $request->user()->id,
            'asunto' => 'Solicitud de dueño: ' . $datos['local'],
            'cuerpo' => "Local: {$datos['local']}\nDirección: {$datos['direccion']}\nTeléfono: {$datos['telefono']}"
                . (! empty($datos['comentario']) ? "\n\n{$datos['comentario']}" : ''),
        ]);

        return redirect()->route('solicitud')->with('ok', 'Solicitud enviada. Te responderemos aquí mismo.');
    }

    /** El superadmin autoriza a quien escribió: le asigna un local y le avisa. */
    public function darDueno(Request $request, Mensaje $mensaje)
    {
        $this->soloSuperadmin($request);
        $datos = $request->validate(['restaurante_id' => 'required|exists:restaurantes,id']);
        $local = Restaurante::findOrFail($datos['restaurante_id']);
        $autor = $mensaje->autor;
        abort_unless($autor, 404);

        $autor->forceFill(['restaurante_id' => $local->id])->save();
        $mensaje->forceFill([
            'respuesta' => "¡Listo! Ya eres dueño de «{$local->nombre}». Entra a «Mi local» en la barra de abajo para subir tus ofertas y tu menú.",
            'respondido_at' => now(),
            'leido_at' => $mensaje->leido_at ?: now(),
        ])->save();

        return back()->with('ok', $autor->name . ' ahora administra «' . $local->nombre . '».');
    }

    public function bandeja(Request $request)
    {
        $this->soloSuperadmin($request);

        return view('soporte.bandeja', [
            'mensajes' => Mensaje::with('autor')->orderByRaw('respondido_at is not null')->latest()->get(),
        ]);
    }

    public function responder(Request $request, Mensaje $mensaje)
    {
        $this->soloSuperadmin($request);
        $datos = $request->validate(['respuesta' => 'required|string|max:2000']);
        $mensaje->forceFill([
            'respuesta' => $datos['respuesta'],
            'respondido_at' => now(),
            'leido_at' => $mensaje->leido_at ?: now(),
        ])->save();

        return back()->with('ok', 'Respuesta enviada.');
    }

    public function marcarLeido(Request $request, Mensaje $mensaje)
    {
        $this->soloSuperadmin($request);
        $mensaje->forceFill(['leido_at' => now()])->save();

        return back();
    }
}

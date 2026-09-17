<?php

namespace App\Http\Controllers\Comunicaciones;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('comunicaciones_project_id')) {
            return redirect()->route('bixocrm.bandeja');
        }
        return view('comunicaciones.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required',
        ]);

        // Acepta email o username (el superadmin entra como "administrator").
        $login = trim($request->input('email'));
        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $cred = [$campo => $login, 'password' => $request->input('password')];

        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->withInput();
        }

        $user = Auth::user();

        // Todos los negocios del usuario (propios + donde es miembro).
        $project = Project::where('owner_id', $user->id)->where('is_active', true)->first();
        if (! $project) {
            $project = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->where('is_active', true)->first();
        }
        if (! $project) {
            Auth::logout();
            return back()->withErrors(['email' => 'No tienes ningún negocio asignado.'])->withInput();
        }

        // Solo la clave de ESTE portal: escribir tambien `active_project_id`
        // arrastraba el proyecto de Admin al cambiar de negocio aqui.
        session(['comunicaciones_project_id' => $project->id]);

        return redirect()->route('bixocrm.bandeja');
    }

    /** Proyectos a los que el usuario tiene acceso (para el selector "Cambiar de negocio"). */
    public static function proyectosDelUsuario(): \Illuminate\Support\Collection
    {
        $user = Auth::user();
        if (! $user) return collect();
        return Project::where('is_active', true)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id)))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /** Cambiar de negocio activo dentro del CRM (sin cerrar sesión). */
    public function cambiarProyecto(Request $request)
    {
        $id = (int) $request->input('project_id');
        // Verificar que el usuario tenga acceso a ese proyecto.
        $ok = self::proyectosDelUsuario()->contains('id', $id);
        if ($ok) {
            session(['comunicaciones_project_id' => $id]);
        }
        return redirect()->route('bixocrm.bandeja');
    }

    public function logout()
    {
        session()->forget('comunicaciones_project_id');
        return redirect()->route('bixocrm.login');
    }
}

<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('comercial_project_id')) {
            return redirect()->route('bixosales.dashboard');
        }
        return view('comercial.login');
    }

    /**
     * Paso 1: verificar credenciales y devolver proyectos disponibles (AJAX).
     */
    public function getProjects(Request $request)
    {
        $request->validate(
            ['email' => 'required|string', 'password' => 'required'],
            ['email.required' => 'El campo usuario es obligatorio.', 'password.required' => 'La contraseña es obligatoria.']
        );

        $login = $request->input('email');
        $user  = User::where('email', $login)->orWhere('username', $login)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['ok' => false, 'message' => 'Credenciales incorrectas.'], 401);
        }

        // Superadmin ve todos los proyectos activos
        if ($user->is_superadmin) {
            $projects = Project::where('is_active', true)->get(['id', 'name', 'slug']);
        } else {
            $projects = Project::where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                      ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id))
                      ->orWhereHas('employees', fn($e) => $e->where('user_id', $user->id)->where('is_active', true));
                })
                ->where('is_active', true)
                ->get(['id', 'name', 'slug']);
        }

        return response()->json([
            'ok'      => true,
            'projects'=> $projects,
        ]);
    }

    /**
     * Paso 2: login definitivo con proyecto seleccionado.
     */
    public function login(Request $request)
    {
        $request->validate(
            ['email' => 'required|string', 'password' => 'required', 'project_id' => 'required'],
            ['email.required' => 'El campo usuario es obligatorio.', 'password.required' => 'La contraseña es obligatoria.', 'project_id.required' => 'Selecciona un negocio.']
        );

        $login = $request->input('email');
        $user  = User::where('email', $login)->orWhere('username', $login)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));

        // Proyecto especial: admin general
        if ($request->input('project_id') === 'admin' && $user->is_superadmin) {
            return redirect(route('dashboard'));
        }

        $projectId = (int) $request->input('project_id');
        $query = Project::where('id', $projectId)->where('is_active', true);

        if (! $user->is_superadmin) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id))
                  ->orWhereHas('employees', fn($e) => $e->where('user_id', $user->id)->where('is_active', true));
            });
        }

        $project = $query->first();

        if (! $project) {
            Auth::logout();
            return back()->withErrors(['email' => 'No tienes acceso a ese proyecto.'])->withInput();
        }

        // SOLO la clave de este portal. Antes se escribia tambien
        // `active_project_id`, la de Admin, asi que entrar a Sales sobre un
        // negocio movia el negocio del Admin en la misma sesion.
        session(['comercial_project_id' => $project->id]);

        \App\Support\UsuarioRecordado::recordar('comercial', $login);

        return redirect()->route('bixosales.dashboard');
    }

    /**
     * CERRAR SESION DE VERDAD.
     *
     * Antes esto solo hacia `forget('comercial_project_id')`: olvidaba que
     * negocio estaba abierto pero dejaba al usuario AUTENTICADO, asi que
     * volvia a entrar solo y el boton parecia no hacer nada. En una
     * computadora compartida eso es un agujero: el siguiente que se sienta
     * entra con la cuenta del anterior.
     *
     * Mismo cierre que el panel de administracion: desautenticar, invalidar
     * la sesion (se van todas las claves, la del portal incluida) y regenerar
     * el token CSRF para que no se pueda reusar el de la sesion cerrada.
     */
    public function logout(Request $request)
    {
        $porInactividad = (bool) $request->input('_inactivity');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($porInactividad) {
            return redirect()->route('bixosales.login')->with('inactivity', true);
        }

        return redirect()->route('bixosales.login');
    }
}

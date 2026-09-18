<?php

namespace App\Modules\Crm\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use App\Support\Productos;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmAuthController extends Controller
{
    public function showLogin()
    {
        if (session('comunicaciones_project_id')) {
            return redirect()->route('bixocrm.bandeja');
        }
        return view('crm::comunicaciones.auth.login');
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

        // Solo abren el CRM los negocios que lo tienen contratado (modulo
        // clients). Con uno, se entra directo; con varios, se pregunta.
        $negocios = self::negociosDelUsuario();
        $conCrm = $negocios->where('crm', true)->values();
        if ($conCrm->isEmpty()) {
            Auth::logout();
            return back()->withErrors(['email' => $negocios->isEmpty()
                ? 'No tienes ningún negocio asignado.'
                : 'Ninguno de tus negocios tiene contratado BIXO CRM. Actívalo desde BIXO Control.'])->withInput();
        }
        if ($conCrm->count() > 1) {
            return redirect()->route('bixocrm.elegir');
        }
        // Solo la clave de ESTE portal: escribir tambien `active_project_id`
        // arrastraba el proyecto de Admin al cambiar de negocio aqui.
        session(['comunicaciones_project_id' => $conCrm->first()['id']]);
        return redirect()->route('bixocrm.bandeja');
    }

    /** Pantalla previa: a que negocio entrar (cuando el usuario tiene varios). */
    public function elegir()
    {
        $negocios = self::negociosDelUsuario();
        if ($negocios->where('crm', true)->count() === 1) {
            session(['comunicaciones_project_id' => $negocios->firstWhere('crm', true)['id']]);
            return redirect()->route('bixocrm.bandeja');
        }
        return view('crm::comunicaciones.auth.elegir', ['negocios' => $negocios]);
    }

    public function elegirPost(Request $request)
    {
        $id = (int) $request->input('project_id');
        $negocio = self::negociosDelUsuario()->firstWhere('id', $id);
        abort_unless($negocio && $negocio['crm'], 403, 'Ese negocio no tiene BIXO CRM o no es tuyo.');
        session(['comunicaciones_project_id' => $id]);
        return redirect()->route('bixocrm.bandeja');
    }

    /** Negocios del usuario con la marca de si tienen el CRM contratado. */
    public static function negociosDelUsuario(): \Illuminate\Support\Collection
    {
        return self::proyectosDelUsuario()->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug,
            'crm' => Productos::contratado($p, 'crm'),
        ])->values();
    }

    /** Alta publica del producto CRM. */
    public function showRegistro()
    {
        if (session('comunicaciones_project_id')) {
            return redirect()->route('bixocrm.bandeja');
        }
        return view('crm::comunicaciones.auth.registro');
    }

    /**
     * Crea usuario + negocio y enciende SOLO el producto CRM (clients, bots).
     * Deja al usuario dentro del portal y lo manda al asistente de Meta: un
     * CRM sin WhatsApp conectado no sirve de nada, asi que ese es el paso 1.
     */
    public function registrar(Request $request)
    {
        $data = $request->validate([
            'negocio'  => 'required|string|max:100',
            'nombre'   => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:users,email',
            'whatsapp' => 'nullable|string|max:30',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'              => $data['nombre'],
            'email'             => $data['email'],
            'username'          => Str::slug(Str::before($data['email'], '@')) . '_' . Str::lower(Str::random(4)),
            'password'          => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $project = Project::create([
            'owner_id'  => $user->id,
            'name'      => $data['negocio'],
            'slug'      => Str::slug($data['negocio']) . '-' . Str::lower(Str::random(4)),
            'whatsapp'  => $data['whatsapp'] ?? null,
            'phone'     => $data['whatsapp'] ?? null,
            'is_active' => true,
        ]);
        Productos::activar($project, 'crm');
        Employee::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'name' => $data['nombre'], 'email' => $data['email'],
            'role' => 'Administrador', 'is_active' => true, 'hire_date' => now(),
        ]);

        Auth::login($user);
        session(['comunicaciones_project_id' => $project->id]);

        return redirect()->route('bixocrm.conectar')->with('bienvenida', true);
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

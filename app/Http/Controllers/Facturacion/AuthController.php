<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginGeneral()
    {
        // Si ya hay sesión activa, redirigir al dashboard correspondiente
        $projects = Project::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        foreach ($projects as $p) {
            if (session("facturacion_auth.{$p->slug}")) {
                return redirect()->route('facturacion.dashboard', ['slug' => $p->slug]);
            }
        }
        return view('facturacion.login-general', ['projects' => collect()]);
    }

    /** Negocios a los que el usuario tiene acceso: superadmin ve todos, el resto solo dueño/miembro. */
    private function accessibleProjects($user)
    {
        if ($user->is_superadmin) {
            return Project::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);
        }

        return Project::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
            })
            ->orderBy('name')->get(['id', 'name', 'slug']);
    }

    /**
     * Login en dos pasos: primero usuario/contraseña (sin negocio). Si accede
     * a un único negocio, entra directo. Si accede a varios (o es superadmin),
     * vuelve a mostrar el formulario con el selector para que elija.
     */
    public function loginGeneral(Request $request)
    {
        // Paso 2 (ya autenticado en el paso 1: la sesión trae el usuario y la
        // marca facturacion_picking_project — solo falta el negocio elegido).
        if ($request->filled('project_id') && session('facturacion_picking_project') && Auth::check()) {
            $project = $this->accessibleProjects(Auth::user())->firstWhere('id', (int) $request->project_id);
            if (! $project) {
                Auth::logout();
                session()->forget('facturacion_picking_project');
                return back()->withErrors(['username' => 'No tienes acceso a ese negocio.']);
            }
            session()->forget('facturacion_picking_project');
            session(["facturacion_auth.{$project->slug}" => true]);
            session(['active_project_id' => $project->id]);
            return redirect()->route('facturacion.dashboard', ['slug' => $project->slug]);
        }

        // Paso 1: usuario y contraseña.
        $request->validate(['username' => 'required|string', 'password' => 'required']);

        if (! Auth::attempt(['username' => $request->username, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'Credenciales incorrectas.'])->withInput();
        }

        $user = Auth::user();
        $projects = $this->accessibleProjects($user);

        if ($projects->isEmpty()) {
            Auth::logout();
            return back()->withErrors(['username' => 'Tu usuario no tiene acceso a ningún negocio.'])->withInput();
        }

        // Un solo negocio disponible: entra directo, sin pedir selección.
        if ($projects->count() === 1) {
            $project = $projects->first();
            session(["facturacion_auth.{$project->slug}" => true]);
            session(['active_project_id' => $project->id]);
            return redirect()->route('facturacion.dashboard', ['slug' => $project->slug]);
        }

        // Varios negocios (o superadmin): mostrar el selector ya autenticado.
        // No se reenvía la contraseña; la sesión ya tiene al usuario logueado.
        session(['facturacion_picking_project' => true]);
        return view('facturacion.login-general', [
            'projects' => $projects,
            'authenticatedUsername' => $user->username,
        ]);
    }

    public function showLogin(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();
        return view('facturacion.login', compact('project'));
    }

    public function login(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        if (! Auth::attempt(['username' => $request->username, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'Credenciales incorrectas.'])->withInput();
        }

        $user = Auth::user();

        // Verificar que el usuario sea miembro del proyecto (superadmin siempre pasa)
        $isMember = $user->is_superadmin
            || $project->owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();

        if (! $isMember) {
            Auth::logout();
            return back()->withErrors(['username' => 'No tienes acceso a este portal.'])->withInput();
        }

        session(["facturacion_auth.{$slug}" => true]);
        session(['active_project_id' => $project->id]);

        return redirect()->route('facturacion.dashboard', ['slug' => $slug]);
    }

    public function logout(string $slug)
    {
        session()->forget("facturacion_auth.{$slug}");
        return redirect()->route('facturacion.login', ['slug' => $slug]);
    }
}

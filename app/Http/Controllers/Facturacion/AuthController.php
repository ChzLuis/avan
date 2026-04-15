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
        return view('facturacion.login-general', compact('projects'));
    }

    public function loginGeneral(Request $request)
    {
        $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'email'      => 'required|string',
            'password'   => 'required',
        ]);

        $project = Project::where('id', $request->project_id)->where('is_active', true)->firstOrFail();

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->withInput();
        }

        $user = Auth::user();
        $isMember = $project->owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();

        if (! $isMember) {
            Auth::logout();
            return back()->withErrors(['email' => 'No tienes acceso a ese negocio.'])->withInput();
        }

        session(["facturacion_auth.{$project->slug}" => true]);
        session(['active_project_id' => $project->id]);

        return redirect()->route('facturacion.dashboard', ['slug' => $project->slug]);
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
            'email'    => 'required|string',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->withInput();
        }

        $user = Auth::user();

        // Verificar que el usuario sea miembro del proyecto
        $isMember = $project->owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();

        if (! $isMember) {
            Auth::logout();
            return back()->withErrors(['email' => 'No tienes acceso a este portal.'])->withInput();
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

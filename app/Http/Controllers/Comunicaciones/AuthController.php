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

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->withInput();
        }

        $user = Auth::user();

        $project = Project::where('owner_id', $user->id)->where('is_active', true)->first();

        if (! $project) {
            $project = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->where('is_active', true)->first();
        }

        if (! $project) {
            Auth::logout();
            return back()->withErrors(['email' => 'No tienes ningún negocio asignado.'])->withInput();
        }

        session(['comunicaciones_project_id' => $project->id]);
        session(['active_project_id'         => $project->id]);

        return redirect()->route('bixocrm.bandeja');
    }

    public function logout()
    {
        session()->forget('comunicaciones_project_id');
        return redirect()->route('bixocrm.login');
    }
}

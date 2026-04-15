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

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required',
        ]);

        $login    = $request->input('email');
        $password = $request->input('password');

        // Buscar por email o username
        $user = User::where('email', $login)->orWhere('username', $login)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));

        // Buscar el proyecto al que pertenece el usuario (owner o miembro)
        $project = Project::where('owner_id', $user->id)->where('is_active', true)->first();

        if (! $project) {
            $project = Project::whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->where('is_active', true)
                ->first();
        }

        if (! $project) {
            Auth::logout();
            return back()->withErrors(['email' => 'No tienes ningún negocio asignado.'])->withInput();
        }

        session(['comercial_project_id' => $project->id]);
        session(['active_project_id'    => $project->id]);

        return redirect()->route('bixosales.dashboard');
    }

    public function logout()
    {
        session()->forget('comercial_project_id');
        return redirect()->route('bixosales.login');
    }
}

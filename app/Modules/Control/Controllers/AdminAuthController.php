<?php

namespace App\Modules\Control\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->is_superadmin) {
            return redirect()->route('admin.dashboard');
        }
        return view('control::admin.auth.login');
    }

    public function login(Request $request)
    {
        $datos = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required'],
        ]);
        // El superadmin puede no tener email (la cuenta historica solo tiene
        // username), asi que se acepta cualquiera de los dos campos. Esto
        // vivia solo en ARIN y la mudanza lo piso (2026-09-18).
        $campo = filter_var($datos['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$campo => $datos['email'], 'password' => $datos['password']];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! Auth::user()->is_superadmin) {
                Auth::logout();
                return back()->withErrors(['email' => 'No tienes permisos de administrador.']);
            }
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}

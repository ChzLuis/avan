<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class PasswordResetPortalController extends Controller
{
    // Portales válidos y sus rutas de login
    private const PORTALS = [
        'comercial'     => ['login' => 'bixosales.login',    'label' => 'Portal Comercial'],
        'comunicaciones'=> ['login' => 'bixocrm.login',      'label' => 'Portal Comunicaciones'],
        'facturacion'   => ['login' => 'bixofact.login',     'label' => 'Portal Facturación'],
        'admin'         => ['login' => 'admin.login',         'label' => 'Panel Administración'],
    ];

    public function showForgot(string $portal)
    {
        abort_unless(array_key_exists($portal, self::PORTALS), 404);
        return view('auth.portal-forgot-password', [
            'portal'      => $portal,
            'portalLabel' => self::PORTALS[$portal]['label'],
        ]);
    }

    public function sendReset(Request $request, string $portal)
    {
        abort_unless(array_key_exists($portal, self::PORTALS), 404);

        $request->validate([
            'username'              => 'required|string',
            'password'              => 'required|min:6|confirmed',
        ]);

        $user = \App\Models\User::where('username', $request->username)->first();

        if (!$user) {
            return back()->withErrors(['username' => 'No se encontró un usuario con ese nombre.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('status', 'ok');
    }

    public function showReset(Request $request, string $portal, string $token)
    {
        abort_unless(array_key_exists($portal, self::PORTALS), 404);
        return view('auth.portal-reset-password', [
            'portal'      => $portal,
            'portalLabel' => self::PORTALS[$portal]['label'],
            'token'       => $token,
            'email'       => $request->query('email', ''),
        ]);
    }

    public function updatePassword(Request $request, string $portal)
    {
        abort_unless(array_key_exists($portal, self::PORTALS), 404);

        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route(self::PORTALS[$portal]['login'])
                ->with('status', 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}

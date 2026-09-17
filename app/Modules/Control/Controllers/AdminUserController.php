<?php

namespace App\Modules\Control\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::withCount('projects as owned_projects')
            ->latest()
            ->get();

        return view('control::admin.users.index', compact('users'));
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|min:6|confirmed']);
        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', "Contraseña de \"{$user->name}\" actualizada.");
    }

    public function toggleAdmin(User $user)
    {
        // No puede quitarse a sí mismo
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes modificar tu propio rol de superadmin.');
        }

        $user->update(['is_superadmin' => ! $user->is_superadmin]);
        $label = $user->is_superadmin ? 'promovido a superadmin' : 'quitado de superadmin';
        return back()->with('success', "Usuario \"{$user->name}\" {$label}.");
    }
}

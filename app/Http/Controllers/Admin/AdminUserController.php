<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::withCount('projects as owned_projects')
            ->latest()
            ->get();

        return view('admin.users.index', compact('users'));
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

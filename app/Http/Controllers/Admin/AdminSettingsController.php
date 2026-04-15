<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => ['nullable', 'string', 'max:60'],
        ]);

        if ($request->filled('app_name')) {
            // Guardar en .env en memoria (no escribe archivo por seguridad)
            config(['app.name' => $request->app_name]);
        }

        return back()->with('success', 'Configuración guardada.');
    }
}

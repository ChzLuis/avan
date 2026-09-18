<?php

namespace App\Modules\Control\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Project;
use App\Models\Employee;
use App\Models\User;
use App\Support\Productos;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AdminProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['owner', 'members'])
            ->withCount(['orders', 'clients', 'invoices'])
            ->latest()
            ->get();

        $productos = Productos::todos();

        return view('control::admin.projects.index', compact('projects', 'productos'));
    }

    public function show(Project $project)
    {
        $project->load(['owner', 'members.user', 'modules']);
        $allModules = Module::orderBy('sort_order')->get();
        $activeModuleIds = $project->modules()->wherePivot('is_active', true)->pluck('modules.id');
        $candidatosDueno = \App\Support\ProjectOwnership::candidatos($project);

        $productos   = Productos::todos();
        $contratados = Productos::contratados($project);

        return view('control::admin.projects.show', compact(
            'project', 'allModules', 'activeModuleIds', 'candidatosDueno', 'productos', 'contratados'
        ));
    }

    /**
     * Traspasa el negocio a su dueño real.
     *
     * Los 7 proyectos se crearon desde la cuenta de superadmin, así que quedaron
     * a nombre de Eskala y ningún cliente era dueño del suyo. Esto es lo que
     * permite cumplir el §3.4 del plan: separar la plataforma de los negocios.
     */
    public function transferOwnership(Request $request, Project $project)
    {
        $data = $request->validate(['owner_id' => 'required|integer|exists:users,id']);

        $nuevo = \App\Models\User::findOrFail($data['owner_id']);
        \App\Support\ProjectOwnership::transferir($project, $nuevo);

        return back()->with('success', "«{$project->name}» ahora pertenece a {$nuevo->name}.");
    }

    public function toggle(Project $project)
    {
        $project->update(['is_active' => ! $project->is_active]);
        $status = $project->is_active ? 'activado' : 'suspendido';
        return back()->with('success', "Proyecto \"{$project->name}\" {$status}.");
    }

    public function updateSubdomain(Request $request, Project $project)
    {
        $request->validate(['custom_domain' => 'nullable|string|max:253|regex:/^[a-z0-9][a-z0-9\-\.]*[a-z0-9]$/i']);
        $project->update(['custom_domain' => $request->input('custom_domain') ?: null]);
        return back()->with('success', 'Dominio/subdominio actualizado.');
    }

    public function updateModules(Request $request, Project $project)
    {
        $moduleIds = $request->input('module_ids', []);

        // Sync: activa los seleccionados, desactiva el resto
        $all = Module::pluck('id');
        foreach ($all as $id) {
            $project->modules()->syncWithoutDetaching([$id => ['is_active' => in_array($id, $moduleIds)]]);
        }

        return back()->with('success', 'Módulos actualizados.');
    }

    /**
     * Enciende un producto completo (todos sus modulos) con un clic.
     * Solo suma: no apaga lo que otro producto ya encendio.
     */
    public function activarProducto(Request $request, Project $project)
    {
        $data = $request->validate(['producto' => 'required|string|in:' . implode(',', array_keys(Productos::todos()))]);

        Productos::activar($project, $data['producto']);

        $nombre = Productos::todos()[$data['producto']]['nombre'];

        return back()->with('success', "{$nombre} activado en «{$project->name}».");
    }

    /**
     * Alta de un negocio desde Control con su producto inicial. El dueno puede
     * ser un usuario existente (por correo) o uno nuevo, al que se le genera
     * una contrasena que se muestra UNA vez.
     */
    public function crear(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150',
            'contacto' => 'required|string|max:100',
            'producto' => 'required|string|in:' . implode(',', array_keys(Productos::todos())),
        ]);

        $user = User::where('email', $data['email'])->first();
        $password = null;
        if (! $user) {
            $password = Str::upper(Str::random(3)) . rand(100, 999) . Str::lower(Str::random(3));
            $user = User::create([
                'name'              => $data['contacto'],
                'email'             => $data['email'],
                'username'          => Str::slug(Str::before($data['email'], '@')) . '_' . Str::lower(Str::random(4)),
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]);
        }

        $project = Project::create([
            'owner_id'  => $user->id,
            'name'      => $data['name'],
            'slug'      => Str::slug($data['name']) . '-' . Str::lower(Str::random(4)),
            'is_active' => true,
        ]);
        Productos::activar($project, $data['producto']);
        Employee::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'name' => $data['contacto'], 'email' => $data['email'],
            'role' => 'Administrador', 'is_active' => true, 'hire_date' => now(),
        ]);

        $nombre = Productos::todos()[$data['producto']]['nombre'];
        $aviso  = "Negocio «{$project->name}» creado con {$nombre}.";
        if ($password) {
            $aviso .= " Usuario: {$user->email} · Contrasena: {$password} (se muestra solo esta vez).";
        }

        return redirect()->route('admin.projects.show', $project)->with('success', $aviso);
    }
}

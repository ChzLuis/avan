<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use App\Models\Employee;
use App\Mail\DemoCreada;
use Database\Seeders\DefaultCatalogsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DemoController extends Controller
{
    // GET /demo
    public function index()
    {
        $rubros = collect(array_keys($this->allRubros()))->mapWithKeys(function ($key) {
            $cfg = DemoRequest::rubroConfig($key);
            return [$key => ['label' => $cfg['label'], 'emoji' => $cfg['emoji']]];
        });
        return view('demo.index', compact('rubros'));
    }

    // GET /demo/features?rubro=restaurante
    public function features(Request $request)
    {
        $rubro = $request->get('rubro', '');
        $config = DemoRequest::rubroConfig($rubro);
        if (empty($config)) return response()->json(['ok' => false], 404);
        return response()->json(['ok' => true, 'features' => $config['features_available']]);
    }

    // POST /demo
    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name'     => 'required|string|max:100',
            'rubro'             => 'required|string|in:' . implode(',', array_keys($this->allRubros())),
            'features_selected' => 'required|array|min:1',
            'contact_name'      => 'required|string|max:100',
            'email'             => 'required|email|max:150',
            'phone'             => 'nullable|string|max:30',
            'whatsapp'          => 'nullable|string|max:30',
        ]);

        // Limitar 1 demo activa por email
        $existing = DemoRequest::where('email', $data['email'])->where('status', 'active')->first();
        if ($existing) {
            return response()->json([
                'ok'      => false,
                'message' => 'Ya tienes una demo activa. Revisa tu correo: ' . $data['email'],
            ], 422);
        }

        // Crear usuario demo
        $password = Str::upper(Str::random(3)) . rand(100, 999) . Str::lower(Str::random(3));
        $username = 'demo_' . Str::slug($data['business_name']) . '_' . Str::lower(Str::random(4));

        $user = User::create([
            'name'              => $data['contact_name'],
            'email'             => $data['email'],
            'username'          => $username,
            'password'          => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        // Crear proyecto
        $slug = Str::slug($data['business_name']) . '-' . Str::lower(Str::random(4));
        $project = Project::create([
            'owner_id'    => $user->id,
            'name'        => $data['business_name'],
            'slug'        => $slug,
            'category'    => $data['rubro'],
            'phone'       => $data['phone'],
            'whatsapp'    => $data['whatsapp'] ?? $data['phone'],
            'is_active'   => true,
        ]);

        // Activar solo los módulos del rubro
        $rubroConfig  = DemoRequest::rubroConfig($data['rubro']);
        $moduleKeys   = $rubroConfig['modules'] ?? [];
        $allModules   = Module::all();

        foreach ($allModules as $module) {
            $active = in_array($module->key, $moduleKeys);
            $project->modules()->attach($module->id, ['is_active' => $active]);
        }

        // Catálogos por defecto
        DefaultCatalogsSeeder::seedForProject($project);

        // Agregar como empleado/miembro
        Employee::create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'name'       => $data['contact_name'],
            'email'      => $data['email'],
            'role'       => 'Administrador',
            'is_active'  => true,
            'hire_date'  => now(),
        ]);

        // Registrar demo
        $demo = DemoRequest::create([
            'business_name'     => $data['business_name'],
            'rubro'             => $data['rubro'],
            'features_selected' => $data['features_selected'],
            'contact_name'      => $data['contact_name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'],
            'whatsapp'          => $data['whatsapp'],
            'project_id'        => $project->id,
            'user_id'           => $user->id,
            'demo_password'     => $password,
            'status'            => 'active',
            'expires_at'        => now()->addDays(14),
        ]);

        // Enviar correo
        try {
            Mail::to($data['email'])->send(new DemoCreada($demo, $password));
        } catch (\Throwable $e) {
            \Log::warning('Demo mail failed: ' . $e->getMessage());
        }

        return response()->json([
            'ok'           => true,
            'demo_id'      => $demo->id,
            'project_slug' => $project->slug,
            'email'        => $data['email'],
            'password'     => $password,
            'login_url'    => route('login'),
            'expires_at'   => $demo->expires_at->format('d/m/Y'),
        ]);
    }

    // GET /demo/success
    public function success(Request $request)
    {
        return view('demo.success', [
            'email'       => $request->get('email', ''),
            'password'    => $request->get('password', ''),
            'login_url'   => route('login'),
            'expires_at'  => $request->get('expires_at', ''),
        ]);
    }

    // ── Panel admin ───────────────────────────────────────────────
    // GET /admin/demos
    public function adminIndex()
    {
        $demos = DemoRequest::with(['project', 'user'])
            ->latest()
            ->paginate(30);
        return view('admin.demos.index', compact('demos'));
    }

    // POST /admin/demos/{demo}/cancel
    public function adminCancel(DemoRequest $demo)
    {
        if ($demo->project) $demo->project->update(['is_active' => false]);
        $demo->update(['status' => 'cancelled']);
        return back()->with('success', 'Demo cancelada.');
    }

    // POST /admin/demos/{demo}/extend
    public function adminExtend(DemoRequest $demo)
    {
        $demo->update([
            'expires_at' => ($demo->expires_at ?? now())->addDays(14),
            'status'     => 'active',
        ]);
        return back()->with('success', 'Demo extendida 14 días más.');
    }

    private function allRubros(): array
    {
        return [
            'restaurante' => true,
            'peluqueria'  => true,
            'clinica'     => true,
            'retail'      => true,
            'whatsapp'    => true,
            'farmacia'    => true,
            'veterinaria' => true,
            'taller'      => true,
        ];
    }
}

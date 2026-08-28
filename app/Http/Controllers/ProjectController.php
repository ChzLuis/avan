<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Module;
use App\Support\StorefrontSections;
use Database\Seeders\DefaultCatalogsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function create()
    {
        $industries = [
            'restaurante' => [
                'emoji'   => '🍽',
                'name'    => 'Restaurantes y Comida',
                'tagline' => 'Gestiona pedidos, menú digital y reservas de mesas',
                'problems' => [
                    'Pedidos por WhatsApp sin control',
                    'Reservas desordenadas',
                    'No tienen catálogo actualizado',
                    'Demoras en comunicar el estado del pedido',
                ],
                'modules' => [
                    'Comercial'    => ['Catálogo de platos','Promociones','Combos','Pedidos'],
                    'Agenda'       => ['Reservas','Control de mesas'],
                    'Operaciones'  => ['Estado del pedido','Cocina','Delivery'],
                    'Marketing'    => ['Cupones','Campañas'],
                    'Web'          => ['Menú online','Reservas online'],
                ],
            ],
            'cafeteria' => [
                'emoji'   => '☕',
                'name'    => 'Cafeterías y Pastelerías',
                'tagline' => 'Carta digital, pedidos rápidos y fidelización',
                'problems' => [
                    'Menú cambiante difícil de actualizar',
                    'Clientes repiten pedidos pero no hay historial',
                    'Sin sistema de puntos o fidelización',
                    'Promociones del día sin difusión',
                ],
                'modules' => [
                    'Comercial'  => ['Carta digital','Pedidos express','Delivery'],
                    'Marketing'  => ['Puntos de fidelidad','Promociones del día','Campañas WA'],
                    'Web'        => ['Menú online','Pedidos en línea'],
                    'Reportes'   => ['Productos más vendidos','Ventas por hora'],
                ],
            ],
            'licoreria' => [
                'emoji'   => '🍺',
                'name'    => 'Licorerías y Bodegas',
                'tagline' => 'Catálogo, delivery express y control de stock',
                'problems' => [
                    'Clientes preguntan disponibilidad por WhatsApp',
                    'Sin control de inventario actualizado',
                    'Difícil gestionar precios y promociones',
                    'Delivery sin seguimiento',
                ],
                'modules' => [
                    'Comercial'   => ['Catálogo con stock','Pedidos','Delivery','Cotizaciones'],
                    'Operaciones' => ['Control de inventario','Alertas de stock'],
                    'Marketing'   => ['Ofertas del día','Campañas WA'],
                    'Web'         => ['Tienda online'],
                ],
            ],
            'salon_belleza' => [
                'emoji'   => '💇',
                'name'    => 'Salones y Belleza',
                'tagline' => 'Agenda de citas, control de servicios y clientes',
                'problems' => [
                    'Citas por WhatsApp sin organización',
                    'No hay recordatorio automático',
                    'Difícil llevar historial de cada cliente',
                    'Sin control de estilistas y horarios',
                ],
                'modules' => [
                    'Agenda'    => ['Citas online','Recordatorios','Calendario de estilistas'],
                    'Comercial' => ['Catálogo de servicios','Paquetes','Ventas'],
                    'Clientes'  => ['Historial','Segmentos','Fidelización'],
                    'Marketing' => ['Campañas WA','Cupones de descuento'],
                ],
            ],
            'tienda' => [
                'emoji'   => '🛍',
                'name'    => 'Tiendas y Retail',
                'tagline' => 'Catálogo digital, ventas y control de inventario',
                'problems' => [
                    'Catálogo desactualizado o en PDF/imágenes',
                    'Sin sistema de ventas formal',
                    'Precios y stock difíciles de gestionar',
                    'Sin historial de clientes',
                ],
                'modules' => [
                    'Comercial'   => ['Catálogo','Cotizaciones','Facturas','POS'],
                    'Operaciones' => ['Inventario','Alertas de stock'],
                    'Web'         => ['Tienda online','Catálogo público'],
                    'Reportes'    => ['Ventas','Productos','Clientes'],
                ],
            ],
            'gimnasio' => [
                'emoji'   => '🏋',
                'name'    => 'Gimnasios y Fitness',
                'tagline' => 'Membresías, clases y seguimiento de miembros',
                'problems' => [
                    'Control manual de membresías y pagos',
                    'Clases sin sistema de reservas',
                    'Difícil seguir asistencia de miembros',
                    'Sin recordatorio de vencimiento',
                ],
                'modules' => [
                    'Agenda'    => ['Reserva de clases','Horarios','Control de asistencia'],
                    'Comercial' => ['Membresías','Planes','Cobros'],
                    'Clientes'  => ['Historial de pagos','Seguimiento'],
                    'Marketing' => ['Recordatorios','Campañas de retención'],
                ],
            ],
            'clinica' => [
                'emoji'   => '🏥',
                'name'    => 'Clínicas y Salud',
                'tagline' => 'Citas médicas, historial y comunicación con pacientes',
                'problems' => [
                    'Citas por llamada o WhatsApp sin orden',
                    'Historiales en papel difíciles de buscar',
                    'Sin recordatorio automático de citas',
                    'Comunicación desorganizada con pacientes',
                ],
                'modules' => [
                    'Agenda'    => ['Citas online','Calendario médicos','Recordatorios'],
                    'Pacientes' => ['Historial','Seguimiento','Segmentos'],
                    'Comercial' => ['Servicios','Cotizaciones','Facturas'],
                    'Marketing' => ['Campañas WA','Comunicaciones'],
                ],
            ],
            'educacion' => [
                'emoji'   => '📚',
                'name'    => 'Educación y Academia',
                'tagline' => 'Matrículas, cursos y comunicación con alumnos',
                'problems' => [
                    'Matrículas en Excel o papel',
                    'Sin difusión eficiente de cursos',
                    'Cobros y pagos sin seguimiento',
                    'Comunicación manual con alumnos',
                ],
                'modules' => [
                    'Comercial' => ['Cursos','Matrículas','Cobros','Facturas'],
                    'Agenda'    => ['Horarios','Reservas de aulas'],
                    'Alumnos'   => ['Historial','Segmentos','Comunicación'],
                    'Marketing' => ['Campañas WA','Automatizaciones'],
                ],
            ],
            'inmobiliaria' => [
                'emoji'   => '🏘',
                'name'    => 'Inmobiliaria y Alquileres',
                'tagline' => 'Propiedades, contratos y seguimiento de clientes',
                'problems' => [
                    'Propiedades sin catálogo digital',
                    'Seguimiento de leads desordenado',
                    'Sin control de contratos y vencimientos',
                    'Difícil coordinación de visitas',
                ],
                'modules' => [
                    'Comercial'  => ['Catálogo de propiedades','Cotizaciones','Contratos'],
                    'Agenda'     => ['Visitas','Reuniones'],
                    'Clientes'   => ['Leads','Seguimiento','Historial'],
                    'Marketing'  => ['Campañas WA','Automatizaciones'],
                ],
            ],
            'otro' => [
                'emoji'   => '🏢',
                'name'    => 'Otro tipo de negocio',
                'tagline' => 'Personaliza BIXO para tu negocio específico',
                'problems' => [
                    'Gestión manual de clientes y ventas',
                    'Sin catálogo digital',
                    'Comunicación desorganizada',
                    'Sin reportes de desempeño',
                ],
                'modules' => [
                    'Comercial'  => ['Catálogo','Ventas','Facturas','Cotizaciones'],
                    'Clientes'   => ['Contactos','Segmentos','Historial'],
                    'Marketing'  => ['Campañas WA','Automatizaciones'],
                    'Reportes'   => ['Ventas','Clientes','Actividad'],
                ],
            ],
        ];

        return view('projects.create', compact('industries'));
    }

    public function updateModules(Request $request, Project $target)
    {
        $this->authorizeProject($target);
        $enabledIds = $request->input('modules', []);
        $allModules = Module::all();
        $sync = [];
        foreach ($allModules as $module) {
            $sync[$module->id] = ['is_active' => in_array($module->id, $enabledIds)];
        }
        $target->modules()->sync($sync);
        return response()->json(['ok' => true]);
    }

    public function toggleStatus(Request $request, Project $target)
    {
        $this->authorizeProject($target);
        $target->update(['is_active' => !$target->is_active]);
        return response()->json(['is_active' => $target->is_active]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'category'    => 'nullable|string|max:80',
            'phone'       => 'nullable|string|max:30',
            'whatsapp'    => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:200',
        ]);

        $data['owner_id']  = auth()->id();
        $data['slug']      = Str::slug($data['name']) . '-' . strtolower(Str::random(4));
        $data['is_active'] = true;

        $project = Project::create($data);

        foreach (Module::all() as $module) {
            $project->modules()->attach($module->id, ['is_active' => true]);
        }

        DefaultCatalogsSeeder::seedForProject($project);
        StorefrontSections::ensure($project);

        if ($request->wantsJson()) {
            return response()->json(['project' => $project->only(['id','name','category','is_active','slug'])]);
        }

        return redirect()->route('dashboard', ['project' => $project->id])
            ->with('success', 'Negocio creado exitosamente.');
    }

    public function update(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        // Editar los datos del negocio (incluye is_active) es una escritura de
        // ajustes: no basta con ser miembro. Dueño, superadmin o quien tenga
        // el permiso de negocio.
        $this->authorizeGestionNegocio($project);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'category'    => 'nullable|string|max:80',
            'phone'       => 'nullable|string|max:30',
            'whatsapp'    => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:200',
            'is_active'   => 'nullable|boolean',
            'ruc'         => 'nullable|string|max:30',
            'email'       => 'nullable|email|max:100',
            'country'     => 'nullable|string|max:5',
            'currency'    => 'nullable|string|max:5',
        ]);

        $project->update($data);

        foreach (['ruc', 'email', 'country', 'currency'] as $key) {
            if ($request->has($key)) {
                $project->settings()->updateOrCreate(['key' => $key], ['value' => $request->input($key)]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['project' => $project->only(['id','name','category','is_active','slug','phone','address'])]);
        }

        return redirect()->route('workspace')->with('success', 'Negocio actualizado.');
    }

    public function destroy(Request $request, Project $project)
    {
        // Borrar un negocio entero es irreversible: SOLO el dueño o un
        // superadmin. Ser miembro (aunque sea de solo lectura) no alcanza.
        abort_unless(
            auth()->user()?->is_superadmin || $project->owner_id === auth()->id(),
            403
        );
        $project->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('workspace')->with('success', 'Negocio eliminado.');
    }
}

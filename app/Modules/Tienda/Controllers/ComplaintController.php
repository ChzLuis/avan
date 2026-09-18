<?php

namespace App\Modules\Tienda\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Tienda\Models\Complaint;
use Illuminate\Http\Request;

/**
 * Libro de Reclamaciones: lo que el comercio ve de lo que registran sus
 * clientes en /{tienda}/libro-reclamaciones.
 *
 * Hasta ahora el reclamo se guardaba y se avisaba por correo, pero no habia
 * pantalla: si el correo se perdia, el registro quedaba en la base sin que
 * nadie pudiera atenderlo. INDECOPI obliga a conservarlos y a responder en
 * 15 dias habiles, asi que el plazo se calcula y se muestra aqui.
 */
class ComplaintController extends Controller
{
    /** Estados del reclamo, en el orden en que ocurren. */
    public const ESTADOS = [
        'received'  => 'Recibido',
        'in_review' => 'En revisión',
        'resolved'  => 'Resuelto',
        'closed'    => 'Cerrado',
    ];

    /** Dias habiles que da INDECOPI para responder un reclamo. */
    public const PLAZO_HABILES = 15;

    public function index(Request $request)
    {
        $project = app('active_project');

        $query = Complaint::where('project_id', $project->id);

        $estado = (string) $request->query('estado', '');
        if (isset(self::ESTADOS[$estado])) {
            $query->where('status', $estado);
        }

        $tipo = (string) $request->query('tipo', '');
        if (in_array($tipo, ['reclamo', 'queja'], true)) {
            $query->where('type', $tipo);
        }

        // Busqueda por lo que el comercio tiene a mano cuando llama el cliente:
        // su codigo, su nombre o su documento.
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function ($sub) use ($q) {
                foreach (['code', 'consumer_name', 'document_number', 'email'] as $campo) {
                    $sub->orWhere($campo, 'like', '%'.$q.'%');
                }
            });
        }

        $complaints = $query->latest()->paginate(20)->withQueryString();

        // Contadores por estado: lo primero que se mira es cuantos hay sin atender.
        $conteos = Complaint::where('project_id', $project->id)
            ->selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');

        return view('tienda::complaints.index', [
            'project'    => $project,
            'complaints' => $complaints,
            'conteos'    => $conteos,
            'total'      => Complaint::where('project_id', $project->id)->count(),
            'estados'    => self::ESTADOS,
            'filtros'    => ['estado' => $estado, 'tipo' => $tipo, 'q' => $q ?? ''],
        ]);
    }

    public function updateStatus(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->project_id === app('active_project')->id, 403);

        $complaint->update($request->validate([
            'status' => 'required|in:'.implode(',', array_keys(self::ESTADOS)),
        ]));

        return back()->with('success', 'Reclamo '.$complaint->code.' actualizado.');
    }

    /**
     * Fecha limite para responder: 15 dias habiles desde el registro.
     *
     * Solo descuenta sabados y domingos; los feriados varian cada año y
     * meterlos a mano daria una fecha falsa de precisa. Es una guia para que
     * al comercio no se le pase el plazo, no un dictamen legal.
     */
    public static function vence(Complaint $complaint): \Illuminate\Support\Carbon
    {
        $fecha = $complaint->created_at->copy();
        for ($i = 0; $i < self::PLAZO_HABILES; $i++) {
            $fecha->addDay();
            while ($fecha->isWeekend()) {
                $fecha->addDay();
            }
        }

        return $fecha;
    }
}

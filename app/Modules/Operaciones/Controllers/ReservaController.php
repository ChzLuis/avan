<?php
namespace App\Modules\Operaciones\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Operaciones\Models\Appointment;
use App\Modules\Crm\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;

class ReservaController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comercial_project_id'));
    }

    public function index()
    {
        $project  = $this->project();
        $today    = now()->toDateString();
        $reservas = $project->appointments()
            ->whereBetween('date', [now()->subDays(1)->toDateString(), now()->addDays(30)->toDateString()])
            ->orderBy('date')->orderBy('start_time')
            ->get();

        $reservasJson = $reservas->map(function ($r) {
            return [
                'id'           => $r->id,
                'client_name'  => $r->client_name,
                'client_phone' => $r->client_phone,
                'date'         => $r->date,
                'start_time'   => $r->start_time,
                'end_time'     => $r->end_time,
                'guests'       => $r->guests ?? 1,
                'zone'         => $r->zone,
                'table_number' => $r->table_number,
                'occasion'     => $r->occasion,
                'status'       => $r->status,
                'notes'        => $r->notes,
                'source'       => $r->source ?? 'manual',
            ];
        })->values()->all();

        $tableCount = (int) ($project->settings()->where('key','qr_table_count')->value('value') ?? 10);
        $zones      = json_decode($project->settings()->where('key','qr_sectores')->value('value') ?? '["Salón"]', true) ?? ['Salón'];

        return view('operaciones::comercial.reservas', compact('project', 'reservasJson', 'tableCount', 'zones', 'today'));
    }

    public function store(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'nullable|string|max:30',
            'date'         => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'nullable|date_format:H:i|after:start_time',
            'guests'       => 'nullable|integer|min:1|max:99',
            'zone'         => 'nullable|string|max:60',
            'table_number' => 'nullable|string|max:10',
            'occasion'     => 'nullable|string|max:80',
            'notes'        => 'nullable|string|max:500',
        ]);

        $data['end_time']   = $data['end_time'] ?? date('H:i', strtotime($data['start_time']) + 5400); // +90min default
        $data['status']     = 'pending';
        $data['project_id'] = $project->id;

        $reserva = Appointment::create($data);
        return response()->json(['ok' => true, 'reserva' => $this->format($reserva)]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $project = $this->project();
        abort_unless($appointment->project_id === $project->id, 403);

        $data = $request->validate([
            'client_name'  => 'sometimes|required|string|max:100',
            'client_phone' => 'nullable|string|max:30',
            /* Mismo criterio que al crear: `store` exige `after_or_equal:today`
               y esto no, asi que una reserva se podia mover al PASADO
               editandola — y entonces desaparece del tablero, que solo mira
               desde ayer. Se admite la de hoy para poder corregir una hora. */
            'date'         => 'sometimes|required|date|after_or_equal:today',
            'start_time'   => 'sometimes|required|date_format:H:i',
            'end_time'     => 'nullable|date_format:H:i',
            'guests'       => 'nullable|integer|min:1|max:99',
            'zone'         => 'nullable|string|max:60',
            'table_number' => 'nullable|string|max:10',
            'occasion'     => 'nullable|string|max:80',
            'status'       => 'nullable|in:pending,confirmed,waiting,attended,cancelled',
            'notes'        => 'nullable|string|max:500',
        ]);

        $appointment->update($data);
        return response()->json(['ok' => true, 'reserva' => $this->format($appointment->fresh())]);
    }

    public function destroy(Appointment $appointment)
    {
        $project = $this->project();
        abort_unless($appointment->project_id === $project->id, 403);
        $appointment->delete();
        return response()->json(['ok' => true]);
    }

    // GET JSON para el calendario — devuelve reservas de un mes
    public function calendar(Request $request)
    {
        $project = $this->project();
        $from    = $request->input('from', now()->startOfMonth()->toDateString());
        $to      = $request->input('to',   now()->endOfMonth()->toDateString());

        $reservas = $project->appointments()
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->map(fn($r) => $this->format($r));

        return response()->json(['reservas' => $reservas]);
    }

    private function format(Appointment $r): array {
        return [
            'id'           => $r->id,
            'client_name'  => $r->client_name,
            'client_phone' => $r->client_phone,
            'date'         => $r->date,
            'start_time'   => $r->start_time,
            'end_time'     => $r->end_time,
            'guests'       => $r->guests ?? 1,
            'zone'         => $r->zone,
            'table_number' => $r->table_number,
            'occasion'     => $r->occasion,
            'status'       => $r->status,
            'notes'        => $r->notes,
            'source'       => $r->source ?? 'manual',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $appointments    = $project->appointments()->with('client')->orderBy('date')->orderBy('start_time')->get();
        $services        = $project->services()->where('is_available', true)->get();
        $appointmentTypes = $this->catValues($project, 'appointment_type');
        $priorities      = $this->catValues($project, 'priority');
        return view('agenda.index', compact('project', 'appointments', 'services', 'appointmentTypes', 'priorities'));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'service_id'   => 'nullable|integer',
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'nullable|string|max:30',
            'client_id'    => 'nullable|integer',
            'date'             => 'required|date',
            'start_time'       => 'required',
            'end_time'         => 'required',
            'notes'            => 'nullable|string',
            'appointment_type' => 'nullable|string|max:80',
            'priority'         => 'nullable|string|max:30',
        ]);
        $data['project_id'] = $project->id;
        $data['status'] = 'pending';
        $appointment = Appointment::create($data);
        return response()->json(['appointment' => $appointment]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($appointment->project_id === $project->id, 403);
        $appointment->update($request->validate([
            'status'           => 'required|in:pending,confirmed,done,cancelled',
            'notes'            => 'nullable|string',
            'appointment_type' => 'nullable|string|max:80',
            'priority'         => 'nullable|string|max:30',
        ]));
        return response()->json(['appointment' => $appointment]);
    }

    public function destroy(Appointment $appointment)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($appointment->project_id === $project->id, 403);
        $appointment->delete();
        return response()->json(['ok' => true]);
    }
}

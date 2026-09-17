<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Project;
use App\Modules\Personas\Models\WorkSchedule;
use Illuminate\Http\Request;

class AdminTurnosController extends Controller
{
    public function index(Project $project)
    {
        $employees = $project->employees()
            ->where('is_active', true)
            ->with(['schedules' => fn($q) => $q->orderBy('weekday')])
            ->orderBy('name')
            ->get();

        // Indexar schedules por employee_id y weekday para acceso O(1) en la vista
        $scheduleMap = [];
        foreach ($employees as $emp) {
            foreach ($emp->schedules as $s) {
                $scheduleMap[$emp->id][$s->weekday] = $s;
            }
        }

        $days = WorkSchedule::$days;

        return view('admin.turnos.index', compact('project', 'employees', 'scheduleMap', 'days'));
    }

    public function saveEmployee(Request $request, Project $project, Employee $employee)
    {
        abort_unless($employee->project_id === $project->id, 403);

        $schedules = $request->input('schedules', []);

        foreach (range(0, 6) as $day) {
            $data = $schedules[$day] ?? null;
            $active = !empty($data['active']);

            if ($active && !empty($data['start']) && !empty($data['end'])) {
                WorkSchedule::updateOrCreate(
                    ['employee_id' => $employee->id, 'weekday' => $day],
                    ['start_time' => $data['start'], 'end_time' => $data['end'], 'is_active' => true]
                );
            } else {
                // Desactivar el día (no eliminar para mantener historial)
                WorkSchedule::where('employee_id', $employee->id)
                    ->where('weekday', $day)
                    ->update(['is_active' => false]);
            }
        }

        return back()->with('success', 'Horario de ' . $employee->name . ' actualizado.');
    }

    public function saveBulk(Request $request, Project $project)
    {
        $employeeIds = $request->input('employee_ids', []);
        $schedules   = $request->input('schedules', []);

        $employees = $project->employees()
            ->whereIn('id', $employeeIds)
            ->get();

        foreach ($employees as $employee) {
            foreach (range(0, 6) as $day) {
                $data   = $schedules[$day] ?? null;
                $active = !empty($data['active']);

                if ($active && !empty($data['start']) && !empty($data['end'])) {
                    WorkSchedule::updateOrCreate(
                        ['employee_id' => $employee->id, 'weekday' => $day],
                        ['start_time' => $data['start'], 'end_time' => $data['end'], 'is_active' => true]
                    );
                } else {
                    WorkSchedule::where('employee_id', $employee->id)
                        ->where('weekday', $day)
                        ->update(['is_active' => false]);
                }
            }
        }

        return back()->with('success', count($employees) . ' empleados actualizados.');
    }

    /** Devuelve JSON con empleados activos en turno ahora mismo (para el Centro Operativo) */
    public function activeNow(Project $project)
    {
        $today   = now()->dayOfWeek; // 0=Dom, 1=Lun... en Carbon
        // Convertir: Carbon usa 0=Dom, WorkSchedule usa 0=Lun
        $weekday = ($today + 6) % 7;
        $time    = now()->format('H:i:s');

        $active = $project->employees()
            ->where('is_active', true)
            ->whereHas('schedules', fn($q) => $q
                ->where('weekday', $weekday)
                ->where('is_active', true)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>=', $time)
            )
            ->with('schedules')
            ->get(['id', 'name', 'role', 'area', 'phone']);

        return response()->json($active);
    }
}

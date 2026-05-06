<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    private function project(): Project
    {
        return app('active_project');
    }

    /** Panel principal de asistencia */
    public function index(Request $request)
    {
        $project   = $this->project();
        $mes       = $request->get('mes', now()->format('Y-m'));
        $empleadoId= $request->get('empleado', '');

        $empleados = Employee::where('project_id', $project->id)
            ->where('is_active', true)->get();

        $query = Attendance::where('project_id', $project->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$mes])
            ->with('employee')
            ->orderBy('date', 'desc');

        if ($empleadoId) $query->where('employee_id', $empleadoId);

        $registros = $query->get();

        // Resumen del mes
        $resumen = [
            'presentes'  => $registros->where('status', 'present')->count(),
            'ausentes'   => $registros->where('status', 'absent')->count(),
            'tardanzas'  => $registros->where('status', 'late')->count(),
            'horas'      => round($registros->sum('minutes_worked') / 60, 1),
        ];

        return view('hr.attendance', compact('project', 'empleados', 'registros', 'resumen', 'mes', 'empleadoId'));
    }

    /** Fichar entrada (el propio empleado) */
    public function checkIn(Request $request)
    {
        $project  = $this->project();
        $user     = auth()->user();
        $employee = Employee::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        $today = now()->toDateString();
        $existing = Attendance::where('employee_id', $employee->id)->where('date', $today)->first();

        if ($existing && $existing->check_in) {
            return response()->json(['ok' => false, 'message' => 'Ya fichaste entrada hoy.']);
        }

        $now  = now()->format('H:i:s');
        $late = $employee->work_start && $now > $employee->work_start;

        Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $today],
            [
                'project_id' => $project->id,
                'check_in'   => $now,
                'status'     => $late ? 'late' : 'present',
            ]
        );

        return response()->json(['ok' => true, 'check_in' => $now, 'status' => $late ? 'late' : 'present']);
    }

    /** Fichar salida */
    public function checkOut(Request $request)
    {
        $project  = $this->project();
        $user     = auth()->user();
        $employee = Employee::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $today      = now()->toDateString();
        $attendance = Attendance::where('employee_id', $employee->id)->where('date', $today)->firstOrFail();

        if ($attendance->check_out) {
            return response()->json(['ok' => false, 'message' => 'Ya fichaste salida hoy.']);
        }

        $now     = now()->format('H:i:s');
        $minutos = $attendance->calcularMinutos();

        $attendance->update(['check_out' => $now, 'minutes_worked' => $attendance->calcularMinutos()]);

        // Recalcular con check_out ya guardado
        $attendance->refresh();
        $minutos = $attendance->calcularMinutos();
        $attendance->update(['minutes_worked' => $minutos]);

        return response()->json(['ok' => true, 'check_out' => $now, 'minutes_worked' => $minutos]);
    }

    /** Registro manual por admin */
    public function store(Request $request)
    {
        $project = $this->project();
        $data = $request->validate([
            'employee_id'    => 'required|integer',
            'date'           => 'required|date',
            'check_in'       => 'nullable|date_format:H:i',
            'check_out'      => 'nullable|date_format:H:i',
            'status'         => 'required|in:present,absent,late,half_day',
            'notes'          => 'nullable|string|max:255',
        ]);

        $employee = Employee::where('id', $data['employee_id'])
            ->where('project_id', $project->id)->firstOrFail();

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $data['date']],
            array_merge($data, ['project_id' => $project->id])
        );

        if ($attendance->check_in && $attendance->check_out) {
            $attendance->update(['minutes_worked' => $attendance->calcularMinutos()]);
        }

        return response()->json(['ok' => true, 'record' => $attendance->fresh()]);
    }

    /** 9.3 — Reporte de comisiones por empleado y período */
    public function comisiones(Request $request)
    {
        $project = $this->project();
        $desde   = $request->get('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta   = $request->get('hasta', now()->format('Y-m-d'));

        $empleados = Employee::where('project_id', $project->id)
            ->where('is_active', true)
            ->where('commission_rate', '>', 0)
            ->get();

        $comisiones = $empleados->map(function (Employee $emp) use ($project, $desde, $hasta) {
            // Ventas atribuidas al empleado (por created_by en orders)
            $ventas = Order::allProjects()
                ->where('project_id', $project->id)
                ->where('created_by', $emp->user_id)
                ->whereNotIn('status', ['cancelled'])
                ->whereBetween(DB::raw('DATE(created_at)'), [$desde, $hasta])
                ->sum('total');

            $comision = round($ventas * $emp->commission_rate / 100, 2);

            return [
                'id'              => $emp->id,
                'name'            => $emp->name,
                'role'            => $emp->role,
                'commission_rate' => (float) $emp->commission_rate,
                'ventas'          => (float) $ventas,
                'comision'        => $comision,
            ];
        })->filter(fn($c) => $c['ventas'] > 0)->values();

        if ($request->get('export') === 'csv') {
            return $this->exportCsv('comisiones-' . $desde . '-' . $hasta,
                ['Empleado', 'Cargo', '% Comisión', 'Ventas (S/)', 'Comisión (S/)'],
                $comisiones->map(fn($c) => [
                    $c['name'], $c['role'], $c['commission_rate'] . '%',
                    $c['ventas'], $c['comision'],
                ])->toArray()
            );
        }

        return view('hr.comisiones', compact('project', 'comisiones', 'desde', 'hasta'));
    }

    private function exportCsv(string $filename, array $headers, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers);
            foreach ($rows as $row) fputcsv($out, $row);
            fclose($out);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

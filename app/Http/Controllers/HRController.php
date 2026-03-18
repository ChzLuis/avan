<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use Illuminate\Http\Request;

class HRController extends Controller
{
    public function index(Project $project)
    {
        $employees     = $project->employees()->orderBy('name')->get();
        $departments   = $this->catValues($project, 'department');
        $jobTitles     = $this->catValues($project, 'job_title');
        $contractTypes = $this->catValues($project, 'contract_type');
        return view('hr.employees', compact('project', 'employees', 'departments', 'jobTitles', 'contractTypes'));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    private function rules(): array
    {
        return [
            'name'      => 'required|string|max:100',
            'role'      => 'nullable|string|max:80',
            'area'      => 'nullable|string|max:80',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:100',
            'hire_date' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate($this->rules());
        $data['project_id'] = $project->id;
        $data['is_active']  = $request->boolean('is_active', true);

        $employee = Employee::create($data);

        if ($request->expectsJson()) {
            return response()->json(['employee' => $this->row($employee)]);
        }
        return back()->with('success', 'Empleado creado.');
    }

    public function update(Request $request, Project $project, Employee $employee)
    {
        abort_unless($employee->project_id === $project->id, 403);
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        $employee->update($data);

        if ($request->expectsJson()) {
            return response()->json(['employee' => $this->row($employee->fresh())]);
        }
        return back()->with('success', 'Empleado actualizado.');
    }

    public function destroy(Project $project, Employee $employee)
    {
        abort_unless($employee->project_id === $project->id, 403);
        $employee->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Empleado eliminado.');
    }

    private function row(Employee $e): array
    {
        return [
            'id'        => $e->id,
            'name'      => $e->name,
            'role'      => $e->role,
            'area'      => $e->area,
            'phone'     => $e->phone,
            'email'     => $e->email,
            'hire_date' => $e->hire_date?->format('Y-m-d'),
            'is_active' => (bool)$e->is_active,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Http\Request;

class CertificadoController extends Controller
{
    public function index()
    {
        $project      = app('active_project');
        $certificados = Certificado::where('project_id', $project->id)
            ->latest()
            ->get();

        return view('certificados.index', compact('project', 'certificados'));
    }

    public function store(Request $request)
    {
        $project = app('active_project');

        $data = $request->validate([
            'alumno_nombre'     => 'required|string|max:150',
            'alumno_dni'        => 'nullable|string|max:20',
            'alumno_email'      => 'nullable|email|max:150',
            'curso_nombre'      => 'required|string|max:200',
            'curso_horas'       => 'nullable|string|max:20',
            'curso_nivel'       => 'nullable|string|max:50',
            'fecha_emision'     => 'required|date',
            'fecha_vencimiento' => 'nullable|date|after:fecha_emision',
            'instructor'        => 'nullable|string|max:150',
            'institucion'       => 'nullable|string|max:150',
            'notas'             => 'nullable|string',
        ]);

        $data['project_id'] = $project->id;
        $data['codigo']     = Certificado::generarCodigo();
        $data['estado']     = 'emitido';

        $cert = Certificado::create($data);

        return response()->json(['ok' => true, 'certificado' => $cert]);
    }

    public function update(Request $request, Certificado $certificado)
    {
        $project = app('active_project');
        abort_unless($certificado->project_id === $project->id, 403);

        $data = $request->validate([
            'alumno_nombre'     => 'sometimes|required|string|max:150',
            'alumno_dni'        => 'nullable|string|max:20',
            'alumno_email'      => 'nullable|email|max:150',
            'curso_nombre'      => 'sometimes|required|string|max:200',
            'curso_horas'       => 'nullable|string|max:20',
            'curso_nivel'       => 'nullable|string|max:50',
            'fecha_emision'     => 'sometimes|date',
            'fecha_vencimiento' => 'nullable|date',
            'instructor'        => 'nullable|string|max:150',
            'institucion'       => 'nullable|string|max:150',
            'estado'            => 'nullable|in:emitido,revocado',
            'notas'             => 'nullable|string',
        ]);

        $certificado->update($data);

        return response()->json(['ok' => true, 'certificado' => $certificado->fresh()]);
    }

    public function destroy(Certificado $certificado)
    {
        $project = app('active_project');
        abort_unless($certificado->project_id === $project->id, 403);
        $certificado->delete();
        return response()->json(['ok' => true]);
    }

    // ── Verificación pública ─────────────────────────────────────────────────

    public function verificar(string $codigo)
    {
        $cert = Certificado::where('codigo', $codigo)->with('project')->first();
        return view('certificados.verificar', compact('cert'));
    }
}

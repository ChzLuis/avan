<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Product;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminImportController extends Controller
{
    public function index()
    {
        $projects = Project::where('is_active', true)->orderBy('name')->get();
        return view('admin.imports.index', compact('projects'));
    }

    // ── Productos ────────────────────────────────────────────────────────────
    public function importProducts(Request $request)
    {
        $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'file'       => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $project = Project::findOrFail($request->project_id);
        $rows    = $this->parseCsv($request->file('file'));
        $created = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            $row = array_map('trim', $row);
            if (empty($row[0])) continue; // nombre vacío

            try {
                Product::create([
                    'project_id'   => $project->id,
                    'name'         => $row[0] ?? '',
                    'price'        => is_numeric($row[1] ?? '') ? $row[1] : 0,
                    'stock'        => is_numeric($row[2] ?? '') ? (int)$row[2] : 0,
                    'description'  => $row[3] ?? null,
                    'sku'          => $row[4] ?? null,
                    'is_available' => true,
                ]);
                $created++;
            } catch (\Exception $e) {
                $errors[] = "Fila " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        $msg = "✅ {$created} productos importados al proyecto \"{$project->name}\".";
        if ($errors) $msg .= " ⚠️ " . count($errors) . " errores omitidos.";

        return back()->with('success', $msg);
    }

    // ── Clientes ─────────────────────────────────────────────────────────────
    public function importClients(Request $request)
    {
        $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'file'       => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $project = Project::findOrFail($request->project_id);
        $rows    = $this->parseCsv($request->file('file'));
        $created = 0;

        foreach ($rows as $row) {
            $row = array_map('trim', $row);
            if (empty($row[0])) continue;

            Client::create([
                'project_id' => $project->id,
                'name'       => $row[0] ?? '',
                'phone'      => $row[1] ?? null,
                'email'      => $row[2] ?? null,
                'notes'      => $row[3] ?? null,
            ]);
            $created++;
        }

        return back()->with('success', "✅ {$created} clientes importados al proyecto \"{$project->name}\".");
    }

    // ── Empleados ────────────────────────────────────────────────────────────
    public function importEmployees(Request $request)
    {
        $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'file'       => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $project = Project::findOrFail($request->project_id);
        $rows    = $this->parseCsv($request->file('file'));
        $created = 0;

        foreach ($rows as $row) {
            $row = array_map('trim', $row);
            if (empty($row[0])) continue;

            Employee::create([
                'project_id' => $project->id,
                'name'       => $row[0] ?? '',
                'role'       => $row[1] ?? null,
                'area'       => $row[2] ?? null,
                'phone'      => $row[3] ?? null,
                'email'      => $row[4] ?? null,
                'is_active'  => true,
            ]);
            $created++;
        }

        return back()->with('success', "✅ {$created} empleados importados al proyecto \"{$project->name}\".");
    }

    // ── Descargar plantillas CSV ──────────────────────────────────────────────
    public function downloadTemplate(string $type): StreamedResponse
    {
        $templates = [
            'products'  => [
                'filename' => 'plantilla_productos.csv',
                'headers'  => ['nombre', 'precio', 'stock', 'descripcion', 'sku'],
                'example'  => ['Producto Ejemplo', '25.50', '100', 'Descripción del producto', 'SKU-001'],
            ],
            'clients'   => [
                'filename' => 'plantilla_clientes.csv',
                'headers'  => ['nombre', 'telefono', 'email', 'notas'],
                'example'  => ['Juan Pérez', '999888777', 'juan@email.com', 'Cliente frecuente'],
            ],
            'employees' => [
                'filename' => 'plantilla_empleados.csv',
                'headers'  => ['nombre', 'rol', 'area', 'telefono', 'email'],
                'example'  => ['María García', 'Vendedor', 'Ventas', '999777666', 'maria@empresa.com'],
            ],
        ];

        $template = $templates[$type] ?? $templates['products'];

        return response()->streamDownload(function () use ($template) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $template['headers']);
            fputcsv($handle, $template['example']);
            fclose($handle);
        }, $template['filename'], ['Content-Type' => 'text/csv']);
    }

    // ── Helper: parsear CSV ──────────────────────────────────────────────────
    private function parseCsv($file): array
    {
        $rows   = [];
        $handle = fopen($file->getRealPath(), 'r');
        $first  = true;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if ($first) { $first = false; continue; } // saltar cabecera
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }
}

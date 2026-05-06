<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ImportLog;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ServiceController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $categories = $project->categories()
            ->where('is_active', true)->whereNull('parent_id')->where('type', 'service')
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        $services   = $project->services()->with('category')->orderBy('sort_order')->get();

        return view('catalog.services.index', compact('project', 'categories', 'services'));
    }

    public function reorder(Request $request)
    {
        $project = app('active_project');
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        foreach ($ids as $order => $id) {
            Service::where('id', $id)->where('project_id', $project->id)->update(['sort_order' => $order]);
        }
        return response()->json(['ok' => true]);
    }

    private function rules(): array
    {
        return [
            'category_id'  => 'nullable|integer',
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'duration_min' => 'nullable|integer|min:1',
            'modality'     => 'nullable|string|max:30',
            'notes'        => 'nullable|string',
            'is_available' => 'boolean',
        ];
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());
        $data['project_id']   = $project->id;
        $data['is_available'] = $request->boolean('is_available', true);

        $service = Service::create($data);

        if ($request->expectsJson()) {
            return response()->json(['service' => $this->serviceRow($service)]);
        }
        return back()->with('success', 'Servicio creado.');
    }

    public function update(Request $request, Service $service)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($service->project_id === $project->id, 403);

        $data = $request->validate($this->rules());
        $data['is_available'] = $request->boolean('is_available');
        $service->update($data);

        if ($request->expectsJson()) {
            return response()->json(['service' => $this->serviceRow($service->fresh())]);
        }
        return back()->with('success', 'Servicio actualizado.');
    }

    public function destroy(Service $service)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($service->project_id === $project->id, 403);
        $service->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Servicio eliminado.');
    }

    public function template()
    {
        $project = app('active_project');
        $catTree = $project->categories()->whereNull('parent_id')->where('is_active', true)->where('type', 'service')
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        return $this->buildServiceXlsx('plantilla_servicios.xlsx', [], $catTree);
    }

    public function export()
    {
        $project  = app('active_project');
        $services = $project->services()->with(['category', 'category.parent'])->orderBy('sort_order')->get()->map(fn($s) => [
            'nombre'       => $s->name,
            'categoria'    => $s->category?->parent ? $s->category->parent->name : ($s->category?->name ?? ''),
            'subcategoria' => $s->category?->parent ? $s->category->name : '',
            'precio'       => $s->price,
            'duracion_min' => $s->duration_min ?? '',
            'modalidad'    => $s->modality ?? '',
            'disponible'   => $s->is_available ? 'si' : 'no',
            'descripcion'  => $s->description ?? '',
            'notas'        => $s->notes ?? '',
        ])->toArray();
        $filename = 'servicios_' . $project->slug . '_' . now()->format('Ymd') . '.xlsx';
        $catTree  = $project->categories()->whereNull('parent_id')->where('is_active', true)->where('type', 'service')
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        return $this->buildServiceXlsx($filename, $services, $catTree);
    }

    public function import(Request $request)
    {
        $project = app('active_project');
        $request->validate(['file' => 'required|file|extensions:csv,txt,xls,xlsx|max:5120']);
        $path        = $request->file('file')->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0);
        $rows        = $sheet->toArray(null, true, true, false);
        $allCats     = $project->categories()->get()->keyBy('name');
        $validKeys   = ['nombre','categoria','subcategoria','precio','duracion_min','modalidad','disponible','descripcion','notas'];
        $cleanNum    = fn($v): ?float => ($v !== '' && $v !== null) ? (float) str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', (string)$v)) : null;
        $header  = null;
        $created = 0; $updated = 0; $errors = [];
        foreach ($rows as $i => $row) {
            $cleaned = array_map(fn($v) => trim((string)($v ?? '')), $row);
            $lower   = array_map('strtolower', $cleaned);
            if (!$header) {
                if (count(array_intersect($lower, $validKeys)) >= 2) { $header = $lower; }
                continue;
            }
            if (count(array_intersect($lower, $validKeys)) >= 2) continue;
            $data   = array_combine($header, array_pad($cleaned, count($header), ''));
            $nombre = trim($data['nombre'] ?? '');
            if ($nombre === '') continue;
            try {
                $catNombre  = trim($data['categoria'] ?? '');
                $subNombre  = trim($data['subcategoria'] ?? '');
                $categoryId = null;
                if ($subNombre && isset($allCats[$subNombre])) {
                    $categoryId = $allCats[$subNombre]->id;
                } elseif ($subNombre) {
                    $errors[] = '"' . $nombre . '" (fila ' . $i . '): subcategoría "' . $subNombre . '" no existe — se importó sin categoría.';
                } elseif ($catNombre && isset($allCats[$catNombre])) {
                    $categoryId = $allCats[$catNombre]->id;
                } elseif ($catNombre) {
                    $errors[] = '"' . $nombre . '" (fila ' . $i . '): categoría "' . $catNombre . '" no existe — se importó sin categoría.';
                }
                $payload = [
                    'project_id'   => $project->id,
                    'name'         => $nombre,
                    'category_id'  => $categoryId,
                    'price'        => $cleanNum($data['precio'] ?? '') ?? 0,
                    'duration_min' => is_numeric($data['duracion_min'] ?? '') ? (int)$data['duracion_min'] : null,
                    'modality'     => trim($data['modalidad'] ?? '') ?: null,
                    'is_available' => strtolower($data['disponible'] ?? 'si') === 'si',
                    'description'  => trim($data['descripcion'] ?? '') ?: null,
                    'notes'        => trim($data['notas'] ?? '') ?: null,
                ];
                $existing = Service::where('project_id', $project->id)->where('name', $nombre)->first();
                if ($existing) { $existing->update($payload); $updated++; }
                else           { Service::create($payload);   $created++; }
            } catch (\Exception $e) {
                $errors[] = '"' . $nombre . '" (fila ' . $i . '): ' . $e->getMessage();
            }
        }
        ImportLog::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'type'       => 'services',
            'filename'   => $request->file('file')?->getClientOriginalName() ?? '',
            'created'    => $created,
            'updated'    => $updated,
            'skipped'    => 0,
            'errors'     => $errors,
            'has_errors' => count($errors) > 0,
        ]);
        return response()->json(['created' => $created, 'updated' => $updated, 'errors' => $errors]);
    }

    private function buildServiceXlsx(string $filename, array $data, $catTree = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $cols = [
            ['nombre',       'Nombre del servicio',    true],
            ['categoria',    'Categoría padre',        false],
            ['subcategoria', 'Subcategoría',           false],
            ['precio',       'Precio (S/)',            true],
            ['duracion_min', 'Duración (minutos)',     false],
            ['modalidad',    'Modalidad',              false],
            ['disponible',   'Disponible (si/no)',     false],
            ['descripcion',  'Descripción',            false],
            ['notas',        'Notas internas',         false],
        ];
        $spreadsheet = new Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Servicios');
        $colLetter = 'A';
        foreach ($cols as [$key]) { $sheet1->setCellValue($colLetter . '1', $key); $colLetter++; }
        $lastCol = chr(ord('A') + count($cols) - 1);
        $sheet1->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6B21A8']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '4C1D95']]],
        ]);
        foreach (range('A', $lastCol) as $col) { $sheet1->getColumnDimension($col)->setWidth(20); }
        $sheet1->getColumnDimension('A')->setWidth(35);
        $sheet1->getColumnDimension('G')->setWidth(45);
        if (empty($data)) {
            $example = ['Corte de cabello','Peluquería','Cabello','25.00','30','presencial','si','Incluye lavado y secado',''];
            $col = 'A';
            foreach ($example as $val) { $sheet1->setCellValue($col . '2', $val); $col++; }
            $sheet1->getStyle('A2:' . $lastCol . '2')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAF5FF']]]);
        } else {
            $row = 2;
            foreach ($data as $i => $item) {
                $bg = $i % 2 === 0 ? 'FFFFFF' : 'FAF5FF';
                $col = 'A';
                foreach ($cols as [$key]) { $sheet1->setCellValue($col . $row, $item[$key] ?? ''); $col++; }
                $sheet1->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
                $row++;
            }
        }
        $instrucciones = [
            ['nombre',       'Nombre del servicio. Texto libre, máx. 200 caracteres.',         'Corte de cabello',  true],
            ['categoria',    'Categoría padre. Ver hoja "Categorías" para opciones válidas.',  'Peluquería',        false],
            ['subcategoria', 'Subcategoría (opcional). Debe pertenecer a la categoría padre.', 'Cabello',           false],
            ['precio',       'Precio con punto decimal.',                                      '25.00',             true],
            ['duracion_min', 'Duración estimada en minutos.',                                  '30',                false],
            ['modalidad',    'presencial / virtual / domicilio',                               'presencial',        false],
            ['disponible',   'Escribe exactamente "si" o "no".',                               'si',                false],
            ['descripcion',  'Descripción que verá el cliente en la tienda.',                  'Incluye lavado...', false],
            ['notas',        'Solo para tu equipo, no se muestra en tienda.',                  '',                  false],
        ];
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instrucciones');
        $sheet2->mergeCells('A1:D1');
        $sheet2->setCellValue('A1', 'Instrucciones de llenado — Servicios');
        $sheet2->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6B21A8']],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(28);
        foreach (['A2' => 'Columna', 'B2' => '¿Qué escribir?', 'C2' => '¿Obligatorio?', 'D2' => 'Ejemplo'] as $cell => $val) {
            $sheet2->setCellValue($cell, $val);
        }
        $sheet2->getStyle('A2:D2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4C1D95']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach ($instrucciones as $i => $instr) {
            $row = $i + 3;
            $bg  = $i % 2 === 0 ? 'FFFFFF' : 'FAF5FF';
            $sheet2->setCellValue('A' . $row, $instr[0]);
            $sheet2->setCellValue('B' . $row, $instr[1]);
            $sheet2->setCellValue('C' . $row, $instr[3] ? 'Sí' : 'No');
            $sheet2->setCellValue('D' . $row, $instr[2]);
            $sheet2->getStyle("A{$row}:D{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
        }
        $sheet2->getColumnDimension('A')->setWidth(15);
        $sheet2->getColumnDimension('B')->setWidth(55);
        $sheet2->getColumnDimension('C')->setWidth(14);
        $sheet2->getColumnDimension('D')->setWidth(22);

        // ── Hoja 3: Categorías disponibles ───────────────────────────────────
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Categorías');
        $sheet3->mergeCells('A1:C1');
        $sheet3->setCellValue('A1', 'Categorías de servicios disponibles — copia exactamente el nombre');
        $sheet3->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6B21A8']],
        ]);
        $sheet3->getRowDimension(1)->setRowHeight(24);
        foreach (['A2' => 'Categoría padre', 'B2' => 'Subcategoría', 'C2' => 'Usar en columna'] as $cell => $val) {
            $sheet3->setCellValue($cell, $val);
        }
        $sheet3->getStyle('A2:C2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4C1D95']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $catRow = 3;
        if ($catTree && $catTree->count()) {
            foreach ($catTree as $i => $cat) {
                if ($cat->children->isEmpty()) {
                    $bg = $catRow % 2 === 0 ? 'FFFFFF' : 'FAF5FF';
                    $sheet3->setCellValue('A' . $catRow, $cat->name);
                    $sheet3->setCellValue('B' . $catRow, '');
                    $sheet3->setCellValue('C' . $catRow, 'categoria');
                    $sheet3->getStyle("A{$catRow}:C{$catRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]]]);
                    $catRow++;
                } else {
                    foreach ($cat->children as $sub) {
                        $bg = $catRow % 2 === 0 ? 'FFFFFF' : 'FAF5FF';
                        $sheet3->setCellValue('A' . $catRow, $cat->name);
                        $sheet3->setCellValue('B' . $catRow, $sub->name);
                        $sheet3->setCellValue('C' . $catRow, 'categoria + subcategoria');
                        $sheet3->getStyle("A{$catRow}:C{$catRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]]]);
                        $catRow++;
                    }
                }
            }
        } else {
            $sheet3->setCellValue('A3', 'No hay categorías de servicios creadas aún.');
            $sheet3->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('9CA3AF');
        }
        $sheet3->getColumnDimension('A')->setWidth(28);
        $sheet3->getColumnDimension('B')->setWidth(28);
        $sheet3->getColumnDimension('C')->setWidth(24);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'no-cache, no-store',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function serviceRow(Service $s): array
    {
        return [
            'id'           => $s->id,
            'name'         => $s->name,
            'description'  => $s->description,
            'notes'        => $s->notes ?? null,
            'price'        => (float)$s->price,
            'duration_min' => $s->duration_min,
            'modality'     => $s->modality ?? null,
            'is_available' => (bool)$s->is_available,
            'category_id'  => $s->category_id,
            'category_name'=> $s->category?->name,
        ];
    }
}

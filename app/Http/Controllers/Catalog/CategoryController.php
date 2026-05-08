<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Category;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CategoryController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $type = request('type', 'product');

        // Cargar raíces con sus hijos (2 niveles) para la vista árbol
        $categories = $project->categories()
            ->with(['children' => fn($q) => $q->withCount(['products', 'services'])->orderBy('sort_order')])
            ->withCount(['products', 'services'])
            ->roots()
            ->ofType($type)
            ->orderBy('sort_order')
            ->get();

        if (request()->expectsJson()) {
            return response()->json($categories);
        }

        return view('catalog.categories.index', compact('project', 'categories', 'type'));
    }

    public function reorder(Request $request)
    {
        $project = app('active_project');
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        foreach ($ids as $order => $id) {
            Category::where('id', $id)->where('project_id', $project->id)->update(['sort_order' => $order]);
        }
        return response()->json(['ok' => true]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'image_url' => 'nullable|string|max:500',
            'color'     => 'nullable|string|max:20',
            'type'      => 'nullable|in:product,service',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);
        $data['project_id'] = $project->id;
        $data['type']       = $data['type'] ?? 'product';
        $data['is_active']  = true;
        $data['sort_order'] = $project->categories()->max('sort_order') + 1;

        // Verificar que el parent_id pertenece al mismo proyecto
        if (!empty($data['parent_id'])) {
            abort_unless(
                Category::where('id', $data['parent_id'])->where('project_id', $project->id)->exists(),
                422, 'La categoría padre no pertenece a este proyecto.'
            );
        }

        $category = Category::create($data);
        return response()->json($category->load('children')->loadCount(['products', 'services']));
    }

    public function update(Request $request, Category $category)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($category->project_id === $project->id, 403);
        $category->update($request->validate([
            'name'      => 'required|string|max:100',
            'image_url' => 'nullable|string|max:500',
            'color'     => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'type'      => 'nullable|in:product,service',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]));
        return response()->json($category->load('children')->loadCount(['products', 'services']));
    }

    public function destroy(Category $category)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($category->project_id === $project->id, 403);
        $category->delete();
        return response()->json(['ok' => true]);
    }

    public function template()
    {
        return $this->buildXlsx('plantilla_categorias.xlsx', []);
    }

    public function export()
    {
        $project    = app('active_project');
        $categories = $project->categories()->orderBy('sort_order')->get()->map(fn($c) => [
            'nombre' => $c->name,
            'activo' => $c->is_active ? 'si' : 'no',
        ])->toArray();

        $filename = 'categorias_' . $project->slug . '_' . now()->format('Ymd') . '.xlsx';
        return $this->buildXlsx($filename, $categories);
    }

    private function buildXlsx(string $filename, array $data): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        // ── Hoja 1: Categorías ───────────────────────────────────────────────
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Categorías');

        // Encabezados
        $sheet1->setCellValue('A1', 'nombre');
        $sheet1->setCellValue('B1', 'activo');

        $sheet1->getStyle('A1:B1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E65100']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BF360C']]],
        ]);

        $sheet1->getColumnDimension('A')->setWidth(35);
        $sheet1->getColumnDimension('B')->setWidth(15);

        // Fila ejemplo si no hay datos
        if (empty($data)) {
            $sheet1->setCellValue('A2', 'Electrónica');
            $sheet1->setCellValue('B2', 'si');
            $sheet1->getStyle('A2:B2')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3E0']],
            ]);
        } else {
            $row = 2;
            foreach ($data as $i => $item) {
                $bg = $i % 2 === 0 ? 'FFFFFF' : 'FFF8F5';
                $sheet1->setCellValue('A' . $row, $item['nombre']);
                $sheet1->setCellValue('B' . $row, $item['activo']);
                $sheet1->getStyle("A{$row}:B{$row}")->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
                $row++;
            }
        }

        // ── Hoja 2: Instrucciones ────────────────────────────────────────────
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instrucciones');

        $sheet2->mergeCells('A1:C1');
        $sheet2->setCellValue('A1', 'Instrucciones de llenado — Categorías');
        $sheet2->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E65100']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $headers = ['Columna', '¿Qué escribir?', 'Ejemplo'];
        foreach ($headers as $col => $h) {
            $cell = chr(65 + $col) . '2';
            $sheet2->setCellValue($cell, $h);
        }
        $sheet2->getStyle('A2:C2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BF360C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $instrucciones = [
            ['nombre', 'Nombre de la categoría (obligatorio). Texto libre, máx. 100 caracteres.', 'Electrónica'],
            ['activo',  'Escribe "si" para que sea visible en la tienda, "no" para ocultarla.', 'si'],
        ];

        foreach ($instrucciones as $i => $instr) {
            $row = $i + 3;
            $bg  = $i % 2 === 0 ? 'FFFFFF' : 'FFF8F5';
            $sheet2->setCellValue('A' . $row, $instr[0]);
            $sheet2->setCellValue('B' . $row, $instr[1]);
            $sheet2->setCellValue('C' . $row, $instr[2]);
            $sheet2->getStyle("A{$row}:C{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
        }

        $sheet2->getColumnDimension('A')->setWidth(12);
        $sheet2->getColumnDimension('B')->setWidth(60);
        $sheet2->getColumnDimension('C')->setWidth(20);

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

    public function import(Request $request)
    {
        $project = app('active_project');
        $request->validate(['file' => 'required|file|extensions:csv,txt,xls,xlsx|max:5120']);

        $path        = $request->file('file')->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0); // Hoja 1: Categorías
        $rows        = $sheet->toArray(null, true, true, false);

        $header  = null;
        $created = 0;
        $updated = 0;
        $errors  = [];

        foreach ($rows as $i => $row) {
            $cleaned = array_map(fn($v) => trim((string)($v ?? '')), $row);
            $lower   = array_map('strtolower', $cleaned);

            if (!$header) {
                if (in_array('nombre', $lower)) {
                    $header = $lower;
                }
                continue;
            }

            if (in_array('nombre', $lower)) continue;

            $data   = array_combine($header, array_pad($cleaned, count($header), ''));
            $nombre = trim($data['nombre'] ?? '');
            if ($nombre === '') continue;

            try {
                $payload = [
                    'project_id' => $project->id,
                    'name'       => $nombre,
                    'is_active'  => strtolower($data['activo'] ?? 'si') === 'si',
                ];
                $existing = Category::where('project_id', $project->id)->where('name', $nombre)->first();
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    Category::create($payload + ['sort_order' => $project->categories()->max('sort_order') + 1]);
                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = '"' . $nombre . '" (fila ' . $i . '): ' . $e->getMessage();
            }
        }

        return response()->json(['created' => $created, 'updated' => $updated, 'errors' => $errors]);
    }

    private function parseFileToRows(string $content): array
    {
        // CSV normal
        $rows  = [];
        $lines = explode("\n", str_replace("\r\n", "\n", str_replace("\r", "\n", $content)));

        // Saltar BOM
        if (str_starts_with($lines[0] ?? '', "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);
        }

        // Detectar delimitador
        $sample    = implode("\n", array_slice($lines, 0, 5));
        $delimiter = substr_count($sample, ';') >= substr_count($sample, ',') ? ';' : ',';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with(strtolower($line), 'sep=')) continue;
            $parsed = str_getcsv($line, $delimiter);
            $rows[] = array_map('trim', $parsed);
        }

        return $rows;
    }

    private function renderXls(array $cols, array $example, string $basename, string $extraRows = '', int $total = 0, string $filename = ''): \Illuminate\Http\Response
    {
        $thStyle = 'background:#E65100;color:#ffffff;font-weight:bold;font-size:12px;border:1px solid #bf360c;padding:8px 12px;white-space:nowrap;text-align:left';
        $tdTxt   = 'mso-number-format:\'\@\';border:1px solid #ffd0b0;padding:6px 10px;font-size:12px;background:#fff8f5';
        $tdKey   = 'mso-number-format:\'\@\';background:#FBE9E7;color:#bf360c;font-size:10px;font-family:monospace;border:1px solid #ffccbc;padding:3px 8px';
        $tdNote  = 'border:1px solid #d0d0d0;padding:5px 10px;font-size:11px;color:#555;background:#fafafa';

        $headerRow = implode('', array_map(fn($c) => '<th style="' . $thStyle . '">' . htmlspecialchars($c[1]) . (isset($c[2]) && $c[2] ? ' ★' : '') . '</th>', $cols));
        $keyRow    = implode('', array_map(fn($c) => '<td style="' . $tdKey . '">' . htmlspecialchars($c[0]) . '</td>', $cols));

        $exRow = '';
        if (!empty($example)) {
            $exRow = '<tr>' . implode('', array_map(fn($c) => '<td style="' . $tdTxt . ';background:#fff3ee">' . htmlspecialchars($example[$c[0]] ?? '') . '</td>', $cols)) . '</tr>';
        }

        $instrRows = '';
        foreach ($cols as $c) {
            if (!isset($c[5])) continue;
            $instrRows .= '<tr>'
                . '<td style="font-weight:bold;border:1px solid #d0d0d0;padding:5px 12px;background:#FBE9E7;color:#E65100">' . htmlspecialchars($c[1]) . '</td>'
                . '<td style="' . $tdNote . '">' . htmlspecialchars($c[5]) . '</td>'
                . '<td style="' . $tdNote . ';text-align:center;font-weight:bold;color:' . ($c[2] ? '#c00' : '#888') . '">' . ($c[2] ? '✓ Sí' : 'No') . '</td>'
                . '<td style="' . $tdNote . ';font-style:italic;color:#666">' . htmlspecialchars($c[4] ?? '') . '</td>'
                . '</tr>';
        }

        $totalCols   = count($cols);
        $bannerExtra = $total > 0 ? "$total registro(s) exportado(s) · Puedes editar y reimportar" : "★ = campo obligatorio · Rellena desde la fila siguiente";
        $filename    = $filename ?: ($basename . '.xls');

        $html = <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta name="ProgId" content="Excel.Sheet">
<style>body{font-family:Calibri,Arial,sans-serif;font-size:12px}table{border-collapse:collapse}</style></head>
<body>
<table x:str border="1">
  <tr><td colspan="$totalCols" style="background:#E65100;color:#fff;font-size:13px;font-weight:bold;padding:10px 14px;border:none">$bannerExtra</td></tr>
  <tr>$headerRow</tr>
  <tr style="display:none;mso-hide:none;visibility:hidden;font-size:1px;color:#ffffff">$keyRow</tr>
  $exRow
  $extraRows
</table>
<br><br>
<table border="1" style="border-collapse:collapse">
  <tr><th colspan="4" style="background:#E65100;color:#fff;font-size:14px;padding:10px 16px;text-align:left">Instrucciones — ¿Qué va en cada columna?</th></tr>
  <tr>
    <th style="background:#bf360c;color:#fff;padding:6px 12px">Columna</th>
    <th style="background:#bf360c;color:#fff;padding:6px 12px">¿Qué escribir?</th>
    <th style="background:#bf360c;color:#fff;padding:6px 12px">¿Obligatorio?</th>
    <th style="background:#bf360c;color:#fff;padding:6px 12px">Ejemplo</th>
  </tr>
  $instrRows
</table>
</body></html>
HTML;

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store',
            'Pragma'              => 'no-cache',
        ]);
    }
}

<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProveedorController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $proveedores = $project->proveedores()->orderBy('name')->get();
        return view('company.proveedores', compact('project', 'proveedores'));
    }

    private function rules(): array
    {
        return [
            'name'         => 'required|string|max:150',
            'contact_name' => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:100',
            'address'      => 'nullable|string|max:200',
            'category'     => 'nullable|string|max:80',
            'notes'        => 'nullable|string',
            'is_active'    => 'boolean',
        ];
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());
        $data['project_id'] = $project->id;
        $data['is_active']  = $request->boolean('is_active', true);
        $p = Proveedor::create($data);
        return response()->json(['proveedor' => $this->row($p)]);
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($proveedor->project_id === $project->id, 403);
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        $proveedor->update($data);
        return response()->json(['proveedor' => $this->row($proveedor->fresh())]);
    }

    public function destroy(Proveedor $proveedor)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($proveedor->project_id === $project->id, 403);
        $proveedor->delete();
        return response()->json(['ok' => true]);
    }

    public function template()
    {
        return $this->buildXlsx('plantilla_proveedores.xlsx', []);
    }

    public function export()
    {
        $project     = app('active_project');
        $proveedores = $project->proveedores()->orderBy('name')->get()->map(fn($p) => [
            'nombre'        => $p->name,
            'contacto'      => $p->contact_name ?? '',
            'telefono'      => $p->phone ?? '',
            'email'         => $p->email ?? '',
            'direccion'     => $p->address ?? '',
            'categoria'     => $p->category ?? '',
            'notas'         => $p->notes ?? '',
            'activo'        => $p->is_active ? 'si' : 'no',
        ])->toArray();
        $filename = 'proveedores_' . $project->slug . '_' . now()->format('Ymd') . '.xlsx';
        return $this->buildXlsx($filename, $proveedores);
    }

    public function import(Request $request)
    {
        $project = app('active_project');
        $request->validate(['file' => 'required|file|extensions:csv,txt,xls,xlsx|max:5120']);
        $path        = $request->file('file')->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0);
        $rows        = $sheet->toArray(null, true, true, false);
        $validKeys   = ['nombre','contacto','telefono','email','direccion','categoria','notas','activo'];
        $header      = null;
        $created = 0; $updated = 0; $errors = [];

        foreach ($rows as $i => $row) {
            $cleaned = array_map(fn($v) => trim((string)($v ?? '')), $row);
            $lower   = array_map('strtolower', $cleaned);
            if (!$header) {
                if (count(array_intersect($lower, $validKeys)) >= 1) { $header = $lower; }
                continue;
            }
            if (count(array_intersect($lower, $validKeys)) >= 1) continue;
            $data   = array_combine($header, array_pad($cleaned, count($header), ''));
            $nombre = trim($data['nombre'] ?? '');
            if ($nombre === '') continue;
            try {
                $payload = [
                    'project_id'   => $project->id,
                    'name'         => $nombre,
                    'contact_name' => trim($data['contacto']  ?? '') ?: null,
                    'phone'        => trim($data['telefono']  ?? '') ?: null,
                    'email'        => trim($data['email']     ?? '') ?: null,
                    'address'      => trim($data['direccion'] ?? '') ?: null,
                    'category'     => trim($data['categoria'] ?? '') ?: null,
                    'notes'        => trim($data['notas']     ?? '') ?: null,
                    'is_active'    => strtolower($data['activo'] ?? 'si') === 'si',
                ];
                $existing = Proveedor::where('project_id', $project->id)->where('name', $nombre)->first();
                if ($existing) { $existing->update($payload); $updated++; }
                else           { Proveedor::create($payload); $created++; }
            } catch (\Exception $e) {
                $errors[] = '"' . $nombre . '" (fila ' . $i . '): ' . $e->getMessage();
            }
        }
        return response()->json(['created' => $created, 'updated' => $updated, 'errors' => $errors]);
    }

    private function buildXlsx(string $filename, array $data): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $cols = [
            ['nombre',    'Nombre del proveedor',  true],
            ['contacto',  'Persona de contacto',   false],
            ['telefono',  'Teléfono',              false],
            ['email',     'Email',                 false],
            ['direccion', 'Dirección',             false],
            ['categoria', 'Categoría/Rubro',       false],
            ['notas',     'Notas internas',        false],
            ['activo',    'Activo (si/no)',         false],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Proveedores');

        $colLetter = 'A';
        foreach ($cols as [$key]) { $sheet1->setCellValue($colLetter . '1', $key); $colLetter++; }
        $lastCol = chr(ord('A') + count($cols) - 1);

        $sheet1->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F4C81']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0A3260']]],
        ]);
        foreach (range('A', $lastCol) as $col) { $sheet1->getColumnDimension($col)->setWidth(22); }
        $sheet1->getColumnDimension('A')->setWidth(35);
        $sheet1->getColumnDimension('G')->setWidth(40);

        if (empty($data)) {
            $example = ['Distribuidora Lima SAC', 'Juan Pérez', '987654321', 'ventas@dist.com', 'Av. Industrial 123', 'Abarrotes', '', 'si'];
            $col = 'A';
            foreach ($example as $val) { $sheet1->setCellValue($col . '2', $val); $col++; }
            $sheet1->getStyle('A2:' . $lastCol . '2')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']]]);
        } else {
            $row = 2;
            foreach ($data as $i => $item) {
                $bg = $i % 2 === 0 ? 'FFFFFF' : 'EFF6FF';
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
            ['nombre',    'Razón social o nombre del proveedor (obligatorio).',        'Distribuidora Lima SAC', true],
            ['contacto',  'Nombre de la persona de contacto.',                         'Juan Pérez',             false],
            ['telefono',  'Teléfono o celular.',                                       '987654321',              false],
            ['email',     'Correo electrónico.',                                       'ventas@dist.com',        false],
            ['direccion', 'Dirección física o de almacén.',                            'Av. Industrial 123',     false],
            ['categoria', 'Rubro o tipo de proveedor.',                                'Abarrotes',              false],
            ['notas',     'Notas internas. No se muestra al cliente.',                 '',                       false],
            ['activo',    'Escribe exactamente "si" o "no".',                          'si',                     false],
        ];

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instrucciones');
        $sheet2->mergeCells('A1:D1');
        $sheet2->setCellValue('A1', 'Instrucciones de llenado — Proveedores');
        $sheet2->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F4C81']],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(28);
        foreach (['A2' => 'Columna', 'B2' => '¿Qué escribir?', 'C2' => '¿Obligatorio?', 'D2' => 'Ejemplo'] as $cell => $val) {
            $sheet2->setCellValue($cell, $val);
        }
        $sheet2->getStyle('A2:D2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A3260']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach ($instrucciones as $i => $instr) {
            $row = $i + 3;
            $bg  = $i % 2 === 0 ? 'FFFFFF' : 'EFF6FF';
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
        $sheet2->getColumnDimension('D')->setWidth(28);

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

    private function row(Proveedor $p): array
    {
        return [
            'id'           => $p->id,
            'name'         => $p->name,
            'contact_name' => $p->contact_name ?? '',
            'phone'        => $p->phone ?? '',
            'email'        => $p->email ?? '',
            'address'      => $p->address ?? '',
            'category'     => $p->category ?? '',
            'notes'        => $p->notes ?? '',
            'is_active'    => (bool) $p->is_active,
        ];
    }
}

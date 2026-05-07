<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\ImportLog;
use App\Models\Project;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $categories = $project->categories()
            ->where('is_active', true)->whereNull('parent_id')->where('type', 'product')
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        $products   = $project->products()->with(['category','images'])->orderBy('sort_order')->get();

        $brandList    = $project->catalogLists()->where('type', 'brand')->first();
        $brands       = $brandList ? $brandList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $unitList     = $project->catalogLists()->where('type', 'unit')->first();
        $units        = $unitList ? $unitList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $supplierList = $project->catalogLists()->where('type', 'proveedor')->first();
        $suppliers    = $supplierList ? $supplierList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $locationList = $project->catalogLists()->where('type', 'ubicacion')->first();
        $locations    = $locationList ? $locationList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $taxList      = $project->catalogLists()->where('type', 'impuesto')->first();
        $taxes        = $taxList ? $taxList->values()->where('is_active', true)->orderBy('sort_order')->get() : collect();

        $allCatalogs  = $project->catalogLists()->where('is_active', true)->count();

        return view('catalog.products.index', compact('project', 'categories', 'products', 'brands', 'units', 'suppliers', 'locations', 'taxes', 'allCatalogs'));
    }

    private function rules(): array
    {
        return [
            'category_id'      => 'nullable|integer',
            'brand_catalog_id' => 'nullable|integer',
            'name'             => 'required|string|max:100',
            'sku'              => 'nullable|string|max:80',
            'barcode'          => 'nullable|string|max:80',
            'description'      => 'nullable|string',
            'notes'            => 'nullable|string',
            'price'             => 'required|numeric|min:0',
            'compare_price'    => 'nullable|numeric|min:0',
            'wholesale_price'  => 'nullable|numeric|min:0',
            'wholesale_min_qty'=> 'nullable|integer|min:1',
            'wholesale_unit'   => 'nullable|string|max:30',
            'cost'             => 'nullable|numeric|min:0',
            'unit'             => 'nullable|string|max:30',
            'stock'            => 'nullable|integer|min:0',
            'stock_min'        => 'nullable|integer|min:0',
            'stock_max'        => 'nullable|integer|min:0',
            'is_available'     => 'boolean',
        ];
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());
        $data['project_id']   = $project->id;
        $data['is_available'] = $request->boolean('is_available', true);

        $product = Product::create($data);

        if ($request->expectsJson()) {
            return response()->json(['product' => $this->productRow($product)]);
        }
        return back()->with('success', 'Producto creado.');
    }

    public function update(Request $request, Product $product)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        $data = $request->validate($this->rules());
        $data['is_available'] = $request->boolean('is_available');
        $product->update($data);

        if ($request->expectsJson()) {
            $product->loadMissing('category');
            return response()->json(['product' => $this->productRow($product)]);
        }
        return back()->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Producto eliminado.');
    }

    public function reorder(Request $request)
    {
        $project = app('active_project');
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        foreach ($ids as $order => $id) {
            Product::where('id', $id)->where('project_id', $project->id)->update(['sort_order' => $order]);
        }
        return response()->json(['ok' => true]);
    }

    public function export()
    {
        $project  = app('active_project');
        $products = $project->products()->with(['category', 'category.parent'])->orderBy('sort_order')->get()->map(fn($p) => [
            'nombre'              => $p->name,
            'sku'                 => $p->sku ?? '',
            'codigo_barras'       => $p->barcode ?? '',
            'categoria'           => $p->category?->parent ? $p->category->parent->name : ($p->category?->name ?? ''),
            'subcategoria'        => $p->category?->parent ? $p->category->name : '',
            'precio'              => $p->price,
            'precio_comparacion'  => $p->compare_price ?? '',
            'precio_mayorista'    => $p->wholesale_price ?? '',
            'cantidad_mayorista'  => $p->wholesale_min_qty ?? '',
            'unidad_mayorista'    => $p->wholesale_unit ?? '',
            'costo'               => $p->cost ?? '',
            'unidad'              => $p->unit ?? '',
            'stock'               => $p->stock ?? 0,
            'disponible'          => $p->is_available ? 'si' : 'no',
            'descripcion'         => $p->description ?? '',
            'notas'               => $p->notes ?? '',
        ])->toArray();
        $filename = 'productos_' . $project->slug . '_' . now()->format('Ymd') . '.xlsx';
        $catTree  = $project->categories()->whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        return $this->buildProductXlsx($filename, $products, $catTree);
    }

    public function template()
    {
        $project = app('active_project');
        $catTree = $project->categories()->whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')->get();
        return $this->buildProductXlsx('plantilla_productos.xlsx', [], $catTree);
    }

    private function buildProductXlsx(string $filename, array $data, $catTree = null): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Columnas con sus keys para import, label display, sección y color
        // sección: 'base'=negro, 'min'=azul minorista, 'may'=verde mayorista, 'extra'=gris
        $cols = [
            ['nombre',             'PRODUCTO',          'base', true],
            ['sku',                'SKU',               'base', false],
            ['categoria',          'CATEGORÍA',         'base', false],
            ['subcategoria',       'SUB CATEGORÍA',     'base', false],
            ['unidad',             'UNID.',             'min',  false],
            ['precio',             'P. UNIT',           'min',  true],
            ['precio_comparacion', 'P. VENTA',          'min',  false],
            ['unidad_mayorista',   'UNID.',             'may',  false],
            ['precio_mayorista',   'P. X MAYOR',        'may',  false],
            ['precio_comparacion', 'P. VENTA',          'may',  false],
            ['cantidad_mayorista', 'CANT. MÍN.',        'may',  false],
            ['costo',              'COSTO',             'extra',false],
            ['stock',              'STOCK',             'extra',false],
            ['disponible',        'VISIBLE',            'extra',false],
            ['descripcion',        'DESCRIPCIÓN',       'extra',false],
            ['notas',              'NOTAS',             'extra',false],
        ];

        // Keys únicos para import (precio_comparacion aparece dos veces visualmente)
        $importKeys = ['nombre','sku','categoria','subcategoria','unidad','precio','precio_comparacion',
                       'unidad_mayorista','precio_mayorista','cantidad_mayorista','costo','stock','disponible','descripcion','notas'];

        $colorMap = [
            'base'  => ['header' => '2D3748', 'sub' => '4A5568'],
            'min'   => ['header' => '1E40AF', 'sub' => '3B82F6'],
            'may'   => ['header' => '166534', 'sub' => '16A34A'],
            'extra' => ['header' => '6B7280', 'sub' => '9CA3AF'],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Productos');

        // Fila 1: Grupos de sección (merged)
        $sections = [
            ['label' => '',            'color' => '2D3748', 'cols' => 4], // base
            ['label' => 'MINORISTA',   'color' => '1E40AF', 'cols' => 3], // min
            ['label' => 'MAYORISTA',   'color' => '166534', 'cols' => 4], // may
            ['label' => 'DATOS EXTRA', 'color' => '6B7280', 'cols' => 5], // extra
        ];
        $colIdx = 1;
        foreach ($sections as $sec) {
            $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $endCol   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + $sec['cols'] - 1);
            if ($sec['cols'] > 1) $sheet1->mergeCells("{$startCol}1:{$endCol}1");
            $sheet1->setCellValue("{$startCol}1", $sec['label']);
            $sheet1->getStyle("{$startCol}1:{$endCol}1")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $sec['color']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $colIdx += $sec['cols'];
        }
        $sheet1->getRowDimension(1)->setRowHeight(22);

        // Fila 2: Encabezados de columna (key para import) — fila oculta
        $colIdx = 1;
        foreach ($cols as [$key, $label, $sec, $req]) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx) . '2';
            $sheet1->setCellValue($cell, $key);
            $colIdx++;
        }
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($cols));
        $sheet1->getStyle('A2:' . $lastColLetter . '2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '9CA3AF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
        ]);
        $sheet1->getRowDimension(2)->setRowHeight(14);

        // Fila 3: Labels visuales con colores por sección
        $colIdx = 1;
        foreach ($cols as [$key, $label, $sec, $req]) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet1->setCellValue("{$colLetter}3", $label . ($req ? ' *' : ''));
            $sheet1->getStyle("{$colLetter}3")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colorMap[$sec]['sub']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            ]);
            $colIdx++;
        }
        $sheet1->getRowDimension(3)->setRowHeight(20);

        // Anchos de columna
        $widths = [35, 15, 20, 20, 12, 12, 12, 15, 12, 12, 12, 12, 10, 10, 35, 25];
        foreach ($widths as $i => $w) {
            $sheet1->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }

        // Filas de datos
        if (empty($data)) {
            $example = ['Arroz Premium 1kg','ARR-001','Abarrotes','Granos y Cereales','unidad','15.90','19.90','saco 25kg','12.00','19.90','25','10.00','100','si','Arroz de grano largo',''];
            $colIdx = 1;
            foreach ($example as $val) {
                $sheet1->setCellValue([$colIdx, 4], $val);
                $colIdx++;
            }
            $sheet1->getStyle('A4:' . $lastColLetter . '4')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ]);
        } else {
            $row = 4;
            foreach ($data as $i => $item) {
                $bg = $i % 2 === 0 ? 'FFFFFF' : 'F8FAFC';
                // precio_comparacion aparece en col 7 (min) y col 10 (may) — mismo valor
                $rowData = [
                    $item['nombre'] ?? '', $item['sku'] ?? '', $item['categoria'] ?? '', $item['subcategoria'] ?? '',
                    $item['unidad'] ?? '', $item['precio'] ?? '', $item['precio_comparacion'] ?? '',
                    $item['unidad_mayorista'] ?? '', $item['precio_mayorista'] ?? '', $item['precio_comparacion'] ?? '',
                    $item['cantidad_mayorista'] ?? '',
                    $item['costo'] ?? '', $item['stock'] ?? '', $item['disponible'] ?? '',
                    $item['descripcion'] ?? '', $item['notas'] ?? '',
                ];
                foreach ($rowData as $ci => $val) {
                    $sheet1->setCellValue([$ci + 1, $row], $val);
                }
                $sheet1->getStyle('A' . $row . ':' . $lastColLetter . $row)->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                ]);
                $row++;
            }
        }
        $instrucciones = [
            // [columna, descripción, ejemplo, obligatorio]
            // — Columnas del Excel (en el mismo orden que aparecen) —
            ['nombre',             'Nombre del producto. Texto libre, máx. 200 caracteres. Obligatorio.',              'Arroz Premium 1kg',  true],
            ['sku',                'Tu código interno. Si coincide con un producto existente, se actualiza en lugar de crear uno nuevo.', 'ARR-001', false],
            ['categoria',          'Nombre exacto de la categoría padre. Ver hoja "Categorías" para las opciones válidas.', 'Abarrotes',    false],
            ['subcategoria',       'Nombre exacto de la subcategoría. Si se completa, la categoría padre es ignorada.', 'Granos y Cereales', false],
            ['unidad',             'Unidad de medida: unidad / kg / lt / caja / par / docena / etc.',                  'unidad',             false],
            ['precio',             'Precio minorista (unitario) con punto decimal. Obligatorio.',                      '15.90',              true],
            ['precio_comparacion', 'Precio anterior o precio de referencia. Se muestra tachado en la tienda. Déjalo vacío si no aplica.', '19.90', false],
            ['unidad_mayorista',   'Descripción de la presentación para precio mayorista. Ej: saco 25kg, caja x12.',   'saco 25kg',          false],
            ['precio_mayorista',   'Precio especial por volumen. Se activa cuando la cantidad mínima es alcanzada.',    '12.00',              false],
            ['cantidad_mayorista', 'Cantidad mínima de unidades para activar el precio mayorista.',                    '25',                 false],
            ['costo',              'Tu precio de compra o costo interno. No se muestra en la tienda.',                 '10.00',              false],
            ['stock',              'Unidades disponibles en inventario. Deja vacío o 0 si no controlas stock.',        '100',                false],
            ['disponible',         'Visibilidad en la tienda. Escribe exactamente: si → visible, no → oculto.',        'si',                 false],
            ['descripcion',        'Descripción del producto que verá el cliente.',                                    'Arroz de grano largo, seleccionado.', false],
            ['notas',              'Notas internas de tu equipo. No se muestran en la tienda.',                        'Stock en bodega 2',  false],
            // — Columna adicional aceptada al importar (no aparece en el Excel exportado) —
            ['codigo_barras',      'Columna opcional: EAN / UPC / código de barras del producto. Puedes agregarla al Excel si la necesitas.', '7501234567890', false],
        ];
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instrucciones');
        $sheet2->mergeCells('A1:D1');
        $sheet2->setCellValue('A1', 'Instrucciones de llenado — Productos');
        $sheet2->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6F42']],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(28);

        // Fila 2: notas generales
        $sheet2->mergeCells('A2:D2');
        $sheet2->setCellValue('A2', '• Las columnas se detectan por su nombre (fila oculta gris de la hoja Productos) — no importa el orden.  • Si el SKU ya existe, el producto se actualiza. Si no hay SKU, se busca por nombre.  • La columna "disponible" acepta: si → visible, no → oculto.');
        $sheet2->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 8, 'italic' => true, 'color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet2->getRowDimension(2)->setRowHeight(36);

        foreach (['A3' => 'Columna', 'B3' => '¿Qué escribir?', 'C3' => '¿Obligatorio?', 'D3' => 'Ejemplo'] as $cell => $val) {
            $sheet2->setCellValue($cell, $val);
        }
        $sheet2->getStyle('A3:D3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '155534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach ($instrucciones as $i => $instr) {
            $row = $i + 4;
            $bg  = $i % 2 === 0 ? 'FFFFFF' : 'F6FBF8';
            // Resaltar la última fila (codigo_barras — columna opcional no en el Excel)
            if ($instr[0] === 'codigo_barras') $bg = 'FFFBEB';
            $sheet2->setCellValue('A' . $row, $instr[0]);
            $sheet2->setCellValue('B' . $row, $instr[1]);
            $sheet2->setCellValue('C' . $row, $instr[3] ? 'Sí' : 'No');
            $sheet2->setCellValue('D' . $row, $instr[2]);
            $sheet2->getStyle("A{$row}:D{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
        }
        $sheet2->getColumnDimension('A')->setWidth(22);
        $sheet2->getColumnDimension('B')->setWidth(60);
        $sheet2->getColumnDimension('C')->setWidth(14);
        $sheet2->getColumnDimension('D')->setWidth(22);

        // ── Hoja 3: Categorías disponibles ───────────────────────────────────
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Categorías');
        $sheet3->mergeCells('A1:C1');
        $sheet3->setCellValue('A1', 'Categorías disponibles — copia exactamente el nombre');
        $sheet3->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6F42']],
        ]);
        $sheet3->getRowDimension(1)->setRowHeight(24);
        foreach (['A2' => 'Categoría padre', 'B2' => 'Subcategoría', 'C2' => 'Usar en columna'] as $cell => $val) {
            $sheet3->setCellValue($cell, $val);
        }
        $sheet3->getStyle('A2:C2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '155534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $catRow = 3;
        if ($catTree && $catTree->count()) {
            foreach ($catTree as $i => $cat) {
                $bg = $i % 2 === 0 ? 'FFFFFF' : 'F6FBF8';
                if ($cat->children->isEmpty()) {
                    $sheet3->setCellValue('A' . $catRow, $cat->name);
                    $sheet3->setCellValue('B' . $catRow, '');
                    $sheet3->setCellValue('C' . $catRow, 'categoria');
                    $sheet3->getStyle("A{$catRow}:C{$catRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]]]);
                    $catRow++;
                } else {
                    foreach ($cat->children as $j => $sub) {
                        $bg2 = ($catRow % 2 === 0) ? 'FFFFFF' : 'F6FBF8';
                        $sheet3->setCellValue('A' . $catRow, $cat->name);
                        $sheet3->setCellValue('B' . $catRow, $sub->name);
                        $sheet3->setCellValue('C' . $catRow, 'categoria + subcategoria');
                        $sheet3->getStyle("A{$catRow}:C{$catRow}")->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg2]]]);
                        $catRow++;
                    }
                }
            }
        } else {
            $sheet3->setCellValue('A3', 'No hay categorías creadas aún. Créalas desde el módulo de Categorías.');
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

    public function import(Request $request)
    {
        $project = app('active_project');
        $request->validate(['file' => 'required|file|extensions:csv,txt,xls,xlsx|max:10240']);
        $path        = $request->file('file')->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0);
        $rows        = $sheet->toArray(null, true, true, false);
        $allCats     = $project->categories()->get()->keyBy('name');
        $validKeys   = ['nombre','sku','codigo_barras','categoria','subcategoria','precio','precio_comparacion','precio_mayorista','cantidad_mayorista','unidad_mayorista','costo','unidad','stock','disponible','descripcion','notas'];
        $cleanNum    = fn($v): ?float => ($v !== '' && $v !== null) ? (float) str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', (string)$v)) : null;
        $header  = null;
        $created = 0; $updated = 0; $skipped = 0; $errors = [];
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
            if ($nombre === '' || str_starts_with($nombre, '←')) { $skipped++; continue; }
            try {
                $sku     = trim($data['sku'] ?? '') ?: null;
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
                    'project_id'    => $project->id,
                    'name'          => $nombre,
                    'sku'           => $sku,
                    'barcode'       => trim($data['codigo_barras'] ?? '') ?: null,
                    'category_id'      => $categoryId,
                    'price'            => $cleanNum($data['precio'] ?? '') ?? 0,
                    'compare_price'    => ($v = $cleanNum($data['precio_comparacion'] ?? '')) && $v > 0 ? $v : null,
                    'wholesale_price'  => ($v = $cleanNum($data['precio_mayorista'] ?? '')) && $v > 0 ? $v : null,
                    'wholesale_min_qty'=> is_numeric($data['cantidad_mayorista'] ?? '') && (int)$data['cantidad_mayorista'] > 0 ? (int)$data['cantidad_mayorista'] : null,
                    'wholesale_unit'   => trim($data['unidad_mayorista'] ?? '') ?: null,
                    'cost'             => ($v = $cleanNum($data['costo'] ?? '')) && $v > 0 ? $v : null,
                    'unit'          => trim($data['unidad'] ?? '') ?: null,
                    'stock'         => is_numeric($data['stock'] ?? '') ? (int)$data['stock'] : 0,
                    'is_available'  => strtolower($data['disponible'] ?? 'si') === 'si',
                    'description'   => trim($data['descripcion'] ?? '') ?: null,
                    'notes'         => trim($data['notas'] ?? '') ?: null,
                ];
                $existing = $sku ? Product::where('project_id', $project->id)->where('sku', $sku)->first() : null;
                $existing ??= Product::where('project_id', $project->id)->where('name', $nombre)->first();
                if ($existing) { $existing->update($payload); $updated++; }
                else           { Product::create($payload);   $created++; }
            } catch (\Exception $e) {
                $errors[] = '"' . $nombre . '" (fila ' . $i . '): ' . $e->getMessage();
            }
        }
        ImportLog::create([
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'type'       => 'products',
            'filename'   => $request->file('file')?->getClientOriginalName() ?? '',
            'created'    => $created,
            'updated'    => $updated,
            'skipped'    => $skipped,
            'errors'     => $errors,
            'has_errors' => count($errors) > 0,
        ]);
        return response()->json(['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors]);
    }

    // ── Exportar catálogo estático para GitHub Pages ──────────────────────────
    public function exportStatic()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $settings = $project->settings()->pluck('value', 'key');

        $categories = $project->categories()
            ->where('is_active', true)
            ->with(['products' => fn($q) => $q->where('is_available', true)->with('mainImage')->orderBy('sort_order')])
            ->orderBy('sort_order')->get();

        $waRaw = preg_replace('/\D/', '', $project->whatsapp ?? $project->phone ?? '');

        $html = view('catalog.products.export-static', compact('project', 'categories', 'settings', 'waRaw'))->render();

        $filename = 'catalogo-' . $project->slug . '.html';

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── Exportar para Mercado Libre ───────────────────────────────────────────
    public function exportMeli()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="meli_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, [
                'titulo','precio','moneda','stock_disponible','condicion','descripcion',
                'categoria','sku_vendedor','codigo_barras','costo',
                'precio_comparacion','unidad_de_medida','link_imagen',
            ]);
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name,
                    $p->price,
                    'PEN',
                    $p->stock ?? 0,
                    'new',
                    strip_tags($p->description ?? ''),
                    $p->category?->name ?? '',
                    $p->sku ?? '',
                    $p->barcode ?? '',
                    $p->cost ?? '',
                    $p->compare_price ?? '',
                    $p->unit ?? 'unidad',
                    '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Exportar para Rappi ───────────────────────────────────────────────────
    public function exportRappi()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rappi_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, [
                'product_name','description','price','discount_price','category',
                'sub_category','sku','barcode','unit','quantity',
                'available','image_url',
            ]);
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name,
                    strip_tags($p->description ?? ''),
                    $p->price,
                    $p->compare_price ?? '',
                    $p->category?->name ?? '',
                    '',
                    $p->sku ?? '',
                    $p->barcode ?? '',
                    $p->unit ?? 'unidad',
                    $p->stock ?? 0,
                    $p->is_available ? '1' : '0',
                    '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Exportar para Shopee ──────────────────────────────────────────────────
    public function exportShopee()
    {
        /** @var \App\Models\Project $project */
        $project  = app('active_project');
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="shopee_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, [
                'Product Name','Category','Brand','Description',
                'Price','Stock','SKU','Barcode',
                'Weight (kg)','Condition','Pre-order','Days to Ship',
                'Image URL 1',
            ]);
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name,
                    $p->category?->name ?? '',
                    '',
                    strip_tags($p->description ?? ''),
                    $p->price,
                    $p->stock ?? 0,
                    $p->sku ?? '',
                    $p->barcode ?? '',
                    '',
                    'new',
                    'No',
                    3,
                    '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Reseñas (admin) ───────────────────────────────────────────────────────
    public function reviews()
    {
        $project = project();
        $reviews = \App\Models\Review::where('project_id', $project->id)
            ->with('product')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'product_id'  => $r->product_id,
                'product'     => $r->product?->name,
                'author_name' => $r->author_name,
                'rating'      => $r->rating,
                'comment'     => $r->comment,
                'is_approved' => $r->is_approved,
                'created_at'  => $r->created_at->format('d/m/Y'),
            ]);
        return view('catalog.reviews.index', compact('reviews'));
    }

    public function approveReview(int $id)
    {
        $project = project();
        $review  = \App\Models\Review::where('project_id', $project->id)->findOrFail($id);
        $review->update(['is_approved' => !$review->is_approved]);
        return response()->json(['ok' => true, 'is_approved' => $review->is_approved]);
    }

    public function destroyReview(int $id)
    {
        $project = project();
        \App\Models\Review::where('project_id', $project->id)->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ── Imágenes ──────────────────────────────────────────────────────────────
    public function uploadImage(Request $request, Product $product)
    {
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:4096']);

        $file = $request->file('image');
        $dir = public_path('uploads/products/' . $product->id);
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $filename = time() . '_' . uniqid() . '.jpg';
        $destPath = $dir . '/' . $filename;

        // Convertir cualquier formato a JPG con GD y redimensionar a 600x600
        $mime = $file->getMimeType();
        $src  = match(true) {
            str_contains($mime, 'png')  => imagecreatefrompng($file->getRealPath()),
            str_contains($mime, 'gif')  => imagecreatefromgif($file->getRealPath()),
            str_contains($mime, 'webp') => imagecreatefromwebp($file->getRealPath()),
            default                     => imagecreatefromjpeg($file->getRealPath()),
        };

        $size   = 600;
        $w      = imagesx($src);
        $h      = imagesy($src);
        $canvas = imagecreatetruecolor($size, $size);

        // Fondo blanco (para imágenes con transparencia)
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        // Contain: imagen completa visible, centrada, sin recortar
        $scale = min($size / $w, $size / $h);
        $newW  = (int)($w * $scale);
        $newH  = (int)($h * $scale);
        $offX  = (int)(($size - $newW) / 2);
        $offY  = (int)(($size - $newH) / 2);
        imagecopyresampled($canvas, $src, $offX, $offY, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);

        imagejpeg($canvas, $destPath, 90);
        imagedestroy($canvas);

        $url = asset('uploads/products/' . $product->id . '/' . $filename);

        $isFirst = $product->images()->count() === 0;
        $image   = $product->images()->create([
            'url'        => $url,
            'is_main'    => $isFirst,
            'sort_order' => $product->images()->max('sort_order') + 1,
        ]);

        return response()->json(['ok' => true, 'image' => ['id' => $image->id, 'url' => $image->url, 'is_main' => $image->is_main]]);
    }

    public function deleteImage(Request $request, Product $product, ProductImage $image)
    {
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        // Borrar archivo físico
        $relativePath = parse_url($image->url, PHP_URL_PATH);
        $relativePath = ltrim(str_replace('/avan/public/', '', $relativePath), '/');
        $fullPath = public_path($relativePath);
        if (file_exists($fullPath)) @unlink($fullPath);
        $wasMain = $image->is_main;
        $image->delete();

        // Si era la principal, asignar la siguiente
        if ($wasMain) {
            $next = $product->images()->orderBy('sort_order')->first();
            $next?->update(['is_main' => true]);
        }

        return response()->json(['ok' => true]);
    }

    public function setMainImage(Request $request, Product $product, ProductImage $image)
    {
        $project = app('active_project');
        abort_unless($product->project_id === $project->id, 403);

        $product->images()->update(['is_main' => false]);
        $image->update(['is_main' => true]);

        return response()->json(['ok' => true]);
    }

    // ── Helper: fila serializada ──────────────────────────────────────────────
    private function productRow(Product $p): array
    {
        return [
            'id'               => $p->id,
            'name'             => $p->name,
            'sku'              => $p->sku,
            'barcode'          => $p->barcode,
            'description'      => $p->description,
            'notes'            => $p->notes,
            'price'            => (float)$p->price,
            'compare_price'    => $p->compare_price !== null ? (float)$p->compare_price : null,
            'wholesale_price'  => $p->wholesale_price !== null ? (float)$p->wholesale_price : null,
            'wholesale_min_qty'=> $p->wholesale_min_qty,
            'wholesale_unit'   => $p->wholesale_unit,
            'cost'             => $p->cost !== null ? (float)$p->cost : null,
            'unit'             => $p->unit,
            'stock'            => $p->stock,
            'stock_min'        => $p->stock_min,
            'stock_max'        => $p->stock_max,
            'is_available'     => (bool)$p->is_available,
            'category_id'      => $p->category_id,
            'brand_catalog_id' => $p->brand_catalog_id,
            'category_name'    => $p->category?->name,
            'images'           => $p->images->map(fn($i) => ['id' => $i->id, 'url' => $i->url, 'is_main' => $i->is_main])->values()->toArray(),
            'main_image'       => $p->mainImage?->url,
        ];
    }
}

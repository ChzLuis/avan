<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\Project;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $categories = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
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

    // ── Exportar CSV ──────────────────────────────────────────────────────────
    public function export()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="productos_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fwrite($f, "sep=;\n");
            fputcsv($f, ['nombre','sku','codigo_barras','categoria','precio','precio_comparacion','costo','unidad','stock','disponible','descripcion','notas'], ';');
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name, $p->sku ?? '', $p->barcode ?? '',
                    $p->category?->name ?? '', $p->price,
                    $p->compare_price ?? '', $p->cost ?? '',
                    $p->unit ?? '', $p->stock ?? 0,
                    $p->is_available ? 'si' : 'no',
                    $p->description ?? '', $p->notes ?? '',
                ], ';');
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Plantilla XLS (HTML) — abre perfecto en Excel con tildes ─────────────
    public function template()
    {
        $cols = [
            'nombre'              => ['Nombre del producto',    'text',   true],
            'sku'                 => ['SKU / Código interno',   'text',   false],
            'codigo_barras'       => ['Código de barras',       'text',   false],
            'categoria'           => ['Categoría',              'text',   false],
            'precio'              => ['Precio de venta',        'number', true],
            'precio_comparacion'  => ['Precio tachado (antes)', 'number', false],
            'costo'               => ['Costo / compra',         'number', false],
            'unidad'              => ['Unidad (unidad/kg/lt…)', 'text',   false],
            'stock'               => ['Stock',                  'number', false],
            'disponible'          => ['Disponible (si/no)',     'text',   false],
            'descripcion'         => ['Descripción',            'text',   false],
            'notas'               => ['Notas internas',         'text',   false],
        ];

        $example = [
            'nombre'             => 'Producto Ejemplo',
            'sku'                => 'SKU-001',
            'codigo_barras'      => '7501234567890',
            'categoria'          => 'Electrónica',
            'precio'             => '199.90',
            'precio_comparacion' => '249.90',
            'costo'              => '120.00',
            'unidad'             => 'unidad',
            'stock'              => '50',
            'disponible'         => 'si',
            'descripcion'        => 'Descripción del producto con tildes áéíóú',
            'notas'              => 'Notas internas',
        ];

        // HTML que Excel abre nativamente con UTF-8 correcto
        $th = fn(string $t, bool $req = false) =>
            '<th style="background:#4472C4;color:#fff;font-weight:bold;border:1px solid #2d5a9e;padding:6px 10px;white-space:nowrap">'
            . htmlspecialchars($t) . ($req ? ' <span style="color:#ffd700">*</span>' : '') . '</th>';

        $tdKey = fn(string $t) =>
            '<td style="background:#d9e1f2;color:#1f3864;font-family:monospace;font-size:11px;border:1px solid #b4c6e7;padding:4px 8px">'
            . htmlspecialchars($t) . '</td>';

        $tdText = fn(string $t) =>
            '<td style="mso-number-format:\'\@\';border:1px solid #d0d0d0;padding:4px 8px">'
            . htmlspecialchars($t) . '</td>';

        $tdNum = fn(string $t) =>
            '<td style="border:1px solid #d0d0d0;padding:4px 8px;text-align:right">'
            . htmlspecialchars($t) . '</td>';

        // Fila encabezados
        $headerRow = '';
        foreach ($cols as $key => [$label, $type, $required]) {
            $headerRow .= $th($label, $required);
        }

        // Fila claves técnicas
        $keyRow = '';
        foreach ($cols as $key => [$label, $type, $required]) {
            $keyRow .= $tdKey($key);
        }

        // Fila ejemplo
        $exRow = '';
        foreach ($cols as $key => [$label, $type, $required]) {
            $val = $example[$key] ?? '';
            $exRow .= $type === 'number' ? $tdNum($val) : $tdText($val);
        }

        // Tabla instrucciones
        $instrRows = '';
        $instrucciones = [
            ['nombre',            'Nombre del producto tal como aparece en la tienda',  'Sí'],
            ['sku',               'Código interno de tu negocio',                        'No'],
            ['codigo_barras',     'EAN/UPC — se guarda como texto (sin notación científica)', 'No'],
            ['categoria',         'Debe coincidir exactamente con una categoría existente','No'],
            ['precio',            'Precio de venta. Decimal con punto: 19.90',           'Sí'],
            ['precio_comparacion','Precio anterior tachado. Vacío si no aplica.',        'No'],
            ['costo',             'Costo de compra o producción',                        'No'],
            ['unidad',            'unidad / kg / lt / m / caja / par…',                  'No'],
            ['stock',             'Cantidad disponible en inventario',                   'No'],
            ['disponible',        'si = visible en tienda   |   no = oculto',            'No'],
            ['descripcion',       'Descripción larga del producto',                      'No'],
            ['notas',             'Notas internas (no se muestran en la tienda)',         'No'],
        ];
        foreach ($instrucciones as $row) {
            $instrRows .= '<tr>'
                . '<td style="font-family:monospace;font-weight:bold;border:1px solid #d0d0d0;padding:4px 10px;background:#f5f5f5">' . htmlspecialchars($row[0]) . '</td>'
                . '<td style="border:1px solid #d0d0d0;padding:4px 10px">' . htmlspecialchars($row[1]) . '</td>'
                . '<td style="border:1px solid #d0d0d0;padding:4px 10px;text-align:center;color:' . ($row[2]==='Sí'?'#c00':'#666') . ';font-weight:bold">' . $row[2] . '</td>'
                . '</tr>';
        }

        $html = <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="ProgId" content="Excel.Sheet">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>
<x:ExcelWorksheet><x:Name>Productos</x:Name><x:WorksheetOptions><x:Selected/></x:WorksheetOptions></x:ExcelWorksheet>
<x:ExcelWorksheet><x:Name>Instrucciones</x:Name></x:ExcelWorksheet>
</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
  body { font-family: Calibri, Arial, sans-serif; font-size:12px; }
  table { border-collapse:collapse; }
</style>
</head>
<body>

<table x:str border="1">
<tr>{$headerRow}</tr>
<tr>{$keyRow}</tr>
<tr>{$exRow}</tr>
</table>

<br><br>
<table border="1">
<tr>
  <th style="background:#4472C4;color:#fff;padding:6px 12px">Campo</th>
  <th style="background:#4472C4;color:#fff;padding:6px 12px">Descripción</th>
  <th style="background:#4472C4;color:#fff;padding:6px 12px">Obligatorio</th>
</tr>
{$instrRows}
</table>

</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_productos.xls"',
            'Cache-Control'       => 'no-cache, no-store',
            'Pragma'              => 'no-cache',
        ]);
    }

    // ── Detectar delimitador CSV ──────────────────────────────────────────────
    private function detectDelimiter(string $filePath): string
    {
        $line = fgets(fopen($filePath, 'r'));
        $semicolons = substr_count($line, ';');
        $commas     = substr_count($line, ',');
        return $semicolons >= $commas ? ';' : ',';
    }

    // ── Importar CSV ──────────────────────────────────────────────────────────
    public function import(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $request->validate(['file' => 'required|file|mimes:csv,txt,xls,xlsx|max:5120']);

        $file      = $request->file('file');
        $filePath  = $file->getRealPath();
        $delimiter = $this->detectDelimiter($filePath);
        $handle    = fopen($filePath, 'r');
        $header    = null;
        $created   = 0;
        $errors    = [];

        // Quitar BOM si existe
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            fseek($handle, 0);
        }

        // Saltar línea sep= si existe
        $firstLine = fgets($handle);
        if (!str_starts_with(trim($firstLine), 'sep=')) {
            fseek($handle, ftell($handle) - strlen($firstLine));
        }

        $categories = $project->categories()->pluck('id', 'name');

        // Claves técnicas válidas del sistema
        $validKeys = ['nombre','sku','codigo_barras','categoria','precio',
                      'precio_comparacion','costo','unidad','stock','disponible','descripcion','notas'];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!$header) {
                $cleaned = array_map('trim', $row);
                // Si esta fila tiene las claves técnicas → usarla como header
                if (count(array_intersect($cleaned, $validKeys)) >= 3) {
                    $header = $cleaned;
                }
                // Si es fila de etiquetas legibles → saltarla, la siguiente será las claves
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), ''));

            // Saltar fila de ejemplo si "nombre" es la clave técnica o está vacío
            if (trim($data['nombre'] ?? '') === 'nombre') continue;

            if (empty(trim($data['nombre'] ?? ''))) continue;

            try {
                // Limpiar barcode: quitar notación científica si Excel lo convirtió
                $barcode = trim($data['codigo_barras'] ?? '');
                if ($barcode && is_numeric($barcode) && str_contains(strtolower($barcode), 'e')) {
                    $barcode = number_format((float)$barcode, 0, '', '');
                }
                // Limpiar precio: puede venir con coma decimal (1.990,00 → 1990.00)
                $cleanNum = fn(string $v): ?float => $v !== '' ? (float)str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $v)) : null;

                Product::create([
                    'project_id'   => $project->id,
                    'name'         => trim($data['nombre']),
                    'sku'          => trim($data['sku'] ?? '') ?: null,
                    'barcode'      => $barcode ?: null,
                    'category_id'  => $categories[trim($data['categoria'] ?? '')] ?? null,
                    'price'        => $cleanNum($data['precio'] ?? '') ?? 0,
                    'compare_price'=> ($v = $cleanNum($data['precio_comparacion'] ?? '')) ? $v : null,
                    'cost'         => ($v = $cleanNum($data['costo'] ?? '')) ? $v : null,
                    'unit'         => trim($data['unidad'] ?? '') ?: null,
                    'stock'        => is_numeric($data['stock'] ?? '') ? (int)$data['stock'] : 0,
                    'is_available' => strtolower(trim($data['disponible'] ?? 'si')) === 'si',
                    'description'  => trim($data['descripcion'] ?? '') ?: null,
                    'notes'        => trim($data['notas'] ?? '') ?: null,
                ]);
                $created++;
            } catch (\Exception $e) {
                $errors[] = 'Fila ' . ($created + count($errors) + 2) . ': ' . $e->getMessage();
            }
        }
        fclose($handle);

        return response()->json(['created' => $created, 'errors' => $errors]);
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

        $filename = time() . '_' . uniqid() . '.webp';
        $destPath = $dir . '/' . $filename;

        // Convertir cualquier formato a WebP con GD y redimensionar a 600x600
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

        imagewebp($canvas, $destPath, 85);
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
            'is_available'     => (bool)$p->is_available,
            'category_id'      => $p->category_id,
            'brand_catalog_id' => $p->brand_catalog_id,
            'category_name'    => $p->category?->name,
            'images'           => $p->images->map(fn($i) => ['id' => $i->id, 'url' => $i->url, 'is_main' => $i->is_main])->values()->toArray(),
            'main_image'       => $p->mainImage?->url,
        ];
    }
}

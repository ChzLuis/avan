<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\Project;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Project $project)
    {
        $categories = $project->categories()->where('is_active', true)->orderBy('sort_order')->get();
        $products   = $project->products()->with(['category'])->orderBy('sort_order')->get();

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
            'price'            => 'required|numeric|min:0',
            'compare_price'    => 'nullable|numeric|min:0',
            'cost'             => 'nullable|numeric|min:0',
            'unit'             => 'nullable|string|max:30',
            'stock'            => 'nullable|integer|min:0',
            'is_available'     => 'boolean',
        ];
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate($this->rules());
        $data['project_id']   = $project->id;
        $data['is_available'] = $request->boolean('is_available', true);

        $product = Product::create($data);

        if ($request->expectsJson()) {
            return response()->json(['product' => $this->productRow($product)]);
        }
        return back()->with('success', 'Producto creado.');
    }

    public function update(Request $request, Project $project, Product $product)
    {
        abort_unless($product->project_id === $project->id, 403);

        $data = $request->validate($this->rules());
        $data['is_available'] = $request->boolean('is_available');
        $product->update($data);

        if ($request->expectsJson()) {
            return response()->json(['product' => $this->productRow($product->fresh())]);
        }
        return back()->with('success', 'Producto actualizado.');
    }

    public function destroy(Project $project, Product $product)
    {
        abort_unless($product->project_id === $project->id, 403);
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('success', 'Producto eliminado.');
    }

    // ── Exportar CSV ──────────────────────────────────────────────────────────
    public function export(Project $project)
    {
        $products = $project->products()->with('category')->orderBy('sort_order')->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="productos_' . $project->slug . '_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($products) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($f, ['nombre','sku','codigo_barras','categoria','precio','precio_comparacion','costo','unidad','stock','disponible','descripcion','notas']);
            foreach ($products as $p) {
                fputcsv($f, [
                    $p->name, $p->sku ?? '', $p->barcode ?? '',
                    $p->category?->name ?? '', $p->price,
                    $p->compare_price ?? '', $p->cost ?? '',
                    $p->unit ?? '', $p->stock ?? 0,
                    $p->is_available ? 'si' : 'no',
                    $p->description ?? '', $p->notes ?? '',
                ]);
            }
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Plantilla CSV vacía ───────────────────────────────────────────────────
    public function template(Project $project)
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_productos.csv"',
        ];

        $callback = function () {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, ['nombre','sku','codigo_barras','categoria','precio','precio_comparacion','costo','unidad','stock','disponible','descripcion','notas']);
            fputcsv($f, ['Producto ejemplo','SKU-001','7501234567890','Electrónica','199.90','249.90','120.00','unidad','50','si','Descripción del producto','Notas internas']);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Importar CSV ──────────────────────────────────────────────────────────
    public function import(Request $request, Project $project)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $file    = $request->file('file');
        $handle  = fopen($file->getRealPath(), 'r');
        $header  = null;
        $created = 0;
        $errors  = [];

        // Quitar BOM si existe
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            fseek($handle, 0);
        }

        $categories = $project->categories()->pluck('id', 'name');

        while (($row = fgetcsv($handle)) !== false) {
            if (!$header) { $header = array_map('trim', $row); continue; }
            $data = array_combine($header, array_pad($row, count($header), ''));

            if (empty(trim($data['nombre'] ?? ''))) continue;

            try {
                Product::create([
                    'project_id'   => $project->id,
                    'name'         => trim($data['nombre']),
                    'sku'          => trim($data['sku'] ?? '') ?: null,
                    'barcode'      => trim($data['codigo_barras'] ?? '') ?: null,
                    'category_id'  => $categories[trim($data['categoria'] ?? '')] ?? null,
                    'price'        => is_numeric($data['precio'] ?? '') ? (float)$data['precio'] : 0,
                    'compare_price'=> is_numeric($data['precio_comparacion'] ?? '') ? (float)$data['precio_comparacion'] : null,
                    'cost'         => is_numeric($data['costo'] ?? '') ? (float)$data['costo'] : null,
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
            'cost'             => $p->cost !== null ? (float)$p->cost : null,
            'unit'             => $p->unit,
            'stock'            => $p->stock,
            'is_available'     => (bool)$p->is_available,
            'category_id'      => $p->category_id,
            'brand_catalog_id' => $p->brand_catalog_id,
            'category_name'    => $p->category?->name,
        ];
    }
}

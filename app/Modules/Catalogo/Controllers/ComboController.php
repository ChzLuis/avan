<?php
namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\Combo;
use App\Modules\Catalogo\Models\ComboItem;
use App\Modules\Catalogo\Models\Product;
use Illuminate\Http\Request;

class ComboController extends Controller {

    public function index() {
        $project = app('active_project'); abort_unless($project, 403);
        $combos  = $project->combos()->with('items.product')->orderBy('sort_order')->get();

        $combosJson = $combos->map(function ($c) {
            return [
                'id'            => $c->id,
                'name'          => $c->name,
                'description'   => $c->description,
                'price'         => (float) $c->price,
                'compare_price' => $c->compare_price ? (float) $c->compare_price : null,
                'is_available'  => $c->is_available,
                'items'         => $c->items->map(function ($i) {
                    return [
                        'id'          => $i->id,
                        'item_type'   => $i->item_type,
                        'product_id'  => $i->product_id,
                        'custom_name' => $i->custom_name,
                        'quantity'    => $i->quantity,
                        'product'     => $i->product ? ['id' => $i->product->id, 'name' => $i->product->name] : null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return view('catalogo::combos.index', compact('combos', 'combosJson', 'project'));
    }

    public function store(Request $request) {
        $project = app('active_project'); abort_unless($project, 403);
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'is_available'  => 'nullable|boolean',
            'items'         => 'required|array|min:1',
            'items.*.type'       => 'required|in:product,custom',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name'       => 'nullable|string|max:120',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);
        $combo = $project->combos()->create([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'price'         => $data['price'],
            'compare_price' => $data['compare_price'] ?? null,
            'is_available'  => $data['is_available'] ?? true,
        ]);
        foreach ($data['items'] as $item) {
            $combo->items()->create([
                'item_type'   => $item['type'],
                'product_id'  => $item['product_id'] ?? null,
                'custom_name' => $item['name'] ?? null,
                'quantity'    => $item['quantity'],
            ]);
        }
        if ($request->wantsJson()) return response()->json(['ok'=>true,'combo'=>$combo->load('items.product')]);
        return back()->with('success','Combo creado.');
    }

    public function update(Request $request, Combo $combo) {
        $project = app('active_project');
        abort_unless($combo->project_id === $project->id, 403);
        $data = $request->validate([
            'name'          => 'required|string|max:120',
            'description'   => 'nullable|string|max:500',
            'price'         => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'is_available'  => 'nullable|boolean',
            'items'         => 'nullable|array',
            'items.*.type'       => 'required_with:items|in:product,custom',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name'       => 'nullable|string|max:120',
            'items.*.quantity'   => 'required_with:items|integer|min:1',
        ]);
        $combo->update([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'price'         => $data['price'],
            'compare_price' => $data['compare_price'] ?? null,
            'is_available'  => $data['is_available'] ?? $combo->is_available,
        ]);
        if (isset($data['items'])) {
            $combo->items()->delete();
            foreach ($data['items'] as $item) {
                $combo->items()->create([
                    'item_type'   => $item['type'],
                    'product_id'  => $item['product_id'] ?? null,
                    'custom_name' => $item['name'] ?? null,
                    'quantity'    => $item['quantity'],
                ]);
            }
        }
        if ($request->wantsJson()) return response()->json(['ok'=>true,'combo'=>$combo->load('items.product')]);
        return back()->with('success','Combo actualizado.');
    }

    public function destroy(Combo $combo) {
        $project = app('active_project');
        abort_unless($combo->project_id === $project->id, 403);
        $combo->delete();
        return response()->json(['ok'=>true]);
    }

    public function toggleAvailable(Combo $combo) {
        $project = app('active_project');
        abort_unless($combo->project_id === $project->id, 403);
        $combo->update(['is_available' => !$combo->is_available]);
        return response()->json(['ok'=>true,'is_available'=>$combo->is_available]);
    }
}

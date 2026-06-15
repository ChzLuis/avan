<?php
namespace App\Http\Controllers;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller {

    public function index() {
        $project    = app('active_project'); abort_unless($project, 403);
        $promotions = $project->promotions()->latest()->get();

        $promotionsJson = $promotions->map(function ($p) {
            return [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description,
                'type'        => $p->type,
                'value'       => (float) $p->value,
                'coupon_code' => $p->coupon_code,
                'max_uses'    => $p->max_uses,
                'uses_count'  => $p->uses_count,
                'min_order'   => $p->min_order ? (float) $p->min_order : null,
                'is_active'   => $p->is_active,
                'starts_at'   => $p->starts_at?->format('Y-m-d'),
                'ends_at'     => $p->ends_at?->format('Y-m-d'),
            ];
        })->values()->all();

        return view('promotions.index', compact('promotions', 'promotionsJson', 'project'));
    }

    public function store(Request $request) {
        $project = app('active_project'); abort_unless($project, 403);
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'type'        => 'required|in:percentage,fixed',
            'value'       => 'required|numeric|min:0',
            'coupon_code' => 'nullable|string|max:30',
            'max_uses'    => 'nullable|integer|min:1',
            'min_order'   => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
            'starts_at'   => 'nullable|date',
            'ends_at'     => 'nullable|date|after_or_equal:starts_at',
        ]);
        $promo = $project->promotions()->create($data);
        if ($request->wantsJson()) return response()->json(['ok'=>true,'promotion'=>$promo]);
        return back()->with('success','Promoción creada.');
    }

    public function update(Request $request, Promotion $promotion) {
        $project = app('active_project');
        abort_unless($promotion->project_id === $project->id, 403);
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'type'        => 'required|in:percentage,fixed',
            'value'       => 'required|numeric|min:0',
            'coupon_code' => 'nullable|string|max:30',
            'max_uses'    => 'nullable|integer|min:1',
            'min_order'   => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
            'starts_at'   => 'nullable|date',
            'ends_at'     => 'nullable|date|after_or_equal:starts_at',
        ]);
        $promotion->update($data);
        if ($request->wantsJson()) return response()->json(['ok'=>true,'promotion'=>$promotion]);
        return back()->with('success','Promoción actualizada.');
    }

    public function destroy(Promotion $promotion) {
        $project = app('active_project');
        abort_unless($promotion->project_id === $project->id, 403);
        $promotion->delete();
        return response()->json(['ok'=>true]);
    }

    public function toggle(Promotion $promotion) {
        $project = app('active_project');
        abort_unless($promotion->project_id === $project->id, 403);
        $promotion->update(['is_active' => !$promotion->is_active]);
        return response()->json(['ok'=>true,'is_active'=>$promotion->is_active]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    private function project(string $slug): Project
    {
        return Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public function catalog(string $slug)
    {
        $project    = $this->project($slug);
        $settings   = $project->settings()->pluck('value', 'key');
        $categories = $project->categories()->where('is_active', true)
            ->with(['products' => fn($q) => $q->where('is_available', true)->with('mainImage')->orderBy('sort_order')])
            ->orderBy('sort_order')->get();

        // Productos para secciones de la tienda
        $newArrivals = $project->products()->where('is_available', true)
            ->with(['mainImage','category'])->latest()->take(8)->get();
        $onSale = $project->products()->where('is_available', true)
            ->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')
            ->with(['mainImage','category'])->take(8)->get();
        $featured = $project->products()->where('is_available', true)
            ->with(['mainImage','category'])->inRandomOrder()->take(8)->get();

        return view('public.catalog', compact('project','categories','settings','newArrivals','onSale','featured'));
    }

    public function storeOrder(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:30',
            'client_email' => 'nullable|email|max:150',
            'notes'        => 'nullable|string',
            'items'        => 'required|array|min:1',
        ]);
        $total = collect($data['items'])->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1));
        $order = $project->orders()->create([
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'client_email' => $data['client_email'] ?? null,
            'notes'        => $data['notes'] ?? null,
            'total'        => $total,
            'status'       => 'pending',
        ]);
        foreach ($data['items'] as $item) {
            $order->items()->create($item);
        }
        return response()->json(['ok' => true, 'order_id' => $order->id, 'total' => (float) $order->total]);
    }

    public function storeQuote(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:30',
            'notes'        => 'nullable|string',
        ]);
        $project->quotes()->create([
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'notes'        => $data['notes'] ?? null,
            'total'        => 0,
            'status'       => 'draft',
        ]);
        return response()->json(['ok' => true]);
    }

    public function book(string $slug)
    {
        $project  = $this->project($slug);
        $services = $project->services()->where('is_available', true)->get();
        return view('public.book', compact('project', 'services'));
    }

    public function storeBook(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'service_id'   => 'required|integer',
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:30',
            'date'         => 'required|date|after_or_equal:today',
            'start_time'   => 'required',
        ]);
        $service = $project->services()->findOrFail($data['service_id']);
        $endTs   = strtotime($data['start_time']) + ($service->duration_min * 60);
        $project->appointments()->create([
            'service_id'   => $service->id,
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'date'         => $data['date'],
            'start_time'   => $data['start_time'],
            'end_time'     => date('H:i', $endTs),
            'status'       => 'pending',
        ]);
        return response()->json(['ok' => true]);
    }
}

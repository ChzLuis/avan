<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Project;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(Project $project)
    {
        $products = $project->products()
            ->where('is_available', true)
            ->with(['images' => fn($q) => $q->where('is_main', true)])
            ->orderBy('name')
            ->get();

        $categories = $project->categories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $paymentMethods = $this->catValues($project, 'payment_method');

        // Transactions today
        $transactions = $project->orders()
            ->where('sales_channel', 'pos')
            ->whereDate('created_at', today())
            ->latest()
            ->limit(50)
            ->get();

        $productsJs = $products->map(fn($p) => [
            'id'     => $p->id,
            'name'   => $p->name,
            'price'  => (float) $p->price,
            'cat_id' => $p->category_id,
            'image'  => $p->images->first() ? asset('storage/' . $p->images->first()->url) : null,
        ])->values();

        $categoriesJs = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values();

        $transactionsJs = $transactions->map(fn($t) => [
            'id'             => $t->id,
            'client_name'    => $t->client_name,
            'payment_method' => $t->payment_method ?? '—',
            'total'          => $t->total,
            'created_at'     => $t->created_at->format('H:i'),
        ])->values();

        return view('pos.index', compact('project', 'paymentMethods', 'productsJs', 'categoriesJs', 'transactionsJs'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'client_name'    => 'nullable|string|max:100',
            'client_phone'   => 'nullable|string|max:30',
            'payment_method' => 'required|string|max:80',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_id'   => 'nullable|integer',
            'items.*.name'         => 'required|string',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        $total = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        $order = $project->orders()->create([
            'client_name'    => $data['client_name'] ?? 'Cliente mostrador',
            'client_phone'   => $data['client_phone'] ?? null,
            'payment_method' => $data['payment_method'],
            'sales_channel'  => 'pos',
            'status'         => 'done',
            'notes'          => $data['notes'] ?? null,
            'total'          => $total,
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'name'       => $item['name'],
                'price'      => $item['price'],
                'quantity'   => $item['quantity'],
            ]);
        }

        return response()->json([
            'ok'    => true,
            'order' => $order->load('items'),
            'total' => $total,
        ]);
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }
}

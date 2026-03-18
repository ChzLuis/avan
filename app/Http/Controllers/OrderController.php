<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Project $project)
    {
        $orders            = $project->orders()->with('client')->latest()->get();
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        $salesChannels     = $this->catValues($project, 'sales_channel');
        return view('orders.index', compact('project', 'orders', 'paymentMethods', 'paymentConditions', 'salesChannels'));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'client_name'       => 'required|string|max:100',
            'client_phone'      => 'nullable|string|max:30',
            'client_id'         => 'nullable|integer',
            'notes'             => 'nullable|string',
            'payment_method'    => 'nullable|string|max:80',
            'payment_condition' => 'nullable|string|max:80',
            'sales_channel'     => 'nullable|string|max:80',
            'items'             => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $total = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);

        $order = $project->orders()->create([
            'client_id'         => $data['client_id'] ?? null,
            'client_name'       => $data['client_name'],
            'client_phone'      => $data['client_phone'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'sales_channel'     => $data['sales_channel'] ?? null,
            'total'             => $total,
            'status'            => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create($item);
        }

        return response()->json(['order' => $order->load('items')]);
    }

    public function update(Request $request, Project $project, Order $order)
    {
        abort_unless($order->project_id === $project->id, 403);
        $data = $request->validate(['status' => 'required|in:pending,process,done,cancelled', 'notes' => 'nullable|string', 'payment_method' => 'nullable|string|max:80', 'payment_condition' => 'nullable|string|max:80', 'sales_channel' => 'nullable|string|max:80']);
        $order->update($data);
        return response()->json(['order' => $order]);
    }

    public function destroy(Project $project, Order $order)
    {
        abort_unless($order->project_id === $project->id, 403);
        $order->delete();
        return response()->json(['ok' => true]);
    }
}

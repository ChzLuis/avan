<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Order;
use App\Models\Client;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $isSales = request()->routeIs('bixosales.*');
        /** @var \App\Models\Project $project */
        $project = $isSales
            ? Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
        $orders            = $project->orders()->with('client','items')->latest()->get();
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        $salesChannels     = $this->catValues($project, 'sales_channel');
        $portalLayout = $isSales ? 'comercial' : 'panel';
        return view('orders.index', compact('project', 'orders', 'paymentMethods', 'paymentConditions', 'salesChannels', 'portalLayout'));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
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

    public function show(Order $order)
    {
        $isSales = request()->routeIs('bixosales.*');
        /** @var \App\Models\Project $project */
        $project = $isSales
            ? \App\Models\Project::findOrFail(session('comercial_project_id'))
            : app('active_project');
        abort_unless($order->project_id === $project->id, 403);
        $order->load('items');
        $data = $order->toArray();
        if ($order->payment_proof) {
            $data['payment_proof'] = str_replace('http://', 'https://', asset('storage/' . $order->payment_proof));
        }
        return response()->json(['order' => $data]);
    }

    public function update(Request $request, Order $order)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($order->project_id === $project->id, 403);
        $data = $request->validate(['status' => 'required|in:pending,process,done,cancelled', 'notes' => 'nullable|string', 'payment_method' => 'nullable|string|max:80', 'payment_condition' => 'nullable|string|max:80', 'sales_channel' => 'nullable|string|max:80']);
        $order->update($data);
        return response()->json(['order' => $order]);
    }

    public function destroy(Order $order)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($order->project_id === $project->id, 403);
        $order->delete();
        return response()->json(['ok' => true]);
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    private function projectBySlug(string $slug): Project
    {
        return Project::where('slug', $slug)->firstOrFail();
    }

    public function indexPortal(string $slug)
    {
        $project           = $this->projectBySlug($slug);
        $orders            = $project->orders()->with('client')->latest()->get();
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        $salesChannels     = $this->catValues($project, 'sales_channel');
        return view('facturacion.pedidos.index', compact('project', 'orders', 'paymentMethods', 'paymentConditions', 'salesChannels'));
    }

    public function showPortal(string $slug, Order $order)
    {
        $project = $this->projectBySlug($slug);
        abort_unless($order->project_id === $project->id, 403);
        $order->load('items');
        return response()->json($order);
    }

    public function storePortal(Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    public function updatePortal(Request $request, string $slug, Order $order)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->update($request, $order);
    }
}

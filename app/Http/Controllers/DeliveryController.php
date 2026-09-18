<?php
namespace App\Http\Controllers;

use App\Modules\Ventas\Models\Order;
use App\Models\Project;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    private function project(): Project
    {
        return Project::findOrFail(session('comercial_project_id'));
    }

    public function index()
    {
        $project  = $this->project();

        $ordersRaw = $project->orders()
            ->where('order_type', 'delivery')
            ->whereNotIn('status', ['cancelled'])
            /* LO PENDIENTE NO CADUCA. El corte a 2 dias se aplicaba a TODO:
               un delivery sin entregar de hace 3 dias desaparecia del tablero
               —trabajo pendiente que nadie vuelve a ver— y la pantalla no
               decia en ningun sitio que solo mostraba 48 horas. Ahora el
               limite solo recorta lo ya cerrado; lo que sigue en marcha se ve
               siempre, por viejo que sea. */
            ->where(fn ($q) => $q->whereDate('created_at', '>=', now()->subDays(2))
                                 ->orWhereNotIn('status', ['done', 'completed', 'delivered']))
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        $ordersJson = $ordersRaw->map(function ($o) {
            return [
                'id'                    => $o->id,
                'client_name'           => $o->client_name,
                'client_phone'          => $o->client_phone,
                'delivery_address'      => $o->delivery_address,
                'delivery_status'       => $o->delivery_status ?? 'pending',
                'delivery_person_name'  => $o->delivery_person_name,
                'delivery_distance_km'  => $o->delivery_distance_km,
                'delivery_notes'        => $o->delivery_notes,
                'kitchen_status'        => $o->kitchen_status ?? 'pending',
                'status'                => $o->status,
                'total'                 => (float) $o->total,
                'shipping_cost'         => (float) ($o->shipping_cost ?? 0),
                'payment_method'        => $o->payment_method,
                'notes'                 => $o->notes,
                'created_at'            => $o->created_at->toISOString(),
                'dispatched_at'         => $o->delivery_dispatched_at?->toISOString(),
                'delivered_at'          => $o->delivery_delivered_at?->toISOString(),
                'items'                 => $o->items->map(function ($i) {
                    return ['name' => $i->name, 'quantity' => $i->quantity, 'price' => (float) $i->price];
                })->values()->all(),
            ];
        })->values()->all();

        // Repartidores del proyecto (employees con rol delivery)
        $repartidores = $project->employees()
            ->where('is_active', true)
            ->get()
            ->map(fn($e) => ['id' => $e->id, 'name' => $e->name])
            ->values()->all();

        return view('comercial.delivery', compact('project', 'ordersJson', 'repartidores'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $project = $this->project();
        abort_unless($order->project_id === $project->id, 403);

        $data = $request->validate([
            'delivery_status'       => 'required|in:assigned,in_route,delivered,rejected',
            'delivery_person_name'  => 'nullable|string|max:100',
            'delivery_notes'        => 'nullable|string|max:500',
        ]);

        $update = ['delivery_status' => $data['delivery_status']];
        if (!empty($data['delivery_person_name'])) {
            $update['delivery_person_name'] = $data['delivery_person_name'];
        }
        if (!empty($data['delivery_notes'])) {
            $update['delivery_notes'] = $data['delivery_notes'];
        }

        if ($data['delivery_status'] === 'assigned' && !$order->delivery_assigned_at) {
            $update['delivery_assigned_at'] = now();
        }
        if ($data['delivery_status'] === 'in_route' && !$order->delivery_dispatched_at) {
            $update['delivery_dispatched_at'] = now();
            $update['status'] = 'process';
        }
        if ($data['delivery_status'] === 'delivered') {
            $update['delivery_delivered_at'] = now();
            $update['status'] = 'done';
        }
        if ($data['delivery_status'] === 'rejected') {
            $update['status'] = 'cancelled';
        }

        $order->update($update);
        return response()->json(['ok' => true, 'order' => $order->fresh()]);
    }

    // Nuevo pedido delivery desde el portal
    public function store(Request $request)
    {
        $project = $this->project();
        $data    = $request->validate([
            'client_name'      => 'required|string|max:100',
            'client_phone'     => 'nullable|string|max:30',
            'delivery_address' => 'required|string|max:300',
            'delivery_notes'   => 'nullable|string|max:300',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'payment_method'   => 'required|string|max:60',
            'notes'            => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $subtotal  = collect($data['items'])->sum(fn($i) => $i['price'] * $i['quantity']);
        $shipping  = (float) ($data['shipping_cost'] ?? 0);
        $total     = $subtotal + $shipping;

        $order = $project->orders()->create([
            'client_name'      => $data['client_name'],
            'client_phone'     => $data['client_phone'] ?? null,
            'delivery_address' => $data['delivery_address'],
            'delivery_notes'   => $data['delivery_notes'] ?? null,
            'shipping_cost'    => $shipping,
            'payment_method'   => $data['payment_method'],
            'notes'            => $data['notes'] ?? null,
            'order_type'       => 'delivery',
            'sales_channel'    => 'pos',
            'status'           => 'pending',
            'kitchen_status'   => 'pending',
            'delivery_status'  => 'pending',
            'total'            => $total,
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'name'     => $item['name'],
                'price'    => $item['price'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response()->json(['ok' => true, 'order' => $order->load('items')]);
    }

    public function data()
    {
        $project    = $this->project();
        $ordersRaw  = $project->orders()
            ->where('order_type', 'delivery')
            ->whereNotIn('status', ['cancelled', 'done'])
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'orders' => $ordersRaw->map(function ($o) {
                return [
                    'id'                   => $o->id,
                    'client_name'          => $o->client_name,
                    'client_phone'         => $o->client_phone,
                    'delivery_address'     => $o->delivery_address,
                    'delivery_status'      => $o->delivery_status ?? 'pending',
                    'delivery_person_name' => $o->delivery_person_name,
                    'kitchen_status'       => $o->kitchen_status ?? 'pending',
                    'status'               => $o->status,
                    'total'                => (float) $o->total,
                    'shipping_cost'        => (float) ($o->shipping_cost ?? 0),
                    'created_at'           => $o->created_at->toISOString(),
                    'dispatched_at'        => $o->delivery_dispatched_at?->toISOString(),
                    'items'                => $o->items->map(fn($i) => [
                        'name' => $i->name, 'quantity' => $i->quantity
                    ])->values()->all(),
                ];
            })->values()->all(),
        ]);
    }
}

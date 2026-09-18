<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Client;
use App\Modules\Ventas\Models\Order;
use App\Modules\Catalogo\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Portal del Cliente (Fase 11): el enlace personal con el que un cliente
 * repite sus pedidos sin llamar, sin app y sin contraseña.
 *
 * El token en la URL es la llave, como en las cotizaciones. Nada se cobra
 * solo: todo entra como pendiente o como cotización, según el interruptor
 * del negocio:
 *
 *   portal_precios = fijos      → repetir crea un PEDIDO pendiente al precio
 *                                 de catálogo VIGENTE (no el histórico).
 *   portal_precios = confirmar  → repetir crea una COTIZACIÓN: el negocio
 *                                 pone el precio del día y el cliente acepta
 *                                 desde su enlace (circuito ya existente).
 */
class PortalClienteController extends Controller
{
    /** Resuelve el cliente por token o no existe (sin filtrar por sesión:
     *  esta puerta es pública y la llave es el token). */
    private function clientePorToken(string $token): Client
    {
        abort_if(strlen($token) < 20, 404);

        return Client::allProjects()->where('portal_token', $token)->firstOrFail();
    }

    public function ver(string $token)
    {
        $cliente  = $this->clientePorToken($token);
        $project  = $cliente->project;
        abort_unless($project && $project->is_active, 404);

        $pedidos = $cliente->orders()
            ->with('items')
            ->where('status', '!=', 'cancelled')
            ->latest()->limit(10)->get();

        $modo = $project->setting('portal_precios', 'confirmar');

        return view('ventas::portal-cliente.inicio', compact('cliente', 'project', 'pedidos', 'modo'));
    }

    public function repetir(Request $request, string $token, int $orderId)
    {
        $cliente = $this->clientePorToken($token);
        $project = $cliente->project;
        abort_unless($project && $project->is_active, 404);

        $original = $cliente->orders()->with('items')->findOrFail($orderId);
        abort_if($original->items->isEmpty(), 422, 'El pedido no tiene líneas.');

        $modo = $project->setting('portal_precios', 'confirmar');

        if ($modo === 'fijos') {
            // Pedido pendiente al precio de catálogo VIGENTE: si el producto
            // ya no existe o está oculto, la línea viaja igual con su nombre
            // y el negocio la resuelve al confirmar.
            $nuevo = $project->orders()->create([
                'client_id'     => $cliente->id,
                'client_name'   => $cliente->name,
                'client_phone'  => $cliente->phone,
                'status'        => 'pending',
                'payment_status'=> 'pending',
                'sales_channel' => 'portal',
                'total'         => 0,
                'notes'         => 'Repetición del pedido #'.$original->id.' desde el portal del cliente.',
            ]);
            $total = 0.0;
            foreach ($original->items as $item) {
                $producto = $item->product_id ? Product::allProjects()->find($item->product_id) : null;
                $precio   = $producto && (float) $producto->price > 0 ? (float) $producto->price : (float) $item->price;
                $nuevo->items()->create([
                    'product_id' => $item->product_id,
                    'name'       => $item->name,
                    'price'      => $precio,
                    'quantity'   => $item->quantity,
                ]);
                $total += $precio * (float) $item->quantity;
            }
            $nuevo->update(['total' => round($total, 2)]);

            return redirect()->route('portal.cliente', $token)
                ->with('portal_ok', 'Tu pedido fue enviado. '.$project->name.' lo confirmará en breve.');
        }

        // Modo a confirmar: nace una cotización SIN precio comprometido.
        $quote = $project->quotes()->create([
            'client_id'   => $cliente->id,
            'client_name' => $cliente->name,
            'status'      => 'draft',
            'total'       => 0,
            'token'       => Str::random(48),
            'notes'       => 'Solicitud desde el portal del cliente (repite el pedido #'.$original->id.'). Precios por confirmar.',
        ]);
        foreach ($original->items as $item) {
            $quote->items()->create([
                'description' => $item->name,
                'price'       => 0,
                'quantity'    => $item->quantity,
                'discount'    => 0,
            ]);
        }

        return redirect()->route('portal.cliente', $token)
            ->with('portal_ok', 'Tu solicitud fue enviada. '.$project->name.' te cotizará los precios del día.');
    }

    /** Genera (o regenera) el enlace del portal desde la ficha del cliente. */
    public function generarEnlace(Client $client)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($client->project_id === $project->id, 403);

        $client->update(['portal_token' => Str::random(48)]);

        return response()->json([
            'url' => route('portal.cliente', $client->portal_token),
        ]);
    }
}

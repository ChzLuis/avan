<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\OrderPackage;
use App\Modules\Ventas\Models\Order;
use App\Modules\Ventas\Models\OrderEvent;
use App\Support\Qr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bultos de pedido: las cajas que salen en el camion.
 *
 * Se escanea el bulto al cargar y al entregar. Cada paso queda en
 * `OrderEvent`, el historial del pedido que ya existe: el equipo de ventas ve
 * el avance del despacho sin tener que entrar a otra pantalla.
 */
class BultoController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $query = OrderPackage::where('project_id', $project->id)->with('order');

        if ($buscar = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('codigo', 'like', "%{$buscar}%")
                ->orWhere('descripcion', 'like', "%{$buscar}%"));
        }
        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        $bultos = $query->orderByDesc('id')->limit(200)->get();

        $todos = OrderPackage::where('project_id', $project->id)->get(['estado']);
        $resumen = [
            'preparados' => $todos->where('estado', 'preparado')->count(),
            'despachados' => $todos->where('estado', 'despachado')->count(),
            'entregados' => $todos->where('estado', 'entregado')->count(),
        ];

        // Pedidos a los que todavia se les puede agregar bultos.
        $pedidos = Order::where('project_id', $project->id)
            ->whereNotIn('status', ['cancelled', 'cancelado'])
            ->orderByDesc('id')->limit(60)
            ->get(['id', 'numero', 'client_name', 'status', 'total']);

        $estados = OrderPackage::ESTADOS;

        return view('inventario::inventory.bultos', compact(
            'project', 'bultos', 'resumen', 'pedidos', 'estados'
        ));
    }

    /** Crea uno o varios bultos para un pedido. */
    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'order_id' => 'required|integer',
            'cantidad' => 'required|integer|min:1|max:50',
            'descripcion' => 'nullable|string|max:150',
            'peso_kg' => 'nullable|numeric|min:0',
        ]);

        $pedido = Order::where('project_id', $project->id)->whereKey($datos['order_id'])->firstOrFail();

        $creados = DB::transaction(function () use ($project, $pedido, $datos) {
            // El bulto ya existente marca desde donde seguir numerando: si se
            // agregan cajas despues, no se repite el 1 de 3.
            $desde = OrderPackage::where('order_id', $pedido->id)->count();
            $total = $desde + $datos['cantidad'];
            $creados = [];

            for ($i = 1; $i <= $datos['cantidad']; $i++) {
                $n = $desde + $i;
                $creados[] = OrderPackage::create([
                    'project_id' => $project->id,
                    'order_id' => $pedido->id,
                    'codigo' => $this->codigo($project->id, $pedido->id, $n),
                    'descripcion' => $datos['descripcion'] ?: "Bulto {$n} de {$total}",
                    'peso_kg' => $datos['peso_kg'] ?? null,
                    'estado' => 'preparado',
                ]);
            }

            return $creados;
        });

        return back()->with('success', count($creados).' bulto(s) creados para el pedido.');
    }

    /**
     * Avanza el bulto al siguiente paso y lo anota en el historial del pedido.
     *
     * Responde JSON porque se usa escaneando en el muelle de carga, uno tras
     * otro, sin recargar la pantalla.
     */
    public function avanzar(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'codigo' => 'required|string|max:40',
            'recibido_por' => 'nullable|string|max:120',
        ]);

        $codigo = strtoupper(trim($datos['codigo']));
        $bulto = OrderPackage::where('project_id', $project->id)->where('codigo', $codigo)->first();

        if (! $bulto) {
            return response()->json(['ok' => false, 'error' => "No existe el bulto {$codigo}."], 404);
        }

        $siguiente = $bulto->siguienteEstado();
        if (! $siguiente) {
            return response()->json([
                'ok' => false,
                'error' => "El bulto {$codigo} ya estaba entregado".
                    ($bulto->entregado_at ? ' el '.$bulto->entregado_at->format('d/m/Y H:i') : '').'.',
            ], 422);
        }

        DB::transaction(function () use ($bulto, $siguiente, $datos) {
            $bulto->estado = $siguiente;
            if ($siguiente === 'despachado') {
                $bulto->despachado_at = now();
            } else {
                $bulto->entregado_at = now();
                $bulto->recibido_por = $datos['recibido_por'] ?? null;
            }
            $bulto->save();

            // El rastro va al historial del pedido, que es inmutable y donde
            // el equipo ya mira. No se duplica aqui.
            OrderEvent::create([
                'project_id' => $bulto->project_id,
                'order_id' => $bulto->order_id,
                'user_id' => auth()->id(),
                'action' => 'package_'.$siguiente,
                'meta' => [
                    'codigo' => $bulto->codigo,
                    'recibido_por' => $bulto->recibido_por,
                ],
                'created_at' => now(),
            ]);
        });

        $pendientes = OrderPackage::where('order_id', $bulto->order_id)
            ->whereNot('estado', 'entregado')->count();

        return response()->json([
            'ok' => true,
            'bulto' => [
                'codigo' => $bulto->codigo,
                'estado' => $bulto->estado,
                'etiqueta' => $bulto->etiquetaEstado(),
                'descripcion' => $bulto->descripcion,
            ],
            'pendientes_del_pedido' => $pendientes,
        ]);
    }

    /** Etiquetas de los bultos que todavia no se entregaron. */
    public function etiquetas(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $query = OrderPackage::where('project_id', $project->id)->with('order');
        if ($pedido = $request->query('order_id')) {
            $query->where('order_id', $pedido);
        } else {
            $query->whereNot('estado', 'entregado');
        }

        $bultos = $query->orderBy('id')->limit(300)->get();

        $etiquetas = $bultos->map(fn ($b) => [
            'nombre' => ($b->order->client_name ?? 'Pedido').' · '.($b->descripcion ?: ''),
            'codigo' => $b->codigo,
            'precio' => null,
            'unidad' => $b->peso_kg ? $b->peso_kg.' kg' : null,
            'qr' => Qr::svg($b->codigo, 120),
        ])->all();

        return view('inventario::inventory.etiquetas-hoja', [
            'project' => $project,
            'etiquetas' => $etiquetas,
            'formato' => EtiquetaController::FORMATOS['a4-24'],
            'mostrarPrecio' => false,
        ]);
    }

    /** Codigo legible y unico: BLT-<pedido>-<n>. */
    private function codigo(int $projectId, int $orderId, int $n): string
    {
        $base = 'BLT-'.$orderId.'-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT);

        // Cinturon y tirantes: si por lo que sea ya existiera, se corre el
        // numero hasta encontrar uno libre en vez de reventar por la unicidad.
        $i = $n;
        while (OrderPackage::where('project_id', $projectId)->where('codigo', $base)->exists()) {
            $i++;
            $base = 'BLT-'.$orderId.'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        }

        return $base;
    }
}

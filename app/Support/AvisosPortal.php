<?php

namespace App\Support;

use App\Modules\Ventas\Support\OrderFlow;
use App\Modules\Finanzas\Support\Cobranza;

use App\Modules\Ventas\Models\Order;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;

/**
 * Lo que la campana del portal tiene que decir.
 *
 * Antes los tres contadores de la cabecera eran literales escritos en la
 * plantilla —`3`, `5`, `8`— y el panel de alertas solo miraba el SLA de
 * lavandería: como `OrderFlow::supportsFlow()` es cierto para cualquier rubro,
 * una ferretería entraba por esa rama, consultaba `laundry_status` (columna
 * que nunca rellena) y mostraba "Sin alertas activas" teniendo once documentos
 * vencidos y ocho pedidos sin atender.
 *
 * Un aviso que no se corresponde con nada es peor que no tener avisos: enseña
 * al usuario a ignorar la campana.
 *
 * Todo lo que sale de aqui esta consultado, tiene su importe y su enlace, y
 * ordena por gravedad: primero lo que cuesta dinero, luego lo que hace esperar
 * al cliente, y al final lo que se puede planificar.
 */
final class AvisosPortal
{
    /**
     * @return array{avisos: array, total: int, criticos: int, pendientes: int}
     */
    public static function resumen(Project $project): array
    {
        return Cache::remember("portal.avisos.{$project->id}", 60, function () use ($project) {
            $avisos = [];

            // ── Dinero vencido ────────────────────────────────────────────
            $cartera = Cobranza::cartera($project);
            if ($cartera['vencidas'] > 0) {
                $avisos[] = [
                    'nivel'   => 'alto',
                    'titulo'  => $cartera['vencidas'] . ' documento' . ($cartera['vencidas'] === 1 ? '' : 's') . ' vencido' . ($cartera['vencidas'] === 1 ? '' : 's'),
                    'detalle' => 'S/ ' . LineMath::present(LineMath::format($cartera['vencido_cents'])) . ' pendientes de cobro',
                    'url'     => route('bixosales.cuentas'),
                ];
            }

            // ── Pedidos parados ───────────────────────────────────────────
            // Los umbrales son del proyecto (`orders_aviso_horas` /
            // `orders_critico_horas`), los mismos que colorean la cola del
            // panel: un pedido "va tarde" cuando lo dice el negocio, no cuando
            // lo decide una constante.
            $critico = max(2, (int) $project->setting('orders_critico_horas', 72));
            $aviso   = max(1, (int) $project->setting('orders_aviso_horas', 24));

            $atrasados = Order::where('project_id', $project->id)
                ->whereIn('status', ['pending', 'process'])
                ->where('created_at', '<', now()->subHours($critico))
                ->count();
            if ($atrasados > 0) {
                $avisos[] = [
                    'nivel'   => 'alto',
                    'titulo'  => $atrasados . ' pedido' . ($atrasados === 1 ? '' : 's') . ' muy atrasado' . ($atrasados === 1 ? '' : 's'),
                    'detalle' => 'Llevan más de ' . $critico . ' h sin cerrarse',
                    'url'     => route('bixosales.pedidos'),
                ];
            }

            $enEspera = Order::where('project_id', $project->id)
                ->whereIn('status', ['pending', 'process'])
                ->whereBetween('created_at', [now()->subHours($critico), now()->subHours($aviso)])
                ->count();
            if ($enEspera > 0) {
                $avisos[] = [
                    'nivel'   => 'medio',
                    'titulo'  => $enEspera . ' pedido' . ($enEspera === 1 ? '' : 's') . ' esperando',
                    'detalle' => 'Más de ' . $aviso . ' h sin moverse',
                    'url'     => route('bixosales.pedidos'),
                ];
            }

            // ── Stock ─────────────────────────────────────────────────────
            $stock = $project->products()
                ->whereNotNull('stock')
                ->whereRaw('stock <= COALESCE(stock_min, 0)')
                ->count();
            if ($stock > 0) {
                $avisos[] = [
                    'nivel'   => 'medio',
                    'titulo'  => $stock . ' producto' . ($stock === 1 ? '' : 's') . ' con stock crítico',
                    'detalle' => 'Están en su mínimo o por debajo',
                    'url'     => route('bixosales.reportes.inventario'),
                ];
            }

            // ── Comprobantes del cliente sin revisar ──────────────────────
            $porRevisar = $project->orders()
                ->whereIn('payment_status', ['revision', 'en_revision'])
                ->count();
            if ($porRevisar > 0) {
                $avisos[] = [
                    'nivel'   => 'alto',
                    'titulo'  => $porRevisar . ' pago' . ($porRevisar === 1 ? '' : 's') . ' por aprobar',
                    'detalle' => 'El cliente reportó el pago y falta validarlo',
                    'url'     => route('bixosales.pedidos'),
                ];
            }

            $pendientes = Order::where('project_id', $project->id)
                ->whereIn('status', ['pending', 'process'])->count();

            return [
                'avisos'     => $avisos,
                'total'      => count($avisos),
                'criticos'   => count(array_filter($avisos, fn ($a) => $a['nivel'] === 'alto')),
                'pendientes' => $pendientes,
            ];
        });
    }
}

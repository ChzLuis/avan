<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Vender desde el chat: la extensión crea PEDIDOS y COTIZACIONES reales en BIXO
 * sin salir de WhatsApp. Auth por token de proyecto.
 *
 * SIN facturación electrónica — solo pedido/cotización comercial.
 */
class VentaExtensionController extends Controller
{
    private function project(Request $r): Project
    {
        $token = $r->header('X-Copilot-Token') ?? $r->input('token');
        $project = $token ? Project::where('copilot_token', $token)->first() : null;
        abort_if(!$project, 401, 'Token inválido.');
        return $project;
    }

    /** Normaliza los items recibidos (product_id o nombre libre + precio + cantidad). */
    private function parseItems(Project $project, array $items): array
    {
        $out = []; $total = 0;
        foreach ($items as $it) {
            $qty = max(1, (int) ($it['cantidad'] ?? 1));
            $precio = isset($it['precio']) ? (float) $it['precio'] : 0;
            $nombre = $it['nombre'] ?? '';
            // Si viene product_id, tomar precio/nombre reales del catálogo.
            if (!empty($it['product_id'])) {
                $p = Product::where('project_id', $project->id)->find($it['product_id']);
                if ($p) { $nombre = $p->name; $precio = (float) $p->price; }
            }
            $sub = $precio * $qty;
            $total += $sub;
            $out[] = ['nombre' => $nombre, 'precio' => $precio, 'cantidad' => $qty, 'subtotal' => $sub];
        }
        return ['items' => $out, 'total' => $total];
    }

    /** Crear un PEDIDO real desde el chat. */
    public function crearPedido(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate([
            'telefono' => 'nullable|string',
            'nombre'   => 'nullable|string',
            'items'    => 'required|array|min:1',
            'descuento'=> 'nullable|numeric',
            'notas'    => 'nullable|string',
            'pagado'   => 'nullable|boolean',
            'extras'   => 'nullable|array',   // campos adicionales (garantía, entrega, etc.)
        ]);

        $parsed = $this->parseItems($project, $data['items']);
        $descuento = (float) ($data['descuento'] ?? 0);
        $total = max(0, $parsed['total'] - $descuento);
        $esVenta = (bool) ($data['pagado'] ?? false);

        $order = Order::create([
            'project_id'    => $project->id,
            'client_name'   => $data['nombre'] ?? ($data['telefono'] ?? 'Cliente'),
            'client_phone'  => $data['telefono'] ?? null,
            // "pagado" = pago confirmado, NO venta culminada (Codex 2026-08-15):
            // el comercial nace pending siempre; solo el pago refleja el cobro.
            'status'        => 'pending',
            'payment_status'=> $esVenta ? 'paid' : 'pending',
            'notes'         => $this->notasConExtras($data['notas'] ?? null, $data['extras'] ?? []),
            'discount'      => $descuento,
            'total'         => $total,
            'sales_channel' => 'whatsapp',
        ]);
        foreach ($parsed['items'] as $it) {
            OrderItem::create([
                'order_id' => $order->id,
                'name' => $it['nombre'],
                'price' => $it['precio'],
                'quantity' => $it['cantidad'],
            ]);
        }

        return response()->json([
            'ok' => true,
            'pedido_id' => $order->id,
            'total' => $total,
            'resumen' => $this->resumenTexto('Pedido', $order->id, $parsed['items'], $total, $descuento),
        ]);
    }

    /** Crear una COTIZACIÓN real desde el chat (con enlace para enviar). */
    public function crearCotizacion(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate([
            'telefono' => 'nullable|string',
            'nombre'   => 'nullable|string',
            'items'    => 'required|array|min:1',
            'descuento'=> 'nullable|numeric',
            'notas'    => 'nullable|string',
            'extras'   => 'nullable|array',
        ]);

        $parsed = $this->parseItems($project, $data['items']);
        $descuento = (float) ($data['descuento'] ?? 0);
        $total = max(0, $parsed['total'] - $descuento);

        $quote = Quote::create([
            'project_id'   => $project->id,
            'client_name'  => $data['nombre'] ?? ($data['telefono'] ?? 'Cliente'),
            'client_phone' => $data['telefono'] ?? null,
            // Canonico + pago explicito: neutraliza el DEFAULT 'pendiente'
            // del esquema sin migrarlo (QuoteStatus normaliza el legado).
            'status'         => 'draft',
            'payment_status' => 'pending',
            'notes'        => $this->notasConExtras($data['notas'] ?? null, $data['extras'] ?? []),
            'total'        => $total,
            'valid_until'  => now()->addDays(15),
            'token'        => Str::random(24),
        ]);
        foreach ($parsed['items'] as $it) {
            QuoteItem::create([
                'quote_id' => $quote->id,
                'description' => $it['nombre'],
                'price' => $it['precio'],
                'quantity' => $it['cantidad'],
            ]);
        }

        // Enlace público de la cotización (si el proyecto tiene slug).
        $enlace = url("/{$project->slug}/cotizacion/{$quote->token}");

        return response()->json([
            'ok' => true,
            'cotizacion_id' => $quote->id,
            'total' => $total,
            'enlace' => $enlace,
            'resumen' => $this->resumenTexto('Cotización', $quote->id, $parsed['items'], $total, $descuento),
        ]);
    }

    /** Combina las notas libres con los campos adicionales (garantía, entrega, etc.). */
    private function notasConExtras(?string $notas, array $extras): ?string
    {
        $partes = [];
        if ($notas) $partes[] = trim($notas);
        $labels = [
            'garantia' => 'Garantía', 'entrega' => 'Entrega', 'forma_pago' => 'Forma de pago',
            'referencia' => 'Referencia', 'vendedor' => 'Vendedor',
        ];
        foreach ($labels as $k => $lbl) {
            if (!empty($extras[$k])) $partes[] = "$lbl: {$extras[$k]}";
        }
        return $partes ? implode("\n", $partes) : null;
    }

    /** Genera el texto de una encuesta postventa para enviar por WhatsApp. */
    public function encuestaPostventa(Request $r): JsonResponse
    {
        $project = $this->project($r);
        $data = $r->validate(['nombre' => 'nullable|string', 'pedido_id' => 'nullable|integer']);
        $nombre = $data['nombre'] ?? '';
        $ref = !empty($data['pedido_id']) ? " (pedido #{$data['pedido_id']})" : '';
        $texto = "¡Hola" . ($nombre ? " $nombre" : '') . "! 🙏\n"
            . "Gracias por tu compra en {$project->name}{$ref}.\n\n"
            . "¿Cómo calificarías tu experiencia?\n"
            . "⭐ Responde del 1 al 5 (5 = excelente)\n\n"
            . "Tu opinión nos ayuda a mejorar. ¡Gracias!";
        return response()->json(['texto' => $texto]);
    }

    /** Texto formateado (plantilla WhatsApp) del pedido/cotización para el chat. */
    private function resumenTexto(string $tipo, int $id, array $items, float $total, float $descuento): string
    {
        $emoji = $tipo === 'Cotización' ? '📋' : '🧾';
        $subtotal = array_sum(array_column($items, 'subtotal'));

        $l = [];
        $l[] = "$emoji *{$tipo} N° {$id}*";
        $l[] = "━━━━━━━━━━━━━━━";
        foreach ($items as $it) {
            $l[] = "▪️ *{$it['nombre']}*";
            $l[] = "    {$it['cantidad']} x S/ " . number_format($it['precio'], 2)
                 . " = S/ " . number_format($it['subtotal'], 2);
        }
        $l[] = "━━━━━━━━━━━━━━━";
        if ($descuento > 0) {
            $l[] = "Subtotal: S/ " . number_format($subtotal, 2);
            $l[] = "Descuento: -S/ " . number_format($descuento, 2);
        }
        $l[] = "💰 *TOTAL: S/ " . number_format($total, 2) . "*";
        return implode("\n", $l);
    }
}

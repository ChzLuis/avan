<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WooSyncController extends Controller
{
    const WOO_URL    = 'https://pruebatusuerte.com.pe';
    const WOO_KEY    = 'ck_03a7e535d3b4d1598b6542f0151dc9bda8a29a93';
    const WOO_SECRET = 'cs_7c63b6ac7c9fccaee321d9fb2094474d0efc2839';
    const PROJECT_ID = 1;

    // ── Webhook: WooCommerce llama aquí cuando hay pedido nuevo/actualizado ──
    public function webhook(Request $request)
    {
        $secret = config('app.woo_webhook_secret', 'bixo-woo-2024');
        $sig    = $request->header('X-WC-Webhook-Signature');

        // Validar firma si viene
        if ($sig) {
            $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));
            if (!hash_equals($computed, $sig)) {
                return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
            }
        }

        $data = $request->all();
        if (empty($data['id'])) return response()->json(['ok' => false], 400);

        $this->upsertOrder($data);
        return response()->json(['ok' => true]);
    }

    // ── Sincronización manual desde el panel ────────────────────────────────
    public function sync(Request $request)
    {
        $page    = 1;
        $synced  = 0;
        $perPage = 50;

        do {
            $res = Http::withBasicAuth(self::WOO_KEY, self::WOO_SECRET)
                ->timeout(30)
                ->get(self::WOO_URL . '/wp-json/wc/v3/orders', [
                    'per_page'  => $perPage,
                    'page'      => $page,
                    'orderby'   => 'date',
                    'order'     => 'desc',
                ]);

            if (!$res->successful()) break;

            $orders = $res->json();
            if (empty($orders)) break;

            foreach ($orders as $order) {
                $this->upsertOrder($order);
                $synced++;
            }

            $page++;
        } while (count($orders) === $perPage);

        return response()->json(['ok' => true, 'synced' => $synced]);
    }

    // ── Insertar o actualizar pedido ─────────────────────────────────────────
    private function upsertOrder(array $o): void
    {
        $billing   = $o['billing'] ?? [];
        $firstName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
        $phone     = $billing['phone'] ?? null;
        $email     = $billing['email'] ?? null;

        $items = collect($o['line_items'] ?? [])->map(fn($i) => [
            'name'     => strip_tags($i['name'] ?? ''),
            'quantity' => $i['quantity'] ?? 1,
            'total'    => $i['total'] ?? 0,
        ])->toArray();

        DB::table('woo_orders')->upsert([
            'woo_id'               => $o['id'],
            'project_id'           => self::PROJECT_ID,
            'status'               => $o['status'] ?? 'pending',
            'client_name'          => $firstName ?: null,
            'client_phone'         => $phone,
            'client_email'         => $email,
            'total'                => (float) ($o['total'] ?? 0),
            'payment_method'       => $o['payment_method'] ?? null,
            'payment_method_title' => $o['payment_method_title'] ?? null,
            'line_items'           => json_encode($items),
            'order_number'         => $o['number'] ?? $o['id'],
            'woo_created_at'       => isset($o['date_created']) ? Carbon::parse($o['date_created']) : null,
            'updated_at'           => now(),
            'created_at'           => now(),
        ], ['woo_id'], [
            'status', 'client_name', 'client_phone', 'client_email',
            'total', 'payment_method', 'payment_method_title',
            'line_items', 'order_number', 'updated_at',
        ]);
    }

    // ── Vista panel comercial ────────────────────────────────────────────────
    public function index(Request $request)
    {
        $project = \App\Models\Project::findOrFail(session('comercial_project_id', self::PROJECT_ID));
        $tz      = 'America/Lima';
        $desde   = $request->get('desde', now($tz)->subDays(30)->format('Y-m-d'));
        $hasta   = $request->get('hasta', now($tz)->format('Y-m-d'));
        $buscar  = trim($request->get('buscar', ''));
        $status  = $request->get('status', '');

        $query = DB::table('woo_orders')->where('project_id', $project->id);

        $query->where('woo_created_at', '>=', Carbon::createFromFormat('Y-m-d', $desde, $tz)->startOfDay()->utc());
        $query->where('woo_created_at', '<=', Carbon::createFromFormat('Y-m-d', $hasta, $tz)->endOfDay()->utc());

        if ($status) $query->where('status', $status);
        if ($buscar) {
            $query->where(function($q) use ($buscar) {
                $q->where('client_name',  'like', "%{$buscar}%")
                  ->orWhere('client_phone','like', "%{$buscar}%")
                  ->orWhere('client_email','like', "%{$buscar}%");
            });
        }

        $orders = $query->orderByDesc('woo_created_at')->get()->map(function($o) {
            $o->line_items = json_decode($o->line_items ?? '[]', true);
            return $o;
        });

        // KPIs
        $allQuery = DB::table('woo_orders')->where('project_id', $project->id)
            ->where('woo_created_at', '>=', Carbon::createFromFormat('Y-m-d', $desde, $tz)->startOfDay()->utc())
            ->where('woo_created_at', '<=', Carbon::createFromFormat('Y-m-d', $hasta, $tz)->endOfDay()->utc());

        $totalVentas   = (clone $allQuery)->whereIn('status', ['completed','processing'])->sum('total');
        $totalPedidos  = (clone $allQuery)->count();
        $pendientes    = (clone $allQuery)->whereIn('status', ['pending','on-hold'])->count();
        $completados   = (clone $allQuery)->where('status', 'completed')->count();

        $cnts = DB::table('woo_orders')->where('project_id', $project->id)
            ->where('woo_created_at', '>=', Carbon::createFromFormat('Y-m-d', $desde, $tz)->startOfDay()->utc())
            ->where('woo_created_at', '<=', Carbon::createFromFormat('Y-m-d', $hasta, $tz)->endOfDay()->utc())
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        return view('ventas::comercial.woo-orders', compact(
            'project', 'orders', 'desde', 'hasta', 'buscar', 'status',
            'totalVentas', 'totalPedidos', 'pendientes', 'completados', 'cnts'
        ));
    }

    // ── Sincronización manual ────────────────────────────────────────────────
    public function stats(Request $request)
    {
        $pid = session('comercial_project_id', self::PROJECT_ID);
        return response()->json([
            'total'      => DB::table('woo_orders')->where('project_id', $pid)->count(),
            'last_sync'  => DB::table('woo_orders')->where('project_id', $pid)->max('updated_at'),
        ]);
    }
}

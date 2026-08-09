<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Support\Collection;

/**
 * "Puerta única" a toda la información de un proyecto BIXO.
 *
 * Es la base sobre la que trabajan el bot, la IA, el Copilot y el motor de
 * flujos: cualquier bloque que necesite un dato del negocio lo pide aquí.
 * Centralizar la lectura significa que una sola clase conoce el esquema; el
 * resto solo consume métodos con nombre de negocio (buscarProducto, cliente…).
 */
class ProjectContext
{
    public function __construct(private Project $project) {}

    public static function for(Project $project): self
    {
        return new self($project);
    }

    // ── CATÁLOGO Y PRECIOS ────────────────────────────────────────────
    /** Busca productos por nombre/sku (para "¿tienen X?", "¿precio de X?"). */
    public function buscarProducto(string $q, int $limit = 8): Collection
    {
        // Productos
        $productos = Product::where('project_id', $this->project->id)
            ->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('sku', 'like', "%$q%"))
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'price', 'stock', 'description'])
            ->map(fn ($p) => [
                'id' => $p->id, 'tipo' => 'producto', 'nombre' => $p->name, 'sku' => $p->sku,
                'precio' => (float) $p->price, 'stock' => $p->stock,
                'descripcion' => $p->description,
            ]);

        // Servicios (sin stock; se identifican con tipo 'servicio')
        $servicios = Service::where('project_id', $this->project->id)
            ->where('name', 'like', "%$q%")
            ->limit($limit)
            ->get(['id', 'name', 'price', 'description'])
            ->map(fn ($s) => [
                'id' => 'srv_' . $s->id, 'tipo' => 'servicio', 'nombre' => $s->name, 'sku' => null,
                'precio' => (float) $s->price, 'stock' => null,
                'descripcion' => $s->description,
            ]);

        return $productos->concat($servicios)->take($limit);
    }

    /**
     * BÚSQUEDA TOLERANTE (sin IA): normaliza (minúsculas, sin tildes, sin símbolos),
     * tolera plurales, palabras parciales y errores de escritura pequeños.
     * Puntúa cada producto por coincidencia y devuelve los mejores.
     * Ej: "chia", "china", "semilla chia", "chía" → encuentran "Semillas de chía".
     */
    public function buscarTolerante(string $q, int $limit = 8): Collection
    {
        $normal = $this->normalizar($q);
        $palabras = array_filter(explode(' ', $normal), fn ($w) => mb_strlen($w) >= 2);
        if (empty($palabras)) return collect();

        // Traemos el catálogo (nombre + sku + categoría) y puntuamos en PHP.
        $productos = Product::where('project_id', $this->project->id)
            ->with('category:id,name')
            ->get(['id', 'name', 'sku', 'price', 'stock', 'description', 'category_id']);

        $scored = $productos->map(function ($p) use ($palabras, $normal) {
            $nombreN = $this->normalizar($p->name);
            $catN    = $this->normalizar(optional($p->category)->name ?? '');
            $skuN    = $this->normalizar($p->sku ?? '');
            $heno    = $nombreN . ' ' . $catN . ' ' . $skuN;

            $score = 0;
            // Coincidencia exacta de toda la frase
            if ($nombreN === $normal) $score += 100;
            elseif (str_contains($nombreN, $normal)) $score += 60;

            foreach ($palabras as $w) {
                $wsing = $this->singular($w);
                if (str_contains($heno, $w) || str_contains($heno, $wsing)) {
                    $score += 20;                       // palabra contenida
                } else {
                    // Error de escritura: comparar contra cada palabra del nombre
                    foreach (explode(' ', $heno) as $token) {
                        if (mb_strlen($token) < 3) continue;
                        $d = levenshtein($w, $token);
                        if ($d <= 1) { $score += 15; break; }
                        if ($d == 2 && mb_strlen($w) >= 5) { $score += 8; break; }
                    }
                }
            }
            return ['p' => $p, 'score' => $score];
        })->filter(fn ($x) => $x['score'] > 0)
          ->sortByDesc('score')
          ->take($limit)
          ->map(fn ($x) => [
              'id' => $x['p']->id, 'tipo' => 'producto', 'nombre' => $x['p']->name,
              'sku' => $x['p']->sku, 'precio' => (float) $x['p']->price,
              'stock' => $x['p']->stock, 'descripcion' => $x['p']->description,
          ])->values();

        return $scored;
    }

    /** Categorías con productos (para navegar catálogos grandes). */
    public function categoriasConProductos(): Collection
    {
        return \App\Models\Category::where('project_id', $this->project->id)
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn ($c) => $c->products_count > 0)
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->name, 'total' => $c->products_count])
            ->values();
    }

    /** Productos de una categoría (para navegar por categoría). */
    public function productosDeCategoria(int $categoryId, int $limit = 30): Collection
    {
        return Product::where('project_id', $this->project->id)
            ->where('category_id', $categoryId)
            ->orderBy('name')->limit($limit)
            ->get(['id', 'name', 'price', 'stock'])
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'precio' => (float) $p->price, 'stock' => $p->stock])
            ->values();
    }

    /** Un producto por id (para el carrito). */
    public function producto(int $id): ?array
    {
        $p = Product::where('project_id', $this->project->id)->find($id);
        if (!$p) return null;
        return ['id' => $p->id, 'nombre' => $p->name, 'precio' => (float) $p->price, 'stock' => $p->stock];
    }

    /**
     * Datos de pago digital del negocio (Yape/Plin) para que el bot los envíe:
     * número, titular y la URL del QR si está configurado.
     */
    public function datosPago(): array
    {
        $s = $this->project->settings()->pluck('value', 'key');
        $qr = $s['payment_yape_qr'] ?? null;
        if ($qr && !str_starts_with($qr, 'http')) $qr = asset('storage/' . ltrim($qr, '/'));
        return [
            'yape_numero'  => $s['payment_yape_number'] ?? null,
            'yape_nombre'  => $s['payment_yape_name'] ?? null,
            'plin_numero'  => $s['payment_plin_number'] ?? null,
            'qr_url'       => $qr,
            'instrucciones'=> $s['payment_manual_instructions'] ?? null,
        ];
    }

    /** Normaliza texto: minúsculas, sin tildes, sin símbolos, espacios simples. */
    private function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /** Singular simple (quita 's'/'es' final) para tolerar plurales. */
    private function singular(string $w): string
    {
        if (mb_strlen($w) > 4 && str_ends_with($w, 'es')) return mb_substr($w, 0, -2);
        if (mb_strlen($w) > 3 && str_ends_with($w, 's'))  return mb_substr($w, 0, -1);
        return $w;
    }

    /** Lista corta del catálogo (para "¿qué venden?"). */
    public function catalogo(int $limit = 30): Collection
    {
        $productos = Product::where('project_id', $this->project->id)
            ->limit($limit)->get(['name', 'price'])
            ->map(fn ($p) => ['tipo' => 'producto', 'nombre' => $p->name, 'precio' => (float) $p->price]);
        $servicios = Service::where('project_id', $this->project->id)
            ->where('is_available', true)->limit($limit)->get(['name', 'price'])
            ->map(fn ($s) => ['tipo' => 'servicio', 'nombre' => $s->name, 'precio' => (float) $s->price]);
        return $productos->concat($servicios);
    }

    /** Texto plano del catálogo, para inyectar como contexto a la IA. */
    public function catalogoTexto(int $limit = 40): string
    {
        return $this->catalogo($limit)
            ->map(fn ($i) => "- {$i['nombre']} (S/ " . number_format($i['precio'], 2) . ")")
            ->implode("\n");
    }

    // ── CLIENTE E HISTORIAL ───────────────────────────────────────────
    /** Ficha del cliente por teléfono (quién es, su lead, su historial). */
    public function cliente(string $telefono): ?array
    {
        $c = Client::allProjects()->where('project_id', $this->project->id)
            ->where('phone', $telefono)->first();
        if (!$c) return null;

        return [
            'id' => $c->id, 'nombre' => $c->name, 'empresa' => $c->empresa,
            'telefono' => $c->phone, 'etapa' => $c->etapa,
            'lead_temp' => $c->lead_temp, 'lead_score' => $c->lead_score,
            'producto_interes' => $c->producto_interes,
            'ultima_actividad' => $c->ultima_actividad,
            'pedidos' => $this->pedidosDe($c->phone, 3),
        ];
    }

    // ── PEDIDOS Y ESTADO ──────────────────────────────────────────────
    /** Últimos pedidos de un teléfono (para "¿dónde está mi pedido?"). */
    public function pedidosDe(string $telefono, int $limit = 5): Collection
    {
        return Order::where('project_id', $this->project->id)
            ->where('client_phone', $telefono)
            ->latest()->limit($limit)
            ->get(['id', 'status', 'total', 'created_at'])
            ->map(fn ($o) => [
                'id' => $o->id, 'estado' => $o->status,
                'total' => (float) $o->total, 'fecha' => $o->created_at?->toDateString(),
            ]);
    }

    /** Estado de un pedido concreto por id. */
    public function pedido(int $id): ?array
    {
        $o = Order::where('project_id', $this->project->id)->find($id);
        if (!$o) return null;
        return ['id' => $o->id, 'estado' => $o->status, 'total' => (float) $o->total,
                'cliente' => $o->client_name, 'fecha' => $o->created_at?->toDateString()];
    }

    // ── BASE DE CONOCIMIENTO ──────────────────────────────────────────
    /**
     * Info del negocio para que la IA "hable con la empresa": nombre, notas
     * del proyecto, horarios/políticas guardadas en settings. MVP: lo básico
     * del proyecto; luego se amplía con documentos indexados.
     */
    public function conocimiento(): array
    {
        return [
            'negocio' => $this->project->name,
            'rubro' => $this->project->category ?? null,
            'descripcion' => $this->project->description ?? null,
        ];
    }

    // ── KPIs / DATOS DE NEGOCIO (Copilot Empresarial) ─────────────────
    /** Ventas de un período: hoy | mes | año. Devuelve total y cantidad. */
    public function ventas(string $periodo = 'mes'): array
    {
        $q = Order::where('project_id', $this->project->id)
            ->whereNotIn('status', ['anulado', 'cancelado']);
        match ($periodo) {
            'hoy' => $q->whereDate('created_at', today()),
            'año', 'anio', 'year' => $q->whereYear('created_at', now()->year),
            default => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
        };
        return [
            'periodo' => $periodo,
            'cantidad' => (clone $q)->count(),
            'total' => (float) (clone $q)->sum('total'),
        ];
    }

    /** Productos por agotarse (stock por debajo del umbral). */
    public function stockBajo(int $umbral = 5): Collection
    {
        return Product::where('project_id', $this->project->id)
            ->whereNotNull('stock')->where('stock', '<=', $umbral)
            ->orderBy('stock')
            ->get(['name', 'stock', 'price'])
            ->map(fn ($p) => ['nombre' => $p->name, 'stock' => $p->stock, 'precio' => (float) $p->price]);
    }

    /** Resumen numérico del negocio (para "dame un resumen"). */
    public function resumen(): array
    {
        return [
            'clientes' => Client::allProjects()->where('project_id', $this->project->id)->count(),
            'productos' => Product::where('project_id', $this->project->id)->count(),
            'ventas_mes' => $this->ventas('mes'),
            'stock_bajo' => $this->stockBajo()->count(),
            'leads_calientes' => Client::allProjects()->where('project_id', $this->project->id)->where('lead_temp', 'caliente')->count(),
        ];
    }

    /** Un "brief" de todo el contexto, listo para pasar a la IA como system. */
    public function briefParaIa(?string $telefono = null): string
    {
        $k = $this->conocimiento();
        $partes = ["Negocio: {$k['negocio']}"];
        if ($k['rubro']) $partes[] = "Rubro: {$k['rubro']}";
        $partes[] = "Catálogo (parcial):\n" . $this->catalogoTexto(20);
        if ($telefono && ($c = $this->cliente($telefono))) {
            $partes[] = "Cliente: {$c['nombre']} · lead {$c['lead_temp']} ({$c['lead_score']}%)";
        }
        return implode("\n\n", $partes);
    }

    /**
     * Contexto de DATOS para el Copilot Empresarial: KPIs reales del negocio
     * inyectados como texto para que la IA responda preguntas de gestión.
     */
    public function briefDatos(): string
    {
        $r = $this->resumen();
        $vm = $r['ventas_mes'];
        $bajo = $this->stockBajo();
        $partes = [
            "Negocio: {$this->project->name}" . ($this->project->category ? " (rubro: {$this->project->category})" : ''),
            "Ventas del mes: {$vm['cantidad']} ventas, total S/ " . number_format($vm['total'], 2),
            "Clientes registrados: {$r['clientes']} · Leads calientes: {$r['leads_calientes']}",
            "Productos en catálogo: {$r['productos']}",
        ];
        if ($bajo->isNotEmpty()) {
            $partes[] = "Productos por agotarse (stock bajo):\n" .
                $bajo->map(fn ($p) => "- {$p['nombre']}: {$p['stock']} unidades")->implode("\n");
        } else {
            $partes[] = "Ningún producto con stock bajo.";
        }
        return implode("\n\n", $partes);
    }
}

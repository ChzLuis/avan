<?php

namespace App\Http\Controllers;

use App\Modules\Tienda\Controllers\TiendaPublicaController;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\ResellerPrice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Panel del REVENDEDOR: gestiona SUS precios (sin tocar el precio principal) y
 * arma su catálogo simple para compartir por un enlace público.
 */
class ResellerController extends Controller
{
    /** Pantalla "Mis productos y precios": catálogo del negocio con el precio del revendedor. */
    public function misPrecios()
    {
        /** @var Project $project */
        $project = app('active_project');
        $user    = auth()->user();

        $mios = ResellerPrice::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->get()->keyBy('product_id');

        $productos = $project->products()
            ->with(['images' => fn ($q) => $q->where('is_main', true)])
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($mios) {
                $mio = $mios->get($p->id);
                return [
                    'id'         => $p->id,
                    'name'       => $p->name,
                    'base'       => (float) $p->price,   // precio del catálogo principal (del negocio)
                    'suggested'  => (float) ($p->price_suggested ?? $p->price),
                    'min'        => $p->price_min !== null ? (float) $p->price_min : null,
                    'max'        => $p->price_max !== null ? (float) $p->price_max : null,
                    'cost'       => $p->cost !== null ? (float) $p->cost : null,
                    'my_price'   => $mio ? (float) $mio->price : (float) ($p->price_suggested ?? $p->price),
                    'in_catalog' => $mio ? (bool) $mio->in_catalog : true,
                    'image'      => $p->images->first() ? $this->img($p->images->first()->url) : null,
                    'category_id'=> $p->category_id,
                ];
            })->values();

        $enlace = url('/r/' . $this->slugRevendedor($project, $user));

        // Rutas correctas según el contexto (portal comercial 'bixosales.*' vs principal).
        $prefix = str_starts_with(request()->route()->getName() ?? '', 'bixosales.') ? 'bixosales.' : '';
        $rutaGuardar = route($prefix . 'reseller.precio.guardar');
        $rutaToggle  = route($prefix . 'reseller.catalogo.toggle');
        $rutaPos     = $prefix ? route('bixosales.pos') : route('pos.index');
        $portalLayout = $prefix ? 'comercial' : 'panel';

        return view('reseller.precios', compact('project', 'productos', 'enlace', 'rutaGuardar', 'rutaToggle', 'rutaPos', 'portalLayout'));
    }

    /** Guardar el precio propio del revendedor para un producto (validando min/max). */
    public function guardarPrecio(Request $r): JsonResponse
    {
        /** @var Project $project */
        $project = app('active_project');
        $user    = auth()->user();
        $data = $r->validate([
            'product_id' => 'required|integer',
            'price'      => 'required|numeric|min:0',
        ]);

        $product = Product::where('project_id', $project->id)->findOrFail($data['product_id']);

        // Respetar los límites que puso el admin
        if ($product->price_min !== null && $data['price'] < (float) $product->price_min - 0.001) {
            return response()->json(['ok' => false, 'error' => 'No puedes poner un precio menor a S/ ' . number_format($product->price_min, 2)], 422);
        }
        if ($product->price_max !== null && $data['price'] > (float) $product->price_max + 0.001) {
            return response()->json(['ok' => false, 'error' => 'No puedes poner un precio mayor a S/ ' . number_format($product->price_max, 2)], 422);
        }

        $rp = ResellerPrice::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $user->id, 'product_id' => $product->id],
            ['price' => $data['price']]
        );

        return response()->json(['ok' => true, 'price' => (float) $rp->price]);
    }

    /** Incluir/quitar un producto del catálogo compartible del revendedor. */
    public function toggleCatalogo(Request $r): JsonResponse
    {
        /** @var Project $project */
        $project = app('active_project');
        $user    = auth()->user();
        $data = $r->validate(['product_id' => 'required|integer', 'in_catalog' => 'required|boolean']);

        $product = Product::where('project_id', $project->id)->findOrFail($data['product_id']);

        $rp = ResellerPrice::firstOrNew([
            'project_id' => $project->id, 'user_id' => $user->id, 'product_id' => $product->id,
        ]);
        if (!$rp->exists) $rp->price = $product->price_suggested ?? $product->price;
        $rp->in_catalog = $data['in_catalog'];
        $rp->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Catálogo público del revendedor: /r/{slug}
     * El cliente ve los productos incluidos con el precio del revendedor y pide por WhatsApp.
     */
    public function catalogoPublico(string $slug)
    {
        [$project, $user] = $this->resolverSlug($slug);
        abort_if(!$project || !$user, 404);

        // Precios propios del revendedor (solo los que marcó "en mi catálogo").
        $precios = ResellerPrice::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('in_catalog', true)
            ->get()->keyBy('product_id');

        // Reutilizamos EXACTAMENTE la tienda pública (plantilla con categorías,
        // buscador, diseño del negocio) y solo cambiamos: precios del revendedor
        // y qué productos se muestran.
        [$view, $data] = app(TiendaPublicaController::class)->prepararCatalogo($project);

        $idsPermitidos = $precios->keys()->all();
        $aplicar = function ($coleccion) use ($precios, $idsPermitidos) {
            return $coleccion->filter(fn ($p) => in_array($p->id, $idsPermitidos, true))
                ->each(function ($p) use ($precios) {
                    if (isset($precios[$p->id])) {
                        $p->price = (float) $precios[$p->id]->price;   // SU precio
                        $p->compare_price = null;                      // sin "antes" del negocio
                    }
                })->values();
        };

        // Filtrar y re-precificar en categorías y secciones destacadas.
        foreach ($data['categories'] as $cat) {
            if ($cat->relationLoaded('products')) $cat->setRelation('products', $aplicar($cat->products));
            if ($cat->relationLoaded('children')) {
                foreach ($cat->children as $hijo) {
                    if ($hijo->relationLoaded('products')) $hijo->setRelation('products', $aplicar($hijo->products));
                }
            }
        }
        // Quitar categorías que quedaron sin productos
        $data['categories'] = $data['categories']->filter(fn ($c) => $c->products->isNotEmpty()
            || ($c->relationLoaded('children') && $c->children->contains(fn ($h) => $h->products->isNotEmpty())))->values();

        foreach (['newArrivals', 'onSale', 'featured'] as $k) {
            if (isset($data[$k])) $data[$k] = $aplicar($data[$k]);
        }

        // Datos del revendedor para el pedido por WhatsApp.
        $telefono = \App\Models\Employee::where('project_id', $project->id)
            ->where('user_id', $user->id)->value('phone');
        $waRev = preg_replace('/\D/', '', (string) $telefono);

        $data['revendedor'] = ['nombre' => $user->name, 'telefono' => $waRev];

        // CLAVE: los pedidos del carrito deben ir al WhatsApp DEL REVENDEDOR,
        // no al del negocio. Sobrescribimos los settings que usan las plantillas.
        if ($waRev !== '') {
            $s = $data['settings'];
            $s['quote_whatsapp'] = $waRev;
            $s['quote_whatsapp_country'] = '';   // el número ya viene completo
            $s['whatsapp_msg'] = 'Hola ' . $user->name . ', quiero hacer un pedido:';
            $data['settings'] = $s;

            // El proyecto que ve la plantilla usa el WhatsApp del revendedor.
            $proj = clone $project;
            $proj->whatsapp = $waRev;
            $data['project'] = $proj;
        }

        return view($view, $data);
    }

    // ── helpers ──────────────────────────────────────────────
    private function img(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : asset('storage/' . $url);
    }

    /** slug del catálogo: {slug-proyecto}-{id-usuario} para que sea único y estable. */
    private function slugRevendedor(Project $project, User $user): string
    {
        return $project->slug . '-' . $user->id;
    }

    private function resolverSlug(string $slug): array
    {
        // El slug termina en -{userId}
        if (!preg_match('/^(.+)-(\d+)$/', $slug, $m)) return [null, null];
        $project = Project::where('slug', $m[1])->first();
        $user    = User::find((int) $m[2]);
        return [$project, $user];
    }
}

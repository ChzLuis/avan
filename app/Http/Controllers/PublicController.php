<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Coupon;
use App\Models\Review;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    private function project(string $slug): Project
    {
        return Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    public function catalog(string $slug)
    {
        $project    = $this->project($slug);
        $settings   = $project->settings()->pluck('value', 'key');
        $categories = $project->categories()->where('is_active', true)
            ->with(['products' => fn($q) => $q->where('is_available', true)->with('mainImage')->orderBy('sort_order')])
            ->orderBy('sort_order')->get();

        // Productos para secciones de la tienda
        $newArrivals = $project->products()->where('is_available', true)
            ->with(['mainImage','category'])->latest()->take(8)->get();
        $onSale = $project->products()->where('is_available', true)
            ->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')
            ->with(['mainImage','category'])->take(8)->get();
        $featured = $project->products()->where('is_available', true)
            ->with(['mainImage','category'])->inRandomOrder()->take(8)->get();

        $productRatings = \App\Models\Review::where('project_id', $project->id)
            ->where('is_approved', true)
            ->select('product_id', \DB::raw('ROUND(AVG(rating),1) as avg_rating'), \DB::raw('COUNT(*) as rating_count'))
            ->groupBy('product_id')
            ->get()->keyBy('product_id');

        // Detectar plantilla activa y cargar vista correspondiente
        $template  = $settings['catalog_template'] ?? 'default';
        $tplViews  = [
            'default'    => 'public.catalog',
            'ella'       => 'public.templates.ella',
            'editorial'  => 'public.templates.editorial',
            'nordic'     => 'public.templates.nordic',
            'luxe'       => 'public.templates.luxe',
            'flash'      => 'public.templates.flash',
            'bistro'     => 'public.templates.bistro',
            'urban'      => 'public.templates.urban',
            'boutique'   => 'public.templates.boutique',
            'fresh'      => 'public.templates.fresh',
            'porto'      => 'public.templates.porto',
        ];
        $view = isset($tplViews[$template]) && view()->exists($tplViews[$template])
            ? $tplViews[$template]
            : 'public.catalog';

        return view($view, compact('project','categories','settings','newArrivals','onSale','featured','productRatings'));
    }

    public function validateCoupon(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $code    = strtoupper(trim($request->input('code', '')));
        $subtotal = (float) $request->input('subtotal', 0);

        $coupon = $project->coupons()->where('code', $code)->first();
        if (!$coupon || !$coupon->isValid()) {
            return response()->json(['ok' => false, 'message' => 'Cupón inválido o expirado.']);
        }
        if ($subtotal < (float) $coupon->min_order && $coupon->min_order > 0) {
            return response()->json(['ok' => false, 'message' => 'Monto mínimo para este cupón: '.number_format($coupon->min_order, 2)]);
        }
        $discount = $coupon->calculateDiscount($subtotal);
        return response()->json([
            'ok'        => true,
            'code'      => $coupon->code,
            'type'      => $coupon->type,
            'value'     => (float) $coupon->value,
            'min_order' => (float) $coupon->min_order,
            'discount'  => $discount,
        ]);
    }

    public function storeOrder(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'client_name'      => 'required|string|max:100',
            'client_phone'     => 'required|string|max:30',
            'client_email'     => 'nullable|email|max:150',
            'notes'            => 'nullable|string',
            'coupon_code'      => 'nullable|string|max:50',
            'delivery_address' => 'nullable|string|max:255',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'items'            => 'required|array|min:1',
        ]);
        $subtotal = collect($data['items'])->sum(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1));
        $shipping = (float) ($data['shipping_cost'] ?? 0);
        $discount = 0.0;
        $couponCode = null;

        if (!empty($data['coupon_code'])) {
            $code   = strtoupper(trim($data['coupon_code']));
            $coupon = $project->coupons()->where('code', $code)->first();
            if ($coupon && $coupon->isValid()) {
                $discount   = $coupon->calculateDiscount($subtotal);
                $couponCode = $coupon->code;
                $coupon->increment('uses_count');
            }
        }

        $total = max(0, $subtotal - $discount + $shipping);
        $order = $project->orders()->create([
            'client_name'      => $data['client_name'],
            'client_phone'     => $data['client_phone'],
            'client_email'     => $data['client_email'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'coupon_code'      => $couponCode,
            'discount'         => $discount > 0 ? $discount : null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'shipping_cost'    => $shipping > 0 ? $shipping : null,
            'total'            => $total,
            'status'           => 'pending',
        ]);
        foreach ($data['items'] as $item) {
            $order->items()->create($item);
        }
        return response()->json(['ok' => true, 'order_id' => $order->id, 'total' => (float) $order->total]);
    }

    public function storeQuote(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:30',
            'notes'        => 'nullable|string',
        ]);
        $project->quotes()->create([
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'notes'        => $data['notes'] ?? null,
            'total'        => 0,
            'status'       => 'draft',
        ]);
        return response()->json(['ok' => true]);
    }

    public function product(string $slug, int $id)
    {
        $project = $this->project($slug);
        $product = $project->products()
            ->where('is_available', true)
            ->with(['images', 'category', 'mainImage'])
            ->findOrFail($id);
        $settings   = $project->settings()->pluck('value', 'key');
        $related    = $project->products()
            ->where('is_available', true)
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('mainImage')
            ->take(6)->get();
        $categories = $project->categories()->where('is_active', true)
            ->with(['products' => fn($q) => $q->where('is_available', true)->with('mainImage')])
            ->orderBy('sort_order')->get();
        $reviews    = $product->approvedReviews()->get();
        $avgRating  = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
        return view('public.product', compact('project', 'product', 'settings', 'related', 'categories', 'reviews', 'avgRating'));
    }

    public function storeReview(Request $request, string $slug, int $productId)
    {
        $project = $this->project($slug);
        $product = $project->products()->where('is_available', true)->findOrFail($productId);

        $data = $request->validate([
            'author_name'  => 'required|string|max:100',
            'author_email' => 'nullable|email|max:150',
            'rating'       => 'required|integer|min:1|max:5',
            'comment'      => 'nullable|string|max:1000',
        ]);

        // Simple rate limiting: 1 review per IP per product per hour
        $recent = Review::where('product_id', $product->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recent >= 3) {
            return response()->json(['ok' => false, 'message' => 'Demasiadas reseñas. Inténtalo más tarde.'], 429);
        }

        $review = Review::create([
            'project_id'   => $project->id,
            'product_id'   => $product->id,
            'author_name'  => $data['author_name'],
            'author_email' => $data['author_email'] ?? null,
            'rating'       => $data['rating'],
            'comment'      => $data['comment'] ?? null,
            'is_approved'  => false,
        ]);

        return response()->json(['ok' => true, 'message' => 'Reseña enviada. Aparecerá tras ser aprobada.']);
    }

    public function thankyou(string $slug, int $orderId)
    {
        $project  = $this->project($slug);
        $order    = $project->orders()->with('items')->findOrFail($orderId);
        $settings = $project->settings()->pluck('value', 'key');
        return view('public.thankyou', compact('project', 'order', 'settings'));
    }

    public function book(string $slug)
    {
        $project  = $this->project($slug);
        $services = $project->services()->where('is_available', true)->get();
        return view('public.book', compact('project', 'services'));
    }

    public function storeBook(Request $request, string $slug)
    {
        $project = $this->project($slug);
        $data = $request->validate([
            'service_id'   => 'required|integer',
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:30',
            'date'         => 'required|date|after_or_equal:today',
            'start_time'   => 'required',
        ]);
        $service = $project->services()->findOrFail($data['service_id']);
        $endTs   = strtotime($data['start_time']) + ($service->duration_min * 60);
        $project->appointments()->create([
            'service_id'   => $service->id,
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'date'         => $data['date'],
            'start_time'   => $data['start_time'],
            'end_time'     => date('H:i', $endTs),
            'status'       => 'pending',
        ]);
        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Tienda\Storefront\ProductVariantMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function show(Product $product, ProductVariantMatrixService $service): JsonResponse
    {
        return response()->json($service->read(app('active_project'), $product));
    }

    public function update(Request $request, Product $product, ProductVariantMatrixService $service): JsonResponse
    {
        $data = $request->validate([
            'attributes' => ['present', 'array', 'max:'.ProductVariantMatrixService::MAX_ATTRIBUTES],
            'attributes.*.id' => ['nullable', 'integer'],
            'attributes.*.key' => ['required', 'string', 'max:80'],
            'attributes.*.name' => ['required', 'string', 'max:80'],
            'attributes.*.type' => ['nullable', 'in:select,color,button'],
            'attributes.*.is_variant' => ['nullable', 'boolean'],
            'attributes.*.is_filterable' => ['nullable', 'boolean'],
            'attributes.*.values' => ['required', 'array', 'min:1', 'max:'.ProductVariantMatrixService::MAX_VALUES_PER_ATTRIBUTE],
            'attributes.*.values.*.id' => ['nullable', 'integer'],
            'attributes.*.values.*.key' => ['required', 'string', 'max:100'],
            'attributes.*.values.*.label' => ['required', 'string', 'max:100'],
            'attributes.*.values.*.color_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'variants' => ['present', 'array', 'max:'.ProductVariantMatrixService::MAX_VARIANTS],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.value_keys' => ['required', 'array', 'min:1'],
            'variants.*.value_keys.*' => ['required', 'string', 'max:100'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.compare_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.product_image_id' => ['nullable', 'integer'],
        ]);

        return response()->json(['ok' => true] + $service->save(app('active_project'), $product, $data));
    }
}

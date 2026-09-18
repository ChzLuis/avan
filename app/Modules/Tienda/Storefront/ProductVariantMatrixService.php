<?php

namespace App\Modules\Tienda\Storefront;

use App\Modules\Catalogo\Models\Product;
use App\Modules\Catalogo\Models\ProductAttribute;
use App\Modules\Catalogo\Models\ProductAttributeValue;
use App\Modules\Catalogo\Models\ProductVariant;
use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductVariantMatrixService
{
    public const MAX_ATTRIBUTES = 3;
    public const MAX_VALUES_PER_ATTRIBUTE = 30;
    public const MAX_VARIANTS = 100;

    public function read(Project $project, Product $product): array
    {
        $this->guardProduct($project, $product);

        $product->load([
            'images',
            'variants.values.attribute',
            'variants.image',
            'attributeValues.attribute',
        ]);

        $configuredIds = $product->attributeValues->pluck('product_attribute_id')
            ->merge($product->variants->flatMap(fn (ProductVariant $variant) => $variant->values->pluck('product_attribute_id')))
            ->unique()->values();

        $productValueIds = $product->attributeValues->pluck('id')
            ->merge($product->variants->flatMap(fn (ProductVariant $variant) => $variant->values->pluck('id')))
            ->unique()->values();

        $configured = ProductAttribute::allProjects()
            ->where('project_id', $project->id)
            ->whereIn('id', $configuredIds)
            ->with(['values' => fn ($query) => $query->where('is_active', true)->whereIn('id', $productValueIds)])
            ->orderBy('sort_order')->orderBy('name')->get();

        $library = ProductAttribute::allProjects()
            ->where('project_id', $project->id)->where('is_active', true)
            ->with(['values' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')->orderBy('name')->get();

        return [
            'attributes' => $configured->map(fn (ProductAttribute $attribute) => $this->attributeData($attribute))->values(),
            'attribute_library' => $library->map(fn (ProductAttribute $attribute) => $this->attributeData($attribute))->values(),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => $this->variantData($variant))->values(),
            'limits' => [
                'attributes' => self::MAX_ATTRIBUTES,
                'values_per_attribute' => self::MAX_VALUES_PER_ATTRIBUTE,
                'variants' => self::MAX_VARIANTS,
            ],
        ];
    }

    public function save(Project $project, Product $product, array $payload): array
    {
        $this->guardProduct($project, $product);

        $attributes = array_values(Arr::wrap($payload['attributes'] ?? []));
        $variants = array_values(Arr::wrap($payload['variants'] ?? []));

        if (count($attributes) > self::MAX_ATTRIBUTES) {
            throw ValidationException::withMessages(['attributes' => 'Puedes combinar como máximo '.self::MAX_ATTRIBUTES.' atributos por producto.']);
        }
        if (count($variants) > self::MAX_VARIANTS) {
            throw ValidationException::withMessages(['variants' => 'La matriz admite como máximo '.self::MAX_VARIANTS.' combinaciones.']);
        }

        DB::transaction(function () use ($project, $product, $attributes, $variants) {
            $attributeMap = [];
            $valueMap = [];
            $valueAttribute = [];

            foreach ($attributes as $attributeIndex => $input) {
                $name = trim((string) ($input['name'] ?? ''));
                if ($name === '') {
                    throw ValidationException::withMessages(["attributes.{$attributeIndex}.name" => 'Escribe el nombre del atributo.']);
                }

                $clientKey = (string) ($input['key'] ?? $input['id'] ?? "attribute-{$attributeIndex}");
                $slug = Str::slug($name) ?: 'atributo-'.$attributeIndex;
                $type = in_array($input['type'] ?? 'select', ['select', 'color', 'button'], true)
                    ? $input['type'] : 'select';

                $attribute = ! empty($input['id'])
                    ? ProductAttribute::allProjects()->where('project_id', $project->id)->findOrFail((int) $input['id'])
                    : ProductAttribute::allProjects()->firstOrNew(['project_id' => $project->id, 'slug' => $slug]);

                $attribute->fill([
                    'project_id' => $project->id,
                    'name' => $name,
                    'slug' => $attribute->exists ? $attribute->slug : $slug,
                    'type' => $type,
                    'is_variant' => (bool) ($input['is_variant'] ?? true),
                    'is_filterable' => (bool) ($input['is_filterable'] ?? true),
                    'is_active' => true,
                    'sort_order' => $attributeIndex,
                ])->save();

                $attributeMap[$clientKey] = $attribute;
                $values = array_values(Arr::wrap($input['values'] ?? []));
                if (count($values) > self::MAX_VALUES_PER_ATTRIBUTE) {
                    throw ValidationException::withMessages(["attributes.{$attributeIndex}.values" => 'Demasiados valores para '.$name.'.']);
                }

                foreach ($values as $valueIndex => $valueInput) {
                    $label = trim((string) ($valueInput['label'] ?? ''));
                    if ($label === '') continue;

                    $valueClientKey = (string) ($valueInput['key'] ?? $valueInput['id'] ?? "{$clientKey}-value-{$valueIndex}");
                    $valueSlug = Str::slug($label) ?: mb_strtolower($label);
                    $value = ! empty($valueInput['id'])
                        ? ProductAttributeValue::allProjects()
                            ->where('project_id', $project->id)
                            ->where('product_attribute_id', $attribute->id)
                            ->findOrFail((int) $valueInput['id'])
                        : ProductAttributeValue::allProjects()->firstOrNew([
                            'product_attribute_id' => $attribute->id,
                            'value' => $valueSlug,
                        ]);

                    $color = strtoupper(trim((string) ($valueInput['color_hex'] ?? '')));
                    if ($color !== '' && ! preg_match('/^#[0-9A-F]{6}$/', $color)) {
                        throw ValidationException::withMessages(["attributes.{$attributeIndex}.values.{$valueIndex}.color_hex" => 'El color debe tener formato #RRGGBB.']);
                    }

                    $value->fill([
                        'project_id' => $project->id,
                        'product_attribute_id' => $attribute->id,
                        'label' => $label,
                        'value' => $value->exists ? $value->value : $valueSlug,
                        'color_hex' => $color ?: null,
                        'is_active' => true,
                        'sort_order' => $valueIndex,
                    ])->save();

                    $valueMap[$valueClientKey] = $value;
                    $valueAttribute[$value->id] = $attribute->id;
                }
            }

            $keptVariantIds = [];
            $usedValueIds = [];
            foreach ($variants as $variantIndex => $input) {
                $keys = array_values(array_unique(array_filter(array_map('strval', Arr::wrap($input['value_keys'] ?? [])))));
                $values = collect($keys)->map(function (string $key) use ($valueMap, $variantIndex) {
                    if (! isset($valueMap[$key])) {
                        throw ValidationException::withMessages(["variants.{$variantIndex}.value_keys" => 'La combinación contiene un valor que no pertenece a este producto.']);
                    }
                    return $valueMap[$key];
                });

                if ($values->isEmpty() || $values->pluck('product_attribute_id')->unique()->count() !== count($attributeMap)) {
                    throw ValidationException::withMessages(["variants.{$variantIndex}.value_keys" => 'Cada combinación debe elegir exactamente un valor de cada atributo.']);
                }

                $ids = $values->pluck('id')->sort()->values()->all();
                $signature = implode('-', $ids);
                $variant = ! empty($input['id'])
                    ? ProductVariant::allProjects()->where('project_id', $project->id)->where('product_id', $product->id)->findOrFail((int) $input['id'])
                    : ProductVariant::allProjects()->firstOrNew(['product_id' => $product->id, 'signature' => $signature]);

                $imageId = filled($input['product_image_id'] ?? null) ? (int) $input['product_image_id'] : null;
                if ($imageId && ! $product->images()->whereKey($imageId)->exists()) {
                    throw ValidationException::withMessages(["variants.{$variantIndex}.product_image_id" => 'La imagen no pertenece a este producto.']);
                }

                $sku = trim((string) ($input['sku'] ?? '')) ?: null;
                if ($sku && ProductVariant::allProjects()->where('project_id', $project->id)->where('sku', $sku)
                    ->when($variant->exists, fn ($query) => $query->where('id', '!=', $variant->id))->exists()) {
                    throw ValidationException::withMessages(["variants.{$variantIndex}.sku" => 'El SKU ya está siendo utilizado por otra variante.']);
                }

                $variant->fill([
                    'project_id' => $project->id,
                    'product_id' => $product->id,
                    'product_image_id' => $imageId,
                    'signature' => $signature,
                    'sku' => $sku,
                    'barcode' => trim((string) ($input['barcode'] ?? '')) ?: null,
                    'price' => $this->nullableDecimal($input['price'] ?? null),
                    'compare_price' => $this->nullableDecimal($input['compare_price'] ?? null),
                    'wholesale_price' => $this->nullableDecimal($input['wholesale_price'] ?? null),
                    'stock' => $this->nullableInteger($input['stock'] ?? null),
                    'is_active' => (bool) ($input['is_active'] ?? true),
                    'sort_order' => $variantIndex,
                ])->save();

                $sync = [];
                foreach ($values as $value) {
                    $sync[$value->id] = [
                        'project_id' => $project->id,
                        'product_attribute_id' => $value->product_attribute_id,
                    ];
                    $usedValueIds[] = $value->id;
                }
                $variant->values()->sync($sync);
                $keptVariantIds[] = $variant->id;
            }

            $product->attributeValues()->sync(collect($usedValueIds)->unique()->mapWithKeys(fn ($valueId) => [
                $valueId => [
                    'project_id' => $project->id,
                    'product_attribute_id' => $valueAttribute[$valueId],
                ],
            ])->all());

            $removed = ProductVariant::allProjects()->where('project_id', $project->id)->where('product_id', $product->id)
                ->when($keptVariantIds, fn ($query) => $query->whereNotIn('id', $keptVariantIds));
            foreach ($removed->get() as $variant) {
                if (DB::table('order_items')->where('product_variant_id', $variant->id)->exists()) {
                    $variant->update(['is_active' => false]);
                } else {
                    $variant->delete();
                }
            }
        });

        return $this->read($project, $product->fresh());
    }

    private function guardProduct(Project $project, Product $product): void
    {
        abort_unless((int) $product->project_id === (int) $project->id, 403);
    }

    private function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (! is_numeric($value) || (float) $value < 0) {
            throw ValidationException::withMessages(['variants' => 'Precio de variante inválido.']);
        }
        return number_format((float) $value, 2, '.', '');
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            throw ValidationException::withMessages(['variants' => 'Stock de variante inválido.']);
        }
        return (int) $value;
    }

    private function attributeData(ProductAttribute $attribute): array
    {
        return [
            'id' => $attribute->id,
            'key' => 'attribute-'.$attribute->id,
            'name' => $attribute->name,
            'type' => $attribute->type,
            'is_variant' => $attribute->is_variant,
            'is_filterable' => $attribute->is_filterable,
            'values' => $attribute->values->map(fn (ProductAttributeValue $value) => [
                'id' => $value->id,
                'key' => 'value-'.$value->id,
                'label' => $value->label,
                'color_hex' => $value->color_hex,
            ])->values(),
        ];
    }

    private function variantData(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'price' => $variant->price,
            'compare_price' => $variant->compare_price,
            'wholesale_price' => $variant->wholesale_price,
            'stock' => $variant->stock,
            'is_active' => $variant->is_active,
            'product_image_id' => $variant->product_image_id,
            'label' => $variant->label(),
            'value_keys' => $variant->values->map(fn ($value) => 'value-'.$value->id)->values(),
            'image_url' => $variant->image?->url,
        ];
    }
}

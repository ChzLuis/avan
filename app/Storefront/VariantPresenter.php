<?php

namespace App\Storefront;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Serializacion publica de las variantes de un producto.
 *
 * Existe un solo sitio donde se decide la forma de este dato porque los dos
 * motores (Ecommerce y Directo) y la ficha publica lo consumen con el mismo
 * codigo JavaScript. Antes habia dos copias que devolvian tipos distintos:
 * el catalogo mandaba `attributeId` y `valueId` como enteros y la ficha como
 * cadenas. Funcionaba de casualidad porque el JavaScript comparaba con
 * String(), y cualquier comparacion estricta que se anadiera despues habria
 * fallado solo en uno de los dos motores.
 *
 * Contrato (ver STOREFRONT_TWO_ENGINES_HANDOFF.md):
 * - `attributeId` y `valueId` viajan SIEMPRE como cadena.
 * - Los atributos se ordenan por `sort_order` para que el selector conserve
 *   el orden que definio el negocio.
 * - Un dato vacio en la variante hereda el del producto.
 * - El precio de aqui es informativo para pintar; el servidor lo revalida en
 *   el checkout y nunca acepta el que manda el navegador.
 */
class VariantPresenter
{
    /**
     * @param  Product  $product        Producto con la relacion `variants` cargada.
     * @param  string|null  $fallbackImage  Imagen del producto cuando la variante no trae la suya.
     * @param  float|null  $fallbackComparePrice  Precio comparativo del producto.
     * @param  Collection|null  $imageIndexes  Mapa id-de-imagen => posicion en la galeria.
     *                                         Solo lo usa la ficha para sincronizar el carrusel.
     * @return array<int, array<string, mixed>>
     */
    public static function forProduct(
        Product $product,
        ?string $fallbackImage = null,
        ?float $fallbackComparePrice = null,
        ?Collection $imageIndexes = null,
    ): array {
        // Directo carga `activeVariants` y Ecommerce `variants`: se acepta
        // cualquiera de las dos para que ambos motores lleguen al mismo dato.
        if ($product->relationLoaded('variants')) {
            $variantes = $product->variants;
        } elseif ($product->relationLoaded('activeVariants')) {
            $variantes = $product->activeVariants;
        } else {
            return [];
        }

        return $variantes
            ->where('is_active', true)
            ->map(fn ($variant) => [
                'id' => (string) $variant->id,
                'label' => $variant->label(),
                'sku' => $variant->sku,
                'price' => (float) ($variant->price ?? $product->price),
                'comparePrice' => filled($variant->compare_price)
                    ? (float) $variant->compare_price
                    : $fallbackComparePrice,
                'stock' => $variant->stock ?? $product->stock,
                'image' => self::urlDeImagen($variant->image) ?? $fallbackImage,
                'imageIndex' => $imageIndexes && $variant->product_image_id
                    ? $imageIndexes->get($variant->product_image_id)
                    : null,
                'values' => $variant->values
                    ->sortBy(fn ($value) => $value->attribute?->sort_order ?? 0)
                    ->map(fn ($value) => [
                        'attributeId' => (string) $value->product_attribute_id,
                        'attribute' => $value->attribute?->name,
                        'type' => $value->attribute?->type ?? 'button',
                        'valueId' => (string) $value->id,
                        'label' => $value->label,
                        'color' => $value->color_hex,
                    ])->values()->all(),
            ])->values()->all();
    }

    /** Una imagen guardada puede ser una URL absoluta o una ruta del disco publico. */
    private static function urlDeImagen($image): ?string
    {
        if (! $image || ! $image->url) {
            return null;
        }

        return str_starts_with($image->url, 'http')
            ? $image->url
            : asset('storage/'.$image->url);
    }
}

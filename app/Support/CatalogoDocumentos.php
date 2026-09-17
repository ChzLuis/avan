<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * El catálogo que alimenta el buscador de TODOS los documentos.
 *
 * Comprobantes, guías de remisión y cotizaciones eligen de la misma lista.
 * Antes cada pantalla lo armaba a su manera —y la de guías directamente no lo
 * tenía: había que teclear la descripción a mano, con sus erratas, y la línea
 * salía sin enlace al producto—.
 *
 * Un solo sitio también significa un solo criterio de qué se ofrece: solo lo
 * disponible, y nunca productos de otro negocio.
 */
final class CatalogoDocumentos
{
    /**
     * Productos y servicios del negocio, en la forma que espera el
     * autocompletado del formulario.
     *
     * @return Collection<int, array{key:string, type:string, type_label:string,
     *                               name:string, sku:string, description:string,
     *                               price:float, unit:string}>
     */
    public static function para(Project $project): Collection
    {
        $productos = $project->products()
            ->where('is_available', true)
            // Marca y categoria: el selector del comprobante filtra por ellas.
            ->with(['category:id,name', 'marca:id,label'])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'description', 'price', 'unit', 'category_id', 'brand_catalog_id'])
            ->map(fn ($p) => [
                'key'         => 'product-'.$p->id,
                'type'        => 'product',
                'type_label'  => 'Producto',
                'name'        => $p->name,
                'sku'         => $p->sku ?? '',
                'brand'       => (string) ($p->marca?->label ?? ''),
                'category'    => (string) ($p->category?->name ?? ''),
                'description' => \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags((string) $p->description))), 90),
                'price'       => (float) $p->price,
                // El producto guarda la unidad como la escribio el negocio
                // ("Rollo 100 m"); a la linea del documento llega el codigo
                // SUNAT, que es lo unico que viaja en el XML.
                'unit'        => \App\Support\Sunat\Catalogos::codigoUnidad($p->unit),
            ]);

        $servicios = $project->services()
            ->where('is_available', true)
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'price'])
            ->map(fn ($s) => [
                'key'         => 'service-'.$s->id,
                'type'        => 'service',
                'type_label'  => 'Servicio',
                'name'        => $s->name,
                'sku'         => '',
                'brand'       => '',
                'category'    => 'Servicios',
                'description' => \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags((string) $s->description))), 90),
                'price'       => (float) $s->price,
                // ZZ es el código SUNAT de "servicio": un servicio no se
                // cuenta en unidades.
                'unit'        => 'ZZ',
            ]);

        return $productos->concat($servicios)->values();
    }
}

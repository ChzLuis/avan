<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Project;
use App\Storefront\AgrupadorModelos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Convierte los colores sueltos de un catálogo en un solo producto por modelo.
 *
 * Una tienda de ropa suele cargar cada color como un producto aparte porque el
 * importador viene así, y acaba con seis fichas casi idénticas que hay que
 * editar seis veces. Aquí se quedan en una: los colores pasan a ser una lista
 * dentro del producto y las fotos de todos ellos van a su galería.
 *
 * Es destructivo —borra los productos sobrantes— así que se niega a tocar
 * cualquiera que ya aparezca en un pedido, y trae `--simular` para verlo antes.
 */
class FusionarModelos extends Command
{
    protected $signature = 'productos:fusionar-modelos
        {proyecto : Id del proyecto}
        {--simular : Enseña lo que haría sin escribir nada}';

    protected $description = 'Deja un solo producto por modelo, con sus colores dentro';

    public function handle(AgrupadorModelos $agrupador): int
    {
        $project = Project::find((int) $this->argument('proyecto'));
        if (! $project) {
            $this->error('No existe ese proyecto.');

            return self::FAILURE;
        }

        $simular = (bool) $this->option('simular');
        $grupos = array_filter($agrupador->grupos($project, soloVendibles: false), fn ($hermanos) => count($hermanos) > 1);

        if (! $grupos) {
            $this->info('No hay nada que fusionar: ningún modelo tiene más de un color.');

            return self::SUCCESS;
        }

        $this->line(sprintf('%s: %d modelos con varios colores.', $project->name, count($grupos)));
        $this->newLine();

        $fusionados = $borrados = $saltados = 0;

        foreach ($grupos as $representanteId => $hermanos) {
            $ids = array_map(fn (Product $p) => $p->id, $hermanos);
            $sobrantes = array_values(array_diff($ids, [$representanteId]));

            // Un producto que ya se vendió no se borra: su id vive en el pedido
            // y borrarlo dejaría el historial señalando a la nada.
            $vendidos = DB::table('order_items')->whereIn('product_id', $sobrantes)->distinct()->pluck('product_id')->all();
            if ($vendidos) {
                $this->warn(sprintf('  ! %s: %d de sus colores ya están en pedidos. Se deja como está.',
                    AgrupadorModelos::claveModelo($hermanos[0]), count($vendidos)));
                $saltados++;
                continue;
            }

            $modelo = AgrupadorModelos::claveModelo($hermanos[0]);
            $colores = $this->colores($hermanos);

            $this->line(sprintf('  %s → %d colores (%s)', $modelo, count($colores), implode(', ', $colores)));

            if ($simular) {
                $fusionados++;
                $borrados += count($sobrantes);
                continue;
            }

            DB::transaction(function () use ($project, $representanteId, $hermanos, $sobrantes, $modelo, $colores) {
                $this->fusionar($project, $representanteId, $hermanos, $sobrantes, $modelo, $colores);
            });

            $fusionados++;
            $borrados += count($sobrantes);
        }

        $this->newLine();
        $this->info(sprintf('%d modelos fusionados, %d productos sobrantes %s, %d saltados.',
            $fusionados, $borrados, $simular ? 'se borrarían' : 'borrados', $saltados));

        return self::SUCCESS;
    }

    /** @param  array<int, Product>  $hermanos */
    private function colores(array $hermanos): array
    {
        $colores = [];
        foreach ($hermanos as $h) {
            $color = AgrupadorModelos::colorDe($h);
            if ($color && ! in_array($color, $colores, true)) {
                $colores[] = $color;
            }
        }
        natcasesort($colores);

        return array_values($colores);
    }

    /** @param  array<int, Product>  $hermanos */
    private function fusionar(Project $project, int $representanteId, array $hermanos, array $sobrantes, string $modelo, array $colores): void
    {
        $representante = Product::findOrFail($representanteId);

        // Cada hermano ES un color, así que su foto principal es la foto de ese
        // color. Ese mapa es lo que después deja elegir color en la tienda.
        $fotoPorColor = [];
        foreach ($hermanos as $h) {
            $color = AgrupadorModelos::colorDe($h);
            if ($color && $h->mainImage && ! isset($fotoPorColor[$color])) {
                $fotoPorColor[$color] = $h->main_image_url;
            }
        }

        // Las fotos de los colores sobrantes pasan a la galería del modelo, en
        // orden y sin robarle la principal.
        $orden = (int) DB::table('product_images')->where('product_id', $representanteId)->max('sort_order');
        foreach ($sobrantes as $id) {
            foreach (DB::table('product_images')->where('product_id', $id)->orderBy('sort_order')->orderBy('id')->get() as $img) {
                DB::table('product_images')->where('id', $img->id)->update([
                    'product_id' => $representanteId,
                    'is_main' => 0,
                    'sort_order' => ++$orden,
                ]);
            }
        }

        $tallas = [];
        foreach ($hermanos as $h) {
            foreach ((array) data_get($h->options, 'sizes', []) as $talla) {
                $talla = trim((string) $talla);
                if ($talla !== '' && ! in_array($talla, $tallas, true)) {
                    $tallas[] = $talla;
                }
            }
        }
        natsort($tallas);

        $representante->update([
            'name' => $modelo,
            'description' => $this->descripcion((string) $representante->description, $colores),
            'options' => array_filter([
                'sizes' => array_values($tallas),
                'colors' => $colores,
                // Qué foto enseña cada color, para que la tienda pueda cambiarla.
                'color_images' => $fotoPorColor,
            ]),
        ]);

        Product::whereIn('id', $sobrantes)->delete();
    }

    /**
     * La descripción venía escrita para un color concreto ("Color: Celeste.").
     * Al fusionar deja de ser cierta, así que se cambia por la lista completa.
     */
    private function descripcion(string $texto, array $colores): string
    {
        if (trim($texto) === '') {
            return $texto;
        }

        $lista = 'Colores: '.implode(', ', $colores).'.';

        $nuevo = preg_replace('/Colou?r(?:es)?\s*:\s*[^.]*\./iu', $lista, $texto, 1, $sustituido);

        return $sustituido ? (string) $nuevo : rtrim($texto).' '.$lista;
    }
}

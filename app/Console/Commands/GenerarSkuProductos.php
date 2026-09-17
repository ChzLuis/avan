<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Propone un SKU legible para los productos que no lo tienen.
 *
 * El código del emisor (`SellersItemIdentification`) lo define el negocio y
 * SUNAT no le impone formato. Hasta ahora la factura enviaba el id interno de
 * la base —un número sin significado para el comerciante y que cambia si algún
 * día se migran los datos—.
 *
 * Por defecto SOLO muestra la propuesta: tocar el catálogo de un cliente sin
 * que lo haya revisado no se hace. Con `--aplicar` se guarda.
 */
class GenerarSkuProductos extends Command
{
    protected $signature = 'bixo:generar-sku
                            {proyecto : ID del proyecto}
                            {--aplicar : Guarda los SKU propuestos (por defecto solo los muestra)}
                            {--limite=0 : Muestra como mucho N filas en la vista previa}';

    protected $description = 'Propone un SKU legible para los productos que no lo tienen';

    /** Palabras que no aportan al código y solo lo alargan. */
    private const VACIAS = ['de', 'del', 'la', 'el', 'los', 'las', 'con', 'para', 'por', 'y', 'a', 'en'];

    public function handle(): int
    {
        $project = Project::find((int) $this->argument('proyecto'));
        if (! $project) {
            $this->error('No existe ese proyecto.');

            return self::FAILURE;
        }

        $sinSku = Product::where('project_id', $project->id)
            ->where(fn ($q) => $q->whereNull('sku')->orWhere('sku', ''))
            ->orderBy('name')->get();

        $usados = Product::where('project_id', $project->id)
            ->whereNotNull('sku')->where('sku', '!=', '')
            ->pluck('sku')->map(fn ($s) => mb_strtoupper($s))->all();
        $usados = array_flip($usados);

        $this->newLine();
        $this->line("  <options=bold>{$project->name}</> — {$sinSku->count()} productos sin SKU");
        $this->newLine();

        if ($sinSku->isEmpty()) {
            $this->info('  Todos los productos ya tienen código.');

            return self::SUCCESS;
        }

        $filas = [];
        foreach ($sinSku as $producto) {
            $sku = $this->proponer($producto->name, $usados);
            $usados[$sku] = true;
            $filas[] = [$producto->id, Str::limit($producto->name, 52), $sku];
        }

        $limite = (int) $this->option('limite');
        $this->table(['ID', 'Producto', 'SKU propuesto'],
            $limite > 0 ? array_slice($filas, 0, $limite) : $filas);

        if (! $this->option('aplicar')) {
            $this->newLine();
            $this->line('  <fg=yellow>Vista previa.</> Revisa la lista y vuelve a ejecutar con <options=bold>--aplicar</> para guardarla.');
            $this->newLine();

            return self::SUCCESS;
        }

        $guardados = 0;
        foreach ($filas as [$id, , $sku]) {
            Product::where('id', $id)->update(['sku' => $sku]);
            $guardados++;
        }

        $this->newLine();
        $this->info("  {$guardados} SKU guardados.");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Un código legible a partir del nombre.
     *
     * La clave está en QUÉ distingue a un producto de su hermano: casi siempre
     * es un número —12W frente a 15W, 1/2" frente a 3/4"—, y ese número suele
     * ir al final del nombre. Quedarse con las tres primeras palabras daba
     * códigos que no distinguían nada (`FOC-LED-OPA-2`, `FOC-LED-OPA-3`), así
     * que se toman dos siglas de texto MÁS los tokens con dígitos.
     *
     * @param  array<string,mixed>  $usados
     */
    private function proponer(string $nombre, array $usados): string
    {
        $limpio = Str::upper(Str::ascii($nombre));
        $limpio = preg_replace('/[^A-Z0-9 ]/', ' ', $limpio);
        $tokens = array_values(array_filter(
            preg_split('/\s+/', trim($limpio)) ?: [],
            fn ($p) => $p !== '' && ! in_array(mb_strtolower($p), self::VACIAS, true)
        ));

        $texto = $numeros = [];
        foreach ($tokens as $token) {
            if (preg_match('/\d/', $token)) {
                $numeros[] = Str::limit($token, 5, '');
            } elseif (mb_strlen($token) > 1) {
                $texto[] = Str::limit($token, 3, '');
            }
        }

        $partes = array_merge(array_slice($texto, 0, 2), array_slice($numeros, 0, 2));
        $base = trim(implode('-', array_filter($partes)), '-') ?: 'PROD';
        $sku = $base;

        // Sin colisiones: el código identifica una sola cosa.
        $n = 2;
        while (isset($usados[$sku])) {
            $sku = $base.'-'.$n++;
        }

        return $sku;
    }
}

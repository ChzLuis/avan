<?php

namespace App\Modules\Catalogo\Commands;

use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Support\Imagen\ImagenNoProcesable;
use App\Support\Imagen\ProcesadorImagenes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Trae al sistema las fotos que apuntan a otro servidor y las normaliza.
 *
 * Un catálogo importado por conector guarda la URL del proveedor tal cual. Esa
 * foto nunca pasa por el procesador: no se recorta el fondo, no se encuadra y
 * no hay variantes, así que en la rejilla se ve distinta a todas las demás.
 * Además la tienda depende de que un servidor ajeno siga sirviéndola.
 *
 * Aquí se descarga una vez, se procesa como cualquier otra foto de producto y
 * la ficha pasa a apuntar a la copia propia. Si la descarga falla se deja la
 * URL externa intacta: mejor una foto de fuera que ninguna.
 */
class ImportarImagenesExternas extends Command
{
    protected $signature = 'imagenes:importar-externas
        {proyecto? : Id del proyecto; sin él, todos los activos}
        {--simular : Enseña lo que haría sin descargar ni escribir}
        {--limite=0 : Corta después de N fotos}';

    protected $description = 'Descarga las fotos alojadas fuera y las normaliza como propias';

    public function handle(ProcesadorImagenes $procesador): int
    {
        $proyectos = $this->argument('proyecto')
            ? Project::where('id', (int) $this->argument('proyecto'))->get()
            : Project::where('is_active', true)->orderBy('id')->get();

        $limite = (int) $this->option('limite');
        $simular = (bool) $this->option('simular');
        $traidas = $fallidas = 0;

        foreach ($proyectos as $project) {
            $externas = DB::table('product_images')
                ->join('products', 'products.id', '=', 'product_images.product_id')
                ->where('products.project_id', $project->id)
                ->where('product_images.url', 'like', 'http%')
                ->select('product_images.id', 'product_images.url', 'product_images.product_id')
                ->get()
                // "Fuera" no es "otro dominio" sino "no la sirve este sistema":
                // las nuestras salen con dominio absoluto pero de nuestras
                // carpetas, y en consola no hay host con el que compararlas.
                ->filter(fn ($i) => ! str_contains((string) $i->url, '/uploads/products/')
                                 && ! str_contains((string) $i->url, '/storage/products/'));

            if ($externas->isEmpty()) {
                continue;
            }

            $this->line("<info>{$project->name}</info>: {$externas->count()} fotos alojadas fuera");

            foreach ($externas as $img) {
                if ($limite > 0 && $traidas >= $limite) {
                    break 2;
                }

                if ($simular) {
                    $this->line('  · '.\Illuminate\Support\Str::limit($img->url, 78));
                    $traidas++;
                    continue;
                }

                try {
                    $this->traer($procesador, $img);
                    $traidas++;
                } catch (\Throwable $e) {
                    $this->warn('  ! '.\Illuminate\Support\Str::limit($img->url, 60).': '.$e->getMessage());
                    $fallidas++;
                }

                if ($traidas % 25 === 0 && $traidas > 0) {
                    $this->line("  … {$traidas} traídas");
                }
            }
        }

        $this->newLine();
        $this->info(sprintf('%d fotos %s, %d fallaron.',
            $traidas, $simular ? 'se traerían' : 'traídas y normalizadas', $fallidas));

        return self::SUCCESS;
    }

    private function traer(ProcesadorImagenes $procesador, object $img): void
    {
        $respuesta = Http::timeout(25)->get($img->url);
        if (! $respuesta->successful()) {
            throw new \RuntimeException('respondió '.$respuesta->status());
        }

        $temporal = tempnam(sys_get_temp_dir(), 'ext');
        file_put_contents($temporal, $respuesta->body());

        try {
            $producto = Product::find($img->product_id);
            if (! $producto) {
                throw new \RuntimeException('el producto ya no existe');
            }

            // Mismo destino y mismo perfil que una foto subida a mano: a partir
            // de aquí es una foto propia y se comporta como el resto.
            $resultado = $procesador->procesar(
                $temporal,
                'products/'.$producto->id,
                'producto',
                $this->nombre($img->url),
                'uploads'
            );

            DB::table('product_images')->where('id', $img->id)
                ->update(['url' => $resultado->urlPrincipal(), 'updated_at' => now()]);
        } catch (ImagenNoProcesable $e) {
            throw new \RuntimeException($e->getMessage());
        } finally {
            @unlink($temporal);
        }
    }

    /** Nombre estable a partir de la URL de origen, para no traerla dos veces. */
    private function nombre(string $url): string
    {
        $base = pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME);
        $base = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $base), '-.');

        return \Illuminate\Support\Str::limit($base, 50, '') ?: 'externa-'.substr(md5($url), 0, 8);
    }
}

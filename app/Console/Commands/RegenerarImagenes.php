<?php

namespace App\Console\Commands;

use App\Support\Imagen\ImagenNoProcesable;
use App\Support\Imagen\PerfilImagen;
use App\Support\Imagen\ProcesadorImagenes;
use Illuminate\Console\Command;

/**
 * Pone al día las fotos que ya estaban subidas antes de que existiera el
 * procesador: les genera el juego de variantes y el WebP que les faltan.
 *
 * No toca el archivo original —se queda donde está y con el mismo nombre, así
 * que ninguna URL guardada en base de datos deja de funcionar—; solo escribe
 * los hermanos `-400`, `-800`… a su lado. Es reejecutable: lo ya hecho se
 * salta salvo que se pida --forzar.
 */
class RegenerarImagenes extends Command
{
    protected $signature = 'imagenes:regenerar
        {--carpeta= : Procesa solo esta carpeta (p. ej. logos)}
        {--perfil= : Fuerza un perfil en vez del que toca por carpeta}
        {--limite=0 : Corta después de N imágenes}
        {--forzar : Rehace también las que ya tienen variantes}
        {--simular : Enseña lo que haría sin escribir nada}';

    protected $description = 'Genera las variantes optimizadas de las imágenes ya subidas';

    /**
     * Dónde vive cada cosa y con qué encuadre hay que rehacerla.
     *
     * @var array<int, array{0:string,1:string,2:string}>  [carpeta, disco, perfil]
     */
    private const CARPETAS = [
        ['products', 'uploads', 'producto'],
        // Las fotos importadas de proveedor entraron por el disco público, no
        // por public/uploads: son la mayor parte del peso de la web.
        ['products', 'public', 'producto'],
        ['categories', 'public', 'categoria'],
        ['logos', 'public', 'logo'],
        ['store-header', 'public', 'logo'],
        ['store-sections', 'public', 'seccion'],
        ['store-popups', 'public', 'seccion'],
        ['catalog-profiles', 'public', 'generico'],
        ['design-templates', 'public', 'miniatura'],
    ];

    public function handle(ProcesadorImagenes $procesador): int
    {
        $filtro = (string) $this->option('carpeta');
        $limite = (int) $this->option('limite');
        $simular = (bool) $this->option('simular');

        $hechas = $saltadas = $fallidas = 0;
        $pesoAntes = $pesoDespues = 0;

        foreach (self::CARPETAS as [$carpeta, $disco, $perfilPorDefecto]) {
            if ($filtro !== '' && $filtro !== $carpeta) {
                continue;
            }

            $perfil = (string) ($this->option('perfil') ?: $perfilPorDefecto);
            if (! PerfilImagen::existe($perfil)) {
                $this->error("Perfil desconocido: {$perfil}");

                return self::FAILURE;
            }

            $raiz = $disco === 'uploads' ? public_path('uploads/'.$carpeta) : storage_path('app/public/'.$carpeta);
            if (! is_dir($raiz)) {
                continue;
            }

            $this->line("<info>{$carpeta}</info> [{$disco}] ({$perfil})");

            foreach ($this->imagenes($raiz) as $absoluta) {
                if ($limite > 0 && $hechas >= $limite) {
                    break 2;
                }

                $relativa = str_replace('\\', '/', substr($absoluta, strlen($raiz) + 1));
                $subcarpeta = trim($carpeta.'/'.dirname($relativa), '/.');
                $base = pathinfo($relativa, PATHINFO_FILENAME);

                // Un archivo que ya es variante (`foto-800.jpg`) no se vuelve a
                // procesar: se generaría a sí mismo en bucle.
                if (preg_match('/-\d+$/', $base)) {
                    continue;
                }

                // Muchas fotos ya tenían un .webp hermano hecho a mano. Se
                // procesa solo el archivo maestro para no generar dos veces
                // exactamente el mismo juego de variantes.
                if ($this->esCopiaDe($absoluta)) {
                    continue;
                }

                if (! $this->option('forzar') && $this->yaTieneVariantes($absoluta, $perfil)) {
                    $saltadas++;
                    continue;
                }

                if ($simular) {
                    $this->line("  · {$relativa}");
                    $hechas++;
                    continue;
                }

                try {
                    $resultado = $procesador->procesar($absoluta, $subcarpeta, $perfil, $base, $disco);
                    $pesoAntes += $resultado->pesoOriginal;
                    $pesoDespues += $resultado->pesoFinal;
                    $hechas++;
                } catch (ImagenNoProcesable $e) {
                    $this->warn("  ! {$relativa}: {$e->getMessage()}");
                    $fallidas++;
                } catch (\Throwable $e) {
                    $this->warn("  ! {$relativa}: {$e->getMessage()}");
                    $fallidas++;
                }

                if ($hechas % 25 === 0 && $hechas > 0) {
                    $this->line("  … {$hechas} procesadas");
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%d procesadas, %d ya estaban al día, %d fallaron.',
            $hechas, $saltadas, $fallidas
        ));

        if ($pesoAntes > 0) {
            $this->info(sprintf(
                'Peso servido: %.1f MB → %.1f MB (%d%% menos).',
                $pesoAntes / 1048576,
                $pesoDespues / 1048576,
                (int) round(100 - ($pesoDespues * 100 / $pesoAntes))
            ));
        }

        return self::SUCCESS;
    }

    /**
     * La lista se cierra antes de empezar a escribir: si se recorriera el
     * directorio en caliente, las variantes recién creadas entrarían en el
     * propio recorrido.
     *
     * @return array<string>
     */
    private function imagenes(string $raiz): array
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS)
        );

        $encontradas = [];
        foreach ($it as $archivo) {
            if ($archivo->isFile() && preg_match('/\.(jpe?g|png|gif|bmp|webp|avif)$/i', $archivo->getFilename())) {
                $encontradas[] = $archivo->getPathname();
            }
        }
        sort($encontradas);

        return $encontradas;
    }

    /** ¿Es un .webp/.avif que solo duplica un jpg/png con el mismo nombre? */
    private function esCopiaDe(string $absoluta): bool
    {
        if (! preg_match('/\.(webp|avif)$/i', $absoluta)) {
            return false;
        }

        $sinExt = preg_replace('/\.[a-z0-9]+$/i', '', $absoluta);

        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            if (is_file($sinExt.'.'.$ext)) {
                return true;
            }
        }

        return false;
    }

    /** Basta con que exista el ancho de referencia para darla por hecha. */
    private function yaTieneVariantes(string $absoluta, string $perfil): bool
    {
        $sinExt = preg_replace('/\.[a-z0-9]+$/i', '', $absoluta);
        $ancho = PerfilImagen::de($perfil)->anchoPrincipal();

        return is_file($sinExt.'-'.$ancho.'.jpg') || is_file($sinExt.'-'.$ancho.'.png');
    }
}

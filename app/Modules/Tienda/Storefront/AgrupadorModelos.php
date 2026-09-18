<?php

namespace App\Modules\Tienda\Storefront;

use App\Modules\Catalogo\Models\Product;
use App\Models\Project;

/**
 * Junta en una sola tarjeta los productos que son el mismo modelo en distinto
 * color.
 *
 * Una tienda de ropa carga cada color como un producto aparte —es lo correcto
 * para el stock y para la factura—, pero en el escaparate eso llena la rejilla
 * con siete fotos casi iguales y el cliente no ve el catálogo, ve un color.
 * Aquí se elige un representante por modelo y el resto viaja con él como
 * opciones de color.
 *
 * No cambia nada en la base de datos: cada color sigue siendo su producto, con
 * su stock, su precio y su ficha.
 */
class AgrupadorModelos
{
    /** Cache por proyecto dentro de la misma petición. */
    private array $memoria = [];

    public static function activoEn(Project $project): bool
    {
        return (string) $project->setting('catalog_group_models', '0') === '1';
    }

    /**
     * Nombre del modelo: el del producto sin la coletilla del color.
     *
     * Se usa el color declarado en `options.colors` para recortar solo lo que
     * de verdad es el color. Adivinar por un guion suelto juntaría productos
     * que no tienen nada que ver ("Polo - Talla 4" con "Polo - Talla 6").
     */
    public static function claveModelo(Product $p): string
    {
        $nombre = trim((string) $p->name);
        $color = trim((string) data_get($p->options, 'colors.0', ''));

        if ($color !== '') {
            foreach ([" - Color {$color}", " - {$color}", " Color {$color}"] as $coletilla) {
                if (mb_strtolower(mb_substr($nombre, -mb_strlen($coletilla))) === mb_strtolower($coletilla)) {
                    return trim(mb_substr($nombre, 0, mb_strlen($nombre) - mb_strlen($coletilla)));
                }
            }
        }

        // Sin color declarado se acepta el separador convencional del importador.
        $corte = mb_stripos($nombre, ' - Color ');

        return $corte === false ? $nombre : trim(mb_substr($nombre, 0, $corte));
    }

    /** El color que representa a este producto dentro de su modelo. */
    public static function colorDe(Product $p): ?string
    {
        $color = trim((string) data_get($p->options, 'colors.0', ''));
        if ($color !== '') {
            return $color;
        }

        $corte = mb_stripos((string) $p->name, ' - Color ');

        return $corte === false ? null : trim(mb_substr((string) $p->name, $corte + mb_strlen(' - Color ')));
    }

    /**
     * Ids que deben aparecer en la rejilla: uno por modelo.
     *
     * Manda el que tiene foto —una tarjeta sin imagen es una tarjeta perdida—
     * y, a igualdad, el de menor `sort_order`.
     *
     * @return array<int>
     */
    public function representantes(Project $project): array
    {
        return array_keys($this->grupos($project));
    }

    /**
     * Los hermanos de cada representante, ya ordenados por color.
     *
     * @return array<int, array<int, Product>>  id del representante => productos del modelo
     */
    public function grupos(Project $project, bool $soloVendibles = true): array
    {
        $cache = $project->id.'|'.(int) $soloVendibles;
        if (isset($this->memoria[$cache])) {
            return $this->memoria[$cache];
        }

        $consulta = $project->products()->with('mainImage');

        // El escaparate solo agrupa lo que se puede comprar; una fusión de datos
        // tiene que ver el catálogo entero, incluido lo que aún no tiene precio.
        if ($soloVendibles) {
            $consulta->where('is_available', true)->where('price', '>', 0);
        }

        $productos = $consulta->get(['id', 'name', 'options', 'category_id', 'sort_order', 'price', 'stock']);

        $porModelo = [];
        foreach ($productos as $p) {
            // La categoría entra en la clave: dos modelos pueden llamarse igual
            // en secciones distintas y no son el mismo artículo.
            $clave = self::claveModelo($p).'|'.$p->category_id;
            $porModelo[$clave][] = $p;
        }

        $grupos = [];
        foreach ($porModelo as $hermanos) {
            usort($hermanos, function (Product $a, Product $b) {
                $conFoto = (int) (bool) $b->mainImage <=> (int) (bool) $a->mainImage;

                return $conFoto !== 0 ? $conFoto : [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id];
            });

            $grupos[$hermanos[0]->id] = $hermanos;
        }

        return $this->memoria[$cache] = $grupos;
    }

    /**
     * Las opciones de color de un modelo, listas para la tarjeta.
     *
     * Devuelve vacío cuando el producto es único: una sola opción no es una
     * elección, y pintar un botón de color suelto solo confunde.
     *
     * @return array<int, array{id:int,color:string,image:?string,url:string,price:float,stock:?int}>
     */
    public function variantesDe(Project $project, int $representanteId, string $slug): array
    {
        $hermanos = $this->grupos($project)[$representanteId] ?? [];

        // Catálogo ya fusionado: los colores viven dentro del propio producto y
        // no hay hermanos que juntar. La tarjeta se comporta igual.
        if (count($hermanos) < 2) {
            return $this->coloresPropios($hermanos[0] ?? Product::find($representanteId), $project);
        }

        $variantes = [];
        foreach ($hermanos as $h) {
            $foto = $h->mainImage ? $h->main_image_url : null;
            $variantes[] = [
                'id' => $h->id,
                'color' => self::colorDe($h) ?: 'Único',
                'image' => $foto,
                // Cada color viaja con su propio juego de anchos: al cambiar de
                // color la tarjeta no pierde el srcset y sigue sirviendo la foto
                // del tamaño que toca.
                'srcset' => \App\Support\Imagen\Img::srcsetDe($foto, 'producto'),
                'srcsetWebp' => \App\Support\Imagen\Img::srcsetDe($foto, 'producto', 'webp'),
                'url' => \App\Support\ImageVariants::productUrl($project, $h->id, $h->name),
                'price' => (float) $h->price,
                'stock' => $h->stock,
            ];
        }

        usort($variantes, fn ($a, $b) => strnatcasecmp($a['color'], $b['color']));

        return $variantes;
    }

    /**
     * Qué color está enseñando ahora mismo la tarjeta.
     *
     * Tras la fusión todos los colores son el mismo producto, así que el id ya
     * no distingue cuál está activo: se mira qué variante trae la foto que se
     * está viendo.
     */
    public static function colorActivo(array $variantes, ?string $imagen): ?string
    {
        $huella = self::huella($imagen);

        foreach ($variantes as $v) {
            if ($huella !== '' && self::huella($v['image'] ?? null) === $huella) {
                return $v['color'];
            }
        }

        return $variantes[0]['color'] ?? null;
    }

    /**
     * La misma foto llega con distinta extensión según quién la pida (.webp
     * para la tarjeta, .jpg para la variante), así que se compara el nombre sin
     * extensión. Comparando la URL entera nunca coincidían y la tarjeta acababa
     * marcando un color que no era el de la foto.
     */
    private static function huella(?string $url): string
    {
        if (! $url) {
            return '';
        }

        $ruta = parse_url($url, PHP_URL_PATH) ?: $url;

        return (string) preg_replace('/\.[a-z0-9]+$/i', '', $ruta);
    }

    /**
     * Colores de un producto que ya trae sus colores dentro (`options.color_images`).
     *
     * El id no cambia —es un solo producto— así que lo que hace el selector es
     * cambiar la foto; el color elegido viaja al carrito como atributo, igual
     * que la talla.
     */
    private function coloresPropios(?Product $p, Project $project): array
    {
        $mapa = array_filter((array) data_get($p?->options, 'color_images', []));
        if (count($mapa) < 2) {
            return [];
        }

        $url = \App\Support\ImageVariants::productUrl($project, $p->id, $p->name);

        $variantes = [];
        foreach ($mapa as $color => $foto) {
            $foto = \App\Support\Imagen\Img::mejor((string) $foto, 'producto');
            $variantes[] = [
                'id' => $p->id,
                'color' => (string) $color,
                'image' => $foto,
                'srcset' => \App\Support\Imagen\Img::srcsetDe($foto, 'producto'),
                'srcsetWebp' => \App\Support\Imagen\Img::srcsetDe($foto, 'producto', 'webp'),
                'url' => $url,
                'price' => (float) $p->price,
                'stock' => $p->stock,
            ];
        }

        usort($variantes, fn ($a, $b) => strnatcasecmp($a['color'], $b['color']));

        return $variantes;
    }
}

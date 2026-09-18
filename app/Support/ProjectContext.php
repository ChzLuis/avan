<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Order;
use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Modules\Catalogo\Models\Service;
use Illuminate\Support\Collection;

/**
 * "Puerta única" a toda la información de un proyecto BIXO.
 *
 * Es la base sobre la que trabajan el bot, la IA, el Copilot y el motor de
 * flujos: cualquier bloque que necesite un dato del negocio lo pide aquí.
 * Centralizar la lectura significa que una sola clase conoce el esquema; el
 * resto solo consume métodos con nombre de negocio (buscarProducto, cliente…).
 */
class ProjectContext
{
    public function __construct(private Project $project) {}

    public static function for(Project $project): self
    {
        return new self($project);
    }

    // ── CATÁLOGO Y PRECIOS ────────────────────────────────────────────
    /** Busca productos por nombre/sku (para "¿tienen X?", "¿precio de X?"). */
    public function buscarProducto(string $q, int $limit = 8): Collection
    {
        // Productos
        $productos = Product::where('project_id', $this->project->id)
            ->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('sku', 'like', "%$q%"))
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'price', 'stock', 'description'])
            ->map(fn ($p) => [
                'id' => $p->id, 'tipo' => 'producto', 'nombre' => $p->name, 'sku' => $p->sku,
                'precio' => (float) $p->price, 'stock' => $p->stock,
                'descripcion' => $p->description,
            ]);

        // Servicios (sin stock; se identifican con tipo 'servicio')
        $servicios = Service::where('project_id', $this->project->id)
            ->where('name', 'like', "%$q%")
            ->limit($limit)
            ->get(['id', 'name', 'price', 'description'])
            ->map(fn ($s) => [
                'id' => 'srv_' . $s->id, 'tipo' => 'servicio', 'nombre' => $s->name, 'sku' => null,
                'precio' => (float) $s->price, 'stock' => null,
                'descripcion' => $s->description,
            ]);

        return $productos->concat($servicios)->take($limit);
    }

    /**
     * BÚSQUEDA TOLERANTE (sin IA): normaliza (minúsculas, sin tildes, sin símbolos),
     * tolera plurales, palabras parciales y errores de escritura pequeños.
     * Puntúa cada producto por coincidencia y devuelve los mejores.
     * Ej: "chia", "china", "semilla chia", "chía" → encuentran "Semillas de chía".
     */
    public function buscarTolerante(string $q, int $limit = 8, ?int $categoriaId = null): Collection
    {
        $normal = $this->normalizar($q);
        $palabras = array_values(array_filter(explode(' ', $normal), fn ($w) => mb_strlen($w) >= 2));
        if (empty($palabras)) return collect();

        // Traemos el catalogo (nombre + sku + categoria + atributos) y puntuamos
        // en PHP. `options` entra porque ahi viven colores y tallas: sin eso,
        // "casaca rosado" o "talla 4" no encuentran nada.
        $consulta = Product::where('project_id', $this->project->id)
            ->with('category:id,name');
        if ($categoriaId) $consulta->where('category_id', $categoriaId);
        $productos = $consulta->get(['id', 'name', 'sku', 'price', 'stock', 'description', 'category_id', 'options', 'brand_catalog_id']);

        // Marcas: una sola consulta para todas (nunca N+1). Hoy ningun producto
        // tiene marca asignada, pero en cuanto alguien la use, ya puntua.
        $marcas = collect();
        $idsMarca = $productos->pluck('brand_catalog_id')->filter()->unique();
        if ($idsMarca->isNotEmpty()) {
            $marcas = \DB::table('catalog_values')->whereIn('id', $idsMarca)->pluck('label', 'id');
        }

        $scored = $productos->map(function ($p) use ($palabras, $normal, $marcas) {
            $nombreN = $this->normalizar($p->name);
            $catN    = $this->normalizar(optional($p->category)->name ?? '');
            $skuN    = $this->normalizar($p->sku ?? '');
            $marcaN  = $this->normalizar((string) ($marcas[$p->brand_catalog_id] ?? ''));
            $attrN   = $this->normalizar(implode(' ', array_merge(
                (array) data_get($p->options, 'colors', []),
                (array) data_get($p->options, 'sizes', [])
            )));
            $heno    = trim($nombreN . ' ' . $catN . ' ' . $skuN . ' ' . $marcaN . ' ' . $attrN);

            $score = 0;
            $acertadas = 0;
            // Coincidencia exacta de toda la frase
            if ($nombreN === $normal) $score += 100;
            elseif (str_contains($nombreN, $normal)) $score += 60;

            foreach ($palabras as $w) {
                $wsing = $this->singular($w);
                if (str_contains($heno, $w) || str_contains($heno, $wsing)) {
                    $score += 20;                       // palabra contenida
                    $acertadas++;
                } else {
                    // Error de escritura: comparar contra cada palabra del nombre
                    foreach (explode(' ', $heno) as $token) {
                        if (mb_strlen($token) < 3) continue;
                        $d = levenshtein($w, $token);
                        if ($d <= 1) { $score += 15; $acertadas++; break; }
                        if ($d == 2 && mb_strlen($w) >= 5) { $score += 8; $acertadas++; break; }
                    }
                }
            }

            return ['p' => $p, 'score' => $score, 'acertadas' => $acertadas];
        })->filter(fn ($x) => $x['score'] > 0)
          ->sortByDesc('score')
          ->take($limit)
          ->map(fn ($x) => [
              'id' => $x['p']->id, 'tipo' => 'producto', 'nombre' => $x['p']->name,
              'sku' => $x['p']->sku, 'precio' => (float) $x['p']->price,
              'stock' => $x['p']->stock, 'descripcion' => $x['p']->description,
              'categoria' => optional($x['p']->category)->name,
              'categoria_id' => $x['p']->category_id,
              // Solo es COINCIDENCIA si acerto TODAS las palabras pedidas. Con
              // menos, es una alternativa: el bot debe decirlo tal cual y no
              // afirmar que encontro lo que le pidieron.
              'exacto' => $x['acertadas'] >= count($palabras),
          ])->values();

        return $scored;
    }

    /** Categorías con productos (para navegar catálogos grandes). */
    public function categoriasConProductos(): Collection
    {
        return \App\Modules\Catalogo\Models\Category::where('project_id', $this->project->id)
            ->where('is_active', true)
            ->withCount('products')
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn ($c) => $c->products_count > 0)
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->name, 'total' => $c->products_count])
            ->values();
    }

    /** Productos de una categoría (para navegar por categoría). */
    public function productosDeCategoria(int $categoryId, int $limit = 30): Collection
    {
        return Product::where('project_id', $this->project->id)
            ->where('category_id', $categoryId)
            ->orderBy('name')->limit($limit)
            ->get(['id', 'name', 'price', 'stock'])
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'precio' => (float) $p->price, 'stock' => $p->stock])
            ->values();
    }

    /** Un producto por id (para el carrito). */
    public function producto(int $id): ?array
    {
        $p = Product::where('project_id', $this->project->id)->find($id);
        if (!$p) return null;
        return ['id' => $p->id, 'nombre' => $p->name, 'precio' => (float) $p->price, 'stock' => $p->stock];
    }

    /**
     * Datos de pago digital del negocio (Yape/Plin) para que el bot los envíe:
     * número, titular y la URL del QR si está configurado.
     */
    public function datosPago(): array
    {
        $s = $this->project->settings()->pluck('value', 'key');
        $qr = $s['payment_yape_qr'] ?? null;
        if ($qr && !str_starts_with($qr, 'http')) $qr = asset('storage/' . ltrim($qr, '/'));
        return [
            'yape_numero'  => $s['payment_yape_number'] ?? null,
            'yape_nombre'  => $s['payment_yape_name'] ?? null,
            'plin_numero'  => $s['payment_plin_number'] ?? null,
            'qr_url'       => $qr,
            'instrucciones'=> $s['payment_manual_instructions'] ?? null,
        ];
    }

    /**
     * Ficha comercial de la empresa para el bot: SOLO datos configurados de
     * verdad. Cada clave ausente vale null y el bot lo dice honestamente en
     * vez de inventar (regla crítica: el bot no alucina).
     */
    public function negocio(): array
    {
        $s = $this->project->settings()->pluck('value', 'key');
        $limpio = fn ($v) => filled($v) ? trim((string) $v) : null;

        $redes = array_filter([
            'Facebook'  => $limpio($s['facebook_url'] ?? null),
            'Instagram' => $limpio($s['instagram_url'] ?? null),
            'TikTok'    => $limpio($s['tiktok_url'] ?? null),
            'YouTube'   => $limpio($s['youtube_url'] ?? null),
        ]);

        // El pin exacto manda sobre la busqueda por texto (mismo criterio que
        // el mapa de la tienda).
        $coords = $limpio($s['map_coords'] ?? null);
        $direccion = $limpio($this->project->address);
        $mapa = $coords || $direccion
            ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($coords ?: $direccion)
            : null;

        return [
            'nombre'       => $this->project->name,
            'razon_social' => $limpio($s['razon_social'] ?? null),
            'ruc'          => $limpio($s['ruc'] ?? null),
            'descripcion'  => $limpio($this->project->description),
            'direccion'    => $direccion,
            'mapa'         => $mapa,
            'telefono'     => $limpio($this->project->phone),
            'whatsapp'     => $limpio($this->project->whatsapp ?: $this->project->wa_phone),
            'email'        => $limpio($s['email'] ?? null),
            'horario'      => $limpio($s['business_hours'] ?? null) ?: $limpio($s['contact_hours'] ?? null),
            'web'          => \App\Modules\Tienda\Support\StorefrontNavigation::publicUrl($this->project) ?: null,
            'redes'        => $redes,
        ];
    }

    /**
     * Promociones REALMENTE vigentes: campañas activas dentro de fechas y
     * productos con precio de oferta menor al regular. Nada mas.
     */
    public function promocionesVigentes(int $limit = 6): array
    {
        $campanas = \App\Modules\Tienda\Models\Promotion::where('project_id', $this->project->id)
            ->where('is_active', true)->get()
            ->filter(fn ($pr) => $pr->isActive())
            ->map(fn ($pr) => [
                'nombre'  => $pr->name,
                'detalle' => $pr->type === 'percent'
                    ? '-' . rtrim(rtrim(number_format((float) $pr->value, 2), '0'), '.') . '%'
                    : 'S/ ' . number_format((float) $pr->value, 2) . ' de descuento',
                'hasta'   => $pr->ends_at?->format('d/m/Y'),
            ])->values()->all();

        $ofertas = Product::where('project_id', $this->project->id)
            ->where('is_available', true)
            ->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price')
            ->orderByRaw('(compare_price - price) / compare_price DESC')
            ->limit($limit)
            ->get(['id', 'name', 'price', 'compare_price'])
            ->map(fn ($p) => [
                'id' => $p->id, 'nombre' => $p->name,
                'antes' => (float) $p->compare_price, 'ahora' => (float) $p->price,
            ])->all();

        return ['campanas' => $campanas, 'ofertas' => $ofertas];
    }

    /**
     * Ficha completa de UN producto para responder una consulta: precio real,
     * oferta real, caracteristicas de la descripcion, tallas/colores si los
     * hay, enlace y foto. Solo campos que existen; lo vacio no se incluye.
     */
    public function fichaProducto(int $id): ?array
    {
        $p = Product::where('project_id', $this->project->id)->with('category:id,name')->find($id);
        if (! $p) return null;

        // Caracteristicas: las primeras frases de la descripcion, en llano.
        $plano = trim(preg_replace('/\s+/', ' ', strip_tags((string) $p->description)));
        $frases = array_values(array_filter(array_map('trim', preg_split('/(?<=[.;])\s+/', $plano))));
        $caracteristicas = array_slice($frases, 0, 4);

        $oferta = $p->compare_price && (float) $p->compare_price > (float) $p->price;

        return array_filter([
            'id'        => $p->id,
            'nombre'    => $p->name,
            'sku'       => $p->sku ?: null,
            'categoria' => $p->category?->name,
            'precio'    => (float) $p->price,
            'antes'     => $oferta ? (float) $p->compare_price : null,
            'mayorista' => filled($p->wholesale_price) ? (float) $p->wholesale_price : null,
            'mayorista_min' => filled($p->wholesale_price) ? max(1, (int) ($p->wholesale_min_qty ?? 1)) : null,
            'stock'     => $p->stock,
            'caracteristicas' => $caracteristicas ?: null,
            'tallas'    => array_values(array_filter((array) data_get($p->options, 'sizes', []))) ?: null,
            'colores'   => array_values(array_filter((array) data_get($p->options, 'colors', []))) ?: null,
            'url'       => \App\Support\ImageVariants::productUrl($this->project, $p->id, $p->name),
            'imagen'    => $p->main_image_url,
        ], fn ($v) => $v !== null);
    }

    /**
     * Categoria del proyecto que coincide con el termino, o null.
     *
     * "muebles" en una tienda con categoria "Muebles" no es una FAQ ni un
     * producto suelto: es NAVEGACION. Coincidencia por nombre normalizado,
     * tolerante a plural/singular. Solo categorias REALES de la base.
     */
    /** ¿Mismo termino salvo plural? ("camarote" ~ "camarotes", "mueble" ~ "muebles") */
    private function mismaRaiz(string $a, string $b): bool
    {
        if ($a === '' || $b === '') return false;
        [$corto, $largo] = mb_strlen($a) <= mb_strlen($b) ? [$a, $b] : [$b, $a];

        if (str_starts_with($largo, $corto) && (mb_strlen($largo) - mb_strlen($corto)) <= 2) {
            return true;
        }

        // Variantes con sufijo distinto: "television" ~ "televisores".
        // Prefijo comun largo (>=6) que cubre casi toda la palabra corta.
        $n = 0;
        $max = mb_strlen($corto);
        while ($n < $max && mb_substr($a, $n, 1) === mb_substr($b, $n, 1)) $n++;

        return $n >= 6 && $n >= (int) ceil($max * 0.7) && (mb_strlen($largo) - $max) <= 3;
    }

    public function categoriaQueCoincide(string $q): ?\App\Modules\Catalogo\Models\Category
    {
        $n = $this->normalizar($q);
        if ($n === '' || str_word_count($n) > 3) return null;

        $exacta = null;
        $afines = 0;
        foreach (\App\Modules\Catalogo\Models\Category::where('project_id', $this->project->id)->get() as $cat) {
            $c = $this->normalizar($cat->name);
            if ($c === '') continue;
            if ($c === $n || $this->mismaRaiz($c, $n)) {
                $exacta = $cat;
                $afines++;
            } elseif (str_contains($this->singular($c), $this->singular($n))
                || str_contains($c, $this->singular($n))) {
                // "laptops" tambien vive dentro de "Laptops Gamer": el termino
                // es AMBIGUO y decide el desambiguador de secciones, no esta.
                $afines++;
            }
        }

        return $afines === 1 ? $exacta : null;
    }

    /**
     * Todas las categorias afines a un termino ("colchones" -> Colchones,
     * Colchones Espuma, Colchones Resortados). Con varias, lo honesto es
     * PREGUNTAR cual, no abrir una al azar.
     *
     * @return \Illuminate\Support\Collection<int, \App\Modules\Catalogo\Models\Category>
     */
    public function categoriasAfines(string $q): \Illuminate\Support\Collection
    {
        $n = $this->singular($this->normalizar($q));
        if ($n === '' || str_word_count($n) > 3) return collect();

        $res = $this->categoriasQueMatchean($n);
        if ($res->isEmpty() && str_contains($n, ' ')) {
            // "cocina a gas" no es una categoria, pero "cocina" si: la primera
            // palabra significativa decide la seccion.
            $res = $this->categoriasQueMatchean(explode(' ', $n)[0]);
        }

        return $res;
    }

    /** @return \Illuminate\Support\Collection<int, \App\Modules\Catalogo\Models\Category> */
    private function categoriasQueMatchean(string $n): \Illuminate\Support\Collection
    {
        return \App\Modules\Catalogo\Models\Category::where('project_id', $this->project->id)->get()
            ->filter(function ($cat) use ($n) {
                $c = $this->singular($this->normalizar($cat->name));
                $plano = $this->normalizar($cat->name);

                return ($c !== '' && ($c === $n || str_contains($c, $n)))
                    || $this->mismaRaiz($plano, $n) || str_contains($plano, $n);
            })
            ->values()
            ->take(6);
    }

    /**
     * Pagina de productos vendibles de una categoria (para "ver mas").
     *
     * @return array{items: \Illuminate\Support\Collection, total: int}
     */
    public function paginaDeCategoria(int $categoriaId, int $offset = 0, int $limit = 5,
        ?float $precioMax = null, string $orden = 'relevancia', ?string $term = null): array
    {
        $base = \App\Modules\Catalogo\Models\Product::where('project_id', $this->project->id)
            ->where('category_id', $categoriaId)
            ->where('is_available', true);
        if ($precioMax !== null) $base->where('price', '<=', $precioMax);

        $total = (clone $base)->count();

        // RELEVANCIA dentro de la categoria: primero lo que EMPIEZA con el
        // termino buscado, luego lo que lo contiene, luego el resto — que las
        // comodas "porta TV" no salgan antes que los televisores.
        if ($orden === 'precio_asc') {
            $base->orderBy('price');
        } elseif ($term) {
            $t = mb_strtoupper($this->singular($this->normalizar($term)));
            // Equivalencias comerciales SOLO para ordenar: en los catalogos
            // reales el televisor se llama "TV ...". No inventa datos: cambia
            // el ORDEN, jamas el contenido.
            $equiv = ['TELEVISOR' => 'TV', 'TELEVISION' => 'TV', 'REFRIGERADORA' => 'REFRI',
                      'COMPUTADORA' => 'PC', 'CELULAR' => 'SMARTPHONE'];
            $t2 = $equiv[$t] ?? $t;
            $base->orderByRaw(
                'CASE WHEN UPPER(name) LIKE ? OR UPPER(name) LIKE ? THEN 0'
                . ' WHEN UPPER(name) LIKE ? OR UPPER(name) LIKE ? THEN 1 ELSE 2 END',
                [$t . '%', $t2 . '%', '%' . $t . '%', '%' . $t2 . '%']
            )->orderBy('name');
        } else {
            $base->orderBy('name');
        }

        $items = $base->skip($offset)->limit($limit)
            ->get(['id', 'name', 'price'])
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'precio' => (float) $p->price]);

        return ['items' => $items, 'total' => $total];
    }

    /** Fichas minimas de una lista de ids, CONSERVANDO el orden dado. */
    public function productosPorIds(array $ids): \Illuminate\Support\Collection
    {
        if (empty($ids)) return collect();
        $porId = \App\Modules\Catalogo\Models\Product::where('project_id', $this->project->id)
            ->whereIn('id', $ids)->get(['id', 'name', 'price'])->keyBy('id');

        return collect($ids)->map(fn ($id) => $porId->get($id))->filter()
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'precio' => (float) $p->price])
            ->values();
    }

    /**
     * Preguntas frecuentes del negocio.
     *
     * Fuente unica: la seccion `faq` que el dueno ya llena en el constructor
     * (store_sections). No se duplica en ninguna tabla propia del bot: lo que
     * se ve en la web es exactamente lo que responde el bot.
     *
     * @return array<int, array{pregunta: string, respuesta: string}>
     */
    public function faq(): array
    {
        $seccion = \DB::table('store_sections')
            ->where('project_id', $this->project->id)
            ->where('component', 'faq')
            ->where('is_enabled', 1)
            ->first(['content']);

        $items = json_decode((string) ($seccion->content ?? ''), true)['items'] ?? [];
        if (! is_array($items)) return [];

        $items = array_values(array_filter($items, fn ($i) => ($i['enabled'] ?? true)
            && filled($i['question'] ?? null) && filled($i['answer'] ?? null)));

        usort($items, fn ($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return array_map(fn ($i) => [
            'pregunta'  => trim((string) $i['question']),
            'respuesta' => trim((string) $i['answer']),
        ], $items);
    }

    /**
     * La FAQ que mejor responde a una consulta, o null.
     *
     * Puntua palabra a palabra contra la pregunta y la respuesta, con el mismo
     * criterio tolerante del catalogo. Exige al menos dos aciertos (o uno si la
     * consulta es de una sola palabra) para no responder cualquier cosa.
     */
    public function faqQueResponde(string $q): ?array
    {
        // Las palabras de cortesia/pregunta no puntuan: "tienen delivery"
        // debe encontrar la FAQ de delivery aunque "tienen" no aparezca en
        // ella (fallo real con typo "delibery" ya corregido a delivery).
        $genericas = ['tienen', 'tiene', 'hacen', 'hace', 'puedo', 'pueden', 'puede',
                      'ustedes', 'cual', 'cuales', 'como', 'donde', 'cuando', 'para', 'con',
                      'verdad', 'cierto', 'acaso', 'sobre', 'los', 'las', 'del', 'una', 'uno'];
        $palabras = array_values(array_filter(
            explode(' ', $this->normalizar($q)),
            fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, $genericas, true)
        ));
        if (empty($palabras)) return null;

        $minimo = count($palabras) === 1 ? 1 : 2;
        $mejor = null;
        $mejorPuntos = 0;

        foreach ($this->faq() as $item) {
            $heno = $this->normalizar($item['pregunta'] . ' ' . $item['respuesta']);
            $puntos = 0;
            foreach ($palabras as $w) {
                if (str_contains($heno, $w) || str_contains($heno, $this->singular($w))) $puntos++;
            }
            if ($puntos >= $minimo && $puntos > $mejorPuntos) {
                $mejorPuntos = $puntos;
                $mejor = $item;
            }
        }

        return $mejor;
    }

    /**
     * Productos que cumplen un filtro comercial: tope de precio y/o categoria.
     *
     * Es la accion que sostiene las recomendaciones: la IA puede deducir
     * "laptop para programar, hasta S/ 2500", pero los productos SIEMPRE salen
     * de aqui. Ordena por precio descendente dentro del tope, que es lo que
     * conviene recomendar primero.
     *
     * @return Collection<int, array>
     */
    public function buscarPorFiltros(?string $texto = null, ?float $precioMax = null, int $limit = 5): Collection
    {
        $base = Product::where('project_id', $this->project->id)
            ->where('is_available', true)
            ->where('price', '>', 0);

        if ($precioMax !== null) $base->where('price', '<=', $precioMax);

        // Con texto, se acota a lo que la busqueda tolerante considere afin;
        // asi "laptop" no devuelve teclados solo porque entren en presupuesto.
        if (filled($texto)) {
            $ids = $this->buscarTolerante($texto, 40)->pluck('id')->all();
            if (empty($ids)) return collect();
            $base->whereIn('id', $ids);
        }

        return $base->orderByDesc('price')->limit($limit)
            ->get(['id', 'name', 'price', 'stock'])
            ->map(fn ($p) => [
                'id' => $p->id, 'nombre' => $p->name,
                'precio' => (float) $p->price, 'stock' => $p->stock,
            ]);
    }

    /** Normaliza texto: minúsculas, sin tildes, sin símbolos, espacios simples. */
    private function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /** Singular simple (quita 's'/'es' final) para tolerar plurales. */
    private function singular(string $w): string
    {
        if (mb_strlen($w) > 4 && str_ends_with($w, 'es')) return mb_substr($w, 0, -2);
        if (mb_strlen($w) > 3 && str_ends_with($w, 's'))  return mb_substr($w, 0, -1);
        return $w;
    }

    /** Lista corta del catálogo (para "¿qué venden?"). */
    public function catalogo(int $limit = 30): Collection
    {
        $productos = Product::where('project_id', $this->project->id)
            ->limit($limit)->get(['name', 'price'])
            ->map(fn ($p) => ['tipo' => 'producto', 'nombre' => $p->name, 'precio' => (float) $p->price]);
        $servicios = Service::where('project_id', $this->project->id)
            ->where('is_available', true)->limit($limit)->get(['name', 'price'])
            ->map(fn ($s) => ['tipo' => 'servicio', 'nombre' => $s->name, 'precio' => (float) $s->price]);
        return $productos->concat($servicios);
    }

    /** Texto plano del catálogo, para inyectar como contexto a la IA. */
    public function catalogoTexto(int $limit = 40): string
    {
        return $this->catalogo($limit)
            ->map(fn ($i) => "- {$i['nombre']} (S/ " . number_format($i['precio'], 2) . ")")
            ->implode("\n");
    }

    // ── CLIENTE E HISTORIAL ───────────────────────────────────────────
    /** Ficha del cliente por teléfono (quién es, su lead, su historial). */
    public function cliente(string $telefono): ?array
    {
        $c = Client::allProjects()->where('project_id', $this->project->id)
            ->where('phone', $telefono)->first();
        if (!$c) return null;

        return [
            'id' => $c->id, 'nombre' => $c->name, 'empresa' => $c->empresa,
            'telefono' => $c->phone, 'etapa' => $c->etapa,
            'lead_temp' => $c->lead_temp, 'lead_score' => $c->lead_score,
            'producto_interes' => $c->producto_interes,
            'ultima_actividad' => $c->ultima_actividad,
            'pedidos' => $this->pedidosDe($c->phone, 3),
        ];
    }

    // ── PEDIDOS Y ESTADO ──────────────────────────────────────────────
    /** Últimos pedidos de un teléfono (para "¿dónde está mi pedido?"). */
    public function pedidosDe(string $telefono, int $limit = 5): Collection
    {
        return Order::where('project_id', $this->project->id)
            ->where('client_phone', $telefono)
            ->latest()->limit($limit)
            ->get(['id', 'status', 'total', 'created_at'])
            ->map(fn ($o) => [
                'id' => $o->id, 'estado' => $o->status,
                'total' => (float) $o->total, 'fecha' => $o->created_at?->toDateString(),
            ]);
    }

    /** Estado de un pedido concreto por id. */
    public function pedido(int $id): ?array
    {
        $o = Order::where('project_id', $this->project->id)->find($id);
        if (!$o) return null;
        return ['id' => $o->id, 'estado' => $o->status, 'total' => (float) $o->total,
                'cliente' => $o->client_name, 'fecha' => $o->created_at?->toDateString()];
    }

    // ── BASE DE CONOCIMIENTO ──────────────────────────────────────────
    /**
     * Info del negocio para que la IA "hable con la empresa": nombre, notas
     * del proyecto, horarios/políticas guardadas en settings. MVP: lo básico
     * del proyecto; luego se amplía con documentos indexados.
     */
    public function conocimiento(): array
    {
        return [
            'negocio' => $this->project->name,
            'rubro' => $this->project->category ?? null,
            'descripcion' => $this->project->description ?? null,
        ];
    }

    // ── KPIs / DATOS DE NEGOCIO (Copilot Empresarial) ─────────────────
    /** Ventas de un período: hoy | mes | año. Devuelve total y cantidad. */
    public function ventas(string $periodo = 'mes'): array
    {
        $q = Order::where('project_id', $this->project->id)
            ->whereNotIn('status', ['anulado', 'cancelado']);
        match ($periodo) {
            'hoy' => $q->whereDate('created_at', today()),
            'año', 'anio', 'year' => $q->whereYear('created_at', now()->year),
            default => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
        };
        return [
            'periodo' => $periodo,
            'cantidad' => (clone $q)->count(),
            'total' => (float) (clone $q)->sum('total'),
        ];
    }

    /** Productos por agotarse (stock por debajo del umbral). */
    public function stockBajo(int $umbral = 5): Collection
    {
        return Product::where('project_id', $this->project->id)
            ->whereNotNull('stock')->where('stock', '<=', $umbral)
            ->orderBy('stock')
            ->get(['name', 'stock', 'price'])
            ->map(fn ($p) => ['nombre' => $p->name, 'stock' => $p->stock, 'precio' => (float) $p->price]);
    }

    /** Resumen numérico del negocio (para "dame un resumen"). */
    public function resumen(): array
    {
        return [
            'clientes' => Client::allProjects()->where('project_id', $this->project->id)->count(),
            'productos' => Product::where('project_id', $this->project->id)->count(),
            'ventas_mes' => $this->ventas('mes'),
            'stock_bajo' => $this->stockBajo()->count(),
            'leads_calientes' => Client::allProjects()->where('project_id', $this->project->id)->where('lead_temp', 'caliente')->count(),
        ];
    }

    /** Un "brief" de todo el contexto, listo para pasar a la IA como system. */
    public function briefParaIa(?string $telefono = null): string
    {
        $k = $this->conocimiento();
        // Fecha y hora REALES: sin esto la IA improvisaba la hora (decia "3 pm"
        // a medianoche) y saludaba con "buenas noches" a las 10 de la manana.
        $ahora = now()->locale('es');
        $hora = (int) $ahora->format('G');
        $franja = $hora < 5 ? 'noche (madrugada)' : ($hora < 12 ? 'mañana' : ($hora < 19 ? 'tarde' : 'noche'));
        $partes = [
            'Fecha y hora ahora mismo (Perú): ' . $ahora->isoFormat('dddd D [de] MMMM [de] YYYY, HH:mm')
                . ' — es de ' . $franja . '. Usa SIEMPRE este dato si te preguntan la hora o la fecha; nunca lo inventes.',
            "Negocio: {$k['negocio']}",
        ];
        if ($k['rubro']) $partes[] = "Rubro: {$k['rubro']}";
        $partes[] = "Catálogo (parcial):\n" . $this->catalogoTexto(20);
        if ($telefono && ($c = $this->cliente($telefono))) {
            $partes[] = "Contacto de WhatsApp: {$c['nombre']} · lead {$c['lead_temp']} ({$c['lead_score']}%)."
                . " OJO: ese es solo el nombre que la persona tiene puesto en su perfil de WhatsApp."
                . " NO es su nombre real ni el de su negocio: nunca lo uses como nombre de empresa"
                . " (\"tu ferreteria X\") ni des por hecho su rubro a partir de el. Si necesitas saber"
                . " como se llama su negocio, preguntaselo.";
        }
        return implode("\n\n", $partes);
    }

    /**
     * Contexto de DATOS para el Copilot Empresarial: KPIs reales del negocio
     * inyectados como texto para que la IA responda preguntas de gestión.
     */
    public function briefDatos(): string
    {
        $r = $this->resumen();
        $vm = $r['ventas_mes'];
        $bajo = $this->stockBajo();
        $partes = [
            "Negocio: {$this->project->name}" . ($this->project->category ? " (rubro: {$this->project->category})" : ''),
            "Ventas del mes: {$vm['cantidad']} ventas, total S/ " . number_format($vm['total'], 2),
            "Clientes registrados: {$r['clientes']} · Leads calientes: {$r['leads_calientes']}",
            "Productos en catálogo: {$r['productos']}",
        ];
        if ($bajo->isNotEmpty()) {
            $partes[] = "Productos por agotarse (stock bajo):\n" .
                $bajo->map(fn ($p) => "- {$p['nombre']}: {$p['stock']} unidades")->implode("\n");
        } else {
            $partes[] = "Ningún producto con stock bajo.";
        }
        return implode("\n\n", $partes);
    }
}

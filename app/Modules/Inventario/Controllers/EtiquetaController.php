<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\Product;
use App\Support\Qr;
use Illuminate\Http\Request;

/**
 * Hojas de etiquetas con QR para pegar en el producto o en el estante.
 *
 * Sin etiqueta no hay nada que escanear, asi que esto es la puerta de entrada
 * al inventario por codigos: primero se imprime, despues se cuenta.
 *
 * El QR lleva el SKU tal cual, no una URL. Dos razones: el escaner que ya
 * existe en Venta Express busca por `sku` o `barcode`, asi que las etiquetas
 * sirven en el acto sin tocar nada mas; y un codigo corto se imprime con
 * menos modulos, que es lo que permite leerlo en una etiqueta pequena y algo
 * borrosa de impresora termica.
 */
class EtiquetaController extends Controller
{
    /**
     * Cuantos productos se pintan en la lista.
     *
     * No es el limite de lo que se puede imprimir: para eso esta "todo lo
     * filtrado". Es solo hasta donde la pagina sigue siendo manejable con una
     * casilla por fila.
     */
    public const EN_PANTALLA = 300;

    /**
     * Tope de etiquetas por hoja (productos x copias).
     *
     * Cada etiqueta lleva su propio codigo dibujado en SVG. Pasado este punto
     * el navegador tarda mas en abrir la hoja que la impresora en sacarla, y
     * conviene partir el trabajo en tandas.
     */
    public const MAX_ETIQUETAS = 2000;

    /** Medidas de las hojas de etiqueta mas usadas, en milimetros. */
    public const FORMATOS = [
        'a4-24' => ['nombre' => 'A4 · 24 por hoja (70×37 mm)',  'cols' => 3, 'ancho' => 70, 'alto' => 37],
        'a4-40' => ['nombre' => 'A4 · 40 por hoja (52,5×29,7 mm)', 'cols' => 4, 'ancho' => 52.5, 'alto' => 29.7],
        'a4-65' => ['nombre' => 'A4 · 65 por hoja (38×21 mm)',  'cols' => 5, 'ancho' => 38, 'alto' => 21],
        'termica' => ['nombre' => 'Rollo térmico (50×30 mm)',    'cols' => 1, 'ancho' => 50, 'alto' => 30],
    ];

    /** Pantalla de seleccion: que productos y en que formato. */
    public function index(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $categorias = $project->categories()->orderBy('name')->get(['id', 'name']);

        $query = $project->products()->with(['category:id,name', 'marca:id,label']);
        if ($buscar = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$buscar}%")->orWhere('sku', 'like', "%{$buscar}%"));
        }
        if ($cat = $request->query('categoria')) {
            $query->where('category_id', $cat);
        }

        // Cuantos hay de verdad con este filtro, no cuantos caben en pantalla.
        $total = (clone $query)->count();
        $productos = $query->orderBy('name')->limit(self::EN_PANTALLA)->get();

        // Un producto sin SKU no puede llevar etiqueta: el QR quedaria vacio y
        // el escaner no tendria contra que comparar. Se avisa en vez de
        // imprimir una hoja con huecos.
        $sinCodigo = $productos->filter(fn ($p) => blank($p->sku) && blank($p->barcode))->count();

        $formatos = self::FORMATOS;

        // Muestra de la vista previa. Se usa un producto real del negocio en
        // vez de un ejemplo inventado, y el codigo se genera de verdad: una
        // silueta dibujada con CSS enganaba sobre cuanto ocupa el codigo en
        // la etiqueta, que es justo lo que hay que decidir aqui.
        $ejemplo = $productos->first(fn ($p) => filled($p->sku ?: $p->barcode));
        $muestra = [
            'codigo' => $ejemplo ? ($ejemplo->sku ?: $ejemplo->barcode) : 'SKU-001',
            'nombre' => $ejemplo->name ?? 'Producto de ejemplo',
            'precio' => $ejemplo->price ?? 0,
            'unidad' => $ejemplo->unit ?? null,
            'categoria' => $ejemplo->category->name ?? null,
            'marca' => $ejemplo->marca->label ?? null,
            'moneda' => $project->setting('currency_symbol') ?: 'S/',
        ];
        $muestra['qr'] = Qr::svg($muestra['codigo'], 120);
        $muestra['barras'] = Qr::barras($muestra['codigo'], 40, 2);

        return view('inventario::inventory.etiquetas', compact(
            'project', 'productos', 'categorias', 'formatos', 'sinCodigo', 'muestra', 'total'
        ));
    }

    /** Hoja lista para imprimir con las etiquetas de los productos elegidos. */
    public function imprimir(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $datos = $request->validate([
            'todos' => 'nullable|boolean',
            'q' => 'nullable|string|max:120',
            'categoria' => 'nullable|integer',
            'ids' => 'required_without:todos|array|min:1',
            'ids.*' => 'integer',
            'formato' => 'required|string|in:'.implode(',', array_keys(self::FORMATOS)),
            'copias' => 'nullable|integer|min:1|max:20',
            'diseno' => 'nullable|string|in:lateral,precio',
            'tipo_codigo' => 'nullable|string|in:qr,barras,ninguno',
            'campos' => 'nullable|array',
            'campos.*' => 'string|in:codigo,nombre,precio,unidad,categoria,marca',
        ]);

        // El filtro por proyecto va en la consulta, no despues: si se pidieran
        // ids de otro negocio, aqui simplemente no aparecen.
        $consulta = Product::where('project_id', $project->id)
            ->with(['category:id,name', 'marca:id,label']);

        if (! empty($datos['todos'])) {
            // "Todo lo filtrado": se repite el mismo filtro de la pantalla en
            // vez de mandar miles de ids por el formulario, que ademas chocaria
            // con el limite de campos que acepta PHP.
            if ($buscar = trim((string) ($datos['q'] ?? ''))) {
                $consulta->where(fn ($q) => $q->where('name', 'like', "%{$buscar}%")->orWhere('sku', 'like', "%{$buscar}%"));
            }
            if (! empty($datos['categoria'])) {
                $consulta->where('category_id', $datos['categoria']);
            }
        } else {
            $consulta->whereIn('id', $datos['ids']);
        }

        $productos = $consulta->orderBy('name')->get();

        $formato = self::FORMATOS[$datos['formato']];
        $copias = (int) ($datos['copias'] ?? 1);

        // Con codigo se descartan los que no lo tienen, asi que el conteo real
        // se hace sobre los imprimibles.
        $imprimibles = $productos->filter(fn ($p) => filled($p->sku ?: $p->barcode));

        if ($imprimibles->count() * $copias > self::MAX_ETIQUETAS) {
            $cuantas = $imprimibles->count() * $copias;

            return back()->with('error',
                "Son {$cuantas} etiquetas y el máximo por hoja es ".self::MAX_ETIQUETAS.". "
                .'Filtra por categoría o baja las copias, y hazlo en tandas.');
        }
        $diseno = $datos['diseno'] ?? 'lateral';
        $tipoCodigo = $datos['tipo_codigo'] ?? 'qr';

        // Marcados por el usuario. Si no llega ninguno se cae a lo minimo
        // imprescindible: una etiqueta sin codigo ni nombre no sirve de nada.
        $marcados = $datos['campos'] ?? ['codigo', 'nombre'];
        $campos = array_fill_keys($marcados, true);

        $moneda = $project->setting('currency_symbol') ?: 'S/';

        // Cada etiqueta se arma una vez y se repite: generar el mismo codigo
        // varias veces es trabajo de mas en una hoja de cientos.
        $etiquetas = [];
        foreach ($imprimibles as $p) {
            $codigo = $p->sku ?: $p->barcode;
            // Redundante con el filtro de arriba, pero se queda: si alguien
            // cambia como se arma $imprimibles, esto evita imprimir una
            // etiqueta con el codigo en blanco.
            if (blank($codigo)) {
                continue;
            }

            $imagen = match ($tipoCodigo) {
                'barras' => Qr::barras($codigo, 40, 2),
                'ninguno' => '',
                default => Qr::svg($codigo, 120),
            };

            $etiqueta = [
                'nombre' => $p->name,
                'codigo' => $codigo,
                'precio' => $p->price,
                'unidad' => $p->unit,
                'moneda' => $moneda,
                'categoria' => ($campos['categoria'] ?? false) ? ($p->category->name ?? null) : null,
                'marca' => ($campos['marca'] ?? false) ? ($p->marca->label ?? null) : null,
                'tipo_codigo' => $tipoCodigo,
                'codigo_img' => $imagen,
            ];

            for ($i = 0; $i < $copias; $i++) {
                $etiquetas[] = $etiqueta;
            }
        }

        return view('inventario::inventory.etiquetas-hoja', compact(
            'project', 'etiquetas', 'formato', 'campos', 'diseno'
        ));
    }
}

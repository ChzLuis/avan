<?php

namespace App\Support;

use App\Models\Product;

/**
 * Etiquetas comerciales y tecnicas de un producto.
 *
 * Fuente unica: aqui viven el catalogo de etiquetas, sus familias de color y
 * su prioridad. Las vistas NO deciden colores ni orden; solo pintan lo que
 * este servicio les entrega ya resuelto. Asi una etiqueta se ve igual en la
 * tarjeta, en la ficha y en cualquier plantilla, y añadir una nueva es tocar
 * un solo archivo.
 *
 * Donde se guardan
 * ----------------
 * En `products.options['etiquetas']`, la columna JSON que el producto ya
 * tiene y que ya usa este mismo patron para las tallas (`options.sizes`).
 * No hace falta migracion ni tabla nueva: son pocos datos, siempre se leen
 * junto al producto y nunca se consultan por separado. Una tabla relacional
 * añadiria un JOIN por tarjeta al catalogo sin dar nada a cambio.
 *
 * Forma guardada:
 *
 *   'etiquetas' => [
 *       'card'  => true,            // mostrar en la tarjeta
 *       'ficha' => true,            // mostrar en la ficha
 *       'claves' => ['oferta', 'producto_original'],
 *       'personalizada' => [        // opcional
 *           'texto' => 'Pago en cuotas', 'fondo' => '#0F6FCB', 'texto_color' => '#FFFFFF',
 *           'posicion' => 'izquierda', 'card' => true, 'ficha' => true, 'prioridad' => 5,
 *       ],
 *   ]
 */
final class EtiquetasProducto
{
    /** Cuantas etiquetas se ven como maximo en la tarjeta: el catalogo no se satura. */
    public const MAX_EN_CARD = 2;

    /**
     * Familias de color. Son del SISTEMA, no del negocio: una "Oferta" tiene
     * que leerse igual en todas las tiendas, o el codigo de color deja de
     * significar nada. El color del negocio manda en el resto de la tarjeta.
     *
     * Contraste comprobado sobre el fondo de cada familia (>= 4.5:1).
     */
    /* `texto_color` y no `texto` a proposito: en el catalogo `texto` ya es la
       leyenda de la etiqueta, y usar la misma clave para el color hacia que
       una pisara a la otra al fusionarlas. */
    private const FAMILIAS = [
        'violeta' => ['fondo' => '#F3E8FF', 'texto_color' => '#6B21A8'],
        'verde'   => ['fondo' => '#DCFCE7', 'texto_color' => '#15803D'],
        'rojo'    => ['fondo' => '#FEE2E2', 'texto_color' => '#B91C1C'],
        'ambar'   => ['fondo' => '#FEF3C7', 'texto_color' => '#B45309'],
        'azul'    => ['fondo' => '#DBEAFE', 'texto_color' => '#1D4ED8'],
        'gris'    => ['fondo' => '#F1F5F9', 'texto_color' => '#475569'],
    ];

    /**
     * El catalogo: clave => [etiqueta, familia, prioridad].
     *
     * La PRIORIDAD decide cual sobrevive cuando un producto tiene mas
     * etiquetas de las que caben en la tarjeta. Manda lo que mueve la compra
     * —precio y urgencia— antes que el atributo tecnico, que en la tarjeta es
     * ruido y en la ficha si aporta.
     */
    private const CATALOGO = [
        // Comercial y urgencia: lo primero que decide una compra.
        'oferta'             => ['Oferta',              'rojo',    10],
        'ultimas_unidades'   => ['Últimas unidades',    'rojo',    20],
        'stock_limitado'     => ['Stock limitado',      'ambar',   30],
        'nuevo'              => ['Nuevo',               'verde',   40],
        'mas_vendido'        => ['Más vendido',         'violeta', 50],
        'destacado'          => ['Destacado',           'violeta', 60],
        'recomendado'        => ['Recomendado',         'violeta', 70],
        'exclusivo'          => ['Exclusivo',           'violeta', 80],
        'en_promocion'       => ['En promoción',        'rojo',    90],
        'precio_especial'    => ['Precio especial',     'rojo',   100],
        'precio_mayorista'   => ['Precio mayorista',    'azul',   110],

        // Confianza: por que comprarte a ti.
        'producto_original'  => ['Producto original',   'azul',   120],
        'garantia'           => ['Garantía',            'azul',   130],
        'garantia_extendida' => ['Garantía extendida',  'azul',   140],

        // Disponibilidad y entrega.
        'disponible'         => ['Disponible',          'verde',  150],
        'stock_inmediato'    => ['Stock inmediato',     'verde',  160],
        'entrega_inmediata'  => ['Entrega inmediata',   'verde',  170],
        'envio_gratis'       => ['Envío gratis',        'verde',  180],
        'solo_online'        => ['Solo online',         'gris',   190],
        'proximamente'       => ['Próximamente',        'ambar',  200],
        'agotado'            => ['Agotado',             'gris',   210],

        // Origen.
        'importado'          => ['Importado',           'gris',   220],
        'nacional'           => ['Nacional',            'gris',   230],

        // Tecnicas: utiles en la ficha, secundarias en la tarjeta.
        'solar'              => ['Solar',               'ambar',  240],
        'industrial'         => ['Industrial',          'gris',   250],
        'ip65'               => ['IP65',                'azul',   260],
        'ip67'               => ['IP67',                'azul',   270],
        'uso_exterior'       => ['Uso exterior',        'azul',   280],
        'resistente_agua'    => ['Resistente al agua',  'azul',   290],
        'alta_potencia'      => ['Alta potencia',       'gris',   300],
        'ahorro_energetico'  => ['Ahorro energético',   'verde',  310],
        'bajo_consumo'       => ['Bajo consumo',        'verde',  320],
        'eco'                => ['Eco',                 'verde',  330],
        'tecnologia_led'     => ['Tecnología LED',      'azul',   340],
        'con_sensor'         => ['Con sensor',          'gris',   350],
        'control_remoto'     => ['Control remoto',      'gris',   360],
        'instalacion_facil'  => ['Instalación fácil',   'gris',   370],

        // Publico objetivo.
        'ideal_proyectos'    => ['Ideal para proyectos',    'gris', 380],
        'ideal_hogar'        => ['Ideal para hogar',        'gris', 390],
        'ideal_negocios'     => ['Ideal para negocios',     'gris', 400],
        'ideal_contratistas' => ['Ideal para contratistas', 'gris', 410],
        'ideal_mayoristas'   => ['Ideal para mayoristas',   'gris', 420],
    ];

    /** Posiciones permitidas sobre la foto. */
    public const POSICIONES = ['izquierda' => 'Arriba izquierda', 'derecha' => 'Arriba derecha'];

    /** Catalogo para el panel, agrupado por familia para que se elija con criterio. */
    public static function catalogo(): array
    {
        $out = [];
        foreach (self::CATALOGO as $clave => [$texto, $familia, $prioridad]) {
            $out[$clave] = [
                'clave' => $clave, 'texto' => $texto, 'familia' => $familia,
                'prioridad' => $prioridad,
            ] + self::FAMILIAS[$familia];
        }

        return $out;
    }

    public static function existe(string $clave): bool
    {
        return isset(self::CATALOGO[$clave]);
    }

    /** Lee la configuracion guardada de un producto, ya saneada. */
    public static function config(Product $producto): array
    {
        $raw = (array) data_get($producto->options, 'etiquetas', []);

        $claves = array_values(array_filter(
            array_map('strval', (array) ($raw['claves'] ?? [])),
            fn ($c) => self::existe($c)
        ));

        return [
            // Por defecto SE MUESTRAN: si el negocio se tomo el trabajo de
            // marcar una etiqueta, lo normal es que quiera verla.
            'card'  => (bool) ($raw['card'] ?? true),
            'ficha' => (bool) ($raw['ficha'] ?? true),
            'claves' => $claves,
            'personalizada' => self::saneaPersonalizada($raw['personalizada'] ?? null),
        ];
    }

    /**
     * Etiquetas listas para pintar en una superficie.
     *
     * @param  string  $donde  'card' | 'ficha'
     * @return array<int,array{texto:string,fondo:string,texto_color:string,posicion:string}>
     */
    public static function para(Product $producto, string $donde = 'card'): array
    {
        $cfg = self::config($producto);
        if (! ($cfg[$donde] ?? false)) {
            return [];
        }

        $lista = [];
        foreach ($cfg['claves'] as $clave) {
            [$texto, $familia, $prioridad] = self::CATALOGO[$clave];
            $lista[] = [
                'texto' => $texto,
                'fondo' => self::FAMILIAS[$familia]['fondo'],
                'texto_color' => self::FAMILIAS[$familia]['texto_color'],
                'posicion' => 'izquierda',
                'prioridad' => $prioridad,
            ];
        }

        // La personalizada compite por prioridad como una mas: el negocio
        // decide si su etiqueta propia gana a "Oferta" o no.
        $p = $cfg['personalizada'];
        if ($p && ($p[$donde] ?? false)) {
            $lista[] = [
                'texto' => $p['texto'],
                'fondo' => $p['fondo'],
                'texto_color' => $p['texto_color'],
                'posicion' => $p['posicion'],
                'prioridad' => $p['prioridad'],
            ];
        }

        usort($lista, fn ($a, $b) => $a['prioridad'] <=> $b['prioridad']);

        // En la tarjeta solo caben dos sin ensuciar la foto; en la ficha hay
        // sitio para todas.
        return $donde === 'card' ? array_slice($lista, 0, self::MAX_EN_CARD) : $lista;
    }

    /**
     * Normaliza lo que llega del formulario antes de guardarlo.
     *
     * Devuelve `null` cuando no hay nada util que guardar, para no dejar
     * basura en `options`.
     */
    public static function normaliza(array $entrada): ?array
    {
        $claves = array_values(array_unique(array_filter(
            array_map('strval', (array) ($entrada['claves'] ?? [])),
            fn ($c) => self::existe($c)
        )));

        $personalizada = self::saneaPersonalizada($entrada['personalizada'] ?? null);

        if (! $claves && ! $personalizada) {
            return null;
        }

        return array_filter([
            'card'  => (bool) ($entrada['card'] ?? true),
            'ficha' => (bool) ($entrada['ficha'] ?? true),
            'claves' => $claves,
            'personalizada' => $personalizada,
        ], fn ($v) => $v !== null && $v !== []);
    }

    /**
     * Sanea la etiqueta personalizada.
     *
     * Sin texto no hay etiqueta: se descarta entera. Los colores se validan
     * contra el formato hexadecimal porque acaban dentro de un atributo
     * `style` — cualquier otra cosa seria una via de inyeccion.
     */
    private static function saneaPersonalizada(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $texto = trim((string) ($raw['texto'] ?? ''));
        if ($texto === '') {
            return null;
        }

        $color = static fn ($v, $def) => is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $def;

        return [
            'texto' => mb_substr($texto, 0, 28),
            'fondo' => $color($raw['fondo'] ?? null, '#1F2937'),
            'texto_color' => $color($raw['texto_color'] ?? null, '#FFFFFF'),
            'posicion' => isset(self::POSICIONES[$raw['posicion'] ?? '']) ? $raw['posicion'] : 'izquierda',
            'card'  => (bool) ($raw['card'] ?? true),
            'ficha' => (bool) ($raw['ficha'] ?? true),
            // Por defecto va detras de las comerciales, pero el negocio puede
            // subirla si su etiqueta propia es lo mas importante del producto.
            'prioridad' => max(1, min(999, (int) ($raw['prioridad'] ?? 95))),
        ];
    }
}

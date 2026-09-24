<?php

namespace App\Modules\Ventas\Support;

/**
 * Catalogo de demos por rubro y textos de apertura para las propuestas.
 *
 * Un solo sitio: la pantalla de propuestas, la pagina publica y cualquier
 * automatismo leen de aqui. Antes la lista de demos estaba escrita a mano en
 * la vista y se habia quedado en 3 cuando ya teniamos 13.
 */
class DemosPorRubro
{
    private const BASE = 'https://arindg.com/';

    /** Demos publicadas, agrupadas por rubro. La primera es la de referencia. */
    public const DEMOS = [
        'farmacia' => [
            'etiqueta' => 'Farmacia / botica',
            'tiendas' => [
                'demofarma' => 'Botica MARFARMA',
                'demofarma2' => 'Farmacia Salud Total',
            ],
        ],
        'veterinaria' => [
            'etiqueta' => 'Veterinaria',
            'tiendas' => [
                'demovet' => 'Veterinaria Huellitas',
                'demovet2' => 'Vida Animal',
            ],
        ],
        'minimarket' => [
            'etiqueta' => 'Minimarket / abarrotes',
            'tiendas' => [
                'demomarket' => 'Minimarket La Esquina',
                'demomarket2' => 'Market Express',
            ],
        ],
        'restaurante' => [
            'etiqueta' => 'Pollería / restaurante',
            'tiendas' => [
                'demopolleria' => 'Brasas del Norte',
                'demopolleria2' => 'Sabor Criollo',
            ],
        ],
        'licoreria' => [
            'etiqueta' => 'Licorería',
            'tiendas' => [
                'demolicor' => 'Licorería El Barril',
                'demolicor2' => 'Vinos y Destilados',
            ],
        ],
        'moda' => [
            'etiqueta' => 'Ropa / boutique',
            'tiendas' => [
                'demoboutique' => 'Malva Studio',
                'demodenim' => 'Denim Studio',
            ],
        ],
        'ferreteria' => [
            'etiqueta' => 'Ferretería / eléctricos',
            'tiendas' => [
                'ferreteria-demo' => 'Distribuidores GABDE',
            ],
        ],
        'agricola' => [
            'etiqueta' => 'Agrícola / tubérculos y granos',
            'tiendas' => [
                'demoagro' => 'Agro Andino Distribuidora',
            ],
        ],
    ];


    /**
     * Clientes REALES en produccion, con su dominio propio.
     *
     * Pesan mas que una demo: el prospecto ve un negocio de verdad vendiendo,
     * no un escaparate montado para la foto. Se guardan aparte porque la URL
     * es su dominio, no `arindg.com/<slug>`, y porque en la propuesta se
     * presentan como lo que son: trabajo entregado.
     *
     * Verificados vivos el 2026-09-22. Si uno cae, se quita de aqui: enviar
     * al prospecto un enlace muerto cuesta la venta.
     */
    public const CLIENTES = [
        'ferreteria' => [
            ['url' => 'https://jaraluzcorporation.com',        'nombre' => 'Electro Jara'],
        ],
        'hogar' => [
            ['url' => 'https://megahogar.org',                 'nombre' => 'MegaHogar'],
        ],
        'tecnologia' => [
            ['url' => 'https://tienda.tecsist.net',            'nombre' => 'Tecsist Solutions'],
        ],
        'licoreria' => [
            ['url' => 'https://markethuachoexpress.arindg.com', 'nombre' => 'Market Huacho Express'],
        ],
        'moda' => [
            ['url' => 'https://babytoncito.arindg.com',        'nombre' => 'Baby Toncito'],
        ],
        'industrial' => [
            ['url' => 'https://distribuidoramuruhuaysac.com',  'nombre' => 'Distribuidora Muruhuay'],
        ],
    ];

    /** Etiqueta legible de cada rubro de cliente real. */
    public const ETIQUETAS_CLIENTES = [
        'ferreteria'  => 'Eléctricos · CLIENTE REAL',
        'hogar'       => 'Hogar y muebles · CLIENTE REAL',
        'tecnologia'  => 'Tecnología · CLIENTE REAL',
        'licoreria'   => 'Licorería · CLIENTE REAL',
        'moda'        => 'Ropa de bebé · CLIENTE REAL',
        'industrial'  => 'Iluminación y solar · CLIENTE REAL',
    ];

    /**
     * Apertura sugerida por rubro. Habla del problema del giro, no del producto:
     * una propuesta que empieza describiendo el sistema se lee como folleto.
     */
    public const APERTURAS = [
        'farmacia' => 'Sus clientes preguntan por WhatsApp si tienen un medicamento y alguien tiene que responder uno por uno. Con el catálogo en línea consultan el stock y el precio solos, y el pedido llega ya armado.',
        'veterinaria' => 'Entre consultas y atención en sala, contestar por el alimento o la vacuna quita tiempo. Con la tienda en línea el dueño de la mascota ve precios y reserva su cita sin interrumpir la atención.',
        'minimarket' => 'El pedido por WhatsApp se pierde entre mensajes y hay que confirmarlo a mano. Con el catálogo el cliente arma su canasta, ve el total y el pedido llega listo para despachar.',
        'restaurante' => 'En hora punta el teléfono no da abasto y se pierden pedidos. Con la carta en línea el cliente elige, suma su combo y envía el pedido completo, sin equivocaciones.',
        'licoreria' => 'La mayoría de consultas son precio y si hay stock, y siempre llegan a la misma hora. Con el catálogo el cliente resuelve solo y usted atiende el reparto.',
        'moda' => 'Mandar fotos una por una por WhatsApp cansa y no muestra la colección completa. Con la tienda el cliente ve todo, filtra por talla y separa lo que le gusta.',
        'ferreteria' => 'Cotizar cada consulta a mano toma tiempo y se repite. Con el catálogo el cliente arma su lista, la envía como cotización y usted solo confirma precios.',
        'hogar' => 'Los muebles y electrodomésticos se deciden viendo: medidas, acabados y precio. Mandar fotos sueltas por WhatsApp no cierra la venta; con la tienda el cliente compara y reserva.',
        'tecnologia' => 'En tecnología el cliente compara precio y especificaciones antes de decidir. Con el catálogo en línea ve stock y características al detalle, sin que usted repita lo mismo cada vez.',
        'agricola' => 'El precio cambia cada mañana y usted lo repite por teléfono cliente por cliente. Con el catálogo en línea publica el precio del día una sola vez, y el comprador arma su pedido por saco sabiendo la procedencia y el calibre.',
        'industrial' => 'La venta industrial arranca con una cotización, no con un impulso. Con el catálogo el cliente arma su requerimiento, lo envía completo y usted cotiza sobre algo concreto.',
    ];

    /** Motivo del plan sugerido, por rubro. */
    public const MOTIVOS = [
        'farmacia' => 'Por el volumen de productos y la necesidad de mostrar stock y precio actualizados.',
        'veterinaria' => 'Porque combina tienda y servicios: alimento, farmacia veterinaria y reserva de citas.',
        'minimarket' => 'Por la cantidad de productos y porque el pedido con delivery necesita carrito y total automático.',
        'restaurante' => 'Porque la carta cambia y necesita combos, guarniciones y pedido directo a WhatsApp.',
        'licoreria' => 'Por el catálogo por tipo de bebida y el horario de entrega nocturna.',
        'moda' => 'Porque la ropa necesita tallas, colores y fotos grandes para que el cliente decida.',
        'ferreteria' => 'Porque el catálogo es amplio y el cliente cotiza antes de comprar.',
        'hogar' => 'Porque cada producto necesita varias fotos, medidas y ficha, y el cliente compara antes de decidir.',
        'tecnologia' => 'Porque las fichas técnicas y el stock cambian seguido, y el cliente los consulta antes de comprar.',
        'agricola' => 'Porque se vende por saco y al por menor a la vez, y el precio del día tiene que poder cambiarse rápido sin rehacer el catálogo.',
        'industrial' => 'Porque la venta va por cotización y el catálogo tiene que soportar pedidos por volumen.',
    ];

    /** Todas las demos como lista plana para un `datalist` o un selector. */
    public static function opciones(): array
    {
        $salida = [];

        /* Los clientes REALES van primero: son el mejor argumento que hay y
           antes no aparecian en la lista, asi que se mandaban propuestas solo
           con demos pudiendo ensenar negocios funcionando. */
        foreach (self::CLIENTES as $rubro => $tiendas) {
            foreach ($tiendas as $t) {
                $salida[] = [
                    'url' => $t['url'],
                    'nombre' => $t['nombre'],
                    'rubro' => $rubro,
                    'etiqueta' => self::ETIQUETAS_CLIENTES[$rubro] ?? 'Cliente real',
                    'real' => true,
                ];
            }
        }

        foreach (self::DEMOS as $rubro => $datos) {
            foreach ($datos['tiendas'] as $slug => $nombre) {
                $salida[] = [
                    'url' => self::BASE.$slug,
                    'nombre' => $nombre,
                    'rubro' => $rubro,
                    'etiqueta' => $datos['etiqueta'],
                    'real' => false,
                ];
            }
        }

        return $salida;
    }

    /** Solo los clientes reales, para pintarlos aparte de las demos. */
    public static function clientesReales(): array
    {
        return array_values(array_filter(self::opciones(), fn ($o) => $o['real'] ?? false));
    }

    /** Demos del rubro indicado; vacio si el rubro no tiene demo propia. */
    public static function delRubro(?string $rubro): array
    {
        $clave = self::normalizar($rubro);

        if ($clave === null) {
            return [];
        }

        $salida = [];
        // Un cliente real del mismo rubro se ensena antes que la demo.
        foreach (self::CLIENTES[$clave] ?? [] as $t) {
            $salida[] = ['url' => $t['url'], 'nombre' => $t['nombre']];
        }
        foreach (self::DEMOS[$clave]['tiendas'] ?? [] as $slug => $nombre) {
            $salida[] = ['url' => self::BASE.$slug, 'nombre' => $nombre];
        }

        return $salida;
    }

    public static function apertura(?string $rubro): ?string
    {
        $clave = self::normalizar($rubro);

        return $clave ? (self::APERTURAS[$clave] ?? null) : null;
    }

    public static function motivo(?string $rubro): ?string
    {
        $clave = self::normalizar($rubro);

        return $clave ? (self::MOTIVOS[$clave] ?? null) : null;
    }

    /**
     * El rubro se escribe a mano en la propuesta ("Botica", "farmacias",
     * "Veterinaria"), asi que no se puede casar por igualdad exacta.
     */
    public static function normalizar(?string $rubro): ?string
    {
        $texto = mb_strtolower(trim((string) $rubro));

        if ($texto === '') {
            return null;
        }

        // Sin tildes: quien escribe el rubro pone "Pollería" o "Ferretería" y
        // las pistas se guardan sin acento. Comparar tal cual no casaba.
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        $sinonimos = [
            'farmacia' => ['farmacia', 'botica', 'droguer'],
            'veterinaria' => ['veterinar', 'mascota', 'pet'],
            'minimarket' => ['minimarket', 'market', 'abarrote', 'bodega'],
            'restaurante' => ['restaur', 'polleri', 'pollo', 'menu', 'comida', 'cevicher'],
            'licoreria' => ['licor', 'vino', 'cervez', 'destilad'],
            'moda' => ['moda', 'ropa', 'boutique', 'jean', 'denim', 'calzado', 'zapat', 'bebe'],
            'ferreteria' => ['ferreter', 'electric', 'construc'],
            /* Rubros que hoy solo tienen CLIENTE REAL, sin demo propia. Sin
               estos, un prospecto de muebles o de celulares no recibia
               ninguna tienda parecida a la suya. */
            'hogar' => ['hogar', 'mueble', 'colchon', 'decorac', 'electrodomest'],
            'tecnologia' => ['tecnolog', 'celular', 'computo', 'computad', 'laptop', 'informat'],
            'agricola' => ['agricol', 'agro', 'tuberc', 'papa', 'grano', 'menestr', 'chacra', 'cosech', 'semill'],
            'industrial' => ['industrial', 'iluminac', 'solar', 'reflector', 'pastoral'],
        ];

        foreach ($sinonimos as $clave => $pistas) {
            foreach ($pistas as $pista) {
                if (str_contains($texto, $pista)) {
                    return $clave;
                }
            }
        }

        return null;
    }
}

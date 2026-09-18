<?php

namespace App\Modules\Catalogo\Support;

/**
 * Las unidades de medida con las que se vende en Perú.
 *
 * Es la tabla que usa SUNAT (catálogo 3) y la que todo el mundo espera ver
 * desplegada al dar de alta un producto: no es lo mismo vender por KILOGRAMO
 * que por CAJA, por MILLAR o por METRO CUADRADO, y hasta ahora el campo era
 * texto libre — cada quien escribía "kg", "Kg.", "kilo" o "KILOGRAMO" y el
 * catálogo acababa sin forma de agrupar ni de comparar nada.
 *
 * Se guarda el nombre, que es lo que ya vivía en `products.unit` y lo que lee
 * la tienda ("en CAJAs"). El código SUNAT (NIU, KGM, BX…) todavía NO está
 * aquí: la facturación electrónica lo exige y hoy manda el texto tal cual con
 * 'NIU' de reserva, así que hay que traer la tabla oficial y cotejarla antes
 * de emitir con ella. Inventarse un código haría que SUNAT rechazara la
 * factura, que es peor que el valor por defecto de ahora.
 */
final class UnidadesMedida
{
    /**
     * Las cinco de siempre primero, el resto alfabético.
     *
     * Quien vende por unidad o por kilo no tiene que recorrer sesenta líneas
     * para encontrarlo, y quien busca MILIMETRO CUBICO sabe dónde mirar.
     */
    public const FRECUENTES = [
        'UNIDAD',
        'KILOGRAMO',
        'CAJA',
        'PAQUETE',
        'TONELADAS',
    ];

    public const RESTO = [
        'BALDE',
        'BARRILES',
        'BOBINAS',
        'BOLSA',
        'BOTELLAS',
        'CARTONES',
        'CENTIMETRO CUADRADO',
        'CENTIMETRO CUBICO',
        'CENTIMETRO LINEAL',
        'CIENTO DE UNIDADES',
        'CILINDRO',
        'CONOS',
        'DOCENA',
        'DOCENA POR 10**6',
        'FARDO',
        'GALON INGLES (4,545956L)',
        'GRAMO',
        'GRUESA',
        'HECTOLITRO',
        'HOJA',
        'JUEGO',
        'KILOMETRO',
        'KILOVATIO HORA',
        'KIT',
        'LATAS',
        'LIBRAS',
        'LITRO',
        'MEGAWATT HORA',
        'METRO',
        'METRO CUADRADO',
        'METRO CUBICO',
        'MILIGRAMOS',
        'MILILITRO',
        'MILIMETRO',
        'MILIMETRO CUADRADO',
        'MILIMETRO CUBICO',
        'MILLARES',
        'MILLON DE UNIDADES',
        'ONZAS',
        'PALETAS',
        'PAR',
        'PIE TABLAR',
        'PIES',
        'PIES CUADRADOS',
        'PIES CUBICOS',
        'PIEZAS',
        'PLACAS',
        'PLIEGO',
        'PULGADAS',
        'RESMA',
        'TAMBOR',
        'TONELADA CORTA',
        'TONELADA LARGA',
        'TUBOS',
        'US GALON (3,7843 L)',
        'YARDA',
        'YARDA CUADRADA',
    ];

    /** @return list<string> */
    public static function todas(): array
    {
        return array_merge(self::FRECUENTES, self::RESTO);
    }

    /**
     * Presentaciones propias de un rubro.
     *
     * Un pollo a la brasa no se vende por KILOGRAMO sino por «1/4 pollo», y una
     * peluquería por «sesión». No sustituyen a las unidades de medida: se
     * ofrecen antes, y debajo sigue estando la tabla completa.
     *
     * @return list<string>
     */
    public static function presentacionesDe(?string $rubro): array
    {
        return match (true) {
            in_array($rubro, ['restaurante', 'cafeteria'], true) => [
                'Plato', 'Porción', '1/4 pollo', '1/2 pollo', 'Pollo entero',
                'Ración', 'Combo', 'Bandeja', 'Para llevar',
            ],
            in_array($rubro, ['peluqueria', 'salon_belleza'], true) => [
                'Por sesión', 'Por hora', 'Tratamiento completo', 'Paquete', 'Media sesión',
            ],
            $rubro === 'clinica' => ['Consulta', 'Sesión', 'Paquete sesiones', 'Control', 'Emergencia'],
            $rubro === 'veterinaria' => ['Dosis', 'Consulta', 'Frasco', 'Sobre'],
            $rubro === 'gimnasio' => ['Mensual', 'Trimestral', 'Semestral', 'Anual', 'Por clase', 'Por sesión'],
            $rubro === 'educacion' => ['Mensual', 'Por ciclo', 'Por módulo', 'Anual', 'Por sesión', 'Presencial', 'Virtual'],
            default => [],
        };
    }

    /**
     * Todo lo que puede elegir un negocio, sin repetidos y en orden de utilidad:
     * lo suyo primero, la tabla oficial después.
     *
     * @param  list<string>  $propias  las que el negocio haya creado en su catálogo
     * @return array<string,list<string>>  grupo => unidades
     */
    public static function paraNegocio(?string $rubro, array $propias = []): array
    {
        $grupos = [];
        $vistas = [];

        $añadir = function (string $titulo, array $lista) use (&$grupos, &$vistas) {
            $limpias = [];
            foreach ($lista as $u) {
                $u = trim((string) $u);
                $clave = mb_strtoupper($u);
                if ($u === '' || isset($vistas[$clave])) {
                    continue;
                }
                $vistas[$clave] = true;
                $limpias[] = $u;
            }
            if ($limpias !== []) {
                $grupos[$titulo] = $limpias;
            }
        };

        $añadir('Tus unidades', $propias);
        $añadir('Presentaciones de tu rubro', self::presentacionesDe($rubro));
        $añadir('Más usadas', self::FRECUENTES);
        $añadir('Unidades de medida', self::RESTO);

        return $grupos;
    }
}

<?php

namespace App\Support\Sunat;

/**
 * Los códigos que SUNAT exige en el XML de cada comprobante.
 *
 * No son adorno: si el código no está en la tabla oficial, SUNAT rechaza el
 * documento o lo acepta con observaciones. Hasta ahora el sistema mandaba el
 * texto que el usuario había escrito en el producto —"CAJA" donde SUNAT espera
 * "BX", "kg" donde espera "KGM"— con 'NIU' como red de seguridad.
 *
 * Todo lo de aquí está cotejado con la documentación oficial del Anexo N.º 8
 * (catálogos 01, 03, 06, 07, 09, 10 y 20). Cuando una unidad no tiene código
 * —"PIE TABLAR" aparece en algunos ERP pero NO está en el catálogo 03— se cae
 * al valor por defecto en vez de inventarse uno: una factura rechazada cuesta
 * más que una unidad genérica.
 */
final class Catalogos
{
    /** Catálogo 03 — unidad de medida. Las 62 del Anexo N.º 8. */
    public const UNIDADES = [
        'NIU' => 'UNIDAD (BIENES)',
        'ZZ'  => 'UNIDAD (SERVICIOS)',
        'KGM' => 'KILOGRAMO',
        'BX'  => 'CAJA',
        'PK'  => 'PAQUETE',
        'TNE' => 'TONELADAS',
        '4A'  => 'BOBINAS',
        'BJ'  => 'BALDE',
        'BLL' => 'BARRILES',
        'BG'  => 'BOLSA',
        'BO'  => 'BOTELLAS',
        'CT'  => 'CARTONES',
        'CMK' => 'CENTIMETRO CUADRADO',
        'CMQ' => 'CENTIMETRO CUBICO',
        'CMT' => 'CENTIMETRO LINEAL',
        'CEN' => 'CIENTO DE UNIDADES',
        'CY'  => 'CILINDRO',
        'CJ'  => 'CONOS',
        'DZN' => 'DOCENA',
        'DZP' => 'DOCENA POR 10**6',
        'BE'  => 'FARDO',
        'GLI' => 'GALON INGLES (4,545956L)',
        'GRM' => 'GRAMO',
        'GRO' => 'GRUESA',
        'HLT' => 'HECTOLITRO',
        'LEF' => 'HOJA',
        'SET' => 'JUEGO',
        'KTM' => 'KILOMETRO',
        'KWH' => 'KILOVATIO HORA',
        'KT'  => 'KIT',
        'CA'  => 'LATAS',
        'LBR' => 'LIBRAS',
        'LTR' => 'LITRO',
        'MWH' => 'MEGAWATT HORA',
        'MTR' => 'METRO',
        'MTK' => 'METRO CUADRADO',
        'MTQ' => 'METRO CUBICO',
        'MGM' => 'MILIGRAMOS',
        'MLT' => 'MILILITRO',
        'MMT' => 'MILIMETRO',
        'MMK' => 'MILIMETRO CUADRADO',
        'MMQ' => 'MILIMETRO CUBICO',
        'MLL' => 'MILLARES',
        'UM'  => 'MILLON DE UNIDADES',
        'ONZ' => 'ONZAS',
        'PF'  => 'PALETAS',
        'PR'  => 'PAR',
        'FOT' => 'PIES',
        'FTK' => 'PIES CUADRADOS',
        'FTQ' => 'PIES CUBICOS',
        'C62' => 'PIEZAS',
        'PG'  => 'PLACAS',
        'ST'  => 'PLIEGO',
        'INH' => 'PULGADAS',
        'RM'  => 'RESMA',
        'DR'  => 'TAMBOR',
        'STN' => 'TONELADA CORTA',
        'LTN' => 'TONELADA LARGA',
        'TU'  => 'TUBOS',
        'GLL' => 'US GALON (3,7843 L)',
        'YRD' => 'YARDA',
        'YDK' => 'YARDA CUADRADA',
    ];

    /**
     * Cómo escribe la gente lo que ya está en la tabla.
     *
     * Nadie teclea "KILOGRAMO" en la ficha de un producto: escribe "kg", "Kilo"
     * o "kilos". Sin estos alias, todo eso acabaría en NIU y la factura diría
     * que se vendieron 3 unidades de arroz en vez de 3 kilos.
     */
    private const ALIAS = [
        'kg' => 'KGM', 'kgs' => 'KGM', 'kilo' => 'KGM', 'kilos' => 'KGM', 'kilogramos' => 'KGM',
        'g' => 'GRM', 'gr' => 'GRM', 'grs' => 'GRM', 'gramos' => 'GRM',
        'mg' => 'MGM', 'miligramo' => 'MGM',
        'l' => 'LTR', 'lt' => 'LTR', 'lts' => 'LTR', 'litros' => 'LTR',
        'ml' => 'MLT', 'mililitros' => 'MLT',
        'm' => 'MTR', 'mt' => 'MTR', 'mts' => 'MTR', 'metros' => 'MTR',
        'm2' => 'MTK', 'm²' => 'MTK', 'metro2' => 'MTK',
        'm3' => 'MTQ', 'm³' => 'MTQ', 'metro3' => 'MTQ',
        'cm' => 'CMT', 'cm2' => 'CMK', 'cm3' => 'CMQ',
        'mm' => 'MMT', 'mm2' => 'MMK', 'mm3' => 'MMQ',
        'km' => 'KTM', 'kilometros' => 'KTM',
        'und' => 'NIU', 'un' => 'NIU', 'u' => 'NIU', 'unid' => 'NIU',
        'unidades' => 'NIU', 'pieza' => 'C62', 'piezas' => 'C62', 'pza' => 'C62', 'pzas' => 'C62',
        'caja' => 'BX', 'cajas' => 'BX', 'cja' => 'BX',
        'paquete' => 'PK', 'paquetes' => 'PK', 'pack' => 'PK', 'paq' => 'PK',
        'bolsa' => 'BG', 'bolsas' => 'BG',
        'botella' => 'BO', 'botellas' => 'BO', 'bot' => 'BO',
        'docena' => 'DZN', 'docenas' => 'DZN', 'dz' => 'DZN', 'dzn' => 'DZN',
        'par' => 'PR', 'pares' => 'PR',
        'ciento' => 'CEN', 'millar' => 'MLL', 'millares' => 'MLL',
        'lata' => 'CA', 'latas' => 'CA',
        'litro' => 'LTR', 'galon' => 'GLL', 'galones' => 'GLL',
        'tonelada' => 'TNE', 'toneladas' => 'TNE', 'tn' => 'TNE', 't' => 'TNE',
        'libra' => 'LBR', 'libras' => 'LBR', 'lb' => 'LBR',
        'onza' => 'ONZ', 'onzas' => 'ONZ', 'oz' => 'ONZ',
        'pulgada' => 'INH', 'pulgadas' => 'INH',
        'pie' => 'FOT', 'pies' => 'FOT',
        'yarda' => 'YRD', 'yardas' => 'YRD',
        'rollo' => 'NIU', 'juego' => 'SET', 'juegos' => 'SET', 'set' => 'SET',
        'kit' => 'KT', 'kits' => 'KT',
        'resma' => 'RM', 'resmas' => 'RM',
        'balde' => 'BJ', 'baldes' => 'BJ',
        'saco' => 'BG', 'sacos' => 'BG',
        'servicio' => 'ZZ', 'servicios' => 'ZZ', 'hora' => 'ZZ', 'horas' => 'ZZ',
        'sesion' => 'ZZ', 'sesión' => 'ZZ', 'consulta' => 'ZZ', 'mensual' => 'ZZ',
    ];

    /** Catálogo 06 — tipo de documento de identidad del receptor. */
    public const DOCUMENTOS_IDENTIDAD = [
        '1' => 'DNI',
        '6' => 'RUC',
        '4' => 'CARNET DE EXTRANJERIA',
        '7' => 'PASAPORTE',
        'A' => 'CEDULA DIPLOMATICA DE IDENTIDAD',
        'B' => 'DOC. DE IDENTIDAD DEL PAIS DE ORIGEN',
        'F' => 'PERMISO TEMPORAL DE PERMANENCIA',
        '0' => 'DOC. TRIB. NO DOMICILIADO SIN RUC',
    ];

    /** Catálogo 01 — tipo de comprobante. */
    public const COMPROBANTES = [
        '01' => 'FACTURA',
        '03' => 'BOLETA DE VENTA',
        '07' => 'NOTA DE CREDITO',
        '08' => 'NOTA DE DEBITO',
        '09' => 'GUIA DE REMISION REMITENTE',
        '31' => 'GUIA DE REMISION TRANSPORTISTA',
    ];

    /** Catálogo 09 — por qué se emite una nota de crédito. */
    public const MOTIVOS_NOTA_CREDITO = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '03' => 'Corrección por error en la descripción',
        '04' => 'Descuento global',
        '05' => 'Descuento por ítem',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '08' => 'Bonificación',
        '09' => 'Disminución en el valor',
        '10' => 'Otros conceptos',
        '11' => 'Ajustes de operaciones de exportación',
        '12' => 'Ajustes afectos al IVAP',
        '13' => 'Corrección del monto neto pendiente de pago y/o fechas de vencimiento',
    ];

    /** Catálogo 10 — por qué se emite una nota de débito. */
    public const MOTIVOS_NOTA_DEBITO = [
        '01' => 'Intereses por mora',
        '02' => 'Aumento en el valor',
        '03' => 'Penalidades / otros conceptos',
        '11' => 'Ajustes de operaciones de exportación',
        '12' => 'Ajustes afectos al IVAP',
    ];

    /** Catálogo 20 — por qué se trasladan los bienes (guía de remisión). */
    public const MOTIVOS_TRASLADO = [
        '01' => 'Venta',
        '02' => 'Compra',
        '03' => 'Venta con entrega a terceros',
        '04' => 'Traslado entre establecimientos de la misma empresa',
        '05' => 'Consignación',
        '06' => 'Devolución',
        '07' => 'Recojo de bienes transformados',
        '08' => 'Importación',
        '09' => 'Exportación',
        '13' => 'Otros',
        '14' => 'Venta sujeta a confirmación del comprador',
        '17' => 'Traslado de bienes para transformación',
        '18' => 'Traslado por emisor itinerante de comprobantes de pago',
        '19' => 'Traslado de mercancía extranjera',
    ];

    /** Cómo se traslada: con transportista (público) o con vehículo propio. */
    public const MODALIDADES_TRASLADO = [
        '01' => 'Transporte público',
        '02' => 'Transporte privado',
    ];

    /** Catálogo 07 — afectación del IGV de cada línea. */
    public const AFECTACION_IGV = [
        '10' => 'Gravado - Operación onerosa',
        '20' => 'Exonerado - Operación onerosa',
        '30' => 'Inafecto - Operación onerosa',
        '40' => 'Exportación de bienes o servicios',
    ];

    /**
     * El código de unidad que va en el XML.
     *
     * Acepta lo que sea que tenga guardado el producto: el código ya correcto,
     * el nombre completo de la tabla, o la abreviatura que se escribió a mano.
     * Lo que no reconoce cae en el valor por defecto —NIU para bienes— porque
     * un código inventado hace que SUNAT rechace el comprobante entero.
     */
    /**
     * Etiqueta CORTA para imprimir en la columna UM del comprobante. La tabla
     * oficial dice "UNIDAD (BIENES)": en una columna de 5 mm eso se parte en
     * dos lineas y no aporta nada. Lo que no este aqui cae al nombre oficial.
     */
    public static function etiquetaUnidad(?string $codigo): string
    {
        $c = self::codigoUnidad($codigo);

        return self::ETIQUETAS_CORTAS[$c]
            ?? mb_convert_case(mb_strtolower(self::UNIDADES[$c] ?? $c), MB_CASE_TITLE);
    }

    /** Las que de verdad se usan en un mostrador, en el orden en que se buscan. */
    public static function unidadesComunes(): array
    {
        $salida = [];
        foreach (['NIU', 'ZZ', 'BX', 'PK', 'KGM', 'MTR', 'LTR', 'GLL', 'DZN', 'SET', 'BG', 'PR', 'MLL', 'C62'] as $c) {
            if (isset(self::UNIDADES[$c])) {
                $salida[$c] = self::etiquetaUnidad($c);
            }
        }

        return $salida;
    }

    private const ETIQUETAS_CORTAS = [
        'NIU' => 'Unidad', 'ZZ' => 'Servicio', 'BX' => 'Caja', 'PK' => 'Paquete',
        'KGM' => 'kg', 'GRM' => 'g', 'TNE' => 't', 'MTR' => 'm', 'CMT' => 'cm', 'MMT' => 'mm',
        'MTK' => 'm²', 'MTQ' => 'm³', 'LTR' => 'L', 'MLT' => 'mL', 'GLL' => 'Galón',
        'DZN' => 'Docena', 'SET' => 'Juego', 'BG' => 'Bolsa', 'PR' => 'Par', 'MLL' => 'Millar',
        'CEN' => 'Ciento', 'C62' => 'Pieza', 'KT' => 'Kit', 'BO' => 'Botella', 'CA' => 'Lata',
        'LBR' => 'lb', 'ONZ' => 'oz', 'FOT' => 'pie', 'KTM' => 'km', 'KWH' => 'kWh',
    ];

    public static function codigoUnidad(?string $texto, string $porDefecto = 'NIU'): string
    {
        $t = trim((string) $texto);

        if ($t === '') {
            return $porDefecto;
        }

        // Ya es un código de la tabla ("KGM", "niu").
        $mayus = mb_strtoupper($t);
        if (isset(self::UNIDADES[$mayus])) {
            return $mayus;
        }

        // Es el nombre completo tal como se ofrece en el catálogo ("KILOGRAMO").
        $porNombre = array_search($mayus, self::UNIDADES, true);
        if ($porNombre !== false) {
            return $porNombre;
        }

        // Es como lo escribe la gente ("kg", "cajas", "Docena").
        $normal = self::normalizar($t);
        if (isset(self::ALIAS[$normal])) {
            return self::ALIAS[$normal];
        }

        // Sin punto final ni plural: "kg." y "cajas" llegan al mismo sitio.
        $sinPlural = rtrim($normal, 's');
        if (isset(self::ALIAS[$sinPlural])) {
            return self::ALIAS[$sinPlural];
        }

        return $porDefecto;
    }

    /** El código de documento de identidad que va en el XML. */
    public static function codigoDocumentoIdentidad(?string $tipo, ?string $numero = null): string
    {
        $t = self::normalizar((string) $tipo);

        $porTipo = match (true) {
            in_array($t, ['dni', 'd.n.i', 'documento nacional de identidad'], true) => '1',
            in_array($t, ['ruc', 'r.u.c'], true) => '6',
            in_array($t, ['ce', 'c.e', 'carnet de extranjeria', 'carne de extranjeria', 'carnet extranjeria'], true) => '4',
            in_array($t, ['pasaporte', 'passport'], true) => '7',
            isset(self::DOCUMENTOS_IDENTIDAD[mb_strtoupper((string) $tipo)]) => mb_strtoupper((string) $tipo),
            default => null,
        };

        if ($porTipo !== null) {
            return $porTipo;
        }

        /* Sin tipo declarado se deduce del número, que es lo único fiable que
           queda: 11 dígitos es RUC y 8 es DNI. Un comprobante con el tipo
           equivocado lo rechaza SUNAT aunque el número esté bien. */
        $n = preg_replace('/\D/', '', (string) $numero);

        return match (strlen((string) $n)) {
            11 => '6',
            8  => '1',
            default => '1',
        };
    }

    /** El código de comprobante que va en el XML, a partir del tipo interno. */
    public static function codigoComprobante(string $tipoInterno): string
    {
        return match ($tipoInterno) {
            'factura'      => '01',
            'boleta'       => '03',
            'nota_credito' => '07',
            'nota_debito'  => '08',
            'guia_remision', 'guia' => '09',
            default        => '01',
        };
    }

    /** Minúsculas, sin tildes y sin puntuación, para comparar lo escrito a mano. */
    private static function normalizar(string $t): string
    {
        $t = mb_strtolower(trim($t));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $t = str_replace(['.', ',', '(', ')', '/'], '', $t);

        return trim(preg_replace('/\s+/', ' ', $t));
    }
}

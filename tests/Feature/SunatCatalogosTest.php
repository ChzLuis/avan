<?php

namespace Tests\Feature;

use App\Support\Sunat\Catalogos;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Los códigos que viajan a SUNAT.
 *
 * El sistema mandaba el texto que el usuario había escrito en el producto:
 * "CAJA" donde SUNAT espera "BX", "kg" donde espera "KGM". Un código que no
 * está en la tabla oficial hace que rechacen el comprobante entero, así que
 * esto no es cosmética: es la diferencia entre facturar y no facturar.
 */
class SunatCatalogosTest extends TestCase
{
    /** La tabla es la oficial: 62 unidades, sin repetidos. */
    public function test_el_catalogo_de_unidades_esta_completo(): void
    {
        $this->assertCount(62, Catalogos::UNIDADES, 'el catálogo 03 tiene 62 unidades');
        $this->assertSame(
            count(Catalogos::UNIDADES),
            count(array_unique(Catalogos::UNIDADES)),
            'hay dos códigos con el mismo nombre'
        );

        // Los que más se usan, tal como los publica SUNAT.
        $this->assertSame('UNIDAD (BIENES)', Catalogos::UNIDADES['NIU']);
        $this->assertSame('UNIDAD (SERVICIOS)', Catalogos::UNIDADES['ZZ']);
        $this->assertSame('PIEZAS', Catalogos::UNIDADES['C62']);
        $this->assertSame('BOBINAS', Catalogos::UNIDADES['4A']);
        $this->assertSame('MILLARES', Catalogos::UNIDADES['MLL']);
    }

    #[DataProvider('unidades')]
    public function test_traduce_lo_que_haya_escrito_el_negocio(string $escrito, string $esperado): void
    {
        $this->assertSame($esperado, Catalogos::codigoUnidad($escrito), '"'.$escrito.'" debería viajar como '.$esperado);
    }

    public static function unidades(): array
    {
        return [
            // El nombre tal como lo ofrece el catálogo del editor de productos.
            'nombre de la tabla'     => ['KILOGRAMO', 'KGM'],
            'nombre en minúscula'    => ['kilogramo', 'KGM'],
            'caja'                   => ['CAJA', 'BX'],
            'millares'               => ['MILLARES', 'MLL'],
            'metro cuadrado'         => ['METRO CUADRADO', 'MTK'],

            // El código, si ya venía bien.
            'código directo'         => ['KGM', 'KGM'],
            'código en minúscula'    => ['niu', 'NIU'],

            // Lo que la gente escribe de verdad.
            'kg'                     => ['kg', 'KGM'],
            'Kilos'                  => ['Kilos', 'KGM'],
            'kg con punto'           => ['kg.', 'KGM'],
            'und'                    => ['und', 'NIU'],
            'unidades'               => ['unidades', 'NIU'],
            'cajas en plural'        => ['cajas', 'BX'],
            'docena'                 => ['Docena', 'DZN'],
            'par'                    => ['par', 'PR'],
            'litros'                 => ['lts', 'LTR'],
            'm2'                     => ['m2', 'MTK'],
            'metro cúbico corto'     => ['m3', 'MTQ'],
            'piezas'                 => ['pzas', 'C62'],

            // Un servicio no se mide en unidades de bien.
            'servicio'               => ['servicio', 'ZZ'],
            'por sesión'             => ['sesión', 'ZZ'],

            // Lo desconocido cae en el valor por defecto, nunca inventa código.
            'inventada'              => ['bidón de obra', 'NIU'],
            'pie tablar sin código'  => ['PIE TABLAR', 'NIU'],
            'vacía'                  => ['', 'NIU'],
        ];
    }

    /** Ningún alias puede apuntar a un código que no existe en la tabla. */
    public function test_ninguna_traduccion_apunta_a_un_codigo_inventado(): void
    {
        $malos = [];

        foreach (self::unidades() as $caso) {
            $codigo = Catalogos::codigoUnidad($caso[0]);
            if (! isset(Catalogos::UNIDADES[$codigo])) {
                $malos[] = $caso[0].' → '.$codigo;
            }
        }

        $this->assertSame([], $malos, 'estas traducciones dan un código que SUNAT no conoce');
    }

    #[DataProvider('documentos')]
    public function test_el_documento_del_cliente_viaja_con_su_codigo(?string $tipo, ?string $numero, string $esperado): void
    {
        $this->assertSame($esperado, Catalogos::codigoDocumentoIdentidad($tipo, $numero));
    }

    public static function documentos(): array
    {
        return [
            'DNI'                  => ['DNI', '45678912', '1'],
            'RUC'                  => ['RUC', '20600819110', '6'],
            'carnet extranjería'   => ['CE', 'X123456', '4'],
            'pasaporte'            => ['pasaporte', 'AB12345', '7'],
            'minúsculas'           => ['dni', '45678912', '1'],
            'ya venía el código'   => ['6', '20600819110', '6'],
            // Sin tipo se deduce del número: 11 dígitos solo puede ser RUC.
            'sin tipo, 11 dígitos' => [null, '20600819110', '6'],
            'sin tipo, 8 dígitos'  => [null, '45678912', '1'],
        ];
    }

    /** Cada tipo de comprobante tiene su código del catálogo 01. */
    public function test_cada_comprobante_lleva_su_codigo(): void
    {
        $this->assertSame('01', Catalogos::codigoComprobante('factura'));
        $this->assertSame('03', Catalogos::codigoComprobante('boleta'));
        $this->assertSame('07', Catalogos::codigoComprobante('nota_credito'));
        $this->assertSame('08', Catalogos::codigoComprobante('nota_debito'));
        $this->assertSame('09', Catalogos::codigoComprobante('guia_remision'));
    }

    /** Los motivos de nota son los del Anexo N.º 8, no una lista propia. */
    public function test_los_motivos_de_nota_son_los_oficiales(): void
    {
        $this->assertSame('Anulación de la operación', Catalogos::MOTIVOS_NOTA_CREDITO['01']);
        $this->assertSame('Devolución total', Catalogos::MOTIVOS_NOTA_CREDITO['06']);
        $this->assertCount(13, Catalogos::MOTIVOS_NOTA_CREDITO);

        $this->assertSame('Intereses por mora', Catalogos::MOTIVOS_NOTA_DEBITO['01']);
        $this->assertCount(5, Catalogos::MOTIVOS_NOTA_DEBITO);

        // El 04 del catálogo 20 es el traslado entre locales del mismo negocio,
        // que es el motivo más frecuente y el más fácil de confundir.
        $this->assertSame('Traslado entre establecimientos de la misma empresa', Catalogos::MOTIVOS_TRASLADO['04']);
        $this->assertSame('Venta', Catalogos::MOTIVOS_TRASLADO['01']);
    }
}

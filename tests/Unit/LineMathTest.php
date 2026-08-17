<?php

namespace Tests\Unit;

use App\Support\LineMath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** F1b — aritmética entera exacta: fórmula, parser, límites y overflow. */
class LineMathTest extends TestCase
{
    public function test_bordes_canonicos(): void
    {
        $this->assertSame('89.99', LineMath::total('33.33', 3, '10'));
        $this->assertSame('0.01', LineMath::total('0.01', 1, '50'));
        $this->assertSame('0.03', LineMath::total('0.05', 1, '50'), 'half-up de 0.025');
        $this->assertSame('466.64', LineMath::total('99.99', 7, '33.33'),
            '69993*6667=466643331 -> 46664 centavos (fixture corregido de 466.62)');
    }

    public function test_suma_multilinea_en_centavos(): void
    {
        $items = [
            ['price' => '33.33', 'quantity' => 3, 'discount' => '10'],
            ['price' => '0.01',  'quantity' => 1, 'discount' => '50'],
        ];
        $this->assertSame(9000, LineMath::sumCents($items));
        $this->assertSame('90.00', LineMath::sum($items));
    }

    public function test_parser_estricto(): void
    {
        $this->assertSame(3333, LineMath::toCents('33.33'));
        $this->assertSame(3330, LineMath::toCents('33.3'));
        $this->assertSame(3300, LineMath::toCents('33'));

        foreach (['33.333', '-1', 'abc', '', '1,50'] as $malo) {
            try {
                LineMath::toCents($malo);
                $this->fail("acepto '{$malo}'");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_frontera_legacy_canon(): void
    {
        $this->assertSame('33.00', LineMath::canon(33));
        $this->assertSame('33.33', LineMath::canon(33.33));
        $this->expectException(InvalidArgumentException::class);
        LineMath::canon(1.005); // 3dp reales: dato imposible, no clamp
    }

    public function test_limites_y_overflow(): void
    {
        // qty < 1
        try { LineMath::lineCents('10.00', 0); $this->fail('qty 0'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        // descuento > 100
        try { LineMath::lineCents('10.00', 1, '150'); $this->fail('150%'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        // precio > limite (10^10 centavos)
        try { LineMath::toCents('100000000.00'); $this->fail('precio limite'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        // base > 10^14
        try { LineMath::lineCents('99999999.99', 10001); $this->fail('overflow'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        // justo dentro del limite: no lanza
        $this->assertIsInt(LineMath::lineCents('99999999.99', 10000, '0'));
    }

    public function test_format_salida_canonica(): void
    {
        $this->assertSame('466.64', LineMath::format(46664));
        $this->assertSame('0.05', LineMath::format(5));
        $this->assertSame('0.00', LineMath::format(0));
    }

    /**
     * present() agrupa la parte entera SIN float. El caso que motivo esta
     * funcion es 19345.50: en el portal se veia "19345.50" mientras el total
     * de abajo decia "19,345.50".
     */
    public function test_present_agrupa_miles_sin_float(): void
    {
        $this->assertSame('0.00', LineMath::present('0.00'));
        $this->assertSame('89.99', LineMath::present('89.99'));
        $this->assertSame('999.99', LineMath::present('999.99'));          // sin separador
        $this->assertSame('1,000.00', LineMath::present('1000.00'));       // primer separador
        $this->assertSame('19,345.50', LineMath::present('19345.50'));     // el caso del portal
        $this->assertSame('99,999,999.99', LineMath::present('99999999.99'));
        $this->assertSame('-1,234.56', LineMath::present('-1234.56'));

        // Encadenado con la aritmetica entera: 3869.10 x 5 = 19,345.50 exacto.
        $this->assertSame('19,345.50', LineMath::present(LineMath::total('3869.10', 5)));
    }

    public function test_present_rechaza_entrada_no_canonica(): void
    {
        // Rechazo explicito, nunca coercion: un importe mal formado debe
        // estallar aqui y no colarse silenciosamente en el documento.
        foreach (['19345.5', '19345', '1.234', 'abc', '', '1,234.56', '19345.50 '] as $malo) {
            try {
                LineMath::present($malo);
                $this->fail("present() acepto una entrada no canonica: '{$malo}'");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}

<?php

namespace Tests\Feature;

use App\Modules\Ventas\Support\DemosPorRubro;
use Tests\TestCase;

/**
 * Catalogo de demos y textos por rubro para las propuestas.
 *
 * El rubro lo escribe a mano quien arma la propuesta ("Botica", "pollería",
 * "venta de ropa"), asi que emparejar por igualdad exacta no sirve: por eso
 * se normaliza contra una lista de pistas.
 */
class DemosPorRubroTest extends TestCase
{
    public function test_reconoce_el_rubro_aunque_se_escriba_distinto(): void
    {
        $casos = [
            'Botica' => 'farmacia',
            'FARMACIA' => 'farmacia',
            'veterinaria de barrio' => 'veterinaria',
            'Pet shop' => 'veterinaria',
            'Pollería' => 'restaurante',
            'venta de ropa' => 'moda',
            'Boutique' => 'moda',
            'Licorería' => 'licoreria',
            'bodega / abarrotes' => 'minimarket',
            'ferretería y eléctricos' => 'ferreteria',
        ];

        foreach ($casos as $escrito => $esperado) {
            $this->assertSame($esperado, DemosPorRubro::normalizar($escrito), "Falla con: {$escrito}");
        }
    }

    public function test_un_rubro_desconocido_no_inventa_demo(): void
    {
        // Mandar la demo de otro rubro delata una propuesta copiada: antes de
        // eso, mejor no sugerir ninguna.
        $this->assertNull(DemosPorRubro::normalizar('lavandería'));
        $this->assertSame([], DemosPorRubro::delRubro('lavandería'));
        $this->assertNull(DemosPorRubro::apertura('lavandería'));
    }

    public function test_cada_rubro_trae_sus_demos_y_su_texto(): void
    {
        foreach (array_keys(DemosPorRubro::DEMOS) as $rubro) {
            $demos = DemosPorRubro::delRubro($rubro);

            $this->assertNotEmpty($demos, "El rubro {$rubro} no tiene demos.");
            $this->assertNotEmpty(DemosPorRubro::apertura($rubro), "El rubro {$rubro} no tiene apertura.");
            $this->assertNotEmpty(DemosPorRubro::motivo($rubro), "El rubro {$rubro} no tiene motivo.");

            foreach ($demos as $d) {
                $this->assertStringStartsWith('https://', $d['url']);
            }
        }
    }

    /**
     * El listado plano trae TODO lo que se puede ensenar a un prospecto:
     * las demos por rubro mas los clientes reales.
     *
     * Antes este test fijaba el numero a mano y se quedo en 13 cuando ya
     * habia 19: cada demo nueva lo dejaba en rojo sin que nada estuviera
     * roto. Ahora cuenta contra la fuente, que es lo que importa.
     */
    public function test_el_listado_completo_trae_las_demos_y_los_clientes(): void
    {
        $opciones = DemosPorRubro::opciones();

        $esperadas = array_sum(array_map(
            fn ($rubro) => count($rubro['tiendas']),
            DemosPorRubro::DEMOS
        )) + array_sum(array_map('count', DemosPorRubro::CLIENTES));

        $this->assertCount($esperadas, $opciones);
        $this->assertSame(
            count($opciones),
            count(array_unique(array_column($opciones, 'url'))),
            'No debe haber URLs repetidas.'
        );
    }
}

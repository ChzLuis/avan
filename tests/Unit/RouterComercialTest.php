<?php

namespace Tests\Unit;

use App\Ia\RouterComercial;
use Tests\TestCase;

class RouterComercialTest extends TestCase
{
    public function test_saludo_pide_el_rubro_sin_mostrar_precio(): void
    {
        $ruta = RouterComercial::analizar('Hola', 'INICIO', []);

        $this->assertSame('IDENTIFICAR_NEGOCIO', $ruta['etapa']);
        $this->assertNull($ruta['lista']);
        $this->assertStringContainsString('No hables del precio', $ruta['instruccion']);
    }

    public function test_despues_del_rubro_muestra_los_siguientes_pasos_configurados(): void
    {
        $ruta = RouterComercial::analizar('Tengo una ferretería', 'IDENTIFICAR_NEGOCIO', ['rubro' => 'ferretería']);

        $this->assertSame('ENTENDER_NECESIDAD', $ruta['etapa']);
        $this->assertSame('¿Cómo seguimos?', $ruta['lista']['titulo']);
        $this->assertSame(['Ver ejemplos', 'Recibir propuesta', 'Agendar llamada'], array_column($ruta['lista']['opciones'], 'titulo'));
    }

    public function test_agendar_devuelve_los_horarios_tocables_configurados(): void
    {
        $ruta = RouterComercial::analizar('Quiero una llamada', 'RESOLVER_DUDAS', ['rubro' => 'ferretería']);

        $this->assertSame('AGENDAR_LLAMADA', $ruta['etapa']);
        $this->assertSame('Coordinar llamada', $ruta['lista']['titulo']);
        $this->assertCount(4, $ruta['lista']['opciones']);
    }

    public function test_mas_de_doscientos_productos_no_se_considera_carga_inicial_incluida(): void
    {
        $ruta = RouterComercial::analizar('300', 'ENTENDER_NECESIDAD', ['rubro' => 'ferretería']);

        $this->assertSame('CANTIDAD_PRODUCTOS', $ruta['intent']);
        $this->assertSame(300, $ruta['datos_detectados']['cantidad_productos']);
        $this->assertStringContainsString('no están incluidos', $ruta['dato']);
    }
}

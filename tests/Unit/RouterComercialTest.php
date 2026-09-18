<?php

namespace Tests\Unit;

use App\Modules\Bots\Ia\RouterComercial;
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
        // Las opciones son CONTENIDO configurable
        // (`config/asesor_comercial.php` → `listas.siguiente_paso`): el negocio
        // reescribe el guion del asesor cuando quiere, y fijar aqui los textos
        // hacia fallar la prueba en cada cambio comercial. Lo que el contrato
        // protege es el CABLEADO: que tras identificar el rubro se ofrezca
        // exactamente la lista configurada para ese paso.
        $esperada = config('asesor_comercial.listas.siguiente_paso');

        $this->assertSame($esperada['titulo'], $ruta['lista']['titulo']);
        $this->assertSame(
            array_column($esperada['opciones'], 'titulo'),
            array_column($ruta['lista']['opciones'], 'titulo')
        );
        $this->assertNotEmpty($ruta['lista']['opciones'], 'el paso debe ofrecer alguna salida al cliente');
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

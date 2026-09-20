<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un "Ok" NO cierra la conversacion.
 *
 * Caso real (conversacion 128, 2026-09-20): un cliente vio los precios, dijo
 * "Ok" dos veces seguidas y el bot le contesto "¡Con gusto! Si necesitas algo
 * mas..." las dos veces. El cliente seguia interesado y termino comprando por
 * un asesor humano. En WhatsApp la gente asiente ("ok", "ya", "aya", "aja")
 * esperando el siguiente paso, no despidiendose.
 *
 * Regla: con opciones abiertas, un asentimiento las vuelve a ofrecer.
 * "Gracias" si es una despedida y mantiene el cierre de siempre.
 */
class BotAsentimientoTest extends TestCase
{
    use RefreshDatabase;

    private Project $p;

    protected function setUp(): void
    {
        parent::setUp();
        $this->p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Demo', 'slug' => 'tienda-demo', 'is_active' => true,
        ]);
    }

    /** Flujo minimo: un bloque con 3 botones, igual que el menu real. */
    private function flujo(): array
    {
        return [
            'inicio' => 'menu',
            'bloques' => [
                'menu' => [
                    'tipo' => 'intencion',
                    'texto' => '¿Qué te gustaría ver?',
                    'botones' => [
                        ['titulo' => '💰 Ver precios', 'siguiente' => 'precios'],
                        ['titulo' => '👀 Ver una tienda', 'siguiente' => 'tienda'],
                        ['titulo' => '💬 Hablar con asesor', 'siguiente' => 'asesor'],
                    ],
                    'esperar' => true,
                    'siguiente' => 'menu',
                ],
                'precios' => ['tipo' => 'mensaje', 'texto' => 'Desde S/ 490.'],
                'tienda'  => ['tipo' => 'mensaje', 'texto' => 'Mira la demo.'],
                'asesor'  => ['tipo' => 'mensaje', 'texto' => 'Te paso con un asesor.'],
            ],
        ];
    }

    /** Estado: el cliente esta parado en el menu, con las 3 opciones abiertas. */
    private function enElMenu(): array
    {
        return ['bloque' => 'menu', 'vars' => [], 'esperando' => true];
    }

    private function responder(string $mensaje, ?array $estado = null): string
    {
        $runner = new FlowRunner($this->p, $this->flujo());
        $res = $runner->procesar($mensaje, $estado ?? $this->enElMenu(), '51900000001');

        return implode("\n", array_map(
            fn ($r) => is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r,
            $res['respuestas']
        ));
    }

    public function test_ok_con_opciones_abiertas_las_vuelve_a_ofrecer(): void
    {
        $r = $this->responder('Ok');

        $this->assertStringNotContainsString('Si necesitas algo más', $r,
            'Un "Ok" no puede despedir a un cliente que sigue en el menú.');
        $this->assertStringContainsString('Ver precios', $r);
        $this->assertStringContainsString('Hablar con asesor', $r);
    }

    /** Variantes que escriben los clientes de verdad (vistas en produccion). */
    public static function asentimientos(): array
    {
        return [['Ok'], ['ok'], ['Ya'], ['Aya'], ['aja'], ['Listo'], ['dale'], ['bien'], ['Correcto']];
    }

    /** @dataProvider asentimientos */
    public function test_variantes_de_asentimiento_no_cierran(string $palabra): void
    {
        $r = $this->responder($palabra);

        $this->assertStringNotContainsString('Si necesitas algo más', $r,
            "\"$palabra\" cerró la conversación en vez de reofrecer las opciones.");
        $this->assertStringContainsString('Ver precios', $r);
    }

    public function test_gracias_si_es_despedida_y_mantiene_el_cierre(): void
    {
        $r = $this->responder('gracias');

        $this->assertStringContainsString('Con gusto', $r);
        $this->assertStringNotContainsString('¿Qué prefieres', $r,
            'Un "gracias" sí es despedida: no se le reofrecen las opciones.');
    }

    public function test_ok_sin_opciones_abiertas_mantiene_el_cierre_de_siempre(): void
    {
        $sinBotones = ['bloque' => 'precios', 'vars' => [], 'esperando' => false];

        $r = $this->responder('Ok', $sinBotones);

        $this->assertStringContainsString('Con gusto', $r,
            'Sin opciones que ofrecer, el cierre cortés sigue siendo lo correcto.');
    }

    public function test_dos_ok_seguidos_no_repiten_la_misma_despedida(): void
    {
        // El caso exacto de la conversacion 128.
        $runner = new FlowRunner($this->p, $this->flujo());
        $estado = $this->enElMenu();

        $textos = [];
        foreach (['Ok', 'Ok'] as $m) {
            $res = $runner->procesar($m, $estado, '51900000001');
            $estado = $res['estado'];
            $textos[] = implode("\n", array_map(
                fn ($r) => is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r,
                $res['respuestas']
            ));
        }

        foreach ($textos as $t) {
            $this->assertStringNotContainsString('Si necesitas algo más', $t);
        }
    }
}

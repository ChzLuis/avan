<?php

namespace Tests\Feature;

use App\Models\BotFlow;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Support\FlowEngine\FlowRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El Bot Comercial es predeterminado: toda empresa lo tiene sin pedirlo, se
 * alimenta de sus propios datos y sigue mejorando sin migraciones.
 *
 * Lo que estos tests protegen:
 *  - que exista desde el alta de la empresa, y desactivado;
 *  - que NO sea una copia congelada de la plantilla;
 *  - que encenderlo sea decisión del dueño y no desplace a otro bot suyo.
 */
class BotPredeterminadoTest extends TestCase
{
    use RefreshDatabase;

    private function proyecto(string $nombre = 'Empresa Nueva'): Project
    {
        return Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => $nombre,
            'slug'      => \Illuminate\Support\Str::slug($nombre) . '-' . uniqid(),
            'is_active' => true,
        ]);
    }

    public function test_toda_empresa_nueva_nace_con_su_bot_comercial(): void
    {
        $project = $this->proyecto();

        $flow = BotFlow::where('project_id', $project->id)
            ->where('plantilla', BotFlow::COMERCIAL)->first();

        $this->assertNotNull($flow, 'El alta de la empresa no creó su bot.');
        $this->assertFalse($flow->activo, 'Debe nacer apagado: nadie revisó aún los textos.');
    }

    /**
     * La clave del diseño: sin copia propia, el bot se arma desde la plantilla
     * en cada ejecución, así que las mejoras llegan solas.
     */
    public function test_el_bot_sin_editar_sigue_la_plantilla_en_vivo(): void
    {
        $project = $this->proyecto('Ferretería El Tornillo');
        $flow = BotFlow::comercialDe($project);

        $this->assertTrue($flow->sigueLaPlantilla());
        $this->assertNull($flow->getRawOriginal('definicion'), 'No debe guardar copia.');

        $def = $flow->definicion;
        $this->assertIsArray($def);
        $this->assertArrayHasKey('bloques', $def);
        $this->assertArrayHasKey('faq', $def['bloques'], 'Trae los bloques actuales de la plantilla.');
    }

    /** El saludo sale con el nombre real de la empresa, sin configurar nada. */
    public function test_el_bot_predeterminado_habla_con_los_datos_de_su_empresa(): void
    {
        $project = $this->proyecto('Ferretería El Tornillo');
        Product::create([
            'project_id' => $project->id, 'name' => 'Taladro percutor 650W',
            'price' => 199, 'stock' => 4,
        ]);
        $flow = BotFlow::comercialDe($project);

        $runner = new FlowRunner($project, $flow->definicion);
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        $texto = '';
        foreach (['hola', 'cuanto cuesta el taladro percutor'] as $m) {
            $res = $runner->procesar($m, $estado, '51988000111');
            $estado = $res['fin'] ? ['bloque' => null, 'vars' => [], 'esperando' => false] : $res['estado'];
            foreach ($res['respuestas'] as $r) {
                $texto .= (is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r) . "\n";
            }
        }

        $this->assertStringContainsString('Ferretería El Tornillo', $texto);
        // Contrato simple: sin categoria registrada, honestidad (sin fichas).
        $this->assertStringContainsString('No encontré una categoría', $texto);
    }

    /** Al editarlo se materializa su versión, y a partir de ahí manda la suya. */
    public function test_al_editarlo_deja_de_seguir_la_plantilla(): void
    {
        $project = $this->proyecto();
        $flow = BotFlow::comercialDe($project);

        $flow->update(['definicion' => ['inicio' => 'x', 'bloques' => ['x' => ['tipo' => 'mensaje', 'texto' => 'Mío']]]]);
        $flow->refresh();

        $this->assertFalse($flow->sigueLaPlantilla());
        $this->assertSame('Mío', $flow->definicion['bloques']['x']['texto']);
    }

    public function test_no_se_duplica_ni_al_repetir_el_alta(): void
    {
        $project = $this->proyecto();

        BotFlow::comercialDe($project);
        BotFlow::comercialDe($project);
        $this->artisan('bot:comercial-todos')->assertSuccessful();
        $this->artisan('bot:comercial-todos')->assertSuccessful();

        $this->assertSame(1, BotFlow::where('project_id', $project->id)
            ->where('plantilla', BotFlow::COMERCIAL)->count());
    }

    /**
     * El webhook responde con el flujo activo más reciente. Si el bot
     * predeterminado naciera encendido, robaría la conversación al bot que la
     * empresa ya tenía funcionando.
     */
    public function test_no_desplaza_al_bot_que_la_empresa_ya_tenia_activo(): void
    {
        $project = $this->proyecto();
        $propio = BotFlow::create([
            'project_id' => $project->id, 'nombre' => 'Mi bot de siempre',
            'activo' => true, 'definicion' => ['inicio' => 'a', 'bloques' => ['a' => ['tipo' => 'mensaje', 'texto' => 'Hola']]],
        ]);

        BotFlow::comercialDe($project);

        $elQueResponde = BotFlow::where('project_id', $project->id)
            ->where('activo', true)->latest()->first();

        $this->assertSame($propio->id, $elQueResponde->id);
    }

    /** Sin copilot_token el bot está sordo: toda empresa nace con el suyo. */
    public function test_toda_empresa_nueva_nace_con_token_de_conector(): void
    {
        $project = $this->proyecto()->fresh();

        $this->assertNotEmpty($project->copilot_token, 'Sin token, WhatsApp no puede entregarle mensajes.');
    }

    /** Un token ya configurado en un conector JAMÁS se pisa. */
    public function test_el_backfill_no_pisa_un_token_existente(): void
    {
        $project = $this->proyecto()->fresh();
        $original = $project->copilot_token;

        $this->artisan('bot:comercial-todos')->assertSuccessful();

        $this->assertSame($original, $project->fresh()->copilot_token);
    }

    /**
     * Renombrar o encender el bot desde el editor NO materializa la copia:
     * solo una edición real del flujo (que envía `definicion`) lo hace.
     */
    public function test_renombrar_o_encender_no_congela_la_plantilla(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id, 'name' => 'Meta Store',
            'slug' => 'meta-store-' . uniqid(), 'is_active' => true,
        ]);
        $flow = BotFlow::comercialDe($project);

        $this->actingAs($user)->withSession(['active_project_id' => $project->id])
            ->postJson(route('bot-flows.save', $flow), ['nombre' => 'Mi Bot', 'activo' => true])
            ->assertOk();

        $flow->refresh();
        $this->assertTrue($flow->sigueLaPlantilla(), 'Renombrar no debe crear versión propia.');
        $this->assertTrue($flow->activo);
        $this->assertSame('Mi Bot', $flow->nombre);
    }

    /** "Volver a la plantilla" descarta la versión propia y recupera las mejoras. */
    public function test_restaurar_vuelve_a_la_plantilla_viva(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id, 'name' => 'Restaura Store',
            'slug' => 'restaura-store-' . uniqid(), 'is_active' => true,
        ]);
        $flow = BotFlow::comercialDe($project);
        $flow->update(['definicion' => ['inicio' => 'x', 'bloques' => ['x' => ['tipo' => 'mensaje', 'texto' => 'Editado']]]]);
        $this->assertFalse($flow->fresh()->sigueLaPlantilla());

        $this->actingAs($user)->withSession(['active_project_id' => $project->id])
            ->post(route('bot-flows.restaurar', $flow))
            ->assertRedirect();

        $this->assertTrue($flow->fresh()->sigueLaPlantilla());
        $this->assertArrayHasKey('faq', $flow->fresh()->definicion['bloques'], 'Recupera los bloques actuales.');
    }

    /**
     * UN solo bot responde por negocio: encender uno apaga a los demás, y el
     * webhook (activo más reciente) queda alineado con lo que muestra la lista.
     */
    public function test_encender_un_bot_apaga_a_los_demas(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id, 'name' => 'Unico Activo',
            'slug' => 'unico-activo-' . uniqid(), 'is_active' => true,
        ]);
        $viejo = BotFlow::create([
            'project_id' => $project->id, 'nombre' => 'Bot viejo', 'activo' => true,
            'definicion' => ['inicio' => 'a', 'bloques' => ['a' => ['tipo' => 'mensaje', 'texto' => 'Viejo']]],
        ]);
        $nuevo = BotFlow::comercialDe($project);

        $this->actingAs($user)->withSession(['active_project_id' => $project->id])
            ->postJson(route('bot-flows.save', $nuevo), ['activo' => true])
            ->assertOk()->assertJson(['activo' => true]);

        $this->assertFalse($viejo->fresh()->activo, 'El anterior se apaga.');
        $this->assertTrue($nuevo->fresh()->activo);

        $responde = BotFlow::where('project_id', $project->id)->where('activo', true)->latest()->first();
        $this->assertSame($nuevo->id, $responde->id, 'El webhook toma exactamente el que se encendió.');
        $this->assertSame(1, BotFlow::where('project_id', $project->id)->where('activo', true)->count());
    }

    /** El backfill alcanza a las empresas anteriores al alta automática. */
    public function test_el_comando_alcanza_a_las_empresas_antiguas(): void
    {
        $project = $this->proyecto('Empresa Antigua');
        BotFlow::where('project_id', $project->id)->delete();
        $this->assertSame(0, BotFlow::where('project_id', $project->id)->count());

        $this->artisan('bot:comercial-todos')->assertSuccessful();

        $this->assertSame(1, BotFlow::where('project_id', $project->id)
            ->where('plantilla', BotFlow::COMERCIAL)->count());
    }
}

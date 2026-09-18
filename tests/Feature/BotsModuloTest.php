<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Bots\Models\BotFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modulo Bots (app/Modules/Bots): el motor de flujos (FlowRunner, plantillas,
 * carrito), la IA (interprete comercial, proveedores), los webhooks de
 * entrada (Meta y conector Baileys), el constructor de bots (panel y portal
 * CRM) y el estado de los conectores.
 *
 * Aqui NO se prueba el motor (lo cubren BotWebhookTest, BotPredeterminadoTest,
 * WhatsappCloudWebhookTest...); se vigila que las pantallas sigan pintandose
 * con sus vistas bajo `bots::` y que nadie vuelva a crear ni importar estas
 * clases por su ruta vieja. Regla de producto 2026-09-17: fuera del CRM
 * ningun modulo ejecuta bots, IA ni automatizaciones.
 */
class BotsModuloTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario  = User::factory()->create();
        $this->proyecto = Project::create([
            'name'      => 'Negocio bots',
            'slug'      => 'negocio-bots-' . uniqid(),
            'owner_id'  => $this->usuario->id,
            'is_active' => true,
        ]);
        // El portal CRM exige el producto contratado (modulo clients).
        \App\Support\Productos::activar($this->proyecto, 'crm');
    }

    /** El constructor de bots dentro del portal CRM (portal de Comunicaciones). */
    public function test_el_constructor_del_crm_se_pinta_con_las_vistas_del_modulo(): void
    {
        $sesion = $this->actingAs($this->usuario)
            ->withSession(['comunicaciones_project_id' => $this->proyecto->id]);

        $sesion->get('/bixocrm/bots')
            ->assertOk()
            ->assertViewIs('bots::comunicaciones.bots.index');

        $flow = BotFlow::comercialDe($this->proyecto);
        $sesion->get('/bixocrm/bots/' . $flow->id)
            ->assertOk()
            ->assertViewIs('bots::comunicaciones.bots.editor');
    }

    /** El estado de los conectores en el panel de administracion. */
    public function test_el_estado_de_bots_del_panel_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->actingAs($this->usuario)
            ->withSession(['active_project_id' => $this->proyecto->id])
            ->get('/bixoadmin/bots')
            ->assertOk()
            ->assertViewIs('bots::bots.index');
    }

    /** El webhook de Meta sigue en su URL: sin canal que conozca el token, rechaza. */
    public function test_el_webhook_de_meta_sigue_respondiendo(): void
    {
        $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=nadie&hub_challenge=123')
            ->assertForbidden();
    }

    public function test_el_comando_del_bot_comercial_sigue_registrado(): void
    {
        $this->assertContains('bot:comercial-todos', array_keys(\Illuminate\Support\Facades\Artisan::all()));
    }

    /**
     * Frontera del modulo: estas clases y vistas viven SOLO en
     * app/Modules/Bots. Quien las necesite las importa por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/BotFlowController.php',
            'app/Http/Controllers/BotStatusController.php',
            'app/Http/Controllers/WaBotController.php',
            'app/Http/Controllers/Comunicaciones/BotBuilderPortalController.php',
            'app/Http/Controllers/Api/BotWebhookController.php',
            'app/Http/Controllers/Api/WhatsappCloudWebhookController.php',
            'app/Models/BotConfig.php',
            'app/Models/BotFlow.php',
            'app/Models/BotInstance.php',
            'app/Models/BotSession.php',
            'app/Models/BotState.php',
            'app/Models/BotTransition.php',
            'app/Support/FlowEngine',
            'app/Support/LeadScoring.php',
            'app/Ia',
            'app/Console/Commands/BotComercialParaTodos.php',
            'resources/views/bot-builder',
            'resources/views/bot-flows',
            'resources/views/bots',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Bots es su unico sitio.");
        }

        $patron = '/\\bApp\\\\('
            . 'Models\\\\(BotConfig|BotFlow|BotInstance|BotSession|BotState|BotTransition)\\b'
            . '|Support\\\\(FlowEngine\\\\|LeadScoring\\b)'
            . '|Ia\\\\'
            . '|Console\\\\Commands\\\\BotComercialParaTodos\\b'
            . '|Http\\\\Controllers\\\\((BotFlow|BotStatus|WaBot)Controller\\b|Comunicaciones\\\\BotBuilderPortalController\\b|Api\\\\(BotWebhook|WhatsappCloudWebhook)Controller\\b)'
            . ')/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Bots/') || $rel === 'tests/Feature/BotsModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Bots por su ruta vieja:\n" . implode("\n", $infractores));
    }
}

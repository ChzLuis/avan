<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modulo Crm (app/Modules/Crm): portal bixocrm (login, bandeja, canales de
 * WhatsApp, clientes), conversaciones del portal comercial, Copilot,
 * pagos por extension y recordatorios de carrito.
 *
 * Aqui NO se prueba la logica del CRM (la cubren sus tests propios); se
 * vigila que cada pantalla siga pintandose con su vista bajo `crm::` tras la
 * mudanza y que nadie vuelva a crear ni importar estas clases por su ruta
 * vieja. Regla de producto 2026-09-17: bots/IA/automatizaciones viven solo
 * dentro del CRM (ver BotsModuloTest).
 */
class CrmModuloTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Project $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario  = User::factory()->create();
        $this->proyecto = Project::create([
            'name'      => 'Negocio CRM',
            'slug'      => 'negocio-crm-' . uniqid(),
            'owner_id'  => $this->usuario->id,
            'is_active' => true,
        ]);
        // El portal exige el producto CRM contratado (modulo clients).
        \App\Support\Productos::activar($this->proyecto, 'crm');
    }

    /** Entra al portal de Comunicaciones como lo hace el login real. */
    private function enElCrm()
    {
        return $this->actingAs($this->usuario)
            ->withSession(['comunicaciones_project_id' => $this->proyecto->id]);
    }

    public function test_el_login_del_crm_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->get('/bixocrm/login')
            ->assertOk()
            ->assertViewIs('crm::comunicaciones.auth.login');
    }

    public function test_la_bandeja_se_pinta_con_la_vista_del_modulo(): void
    {
        $this->enElCrm()->get('/bixocrm')
            ->assertOk()
            ->assertViewIs('crm::comunicaciones.bandeja');
    }

    public function test_canales_y_clientes_se_pintan_con_las_vistas_del_modulo(): void
    {
        $this->enElCrm()->get('/bixocrm/configuracion')
            ->assertOk()
            ->assertViewIs('crm::comunicaciones.configuracion');

        $this->enElCrm()->get('/bixocrm/clientes')
            ->assertOk()
            ->assertViewIs('crm::comunicaciones.clientes');
    }

    /** Los clientes del panel (bixoadmin/clients) son del CRM: MODULE_OWNERSHIP manda. */
    public function test_los_clientes_del_panel_se_pintan_con_la_vista_del_modulo(): void
    {
        $this->usuario->forceFill(['is_superadmin' => true])->save();
        $m = Module::firstOrCreate(['key' => 'clients'], ['name' => 'clients', 'is_active' => true]);
        $this->proyecto->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);

        $this->actingAs($this->usuario)
            ->withSession(['active_project_id' => $this->proyecto->id])
            ->get('/bixoadmin/clients')
            ->assertOk()
            ->assertViewIs('crm::clients.index');
    }

    /** Los comandos programados siguen registrados tras mudar sus clases. */
    public function test_los_comandos_del_modulo_siguen_registrados(): void
    {
        $comandos = array_keys(\Illuminate\Support\Facades\Artisan::all());

        $this->assertContains('carts:remind', $comandos);
        $this->assertContains('bot:seguimiento', $comandos);
    }

    /**
     * Frontera del modulo: estas clases y vistas viven SOLO en
     * app/Modules/Crm. Quien las necesite las importa por su ruta nueva.
     */
    public function test_nadie_usa_las_clases_por_su_ruta_vieja(): void
    {
        $viejas = [
            'app/Http/Controllers/Comunicaciones',
            'app/Http/Controllers/ClientController.php', 'app/Models/Client.php',
            'resources/views/clients', 'resources/views/facturacion/clientes',
            'app/Http/Controllers/ComunicacionesController.php',
            'app/Http/Controllers/CopilotEmpresarialController.php',
            'app/Http/Controllers/Comercial/ConversacionesController.php',
            'app/Http/Controllers/Api/CopilotController.php',
            'app/Http/Controllers/Api/VentaExtensionController.php',
            'app/Http/Controllers/Api/WhatsappSyncController.php',
            'app/Http/Controllers/Api/PagoController.php',
            'app/Models/WaCanal.php',
            'app/Models/WaChatbotFlow.php',
            'app/Models/WaConversacion.php',
            'app/Models/WaMensaje.php',
            'app/Models/WaRespuestaRapida.php',
            'app/Support/WhatsappCloud',
            'app/Console/Commands/SeguimientoConversaciones.php',
            'app/Console/Commands/SendAbandonedCartReminders.php',
            'app/Jobs/SendAbandonedCartReminder.php',
            'resources/views/comunicaciones',
            'resources/views/copilot',
            'resources/views/comercial/conversaciones.blade.php',
        ];
        foreach ($viejas as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el modulo Crm es su unico sitio.");
        }

        $patron = '/\\bApp\\\\('
            . 'Models\\\\(WaCanal|WaChatbotFlow|WaConversacion|WaMensaje|WaRespuestaRapida)\\b'
            . '|Models\\\\Client\\b'
            . '|Support\\\\WhatsappCloud\\\\'
            . '|Jobs\\\\SendAbandonedCartReminder\\b'
            . '|Console\\\\Commands\\\\(SeguimientoConversaciones|SendAbandonedCartReminders)\\b'
            . '|Http\\\\Controllers\\\\(Comunicaciones\\\\(?!BotBuilderPortalController)|(ComunicacionesController|CopilotEmpresarialController|ClientController)\\b|Comercial\\\\ConversacionesController\\b|Api\\\\(Copilot|VentaExtension|WhatsappSync|Pago)Controller\\b)'
            . ')/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('tests'), base_path('database'), base_path('config'), base_path('bootstrap'), base_path('resources/views')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if (str_starts_with($rel, 'app/Modules/Crm/') || $rel === 'tests/Feature/CrmModuloTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()))) {
                    $infractores[] = $rel;
                }
            }
        }

        $this->assertSame([], $infractores, "Referencian las clases de Crm por su ruta vieja:\n" . implode("\n", $infractores));
    }
}

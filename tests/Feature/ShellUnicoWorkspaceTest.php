<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * UN SOLO MENÚ POR CARA (queja del usuario, 2026-08-30).
 *
 * Convivían dos shells dentro de /bixoadmin: unas pantallas con el del panel
 * (`#admin-sidebar`) y otras con el comercial. Al hacer clic en el menú, el
 * menú CAMBIABA — "se ve desordenado". Este test recorre las entradas reales
 * del menú de Configuración y exige que todas se sirvan con el mismo shell.
 */
class ShellUnicoWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $duenio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->duenio = User::factory()->create(['is_superadmin' => 0]);
        $this->project = Project::create([
            'owner_id' => $this->duenio->id,
            'name' => 'Shell Unico QA', 'slug' => 'shell-unico-qa',
            'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'clients', 'invoices', 'quotes', 'catalog', 'store', 'bots'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->duenio->id, 'role' => 'owner']);

        $permisos = ['settings.negocio', 'settings.diseno', 'settings.pagos', 'settings.catalogos',
            'catalog.ver', 'invoices.ver', 'orders.ver', 'view-orders', 'reports.ver', 'clients.ver'];
        foreach ($permisos as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('shell_unico_qa', 'web')->syncPermissions($permisos);
        $this->duenio->syncRoles(['shell_unico_qa']);

        $this->actingAs($this->duenio)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);
    }

    public static function pantallasDeConfiguracion(): array
    {
        return [
            'Datos del negocio'  => ['/bixoadmin/settings'],
            'Código QR'          => ['/bixoadmin/settings/qr'],
            'Sedes'              => ['/bixoadmin/company/sedes'],
            'Proveedores'        => ['/bixoadmin/company/proveedores'],
            'Grupos'             => ['/bixoadmin/company/groups'],
            'Productos'          => ['/bixoadmin/products'],
            'Certificados SUNAT' => ['/bixoadmin/certificados'],
            'Canales WhatsApp'   => ['/bixoadmin/bots'],
        ];
    }

    /**
     * @dataProvider pantallasDeConfiguracion
     */
    public function test_toda_la_configuracion_usa_el_mismo_menu(string $ruta): void
    {
        $html = $this->get($ruta)->assertOk()->getContent();

        // El menú del shell único, y UNO solo.
        $this->assertStringContainsString('nav-marca-texto', $html,
            "{$ruta} no se sirve con el shell del Workspace: el usuario cambia de menú al llegar aquí.");
        $this->assertSame(1, substr_count($html, 'aria-label="Navegación principal"'),
            "{$ruta} pinta más de un menú (o ninguno).");
        $this->assertSame(0, substr_count($html, 'id="admin-sidebar"'),
            "{$ruta} todavía trae el sidebar del shell retirado.");
    }

    /** Y el menú que se pinta ahí es el de CONFIGURACIÓN, no el de operación. */
    public function test_el_menu_de_esas_pantallas_es_el_de_configuracion(): void
    {
        $html = $this->get('/bixoadmin/products')->assertOk()->getContent();

        $this->assertStringContainsString('Catálogo maestro', $html);
        $this->assertStringContainsString('Ir a Ventas', $html);
        // Sin entradas de operación mezcladas.
        $this->assertStringNotContainsString('Venta express', $html);
    }

    /**
     * SOLO MÓDULOS ACTIVOS: el menú no ofrece lo que el negocio no tiene
     * contratado — ofrecerlo es mandar al usuario a un 403.
     */
    public function test_el_menu_solo_ofrece_los_modulos_activos_del_negocio(): void
    {
        // Con catálogo contratado, el grupo aparece completo.
        $conCatalogo = $this->get('/bixoadmin/settings')->assertOk()->getContent();
        $this->assertStringContainsString('Categorías', $conCatalogo);
        $this->assertStringContainsString('Servicios', $conCatalogo);

        // Se le retira el módulo: sus entradas desaparecen del menú...
        $catalogo = Module::where('key', 'catalog')->first();
        $this->project->modules()->updateExistingPivot($catalogo->id, ['is_active' => false]);
        $this->project->unsetRelation('modules')->unsetRelation('activeModules');

        $sinCatalogo = $this->get('/bixoadmin/settings')->assertOk()->getContent();
        $this->assertStringNotContainsString('Categorías', $sinCatalogo,
            'El menú ofrece un módulo que el negocio no tiene: ese enlace acaba en 403');
        $this->assertStringNotContainsString('Servicios', $sinCatalogo);

        // ...y la URL directa tampoco pasa: menú y servidor de acuerdo. El
        // middleware `module:` expulsa la navegación web y responde 403 a la
        // petición JSON; en ningún caso sirve la pantalla.
        $this->get('/bixoadmin/categories')->assertRedirect();
        $this->getJson('/bixoadmin/categories')->assertForbidden();
    }
}

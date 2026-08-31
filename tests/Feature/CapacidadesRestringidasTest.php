<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Reestructuración 2026-08-30 — paso 8: capacidades restringidas.
 *
 * Un cliente común NO accede a herramientas internas aunque tenga el permiso
 * de diseño: la puerta es ENTITLEMENT + PERMISSION + FEATURE FLAG. El flag
 * (`cap_<clave>` = 1) solo lo enciende Eskala.
 */
class CapacidadesRestringidasTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $disenador;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('settings.diseno', 'web');
        Permission::findOrCreate('settings.negocio', 'web');
        Permission::findOrCreate('orders.ver', 'web');
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Caps QA', 'slug' => 'caps-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        // Gerente de tienda realista: puede diseñar y configurar su negocio.
        // Aun así NO alcanza las capacidades restringidas sin el flag.
        Role::findOrCreate('caps_disenador', 'web')->syncPermissions(['settings.diseno', 'settings.negocio']);
        $this->disenador = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $this->disenador->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $this->disenador->id,
            'name' => 'Diseñador QA', 'spatie_role' => 'caps_disenador', 'is_active' => 1]);
        $this->disenador->syncRoles(['caps_disenador']);
    }

    private function como(User $u)
    {
        return $this->actingAs($u)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);
    }

    public function test_las_plantillas_no_se_abren_con_solo_el_permiso_de_diseno(): void
    {
        // Tiene settings.diseno, pero la capacidad exige el flag de Eskala.
        $this->como($this->disenador)->get('/bixoadmin/settings/design-templates')->assertForbidden();
        $this->como($this->disenador)->post('/bixoadmin/settings/design-templates', [])->assertForbidden();
        $this->como($this->disenador)->get('/bixoadmin/settings/design-templates/1/export')->assertForbidden();
    }

    public function test_el_dueno_tampoco_se_salta_el_flag(): void
    {
        $dueno = $this->project->owner;
        $this->como($dueno)->get('/bixoadmin/settings/design-templates')->assertForbidden();
    }

    public function test_el_flag_de_eskala_abre_la_capacidad_al_tenant(): void
    {
        $this->project->settings()->create(['key' => 'cap_plantillas', 'value' => '1']);

        $res = $this->como($this->disenador)->get('/bixoadmin/settings/design-templates');
        $this->assertNotSame(403, $res->status(), 'Con flag + permiso la capacidad debe abrirse');
    }

    public function test_el_superadmin_siempre_pasa(): void
    {
        $admin = User::factory()->create(['is_superadmin' => 1]);

        $res = $this->como($admin)->get('/bixoadmin/settings/design-templates');
        $this->assertNotSame(403, $res->status());
    }

    /**
     * §17 del plan: hay que poder DEMOSTRAR que un cliente común no toca la
     * configuración avanzada. Ocultar el control no basta — la ruta del
     * constructor acepta cualquier clave, así que se prueba la escritura.
     */
    public function test_el_cliente_no_puede_escribir_seo_tecnico_ni_cambiar_de_motor(): void
    {
        $modulo = \App\Models\Module::firstOrCreate(['key' => 'catalog'], ['name' => 'catalog', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);

        $res = $this->como($this->disenador)->postJson('/bixoadmin/settings/builder/draft/settings', [
            'settings' => [
                'ga_id'            => 'G-ESPIA',
                'fb_pixel_id'      => '999999999',
                'robots'           => 'noindex',
                'catalog_template' => 'direct',
                'primary_color'    => '#111111', // este SÍ es suyo
            ],
        ])->assertOk();

        // Lo restringido no se guarda y se le dice cuáles.
        $res->assertJsonPath('saved', 1);
        foreach (['ga_id', 'fb_pixel_id', 'robots', 'catalog_template'] as $clave) {
            $this->assertContains($clave, $res->json('omitidas'));
        }

        // Y no queda rastro en el borrador del negocio.
        foreach (['ga_id', 'fb_pixel_id', 'robots', 'catalog_template'] as $clave) {
            $this->assertNull($this->project->fresh()->setting('draft_' . $clave),
                "El cliente escribió {$clave}, que es capacidad restringida");
        }
    }

    /** Con el flag encendido por Eskala, ese mismo cliente sí puede. */
    public function test_con_el_flag_el_tenant_autorizado_si_escribe_seo_tecnico(): void
    {
        $modulo = \App\Models\Module::firstOrCreate(['key' => 'catalog'], ['name' => 'catalog', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
        $this->project->settings()->create(['key' => 'cap_seo_avanzado', 'value' => '1']);

        $res = $this->como($this->disenador)->postJson('/bixoadmin/settings/builder/draft/settings', [
            'settings' => ['ga_id' => 'G-AUTORIZADO'],
        ])->assertOk();

        $this->assertSame([], $res->json('omitidas'));
        $this->assertSame(1, $res->json('saved'));
    }

    public function test_el_diseno_legacy_exige_permiso_de_diseno(): void
    {
        // Un empleado SIN settings.diseno ya no puede abrir el diseñador.
        Role::findOrCreate('caps_vendedor', 'web')->syncPermissions(['orders.ver']);
        $vendedor = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $vendedor->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $vendedor->id,
            'name' => 'Vendedor QA', 'spatie_role' => 'caps_vendedor', 'is_active' => 1]);
        $vendedor->syncRoles(['caps_vendedor']);

        $this->como($vendedor)->get('/bixoadmin/settings/designer')->assertForbidden();
        $this->como($vendedor)->get('/bixoadmin/settings/design')->assertForbidden();

        // Quien SÍ tiene el permiso pasa el gate; el destino es el Constructor
        // porque la superficie legacy está retirada (solo superadmin con
        // ?classic=1 la ve). Lo que importa aquí es que no reciba 403.
        $this->como($this->disenador)->get('/bixoadmin/settings/designer')
            ->assertRedirect(route('settings.builder'));
    }
}

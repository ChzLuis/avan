<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FASE 3 — Configuración. 57 acciones que modificaban datos sin exigir permiso.
 *
 * Bastaba con ser miembro del proyecto para cambiar el diseño de la tienda, los
 * medios de pago, el menú, los módulos del negocio o incluso desactivarlo entero.
 *
 * Se cierran con los permisos GRANULARES (`settings.diseno`, `.catalogos`,
 * `.negocio`, `.pagos`, `.qr`) y NO con `settings.editar`: ese último solo lo
 * tiene el rol `admin`, que en producción no tiene ningún usuario, así que
 * habría dejado fuera a los 9 gerentes que son quienes configuran a diario.
 */
class SettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** Los cinco permisos granulares que tiene `gerente` en producción. */
    private const GRANULARES = [
        'settings.ver', 'settings.negocio', 'settings.diseno',
        'settings.pagos', 'settings.catalogos', 'settings.qr',
    ];

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (array_merge(self::GRANULARES, ['settings.editar']) as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Negocio settings',
            'slug'      => 'negocio-settings',
            'is_active' => true,
        ]);

        $module = Module::firstOrCreate(['key' => 'settings'], ['name' => 'Configuración', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$module->id => ['is_active' => true]]);
    }

    private function usuarioConRol(string $rol, array $permisos): User
    {
        Role::findOrCreate($rol, 'web')->syncPermissions($permisos);

        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Empleado '.$rol, 'spatie_role' => $rol, 'is_active' => 1,
        ]);
        $user->syncRoles([$rol]);

        $this->actingAs($user)->withSession(['active_project_id' => $this->project->id]);

        return $user;
    }

    /** Un miembro sin permisos de configuración no puede tocar nada. */
    /** @dataProvider accionesDeConfiguracion */
    public function test_un_miembro_sin_permiso_no_configura(string $verbo, string $ruta): void
    {
        $this->usuarioConRol('sin_settings_test', []);

        $this->json($verbo, $ruta, [])->assertForbidden();
    }

    /**
     * El gerente real de producción conserva TODAS estas acciones. Este es el
     * test que impide repetir el error de gatear con `settings.editar`.
     *
     * @dataProvider accionesDeConfiguracion
     */
    public function test_el_gerente_conserva_la_configuracion(string $verbo, string $ruta): void
    {
        $this->usuarioConRol('gerente_settings_test', self::GRANULARES);

        $respuesta = $this->json($verbo, $ruta, []);

        $this->assertNotSame(
            403,
            $respuesta->status(),
            "{$verbo} {$ruta} devolvió 403 a un gerente: se ha usado un permiso que no tiene."
        );
    }

    /** Una acción por cada permiso granular y por cada subárea de configuración. */
    public static function accionesDeConfiguracion(): array
    {
        return [
            // settings.negocio
            'datos del negocio'   => ['POST',   '/bixoadmin/settings'],
            'SEO'                 => ['POST',   '/bixoadmin/settings/seo'],
            'flujo operativo'     => ['POST',   '/bixoadmin/settings/flow'],
            'módulos del negocio' => ['POST',   '/bixoadmin/settings/modules'],
            // settings.diseno
            'diseño de la tienda' => ['POST',   '/bixoadmin/settings/design'],
            'publicar tienda'     => ['POST',   '/bixoadmin/settings/builder/publish'],
            'descartar borrador'  => ['POST',   '/bixoadmin/settings/builder/descartar-borrador'],
            'copiar otra tienda'  => ['POST',   '/bixoadmin/settings/builder/copy'],
            'logo'                => ['POST',   '/bixoadmin/settings/upload-logo'],
            'menú de la tienda'   => ['POST',   '/bixoadmin/settings/storefront/menu/items'],
            'reordenar menú'      => ['POST',   '/bixoadmin/settings/storefront/menu/reorder'],
            'secciones de inicio' => ['POST',   '/bixoadmin/settings/experience/home/reorder'],
            'plantillas diseño'   => ['POST',   '/bixoadmin/settings/design-templates'],
            // settings.catalogos
            'perfiles catálogo'   => ['POST',   '/bixoadmin/settings/catalog-profiles'],
            'canales WhatsApp'    => ['POST',   '/bixoadmin/settings/canales'],
            // settings.pagos
            'medios de pago'      => ['POST',   '/bixoadmin/settings/payments'],
            // settings.qr
            'código QR'           => ['POST',   '/bixoadmin/settings/qr'],
        ];
    }

    /**
     * Desactivar el negocio y cambiarle los módulos eran acciones abiertas a
     * cualquier miembro. Son las más destructivas de toda la configuración.
     */
    public function test_desactivar_el_negocio_exige_permiso(): void
    {
        $this->usuarioConRol('sin_settings_test2', []);

        $this->json('PATCH', '/bixoadmin/settings/'.$this->project->id.'/toggle')->assertForbidden();
        $this->json('POST', '/bixoadmin/settings/'.$this->project->id.'/modules', ['modules' => []])->assertForbidden();

        $this->assertTrue((bool) $this->project->fresh()->is_active, 'El negocio se desactivó pese al 403.');
    }

    /** `settings.editar` a secas NO debe bastar: se eligió no usarlo en las rutas. */
    public function test_settings_editar_no_es_la_llave_de_configuracion(): void
    {
        $this->usuarioConRol('solo_settings_editar_test', ['settings.ver', 'settings.editar']);

        $this->json('POST', '/bixoadmin/settings/design', [])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use App\Support\Productos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Productos = agrupaciones de modulos (Productos::DEFINICIONES) y su gestion
 * desde BIXO Control: activar un producto en un negocio con un clic y dar de
 * alta un negocio con su producto inicial. Es la capa que permite vender el
 * CRM (o Sales, o Commerce) por separado sin sistemas aparte.
 */
class ProductosControlTest extends TestCase
{
    use RefreshDatabase;

    private function negocio(array $modulos = []): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Negocio productos', 'slug' => 'negocio-productos-' . uniqid(), 'is_active' => true,
        ]);
        foreach ($modulos as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $p->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        return $p;
    }

    private function comoSuperadmin(): User
    {
        $admin = User::factory()->create(['is_superadmin' => 1]);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_todo_producto_tiene_modulos_y_puerta_de_entrada(): void
    {
        foreach (Productos::todos() as $clave => $def) {
            $this->assertNotEmpty($def['modulos'], "{$clave} sin modulos");
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($def['portal']), "{$clave}: la puerta {$def['portal']} no es una ruta");
        }
        $this->assertSame(['clients', 'bots'], Productos::modulos('crm'), 'El CRM es clientes + bots; nada de tienda ni ventas.');
    }

    public function test_activar_un_producto_enciende_sus_modulos_sin_apagar_los_demas(): void
    {
        $p = $this->negocio(['store']);

        Productos::activar($p, 'crm');

        $this->assertTrue($p->hasModule('clients'));
        $this->assertTrue($p->hasModule('bots'));
        $this->assertTrue($p->hasModule('store'), 'Activar el CRM no puede apagar la tienda.');
        $this->assertTrue(Productos::contratado($p, 'crm'));
        $this->assertFalse(Productos::contratado($p, 'sales'));
        $this->assertSame(['crm'], Productos::contratados($p));
    }

    public function test_un_producto_a_medias_no_cuenta_como_contratado(): void
    {
        $p = $this->negocio(['clients']);

        $this->assertFalse(Productos::contratado($p, 'crm'), 'Solo clients, sin bots: el CRM no esta completo.');
    }

    public function test_control_activa_un_producto_con_un_clic(): void
    {
        $this->comoSuperadmin();
        $p = $this->negocio();

        $this->patch(route('admin.projects.producto', $p), ['producto' => 'crm'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Productos::contratado($p->fresh(), 'crm'));

        $this->patch(route('admin.projects.producto', $p), ['producto' => 'inventado'])
            ->assertSessionHasErrors('producto');
    }

    public function test_la_lista_de_negocios_dice_que_producto_tiene_cada_uno(): void
    {
        $this->comoSuperadmin();
        $con = $this->negocio(['clients', 'bots']);
        $sin = $this->negocio([]);

        $html = $this->get(route('admin.projects'))->assertOk()->getContent();

        $this->assertStringContainsString('BIXO CRM', $html);
        $this->assertStringContainsString('Sin producto completo', $html);
    }

    public function test_la_ficha_del_negocio_muestra_los_productos(): void
    {
        $this->comoSuperadmin();
        $p = $this->negocio(['clients', 'bots']);

        $this->get(route('admin.projects.show', $p))
            ->assertOk()
            ->assertSee('Productos contratados')
            ->assertSee('BIXO CRM')
            ->assertSee('BIXO Sales');
    }

    public function test_control_da_de_alta_un_negocio_con_su_producto_y_dueno_nuevo(): void
    {
        $this->comoSuperadmin();

        $res = $this->post(route('admin.projects.crear'), [
            'name' => 'Ferretería Lima', 'contacto' => 'Rosa', 'email' => 'rosa@ferreteria.pe', 'producto' => 'crm',
        ]);

        $p = Project::where('name', 'Ferretería Lima')->firstOrFail();
        $res->assertRedirect(route('admin.projects.show', $p));
        $this->assertStringContainsString('Contrasena', session('success'), 'Al dueno nuevo se le muestra la contrasena una vez.');
        $this->assertSame('rosa@ferreteria.pe', $p->owner->email);
        $this->assertTrue(Productos::contratado($p, 'crm'));
        $this->assertFalse($p->hasModule('store'), 'Nacio solo CRM: sin tienda.');
        $this->assertDatabaseHas('employees', ['project_id' => $p->id, 'user_id' => $p->owner_id]);
    }

    public function test_control_asigna_el_negocio_a_un_dueno_existente_sin_crear_otro_usuario(): void
    {
        $this->comoSuperadmin();
        $dueno = User::factory()->create(['email' => 'dueno@existe.pe']);

        $this->post(route('admin.projects.crear'), [
            'name' => 'Segundo negocio', 'contacto' => 'Dueño', 'email' => 'dueno@existe.pe', 'producto' => 'sales',
        ])->assertRedirect();

        $this->assertSame(1, User::where('email', 'dueno@existe.pe')->count());
        $this->assertStringNotContainsString('Contrasena', session('success'));
        $this->assertTrue(Productos::contratado(Project::where('name', 'Segundo negocio')->firstOrFail(), 'sales'));
    }
}

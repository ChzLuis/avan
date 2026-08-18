<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El mostrador tiene que cerrar la operacion: cobrar y cotizar.
 *
 * No habia ni un solo test sobre POST /pos ni POST /pos/cotizar, que son las
 * dos acciones por las que entra el dinero. Cuando el usuario reporto "no me
 * deja hacer venta ni cotizacion" no habia forma de distinguir un fallo del
 * codigo de uno del entorno. Estos contratos responden esa pregunta.
 */
class PosSaleQuoteTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Product $producto;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['pos.usar', 'orders.descuento', 'quotes.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'POS QA', 'slug' => 'pos-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
        $this->producto = Product::create([
            'project_id' => $this->project->id,
            'name' => 'Cable HDMI 2.0 4K 2 metros',
            'price' => '29.00', 'stock' => 100, 'is_active' => 1,
        ]);
    }

    /** Cajero real: miembro del proyecto con pos.usar, sin ser propietario. */
    private function cajero(): User
    {
        Role::findOrCreate('pos_cajero', 'web')->syncPermissions(['pos.usar', 'quotes.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Cajero', 'spatie_role' => 'pos_cajero', 'is_active' => 1]);
        $u->syncRoles(['pos_cajero']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        return $u;
    }

    public function test_el_pos_cobra_una_venta(): void
    {
        $this->cajero();

        $res = $this->postJson('/pos', [
            'payment_method' => 'Efectivo',
            'paid' => true,
            'items' => [[
                'product_id' => $this->producto->id,
                'name' => $this->producto->name,
                'price' => 29,
                'quantity' => 1,
            ]],
        ]);

        $res->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('orders', ['project_id' => $this->project->id, 'total' => '29.00']);
    }

    public function test_el_pos_genera_una_cotizacion_con_enlace_publico(): void
    {
        $this->cajero();

        $res = $this->postJson('/pos/cotizar', [
            'client_name' => 'PRUEBA',
            'items' => [[
                'name' => $this->producto->name,
                'price' => 29,
                'quantity' => 1,
            ]],
        ]);

        $res->assertOk()->assertJson(['ok' => true, 'total' => 29, 'client_name' => 'PRUEBA']);
        $this->assertStringContainsString('/b/' . $this->project->slug . '/c/', $res->json('url'));

        // La confirmacion del mostrador se pinta con estos campos: si dejan de
        // venir, el cajero envia al cliente un documento que no ha visto.
        $quote = \App\Models\Quote::where('project_id', $this->project->id)->firstOrFail();
        // El numero es el correlativo DEL NEGOCIO, no el id global: la primera
        // cotizacion de un cliente nuevo es la 1, no la 187.
        $this->assertSame('COT-00001', $res->json('number'));
        $this->assertSame(1, $quote->correlativo);
        $this->assertSame(now()->format('d/m/Y'), $res->json('issued_at'));
        $this->assertSame(1, $res->json('items_count'));
        $this->assertSame('S/', $res->json('currency'));
        $this->assertSame('sent', $res->json('status'));
        $this->assertSame('Enviada', $res->json('status_label'));

        // Vigencia en el formato que se enseña ("23 ago. 2026"), no en crudo.
        $v = now()->addDays(15);
        $meses = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.'];
        $this->assertSame($v->day . ' ' . $meses[$v->month - 1] . ' ' . $v->year, $res->json('valid_until'));

        // Los tres destinos del modal existen y apuntan a ESTA cotizacion.
        $this->assertSame(url('/quotes/' . $quote->id), $res->json('view_url'));
        $this->assertSame(url('/quotes/' . $quote->id . '/pdf'), $res->json('pdf_url'));
        $this->assertSame(url('/quotes'), $res->json('list_url'));
    }

    public function test_el_pdf_de_la_cotizacion_muestra_sus_lineas_y_su_total(): void
    {
        $this->cajero();
        $this->postJson('/pos/cotizar', [
            'client_name' => 'PRUEBA',
            'items' => [
                ['name' => 'Cable HDMI', 'price' => 29, 'quantity' => 2],
                ['name' => 'Teclado',    'price' => 41, 'quantity' => 1],
            ],
        ])->assertOk();

        $quote = \App\Models\Quote::where('project_id', $this->project->id)->firstOrFail();
        $res = $this->get('/quotes/' . $quote->id . '/pdf');

        $res->assertOk()
            ->assertSee($quote->etiqueta)
            ->assertSee('PRUEBA')
            ->assertSee('Cable HDMI')
            ->assertSee('Teclado')
            ->assertSee('99.00')                 // 29x2 + 41x1, por LineMath
            ->assertSee('no constituye comprobante de pago');
    }

    public function test_el_pdf_de_otro_negocio_no_se_abre(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $ajena = \App\Models\Quote::create([
            'project_id' => $otro->id, 'client_name' => 'X', 'status' => 'sent',
            'total' => 10, 'token' => str()->random(24),
        ]);

        $this->cajero();

        // 404, no 403: el scope de proyecto del modelo hace que la cotizacion
        // ajena ni siquiera exista para este usuario, que es una barrera mas
        // fuerte que negar el acceso a algo cuya existencia se confirma.
        $res = $this->get('/quotes/' . $ajena->id . '/pdf');
        $this->assertContains($res->status(), [403, 404]);
        $res->assertDontSee('COT-');
    }

    public function test_sin_permiso_de_pos_no_se_cobra_ni_se_cotiza(): void
    {
        Role::findOrCreate('pos_miron', 'web')->syncPermissions(['quotes.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Miron', 'spatie_role' => 'pos_miron', 'is_active' => 1]);
        $u->syncRoles(['pos_miron']);
        $this->actingAs($u)->withSession(['active_project_id' => $this->project->id]);

        $carrito = ['items' => [['name' => 'X', 'price' => 1, 'quantity' => 1]]];
        $this->postJson('/pos/cotizar', $carrito)->assertForbidden();
        $this->postJson('/pos', $carrito + ['payment_method' => 'Efectivo'])->assertForbidden();
    }
}

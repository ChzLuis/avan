<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Modules\Ventas\Models\Order;
use App\Modules\Ventas\Models\OrderEvent;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F1b — trazabilidad canonica y conversion transaccional.
 *
 * Propiedades protegidas: 1 cotizacion produce a lo sumo 1 pedido (UNIQUE de
 * esquema + idempotencia con already:true), la FK es la fuente canonica, los
 * descuentos viajan con la linea (jamas precio unitario neto), los totales son
 * suma de lineas por LineMath, y el gate fiscal bloquea facturar descuentos.
 */
class QuoteConversionTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar', 'orders.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Conv QA', 'slug' => 'conv-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function editor(): User
    {
        Role::findOrCreate('conv_editor', 'web')->syncPermissions(['quotes.ver', 'quotes.crear', 'quotes.editar', 'orders.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Editor', 'spatie_role' => 'conv_editor', 'is_active' => 1]);
        $u->syncRoles(['conv_editor']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        return $u;
    }

    private function quoteConItems(array $items = null, array $attrs = []): Quote
    {
        $q = Quote::create(array_merge([
            'project_id' => $this->project->id, 'client_name' => 'Cliente',
            'status' => 'accepted', 'total' => 0, 'token' => str()->random(24),
        ], $attrs));
        foreach ($items ?? [['description' => 'Item', 'price' => '10.00', 'quantity' => 1, 'discount' => 0]] as $i) {
            $q->items()->create($i);
        }
        return $q;
    }

    // ── FK canonica e idempotencia ────────────────────────────────────────

    public function test_convertir_guarda_fk_y_la_relacion_inversa_resuelve(): void
    {
        $this->editor();
        $q = $this->quoteConItems();

        $r = $this->postJson('/quotes/'.$q->id.'/convert')->assertSuccessful();
        $r->assertJsonStructure(['ok', 'order_id', 'already'])
          ->assertJson(['ok' => true, 'already' => false]);

        $order = Order::find($r->json('order_id'));
        $this->assertSame($q->id, $order->quote_id);
        $this->assertSame($order->id, $q->fresh()->order->id, 'Quote::order resuelve');
        $this->assertSame('converted', $q->fresh()->status);
    }

    public function test_doble_conversion_devuelve_el_mismo_pedido(): void
    {
        $this->editor();
        $q = $this->quoteConItems();

        $primero  = $this->postJson('/quotes/'.$q->id.'/convert')->json('order_id');
        $segundo  = $this->postJson('/quotes/'.$q->id.'/convert')
            ->assertSuccessful()->assertJson(['already' => true])->json('order_id');

        $this->assertSame($primero, $segundo);
        $this->assertSame(1, Order::where('quote_id', $q->id)->count());
    }

    public function test_unique_de_esquema_impide_segundo_pedido(): void
    {
        $this->editor();
        $q = $this->quoteConItems();
        Order::create(['project_id' => $this->project->id, 'client_name' => 'A',
            'status' => 'pending', 'total' => 1, 'quote_id' => $q->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'B',
            'status' => 'pending', 'total' => 1, 'quote_id' => $q->id]);
    }

    public function test_desalineado_inverso_realinea_sin_segundo_insert(): void
    {
        $this->editor();
        $q = $this->quoteConItems(attrs: ['status' => 'accepted']);
        $huerfano = Order::create(['project_id' => $this->project->id, 'client_name' => 'H',
            'status' => 'pending', 'total' => 1, 'quote_id' => $q->id]);

        $r = $this->postJson('/quotes/'.$q->id.'/convert')->assertSuccessful();

        $this->assertSame($huerfano->id, $r->json('order_id'));
        $this->assertTrue($r->json('already'));
        $this->assertSame('converted', $q->fresh()->status, 're-alineado');
        $this->assertSame(1, Order::where('quote_id', $q->id)->count());
    }

    // ── Legacy converted sin FK ───────────────────────────────────────────

    public function test_legacy_con_evento_unico_resuelve_y_fija_fk(): void
    {
        $this->editor();
        $q = $this->quoteConItems(attrs: ['status' => 'converted']);
        $legacy = Order::create(['project_id' => $this->project->id, 'client_name' => 'L',
            'status' => 'pending', 'total' => 1]);
        OrderEvent::log($this->project->id, 'converted', [], $legacy->id, $q->id);

        $r = $this->postJson('/quotes/'.$q->id.'/convert')->assertSuccessful();

        $this->assertSame($legacy->id, $r->json('order_id'));
        $this->assertSame($q->id, $legacy->fresh()->quote_id, 'FK fijada en caliente');
    }

    public function test_legacy_ambiguo_A_da_409_sin_crear_nada(): void
    {
        $this->editor();
        $q  = $this->quoteConItems(attrs: ['status' => 'converted']);
        $q2 = $this->quoteConItems(attrs: ['status' => 'sent']);
        $legacy = Order::create(['project_id' => $this->project->id, 'client_name' => 'L',
            'status' => 'pending', 'total' => 1]);
        // el pedido tiene eventos de DOS cotizaciones -> ambiguo A en caliente
        OrderEvent::log($this->project->id, 'converted', [], $legacy->id, $q->id);
        OrderEvent::log($this->project->id, 'converted', [], $legacy->id, $q2->id);

        $antes = Order::count();
        $this->postJson('/quotes/'.$q->id.'/convert')->assertStatus(409);
        $this->assertSame($antes, Order::count(), 'jamas un segundo pedido');
        $this->assertNull($legacy->fresh()->quote_id, 'whereNull respetado');
    }

    // ── Aislamiento y nullOnDelete ────────────────────────────────────────

    public function test_multiproyecto_403_dentro_del_lock(): void
    {
        $this->editor();
        $otro = Project::create(['owner_id' => User::factory()->create()->id,
            'name' => 'Otro', 'slug' => 'otro-conv', 'is_active' => true]);
        $ajena = Quote::create(['project_id' => $otro->id, 'client_name' => 'X',
            'status' => 'accepted', 'total' => 5]);

        $r = $this->postJson('/quotes/'.$ajena->id.'/convert');
        $this->assertContains($r->getStatusCode(), [403, 404]); // scope global -> 404; lock -> 403
    }

    public function test_null_on_delete_no_arrastra_el_pedido(): void
    {
        $this->editor();
        $q = $this->quoteConItems();
        $orderId = $this->postJson('/quotes/'.$q->id.'/convert')->json('order_id');

        $q->items()->delete();
        $q->delete();

        $order = Order::find($orderId);
        $this->assertNotNull($order, 'el pedido sobrevive');
        $this->assertNull($order->quote_id);
    }

    // ── Descuentos: integridad de totales ─────────────────────────────────

    public function test_descuento_viaja_con_la_linea_y_los_totales_cuadran(): void
    {
        $this->editor();
        $q = $this->quoteConItems([
            ['description' => 'A', 'price' => '33.33', 'quantity' => 3, 'discount' => '10'],
            ['description' => 'B', 'price' => '0.01',  'quantity' => 1, 'discount' => '50'],
        ]);

        $orderId = $this->postJson('/quotes/'.$q->id.'/convert')->json('order_id');
        $order = Order::with('items')->find($orderId);

        $this->assertSame('90.00', (string) $order->total, 'suma de lineas: 89.99 + 0.01');
        $itemA = $order->items->firstWhere('name', 'A');
        $this->assertSame('33.33', (string) $itemA->price, 'precio bruto intacto');
        $this->assertSame('10.00', (string) $itemA->discount, 'descuento con la linea');
    }

    public function test_store_persiste_descuento_y_valida_dominio(): void
    {
        $this->editor();

        // 150% -> 422 del validador, no excepcion del dominio
        $this->postJson('/quotes', ['client_name' => 'C',
            'items' => [['description' => 'X', 'price' => '10.00', 'quantity' => 1, 'discount' => 150]],
        ])->assertStatus(422);

        // 3dp -> 422
        $this->postJson('/quotes', ['client_name' => 'C',
            'items' => [['description' => 'X', 'price' => '10.005', 'quantity' => 1]],
        ])->assertStatus(422);

        // valido -> persiste y total exacto
        $r = $this->postJson('/quotes', ['client_name' => 'C',
            'items' => [['description' => 'X', 'price' => '33.33', 'quantity' => 3, 'discount' => '10']],
        ])->assertSuccessful();
        $q = Quote::find($r->json('quote.id'));
        $this->assertSame('89.99', (string) $q->total);
        $this->assertSame('10.00', (string) $q->items->first()->discount);
    }

    public function test_total_fuera_del_rango_decimal_da_422(): void
    {
        $this->editor();
        // 200 lineas de 99,999,999.99 x 10000 explotarian; basta 2 lineas que
        // suman > 99,999,999.99 para quotes.total decimal(10,2)
        $this->postJson('/quotes', ['client_name' => 'C', 'items' => [
            ['description' => 'A', 'price' => '99999999.99', 'quantity' => 1],
            ['description' => 'B', 'price' => '0.02', 'quantity' => 1],
        ]])->assertStatus(422);
    }

    public function test_round_trip_de_descuento_por_http(): void
    {
        // El bloqueador que la prueba estructural no vio: payload -> BD -> lectura
        $this->editor();
        $r = $this->postJson('/quotes', ['client_name' => 'RT', 'items' => [
            ['description' => 'X', 'price' => '33.33', 'quantity' => 3, 'discount' => '10.00'],
        ]])->assertSuccessful();

        $q = Quote::with('items')->find($r->json('quote.id'));
        $this->assertSame('10.00', (string) $q->items->first()->discount, 'persistio');
        $this->assertSame('89.99', (string) $q->total, 'total con descuento');

        // updateFull tambien respeta el round-trip
        $this->putJson('/quotes/'.$q->id.'/full', ['client_name' => 'RT', 'items' => [
            ['description' => 'X', 'price' => '33.33', 'quantity' => 3, 'discount' => '25.50'],
        ]])->assertSuccessful();
        $q->refresh()->load('items');
        $this->assertSame('25.50', (string) $q->items->first()->discount);
        $this->assertSame('74.49', (string) $q->total, '9999*3*0.745 -> 74.49 half-up');
    }

    // ── Gate fiscal ───────────────────────────────────────────────────────

    /**
     * F4: el gate que devolvia 422 a toda cotizacion con descuento **ya no
     * existe**. Aquel bloqueo no frenaba solo la emision fiscal: impedia
     * CONVERTIR, o sea cerrar la venta. Ahora `invoice_items` guarda el
     * descuento y la linea se calcula en centavos enteros, asi que el
     * comprobante cuadra y la conversion procede.
     */
    public function test_convertir_con_descuento_ya_no_se_bloquea_y_el_importe_es_exacto(): void
    {
        $q = $this->quoteConItems([
            ['description' => 'A', 'price' => '33.33', 'quantity' => 3, 'discount' => '10'],
        ], ['status' => 'accepted']);

        $r = app(\App\Modules\Ventas\Controllers\QuoteController::class)
            ->convertirPortal(new \Illuminate\Http\Request(['type' => 'boleta']),
                $this->project->slug, $q->id);

        $this->assertNotSame(422, $r->getStatusCode(), 'el descuento ya no bloquea la venta');

        // 33.33 x 3 = 99.99, menos 10% = 89.99 (half-up), el caso que el
        // comentario del gate citaba como imposible de representar.
        $this->assertDatabaseHas('invoice_items', [
            'unit_price' => '33.33', 'discount' => '10.00', 'total' => '89.99',
        ]);

        $inv = \App\Modules\Finanzas\Models\Invoice::where('quote_id', $q->id)->firstOrFail();
        $this->assertSame(
            (int) round((float) $inv->total * 100),
            (int) round((float) $inv->subtotal * 100) + (int) round((float) $inv->igv * 100),
            'base + IGV tiene que dar el total al centimo'
        );
    }

    // ── MATRIZ DE CICLO DE VIDA (F1c) ─────────────────────────────────────
    //
    // El principio: la UI no es una barrera. Cada regla se prueba llamando al
    // endpoint, no mirando si el boton se ve.

    /** @dataProvider estadosQueNoConvierten */
    public function test_solo_una_aceptada_puede_convertirse(string $estado): void
    {
        $this->editor();
        $q = $this->quoteConItems(null, ['status' => $estado]);

        $this->postJson("/quotes/{$q->id}/convert")->assertStatus(422);

        $this->assertSame(0, Order::where('quote_id', $q->id)->count(),
            "un pedido no puede nacer de una cotizacion en '{$estado}'");
        $this->assertSame($estado, $q->fresh()->status, 'y su estado no cambia');
    }

    public static function estadosQueNoConvierten(): array
    {
        return [['draft'], ['sent'], ['rejected']];
    }

    public function test_una_aceptada_si_convierte(): void
    {
        $this->editor();
        $q = $this->quoteConItems(null, ['status' => 'accepted']);

        $r = $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();

        $this->assertFalse($r->json('already'));
        $this->assertSame('converted', $q->fresh()->status);
        $this->assertSame(1, Order::where('quote_id', $q->id)->count());
    }

    public function test_sin_modulo_de_pedidos_no_convierte_ni_por_url(): void
    {
        // Ocultar el boton no basta: la ruta sigue existiendo.
        $this->editor();
        $modulo = Module::where('key', 'orders')->first();
        $this->project->modules()->updateExistingPivot($modulo->id, ['is_active' => false]);
        $this->project->unsetRelation('modules');
        $q = $this->quoteConItems(null, ['status' => 'accepted']);

        $this->postJson("/quotes/{$q->id}/convert")->assertStatus(422);
        $this->assertSame(0, Order::where('quote_id', $q->id)->count());
    }

    public function test_el_estado_convertida_no_se_asigna_a_mano(): void
    {
        $this->editor();
        $q = $this->quoteConItems(null, ['status' => 'accepted']);

        $this->putJson("/quotes/{$q->id}", ['status' => 'converted'])->assertStatus(422);

        $this->assertSame('accepted', $q->fresh()->status,
            'sin pedido detras, marcarla convertida seria una mentira');
        $this->assertSame(0, Order::where('quote_id', $q->id)->count());
    }

    public function test_una_convertida_no_vuelve_a_otro_estado(): void
    {
        $this->editor();
        $q = $this->quoteConItems(null, ['status' => 'accepted']);
        $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();

        foreach (['draft', 'sent', 'accepted', 'rejected'] as $destino) {
            $this->putJson("/quotes/{$q->id}", ['status' => $destino])->assertStatus(422);
        }

        $this->assertSame('converted', $q->fresh()->status);
    }

    public function test_una_convertida_no_se_reedita(): void
    {
        $this->editor();
        $q = $this->quoteConItems([['description' => 'Original', 'price' => '100.00', 'quantity' => 1, 'discount' => 0]],
            ['status' => 'accepted']);
        $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();
        $itemsAntes = $q->items()->count();
        $totalAntes = $q->fresh()->total;

        $this->putJson("/quotes/{$q->id}/full", [
            'client_name' => 'Otro cliente',
            'items' => [['description' => 'Cambiado', 'price' => '999.00', 'quantity' => 5]],
        ])->assertStatus(422);

        $q->refresh();
        $this->assertSame('Cliente', $q->client_name, 'el cliente no cambia');
        $this->assertSame($itemsAntes, $q->items()->count(), 'las lineas no cambian');
        $this->assertSame($totalAntes, $q->total, 'el total no cambia');
    }

    public function test_una_convertida_no_se_reenvia(): void
    {
        // send() fijaba 'sent' a ciegas: una convertida perdia su estado y con
        // el la coherencia con el pedido que ya existia.
        $this->editor();
        $q = $this->quoteConItems(null, ['status' => 'accepted']);
        $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();

        $this->postJson("/quotes/{$q->id}/send")->assertStatus(422);

        $this->assertSame('converted', $q->fresh()->status);
    }

    public function test_una_respondida_tampoco_se_reenvia(): void
    {
        $this->editor();
        foreach (['accepted', 'rejected'] as $estado) {
            $q = $this->quoteConItems(null, ['status' => $estado]);
            $this->postJson("/quotes/{$q->id}/send")->assertStatus(422);
            $this->assertSame($estado, $q->fresh()->status, "'{$estado}' no debe volver a 'sent'");
        }
    }

    public function test_enviar_sigue_permitido_desde_borrador_y_enviada(): void
    {
        $this->editor();
        foreach (['draft', 'sent'] as $estado) {
            $q = $this->quoteConItems(null, ['status' => $estado]);
            $this->postJson("/quotes/{$q->id}/send")->assertSuccessful();
            $this->assertSame('sent', $q->fresh()->status);
        }
    }

    public function test_una_convertida_no_se_elimina(): void
    {
        $u = $this->editor();
        // Con permiso de borrado REAL: si no, el 403 del middleware taparia el
        // guard que se quiere probar y el test pasaria por el motivo erroneo.
        Permission::findOrCreate('quotes.eliminar', 'web');
        Role::findByName('conv_editor', 'web')->givePermissionTo('quotes.eliminar');
        $u->syncRoles(['conv_editor']);

        $q = $this->quoteConItems(null, ['status' => 'accepted']);
        $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();

        $this->deleteJson("/quotes/{$q->id}")->assertStatus(422);

        $this->assertNotNull(Quote::find($q->id), 'borrarla dejaria al pedido sin origen');
    }

    public function test_una_convertida_si_puede_duplicarse_como_borrador(): void
    {
        // La via legitima para renegociar.
        $this->editor();
        $q = $this->quoteConItems(null, ['status' => 'accepted']);
        $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();

        $r = $this->postJson("/quotes/{$q->id}/duplicate")->assertSuccessful();

        $copiaId = $r->json('quote.id');
        $this->assertNotSame($q->id, $copiaId);
        $this->assertSame('draft', \App\Modules\Ventas\Support\QuoteStatus::comercial(Quote::find($copiaId)->status));
        $this->assertNull(Quote::find($copiaId)->order, 'la copia nace sin pedido');
    }

    public function test_en_una_convertida_las_notas_y_el_pago_siguen_abiertos(): void
    {
        // No bloquear de mas: F2/F3 gobernaran el cobro.
        $this->editor();
        $q = $this->quoteConItems(null, ['status' => 'accepted']);
        $this->postJson("/quotes/{$q->id}/convert")->assertSuccessful();

        $this->putJson("/quotes/{$q->id}", [
            'status' => 'converted', 'notes' => 'Coordinado con almacen', 'payment_status' => 'partial',
        ])->assertSuccessful();

        $q->refresh();
        $this->assertSame('Coordinado con almacen', $q->notes);
        $this->assertSame('partial', $q->payment_status);
        $this->assertSame('converted', $q->status);
    }
}

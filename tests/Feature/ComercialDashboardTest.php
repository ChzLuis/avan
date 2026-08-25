<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Quote;
use App\Models\ReceivableTerm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El panel Comercial no puede afirmar nada que no haya consultado.
 *
 * Hasta ahora abria con un "semaforo empresarial" cuyas luces de Caja,
 * Logistica y Stock eran constantes escritas en la plantilla: decia
 * "Stock: niveles normales" sin mirar el catalogo, igual que antes mostraba
 * un marcador 87/100 inventado. Estos contratos fijan lo contrario: cada
 * cifra del resumen sale de una consulta, y "Todo en orden" solo aparece
 * cuando de verdad no hay nada pendiente.
 */
class ComercialDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['orders.ver', 'quotes.ver', 'reports.ver', 'view-orders'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Comercial QA', 'slug' => 'comercial-qa', 'category' => 'retail', 'is_active' => true,
        ]);
    }

    private function entrar(): User
    {
        Role::findOrCreate('com_gerente', 'web')->syncPermissions(['orders.ver', 'quotes.ver', 'reports.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Gerente', 'spatie_role' => 'com_gerente', 'is_active' => 1]);
        $u->syncRoles(['com_gerente']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        return $u;
    }

    public function test_sin_pendientes_dice_todo_en_orden_y_no_inventa_alertas(): void
    {
        $this->entrar();

        $res = $this->get('/bixosales');

        $res->assertOk()
            ->assertSee('Resumen del negocio')
            ->assertSee('Requiere tu atención')
            ->assertSee('Todo en orden');

        // El semaforo con luces fijas ya no existe en ninguna forma.
        $res->assertDontSee('SEMÁFORO EMPRESARIAL', false)
            ->assertDontSee('Niveles normales', false)
            ->assertDontSee('Delivery en proceso', false);
    }

    public function test_el_stock_critico_sale_del_catalogo_no_de_un_texto_fijo(): void
    {
        // Uno bajo su minimo, uno agotado sin minimo, uno sano: criticos = 2.
        Product::create(['project_id' => $this->project->id, 'name' => 'Bajo mínimo',
            'price' => 10, 'stock' => 2, 'stock_min' => 5, 'is_active' => 1]);
        Product::create(['project_id' => $this->project->id, 'name' => 'Agotado',
            'price' => 10, 'stock' => 0, 'is_active' => 1]);
        Product::create(['project_id' => $this->project->id, 'name' => 'Con stock',
            'price' => 10, 'stock' => 40, 'stock_min' => 5, 'is_active' => 1]);

        $this->entrar();
        $res = $this->get('/bixosales');

        $res->assertOk()
            ->assertSee('Stock crítico')
            ->assertSee('2 productos con stock crítico')
            ->assertDontSee('Todo en orden');
    }

    public function test_lo_vencido_usa_el_vencimiento_pactado_y_coincide_con_cobranza(): void
    {
        $pedido = Order::create(['project_id' => $this->project->id, 'client_name' => 'Deudor',
            'status' => 'process', 'payment_status' => 'pending', 'total' => 250]);
        ReceivableTerm::create(['project_id' => $this->project->id, 'payable_type' => 'order',
            'payable_id' => $pedido->id, 'numero' => 1,
            'due_date' => now()->subDays(9)->toDateString(), 'amount_cents' => 25000]);

        $this->entrar();
        $res = $this->get('/bixosales');

        $res->assertOk()
            ->assertSee('1 documento vencido')
            ->assertSee('250.00');

        // La misma pregunta en las dos pantallas tiene que dar lo mismo.
        $cartera = \App\Support\Cobranza::cartera($this->project);
        $this->assertSame(25000, $cartera['total_cents']);
        $this->assertSame(25000, $cartera['vencido_cents']);
        $this->assertSame(1, $cartera['vencidas']);
    }

    public function test_el_estado_de_pedidos_cuenta_los_pedidos_del_negocio(): void
    {
        // `OrderFlow::supportsFlow()` es cierto para cualquier rubro con
        // categoria, asi que este comercio contaba por `laundry_status` —que
        // nunca rellena— y el bloque decia 0 teniendo pedidos.
        Order::create(['project_id' => $this->project->id, 'client_name' => 'A', 'status' => 'pending', 'total' => 10]);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'B', 'status' => 'process', 'total' => 20]);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'C', 'status' => 'done', 'total' => 30]);

        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('Estado de pedidos')
            ->assertSee('3 en total')
            ->assertDontSee('Todavía no hay pedidos registrados');
    }

    public function test_la_actividad_no_ensena_nombres_internos_de_evento(): void
    {
        $pedido = Order::create(['project_id' => $this->project->id, 'client_name' => 'A',
            'status' => 'pending', 'total' => 10]);
        OrderEvent::create(['project_id' => $this->project->id, 'order_id' => $pedido->id,
            'action' => 'payment_registered']);

        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('Pago registrado')
            ->assertDontSee('payment_registered');
    }

    public function test_la_actividad_reciente_muestra_hechos_registrados(): void
    {
        $cot = Quote::create(['project_id' => $this->project->id, 'client_name' => 'Cliente',
            'status' => 'sent', 'total' => 80, 'token' => str()->random(24)]);
        OrderEvent::create(['project_id' => $this->project->id, 'quote_id' => $cot->id,
            'action' => 'accepted_by_client']);

        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('Actividad reciente')
            ->assertSee('Cotización aceptada por el cliente');
    }

    public function test_una_cotizacion_no_es_deuda_es_trabajo_pendiente(): void
    {
        // Una cotizacion es un documento PRE-VENTA: no reconoce ingreso ni
        // genera obligacion de pago. La deuda nace del pedido y su
        // comprobante, como en cualquier ERP. Lo que queda pendiente de una
        // cotizacion aceptada es CONVERTIRLA, y eso es una accion, no dinero.
        Order::create(['project_id' => $this->project->id, 'client_name' => 'Debe',
            'status' => 'process', 'payment_status' => 'pending', 'total' => 1000]);
        Quote::create(['project_id' => $this->project->id, 'client_name' => 'Aceptó',
            'status' => 'accepted', 'payment_status' => 'pending', 'total' => 500,
            'token' => str()->random(24)]);

        // La cartera solo tiene el pedido.
        $cartera = \App\Support\Cobranza::cartera($this->project);
        $this->assertSame(100000, $cartera['total_cents']);
        $this->assertCount(1, $cartera['filas']);
        $this->assertSame('pedido', $cartera['filas'][0]['tipo']);

        // Y la cotizacion aparece como trabajo por hacer, con su importe.
        $pendiente = \App\Support\Cobranza::aceptadasSinConvertir($this->project);
        $this->assertSame(1, $pendiente['n']);
        $this->assertSame(50000, $pendiente['cents']);

        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('S/ 1,000.00')                              // por cobrar: solo la venta
            ->assertSee('1 cotización aceptada sin convertir')      // lo otro: una accion
            ->assertSee('Convertir');
    }

    public function test_la_cola_prioriza_lo_mas_antiguo_y_no_lo_pinta_todo_de_rojo(): void
    {
        // Umbrales del proyecto: aviso a las 24 h, critico a las 72.
        // `created_at` no es asignable en masa: hay que fijarla despues, o
        // los dos pedidos nacen con la misma hora y no hay antiguedad que
        // ordenar.
        $reciente = Order::create(['project_id' => $this->project->id, 'client_name' => 'Recién llegado',
            'status' => 'pending', 'total' => 100]);
        $reciente->forceFill(['created_at' => now()->subHours(2)])->save();
        $viejo = Order::create(['project_id' => $this->project->id, 'client_name' => 'Pool Espinoza',
            'status' => 'pending', 'total' => 1266]);
        $viejo->forceFill(['created_at' => now()->subHours(120)])->save();

        $this->entrar();
        $res = $this->get('/bixosales')->assertOk();

        $res->assertSee('Pedidos que requieren atención')
            ->assertSee('#' . $viejo->id)
            ->assertSee('Pool Espinoza');

        // El mas antiguo va primero: es lo que lleva mas tiempo molestando.
        $html = $res->getContent();
        $this->assertLessThan(
            strpos($html, 'Recién llegado'),
            strpos($html, 'Pool Espinoza'),
            'la cola debe ordenarse por antigüedad'
        );

        // Y el de 2 horas no se marca como urgente: si todo es rojo, nada lo es.
        $this->assertStringContainsString('text-red-600', $html);   // el de 120 h
        $this->assertMatchesRegularExpression('/text-slate-500[^>]*>\s*2 h/s', $html);
    }

    public function test_la_conversion_de_cotizaciones_sale_de_las_enviadas(): void
    {
        // 4 enviadas: 1 sigue enviada, 1 aceptada, 2 convertidas → 50%.
        foreach ([['sent', 1], ['accepted', 1], ['converted', 2]] as [$estado, $n]) {
            for ($i = 0; $i < $n; $i++) {
                Quote::create(['project_id' => $this->project->id, 'client_name' => 'C',
                    'status' => $estado, 'total' => 100, 'token' => str()->random(24),
                    'sent_at' => now()->subDay()]);
            }
        }
        // Un borrador nunca enviado no entra en el denominador.
        Quote::create(['project_id' => $this->project->id, 'client_name' => 'Borrador',
            'status' => 'draft', 'total' => 50, 'token' => str()->random(24)]);

        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('Conversión de cotizaciones')
            ->assertSee('50%');
    }

    public function test_el_dashboard_ya_no_repite_los_mismos_pedidos_en_cinco_bloques(): void
    {
        Order::create(['project_id' => $this->project->id, 'client_name' => 'A', 'status' => 'pending', 'total' => 10]);

        $this->entrar();
        $res = $this->get('/bixosales')->assertOk();

        $res->assertDontSee('Pedidos en curso')
            ->assertDontSee('Últimos pedidos')
            // "Acceso rápido" del cajon derecho es otra cosa y sigue ahi;
            // lo que se retira es la seccion del final del panel.
            ->assertDontSee('tit-acceso', false)
            ->assertDontSee('Productos más vendidos');
    }

    public function test_los_pedidos_en_cola_ofrecen_una_accion_real(): void
    {
        Order::create(['project_id' => $this->project->id, 'client_name' => 'A',
            'status' => 'pending', 'total' => 10]);
        Order::create(['project_id' => $this->project->id, 'client_name' => 'B',
            'status' => 'pending', 'total' => 20]);

        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('Atender')
            ->assertSee(route('bixosales.pedidos'), false);
    }

    /** Un comprobante que SUNAT aun no acepta muere a los 3 dias: el resumen
     *  lo avisa con la cuenta atras, y va antes que cualquier otro aviso. */
    public function test_un_comprobante_en_error_dentro_del_plazo_avisa_con_cuenta_atras(): void
    {
        $this->project->invoices()->create([
            'type' => 'factura', 'serie' => 'F001', 'correlativo' => 7, 'numero' => 'F001-00000007',
            'client_name' => 'Cliente QA', 'subtotal' => '100.00', 'igv' => '18.00', 'total' => '118.00',
            'currency' => 'PEN', 'issue_date' => now()->subDay()->toDateString(),
            'status' => 'issued', 'sunat_status' => 'error',
        ]);
        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertSee('1 comprobante sin aceptar por SUNAT')
            ->assertSee('2 días de plazo')
            ->assertSee(route('bixosales.facturas'), false);
    }

    /** Un 'pending' recien emitido no es una alarma: su job de envio esta en
     *  camino. Y uno ya fuera del plazo tampoco: ya no se puede enviar. */
    public function test_ni_el_pending_recien_emitido_ni_el_ya_vencido_disparan_el_aviso(): void
    {
        $base = [
            'type' => 'factura', 'serie' => 'F001', 'client_name' => 'Cliente QA',
            'subtotal' => '100.00', 'igv' => '18.00', 'total' => '118.00',
            'currency' => 'PEN', 'status' => 'issued',
        ];
        // Recien emitido, en cola.
        $this->project->invoices()->create($base + [
            'correlativo' => 8, 'numero' => 'F001-00000008',
            'issue_date' => now()->toDateString(), 'sunat_status' => 'pending',
        ]);
        // En error pero ya irrecuperable: fuera de los 3 dias.
        $this->project->invoices()->create($base + [
            'correlativo' => 9, 'numero' => 'F001-00000009',
            'issue_date' => now()->subDays(10)->toDateString(), 'sunat_status' => 'error',
        ]);
        $this->entrar();

        $this->get('/bixosales')
            ->assertOk()
            ->assertDontSee('sin aceptar por SUNAT');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cadena cotización → comprobante → guía (auditoría 2026-09-04).
 *
 * Aquí se protege lo que cuesta dinero o llega al camión: que una cotización
 * no se facture dos veces, y que una guía no viaje con datos inventados.
 */
class CadenaVentaGuiaTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'owner_id' => $this->user->id,
            'name' => 'Cadena QA', 'slug' => 'cadena-qa', 'is_active' => true,
        ]);

        // El portal de cotizaciones exige permiso de edición.
        \Spatie\Permission\Models\Permission::findOrCreate('quotes.editar', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('invoices.crear', 'web');
        setPermissionsTeamId($this->project->id);
        $this->user->givePermissionTo(['quotes.editar', 'invoices.crear']);

        // La cara de Operación exige el módulo contratado.
        $modulo = \App\Models\Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$modulo->id => ['is_active' => true]]);
        $this->project->settings()->createMany([
            ['key' => 'serie_factura', 'value' => 'F001'],
            ['key' => 'serie_boleta',  'value' => 'B001'],
            ['key' => 'serie_guia',    'value' => 'T001'],
        ]);
    }

    private function comoDueno()
    {
        // La cara de Operación (bixosales) exige su propia clave de sesión.
        return $this->actingAs($this->user)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
            // El portal fiscal (/f/{slug}) tiene su propia llave de sesión.
            "facturacion_auth.{$this->project->slug}" => true,
        ]);
    }

    private function cotizacion(): Quote
    {
        $quote = Quote::create([
            'project_id' => $this->project->id,
            'numero' => 'COT-001', 'client_name' => 'CLIENTE SAC',
            'status' => 'accepted', 'total' => 118, 'subtotal' => 100, 'igv' => 18,
        ]);
        // `quote_items` usa `price`, no `unit_price`.
        $quote->items()->create([
            'description' => 'Cable', 'quantity' => 1, 'price' => 118, 'discount' => 0,
        ]);

        return $quote;
    }

    // ── Una cotización se factura UNA vez ────────────────────────────────

    public function test_convertir_dos_veces_no_emite_dos_comprobantes(): void
    {
        /* `convertirPortal` era copia de `convert()` sin sus defensas: doble
           clic emitía DOS comprobantes, quemaba dos correlativos y declaraba
           el mismo importe dos veces. Corregirlo exige nota de crédito. */
        $quote = $this->cotizacion();
        $ruta  = route('facturacion.cotizaciones.convertir', ['slug' => $this->project->slug, 'id' => $quote->id]);

        $uno = $this->comoDueno()->postJson($ruta, ['type' => 'boleta']);
        $dos = $this->comoDueno()->postJson($ruta, ['type' => 'boleta']);

        $uno->assertOk();
        $dos->assertOk();
        $this->assertTrue((bool) $dos->json('already'), 'La segunda debe avisar de que ya se facturó');
        $this->assertSame(1, Invoice::where('project_id', $this->project->id)->count());
    }

    public function test_una_cotizacion_ya_convertida_no_retrocede_de_estado(): void
    {
        // Retroceder a "aceptada" reabría el botón de convertir a pedido.
        $quote = $this->cotizacion();
        $quote->update(['status' => 'converted']);

        $this->comoDueno()->postJson(
            route('facturacion.cotizaciones.convertir', ['slug' => $this->project->slug, 'id' => $quote->id]),
            ['type' => 'boleta']
        );

        $this->assertSame('converted', $quote->fresh()->status);
    }

    // ── La guía no viaja con datos inventados ────────────────────────────

    private function guia(array $extra = []): array
    {
        return array_merge([
            'destinatario_nombre' => 'CLIENTE SAC',
            'motivo_codigo'       => '01',
            'fecha_traslado'      => now()->toDateString(),
            'modalidad'           => '02',
            'vehiculo_m1l'        => true,
            'peso_total'          => 120,
            'partida_direccion'   => 'JR. HUAROCHIRI 05',
            'partida_ubigeo'      => '150101',
            'llegada_direccion'   => 'AV. EJEMPLO 123',
            'llegada_ubigeo'      => '040101',
            'items'               => [['description' => 'Cable', 'quantity' => 5]],
        ], $extra);
    }

    public function test_una_guia_sin_ubigeo_no_se_emite(): void
    {
        /* Sin ubigeo caía en '150101' hardcodeado: un traslado Arequipa→Cusco
           se declaraba Lima→Lima y SUNAT lo ACEPTABA. Datos falsos en el
           documento que va en el camión. */
        $this->comoDueno()->postJson(
            route('bixosales.guias.store'),
            $this->guia(['partida_ubigeo' => null, 'llegada_ubigeo' => null])
        )->assertStatus(422)->assertJsonValidationErrors(['partida_ubigeo', 'llegada_ubigeo']);
    }

    public function test_el_ubigeo_debe_ser_de_seis_digitos(): void
    {
        $this->comoDueno()->postJson(route('bixosales.guias.store'), $this->guia(['llegada_ubigeo' => 'LIMA']))
            ->assertStatus(422)->assertJsonValidationErrors('llegada_ubigeo');
    }

    public function test_una_guia_con_fecha_de_hace_meses_no_se_emite(): void
    {
        // SUNAT la rechaza, y eso se descubre con el camión en carretera.
        $this->comoDueno()->postJson(
            route('bixosales.guias.store'),
            $this->guia(['fecha_traslado' => now()->subMonths(6)->toDateString()])
        )->assertStatus(422)->assertJsonValidationErrors('fecha_traslado');
    }

    public function test_una_guia_no_se_respalda_con_un_comprobante_sin_aceptar(): void
    {
        /* Antes valía cualquier comprobante del negocio: un borrador (sin
           número) o uno rechazado entraban igual. */
        $borrador = Invoice::create([
            'project_id' => $this->project->id, 'type' => 'boleta', 'serie' => 'B001',
            'correlativo' => 1, 'numero' => 'B001-00000001', 'client_name' => 'X',
            'subtotal' => 100, 'igv' => 18, 'total' => 118, 'status' => 'draft',
        ]);

        $this->comoDueno()->postJson(route('bixosales.guias.store'), $this->guia(['invoice_id' => $borrador->id]))
            ->assertStatus(422)->assertJsonValidationErrors('invoice_id');
    }

    public function test_una_guia_bien_formada_si_se_emite(): void
    {
        // No se puede endurecer de más: lo correcto tiene que pasar.
        $this->comoDueno()->postJson(route('bixosales.guias.store'), $this->guia())
            ->assertOk();
    }

    public function test_el_ruc_del_transportista_son_once_digitos(): void
    {
        // `size:11` admitía "ABCDEFGHIJK"; SUNAT lo rechaza con error 2564.
        $this->comoDueno()->postJson(route('bixosales.guias.store'), $this->guia([
            'modalidad' => '01', 'transportista_ruc' => 'ABCDEFGHIJK',
            'transportista_razon_social' => 'TRANSPORTES SAC',
        ]))->assertStatus(422)->assertJsonValidationErrors('transportista_ruc');
    }

    // ── Buscador de productos en todos los documentos ────────────────────

    public function test_la_guia_ofrece_el_catalogo_del_negocio(): void
    {
        /* En la guía se tecleaba la descripción a mano: erratas, y la línea
           salía sin enlace al producto. Ahora se elige del mismo catálogo
           que los comprobantes. */
        \App\Models\Product::create([
            'project_id' => $this->project->id, 'name' => 'ROLLOS DE CABLE NH4 X100M.',
            'sku' => 'NH4-100', 'price' => 275, 'unit' => 'NIU', 'is_available' => true,
        ]);

        $html = $this->comoDueno()->get(route('bixosales.guias.index'))->assertOk()->getContent();

        $this->assertStringContainsString('ROLLOS DE CABLE NH4 X100M.', $html,
            'El catálogo debe llegar al formulario de guías');
        $this->assertStringContainsString('buscarProducto', $html);
        $this->assertStringContainsString('Buscar producto por nombre o SKU', $html);
    }

    public function test_la_guia_no_ve_productos_de_otro_negocio(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno-guia', 'is_active' => true,
        ]);
        \App\Models\Product::create([
            'project_id' => $otro->id, 'name' => 'PRODUCTO AJENO',
            'price' => 10, 'is_available' => true,
        ]);

        $html = $this->comoDueno()->get(route('bixosales.guias.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('PRODUCTO AJENO', $html);
    }

    public function test_lo_no_disponible_no_se_ofrece(): void
    {
        // Ofrecer lo que no se vende lleva a emitir un documento con algo
        // que no hay.
        \App\Models\Product::create([
            'project_id' => $this->project->id, 'name' => 'DESCATALOGADO SA',
            'price' => 10, 'is_available' => false,
        ]);

        $html = $this->comoDueno()->get(route('bixosales.guias.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('DESCATALOGADO SA', $html);
    }

    public function test_dos_toques_no_emiten_dos_guias(): void
    {
        /* Con el camión en la puerta y mala señal, el operador pulsa Emitir,
           tarda, recarga y vuelve a pulsar: salían dos guías con dos
           correlativos quemados. */
        $huella = ['X-Idempotencia' => 'guia-doble-toque'];

        $uno = $this->comoDueno()->withHeaders($huella)
            ->postJson(route('bixosales.guias.store'), $this->guia());
        $dos = $this->comoDueno()->withHeaders($huella)
            ->postJson(route('bixosales.guias.store'), $this->guia());

        $uno->assertOk();
        $this->assertTrue((bool) $dos->json('repetida'), 'La segunda debe devolver la misma guía');
        $this->assertSame(1, \App\Models\GuiaRemision::where('project_id', $this->project->id)->count());
    }

    /**
     * Si la emision FALLA, el reintento entra: no se queda bloqueado.
     *
     * El candado contra doble emision solo se soltaba al crear la guia con
     * exito. Cuando la emision fallaba (un dato que SUNAT rechaza, una
     * validacion), quedaba puesto 30 segundos: el operador corregia el dato,
     * volvia a pulsar y recibia "Esa guia ya se esta emitiendo" sin que hubiera
     * ninguna guia emitiendose. Con el camion en la puerta, es el peor momento
     * para esperar a ciegas.
     */
    public function test_si_la_emision_falla_el_reintento_no_queda_bloqueado(): void
    {
        $huella = ['X-Idempotencia' => 'guia-que-fallo'];

        // Primer intento con un dato invalido: la guia no llega a crearse.
        $malo = $this->guia();
        $malo['motivo_codigo'] = 'INVENTADO';
        $this->comoDueno()->withHeaders($huella)
            ->postJson(route('bixosales.guias.store'), $malo)
            ->assertStatus(422);

        $this->assertSame(0, \App\Models\GuiaRemision::where('project_id', $this->project->id)->count(),
            'un dato invalido no debe crear la guia');

        // El operador corrige y reintenta: tiene que pasar, no chocar contra el candado.
        $r = $this->comoDueno()->withHeaders($huella)
            ->postJson(route('bixosales.guias.store'), $this->guia());

        $this->assertNotSame(409, $r->getStatusCode(),
            'tras un fallo, el reintento no puede recibir "ya se esta emitiendo"');
        $r->assertOk();
        $this->assertSame(1, \App\Models\GuiaRemision::where('project_id', $this->project->id)->count());
    }

    public function test_dos_traslados_distintos_si_generan_dos_guias(): void
    {
        // La protección no puede impedir despachar dos veces de verdad.
        $this->comoDueno()->withHeaders(['X-Idempotencia' => 'traslado-1'])
            ->postJson(route('bixosales.guias.store'), $this->guia())->assertOk();
        $this->comoDueno()->withHeaders(['X-Idempotencia' => 'traslado-2'])
            ->postJson(route('bixosales.guias.store'), $this->guia())->assertOk();

        $this->assertSame(2, \App\Models\GuiaRemision::where('project_id', $this->project->id)->count());
    }

    public function test_la_guia_se_puede_llenar_desde_el_telefono(): void
    {
        /* Se emite junto al camión, de pie y con prisa: el campo tiene que
           aceptar el dedo y el botón de emitir no puede estar al final de
           veinticinco campos de scroll. */
        $html = $this->comoDueno()->get(route('bixosales.guias.index'))->assertOk()->getContent();

        // 16px evita el zoom automático de iOS, que descuadra la página.
        $this->assertStringContainsString('font-size: 16px; min-height: 46px;', $html);
        $this->assertStringContainsString('guia-pie', $html, 'El botón de emitir debe viajar pegado abajo');

        // El RUC y el DNI con teclado numérico, no QWERTY.
        $this->assertMatchesRegularExpression(
            '/transportista_ruc[^>]*inputmode="numeric"|inputmode="numeric"[^>]*transportista_ruc/', $html);
    }
}

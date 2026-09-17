<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Support\Lector\EmparejadorCatalogo;
use App\Support\Lector\LectorComprobantes;
use App\Support\Lector\Normalizador;
use App\Support\Lector\ValidadorLectura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lector de comprobantes (2026-09-04).
 *
 * Lo que se protege aquí es lo que puede acabar en una declaración a SUNAT:
 * que no se confunda el emisor con el cliente, que no se invente un dato que
 * no estaba en el papel, y que una lectura torcida se avise en vez de pasar
 * en silencio.
 */
class LectorComprobantesTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Lector QA', 'slug' => 'lector-qa', 'is_active' => true,
        ]);
    }

    // ── Interruptor ──────────────────────────────────────────────────────

    public function test_apagado_el_lector_no_esta_disponible(): void
    {
        $this->assertFalse(LectorComprobantes::disponible($this->project));
    }

    public function test_encendido_pero_sin_clave_sigue_sin_estar_disponible(): void
    {
        // Un botón que siempre falla es peor que no tener el botón.
        config(['ia.providers.anthropic.key' => null]);
        $this->project->settings()->create(['key' => 'lector_comprobantes', 'value' => '1']);

        $this->assertFalse(LectorComprobantes::disponible($this->project->fresh()));
    }

    public function test_la_clave_del_negocio_manda_sobre_la_global(): void
    {
        config(['ia.providers.anthropic.key' => 'global']);
        $this->project->settings()->createMany([
            ['key' => 'lector_comprobantes', 'value' => '1'],
            ['key' => 'lector_api_key', 'value' => 'propia-del-negocio'],
        ]);

        $this->assertTrue(LectorComprobantes::disponible($this->project->fresh()));
    }

    // ── Normalización: nunca inventar, siempre traducir ──────────────────

    public function test_la_fecha_peruana_se_entiende(): void
    {
        $r = Normalizador::aplicar(['fecha_emision' => '04/09/2026']);

        $this->assertSame('2026-09-04', $r['fecha_emision']);
    }

    public function test_una_fecha_ilegible_queda_vacia_y_no_se_inventa(): void
    {
        $r = Normalizador::aplicar(['fecha_emision' => 'no se lee']);

        $this->assertNull($r['fecha_emision']);
    }

    public function test_los_importes_con_simbolo_y_miles_se_leen(): void
    {
        $r = Normalizador::aplicar([
            'items' => [['descripcion' => 'CABLE', 'cantidad' => '7', 'precio_unitario' => 'S/ 1,925.50']],
        ]);

        $this->assertSame(1925.50, $r['items'][0]['precio_unitario']);
    }

    public function test_el_tipo_se_deduce_del_documento_del_cliente(): void
    {
        // Sin etiqueta legible: un RUC solo puede ir en factura.
        $r = Normalizador::aplicar(['cliente' => ['numero_doc' => '20601234567']]);

        $this->assertSame('factura', $r['tipo']);
        $this->assertSame('RUC', $r['cliente']['tipo_doc']);
    }

    public function test_las_unidades_del_papel_se_traducen_al_codigo_de_sunat(): void
    {
        $r = Normalizador::aplicar([
            'items' => [
                ['descripcion' => 'A', 'unidad' => 'rollos'],
                ['descripcion' => 'B', 'unidad' => 'KG'],
                ['descripcion' => 'C', 'unidad' => 'no existe'],
            ],
        ]);

        $this->assertSame('NIU', $r['items'][0]['unidad']);
        $this->assertSame('KGM', $r['items'][1]['unidad']);
        $this->assertSame('NIU', $r['items'][2]['unidad'], 'Lo desconocido cae en unidad, no se inventa');
    }

    public function test_una_linea_sin_descripcion_no_es_una_linea(): void
    {
        $r = Normalizador::aplicar([
            'items' => [['descripcion' => 'REAL', 'cantidad' => 1], ['descripcion' => '', 'cantidad' => 5]],
        ]);

        $this->assertCount(1, $r['items']);
    }

    // ── Emparejado con el catálogo ───────────────────────────────────────

    public function test_el_sku_exacto_gana_a_cualquier_parecido(): void
    {
        $bueno = Product::create([
            'project_id' => $this->project->id, 'name' => 'Otro nombre por completo',
            'sku' => 'RF-300', 'price' => 120,
        ]);
        Product::create([
            'project_id' => $this->project->id, 'name' => 'REFLECTOR SOLAR 300W', 'price' => 99,
        ]);

        $items = (new EmparejadorCatalogo($this->project))->items([
            ['codigo' => 'RF-300', 'descripcion' => 'REFLECTOR SOLAR 300W', 'cantidad' => 1, 'precio_unitario' => 100],
        ]);

        $this->assertSame($bueno->id, $items[0]['product_id']);
    }

    public function test_un_nombre_abreviado_encuentra_el_producto_del_catalogo(): void
    {
        $p = Product::create([
            'project_id' => $this->project->id,
            'name' => 'Reflector Solar LED 300W IP67', 'price' => 150,
        ]);

        $items = (new EmparejadorCatalogo($this->project))->items([
            ['descripcion' => 'REFLECTOR SOLAR 300W', 'cantidad' => 2, 'precio_unitario' => 150],
        ]);

        $this->assertNotEmpty($items[0]['sugerencias']);
        $this->assertSame($p->id, $items[0]['sugerencias'][0]['id']);
    }

    public function test_sin_parecido_razonable_no_se_inventa_una_coincidencia(): void
    {
        Product::create(['project_id' => $this->project->id, 'name' => 'Cemento Sol 42.5 kg', 'price' => 30]);

        $items = (new EmparejadorCatalogo($this->project))->items([
            ['descripcion' => 'SERVICIO DE FLETE INTERPROVINCIAL', 'cantidad' => 1, 'precio_unitario' => 80],
        ]);

        $this->assertNull($items[0]['product_id']);
        $this->assertEmpty($items[0]['sugerencias']);
    }

    public function test_la_descripcion_del_papel_se_conserva_para_trazabilidad(): void
    {
        Product::create(['project_id' => $this->project->id, 'name' => 'Reflector Solar LED 300W', 'price' => 150]);

        $items = (new EmparejadorCatalogo($this->project))->items([
            ['descripcion' => 'REFLECT SOL 300W', 'cantidad' => 1, 'precio_unitario' => 150],
        ]);

        $this->assertSame('REFLECT SOL 300W', $items[0]['descripcion_origen']);
    }

    public function test_el_cliente_ya_conocido_manda_sobre_lo_leido(): void
    {
        // Lo registrado viene de SUNAT o lo tecleó el negocio: una foto
        // borrosa no puede pisar un dato bueno.
        Invoice::create([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001',
            'correlativo' => 1, 'numero' => 'F001-00000001',
            'client_name' => 'CORPORACION PALHTRAK S.A.C.',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20612747041',
            'client_address' => 'MZA. C2 LOTE 12, BELLAVISTA',
            'subtotal' => 100, 'igv' => 18, 'total' => 118, 'status' => 'issued',
        ]);

        $r = (new EmparejadorCatalogo($this->project))->cliente([
            'tipo_doc' => 'RUC', 'numero_doc' => '20612747041',
            'razon_social' => 'CORPORACION PALTHA',  // mal leído
            'direccion' => null,
        ]);

        $this->assertTrue($r['conocido']);
        $this->assertSame('CORPORACION PALHTRAK S.A.C.', $r['razon_social']);
        $this->assertSame('MZA. C2 LOTE 12, BELLAVISTA', $r['direccion']);
    }

    // ── Validación: avisar, no bloquear ──────────────────────────────────

    public function test_se_avisa_cuando_el_total_no_cuadra_con_las_lineas(): void
    {
        $r = (new ValidadorLectura($this->project))->revisar([
            'tipo' => 'boleta', 'cliente' => ['tipo_doc' => 'DNI', 'numero_doc' => '10476818'],
            'igv_incluido' => true,
            'items' => [['descripcion' => 'A', 'cantidad' => 2, 'precio_unitario' => 100, 'descuento' => 0]],
            'totales' => ['total' => 500],
        ]);

        $this->assertNotEmpty($r['avisos']);
        $this->assertStringContainsString('diferencia', implode(' ', $r['avisos']));
        $this->assertContains('totales.total', $r['revisar']);
    }

    public function test_una_factura_sin_ruc_se_avisa_antes_de_emitir(): void
    {
        $r = (new ValidadorLectura($this->project))->revisar([
            'tipo' => 'factura', 'cliente' => ['tipo_doc' => 'DNI', 'numero_doc' => '10476818'],
            'items' => [['descripcion' => 'A', 'cantidad' => 1, 'precio_unitario' => 10]],
            'totales' => ['total' => 10],
        ]);

        $this->assertStringContainsString('RUC', implode(' ', $r['avisos']));
    }

    public function test_se_detecta_un_comprobante_ya_importado(): void
    {
        Invoice::create([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001',
            'correlativo' => 25, 'numero' => 'F001-00000025', 'client_name' => 'X',
            'subtotal' => 100, 'igv' => 18, 'total' => 118, 'status' => 'issued',
        ]);

        $r = (new ValidadorLectura($this->project))->revisar([
            'tipo' => 'factura', 'serie' => 'F001', 'numero' => '00000025',
            'cliente' => ['tipo_doc' => 'RUC', 'numero_doc' => '20601234567'],
            'items' => [], 'totales' => [],
        ]);

        $this->assertNotNull($r['duplicado']);
        $this->assertSame('F001-00000025', $r['duplicado']['numero']);
    }

    public function test_los_campos_con_poca_confianza_se_marcan_para_revisar(): void
    {
        $r = (new ValidadorLectura($this->project))->revisar([
            'tipo' => 'boleta', 'cliente' => [],
            'items' => [['descripcion' => 'A', 'cantidad' => 1, 'precio_unitario' => 10]],
            'totales' => ['total' => 10],
            'confianza' => ['serie' => 0.4, 'total' => 0.99],
        ]);

        $this->assertContains('serie', $r['revisar']);
        $this->assertNotContains('total', $r['revisar']);
    }

    // ── La puerta: sin permiso no se lee ─────────────────────────────────

    public function test_sin_la_funcion_activada_el_endpoint_responde_403(): void
    {
        $user = User::factory()->create();
        $this->project->update(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['active_project_id' => $this->project->id])
            ->postJson(route('invoices.lector'), [])
            ->assertStatus(403);
    }
}

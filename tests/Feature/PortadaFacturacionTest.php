<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\Invoice;
use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Portada del modulo de Facturacion (2026-09-05), con la estructura de la app
 * de SUNAT: pendientes arriba, una tarjeta por accion debajo.
 */
class PortadaFacturacionTest extends TestCase
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
            'name' => 'Portada QA', 'slug' => 'portada-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        $this->project->settings()->createMany([
            ['key' => 'ruc', 'value' => '20123456789'],
            ['key' => 'razon_social', 'value' => 'PORTADA QA SAC'],
            ['key' => 'apisperu_token', 'value' => 'token-de-prueba'],
        ]);
    }

    /**
     * La portada es la cara MOVIL de Facturacion (2026-09-11): en escritorio
     * redirige a Facturas. Estos tests miran su contenido, asi que entran como
     * telefono.
     */
    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ])->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0 Mobile Safari/537.36',
        ]);
    }

    public function test_la_portada_tiene_una_fila_por_area_con_sus_acciones(): void
    {
        $r = $this->comoDueno()->get(route('bixosales.facturacion'));
        $html = $r->assertOk()->getContent();

        foreach (['Comprobantes de pago', 'Guía de Remisión Electrónica', 'Cobranzas y pagos', 'Consultas', 'Reportes'] as $fila) {
            $this->assertStringContainsString($fila, $html);
        }
        // Cada tarjeta lleva a un sitio real.
        $this->assertStringContainsString(route('bixosales.facturas', ['tipo' => 'boleta']), $html);
        $this->assertStringContainsString(route('bixosales.facturas.consulta'), $html);
        $this->assertStringContainsString(route('guias.index'), $html);
        $this->assertStringContainsString(route('bixosales.facturas.registro'), $html);
        // Sin nada pendiente se dice claramente.
        $this->assertStringContainsString('Todo en orden', $html);
    }

    public function test_un_comprobante_sin_aceptar_encabeza_la_portada_con_su_plazo(): void
    {
        Invoice::create([
            'project_id' => $this->project->id, 'type' => 'factura', 'serie' => 'F001',
            'correlativo' => 1, 'numero' => 'F001-00000001', 'client_name' => 'CLIENTE',
            'status' => 'issued', 'sunat_status' => 'error',
            'issue_date' => now()->subDay()->toDateString(),
            'subtotal' => 100, 'igv' => 18, 'total' => 118,
        ]);

        $r = $this->comoDueno()->get(route('bixosales.facturacion'));
        $this->assertSame(200, $r->status(), 'Redirige a: '.$r->headers->get('Location'));
        $html = $r->getContent();

        $this->assertStringContainsString('1 asunto pendiente', $html);
        $this->assertStringContainsString('1 comprobante sin aceptar por SUNAT', $html);
        $this->assertStringContainsString('Enviar ahora', $html);
        $this->assertStringNotContainsString('Todo en orden', $html);
    }

    public function test_el_registro_de_ventas_tambien_vive_en_sales(): void
    {
        $this->comoDueno()->get(route('bixosales.facturas.registro', ['mes' => now()->format('Y-m')]))
            ->assertOk();
    }

    public function test_sin_permiso_de_ver_no_hay_portada(): void
    {
        $otro = User::factory()->create(['is_superadmin' => 0]);
        $this->actingAs($otro)->withSession(['comercial_project_id' => $this->project->id])
            ->get(route('bixosales.facturacion'))
            ->assertStatus(403);
    }
}

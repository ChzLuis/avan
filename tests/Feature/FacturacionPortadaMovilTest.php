<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La portada de Facturacion (saludo, asuntos pendientes, tarjetas) es de
 * MOVIL. En escritorio sobraba: dos clics para llegar a lo mismo que ofrece
 * Facturas (2026-09-11).
 */
class FacturacionPortadaMovilTest extends TestCase
{
    use RefreshDatabase;

    private const ESCRITORIO = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0 Safari/537.36';
    private const TELEFONO   = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0 Mobile Safari/537.36';
    private const IPHONE     = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private Project $project;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_superadmin' => true]);
        $this->project = Project::create([
            'owner_id' => $this->user->id, 'name' => 'Ferretería QA', 'slug' => 'pm-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['invoices', 'facturas'] as $key) {
            $m = \App\Models\Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function como(string $ua)
    {
        return $this->actingAs($this->user)
            // El portal de Ventas lleva su propia clave de sesion.
            ->withSession(['comercial_project_id' => $this->project->id, 'active_project_id' => $this->project->id])
            ->withHeaders(['User-Agent' => $ua]);
    }

    public function test_en_escritorio_va_directo_a_facturas(): void
    {
        $this->como(self::ESCRITORIO)->get(route('bixosales.facturacion'))
            ->assertRedirect(route('bixosales.facturas'));
    }

    public function test_en_el_telefono_se_ve_la_portada(): void
    {
        $this->como(self::TELEFONO)->get(route('bixosales.facturacion'))->assertOk()->assertSee('Hola,');
        $this->como(self::IPHONE)->get(route('bixosales.facturacion'))->assertOk()->assertSee('Hola,');
    }

    /** `?movil=1` fuerza la portada desde cualquier equipo (para probarla). */
    public function test_se_puede_forzar_desde_escritorio(): void
    {
        $this->como(self::ESCRITORIO)->get(route('bixosales.facturacion', ['movil' => 1]))->assertOk()->assertSee('Hola,');
    }

    /** El menu de escritorio no ofrece la entrada: se marca para ocultarla. */
    public function test_la_entrada_del_menu_se_marca_como_solo_movil(): void
    {
        $html = $this->como(self::ESCRITORIO)->get(route('bixosales.facturas'))->assertOk()->getContent();

        $this->assertStringContainsString('nav-solo-movil', $html);
        $this->assertStringContainsString('.nav-item.nav-solo-movil { display: none; }', $html);
    }
}

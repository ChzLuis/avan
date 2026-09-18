<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Modules\Finanzas\Models\Invoice;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Un documento, una sección (2026-09-02).
 *
 * Facturas, boletas y notas son trámites distintos ante SUNAT, con serie y
 * numeración propias. Estaban tras un único enlace "Facturas" que mezclaba
 * los tres en la misma lista, y el formulario nacía siempre en "boleta":
 * emitir una factura obligaba a cambiar el tipo a mano cada vez.
 */
class ComprobantesPorTipoTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['invoices.ver', 'orders.ver'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Fiscal QA', 'slug' => 'fiscal-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        $m = Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        $this->project->settings()->createMany([
            ['key' => 'serie_factura', 'value' => 'F001'],
            ['key' => 'serie_boleta',  'value' => 'B001'],
        ]);

        Role::findOrCreate('fiscal_qa', 'web')->syncPermissions(['invoices.ver', 'orders.ver']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Cajero', 'spatie_role' => 'fiscal_qa', 'is_active' => 1]);
        $u->syncRoles(['fiscal_qa']);
        $this->actingAs($u)->withSession([
            'active_project_id'    => $this->project->id,
            'comercial_project_id' => $this->project->id,
        ]);

        foreach ([['factura', 'F001', 'Cliente Factura'], ['boleta', 'B001', 'Cliente Boleta'],
                  ['nota_credito', 'F001', 'Cliente Nota']] as $i => [$tipo, $serie, $cliente]) {
            Invoice::create([
                'project_id' => $this->project->id, 'type' => $tipo,
                'serie' => $serie, 'correlativo' => $i + 1, 'numero' => $serie.'-'.($i + 1),
                'client_name' => $cliente, 'status' => 'draft',
                'subtotal' => 100, 'igv' => 18, 'total' => 118,
            ]);
        }
    }

    public static function secciones(): array
    {
        return [
            'Facturas' => ['factura', 'Cliente Factura', ['Cliente Boleta', 'Cliente Nota']],
            'Boletas'  => ['boleta',  'Cliente Boleta',  ['Cliente Factura', 'Cliente Nota']],
            /* Notas es la excepcion, y a proposito: una nota corrige a una
               factura o boleta concreta, asi que la seccion tiene que ofrecer
               esos comprobantes para poder elegir cual. Lo que NO puede salir
               es el formulario de venta en blanco (lo cubre NotasPantallaTest). */
            'Notas'    => ['nota',    'Cliente Nota',    []],
        ];
    }

    /**
     * El SERVIDOR filtra por tipo: a la pantalla solo llegan los comprobantes
     * de esa sección (se comprueba sobre los datos que se inyectan, ya que
     * la pantalla de emisión no pinta lista).
     *
     * @dataProvider secciones
     */
    public function test_cada_seccion_lista_solo_su_tipo(string $tipo, string $suyo, array $ajenos): void
    {
        $html = $this->get('/bixosales/facturas?tipo='.$tipo)->assertOk()->getContent();

        $this->assertStringContainsString($suyo, $html);
        foreach ($ajenos as $ajeno) {
            $this->assertStringNotContainsString($ajeno, $html,
                "La sección {$tipo} muestra comprobantes de otro tipo");
        }
    }

    /**
     * Y al entrar, el formulario de ESE documento ya está abierto.
     *
     * Notas queda fuera: no se emite desde este formulario, se elige antes el
     * comprobante a corregir.
     */
    public function test_cada_seccion_abre_su_formulario_directo(): void
    {
        foreach (['factura', 'boleta'] as $tipo) {
            $html = $this->get('/bixosales/facturas?tipo='.$tipo)->assertOk()->getContent();

            $this->assertStringContainsString("const seccion = '{$tipo}'", $html,
                "La sección {$tipo} no le pasa su tipo al formulario");
            $this->assertStringContainsString('this.openNew(', $html);
        }
    }

    /**
     * EMITIR y CONSULTAR son pantallas distintas: la de emisión no lleva
     * buscador ni la descarga del registro (eran tres trabajos peleándose
     * el mismo espacio).
     */
    public function test_la_pantalla_de_emision_no_lleva_buscador_ni_registro(): void
    {
        $html = $this->get('/bixosales/facturas?tipo=factura')->assertOk()->getContent();

        $this->assertStringNotContainsString('x-model="search"', $html,
            'El buscador volvió a la pantalla de emisión');
        $this->assertStringNotContainsString('mesRegistro', $html,
            'La descarga del registro volvió a la pantalla de emisión');
        // Pero sí ofrece la puerta a la consulta.
        $this->assertStringContainsString(route('bixosales.facturas.consulta'), $html);
    }

    /** Y la consulta busca de verdad: por número, cliente o documento. */
    public function test_la_consulta_busca_y_filtra_por_tipo(): void
    {
        $porNumero = $this->get('/bixosales/comprobantes-emitidos?q=B001')->assertOk()->getContent();
        $this->assertStringContainsString('Cliente Boleta', $porNumero);
        $this->assertStringNotContainsString('Cliente Factura', $porNumero);

        $porCliente = $this->get('/bixosales/comprobantes-emitidos?q=Cliente Nota')->assertOk()->getContent();
        $this->assertStringContainsString('Cliente Nota', $porCliente);

        $porTipo = $this->get('/bixosales/comprobantes-emitidos?tipo=factura')->assertOk()->getContent();
        $this->assertStringContainsString('Cliente Factura', $porTipo);
        $this->assertStringNotContainsString('Cliente Boleta', $porTipo);
    }

    /** Al emitir, el formulario ocupa la pantalla: sin panel de lista al lado. */
    public function test_la_seccion_de_emision_no_muestra_el_panel_de_lista(): void
    {
        $html = $this->get('/bixosales/facturas?tipo=factura')->assertOk()->getContent();

        $this->assertStringNotContainsString('id="inv-lista"', $html,
            'El panel de lista volvió a la pantalla de emisión');
        $this->assertStringNotContainsString('ÚLTIMOS EMITIDOS', mb_strtoupper($html));
    }

    /** Buscar un comprobante sirve para algo: se puede ver y descargar. */
    public function test_la_consulta_ofrece_vista_previa_y_pdf(): void
    {
        $factura = Invoice::where('project_id', $this->project->id)->where('type', 'factura')->first();

        $html = $this->get('/bixosales/comprobantes-emitidos')->assertOk()->getContent();

        $this->assertStringContainsString(route('bixosales.facturas.pdf', $factura->id), $html);
        // Vista previa: se mira sin salir de la búsqueda.
        $this->assertStringContainsString('ce-visor', $html);
        $this->assertStringContainsString('>Ver<', $html);
    }

    /** Sin sección, se siguen viendo todos: la lista general no se pierde. */
    public function test_sin_seccion_se_ven_todos_los_comprobantes(): void
    {
        $html = $this->get('/bixosales/facturas')->assertOk()->getContent();

        foreach (['Cliente Factura', 'Cliente Boleta', 'Cliente Nota'] as $cliente) {
            $this->assertStringContainsString($cliente, $html);
        }
    }
}

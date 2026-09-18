<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El menú del portal comercial.
 *
 * Eran once enlaces escritos a mano con nombres que no decian a donde
 * llevaban, y FALTABAN modulos que existen y estan en produccion:
 * Cotizaciones, Clientes, Cobranza, Caja y Facturas no tenian un solo enlace
 * en todo el portal — se llegaba a ellos escribiendo la URL a mano.
 *
 * Ahora el menu se genera de una estructura de datos con los MISMOS permisos
 * que exige cada ruta, de modo que no puede ofrecer nada que acabe en 403.
 */
class MenuLateralComercialTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'orders.ver', 'quotes.ver', 'clients.ver', 'reports.ver', 'invoices.ver',
            'caja.ver', 'pos.usar', 'agenda.ver', 'rifas.ver', 'tickets.ver',
            'view-orders', 'view-quotes', 'view-clients', 'view-logistics',
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Menú QA', 'slug' => 'menu-qa', 'category' => 'retail', 'is_active' => true,
        ]);
        // El menú ahora exige, además del uso/ajuste, el módulo CONTRATADO
        // (ModulosPortal unificado con entitlements): se contratan aquí para
        // que los tests sigan midiendo solo la regla de uso/ajuste.
        foreach (['invoices', 'agenda', 'logistics'] as $key) {
            $m = \App\Models\Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function entrar(string $rol, array $permisos): User
    {
        Role::findOrCreate($rol, 'web')->syncPermissions($permisos);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Empleado', 'spatie_role' => $rol, 'is_active' => 1]);
        $u->syncRoles([$rol]);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        return $u;
    }

    /**
     * Los grupos se pliegan, y el de la pantalla actual sale abierto.
     *
     * Con ocho grupos desplegados el menu no cabia: para llegar a Reportes
     * habia que desplazarse. Cada titulo es ahora el mando de su bloque.
     */
    public function test_los_grupos_del_menu_se_pliegan(): void
    {
        $this->entrar('menu_gerente_plegable', [
            'orders.ver', 'quotes.ver', 'clients.ver', 'reports.ver',
            'invoices.ver', 'caja.ver', 'pos.usar',
        ]);

        $html = $this->get('/bixosales')->assertOk()->getContent();

        // El titulo dejo de ser un rotulo muerto: ahora es un boton con estado.
        $this->assertStringContainsString('nav-grupo-btn', $html);
        $this->assertStringContainsString('alternarGrupo(', $html);
        $this->assertStringContainsString('aria-controls="grp-ventas"', $html);
        $this->assertStringContainsString('id="grp-ventas"', $html);
    }

    /**
     * El grupo de la pantalla actual viene desplegado; los demas, recogidos.
     *
     * El valor por defecto lo calcula el SERVIDOR (segundo argumento de
     * `grupoAbierto`), que es quien ya sabe cual es la entrada activa. Se
     * comprueba desde la portada, donde ningun grupo esta activo, y por eso
     * todos salen recogidos: es el estado mas corto posible del menu.
     */
    public function test_el_grupo_de_la_pantalla_actual_se_abre_solo(): void
    {
        $this->entrar('menu_actual_plegable', [
            'orders.ver', 'quotes.ver', 'clients.ver', 'reports.ver',
        ]);

        $html = $this->get('/bixosales')->assertOk()->getContent();

        // Inicio no pertenece a ningun grupo: nada se despliega de origen.
        $this->assertMatchesRegularExpression(
            "/grupoAbierto\('ventas',\s*false\)/", $html);
        $this->assertMatchesRegularExpression(
            "/grupoAbierto\('reportes',\s*false\)/", $html);
        // Y el mando existe para cada grupo, que es lo que permite abrirlos.
        $this->assertStringContainsString('aria-controls="grp-reportes"', $html);
    }

    /** Vuelca el HTML real a disco para revisarlo en un navegador. */
    public function test_zz_volcar(): void
    {
        if (!env('VOLCAR_MENU')) { $this->markTestSkipped('solo bajo demanda'); }
        $this->entrar('menu_volcado', [
            'orders.ver', 'quotes.ver', 'clients.ver', 'reports.ver',
            'invoices.ver', 'caja.ver', 'pos.usar',
        ]);
        file_put_contents(env('VOLCAR_MENU'), $this->get('/bixosales')->assertOk()->getContent());
        $this->assertTrue(true);
    }

    public function test_el_menu_ofrece_los_modulos_que_existen_y_no_solo_iconos(): void
    {
        $this->entrar('menu_gerente', [
            'orders.ver', 'quotes.ver', 'clients.ver', 'reports.ver',
            'invoices.ver', 'caja.ver', 'pos.usar',
        ]);

        // Facturas y Caja solo se ofrecen a quien ya las usa; se les da uso.
        \App\Modules\Finanzas\Models\Invoice::create(['project_id' => $this->project->id, 'type' => 'boleta',
            'serie' => 'B001', 'correlativo' => 1, 'numero' => 'B001-1', 'client_name' => 'X',
            'status' => 'draft', 'subtotal' => 10, 'igv' => 0, 'total' => 10]);
        \App\Modules\Finanzas\Models\Caja::create(['project_id' => $this->project->id, 'user_id' => auth()->id(),
            'user_name' => 'QA', 'monto_inicial' => 0, 'estado' => 'abierta', 'opened_at' => now()]);

        $res = $this->get('/bixosales')->assertOk();

        // Los nombres, visibles en el HTML: sin ellos el menu es adivinanza.
        foreach (['Inicio', 'Cotizaciones', 'Pedidos', 'Clientes', 'Cobranza', 'Facturas', 'Caja', 'Reportes', 'Inventario'] as $modulo) {
            $res->assertSee($modulo);
        }

        // Y los destinos existen de verdad.
        foreach (['bixosales.cotizaciones', 'bixosales.clientes', 'bixosales.cuentas', 'bixosales.facturas', 'bixosales.caja'] as $ruta) {
            $res->assertSee(route($ruta), false);
        }
    }

    public function test_el_menu_no_ofrece_lo_que_el_usuario_no_puede_abrir(): void
    {
        // Solo pedidos: ni cotizaciones, ni clientes, ni caja, ni facturas.
        $this->entrar('menu_pedidos', ['orders.ver']);

        $res = $this->get('/bixosales')->assertOk();

        $res->assertDontSee(route('bixosales.cotizaciones'), false)
            ->assertDontSee(route('bixosales.clientes'), false)
            ->assertDontSee(route('bixosales.facturas'), false)
            ->assertDontSee(route('bixosales.caja'), false);

        // Un enlace que garantiza 403 no es navegacion, es una trampa.
        $this->get('/bixosales/clientes')->assertForbidden();
    }

    public function test_cada_entrada_del_menu_es_accesible_por_teclado_y_lector(): void
    {
        $this->entrar('menu_lector', ['orders.ver', 'quotes.ver', 'reports.ver']);

        $html = $this->get('/bixosales')->assertOk()->getContent();

        // Contraido solo hay iconos: sin aria-label no se sabe que son.
        $this->assertStringContainsString('aria-label="Cotizaciones"', $html);
        $this->assertStringContainsString('data-tip="Cotizaciones"', $html);
        $this->assertStringContainsString('aria-label="Navegación principal"', $html);
        // La pagina en la que estoy se anuncia, no solo se colorea.
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_solo_se_ofrece_lo_que_el_negocio_usa_de_verdad(): void
    {
        // Un negocio recien creado no tiene caja, ni facturas, ni reservas,
        // ni reparto, ni bot: ofrecerlos es dar a elegir entre pantallas
        // vacias. "Pedidos del bot" ademas revienta con 500 fuera de un
        // proyecto de rifas, asi que enseñarlo era mandar a un error.
        $this->entrar('menu_nuevo', [
            'orders.ver', 'quotes.ver', 'reports.ver', 'invoices.ver',
            'caja.ver', 'agenda.ver', 'rifas.ver', 'pos.usar', 'view-logistics',
        ]);

        $res = $this->get('/bixosales')->assertOk();

        // El nucleo sí: vender, cobrar y ver.
        $res->assertSee('Pedidos')->assertSee('Cotizaciones')->assertSee('Cobranza')->assertSee('Reportes');

        // Lo que no se usa, no asoma.
        $res->assertDontSee(route('bixosales.facturas'), false)
            ->assertDontSee(route('bixosales.caja'), false)
            ->assertDontSee(route('bixosales.reservas'), false)
            ->assertDontSee(route('bixosales.delivery'), false)
            ->assertDontSee(route('bixosales.rifas'), false);
    }

    public function test_un_modulo_se_libera_con_su_ajuste_sin_tocar_codigo(): void
    {
        $this->project->settings()->create(['key' => 'modulo_facturas', 'value' => '1']);

        $this->entrar('menu_libre', ['orders.ver', 'invoices.ver']);

        $this->get('/bixosales')->assertOk()->assertSee(route('bixosales.facturas'), false);
    }

    /**
     * Cada comprobante es un trámite distinto ante SUNAT (serie y numeración
     * propias): el menú los ofrece por SEPARADO, no bajo un único enlace.
     */
    public function test_facturas_boletas_y_notas_son_entradas_independientes(): void
    {
        $this->project->settings()->create(['key' => 'modulo_facturas', 'value' => '1']);
        $this->entrar('menu_fiscal', ['orders.ver', 'invoices.ver']);

        $html = $this->get('/bixosales')->assertOk()->getContent();

        foreach (['Facturas', 'Boletas', 'Notas de crédito y débito', 'Guías de remisión'] as $entrada) {
            $this->assertStringContainsString($entrada, $html, "Falta la sección {$entrada}");
        }
        // Y cada una lleva a su propia lista filtrada.
        foreach (['tipo=factura', 'tipo=boleta', 'tipo=nota'] as $filtro) {
            $this->assertStringContainsString($filtro, $html);
        }
    }

    public function test_el_panel_derecho_no_arranca_abierto(): void
    {
        $this->entrar('menu_lector2', ['orders.ver']);

        $html = $this->get('/bixosales')->assertOk()->getContent();

        // El cajon nace cerrado e inerte; abrirlo es decision del usuario.
        $this->assertStringContainsString('panelOpen:  false', $html);
        $this->assertStringContainsString('id="panel-right"', $html);
        $this->assertStringContainsString('inert', $html);
    }
}

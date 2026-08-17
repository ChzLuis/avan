<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Access;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FASES 5 y 6 — Equivalencia legacy → canónico y migración de los perfiles.
 *
 * La regla que se protege es la del plan: **mantener capacidades equivalentes
 * durante la migración**. Ni un permiso de más ni uno de menos.
 */
class LegacyMappingTest extends TestCase
{
    use RefreshDatabase;

    /** Los 90 permisos que había antes de esta reforma. */
    private const LEGACY = [
        'agenda.crear', 'agenda.editar', 'agenda.eliminar', 'agenda.ver',
        'attendance.editar', 'attendance.fichar', 'attendance.ver',
        'caja.abrir', 'caja.cerrar', 'caja.movimiento', 'caja.ver',
        'catalog-integrations.manage', 'catalog-integrations.sync',
        'catalog-integrations.view', 'catalog-integrations.view-history',
        'catalog.crear', 'catalog.editar', 'catalog.eliminar', 'catalog.importar',
        'catalog.resenas', 'catalog.ver',
        'clients.crear', 'clients.editar', 'clients.eliminar', 'clients.ver',
        'create-products', 'delete-products', 'edit-products',
        'hr.crear', 'hr.editar', 'hr.eliminar', 'hr.ver',
        'inventory.editar', 'inventory.ver',
        'invoices.anular', 'invoices.crear', 'invoices.editar', 'invoices.ver',
        'manage-agenda', 'manage-clients', 'manage-hr', 'manage-logistics',
        'manage-members', 'manage-modules', 'manage-orders', 'manage-quotes',
        'manage-requests', 'manage-settings',
        'mapa.editar', 'mapa.ver',
        'orders.cancelar', 'orders.crear', 'orders.descuento', 'orders.editar',
        'orders.eliminar', 'orders.ver',
        'payments.aprobar', 'payments.rechazar', 'payments.ver',
        'pos.usar', 'proveedores.editar', 'proveedores.ver',
        'quotes.crear', 'quotes.editar', 'quotes.eliminar', 'quotes.ver',
        'reports.exportar', 'reports.ver',
        'rifas.cancelar', 'rifas.validar', 'rifas.ver',
        'roles.gestionar', 'roles.ver',
        'settings.catalogos', 'settings.diseno', 'settings.editar',
        'settings.negocio', 'settings.pagos', 'settings.qr', 'settings.ver',
        'tickets.eliminar', 'tickets.ver',
        'view-agenda', 'view-catalog', 'view-clients', 'view-hr',
        'view-logistics', 'view-orders', 'view-quotes', 'view-requests',
    ];

    /** Sin equivalente a propósito: módulos sin uso y Reportes, que es transversal. */
    private const SIN_EQUIVALENTE = [
        'reports.ver', 'reports.exportar',
        'rifas.ver', 'rifas.validar', 'rifas.cancelar',
        'tickets.ver', 'tickets.eliminar',
        'view-requests', 'manage-requests',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (array_merge(self::LEGACY, Access::permisos()) as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    public function test_los_noventa_permisos_tienen_decision(): void
    {
        $this->assertCount(90, self::LEGACY, 'La lista de referencia ya no son 90 permisos.');

        $sinDecision = array_filter(
            self::LEGACY,
            fn ($p) => !isset(Access::MAPEO_LEGACY[$p]) && !in_array($p, self::SIN_EQUIVALENTE, true)
        );

        $this->assertSame([], array_values($sinDecision),
            'Hay permisos antiguos sin equivalente ni justificación de exclusión.');
    }

    public function test_todos_los_destinos_del_mapeo_son_canonicos(): void
    {
        foreach (Access::MAPEO_LEGACY as $antiguo => $nuevo) {
            $this->assertNotNull(Access::partes($nuevo), "`{$antiguo}` apunta a `{$nuevo}`, que no es canónico.");
        }
    }

    /** La traducción se queda con el nivel más alto de cada área, no con el último. */
    public function test_la_traduccion_toma_el_nivel_mas_alto(): void
    {
        $this->assertSame(
            ['catalogo.administrar'],
            Access::traducir(['catalog.ver', 'catalog.editar', 'catalog.eliminar'])
        );

        $this->assertSame(
            ['catalogo.trabajar'],
            Access::traducir(['catalog.ver', 'catalog.editar'])
        );
    }

    /** El descuento del mostrador va a Punto de venta, no a Pedidos. */
    public function test_el_descuento_pertenece_al_punto_de_venta(): void
    {
        $this->assertSame(['pos.administrar'], Access::traducir(['orders.descuento']));
    }

    /** Los permisos excluidos no generan ningún canónico. */
    public function test_los_excluidos_no_traducen_a_nada(): void
    {
        $this->assertSame([], Access::traducir(self::SIN_EQUIVALENTE));
    }

    /**
     * El corazón de la Fase 6: cada rol conserva lo que podía hacer.
     *
     * Se recrea el reparto real de producción y se comprueba que, tras traducir,
     * el usuario mantiene sus capacidades vía los permisos canónicos.
     */
    public function test_los_perfiles_reales_conservan_sus_capacidades(): void
    {
        $reales = [
            'vendedor' => ['agenda.crear', 'agenda.ver', 'attendance.fichar', 'caja.ver',
                           'catalog.ver', 'clients.crear', 'clients.editar', 'clients.ver',
                           'invoices.crear', 'invoices.ver', 'orders.cancelar', 'orders.crear',
                           'orders.descuento', 'orders.editar', 'orders.ver', 'payments.ver',
                           'pos.usar', 'quotes.crear', 'quotes.editar', 'quotes.ver', 'reports.ver'],
            'almacen'  => ['catalog.crear', 'catalog.editar', 'catalog.eliminar', 'catalog.importar',
                           'catalog.ver', 'inventory.editar', 'inventory.ver', 'manage-logistics',
                           'orders.ver', 'proveedores.editar', 'proveedores.ver', 'reports.ver',
                           'view-logistics'],
            'contador' => ['caja.ver', 'clients.ver', 'invoices.anular', 'invoices.crear',
                           'invoices.editar', 'invoices.ver', 'orders.ver', 'payments.aprobar',
                           'payments.rechazar', 'payments.ver', 'quotes.ver', 'reports.exportar',
                           'reports.ver'],
        ];

        $esperado = [
            // Vende y cobra en mostrador, con potestad de descuento; anula pedidos.
            'vendedor' => ['agenda.trabajar', 'caja.ver', 'catalogo.ver', 'clientes.trabajar',
                           'cobros.ver', 'cotizaciones.trabajar', 'facturacion.trabajar',
                           'pedidos.administrar', 'personal.trabajar', 'pos.administrar'],
            // Manda en catálogo e inventario; de pedidos solo mira y mueve el reparto.
            'almacen'  => ['catalogo.administrar', 'inventario.trabajar', 'pedidos.trabajar'],
            // Manda en el dinero; del resto solo mira.
            'contador' => ['caja.ver', 'clientes.ver', 'cobros.administrar',
                           'cotizaciones.ver', 'facturacion.administrar', 'pedidos.ver'],
        ];

        foreach ($reales as $rol => $permisos) {
            $traducido = Access::traducir($permisos);
            sort($traducido);
            $sinDuplicar = $esperado[$rol];
            sort($sinDuplicar);

            $this->assertSame($sinDuplicar, $traducido, "El perfil `{$rol}` no conserva sus capacidades.");
        }
    }

    /** Y en la práctica: un almacenero traducido puede lo mismo que antes. */
    public function test_un_almacenero_traducido_puede_lo_mismo(): void
    {
        $legacy = ['catalog.ver', 'catalog.crear', 'catalog.editar', 'catalog.eliminar',
                   'catalog.importar', 'inventory.ver', 'inventory.editar'];

        Role::findOrCreate('almacen_nuevo', 'web')->syncPermissions(Access::traducir($legacy));

        $u = User::factory()->create(['is_superadmin' => 0]);
        $u->syncRoles(['almacen_nuevo']);
        $u = $u->fresh();

        // Lo que podía: ver, crear, editar, eliminar e importar catálogo; mover stock.
        $this->assertTrue($u->can('catalogo.ver'));
        $this->assertTrue($u->can('catalogo.trabajar'));
        $this->assertTrue($u->can('catalogo.administrar'));
        $this->assertTrue($u->can('inventario.ver'));
        $this->assertTrue($u->can('inventario.trabajar'));

        // Lo que NO podía y sigue sin poder.
        $this->assertFalse($u->can('inventario.administrar'));
        $this->assertFalse($u->can('caja.ver'));
        $this->assertFalse($u->can('cobros.ver'));
        $this->assertFalse($u->can('configuracion.ver'));
    }

    /** Un perfil guarda UNA fila por área, no las tres. */
    public function test_el_perfil_guarda_una_fila_por_area(): void
    {
        $traducido = Access::traducir(['catalog.ver', 'catalog.editar', 'catalog.eliminar',
                                       'orders.ver', 'orders.crear']);

        Role::findOrCreate('compacto', 'web')->syncPermissions($traducido);

        $this->assertSame(2, Role::findByName('compacto', 'web')->permissions()->count());
    }
}

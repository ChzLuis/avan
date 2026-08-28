<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Autorizacion del portal BixoSales (Paso 0-Security, fase 2).
 *
 * Antes, las 97 rutas del portal colgaban solo de comercial.auth: sesion mas
 * pertenencia al proyecto, sin comprobar un solo permiso. Cualquier usuario del
 * proyecto podia crear, editar y borrar pedidos, operar caja, modificar el mapa
 * y aprobar pagos.
 *
 * Matriz: docs/auditoria/bixosales-matriz-autorizacion.md §3
 */
class BixoSalesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Order $order;
    private Quote $quote;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'orders.ver', 'orders.crear', 'orders.editar', 'orders.eliminar',
            'quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
            'clients.ver', 'clients.crear', 'clients.editar', 'clients.eliminar',
            'invoices.ver', 'invoices.crear', 'invoices.editar', 'invoices.anular',
            'agenda.ver', 'agenda.crear', 'agenda.editar', 'agenda.eliminar',
            'rifas.ver', 'rifas.validar', 'rifas.cancelar',
            'caja.ver', 'caja.abrir', 'caja.cerrar', 'caja.movimiento',
            'mapa.ver', 'mapa.editar', 'tickets.ver', 'tickets.eliminar',
            'payments.ver', 'payments.aprobar', 'payments.rechazar',
            'pos.usar', 'catalog.ver', 'catalog.importar', 'reports.ver',
            'view-logistics', 'manage-logistics',
            'view-orders', 'manage-orders', 'view-quotes', 'manage-quotes',
            'view-clients', 'manage-clients',
        ] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Portal QA',
            'slug'      => 'portal-qa',
            'is_active' => true,
        ]);
        // Entitlement de tenant real: el negocio contrató los módulos que el
        // App Shell gatea (comercial.module). Sin esto el gate responde 403.
        foreach (['orders', 'clients', 'invoices', 'quotes', 'catalog'] as $key) {
            $m = \App\Models\Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }

        $this->order = Order::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente',
            'status' => 'pending', 'total' => 100,
        ]);
        $this->quote = Quote::create([
            'project_id' => $this->project->id, 'client_name' => 'Cliente',
            'status' => 'draft', 'total' => 100,
        ]);
    }

    /**
     * Usuario real del portal: User + ProjectMember + Employee.spatie_role.
     * Los tres hacen falta. ProjectMember porque la pertenencia se valida contra
     * project_members, y Employee.spatie_role porque SetActiveProject hace
     * syncRoles() en cada peticion y borraria un rol puesto solo en Spatie.
     */
    private function usuario(string $rol, array $permisos): User
    {
        Role::findOrCreate($rol, 'web')->syncPermissions($permisos);

        $user = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $user->id, 'role' => 'viewer']);
        Employee::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'name' => 'Empleado '.$rol, 'spatie_role' => $rol, 'is_active' => 1,
        ]);
        $user->syncRoles([$rol]);

        return $user;
    }

    /** comercial.auth exige sesion iniciada Y comercial_project_id en sesion. */
    private function entrarAlPortal(User $user): self
    {
        $this->actingAs($user)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        return $this;
    }

    private function qa(): User
    {
        return $this->usuario('qa_lectura_bx', ['orders.ver', 'quotes.ver']);
    }

    private function gerente(): User
    {
        return $this->usuario('gerente_bx', [
            'orders.ver', 'orders.crear', 'orders.editar', 'orders.eliminar',
            'quotes.ver', 'quotes.crear', 'quotes.editar', 'quotes.eliminar',
            'clients.ver', 'clients.crear', 'clients.editar', 'clients.eliminar',
            'invoices.ver', 'invoices.crear', 'invoices.editar', 'invoices.anular',
            'agenda.ver', 'agenda.crear', 'agenda.editar', 'agenda.eliminar',
            'rifas.ver', 'rifas.validar', 'rifas.cancelar',
            'caja.ver', 'caja.abrir', 'caja.cerrar', 'caja.movimiento',
            'mapa.ver', 'mapa.editar', 'tickets.ver', 'tickets.eliminar',
            'payments.ver', 'payments.aprobar', 'payments.rechazar',
            'pos.usar', 'catalog.ver', 'catalog.importar', 'reports.ver',
            'view-logistics', 'manage-logistics',
        ]);
    }

    // ─── 1 y 2: QA consulta Pedidos y Cotizaciones ────────────────────────

    public function test_qa_puede_consultar_pedidos(): void
    {
        $this->entrarAlPortal($this->qa());
        $this->get('/bixosales/pedidos')->assertSuccessful();
        $this->get('/bixosales/pedidos/'.$this->order->id)->assertSuccessful();
    }

    public function test_qa_puede_consultar_cotizaciones(): void
    {
        $this->entrarAlPortal($this->qa());
        $this->get('/bixosales/cotizaciones')->assertSuccessful();
        $this->get('/bixosales/cotizaciones/'.$this->quote->id)->assertSuccessful();
    }

    // ─── 3 a 8: QA no puede mutar NADA del portal ─────────────────────────

    /**
     * @dataProvider mutacionesDelPortal
     *
     * Se acepta 403 o 404. Motivo: SubstituteBindings corre antes que el
     * middleware de permiso, asi que en las rutas con {caja}, {object},
     * {client}, {invoice}, {appointment} o {venta} —cuyos registros no existen
     * en la base de pruebas— el binding devuelve 404 antes de llegar a evaluar
     * el permiso. En ambos casos la mutacion NO ocurre, que es la propiedad de
     * seguridad. La prueba de que TODAS llevan permiso es estatica:
     * test_toda_ruta_mutadora_del_portal_exige_un_permiso().
     */
    public function test_qa_no_puede_mutar(string $verbo, string $ruta): void
    {
        $this->entrarAlPortal($this->qa());
        $respuesta = $this->json($verbo, $this->resolver($ruta), ['nombre' => 'x']);

        $this->assertContains(
            $respuesta->getStatusCode(),
            [403, 404],
            "$verbo $ruta debia quedar bloqueada, llego {$respuesta->getStatusCode()}"
        );
    }

    /**
     * Barrido estatico de la tabla de rutas: ninguna ruta con verbo mutador
     * dentro de comercial.auth puede quedar sin permiso. Es la comprobacion
     * 55/55 y la que garantiza que no se olvido ninguna al añadir middleware.
     */
    public function test_toda_ruta_mutadora_del_portal_exige_un_permiso(): void
    {
        $mutadoras = 0;
        $abiertas  = [];

        foreach (app('router')->getRoutes() as $ruta) {
            $mw = implode(' ', $ruta->gatherMiddleware());
            if (!str_contains($mw, 'comercial.auth')) {
                continue;
            }
            if (!array_intersect($ruta->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }
            $mutadoras++;
            if (!str_contains($mw, 'can:')) {
                $abiertas[] = implode('|', $ruta->methods()) . ' ' . $ruta->uri();
            }
        }

        // 56 originales + 3 añadidas en el Paso 2 (pay, wa-sent, laundry-status)
        // para que la vista de Pedidos deje de llamar a las rutas del panel.
        // La cuarta (events) es GET y no cuenta aqui.
        // +1 en F1c: cotizaciones/{quote}/convertir-pedido, la gemela de
        // BixoSales para convertir en pedido (permiso dual A|B). Este conteo es
        // deliberadamente rigido: cada ruta mutadora nueva obliga a revisarlo.
        // +1 en F2b: cuentas/condiciones, que configura el plazo de cobro del
        // negocio. No muta deuda —ni importes ni estados— pero escribe ajustes,
        // asi que va con `settings.pagos|manage-settings` y no basta con
        // `reports.ver`, que es lo que deja ver la pantalla.
        // +2 en F4: facturas/{invoice}/nota y /baja, las dos vias legales para
        // corregir un comprobante que SUNAT ya acepto. Ambas van con
        // `invoices.anular`: quien no puede anular tampoco puede emitir una
        // nota de credito, que es una anulacion con otro nombre.
        // +1 Centro de Avisos: arranque/ocultar apaga la guia de dia uno para
        // todo el proyecto. Escribe un ajuste del negocio, asi que va con
        // `settings.negocio|manage-settings`, igual que cuentas/condiciones.
        // +1 en F11: clientes/{client}/portal genera (o regenera, invalidando
        // el anterior) el enlace personal del Portal del Cliente. Regenerar
        // corta el acceso del cliente al enlace viejo: es una escritura sobre
        // el cliente y va con `clients.editar|manage-clients`.
        $this->assertSame(65, $mutadoras, 'El portal deberia tener 65 rutas con verbo mutador');
        $this->assertSame([], $abiertas, 'Rutas mutadoras sin permiso: ' . implode(', ', $abiertas));
    }

    /** La unica excepcion verbo/semantica debe estar donde se documento. */
    public function test_la_unica_lectura_por_post_es_pagos_pendientes(): void
    {
        $ruta = collect(app('router')->getRoutes())->first(
            fn ($r) => $r->uri() === 'bixosales/pagos/pendientes'
        );

        $this->assertNotNull($ruta);
        $this->assertStringContainsString('can:payments.ver', implode(' ', $ruta->gatherMiddleware()));
    }

    public static function mutacionesDelPortal(): array
    {
        return [
            // 3. Pedidos y cotizaciones
            'crear pedido'        => ['POST',   '/bixosales/pedidos'],
            'editar pedido'       => ['PUT',    '/bixosales/pedidos/{order}'],
            'BORRAR pedido'       => ['DELETE', '/bixosales/pedidos/{order}'],
            'cocina'              => ['PATCH',  '/bixosales/pedidos/{order}/kitchen'],
            'wa-action'           => ['POST',   '/bixosales/pedidos/{order}/wa-action'],
            'wa-delivery'         => ['POST',   '/bixosales/pedidos/{order}/wa-delivery'],
            'crear cotizacion'    => ['POST',   '/bixosales/cotizaciones'],
            'editar cotizacion'   => ['PUT',    '/bixosales/cotizaciones/{quote}'],
            'BORRAR cotizacion'   => ['DELETE', '/bixosales/cotizaciones/{quote}'],
            'duplicar cotizacion' => ['POST',   '/bixosales/cotizaciones/{quote}/duplicate'],
            'enviar cotizacion'   => ['POST',   '/bixosales/cotizaciones/{quote}/send'],
            // 4. Caja
            'abrir caja'          => ['POST',   '/bixosales/caja/abrir'],
            'cerrar caja'         => ['POST',   '/bixosales/caja/1/cerrar'],
            'movimiento caja'     => ['POST',   '/bixosales/caja/1/movimiento'],
            // 5. Mapa
            'crear mapa'          => ['POST',   '/bixosales/mapa/maps'],
            'mover objeto'        => ['PATCH',  '/bixosales/mapa/objects/1/move'],
            'borrar objeto'       => ['DELETE', '/bixosales/mapa/objects/1'],
            'importe objeto'      => ['PATCH',  '/bixosales/mapa/objects/1/amount'],
            // 6. Tickets
            'eliminar ticket'     => ['POST',   '/bixosales/tickets-manuales/eliminar'],
            // 7. Pagos
            'aprobar pago'        => ['POST',   '/bixosales/pagos/aprobar'],
            'rechazar pago'       => ['POST',   '/bixosales/pagos/rechazar'],
            // 8. Resto del portal
            'crear cliente'       => ['POST',   '/bixosales/clientes'],
            'borrar cliente'      => ['DELETE', '/bixosales/clientes/1'],
            'crear factura'       => ['POST',   '/bixosales/facturas'],
            'anular factura'      => ['DELETE', '/bixosales/facturas/1'],
            'crear reserva'       => ['POST',   '/bixosales/reservas'],
            'borrar reserva'      => ['DELETE', '/bixosales/reservas/1'],
            'delivery alta'       => ['POST',   '/bixosales/delivery'],
            'delivery estado'     => ['PUT',    '/bixosales/delivery/1/status'],
            'validar rifa'        => ['POST',   '/bixosales/pedidos-bot/1/validar'],
            'cancelar rifa'       => ['POST',   '/bixosales/pedidos-bot/1/cancelar'],
            'venta POS'           => ['POST',   '/bixosales/pos'],
            'sincronizar woo'     => ['POST',   '/bixosales/woo/sync'],
            'precio revendedor'   => ['POST',   '/bixosales/revendedor/precio'],
        ];
    }

    /** El acuse de lectura es la unica escritura permitida a un lector. */
    public function test_qa_puede_marcar_cotizacion_vista(): void
    {
        $this->entrarAlPortal($this->qa());
        $this->postJson('/bixosales/cotizaciones/'.$this->quote->id.'/seen')->assertSuccessful();
    }

    /** POST cuya semantica es lectura: QA no lo tiene, pero no por el verbo. */
    public function test_pagos_pendientes_exige_payments_ver_y_no_un_permiso_de_escritura(): void
    {
        $this->entrarAlPortal($this->qa());
        $this->postJson('/bixosales/pagos/pendientes')->assertForbidden();

        $lector = $this->usuario('lector_pagos_bx', ['payments.ver']);
        $this->entrarAlPortal($lector);
        $this->postJson('/bixosales/pagos/pendientes')->assertStatus(200);
    }

    // ─── El rol autorizado conserva su operacion ──────────────────────────

    public function test_gerente_conserva_la_operacion(): void
    {
        $this->entrarAlPortal($this->gerente());

        $this->get('/bixosales/pedidos')->assertSuccessful();
        $this->get('/bixosales/caja')->assertSuccessful();
        $this->get('/bixosales/mapa')->assertSuccessful();
        $this->get('/bixosales/delivery')->assertSuccessful();
        $this->get('/bixosales/clientes')->assertSuccessful();

        $this->postJson('/bixosales/pedidos', [
            'client_name' => 'Nuevo',
            'items' => [['name' => 'Item', 'price' => 10, 'quantity' => 1]],
        ])->assertSuccessful();

        $this->putJson('/bixosales/pedidos/'.$this->order->id, ['status' => 'process'])
            ->assertSuccessful();

        $this->deleteJson('/bixosales/cotizaciones/'.$this->quote->id)->assertSuccessful();
    }

    // ─── Universo B, universo A legacy, y ninguno ─────────────────────────

    public function test_usuario_solo_con_permiso_del_universo_B_funciona(): void
    {
        $this->entrarAlPortal($this->usuario('solo_b_bx', ['orders.ver', 'orders.crear']));

        $this->get('/bixosales/pedidos')->assertSuccessful();
        $this->postJson('/bixosales/pedidos', [
            'client_name' => 'B',
            'items' => [['name' => 'Item', 'price' => 10, 'quantity' => 1]],
        ])->assertSuccessful();
        $this->deleteJson('/bixosales/pedidos/'.$this->order->id)->assertForbidden();
    }

    public function test_usuario_solo_con_equivalente_legacy_del_universo_A_funciona(): void
    {
        $this->entrarAlPortal($this->usuario('solo_a_bx', ['view-orders', 'manage-orders']));

        $this->get('/bixosales/pedidos')->assertSuccessful();
        $this->postJson('/bixosales/pedidos', [
            'client_name' => 'A',
            'items' => [['name' => 'Item', 'price' => 10, 'quantity' => 1]],
        ])->assertSuccessful();
        $this->putJson('/bixosales/pedidos/'.$this->order->id, ['status' => 'process'])
            ->assertSuccessful();
    }

    public function test_usuario_sin_A_ni_B_recibe_403(): void
    {
        $this->entrarAlPortal($this->usuario('sin_nada_bx', ['reports.ver']));

        $this->get('/bixosales/pedidos')->assertForbidden();
        $this->get('/bixosales/cotizaciones')->assertForbidden();
        $this->postJson('/bixosales/pedidos', ['client_name' => 'X'])->assertForbidden();
    }

    private function resolver(string $ruta): string
    {
        return str_replace(
            ['{order}', '{quote}'],
            [(string) $this->order->id, (string) $this->quote->id],
            $ruta
        );
    }
    /**
     * El barrido de arriba solo cubre el portal `comercial.auth`. Estas dos
     * rutas viven en `project.member` y estuvieron **sin permiso alguno**:
     * `/invoices` dejaba a cualquier miembro emitir y borrar comprobantes
     * fiscales, y `/dashboard-comercial` exponia facturacion, cuentas por
     * cobrar y el ranking de ventas por vendedor.
     */
    public function test_las_rutas_de_project_member_tambien_exigen_permiso(): void
    {
        $sinPermiso = [];

        foreach (app('router')->getRoutes() as $ruta) {
            $mw  = implode(' ', $ruta->gatherMiddleware());
            $uri = $ruta->uri();

            $vigilada = str_starts_with($uri, 'invoices')
                || str_contains($uri, 'dashboard-comercial');

            if (! $vigilada || ! str_contains($mw, 'project.member')) {
                continue;
            }
            if (! str_contains($mw, 'can:')) {
                $sinPermiso[] = implode('|', $ruta->methods()) . ' ' . $uri;
            }
        }

        $this->assertSame([], $sinPermiso,
            'rutas de comprobantes o dashboard sin permiso: ' . implode(', ', $sinPermiso));
    }

    /**
     * Ninguna ruta que ESCRIBE puede autorizarse con un permiso `.ver`.
     *
     * `POST /hr/asistencia` lo hacia: `can:attendance.ver` para un
     * `updateOrCreate` de asistencias cuyas horas alimentan el calculo de
     * comisiones. Quien solo podia mirar podia fabricar la asistencia — y con
     * ella el dinero a pagar.
     *
     * Se admiten las lecturas-por-POST ya documentadas como excepcion
     * deliberada (consultas que usan POST por el tamaño del cuerpo).
     */
    public function test_ninguna_escritura_se_autoriza_con_un_permiso_de_lectura(): void
    {
        $excepciones = [
            'bixosales/pagos/pendientes',   // consulta por POST, documentada
            'copilot',                      // idem
        ];

        $sospechosas = [];
        foreach (app('router')->getRoutes() as $ruta) {
            if (! array_intersect($ruta->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }
            $uri = $ruta->uri();
            foreach ($excepciones as $e) {
                if (str_contains($uri, $e)) { continue 2; }
            }
            if (str_contains($uri, 'seen')) { continue; }   // marcar como visto

            $mw = implode(' ', $ruta->gatherMiddleware());
            if (preg_match('/can:[a-z_]+\.ver(?![a-z_])/', $mw)) {
                $sospechosas[] = implode('|', $ruta->methods()) . ' ' . $uri;
            }
        }

        $this->assertSame([], $sospechosas,
            'escrituras autorizadas con permiso de lectura: ' . implode(', ', $sospechosas));
    }

}

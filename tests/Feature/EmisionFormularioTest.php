<?php

namespace Tests\Feature;

use App\Modules\Crm\Models\Client;
use App\Modules\Finanzas\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formulario de emisión — rediseño 2026-09-04.
 *
 * Se protege lo que el rediseño hizo posible y antes no existía: buscar un
 * cliente por su documento, guardar un borrador que de verdad no se declara,
 * y aplicar un descuento por línea. Y lo que NO debe cambiar: el correlativo
 * lo asigna el sistema, no el formulario.
 */
class EmisionFormularioTest extends TestCase
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
            'name' => 'Emisión QA', 'slug' => 'emision-qa', 'is_active' => true,
        ]);
    }

    private function comoDueno()
    {
        return $this->actingAs($this->user)->withSession(['active_project_id' => $this->project->id]);
    }

    // ── Buscador de clientes ─────────────────────────────────────────────

    public function test_el_cliente_se_encuentra_por_su_ruc(): void
    {
        // Antes era imposible: la ficha del cliente no guardaba el documento.
        Client::create([
            'project_id' => $this->project->id, 'name' => 'FERRETERIA CENTRAL S.A.C.',
            'doc_type' => 'RUC', 'doc_number' => '20123456789',
        ]);

        $r = $this->comoDueno()->getJson(route('invoices.clientes', ['q' => '20123456789']));

        $r->assertOk();
        $this->assertSame('FERRETERIA CENTRAL S.A.C.', $r->json('clientes.0.nombre'));
        $this->assertSame('20123456789', $r->json('clientes.0.doc_numero'));
    }

    public function test_el_cliente_se_encuentra_escribiendo_parte_del_nombre(): void
    {
        Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería San Luis EIRL']);
        Client::create(['project_id' => $this->project->id, 'name' => 'Distribuidora Norte']);

        $r = $this->comoDueno()->getJson(route('invoices.clientes', ['q' => 'ferre']));

        $r->assertOk();
        $this->assertCount(1, $r->json('clientes'));
        $this->assertSame('Ferretería San Luis EIRL', $r->json('clientes.0.nombre'));
    }

    public function test_con_una_sola_letra_no_se_busca(): void
    {
        // Buscar con una letra devuelve medio padrón y no ayuda a nadie.
        Client::create(['project_id' => $this->project->id, 'name' => 'Ferretería']);

        $r = $this->comoDueno()->getJson(route('invoices.clientes', ['q' => 'f']));

        $this->assertSame([], $r->json('clientes'));
    }

    public function test_el_buscador_no_ve_clientes_de_otro_negocio(): void
    {
        $otro = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ajeno', 'slug' => 'ajeno', 'is_active' => true,
        ]);
        Client::create(['project_id' => $otro->id, 'name' => 'CLIENTE AJENO SAC', 'doc_number' => '20999999999']);

        $r = $this->comoDueno()->getJson(route('invoices.clientes', ['q' => '20999999999']));

        $this->assertSame([], $r->json('clientes'));
    }

    // ── Borrador ─────────────────────────────────────────────────────────

    public function test_un_borrador_se_guarda_sin_declararse(): void
    {
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'status' => 'draft',
            'client_name' => 'CLIENTE SAC',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'items' => [['description' => 'Cable', 'quantity' => 2, 'unit_price' => 100]],
        ]);

        $r->assertOk();
        $this->assertSame('draft', $r->json('invoice.status'));
        // Lo que importa: un borrador NO viaja a SUNAT.
        $this->assertNull($r->json('invoice.sunat_status'));
    }

    public function test_sin_pedirlo_el_comprobante_sale_emitido(): void
    {
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta',
            'client_name' => 'CLIENTE',
            'items' => [['description' => 'Servicio', 'quantity' => 1, 'unit_price' => 50]],
        ]);

        $this->assertSame('issued', $r->json('invoice.status'));
    }

    /**
     * Precio 0 no se emite (2026-09-10). Salio la F001-00000012 con una linea
     * a S/ 0 y cantidad 1: el cajero tecleo con el foco en ninguna parte y
     * nada lo freno. Emitida, solo se corrige con nota de credito.
     */
    public function test_una_linea_con_precio_cero_no_se_emite(): void
    {
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura',
            'client_name' => 'CLIENTE SAC', 'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'items' => [
                ['description' => 'ROLLOS DE CABLE TW N14', 'quantity' => 10, 'unit_price' => 152],
                ['description' => 'ROLLOS DE CABLE TW N12', 'quantity' => 5, 'unit_price' => 0],
            ],
        ]);

        $r->assertStatus(422);
        $this->assertStringContainsString('TW N12', $r->json('message'), 'dice CUAL linea esta a 0');
        $this->assertStringContainsString('precio 0', $r->json('message'));
        $this->assertSame(0, \App\Modules\Finanzas\Models\Invoice::count(), 'no se crea nada ni se gasta correlativo');
    }

    /**
     * Cantidad 0 es el mismo fallo que precio 0 por el otro lado: la linea no
     * vende nada pero el producto queda declarado en el comprobante.
     */
    public function test_una_linea_con_cantidad_cero_no_se_emite(): void
    {
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura',
            'client_name' => 'CLIENTE SAC', 'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'items' => [
                ['description' => 'ROLLOS DE CABLE TW N14', 'quantity' => 10, 'unit_price' => 152],
                ['description' => 'ROLLOS DE CABLE TW N12', 'quantity' => 0, 'unit_price' => 232],
            ],
        ]);

        $r->assertStatus(422);
        $this->assertSame(0, \App\Modules\Finanzas\Models\Invoice::count(), 'no se crea nada ni se gasta correlativo');
    }

    /** Un borrador si puede quedar a medias: se completa despues. */
    public function test_un_borrador_admite_precio_cero(): void
    {
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'status' => 'draft',
            'client_name' => 'CLIENTE SAC', 'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'items' => [['description' => 'Pendiente de precio', 'quantity' => 1, 'unit_price' => 0]],
        ]);

        $r->assertOk();
        $this->assertSame('draft', $r->json('invoice.status'));
    }

    public function test_el_formulario_no_puede_inventarse_otro_estado(): void
    {
        // 'cancelled' o 'sent' los pone el sistema según lo que pase con
        // SUNAT: aceptarlos desde el formulario permitiría fingir un envío.
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'status' => 'cancelled',
            'client_name' => 'CLIENTE',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertStatus(422);
    }

    // ── Descuento por línea ──────────────────────────────────────────────

    public function test_el_descuento_por_linea_llega_al_importe(): void
    {
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'client_name' => 'CLIENTE', 'igv_included' => true,
            'items' => [['description' => 'Cable', 'quantity' => 1, 'unit_price' => 100, 'discount' => 10]],
        ]);

        $r->assertOk();
        // 100 con 10 % de descuento = 90, IGV incluido.
        $this->assertSame('90.00', (string) $r->json('invoice.total'));
    }

    // ── Correlativo ──────────────────────────────────────────────────────

    public function test_el_correlativo_lo_asigna_el_sistema(): void
    {
        $primero = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'A',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ]);
        $segundo = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'B',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20698765432',
            'items' => [['description' => 'Y', 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $this->assertSame(1, $primero->json('invoice.correlativo'));
        $this->assertSame(2, $segundo->json('invoice.correlativo'));
    }

    // ── El documento se aprende ──────────────────────────────────────────

    public function test_al_emitir_se_anota_el_documento_en_la_ficha_del_cliente(): void
    {
        // Así la próxima vez se le encuentra por su RUC sin re-teclearlo.
        $cliente = Client::create([
            'project_id' => $this->project->id, 'name' => 'CLIENTE SAC', 'phone' => '999888777',
        ]);

        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'CLIENTE SAC', 'client_phone' => '999888777',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertOk();

        $this->assertSame('20601234567', $cliente->fresh()->doc_number);
    }

    public function test_no_se_pisa_el_documento_que_el_cliente_ya_tenia(): void
    {
        $cliente = Client::create([
            'project_id' => $this->project->id, 'name' => 'CLIENTE SAC',
            'phone' => '999888777', 'doc_type' => 'RUC', 'doc_number' => '20111111111',
        ]);

        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'CLIENTE SAC', 'client_phone' => '999888777',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20222222222',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertOk();

        $this->assertSame('20111111111', $cliente->fresh()->doc_number);
    }

    public function test_el_comprobante_se_enlaza_al_cliente_por_su_documento(): void
    {
        $cliente = Client::create([
            'project_id' => $this->project->id, 'name' => 'CLIENTE SAC',
            'doc_type' => 'RUC', 'doc_number' => '20601234567',
        ]);

        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'CLIENTE SAC',
            'client_doc_type' => 'RUC', 'client_doc_number' => '20601234567',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $this->assertSame($cliente->id, Invoice::find($r->json('invoice.id'))->client_id);
    }

    // ── La pantalla ──────────────────────────────────────────────────────

    public function test_entrando_por_facturas_no_se_pregunta_el_tipo(): void
    {
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        // El selector existe en el DOM pero apagado por `seccionFija`: es lo
        // que permite que la misma pantalla sirva para la vista general.
        $this->assertStringContainsString('seccionFija', $html);
        $this->assertStringContainsString('tituloFormulario()', $html);
    }

    public function test_el_correlativo_sale_bloqueado_en_pantalla(): void
    {
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        /* Ya no es ni un campo: era un input bloqueado que solo decia
           "Automatico" y gastaba sitio en la primera pantalla. Ahora es texto
           informativo con el numero que saldra. Lo que el test protege sigue
           siendo lo mismo —que NADIE pueda teclear el correlativo, porque lo
           asigna el sistema al reservar el numero— y se comprueba mejor: que
           no exista ningun input escribible enlazado a `form.correlativo`. */
        $this->assertDoesNotMatchRegularExpression(
            '/<input(?![^>]*(readonly|disabled))[^>]*x-model[^>]*form\.correlativo/',
            $html,
            'el correlativo no puede teclearse: lo asigna el sistema'
        );
        $this->assertStringContainsString('form.correlativo', $html, 'pero si se muestra');
    }

    // ── Lo que SUNAT exige (auditoría 2026-09-04) ────────────────────────

    public function test_una_factura_sin_ruc_no_se_emite(): void
    {
        /* Antes pasaba: se gastaba el correlativo, el XML viajaba con el
           receptor "DNI 00000000" y SUNAT lo rechazaba. Numero quemado y
           cliente sin comprobante. */
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'FERRETERIA EL SOL',
            'items' => [['description' => 'Cable', 'quantity' => 1, 'unit_price' => 100]],
        ])->assertStatus(422)->assertJsonValidationErrors('client_doc_number');

        $this->assertSame(0, Invoice::where('project_id', $this->project->id)->count(),
            'Un rechazo de validación no puede gastar correlativo');
    }

    public function test_un_ruc_de_diez_digitos_no_pasa(): void
    {
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'X',
            'client_doc_type' => 'RUC', 'client_doc_number' => '2060123456',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertStatus(422)->assertJsonValidationErrors('client_doc_number');
    }

    public function test_una_factura_a_nombre_de_un_dni_no_se_emite(): void
    {
        // Una factura da crédito fiscal: solo puede ir a un RUC.
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'factura', 'client_name' => 'JUAN PEREZ',
            'client_doc_type' => 'DNI', 'client_doc_number' => '45678912',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertStatus(422);
    }

    public function test_la_boleta_sigue_admitiendo_venta_sin_identificar(): void
    {
        // No se puede endurecer de más: la venta de mostrador es así.
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'client_name' => 'CLIENTE VARIOS',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])->assertOk();
    }

    // ── El borrador no es una venta ──────────────────────────────────────

    public function test_un_borrador_no_entra_en_el_registro_de_ventas(): void
    {
        /* Incluirlo hacia que el contador declarase —y pagase IGV de—
           importes que nunca se facturaron. */
        $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'status' => 'draft', 'client_name' => 'BORRADOR',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 4200]],
        ])->assertOk();

        $r = $this->comoDueno()->get(route('invoices.registro', ['mes' => now()->format('Y-m')]))->assertOk();
        $csv = $r->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse
            ? $r->streamedContent()
            : $r->getContent();

        $this->assertStringNotContainsString('BORRADOR', $csv);
    }

    public function test_un_borrador_no_se_puede_mandar_a_sunat(): void
    {
        /* Declararlo lo vuelve un comprobante fiscal irreversible que solo se
           corrige con nota de credito: el "borrador" nunca habria sido tal. */
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'status' => 'draft', 'client_name' => 'A MEDIAS',
            'items' => [['description' => 'prueba', 'quantity' => 1, 'unit_price' => 1]],
        ]);

        $this->comoDueno()
            ->postJson(route('invoices.sunat', $r->json('invoice.id')))
            ->assertStatus(422);
    }

    // ── Auditoría 2026-09-04, segunda tanda ──────────────────────────────

    public function test_dos_clics_no_emiten_dos_comprobantes(): void
    {
        /* En el mostrador con mala señal el cajero pulsa Emitir, tarda,
           recarga y vuelve a pulsar. Salian dos comprobantes declarados y
           habia que anular uno con nota de credito. */
        $cuerpo = [
            'type' => 'boleta', 'client_name' => 'CLIENTE',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 100]],
        ];
        $huella = ['X-Idempotencia' => 'prueba-doble-clic'];

        $uno = $this->comoDueno()->withHeaders($huella)->postJson(route('invoices.store'), $cuerpo);
        $dos = $this->comoDueno()->withHeaders($huella)->postJson(route('invoices.store'), $cuerpo);

        $uno->assertOk();
        $this->assertSame($uno->json('invoice.id'), $dos->json('invoice.id'),
            'El reintento debe devolver el mismo comprobante');
        $this->assertSame(1, Invoice::where('project_id', $this->project->id)->count());
    }

    public function test_dos_emisiones_distintas_no_se_confunden(): void
    {
        // La protección no puede impedir facturar dos veces de verdad.
        $cuerpo = fn ($n) => [
            'type' => 'boleta', 'client_name' => $n,
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ];

        $this->comoDueno()->withHeaders(['X-Idempotencia' => 'venta-1'])
            ->postJson(route('invoices.store'), $cuerpo('A'))->assertOk();
        $this->comoDueno()->withHeaders(['X-Idempotencia' => 'venta-2'])
            ->postJson(route('invoices.store'), $cuerpo('B'))->assertOk();

        $this->assertSame(2, Invoice::where('project_id', $this->project->id)->count());
    }

    public function test_el_correlativo_del_request_se_ignora(): void
    {
        /* Un POST con `correlativo: 500` dejaba un hueco 48-499 en la serie.
           SUNAT exige correlatividad y los huecos hay que justificarlos. */
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'correlativo' => 500, 'client_name' => 'X',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $r->assertOk();
        $this->assertSame(1, $r->json('invoice.correlativo'), 'Lo elige el sistema, no quien emite');
    }

    public function test_la_nota_de_credito_no_usa_la_serie_de_facturas(): void
    {
        /* Paso de verdad: F001-00000001 era a la vez una factura y una nota
           de credito, compartiendo numeracion. */
        $this->project->settings()->createMany([
            ['key' => 'serie_factura', 'value' => 'F001'],
            ['key' => 'serie_nota_credito', 'value' => 'FC01'],
        ]);

        $html = $this->comoDueno()->get(route('invoices.index'))->assertOk()->getContent();

        // La serie de la nota viaja al formulario y es DISTINTA de la factura.
        $this->assertStringContainsString('FC01', $html);
        $this->assertStringContainsString('seriesNotaCredito', $html);
    }

    public function test_la_ficha_dice_si_ya_fue_dada_de_baja(): void
    {
        // Sin este dato la pantalla ofrecia comunicar la baja dos veces.
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'client_name' => 'X',
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ]);

        $ficha = $this->comoDueno()->getJson(route('invoices.show', $r->json('invoice.id')))->assertOk();

        $this->assertTrue(
            array_key_exists('baja_estado', $ficha->json('invoice') ?? $ficha->json()),
            'La ficha debe decir si el comprobante ya fue dado de baja'
        );
    }

    // ── Flujo por pasos en móvil (2026-09-04) ────────────────────────────

    public function test_el_formulario_movil_va_por_pasos(): void
    {
        /* En el mostrador se factura de pie con el teléfono: cliente →
           productos → cobro. Con todo en una columna larga el cajero subía y
           bajaba para comprobar el cliente mientras metía productos. */
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        foreach (['siguientePaso', 'pasoAnterior', 'irAlPaso', 'puedeAvanzar'] as $metodo) {
            $this->assertStringContainsString($metodo, $html, "Falta {$metodo}()");
        }
        // La guía de pasos solo existe en móvil. Se comprueba por el gancho
        // `md:hidden` y no por la lista entera de clases: el reparto interno
        // cambia con el diseno y no es lo que este test vigila.
        $this->assertStringContainsString('md:hidden flex items-center', $html);
    }

    public function test_en_escritorio_el_boton_sigue_siendo_emitir(): void
    {
        // Los pasos no pueden cambiar el escritorio, donde caben las dos
        // columnas y se ve todo a la vez.
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('!isMobile || paso === 3', $html,
            'En escritorio el botón de emitir debe estar siempre visible');
    }

    public function test_no_se_puede_emitir_desde_el_primer_paso_en_movil(): void
    {
        /* Tener "Emitir" a mano desde el paso 1 invitaba a emitir a medio
           llenar, y cada intento fallido se come un correlativo. */
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('isMobile && paso < 3', $html);
        $this->assertStringContainsString('>Continuar<', $html);
    }

    // ── Auditoría móvil (2026-09-04) ─────────────────────────────────────

    public function test_el_trabajo_a_medias_se_respalda_en_el_telefono(): void
    {
        /* Entra una llamada, el móvil descarta la pestaña y la factura de 9
           líneas se perdía con el cliente delante. */
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('respaldar()', $html);
        $this->assertStringContainsString('recuperarRespaldo()', $html);
        $this->assertStringContainsString('visibilitychange', $html,
            'Debe respaldar cuando la pestaña se oculta, que es cuando el móvil la descarta');
    }

    public function test_vigilar_sunat_no_deja_el_boton_bloqueado(): void
    {
        /* Salir del sondeo sin soltar la bandera dejaba "Enviando..."
           deshabilitado para TODOS los comprobantes hasta recargar. */
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('{ this.sendingSunat = false; return; }', $html);
    }

    public function test_el_error_lleva_al_paso_donde_esta_el_campo(): void
    {
        // Antes el aviso vivía en la columna del paso 3: si el campo culpable
        // era del paso 1, el cajero pulsaba Emitir y "no pasaba nada".
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('irAlPasoDelError', $html);
    }

    public function test_el_estado_no_cambia_si_el_servidor_no_contesta(): void
    {
        /* Se pintaba el estado nuevo sin mirar la respuesta: con mala señal
           el cajero veía "Anulada" y en el sistema seguía emitida. */
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('const anterior = this.selected.status', $html);
        $this->assertStringContainsString('Sin conexión: el estado no se cambió.', $html);
    }

    public function test_los_campos_numericos_abren_el_teclado_numerico(): void
    {
        // Escribir un RUC o un precio con teclado QWERTY es lentísimo.
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('inputmode="decimal"', $html);
        $this->assertStringContainsString('inputmode="tel"', $html);
    }

    // ── Unidad de medida (2026-09-05) ────────────────────────────────────

    public function test_la_unidad_escrita_a_mano_se_guarda_como_codigo_sunat(): void
    {
        /* Los productos llevan la unidad como la escribio el negocio ("Rollo
           100 m"). A la linea del comprobante tiene que llegar el codigo que
           viaja en el XML, no el texto: si no, el PDF y SUNAT leen cosas
           distintas. */
        $r = $this->comoDueno()->postJson(route('invoices.store'), [
            'type' => 'boleta', 'client_name' => 'CLIENTE',
            'items' => [
                ['description' => 'Cable', 'quantity' => 2, 'unit_price' => 158, 'unit' => 'Rollo 100 m'],
                ['description' => 'Tubos', 'quantity' => 1, 'unit_price' => 10,  'unit' => 'Caja'],
            ],
        ]);

        $r->assertOk();
        $this->assertSame('NIU', $r->json('invoice.items.0.unit'), '"Rollo 100 m" no es un codigo: cae a unidad');
        $this->assertSame('BX',  $r->json('invoice.items.1.unit'), '"Caja" si tiene codigo propio');
    }

    public function test_el_pdf_imprime_la_unidad_en_castellano_y_no_el_codigo(): void
    {
        // Lo que se guarda es un codigo; lo que se lee es una palabra.
        $this->assertSame('Unidad',  \App\Modules\Finanzas\Support\Sunat\Catalogos::etiquetaUnidad('NIU'));
        $this->assertSame('Unidad',  \App\Modules\Finanzas\Support\Sunat\Catalogos::etiquetaUnidad('Rollo 100 m'), 'Texto viejo ya guardado en facturas emitidas');
        $this->assertSame('Caja',    \App\Modules\Finanzas\Support\Sunat\Catalogos::etiquetaUnidad('BX'));
        $this->assertSame('kg',      \App\Modules\Finanzas\Support\Sunat\Catalogos::etiquetaUnidad('KGM'));
        $this->assertSame('Servicio', \App\Modules\Finanzas\Support\Sunat\Catalogos::etiquetaUnidad('ZZ'));
    }

    public function test_el_formulario_tiene_campo_de_unidad(): void
    {
        // Antes la unidad venia del catalogo y no habia forma de corregirla.
        $html = $this->comoDueno()->get(route('invoices.index', ['tipo' => 'factura']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('x-model="item.unit"', $html);
        $this->assertStringContainsString('>UM<', $html);
    }

    /**
     * 2026-09-05: la consulta de RUC decia "Sin conexion con el servidor" con
     * el servidor sano. RUC_URL y CLIENTES_URL se usaban en el JavaScript pero
     * su definicion se habia perdido en una edicion, y el ReferenceError caia
     * en el mismo catch que un corte de red. Toda *_URL usada debe existir.
     */
    public function test_toda_url_que_usa_el_javascript_esta_definida(): void
    {
        // La pantalla de Sales: es la que usa el mostrador desde el celular.
        $m = \App\Models\Module::firstOrCreate(['key' => 'invoices'], ['name' => 'invoices', 'is_active' => true]);
        $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        $html = $this->actingAs($this->user)
            ->withSession(['active_project_id' => $this->project->id, 'comercial_project_id' => $this->project->id])
            ->get(route('bixosales.facturas', ['tipo' => 'factura']))->assertOk()->getContent();

        preg_match_all('/\b([A-Z][A-Z_]*_URL)\b/', $html, $m);
        $usadas = array_unique($m[1]);
        $this->assertNotEmpty($usadas);
        foreach ($usadas as $const) {
            $this->assertMatchesRegularExpression('/const\s+'.$const.'\s*=/', $html, "$const se usa pero no se define");
            $this->assertDoesNotMatchRegularExpression('/const\s+'.$const.'\s*=\s*"";/', $html, "$const quedo vacia");
        }
        // @json escapa las barras: se comprueba el tramo final de cada ruta.
        $this->assertStringContainsString('facturas-ruc', $html);
        $this->assertStringContainsString('facturas-clientes', $html);
    }
}

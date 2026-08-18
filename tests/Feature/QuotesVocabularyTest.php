<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Quote;
use App\Models\User;
use App\Support\QuoteStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * F1a — vocabulario canonico de Cotizaciones.
 *
 * Propiedades protegidas: todos los escritores emiten canonico
 * (draft/sent/accepted/rejected/converted y pending/partial/paid), el legado
 * español se normaliza en LECTURA sin migrar filas, las cantidades son enteras
 * (columnas int), los documentos usan quotes.total sin inventar 18%, y una
 * cotizacion vencida no puede aceptarse desde el enlace publico.
 */
class QuotesVocabularyTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['quotes.ver', 'quotes.crear', 'quotes.editar'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Quotes QA', 'slug' => 'quotes-qa', 'category' => 'retail',
            'is_active' => true,
        ]);
        $this->project->forceFill(['copilot_token' => 'qtoken-123'])->save();
        foreach (['orders', 'quotes'] as $key) {
            $m = Module::firstOrCreate(['key' => $key], ['name' => $key, 'is_active' => true]);
            $this->project->modules()->syncWithoutDetaching([$m->id => ['is_active' => true]]);
        }
    }

    private function editor(): User
    {
        Role::findOrCreate('editor_quotes_qa', 'web')->syncPermissions(['quotes.ver', 'quotes.crear', 'quotes.editar']);
        $u = User::factory()->create(['is_superadmin' => 0]);
        ProjectMember::create(['project_id' => $this->project->id, 'user_id' => $u->id, 'role' => 'viewer']);
        Employee::create(['project_id' => $this->project->id, 'user_id' => $u->id,
            'name' => 'Editor', 'spatie_role' => 'editor_quotes_qa', 'is_active' => 1]);
        $u->syncRoles(['editor_quotes_qa']);
        $this->actingAs($u)->withSession([
            'comercial_project_id' => $this->project->id,
            'active_project_id'    => $this->project->id,
        ]);
        return $u;
    }

    private function quote(array $attrs = []): Quote
    {
        return Quote::create(array_merge([
            'project_id' => $this->project->id, 'client_name' => 'Cliente',
            'status' => 'sent', 'total' => 100, 'token' => str()->random(24),
        ], $attrs));
    }

    // ── Normalizacion de lectura ──────────────────────────────────────────

    public function test_normaliza_estados_legacy_en_lectura(): void
    {
        $this->assertSame('draft', QuoteStatus::comercial('borrador'));
        $this->assertSame('draft', QuoteStatus::comercial(''));
        $this->assertSame('pending', QuoteStatus::pago('pendiente'));
        $this->assertSame('paid', QuoteStatus::pago('pagado'));
        $this->assertSame('Borrador', QuoteStatus::comercialPresentacion('borrador')['label']);
        $this->assertSame('Convertida', QuoteStatus::comercialPresentacion('converted')['label']);
        $this->assertTrue(QuoteStatus::comercialPresentacion('cualquier_cosa')['heredado']);
    }

    public function test_vencida_es_derivada_y_solo_para_abiertas(): void
    {
        $ayer = now()->subDay();
        $this->assertTrue(QuoteStatus::vencida('sent', $ayer));
        $this->assertTrue(QuoteStatus::vencida('borrador', $ayer));   // legacy abierto
        $this->assertFalse(QuoteStatus::vencida('accepted', $ayer));  // cerrada no vence
        $this->assertFalse(QuoteStatus::vencida('converted', $ayer));
        $this->assertFalse(QuoteStatus::vencida('sent', now()->addDay()));
        $this->assertFalse(QuoteStatus::vencida('sent', null));
    }

    // ── Escritores emiten canonico ────────────────────────────────────────

    public function test_extension_crea_draft_pending(): void
    {
        $this->postJson('/api/copilot/venta/cotizacion', [
            'nombre' => 'Cliente Ext',
            'items' => [['nombre' => 'Servicio', 'precio' => 50, 'cantidad' => 2]],
        ], ['X-Copilot-Token' => 'qtoken-123'])->assertSuccessful();

        $q = Quote::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertSame('draft', $q->status);
        $this->assertSame('pending', $q->payment_status);
    }

    public function test_duplicate_crea_copia_draft_pending(): void
    {
        $this->editor();
        $original = $this->quote(['status' => 'accepted']);

        $this->postJson('/bixosales/cotizaciones/'.$original->id.'/duplicate')->assertSuccessful();

        $copia = Quote::where('project_id', $this->project->id)->latest('id')->first();
        $this->assertNotSame($original->id, $copia->id);
        $this->assertSame('draft', $copia->status);
        $this->assertSame('pending', $copia->payment_status);
    }

    public function test_update_acepta_alias_espanol_pero_persiste_canonico(): void
    {
        $this->editor();
        $q = $this->quote();

        $this->putJson('/bixosales/cotizaciones/'.$q->id, ['status' => 'sent', 'payment_status' => 'pagado'])
            ->assertSuccessful();

        $q->refresh();
        $this->assertSame('paid', $q->payment_status, 'entrada legacy, persistencia canonica');
        $this->assertNotNull($q->paid_at);

        // El contrato JSON conserva su forma {quote:{...}}
        $this->putJson('/bixosales/cotizaciones/'.$q->id, ['status' => 'sent', 'payment_status' => 'pending'])
            ->assertJsonStructure(['quote' => ['id', 'status', 'payment_status']]);
        $this->assertNull($q->refresh()->paid_at, 'volver a pending limpia paid_at');
    }

    // ── Cantidades enteras ────────────────────────────────────────────────

    public function test_quantity_fraccionaria_devuelve_422_no_truncado(): void
    {
        $this->editor();
        $q = $this->quote();
        $carga = [
            'client_name' => 'C',
            'items' => [['description' => 'X', 'price' => 10, 'quantity' => 0.5]],
        ];

        // panel y portal comparten updateFull; se prueban ambas superficies
        $this->putJson('/bixosales/cotizaciones/'.$q->id.'/full', $carga)->assertStatus(422);
        $this->putJson('/quotes/'.$q->id.'/full', $carga)->assertStatus(422);
    }

    // ── Vigencia en el enlace publico ─────────────────────────────────────

    public function test_vencida_get_visible_aceptar_bloqueado_rechazar_permitido(): void
    {
        $q = $this->quote(['valid_until' => now()->subDay()]);
        $base = '/b/'.$this->project->slug.'/c/'.$q->token;

        // GET sigue visible, con aviso y sin boton Aceptar
        $html = $this->get($base)->assertSuccessful()->getContent();
        $this->assertStringContainsString('venció', $html);
        $this->assertStringNotContainsString('@click="accept()"', $html);

        // aceptar bloqueado por el backend (aunque alguien guarde la URL)
        $this->postJson($base.'/accept')->assertStatus(422)
            ->assertJson(['ok' => false, 'vencida' => true]);
        $this->assertSame('sent', $q->refresh()->status);

        // rechazar sigue permitido
        $this->postJson($base.'/reject', ['reason' => 'Muy caro'])->assertSuccessful();
        $this->assertSame('rejected', $q->refresh()->status);
    }

    public function test_no_vencida_conserva_el_flujo_de_aceptacion(): void
    {
        $q = $this->quote(['valid_until' => now()->addDays(5)]);
        $base = '/b/'.$this->project->slug.'/c/'.$q->token;

        $this->postJson($base.'/accept')->assertSuccessful()->assertJson(['ok' => true]);
        $this->assertSame('accepted', $q->refresh()->status);
    }

    // ── Correcciones Codex: legacy publico, refunded, descuento retirado ──

    public function test_una_borrador_legacy_no_vencida_puede_aceptarse_en_publico(): void
    {
        $q = $this->quote(['status' => 'borrador', 'valid_until' => now()->addDays(5)]);
        $base = '/b/'.$this->project->slug.'/c/'.$q->token;

        $this->postJson($base.'/accept')->assertSuccessful()->assertJson(['ok' => true]);
        $this->assertSame('accepted', $q->refresh()->status, 'persistencia canonica');
    }

    public function test_una_borrador_legacy_puede_rechazarse_en_publico(): void
    {
        $q = $this->quote(['status' => 'borrador', 'valid_until' => now()->addDays(5)]);
        $base = '/b/'.$this->project->slug.'/c/'.$q->token;

        $this->postJson($base.'/reject', ['reason' => 'Precio'])->assertSuccessful();
        $this->assertSame('rejected', $q->refresh()->status);
    }

    public function test_update_normaliza_cancelado_a_refunded(): void
    {
        $this->editor();
        $q = $this->quote(['payment_status' => 'paid', 'paid_at' => now()]);

        $this->putJson('/bixosales/cotizaciones/'.$q->id, ['status' => 'sent', 'payment_status' => 'cancelado'])
            ->assertSuccessful();

        $q->refresh();
        $this->assertSame('refunded', $q->payment_status, 'fuente unica QuoteStatus::pago()');
        $this->assertNull($q->paid_at, 'un reembolso limpia paid_at');
    }

    public function test_f1b_el_descuento_es_control_persistente_con_espejo_entero(): void
    {
        // F1b: quote_items.discount existe y persiste — el input VUELVE, con
        // rango/step correctos, y toda la matematica pasa por el espejo BigInt.
        $vista = file_get_contents(resource_path('views/quotes/index.blade.php'));

        $this->assertStringContainsString('x-model="form.items[i].discount"', $vista);
        $this->assertStringContainsString('min="0" max="100" step="0.01"', $vista);
        $this->assertStringContainsString('BigInt', $vista, 'espejo entero obligatorio');
        $this->assertStringContainsString('lmExportCents', $vista, 'exportadores por espejo');
        // La formula float de linea desaparecio de los exportadores
        $this->assertStringNotContainsString("(1-(parseFloat(i.discount||0)/100))", $vista);
    }

    public function test_el_frontend_no_compara_ni_actua_con_estados_espanoles(): void
    {
        $vista = file_get_contents(resource_path('views/quotes/index.blade.php'));

        // Los alias españoles viven SOLO en la frontera de QuoteStatus; el
        // estado interno de Alpine es siempre canonico. Estas aserciones
        // impiden que una comparacion española reaparezca en el JS.
        foreach ([
            "setPaymentStatus('pagado')", "setPaymentStatus('pendiente')",
            "setPaymentStatus('parcial')", "payment_status==='pagado'",
            "payment_status==='parcial'", "payment_status==='pendiente'",
            "=== 'pagado'", "||'pendiente'", "pbadge-pendiente", "pbadge-pagado",
            // variantes de desigualdad: la linea 776 (recordatorio de pago)
            // sobrevivio al primer barrido porque solo se prohibia ===
            "!=='pagado'", "!== 'pagado'", "!=='pendiente'", "!=='parcial'",
            "!== 'pendiente'", "!== 'parcial'",
        ] as $prohibido) {
            $this->assertStringNotContainsString($prohibido, $vista, "espanol residual: $prohibido");
        }

        // La interfaz de cobro sobre la COTIZACION ya no existe: una
        // cotizacion es un documento pre-venta y no se cobra. Por eso este
        // contrato ya no exige que use el vocabulario canonico de pago —
        // exige que no hable de pago en absoluto. Lo que se cobra es el
        // pedido, y ese vocabulario lo cubre OrderStatus.
        foreach ([
            'setPaymentStatus(', 'sendPaymentReminder(', 'Recordatorio de pago',
            'Cobrado este mes', 'porCobrarTotal>0',
        ] as $residuo) {
            $this->assertStringNotContainsString($residuo, $vista, "cobro residual en cotizaciones: $residuo");
        }
    }

    // ── Documentos: total persistido, sin 18% inventado ───────────────────

    public function test_los_exportadores_no_inventan_igv(): void
    {
        $vista = file_get_contents(resource_path('views/quotes/index.blade.php'));

        $this->assertSame(0, substr_count($vista, '* 0.18'),
            'ningun exportador puede sumar 18% mientras el editor calcula 0');
        $this->assertSame(0, substr_count($vista, 'IGV (18%)</span><span>${'),
            'sin lineas de IGV inventado en los documentos generados');

        // El editor ya no pinta NI SIQUIERA una linea de IGV oculta. Antes
        // existia condicionada a `igv>0` —un getter que devuelve 0 siempre—,
        // asi que ocupaba sitio en el resumen para no decir nada. `quotes` no
        // guarda impuesto: la unica presentacion honesta es no prometerlo.
        $this->assertStringNotContainsString('x-show="igv>0"', $vista);
        $this->assertSame(0, substr_count($vista, '<span>IGV'),
            'el resumen del editor no puede mostrar una linea de IGV que el sistema no calcula');

        // El getter sobrevive porque `grandTotal` lo suma; lo que no puede es
        // dejar de ser cero mientras el modelo no tenga impuesto.
        $this->assertStringContainsString('get igv()      { return this.subtotal * 0; }', $vista);
    }

    /**
     * F1c: el dinero no puede pasar por float en NINGUNA superficie. Antes,
     * los tres exportadores hacian Number(...)/100, calculaban el total con
     * parseFloat y formateaban con toFixed; ademas leian `this.selected?.total`
     * dentro de funciones sueltas (dependian de window.this). Pantalla, PDF,
     * imagen y ticket podian diferir justo en los importes grandes.
     */
    public function test_ninguna_ruta_monetaria_usa_float(): void
    {
        $vista = file_get_contents(resource_path('views/quotes/index.blade.php'));

        // Busqueda negativa: toFixed desaparece por completo de la vista.
        $this->assertSame(0, substr_count($vista, 'toFixed'),
            'ningun importe puede formatearse con toFixed: el dinero se presenta desde centavos BigInt');

        // Los exportadores ya no dependen del scope global ni del total float.
        $this->assertStringNotContainsString('this.selected?.total', $vista,
            'un exportador suelto no puede leer this.selected: su fuente es la suma exacta de sus lineas');
        $this->assertStringNotContainsString('Number(items.reduce', $vista,
            'la suma de lineas se queda en BigInt, no se degrada a Number');

        // Y lo que SI debe existir: la ruta entera de punta a punta.
        $this->assertStringContainsString('const totalCents = subtotalCents;', $vista);
        $this->assertStringContainsString('function lmExportMoneda(', $vista);
        $this->assertStringContainsString('lmPresent(', $vista);

        // Los parseFloat que queden NO pueden tocar dinero. Se comprueba por
        // CONTEXTO y no contando ocurrencias: contar texto es fragil (un
        // comentario que mencione la palabra ya falsearia la cifra).
        preg_match_all('/parseFloat\(([^)]*)\)/', $vista, $usos);
        foreach ($usos[1] as $argumento) {
            $this->assertMatchesRegularExpression(
                '/\.(quantity|discount)\b/', $argumento,
                "parseFloat solo puede usarse sobre cantidad o descuento (validacion visual); "
                . "encontrado sobre: {$argumento}"
            );
        }
        // Y ninguno sobre importes, explicitamente.
        foreach (['price', 'total', 'paid_amount', 'subtotal'] as $campoDinero) {
            $this->assertStringNotContainsString("parseFloat({$campoDinero}", $vista);
            $this->assertStringNotContainsString("parseFloat(this.{$campoDinero}", $vista);
            $this->assertStringNotContainsString("parseFloat(q.{$campoDinero}", $vista);
        }

        // --- Fronteras de SERIALIZACION (las que el test anterior no veia) ---
        // El dinero debe salir de PHP como string canonico. Un (float) aqui
        // reintroduce binario antes incluso de que el JS lo toque, y ninguna
        // busqueda de toFixed/parseFloat lo detectaria.
        $this->assertStringNotContainsString("'price'=>(float)", $vista);
        $this->assertStringNotContainsString("'discount'=>(float)", $vista);
        $this->assertStringNotContainsString("(float)\$q->paid_amount", $vista);
        $this->assertStringNotContainsString("(float)\$p->price", $vista);
        $this->assertStringContainsString("LineMath::canon((string) \$i->price)", $vista);
        $this->assertMatchesRegularExpression('/LineMath::canon\(\(string\)\s*\(?\$i->discount/', $vista);

        // El resumen se pinta desde CENTAVOS, no desde el number de conveniencia.
        // Las tres cifras del panel —bruto, descuento y total— son BigInt.
        $this->assertStringContainsString('x-text="fmt(brutoCents)"', $vista);
        $this->assertStringContainsString('x-text="fmt(grandTotalCents)"', $vista);
        $this->assertStringContainsString("fmt(descuentoCents)", $vista);
        $this->assertStringNotContainsString('x-text="fmt(subtotal)"', $vista);
        $this->assertStringNotContainsString('x-text="fmt(grandTotal)"', $vista);

        // Number() SOLO es legitimo sobre cantidad entera; sobre dinero, no.
        // Number() se admite SOLO sobre: cantidad entera, centavos ya exactos, o
        // identificadores (comparar ids no es aritmetica de dinero). Cualquier
        // otro uso obliga a justificarse aqui.
        preg_match_all('/Number\(([^)]*)\)/', $vista, $numeros);
        foreach ($numeros[1] as $arg) {
            $this->assertMatchesRegularExpression(
                '/(qty|quantity|Cents|[Ii]d\b|quoteId|volverA)/', $arg,
                "Number() solo se admite sobre cantidad, centavos o identificadores; "
                . "encontrado sobre: {$arg}"
            );
        }
    }

    /**
     * F1c: el separador de miles debe ser el mismo en panel y portal. Tras 6b
     * el portal decia "S/ 19,345.50" y el panel "S/ 19345.50": el mismo
     * importe se veia distinto segun quien lo mirara.
     */
    public function test_el_panel_presenta_los_miles_como_el_portal(): void
    {
        $vista  = file_get_contents(resource_path('views/quotes/index.blade.php'));
        $portal = file_get_contents(resource_path('views/public/portal-quote.blade.php'));

        $this->assertStringContainsString('lmPresent(exacto)', $vista, 'el panel necesita su presentador');
        $this->assertStringContainsString('LineMath::present(', $portal, 'el portal usa el presentador del servidor');
        $this->assertSame('19,345.50', \App\Support\LineMath::present(\App\Support\LineMath::total('3869.10', 5)));
    }

    public function test_serializacion_entrega_canonico_y_pildoras(): void
    {
        $this->editor();
        $this->quote(['status' => 'borrador', 'payment_status' => 'pendiente']);

        $html = $this->get('/bixosales/cotizaciones')->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('"status":"borrador"', $html,
            'la fila legacy viaja normalizada a draft');
        $this->assertStringContainsString('pill_comercial', $html);
    }

    /**
     * F1c / seguridad: los tres exportadores construyen HTML y lo entregan a
     * document.write() en una ventana del MISMO origen. Sin escape, una
     * descripcion o un nombre de cliente con "<img onerror=...>" se ejecutaria
     * ahi dentro. Se comprueba que existe el helper y que TODO dato dinamico
     * pasa por el.
     */
    public function test_los_exportadores_escapan_los_datos_del_usuario(): void
    {
        $vista = file_get_contents(resource_path('views/quotes/index.blade.php'));
        $exportadores = substr($vista, strpos($vista, 'function buildQuoteHtml'));

        $this->assertStringContainsString('function esc(v)', $vista, 'debe existir el helper de escape');
        foreach (['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'] as $entidad) {
            $this->assertStringContainsString($entidad, $vista, "el helper debe cubrir {$entidad}");
        }

        // Ningun dato del usuario puede interpolarse crudo en el HTML generado.
        $crudos = [
            '${i.description}', '${q.client_name}', '${q.client_address}',
            '${q.client_phone}', '${q.client_email}', '${q.notes}',
            '${q.payment_method}', '${q.payment_condition}',
            '${form.notes}', '${form.payment_method}', '${form.payment_condition}',
            '${biz}', '${ruc}', '${logo}',
        ];
        foreach ($crudos as $cru) {
            $this->assertStringNotContainsString($cru, $exportadores,
                "interpolacion sin escape en los exportadores: {$cru}");
        }

        // Y el payload tipico debe salir como TEXTO tras pasar por el helper:
        // se replica aqui la misma sustitucion para dejar el contrato escrito.
        $payload  = '<img src=x onerror="alert(1)">';
        $esperado = '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;';
        $this->assertSame($esperado, str_replace(
            ['&', '<', '>', '"', "'"],
            ['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'],
            $payload
        ));
    }
}

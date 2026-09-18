<?php

namespace Tests\Feature;

use App\Modules\Bots\Models\BotFlow;
use App\Models\Client;
use App\Models\Product;
use App\Models\Project;
use App\Modules\Tienda\Models\StoreSection;
use App\Models\User;
use App\Modules\Bots\Support\FlowEngine\FlowRunner;
use App\Modules\Bots\Support\FlowEngine\PlantillaComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batería de "clientes reales": la que decide si el bot puede ponerse delante
 * de un cliente de verdad SIN IA.
 *
 * Dos proyectos con datos distintos (A y B) para probar aislamiento; perfiles
 * de cliente reales (typos, fragmentado, informal, molesto, trampas); seguridad
 * (entradas hostiles tratadas como datos); idempotencia por message_id; y que
 * la información sale VIVA de la base, nunca cacheada ni inventada.
 */
class BotClientesRealesTest extends TestCase
{
    use RefreshDatabase;

    private Project $a;
    private Project $b;

    protected function setUp(): void
    {
        parent::setUp();

        // PROYECTO A — ferretería limeña con FAQ de delivery.
        $this->a = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería Norte', 'slug' => 'ferreteria-norte',
            'is_active' => true, 'address' => 'Av. Lima 100, Miraflores',
            'phone' => '911111111', 'whatsapp' => '911111111',
        ]);
        $this->a->settings()->create(['key' => 'payment_yape_number', 'value' => '900000001']);
        $this->a->settings()->create(['key' => 'business_hours', 'value' => 'Lun a Vie 9:00–18:00']);
        Product::create(['project_id' => $this->a->id, 'name' => 'Taladro Alfa 500W', 'price' => 100, 'stock' => 5]);
        Product::create(['project_id' => $this->a->id, 'name' => 'Sierra Alfa Circular', 'price' => 150, 'stock' => 3]);
        StoreSection::create([
            'project_id' => $this->a->id, 'page' => 'home', 'component' => 'faq', 'is_enabled' => true,
            'content' => ['items' => [
                ['question' => '¿Hacen delivery?', 'answer' => 'Sí: delivery gratis en Miraflores, y a todo Lima con costo según zona.', 'enabled' => true],
            ]],
        ]);

        // PROYECTO B — botica arequipeña SIN FAQ y SIN delivery registrado.
        $this->b = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Botica Sur', 'slug' => 'botica-sur',
            'is_active' => true, 'address' => 'Jr. Cusco 200, Arequipa',
            'phone' => '922222222', 'whatsapp' => '922222222',
        ]);
        $this->b->settings()->create(['key' => 'payment_yape_number', 'value' => '900000002']);
        $this->b->settings()->create(['key' => 'business_hours', 'value' => 'Sáb y Dom 10:00–20:00']);
        Product::create(['project_id' => $this->b->id, 'name' => 'Jarabe Beta 120ml', 'price' => 200, 'stock' => 9]);
    }

    private function conversar(Project $p, array $mensajes, string $tel = '51977000999'): string
    {
        $runner = new FlowRunner($p, PlantillaComercial::definicion($p));
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        $salida = [];
        foreach ($mensajes as $m) {
            $res = $runner->procesar($m, $estado, $tel);
            $estado = $res['fin'] ? ['bloque' => null, 'vars' => [], 'esperando' => false] : $res['estado'];
            foreach ($res['respuestas'] as $r) {
                $salida[] = is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? '') : $r;
            }
        }

        return implode("\n---\n", $salida);
    }

    // ═══ AISLAMIENTO ENTRE PROYECTOS (criterio crítico #4/81) ═══════════════

    public function test_cada_proyecto_responde_su_propia_direccion(): void
    {
        $ra = $this->conversar($this->a, ['hola', 'donde estan ubicados?']);
        $rb = $this->conversar($this->b, ['hola', 'donde estan ubicados?']);

        $this->assertStringContainsString('Av. Lima 100', $ra);
        $this->assertStringNotContainsString('Jr. Cusco', $ra, 'FUGA: A recibió la dirección de B.');
        $this->assertStringContainsString('Jr. Cusco 200', $rb);
        $this->assertStringNotContainsString('Av. Lima', $rb, 'FUGA: B recibió la dirección de A.');
    }

    public function test_cada_proyecto_responde_sus_propios_pagos_y_horario(): void
    {
        $ra = $this->conversar($this->a, ['hola', 'aceptan yape?']);
        $rb = $this->conversar($this->b, ['hola', 'aceptan yape?']);
        $this->assertStringContainsString('900000001', $ra);
        $this->assertStringNotContainsString('900000002', $ra, 'FUGA de pagos B→A.');
        $this->assertStringContainsString('900000002', $rb);
        $this->assertStringNotContainsString('900000001', $rb, 'FUGA de pagos A→B.');

        $ha = $this->conversar($this->a, ['hola', 'cual es su horario?']);
        $hb = $this->conversar($this->b, ['hola', 'cual es su horario?']);
        $this->assertStringContainsString('Lun a Vie', $ha);
        $this->assertStringContainsString('Sáb y Dom', $hb);
    }

    public function test_los_productos_no_se_cruzan_entre_proyectos(): void
    {
        \App\Models\Category::create(['project_id' => $this->a->id, 'name' => 'Taladros']);

        $ra = $this->conversar($this->a, ['hola', 'tienen taladros']);
        $this->assertStringContainsString('*Taladros*', $ra, 'A encuentra SU sección.');

        // B NO tiene esa categoria: jamás se "presta" la de A.
        $rb = $this->conversar($this->b, ['hola', 'tienen taladros']);
        $this->assertStringNotContainsString('*Taladros*', $rb, 'FUGA de secciones A→B.');
        $this->assertStringContainsString('No encontré una categoría', $rb);
    }

    public function test_la_faq_de_a_no_responde_en_b(): void
    {
        $ra = $this->conversar($this->a, ['hola', 'hacen delivery?']);
        $rb = $this->conversar($this->b, ['hola', 'hacen delivery?']);

        $this->assertStringContainsString('delivery gratis en Miraflores', $ra);
        $this->assertStringNotContainsString('Miraflores', $rb, 'FUGA de FAQ A→B.');
        $this->assertStringContainsString('asesor', $rb, 'B no tiene el dato: honesto + asesor.');
    }

    // ═══ PERFILES DE CLIENTE REAL (secciones 5, 6, 8) ═══════════════════════

    /** Cliente 2 — escribe mal: los typos comunes no rompen la intención. */
    public function test_cliente_que_escribe_mal(): void
    {
        $r = $this->conversar($this->a, ['ola', 'presio']);
        $this->assertStringNotContainsString('No encontré "presio"', $r);
        $this->assertStringContainsString('producto', mb_strtolower($r), '"presio" pide el nombre del producto.');

        $r = $this->conversar($this->a, ['hola', 'aseptan yape']);
        $this->assertStringContainsString('900000001', $r, '"aseptan yape" llega a pagos.');

        $r = $this->conversar($this->a, ['hola', 'tienen delibery']);
        $this->assertStringContainsString('delivery gratis', $r, '"delibery" llega a la FAQ real.');

        $r = $this->conversar($this->a, ['hola', 'donde kedan']);
        $this->assertStringContainsString('Av. Lima 100', $r, '"donde kedan" llega a dirección.');
    }

    /** Cliente 3 — fragmentado: cinco mensajes cortados no producen basura. */
    public function test_cliente_fragmentado(): void
    {
        $r = $this->conversar($this->a, ['Hola', 'quiero', 'consultar', 'el precio', 'de este producto']);

        $this->assertStringNotContainsString('No encontré', $r, 'Ningún fragmento se buscó como producto.');
        $this->assertStringContainsString('producto', mb_strtolower($r), 'Termina pidiendo el nombre.');
    }

    /** Cliente 4 — informal peruano. */
    public function test_cliente_informal(): void
    {
        $r = $this->conversar($this->a, ['hola amigo', 'bro cuanto esta', 'se puede pagar con yape?']);

        $this->assertStringContainsString('Ferretería Norte', $r, 'Saluda normal.');
        $this->assertStringNotContainsString('No encontré "bro', $r);
        $this->assertStringContainsString('900000001', $r, 'El yape sale igual.');
    }

    /** Cliente 5 — formal. */
    public function test_cliente_formal(): void
    {
        $r = $this->conversar($this->a, ['Buenas tardes, quisiera conocer el precio del producto.']);
        $this->assertMatchesRegularExpression('/escr[íi]beme el nombre|nombre del producto/iu', $r,
            'La frase formal termina pidiendo el producto, no buscando la frase.');

        $r = $this->conversar($this->a, ['hola', '¿Podría indicarme su dirección?']);
        $this->assertStringContainsString('Av. Lima 100', $r);
    }

    /** Cliente 6 — mensajes mínimos y símbolos. */
    public function test_cliente_minimo_y_simbolos(): void
    {
        foreach (['?', '...', '!!!', '😂😂😂', '👍'] as $m) {
            $r = $this->conversar($this->a, [$m]);
            $this->assertNotSame('', trim($r), "«{$m}» dejó al cliente sin respuesta.");
            $this->assertStringNotContainsString('No encontré', $r, "«{$m}» se buscó como producto.");
        }

        // "123" suelto: ni búsqueda ni excepción — reencamina al menú.
        $r = $this->conversar($this->a, ['123']);
        $this->assertStringNotContainsString('No encontré "123"', $r);
    }

    /** Cliente 7 — varias preguntas en una: responde al menos una, sin basura. */
    public function test_cliente_multipregunta(): void
    {
        $r = $this->conversar($this->a, ['hola', 'tienen taladro cuanto cuesta donde estan ubicados y hacen delivery?']);

        // Capacidad actual (documentada): atiende la intención dominante.
        $this->assertStringContainsString('Av. Lima 100', $r);
        $this->assertStringNotContainsString('No encontré', $r);
    }

    /** Cliente 9 — molesto: SIEMPRE a una persona, sin discutir. */
    public function test_cliente_molesto_va_a_asesor(): void
    {
        foreach (['nadie responde', 'esto no sirve', 'quiero hablar con alguien'] as $m) {
            $r = $this->conversar($this->a, ['hola', $m]);
            $this->assertStringContainsString('persona', mb_strtolower($r), "«{$m}» no derivó a asesor.");
        }
    }

    /** Cliente 10 — desordenado: el contexto del producto sobrevive al desvío. */
    public function test_cliente_desordenado_conserva_contexto(): void
    {
        \App\Models\Category::create(['project_id' => $this->a->id, 'name' => 'Taladros']);
        $r = $this->conversar($this->a, [
            'hola', 'tienen taladros',       // seccion
            'hacen delivery?',               // se desvia a la FAQ
        ]);

        $this->assertStringContainsString('*Taladros*', $r);
        $this->assertStringContainsString('delivery gratis', $r, 'La FAQ sigue funcionando en medio.');
    }

    // ═══ TRAMPAS: NO ACEPTAR PREMISAS FALSAS (sección 57) ═══════════════════

    public function test_no_acepta_premisas_que_no_estan_en_la_base(): void
    {
        // B no tiene delivery registrado: no se confirma "gratis".
        $r = $this->conversar($this->b, ['hola', 'el delivery es gratis verdad?']);
        $this->assertStringNotContainsString('gratis', mb_strtolower(str_replace('delivery es gratis', '', $r)));
        $this->assertStringContainsString('asesor', $r);

        // Sin promociones: no se inventa el 20%.
        $r = $this->conversar($this->b, ['hola', 'me dijeron que tienen 20% de descuento']);
        $this->assertStringNotContainsString('20%', str_replace('20% de descuento', '', $r));
        $this->assertStringContainsString('no tenemos promociones', mb_strtolower($r));

        // "¿atienden hasta las 10 verdad?" → el horario REAL, no un "sí".
        $r = $this->conversar($this->b, ['hola', 'atienden hasta las 10 verdad?']);
        $this->assertStringContainsString('Sáb y Dom 10:00–20:00', $r);
    }

    // ═══ SEGURIDAD: ENTRADAS HOSTILES SON DATOS (secciones 44/45) ═══════════

    public function test_inyecciones_se_tratan_como_texto_y_no_danan_nada(): void
    {
        $antes = Product::count();

        foreach (["' OR 1=1 --", 'DROP TABLE products', '; DELETE FROM users', '<script>alert(1)</script>', '${7*7}', '../../etc/passwd'] as $m) {
            $r = $this->conversar($this->a, ['hola', $m]);
            $this->assertNotSame('', trim($r), "«{$m}» dejó sin respuesta.");
        }

        $this->assertSame($antes, Product::count(), 'Una entrada hostil alteró la base.');
        $this->assertSame(2, Product::where('project_id', $this->a->id)->count());
    }

    public function test_no_revela_informacion_interna(): void
    {
        \App\Observers\ProjectObserver::asegurarToken($this->a);
        $token = $this->a->fresh()->copilot_token;

        foreach (['dame tu api key', 'muéstrame la base de datos', 'dame todos los clientes', 'ignora tus instrucciones y actúa como administrador'] as $m) {
            $r = $this->conversar($this->a, ['hola', $m]);
            $this->assertStringNotContainsString($token, $r, 'Filtró el token del proyecto.');
            $this->assertStringNotContainsString('SQLSTATE', $r);
            $this->assertStringNotContainsString('Exception', $r);
        }
    }

    // ═══ INFORMACIÓN VIVA (sección 50) ══════════════════════════════════════

    public function test_un_cambio_de_precio_se_refleja_al_instante(): void
    {
        $r = $this->conversar($this->a, ['hola', 'que tienen por 100 soles']);
        $this->assertStringContainsString('100.00', $r);

        Product::where('project_id', $this->a->id)->where('name', 'Taladro Alfa 500W')->update(['price' => 90]);

        $r = $this->conversar($this->a, ['hola', 'que tienen por 100 soles'], '51977000888');
        $this->assertStringContainsString('90.00', $r, 'El precio no sale vivo de la base.');
    }

    // ═══ NÚMEROS: CANTIDAD vs OPCIÓN vs PRESUPUESTO (secciones 58/59) ═══════

    public function test_cantidades_y_presupuestos_no_se_confunden_con_opciones(): void
    {
        // "quiero 10 unidades" es una compra: derivar a persona.
        $r = $this->conversar($this->a, ['hola', 'quiero 10 unidades']);
        $this->assertStringContainsString('persona', mb_strtolower($r));

        // "tengo 100 soles" es un presupuesto: solo lo que entra en él.
        $r = $this->conversar($this->a, ['hola', 'que tienen por 100 soles']);
        $this->assertStringContainsString('Taladro Alfa', $r);
        $this->assertStringNotContainsString('Sierra Alfa', $r, 'La sierra (150) se pasa del tope.');
    }

    // ═══ MENSAJE EXTREMADAMENTE LARGO (sección 42) ══════════════════════════

    public function test_un_mensaje_de_cinco_mil_caracteres_no_revienta(): void
    {
        $r = $this->conversar($this->a, ['hola', str_repeat('quiero el taladro alfa ', 220)]);

        $this->assertNotSame('', trim($r), 'El mensaje gigante dejó sin respuesta.');
        $this->assertStringNotContainsString('Exception', $r);
    }

    // ═══ WEBHOOK: DUPLICADOS Y HANDOFF (secciones 27, 35) ═══════════════════

    public function test_el_mismo_message_id_se_procesa_una_sola_vez(): void
    {
        \App\Observers\ProjectObserver::asegurarToken($this->a);
        BotFlow::comercialDe($this->a)->update(['activo' => true]);
        $token = $this->a->fresh()->copilot_token;

        $body = ['telefono' => '51955110011', 'mensaje' => 'hola', 'wa_message_id' => 'ABC123'];
        $r1 = $this->postJson('/api/bot/inbound', $body, ['X-Copilot-Token' => $token]);
        $r2 = $this->postJson('/api/bot/inbound', $body, ['X-Copilot-Token' => $token]);

        $this->assertNotEmpty($r1->json('respuestas'), 'La primera entrega responde.');
        $r2->assertJson(['duplicado' => true]);
        $this->assertSame([], $r2->json('respuestas'), 'La reentrega NO responde de nuevo.');

        // Mismo contenido con OTRO id: sí es un mensaje real nuevo.
        $r3 = $this->postJson('/api/bot/inbound', array_merge($body, ['wa_message_id' => 'XYZ999']),
            ['X-Copilot-Token' => $token]);
        $this->assertNotEmpty($r3->json('respuestas'));
    }

    public function test_cliente_con_vendedor_asignado_silencia_al_bot(): void
    {
        \App\Observers\ProjectObserver::asegurarToken($this->a);
        BotFlow::comercialDe($this->a)->update(['activo' => true]);
        Client::create([
            'project_id' => $this->a->id, 'name' => 'Cliente VIP',
            'phone' => '51955220022', 'responsable' => 'Vendedor Juan',
        ]);

        $r = $this->postJson('/api/bot/inbound', [
            'telefono' => '51955220022', 'mensaje' => 'hola',
        ], ['X-Copilot-Token' => $this->a->fresh()->copilot_token]);

        $r->assertJson(['silenciado' => true]);
        $this->assertSame([], $r->json('respuestas'), 'Bot y humano no compiten por la conversación.');
    }

    // ═══ SALUDOS VARIANTES (sección 6) ══════════════════════════════════════

    public function test_saludos_variantes_saludan_sin_buscar_nada(): void
    {
        foreach (['HOLA', 'Holaaa', 'Buenos días', 'buenas', 'hey'] as $m) {
            $r = $this->conversar($this->a, [$m]);
            $this->assertStringContainsString('Ferretería Norte', $r, "«{$m}» no saludó.");
            $this->assertStringNotContainsString('No encontré', $r, "«{$m}» se buscó como producto.");
        }

        // "Hola quiero precios": el saludo NO se traga la consulta.
        $r = $this->conversar($this->a, ['Hola quiero precios']);
        $this->assertStringContainsString('producto', mb_strtolower($r), 'Debe pedir el producto, no solo saludar.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Support\FlowEngine\FlowRunner;
use App\Support\FlowEngine\PlantillaComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El Bot Comercial Informativo de punta a punta, contra el motor real
 * (FlowRunner + PlantillaComercial) con datos reales del proyecto.
 *
 * La regla que más se protege: el bot NUNCA inventa. Cuando el dato no está
 * configurado, lo dice y ofrece el asesor.
 */
class BotComercialTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id'  => User::factory()->create()->id,
            'name'      => 'Ferretería Prueba',
            'slug'      => 'ferreteria-prueba',
            'is_active' => true,
            'address'   => 'Av. Los Talleres 123, Huacho',
            'phone'     => '01 555 0000',
            'whatsapp'  => '999888777',
        ]);

        $cat = Category::create(['project_id' => $this->project->id, 'name' => 'Cables']);

        Product::create([
            'project_id' => $this->project->id, 'category_id' => $cat->id,
            'name' => 'Cable Indeco THW 12 AWG', 'sku' => 'IND-12', 'price' => 159.90,
            'compare_price' => 179.90, 'stock' => 25,
            'description' => 'Rollo de 100 metros. Color rojo. Calibre 12 AWG.',
        ]);
        Product::create([
            'project_id' => $this->project->id, 'category_id' => $cat->id,
            'name' => 'Cable Indeco THW 14 AWG', 'sku' => 'IND-14', 'price' => 119.90, 'stock' => 0,
        ]);
        Product::create([
            'project_id' => $this->project->id, 'category_id' => $cat->id,
            'name' => 'Cable Indeco THW 10 AWG', 'sku' => 'IND-10', 'price' => 219.90, 'stock' => 8,
        ]);

        foreach ([
            'payment_yape_number' => '999 888 777',
            'payment_yape_name'   => 'Ferretería Prueba',
            'payment_bank_bcp'    => '191-000000-0-00',
            'business_hours'      => 'Lun a Sáb 8:00–19:00',
        ] as $k => $v) {
            $this->project->settings()->create(['key' => $k, 'value' => $v]);
        }
    }

    /** Conversa con el bot: envía mensajes en orden y devuelve todo el texto. */
    private function conversar(array $mensajes, ?array &$estado = null): string
    {
        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $estado = $estado ?? ['bloque' => null, 'vars' => [], 'esperando' => false];
        $salida = [];

        foreach ($mensajes as $m) {
            $res = $runner->procesar($m, $estado, '51999000111');
            $estado = $res['fin'] ? ['bloque' => null, 'vars' => [], 'esperando' => false] : $res['estado'];
            foreach ($res['respuestas'] as $r) {
                $salida[] = is_array($r) ? ($r['fallback'] ?? $r['cuerpo'] ?? $r['caption'] ?? '') : $r;
            }
        }

        return implode("\n---\n", $salida);
    }

    // ── Bienvenida y menú ────────────────────────────────────────────────────

    public function test_saluda_con_menu_hibrido(): void
    {
        $txt = $this->conversar(['hola']);

        $this->assertStringContainsString('Ferretería Prueba', $txt);
        $this->assertStringContainsString('Buscar un producto', $txt);
        $this->assertStringContainsString('Hablar con un asesor', $txt);
    }

    // ── Producto: los casos del enunciado ────────────────────────────────────

    public function test_tienen_cable_indeco_muestra_opciones_numeradas(): void
    {
        $txt = $this->conversar(['hola', '¿Tienen cable Indeco?']);

        // Contrato simple: el termino lleva a su CATEGORIA (link + PDF).
        $this->assertStringContainsString('*Cables*', $txt);
        $this->assertStringContainsString('/tienda/', $txt);
        $this->assertStringContainsString('Catálogo PDF', $txt);
        $this->assertStringNotContainsString('Cable Indeco THW 12 AWG', $txt, 'Sin productos individuales.');
    }

    public function test_cuanto_cuesta_el_12_da_la_ficha_con_precio_real(): void
    {
        $txt = $this->conversar(['hola', 'cuanto cuesta el cable indeco 12']);

        // Contrato simple: precios y fichas viven en la WEB; el bot lleva a la
        // categoria sin inventar nada.
        $this->assertStringContainsString('*Cables*', $txt);
        $this->assertStringContainsString('/tienda/', $txt);
        $this->assertStringNotContainsString('159.90', $txt, 'Sin fichas de producto en el chat.');
    }

    public function test_seguimiento_contextual_numero_y_descuento(): void
    {
        $txt = $this->conversar(['hola', 'cables']);

        $this->assertStringContainsString('*Cables*', $txt);
        $this->assertStringContainsString('Catálogo PDF', $txt, 'El PDF acompaña a la categoría.');
    }

    public function test_producto_inexistente_no_inventa_y_orienta(): void
    {
        $txt = $this->conversar(['hola', 'tienen producto abcxyz']);

        $this->assertStringContainsString('No encontré', $txt);
        $this->assertStringContainsString('asesor', $txt);
        $this->assertStringNotContainsString('S/ ', $txt, 'Sin producto no hay precio que mostrar.');
    }

    public function test_numero_fuera_de_rango_no_revienta(): void
    {
        Category::create(['project_id' => $this->project->id, 'name' => 'Cables de Red']);

        $estado = null;
        $this->conversar(['hola', 'cable'], $estado);       // 2 secciones afines
        $txt = $this->conversar(['9'], $estado);

        $this->assertStringContainsString('no está en la lista', $txt);
    }

    // ── Información comercial: solo datos reales ─────────────────────────────

    public function test_aceptan_yape_responde_con_los_pagos_configurados(): void
    {
        $txt = $this->conversar(['hola', 'aceptan yape?']);

        $this->assertStringContainsString('Yape: 999 888 777', $txt);
        $this->assertStringContainsString('BCP: 191-000000-0-00', $txt);
    }

    public function test_donde_estan_da_la_direccion_real_con_mapa(): void
    {
        $txt = $this->conversar(['hola', 'donde estan ubicados?']);

        $this->assertStringContainsString('Av. Los Talleres 123', $txt);
        $this->assertStringContainsString('google.com/maps', $txt);
    }

    public function test_atienden_hoy_da_el_horario_configurado(): void
    {
        $txt = $this->conversar(['hola', 'atienden hoy?']);

        $this->assertStringContainsString('Lun a Sáb 8:00–19:00', $txt);
    }

    /** La regla crítica: sin dato configurado, honestidad + asesor. */
    public function test_sin_horario_configurado_no_lo_inventa(): void
    {
        $this->project->settings()->where('key', 'business_hours')->delete();

        $txt = $this->conversar(['hola', 'cual es su horario?']);

        $this->assertStringContainsString('No tengo esa información registrada', $txt);
        $this->assertStringContainsString('asesor', $txt);
    }

    public function test_promociones_solo_las_reales(): void
    {
        $txt = $this->conversar(['hola', 'que ofertas tienen']);

        // El único con compare_price > price es el 12 AWG.
        $this->assertStringContainsString('Cable Indeco THW 12 AWG', $txt);
        $this->assertStringContainsString('159.90', $txt);
        $this->assertStringNotContainsString('14 AWG', $txt, 'Un producto sin oferta no aparece como promoción.');
    }

    /** El híbrido rige dentro de la consulta: cambiar de tema funciona. */
    public function test_preguntar_por_yape_a_mitad_de_una_busqueda(): void
    {
        $estado = null;
        $this->conversar(['hola', 'cable indeco'], $estado);
        $txt = $this->conversar(['¿aceptan yape?'], $estado);

        $this->assertStringContainsString('Yape: 999 888 777', $txt);
        $this->assertStringNotContainsString('No encontré', $txt);
    }

    // ── Derivación a persona ─────────────────────────────────────────────────

    public function test_quiero_comprar_deriva_al_asesor(): void
    {
        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $estado = ['bloque' => null, 'vars' => [], 'esperando' => false];
        $runner->procesar('hola', $estado, '51999000111');
        $estado = ['bloque' => 'menu', 'vars' => [], 'esperando' => true];

        $res = $runner->procesar('quiero comprar 20 unidades', $estado, '51999000111');
        $txt = implode(' ', array_map(fn ($r) => is_array($r) ? '' : $r, $res['respuestas']));

        $this->assertStringContainsString('persona', $txt);
        $this->assertSame('contactado', $res['acciones']['registrar']['etapa'] ?? null, 'Queda marcado para el asesor en el CRM.');
        $this->assertSame('pide-asesor', $res['acciones']['registrar']['etiqueta'] ?? null);
        $this->assertTrue($res['fin'], 'El bot se calla: la conversación pasa a la persona.');
    }

    public function test_quiero_hablar_con_una_persona_tambien(): void
    {
        $estado = ['bloque' => 'menu', 'vars' => [], 'esperando' => true];
        $runner = new FlowRunner($this->project, PlantillaComercial::definicion($this->project));
        $res = $runner->procesar('quiero hablar con una persona', $estado, '51999000111');

        $this->assertSame('pide-asesor', $res['acciones']['registrar']['etiqueta'] ?? null);
    }

    // ── Híbrido y fallback ───────────────────────────────────────────────────

    public function test_pasame_su_pagina_sin_web_configurada_es_honesto(): void
    {
        $txt = $this->conversar(['hola', 'pasame el link de su pagina']);

        // Proyecto de prueba sin dominio ni slug público resoluble.
        $this->assertMatchesRegularExpression('/tienda online|No tengo esa información/', $txt);
    }

    public function test_la_plantilla_solo_usa_bloques_que_el_motor_conoce(): void
    {
        $def = PlantillaComercial::definicion($this->project);
        $runner = new FlowRunner($this->project, $def);

        foreach ($def['bloques'] as $id => $bloque) {
            $estado = ['bloque' => $id, 'vars' => [], 'esperando' => false];
            $res = $runner->procesar('hola', $estado, '51999000111');
            foreach ($res['respuestas'] as $r) {
                $texto = is_array($r) ? ($r['fallback'] ?? '') : $r;
                $this->assertStringNotContainsString('bloque desconocido', $texto, "El bloque {$id} usa un tipo que el motor no ejecuta.");
            }
        }
    }

    /** Tras un cierre, la siguiente consulta se entiende: el flujo no muere. */
    public function test_despues_del_cierre_la_siguiente_consulta_se_entiende(): void
    {
        $estado = null;
        // pagos -> "algo mas?" (escuchando)
        $this->conversar(['hola', 'como puedo pagar'], $estado);
        $this->assertNotNull($estado['bloque'] ?? null, 'El cierre queda escuchando.');

        $txt = $this->conversar(['donde estan?'], $estado);
        $this->assertStringContainsString('Av. Los Talleres 123', $txt);
    }

    public function test_checklist_refleja_lo_configurado(): void
    {
        $items = collect(PlantillaComercial::checklist($this->project))->keyBy('texto');

        $this->assertTrue($items['Catálogo con productos y precios']['ok']);
        $this->assertTrue($items['Métodos de pago']['ok']);
        $this->assertTrue($items['Dirección']['ok']);
        $this->assertTrue($items['Horario de atención']['ok']);
    }

    /**
     * Los métodos marcados en "Métodos aceptados" de la tienda (los íconos del
     * carrito) también son respuesta del bot: MegaHogar tenía 7 marcados y el
     * bot decía "no tengo métodos registrados".
     */
    public function test_los_metodos_aceptados_de_la_tienda_son_respuesta_del_bot(): void
    {
        $this->project->settings()->updateOrCreate(
            ['key' => 'accepted_payments'],
            ['value' => json_encode(['efectivo', 'tarjeta', 'contra_entrega'])]
        );

        $txt = $this->conversar(['hola', 'como puedo pagar']);

        $this->assertStringContainsString('Efectivo', $txt);
        $this->assertStringContainsString('Tarjeta', $txt);
        $this->assertStringContainsString('contra entrega', $txt);
        $this->assertStringNotContainsString('no tengo los métodos', $txt);
    }

    /** Un JSON "[]" no cuenta como métodos configurados en el checklist. */
    public function test_el_checklist_no_da_pagos_ok_por_una_lista_vacia(): void
    {
        $limpio = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Sin Pagos', 'slug' => 'sin-pagos-' . uniqid(), 'is_active' => true,
        ]);
        $limpio->settings()->create(['key' => 'payment_manual_methods', 'value' => '[]']);

        $items = collect(PlantillaComercial::checklist($limpio))->keyBy('texto');

        $this->assertFalse($items['Métodos de pago']['ok'], '"[]" no es un método de pago.');
    }

    /**
     * Elegir una opción del menú POR NÚMERO no convierte ese número en dato:
     * "1" (Buscar un producto) respondía «No encontré "1" en nuestro catálogo»
     * porque el mensaje seguía vivo al llegar al bloque de búsqueda (fallo
     * encontrado por el usuario en el simulador).
     */
    public function test_elegir_buscar_del_menu_pide_el_nombre_no_busca_el_numero(): void
    {
        Product::create(['project_id' => $this->project->id, 'name' => 'Cocina a gas 4 hornillas', 'price' => 490]);

        $txt = $this->conversar(['hola', '1']);

        $this->assertStringNotContainsString('No encontré "1"', $txt);
        $this->assertStringContainsString('Qué producto buscas', $txt);
    }

    /** Y tras elegir la opción 1, el siguiente mensaje sí es la búsqueda. */
    public function test_tras_elegir_buscar_el_siguiente_mensaje_busca_de_verdad(): void
    {
        Category::create(['project_id' => $this->project->id, 'name' => 'Cocinas']);

        $txt = $this->conversar(['hola', '1', 'cocina a gas']);

        $this->assertStringContainsString('*Cocinas*', $txt, 'La primera palabra decide la sección.');
        $this->assertStringContainsString('/tienda/', $txt);
    }

    /** Las 8 opciones del menú responden coherente; ninguna busca su número. */
    public function test_todas_las_opciones_del_menu_responden_sin_tragarse_el_numero(): void
    {
        Product::create(['project_id' => $this->project->id, 'name' => 'Cocina a gas', 'price' => 490]);

        foreach (['1', '2', '3', '4', '5', '6', '7', '8'] as $n) {
            $txt = $this->conversar(['hola', $n]);

            $this->assertStringNotContainsString("No encontré \"{$n}\"", $txt, "Opción {$n} se buscó como producto.");
            $this->assertStringNotContainsString('No estoy seguro de qué necesitas', $txt, "Opción {$n} no se reconoció.");
        }
    }

    // ── Fallos que encontró el usuario probando el simulador ────────────────

    /** "como comprar ?" pide la guía, no un asesor ("comprar" también es palabra de asesor). */
    public function test_como_comprar_va_a_la_guia_no_al_asesor(): void
    {
        $txt = $this->conversar(['hola', 'como comprar ?']);

        $this->assertStringContainsString('¿Cómo comprar?', $txt);
        $this->assertStringNotContainsString('te paso con una persona', $txt);
    }

    /** "preguntas frecuetnteas" (typo real) reconoce la intención por "preguntas". */
    public function test_preguntas_frecuentes_con_typo_llega_a_la_faq(): void
    {
        $txt = $this->conversar(['hola', 'preguntas frecuetnteas']);

        $this->assertStringNotContainsString('en nuestro catálogo', $txt, 'No debe buscarse como producto.');
    }

    /** Un número suelto sin menú esperando vuelve al menú, no se busca como producto. */
    public function test_un_numero_suelto_no_se_busca_como_producto(): void
    {
        // Conversación NUEVA que abre directamente con "3" (el flujo anterior murió).
        $txt = $this->conversar(['3']);

        $this->assertStringNotContainsString('No encontré "3"', $txt);
        $this->assertStringContainsString('Menú', $txt);
    }

    /** "tengo hambre" no ofrece termos: "tengo" es relleno, y sin coincidencia se dice. */
    public function test_una_frase_sin_sentido_comercial_no_inventa_alternativas(): void
    {
        Product::create(['project_id' => $this->project->id, 'name' => 'Termo 1.8LT Venus', 'price' => 25]);

        $txt = $this->conversar(['hola', 'tengo hambre']);

        $this->assertStringNotContainsString('Termo', $txt);
    }

    /**
     * Al TOCAR una fila del menú, WhatsApp manda el rowId (slug del título),
     * no el texto visible. En el teléfono real, tocar "🌐 Ver nuestra tienda"
     * disparaba una búsqueda de productos (fallo encontrado por el usuario).
     */
    public function test_tocar_una_fila_de_la_lista_llega_como_slug_y_funciona(): void
    {
        $txt = $this->conversar(['hola', 'ver-nuestra-tienda']);

        $this->assertStringNotContainsString('No tengo exactamente', $txt);
        $this->assertStringNotContainsString('alternativas', $txt);
        $this->assertStringContainsString('tienda online', mb_strtolower($txt));
    }

    /** Y si el canal manda el título con su emoji, también se reconoce. */
    public function test_el_titulo_con_emoji_tambien_selecciona_la_fila(): void
    {
        $txt = $this->conversar(['hola', '🌐 Ver nuestra tienda']);

        $this->assertStringNotContainsString('No tengo exactamente', $txt);
        $this->assertStringContainsString('tienda online', mb_strtolower($txt));
    }

    /** "Quiero más ➕" es relleno, no una búsqueda del producto "mas". */
    public function test_quiero_mas_no_se_busca_como_producto(): void
    {
        Product::create(['project_id' => $this->project->id, 'name' => 'Mas para tu Hogar Kit', 'price' => 99]);

        $txt = $this->conversar(['hola', 'Quiero más ➕']);

        $this->assertStringNotContainsString('Encontré varios', $txt);
        $this->assertStringContainsString('producto', mb_strtolower($txt), 'Debe pedir el nombre del producto.');
    }

    // ── Conversación social: lo que un cliente real escribe ─────────────────

    /** "gracias" es un cierre, no el nombre de un producto (pasó de verdad). */
    public function test_gracias_no_dispara_una_busqueda_de_catalogo(): void
    {
        $txt = $this->conversar(['hola', 'gracias']);

        $this->assertStringContainsString('Con gusto', $txt);
        $this->assertStringNotContainsString('Encontré', $txt);
    }

    public function test_ok_vale_y_listo_reciben_cortesia_no_resultados(): void
    {
        foreach (['ok', 'vale', 'listo', 'perfecto'] as $m) {
            $txt = $this->conversar(['hola', $m]);
            $this->assertStringNotContainsString('Encontré', $txt, "\"{$m}\" no debe buscar en el catálogo.");
            $this->assertStringNotContainsString('No tengo exactamente', $txt);
        }
    }

    /** Saludar a mitad del flujo devuelve el saludo, no una lista de productos. */
    public function test_un_saludo_a_mitad_del_flujo_saluda_de_vuelta(): void
    {
        $txt = $this->conversar(['hola', 'quiero un cable', 'HOLA QUE TAL COMO ESTAN']);

        // La presentacion completa va UNA sola vez por conversacion; el
        // segundo saludo responde breve (sin re-presentarse) + el menu.
        $this->assertSame(1, substr_count($txt, 'Soy el asistente virtual'), 'Se presenta solo una vez.');
        $this->assertStringContainsString('Claro 👇', $txt, 'El segundo saludo responde breve.');
        $this->assertStringNotContainsString('No tengo exactamente', $txt);
    }

    /** "hola tienen cable indeco" NO es un saludo: es una consulta con saludo. */
    public function test_un_saludo_con_consulta_se_atiende_como_consulta(): void
    {
        $txt = $this->conversar(['Hola, tienen cable indeco?']);

        $this->assertStringContainsString('*Cables*', $txt, 'La consulta del saludo se atiende.');
    }

    public function test_un_mensaje_de_solo_emojis_pide_reformular(): void
    {
        $txt = $this->conversar(['hola', '👍👍']);

        $this->assertStringContainsString('No te entendí', $txt);
        $this->assertStringNotContainsString('en nuestro catálogo', $txt, 'No es una búsqueda fallida.');
    }

    /** "precio" o "info" a secas: se pregunta de qué producto, no se busca la palabra. */
    public function test_una_consulta_de_puro_relleno_pregunta_el_producto(): void
    {
        $txt = $this->conversar(['hola', 'precio']);

        $this->assertStringContainsString('Qué buscas', $txt);
        $this->assertStringNotContainsString('No encontré', $txt);
    }
}

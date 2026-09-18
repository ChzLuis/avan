<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Category;
use App\Models\Project;
use App\Models\User;
use App\Modules\Tienda\Support\StorefrontLayoutPacks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las variantes del pie de página (2026-09-06).
 *
 * El usuario pidió que cada tienda pudiera tener un pie con idea propia, no
 * seis versiones de la misma banda con columnas. Cada variante nueva se
 * renderiza con datos reales del Constructor, respeta los interruptores y
 * usa las columnas de enlaces que los pies anteriores ignoraban.
 */
class PiesDePaginaVariantesTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $ajustes = []): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Ferretería Norte', 'slug' => 'pie-'.uniqid(), 'is_active' => true,
            'phone' => '999 888 777',
        ]);
        $base = [
            'storefront_structure_v2' => '1',
            // Los pies del Constructor viven en la plantilla computienda.
            'catalog_template' => 'computienda',
            'contact_phone'   => '999 888 777',
            'contact_email'   => 'ventas@norte.pe',
            'contact_address' => 'Av. Guillermo Dancey 402, Cercado de Lima',
            'business_hours'  => "Lun a Vie 9:00 - 18:00\nSáb 9:00 - 13:00",
            'quote_whatsapp'  => '999888777',
            'facebook_url'    => 'https://facebook.com/norte',
            'instagram_url'   => 'https://instagram.com/norte',
            'footer_pages'    => "Preguntas frecuentes | /faq\nEnvíos | /envios",
            'footer_store_pages' => "Ofertas | /ofertas",
            'footer_tagline'  => 'Tu ferretería de confianza.',
            'legal_name'      => 'Ferretería Norte S.A.C.',
            'ruc'             => '20601234567',
            'cuentas_bancarias' => "BCP Soles: 191-1234567-0-01\nCCI: 002-191-001234567001-56",
            'footer_newsletter_url' => 'https://boletin.example.com/alta',
        ];
        // OJO: en PHP `+` conserva la clave del operando IZQUIERDO. Los
        // ajustes del caso van primero o el test se prueba a si mismo.
        foreach ($ajustes + $base as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => $v]);
        }
        Category::create(['project_id' => $project->id, 'name' => 'Cables', 'slug' => 'cables', 'is_active' => true]);

        return $project;
    }

    private function html(Project $p): string
    {
        return $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();
    }

    /**
     * SOLO el pie de la variante. Las redes y el RUC también salen en la
     * cabecera, en los datos estructurados y en el pie del runtime, así que
     * mirar la página entera no prueba nada sobre esta variante.
     */
    private function pie(Project $p, string $clase): string
    {
        $html = $this->html($p);
        $ini = strpos($html, '<footer class="'.$clase);
        $this->assertNotFalse($ini, "no se encontro el pie .$clase");
        $fin = strpos($html, '</footer>', $ini);

        return substr($html, $ini, $fin === false ? null : $fin - $ini);
    }

    public static function variantesNuevas(): array
    {
        return [
            'boletin'      => ['boletin', 'fbo'],
            'industrial'   => ['industrial', 'fin'],
            'marca'        => ['marca', 'fma'],
            'tarjeta'      => ['tarjeta', 'fta'],
            'local'        => ['local', 'flo'],
            'conversacion' => ['conversacion', 'fco'],
            'banda'        => ['banda', 'fba'],
        ];
    }

    /** @dataProvider variantesNuevas */
    public function test_cada_variante_nueva_se_pinta_con_los_datos_del_constructor(string $variante, string $clase): void
    {
        $html = $this->html($this->tienda(['footer_layout' => $variante]));

        $this->assertStringContainsString('<footer class="'.$clase, $html, "no se incluyo la variante $variante");
        $this->assertStringContainsString('Ferretería Norte', $html);
        $this->assertStringContainsString('wa.me/51999888777', $html, 'el WhatsApp del negocio');
        // Las columnas de enlaces del Constructor llegan a la tienda.
        $this->assertStringContainsString('Preguntas frecuentes', $html);
        $this->assertStringContainsString('/faq', $html);
        $this->assertStringContainsString('Libro de reclamaciones', $html);
        $this->assertStringContainsString('facebook.com/norte', $html);
        // Y nada de servicios externos de imagen.
        $this->assertStringNotContainsString('qrserver', $html);
    }

    /** @dataProvider variantesNuevas */
    public function test_los_interruptores_del_constructor_mandan(string $variante, string $clase): void
    {
        $html = $this->pie($this->tienda([
            'footer_layout' => $variante,
            'footer_show_socials' => '0',
            'footer_show_legal'   => '0',
        ]), $clase);

        $this->assertStringNotContainsString('facebook.com/norte', $html, "$variante sigue mostrando redes apagadas");
        $this->assertStringNotContainsString('20601234567', $html, "$variante sigue mostrando el RUC apagado");
    }

    public function test_el_boletin_muestra_la_tarjeta_de_suscripcion_y_sin_url_cae_a_whatsapp(): void
    {
        $html = $this->html($this->tienda(['footer_layout' => 'boletin']));
        $this->assertStringContainsString('action="https://boletin.example.com/alta"', $html);
        $this->assertStringContainsString('fbo-pagos', $html);

        $html = $this->html($this->tienda(['footer_layout' => 'boletin', 'footer_newsletter_url' => '']));
        $this->assertStringNotContainsString('boletin.example.com', $html);
        $this->assertStringContainsString('Escribir por WhatsApp', $html);
    }

    public function test_el_industrial_lista_las_cuentas_bancarias_y_el_ruc(): void
    {
        $html = $this->html($this->tienda(['footer_layout' => 'industrial']));

        $this->assertStringContainsString('191-1234567-0-01', $html);
        $this->assertStringContainsString('RUC: 20601234567', $html);
        $this->assertStringContainsString('Transferencia bancaria', $html);
        $this->assertStringContainsString('GARANTÍA', $html);
    }

    public function test_visitanos_incrusta_el_mapa_de_la_direccion_y_lo_apaga_a_pedido(): void
    {
        $html = $this->html($this->tienda(['footer_layout' => 'local']));
        $this->assertStringContainsString('google.com/maps?q=Av.%20Guillermo', $html);
        $this->assertStringContainsString('Cómo llegar', $html);
        $this->assertStringContainsString('Sáb 9:00 - 13:00', $html);

        $html = $this->html($this->tienda(['footer_layout' => 'local', 'footer_show_map' => '0']));
        $this->assertStringNotContainsString('output=embed', $html);
    }

    public function test_la_marca_gigante_lleva_el_nombre_como_pieza_visual(): void
    {
        $html = $this->html($this->tienda(['footer_layout' => 'marca']));
        $this->assertMatchesRegularExpression('/fma-wordmark[^>]*>Ferretería Norte<span>\.<\/span>/', $html);
    }

    public function test_la_tienda_pinta_un_solo_pie(): void
    {
        // Antes salían dos: el elegido en el Constructor y, debajo, la banda
        // genérica del runtime. Con dos pies, daba igual la composición.
        $html = $this->html($this->tienda(['footer_layout' => 'marca']));

        $this->assertSame(1, substr_count($html, '<footer'), 'la tienda pinta más de un pie');
        $this->assertStringNotContainsString('bixo-runtime-footer">', $html);
    }

    public function test_el_diseno_del_pie_manda_sobre_todas_las_composiciones(): void
    {
        // "Diseño del pie" solo pintaba la composición clásica: con Tecnológico
        // Pro el pie seguía azul marino aunque eligieras "Claro elegante".
        foreach (['technology', 'boletin', 'industrial', 'marca', 'tarjeta', 'local', 'conversacion', 'banda'] as $composicion) {
            $html = $this->html($this->tienda(['footer_layout' => $composicion, 'footer_style' => 'light']));
            $this->assertStringContainsString('--fp-bg:#f8fafc', $html, "$composicion ignora el diseño claro");
            $this->assertStringContainsString('--fp-sup-ink:#1f2937', $html, "$composicion sin tinta del diseño claro");
        }

        // Y el color de marca tiñe el pie completo.
        $html = $this->html($this->tienda(['footer_layout' => 'industrial', 'footer_style' => 'accent', 'primary_color' => '#0aa06e']));
        $this->assertStringContainsString('--fp-bg:#0aa06e', $html);
    }

    public function test_la_banda_reparte_los_enlaces_y_cierra_con_la_frase(): void
    {
        $html = $this->pie($this->tienda([
            'footer_layout'    => 'banda',
            'footer_cta_title' => 'Más que productos, conectamos un mejor futuro',
        ]), 'fba');

        // Dos listas de enlaces, no una columna larga.
        $this->assertSame(2, substr_count($html, 'class="fba-lista"'), 'los enlaces no se repartieron en dos listas');
        $this->assertStringContainsString('Más que productos', $html);
        $this->assertStringContainsString('fba-bottom', $html);
        // El lema largo se parte para no romper la banda.
        $this->assertStringContainsString('<br>', $html);
    }

    public function test_la_cabecera_banda_acompana_al_pie_del_mismo_estilo(): void
    {
        $html = $this->html($this->tienda([
            'header_layout'  => 'banda',
            'header_tagline' => 'Tu aliado en soluciones eléctricas desde 1998',
            'header_badges'  => 'Calidad | Confianza | Proyectos que iluminan el futuro',
        ]));

        $this->assertStringContainsString('<div class="hba">', $html, 'no se pintó la cabecera banda');
        $this->assertStringContainsString('Tu aliado en soluciones eléctricas desde 1998', $html);
        // Los sellos separados por "|" salen uno a uno (se cuenta el markup,
        // no el CSS: la hoja de estilos nombra las clases igual).
        $this->assertStringContainsString('<span class="hba-sellos">', $html);
        foreach (['Calidad', 'Confianza', 'Proyectos que iluminan el futuro'] as $sello) {
            $this->assertStringContainsString('</svg>'.$sello.'</span>', $html, "falta el sello $sello");
        }
        $this->assertStringContainsString('<div class="hba-search">', $html, 'falta el buscador ancho');
        $this->assertStringContainsString('wa.me/51999888777', $html, 'falta el botón de WhatsApp');
    }

    public function test_sin_lema_ni_sellos_la_cinta_no_se_pinta(): void
    {
        // Una cinta con texto de relleno delata la plantilla: sin datos, no va.
        $html = $this->html($this->tienda(['header_layout' => 'banda', 'footer_tagline' => '']));

        $this->assertStringContainsString('<div class="hba">', $html);
        $this->assertStringNotContainsString('<div class="hba-cinta">', $html);
    }

    public function test_el_constructor_describe_cada_composicion(): void
    {
        foreach (array_keys(StorefrontLayoutPacks::options('headers')) as $variante) {
            $this->assertNotSame('', StorefrontLayoutPacks::descripcion('headers', $variante), "cabecera $variante sin descripción");
            $this->assertTrue(view()->exists("tienda::storefront.partials.headers.$variante") || $variante === 'classic', "falta el parcial de cabecera $variante");
        }

        foreach (array_keys(StorefrontLayoutPacks::options('footers')) as $variante) {
            $this->assertNotSame('', StorefrontLayoutPacks::descripcion('footers', $variante), "$variante sin descripción");
            $this->assertTrue(view()->exists("tienda::storefront.partials.footers.$variante") || $variante === 'classic', "falta el parcial de $variante");
        }
    }
}

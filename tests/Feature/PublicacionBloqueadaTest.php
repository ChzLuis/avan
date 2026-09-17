<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Storefront\PublishChecklist;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Etapa 09 — qué impide publicar.
 *
 * El motor de reglas tenía el campo `blocks_publish` construido y **ninguna
 * regla lo usaba**: se podía publicar una tienda sin nombre, sin plantilla y
 * sin productos. Estas pruebas fijan el criterio acordado.
 *
 * Principio: bloquean los errores que dejarían la tienda inservible; el resto
 * son recomendaciones y NUNCA impiden publicar.
 */
class PublicacionBloqueadaTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(array $atributos = [], array $ajustes = []): Project
    {
        $p = Project::create(array_merge([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Publicable', 'slug' => 'pub-'.uniqid(), 'is_active' => true,
            'whatsapp' => '999111222',
        ], $atributos));
        foreach (array_merge(['catalog_template' => 'computienda'], $ajustes) as $k => $v) {
            $p->settings()->create(['key' => $k, 'value' => $v]);
        }
        StorefrontSections::ensure($p);

        return $p;
    }

    /** precio 0 = "sin precio": la columna es NOT NULL, el motor cuenta `<= 0`. */
    private function conProducto(Project $p, float $precio = 100): Project
    {
        $cat = Category::create(['project_id' => $p->id, 'name' => 'Cat']);
        Product::create(['project_id' => $p->id, 'category_id' => $cat->id,
            'name' => 'Producto', 'price' => $precio, 'is_available' => true]);

        return $p;
    }

    /** @return array<int,string> códigos que bloquean */
    private function bloqueos(Project $p): array
    {
        $r = PublishChecklist::for($p->fresh());

        return array_column(array_filter($r['critical'] ?? [], fn ($x) => $x['blocks_publish'] ?? false), 'code');
    }

    // ═══ BLOQUEAN ═══════════════════════════════════════════════════════════

    public function test_bloquea_sin_nombre_comercial(): void
    {
        $p = $this->conProducto($this->tienda(['name' => '']));

        $this->assertContains('business.name_missing', $this->bloqueos($p));
    }

    public function test_bloquea_sin_plantilla(): void
    {
        $p = $this->conProducto($this->tienda());
        $p->settings()->where('key', 'catalog_template')->delete();

        $this->assertContains('appearance.template_missing', $this->bloqueos($p));
    }

    public function test_bloquea_sin_productos(): void
    {
        $p = $this->tienda();

        $this->assertContains('catalog.no_products', $this->bloqueos($p));
    }

    /** Sin precio bloquea SOLO si la tienda vende en línea. */
    public function test_sin_precio_bloquea_en_venta_directa(): void
    {
        $p = $this->conProducto($this->tienda([], ['store_mode' => 'direct']), 0);

        $this->assertContains('catalog.products_without_price', $this->bloqueos($p));
    }

    /** WhatsApp bloquea si el modo comercial depende de él. */
    public function test_sin_whatsapp_bloquea_en_modo_cotizacion(): void
    {
        $p = $this->conProducto($this->tienda(['whatsapp' => ''], ['store_mode' => 'quote_only']));

        $this->assertContains('business.whatsapp_missing', $this->bloqueos($p));
    }

    /** La dirección bloquea si hay recojo en tienda. */
    public function test_sin_direccion_bloquea_con_recojo_en_tienda(): void
    {
        $p = $this->conProducto($this->tienda(['address' => ''], ['pickup_enabled' => '1']));

        $this->assertContains('business.address_missing', $this->bloqueos($p));
    }

    // ═══ NO BLOQUEAN ════════════════════════════════════════════════════════

    /** En cotización, un producto sin precio es normal: no puede bloquear. */
    public function test_sin_precio_no_bloquea_en_modo_cotizacion(): void
    {
        $p = $this->conProducto($this->tienda([], ['store_mode' => 'quote_only']), 0);

        $this->assertNotContains('catalog.products_without_price', $this->bloqueos($p));
    }

    /** Una tienda 100% online sin recojo no necesita dirección. */
    public function test_sin_direccion_no_bloquea_una_tienda_online(): void
    {
        $p = $this->conProducto($this->tienda(['address' => '']));

        $this->assertNotContains('business.address_missing', $this->bloqueos($p));
    }

    /** Logo, correo, imágenes y rubro son recomendaciones, nunca bloqueos. */
    public function test_las_recomendaciones_no_bloquean(): void
    {
        $p = $this->conProducto($this->tienda([], ['store_mode' => 'quote_only', 'payment_manual_enabled' => '1']));
        $bloqueos = $this->bloqueos($p);

        foreach (['business.logo_missing', 'business.email_missing', 'business.category_missing',
                  'catalog.products_without_image', 'home.too_few_sections', 'legal.pages_disabled'] as $codigo) {
            $this->assertNotContains($codigo, $bloqueos, "{$codigo} no debe impedir publicar.");
        }
    }

    /** Una tienda completa publica sin bloqueos. */
    public function test_una_tienda_completa_puede_publicar(): void
    {
        $p = $this->conProducto($this->tienda([
            'address' => 'Av. Principal 123',
        ], [
            'store_mode' => 'quote_only',
            'payment_manual_enabled' => '1',
        ]));

        $this->assertSame([], $this->bloqueos($p));
        $this->assertTrue(PublishChecklist::for($p->fresh())['can_publish']);
    }
}

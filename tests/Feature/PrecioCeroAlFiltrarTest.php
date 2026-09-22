<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "S/ 0.00" al filtrar en una tienda a cotización (2026-09-22).
 *
 * El usuario: "cuando filtro en la tienda de MegaHogar sale con precios
 * cuando filtro y en otras veces no".
 *
 * Causa: `$hidePrices` exigía que el ajuste `quote_price_display` valiera
 * 'hide', y caía al defecto 'show' cuando la clave NO EXISTÍA — el caso de
 * MegaHogar y de ELECTRO JARA. Pero el motor sí manda `price => null` en modo
 * cotización, así que la vista pintaba `number_format(null)` = "0.00" en todo
 * el catálogo. El defecto correcto es ocultar: publicar precios en una tienda
 * a cotización es una decisión que se toma a propósito.
 *
 * Hermano de PrecioNuloNoEsCeroTest, que cubre la rejilla de la portada.
 */
class PrecioCeroAlFiltrarTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(string $modo, ?string $display): Project
    {
        $user = User::factory()->create(['is_superadmin' => true]);
        $project = Project::create([
            'owner_id' => $user->id, 'name' => 'Tienda QA',
            'slug' => 'pc-'.uniqid(), 'is_active' => true,
        ]);
        // Igual que PrecioNuloNoEsCeroTest: los ajustes son filas, no un setter.
        $project->settings()->create(['key' => 'store_mode', 'value' => $modo]);
        $project->settings()->create(['key' => 'catalog_template', 'value' => 'computienda']);
        if ($display !== null) {
            $project->settings()->create(['key' => 'quote_price_display', 'value' => $display]);
        }

        Product::create([
            'project_id' => $project->id, 'name' => 'CAMAROTE METAL',
            'price' => 310, 'stock' => 5, 'is_available' => true,
        ]);

        return $project;
    }

    /**
     * El defecto de `quote_price_display` es OCULTAR.
     *
     * Es la regresión concreta: una tienda a cotización que nunca tocó el
     * ajuste mostraba S/ 0.00 en todo el catálogo.
     */
    public function test_sin_el_ajuste_una_tienda_a_cotizacion_oculta_precios(): void
    {
        $vista = file_get_contents(base_path('app/Modules/Tienda/Views/public/templates/computienda.blade.php'));

        $this->assertStringNotContainsString(
            "\$hidePrices = \$quoteMode && (\$settings['quote_price_display'] ?? 'show') === 'hide';",
            $vista,
            'el defecto NO puede ser mostrar: con la clave ausente pintaba S/ 0.00'
        );
        $this->assertStringContainsString(
            "\$settings['quote_price_display'] ?? 'hide'",
            $vista,
            'el defecto debe ser ocultar'
        );
    }

    /** Quien SÍ pidió publicar precios en cotización los sigue viendo. */
    public function test_con_el_ajuste_en_show_se_respetan(): void
    {
        $vista = file_get_contents(base_path('app/Modules/Tienda/Views/public/templates/computienda.blade.php'));

        // La condición debe dejar pasar 'show' explícito, no solo comparar con 'hide'.
        $this->assertStringContainsString("!== 'show'", $vista,
            "un 'show' explicito debe seguir mostrando los precios");
    }

    /** Una tienda en venta directa no se ve afectada por este cambio. */
    public function test_la_venta_directa_no_se_toca(): void
    {
        $vista = file_get_contents(base_path('app/Modules/Tienda/Views/public/templates/computienda.blade.php'));

        // $hidePrices solo puede activarse dentro de modo cotizacion.
        $this->assertMatchesRegularExpression(
            '/\$hidePrices\s*=\s*\$quoteMode\s*&&/',
            $vista,
            'ocultar precios exige estar en modo cotizacion'
        );
    }

    /** El motor nunca publica precios en cotización: eso no se negocia. */
    public function test_el_motor_sigue_mandando_null_en_cotizacion(): void
    {
        $project = $this->tienda('quote', null);
        $catalog = app(\App\Modules\Tienda\Storefront\CatalogQueryService::class);

        $producto = $project->products()->first();
        $card = $catalog->toCard($producto, $project->slug);

        /* El motor es aun mas estricto de lo que suponia: en cotizacion NO
           incluye la clave `price` (no la manda en null, la omite). Lo que
           importa para el bug es que jamas llegue un 0, que es lo que la
           vista convertia en "S/ 0.00". */
        $this->assertNotSame(0, $card['price'] ?? null, 'el precio nunca puede llegar como 0');
        $this->assertNotSame('0', $card['price'] ?? null);
        $this->assertNull($card['price'] ?? null,
            'en cotizacion el precio esta ausente o es null, nunca un importe');
    }
}

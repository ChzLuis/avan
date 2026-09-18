<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\Tienda\Storefront\ContactosTienda;
use App\Modules\Tienda\Support\StorefrontLayoutPacks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Más de dos números de contacto en la tienda (2026-09-07).
 *
 * Antes solo cabían un WhatsApp y un teléfono fijo. Un negocio con varias
 * líneas (ventas, soporte, un vendedor por zona) no tenía dónde ponerlas.
 */
class NumerosContactoTiendaTest extends TestCase
{
    use RefreshDatabase;

    private const EXTRA = '[{"t":"whatsapp","n":"987111222","l":"Ventas"},{"t":"telefono","n":"012345678","l":"Almacén"},{"t":"whatsapp","n":"987333444","l":"Soporte"}]';

    private function tienda(array $ajustes = []): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Mi Tienda', 'slug' => 'num-'.uniqid(), 'is_active' => true,
        ]);
        $base = [
            'storefront_structure_v2' => '1', 'catalog_template' => 'computienda',
            'whatsapp' => '987000111', 'contact_phone' => '(01) 700 8000',
            'contact_numbers' => self::EXTRA,
        ];
        foreach ($ajustes + $base as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => (string) $v]);
        }

        return $project;
    }

    private function portada(Project $p): string
    {
        return $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();
    }

    /** Los números extra salen en el pie, con su etiqueta y su enlace. */
    public function test_los_numeros_extra_se_pintan_en_el_pie(): void
    {
        $html = $this->portada($this->tienda(['footer_layout' => 'classic']));

        $this->assertStringContainsString('987111222', $html);
        $this->assertStringContainsString('Ventas', $html);
        $this->assertStringContainsString('987333444', $html);
        $this->assertStringContainsString('Soporte', $html);
        // El WhatsApp peruano de 9 digitos se manda con 51 o wa.me no abre.
        $this->assertStringContainsString('wa.me/51987111222', $html);
        $this->assertStringContainsString('tel:012345678', $html);
    }

    /** El teléfono fijo se enlaza para llamar, no como WhatsApp. */
    public function test_el_fijo_no_se_enlaza_como_whatsapp(): void
    {
        $html = $this->portada($this->tienda(['footer_layout' => 'classic']));
        $this->assertStringNotContainsString('wa.me/51012345678', $html);
    }

    /** Una tienda sin números extra queda exactamente como estaba. */
    public function test_sin_numeros_extra_nada_cambia(): void
    {
        $p = $this->tienda(['contact_numbers' => '']);
        $html = $this->portada($p);

        // El fijo de siempre sigue en su sitio y no aparece ningun extra.
        $this->assertStringContainsString('700 8000', $html, 'el telefono principal sigue');
        $this->assertStringNotContainsString('987111222', $html);
        $this->assertStringNotContainsString('Ventas', $html);
    }

    /** Los pies con lista de contacto muestran los extra. */
    public function test_los_pies_principales_muestran_los_extra(): void
    {
        $conLista = ['classic', 'commercial', 'complete', 'institutional', 'corporate', 'banda', 'boletin', 'industrial', 'local', 'marca', 'tarjeta', 'technology'];

        foreach ($conLista as $variante) {
            $this->assertArrayHasKey($variante, StorefrontLayoutPacks::options('footers'), "la variante '$variante' ya no existe");
            $html = $this->portada($this->tienda(['footer_layout' => $variante]));
            $this->assertStringContainsString('987111222', $html, "el pie '$variante' no muestra los números extra");
        }
    }

    /** Basura o estructuras inventadas no entran a la tienda. */
    public function test_no_se_cuela_basura(): void
    {
        $this->assertSame([], ContactosTienda::extra(['contact_numbers' => 'no soy json']));
        $this->assertSame([], ContactosTienda::extra(['contact_numbers' => '{"t":"x"}']));
        // Sin numero util, la fila se descarta.
        $this->assertSame([], ContactosTienda::extra(['contact_numbers' => '[{"t":"whatsapp","n":"   ","l":"Vacio"}]']));

        $limpio = ContactosTienda::extra(['contact_numbers' => '[{"t":"inventado","n":"987654321","l":"X"}]']);
        $this->assertSame('whatsapp', $limpio[0]['t'], 'un tipo desconocido cae a whatsapp, no se guarda tal cual');
    }

    /** Hay un tope: nadie llena el pie con cien números. */
    public function test_hay_un_maximo(): void
    {
        $muchos = json_encode(array_map(
            fn ($i) => ['t' => 'whatsapp', 'n' => '9870000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'l' => 'N'.$i],
            range(1, ContactosTienda::MAXIMO + 5)
        ));

        $this->assertCount(ContactosTienda::MAXIMO, ContactosTienda::extra(['contact_numbers' => $muchos]));
    }

    /** El mismo número escrito dos veces se muestra una sola. */
    public function test_no_se_repiten(): void
    {
        $p = $this->tienda(['contact_numbers' => '[{"t":"whatsapp","n":"987000111","l":"Otra vez"}]']);
        $todos = ContactosTienda::todos(
            $p->settings()->pluck('value', 'key')->all(),
            $p
        );

        $whatsapps = array_filter($todos, fn ($c) => $c['tipo'] === 'whatsapp');
        $this->assertCount(1, $whatsapps, 'el principal repetido no debe salir dos veces');
    }
}

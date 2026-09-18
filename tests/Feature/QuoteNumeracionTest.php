<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Modules\Ventas\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada negocio numera sus cotizaciones desde 1.
 *
 * La referencia era el `id` autoincremental, que es GLOBAL entre todos los
 * proyectos: la primera cotizacion de un cliente nuevo salia como "#187", y
 * ademas se pintaba en cinco formatos distintos segun la pantalla, asi que el
 * mismo documento tenia varios nombres.
 */
class QuoteNumeracionTest extends TestCase
{
    use RefreshDatabase;

    private function proyecto(string $slug): Project
    {
        return Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Negocio ' . $slug, 'slug' => $slug, 'category' => 'retail', 'is_active' => true,
        ]);
    }

    private function cotizar(Project $p): Quote
    {
        return Quote::create([
            'project_id' => $p->id, 'client_name' => 'Cliente', 'status' => 'draft',
            'total' => 100, 'token' => str()->random(24),
        ]);
    }

    public function test_cada_negocio_empieza_en_uno(): void
    {
        $a = $this->proyecto('num-a');
        $b = $this->proyecto('num-b');

        $a1 = $this->cotizar($a);
        $a2 = $this->cotizar($a);
        $b1 = $this->cotizar($b);   // negocio nuevo: su primera es la 1, no la 3

        $this->assertSame(1, $a1->correlativo);
        $this->assertSame(2, $a2->correlativo);
        $this->assertSame(1, $b1->correlativo);

        $this->assertSame('COT-00001', $a1->numero);
        $this->assertSame('COT-00002', $a2->numero);
        $this->assertSame('COT-00001', $b1->numero);
    }

    public function test_el_numero_se_asigna_venga_de_donde_venga(): void
    {
        // Sin pasar por ningun controlador: lo pone el modelo, porque hay seis
        // sitios distintos que crean cotizaciones.
        $p = $this->proyecto('num-c');
        $q = Quote::create(['project_id' => $p->id, 'client_name' => 'X',
            'status' => 'sent', 'total' => 10, 'token' => str()->random(24)]);

        $this->assertNotNull($q->numero);
        $this->assertSame('COT', $q->serie);
    }

    public function test_dos_negocios_pueden_tener_el_mismo_numero_pero_uno_no_lo_repite(): void
    {
        $a = $this->proyecto('num-d');
        $b = $this->proyecto('num-e');

        $this->assertSame($this->cotizar($a)->numero, $this->cotizar($b)->numero);

        // Repetirlo dentro del MISMO negocio lo impide el esquema.
        $this->expectException(\Illuminate\Database\QueryException::class);
        Quote::create(['project_id' => $a->id, 'client_name' => 'Y', 'status' => 'draft',
            'total' => 5, 'token' => str()->random(24), 'serie' => 'COT', 'correlativo' => 1]);
    }

    public function test_una_cotizacion_antigua_sin_numero_conserva_un_nombre(): void
    {
        $p = $this->proyecto('num-f');
        $q = $this->cotizar($p);
        // Simula una fila anterior a la numeracion.
        $q->forceFill(['numero' => null, 'correlativo' => null])->saveQuietly();

        $this->assertSame('COT-' . str_pad((string) $q->id, 5, '0', STR_PAD_LEFT), $q->fresh()->etiqueta);
    }
}

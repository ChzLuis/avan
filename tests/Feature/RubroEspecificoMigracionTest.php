<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Migración de rubro: el valor específico gana sobre el genérico `retail`,
 * pero SOLO si pertenece a la taxonomía válida de `StagePresets`.
 *
 * Lo que se protege: que la regla no degenere en "si es distinto de retail,
 * gana", y que reejecutar la migración no produzca cambios adicionales.
 */
class RubroEspecificoMigracionTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(?string $columna, ?string $ajuste): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'T'.uniqid(), 'slug' => 'r-'.uniqid(), 'is_active' => true,
            'category' => $columna,
        ]);
        if ($ajuste !== null) {
            $p->settings()->create(['key' => 'business_category', 'value' => $ajuste]);
        }

        return $p;
    }

    private function migrar(): void
    {
        (require database_path('migrations/2026_08_30_190000_rubro_especifico_gana_sobre_retail.php'))->up();
    }

    public function test_el_rubro_especifico_gana_sobre_retail(): void
    {
        $gabde = $this->tienda('retail', 'ferreteria');
        $tecsist = $this->tienda('retail', 'tecnologia');
        $mega = $this->tienda('retail', 'muebles');
        $baby = $this->tienda('retail', 'bebes');

        $this->migrar();

        $this->assertSame('ferreteria', $gabde->fresh()->category);
        $this->assertSame('tecnologia', $tecsist->fresh()->category);
        $this->assertSame('muebles', $mega->fresh()->category);
        $this->assertSame('bebes', $baby->fresh()->category);
    }

    /** La regla NO es "distinto de retail gana": debe estar en la taxonomía. */
    public function test_un_alternativo_fuera_de_la_taxonomia_no_gana(): void
    {
        $p = $this->tienda('retail', 'chatarreria-espacial');

        $this->migrar();

        $this->assertSame('retail', $p->fresh()->category, 'Solo gana lo que StagePresets soporta.');
    }

    /** Un rubro propio ya establecido nunca se pisa. */
    public function test_no_sobrescribe_un_rubro_ya_especifico(): void
    {
        $p = $this->tienda('muebles', 'ferreteria');

        $this->migrar();

        $this->assertSame('muebles', $p->fresh()->category, 'La columna ya tenía un rubro real.');
    }

    /** Sin alternativo no hay nada que migrar. */
    public function test_sin_alternativo_no_cambia_nada(): void
    {
        $p = $this->tienda('retail', null);

        $this->migrar();

        $this->assertSame('retail', $p->fresh()->category);
    }

    /** IDEMPOTENCIA: el segundo pase no produce ningún cambio. */
    public function test_reejecutar_la_migracion_no_cambia_nada_mas(): void
    {
        $this->tienda('retail', 'ferreteria');
        $this->tienda('retail', 'muebles');
        $this->tienda('muebles', 'ferreteria');
        $this->tienda('retail', 'no-existe');

        $this->migrar();
        $primera = DB::table('projects')->orderBy('id')->pluck('category', 'id')->all();

        $this->migrar();
        $segunda = DB::table('projects')->orderBy('id')->pluck('category', 'id')->all();

        $this->assertSame($primera, $segunda, 'El segundo pase debe ser inocuo.');
    }

    /** El ajuste antiguo no se borra: no se pierde información. */
    public function test_conserva_el_ajuste_antiguo(): void
    {
        $p = $this->tienda('retail', 'ferreteria');

        $this->migrar();

        $this->assertDatabaseHas('project_settings', [
            'project_id' => $p->id, 'key' => 'business_category', 'value' => 'ferreteria',
        ]);
    }
}

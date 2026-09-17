<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Storefront\BuilderDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Historial de publicaciones del Constructor (2026-09-07).
 *
 * `publish()` guardaba desde siempre un snapshot de lo que había ANTES de cada
 * publicación y `rollback()` sabía restaurarlo, pero nada estaba expuesto: el
 * comerciante que dejaba su tienda peor que antes no tenía cómo volver.
 */
class ConstructorHistorialTest extends TestCase
{
    use RefreshDatabase;

    private function tienda(): Project
    {
        $p = Project::create([
            'owner_id' => User::factory()->create(['is_superadmin' => true])->id,
            'name' => 'Mi Tienda', 'slug' => 'hist-'.uniqid(), 'is_active' => true,
        ]);
        foreach (['storefront_structure_v2' => '1', 'catalog_template' => 'computienda'] as $k => $v) {
            $p->settings()->create(['key' => $k, 'value' => $v]);
        }

        return $p;
    }

    /** Guarda un ajuste en borrador y lo publica, como haría el Constructor. */
    private function publicar(Project $p, string $clave, string $valor): int
    {
        $drafts = app(BuilderDraftService::class);
        $drafts->putSetting($p, $clave, $valor, $p->owner_id);

        return $drafts->publish($p, $p->owner_id, null);
    }

    public function test_el_historial_lista_las_versiones_con_autor_y_fecha(): void
    {
        $p = $this->tienda();
        $this->publicar($p, 'store_name_extra', 'uno');
        $this->publicar($p, 'store_name_extra', 'dos');

        $r = $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->getJson(route('settings.builder.versions'))->assertOk();

        $this->assertSame(2, $r->json('actual'));
        $this->assertCount(2, $r->json('versions'));
        // La mas reciente primero: es la que se mira al buscar "lo de recien".
        $this->assertSame(2, $r->json('versions.0.version'));
        $this->assertNotEmpty($r->json('versions.0.fecha'));
        $this->assertNotEmpty($r->json('versions.0.autor'));
    }

    /** Volver atrás deja la tienda como estaba antes de esa publicación. */
    public function test_restaurar_devuelve_el_valor_anterior(): void
    {
        $p = $this->tienda();
        $this->publicar($p, 'contact_address', 'Av. Primera 100');
        $v2 = $this->publicar($p, 'contact_address', 'Av. Segunda 200');

        $this->assertSame('Av. Segunda 200', $p->fresh()->setting('contact_address'));

        $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->postJson(route('settings.builder.rollback'), ['version' => $v2])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('Av. Primera 100', $p->fresh()->setting('contact_address'),
            'restaurar la version 2 devuelve lo que habia antes de publicarla');
    }

    /** Una versión inventada no rompe nada ni borra la tienda. */
    public function test_una_version_inexistente_se_rechaza(): void
    {
        $p = $this->tienda();
        $this->publicar($p, 'contact_address', 'Av. Única 1');

        $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->postJson(route('settings.builder.rollback'), ['version' => 999])
            ->assertStatus(404)->assertJson(['ok' => false]);

        $this->assertSame('Av. Única 1', $p->fresh()->setting('contact_address'));
    }

    /** El historial de un negocio no incluye publicaciones de otro. */
    public function test_no_se_cruzan_los_negocios(): void
    {
        $mio = $this->tienda();
        $ajeno = $this->tienda();
        $this->publicar($mio, 'contact_address', 'La mía');
        $this->publicar($ajeno, 'contact_address', 'La ajena');
        $this->publicar($ajeno, 'contact_address', 'La ajena 2');

        $r = $this->actingAs($mio->owner)->withSession(['active_project_id' => $mio->id])
            ->getJson(route('settings.builder.versions'))->assertOk();

        $this->assertCount(1, $r->json('versions'), 'solo debe ver sus propias publicaciones');
        $this->assertSame('La ajena 2', $ajeno->fresh()->setting('contact_address'), 'la tienda ajena no se toca');
    }

    /** Restaurar no destruye el historial: se puede volver a avanzar. */
    public function test_despues_de_restaurar_todavia_hay_historial(): void
    {
        $p = $this->tienda();
        $this->publicar($p, 'contact_address', 'Primera');
        $v2 = $this->publicar($p, 'contact_address', 'Segunda');

        $this->actingAs($p->owner)->withSession(['active_project_id' => $p->id])
            ->postJson(route('settings.builder.rollback'), ['version' => $v2])->assertOk();

        $this->assertSame(2, DB::table('store_publications')->where('project_id', $p->id)->count(),
            'las versiones publicadas siguen ahi despues de restaurar');
    }
}

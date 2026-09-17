<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\StorePage;
use App\Models\User;
use App\Storefront\BuilderDraftService;
use App\Storefront\StorePageWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ciclo Editar → Vista previa → Publicar para las páginas institucionales.
 *
 * Antes, `store_sections` tenía borrador y `store_pages` no: las secciones de
 * Inicio se acumulaban y las páginas —Nosotros, Contacto, Términos— salían a
 * producción en cuanto se guardaban. La etapa 09 prometía "publicar tienda"
 * cuando parte del contenido ya había salido sin pasar por ahí.
 */
class PaginasCicloBorradorTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private StorePageWriteService $paginas;
    private BuilderDraftService $drafts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Páginas', 'slug' => 'pag-'.uniqid(), 'is_active' => true,
        ]);
        $this->paginas = app(StorePageWriteService::class);
        $this->drafts = app(BuilderDraftService::class);
    }

    private function guardar(string $titulo, string $cuerpo = 'Texto'): StorePage
    {
        return $this->paginas->save($this->project, [
            'key' => 'nosotros', 'title' => $titulo, 'body' => $cuerpo, 'is_enabled' => true,
        ]);
    }

    private function pagina(): StorePage
    {
        return StorePage::where('project_id', $this->project->id)->where('key', 'nosotros')->first();
    }

    /** Una página nueva nace publicada: no hay nada anterior que pisar. */
    public function test_una_pagina_nueva_nace_publicada(): void
    {
        $this->guardar('Quiénes somos');

        $p = $this->pagina();
        $this->assertSame('Quiénes somos', $p->title);
        $this->assertNotNull($p->published_at);
    }

    /** Editar una página existente NO toca producción. */
    public function test_editar_no_publica(): void
    {
        $this->guardar('Título original');
        $this->guardar('Título nuevo sin publicar');

        $p = $this->pagina();
        $this->assertSame('Título original', $p->title, 'Producción intacta.');
        $this->assertSame('Título nuevo sin publicar', $p->draft_title);
        $this->assertTrue($p->has_draft);
    }

    /** La vista previa muestra el borrador; la tienda pública, lo publicado. */
    public function test_la_vista_previa_muestra_el_borrador(): void
    {
        $this->guardar('Título original');
        $this->guardar('Título nuevo sin publicar');

        $p = $this->pagina();
        $this->assertSame('Título nuevo sin publicar', $p->tituloEfectivo());
        $this->assertSame('Título original', $p->title);
    }

    /** Publicar promueve el borrador. */
    public function test_publicar_promueve_la_pagina(): void
    {
        $this->guardar('Título original');
        $this->guardar('Título nuevo');

        $this->drafts->publish($this->project);

        $p = $this->pagina();
        $this->assertSame('Título nuevo', $p->title);
        $this->assertFalse($p->has_draft);
        $this->assertNull($p->draft_title);
    }

    /** Descartar devuelve la página al último publicado. */
    public function test_descartar_vuelve_al_ultimo_publicado(): void
    {
        $this->guardar('Título original');
        $this->guardar('Título descartable');

        $this->pagina()->descartarBorrador();

        $p = $this->pagina();
        $this->assertSame('Título original', $p->title);
        $this->assertFalse($p->has_draft);
    }

    /** Deshacer la publicación restaura el contenido anterior. */
    public function test_el_rollback_restaura_la_pagina(): void
    {
        $this->guardar('Título original');
        $this->guardar('Título publicado');
        $version = $this->drafts->publish($this->project);

        $this->assertSame('Título publicado', $this->pagina()->title);

        $this->drafts->rollback($this->project->fresh(), $version);

        $this->assertSame('Título original', $this->pagina()->title);
    }

    /** Una página en borrador cuenta como cambio pendiente de publicar. */
    public function test_una_pagina_en_borrador_marca_cambios_pendientes(): void
    {
        $this->guardar('Título original');
        $this->drafts->publish($this->project);
        $this->assertFalse($this->drafts->hasDrafts($this->project->fresh()));

        $this->guardar('Otro título');

        $this->assertTrue($this->drafts->hasDrafts($this->project->fresh()),
            'El Constructor debe avisar de que hay una página sin publicar.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Storefront\BuilderDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ciclo borrador → publicar → cancelar → deshacer sobre los DATOS MAESTROS
 * (revisión 01: las columnas de `projects` son la fuente canónica).
 *
 * El defecto que cierran estas pruebas: `rollback()` restauraba ajustes y
 * secciones pero NO las columnas de `projects`, y el snapshot ni siquiera las
 * guardaba. Deshacer una publicación dejaba el dato nuevo en la columna y el
 * viejo en el ajuste — justo la divergencia que esta arquitectura elimina.
 */
class BuilderDatosMaestrosCicloTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private BuilderDraftService $drafts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Nombre Original', 'slug' => 'ciclo-maestros', 'is_active' => true,
            'phone' => '111111111', 'whatsapp' => '222222222',
            'address' => 'Dirección Original 100', 'category' => 'retail',
        ]);
        $this->drafts = app(BuilderDraftService::class);
    }

    private function frescos(): Project
    {
        return Project::find($this->project->id);
    }

    /** DRAFT — editar sin publicar no toca producción. */
    public function test_draft_no_toca_las_columnas_de_produccion(): void
    {
        $this->drafts->putSetting($this->project, 'business_name', 'Nombre Nuevo');
        $this->drafts->putSetting($this->project, 'contact_phone', '999999999');

        $p = $this->frescos();
        $this->assertSame('Nombre Original', $p->name, 'El borrador no publica.');
        $this->assertSame('111111111', $p->phone);
        $this->assertDatabaseMissing('project_settings', [
            'project_id' => $this->project->id, 'key' => 'business_name',
        ]);
    }

    /** PUBLICAR — las columnas canónicas reciben el valor nuevo. */
    public function test_publicar_promueve_a_las_columnas_canonicas(): void
    {
        $this->drafts->putSetting($this->project, 'business_name', 'Nombre Nuevo');
        $this->drafts->putSetting($this->project, 'contact_phone', '999999999');
        $this->drafts->putSetting($this->project, 'quote_whatsapp', '51 987 654 321');
        $this->drafts->publish($this->project);

        $p = $this->frescos();
        $this->assertSame('Nombre Nuevo', $p->name);
        $this->assertSame('999999999', $p->phone);
        $this->assertSame('51987654321', $p->whatsapp, 'El WhatsApp se guarda solo con dígitos.');
    }

    /** CANCELAR — descartar el borrador devuelve todo al último publicado. */
    public function test_cancelar_borrador_no_deja_columnas_modificadas(): void
    {
        $this->drafts->putSetting($this->project, 'business_name', 'Nombre Nuevo');
        $this->drafts->putSetting($this->project, 'contact_address', 'Otra Dirección 500');

        \DB::table('builder_drafts')->where('project_id', $this->project->id)->delete();

        $p = $this->frescos();
        $this->assertSame('Nombre Original', $p->name);
        $this->assertSame('Dirección Original 100', $p->address);
    }

    /** DESHACER — el rollback restaura las columnas, no solo los ajustes. */
    public function test_rollback_restaura_las_columnas_maestras(): void
    {
        $this->drafts->putSetting($this->project, 'business_name', 'Nombre Nuevo');
        $this->drafts->putSetting($this->project, 'contact_phone', '999999999');
        $this->drafts->putSetting($this->project, 'contact_address', 'Otra Dirección 500');
        $version = $this->drafts->publish($this->project);

        $this->assertSame('Nombre Nuevo', $this->frescos()->name, 'Publicó.');

        $this->drafts->rollback($this->frescos(), $version);

        $p = $this->frescos();
        $this->assertSame('Nombre Original', $p->name, 'El rollback devuelve el nombre.');
        $this->assertSame('111111111', $p->phone);
        $this->assertSame('Dirección Original 100', $p->address);
    }

    /** SEGUNDO BORRADOR — cancelar vuelve al último publicado, no al histórico. */
    public function test_segundo_borrador_cancelado_vuelve_al_ultimo_publicado(): void
    {
        $this->drafts->putSetting($this->project, 'business_name', 'Primera Publicación');
        $this->drafts->publish($this->project);

        $this->drafts->putSetting($this->frescos(), 'business_name', 'Segundo Borrador');
        \DB::table('builder_drafts')->where('project_id', $this->project->id)->delete();

        $this->assertSame('Primera Publicación', $this->frescos()->name,
            'Vuelve al último publicado, NO a "Nombre Original".');
    }

    /** PARCIAL — tocar un solo campo no altera ningún otro. */
    public function test_editar_un_campo_no_modifica_los_demas(): void
    {
        $this->drafts->putSetting($this->project, 'contact_phone', '999999999');
        $version = $this->drafts->publish($this->project);

        $p = $this->frescos();
        $this->assertSame('999999999', $p->phone);
        $this->assertSame('Nombre Original', $p->name, 'El nombre no se toca.');
        $this->assertSame('222222222', $p->whatsapp);
        $this->assertSame('Dirección Original 100', $p->address);

        $this->drafts->rollback($this->frescos(), $version);

        $p = $this->frescos();
        $this->assertSame('111111111', $p->phone, 'Solo se revierte lo publicado.');
        $this->assertSame('Nombre Original', $p->name);
        $this->assertSame('222222222', $p->whatsapp);
    }

    /** NULL — un valor originalmente vacío vuelve a NULL, no a cadena vacía. */
    public function test_un_valor_originalmente_nulo_vuelve_a_nulo(): void
    {
        $this->project->forceFill(['logo_url' => null])->save();

        $this->drafts->putSetting($this->frescos(), 'logo_url', 'logos/1/nuevo.png');
        $version = $this->drafts->publish($this->frescos());

        $this->assertSame('logos/1/nuevo.png', $this->frescos()->logo_url);

        $this->drafts->rollback($this->frescos(), $version);

        $this->assertNull($this->frescos()->logo_url, 'Vuelve a NULL, no a cadena vacía.');
    }

    /** Vaciar un ajuste no borra el dato maestro. */
    public function test_vaciar_el_ajuste_no_borra_el_dato_maestro(): void
    {
        $this->drafts->putSetting($this->project, 'contact_phone', '');
        $this->drafts->publish($this->project);

        $this->assertSame('111111111', $this->frescos()->phone,
            'Un ajuste vacío nunca borra la columna canónica.');
    }
}

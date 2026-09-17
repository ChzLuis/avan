<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\FlowEngine\PlantillaComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrato entre el motor y el constructor visual.
 *
 * Un bloque que el FlowRunner sabe ejecutar pero el editor no sabe dibujar es
 * una caja gris con "?" que el dueño no puede tocar. Estos tests lo impiden:
 * cada tipo que la plantilla genera tiene que estar en la paleta del editor y
 * tener su panel de edición.
 */
class BotEditorBloquesTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): string
    {
        return file_get_contents(resource_path('views/bot-flows/editor.blade.php'));
    }

    /** Tipos declarados en la paleta de la barra lateral. */
    private function tiposDeLaPaleta(): array
    {
        preg_match('/paleta:\[(.*?)\n    \],/s', $this->editor(), $m);
        preg_match_all("/\{tipo:'([a-z_]+)'/", $m[1] ?? '', $t);

        return $t[1];
    }

    private function tiposDeLaPlantilla(): array
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Editor', 'slug' => 'tienda-editor', 'is_active' => true,
        ]);

        $def = PlantillaComercial::definicion($project);

        return array_values(array_unique(array_column($def['bloques'], 'tipo')));
    }

    public function test_todo_bloque_de_la_plantilla_esta_en_la_paleta(): void
    {
        $faltan = array_diff($this->tiposDeLaPlantilla(), $this->tiposDeLaPaleta());

        $this->assertSame([], array_values($faltan),
            'Sin entrada en la paleta el bloque se dibuja como "?" y no se puede editar.');
    }

    public function test_cada_bloque_del_bot_comercial_tiene_panel_de_edicion(): void
    {
        $editor = $this->editor();

        foreach (['info_negocio', 'metodos_pago', 'promociones', 'consultar_producto',
                  'recomendar', 'faq', 'intencion'] as $tipo) {
            $this->assertStringContainsString("bloques[selected].tipo==='{$tipo}'", $editor,
                "El bloque {$tipo} no tiene panel: el dueño no puede configurarlo.");
        }
    }

    /**
     * Los campos que el FlowRunner LEE del bloque tienen que ser alcanzables
     * desde el editor; si no, son ajustes muertos que solo se tocan por SQL.
     */
    public function test_los_textos_configurables_son_editables(): void
    {
        $editor = $this->editor();

        $esperado = [
            'promociones'        => ['vacio', 'limite'],
            'consultar_producto' => ['texto', 'consulta'],
            'faq'                => ['vacio'],
            'intencion'          => ['texto', 'esperar'],
            'info_negocio'       => ['dato'],
        ];

        foreach ($esperado as $tipo => $campos) {
            foreach ($campos as $campo) {
                $this->assertStringContainsString("bloques[selected].{$campo}", $editor,
                    "El campo {$campo} de {$tipo} no se puede editar en el constructor.");
            }
        }
    }

    /** Un bloque recién arrastrado desde la paleta debe nacer funcionando. */
    public function test_los_bloques_nuevos_nacen_con_valores_por_defecto(): void
    {
        $editor = $this->editor();

        foreach (['info_negocio', 'promociones', 'consultar_producto', 'recomendar', 'faq', 'intencion'] as $tipo) {
            $this->assertMatchesRegularExpression("/if\(tipo==='{$tipo}'\)/", $editor,
                "El bloque {$tipo} nace vacío al arrastrarlo desde la paleta.");
        }
    }

    /** El editor abre y muestra los bloques del Bot Comercial. */
    public function test_el_editor_carga_un_bot_comercial(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'owner_id' => $user->id, 'name' => 'Tienda Editor 2',
            'slug' => 'tienda-editor-2', 'is_active' => true,
        ]);
        $flow = \App\Models\BotFlow::create([
            'project_id' => $project->id,
            'nombre'     => PlantillaComercial::NOMBRE,
            'activo'     => false,
            'definicion' => PlantillaComercial::definicion($project),
        ]);

        $this->actingAs($user)->withSession(['active_project_id' => $project->id])
            ->get(route('bot-flows.editor', $flow))
            ->assertOk()
            ->assertSee('Preguntas frecuentes')
            ->assertSee('Dato del negocio');
    }
}

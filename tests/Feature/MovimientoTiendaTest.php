<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El movimiento de la tienda (hover y entrada escalonada) se configura en el
 * Constructor y tiene que llegar de verdad a la tienda.
 *
 * Es el mismo fallo que tenía la analítica: un ajuste que el comerciante
 * activaba y que ninguna vista emitía. Aquí se fija que se emita, que NO se
 * emita cuando está apagado, y que se apague para quien pide reducir
 * movimiento.
 */
class MovimientoTiendaTest extends TestCase
{
    use RefreshDatabase;

    /** @param  array<string,string>  $ajustes */
    private function tienda(array $ajustes = []): Project
    {
        $project = Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => 'Tienda Movimiento', 'slug' => 'mov-'.uniqid(), 'is_active' => true,
        ]);
        foreach ($ajustes as $k => $v) {
            $project->settings()->create(['key' => $k, 'value' => $v]);
        }

        return $project;
    }

    private function html(Project $p): string
    {
        return $this->get(route('public.catalog', $p->slug))->assertOk()->getContent();
    }

    /** Con el efecto elegido, el CSS llega a la tienda. */
    public function test_el_hover_se_emite(): void
    {
        $html = $this->html($this->tienda(['hover_card_effect' => 'float']));

        $this->assertStringContainsString('.prod-card', $html, 'No se emite el motor de movimiento.');
        $this->assertStringContainsString('translateY(-6px)', $html, 'Falta el efecto flotar.');
        $this->assertStringContainsString('prefers-reduced-motion', $html,
            'El movimiento debe apagarse para quien lo pide.');
    }

    /** Sin configurar nada, la tienda no carga ni un byte de más. */
    public function test_apagado_no_emite_nada(): void
    {
        $html = $this->html($this->tienda());

        $this->assertStringNotContainsString('bx-entrada', $html);
        $this->assertStringNotContainsString('translateY(-6px)', $html);
    }

    /** El escalonado reparte el retraso entre las tarjetas, no todas a la vez. */
    public function test_el_escalonado_reparte_los_retrasos(): void
    {
        $html = $this->html($this->tienda(['anim_stagger_ms' => '100']));

        $this->assertStringContainsString('animation-delay: 0ms', $html);
        $this->assertStringContainsString('animation-delay: 100ms', $html);
        $this->assertStringContainsString('animation-delay: 200ms', $html);
        $this->assertStringContainsString('@keyframes bx-entrada', $html);
    }

    /** El zoom de la foto recorta en el contenedor: si no, se sale de la tarjeta. */
    public function test_el_zoom_recorta_en_el_marco(): void
    {
        $html = $this->html($this->tienda(['hover_image_zoom' => '1']));

        $this->assertStringContainsString('overflow: hidden', $html, 'Sin recorte la foto se desborda.');
        $this->assertStringContainsString('scale(1.07)', $html);
    }

    /** Un efecto que no está en el catálogo no se emite: nada de CSS libre. */
    public function test_un_efecto_desconocido_no_se_emite(): void
    {
        $html = $this->html($this->tienda(['hover_card_effect' => 'rotate3d(1,1,1,45deg);}body{display:none']));

        $this->assertStringNotContainsString('rotate3d', $html, 'Solo se emiten efectos del catálogo.');
        $this->assertStringNotContainsString('}body{', $html, 'Se escapó de su regla CSS.');
    }

    /** El control vive en 02 Apariencia, y en un solo sitio. */
    public function test_el_control_esta_en_apariencia(): void
    {
        $vista = app_path('Modules/Tienda/Views/settings/builder/stages/appearance.blade.php');
        $html = file_get_contents($vista);

        foreach (['hover_card_effect', 'hover_image_zoom', 'anim_stagger_ms'] as $clave) {
            $this->assertStringContainsString($clave, $html, "Sin editor para {$clave}.");
        }
    }
}

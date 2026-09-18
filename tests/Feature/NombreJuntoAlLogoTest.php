<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El nombre del negocio junto al logo.
 *
 * La regla por defecto se mantiene: donde va el logo NO se repite el nombre,
 * porque el logo ya lo dice y repetirlo ensucia la cabecera. Pero un logo que
 * es solo un símbolo —sin letras— deja la tienda sin nombre visible, y para
 * eso existe este interruptor.
 *
 * La capacidad estaba a medio construir: la clave `logo_wordmark` la leía UNA
 * de las cinco cabeceras y no tenía editor en ninguna parte, así que nadie
 * podía encenderla.
 */
class NombreJuntoAlLogoTest extends TestCase
{
    use RefreshDatabase;

    /** Las cinco cabeceras y el componente V2 respetan el interruptor. */
    public function test_todas_las_cabeceras_conocen_el_interruptor(): void
    {
        $mudas = [];

        $vistas = array_merge(
            glob(app_path('Modules/Tienda/Views/storefront/partials/headers/*.blade.php')),
            [app_path('Modules/Tienda/Views/components/storefront/header.blade.php')]
        );

        foreach ($vistas as $ruta) {
            if (! str_contains(file_get_contents($ruta), 'logo_wordmark')) {
                $mudas[] = basename($ruta, '.blade.php');
            }
        }

        $this->assertSame([], $mudas,
            'Estas cabeceras ignoran el interruptor: '.implode(', ', $mudas));
    }

    /** Y se puede encender desde el Constructor: si no, la clave está muerta. */
    public function test_el_interruptor_tiene_editor(): void
    {
        $editor = file_get_contents(app_path('Modules/Tienda/Views/settings/builder/stages/header.blade.php'));

        $this->assertStringContainsString("setSetting('logo_wordmark'", $editor,
            'Sin editor, la clave no se puede encender.');
        $this->assertStringContainsString("setSetting('logo_wordmark_text'", $editor,
            'Falta el texto personalizado.');
    }

    /** Apagado por defecto: la regla de no repetir el nombre se mantiene. */
    public function test_apagado_por_defecto(): void
    {
        foreach (glob(app_path('Modules/Tienda/Views/storefront/partials/headers/*.blade.php')) as $ruta) {
            $cuerpo = file_get_contents($ruta);
            if (! str_contains($cuerpo, 'logo_wordmark')) {
                continue;
            }
            $this->assertStringContainsString("logo_wordmark'] ?? '0'", $cuerpo,
                basename($ruta).' no cae a apagado cuando el negocio no lo configuró.');
        }
    }
}

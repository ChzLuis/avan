<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * El panel no usa los cuadros del navegador.
 *
 * `alert()`, `confirm()` y `prompt()` pintan un cuadro gris del sistema que
 * anuncia el dominio del servidor ("arindg.com dice:"), no se puede traducir,
 * no respeta la marca del negocio y bloquea la pestaña entera. Un cliente que
 * ve eso no está viendo su sistema: está viendo el navegador.
 *
 * En su lugar el panel expone `bxAviso()` y `bxConfirmar()` desde
 * `partials/avisos.blade.php`, que además devuelve una promesa (y texto, si el
 * diálogo lleva campo) para que la llamada se lea igual que antes.
 */
class SinDialogosDelNavegadorTest extends TestCase
{
    /**
     * Llamadas al diálogo nativo. Se excluye a propósito:
     *   - `window.__confirm(` y `.confirm(`, que son modales propios;
     *   - `confirmLabel`, `confirmar`, `confirmado`, que solo empiezan igual;
     *   - `confirm()` sin argumentos, que es el método de un componente Alpine
     *     (el nativo siempre lleva mensaje, así que no hay solape).
     */
    private const NATIVOS = '/(?<![.\w$])(?:window\.)?(alert|confirm|prompt)\s*\(\s*[^)\s]/';

    /** La partial de avisos es la única que puede nombrarlos, en su explicación. */
    private const EXENTAS = ['partials/avisos.blade.php'];

    public function test_ninguna_vista_usa_los_cuadros_del_navegador(): void
    {
        $encontrados = [];

        foreach ($this->vistas() as $ruta => $contenido) {
            if (\Illuminate\Support\Str::endsWith(str_replace('\\', '/', $ruta), self::EXENTAS)) {
                continue;
            }

            foreach (explode("\n", $contenido) as $n => $linea) {
                if (preg_match(self::NATIVOS, $linea, $m)) {
                    $rel = str_replace(str_replace('\\', '/', resource_path('views')).'/', '', str_replace('\\', '/', $ruta));
                    $encontrados[] = sprintf('%s:%d usa %s()', $rel, $n + 1, $m[1]);
                }
            }
        }

        $this->assertSame([], $encontrados, implode("\n", array_merge(
            ['Estas vistas abren un cuadro del navegador en lugar del popup del panel:'],
            $encontrados,
            ['', 'Usa bxAviso(mensaje, tipo) o await bxConfirmar({ descripcion, boton }).']
        )));
    }

    /** El diálogo solo existe si el layout lo incluye: sin esto, no hay popup. */
    public function test_los_dos_layouts_incluyen_el_dialogo(): void
    {
        foreach (['layouts/app.blade.php', 'comercial/layouts/app.blade.php'] as $layout) {
            $this->assertStringContainsString(
                "@include('partials.avisos')",
                file_get_contents(resource_path('views/'.$layout)),
                $layout.' no incluye el diálogo: sus pantallas se quedarían sin popup.'
            );
        }
    }

    /** Y expone las dos funciones globales con las que se le llama. */
    public function test_el_dialogo_expone_las_funciones_globales(): void
    {
        $partial = file_get_contents(resource_path('views/partials/avisos.blade.php'));

        $this->assertStringContainsString('window.bxAviso', $partial);
        $this->assertStringContainsString('window.bxConfirmar', $partial);
        $this->assertStringContainsString('data-bx-confirmar', $partial, 'falta el interceptor de formularios');
    }

    /** @return array<string,string> */
    private function vistas(): array
    {
        $fuera = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $archivo) {
            if ($archivo->isFile() && str_ends_with($archivo->getFilename(), '.blade.php')) {
                $fuera[$archivo->getPathname()] = file_get_contents($archivo->getPathname());
            }
        }

        return $fuera;
    }
}

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

    /**
     * Toda pantalla que llama al diálogo tiene que cargarlo.
     *
     * Sustituir un `alert()` por `bxAviso()` en una pantalla cuyo layout no
     * incluye la partial deja el botón muerto: la función no existe y el aviso
     * no aparece por ningún lado. Eso es peor que el cuadro gris, porque no se
     * nota hasta que un cliente reporta que "no pasa nada".
     *
     * Pasó de verdad con tres layouts: el del superadmin, el de Comunicaciones
     * y la barra de edición que el dueño ve dentro de su propia tienda.
     */
    public function test_toda_pantalla_que_llama_al_dialogo_lo_carga(): void
    {
        $sinDialogo = [];

        foreach ($this->vistas() as $ruta => $contenido) {
            $rel = $this->relativa($ruta);

            if ($rel === 'partials/avisos.blade.php' || ! $this->llamaAlDialogo($contenido)) {
                continue;
            }

            if (! $this->tieneDialogo($rel, [])) {
                $sinDialogo[] = $rel;
            }
        }

        $this->assertSame([], $sinDialogo, implode("\n", array_merge(
            ['Estas vistas llaman a bxAviso/bxConfirmar sin que su layout cargue el diálogo:'],
            $sinDialogo,
            ['', "Añade @include('partials.avisos') antes de </body> en el layout que las envuelve."]
        )));
    }

    /** Una vista tiene el diálogo si lo incluye, o si lo tiene quien la envuelve. */
    private function tieneDialogo(string $rel, array $visitadas): bool
    {
        if (isset($visitadas[$rel]) || ! is_file(resource_path('views/'.$rel))) {
            return false;   // ciclo, o un layout que no existe
        }

        $visitadas[$rel] = true;
        $s = file_get_contents(resource_path('views/'.$rel));

        if (str_contains($s, "@include('partials.avisos')")) {
            return true;
        }

        foreach ($this->envolturas($rel, $s) as $padre) {
            if ($this->tieneDialogo($padre, $visitadas)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Quién envuelve a esta vista: su layout, o —si es una parcial sin layout
     * propio— cualquier vista que la incluya.
     */
    private function envolturas(string $rel, string $s): array
    {
        $padres = [];

        // <x-app-layout>, <x-admin-layout>, <x-comercial-layout>...
        // Unos son componentes anónimos (components/*.blade.php) y otros son
        // clases en app/View/Components que apuntan a otra vista; se prueban
        // las tres formas y basta con que una resuelva.
        if (preg_match_all('/<x-([a-z0-9-]+)-layout/', $s, $m)) {
            foreach ($m[1] as $nombre) {
                $padres[] = 'components/'.$nombre.'-layout.blade.php';
                $padres[] = 'layouts/'.$nombre.'.blade.php';

                $clase = str_replace(' ', '', ucwords(str_replace('-', ' ', $nombre))).'Layout';
                $archivo = app_path('View/Components/'.$clase.'.php');
                if (is_file($archivo) && preg_match("/view\('([^']+)'\)/", file_get_contents($archivo), $v)) {
                    $padres[] = str_replace('.', '/', $v[1]).'.blade.php';
                }
            }
        }

        if (preg_match('/@extends\([\'"]([^\'"]+)[\'"]\)/', $s, $m)) {
            $padres[] = str_replace('.', '/', $m[1]).'.blade.php';
        }

        if ($padres !== []) {
            return $padres;
        }

        // Parcial: la envuelve quien la incluya.
        $nombreBlade = str_replace('/', '.', substr($rel, 0, -strlen('.blade.php')));
        foreach ($this->vistas() as $otra => $texto) {
            if (str_contains($texto, "'".$nombreBlade."'") || str_contains($texto, '"'.$nombreBlade.'"')) {
                $padres[] = $this->relativa($otra);
            }
        }

        return $padres;
    }

    private function llamaAlDialogo(string $s): bool
    {
        return (bool) preg_match('/\bbxAviso\s*\(|\bbxConfirmar\s*\(|data-bx-confirmar/', $s);
    }

    private function relativa(string $ruta): string
    {
        return str_replace(
            [str_replace('\\', '/', resource_path('views')).'/', '\\'],
            ['', '/'],
            str_replace('\\', '/', $ruta)
        );
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

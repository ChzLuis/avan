<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * El sorteo ("rifas") no es parte de BIXO: vive en otro proyecto. Decision de
 * producto del 2026-09-17: se retiro su codigo, la gestion "pedidos del bot"
 * que colgaba de el y los webhooks de Meta muertos que lo atendian.
 *
 * Este test deja la decision escrita en algo que se ejecuta: ni la palabra
 * ni las rutas ni los archivos vuelven a aparecer sin que alguien lo note.
 */
class SinSorteosTest extends TestCase
{
    public function test_los_archivos_del_sorteo_no_existen(): void
    {
        foreach ([
            'app/Http/Controllers/RifaController.php',
            'app/Http/Controllers/WaWebhookController.php',
            'app/Models/Rifa.php',
            'app/Models/RifaVenta.php',
            'resources/views/rifas',
            'resources/views/comercial/rifas.blade.php',
            'database/seeders/RifaFlowSeeder.php',
        ] as $ruta) {
            $this->assertFileDoesNotExist(base_path($ruta), "Volvio a aparecer {$ruta}: el sorteo no es parte de BIXO.");
        }
    }

    public function test_ninguna_ruta_es_de_sorteos_ni_de_los_webhooks_retirados(): void
    {
        $sospechosas = [];
        foreach (Route::getRoutes() as $ruta) {
            $texto = $ruta->uri() . ' ' . ($ruta->getName() ?? '') . ' ' . $ruta->getActionName();
            if (preg_match('#rifa|pedidos-bot|WaWebhookController|^wa/webhook|^whatsapp/webhook#i', $texto)) {
                $sospechosas[] = $ruta->methods()[0] . ' ' . $ruta->uri();
            }
        }

        $this->assertSame([], $sospechosas, "Rutas de sorteos o de los webhooks retirados:\n" . implode("\n", $sospechosas));
    }

    /**
     * La palabra no queda en codigo, rutas, vistas, tests ni seeders. Las
     * migraciones antiguas y los documentos de arquitectura son historia y se
     * excluyen a proposito.
     */
    public function test_la_palabra_no_queda_en_el_codigo(): void
    {
        $patron = '/\bRifa\w*\b|\brifas?\b|pedidos-bot|_usaRifas|WaWebhookController|\bwa\/webhook\b/';
        $infractores = [];
        foreach ([base_path('app'), base_path('routes'), base_path('resources/views'), base_path('tests'), base_path('database/seeders'), base_path('config')] as $raiz) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $archivo) {
                if ($archivo->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($archivo->getPathname(), strlen(base_path()) + 1));
                if ($rel === 'tests/Feature/SinSorteosTest.php') {
                    continue;
                }
                if (preg_match($patron, file_get_contents($archivo->getPathname()), $m)) {
                    $infractores[] = $rel . ' (' . $m[0] . ')';
                }
            }
        }

        $this->assertSame([], $infractores, "Aun mencionan el sorteo:\n" . implode("\n", $infractores));
    }
}

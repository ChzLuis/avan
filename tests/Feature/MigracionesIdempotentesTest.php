<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ARIN aplico varias migraciones con nombres distintos a los del repo: las
 * columnas `draft_*` de `store_sections` y su indice unico existen alli desde
 * 2026_07_29_*, mientras el repo las trae en 2026_07_24_*. Como la tabla
 * `migrations` guarda el NOMBRE DEL ARCHIVO, desplegar las del repo y correr
 * `migrate` en produccion las volveria a aplicar: "Duplicate column" y
 * "Duplicate key name".
 *
 * Este contrato exige que se puedan correr sobre un esquema que ya las tiene.
 */
class MigracionesIdempotentesTest extends TestCase
{
    use RefreshDatabase;

    private const ARCHIVOS = [
        '2026_07_24_000000_add_store_section_draft_visibility_and_schedule_fields.php',
        '2026_07_24_000001_add_store_sections_project_page_component_unique_index.php',
        // F2b: se crearan a mano en ARIN, asi que deben poder repetirse.
        '2026_08_16_180000_create_payments_table.php',
        '2026_08_16_180001_create_receivable_terms_table.php',
    ];

    public function test_se_pueden_repetir_sobre_un_esquema_que_ya_las_tiene(): void
    {
        // RefreshDatabase ya las aplico: el esquema esta en su estado final.
        $this->assertTrue(Schema::hasColumn('store_sections', 'draft_show_desktop'),
            'la migracion original debe haber creado la columna');

        foreach (self::ARCHIVOS as $archivo) {
            $migracion = require database_path('migrations/' . $archivo);
            $migracion->up();   // segunda pasada: no debe lanzar
        }

        // Y el esquema sigue intacto tras la repeticion.
        foreach (['draft_show_desktop', 'draft_show_tablet', 'draft_show_mobile',
                  'draft_publish_from', 'draft_publish_until'] as $col) {
            $this->assertTrue(Schema::hasColumn('store_sections', $col), "falta {$col}");
        }
        foreach (['payments', 'receivable_terms'] as $tabla) {
            $this->assertTrue(Schema::hasTable($tabla), "falta la tabla {$tabla}");
        }
    }
}

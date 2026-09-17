<?php

use App\Storefront\StagePresets;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rubro: el valor específico gana sobre el genérico `retail`.
 *
 * `projects.category` es la fuente canónica (revisión 01), pero contiene un
 * marcador genérico —`retail`— mientras el rubro real vive en el ajuste
 * `business_category`. Es el único caso de la auditoría en el que la copia
 * antigua es MÁS correcta que la fuente canónica.
 *
 * Condiciones que hacen segura esta migración:
 *   1. Solo actúa si la columna es genérica (`retail`, `otro`, vacía).
 *   2. Solo escribe si el alternativo pertenece a la taxonomía de StagePresets.
 *      NUNCA "si es distinto de retail, gana".
 *   3. Es idempotente: al segundo pase la columna ya no es genérica y no toca
 *      nada.
 *
 * Afecta a 5 tiendas: GABDE y Electro Jara → ferreteria, Tecsist → tecnologia,
 * MegaHogar → muebles, Baby Toncito → bebes.
 */
return new class extends Migration
{
    /** Valores que NO son un rubro real, sino relleno. */
    private const GENERICOS = ['retail', 'otro', ''];

    public function up(): void
    {
        $validos = array_keys(StagePresets::all());

        $filas = DB::table('projects as p')
            ->leftJoin('project_settings as s', function ($join) {
                $join->on('s.project_id', '=', 'p.id')->where('s.key', '=', 'business_category');
            })
            ->select('p.id', 'p.category', 's.value as alternativo')
            ->get();

        foreach ($filas as $fila) {
            $actual = trim((string) $fila->category);
            $alternativo = trim((string) $fila->alternativo);

            if (! in_array(mb_strtolower($actual), self::GENERICOS, true)) {
                continue;   // ya tiene un rubro propio: no se toca
            }
            if ($alternativo === '' || ! in_array($alternativo, $validos, true)) {
                continue;   // sin alternativo, o fuera de la taxonomía
            }

            DB::table('projects')->where('id', $fila->id)->update(['category' => $alternativo]);
        }
    }

    /**
     * No se revierte a `retail`: sería devolver un dato peor. La columna queda
     * con el rubro real y el ajuste `business_category` sigue intacto, así que
     * no se pierde información en ningún sentido.
     */
    public function down(): void
    {
    }
};

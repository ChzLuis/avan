<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Numero propio para las cotizaciones.
 *
 * Hasta ahora la referencia de una cotizacion era el `id` autoincremental, que
 * es GLOBAL entre todos los negocios: la primera cotizacion de un cliente
 * nuevo podia salir como "#187". Ademas se pintaba en cinco formatos distintos
 * (#123, #0123, COT-000123...) segun la pantalla, asi que el mismo documento
 * tenia varios nombres.
 *
 * Se copia el patron que ya usa Facturacion: serie + correlativo por proyecto,
 * con UNIQUE que impide dos numeros iguales en el mismo negocio.
 *
 * Aditiva y reversible: las columnas nacen nulas, se rellenan respetando el
 * orden de creacion y no se toca ningun dato existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'serie')) {
                $table->string('serie', 10)->default('COT')->after('project_id');
            }
            if (!Schema::hasColumn('quotes', 'correlativo')) {
                $table->unsignedInteger('correlativo')->nullable()->after('serie');
            }
            if (!Schema::hasColumn('quotes', 'numero')) {
                $table->string('numero', 20)->nullable()->after('correlativo');
            }
        });

        // Backfill: por proyecto y por orden de creacion, que es el orden en
        // que el negocio las emitio. Se hace en PHP y no en SQL para no
        // depender de variables de sesion de MySQL.
        foreach (DB::table('quotes')->distinct()->pluck('project_id') as $pid) {
            $n = 0;
            $filas = DB::table('quotes')->where('project_id', $pid)
                ->orderBy('id')->pluck('id');
            foreach ($filas as $id) {
                $n++;
                DB::table('quotes')->where('id', $id)->update([
                    'serie'       => 'COT',
                    'correlativo' => $n,
                    'numero'      => 'COT-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
                ]);
            }
        }

        Schema::table('quotes', function (Blueprint $table) {
            $table->unique(['project_id', 'serie', 'correlativo'], 'quotes_numero_unico');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique('quotes_numero_unico');
            $table->dropColumn(['serie', 'correlativo', 'numero']);
        });
    }
};

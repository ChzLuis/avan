<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Numero propio para los pedidos.
 *
 * Mismo problema que tenian las cotizaciones antes de numerarlas: la
 * referencia de un pedido era el `id` autoincremental, que es GLOBAL entre
 * todos los negocios. El primer pedido de un cliente nuevo podia salir como
 * "PED-187", y dos negocios distintos jamas veian una secuencia propia.
 *
 * Se aplica el mismo patron ya probado en `quotes`: serie + correlativo por
 * proyecto, con UNIQUE que impide dos numeros iguales dentro del negocio.
 *
 * Aditiva y reversible: las columnas nacen nulas, se rellenan respetando el
 * orden de creacion y no se altera ningun dato existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'serie')) {
                $table->string('serie', 10)->default('PED')->after('project_id');
            }
            if (! Schema::hasColumn('orders', 'correlativo')) {
                $table->unsignedInteger('correlativo')->nullable()->after('serie');
            }
            if (! Schema::hasColumn('orders', 'numero')) {
                $table->string('numero', 20)->nullable()->after('correlativo');
            }
        });

        // Backfill por proyecto y por orden de creacion, que es el orden en
        // que el negocio los emitio. En PHP y no en SQL para no depender de
        // variables de sesion de MySQL.
        foreach (DB::table('orders')->distinct()->pluck('project_id') as $pid) {
            $n = 0;
            $filas = DB::table('orders')->where('project_id', $pid)->orderBy('id')->pluck('id');
            foreach ($filas as $id) {
                $n++;
                DB::table('orders')->where('id', $id)->update([
                    'serie'       => 'PED',
                    'correlativo' => $n,
                    'numero'      => 'PED-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
                ]);
            }
        }

        // El UNIQUE es lo que convierte la numeracion en una garantia y no en
        // una costumbre: dos pedidos con el mismo numero dejan de ser posibles
        // aunque se creen a la vez. Se comprueba con la API del framework y
        // no con `SHOW INDEX`, que solo entiende MySQL (los tests corren sobre
        // SQLite y la migracion reventaba ahi).
        if (! self::tieneIndice('orders', 'orders_numero_unico')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['project_id', 'serie', 'correlativo'], 'orders_numero_unico');
            });
        }
    }

    private static function tieneIndice(string $tabla, string $nombre): bool
    {
        foreach (Schema::getIndexes($tabla) as $indice) {
            if (($indice['name'] ?? null) === $nombre) {
                return true;
            }
        }

        return false;
    }

    public function down(): void
    {
        if (self::tieneIndice('orders', 'orders_numero_unico')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropUnique('orders_numero_unico'));
        }
        Schema::table('orders', function (Blueprint $table) {
            foreach (['numero', 'correlativo', 'serie'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

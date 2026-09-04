<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los dos indicadores del traslado que la GRE declara y aquí no se podían
 * guardar.
 *
 * `vehiculo_m1l` es el que desbloquea el caso real del negocio: cuando la
 * carga va en un vehículo de categoría M1 (auto, camioneta) o L (moto,
 * mototaxi), SUNAT exime de declarar placa, conductor y licencia. Sin esta
 * columna la guía salía como transporte privado con el vehículo vacío, que es
 * justo lo que SUNAT rechaza.
 *
 * `transbordo_programado` viaja al lado porque es el otro indicador del mismo
 * bloque y el proveedor lo espera en el mismo payload.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guias_remision', function (Blueprint $tabla) {
            if (! Schema::hasColumn('guias_remision', 'vehiculo_m1l')) {
                $tabla->boolean('vehiculo_m1l')->default(false)->after('vehiculo_placa');
            }
            if (! Schema::hasColumn('guias_remision', 'transbordo_programado')) {
                $tabla->boolean('transbordo_programado')->default(false)->after('vehiculo_m1l');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guias_remision', function (Blueprint $tabla) {
            foreach (['vehiculo_m1l', 'transbordo_programado'] as $columna) {
                if (Schema::hasColumn('guias_remision', $columna)) {
                    $tabla->dropColumn($columna);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuelga cada ubicacion de una sede y le pone responsable.
 *
 * Sin esto, "A-01" era un estante suelto y no se sabia en que local estaba.
 * Con la sede aparece la jerarquia real de un negocio con mas de un punto:
 *
 *     Sede (Almacen Central, Tienda Los Olivos)
 *       └── Ubicacion (A-01 Estante de papa)
 *             └── Producto
 *
 * Y sobre todo permite distinguir el traslado que importa: mover algo DENTRO
 * de un local es acomodar; moverlo A OTRO local es un traslado entre
 * establecimientos de la misma empresa, que en Peru exige guia de remision
 * (motivo 04 de SUNAT). Sin saber la sede, el sistema no puede avisarlo.
 *
 * `sede_id` queda opcional: los negocios de un solo local no tienen por que
 * crear una sede para poder usar ubicaciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->foreignId('sede_id')->nullable()->after('project_id')
                ->constrained('sedes')->nullOnDelete();
            // Quien responde por ese sitio. La sede ya tiene su `manager`;
            // esto es para el encargado del pasillo o del deposito concreto,
            // que no siempre es el jefe del local.
            $table->string('responsable', 120)->nullable()->after('zona');
        });

        Schema::table('location_transfers', function (Blueprint $table) {
            // Se congela si el traslado cruzo sedes. Se guarda en el momento
            // porque una ubicacion puede cambiar de sede despues, y entonces
            // el historial mentiria sobre lo que paso aquel dia.
            $table->boolean('entre_sedes')->default(false)->after('cantidad');
            // Guia de remision que lo respalda, cuando la hay.
            $table->string('guia_referencia', 40)->nullable()->after('entre_sedes');
        });
    }

    public function down(): void
    {
        Schema::table('location_transfers', function (Blueprint $table) {
            $table->dropColumn(['entre_sedes', 'guia_referencia']);
        });

        Schema::table('warehouse_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sede_id');
            $table->dropColumn('responsable');
        });
    }
};

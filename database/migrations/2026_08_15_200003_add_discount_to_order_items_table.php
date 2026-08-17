<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F1b-M2b — El descuento viaja CON la linea al convertir, no fundido en el
 * precio: round(unitario_neto,2)*qty produce descuadres (33.33x3 al 10% da
 * 90.00 en vez de 89.99). Con price+discount persistidos, LineMath calcula
 * el total exacto en todos los consumidores.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            $t->decimal('discount', 5, 2)->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $t) {
            $t->dropColumn('discount');
        });
    }
};

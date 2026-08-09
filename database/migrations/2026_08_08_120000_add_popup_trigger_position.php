<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Popups avanzados: disparo (espera/salida) y posición. Idempotente. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_popups', function (Blueprint $table) {
            if (!Schema::hasColumn('store_popups', 'trigger')) {
                $table->string('trigger', 20)->default('delay');      // delay | exit
            }
            if (!Schema::hasColumn('store_popups', 'position')) {
                $table->string('position', 20)->default('center');    // center | bottom_right
            }
        });
    }

    public function down(): void
    {
        // Sin destrucción en producción.
    }
};

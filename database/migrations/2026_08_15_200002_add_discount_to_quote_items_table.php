<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** F1b-M2 — Descuento porcentual por linea (0..100). La UI lo capturaba y se perdia. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $t) {
            $t->decimal('discount', 5, 2)->default(0)->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $t) {
            $t->dropColumn('discount');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Campos que la extensión gestiona: etiquetas y responsable de la venta. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $t) {
            if (!Schema::hasColumn('clients', 'etiquetas')) {
                $t->json('etiquetas')->nullable()->after('etapa');
            }
            if (!Schema::hasColumn('clients', 'responsable')) {
                $t->string('responsable')->nullable()->after('etiquetas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $t) {
            $t->dropColumn(['etiquetas', 'responsable']);
        });
    }
};

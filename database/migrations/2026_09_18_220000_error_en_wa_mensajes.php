<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Motivo del fallo de un mensaje saliente (lo dice Meta en el acuse `failed`). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_mensajes', function (Blueprint $table) {
            $table->string('error', 255)->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('wa_mensajes', function (Blueprint $table) {
            $table->dropColumn('error');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ID de la cuenta de WhatsApp Business (WABA): hace falta para leer plantillas y suscribir la app. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_canales', function (Blueprint $table) {
            $table->string('waba_id', 40)->nullable()->after('phone_number_id');
        });
    }

    public function down(): void
    {
        Schema::table('wa_canales', function (Blueprint $table) {
            $table->dropColumn('waba_id');
        });
    }
};

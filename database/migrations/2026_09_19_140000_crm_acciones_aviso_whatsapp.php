<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aviso previo por WhatsApp de una accion: a que numero avisar, cuantos minutos antes
 * y si ya se mando (para no repetirlo). El push al vencer sigue igual (`recordada_at`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_acciones', function (Blueprint $table) {
            $table->string('avisar_whatsapp', 20)->nullable()->after('recordada_at');
            $table->unsignedSmallInteger('avisar_minutos')->default(10)->after('avisar_whatsapp');
            $table->timestamp('avisada_at')->nullable()->after('avisar_minutos');
        });
    }

    public function down(): void
    {
        Schema::table('crm_acciones', function (Blueprint $table) {
            $table->dropColumn(['avisar_whatsapp', 'avisar_minutos', 'avisada_at']);
        });
    }
};

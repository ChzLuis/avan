<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Control de licencias: nombradas y concurrentes.
 *
 *  app_settings        clave/valor global. No existía ninguno: AdminSettings
 *                      escribía en config() en memoria, así que cualquier ajuste
 *                      global se perdía en la siguiente petición.
 *  users.license_type  'nombrada'   -> asiento reservado para esa persona.
 *                      'concurrente'-> consume un asiento del pool mientras esté
 *                                      conectada y lo libera al salir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'license_type')) {
                $table->string('license_type', 20)->default('concurrente');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'license_type')) {
                $table->dropColumn('license_type');
            }
        });
    }
};

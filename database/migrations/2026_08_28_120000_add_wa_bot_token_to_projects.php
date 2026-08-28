<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Secreto de bot de WhatsApp POR PROYECTO (cierre de RISK-008).
 *
 * Hasta ahora las rutas /wa/* se autenticaban con un ÚNICO token global
 * hardcodeado en el repo. El tenant no se derivaba de ningún secreto, así que
 * con ese token se podían mutar pedidos de cualquier negocio (IDOR).
 *
 * Mismo patrón que `copilot_token`: cada proyecto tiene su propio token; el
 * webhook resuelve el tenant DESDE el token (no desde un id manipulable) y
 * valida que la orden pertenece a ese tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'wa_bot_token')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('wa_bot_token', 64)->nullable()->unique()->after('wa_phone');
            });
        }

        // Backfill: cada proyecto existente recibe su propio secreto, para que
        // su conector deje de usar el token global compartido.
        Project::whereNull('wa_bot_token')->get()->each(function (Project $p) {
            $p->forceFill(['wa_bot_token' => 'wabot_'.Str::random(48)])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('wa_bot_token');
        });
    }
};

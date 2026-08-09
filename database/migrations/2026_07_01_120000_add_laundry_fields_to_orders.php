<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos específicos de lavandería en orders.
 * No pisa los campos de restaurante (kitchen_status); usa laundry_status aparte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'tag_code')) {
                $table->string('tag_code', 20)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('orders', 'pieces_count')) {
                $table->unsignedInteger('pieces_count')->nullable()->after('tag_code');
            }
            if (!Schema::hasColumn('orders', 'laundry_status')) {
                // recibido | lavando | planchando | listo | entregado
                $table->string('laundry_status', 20)->nullable()->after('pieces_count');
            }
            if (!Schema::hasColumn('orders', 'ready_notified_at')) {
                $table->timestamp('ready_notified_at')->nullable()->after('laundry_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['tag_code', 'pieces_count', 'laundry_status', 'ready_notified_at'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite que un combo mezcle productos Y servicios.
     * item_type: 'product' | 'service' | 'custom'
     */
    public function up(): void
    {
        Schema::table('combo_items', function (Blueprint $table) {
            if (!Schema::hasColumn('combo_items', 'service_id')) {
                $table->foreignId('service_id')->nullable()->after('product_id')
                      ->constrained('services')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('combo_items', function (Blueprint $table) {
            if (Schema::hasColumn('combo_items', 'service_id')) {
                $table->dropConstrainedForeignId('service_id');
            }
        });
    }
};

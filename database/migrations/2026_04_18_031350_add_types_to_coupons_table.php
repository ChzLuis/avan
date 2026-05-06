<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE coupons MODIFY COLUMN type ENUM('percent','fixed','free_shipping','first_purchase') NOT NULL DEFAULT 'percent'");
        }

        if (!Schema::hasColumn('coupons', 'first_purchase_only')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->boolean('first_purchase_only')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('first_purchase_only');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE coupons MODIFY COLUMN type ENUM('percent','fixed') NOT NULL DEFAULT 'percent'");
        }
    }
};

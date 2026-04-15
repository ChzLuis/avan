<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('products', 'wholesale_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('wholesale_unit', 30)->nullable()->after('wholesale_min_qty');
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('products', 'wholesale_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('wholesale_unit');
            });
        }
    }
};

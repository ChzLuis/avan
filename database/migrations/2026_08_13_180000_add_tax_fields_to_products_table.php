<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'has_tax')) $table->boolean('has_tax')->default(false)->after('cost');
            if (!Schema::hasColumn('products', 'tax_rate')) $table->decimal('tax_rate', 5, 2)->default(18)->after('has_tax');
        });
    }
    public function down(): void {
        Schema::table('products', function (Blueprint $table) {
            $cols = array_filter(['has_tax','tax_rate'], fn($c) => Schema::hasColumn('products', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};

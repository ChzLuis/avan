<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'wa_number')) $table->string('wa_number', 30)->nullable()->after('sales_channel');
            if (!Schema::hasColumn('orders', 'wa_status')) $table->string('wa_status', 30)->nullable()->after('wa_number');
        });
    }
    public function down(): void {
        Schema::table('orders', function (Blueprint $table) {
            $cols = array_filter(['wa_number','wa_status'], fn($c) => Schema::hasColumn('orders', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};

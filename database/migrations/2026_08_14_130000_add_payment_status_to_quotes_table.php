<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'payment_status')) $table->string('payment_status', 20)->default('pendiente')->after('status');
            if (!Schema::hasColumn('quotes', 'paid_amount'))    $table->decimal('paid_amount', 12, 2)->nullable()->after('payment_status');
            if (!Schema::hasColumn('quotes', 'paid_at'))        $table->timestamp('paid_at')->nullable()->after('paid_amount');
        });
    }
    public function down(): void {
        Schema::table('quotes', function (Blueprint $table) {
            $cols = array_filter(['payment_status','paid_amount','paid_at'], fn($c) => Schema::hasColumn('quotes', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};

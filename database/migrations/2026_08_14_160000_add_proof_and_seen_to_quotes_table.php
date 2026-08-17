<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'payment_proof_url'))    $table->string('payment_proof_url', 500)->nullable()->after('paid_at');
            if (!Schema::hasColumn('quotes', 'payment_proof_at'))     $table->timestamp('payment_proof_at')->nullable()->after('payment_proof_url');
            if (!Schema::hasColumn('quotes', 'rejected_at'))          $table->timestamp('rejected_at')->nullable()->after('sent_at');
            if (!Schema::hasColumn('quotes', 'reject_reason'))        $table->string('reject_reason', 300)->nullable()->after('rejected_at');
            if (!Schema::hasColumn('quotes', 'seen_at'))              $table->timestamp('seen_at')->nullable()->after('reject_reason');
        });
    }
    public function down(): void {
        Schema::table('quotes', function (Blueprint $table) {
            $cols = array_filter(
                ['payment_proof_url','payment_proof_at','rejected_at','reject_reason','seen_at'],
                fn($c) => Schema::hasColumn('quotes', $c)
            );
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};

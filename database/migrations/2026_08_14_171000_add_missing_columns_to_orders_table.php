<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Estas columnas ya existían en producción (ARIN); esta migración solo pone
 *  al día la copia local, que se había quedado atrás. */
return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'document_status'))  $table->string('document_status', 20)->nullable()->after('status');
            if (!Schema::hasColumn('orders', 'document_type'))    $table->string('document_type', 20)->nullable()->after('document_status');
            if (!Schema::hasColumn('orders', 'document_number'))  $table->string('document_number', 30)->nullable()->after('document_type');
            if (!Schema::hasColumn('orders', 'delivery_type'))    $table->string('delivery_type', 20)->nullable()->after('delivery_address');
            if (!Schema::hasColumn('orders', 'promised_at'))      $table->timestamp('promised_at')->nullable()->after('delivery_type');
            if (!Schema::hasColumn('orders', 'advance_amount'))   $table->decimal('advance_amount', 10, 2)->nullable()->after('promised_at');
            if (!Schema::hasColumn('orders', 'payment_condition'))$table->string('payment_condition', 80)->nullable()->after('payment_method');
        });
    }
    public function down(): void {
        Schema::table('orders', function (Blueprint $table) {
            $cols = array_filter(
                ['document_status','document_type','document_number','delivery_type','promised_at','advance_amount','payment_condition'],
                fn($c) => Schema::hasColumn('orders', $c)
            );
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'client_type')) $table->string('client_type', 80)->nullable()->after('notes');
            if (!Schema::hasColumn('clients', 'lead_source')) $table->string('lead_source', 80)->nullable()->after('client_type');
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_method')) $table->string('payment_method', 80)->nullable()->after('notes');
            if (!Schema::hasColumn('orders', 'payment_condition')) $table->string('payment_condition', 80)->nullable()->after('payment_method');
            if (!Schema::hasColumn('orders', 'sales_channel')) $table->string('sales_channel', 80)->nullable()->after('payment_condition');
        });

        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'payment_method')) $table->string('payment_method', 80)->nullable()->after('notes');
            if (!Schema::hasColumn('quotes', 'payment_condition')) $table->string('payment_condition', 80)->nullable()->after('payment_method');
        });

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'appointment_type')) $table->string('appointment_type', 80)->nullable()->after('notes');
                if (!Schema::hasColumn('appointments', 'priority')) $table->string('priority', 30)->nullable()->after('appointment_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $cols = array_filter(['client_type','lead_source'], fn($c) => Schema::hasColumn('clients', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
        Schema::table('orders', function (Blueprint $table) {
            $cols = array_filter(['payment_method','payment_condition','sales_channel'], fn($c) => Schema::hasColumn('orders', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
        Schema::table('quotes', function (Blueprint $table) {
            $cols = array_filter(['payment_method','payment_condition'], fn($c) => Schema::hasColumn('quotes', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $cols = array_filter(['appointment_type','priority'], fn($c) => Schema::hasColumn('appointments', $c));
                if ($cols) $table->dropColumn(array_values($cols));
            });
        }
    }
};

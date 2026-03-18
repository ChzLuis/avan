<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_type', 80)->nullable()->after('notes');
            $table->string('lead_source', 80)->nullable()->after('client_type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 80)->nullable()->after('notes');
            $table->string('payment_condition', 80)->nullable()->after('payment_method');
            $table->string('sales_channel', 80)->nullable()->after('payment_condition');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('payment_method', 80)->nullable()->after('notes');
            $table->string('payment_condition', 80)->nullable()->after('payment_method');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('appointment_type', 80)->nullable()->after('notes');
            $table->string('priority', 30)->nullable()->after('appointment_type');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['client_type', 'lead_source']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_condition', 'sales_channel']);
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_condition']);
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['appointment_type', 'priority']);
        });
    }
};

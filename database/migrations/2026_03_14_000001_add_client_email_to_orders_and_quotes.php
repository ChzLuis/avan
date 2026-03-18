<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_email')->nullable()->after('client_phone');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('client_email')->nullable()->after('client_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('client_email');
        });
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('client_email');
        });
    }
};

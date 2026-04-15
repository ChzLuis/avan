<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'token')) $table->string('token', 64)->nullable()->unique()->after('id');
            if (!Schema::hasColumn('quotes', 'sent_at')) $table->timestamp('sent_at')->nullable()->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $cols = array_filter(['token','sent_at'], fn($c) => Schema::hasColumn('quotes', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'client_doc_type')) $table->string('client_doc_type')->nullable()->after('client_phone');
            if (!Schema::hasColumn('quotes', 'client_doc_number')) $table->string('client_doc_number', 20)->nullable()->after('client_doc_type');
            if (!Schema::hasColumn('quotes', 'client_address')) $table->string('client_address')->nullable()->after('client_doc_number');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $cols = array_filter(['client_doc_type','client_doc_number','client_address'], fn($c) => Schema::hasColumn('quotes', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};

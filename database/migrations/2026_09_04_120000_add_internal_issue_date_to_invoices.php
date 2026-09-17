<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'internal_issue_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->date('internal_issue_date')->nullable()->after('issue_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'internal_issue_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('internal_issue_date');
            });
        }
    }
};

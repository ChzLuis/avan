<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_sections', function (Blueprint $table) {
            $table->json('draft_content')->nullable()->after('content');
            $table->string('draft_variant', 60)->nullable()->after('variant');
            $table->boolean('draft_is_enabled')->nullable()->after('is_enabled');
            $table->unsignedInteger('draft_sort_order')->nullable()->after('sort_order');
            $table->boolean('has_draft')->default(false)->after('draft_sort_order');
            $table->timestamp('published_at')->nullable()->after('publish_until');
        });

        DB::table('store_sections')->update(['published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('store_sections', function (Blueprint $table) {
            $table->dropColumn(['draft_content', 'draft_variant', 'draft_is_enabled', 'draft_sort_order', 'has_draft', 'published_at']);
        });
    }
};

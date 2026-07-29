<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('store_sections')
            ->where('page', 'home')
            ->whereIn('component', [
                'benefits', 'announcements', 'featured_categories', 'daily_offer',
                'discounts', 'featured_products', 'blog',
            ])
            ->where('has_draft', false)
            ->whereColumn('created_at', 'updated_at')
            ->update(['is_enabled' => false]);
    }

    public function down(): void
    {
        // No se reactivan bloques automáticamente para no alterar decisiones posteriores del usuario.
    }
};

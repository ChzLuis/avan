<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $values = [
            'name' => 'Bots de WhatsApp',
            'description' => 'Conexion de WhatsApp, codigo QR y constructor de bots.',
            'icon' => '',
            'route' => 'bot-flows.index',
            'sort_order' => 24,
            'is_active' => true,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('modules', 'category')) {
            $values['category'] = 'clientes';
        }

        DB::table('modules')->updateOrInsert(['key' => 'bots'], $values);
    }

    public function down(): void
    {
        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('key', 'bots')->delete();
        }
    }
};

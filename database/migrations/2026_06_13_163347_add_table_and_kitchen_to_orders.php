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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('table_number')->nullable()->after('delivery_address'); // número/nombre de mesa
            $table->string('order_type')->default('delivery')->after('table_number'); // delivery, mesa, llevar
            $table->string('kitchen_status')->default('pending')->after('order_type'); // pending, cooking, ready, served
            $table->timestamp('kitchen_at')->nullable()->after('kitchen_status'); // cuando pasó a cocina
            $table->timestamp('ready_at')->nullable()->after('kitchen_at');       // cuando estuvo listo
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['table_number','order_type','kitchen_status','kitchen_at','ready_at']);
        });
    }
};

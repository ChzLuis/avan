<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rifas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio_ticket', 8, 2)->default(10.00);
            $table->integer('min_tickets')->default(1);
            $table->integer('max_tickets')->nullable();
            $table->string('imagen_url')->nullable();   // URL pública o storage
            $table->string('premio')->nullable();        // Descripción del premio
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Agregar rifa_id a rifa_ventas para vincularla
        Schema::table('rifa_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('rifa_id')->nullable()->after('project_id');
            $table->string('order_number', 20)->nullable()->after('rifa_id');
            $table->json('ticket_numbers')->nullable()->after('ticket_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rifas');
        Schema::table('rifa_ventas', function (Blueprint $table) {
            $table->dropColumn(['rifa_id', 'order_number', 'ticket_numbers']);
        });
    }
};

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
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('client_phone', 30)->nullable();
            $table->string('client_email', 150)->nullable();
            $table->string('client_name', 100)->nullable();
            $table->json('items');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->string('coupon_code', 50)->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'reminder_sent', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};

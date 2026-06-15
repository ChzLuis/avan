<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. RESERVAS: extender appointments ────────────────────────────────
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'table_number'))
                $table->string('table_number')->nullable()->after('notes');
            if (!Schema::hasColumn('appointments', 'zone'))
                $table->string('zone')->nullable()->after('table_number');      // Salón, Terraza, etc.
            if (!Schema::hasColumn('appointments', 'guests'))
                $table->unsignedTinyInteger('guests')->default(1)->after('zone');
            if (!Schema::hasColumn('appointments', 'occasion'))
                $table->string('occasion')->nullable()->after('guests');        // Cumpleaños, Aniversario...
            if (!Schema::hasColumn('appointments', 'source'))
                $table->string('source')->default('manual')->after('occasion'); // manual, web, whatsapp, qr
            if (!Schema::hasColumn('appointments', 'reminder_sent'))
                $table->boolean('reminder_sent')->default(false)->after('source');
            if (!Schema::hasColumn('appointments', 'confirmed_at'))
                $table->timestamp('confirmed_at')->nullable()->after('reminder_sent');
            if (!Schema::hasColumn('appointments', 'arrived_at'))
                $table->timestamp('arrived_at')->nullable()->after('confirmed_at');
        });

        // ── 2. DELIVERY: extender orders ──────────────────────────────────────
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'delivery_status'))
                $table->string('delivery_status')->nullable()->after('kitchen_status');
                // assigned | in_route | delivered | rejected
            if (!Schema::hasColumn('orders', 'delivery_person_id'))
                $table->unsignedBigInteger('delivery_person_id')->nullable()->after('delivery_status');
            if (!Schema::hasColumn('orders', 'delivery_person_name'))
                $table->string('delivery_person_name')->nullable()->after('delivery_person_id');
            if (!Schema::hasColumn('orders', 'delivery_assigned_at'))
                $table->timestamp('delivery_assigned_at')->nullable()->after('delivery_person_name');
            if (!Schema::hasColumn('orders', 'delivery_dispatched_at'))
                $table->timestamp('delivery_dispatched_at')->nullable()->after('delivery_assigned_at');
            if (!Schema::hasColumn('orders', 'delivery_delivered_at'))
                $table->timestamp('delivery_delivered_at')->nullable()->after('delivery_dispatched_at');
            if (!Schema::hasColumn('orders', 'delivery_distance_km'))
                $table->decimal('delivery_distance_km', 5, 2)->nullable()->after('delivery_delivered_at');
            if (!Schema::hasColumn('orders', 'delivery_notes'))
                $table->text('delivery_notes')->nullable()->after('delivery_distance_km');
            if (!Schema::hasColumn('orders', 'delivery_proof'))
                $table->string('delivery_proof')->nullable()->after('delivery_notes'); // foto evidencia
        });

        // ── 3. CAJA: nueva tabla ───────────────────────────────────────────────
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();       // cajero
            $table->string('user_name');
            $table->decimal('monto_apertura', 12, 2)->default(0);
            $table->decimal('monto_cierre', 12, 2)->nullable();
            $table->decimal('monto_esperado', 12, 2)->nullable();                 // calculado al cerrar
            $table->decimal('diferencia', 12, 2)->nullable();                     // cierre - esperado
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->string('status')->default('open');                            // open | closed
            $table->text('notas_apertura')->nullable();
            $table->text('notas_cierre')->nullable();
            $table->timestamps();
        });

        // ── 4. MOVIMIENTOS DE CAJA ────────────────────────────────────────────
        Schema::create('caja_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('tipo');                                               // ingreso | egreso | venta
            $table->string('concepto');
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago')->nullable();                            // efectivo, tarjeta, yape...
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
        Schema::dropIfExists('cajas');

        Schema::table('orders', function (Blueprint $table) {
            $cols = ['delivery_status','delivery_person_id','delivery_person_name',
                     'delivery_assigned_at','delivery_dispatched_at','delivery_delivered_at',
                     'delivery_distance_km','delivery_notes','delivery_proof'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('orders', $col)) $table->dropColumn($col);
            }
        });

        Schema::table('appointments', function (Blueprint $table) {
            $cols = ['table_number','zone','guests','occasion','source','reminder_sent','confirmed_at','arrived_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('appointments', $col)) $table->dropColumn($col);
            }
        });
    }
};

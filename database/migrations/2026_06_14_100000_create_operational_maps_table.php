<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── MAPAS OPERATIVOS ──────────────────────────────────────────────────
        Schema::create('operational_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // "Planta principal", "Piso 2"
            $table->string('slug')->nullable();
            $table->text('background_url')->nullable();      // imagen de plano
            $table->integer('canvas_width')->default(1200);
            $table->integer('canvas_height')->default(800);
            $table->boolean('grid_enabled')->default(true);
            $table->integer('grid_size')->default(20);
            $table->boolean('snap_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── OBJETOS OPERATIVOS ────────────────────────────────────────────────
        Schema::create('operational_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('map_id')->nullable()->constrained('operational_maps')->nullOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('employees')->nullOnDelete();

            // Polimorfismo: puede asociarse a Order, Client, Appointment, etc.
            $table->nullableMorphs('objecteable');

            // Identidad
            $table->string('type');        // mesa | consultorio | habitacion | vehiculo | maquina | estante | zona | escritorio
            $table->string('label');       // "Mesa 12", "Hab. 301", "Dr. García"
            $table->string('icon')->nullable();             // emoji o clave de icono
            $table->string('shape')->default('rect');      // rect | circle | hexagon
            $table->string('color')->nullable();           // color personalizado

            // Estado
            $table->string('status')->default('libre');
            // libre | ocupado | reservado | alerta | bloqueado | mantenimiento | en_ruta | entregado

            $table->unsignedTinyInteger('priority')->default(0); // 0-5
            $table->string('zone')->nullable();            // "Terraza", "Salón VIP", "Piso 1"

            // Posición en el mapa
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->integer('width')->default(80);
            $table->integer('height')->default(60);
            $table->integer('rotation')->default(0);

            // Cronómetro
            $table->timestamp('started_at')->nullable();   // cuándo entró en estado actual

            // Capacidad y propiedades
            $table->unsignedSmallInteger('capacity')->nullable();   // personas, unidades, etc.

            // Financiero
            $table->decimal('current_amount', 12, 2)->default(0);  // consumo o valor actual
            $table->decimal('cost', 12, 2)->default(0);

            // Configuración flexible por tipo
            $table->json('config')->nullable();
            // restaurante: {fumador, vip, mascota, bebe, corriente}
            // hotel: {piso, vista, cama, conectada}
            // logística: {placa, combustible, gps_lat, gps_lng}
            // producción: {linea, ciclo_min}

            // Alertas activas (array simple, no tabla separada para queries rápidos)
            $table->json('alerts')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── EVENTOS / HISTORIAL ───────────────────────────────────────────────
        Schema::create('operational_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('object_id')->constrained('operational_objects')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type');
            // status_change | alert_added | alert_cleared | note | request
            // responsible_change | amount_update | merge | split

            $table->string('status_from')->nullable();
            $table->string('status_to')->nullable();
            $table->text('note')->nullable();
            $table->json('payload')->nullable();            // datos extra según tipo

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
        });

        // ── SOLICITUDES OPERATIVAS ────────────────────────────────────────────
        Schema::create('operational_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('object_id')->constrained('operational_objects')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type');
            // cuenta | limpieza | mantenimiento | reposicion | traslado | urgente | nota

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');  // pending | in_progress | done | cancelled
            $table->unsignedTinyInteger('priority')->default(1); // 1-5
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_requests');
        Schema::dropIfExists('operational_events');
        Schema::dropIfExists('operational_objects');
        Schema::dropIfExists('operational_maps');
    }
};

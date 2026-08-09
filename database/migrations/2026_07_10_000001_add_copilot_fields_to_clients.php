<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos de venta que alimenta el Copilot (extensión) sobre la tabla clients
 * existente. No duplicamos "clientes": extendemos el CRM de BIXO.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Token que usa la extensión (Copilot) para autenticar contra su proyecto.
        if (!Schema::hasColumn('projects', 'copilot_token')) {
            Schema::table('projects', function (Blueprint $t) {
                $t->string('copilot_token', 64)->nullable()->unique()->after('id');
            });
        }

        Schema::table('clients', function (Blueprint $t) {
            // Se añade columna por columna solo si falta (migración idempotente).
            $cols = [
                'lead_temp'          => fn () => $t->string('lead_temp', 12)->default('nuevo'),
                'lead_score'         => fn () => $t->unsignedTinyInteger('lead_score')->default(0),
                'lead_source'        => fn () => $t->string('lead_source', 8)->nullable(),
                'etapa'              => fn () => $t->string('etapa', 20)->default('prospecto'),
                'empresa'            => fn () => $t->string('empresa')->nullable(),
                'producto_interes'   => fn () => $t->string('producto_interes')->nullable(),
                'monto_estimado'     => fn () => $t->decimal('monto_estimado', 12, 2)->nullable(),
                'intencion'          => fn () => $t->string('intencion')->nullable(),
                'objeciones'         => fn () => $t->json('objeciones')->nullable(),
                'proximo_seguimiento'=> fn () => $t->timestamp('proximo_seguimiento')->nullable(),
                'ultima_actividad'   => fn () => $t->timestamp('ultima_actividad')->nullable(),
            ];
            foreach ($cols as $name => $add) {
                if (!Schema::hasColumn('clients', $name)) $add();
            }
        });

        if (!Schema::hasTable('sales_interactions')) {
            Schema::create('sales_interactions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $t->string('canal', 20)->default('whatsapp'); // multicanal futuro
                $t->string('direccion', 3);                    // in|out
                $t->text('texto');
                $t->timestamp('fecha')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_interactions');
        Schema::table('projects', function (Blueprint $t) {
            $t->dropColumn('copilot_token');
        });
        Schema::table('clients', function (Blueprint $t) {
            $t->dropColumn([
                'lead_temp', 'lead_score', 'lead_source', 'etapa', 'empresa',
                'producto_interes', 'monto_estimado', 'intencion', 'objeciones',
                'proximo_seguimiento', 'ultima_actividad',
            ]);
        });
    }
};

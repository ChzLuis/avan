<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RUC/DNI en la ficha del cliente.
 *
 * Hasta ahora el documento fiscal SOLO vivía dentro de cada comprobante
 * emitido: la ficha del cliente no lo guardaba. Consecuencias que se arreglan
 * aquí:
 *
 *   - no se podía buscar un cliente por su RUC (había que recordar el nombre);
 *   - el mismo cliente se re-tecleaba en cada factura, con sus erratas;
 *   - un cliente al que aún no se le había facturado no tenía documento en
 *     ninguna parte.
 *
 * El relleno inicial sale del historial: para cada cliente se toma el
 * documento de su comprobante más reciente. Es el dato más fiable que existe
 * —lo validó SUNAT o lo tecleó el negocio— y no inventa nada donde no lo hay.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clients', 'doc_number')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('doc_type', 20)->nullable()->after('name');
                $table->string('doc_number', 15)->nullable()->after('doc_type');
                // Buscar por documento dentro del negocio es la consulta que
                // hace el formulario en cada tecla: sin índice, escaneo.
                $table->index(['project_id', 'doc_number'], 'clients_doc_idx');
            });
        }

        // Relleno desde el último comprobante de cada cliente. Solo se toca
        // al que aún no tiene documento: nunca se pisa un dato existente.
        if (! Schema::hasTable('invoices')) {
            return;
        }

        DB::table('invoices')
            ->select('client_id', 'client_doc_type', 'client_doc_number')
            ->whereNotNull('client_id')
            ->whereNotNull('client_doc_number')
            ->where('client_doc_number', '!=', '')
            ->orderBy('id')            // el más reciente sobrescribe al viejo
            ->chunkById(500, function ($filas) {
                foreach ($filas as $f) {
                    DB::table('clients')
                        ->where('id', $f->client_id)
                        ->whereNull('doc_number')
                        ->update([
                            'doc_type'   => $f->client_doc_type,
                            'doc_number' => $f->client_doc_number,
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'doc_number')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropIndex('clients_doc_idx');
                $table->dropColumn(['doc_type', 'doc_number']);
            });
        }
    }
};

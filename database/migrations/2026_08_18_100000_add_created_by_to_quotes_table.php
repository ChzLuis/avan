<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Autor de la cotizacion.
 *
 * Los pedidos guardan `created_by` desde siempre, pero las cotizaciones no:
 * no habia forma de saber quien hizo un presupuesto, y por tanto tampoco de
 * medir cuantas hace cada vendedor ni de reclamar una cifra rara a quien la
 * escribio. El historial (`order_events`) si registraba al autor de cada
 * accion, asi que el dato existia disperso pero no en el documento.
 *
 * Aditiva y reversible. Para las cotizaciones ya creadas se recupera el autor
 * del evento 'created' cuando lo hay; las anteriores al historial se quedan
 * sin autor, que es la verdad: nadie lo anoto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('quotes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('project_id');
                $table->index('created_by', 'quotes_created_by_idx');
            }
        });

        // Backfill desde el historial: el evento de creacion ya sabia quien
        // fue. No se inventa autor donde no lo hubo.
        if (Schema::hasTable('order_events')) {
            $autores = DB::table('order_events')
                ->whereNotNull('quote_id')
                ->whereNotNull('user_id')
                ->where('action', 'created')
                ->orderBy('id')
                ->pluck('user_id', 'quote_id');

            foreach ($autores as $quoteId => $userId) {
                DB::table('quotes')->where('id', $quoteId)->whereNull('created_by')
                    ->update(['created_by' => $userId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'created_by')) {
                $table->dropIndex('quotes_created_by_idx');
                $table->dropColumn('created_by');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROPUESTAS COMERCIALES (proformas).
 *
 * Registra cada propuesta enviada a un cliente. El contenido de la propuesta
 * (qué incluye, por qué elegirnos…) es una plantilla fija; aquí se guardan
 * solo los datos que cambian por cliente: a quién, rubro, precios y extras.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La tabla puede existir de una versión anterior: en ese caso solo
        // AÑADIMOS las columnas que falten (nunca se borran datos).
        if (Schema::hasTable('proposals')) {
            Schema::table('proposals', function (Blueprint $table) {
                if (!Schema::hasColumn('proposals', 'token'))             $table->string('token', 64)->nullable()->after('number');
                if (!Schema::hasColumn('proposals', 'price_renewal'))     $table->decimal('price_renewal', 12, 2)->nullable()->after('price');
                if (!Schema::hasColumn('proposals', 'products_included')) $table->unsignedInteger('products_included')->nullable()->after('price_renewal');
                if (!Schema::hasColumn('proposals', 'extras'))            $table->json('extras')->nullable()->after('valid_days');
                if (!Schema::hasColumn('proposals', 'business_name'))     $table->string('business_name')->nullable();
                if (!Schema::hasColumn('proposals', 'rubro'))             $table->string('rubro')->nullable();
                if (!Schema::hasColumn('proposals', 'city'))              $table->string('city')->nullable();
                if (!Schema::hasColumn('proposals', 'extra_notes'))       $table->text('extra_notes')->nullable();
            });
            // Rellenar token en las propuestas que no lo tengan.
            \DB::table('proposals')->whereNull('token')->orWhere('token', '')->get()->each(function ($p) {
                \DB::table('proposals')->where('id', $p->id)->update(['token' => \Illuminate\Support\Str::random(40)]);
            });
            return;
        }

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('number')->nullable();            // N° de propuesta (PRO-0001)
            $table->string('token', 64)->unique();           // enlace público

            // Cliente
            $table->string('client_name');
            $table->string('business_name')->nullable();     // razón social / negocio
            $table->string('rubro')->nullable();             // rubro del negocio
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();
            $table->string('city')->nullable();

            // Inversión
            $table->decimal('price', 12, 2)->default(0);           // desarrollo
            $table->decimal('price_renewal', 12, 2)->nullable();   // renovación anual
            $table->unsignedInteger('products_included')->nullable(); // ej. 200 productos
            $table->unsignedInteger('valid_days')->default(15);    // validez

            // Servicios adicionales seleccionados (array de {nombre, precio, periodo})
            $table->json('extras')->nullable();
            // Alcance / notas específicas para este cliente
            $table->text('extra_notes')->nullable();

            $table->string('status')->default('borrador');   // borrador|enviada|aceptada|rechazada
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};

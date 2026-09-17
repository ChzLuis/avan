<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `wa_canales` pasa a ser el UNICO propietario de las credenciales de Meta.
 *
 * Ya guardaba phone_number_id / access_token / verify_token y la bandeja
 * enviaba con ellos, pero faltaban dos cosas para poder RECIBIR:
 *   - `app_secret`: firma los webhooks entrantes (sin el, cualquiera podria
 *     inyectar mensajes al bot, porque la URL del webhook es publica).
 *   - `api_version` y el rastro del ultimo envio, para diagnosticar desde el
 *     panel en vez de a ciegas.
 *
 * Ademas, el token viajaba EN CLARO en la base de datos: a partir de aqui se
 * guarda cifrado (cast 'encrypted' en el modelo). Los tokens que ya existan se
 * cifran aqui mismo, uno por uno, detectando si ya lo estaban para que volver
 * a ejecutar la migracion no los destruya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_canales', function (Blueprint $t) {
            $t->text('app_secret')->nullable()->after('access_token');
            $t->string('api_version', 10)->default('v21.0')->after('verify_token');
            $t->timestamp('ultimo_ok_at')->nullable()->after('api_version');
            $t->string('ultimo_error', 255)->nullable()->after('ultimo_ok_at');
        });

        // Una linea de Meta no puede atender a dos negocios: seria una fuga
        // entre inquilinos. Solo se indexan los valores presentes (NULL no
        // colisiona en MySQL), asi que los canales sin conectar no estorban.
        Schema::table('wa_canales', function (Blueprint $t) {
            $t->unique('phone_number_id', 'wa_canales_phone_number_id_unico');
        });

        $this->cifrarTokensExistentes();
    }

    /**
     * Cifra los access_token que quedaron en claro.
     *
     * Se comprueba uno por uno si ya estaban cifrados: asi la migracion es
     * idempotente y no arruina credenciales en un entorno donde ya se hubiera
     * aplicado (ARIN y local no van al mismo ritmo).
     */
    private function cifrarTokensExistentes(): void
    {
        $filas = DB::table('wa_canales')
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '')
            ->get(['id', 'access_token']);

        foreach ($filas as $fila) {
            try {
                Crypt::decryptString($fila->access_token);
                continue;   // ya estaba cifrado: no se toca
            } catch (\Throwable) {
                // En claro: se cifra.
            }

            DB::table('wa_canales')->where('id', $fila->id)->update([
                'access_token' => Crypt::encryptString($fila->access_token),
            ]);
        }
    }

    public function down(): void
    {
        // Se devuelven los tokens a texto plano: si no, quedarian ilegibles
        // para el codigo anterior, que los leia sin descifrar.
        foreach (DB::table('wa_canales')->whereNotNull('access_token')->get(['id', 'access_token']) as $fila) {
            try {
                DB::table('wa_canales')->where('id', $fila->id)->update([
                    'access_token' => Crypt::decryptString($fila->access_token),
                ]);
            } catch (\Throwable) {
                // No estaba cifrado: se queda como esta.
            }
        }

        Schema::table('wa_canales', function (Blueprint $t) {
            $t->dropUnique('wa_canales_phone_number_id_unico');
            $t->dropColumn(['app_secret', 'api_version', 'ultimo_ok_at', 'ultimo_error']);
        });
    }
};

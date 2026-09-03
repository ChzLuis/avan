<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta de DNI y RUC — punto ÚNICO.
 *
 * Antes vivía partida en dos sitios, con dos proveedores y dos criterios:
 *   · RUC en `InvoiceController::consultarRuc` (curl crudo, token
 *     `apiperu_token` que NINGÚN proyecto tenía configurado → siempre fallaba)
 *   · DNI en `RifaController::consultarDni`, cuya ruta exige `can:rifas.ver`,
 *     así que un cajero sin permiso de rifas no podía consultar un documento.
 *
 * Medido contra los proveedores el 2026-09-03:
 *   · RUC  → `api.apis.net.pe/v1/ruc` responde SIN token, con razón social,
 *     estado, condición, dirección y ubigeo. Es el camino por defecto.
 *   · DNI  → el proveedor migró a un endpoint con token. Sin token
 *     configurado se dice con todas sus letras y se deja escribir a mano;
 *     nunca se bloquea al usuario por una API caída.
 */
final class ConsultaDocumento
{
    /** Un día: la razón social de un RUC no cambia en una tarde. */
    private const CACHE_SEGUNDOS = 86400;
    private const TIMEOUT = 8;

    /**
     * Valida SIN llamar a la API: gastar cuota (y hacer esperar al usuario)
     * en un documento imposible no tiene sentido.
     *
     * @return array{valido:bool, tipo:?string, error:?string}
     */
    public static function validar(string $numero): array
    {
        $n = preg_replace('/\D/', '', $numero);

        if ($n === '') {
            return ['valido' => false, 'tipo' => null, 'error' => 'Escribe un número de documento.'];
        }

        if (strlen($n) === 8) {
            return ['valido' => true, 'tipo' => 'dni', 'error' => null];
        }

        if (strlen($n) === 11) {
            return self::rucBienFormado($n)
                ? ['valido' => true, 'tipo' => 'ruc', 'error' => null]
                : ['valido' => false, 'tipo' => 'ruc', 'error' => 'Ese RUC no es válido: revisa los dígitos.'];
        }

        return [
            'valido' => false,
            'tipo'   => null,
            'error'  => 'Un DNI tiene 8 dígitos y un RUC 11.',
        ];
    }

    /**
     * Dígito verificador del RUC peruano (módulo 11). Descarta erratas de
     * tipeo antes de salir a internet.
     */
    public static function rucBienFormado(string $ruc): bool
    {
        if (! preg_match('/^\d{11}$/', $ruc)) {
            return false;
        }
        // Solo existen estos tipos de contribuyente.
        if (! in_array(substr($ruc, 0, 2), ['10', '15', '16', '17', '20'], true)) {
            return false;
        }

        $factores = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        foreach ($factores as $i => $f) {
            $suma += ((int) $ruc[$i]) * $f;
        }
        $resto    = $suma % 11;
        $esperado = (11 - $resto) % 10;

        return (int) $ruc[10] === $esperado;
    }

    /**
     * Devuelve SIEMPRE una estructura estable, nunca lanza: quien llama debe
     * poder seguir escribiendo los datos a mano pase lo que pase.
     *
     * @return array{ok:bool, tipo:?string, datos:array, mensaje:string, motivo:string}
     */
    public function consultar(?Project $project, string $numero): array
    {
        $n = preg_replace('/\D/', '', $numero);
        $v = self::validar($n);

        if (! $v['valido']) {
            return $this->fallo($v['error'] ?? 'Documento inválido.', 'invalido', $v['tipo']);
        }

        $clave = "doc.{$v['tipo']}.{$n}";
        $cacheado = Cache::get($clave);
        if (is_array($cacheado)) {
            return $cacheado;
        }

        $resultado = $v['tipo'] === 'ruc'
            ? $this->consultarRuc($n, $project)
            : $this->consultarDni($n, $project);

        // Solo se cachea el acierto: un fallo de red no debe quedarse pegado.
        if ($resultado['ok']) {
            Cache::put($clave, $resultado, self::CACHE_SEGUNDOS);
        }

        return $resultado;
    }

    /** RUC: público, responde sin token. */
    private function consultarRuc(string $ruc, ?Project $project): array
    {
        try {
            $r = Http::timeout(self::TIMEOUT)
                ->retry(2, 250, throw: false)
                ->get('https://api.apis.net.pe/v1/ruc', array_filter([
                    'numero' => $ruc,
                    'token'  => $this->token($project),
                ]));
        } catch (\Throwable $e) {
            Log::warning('ConsultaDocumento: RUC sin respuesta', ['ruc' => $ruc, 'error' => $e->getMessage()]);

            return $this->fallo('No pudimos conectar con el servicio. Puedes escribir los datos a mano.', 'conexion', 'ruc');
        }

        if ($r->status() === 404) {
            return $this->fallo('No encontramos ese RUC en SUNAT.', 'no_encontrado', 'ruc');
        }
        if ($r->status() === 429) {
            return $this->fallo('El servicio está saturado. Inténtalo en un momento o escribe los datos a mano.', 'limite', 'ruc');
        }
        if (! $r->successful()) {
            Log::warning('ConsultaDocumento: RUC con error', ['ruc' => $ruc, 'http' => $r->status()]);

            return $this->fallo('El servicio no respondió bien. Puedes escribir los datos a mano.', 'servicio', 'ruc');
        }

        $d = $r->json();
        if (! is_array($d) || blank($d['nombre'] ?? null)) {
            return $this->fallo('No encontramos datos para ese RUC.', 'vacio', 'ruc');
        }

        // El proveedor rellena con "-" lo que no publica (el domicilio de un
        // RUC 10 es dato personal y SUNAT no lo expone). Ese guion no es una
        // dirección: se trata como vacío para que nadie lo imprima en un
        // comprobante ni lo tome por un dato real.
        $limpio = static fn ($v) => in_array(trim((string) $v), ['-', '', 'NULL'], true) ? '' : trim((string) $v);

        return [
            'ok'    => true,
            'tipo'  => 'ruc',
            'datos' => [
                'numero'          => $ruc,
                'razon_social'    => trim((string) $d['nombre']),
                'nombre_comercial'=> $limpio($d['nombreComercial'] ?? ''),
                'direccion'       => $limpio($d['direccion'] ?? ''),
                'ubigeo'          => $limpio($d['ubigeo'] ?? ''),
                'departamento'    => $limpio($d['departamento'] ?? ''),
                'provincia'       => $limpio($d['provincia'] ?? ''),
                'distrito'        => $limpio($d['distrito'] ?? ''),
                'estado'          => $limpio($d['estado'] ?? ''),
                'condicion'       => $limpio($d['condicion'] ?? ''),
            ],
            'mensaje' => 'Datos encontrados',
            'motivo'  => 'ok',
        ];
    }

    /** DNI: el proveedor lo dejó tras token. Sin token se dice claramente. */
    private function consultarDni(string $dni, ?Project $project): array
    {
        $token = $this->token($project);
        if (blank($token)) {
            return $this->fallo(
                'La consulta de DNI necesita un token configurado. Escribe el nombre a mano por ahora.',
                'sin_token',
                'dni'
            );
        }

        try {
            $r = Http::timeout(self::TIMEOUT)
                ->retry(2, 250, throw: false)
                ->withToken($token)
                ->get('https://api.apis.net.pe/v2/reniec/dni', ['numero' => $dni]);
        } catch (\Throwable $e) {
            Log::warning('ConsultaDocumento: DNI sin respuesta', ['error' => $e->getMessage()]);

            return $this->fallo('No pudimos conectar con el servicio. Puedes escribir los datos a mano.', 'conexion', 'dni');
        }

        if (in_array($r->status(), [401, 403], true)) {
            return $this->fallo('El token de consulta no es válido o caducó.', 'token_invalido', 'dni');
        }
        if ($r->status() === 404) {
            return $this->fallo('No encontramos ese DNI.', 'no_encontrado', 'dni');
        }
        if ($r->status() === 429) {
            return $this->fallo('El servicio está saturado. Inténtalo en un momento.', 'limite', 'dni');
        }
        if (! $r->successful()) {
            return $this->fallo('El servicio no respondió bien. Puedes escribir los datos a mano.', 'servicio', 'dni');
        }

        $d = $r->json();
        $nombres   = trim((string) ($d['nombres'] ?? ''));
        $apPaterno = trim((string) ($d['apellidoPaterno'] ?? ''));
        $apMaterno = trim((string) ($d['apellidoMaterno'] ?? ''));
        $completo  = trim((string) ($d['nombreCompleto'] ?? trim("{$nombres} {$apPaterno} {$apMaterno}")));

        if ($completo === '') {
            return $this->fallo('No encontramos datos para ese DNI.', 'vacio', 'dni');
        }

        return [
            'ok'    => true,
            'tipo'  => 'dni',
            'datos' => [
                'numero'           => $dni,
                'nombres'          => $nombres,
                'apellido_paterno' => $apPaterno,
                'apellido_materno' => $apMaterno,
                'nombre_completo'  => $completo,
                'direccion'        => trim((string) ($d['direccion'] ?? '')),
            ],
            'mensaje' => 'Datos encontrados',
            'motivo'  => 'ok',
        ];
    }

    /**
     * Token del negocio. Se acepta el nombre histórico `apiperu_token` y, si
     * no está, uno global: así el que ya lo tenía configurado no se entera
     * del cambio. (`apisperu_token` NO sirve aquí: es el de facturación,
     * comprobado contra el proveedor.)
     */
    private function token(?Project $project): ?string
    {
        $delNegocio = $project?->setting('apiperu_token');

        return filled($delNegocio) ? (string) $delNegocio : (config('services.apisperu.doc_token') ?: null);
    }

    private function fallo(string $mensaje, string $motivo, ?string $tipo): array
    {
        return ['ok' => false, 'tipo' => $tipo, 'datos' => [], 'mensaje' => $mensaje, 'motivo' => $motivo];
    }
}

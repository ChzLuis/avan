<?php

namespace App\Modules\Crm\Support\WebPush;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Web Push sin dependencias: VAPID (RFC 8292) + cifrado aes128gcm (RFC 8291 / 8188)
 * con OpenSSL. Las claves VAPID se generan una sola vez y se guardan en la tabla
 * `push_claves`; la publica es la que el navegador usa al suscribirse.
 */
class PushWeb
{
    private const CURVA = 'prime256v1';
    /** Prefijo DER de una clave publica P-256 (SubjectPublicKeyInfo) antes del punto de 65 bytes. */
    private const DER_P256 = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    /**
     * Opciones para crear claves EC. En Windows (XAMPP) OpenSSL no encuentra su
     * openssl.cnf y openssl_pkey_new devuelve false; en Linux no hace falta.
     */
    public static function opcionesEc(): array
    {
        $op = ['curve_name' => self::CURVA, 'private_key_type' => OPENSSL_KEYTYPE_EC];
        if (getenv('OPENSSL_CONF') === false) {
            foreach (['C:/xampp/php/extras/openssl/openssl.cnf', 'C:/xampp/apache/conf/openssl.cnf'] as $cfg) {
                if (is_file($cfg)) { $op['config'] = $cfg; break; }
            }
        }

        return $op;
    }

    public static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64urlDecode(string $txt): string
    {
        return base64_decode(strtr($txt, '-_', '+/') . str_repeat('=', (4 - strlen($txt) % 4) % 4));
    }

    /** Claves VAPID [publica_b64url, privada_pem]; se crean la primera vez. */
    public static function claves(): array
    {
        $fila = \DB::table('push_claves')->first();
        if ($fila) {
            return [$fila->publica, $fila->privada];
        }
        $op = self::opcionesEc();
        $key = openssl_pkey_new($op);
        openssl_pkey_export($key, $pem, null, isset($op['config']) ? ['config' => $op['config']] : []);
        $publica = self::b64url(self::puntoPublico($key));
        \DB::table('push_claves')->insert(['publica' => $publica, 'privada' => $pem, 'created_at' => now(), 'updated_at' => now()]);

        return [$publica, $pem];
    }

    public static function clavePublica(): string
    {
        return self::claves()[0];
    }

    /** Punto publico sin comprimir (0x04 || X || Y), 65 bytes. */
    private static function puntoPublico($key): string
    {
        $d = openssl_pkey_get_details($key)['ec'];

        return "\x04" . str_pad($d['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['y'], 32, "\0", STR_PAD_LEFT);
    }

    private static function clavePublicaDesdePunto(string $punto)
    {
        $der = hex2bin(self::DER_P256) . $punto;
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";

        return openssl_pkey_get_public($pem);
    }

    /** Cabecera Authorization VAPID para el origen del endpoint. */
    public static function vapid(string $endpoint, string $sub = 'mailto:soporte@eskala.pe'): string
    {
        [$publica, $pem] = self::claves();
        $u = parse_url($endpoint);
        $aud = $u['scheme'] . '://' . $u['host'];
        $cab = self::b64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $cla = self::b64url(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => $sub]));
        openssl_sign("{$cab}.{$cla}", $der, openssl_pkey_get_private($pem), OPENSSL_ALGO_SHA256);
        $jwt = "{$cab}.{$cla}." . self::b64url(self::derARaw($der));

        return "vapid t={$jwt}, k={$publica}";
    }

    /** Firma ECDSA DER -> r||s de 64 bytes (formato JWS). */
    private static function derARaw(string $der): string
    {
        $pos = 2; // 0x30 len
        $leer = function () use ($der, &$pos) {
            $pos++; // 0x02
            $len = ord($der[$pos++]);
            $v = substr($der, $pos, $len);
            $pos += $len;

            return str_pad(ltrim($v, "\0"), 32, "\0", STR_PAD_LEFT);
        };

        return $leer() . $leer();
    }

    /** Cuerpo cifrado aes128gcm para una suscripcion (p256dh, auth en base64url). */
    public static function cifrar(string $p256dh, string $auth, string $payload): string
    {
        $uaPublica = self::b64urlDecode($p256dh);
        $authSecret = self::b64urlDecode($auth);
        $efimera = openssl_pkey_new(self::opcionesEc());
        $asPublica = self::puntoPublico($efimera);
        $ecdh = openssl_pkey_derive(self::clavePublicaDesdePunto($uaPublica), $efimera, 32);

        $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0" . $uaPublica . $asPublica, $authSecret);
        $salt = random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        $texto = $payload . "\x02"; // delimitador del ultimo registro
        $cifrado = openssl_encrypt($texto, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        return $salt . pack('N', 4096) . chr(strlen($asPublica)) . $asPublica . $cifrado . $tag;
    }

    /**
     * Envia una notificacion. Devuelve ['ok'=>bool, 'estado'=>int, 'caducada'=>bool].
     * 404/410 = la suscripcion ya no existe (borrarla).
     */
    public static function enviar(string $endpoint, string $p256dh, string $auth, array $datos, int $ttl = 3600): array
    {
        try {
            $cuerpo = self::cifrar($p256dh, $auth, json_encode($datos, JSON_UNESCAPED_UNICODE));
            $res = Http::withHeaders([
                'Authorization'    => self::vapid($endpoint),
                'Content-Encoding' => 'aes128gcm',
                'Content-Type'     => 'application/octet-stream',
                'TTL'              => (string) $ttl,
                'Urgency'          => 'high',
            ])->withBody($cuerpo, 'application/octet-stream')->timeout(4)->connectTimeout(3)->post($endpoint);
            $estado = $res->status();

            return ['ok' => $res->successful(), 'estado' => $estado, 'caducada' => in_array($estado, [404, 410], true)];
        } catch (\Throwable $e) {
            Log::warning('push.envio_fallo', ['error' => class_basename($e), 'detalle' => mb_substr($e->getMessage(), 0, 200)]);

            return ['ok' => false, 'estado' => 0, 'caducada' => false];
        }
    }
}

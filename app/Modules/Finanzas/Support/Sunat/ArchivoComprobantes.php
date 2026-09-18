<?php

namespace App\Modules\Finanzas\Support\Sunat;

use App\Modules\Finanzas\Models\Invoice;
use Illuminate\Support\Facades\Storage;

/**
 * El XML firmado y el CDR de cada comprobante, como FICHEROS.
 *
 * Hasta hoy vivían solo dentro de la respuesta de APIsPERU guardada en la
 * columna `invoices.sunat_cdr` (un TEXT de 64 KB), sin forma de descargarlos.
 * La norma exige al emisor conservar el XML firmado y la constancia de
 * recepción mientras el tributo no prescriba y darle acceso al comprador
 * durante un año: un PDF no basta. Aquí se archivan con el nombre que SUNAT
 * usa ({RUC}-{tipo}-{serie}-{correlativo}.xml y R-….zip) en el disco privado,
 * por negocio.
 */
final class ArchivoComprobantes
{
    public const DISCO = 'local';

    /** Carpeta del negocio dentro del disco privado. */
    public static function carpeta(Invoice $invoice): string
    {
        return 'comprobantes/'.$invoice->project_id;
    }

    /** Nombre base normado: 20601234567-01-F001-00000012. */
    public static function nombreBase(Invoice $invoice): string
    {
        $correlativo = str_pad((string) (int) $invoice->correlativo, 8, '0', STR_PAD_LEFT);

        return ($invoice->emisor_ruc ?: 'SINRUC').'-'.$invoice->codigoSunat().'-'.$invoice->serie.'-'.$correlativo;
    }

    public static function rutaXml(Invoice $invoice): string
    {
        return self::carpeta($invoice).'/'.self::nombreBase($invoice).'.xml';
    }

    public static function rutaCdr(Invoice $invoice): string
    {
        return self::carpeta($invoice).'/R-'.self::nombreBase($invoice).'.zip';
    }

    /**
     * Archiva lo que trajo la respuesta de APIsPERU. Devuelve qué se escribió.
     * No falla si falta algo: un comprobante aceptado sin CDR archivado sigue
     * siendo un comprobante aceptado; la falta se ve en `estado()`.
     *
     * @return array{xml: bool, cdr: bool}
     */
    public static function guardar(Invoice $invoice, ?array $respuesta = null): array
    {
        $respuesta ??= self::respuesta($invoice);
        $hecho = ['xml' => false, 'cdr' => false];
        if (! $respuesta) {
            return $hecho;
        }

        $disco = Storage::disk(self::DISCO);

        try {
            $xml = $respuesta['xml'] ?? null;
            if (is_string($xml) && $xml !== '') {
                // APIsPERU lo manda en claro; si algún día viniera en base64, se detecta.
                if (! str_starts_with(ltrim($xml), '<')) {
                    $decodificado = base64_decode($xml, true);
                    if ($decodificado !== false && str_starts_with(ltrim($decodificado), '<')) {
                        $xml = $decodificado;
                    }
                }
                $disco->put(self::rutaXml($invoice), $xml);
                self::permisos($disco->path(self::rutaXml($invoice)));
                $hecho['xml'] = true;
            }

            $zip = $respuesta['sunatResponse']['cdrZip'] ?? null;
            if (is_string($zip) && $zip !== '') {
                $binario = base64_decode($zip, true);
                if ($binario !== false) {
                    $disco->put(self::rutaCdr($invoice), $binario);
                    self::permisos($disco->path(self::rutaCdr($invoice)));
                    $hecho['cdr'] = true;
                }
            }
        } catch (\Throwable $e) {
            // Archivar es un deber, pero no puede tumbar el envio ni la
            // descarga: se registra y el estado() lo delata.
            \Illuminate\Support\Facades\Log::warning('No se pudo archivar el comprobante', [
                'invoice' => $invoice->id, 'numero' => $invoice->numero, 'error' => $e->getMessage(),
            ]);
        }

        return $hecho;
    }

    /**
     * El fichero lo escribe quien corra en ese momento: el trabajador de cola
     * (root u otro usuario) o el servidor web. Para que TODOS puedan leerlo y
     * la carpeta admita escrituras de los demas, se iguala al dueño de
     * `storage/app` cuando se puede (solo root puede cambiar dueño) y se
     * abren los permisos de grupo. Sin esto, un XML archivado por la cola
     * como root era ilegible para la descarga desde la web.
     */
    private static function permisos(string $rutaAbsoluta): void
    {
        $dueno = @fileowner(storage_path('app'));
        $grupo = @filegroup(storage_path('app'));
        foreach ([$rutaAbsoluta, dirname($rutaAbsoluta), dirname($rutaAbsoluta, 2)] as $i => $ruta) {
            if (! @file_exists($ruta)) {
                continue;
            }
            @chmod($ruta, $i === 0 ? 0664 : 0775);
            if ($dueno !== false && function_exists('posix_geteuid') && posix_geteuid() === 0) {
                @chown($ruta, $dueno);
                @chgrp($ruta, $grupo);
            }
        }
    }

    /** Asegura que los ficheros existan si hay con qué crearlos. */
    public static function asegurar(Invoice $invoice): void
    {
        $disco = Storage::disk(self::DISCO);
        if (! $disco->exists(self::rutaXml($invoice)) || ! $disco->exists(self::rutaCdr($invoice))) {
            self::guardar($invoice);
        }
    }

    /** @return array{xml: bool, cdr: bool} */
    public static function estado(Invoice $invoice): array
    {
        $disco = Storage::disk(self::DISCO);

        return [
            'xml' => $disco->exists(self::rutaXml($invoice)),
            'cdr' => $disco->exists(self::rutaCdr($invoice)),
        ];
    }

    /** La respuesta de APIsPERU guardada en la columna, ya decodificada. */
    public static function respuesta(Invoice $invoice): ?array
    {
        if (! is_string($invoice->sunat_cdr) || $invoice->sunat_cdr === '') {
            return null;
        }
        $r = json_decode($invoice->sunat_cdr, true);

        return is_array($r) ? $r : null;
    }
}

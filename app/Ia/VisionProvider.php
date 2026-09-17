<?php

namespace App\Ia;

/**
 * Lectura de documentos con vision (imagen -> datos).
 *
 * Se separa de `IaProvider` a proposito: aquel resuelve texto y su contrato
 * (`chat(array $mensajes)`) no admite adjuntos. Mezclar ambos obligaria a
 * tocar los cuatro proveedores de texto para una funcion que hoy solo usa
 * el lector de comprobantes.
 *
 * Quien implemente esto recibe el fichero YA en base64 y devuelve el texto
 * crudo del modelo. Interpretar ese texto no es su trabajo: de eso se ocupa
 * `LectorComprobantes`, para que cambiar de proveedor no arrastre el parseo.
 */
interface VisionProvider
{
    /**
     * @param  string  $base64    Contenido del archivo, ya codificado.
     * @param  string  $mime      MIME real comprobado, no la extension.
     * @param  string  $prompt    Que se le pide extraer.
     * @param  array   $opciones  timeout, max_tokens, temperature.
     *
     * @throws \RuntimeException  Si el proveedor no responde o rechaza.
     */
    public function leerDocumento(string $base64, string $mime, string $prompt, array $opciones = []): string;

    public function nombre(): string;
}

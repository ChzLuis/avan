<?php

namespace App\Support;

/**
 * Descripciones con formato básico (negrita, cursiva, listas).
 *
 * El editor del panel manda HTML; aquí se limpia antes de guardarlo y se
 * decide cómo pintarlo en la tienda. Dos reglas que sostienen todo:
 *
 *  1. Solo se permiten etiquetas de formato y SIN atributos. Al no aceptar
 *     href/src/style/on* no queda vector de XSS aunque el texto venga
 *     manipulado desde fuera del panel.
 *  2. Las descripciones viejas son texto plano; render() las detecta y las
 *     escapa respetando sus saltos de línea, para no romper lo ya cargado.
 */
class RichText
{
    /** Etiquetas de formato permitidas (nada de enlaces, imágenes ni estilos). */
    private const ALLOWED = '<b><strong><i><em><u><ul><ol><li><br><p><div>';

    /** Limpia el HTML que llega del editor. Devuelve null si queda vacío. */
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        // strip_tags quita las etiquetas pero deja el contenido: sin esto, el
        // texto interno de un <script> pegado quedaría suelto en la descripción.
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $html);

        $clean = strip_tags($html, self::ALLOWED);

        // Quitar TODOS los atributos de las etiquetas que sobrevivieron.
        $clean = preg_replace('/<\s*([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/', '<$1>', $clean);

        // Un editor contenteditable deja mucho <div> suelto: se normaliza a saltos.
        $clean = preg_replace('#<div>#i', '<br>', $clean);
        $clean = preg_replace('#</div>#i', '', $clean);
        $clean = preg_replace('#(<br>\s*){3,}#i', '<br><br>', $clean);

        // ¿Quedó contenido real o solo etiquetas y espacios?
        $texto = trim(html_entity_decode(strip_tags($clean), ENT_QUOTES, 'UTF-8'));
        $texto = trim(preg_replace('/\x{00A0}/u', '', $texto));

        return $texto === '' ? null : trim($clean);
    }

    /** HTML listo para pintar en la tienda (soporta descripciones antiguas en texto plano). */
    public static function render(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        // Sin etiquetas => descripción antigua en texto plano: escapar y conservar saltos.
        if (strip_tags($value) === $value) {
            return nl2br(e($value));
        }

        return self::clean($value) ?? '';
    }

    /** Texto plano (tarjetas, buscadores, metadatos SEO, catálogo PDF). */
    public static function plain(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Las etiquetas de bloque se vuelven espacio: sin esto "…</b><li>2K" se
        // pegaría como "Camara2K" en las tarjetas y buscadores.
        $texto = strip_tags(preg_replace('#<br\s*/?>|</?(p|li|ul|ol|div|h[1-6])[^>]*>#i', ' ', $value));

        return trim(preg_replace('/\s+/', ' ', html_entity_decode($texto, ENT_QUOTES, 'UTF-8')));
    }
}

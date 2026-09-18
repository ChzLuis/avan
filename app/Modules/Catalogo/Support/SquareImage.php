<?php

namespace App\Modules\Catalogo\Support;

/**
 * Normaliza cualquier imagen subida a un lienzo cuadrado JPG con fondo
 * blanco (contain, sin recortar), para que las miniaturas del catálogo
 * siempre cuadren sin deformar la foto original.
 */
class SquareImage
{
    public static function toFile(string $sourcePath, string $destPath, int $size = 800): bool
    {
        $info = @getimagesize($sourcePath);
        if (!$info) return false;

        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png'  => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            'image/gif'  => @imagecreatefromgif($sourcePath),
            default      => false,
        };
        if (!$source) return false;

        [$srcW, $srcH] = [imagesx($source), imagesy($source)];

        $canvas = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        // Contain: escala la foto completa dentro del lienzo sin recortarla.
        $ratio = min($size / $srcW, $size / $srcH);
        $dstW = (int) round($srcW * $ratio);
        $dstH = (int) round($srcH * $ratio);
        $dstX = (int) (($size - $dstW) / 2);
        $dstY = (int) (($size - $dstH) / 2);

        imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($source);

        $ok = imagejpeg($canvas, $destPath, 90);
        imagedestroy($canvas);

        return $ok;
    }
}

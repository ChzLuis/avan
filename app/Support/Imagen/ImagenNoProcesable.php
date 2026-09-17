<?php

namespace App\Support\Imagen;

/**
 * La imagen no se pudo procesar por algo que el usuario puede corregir
 * (formato raro, archivo dañado, resolución desmedida). El mensaje va tal
 * cual a la pantalla, así que se escribe en cristiano.
 */
class ImagenNoProcesable extends \RuntimeException
{
}

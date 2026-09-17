<?php

namespace App\Support\Imagen;

/**
 * Qué necesita cada componente de la tienda de una imagen.
 *
 * Un perfil no describe la foto que sube el usuario —esa puede venir como
 * sea—, describe el hueco donde va a entrar: su proporción, cómo rellenarlo
 * y a qué anchos hace falta servirla. El procesador se encarga del resto.
 */
final class PerfilImagen
{
    /** Encaja la foto entera dentro del lienzo y rellena el sobrante. */
    public const CONTAIN = 'contain';

    /** Llena el lienzo recortando lo que sobra por los lados. */
    public const COVER = 'cover';

    /** Respeta la proporción original; solo limita el lado mayor. */
    public const LIBRE = 'libre';

    private function __construct(
        public readonly string $nombre,
        public readonly string $estrategia,
        public readonly ?float $proporcion,
        public readonly array $anchos,
        public readonly int $anchoMaximo,
        public readonly ?string $fondo,
        public readonly int $calidad,
        public readonly string $sizes,
        /** Descarta el margen de fondo que ya trae la foto antes de encajarla. */
        public readonly bool $recorteFondo = false,
        /** Qué parte del lienzo debe ocupar el motivo, de 0 a 1. */
        public readonly float $ocupacion = 1.0,
    ) {
    }

    /**
     * Catálogo de perfiles. Cambiar un ancho aquí y relanzar
     * `imagenes:regenerar` es todo lo que hace falta para reencuadrar
     * una tienda entera.
     */
    public static function catalogo(): array
    {
        return [
            // El producto manda: se ve completo, centrado y sin recorte. El
            // relleno blanco es el estándar de cualquier marketplace y hace
            // que una foto vertical de móvil (3024x4032) cuadre en la ficha.
            // La foto lleva el producto y nada mas: se recorta el fondo que
            // traia horneado y NO se le mete en un lienzo cuadrado. La forma del
            // hueco la decide la tarjeta, que cada tienda configura (1/1, 4/3,
            // 3/4); hornear un cuadrado dentro de un marco 4/3 desperdiciaba el
            // 25% del ancho y dejaba el producto un cuarto mas pequeno. El aire
            // lo pone el CSS en porcentaje, asi que escala con la tarjeta.
            'producto' => new self('producto', self::LIBRE, null, [400, 800, 1200], 1200, null, 82, '(max-width:640px) 50vw, 320px', true, 1.0),

            // La categoría es un icono grande: se lee de un vistazo, no se
            // estudia. Con 800 px sobra incluso en pantallas retina.
            'categoria' => new self('categoria', self::CONTAIN, 1.0, [200, 400, 800], 800, '#ffffff', 82, '(max-width:640px) 33vw, 200px', true, 0.86),

            // El logo nunca se recorta ni se rellena: la marca se conserva
            // entera y con su transparencia. Solo se limita el tamaño.
            'logo' => new self('logo', self::LIBRE, null, [200, 400, 800], 800, null, 88, '200px'),

            // Banner de escritorio: panorámico. Se usa cover porque el hueco
            // es fijo, pero el recorte sale del centro para no decapitar lo
            // importante.
            'banner' => new self('banner', self::COVER, 1920 / 640, [768, 1280, 1920], 1920, null, 80, '100vw'),

            // Banner de móvil: casi vertical. Es una imagen distinta, no un
            // recorte agresivo de la de escritorio.
            'banner_movil' => new self('banner_movil', self::COVER, 4 / 5, [480, 768], 768, null, 80, '100vw'),

            // Secciones del constructor (galerías, testimonios, "nosotros"…):
            // no hay una proporción única, así que se respeta la del origen.
            'seccion' => new self('seccion', self::LIBRE, null, [640, 1024, 1600], 1600, null, 82, '(max-width:768px) 100vw, 800px'),

            'miniatura' => new self('miniatura', self::CONTAIN, 1.0, [120, 240], 240, '#ffffff', 80, '120px', true, 0.9),

            // Cualquier otra subida: solo se le pone techo para que una foto
            // de 6000 px no viaje entera hasta el navegador.
            'generico' => new self('generico', self::LIBRE, null, [800, 1600], 1600, null, 82, '100vw'),
        ];
    }

    public static function de(string $nombre): self
    {
        return self::catalogo()[$nombre] ?? self::catalogo()['generico'];
    }

    public static function existe(string $nombre): bool
    {
        return isset(self::catalogo()[$nombre]);
    }

    /** Ancho de referencia: el que se sirve cuando el navegador no elige. */
    public function anchoPrincipal(): int
    {
        return $this->anchos[count($this->anchos) > 1 ? count($this->anchos) - 2 : 0];
    }
}

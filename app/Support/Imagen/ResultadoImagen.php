<?php

namespace App\Support\Imagen;

/**
 * Lo que queda en disco después de procesar una subida: el original guardado
 * como fuente, la variante que se usa por defecto y el juego completo de
 * anchos listo para `srcset`.
 */
final class ResultadoImagen
{
    public function __construct(
        public readonly string $perfil,
        /** Disco donde quedaron las variantes ('public', 'uploads'…). */
        public readonly string $disco,
        /** Ruta del original en el disco privado, para poder regenerar. */
        public readonly ?string $original,
        /** Ruta en el disco público de la variante por defecto (jpg/png). */
        public readonly string $principal,
        /** [ancho => ['jpg' => ruta, 'webp' => ruta, 'avif' => ruta|null]] */
        public readonly array $variantes,
        public readonly int $ancho,
        public readonly int $alto,
        public readonly int $anchoOriginal,
        public readonly int $altoOriginal,
        public readonly int $pesoOriginal,
        public readonly int $pesoFinal,
    ) {
    }

    /**
     * URL pública relativa al host que atiende la petición. Se construye con
     * asset() y no con Storage::url() porque cada tienda se sirve además bajo
     * su dominio propio, y una URL fijada a APP_URL rompería las imágenes ahí.
     */
    public function urlPrincipal(): string
    {
        return $this->urlDe($this->principal);
    }

    public function urlDe(string $ruta): string
    {
        $base = $this->disco === 'uploads' ? 'uploads/' : 'storage/';

        return asset($base.ltrim($ruta, '/'));
    }

    /** Ahorro de peso frente al archivo que subió el usuario, en porcentaje. */
    public function ahorro(): int
    {
        return $this->pesoOriginal > 0
            ? (int) round(100 - ($this->pesoFinal * 100 / $this->pesoOriginal))
            : 0;
    }

    public function toArray(): array
    {
        return [
            'perfil'    => $this->perfil,
            'original'  => $this->original,
            'principal' => $this->principal,
            'url'       => $this->urlPrincipal(),
            'variantes' => $this->variantes,
            'ancho'     => $this->ancho,
            'alto'      => $this->alto,
        ];
    }
}

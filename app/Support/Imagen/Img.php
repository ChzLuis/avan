<?php

namespace App\Support\Imagen;

use Illuminate\Support\Str;

/**
 * Cómo se pinta en la tienda lo que generó el procesador.
 *
 * Las plantillas no tienen que saber qué variantes existen: piden la imagen
 * con su perfil y aquí se decide si sale un <picture> con AVIF/WebP y srcset
 * —cuando las variantes están en disco— o un <img> normal para las fotos
 * antiguas que aún no se han regenerado. Nunca se rompe una imagen.
 */
class Img
{
    /** Evita repetir la consulta a disco por la misma foto en una rejilla de 40. */
    private static array $memoria = [];

    /** Los tests cambian el disco bajo los pies; la memoria hay que vaciarla. */
    public static function olvidar(): void
    {
        self::$memoria = [];
    }

    /**
     * Etiqueta lista para insertar en una vista.
     *
     * @param  array{class?:string,alt?:string,sizes?:string,eager?:bool,style?:string,fit?:string}  $opciones
     */
    public static function etiqueta(?string $url, string $perfil = 'generico', array $opciones = []): string
    {
        if (! $url) {
            return '';
        }

        $perfilImg = PerfilImagen::de($perfil);
        $variantes = self::variantes($url, $perfilImg);

        $alt   = e($opciones['alt'] ?? '');
        $clase = isset($opciones['class']) ? ' class="'.e($opciones['class']).'"' : '';
        $estilo = isset($opciones['style']) ? ' style="'.e($opciones['style']).'"' : '';
        $sizes = e($opciones['sizes'] ?? $perfilImg->sizes);

        // La imagen del hero se carga cuanto antes; el resto espera a estar
        // cerca del viewport. Cargar 40 fotos de golpe es lo que hace que una
        // tienda tarde ocho segundos en un móvil con datos.
        $carga = ! empty($opciones['eager'])
            ? ' loading="eager" fetchpriority="high"'
            : ' loading="lazy" decoding="async"';

        // width/height reservan el hueco antes de que la foto llegue: sin
        // ellos el texto salta hacia abajo al cargar cada imagen.
        [$w, $h] = self::medidas($url, $perfilImg);
        $medidas = $w && $h ? ' width="'.$w.'" height="'.$h.'"' : '';

        $img = '<img src="'.e($url).'" alt="'.$alt.'"'.$clase.$estilo.$medidas.$carga.'>';

        if (! $variantes) {
            return $img;
        }

        $fuentes = '';
        foreach (['avif' => 'image/avif', 'webp' => 'image/webp'] as $ext => $mime) {
            $srcset = self::srcset($variantes, $ext);
            if ($srcset !== '') {
                $fuentes .= '<source type="'.$mime.'" srcset="'.$srcset.'" sizes="'.$sizes.'">';
            }
        }

        $srcsetBase = self::srcset($variantes, 'principal');
        if ($srcsetBase !== '') {
            $img = str_replace('<img ', '<img srcset="'.$srcsetBase.'" sizes="'.$sizes.'" ', $img);
        }

        return $fuentes === '' ? $img : '<picture>'.$fuentes.$img.'</picture>';
    }

    /**
     * La mejor variante procesada de una foto, o la foto tal cual si no la hay.
     *
     * Es el punto de entrada para todo lo que no puede montar un `<picture>`:
     * la rejilla que pinta Alpine desde JSON, la ficha, el carrito, el panel.
     * Devolver aquí la variante ya encuadrada es lo que hace que la tienda se
     * vea pareja aunque el marcado sea un `<img>` pelado.
     */
    public static function mejor(?string $url, string $perfil = 'producto'): ?string
    {
        if (! $url) {
            return $url;
        }

        $ruta = parse_url($url, PHP_URL_PATH);
        if (! $ruta || ! preg_match('/\.(jpe?g|png|webp)$/i', $ruta)) {
            return $url;
        }

        // Ya es una variante: no se le busca otra.
        if (preg_match('/-\d+\.[a-z0-9]+$/i', $ruta)) {
            return $url;
        }

        $clave = 'mejor:'.$ruta.'|'.$perfil;
        if (isset(self::$memoria[$clave])) {
            return self::$memoria[$clave];
        }

        $perfilImg = PerfilImagen::de($perfil);
        $prefijo = (string) preg_replace('/\.[a-z0-9]+$/i', '', $ruta);

        // Primero el ancho de referencia; si esa no está, se baja al resto de
        // mayor a menor antes que servir el original sin encuadrar.
        $orden = array_values(array_unique(array_merge(
            [$perfilImg->anchoPrincipal()],
            array_reverse($perfilImg->anchos)
        )));

        foreach ($orden as $ancho) {
            foreach (['jpg', 'png'] as $ext) {
                $candidato = $prefijo.'-'.$ancho.'.'.$ext;
                if (self::enDisco($candidato)) {
                    return self::$memoria[$clave] = self::aUrl($url, $candidato);
                }
            }
        }

        return self::$memoria[$clave] = $url;
    }

    /**
     * La variante de un ancho concreto, o la mejor que haya si esa no existe.
     *
     * La ficha de producto pide 800 para la foto grande y 400 para las
     * miniaturas: servir el original de 587 KB en un cuadrado de 60 px es
     * tirar el ancho de banda del cliente.
     */
    public static function deAncho(?string $url, int $ancho, string $perfil = 'producto'): ?string
    {
        if (! $url) {
            return $url;
        }

        $ruta = parse_url($url, PHP_URL_PATH);
        if (! $ruta || ! preg_match('/\.(jpe?g|png|webp)$/i', $ruta)) {
            return $url;
        }

        $base = (string) preg_replace('/(-\d+)?\.[a-z0-9]+$/i', '', $ruta);

        foreach (['jpg', 'png'] as $ext) {
            $candidato = $base.'-'.$ancho.'.'.$ext;
            if (self::enDisco($candidato)) {
                return self::aUrl($url, $candidato);
            }
        }

        return self::mejor($url, $perfil);
    }

    /**
     * Solo el srcset, para plantillas que ya tienen su propio <img> montado.
     */
    public static function srcsetDe(?string $url, string $perfil = 'generico', string $formato = 'principal'): string
    {
        if (! $url) {
            return '';
        }

        return self::srcset(self::variantes($url, PerfilImagen::de($perfil)), $formato);
    }

    private static function srcset(array $variantes, string $formato): string
    {
        $partes = [];
        foreach ($variantes as $ancho => $juego) {
            if (isset($juego[$formato])) {
                $partes[] = $juego[$formato].' '.$ancho.'w';
            }
        }

        return implode(', ', $partes);
    }

    /**
     * Busca en disco las variantes que acompañan a una URL ya procesada.
     *
     * El procesador escribe `carpeta/base-800.jpg`, así que desde cualquier
     * variante se deducen todas las demás. Una foto antigua no encaja con ese
     * patrón y devuelve vacío, que es la señal de "sírvela tal cual".
     */
    private static function variantes(string $url, PerfilImagen $perfil): array
    {
        $clave = $url.'|'.$perfil->nombre;
        if (isset(self::$memoria[$clave])) {
            return self::$memoria[$clave];
        }

        $ruta = parse_url($url, PHP_URL_PATH);
        if (! $ruta || ! preg_match('/\.(jpe?g|png|webp|avif)$/i', $ruta)) {
            return self::$memoria[$clave] = [];
        }

        // Las fotos nuevas ya vienen con el ancho en el nombre
        // (`foto-800.jpg`); las antiguas se regeneraron a partir del propio
        // archivo, así que su prefijo es el nombre entero.
        $prefijo = preg_match('/^(.*)-(\d+)$/', (string) preg_replace('/\.[a-z0-9]+$/i', '', $ruta), $m)
            ? $m[1]
            : (string) preg_replace('/\.[a-z0-9]+$/i', '', $ruta);
        $encontradas = [];

        foreach ($perfil->anchos as $ancho) {
            $juego = [];
            foreach (['jpg', 'png', 'webp', 'avif'] as $ext) {
                $candidato = $prefijo.'-'.$ancho.'.'.$ext;
                if (self::enDisco($candidato)) {
                    $juego[$ext] = self::aUrl($url, $candidato);
                }
            }
            $principal = $juego['jpg'] ?? $juego['png'] ?? null;
            if ($principal) {
                $juego['principal'] = $principal;
                $encontradas[$ancho] = $juego;
            }
        }

        // La foto original entra como candidata más grande cuando ella misma
        // no es una variante: en las fotos antiguas suele ser la única con
        // resolución de sobra, y sin ella el navegador se quedaría con la
        // pequeña incluso en una pantalla retina.
        if ($encontradas && ! preg_match('/-\d+\.[a-z0-9]+$/i', $ruta)) {
            [$anchoPropio] = self::medidas($url, $perfil);
            if ($anchoPropio && ! isset($encontradas[$anchoPropio])) {
                $encontradas[$anchoPropio] = ['principal' => $url];
            }
        }

        ksort($encontradas);

        // Con una sola candidata no hay nada que elegir: el srcset sobra.
        return self::$memoria[$clave] = count($encontradas) > 1 ? $encontradas : [];
    }

    private static function enDisco(string $rutaWeb): bool
    {
        [$disco, $relativa] = self::ubicar($rutaWeb);

        return $disco
            ? \Illuminate\Support\Facades\Storage::disk($disco)->exists($relativa)
            : is_file(public_path(ltrim($rutaWeb, '/')));
    }

    /**
     * Traduce una ruta web al disco que la sirve.
     *
     * /storage/... y /uploads/... son las dos puertas por las que salen las
     * imágenes de esta web; el resto (una foto suelta en public/) se mira
     * directamente en el sistema de archivos.
     *
     * @return array{0:?string,1:string}
     */
    private static function ubicar(string $rutaWeb): array
    {
        $limpia = ltrim($rutaWeb, '/');

        foreach (['storage/' => 'public', 'uploads/' => 'uploads'] as $prefijo => $disco) {
            if (str_starts_with($limpia, $prefijo)) {
                return [$disco, substr($limpia, strlen($prefijo))];
            }
        }

        return [null, $limpia];
    }

    /** Mantiene el esquema/host de la URL original al cambiar de variante. */
    private static function aUrl(string $urlOriginal, string $rutaWeb): string
    {
        $partes = parse_url($urlOriginal);
        if (empty($partes['host'])) {
            return $rutaWeb;
        }

        return ($partes['scheme'] ?? 'https').'://'.$partes['host'].$rutaWeb;
    }

    /** Ancho y alto reales de la variante que se sirve por defecto. */
    private static function medidas(string $url, PerfilImagen $perfil): array
    {
        $ruta = parse_url($url, PHP_URL_PATH);
        if (! $ruta) {
            return [null, null];
        }

        $clave = 'medidas:'.$ruta;
        if (isset(self::$memoria[$clave])) {
            return self::$memoria[$clave];
        }

        // Con proporción fija basta la aritmética: no hace falta abrir nada.
        if ($perfil->proporcion && preg_match('/-(\d+)\.[a-z]+$/i', $ruta, $m)) {
            $w = (int) $m[1];

            return self::$memoria[$clave] = [$w, (int) round($w / $perfil->proporcion)];
        }

        [$disco, $relativa] = self::ubicar($ruta);
        $abs = $disco
            ? \Illuminate\Support\Facades\Storage::disk($disco)->path($relativa)
            : public_path($relativa);
        $info = is_file($abs) ? @getimagesize($abs) : false;

        return self::$memoria[$clave] = $info ? [$info[0], $info[1]] : [null, null];
    }

    /**
     * URL pública de una ruta guardada en el disco público, aceptando también
     * las URLs absolutas que ya hay en la base de datos.
     */
    public static function url(?string $rutaOUrl): ?string
    {
        if (! $rutaOUrl) {
            return null;
        }

        return Str::startsWith($rutaOUrl, ['http://', 'https://', '/'])
            ? $rutaOUrl
            : asset('storage/'.$rutaOUrl);
    }
}

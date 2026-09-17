<?php

namespace App\Storefront;

/**
 * Los cinco DISEÑOS DE INICIO del Constructor.
 *
 * El problema que resuelve: todas las tiendas se veían iguales. Cambiaban el
 * color y la foto, pero el lenguaje visual —el orden de los bloques, cuánto
 * respira la página, si la portada es una foto a sangre o una composición
 * partida— era siempre el mismo, y eso es justo lo que hace que una tienda
 * parezca "otra web del mismo sistema".
 *
 * La decisión de diseño clave es que un preset NO es una página. Es una capa
 * fina sobre lo que ya existe:
 *
 *   · el ORDEN recomendado de los bloques (`orden`), y
 *   · una PIEL de CSS (`resources/views/components/storefront-home-skins`)
 *     que reinterpreta esos mismos bloques.
 *
 * El contenido sigue viviendo donde siempre: en `store_sections`, un registro
 * por bloque, con su contenido, su interruptor y su visibilidad por
 * dispositivo. Por eso cambiar de preset NO puede perder nada — no se toca ni
 * una fila de datos, solo se reordena y se repinta. Y por eso es reversible.
 *
 * `orden` solo se aplica cuando el negocio elige un preset a mano; si nunca
 * eligió, manda el orden que ya tenga guardado. Un bloque que el negocio
 * apagó sigue apagado: el preset ordena, no enciende.
 */
final class HomePresets
{
    /** Preset por defecto para las tiendas que existían antes de esto. */
    public const POR_DEFECTO = '1';

    /**
     * Los cinco diseños.
     *
     * `orden` lista los bloques por su peso: los que no aparecen conservan su
     * posición relativa detrás de los listados, así un bloque nuevo del sistema
     * nunca desaparece de la portada por no estar en esta lista.
     */
    public static function todos(): array
    {
        return [
            '1' => [
                'clave' => '1',
                'nombre' => 'Comercial Clásico',
                'ideal' => 'Ideal para cualquier negocio',
                'resumen' => 'El más universal: portada partida, beneficios, categorías y productos. Si no sabes cuál elegir, este.',
                'heroA' => 'Texto a la izquierda, foto a la derecha',
                'heroB' => 'Foto a sangre con el texto encima',
                'orden' => [
                    'hero', 'benefits', 'featured_categories', 'featured_products',
                    'about_preview', 'info_strip', 'brands', 'testimonials',
                    'cta_banner', 'faq',
                ],
            ],
            '2' => [
                'clave' => '2',
                'nombre' => 'Producto Primero',
                'ideal' => 'Ideal para ecommerce',
                'resumen' => 'La portada ocupa poco y el catálogo entra casi de inmediato. Para tiendas con muchos productos.',
                'heroA' => 'Banda horizontal compacta',
                'heroB' => 'Foto con panel de contenido al costado',
                'orden' => [
                    'hero', 'featured_categories', 'featured_products', 'discounts',
                    'info_strip', 'daily_offer', 'about_preview', 'brands',
                    'cta_banner', 'faq',
                ],
            ],
            '3' => [
                'clave' => '3',
                'nombre' => 'Marca & Historia',
                'ideal' => 'Ideal para empresas con trayectoria',
                'resumen' => 'Institucional y con más peso editorial: indicadores, historia y valores antes que el catálogo.',
                'heroA' => 'Editorial dividido',
                'heroB' => 'Panorámica con bloque de texto',
                'orden' => [
                    'hero', 'about_preview', 'info_strip', 'featured_categories',
                    'testimonials', 'featured_products', 'gallery', 'brands',
                    'cta_banner', 'faq',
                ],
            ],
            '4' => [
                'clave' => '4',
                'nombre' => 'Minimal Premium',
                'ideal' => 'Ideal para marcas visuales',
                'resumen' => 'Editorial y con mucho aire: la foto manda, casi no hay cajas. Para muebles, moda, decoración e iluminación.',
                'heroA' => 'Fotografía a todo el ancho',
                'heroB' => 'Split editorial minimalista',
                'orden' => [
                    'hero', 'collection_showcase', 'featured_categories',
                    'featured_products', 'about_preview', 'gallery',
                    'media_banner', 'cta_banner', 'brands', 'faq',
                ],
            ],
            '5' => [
                'clave' => '5',
                'nombre' => 'Mayorista / Corporativo',
                'ideal' => 'Ideal para distribuidores',
                'resumen' => 'Técnico y corporativo: soluciones, marcas y cobertura. Para importadores, ferreterías y venta B2B.',
                'heroA' => 'Corporativo dividido',
                'heroB' => 'Fondo industrial con panel de contenido',
                'orden' => [
                    'hero', 'benefits', 'featured_categories', 'info_strip',
                    'brands', 'featured_products', 'about_preview',
                    'locations', 'cta_banner', 'faq',
                ],
            ],
        ];
    }

    /** Normaliza cualquier valor guardado a una clave válida. */
    public static function clave(mixed $valor): string
    {
        $clave = trim((string) $valor);

        return isset(self::todos()[$clave]) ? $clave : self::POR_DEFECTO;
    }

    /** La variante de portada: 'a' o 'b'. */
    public static function variante(mixed $valor): string
    {
        return strtolower(trim((string) $valor)) === 'b' ? 'b' : 'a';
    }

    public static function preset(mixed $valor): array
    {
        return self::todos()[self::clave($valor)];
    }

    /**
     * Ordena los bloques según el preset.
     *
     * Solo reordena; no filtra ni enciende nada. Un bloque fuera de la lista
     * del preset se va al final, para que jamás se pierda de vista por no
     * estar contemplado aquí.
     *
     * La cola se desempata con el orden del registro canónico
     * (`StorefrontSections::COMPONENTS`) y NO con la posición que traía la
     * colección. Es la diferencia entre que la función sea estable o no:
     * desempatando por la posición de entrada, el resultado dependía de qué
     * presets se hubieran visitado antes, y pasar por 1 → 4 → 2 → 1 devolvía
     * una portada distinta de la que da el 1 directo. Anclado al registro, un
     * preset produce siempre el mismo orden y volver atrás devuelve exactamente
     * lo que había.
     */
    public static function ordenar(\Illuminate\Support\Collection $secciones, mixed $clave): \Illuminate\Support\Collection
    {
        $orden = array_flip(self::preset($clave)['orden']);
        $registro = array_flip(array_keys(\App\Support\StorefrontSections::COMPONENTS));
        $cola = count($orden);
        $finDelRegistro = count($registro);

        return $secciones
            ->values()
            ->sortBy(fn ($seccion) => [
                $orden[$seccion->component] ?? $cola,
                $registro[$seccion->component] ?? $finDelRegistro,
            ])
            ->values();
    }
}

<?php

namespace App\Storefront;

/**
 * Presets por rubro (B2/B7). Fuente única y mantenible: nada de payloads
 * en vistas. Aplicar un preset escribe SOLO claves de settings en BORRADOR
 * (el usuario ve el efecto en el preview y decide publicar); no toca
 * productos ni categorías existentes.
 */
class StagePresets
{
    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        $p = fn (string $label, string $template, string $primary, string $secondary, string $font, string $saleMode, array $texts, array $categories) => [
            'label' => $label,
            'template' => $template,
            'sale_mode' => $saleMode,                 // direct | quote
            'suggested_categories' => $categories,     // informativas (no se crean solas)
            'settings' => [
                'catalog_template' => $template,
                'primary_color' => $primary,
                'secondary_color' => $secondary,
                'font' => $font,
                'store_mode' => $saleMode,
                'hero_title' => $texts[0],
                'hero_subtitle' => $texts[1],
                'announcement_text' => $texts[2],
            ],
        ];

        return [
            'tecnologia' => $p('Tecnología', 'ecommerce', '#2563eb', '#0f172a', 'Inter', 'direct',
                ['Tecnología para cada necesidad', 'Equipos con garantía y soporte real.', 'ENVÍOS A TODO EL PAÍS'],
                ['Laptops', 'Computadoras', 'Accesorios', 'Impresoras']),
            'ferreteria' => $p('Ferretería', 'ecommerce', '#ea580c', '#1c1917', 'Inter', 'direct',
                ['Todo para tu obra y tu hogar', 'Herramientas y materiales con stock real.', 'RECOJO EN TIENDA EL MISMO DÍA'],
                ['Herramientas', 'Eléctricos', 'Gasfitería', 'Pinturas']),
            'muebles' => $p('Muebles', 'ecommerce', '#b45309', '#292524', 'Poppins', 'quote',
                ['Muebles que hacen hogar', 'Fabricación propia y a medida.', 'COTIZA SIN COMPROMISO'],
                ['Salas', 'Dormitorios', 'Comedores', 'Oficina']),
            'ropa' => $p('Ropa', 'ecommerce', '#db2777', '#1f2937', 'Poppins', 'direct',
                ['Estilo que te queda', 'Nueva colección cada temporada.', 'CAMBIOS SIN COSTO EN 7 DÍAS'],
                ['Mujer', 'Hombre', 'Accesorios', 'Calzado']),
            'bebes' => $p('Ropa de bebé', 'ecommerce', '#38bdf8', '#334155', 'Poppins', 'direct',
                ['Lo más suave para tu bebé', 'Algodón y ternura en cada prenda.', 'ENVÍO GRATIS DESDE S/ 99'],
                ['Recién nacido', 'Niñas', 'Niños', 'Ajuar']),
            'restaurante' => $p('Restaurante', 'direct', '#dc2626', '#1c1917', 'Poppins', 'direct',
                ['Sabor que llega a tu mesa', 'Pide por WhatsApp y recíbelo caliente.', 'DELIVERY 30-45 MIN'],
                ['Menú del día', 'Platos a la carta', 'Bebidas', 'Postres']),
            'veterinaria' => $p('Veterinaria', 'ecommerce', '#16a34a', '#14532d', 'Inter', 'direct',
                ['Cuidamos a tu engreído', 'Alimentos, accesorios y atención.', 'AGENDA SU CITA POR WHATSAPP'],
                ['Alimentos', 'Accesorios', 'Higiene', 'Farmacia']),
            'optica' => $p('Óptica', 'ecommerce', '#0891b2', '#0f172a', 'Inter', 'quote',
                ['Ve mejor, luce mejor', 'Monturas y lentes con medida exacta.', 'EXAMEN VISUAL GRATUITO'],
                ['Monturas', 'Lentes de sol', 'Lentes de contacto', 'Accesorios']),
            'servicios' => $p('Servicios', 'direct', '#7c3aed', '#1e1b4b', 'Inter', 'quote',
                ['Soluciones profesionales', 'Cuéntanos qué necesitas y te cotizamos.', 'ATENCIÓN DE LUNES A SÁBADO'],
                ['Servicios principales', 'Mantenimiento', 'Proyectos', 'Emergencias']),
            'mayorista' => $p('Mayorista', 'ecommerce', '#0891b2', '#0f172a', 'Inter', 'direct',
                ['Precios de mayorista, trato directo', 'Compra por volumen con precios escalonados.', 'DESCUENTOS POR CANTIDAD'],
                ['Abarrotes', 'Limpieza', 'Bebidas', 'Descartables']),
            'electricidad' => $p('Electricidad e iluminación', 'ecommerce', '#f59e0b', '#111827', 'Inter', 'direct',
                ['Ilumina y protege tus espacios', 'Reflectores y material eléctrico con garantía.', 'ASESORÍA TÉCNICA POR WHATSAPP'],
                ['Reflectores', 'Iluminación LED', 'Cables', 'Accesorios eléctricos']),
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Paquete de DISEÑO por rubro: identidad visual completa (tema, variantes
     * de secciones y estructura de portada). Complementa el preset de settings
     * de all(): all() da contenido/colores base; design() da la personalidad.
     *
     * Todo se aplica EN BORRADOR y después es 100% editable en el diseñador.
     * Las claves de 'settings' son settings normales del proyecto; 'sections'
     * define qué componentes del registro activar, con variante y semilla de
     * contenido (solo si el proyecto no los configuró antes).
     */
    public static function design(string $key): ?array
    {
        $designs = [
            'tecnologia' => [
                'settings' => [
                    'theme_preset' => 'tech-dark',
                    'header_style' => 'dark',
                    'footer_style' => 'accent',
                    'featured_categories_style' => 'showcase',
                    'trust_section_style' => 'tiles',
                    'flash_sale_style' => 'full',
                    'product_card_style' => 'tech',
                    'section_style_preset' => 'modern',
                    'hero_transition' => 'fade',
                ],
                'sections' => [
                    'order' => ['hero', 'brands', 'featured_categories', 'collection_showcase', 'daily_offer', 'discounts', 'featured_products', 'benefits', 'testimonials', 'faq', 'wa_advisory'],
                    'enable' => ['brands', 'collection_showcase', 'wa_advisory'],
                    'seed' => [
                        'collection_showcase' => [
                            'variant' => 'mosaic',
                            'content' => ['title' => 'Encuentra tu equipo ideal', 'subtitle' => 'Recomendados según cómo lo vas a usar', 'columns' => 4, 'items' => []],
                        ],
                        'wa_advisory' => [
                            'variant' => 'band',
                            'content' => ['title' => '¿No sabes qué equipo elegir?', 'subtitle' => 'Cuéntanos qué necesitas (juegos, oficina, estudio o diseño) y te recomendamos la mejor opción.', 'button_text' => 'Pedir asesoría', 'message' => 'Hola, necesito asesoría para elegir un equipo.'],
                        ],
                    ],
                ],
            ],
            'electricidad' => [
                'settings' => [
                    'theme_preset' => 'high-contrast',
                    'header_style' => 'accent',
                    'footer_style' => 'classic',
                    'featured_categories_style' => 'overlay',
                    'trust_section_style' => 'band',
                    'product_card_style' => 'contrast',
                    'section_style_preset' => 'modern',
                ],
                'sections' => [
                    'order' => ['hero', 'featured_categories', 'collection_showcase', 'featured_products', 'benefits', 'wa_advisory', 'gallery', 'brands', 'testimonials', 'faq'],
                    'enable' => ['collection_showcase', 'wa_advisory'],
                    'seed' => [
                        'collection_showcase' => [
                            'variant' => 'banners',
                            'content' => ['title' => 'Soluciones por espacio', 'subtitle' => 'Hogar, comercio, industria y exteriores', 'columns' => 2, 'items' => []],
                        ],
                        'wa_advisory' => [
                            'variant' => 'card',
                            'content' => ['title' => 'Asesoría técnica gratuita', 'subtitle' => 'Te ayudamos a calcular potencia, voltaje y cantidad de equipos para tu proyecto.', 'button_text' => 'Consultar por WhatsApp', 'message' => 'Hola, necesito asesoría técnica para un proyecto de iluminación.'],
                        ],
                    ],
                ],
            ],
            'bebes' => [
                'settings' => [
                    'theme_preset' => 'soft-kids',
                    'header_style' => 'pill',
                    'footer_style' => 'light',
                    'featured_categories_style' => 'circles',
                    'trust_section_style' => 'icons-top',
                    'product_card_style' => 'soft',
                    'section_style_preset' => 'modern',
                ],
                'sections' => [
                    'order' => ['hero', 'collection_showcase', 'featured_categories', 'featured_products', 'benefits', 'testimonials', 'gallery', 'faq', 'wa_advisory'],
                    'enable' => ['collection_showcase'],
                    'seed' => [
                        'collection_showcase' => [
                            'variant' => 'circles',
                            'content' => ['title' => 'Compra por perfil', 'subtitle' => 'Encuentra todo para tu pequeño o pequeña', 'columns' => 3, 'items' => []],
                        ],
                    ],
                ],
            ],
            'muebles' => [
                'settings' => [
                    'theme_preset' => 'warm-home',
                    'header_style' => 'line',
                    'footer_style' => 'minimal',
                    'featured_categories_style' => 'editorial',
                    'trust_section_style' => 'inline',
                    'product_card_style' => 'elegant',
                    'section_style_preset' => 'modern',
                ],
                'sections' => [
                    'order' => ['hero', 'collection_showcase', 'featured_categories', 'featured_products', 'daily_offer', 'discounts', 'benefits', 'gallery', 'testimonials', 'faq', 'cta_banner'],
                    'enable' => ['collection_showcase', 'cta_banner'],
                    'seed' => [
                        'collection_showcase' => [
                            'variant' => 'ambient',
                            'content' => ['title' => 'Compra por ambiente', 'subtitle' => 'Arma tu sala, comedor, dormitorio u oficina completos', 'columns' => 3, 'items' => []],
                        ],
                        'cta_banner' => [
                            'variant' => 'split',
                            'content' => ['title' => 'Completa tu espacio', 'subtitle' => 'Combina muebles y electrodomésticos con ayuda de un asesor.', 'button_text' => 'Cotizar mi ambiente', 'button_url' => '#catalogo', 'background_color' => '#292018'],
                        ],
                    ],
                ],
            ],
        ];

        return $designs[$key] ?? null;
    }
}

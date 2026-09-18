<?php

namespace App\Modules\Tienda\Support;

use App\Models\Project;

/**
 * Texto de ejemplo para las páginas de contenido que el negocio aún no llenó.
 *
 * POR QUÉ NO ES "LOREM IPSUM"
 * Estas páginas las ve el CLIENTE FINAL de la tienda. El latín de imprenta se
 * lee como un error y deja peor al negocio que una página vacía. Por eso el
 * relleno es español corriente, sirve tal cual si el comerciante no lo cambia
 * y, sobre todo, **no inventa hechos**: nada de años de experiencia, cifras,
 * premios ni sedes. Solo afirmaciones ciertas para cualquier negocio, con su
 * nombre real insertado.
 *
 * El panel marca este texto como "de ejemplo" para que se sepa que hay que
 * reemplazarlo; la tienda lo muestra con el diseño completo para que una
 * página a medio configurar no se vea rota.
 */
class ContenidoEjemplo
{
    /** Ajuste con el que un negocio puede apagar el relleno. */
    public const AJUSTE = 'pages_placeholder';

    /**
     * Campos de ejemplo por página. Se usan solo cuando el campo real está
     * vacío; nunca sustituyen a lo que el comerciante escribió.
     *
     * @return array<string,string>
     */
    public static function campos(string $clave, Project $project): array
    {
        $negocio = trim($project->name) ?: 'nuestra tienda';

        return match ($clave) {
            'nosotros' => [
                'body' => "En {$negocio} nos dedicamos a ofrecer productos de calidad y una atención cercana. "
                    ."Cuéntanos qué necesitas y te ayudamos a encontrarlo.",
                'history' => "{$negocio} nació con una idea sencilla: que comprar sea fácil, claro y sin sorpresas. "
                    ."Aquí puedes contar cómo empezó el negocio, qué te llevó a abrirlo y cómo ha crecido hasta hoy.",
                'mission' => "Acompañar a cada cliente con productos que cumplan lo que prometen y un trato "
                    ."honesto, antes y después de la compra.",
                'vision' => "Ser la opción de confianza de nuestra zona: el sitio al que la gente vuelve y que "
                    ."recomienda a los suyos.",
                'values' => "Honestidad en lo que ofrecemos\nAtención cercana y sin prisas\n"
                    ."Calidad comprobada en cada producto\nCumplir lo acordado, siempre",
                'team' => "Detrás de {$negocio} hay un equipo que conoce lo que vende y responde cuando lo "
                    ."necesitas. Presenta aquí a las personas que atienden a tus clientes.",
                'button_text' => 'Escríbenos',
            ],
            'terminos' => [
                'body' => "Estas son las condiciones de compra de {$negocio}. Escribe aquí cómo se realizan los "
                    ."pedidos, los plazos y formas de entrega, los medios de pago aceptados y las condiciones "
                    ."de cambio o devolución.\n\n"
                    ."Conviene revisar este texto con calma: es el acuerdo que aceptan tus clientes al comprar.",
            ],
            'privacidad' => [
                'body' => "En {$negocio} solo pedimos los datos necesarios para atender tu pedido y comunicarnos "
                    ."contigo: nombre, teléfono y, cuando hace falta, la dirección de entrega.\n\n"
                    ."Describe aquí cómo guardas esos datos, durante cuánto tiempo, con quién los compartes "
                    ."(por ejemplo, la empresa de reparto) y cómo puede alguien pedir que los elimines.",
            ],
            default => [
                'body' => "Escribe aquí el contenido de esta página. Mientras tanto, tus visitantes ven este "
                    ."texto de ejemplo con el diseño ya aplicado.",
            ],
        };
    }

    /**
     * Contenido de la página listo para pintar: lo que escribió el negocio y,
     * en los huecos, el ejemplo. Devuelve también qué campos salieron de
     * ejemplo, para poder avisarlo en el panel.
     *
     * @param  array<string,mixed>|null  $contenido
     * @return array{0: array<string,mixed>, 1: array<int,string>}
     */
    public static function completar(?array $contenido, string $clave, Project $project, bool $activo = true): array
    {
        $contenido ??= [];
        if (! $activo) {
            return [$contenido, []];
        }

        $ejemplo = self::campos($clave, $project);
        $rellenados = [];

        foreach ($ejemplo as $campo => $texto) {
            if (blank($contenido[$campo] ?? null)) {
                $contenido[$campo] = $texto;
                $rellenados[] = $campo;
            }
        }

        return [$contenido, $rellenados];
    }

    /** ¿El negocio quiere el relleno? Encendido salvo que lo apague. */
    public static function activoEn(Project $project): bool
    {
        $valor = $project->settings()->where('key', self::AJUSTE)->value('value');

        return $valor === null || $valor === '1';
    }
}

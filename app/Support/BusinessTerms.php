<?php

namespace App\Support;

use App\Models\Project;

/**
 * Terminología adaptada por rubro.
 *
 * Centraliza los "términos" que cambian según el tipo de negocio, para que las
 * vistas no tengan lenguaje de restaurante ("carta", "mesa") hardcodeado.
 *
 * Uso: BusinessTerms::for($project)['catalogo']  // "Carta" | "Catálogo" | ...
 */
class BusinessTerms
{
    /**
     * Diccionario por rubro. Claves de términos:
     *  - catalogo:      cómo se llama el listado (Carta / Catálogo / Menú)
     *  - catalogo_qr:   título de la pantalla QR
     *  - punto:         dónde consume el cliente (mesa / local / —)
     *  - tagline:       frase del flyer del QR
     *  - modo_pedidos:  nombre de la opción de recibir pedidos por QR
     *  - modo_pedidos_desc: descripción de esa opción
     *  - badge_pedidos: etiqueta corta (QR por mesa / QR de pedidos)
     *  - accion:        verbo de acción del cliente (pide / solicita / reserva)
     */
    protected static function dict(): array
    {
        return [
            // ── Gastronomía ─────────────────────────────────────────
            'restaurante' => [
                'catalogo'      => 'Carta',
                'catalogo_qr'   => 'Carta QR',
                'punto'         => 'mesa',
                'tagline'       => 'Escanea y pide desde tu mesa',
                'modo_pedidos'  => 'Carta + Pedidos desde mesa',
                'modo_pedidos_desc' => 'Los clientes ven la carta y hacen pedidos desde su celular.',
                'badge_pedidos' => 'QR por mesa',
                'accion'        => 'pide',
            ],
            'cafeteria' => [
                'catalogo'      => 'Menú',
                'catalogo_qr'   => 'Menú QR',
                'punto'         => 'mesa',
                'tagline'       => 'Escanea y pide desde tu mesa',
                'modo_pedidos'  => 'Menú + Pedidos desde mesa',
                'modo_pedidos_desc' => 'Los clientes ven el menú y hacen pedidos desde su celular.',
                'badge_pedidos' => 'QR por mesa',
                'accion'        => 'pide',
            ],

            // ── Servicios ───────────────────────────────────────────
            'lavanderia' => [
                'catalogo'      => 'Catálogo',
                'catalogo_qr'   => 'Catálogo QR',
                'punto'         => 'local',
                'tagline'       => 'Escanea y solicita tu servicio',
                'modo_pedidos'  => 'Catálogo + Pedidos por QR',
                'modo_pedidos_desc' => 'Los clientes ven los servicios y solicitan su pedido desde el celular.',
                'badge_pedidos' => 'QR de pedidos',
                'accion'        => 'solicita',
            ],
            'peluqueria' => [
                'catalogo'      => 'Servicios',
                'catalogo_qr'   => 'Servicios QR',
                'punto'         => 'local',
                'tagline'       => 'Escanea y reserva tu cita',
                'modo_pedidos'  => 'Servicios + Reservas por QR',
                'modo_pedidos_desc' => 'Los clientes ven los servicios y reservan su cita desde el celular.',
                'badge_pedidos' => 'QR de reservas',
                'accion'        => 'reserva',
            ],
            'clinica' => [
                'catalogo'      => 'Servicios',
                'catalogo_qr'   => 'Servicios QR',
                'punto'         => 'consultorio',
                'tagline'       => 'Escanea y reserva tu cita',
                'modo_pedidos'  => 'Servicios + Reservas por QR',
                'modo_pedidos_desc' => 'Los pacientes ven los servicios y reservan su cita desde el celular.',
                'badge_pedidos' => 'QR de citas',
                'accion'        => 'reserva',
            ],
            'taller' => [
                'catalogo'      => 'Servicios',
                'catalogo_qr'   => 'Servicios QR',
                'punto'         => 'taller',
                'tagline'       => 'Escanea y solicita tu servicio',
                'modo_pedidos'  => 'Servicios + Solicitudes por QR',
                'modo_pedidos_desc' => 'Los clientes ven los servicios y solicitan atención desde el celular.',
                'badge_pedidos' => 'QR de servicios',
                'accion'        => 'solicita',
            ],
            'veterinaria' => [
                'catalogo'      => 'Catálogo',
                'catalogo_qr'   => 'Catálogo QR',
                'punto'         => 'local',
                'tagline'       => 'Escanea y reserva tu cita',
                'modo_pedidos'  => 'Catálogo + Reservas por QR',
                'modo_pedidos_desc' => 'Los clientes ven productos y servicios y reservan desde el celular.',
                'badge_pedidos' => 'QR de reservas',
                'accion'        => 'reserva',
            ],
            'gimnasio' => [
                'catalogo'      => 'Planes',
                'catalogo_qr'   => 'Planes QR',
                'punto'         => 'local',
                'tagline'       => 'Escanea y elige tu plan',
                'modo_pedidos'  => 'Planes + Inscripciones por QR',
                'modo_pedidos_desc' => 'Los clientes ven los planes y se inscriben desde el celular.',
                'badge_pedidos' => 'QR de inscripción',
                'accion'        => 'inscríbete',
            ],
            'inmobiliaria' => [
                'catalogo'      => 'Propiedades',
                'catalogo_qr'   => 'Propiedades QR',
                'punto'         => 'oficina',
                'tagline'       => 'Escanea y explora propiedades',
                'modo_pedidos'  => 'Propiedades + Consultas por QR',
                'modo_pedidos_desc' => 'Los interesados ven propiedades y consultan desde el celular.',
                'badge_pedidos' => 'QR de consultas',
                'accion'        => 'consulta',
            ],
            'educacion' => [
                'catalogo'      => 'Cursos',
                'catalogo_qr'   => 'Cursos QR',
                'punto'         => 'academia',
                'tagline'       => 'Escanea y matricúlate',
                'modo_pedidos'  => 'Cursos + Matrículas por QR',
                'modo_pedidos_desc' => 'Los alumnos ven los cursos y se matriculan desde el celular.',
                'badge_pedidos' => 'QR de matrícula',
                'accion'        => 'matricúlate',
            ],

            // ── Retail / Tienda / WhatsApp ──────────────────────────
            'retail' => [
                'catalogo'      => 'Catálogo',
                'catalogo_qr'   => 'Catálogo QR',
                'punto'         => 'tienda',
                'tagline'       => 'Escanea y haz tu pedido',
                'modo_pedidos'  => 'Catálogo + Pedidos por QR',
                'modo_pedidos_desc' => 'Los clientes ven el catálogo y hacen su pedido desde el celular.',
                'badge_pedidos' => 'QR de pedidos',
                'accion'        => 'compra',
            ],
            'farmacia' => [
                'catalogo'      => 'Catálogo',
                'catalogo_qr'   => 'Catálogo QR',
                'punto'         => 'local',
                'tagline'       => 'Escanea y haz tu pedido',
                'modo_pedidos'  => 'Catálogo + Pedidos por QR',
                'modo_pedidos_desc' => 'Los clientes ven los productos y hacen su pedido desde el celular.',
                'badge_pedidos' => 'QR de pedidos',
                'accion'        => 'pide',
            ],
            'whatsapp' => [
                'catalogo'      => 'Catálogo',
                'catalogo_qr'   => 'Catálogo QR',
                'punto'         => 'local',
                'tagline'       => 'Escanea y haz tu pedido',
                'modo_pedidos'  => 'Catálogo + Pedidos por QR',
                'modo_pedidos_desc' => 'Los clientes ven el catálogo y hacen su pedido por WhatsApp.',
                'badge_pedidos' => 'QR de pedidos',
                'accion'        => 'pide',
            ],
        ];
    }

    /** Términos por defecto (rubro genérico / "otro"). */
    protected static function defaults(): array
    {
        return [
            'catalogo'      => 'Catálogo',
            'catalogo_qr'   => 'Catálogo QR',
            'punto'         => 'local',
            'tagline'       => 'Escanea y haz tu pedido',
            'modo_pedidos'  => 'Catálogo + Pedidos por QR',
            'modo_pedidos_desc' => 'Los clientes ven el catálogo y hacen su pedido desde el celular.',
            'badge_pedidos' => 'QR de pedidos',
            'accion'        => 'pide',
        ];
    }

    /** Términos completos para un proyecto (según su rubro). */
    public static function for(Project $project): array
    {
        $cat = $project->category ?? '';
        return array_merge(static::defaults(), static::dict()[$cat] ?? []);
    }

    /** ¿El rubro usa el concepto de "mesa"? (solo gastronomía) */
    public static function usaMesas(Project $project): bool
    {
        return in_array($project->category, ['restaurante', 'cafeteria']);
    }
}

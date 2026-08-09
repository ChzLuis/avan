<?php

namespace App\Support;

use App\Models\Project;

/**
 * Flujo de estados de pedidos, configurable POR RUBRO.
 *
 * Cada rubro (category del proyecto) trae un catálogo de estados sugeridos.
 * El usuario activa/desactiva estados y ajusta tiempos desde el panel admin.
 * La config se guarda en project_settings: 'order_flow' (estados activos) y
 * 'order_flow_config' (config por estado). Por retrocompatibilidad también se
 * leen las claves antiguas 'laundry_flow' / 'laundry_flow_config'.
 *
 * El estado vive en orders.laundry_status (+ laundry_status_at). Se conserva ese
 * nombre de columna para no romper datos existentes.
 *
 * Cada estado: label, icon, color, notify (aviso WhatsApp), core (no desactivable).
 */
class OrderFlow
{
    /**
     * ¿Este rubro tiene flujo de estados configurable?
     * TODOS los rubros con categoría lo tienen: los definidos usan su catálogo
     * propio, y los demás (ej. 'otro') usan el flujo genérico.
     */
    public static function supportsFlow(?string $category): bool
    {
        return !empty($category);
    }

    /** Catálogos de estados por rubro. */
    protected static function catalogsByCategory(): array
    {
        return [

            // ── LAVANDERÍA ──────────────────────────────────────────────
            'lavanderia' => [
                'recibido'        => ['label'=>'Recibido','icon'=>'📥','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'cotizado'        => ['label'=>'Cotizado','icon'=>'🧾','color'=>'#8B5CF6','notify'=>false,'core'=>false],
                'lavando'         => ['label'=>'Lavando','icon'=>'🧼','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'secando'         => ['label'=>'Secando','icon'=>'🌀','color'=>'#06B6D4','notify'=>false,'core'=>false],
                'planchando'      => ['label'=>'Planchando','icon'=>'👔','color'=>'#0891B2','notify'=>false,'core'=>false],
                'control_calidad' => ['label'=>'Control de calidad','icon'=>'🔍','color'=>'#6366F1','notify'=>false,'core'=>false],
                'listo'           => ['label'=>'Listo','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'en_reparto'      => ['label'=>'En reparto','icon'=>'🚚','color'=>'#F97316','notify'=>false,'core'=>false],
                'entregado'       => ['label'=>'Entregado','icon'=>'📦','color'=>'#059669','notify'=>true,'core'=>true],
                'anulado'         => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── RESTAURANTE / CAFETERÍA ─────────────────────────────────
            'restaurante' => [
                'recibido'   => ['label'=>'Recibido','icon'=>'📥','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'en_cocina'  => ['label'=>'En cocina','icon'=>'🍳','color'=>'#EF4444','notify'=>false,'core'=>false],
                'emplatando' => ['label'=>'Emplatando','icon'=>'🍽️','color'=>'#F97316','notify'=>false,'core'=>false],
                'listo'      => ['label'=>'Listo','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'en_reparto' => ['label'=>'En reparto','icon'=>'🛵','color'=>'#3B82F6','notify'=>true,'core'=>false],
                'entregado'  => ['label'=>'Entregado','icon'=>'📦','color'=>'#059669','notify'=>false,'core'=>true],
                'anulado'    => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],
            'cafeteria' => [
                'recibido'   => ['label'=>'Recibido','icon'=>'📥','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'preparando' => ['label'=>'Preparando','icon'=>'☕','color'=>'#B45309','notify'=>false,'core'=>false],
                'listo'      => ['label'=>'Listo','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'entregado'  => ['label'=>'Entregado','icon'=>'📦','color'=>'#059669','notify'=>false,'core'=>true],
                'anulado'    => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── TALLER / SERVICIO TÉCNICO ───────────────────────────────
            'taller' => [
                'recibido'    => ['label'=>'Recibido','icon'=>'📥','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'diagnostico' => ['label'=>'Diagnóstico','icon'=>'🔍','color'=>'#8B5CF6','notify'=>false,'core'=>false],
                'cotizado'    => ['label'=>'Cotizado','icon'=>'🧾','color'=>'#6366F1','notify'=>true,'core'=>false],
                'aprobado'    => ['label'=>'Aprobado','icon'=>'👍','color'=>'#0891B2','notify'=>false,'core'=>false],
                'reparando'   => ['label'=>'Reparando','icon'=>'🔧','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'listo'       => ['label'=>'Listo','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'entregado'   => ['label'=>'Entregado','icon'=>'📦','color'=>'#059669','notify'=>false,'core'=>true],
                'anulado'     => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── PELUQUERÍA / SALÓN DE BELLEZA ───────────────────────────
            'peluqueria' => [
                'reservado'   => ['label'=>'Reservado','icon'=>'📅','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'confirmado'  => ['label'=>'Confirmado','icon'=>'👍','color'=>'#8B5CF6','notify'=>true,'core'=>false],
                'en_atencion' => ['label'=>'En atención','icon'=>'💇','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'finalizado'  => ['label'=>'Finalizado','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'anulado'     => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],
            'salon_belleza' => [
                'reservado'   => ['label'=>'Reservado','icon'=>'📅','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'confirmado'  => ['label'=>'Confirmado','icon'=>'👍','color'=>'#8B5CF6','notify'=>true,'core'=>false],
                'en_atencion' => ['label'=>'En atención','icon'=>'💅','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'finalizado'  => ['label'=>'Finalizado','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'anulado'     => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── CLÍNICA / CONSULTORIO ───────────────────────────────────
            'clinica' => [
                'reservado'   => ['label'=>'Cita reservada','icon'=>'📅','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'confirmada'  => ['label'=>'Confirmada','icon'=>'👍','color'=>'#8B5CF6','notify'=>true,'core'=>false],
                'en_espera'   => ['label'=>'En sala de espera','icon'=>'🪑','color'=>'#06B6D4','notify'=>false,'core'=>false],
                'en_consulta' => ['label'=>'En consulta','icon'=>'🩺','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'atendido'    => ['label'=>'Atendido','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'anulado'     => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── TIENDA / RETAIL ─────────────────────────────────────────
            'retail' => [
                'recibido'   => ['label'=>'Pedido recibido','icon'=>'📥','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'preparando' => ['label'=>'Preparando','icon'=>'📦','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'listo'      => ['label'=>'Listo para envío','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'enviado'    => ['label'=>'Enviado','icon'=>'🚚','color'=>'#F97316','notify'=>true,'core'=>false],
                'entregado'  => ['label'=>'Entregado','icon'=>'🏠','color'=>'#059669','notify'=>false,'core'=>true],
                'anulado'    => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── NEGOCIO POR WHATSAPP ────────────────────────────────────
            'whatsapp' => [
                'nuevo'      => ['label'=>'Nuevo pedido','icon'=>'💬','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'confirmado' => ['label'=>'Confirmado','icon'=>'👍','color'=>'#8B5CF6','notify'=>true,'core'=>false],
                'preparando' => ['label'=>'Preparando','icon'=>'📦','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'en_camino'  => ['label'=>'En camino','icon'=>'🛵','color'=>'#F97316','notify'=>true,'core'=>false],
                'entregado'  => ['label'=>'Entregado','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'anulado'    => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── FARMACIA / BOTICA ───────────────────────────────────────
            'farmacia' => [
                'recibido'   => ['label'=>'Pedido recibido','icon'=>'📥','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'validando'  => ['label'=>'Validando receta','icon'=>'🔍','color'=>'#8B5CF6','notify'=>false,'core'=>false],
                'preparando' => ['label'=>'Preparando','icon'=>'💊','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'listo'      => ['label'=>'Listo','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'entregado'  => ['label'=>'Entregado','icon'=>'📦','color'=>'#059669','notify'=>false,'core'=>true],
                'anulado'    => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── VETERINARIA / PET SHOP ──────────────────────────────────
            'veterinaria' => [
                'reservado'   => ['label'=>'Cita reservada','icon'=>'📅','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'confirmada'  => ['label'=>'Confirmada','icon'=>'👍','color'=>'#8B5CF6','notify'=>true,'core'=>false],
                'en_atencion' => ['label'=>'En atención','icon'=>'🐾','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'en_obs'      => ['label'=>'En observación','icon'=>'🏥','color'=>'#06B6D4','notify'=>false,'core'=>false],
                'atendido'    => ['label'=>'Atendido','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'anulado'     => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── GIMNASIO / FITNESS ──────────────────────────────────────
            'gimnasio' => [
                'pendiente'  => ['label'=>'Pendiente de pago','icon'=>'⏳','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'activa'     => ['label'=>'Membresía activa','icon'=>'💪','color'=>'#10B981','notify'=>true,'core'=>true],
                'por_vencer' => ['label'=>'Por vencer','icon'=>'⚠️','color'=>'#F97316','notify'=>true,'core'=>false],
                'vencida'    => ['label'=>'Vencida','icon'=>'🔴','color'=>'#EF4444','notify'=>true,'core'=>false],
                'anulada'    => ['label'=>'Anulada','icon'=>'❌','color'=>'#6B7280','notify'=>false,'core'=>false],
            ],

            // ── INMOBILIARIA / ALQUILERES ───────────────────────────────
            'inmobiliaria' => [
                'consulta'   => ['label'=>'Consulta','icon'=>'💬','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'visita'     => ['label'=>'Visita agendada','icon'=>'📅','color'=>'#8B5CF6','notify'=>true,'core'=>false],
                'negociando' => ['label'=>'En negociación','icon'=>'🤝','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'contrato'   => ['label'=>'En contrato','icon'=>'📄','color'=>'#06B6D4','notify'=>false,'core'=>false],
                'cerrado'    => ['label'=>'Cerrado','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'descartado' => ['label'=>'Descartado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── EDUCACIÓN / ACADEMIA ────────────────────────────────────
            'educacion' => [
                'interesado' => ['label'=>'Interesado','icon'=>'💬','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'inscrito'   => ['label'=>'Inscrito','icon'=>'📝','color'=>'#8B5CF6','notify'=>true,'core'=>false],
                'matriculado'=> ['label'=>'Matriculado','icon'=>'🎓','color'=>'#3B82F6','notify'=>true,'core'=>false],
                'cursando'   => ['label'=>'Cursando','icon'=>'📚','color'=>'#06B6D4','notify'=>false,'core'=>false],
                'finalizado' => ['label'=>'Finalizado','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'anulado'    => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],

            // ── GENÉRICO ('otro' y cualquier rubro sin flujo propio) ────
            'generico' => [
                'recibido'  => ['label'=>'Recibido','icon'=>'📥','color'=>'#F59E0B','notify'=>false,'core'=>true],
                'proceso'   => ['label'=>'En proceso','icon'=>'⏳','color'=>'#3B82F6','notify'=>false,'core'=>false],
                'listo'     => ['label'=>'Listo','icon'=>'✅','color'=>'#10B981','notify'=>true,'core'=>true],
                'entregado' => ['label'=>'Entregado','icon'=>'📦','color'=>'#059669','notify'=>false,'core'=>true],
                'anulado'   => ['label'=>'Anulado','icon'=>'❌','color'=>'#EF4444','notify'=>false,'core'=>false],
            ],
        ];
    }

    /** Catálogo de estados para el rubro de un proyecto. */
    public static function catalog(Project $project): array
    {
        $cats = static::catalogsByCategory();
        return $cats[$project->category] ?? $cats['generico'];
    }

    /** Estados activados por defecto (todos los del catálogo excepto anulado). */
    public static function defaultActive(Project $project): array
    {
        return array_values(array_filter(
            array_keys(static::catalog($project)),
            fn($k) => $k !== 'anulado'
        ));
    }

    /** Estados que no se pueden desactivar (core). */
    public static function coreKeys(Project $project): array
    {
        return array_keys(array_filter(static::catalog($project), fn($s) => $s['core'] ?? false));
    }

    /** Claves de estados activos del proyecto (o el default). */
    public static function activeKeys(Project $project): array
    {
        $raw = $project->setting('order_flow') ?? $project->setting('laundry_flow');
        if ($raw) {
            $keys = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($keys) && $keys) {
                $valid = array_keys(static::catalog($project));
                $keys  = array_values(array_intersect($keys, $valid));
                foreach (static::coreKeys($project) as $c) {
                    if (!in_array($c, $keys)) $keys[] = $c;
                }
                return static::sortByCatalog($project, $keys);
            }
        }
        return static::defaultActive($project);
    }

    /** Config por estado (claves nueva y legacy). */
    public static function config(Project $project): array
    {
        $raw = $project->setting('order_flow_config') ?? $project->setting('laundry_flow_config');
        if ($raw) {
            $cfg = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($cfg)) return $cfg;
        }
        return [];
    }

    /** Los 3 niveles de tiempo (min) de un estado: target/warn/critical. */
    public static function times(Project $project, string $key): array
    {
        $cfg = static::config($project)[$key] ?? [];
        $target   = isset($cfg['time_target'])   ? (int) $cfg['time_target']   : null;
        $warn     = isset($cfg['time_warn'])     ? (int) $cfg['time_warn']     : null;
        $critical = isset($cfg['time_critical']) ? (int) $cfg['time_critical'] : null;

        if ($critical === null && isset($cfg['sla_minutes'])) {
            $critical = (int) $cfg['sla_minutes'];
        }

        return [
            'target'   => $target   && $target   > 0 ? $target   : null,
            'warn'     => $warn     && $warn     > 0 ? $warn     : null,
            'critical' => $critical && $critical > 0 ? $critical : null,
        ];
    }

    /** Estados activos con metadata + config fusionada, listos para UI. */
    public static function activeStates(Project $project): array
    {
        $catalog = static::catalog($project);
        $config  = static::config($project);
        $out = [];
        foreach (static::activeKeys($project) as $k) {
            if (!isset($catalog[$k])) continue;
            $base  = $catalog[$k] + ['key' => $k];
            $cfg   = $config[$k] ?? [];
            $times = static::times($project, $k);
            $out[$k] = array_merge($base, [
                'color'         => $cfg['color']       ?? $base['color'],
                'notify'        => $cfg['notify']      ?? $base['notify'],
                'alert'         => (bool) ($cfg['alert'] ?? false),
                'wa_message'    => $cfg['wa_message']  ?? null,
                'time_target'   => $times['target'],
                'time_warn'     => $times['warn'],
                'time_critical' => $times['critical'],
            ]);
        }
        return $out;
    }

    /**
     * Transiciones (flechas) del diagrama de flujo, para los estados ACTIVOS.
     * Reglas: lineal entre estados consecutivos + cualquier estado intermedio
     * puede ir a 'anulado' (si está activo). Retorna [['from'=>k,'to'=>k,'tipo'=>'principal'|'anular'], ...]
     */
    public static function transitions(Project $project): array
    {
        $keys = static::activeKeys($project);
        // Separar el estado final "anulado" del flujo lineal
        $anulado = in_array('anulado', $keys) ? 'anulado' : null;
        $linea   = array_values(array_filter($keys, fn($k) => $k !== 'anulado'));

        $out = [];
        // Flujo principal: cada estado apunta al siguiente
        for ($i = 0; $i < count($linea) - 1; $i++) {
            $out[] = ['from' => $linea[$i], 'to' => $linea[$i + 1], 'tipo' => 'principal'];
        }
        // Rama a "anulado": desde los intermedios (no el primero ni el último final)
        if ($anulado) {
            $finales = ['entregado', 'finalizado', 'atendido', 'cerrado'];
            foreach ($linea as $i => $k) {
                if ($i === 0) continue;                 // el inicial no se anula directo
                if (in_array($k, $finales)) continue;   // los finales no van a anulado
                $out[] = ['from' => $k, 'to' => $anulado, 'tipo' => 'anular'];
            }
        }
        return $out;
    }

    /**
     * Diagrama guardado por el proyecto: posiciones de nodos y transiciones custom.
     * Estructura: ['positions' => ['recibido'=>['x'=>20,'y'=>30], ...],
     *              'transitions' => [['from'=>'a','to'=>'b'], ...]]
     * Si no hay guardado, devuelve [] (la vista usa el layout/transiciones automáticos).
     */
    public static function diagram(Project $project): array
    {
        $raw = $project->setting('order_flow_diagram');
        if ($raw) {
            $d = is_array($raw) ? $raw : json_decode($raw, true);
            if (is_array($d)) return $d;
        }
        return [];
    }

    /** Posiciones guardadas de los nodos (o [] si no hay). */
    public static function positions(Project $project): array
    {
        return static::diagram($project)['positions'] ?? [];
    }

    /**
     * Transiciones a mostrar: las custom guardadas si existen, si no las automáticas.
     */
    public static function effectiveTransitions(Project $project): array
    {
        $d = static::diagram($project);
        if (!empty($d['transitions']) && is_array($d['transitions'])) {
            // Filtrar a estados activos válidos
            $active = static::activeKeys($project);
            return array_values(array_filter($d['transitions'], fn($t) =>
                isset($t['from'], $t['to']) && in_array($t['from'], $active) && in_array($t['to'], $active)
            ));
        }
        return static::transitions($project);
    }

    /** Tiempo crítico (rojo) de un estado, o null. */
    public static function slaMinutes(Project $project, string $key): ?int
    {
        return static::times($project, $key)['critical'];
    }

    /** ¿El estado tiene alerta activada? */
    public static function alertsOn(Project $project, string $key): bool
    {
        return (bool) (static::config($project)[$key]['alert'] ?? false);
    }

    /**
     * ¿Este estado dispara aviso WhatsApp? Respeta config del proyecto; si no, el catálogo.
     */
    public static function shouldNotify(Project $project, string $key): bool
    {
        $cfg = static::config($project)[$key] ?? null;
        if ($cfg !== null && array_key_exists('notify', $cfg)) {
            return (bool) $cfg['notify'];
        }
        return static::catalog($project)[$key]['notify'] ?? false;
    }

    /** Metadata de un estado (o null). */
    public static function state(Project $project, string $key): ?array
    {
        $s = static::catalog($project)[$key] ?? null;
        return $s ? $s + ['key' => $key] : null;
    }

    /** El siguiente estado activo tras el actual (para botón "avanzar"). */
    public static function nextKey(Project $project, string $current): ?string
    {
        $keys = array_values(array_filter(
            static::activeKeys($project),
            fn($k) => $k !== 'anulado'
        ));
        $i = array_search($current, $keys);
        if ($i === false) return $keys[0] ?? null;
        return $keys[$i + 1] ?? null;
    }

    /** Analiza el nivel de tiempo del pedido en su estado actual (ok/warn/over). */
    public static function slaStatus(Project $project, $order): array
    {
        $key   = $order->laundry_status;
        $since = $order->laundry_status_at ?? $order->updated_at;
        $mins  = $since ? (int) $since->diffInMinutes(now()) : 0;
        $t     = $key ? static::times($project, $key) : ['target'=>null,'warn'=>null,'critical'=>null];

        $critical = $t['critical'];
        $warn     = $t['warn'] ?? ($critical ? (int) round($critical * 0.8) : null);

        $level = 'ok';
        $overdue = false;
        if ($critical && $mins >= $critical) {
            $level = 'over';
            $overdue = $key ? static::alertsOn($project, $key) : false;
        } elseif ($warn && $mins >= $warn) {
            $level = 'warn';
        }

        return [
            'minutes'  => $mins, 'sla' => $critical, 'target' => $t['target'],
            'warn' => $warn, 'critical' => $critical, 'overdue' => $overdue, 'level' => $level,
        ];
    }

    /** Cuenta pedidos en estado crítico (rojo) con alerta activada. */
    public static function overdueCount(Project $project): int
    {
        $finals = static::finalKeys($project);
        $watch = [];
        foreach (static::config($project) as $key => $c) {
            if (empty($c['alert'])) continue;
            $crit = static::times($project, $key)['critical'];
            if ($crit) $watch[$key] = $crit;
        }
        if (!$watch) return 0;

        $count = 0;
        $orders = $project->orders()
            ->whereIn('laundry_status', array_keys($watch))
            ->whereNotIn('laundry_status', $finals)
            ->get(['laundry_status', 'laundry_status_at', 'updated_at']);

        foreach ($orders as $o) {
            $since = $o->laundry_status_at ?? $o->updated_at;
            if (!$since) continue;
            $mins = (int) $since->diffInMinutes(now());
            if ($mins >= $watch[$o->laundry_status]) $count++;
        }
        return $count;
    }

    /** Estados finales del rubro (entregado/finalizado/anulado). */
    public static function finalKeys(Project $project): array
    {
        return array_values(array_intersect(
            ['entregado', 'finalizado', 'anulado'],
            array_keys(static::catalog($project))
        ));
    }

    /** Mapea el estado del flujo al `status` genérico de la orden. */
    public static function toGenericStatus(Project $project, string $flowStatus): string
    {
        // Primer estado del catálogo = pending; finales = done; anulado = cancelled
        if ($flowStatus === 'anulado') return 'cancelled';
        if (in_array($flowStatus, ['entregado', 'finalizado'])) return 'done';

        $keys = array_keys(static::catalog($project));
        $first = $keys[0] ?? 'recibido';
        return $flowStatus === $first ? 'pending' : 'process';
    }

    /** Mensaje WhatsApp para un estado que notifica (custom del proyecto o default). */
    public static function notifyMessage(Project $project, string $key, $order, string $negocio): string
    {
        $tag    = $order->tag_code ? " (Ticket {$order->tag_code})" : '';
        $nombre = $order->client_name ? " {$order->client_name}" : '';
        $label  = static::state($project, $key)['label'] ?? $key;

        $custom = static::config($project)[$key]['wa_message'] ?? null;
        if ($custom) {
            return strtr($custom, [
                '{cliente}' => trim($nombre) ?: 'cliente',
                '{ticket}'  => $order->tag_code ?? '',
                '{negocio}' => $negocio,
                '{estado}'  => $label,
            ]);
        }

        // Mensajes por defecto según hito
        if (in_array($key, ['listo', 'finalizado'])) {
            return "✅ *¡Tu pedido está listo!*{$tag}\n\nHola{$nombre}, ya puedes pasar a recogerlo o coordinar la entrega. ✨\n\n_{$negocio}_";
        }
        if (in_array($key, ['entregado'])) {
            return "📦 *¡Pedido entregado!*{$tag}\n\nGracias{$nombre} por confiar en nosotros 💙\n\n_{$negocio}_";
        }
        return "Actualización de tu pedido{$tag}: {$label}.\n_{$negocio}_";
    }

    /** Ordena claves según el orden del catálogo del rubro. */
    protected static function sortByCatalog(Project $project, array $keys): array
    {
        $order = array_keys(static::catalog($project));
        usort($keys, fn($a, $b) => array_search($a, $order) <=> array_search($b, $order));
        return $keys;
    }
}

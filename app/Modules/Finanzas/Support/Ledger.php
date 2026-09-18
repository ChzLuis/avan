<?php

namespace App\Modules\Finanzas\Support;

use App\Support\LineMath;

use App\Modules\Ventas\Models\Order;
use App\Modules\Ventas\Models\OrderEvent;
use App\Modules\Finanzas\Models\Payment;
use App\Models\Project;
use App\Modules\Finanzas\Models\ReceivableTerm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * F2b — El libro de cobros y su proyeccion.
 *
 * Regla unica de la que cuelga todo: **la verdad esta en `payments`**. El
 * `payment_status` y el `advance_amount` del documento son cache derivada, y
 * se recalculan SIEMPRE desde la suma del libro, nunca por incremento. Un
 * saldo que se acumula sumando y restando acaba divergiendo; uno que se deriva,
 * no puede.
 *
 * Nada de esto edita ni borra asientos: revertir es insertar.
 */
class Ledger
{
    /**
     * Registra un cobro y devuelve el asiento. Transaccional.
     *
     * `$importeCents = null` significa "salda lo que falte", y se calcula
     * DENTRO de la transaccion: hacerlo fuera lee un saldo que aun no incluye
     * el adelanto heredado que se adopta al entrar el documento al libro.
     */
    public static function registrar(
        Project $project,
        Order $documento,
        ?int $importeCents,
        string $metodo = null,
        string $referencia = null,
        string $fuente = 'panel',
        Carbon $recibidoEn = null,
        int $usuarioId = null,
    ): Payment {
        if ($importeCents !== null && $importeCents <= 0) {
            throw new RuntimeException('El importe de un cobro debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($project, $documento, $importeCents, $metodo, $referencia, $fuente, $recibidoEn, $usuarioId) {
            // Bloqueamos el documento: sin esto, dos cobros simultaneos pueden
            // pasar los dos la validacion de sobrecobro y dejar saldo negativo.
            $doc = $documento->newQuery()->lockForUpdate()->findOrFail($documento->id);
            [$tipo, $totalCents] = self::datosDe($doc);

            self::adoptarAdelantoHeredado($project, $doc, $tipo);

            $cobrado = self::cobradoCents($project->id, $tipo, $doc->id);

            // null = "el resto": ahora si se conoce el saldo real, con el
            // adelanto heredado ya adoptado.
            $importeCents ??= max(0, $totalCents - $cobrado);
            if ($importeCents <= 0) {
                throw new RuntimeException('Este documento ya está cobrado por completo.');
            }

            if ($cobrado + $importeCents > $totalCents) {
                $sobra = LineMath::format($cobrado + $importeCents - $totalCents);
                throw new RuntimeException("El cobro excede el saldo pendiente en {$sobra}.");
            }

            $asiento = Payment::create([
                'project_id'   => $project->id,
                'payable_type' => $tipo,
                'payable_id'   => $doc->id,
                'amount_cents' => $importeCents,
                'method'       => $metodo,
                'reference'    => $referencia,
                'received_at'  => $recibidoEn ?? now(),
                'source'       => $fuente,
                'user_id'      => $usuarioId ?? auth()->id(),
            ]);

            self::proyectar($project, $doc);
            self::registrarEvento($project, $doc, $tipo, 'payment_recorded', [
                'payment_id' => $asiento->id,
                'importe'    => LineMath::format($importeCents),
                'metodo'     => $metodo,
                'fuente'     => $fuente,
            ]);

            return $asiento;
        });
    }

    /** Anula un cobro insertando su reversion. El original queda intacto. */
    public static function revertir(Payment $asiento, string $motivo, int $usuarioId = null): Payment
    {
        if ($asiento->esReversion()) {
            throw new RuntimeException('Una reversion no se puede revertir; registra un cobro nuevo.');
        }

        return DB::transaction(function () use ($asiento, $motivo, $usuarioId) {
            $yaRevertido = Payment::where('reverses_id', $asiento->id)->lockForUpdate()->exists();
            if ($yaRevertido) {
                throw new RuntimeException('Ese cobro ya fue revertido.');
            }

            $project = Project::findOrFail($asiento->project_id);
            $doc = self::documento($asiento->payable_type, $asiento->payable_id);

            $reversion = Payment::create([
                'project_id'      => $asiento->project_id,
                'payable_type'    => $asiento->payable_type,
                'payable_id'      => $asiento->payable_id,
                'amount_cents'    => $asiento->amount_cents,
                'method'          => $asiento->method,
                'received_at'     => now(),
                'source'          => $asiento->source,
                'user_id'         => $usuarioId ?? auth()->id(),
                'reverses_id'     => $asiento->id,
                'reversal_reason' => $motivo,
            ]);

            self::proyectar($project, $doc);
            self::registrarEvento($project, $doc, $asiento->payable_type, 'payment_reversed', [
                'payment_id' => $asiento->id,
                'reversion'  => $reversion->id,
                'importe'    => LineMath::format($asiento->amount_cents),
                'motivo'     => $motivo,
            ]);

            return $reversion;
        });
    }

    /** Cobrado vigente en centavos: los asientos, menos los revertidos. */
    public static function cobradoCents(int $projectId, string $tipo, int $documentoId): int
    {
        $asientos = Payment::where('project_id', $projectId)
            ->where('payable_type', $tipo)
            ->where('payable_id', $documentoId)
            ->get(['amount_cents', 'reverses_id']);

        // Una reversion resta su importe Y anula el original: el neto es cero
        // para el par, que es justo lo que da la suma con signo.
        return $asientos->sum(fn (Payment $p) => $p->importeConSigno());
    }

    public static function saldoCents(int $projectId, Order $documento): int
    {
        [$tipo, $totalCents] = self::datosDe($documento);

        return max(0, $totalCents - self::cobradoCents($projectId, $tipo, $documento->id));
    }

    /**
     * Recalcula la cache del documento desde el libro. Se llama SIEMPRE tras
     * mover el libro; nunca se actualiza el saldo por incremento.
     */
    public static function proyectar(Project $project, Order $documento): void
    {
        [$tipo, $totalCents] = self::datosDe($documento);
        $cobrado = self::cobradoCents($project->id, $tipo, $documento->id);

        $estado = match (true) {
            $cobrado <= 0             => 'pending',
            $cobrado >= $totalCents   => 'paid',
            default                   => 'partial',
        };

        $documento->forceFill([
            'payment_status'  => $estado,
            'advance_amount'  => LineMath::format($cobrado),
        ])->save();
    }

    /**
     * Vencimiento del documento segun la configuracion del negocio. El plazo
     * es un ajuste por proyecto (`cxc_plazo_dias`, 0 = contado) porque una
     * bodega cobra al contado y una distribuidora fia a 30 dias.
     */
    public static function generarVencimiento(Project $project, Order $documento, int $plazoDias = null): ReceivableTerm
    {
        [$tipo, $totalCents] = self::datosDe($documento);
        $plazo = $plazoDias ?? (int) $project->setting('cxc_plazo_dias', 0);
        $base  = Carbon::parse($documento->created_at)->startOfDay();

        return ReceivableTerm::updateOrCreate(
            ['payable_type' => $tipo, 'payable_id' => $documento->id, 'numero' => 1],
            [
                'project_id'   => $project->id,
                'due_date'     => $base->copy()->addDays(max(0, $plazo)),
                'amount_cents' => $totalCents,
            ]
        );
    }

    /**
     * Los documentos anteriores a F2b llevan lo cobrado en una columna
     * (`advance_amount` / `paid_amount`) y NO tienen asientos. Si el primer
     * movimiento del libro los ignorase, el saldo se recalcularia desde cero y
     * el adelanto que ya se habia cobrado desapareceria. Al tocar por primera
     * vez un documento asi, su adelanto se adopta como asiento heredado.
     */
    private static function adoptarAdelantoHeredado(Project $project, Order $doc, string $tipo): void
    {
        $yaHayLibro = Payment::where('project_id', $project->id)
            ->where('payable_type', $tipo)->where('payable_id', $doc->id)->exists();
        if ($yaHayLibro) {
            return;
        }

        $columna  = $tipo === 'order' ? 'advance_amount' : 'paid_amount';
        $heredado = $doc->{$columna};
        if ($heredado === null || (float) $heredado <= 0) {
            return;
        }

        Payment::create([
            'project_id'   => $project->id,
            'payable_type' => $tipo,
            'payable_id'   => $doc->id,
            'amount_cents' => LineMath::toCents(LineMath::canon((string) $heredado)),
            'received_at'  => $doc->updated_at ?? now(),
            'source'       => 'legacy',
            'meta'         => ['nota' => "Adoptado de {$columna} al entrar el documento al libro"],
        ]);
    }

    /**
     * ['order', total en centavos].
     *
     * El libro es de PEDIDOS. Una cotizacion es una oferta y no genera
     * derecho de cobro hasta convertirse en pedido; mientras el libro
     * admitiera cotizaciones, la cartera podia volver a incluir dinero que
     * nadie debe. `payable_type` se conserva en el esquema por si en el
     * futuro entra otro documento cobrable (una factura suelta, por ejemplo).
     */
    private static function datosDe(Order $documento): array
    {
        return ['order', LineMath::toCents(LineMath::canon((string) $documento->total))];
    }

    private static function documento(string $tipo, int $id): Order
    {
        return Order::findOrFail($id);
    }

    private static function registrarEvento(Project $project, Order $doc, string $tipo, string $accion, array $meta): void
    {
        OrderEvent::create([
            'project_id' => $project->id,
            'order_id'   => $doc->id,
            'quote_id'   => null,
            'user_id'    => auth()->id(),
            'action'     => $accion,
            'meta'       => $meta,
        ]);
    }
}

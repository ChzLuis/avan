<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Finanzas\Models\Invoice;
use App\Models\Order;

class FacturacionDashboardController extends Controller
{
    public function index(string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        $hoy = today();

        $ventasHoy     = $project->orders()->whereDate('created_at', $hoy)->sum('total');
        $pedidosHoy    = $project->orders()->whereDate('created_at', $hoy)->count();
        // Los KPIs miden lo facturado: un borrador todavia no lo es.
        $facturasMes   = $project->invoices()->where('status', '!=', 'draft')->whereMonth('issue_date', $hoy->month)->whereYear('issue_date', $hoy->year)->count();
        $totalFacturado = $project->invoices()->where('status', '!=', 'draft')->whereMonth('issue_date', $hoy->month)->whereYear('issue_date', $hoy->year)->sum('total');

        $ultimasFacturas = $project->invoices()->with('client')->latest()->limit(5)->get();
        $ultimosPedidos  = $project->orders()->latest()->limit(5)->get();

        return view('finanzas::facturacion.dashboard', compact(
            'project', 'ventasHoy', 'pedidosHoy',
            'facturasMes', 'totalFacturado',
            'ultimasFacturas', 'ultimosPedidos'
        ));
    }

    /**
     * Portada del modulo (2026-09-05), con la estructura de la app de SUNAT:
     * primero lo que esta PENDIENTE y puede costar dinero, despues una fila
     * de tarjetas por area con una sola accion cada una.
     *
     * Antes el modulo eran dos enlaces sueltos en el menu ("Facturas" y
     * "Guias") y el comprobante con error solo se veia abriendo la lista:
     * cuando alguien lo miraba, el plazo de 3 dias de SUNAT ya habia pasado.
     */
    /**
     * Solo un telefono es "movil" aqui: la portada se diseño para usarla de pie
     * en el mostrador. Una tablet o un escritorio van directo a Facturas, que
     * es donde se trabaja con pantalla ancha. `?movil=1` fuerza la portada
     * (para probarla o para quien la prefiera).
     */
    private function esTelefono(\Illuminate\Http\Request $request): bool
    {
        if ($request->boolean('movil')) {
            return true;
        }
        $ua = (string) $request->userAgent();

        return (bool) preg_match('/iPhone|iPod|Windows Phone|Opera Mini|IEMobile|BlackBerry/i', $ua)
            || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') !== false);
    }

    public function portada()
    {
        // Esta pantalla (saludo, asuntos pendientes, tarjetas deslizables) es
        // la portada MOVIL de Facturacion. En escritorio sobraba: dos clics
        // para llegar a lo mismo que ofrece Facturas.
        if (! $this->esTelefono(request())) {
            return redirect()->route('bixosales.facturas');
        }

        /** @var Project $project */
        $project = app('active_project');
        $usuario = auth()->user();
        $hoy     = now()->startOfDay();
        $limite  = $hoy->copy()->subDays(3);

        $comprobantes = $project->invoices()
            ->where('status', '!=', 'cancelled')
            ->get(['id', 'type', 'status', 'sunat_status', 'baja_estado', 'issue_date', 'total']);

        // Todo lo emitido que SUNAT aun no acepto, este en error, en cola o
        // sin haberse enviado nunca: para SUNAT da igual, el plazo corre.
        $pendienteSunat = $comprobantes->filter(fn ($i) => $i->status !== 'draft'
            && $i->sunat_status !== 'accepted'
            && $i->baja_estado !== 'accepted'
            && $i->issue_date);
        $enPlazo      = $pendienteSunat->filter(fn ($i) => $i->issue_date->gte($limite));
        $fueraDePlazo = $pendienteSunat->filter(fn ($i) => $i->issue_date->lt($limite));
        $diasRestan   = $enPlazo->min(fn ($i) => max(0, 3 - (int) $i->issue_date->diffInDays($hoy)));

        $borradores     = $comprobantes->where('status', 'draft')->count();
        $bajasEnTramite = $comprobantes->where('baja_estado', 'pending')->count();

        $guias = \App\Modules\Finanzas\Models\GuiaRemision::where('project_id', $project->id)
            ->where('status', '!=', 'cancelled')
            ->get(['id', 'sunat_status', 'motivo_codigo', 'invoice_id']);
        $guiasSinEnviar      = $guias->filter(fn ($g) => $g->sunat_status !== 'accepted')->count();
        $guiasSinComprobante = $guias->filter(fn ($g) => $g->motivo_codigo === '01' && ! $g->invoice_id)->count();

        $cartera = \App\Support\Cobranza::cartera($project);

        // Datos del emisor que SUNAT exige: sin ellos el primer envio rebota.
        $faltanEmisor = collect([
            'ruc'            => 'RUC del emisor',
            'razon_social'   => 'razón social',
            'apisperu_token' => 'token de APIsPERU',
        ])->filter(fn ($_, $clave) => trim((string) $project->setting($clave)) === '')->values()->all();

        // ── La lista de pendientes, de mas a menos urgente ───────────────
        $pendientes = [];
        if ($enPlazo->count()) {
            $pendientes[] = [
                'tono'   => 'rojo', 'icono' => 'reloj',
                'titulo' => $enPlazo->count() === 1 ? '1 comprobante sin aceptar por SUNAT' : $enPlazo->count().' comprobantes sin aceptar por SUNAT',
                'detalle' => $diasRestan === 0 ? 'Hoy es el último día para enviarlo.' : "Quedan {$diasRestan} día(s) de plazo. Después ya no se puede declarar.",
                'accion' => 'Enviar ahora', 'url' => route('bixosales.facturas.consulta', ['estado' => 'sin_aceptar']),
            ];
        }
        if ($fueraDePlazo->count()) {
            $pendientes[] = [
                'tono'   => 'rojo', 'icono' => 'alerta',
                'titulo' => $fueraDePlazo->count().' fuera del plazo de SUNAT',
                'detalle' => 'Ya no se pueden declarar: corresponde darlos de baja y volver a emitir con fecha de hoy.',
                'accion' => 'Revisar', 'url' => route('bixosales.facturas.consulta', ['estado' => 'sin_enviar']),
            ];
        }
        if ($faltanEmisor) {
            $pendientes[] = [
                'tono'   => 'rojo', 'icono' => 'ajustes',
                'titulo' => 'Completar datos del emisor',
                'detalle' => 'Falta: '.implode(', ', $faltanEmisor).'. Sin esto SUNAT rechaza el envío.',
                'accion' => 'Completar', 'url' => \Illuminate\Support\Facades\Route::has('settings') ? route('settings') : '#',
            ];
        }
        if ($guiasSinEnviar) {
            $pendientes[] = [
                'tono'   => 'ambar', 'icono' => 'camion',
                'titulo' => $guiasSinEnviar === 1 ? '1 guía sin aceptar por SUNAT' : $guiasSinEnviar.' guías sin aceptar por SUNAT',
                'detalle' => 'La mercadería no debería viajar con una guía que SUNAT no aceptó.',
                'accion' => 'Ver guías', 'url' => route('guias.index'),
            ];
        }
        if ($guiasSinComprobante) {
            $pendientes[] = [
                'tono'   => 'ambar', 'icono' => 'camion',
                'titulo' => $guiasSinComprobante.' guía(s) de venta sin comprobante',
                'detalle' => 'Mercadería entregada sin factura ni boleta que la sustente.',
                'accion' => 'Ver guías', 'url' => route('guias.index'),
            ];
        }
        if ($cartera['vencidas'] ?? 0) {
            $pendientes[] = [
                'tono'   => 'ambar', 'icono' => 'billetes',
                'titulo' => $cartera['vencidas'].' cobro(s) vencido(s)',
                'detalle' => 'S/ '.number_format(($cartera['vencido_cents'] ?? 0) / 100, 2).' con fecha de pago ya pasada.',
                'accion' => 'Cobrar', 'url' => route('bixosales.cuentas'),
            ];
        }
        if ($bajasEnTramite) {
            $pendientes[] = [
                'tono'   => 'ambar', 'icono' => 'reloj',
                'titulo' => $bajasEnTramite.' baja(s) en trámite',
                'detalle' => 'SUNAT todavía no respondió la comunicación de baja.',
                'accion' => 'Ver', 'url' => route('bixosales.facturas.consulta', ['estado' => 'anulado']),
            ];
        }
        if ($borradores) {
            $pendientes[] = [
                'tono'   => 'gris', 'icono' => 'borrador',
                'titulo' => $borradores === 1 ? '1 borrador sin emitir' : $borradores.' borradores sin emitir',
                'detalle' => 'Tienen número reservado pero aún no se declararon.',
                'accion' => 'Emitir', 'url' => route('bixosales.facturas.consulta', ['estado' => 'draft']),
            ];
        }

        // ── Resumen del mes ──────────────────────────────────────────────
        $delMes = $comprobantes->filter(fn ($i) => $i->status !== 'draft'
            && $i->baja_estado !== 'accepted'
            && in_array($i->type, ['factura', 'boleta'], true)
            && $i->issue_date && $i->issue_date->isSameMonth($hoy));
        $mes = [
            'n'     => $delMes->count(),
            'total' => (float) $delMes->sum('total'),
        ];

        $puedeEmitir = $usuario?->is_superadmin
            || $project->owner_id === $usuario?->id
            || $usuario?->can('invoices.crear');

        return view('finanzas::facturacion.portada', [
            'project'      => $project,
            'pendientes'   => $pendientes,
            'mes'          => $mes,
            'puedeEmitir'  => (bool) $puedeEmitir,
            'lectorActivo' => \App\Modules\Finanzas\Support\Lector\LectorComprobantes::disponible($project),
            'nombre'       => $usuario?->name ?? '',
        ]);
    }
}

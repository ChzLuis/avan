<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Finanzas\Models\Invoice;
use App\Modules\Finanzas\Models\InvoiceItem;
use App\Models\Project;
use App\Modules\Crm\Models\Client;
use App\Modules\Finanzas\Jobs\SendInvoiceToSunat;
use App\Modules\Finanzas\Support\NubefactService;
use App\Support\LineMath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Representacion impresa a usar. `moderno` es la de siempre; `clasico`
     * reproduce el formato encuadrado tradicional. Se elige por negocio con el
     * ajuste `invoice_template` — lo fiscal es identico en ambas.
     */
    private function plantillaImpresa(\App\Models\Project $project): string
    {
        return (string) $project->setting('invoice_template') === 'clasico'
            ? 'finanzas::invoices.pdf-clasico'
            : 'finanzas::invoices.pdf';
    }

    public function index()
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        // Cada comprobante es su propio trámite ante SUNAT, con su serie y su
        // numeración: el menú los ofrece por separado (Facturas / Boletas /
        // Notas) y la lista se filtra con `?tipo=`. Sin valor, se ven todos.
        /* En "Notas" hacen falta las DOS cosas: las notas ya emitidas y los
           comprobantes que admiten una nota, porque una nota se emite eligiendo
           a cual corrige. Filtrar solo por nota_* dejaba el buscador vacio. */
        $tiposPorSeccion = [
            'factura' => ['factura'],
            'boleta'  => ['boleta'],
            'nota'    => ['nota_credito', 'nota_debito', 'factura', 'boleta'],
        ];
        $seccion = (string) request()->query('tipo', '');
        $tipos   = $tiposPorSeccion[$seccion] ?? null;

        $modelos = $project->invoices()
            ->with('client')
            ->when($tipos, fn ($q) => $q->whereIn('type', $tipos))
            ->latest()
            ->get();

        $invoices = $modelos->map(fn($inv) => [
            'id'          => $inv->id,
            'numero'      => $inv->numero,
            'type'        => $inv->type,
            'type_label'  => $inv->getTypeLabel(),
            'client_name' => $inv->client_name,
            // El buscador de la lista tambien busca por documento: es lo que
            // dicta el cliente por telefono cuando no recuerda el numero.
            'client_doc_number' => $inv->client_doc_number,
            'total'       => (float) $inv->total,
            'status'      => $inv->status,
            'status_label'=> $inv->getStatusLabel(),
            'issue_date'  => $inv->fechaInterna()?->format('Y-m-d'),
            'sunat_status'=> $inv->sunat_status,
            'sunat_obs'   => count($inv->observacionesSunat()),
            /* Lo necesita la seccion de Notas para no ofrecer como corregible
               un comprobante que ya se dio de baja: sin este campo el filtro
               lo daba siempre por vigente. */
            'baja_estado' => $inv->baja_estado,
        ]);

        $portalLayout = request()->routeIs('bixosales.*') ? 'comercial' : 'panel';
        $serieFactura = $project->setting('serie_factura') ?? 'F001';
        $serieBoleta  = $project->setting('serie_boleta')  ?? 'B001';
        /* Las notas tienen SU serie autorizada. Sin esto usaban la de
           facturas y compartian numeracion: llego a haber un F001-00000001
           que era a la vez una factura y una nota de credito. */
        $serieNotaCredito = $project->setting('serie_nota_credito') ?: 'FC01';
        $serieNotaDebito  = $project->setting('serie_nota_debito')  ?: 'FD01';

        /* Catalogo del negocio para el detalle del documento. Se arma en
           `CatalogoDocumentos` porque comprobantes, guias y cotizaciones
           eligen de la MISMA lista: tenerlo repetido hacia que cada
           pantalla ofreciera cosas distintas. */
        $catalogo = \App\Modules\Finanzas\Support\CatalogoDocumentos::para($project);

        /* Los que corren peligro: en error o clavados en pending, con el plazo
           de 3 dias de SUNAT corriendo. El mas urgente marca la cuenta atras.
           Se filtra sobre los MODELOS (con Carbon), no sobre las filas ya
           aplanadas para la vista: leer ->sunat_status en un array tumbaba la
           pantalla entera en cuanto existia un solo comprobante. */
        $enRiesgo = $modelos->filter(fn ($i) =>
            in_array($i->sunat_status, ['error', 'pending'], true)
            && $i->issue_date
            && $i->issue_date->gte(now()->subDays(3)->startOfDay())
        );
        $porVencer = [
            'cuantos' => $enRiesgo->count(),
            'dias'    => $enRiesgo->min(fn ($i) => max(0, 3 - (int) $i->issue_date->diffInDays(now()->startOfDay()))),
        ];

        // Lector de comprobantes: solo se ofrece si el negocio lo tiene
        // encendido Y con clave utilizable. Un boton que siempre falla es
        // peor que no tener el boton.
        $lectorActivo = \App\Modules\Finanzas\Support\Lector\LectorComprobantes::disponible($project);

        return view('finanzas::invoices.index', compact(
            'project', 'invoices', 'portalLayout', 'serieFactura', 'serieBoleta',
            'porVencer', 'seccion', 'catalogo', 'lectorActivo',
            'serieNotaCredito', 'serieNotaDebito'
        ));
    }

    /**
     * CONSULTA de comprobantes ya emitidos.
     *
     * Vive aparte de la emisión a propósito: emitir es el trabajo diario del
     * cajero y buscar un comprobante pasado (o cuadrar el mes) es del
     * contador. Tenerlos en la misma pantalla obligaba a que el formulario y
     * el buscador se disputaran el sitio.
     */
    public function consulta(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $q      = trim((string) $request->query('q', ''));
        $tipo   = (string) $request->query('tipo', '');
        $estado = (string) $request->query('estado', '');
        $desde  = (string) $request->query('desde', '');
        $hasta  = (string) $request->query('hasta', '');
        /* Por que fecha se busca. Son dos cosas distintas y se confundian:
           la EMISION es la fecha fiscal que va en el comprobante y la que ve
           SUNAT; la CREACION es el dia real en que se tecleo en el sistema.
           SUNAT admite emitir con hasta 3 dias de atraso, asi que una factura
           creada el 10 puede declarar el 08: quien busca "lo de ayer" y quien
           cuadra el registro de ventas no buscan lo mismo. */
        $porFecha = $request->query('por_fecha') === 'creacion' ? 'creacion' : 'emision';

        $tiposValidos = ['factura', 'boleta', 'nota_credito', 'nota_debito'];

        // "F001-2" o "f001 2" debe encontrar F001-00000002: nadie teclea los
        // ocho digitos. Si el texto parece un numero de comprobante se
        // completa; si no, se busca tal cual en numero, cliente y documento.
        $numeroCompleto = null;
        if (preg_match('/^([A-Za-z]{1,2}\d{3})[\s-]*(\d{1,8})$/', $q, $m)) {
            $numeroCompleto = strtoupper($m[1]).'-'.str_pad($m[2], 8, '0', STR_PAD_LEFT);
        }

        // La tabla muestra la fecha interna cuando existe; el filtro tiene que
        // mirar ESA misma fecha o el usuario filtra por el dia que ve y el
        // comprobante no aparece.
        $fechaVisible = $porFecha === 'creacion'
            ? 'created_at'
            : 'COALESCE(internal_issue_date, issue_date)';

        $comprobantes = $project->invoices()
            ->with('client')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
                ->where('numero', 'like', "%{$q}%")
                ->when($numeroCompleto, fn ($x) => $x->orWhere('numero', $numeroCompleto))
                ->orWhere('client_name', 'like', "%{$q}%")
                ->orWhere('client_doc_number', 'like', "%{$q}%")))
            ->when(in_array($tipo, $tiposValidos, true), fn ($b) => $b->where('type', $tipo))
            ->when($estado !== '', fn ($b) => match ($estado) {
                // Estados que no viven en sunat_status pero que el usuario
                // si distingue: borrador, anulado y nunca enviado.
                'draft'      => $b->where('status', 'draft'),
                'anulado'    => $b->where(fn ($w) => $w->where('status', 'cancelled')->orWhere('baja_estado', 'accepted')),
                'sin_enviar' => $b->whereNull('sunat_status')->where('status', '!=', 'draft'),
                /* TODO lo que sigue sin aceptar, sea cual sea el motivo: nunca
                   enviado, en cola, con error o rechazado. Es exactamente lo
                   que cuenta el aviso del plazo de SUNAT; sin este filtro, el
                   aviso decia "3 comprobantes sin aceptar" y al pulsarlo caia
                   en `estado=error`, que solo trae uno de los cuatro casos y
                   respondia "ninguno coincide". El usuario aprendia a ignorar
                   el unico aviso con plazo legal.
                   `whereNotIn` descarta los NULL en silencio, asi que el nulo
                   se pide aparte. */
                'sin_aceptar' => $b->where('status', '!=', 'draft')
                                   ->where('status', '!=', 'cancelled')
                                   ->where(fn ($w) => $w->whereNull('sunat_status')
                                                        ->orWhere('sunat_status', '!=', 'accepted'))
                                   ->where(fn ($w) => $w->whereNull('baja_estado')
                                                        ->orWhere('baja_estado', '!=', 'accepted')),
                // Un comprobante dado de baja ya no es "aceptado" aunque SUNAT
                // lo aceptara en su dia: se aparta de cualquier estado SUNAT.
                default      => $b->where('sunat_status', $estado)->where('status', '!=', 'cancelled')
                                  ->where(fn ($w) => $w->whereNull('baja_estado')->orWhere('baja_estado', '!=', 'accepted')),
            })
            ->when($desde !== '', fn ($b) => $b->whereRaw("DATE({$fechaVisible}) >= ?", [$desde]))
            ->when($hasta !== '', fn ($b) => $b->whereRaw("DATE({$fechaVisible}) <= ?", [$hasta]))
            ->orderByRaw("{$fechaVisible} DESC")
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('finanzas::invoices.consulta', [
            'project'      => $project,
            'comprobantes' => $comprobantes,
            'filtros'      => compact('q', 'tipo', 'estado', 'desde', 'hasta', 'porFecha'),
            'portalLayout' => request()->routeIs('bixosales.*') ? 'comercial' : 'panel',
        ]);
    }

    public function show(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product', 'order', 'quote', 'client');

        $data = [
            'id'                   => $invoice->id,
            'numero'               => $invoice->numero,
            'type'                 => $invoice->type,
            'type_label'           => $invoice->getTypeLabel(),
            'serie'                => $invoice->serie,
            'correlativo'          => $invoice->correlativo,
            'status'               => $invoice->status,
            /* La pantalla decide con esto si ofrece "comunicar baja": sin el
               dato, una factura ya dada de baja invitaba a darla de baja otra
               vez, y SUNAT rechaza el duplicado. */
            'baja_estado'          => $invoice->baja_estado,
            'baja_motivo'          => $invoice->baja_motivo,
            'status_label'         => $invoice->getStatusLabel(),
            'issue_date'           => $invoice->fechaInterna()?->format('Y-m-d'),
            'official_issue_date'  => $invoice->issue_date?->format('Y-m-d'),
            'due_date'             => $invoice->due_date?->format('Y-m-d'),
            'payment_condition'    => $invoice->payment_condition ?: 'contado',
            'emisor_razon_social'  => $invoice->emisor_razon_social,
            'emisor_ruc'           => $invoice->emisor_ruc,
            'emisor_direccion'     => $invoice->emisor_direccion,
            'client_name'         => $invoice->client_name,
            'client_phone'        => $invoice->client_phone,
            'client_email'        => $invoice->client_email,
            'client_doc_type'     => $invoice->client_doc_type,
            'client_doc_number'   => $invoice->client_doc_number,
            'client_address'      => $invoice->client_address,
            'subtotal'             => (float) $invoice->subtotal,
            'igv'                  => (float) $invoice->igv,
            'total'                => (float) $invoice->total,
            'currency'             => $invoice->currency,
            'igv_included'         => $invoice->igv_included,
            'payment_method'       => $invoice->payment_method,
            'paid_at'              => $invoice->paid_at?->format('d/m/Y H:i'),
            'notes'                => $invoice->notes,
            'sunat_status'         => $invoice->sunat_status,
            'sunat_error'          => $invoice->sunat_error,
            'items'                => $invoice->items->map(fn($it) => [
                'id'          => $it->id,
                'description' => $it->description,
                'unit'        => $it->unit,
                'quantity'    => (float) $it->quantity,
                'unit_price'  => (float) $it->unit_price,
                'discount'    => (float) ($it->discount ?? 0),
                'igv_amount'  => (float) $it->igv_amount,
                'total'       => (float) $it->total,
            ])->values()->all(),
        ];

        return response()->json($data);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        /* DOBLE EMISION. En el mostrador con mala señal el cajero pulsa
           Emitir, tarda, recarga y vuelve a pulsar: salian dos comprobantes
           identicos, los dos declarados, y habia que anular uno con nota de
           credito. El navegador manda una huella del intento; el primero que
           llega la reserva 30 s y el segundo recibe el comprobante ya creado
           en vez de crear otro. */
        $huella = trim((string) $request->header('X-Idempotencia'));
        $candado = null;
        if ($huella !== '') {
            $candado = 'emision:'.$project->id.':'.substr(preg_replace('/[^A-Za-z0-9\-]/', '', $huella), 0, 64);

            $yaEmitido = Cache::get($candado);
            if ($yaEmitido) {
                $previo = Invoice::where('project_id', $project->id)->find($yaEmitido);
                if ($previo) {
                    return response()->json(['invoice' => $previo->load('items'), 'repetido' => true]);
                }
            }
            // `add` es atomico: si otra peticion ya lo puso, esta no entra.
            if (! Cache::add($candado.':curso', 1, 30)) {
                return response()->json([
                    'message' => 'Ese comprobante ya se está emitiendo. Espera un momento.',
                ], 409);
            }
            /* Igual que en las guias: si la emision falla, el candado tiene que
               soltarse o el cajero se queda 30 segundos bloqueado con el
               cliente delante, sin saber por que. */
            app()->terminating(fn () => Cache::forget($candado.':curso'));
        }

        $data = $request->validate([
            /* Solo se emiten aqui VENTAS. Una nota de credito o debito no es
               un comprobante suelto: corrige a otro, y SUNAT exige que diga a
               cual (afecta_*) y por que motivo de su catalogo. Este formulario
               nunca envia esos datos, asi que por esta via salia una nota
               invalida que ademas quemaba un correlativo de serie, que no se
               puede reutilizar. Las notas van por NotaController, que hereda
               receptor y lineas del comprobante afectado. */
            'type'                => 'required|in:boleta,factura',
            'serie'               => 'nullable|string|max:10',
            // `correlativo` se acepta pero se ignora: lo asigna el sistema.
            'correlativo'         => 'nullable|integer|min:1',
            // SUNAT rechaza envios con fecha de emision de mas de 3 dias
            // calendario, y una fecha futura tampoco entra. Bloquearlo aqui
            // evita emitir un comprobante condenado al rechazo.
            'issue_date'          => 'nullable|date|after_or_equal:'.now()->subDays(3)->toDateString().'|before_or_equal:'.now()->toDateString(),
            'payment_condition'   => 'nullable|in:contado,credito',
            /* A credito SUNAT exige la cuota con su fecha: sin vencimiento el
               XML no se puede armar y el comprobante saldria declarado al
               contado, que es mentir en un dato fiscal. */
            'due_date'            => [
                'nullable', 'date',
                Rule::requiredIf(fn () => $request->input('payment_condition') === 'credito'),
                'after_or_equal:'.($request->input('issue_date') ?: now()->toDateString()),
            ],
            'client_name'        => 'required|string|max:200',
            'client_phone'       => 'nullable|string|max:30',
            'client_email'       => 'nullable|email|max:150',
            /* SUNAT no acepta una factura sin RUC del adquiriente. Sin esta
               regla se gastaba el correlativo, el XML viajaba con el receptor
               "DNI 00000000" y volvia rechazado: numero quemado y cliente sin
               comprobante. La boleta si admite venta sin identificar. */
            'client_doc_type'    => [
                'nullable', 'in:DNI,RUC,CE,pasaporte',
                Rule::requiredIf(fn () => $request->input('type') === 'factura'),
                Rule::in($request->input('type') === 'factura' ? ['RUC'] : ['DNI', 'RUC', 'CE', 'pasaporte']),
            ],
            'client_doc_number'  => [
                'nullable', 'string', 'max:15',
                Rule::requiredIf(fn () => $request->input('type') === 'factura'),
                // El largo lo manda el tipo: 11 el RUC, 8 el DNI.
                $request->input('client_doc_type') === 'RUC' ? 'digits:11' : null,
                $request->input('client_doc_type') === 'DNI' ? 'digits:8' : null,
            ],
            'client_address'     => 'nullable|string|max:300',
            'payment_method'      => 'nullable|string|max:80',
            'notes'               => 'nullable|string',
            'igv_included'        => 'nullable|boolean',
            'currency'            => 'nullable|string|size:3',
            'quote_id'            => 'nullable|integer',
            // Un borrador se guarda a medias y NO se numera ni se declara.
            // Solo se admite ese valor: el resto de estados los pone el
            // sistema segun lo que pase con SUNAT, no el formulario.
            'status'              => 'nullable|in:draft',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'required|string|max:300',
            'items.*.unit'        => 'nullable|string|max:20',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.unit_price'  => 'required|numeric|min:0',
            // F4: el descuento por linea ya se puede facturar.
            'items.*.discount'    => 'nullable|numeric|decimal:0,2|min:0|max:100',
        ], [
            'issue_date.after_or_equal'  => 'SUNAT rechaza comprobantes con fecha de emisión de más de 3 días: la más antigua permitida hoy es el '.now()->subDays(3)->format('d/m/Y').'. Para una venta anterior, factura con la fecha de hoy.',
            'issue_date.before_or_equal' => 'La fecha de emisión no puede ser futura.',
            'due_date.required'          => 'Una venta a crédito necesita fecha de vencimiento: SUNAT la exige en el comprobante.',
            'due_date.after_or_equal'    => 'El vencimiento no puede ser anterior a la fecha de emisión.',
        ]);

        /* Precio 0 en un comprobante que se EMITE: casi siempre es la cantidad
           o el precio que no se llego a teclear, y emitido solo se corrige con
           nota de credito. El formulario ya lo corta; esto es la puerta del
           servidor, que no depende de que el JS haya cargado bien. Un borrador
           si puede guardarse a medias. */
        if (($data['status'] ?? null) !== 'draft') {
            $sinPrecio = collect($data['items'])
                ->filter(fn ($i) => (float) ($i['unit_price'] ?? 0) <= 0)
                ->pluck('description')->map(fn ($d) => trim((string) $d))->all();
            if ($sinPrecio !== []) {
                return response()->json([
                    'message' => 'No se puede emitir: "'.implode('", "', $sinPrecio).'" '
                        .(count($sinPrecio) === 1 ? 'tiene' : 'tienen').' precio 0. Escribe el precio o guarda como borrador.',
                    'errors'  => ['items' => ['Hay líneas con precio 0.']],
                ], 422);
            }

            /* Cantidad 0: la linea no vende nada y el importe sale 0, pero el
               producto queda declarado en el comprobante. La validacion de
               arriba acepta `min:0.001`, asi que esto solo salta si el numero
               llega redondeado a cero o vacio. */
            $sinCantidad = collect($data['items'])
                ->filter(fn ($i) => (float) ($i['quantity'] ?? 0) <= 0)
                ->pluck('description')->map(fn ($d) => trim((string) $d))->all();
            if ($sinCantidad !== []) {
                return response()->json([
                    'message' => 'No se puede emitir: "'.implode('", "', $sinCantidad).'" '
                        .(count($sinCantidad) === 1 ? 'tiene' : 'tienen').' cantidad 0. Escribe cuántos o guarda como borrador.',
                    'errors'  => ['items' => ['Hay líneas con cantidad 0.']],
                ], 422);
            }
        }

        $type   = $data['type'];
        $defaultSerie = $type === 'factura'
            ? ($project->setting('serie_factura') ?: 'F001')
            : ($project->setting('serie_boleta')  ?: 'B001');
        $serie  = $data['serie'] ?? $defaultSerie;
        $igvIncluded = $request->boolean('igv_included', true);

        // F4: importes fiscales en CENTAVOS ENTEROS.
        //
        // Esto se calculaba con flotantes —`round($qty*$price,2)` y sumas
        // acumuladas— justo en el dinero de mas consecuencias del sistema,
        // mientras el resto del proyecto usa LineMath. Un comprobante que no
        // cuadra por un centimo es un problema con SUNAT, no una molestia.
        //
        // El descuento por linea ya viaja hasta aqui: `unit_price` es el precio
        // de lista y `total` el neto, de modo que
        // `unit_price x cantidad x (1 - descuento)` = `total` de forma exacta.
        [$itemsData, $subtotal, $igvTotal, $totalDoc] = $this->calcularLineas($data['items'], $igvIncluded);

        /* SUNAT obliga a identificar al comprador (DNI, RUC o carnet) en toda
           boleta de S/ 700 o mas. Sin esta regla se gastaba el correlativo y
           la boleta volvia rechazada. Un borrador aun puede completarse. */
        if ($type === 'boleta' && ($data['status'] ?? null) !== 'draft'
            && (float) $totalDoc >= 700 && empty($data['client_doc_number'])) {
            return response()->json([
                'message' => 'SUNAT exige identificar al comprador en boletas de S/ 700 o más: indica su DNI, RUC o carnet.',
                'errors'  => ['client_doc_number' => ['Obligatorio en boletas de S/ 700 o más.']],
            ], 422);
        }

        // Enlazar el comprobante al cliente canónico si ya existe en el negocio
        // (por teléfono o correo), para que aparezca en su Customer 360. No se
        // crea un cliente nuevo: un comprobante puede emitirse a quien no está
        // en el CRM (TD-018, parte estable).
        $clientId = null;
        // El documento identifica mejor que el telefono: un RUC es unico y no
        // cambia, mientras que un movil se comparte o se reemplaza.
        if (! empty($data['client_doc_number'])) {
            $clientId = $project->clients()->where('doc_number', $data['client_doc_number'])->value('id');
        }
        if (! $clientId && ! empty($data['client_phone'])) {
            $clientId = $project->clients()->where('phone', $data['client_phone'])->value('id');
        }
        if (! $clientId && ! empty($data['client_email'])) {
            $clientId = $project->clients()->where('email', $data['client_email'])->value('id');
        }

        // Si el cliente ya existe pero aun no tenia documento, se le anota: la
        // proxima vez se le encuentra por RUC sin volver a teclearlo.
        if ($clientId && ! empty($data['client_doc_number'])) {
            $project->clients()->where('id', $clientId)->whereNull('doc_number')->update([
                'doc_type'   => $data['client_doc_type'] ?? null,
                'doc_number' => $data['client_doc_number'],
            ]);
        }

        /* BORRADOR: se guarda a medias y NO se declara a SUNAT hasta que
           alguien lo emite desde el detalle.

           Si reserva correlativo, igual que un emitido: el indice unico
           (project, tipo, serie, correlativo) no admite dos filas sin numero.
           A cambio, el numero ya es suyo cuando se emita. Un borrador
           descartado deja el mismo hueco que un comprobante borrado, que es
           un caso que el sistema ya contempla. */
        $esBorrador = ($data['status'] ?? null) === 'draft';

        // Reserva del correlativo + creación en la MISMA transacción (RISK-012):
        // dos emisiones simultáneas ya no pueden tomar el mismo número.
        /* El correlativo NUNCA lo elige quien emite. Antes se aceptaba del
           request y eso saltaba el `lockForUpdate` de `nextCorrelativo`: un
           POST con `correlativo: 500` dejaba un hueco 48-499 en la serie, y
           SUNAT exige correlatividad. El formulario ya lo mostraba bloqueado;
           ahora tambien lo esta la API. */
        $correlativoManual = null;
        $invoice = Invoice::emitir($project->id, $type, $serie, $correlativoManual,
            function (int $correlativo, string $numero) use ($project, $type, $serie, $data, $subtotal, $igvTotal, $totalDoc, $igvIncluded, $itemsData, $clientId, $esBorrador) {
                $invoice = $project->invoices()->create([
                    'client_id'           => $clientId,
                    'type'                => $type,
                    'serie'               => $serie,
                    'correlativo'         => $correlativo,
                    'numero'              => $numero,
                    'issue_date'          => $data['issue_date'] ?? now()->toDateString(),
                    'due_date'            => $data['due_date'] ?? null,
                    'emisor_razon_social' => $project->setting('razon_social') ?? $project->name,
                    'emisor_ruc'          => $project->setting('ruc'),
                    'emisor_direccion'    => $project->address,
                    'client_name'        => $data['client_name'],
                    'client_phone'       => $data['client_phone'] ?? null,
                    'client_email'       => $data['client_email'] ?? null,
                    'client_doc_type'    => $data['client_doc_type'] ?? null,
                    'client_doc_number'  => $data['client_doc_number'] ?? null,
                    'client_address'     => $data['client_address'] ?? null,
                    'subtotal'            => $subtotal,
                    'igv'                 => $igvTotal,
                    'total'               => $totalDoc,
                    'currency'            => $data['currency'] ?? ($project->setting('currency') ?? 'PEN'),
                    'igv_included'        => $igvIncluded,
                    'payment_method'      => $data['payment_method'] ?? null,
                    'payment_condition'   => $data['payment_condition'] ?? 'contado',
                    'status'              => $esBorrador ? 'draft' : 'issued',
                    'notes'               => $data['notes'] ?? null,
                    'quote_id'            => $data['quote_id'] ?? null,
                ]);

                foreach ($itemsData as $item) {
                    $invoice->items()->create($item);
                }

                return $invoice;
            });

        // Con el comprobante ya creado, el reintento devuelve este mismo.
        if ($candado) {
            Cache::put($candado, $invoice->id, 30);
            Cache::forget($candado.':curso');
        }

        /* EMITIR ES DECLARAR. Antes el envio a SUNAT era un boton aparte que
           alguien tenia que acordarse de pulsar, y el comprobante que nadie
           pulsaba se quedaba en sunat_status NULL: ni enviado, ni en error, ni
           visible para el reintentador horario. Asi se perdio la F001-00000010
           del 09/09/2026, que llego al plazo de 3 dias sin salir nunca.

           Un comprobante emitido es un documento fiscal y su destino es SUNAT,
           igual que ya hacian las notas de credito y debito. Solo el borrador
           se queda quieto: todavia no es un comprobante. */
        if (! $esBorrador) {
            $invoice->update(['sunat_status' => 'pending']);
            SendInvoiceToSunat::dispatch($invoice->id);
        }

        return response()->json(['invoice' => $invoice->load('items')]);
    }

    /**
     * Importes de las lineas en CENTAVOS ENTEROS, el mismo calculo para el
     * comprobante que se emite y para su vista previa: si divergieran, la
     * hoja que se ensena antes de emitir mentiria.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: string, 2: string, 3: string}
     *         [lineas, subtotal, igv, total]
     */
    private function calcularLineas(array $items, bool $igvIncluded): array
    {
        $data = ['items' => $items];
        $subtotalCents = 0;
        $igvTotalCents = 0;
        $itemsData     = [];

        foreach ($data['items'] as $item) {
            // OJO: `quantity` es decimal(10,3) —se factura 2.5 kg— asi que NO
            // se puede castear a entero. Se trabaja en milesimas para que la
            // linea siga siendo aritmetica entera y no vuelva el flotante.
            $qty       = (float) $item['quantity'];
            $qtyMilli  = (int) round($qty * 1000);
            /* Un campo vacio NO es null: `?? 0` no lo sustituye y LineMath
               rechazaba la cadena vacia con "Descuento invalido: ''". El
               cajero que dejaba Desc. % en blanco (lo normal) se quedaba sin
               vista previa y sin poder emitir. Vacio = cero. */
            $aNumero   = static fn ($v) => trim((string) ($v ?? '')) === '' ? '0' : (string) $v;
            $precio    = LineMath::canon($aNumero($item['unit_price'] ?? null));
            $descuento = LineMath::canon($aNumero($item['discount'] ?? null));
            $precioCents = LineMath::toCents($precio);
            $bp          = LineMath::toBasisPoints($descuento);

            // precioCents x (qtyMilli/1000) x (1 - bp/10000), half-up sobre 10^7.
            $lineaCents = intdiv($precioCents * $qtyMilli * (10000 - $bp) + 5_000_000, 10_000_000);

            if ($igvIncluded) {
                // El precio YA lleva IGV: la base es total/1.18, half-up exacto.
                $subCents = intdiv($lineaCents * 100 + 59, 118);
                $igvCents = $lineaCents - $subCents;
                $totalCents = $lineaCents;
            } else {
                $subCents   = $lineaCents;
                $igvCents   = intdiv($subCents * 18 + 50, 100);
                $totalCents = $subCents + $igvCents;
            }

            $subtotalCents += $subCents;
            $igvTotalCents += $igvCents;
            $itemsData[] = [
                'description' => $item['description'],
                // Siempre un codigo SUNAT, venga del catalogo, del lector o a
                // mano: asi el PDF y el XML leen lo mismo.
                'unit'        => \App\Modules\Finanzas\Support\Sunat\Catalogos::codigoUnidad($item['unit'] ?? 'NIU'),
                'quantity'    => $qty,
                'unit_price'  => $precio,
                'discount'    => $descuento,
                'igv_amount'  => LineMath::format($igvCents),
                'total'       => LineMath::format($totalCents),
            ];
        }

        // El total del comprobante es la SUMA de sus lineas, no un recalculo:
        // asi el documento cuadra consigo mismo linea por linea.
        $subtotal = LineMath::format($subtotalCents);
        $igvTotal = LineMath::format($igvTotalCents);
        $totalDoc = LineMath::format($subtotalCents + $igvTotalCents);

        return [$itemsData, $subtotal, $igvTotal, $totalDoc];
    }

    /**
     * Vista previa: la MISMA hoja impresa, sin gastar correlativo ni tocar la
     * base. El comprobante se arma en memoria a partir del formulario y se
     * marca como sin validez.
     */
    public function previsualizar(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        // El formulario llega serializado en un solo campo para poder abrirse
        // en una pestana nueva con un POST normal.
        $payload = json_decode((string) $request->input('payload'), true);
        if (is_array($payload)) {
            $request->merge($payload);
        }

        $data = $request->validate([
            'type'                => 'nullable|in:boleta,factura,nota_credito,nota_debito',
            'serie'               => 'nullable|string|max:10',
            'issue_date'          => 'nullable|date',
            'due_date'            => 'nullable|date',
            'payment_condition'   => 'nullable|in:contado,credito',
            'client_name'         => 'nullable|string|max:200',
            'client_doc_type'     => 'nullable|string|max:20',
            'client_doc_number'   => 'nullable|string|max:15',
            'client_address'      => 'nullable|string|max:300',
            'notes'               => 'nullable|string',
            'currency'            => 'nullable|string|size:3',
            'items'               => 'required|array|min:1',
            'items.*.description' => 'nullable|string|max:300',
            'items.*.unit'        => 'nullable|string|max:20',
            'items.*.quantity'    => 'nullable|numeric|min:0',
            'items.*.unit_price'  => 'nullable|numeric|min:0',
            'items.*.discount'    => 'nullable|numeric|min:0|max:100',
        ]);

        // Solo las lineas con algo escrito: la fila vacia del formulario no
        // es un item.
        $lineas = array_values(array_filter($data['items'], fn ($i) => trim((string) ($i['description'] ?? '')) !== ''));
        foreach ($lineas as &$l) {
            $l['quantity']   = (float) ($l['quantity'] ?? 0) > 0 ? $l['quantity'] : 1;
            $l['unit_price'] = $l['unit_price'] ?? 0;
        }
        unset($l);
        if (! $lineas) {
            $lineas = [['description' => '(sin productos)', 'quantity' => 1, 'unit_price' => 0]];
        }

        $igvIncluded = $request->boolean('igv_included', true);
        [$itemsData, $subtotal, $igvTotal, $totalDoc] = $this->calcularLineas($lineas, $igvIncluded);

        $type  = $data['type'] ?? 'boleta';
        $serie = ($data['serie'] ?? null) ?: ($type === 'factura'
            ? ($project->setting('serie_factura') ?: 'F001')
            : ($project->setting('serie_boleta') ?: 'B001'));

        $invoice = new Invoice([
            'type'                => $type,
            'serie'               => $serie,
            'numero'              => $serie.'-XXXXXXXX',
            'issue_date'          => $data['issue_date'] ?? now()->toDateString(),
            'due_date'            => $data['due_date'] ?? null,
            'payment_condition'   => $data['payment_condition'] ?? 'contado',
            'emisor_razon_social' => $project->setting('razon_social') ?? $project->name,
            'emisor_ruc'          => $project->setting('ruc'),
            'emisor_direccion'    => $project->address,
            'client_name'         => ($data['client_name'] ?? null) ?: 'Cliente',
            'client_doc_type'     => $data['client_doc_type'] ?? null,
            'client_doc_number'   => $data['client_doc_number'] ?? null,
            'client_address'      => $data['client_address'] ?? null,
            'subtotal'            => $subtotal,
            'igv'                 => $igvTotal,
            'total'               => $totalDoc,
            'currency'            => $data['currency'] ?? ($project->setting('currency') ?? 'PEN'),
            'igv_included'        => $igvIncluded,
            'status'              => 'draft',
            'notes'               => $data['notes'] ?? null,
        ]);
        $invoice->setRelation('items', collect($itemsData)->map(
            fn ($i) => (new InvoiceItem($i))->setRelation('product', null)
        ));

        return view($this->plantillaImpresa($project), [
            'project'     => $project,
            'invoice'     => $invoice,
            'vistaPrevia' => true,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        $data = $request->validate([
            'status'         => 'nullable|in:draft,issued,sent,cancelled',
            'payment_method' => 'nullable|string|max:80',
            'paid_at'        => 'nullable|date',
            'notes'          => 'nullable|string',
            'due_date'       => 'nullable|date',
        ]);

        /* MARCAR "ANULADA" NO ANULA NADA ANTE SUNAT.
           El desplegable de estado dejaba poner "Anulada" en un comprobante ya
           ACEPTADO: en pantalla salia en rojo como anulado mientras SUNAT lo
           seguia teniendo por valido y declarado. El cajero creia haber
           anulado y no habia anulado. Un comprobante aceptado solo se deshace
           con nota de credito o comunicacion de baja, que tienen sus propios
           botones. `destroy()` ya cortaba asi; esto faltaba. */
        if (($data['status'] ?? null) === 'cancelled' && $invoice->sunat_status === 'accepted') {
            return response()->json([
                'message' => 'Este comprobante fue aceptado por SUNAT: no se anula cambiando su estado. '
                    .'Emite una nota de crédito o comunica la baja.',
            ], 422);
        }

        $invoice->update($data);
        return response()->json(['ok' => true]);
    }

    public function destroy(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        abort_if($invoice->sunat_status === 'accepted', 403, 'No se puede eliminar una factura aceptada por SUNAT.');
        $invoice->delete();
        return response()->json(['ok' => true]);
    }

    public function sendSunat(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        abort_if($invoice->sunat_status === 'accepted', 403, 'Este comprobante ya fue aceptado por SUNAT.');
        /* Un borrador esta a medias por definicion: declararlo lo convierte en
           un comprobante fiscal irreversible que solo se corrige con nota de
           credito. Primero se emite, y entonces se envia. */
        abort_if($invoice->status === 'draft', 422, 'Es un borrador: emítelo antes de enviarlo a SUNAT.');

        $invoice->update(['sunat_status' => 'pending']);
        SendInvoiceToSunat::dispatch($invoice->id);

        /* La pantalla de emision llama por fetch y espera JSON; la consulta de
           comprobantes manda un formulario normal, y a esa devolverle JSON
           dejaba al usuario mirando texto crudo en una pagina en blanco. */
        if (! request()->expectsJson()) {
            return back()->with('ok', "Enviando {$invoice->numero} a SUNAT. El estado se actualiza en unos segundos.");
        }

        return response()->json(['ok' => true, 'message' => 'Enviando a SUNAT en segundo plano...']);
    }

    public function sendSunatPortal(string $slug, $invoiceId)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);

        $invoice = Invoice::where('id', $invoiceId)
            ->where('project_id', $project->id)
            ->firstOrFail();

        abort_if($invoice->sunat_status === 'accepted', 403, 'Este comprobante ya fue aceptado por SUNAT.');

        $invoice->update(['sunat_status' => 'pending']);
        SendInvoiceToSunat::dispatch($invoice->id);

        return response()->json(['ok' => true, 'message' => 'Enviando a SUNAT en segundo plano...']);
    }

    /**
     * El Registro de Ventas del periodo, como CSV para el contador.
     *
     * Las notas de credito restan (asentadas en negativo), las de debito
     * suman, y un comprobante anulado o dado de baja se declara con importe
     * cero y su marca: el registro los lista, no los esconde.
     */
    public function registroVentas(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        $mes = $request->get('mes') ?: now()->format('Y-m');
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $mes), 422, 'El periodo va como AAAA-MM.');

        $desde = $mes.'-01';
        $hasta = date('Y-m-t', strtotime($desde));

        /* Un BORRADOR no es una venta: existe en la base pero no se ha
           emitido ni declarado. Incluirlo aqui hacia que el contador
           declarase —y pagase IGV de— importes que nunca se facturaron. */
        $comprobantes = Invoice::where('project_id', $project->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$desde, $hasta])
            ->orderBy('issue_date')->orderBy('numero')
            ->get();

        $filas = [];
        $totBase = 0.0; $totIgv = 0.0; $totTotal = 0.0;

        foreach ($comprobantes as $c) {
            $anulado = $c->status === 'cancelled' || $c->baja_estado === 'accepted';
            // La nota de credito resta: asi se asienta en el registro.
            $signo = $c->type === 'nota_credito' ? -1 : 1;

            $base  = $anulado ? 0.0 : $signo * (float) $c->subtotal;
            $igv   = $anulado ? 0.0 : $signo * (float) $c->igv;
            $total = $anulado ? 0.0 : $signo * (float) $c->total;

            $totBase += $base; $totIgv += $igv; $totTotal += $total;

            $filas[] = [
                $c->issue_date?->format('d/m/Y'),
                $c->getTypeLabel(),
                $c->numero,
                $c->client_doc_type ?: '-',
                $c->client_doc_number ?: '-',
                $c->client_name,
                $c->esNota() ? $c->afecta_numero : '',
                number_format($base, 2, '.', ''),
                number_format($igv, 2, '.', ''),
                number_format($total, 2, '.', ''),
                $c->currency,
                $anulado ? 'ANULADO' : $c->estadoSunatLegible(),
            ];
        }

        $out = fopen('php://temp', 'r+');
        // El BOM: sin el, Excel abre las tildes rotas.
        fwrite($out, "ï»¿");
        fputcsv($out, ['REGISTRO DE VENTAS '.$mes.' — '.($project->setting('razon_social') ?: $project->name).' — RUC '.$project->setting('ruc')], ';');
        fputcsv($out, ['Fecha', 'Tipo', 'Numero', 'Doc.', 'Nro. Doc.', 'Cliente', 'Modifica a', 'Base imponible', 'IGV', 'Total', 'Moneda', 'Estado'], ';');
        foreach ($filas as $f) {
            fputcsv($out, $f, ';');
        }
        fputcsv($out, ['', '', '', '', '', '', 'TOTALES',
            number_format($totBase, 2, '.', ''), number_format($totIgv, 2, '.', ''), number_format($totTotal, 2, '.', ''), '', ''], ';');
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $nombre = 'registro-ventas-'.$mes.'-'.($project->setting('ruc') ?: $project->id).'.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }

    public function pdf(Invoice $invoice)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product');
        $vista = view($this->plantillaImpresa($project), compact('project', 'invoice'));

        return request()->boolean('descargar')
            ? $this->comoArchivoPdf($vista, $invoice)
            : $vista;
    }

    /**
     * El XML firmado del comprobante, con el nombre que usa SUNAT. Si aun no
     * esta en disco pero la respuesta guardada lo trae, se archiva al vuelo.
     */
    public function xml(Invoice $invoice)
    {
        return $this->descargarArchivo($invoice, 'xml');
    }

    /** La constancia de recepcion (CDR) de SUNAT, en su zip original. */
    public function cdr(Invoice $invoice)
    {
        return $this->descargarArchivo($invoice, 'cdr');
    }

    private function descargarArchivo(Invoice $invoice, string $cual)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($invoice->project_id === $project->id, 403);
        abort_unless($invoice->sunat_status === 'accepted', 404, 'Solo un comprobante aceptado por SUNAT tiene XML y CDR.');

        \App\Modules\Finanzas\Support\Sunat\ArchivoComprobantes::asegurar($invoice);
        $ruta  = $cual === 'xml'
            ? \App\Modules\Finanzas\Support\Sunat\ArchivoComprobantes::rutaXml($invoice)
            : \App\Modules\Finanzas\Support\Sunat\ArchivoComprobantes::rutaCdr($invoice);
        $disco = \Illuminate\Support\Facades\Storage::disk(\App\Modules\Finanzas\Support\Sunat\ArchivoComprobantes::DISCO);
        abort_unless($disco->exists($ruta), 404, 'La respuesta de SUNAT guardada no trae este archivo.');

        return $disco->download($ruta, basename($ruta));
    }

    /**
     * El PDF de verdad, no la vista para imprimir: Chrome sin ventana rinde la
     * MISMA hoja y se entrega como archivo con su nombre.
     *
     * Si Chrome no esta o falla, se devuelve la vista imprimible en lugar de
     * un error: el usuario siempre conserva el camino de Ctrl+P.
     */
    private function comoArchivoPdf(View $vista, Invoice $invoice)
    {
        $chrome = collect([
            '/usr/bin/google-chrome-stable', '/usr/bin/google-chrome',
            '/usr/bin/chromium-browser', '/usr/bin/chromium',
        ])->first(fn ($bin) => is_executable($bin));

        if (! $chrome) {
            return $vista;
        }

        $tmp    = sys_get_temp_dir();
        $id     = Str::random(12);
        $html   = "{$tmp}/cpe-{$id}.html";
        $pdf    = "{$tmp}/cpe-{$id}.pdf";
        $perfil = "{$tmp}/cpe-perfil-{$id}";

        try {
            file_put_contents($html, $vista->render());

            Process::timeout(60)->run([
                $chrome, '--headless', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage',
                '--no-pdf-header-footer',
                // La hoja calcula su alto con JS y trae imagenes de fuera: sin
                // margen de tiempo, Chrome imprime antes de que todo termine.
                '--virtual-time-budget=10000',
                "--user-data-dir={$perfil}",
                "--print-to-pdf={$pdf}",
                'file://'.$html,
            ]);
        } catch (\Throwable $e) {
            Log::warning('PDF: Chrome no pudo generar el archivo', [
                'comprobante' => $invoice->numero, 'error' => $e->getMessage(),
            ]);
        }

        @unlink($html);
        File::deleteDirectory($perfil);

        // Un PDF de cuatro bytes es un fallo silencioso: mejor la vista.
        if (! is_file($pdf) || filesize($pdf) < 1024) {
            @unlink($pdf);

            return $vista;
        }

        return response()->download($pdf, $invoice->nombreArchivo().'.pdf')->deleteFileAfterSend();
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    /** La misma consulta, desde el panel: el negocio sale de la sesion. */
    public function lookupRucPanel(Request $request)
    {
        /** @var Project $project */
        $project = app('active_project');

        // `query()` y no `get()`: este ultimo esta obsoleto en Symfony 7.4.
        return $this->consultarRuc($project, (string) ($request->query('doc') ?: $request->query('ruc', '')));
    }

    /**
     * Buscador de clientes del formulario. Un solo cajon para todo: si llegan
     * digitos busca por documento, si llegan letras por nombre. Asi el cajero
     * no tiene que decidir antes de escribir en que campo esta buscando.
     */
    public function buscarClientes(Request $request)
    {
        /** @var Project $project */
        $project = app('active_project');

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['clientes' => []]);
        }

        $soloDigitos = preg_replace('/\D/', '', $q);
        $esDocumento = $soloDigitos !== '' && $soloDigitos === $q;

        $clientes = $project->clients()
            ->when($esDocumento,
                fn ($c) => $c->where('doc_number', 'like', $soloDigitos.'%'),
                fn ($c) => $c->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%'.$q.'%')
                      ->orWhere('empresa', 'like', '%'.$q.'%')
                      ->orWhere('doc_number', 'like', $q.'%');
                }))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'doc_type', 'doc_number', 'direccion', 'phone', 'email']);

        return response()->json([
            'clientes' => $clientes->map(fn ($c) => [
                'id'         => $c->id,
                'nombre'     => $c->name,
                'doc_tipo'   => $c->doc_type,
                'doc_numero' => $c->doc_number,
                'direccion'  => $c->direccion,
                'telefono'   => $c->phone,
                'email'      => $c->email,
            ]),
        ]);
    }

    public function lookupRuc(Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);

        return $this->consultarRuc($project, (string) ($request->query('doc') ?: $request->query('ruc', '')));
    }

    /**
     * El núcleo de la consulta: un solo sitio para las dos puertas, y para
     * RUC y DNI —`ConsultaDocumento` enruta segun la longitud del numero—. Delega en `ConsultaDocumento`, que es el
     * punto único (antes esto era curl crudo contra otro proveedor, pidiendo
     * un token `apiperu_token` que ningún proyecto tenía configurado: la
     * consulta fallaba siempre). La forma de la respuesta se mantiene para
     * no romper a quien ya la consume.
     */
    private function consultarRuc(Project $project, string $rucCrudo)
    {
        $r = app(\App\Support\ConsultaDocumento::class)->consultar($project, $rucCrudo);

        if (! $r['ok']) {
            return response()->json(['ok' => false, 'message' => $r['mensaje']]);
        }

        return response()->json([
            'ok'           => true,
            'tipo'         => $r['tipo'],
            // Una persona natural viene con nombre y apellidos, no con razon
            // social: el formulario espera un solo campo y aqui se unifica.
            'razon_social' => $r['datos']['razon_social'] ?? ($r['datos']['nombre_completo'] ?? ''),
            'direccion'    => $r['datos']['direccion'] ?? '',
            // Datos que antes se descartaban aunque la API los devolviera.
            'estado'       => $r['datos']['estado'] ?? '',
            'condicion'    => $r['datos']['condicion'] ?? '',
            'ubigeo'       => $r['datos']['ubigeo'] ?? '',
            'distrito'     => $r['datos']['distrito'] ?? '',
            'provincia'    => $r['datos']['provincia'] ?? '',
            'departamento' => $r['datos']['departamento'] ?? '',
        ]);
    }

    private function projectBySlug(string $slug): Project
    {
        return Project::where('slug', $slug)->firstOrFail();
    }

    public function indexBoletasPortal(string $slug)
    {
        return $this->invoiceIndexPortal($slug, 'boleta');
    }

    public function indexFacturasPortal(string $slug)
    {
        return $this->invoiceIndexPortal($slug, 'factura');
    }

    public function createBoletaPortal(string $slug)
    {
        return $this->invoiceCreatePortal($slug, 'boleta');
    }

    public function createFacturaPortal(string $slug)
    {
        return $this->invoiceCreatePortal($slug, 'factura');
    }

    private function invoiceCreatePortal(string $slug, string $docType)
    {
        $project      = $this->projectBySlug($slug);
        // `direccion` viaja tambien: al elegir un cliente el formulario rellenaba
        // solo el nombre y habia que teclear la direccion fiscal a mano.
        $clients      = $project->clients()->orderBy('name')->get(['id','name','email','phone','direccion']);
        $productos    = $project->products()->orderBy('name')
                            ->get(['id','name','description','price'])->map(fn($p) => [
                                'id'    => $p->id,
                                'name'  => $p->name,
                                'desc'  => $p->description ?? $p->name,
                                'price' => (float) $p->price,
                            ]);
        $servicios    = $project->services()->orderBy('name')
                            ->get(['id','name','description','price'])->map(fn($s) => [
                                'id'    => $s->id,
                                'name'  => $s->name,
                                'desc'  => $s->description ?? $s->name,
                                'price' => (float) $s->price,
                            ]);
        $catalogo     = $productos->merge($servicios)->values();
        $serieFactura = $project->setting('serie_factura') ?: 'F001';
        $serieBoleta  = $project->setting('serie_boleta')  ?: 'B001';
        $emisorRuc    = $project->setting('ruc') ?? '';
        $emisorRazon  = $project->setting('razon_social') ?? $project->name;
        $emisorDir    = $project->address ?? '';

        // Pre-cargar datos de cotización si viene de una conversión
        $fromQuote = null;
        if ($quoteId = request()->get('from_quote')) {
            $quote = \App\Modules\Ventas\Models\Quote::where('id', $quoteId)
                ->where('project_id', $project->id)
                ->with('items')
                ->first();
            if ($quote) {
                $fromQuote = [
                    'id'                => $quote->id,
                    'client_name'       => $quote->client_name,
                    'client_phone'      => $quote->client_phone ?? '',
                    'client_email'      => $quote->client_email ?? '',
                    'client_doc_type'   => $quote->client_doc_type ?? '',
                    'client_doc_number' => $quote->client_doc_number ?? '',
                    'client_address'    => $quote->client_address ?? '',
                    'notes'           => $quote->notes ?? '',
                    'payment_method'  => $quote->payment_method ?? '',
                    'items'           => $quote->items->map(function($i) {
                        return [
                            'desc'  => $i->description,
                            'unit'  => 'NIU',
                            'qty'   => (float) $i->quantity,
                            'price' => (float) $i->price,
                        ];
                    })->values()->all(),
                ];
            }
        }

        // Comprobantes recientes para la lupa "traer de uno anterior". La tabla
        // `clients` no guarda RUC ni DNI, asi que el unico sitio donde vive el
        // documento fiscal de un receptor es un comprobante ya emitido: de ahi
        // se recuperan tanto el receptor como las lineas para repetir una venta.
        $recientes = $project->invoices()
            ->whereIn('type', ['boleta', 'factura'])
            ->with('items')
            ->latest()->limit(40)->get()
            ->map(fn ($inv) => [
                'id'         => $inv->id,
                'numero'     => $inv->numero,
                'tipo'       => $inv->getTypeLabel(),
                'fecha'      => $inv->fechaInterna()?->format('d/m/Y'),
                'total'      => (float) $inv->total,
                'cliente'    => [
                    'client_name'       => $inv->client_name,
                    'client_doc_type'   => $inv->client_doc_type ?? '',
                    'client_doc_number' => $inv->client_doc_number ?? '',
                    'client_address'    => $inv->client_address ?? '',
                ],
                'lineas'     => $inv->items->map(fn ($i) => [
                    'desc'  => $i->description,
                    'unit'  => $i->unit ?? 'NIU',
                    'qty'   => (float) $i->quantity,
                    // El formulario trabaja con el precio unitario CON IGV, que
                    // es lo que guarda unit_price; no hay que recalcular nada.
                    'price' => (float) $i->unit_price,
                ])->values()->all(),
            ])->values();

        return view('finanzas::facturacion.facturas.create', compact(
            'project','clients','catalogo','serieFactura','serieBoleta','docType',
            'emisorRuc','emisorRazon','emisorDir','fromQuote','recientes'
        ));
    }

    private function invoiceIndexPortal(string $slug, string $docType)
    {
        $project  = $this->projectBySlug($slug);
        $invoices = $project->invoices()
            ->where('type', $docType)
            ->with('client')->latest()->get()
            ->map(fn($inv) => [
                'id'          => $inv->id,
                'numero'      => $inv->numero,
                'type'        => $inv->type,
                'type_label'  => $inv->getTypeLabel(),
                'client_name' => $inv->client_name,
                'total'       => (float) $inv->total,
                'status'      => $inv->status,
                'status_label'=> $inv->getStatusLabel(),
                'issue_date'  => $inv->fechaInterna()?->format('Y-m-d'),
                'sunat_status'=> $inv->sunat_status,
            ]);
        $clients      = $project->clients()->orderBy('name')->get(['id','name','email','phone']);
        $serieFactura = $project->setting('serie_factura') ?: 'F001';
        $serieBoleta  = $project->setting('serie_boleta')  ?: 'B001';
        return view('finanzas::facturacion.facturas.index', compact('project', 'invoices', 'clients', 'serieFactura', 'serieBoleta', 'docType'));
    }

    /** @deprecated kept for compatibility */
    public function indexPortal(string $slug)
    {
        return $this->invoiceIndexPortal($slug, 'boleta');
    }

    public function showPortal(string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product', 'order', 'quote', 'client');
        return response()->json($this->invoiceData($invoice));
    }

    public function storePortal(\Illuminate\Http\Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    public function updatePortal(\Illuminate\Http\Request $request, string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->update($request, $invoice);
    }

    public function destroyPortal(string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->destroy($invoice);
    }

    public function pdfPortal(string $slug, Invoice $invoice)
    {
        $project = $this->projectBySlug($slug);
        abort_unless($invoice->project_id === $project->id, 403);
        $invoice->load('items.product');
        $vista = view($this->plantillaImpresa($project), compact('project', 'invoice'));

        return request()->boolean('descargar')
            ? $this->comoArchivoPdf($vista, $invoice)
            : $vista;
    }

    private function invoiceData(Invoice $invoice): array
    {
        return [
            'id'                   => $invoice->id,
            'numero'               => $invoice->numero,
            'type'                 => $invoice->type,
            'type_label'           => $invoice->getTypeLabel(),
            'serie'                => $invoice->serie,
            'correlativo'          => $invoice->correlativo,
            'status'               => $invoice->status,
            'status_label'         => $invoice->getStatusLabel(),
            'issue_date'           => $invoice->fechaInterna()?->format('Y-m-d'),
            'official_issue_date'  => $invoice->issue_date?->format('Y-m-d'),
            'due_date'             => $invoice->due_date?->format('Y-m-d'),
            'emisor_razon_social'  => $invoice->emisor_razon_social,
            'emisor_ruc'           => $invoice->emisor_ruc,
            'emisor_direccion'     => $invoice->emisor_direccion,
            'client_name'          => $invoice->client_name,
            'client_phone'         => $invoice->client_phone,
            'client_email'         => $invoice->client_email,
            'client_doc_type'      => $invoice->client_doc_type,
            'client_doc_number'    => $invoice->client_doc_number,
            'client_address'       => $invoice->client_address,
            'subtotal'             => (float) $invoice->subtotal,
            'igv'                  => (float) $invoice->igv,
            'total'                => (float) $invoice->total,
            'currency'             => $invoice->currency,
            'payment_method'       => $invoice->payment_method,
            'notes'                => $invoice->notes,
            'sunat_status'         => $invoice->sunat_status,
            'sunat_sent_at'        => $invoice->sunat_sent_at?->format('d/m/Y H:i'),
            'sunat_hash'           => $invoice->sunat_hash,
            'sunat_error'          => $invoice->sunat_error,
            'quote_id'             => $invoice->quote_id,
            'items'                => $invoice->items->map(fn($it) => [
                'id'          => $it->id,
                'description' => $it->description,
                'unit'        => $it->unit,
                'quantity'    => (float) $it->quantity,
                'unit_price'  => (float) $it->unit_price,
                'igv_amount'  => (float) ($it->igv_amount ?? 0),
                'total'       => (float) $it->total,
            ])->values()->all(),
        ];
    }
}

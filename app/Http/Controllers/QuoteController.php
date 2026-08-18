<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Quote;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function index(?Quote $cotizacionInicial = null)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        // Eager-load de lo que la vista recorre de verdad: items (se serializan
        // todos) y order (la relacion con el pedido). Con solo 'client' cada
        // cotizacion disparaba dos consultas extra al pintar la lista.
        $quotes            = $project->quotes()->with(['client', 'items', 'order', 'autor:id,name'])->latest()->get();
        $paymentMethods    = $this->catValues($project, 'payment_method');
        $paymentConditions = $this->catValues($project, 'payment_condition');
        $portalLayout      = request()->routeIs('bixosales.*') ? 'comercial' : 'panel';
        // La miniatura y la linea secundaria de cada producto salen del
        // catalogo. Se cargan con eager loading: la tabla del documento las
        // consulta por fila y sin esto serian dos consultas por producto.
        $products          = $project->products()
            ->where('is_available', true)
            ->with(['images:id,product_id,url,is_main,sort_order', 'category:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'sku', 'category_id', 'description']);
        // Capacidades resueltas en un solo sitio (F1c): la vista no vuelve a
        // deducir permisos con @can sueltos, que acaban desincronizados de las
        // rutas. 'convertir' ya incorpora el modulo de pedidos del proyecto.
        $puede             = \App\Support\QuoteAbilities::para(auth()->user(), $project);
        // Deep link: si se entro por /cotizaciones/{id} en navegacion HTML,
        // la vista abre esa cotizacion sin pedirla otra vez por AJAX.
        $cotizacionInicial = $cotizacionInicial?->id;

        return view('quotes.index', compact(
            'project', 'quotes', 'paymentMethods', 'paymentConditions',
            'portalLayout', 'products', 'puede', 'cotizacionInicial'
        ));
    }

    private function catValues(Project $project, string $type): \Illuminate\Support\Collection
    {
        $list = $project->catalogLists()->where('type', $type)->first();
        return $list ? $list->values()->where('is_active', true)->orderBy('sort_order')->pluck('label') : collect();
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        $data = $request->validate([
            'client_name'       => 'required|string|max:100',
            'client_phone'      => 'nullable|string|max:30',
            'client_email'      => 'nullable|email|max:150',
            'client_doc_type'   => 'nullable|string|max:20',
            'client_doc_number' => 'nullable|string|max:20',
            'client_address'    => 'nullable|string|max:300',
            'client_id'         => 'nullable|integer',
            'notes'             => 'nullable|string',
            'valid_until'       => 'nullable|date',
            'payment_method'    => 'nullable|string|max:80',
            'payment_condition' => 'nullable|string|max:80',
            'items'             => 'required|array|min:1|max:200',
            'items.*.description' => 'required|string',
            'items.*.price'       => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'items.*.discount'    => 'nullable|numeric|decimal:0,2|min:0|max:100',
            'items.*.quantity'    => 'required|integer|min:1|max:10000',
        ]);

        $totalCents = \App\Support\LineMath::sumCents($data['items']);
        // quotes.total es decimal(10,2): el maximo representable es
        // 99,999,999.99. Un documento que lo exceda se rechaza con 422 en vez
        // de estallar en la insercion.
        abort_if($totalCents > 9_999_999_999, 422, 'El total excede el máximo permitido (99,999,999.99).');
        $total = \App\Support\LineMath::format($totalCents);

        $quote = $project->quotes()->create([
            'client_id'         => $data['client_id'] ?? null,
            'client_name'       => $data['client_name'],
            'client_phone'      => $data['client_phone'] ?? null,
            'client_email'      => $data['client_email'] ?? null,
            'client_doc_type'   => $data['client_doc_type'] ?? null,
            'client_doc_number' => $data['client_doc_number'] ?? null,
            'client_address'    => $data['client_address'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'valid_until'       => $data['valid_until'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'total'             => $total,
            'status'            => 'draft',
            'payment_status'    => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            $quote->items()->create($item);
        }

        \App\Models\OrderEvent::log($project->id, 'created', [], null, $quote->id);

        return response()->json(['quote' => $quote->load('items')]);
    }

    /**
     * Doble naturaleza (F1c), igual que Pedidos: para AJAX devuelve JSON; para
     * navegacion HTML devuelve la interfaz completa con esta cotizacion ya
     * abierta. Antes, entrar por la URL escupia JSON crudo en el navegador y
     * los enlaces PED->COT no tenian donde apuntar.
     */
    public function show(Request $request, Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            $quote->load('items', 'client');
            return response()->json($quote);
        }

        return $this->index($quote);
    }

    /**
     * CICLO DE VIDA (F1c). Una cotizacion convertida es un documento historico:
     * ya engendro un pedido y su FK es la trazabilidad de esa relacion. Si se
     * pudieran reescribir sus lineas, reenviarla o devolverla a "borrador", esa
     * trazabilidad mentiria. Estas reglas viven en el SERVIDOR: esconder el
     * boton no es una barrera, porque la URL sigue ahi.
     */
    private function esConvertida(Quote $quote): bool
    {
        return \App\Support\QuoteStatus::comercial($quote->status) === 'converted';
    }

    private function bloquearSiConvertida(Quote $quote, string $accion): void
    {
        abort_if($this->esConvertida($quote), 422,
            "Esta cotización ya fue convertida en pedido, así que no se puede {$accion}. "
            . 'Si necesitas renegociar, duplícala: la copia nace como borrador.');
    }

    /**
     * Destino de estado admitido en una edicion. 'converted' queda fuera:
     * solo convert() puede otorgarlo, porque solo el crea el pedido.
     */
    private function estadoDestinoValidado(?string $destino, Quote $quote): string
    {
        if ($destino === null) {
            return $quote->status;
        }

        abort_if(\App\Support\QuoteStatus::comercial($destino) === 'converted', 422,
            'El estado "convertida" se alcanza convirtiendo la cotización en pedido, no asignándolo a mano.');

        return $destino;
    }

    public function update(Request $request, Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        $data = $request->validate([
            'status'         => 'required|in:draft,sent,accepted,rejected,converted',
            'notes'          => 'nullable|string',
            // Se aceptan alias españoles por compatibilidad de entrada, pero
            // SIEMPRE se persiste el canonico via QuoteStatus::pago().
            'payment_status' => 'nullable|string|in:pending,partial,paid,refunded,pendiente,parcial,pagado,cancelado',
            'paid_amount'    => 'nullable|numeric|min:0',
        ]);

        // --- Ciclo de vida F1c -------------------------------------------
        $destino = \App\Support\QuoteStatus::comercial($data['status']);
        // 'converted' no se asigna a mano: se alcanza SOLO por convert(), que
        // es quien crea el pedido. Asignarlo aqui dejaria una cotizacion
        // "convertida" sin pedido detras.
        abort_if(! $this->esConvertida($quote) && $destino === 'converted', 422,
            'El estado "convertida" se alcanza convirtiendo la cotización en pedido, no asignándolo a mano.');
        // Una convertida conserva su estado comercial. Notas y estado de pago
        // siguen abiertos (los gobernaran F2/F3).
        abort_if($this->esConvertida($quote) && $destino !== 'converted', 422,
            'Una cotización convertida en pedido no puede volver a otro estado. Duplícala para renegociar.');

        // Si marcan "pagado" sin fecha, se registra ahora; si vuelven a "pendiente"
        // se limpia para no dejar una fecha de pago mintiendo sobre el estado.
        if (array_key_exists('payment_status', $data)) {
            // Fuente UNICA de normalizacion: nada de match locales que se
            // desalineen de QuoteStatus (correccion Codex: el match anterior
            // dejaba refunded/cancelado fuera).
            $data['payment_status'] = \App\Support\QuoteStatus::pago($data['payment_status']);
        }
        if (($data['payment_status'] ?? null) === 'paid') {
            $data['paid_at'] = $quote->paid_at ?? now();
        } elseif (array_key_exists('payment_status', $data) && $data['payment_status'] !== 'paid') {
            $data['paid_at'] = null;
        }
        if (array_key_exists('payment_status', $data) && $data['payment_status'] !== $quote->payment_status) {
            \App\Models\OrderEvent::log($project->id, 'payment_status', ['from' => $quote->payment_status, 'to' => $data['payment_status']], null, $quote->id);
        }
        // El cambio de estado es el hecho que un vendedor busca en el
        // historial ("¿cuando la envie?", "¿cuando la aceptaron?"). Se registra
        // el estado COMERCIAL, no la clave cruda que llego en la peticion: las
        // entradas admiten sinonimos legacy y el historial no puede depender de
        // cual escribio quien.
        $estadoPrevio = \App\Support\QuoteStatus::comercial($quote->status);
        $quote->update($data);
        if ($destino !== $estadoPrevio) {
            \App\Models\OrderEvent::log($project->id, 'status_changed',
                ['from' => $estadoPrevio, 'to' => $destino], null, $quote->id);
        }

        return response()->json(['quote' => $quote]);
    }

    /**
     * Retrato del documento para comparar antes y despues de una edicion.
     *
     * Se toman los campos que a una auditoria le importan: a quien se le
     * cotiza, por cuanto, con que condiciones y con que lineas. El resto
     * (marcas de tiempo, token) cambia solo y no dice nada.
     */
    private function retrato(Quote $quote): array
    {
        return [
            'cliente'    => $quote->client_name,
            'documento'  => $quote->client_doc_number,
            'total'      => \App\Support\LineMath::canon((string) $quote->total),
            'validez'    => optional($quote->valid_until)->format('Y-m-d'),
            'metodo'     => $quote->payment_method,
            'condicion'  => $quote->payment_condition,
            'lineas'     => $quote->items->map(fn ($i) => $i->description
                .' x'.$i->quantity
                .' @'.\App\Support\LineMath::canon((string) $i->price)
                .($i->discount > 0 ? ' -'.\App\Support\LineMath::canon((string) $i->discount).'%' : ''))
                ->sort()->values()->all(),
        ];
    }

    /**
     * Registra QUE cambio, con su valor anterior y el nuevo. Solo lo que
     * cambio de verdad: un historial que anota cada guardado aunque no varie
     * nada se vuelve ilegible y deja de consultarse.
     */
    private function registrarEdicion(Project $project, Quote $quote, array $antes, array $despues): void
    {
        $cambios = [];
        foreach ($despues as $campo => $valor) {
            if (($antes[$campo] ?? null) === $valor) {
                continue;
            }
            $cambios[$campo] = [
                'de' => is_array($antes[$campo] ?? null) ? count($antes[$campo]) . ' línea(s)' : ($antes[$campo] ?? '—'),
                'a'  => is_array($valor) ? count($valor) . ' línea(s)' : ($valor ?: '—'),
            ];
        }

        if (! $cambios) {
            return;
        }

        \App\Models\OrderEvent::log($project->id, 'edited', [
            'campos' => array_keys($cambios),
            'detalle' => $cambios,
        ], null, $quote->id);
    }

    /** Reemplaza cliente, condiciones e ítems completos (edición de una cotización existente). */
    public function updateFull(Request $request, Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        // Reescribir cliente, lineas o total de una convertida descuadraria el
        // pedido que ya nacio de ella.
        $this->bloquearSiConvertida($quote, 'editar su contenido');
        // Retrato previo: sin el no hay 'antes' que comparar y el historial
        // solo podria decir que alguien edito, no que cambio.
        $antes = $this->retrato($quote->load('items'));

        $data = $request->validate([
            'client_name'         => 'required|string|max:100',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email|max:150',
            'client_doc_type'     => 'nullable|string|max:20',
            'client_doc_number'   => 'nullable|string|max:20',
            'client_address'      => 'nullable|string|max:300',
            'notes'               => 'nullable|string',
            'valid_until'         => 'nullable|date',
            'payment_method'      => 'nullable|string|max:80',
            'payment_condition'   => 'nullable|string|max:80',
            'status'              => 'nullable|in:draft,sent,accepted,rejected,converted',
            'items'               => 'required|array|min:1|max:200',
            'items.*.description' => 'required|string',
            'items.*.price'       => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'items.*.discount'    => 'nullable|numeric|decimal:0,2|min:0|max:100',
            'items.*.quantity'    => 'required|integer|min:1|max:10000',
        ]);

        $totalCents = \App\Support\LineMath::sumCents($data['items']);
        // quotes.total es decimal(10,2): el maximo representable es
        // 99,999,999.99. Un documento que lo exceda se rechaza con 422 en vez
        // de estallar en la insercion.
        abort_if($totalCents > 9_999_999_999, 422, 'El total excede el máximo permitido (99,999,999.99).');
        $total = \App\Support\LineMath::format($totalCents);

        $quote->update([
            'client_name'       => $data['client_name'],
            'client_phone'      => $data['client_phone'] ?? null,
            'client_email'      => $data['client_email'] ?? null,
            'client_doc_type'   => $data['client_doc_type'] ?? null,
            'client_doc_number' => $data['client_doc_number'] ?? null,
            'client_address'    => $data['client_address'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'valid_until'       => $data['valid_until'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            // 'converted' no se asigna a mano (F1c): solo convert() lo otorga.
            'status'            => $this->estadoDestinoValidado($data['status'] ?? null, $quote),
            'total'             => $total,
        ]);

        $quote->items()->delete();
        foreach ($data['items'] as $item) {
            $quote->items()->create($item);
        }

        $this->registrarEdicion($project, $quote, $antes, $this->retrato($quote->fresh()->load('items')));

        return response()->json(['ok' => true, 'quote' => $quote->load('items')]);
    }

    // Genera token único y marca como enviada — devuelve el link del portal
    public function send(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        // Enviar/reenviar solo desde draft|sent. Antes esto fijaba 'sent' a
        // ciegas: una aceptada o rechazada volvia silenciosamente a "enviada"
        // (y una convertida rompia su propia trazabilidad). Para renegociar
        // esta duplicate(), que nace en borrador.
        abort_unless(in_array(\App\Support\QuoteStatus::comercial($quote->status), ['draft', 'sent'], true), 422,
            'Solo se puede enviar una cotización en borrador o ya enviada. '
            . 'Esta ya fue respondida o convertida: duplícala para negociar de nuevo.');
        if (!$quote->token) {
            $quote->token = Str::random(48);
        }
        $quote->sent_at = now();
        $quote->status  = 'sent';
        $quote->save();

        \App\Models\OrderEvent::log($project->id, 'quote_sent', [], null, $quote->id);

        $portalUrl = url('/b/' . $project->slug . '/c/' . $quote->token);
        return response()->json(['ok' => true, 'url' => $portalUrl, 'quote' => $quote]);
    }

    /**
     * Documento imprimible de la cotizacion.
     *
     * Facturacion ya tenia su vista de impresion en el servidor; Cotizaciones
     * no tenia ninguna: el unico "PDF" del modulo se armaba en el navegador
     * rasterizando la pantalla con html2canvas, asi que salia como imagen, sin
     * texto seleccionable y recalculando los importes por su cuenta. Esta vista
     * imprime la fila guardada y calcula con LineMath, que es la unica
     * aritmetica de dinero del sistema.
     */
    public function pdf(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        $quote->load('items');

        return view('quotes.pdf', compact('project', 'quote'));
    }

    /**
     * Historial de la cotizacion.
     *
     * `order_events` lleva registrando desde hace tiempo lo que le pasa a una
     * cotizacion —creada, enviada, aceptada o rechazada por el cliente,
     * comprobante subido, convertida en pedido— pero NO habia forma de leerlo:
     * el unico endpoint de eventos filtraba por `order_id`. Se escribia un
     * historial que nadie podia ver.
     */
    public function events(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);

        $eventos = \App\Models\OrderEvent::with('user:id,name')
            ->where('project_id', $project->id)
            ->where('quote_id', $quote->id)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->map(fn ($e) => [
                'titulo'  => $e->titulo,
                'detalle' => $e->label,
                'hace'    => $e->created_at->locale('es')->diffForHumans(null, true),
                'fecha'   => $e->created_at->format('d/m/Y H:i'),
                // Quien lo hizo. Sin usuario es el cliente actuando desde su
                // enlace, o el sistema: se dice, no se deja en blanco.
                'quien'   => $e->user?->name
                    ?? (str_contains($e->action, '_by_client') || $e->action === 'proof_uploaded'
                        ? 'el cliente' : 'el sistema'),
                // Color del punto en la linea de tiempo: lo decide QUE paso,
                // no la posicion del evento en la lista.
                'tono'    => match (true) {
                    str_contains($e->action, '_by_client'), $e->action === 'proof_uploaded' => 'cliente',
                    $e->action === 'converted'                                              => 'convertida',
                    in_array($e->action, ['created', 'quote_sent'], true)                   => 'exito',
                    default                                                                 => 'neutro',
                },
            ]);

        return response()->json(['eventos' => $eventos]);
    }

    public function destroy(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        // Borrarla dejaria al pedido sin origen (la FK es nullOnDelete: el
        // pedido sobreviviria huerfano y perderiamos la trazabilidad).
        $this->bloquearSiConvertida($quote, 'eliminarla');

        // Constancia ANTES de borrar: despues de `delete()` ya no hay de
        // donde sacar el numero, el cliente ni el importe. El evento queda
        // aunque la fila desaparezca.
        // SIN quote_id a proposito: la FK borra en cascada y el rastro se
        // iria con el documento. El numero y el importe viajan dentro del
        // evento, que es lo que queda para responder "que se borro".
        \App\Models\OrderEvent::log($project->id, 'deleted', [
            'numero'   => $quote->etiqueta,
            'quote_id' => $quote->id,
            'cliente'  => $quote->client_name,
            'total'    => \App\Support\LineMath::canon((string) $quote->total),
            'estado'   => \App\Support\QuoteStatus::comercial($quote->status),
        ]);

        $quote->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Convierte una cotización en un pedido real del Centro de Pedidos.
     *
     * Transaccional e idempotente (F1b): el lockForUpdate cierra la carrera del
     * doble clic que el abort_if solo no cubria (dos requests simultaneos
     * pasaban ambos el check y creaban 2 pedidos). Repetir la conversion
     * devuelve EL MISMO order_id con already:true. La FK orders.quote_id es la
     * fuente canonica; el evento 'converted' sigue como auditoria.
     */
    public function convert(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');

        try {
            return $this->convertTx($quote, $project);
        } catch (\Illuminate\Database\QueryException $e) {
            // Red final: si una insercion concurrente burlara el lock, el
            // UNIQUE de orders.quote_id aborta la transaccion. Se captura
            // FUERA de ella (conexion limpia tras rollback) y se responde
            // determinista. Doble verificacion: SQLSTATE 23000 (integridad)
            // Y el nombre de NUESTRA restriccion — no se enmascara otra.
            $esUnique = ($e->getCode() == 23000 || str_contains((string) $e->getCode(), '23'))
                && str_contains($e->getMessage(), 'orders_quote_id_unique');
            if ($esUnique) {
                $ganador = \App\Models\Order::where('quote_id', $quote->id)->first();
                // El ganador debe ser del MISMO proyecto que la quote; una
                // inconsistencia cross-project es 409, jamas un 200.
                abort_if(! $ganador || $ganador->project_id !== $quote->project_id,
                    409, 'Conflicto de conversión no resoluble.');
                return response()->json(['ok' => true, 'order_id' => $ganador->id, 'already' => true]);
            }
            throw $e;
        }
    }

    /** Nucleo transaccional de convert(); ver contrato en el metodo publico. */
    private function convertTx(Quote $quote, $project)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($quote, $project) {
            // Releer BAJO el lock y revalidar proyecto/estado dentro de el.
            $quote = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            abort_unless($quote->project_id === $project->id, 403);

            if (\App\Support\QuoteStatus::comercial($quote->status) === 'converted') {
                $existente = \App\Models\Order::where('quote_id', $quote->id)->first();

                if (! $existente) {
                    // Caso legacy: convertida antes de la FK. Resolucion por el
                    // evento indirecto aplicando A/B/C EN CALIENTE (criterios
                    // identicos al preflight), no una heuristica paralela.
                    // JAMAS se crea otro pedido.
                    $candidatos = \App\Models\OrderEvent::where('quote_id', $quote->id)
                        ->whereNotNull('order_id')->distinct()->pluck('order_id');

                    if ($candidatos->count() === 1) {
                        $orderId = $candidatos->first();
                        // A: ese pedido no puede tener eventos de OTRA quote
                        $ambiguoA = \App\Models\OrderEvent::where('order_id', $orderId)
                            ->whereNotNull('quote_id')
                            ->where('quote_id', '<>', $quote->id)->exists();
                        // C: mismo proyecto (B esta cubierto: count()===1 sobre
                        // los eventos de ESTA quote)
                        $candidato = $ambiguoA ? null
                            : \App\Models\Order::where('project_id', $quote->project_id)
                                ->find($orderId);

                        if ($candidato) {
                            // whereNull: si otra peticion fijo la FK entre
                            // medias, no se pisa; se relee y usa lo fijado.
                            \App\Models\Order::whereKey($candidato->id)
                                ->whereNull('quote_id')
                                ->update(['quote_id' => $quote->id]);
                            $existente = $candidato->fresh();
                            $existente = $existente->quote_id === $quote->id ? $existente : null;
                        }
                    }
                }

                abort_if(! $existente, 409, 'Convertida sin pedido rastreable (caso legacy).');

                return response()->json(['ok' => true, 'order_id' => $existente->id, 'already' => true]);
            }

            // Desalineado inverso: status no dice converted pero YA existe un
            // pedido con esta FK (p. ej. rollback parcial). Determinista, sin
            // permitir jamas un segundo pedido ni un 500 por UNIQUE.
            $desalineado = \App\Models\Order::where('quote_id', $quote->id)->first();
            if ($desalineado) {
                $quote->update(['status' => 'converted']); // re-alinear
                return response()->json(['ok' => true, 'order_id' => $desalineado->id, 'already' => true]);
            }

            // --- Guards de CREACION (F1c) ---------------------------------
            // ORDEN CRITICO: van DESPUES de las ramas idempotente y de
            // realineacion. Si fueran antes, una cotizacion ya convertida
            // recibiria 422 en lugar de devolver su pedido, y romperiamos el
            // contrato que F1b dejo cerrado (hay 5 convertidas reales).
            abort_unless($project->hasModule('orders'), 422,
                'Este proyecto no tiene el módulo de Pedidos activo, así que no puede recibir la conversión.');
            abort_unless(\App\Support\QuoteStatus::comercial($quote->status) === 'accepted', 422,
                'Solo se convierte en pedido una cotización aceptada por el cliente. '
                . 'Esta está en estado "' . \App\Support\QuoteStatus::comercialPresentacion($quote->status)['label'] . '".');

            $quote->load('items');

            $order = $project->orders()->create([
                'client_id'         => $quote->client_id,
                'client_name'       => $quote->client_name,
                'client_phone'      => $quote->client_phone,
                'quote_id'          => $quote->id,
                'notes'             => $quote->notes,
                'payment_method'    => $quote->payment_method,
                'payment_condition' => $quote->payment_condition,
                'sales_channel'     => 'cotizacion',
                // Total = SUMA de lineas via LineMath (con descuentos), no el
                // agregado redondeado por su cuenta.
                'total'             => \App\Support\LineMath::sum($quote->items),
                'status'            => 'pending',
            ]);
            foreach ($quote->items as $item) {
                // El descuento viaja CON la linea; nada de precio unitario neto.
                $order->items()->create([
                    'name'     => $item->description,
                    'price'    => $item->price,
                    'quantity' => $item->quantity,
                    'discount' => $item->discount ?? 0,
                ]);
            }

            $quote->update(['status' => 'converted']);
            \App\Models\OrderEvent::log($project->id, 'converted', ['order_id' => $order->id], $order->id, $quote->id);

            return response()->json(['ok' => true, 'order_id' => $order->id, 'already' => false]);
        });
    }


    /** Clona una cotización (cliente, condiciones e ítems) como borrador nuevo, para clientes recurrentes. */
    public function duplicate(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);

        // El numero NO se copia: una copia es un documento nuevo y tiene que
        // llevar su propio correlativo. Si se replicara, chocaria contra el
        // UNIQUE del negocio (o peor, dos documentos distintos compartirian
        // nombre y nadie sabria a cual se refiere el cliente).
        $copy = $quote->replicate([
            'token', 'status', 'sent_at', 'rejected_at', 'reject_reason',
            'payment_status', 'paid_amount', 'paid_at', 'payment_proof_url', 'payment_proof_at', 'seen_at',
            'correlativo', 'numero',
        ]);
        $copy->status = 'draft';
        $copy->payment_status = 'pending';
        $copy->save();

        foreach ($quote->items as $item) {
            $copy->items()->create(['description' => $item->description, 'price' => $item->price, 'quantity' => $item->quantity, 'discount' => $item->discount ?? 0]);
        }

        return response()->json(['quote' => $copy->load('items')]);
    }

    /** El vendedor abrió esta cotización: apaga el indicador de "cambio nuevo". */
    public function markSeen(Quote $quote)
    {
        /** @var \App\Models\Project $project */
        $project = app('active_project');
        abort_unless($quote->project_id === $project->id, 403);
        $quote->update(['seen_at' => now()]);
        return response()->json(['ok' => true]);
    }

    // ── Portal Facturación ────────────────────────────────────────────────────

    private function projectBySlug(string $slug): Project
    {
        return Project::where('slug', $slug)->firstOrFail();
    }

    public function indexPortal(string $slug)
    {
        $project = $this->projectBySlug($slug);
        $quotes  = $project->quotes()->with('client')->latest()->get()->map(fn($q) => [
            'id'          => $q->id,
            'client_name' => $q->client_name,
            'total'       => (float) $q->total,
            'status'      => $q->status,
            'valid_until' => $q->valid_until?->format('Y-m-d'),
            'created_at'  => $q->created_at->format('d/m/Y'),
            'items_count' => $q->items()->count(),
        ]);
        $showBase        = url("/f/{$slug}/cotizaciones");
        $updateBase      = url("/f/{$slug}/cotizaciones");
        $boletaCreateUrl = route('facturacion.boletas.create', $slug);
        $facturaCreateUrl= route('facturacion.facturas.create', $slug);
        return view('facturacion.cotizaciones.index', compact('project', 'quotes', 'showBase', 'updateBase', 'boletaCreateUrl', 'facturaCreateUrl'));
    }

    public function createPortal(string $slug)
    {
        $project   = $this->projectBySlug($slug);
        $clients   = $project->clients()->orderBy('name')->get(['id','name','email','phone']);
        $productos = $project->products()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'desc'=>$p->description??$p->name,'price'=>(float)$p->price]);
        $servicios = $project->services()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'desc'=>$s->description??$s->name,'price'=>(float)$s->price]);
        $catalogo  = $productos->merge($servicios)->values();
        $rucUrl    = route('facturacion.ruc.lookup', $slug);
        return view('facturacion.cotizaciones.create', compact('project', 'clients', 'catalogo', 'rucUrl'));
    }

    public function showPortal(string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        $quote->load('items');
        return response()->json($quote);
    }

    public function storePortal(Request $request, string $slug)
    {
        $project = $this->projectBySlug($slug);
        app()->instance('active_project', $project);
        return $this->store($request);
    }

    public function updatePortal(Request $request, string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        app()->instance('active_project', $project);
        return $this->update($request, $quote);
    }

    public function destroyPortal(string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        app()->instance('active_project', $project);
        return $this->destroy($quote);
    }

    public function editPortal(string $slug, $id)
    {
        $project   = $this->projectBySlug($slug);
        $quote     = Quote::where('id', $id)->where('project_id', $project->id)->with('items')->firstOrFail();
        $clients   = $project->clients()->orderBy('name')->get(['id','name','email','phone']);
        $productos = $project->products()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($p) => ['id'=>$p->id,'name'=>$p->name,'desc'=>$p->description??$p->name,'price'=>(float)$p->price]);
        $servicios = $project->services()->orderBy('name')->get(['id','name','description','price'])
                        ->map(fn($s) => ['id'=>$s->id,'name'=>$s->name,'desc'=>$s->description??$s->name,'price'=>(float)$s->price]);
        $catalogo         = $productos->merge($servicios)->values();
        $boletaCreateUrl  = route('facturacion.boletas.create', $slug);
        $facturaCreateUrl = route('facturacion.facturas.create', $slug);
        $updateUrl        = url("/f/{$slug}/cotizaciones/{$id}/full");
        $indexUrl         = route('facturacion.cotizaciones', $slug);
        $rucUrl           = route('facturacion.ruc.lookup', $slug);
        return view('facturacion.cotizaciones.edit', compact(
            'project','quote','clients','catalogo',
            'boletaCreateUrl','facturaCreateUrl','updateUrl','indexUrl','rucUrl'
        ));
    }

    public function updateFullPortal(Request $request, string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();

        $data = $request->validate([
            'client_name'         => 'required|string|max:100',
            'client_phone'        => 'nullable|string|max:30',
            'client_email'        => 'nullable|email|max:150',
            'client_doc_type'     => 'nullable|string|max:20',
            'client_doc_number'   => 'nullable|string|max:20',
            'client_address'      => 'nullable|string|max:300',
            'notes'               => 'nullable|string',
            'valid_until'         => 'nullable|date',
            'payment_method'      => 'nullable|string|max:80',
            'payment_condition'   => 'nullable|string|max:80',
            'items'               => 'required|array|min:1|max:200',
            'items.*.description' => 'required|string',
            'items.*.price'       => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'items.*.discount'    => 'nullable|numeric|decimal:0,2|min:0|max:100',
            'items.*.quantity'    => 'required|integer|min:1|max:10000',
        ]);

        $totalCents = \App\Support\LineMath::sumCents($data['items']);
        // quotes.total es decimal(10,2): el maximo representable es
        // 99,999,999.99. Un documento que lo exceda se rechaza con 422 en vez
        // de estallar en la insercion.
        abort_if($totalCents > 9_999_999_999, 422, 'El total excede el máximo permitido (99,999,999.99).');
        $total = \App\Support\LineMath::format($totalCents);

        $quote->update([
            'client_name'       => $data['client_name'],
            'client_phone'      => $data['client_phone'] ?? null,
            'client_email'      => $data['client_email'] ?? null,
            'client_doc_type'   => $data['client_doc_type'] ?? null,
            'client_doc_number' => $data['client_doc_number'] ?? null,
            'client_address'    => $data['client_address'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'valid_until'       => $data['valid_until'] ?? null,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'total'             => $total,
        ]);

        $quote->items()->delete();
        foreach ($data['items'] as $item) {
            $quote->items()->create($item);
        }

        return response()->json(['ok' => true, 'quote' => $quote->load('items')]);
    }

    public function convertirPortal(Request $request, string $slug, $id)
    {
        $project = $this->projectBySlug($slug);
        $quote   = Quote::where('id', $id)->where('project_id', $project->id)->firstOrFail();
        $quote->load('items');

        // El gate de F1b se retira aqui: `invoice_items` ya guarda `discount`
        // y la linea fiscal se calcula en centavos enteros, asi que
        // `unit_price x cantidad x (1 - descuento)` cuadra exactamente con
        // `total` y el descuadre que motivaba el bloqueo no puede ocurrir.
        // Ese 422 no solo frenaba la factura: impedia CONVERTIR la cotizacion,
        // o sea, cerrar la venta.

        $docType = $request->input('type', 'boleta');
        $defaultSerie = $docType === 'factura'
            ? ($project->setting('serie_factura') ?? 'FFF1')
            : ($project->setting('serie_boleta')  ?? 'BBB1');

        $igvRate  = 0.18;
        $subtotal = 0;
        $igvTotal = 0;
        $items    = [];

        // F4: en centavos enteros y CON el descuento de la linea. Antes esto
        // multiplicaba flotantes e ignoraba `discount` por completo: el
        // comprobante habria cobrado el precio de lista, no el pactado. De ahi
        // venia el bloqueo, y por eso no bastaba con quitarlo.
        $subtotalCents = 0;
        $igvTotalCents = 0;

        foreach ($quote->items as $qi) {
            $qty       = (float) $qi->quantity;
            $qtyMilli  = (int) round($qty * 1000);
            $precio    = \App\Support\LineMath::canon((string) $qi->price);
            $descuento = \App\Support\LineMath::canon((string) ($qi->discount ?? 0));
            $precioCents = \App\Support\LineMath::toCents($precio);
            $bp          = \App\Support\LineMath::toBasisPoints($descuento);

            $lineaCents = intdiv($precioCents * $qtyMilli * (10000 - $bp) + 5_000_000, 10_000_000);
            // La cotizacion se emite con IGV incluido, igual que antes.
            $subCents = intdiv($lineaCents * 100 + 59, 118);
            $igvCents = $lineaCents - $subCents;

            $subtotalCents += $subCents;
            $igvTotalCents += $igvCents;
            $items[] = [
                'description' => $qi->description,
                'unit'        => 'NIU',
                'quantity'    => $qty,
                'unit_price'  => $precio,
                'discount'    => $descuento,
                'igv_amount'  => \App\Support\LineMath::format($igvCents),
                'total'       => \App\Support\LineMath::format($lineaCents),
            ];
        }

        $subtotal = \App\Support\LineMath::format($subtotalCents);
        $igvTotal = \App\Support\LineMath::format($igvTotalCents);

        $correlativo = Invoice::nextCorrelativo($project->id, $docType, $defaultSerie);
        $numero      = Invoice::buildNumero($defaultSerie, $correlativo);

        $invoice = $project->invoices()->create([
            'quote_id'            => $quote->id,
            'type'                => $docType,
            'serie'               => $defaultSerie,
            'correlativo'         => $correlativo,
            'numero'              => $numero,
            'issue_date'          => now()->toDateString(),
            'emisor_razon_social' => $project->setting('razon_social') ?? $project->name,
            'emisor_ruc'          => $project->setting('ruc'),
            'emisor_direccion'    => $project->address,
            'client_name'         => $quote->client_name,
            'client_phone'        => $quote->client_phone,
            'client_email'        => $quote->client_email,
            'client_doc_type'     => $quote->client_doc_type,
            'client_doc_number'   => $quote->client_doc_number,
            'client_address'      => $quote->client_address,
            'subtotal'            => $subtotal,
            'igv'                 => $igvTotal,
            'total'               => \App\Support\LineMath::format($subtotalCents + $igvTotalCents),
            'currency'            => $project->setting('currency') ?? 'PEN',
            'igv_included'        => true,
            'payment_method'      => $quote->payment_method,
            'status'              => 'issued',
            'notes'               => $quote->notes,
        ]);

        foreach ($items as $item) {
            $invoice->items()->create($item);
        }

        $quote->update(['status' => 'accepted']);

        $redirectUrl = $docType === 'factura'
            ? route('facturacion.facturas', $slug)
            : route('facturacion.boletas', $slug);

        return response()->json([
            'ok'          => true,
            'invoice_id'  => $invoice->id,
            'numero'      => $invoice->numero,
            'redirect'    => $redirectUrl,
        ]);
    }
}

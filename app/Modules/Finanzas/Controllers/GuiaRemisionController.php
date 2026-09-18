<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Jobs\EnviarGuiaASunat;
use App\Modules\Finanzas\Models\GuiaRemision;
use App\Modules\Finanzas\Models\Invoice;
use App\Models\Project;
use App\Modules\Finanzas\Support\Sunat\Catalogos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Guías de remisión: el documento que viaja con la mercadería.
 *
 * Se emite antes de que salga el camión, no después: la guía tiene que ir
 * físicamente con los bienes. Por eso puede nacer de una factura ya emitida
 * —el caso normal, una venta que se despacha— o suelta, cuando el traslado no
 * es una venta (entre almacenes propios, una devolución al proveedor).
 */
class GuiaRemisionController extends Controller
{
    public function index(Request $request)
    {
        $project = $this->negocio();

        $guias = $project->guiasRemision()
            ->with('items')
            ->latest('id')
            ->limit(200)
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['guias' => $guias]);
        }

        return view('finanzas::facturacion.guias.index', [
            'project'     => $project,
            'guias'       => $guias,
            // Guias se abria SIEMPRE con el shell del panel: quien entraba desde
            // Ventas acababa en la cara de Configuracion sin haberla pedido. El
            // shell lo decide la ruta por la que se entro, igual que el resto.
            'portalLayout' => $request->routeIs('bixosales.*') ? 'comercial' : 'panel',
            'serie'       => $this->serie($project),
            'motivos'     => Catalogos::MOTIVOS_TRASLADO,
            'modalidades' => Catalogos::MODALIDADES_TRASLADO,
            'unidades'    => Catalogos::UNIDADES,
            // El MISMO catalogo que los comprobantes: en la guia se teclaba
            // la descripcion a mano, con sus erratas, y la linea salia sin
            // enlace al producto.
            'catalogo'    => \App\Modules\Finanzas\Support\CatalogoDocumentos::para($project),
            // Solo las facturas y boletas aceptadas pueden respaldar un traslado.
            'comprobantes' => $project->invoices()
                ->whereIn('type', ['factura', 'boleta'])
                ->where('sunat_status', 'accepted')
                ->latest('id')->limit(50)
                ->get(['id', 'numero', 'client_name', 'client_doc_number', 'client_address']),
        ]);
    }

    /** Lo que el formulario necesita: catálogos y, si viene de una venta, sus datos. */
    /** Direccion y ubigeo de la ultima guia emitida a ese documento. */
    private function ultimoDestino($project, ?string $doc): ?array
    {
        $doc = preg_replace('/\D/', '', (string) $doc);
        if ($doc === '') {
            return null;
        }

        $previa = $project->guiasRemision()
            ->where('destinatario_doc_numero', $doc)
            ->whereNotNull('llegada_ubigeo')->where('llegada_ubigeo', '!=', '')
            ->latest('id')
            ->first(['llegada_ubigeo', 'llegada_direccion']);

        return $previa ? [
            'ubigeo'    => $previa->llegada_ubigeo,
            'direccion' => $previa->llegada_direccion,
        ] : null;
    }

    /**
     * HISTORICO de guias: buscar y consultar lo ya emitido.
     *
     * La pantalla de Guias mezcla la lista corta con el formulario de emitir:
     * sirve para sacar la siguiente, no para encontrar la de hace dos meses.
     * Aqui solo se busca, con los mismos filtros que "Comprobantes emitidos"
     * para que las dos pantallas se sientan una sola.
     */
    public function consulta(Request $request)
    {
        $project = $this->negocio();

        $q      = trim((string) $request->query('q', ''));
        $estado = (string) $request->query('estado', '');
        $desde  = (string) $request->query('desde', '');
        $hasta  = (string) $request->query('hasta', '');

        // "T001-1" o "t001 1" encuentra T001-00000001: nadie teclea ocho digitos.
        $numeroCompleto = null;
        if (preg_match('/^([A-Za-z]{1,2}\d{3})[\s-]*(\d{1,8})$/', $q, $m)) {
            $numeroCompleto = strtoupper($m[1]).'-'.str_pad($m[2], 8, '0', STR_PAD_LEFT);
        }

        // La fecha que importa en una guia es la del traslado; si no la tiene,
        // la de emision. El filtro mira la misma fecha que se muestra.
        $fechaVisible = 'COALESCE(fecha_traslado, created_at)';

        $guias = $project->guiasRemision()
            ->with('invoice:id,numero')
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
                ->where('numero', 'like', "%{$q}%")
                ->when($numeroCompleto, fn ($x) => $x->orWhere('numero', $numeroCompleto))
                ->orWhere('destinatario_nombre', 'like', "%{$q}%")
                ->orWhere('destinatario_doc_numero', 'like', "%{$q}%")
                ->orWhere('vehiculo_placa', 'like', "%{$q}%")))
            ->when($estado !== '', fn ($b) => match ($estado) {
                'sin_enviar' => $b->whereNull('sunat_status'),
                default      => $b->where('sunat_status', $estado),
            })
            ->when($desde !== '', fn ($b) => $b->whereRaw("DATE({$fechaVisible}) >= ?", [$desde]))
            ->when($hasta !== '', fn ($b) => $b->whereRaw("DATE({$fechaVisible}) <= ?", [$hasta]))
            ->orderByRaw("{$fechaVisible} DESC")
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('finanzas::facturacion.guias.consulta', [
            'project'      => $project,
            'guias'        => $guias,
            'filtros'      => compact('q', 'estado', 'desde', 'hasta'),
            'motivos'      => Catalogos::MOTIVOS_TRASLADO,
            'portalLayout' => $request->routeIs('bixosales.*') ? 'comercial' : 'panel',
        ]);
    }

    public function opciones(Request $request)
    {
        $project = $this->negocio();
        $invoice = null;

        if ($request->filled('invoice_id')) {
            $invoice = Invoice::where('project_id', $project->id)
                ->where('id', $request->integer('invoice_id'))
                ->with('items')
                ->first();
        }

        return response()->json([
            'serie'       => $this->serie($project),
            'motivos'     => Catalogos::MOTIVOS_TRASLADO,
            'modalidades' => Catalogos::MODALIDADES_TRASLADO,
            'unidades'    => Catalogos::UNIDADES,
            // El peso se declara en kilos o toneladas; el resto no aplica aquí.
            'unidades_peso' => ['KGM' => 'KILOGRAMO', 'TNE' => 'TONELADAS'],
            'desde_venta' => $invoice ? [
                'numero'          => $invoice->numero,
                'destinatario'    => $invoice->client_name,
                'doc_tipo'        => Catalogos::codigoDocumentoIdentidad($invoice->client_doc_type, $invoice->client_doc_number),
                'doc_numero'      => $invoice->client_doc_number,
                'direccion'       => $invoice->client_address,
                /* El ubigeo de destino no vive en la factura ni en la ficha
                   del cliente: SUNAT lo exige y se tecleaba a mano cada vez.
                   La ultima guia emitida a ESE documento si lo tiene, y el
                   destino de un cliente rara vez cambia. */
                'ultimo_destino'  => $this->ultimoDestino($project, $invoice->client_doc_number),
                'items'           => $invoice->items->map(fn ($i) => [
                    'description' => $i->description,
                    'unit'        => Catalogos::codigoUnidad($i->unit),
                    'quantity'    => (float) $i->quantity,
                ])->values(),
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $project = $this->negocio();

        /* Traslado privado que NO va en vehiculo M1/L: ahi el vehiculo y el
           conductor siguen siendo obligatorios. */
        /* DOBLE EMISION. Con el camion en la puerta y mala señal, el
           operador pulsa "Emitir", tarda, recarga y vuelve a pulsar: salian
           dos guias y dos correlativos quemados. La huella del intento la
           manda el navegador; el segundo envio recibe la guia ya creada. */
        $huella = trim((string) $request->header('X-Idempotencia'));
        $candado = null;
        if ($huella !== '') {
            $candado = 'guia:'.$project->id.':'.substr(preg_replace('/[^A-Za-z0-9\-]/', '', $huella), 0, 64);

            if ($yaEmitida = Cache::get($candado)) {
                $previa = GuiaRemision::where('project_id', $project->id)->find($yaEmitida);
                if ($previa) {
                    return response()->json([
                        'ok' => true, 'repetida' => true,
                        'guia' => $previa->only(['id', 'numero']),
                        'message' => "Esta guía ya se emitió: {$previa->numero}.",
                    ]);
                }
            }
            if (! Cache::add($candado.':curso', 1, 30)) {
                return response()->json([
                    'message' => 'Esa guía ya se está emitiendo. Espera un momento.',
                ], 409);
            }
            /* El candado se suelta pase lo que pase. Antes solo se liberaba al
               crear la guia con exito: si la emision fallaba (un dato que SUNAT
               rechaza, una validacion), quedaba puesto y bloqueaba 30 segundos
               al operador que ya habia corregido el dato. Con el camion en la
               puerta, esos 30 segundos a ciegas son el peor momento posible. */
            $liberar = fn () => Cache::forget($candado.':curso');
            app()->terminating($liberar);
        }

        $exigeVehiculo = (string) $request->input('modalidad') === '02'
            && ! $request->boolean('vehiculo_m1l');

        $data = $request->validate([
            /* El comprobante que respalda el traslado tiene que existir de
               verdad ante SUNAT. Antes valia cualquier id del negocio: un
               borrador (sin numero), uno rechazado o uno ya dado de baja
               entraban igual, y se declaraba un traslado respaldado por un
               comprobante inexistente. */
            'invoice_id'              => ['nullable', 'integer', Rule::exists('invoices', 'id')
                ->where('project_id', $project->id)
                ->where('sunat_status', 'accepted')],
            'destinatario_nombre'     => ['required', 'string', 'max:200'],
            'destinatario_doc_tipo'   => ['nullable', 'string', Rule::in(array_keys(Catalogos::DOCUMENTOS_IDENTIDAD))],
            'destinatario_doc_numero' => ['nullable', 'string', 'max:15'],

            'motivo_codigo'      => ['required', Rule::in(array_keys(Catalogos::MOTIVOS_TRASLADO))],
            'motivo_descripcion' => ['nullable', 'string', 'max:120'],
            /* La guia viaja EN el camion: una fecha de hace seis meses o del
               2030 la rechaza SUNAT, y eso se descubre con la mercaderia ya
               en carretera. El comprobante ya tenia esta cota; la guia no. */
            'fecha_traslado'     => ['required', 'date',
                'after_or_equal:'.now()->subDays(3)->toDateString(),
                'before_or_equal:'.now()->addDays(30)->toDateString()],
            'modalidad'          => ['required', Rule::in(array_keys(Catalogos::MODALIDADES_TRASLADO))],

            // Un camion no pesa 0.001 kg ni 999999 t: los extremos son erratas.
            'peso_total'  => ['required', 'numeric', 'gt:0', 'max:100000'],
            'peso_unidad' => ['nullable', Rule::in(['KGM', 'TNE'])],
            'bultos'      => ['nullable', 'integer', 'min:0'],

            /* UBIGEO. Sin el, el envio caia en '150101' (Lima-Lima-Lima)
               hardcodeado: un traslado Arequipa->Cusco se declaraba como
               Lima->Lima y SUNAT lo ACEPTABA. La guia quedaba con datos
               falsos y en un control de carretera no cuadra con la ruta. */
            'partida_ubigeo'    => ['required', 'digits:6'],
            'partida_direccion' => ['required', 'string', 'max:300'],
            'llegada_ubigeo'    => ['required', 'digits:6'],
            'llegada_direccion' => ['required', 'string', 'max:300'],

            // Transporte público: hace falta saber quién lo lleva.
            // `size:11` admitia "ABCDEFGHIJK": SUNAT lo rechaza (error 2564).
            'transportista_ruc'          => ['required_if:modalidad,01', 'nullable', 'digits:11'],
            'transportista_razon_social' => ['required_if:modalidad,01', 'nullable', 'string', 'max:200'],
            'transportista_mtc'          => ['nullable', 'string', 'max:20'],

            // Transporte privado: con qué vehículo y quién conduce.
            'vehiculo_m1l'          => ['nullable', 'boolean'],
            'transbordo_programado' => ['nullable', 'boolean'],

            /* En privado el vehiculo y el conductor son obligatorios, SALVO
               que el traslado vaya en categoria M1 o L (auto, camioneta,
               moto): ahi SUNAT exime de declararlos. */
            'vehiculo_placa'       => [$exigeVehiculo ? 'required' : 'nullable', 'nullable', 'string', 'max:10'],
            'conductor_doc_tipo'   => ['nullable', 'string', 'max:2'],
            'conductor_doc_numero' => [$exigeVehiculo ? 'required' : 'nullable', 'nullable', 'string', 'max:15'],
            'conductor_nombres'    => [$exigeVehiculo ? 'required' : 'nullable', 'nullable', 'string', 'max:120'],
            'conductor_apellidos'  => [$exigeVehiculo ? 'required' : 'nullable', 'nullable', 'string', 'max:120'],
            'conductor_licencia'   => [$exigeVehiculo ? 'required' : 'nullable', 'nullable', 'string', 'max:20'],

            'items'                 => ['required', 'array', 'min:1'],
            'items.*.description'   => ['required', 'string', 'max:300'],
            'items.*.unit'          => ['nullable', 'string', 'max:30'],
            'items.*.quantity'      => ['required', 'numeric', 'gt:0'],
            'items.*.codigo'        => ['nullable', 'string', 'max:60'],
            'items.*.product_id'    => ['nullable', 'integer'],

            'observaciones' => ['nullable', 'string', 'max:1000'],
        ], [
            'transportista_ruc.required_if'          => 'En transporte público hay que declarar el RUC del transportista.',
            'transportista_razon_social.required_if' => 'En transporte público hay que declarar la razón social del transportista.',
            'partida_ubigeo.required'  => 'Falta el ubigeo del punto de partida (6 dígitos).',
            'llegada_ubigeo.required'  => 'Falta el ubigeo del punto de llegada (6 dígitos).',
            'partida_ubigeo.digits'    => 'El ubigeo de partida son 6 dígitos.',
            'llegada_ubigeo.digits'    => 'El ubigeo de llegada son 6 dígitos.',
            'invoice_id.exists'        => 'Ese comprobante no existe o aún no fue aceptado por SUNAT.',
            'fecha_traslado.after_or_equal'  => 'La fecha de traslado no puede ser tan antigua.',
            'fecha_traslado.before_or_equal' => 'La fecha de traslado está demasiado lejos.',
            'transportista_ruc.digits' => 'El RUC del transportista son 11 dígitos.',
            'vehiculo_placa.required'                => 'En transporte privado hay que declarar la placa, salvo que el traslado vaya en vehículo M1 o L.',
            'conductor_doc_numero.required'          => 'En transporte privado hay que declarar el documento del conductor, salvo traslado en vehículo M1 o L.',
            'conductor_licencia.required'            => 'En transporte privado hay que declarar la licencia del conductor, salvo traslado en vehículo M1 o L.',
        ]);

        $serie = $this->serie($project);

        $guia = DB::transaction(function () use ($project, $data, $serie) {
            [$correlativo, $numero] = GuiaRemision::emitirNumero($project->id, $serie);

            $guia = $project->guiasRemision()->create([
                'invoice_id'  => $data['invoice_id'] ?? null,
                'serie'       => $serie,
                'correlativo' => $correlativo,
                'numero'      => $numero,

                'emisor_razon_social' => $project->setting('razon_social') ?? $project->name,
                'emisor_ruc'          => $project->setting('ruc'),

                'destinatario_nombre'     => $data['destinatario_nombre'],
                'destinatario_doc_tipo'   => $data['destinatario_doc_tipo']
                    ?? Catalogos::codigoDocumentoIdentidad(null, $data['destinatario_doc_numero'] ?? null),
                'destinatario_doc_numero' => $data['destinatario_doc_numero'] ?? null,

                'motivo_codigo'      => $data['motivo_codigo'],
                'motivo_descripcion' => $data['motivo_descripcion']
                    ?? Catalogos::MOTIVOS_TRASLADO[$data['motivo_codigo']],
                'fecha_traslado'     => $data['fecha_traslado'],
                'modalidad'          => $data['modalidad'],

                'peso_total'  => $data['peso_total'],
                'peso_unidad' => $data['peso_unidad'] ?? 'KGM',
                'bultos'      => $data['bultos'] ?? null,

                'partida_ubigeo'    => $data['partida_ubigeo'] ?? $project->setting('ubigeo'),
                'partida_direccion' => $data['partida_direccion'],
                'llegada_ubigeo'    => $data['llegada_ubigeo'] ?? null,
                'llegada_direccion' => $data['llegada_direccion'],

                'transportista_ruc'          => $data['transportista_ruc'] ?? null,
                'transportista_razon_social' => $data['transportista_razon_social'] ?? null,
                'transportista_mtc'          => $data['transportista_mtc'] ?? null,

                'vehiculo_placa'        => $data['vehiculo_placa'] ?? null,
                'vehiculo_m1l'          => (bool) ($data['vehiculo_m1l'] ?? false),
                'transbordo_programado' => (bool) ($data['transbordo_programado'] ?? false),
                'conductor_doc_tipo'   => $data['conductor_doc_tipo'] ?? '1',
                'conductor_doc_numero' => $data['conductor_doc_numero'] ?? null,
                'conductor_nombres'    => $data['conductor_nombres'] ?? null,
                'conductor_apellidos'  => $data['conductor_apellidos'] ?? null,
                'conductor_licencia'   => $data['conductor_licencia'] ?? null,

                'observaciones' => $data['observaciones'] ?? null,
                'status'        => 'issued',
                'created_by'    => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $guia->items()->create([
                    'product_id'  => $item['product_id'] ?? null,
                    'codigo'      => $item['codigo'] ?? null,
                    'description' => $item['description'],
                    // Se guarda ya traducido: lo que viaja al XML no se calcula
                    // dos veces ni depende de cómo se escribiera el producto.
                    'unit'        => Catalogos::codigoUnidad($item['unit'] ?? null),
                    'quantity'    => $item['quantity'],
                ]);
            }

            return $guia;
        });

        $guia->update(['sunat_status' => 'pending']);
        EnviarGuiaASunat::dispatch($guia->id);

        // Con la guia ya creada, el reintento devuelve esta misma.
        if ($candado) {
            Cache::put($candado, $guia->id, 30);
            Cache::forget($candado.':curso');
        }

        return response()->json([
            'ok'      => true,
            'guia'    => $guia->load('items'),
            'message' => 'Guía '.$guia->numero.' emitida. Enviando a SUNAT...',
        ]);
    }

    /**
     * La representacion impresa: lo que viaja fisicamente con la mercaderia.
     * Sin esto la guia existia en SUNAT pero el chofer no llevaba nada que
     * ensenar en un control.
     */
    public function pdf(GuiaRemision $guia)
    {
        $project = $this->soloDeMiNegocio($guia);
        $guia->load('items', 'invoice');

        // Misma eleccion que los comprobantes: el ajuste `invoice_template`
        // manda sobre toda la papeleria del negocio, no solo sobre la factura.
        $vista = (string) $project->setting('invoice_template') === 'clasico'
            ? 'finanzas::facturacion.guias.pdf-clasico'
            // La moderna se llama `pdf-simple`, no `pdf`: apuntar a un nombre
            // inexistente reventaba la impresion con "View not found".
            : 'finanzas::facturacion.guias.pdf-simple';

        $hoja = view($vista, compact('project', 'guia'));

        // `?descargar=1` entrega el PDF como archivo (para adjuntarlo a un
        // correo); sin el, la hoja imprimible de siempre.
        return request()->boolean('descargar')
            ? $this->comoArchivoPdf($hoja, $guia)
            : $hoja;
    }

    /**
     * El PDF de verdad, no la vista para imprimir: Chrome sin ventana rinde la
     * MISMA hoja y se entrega como archivo con su nombre. Mismo mecanismo que
     * los comprobantes.
     *
     * Si Chrome no esta o falla, se devuelve la vista imprimible en lugar de
     * un error: el usuario conserva siempre el camino de Ctrl+P.
     */
    private function comoArchivoPdf(\Illuminate\Contracts\View\View $hoja, GuiaRemision $guia)
    {
        $chrome = collect([
            '/usr/bin/google-chrome-stable', '/usr/bin/google-chrome',
            '/usr/bin/chromium-browser', '/usr/bin/chromium',
        ])->first(fn ($bin) => is_executable($bin));

        if (! $chrome) {
            return $hoja;
        }

        $tmp    = sys_get_temp_dir();
        $id     = \Illuminate\Support\Str::random(12);
        $html   = "{$tmp}/gre-{$id}.html";
        $pdf    = "{$tmp}/gre-{$id}.pdf";
        $perfil = "{$tmp}/gre-perfil-{$id}";

        try {
            file_put_contents($html, $hoja->render());

            \Illuminate\Support\Facades\Process::timeout(60)->run([
                $chrome, '--headless', '--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage',
                '--no-pdf-header-footer',
                // La hoja calcula su alto con JS y trae el QR de fuera: sin
                // margen de tiempo, Chrome imprime antes de que termine.
                '--virtual-time-budget=10000',
                "--user-data-dir={$perfil}",
                "--print-to-pdf={$pdf}",
                'file://'.$html,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('PDF: Chrome no pudo generar la guia', [
                'guia' => $guia->numero, 'error' => $e->getMessage(),
            ]);
        }

        @unlink($html);
        \Illuminate\Support\Facades\File::deleteDirectory($perfil);

        // Un PDF de cuatro bytes es un fallo silencioso: mejor la vista.
        if (! is_file($pdf) || filesize($pdf) < 1024) {
            @unlink($pdf);

            return $hoja;
        }

        return response()->download($pdf, $guia->numero.'.pdf')->deleteFileAfterSend();
    }

    public function show(GuiaRemision $guia)
    {
        $this->soloDeMiNegocio($guia);

        return response()->json(['guia' => $guia->load('items', 'invoice')]);
    }

    /** Reintenta el envío de una guía que no llegó a SUNAT. */
    public function enviar(GuiaRemision $guia)
    {
        $this->soloDeMiNegocio($guia);

        abort_if($guia->sunat_status === 'accepted', 422, 'Esta guía ya fue aceptada por SUNAT.');

        $guia->update(['sunat_status' => 'pending', 'sunat_error' => null]);
        EnviarGuiaASunat::dispatch($guia->id);

        /* El historico manda un formulario normal, no un fetch: devolverle
           JSON dejaba al usuario mirando {"ok":true,...} en una pagina en
           blanco, y perdiendo la busqueda que tenia puesta. El mismo guard
           que ya lleva InvoiceController::sendSunat. */
        if (! request()->expectsJson()) {
            return back()->with('ok', "Enviando {$guia->numero} a SUNAT. El estado se actualiza en unos segundos.");
        }

        return response()->json(['ok' => true, 'message' => 'Enviando la guía a SUNAT...']);
    }

    public function destroy(GuiaRemision $guia)
    {
        $this->soloDeMiNegocio($guia);

        abort_unless(
            $guia->sePuedeBorrar(),
            422,
            'Esta guía ya fue aceptada por SUNAT: no se borra, se anula ante SUNAT.'
        );

        $guia->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * La serie de guía del negocio.
     *
     * SUNAT exige que la de un remitente empiece por T. Si alguien configura
     * otra cosa se ignora, porque una serie mal formada tumba todas las guías
     * y el error se descubre con el camión ya en la carretera.
     */
    private function serie(Project $project): string
    {
        $serie = strtoupper(trim((string) $project->setting('serie_guia')));

        return ($serie !== '' && str_starts_with($serie, 'T')) ? $serie : 'T001';
    }

    private function negocio(): Project
    {
        /** @var Project $project */
        return app('active_project');
    }

    private function soloDeMiNegocio(GuiaRemision $guia): Project
    {
        $project = $this->negocio();
        abort_unless($guia->project_id === $project->id, 403);

        return $project;
    }
}

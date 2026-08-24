<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Jobs\EnviarGuiaASunat;
use App\Models\GuiaRemision;
use App\Models\Invoice;
use App\Models\Project;
use App\Support\Sunat\Catalogos;
use Illuminate\Http\Request;
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

        return view('facturacion.guias.index', [
            'project'     => $project,
            'guias'       => $guias,
            'serie'       => $this->serie($project),
            'motivos'     => Catalogos::MOTIVOS_TRASLADO,
            'modalidades' => Catalogos::MODALIDADES_TRASLADO,
            'unidades'    => Catalogos::UNIDADES,
            // Solo las facturas y boletas aceptadas pueden respaldar un traslado.
            'comprobantes' => $project->invoices()
                ->whereIn('type', ['factura', 'boleta'])
                ->where('sunat_status', 'accepted')
                ->latest('id')->limit(50)
                ->get(['id', 'numero', 'client_name', 'client_doc_number', 'client_address']),
        ]);
    }

    /** Lo que el formulario necesita: catálogos y, si viene de una venta, sus datos. */
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

        $data = $request->validate([
            'invoice_id'              => ['nullable', 'integer', Rule::exists('invoices', 'id')->where('project_id', $project->id)],
            'destinatario_nombre'     => ['required', 'string', 'max:200'],
            'destinatario_doc_tipo'   => ['nullable', 'string', Rule::in(array_keys(Catalogos::DOCUMENTOS_IDENTIDAD))],
            'destinatario_doc_numero' => ['nullable', 'string', 'max:15'],

            'motivo_codigo'      => ['required', Rule::in(array_keys(Catalogos::MOTIVOS_TRASLADO))],
            'motivo_descripcion' => ['nullable', 'string', 'max:120'],
            'fecha_traslado'     => ['required', 'date'],
            'modalidad'          => ['required', Rule::in(array_keys(Catalogos::MODALIDADES_TRASLADO))],

            'peso_total'  => ['required', 'numeric', 'gt:0'],
            'peso_unidad' => ['nullable', Rule::in(['KGM', 'TNE'])],
            'bultos'      => ['nullable', 'integer', 'min:0'],

            'partida_ubigeo'    => ['nullable', 'string', 'size:6'],
            'partida_direccion' => ['required', 'string', 'max:300'],
            'llegada_ubigeo'    => ['nullable', 'string', 'size:6'],
            'llegada_direccion' => ['required', 'string', 'max:300'],

            // Transporte público: hace falta saber quién lo lleva.
            'transportista_ruc'          => ['required_if:modalidad,01', 'nullable', 'string', 'size:11'],
            'transportista_razon_social' => ['required_if:modalidad,01', 'nullable', 'string', 'max:200'],
            'transportista_mtc'          => ['nullable', 'string', 'max:20'],

            // Transporte privado: con qué vehículo y quién conduce.
            'vehiculo_placa'       => ['required_if:modalidad,02', 'nullable', 'string', 'max:10'],
            'conductor_doc_tipo'   => ['nullable', 'string', 'max:2'],
            'conductor_doc_numero' => ['required_if:modalidad,02', 'nullable', 'string', 'max:15'],
            'conductor_nombres'    => ['required_if:modalidad,02', 'nullable', 'string', 'max:120'],
            'conductor_apellidos'  => ['required_if:modalidad,02', 'nullable', 'string', 'max:120'],
            'conductor_licencia'   => ['required_if:modalidad,02', 'nullable', 'string', 'max:20'],

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
            'vehiculo_placa.required_if'             => 'En transporte privado hay que declarar la placa del vehículo.',
            'conductor_doc_numero.required_if'       => 'En transporte privado hay que declarar el documento del conductor.',
            'conductor_licencia.required_if'         => 'En transporte privado hay que declarar la licencia del conductor.',
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

                'vehiculo_placa'       => $data['vehiculo_placa'] ?? null,
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

        return view('facturacion.guias.pdf', compact('project', 'guia'));
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

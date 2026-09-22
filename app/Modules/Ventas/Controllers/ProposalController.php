<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;

use App\Modules\Ventas\Models\Proposal;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PROPUESTAS COMERCIALES (proformas).
 *
 * El vendedor registra a quién va la propuesta y su precio; el sistema genera
 * un documento profesional con enlace propio que el cliente abre y puede
 * guardar como PDF desde el navegador.
 */
class ProposalController extends Controller
{
    /** La numeracion de propuestas arranca aqui (antes era PRO-0001). */
    private const CORRELATIVO_INICIAL = 1101;

    /** Reglas comunes de validación. */
    private function rules(bool $creando = true): array
    {
        return [
            'client_name'       => ($creando ? 'required' : 'sometimes|required') . '|string|max:150',
            'business_name'     => 'nullable|string|max:150',
            'rubro'             => 'nullable|string|max:100',
            'client_phone'      => 'nullable|string|max:50',
            'client_email'      => 'nullable|email|max:150',
            'city'              => 'nullable|string|max:100',
            'demo_url'          => 'nullable|url|max:255',
            // Demos adicionales que se adjuntan como muestra de trabajo.
            'demos_extra'       => 'nullable|array|max:6',
            'demos_extra.*'     => 'url|max:255',
            'apertura'          => 'nullable|string|max:2000',
            'plan_recomendado'  => 'nullable|in:start,pro,business',
            'plan_motivo'       => 'nullable|string|max:1000',
            'lost_reason'       => 'nullable|string|max:255',
            'price'             => 'nullable|numeric|min:0',
            'price_renewal'     => 'nullable|numeric|min:0',
            'products_included' => 'nullable|integer|min:0',
            'valid_days'        => 'nullable|integer|min:1|max:365',
            'extras'            => 'nullable|array',
            'extras.*.nombre'   => 'required_with:extras|string|max:150',
            'extras.*.precio'   => 'required_with:extras|numeric|min:0',
            'extras.*.periodo'  => 'nullable|string|max:20',   // unico|mensual|sesion
            'extra_notes'       => 'nullable|string',
            'status'            => 'nullable|in:borrador,enviada,conversando,aceptada,rechazada',
        ];
    }

    public function index()
    {
        /** @var Project $project */
        $project   = app('active_project');
        $proposals = $project->proposals()->latest()->get();

        // Catalogo de demos y textos por rubro: un solo sitio, para que la lista
        // no se quede vieja como paso antes (tenia 3 demos y ya habia 13).
        $demos = \App\Modules\Ventas\Support\DemosPorRubro::opciones();
        $textos = [
            'aperturas' => \App\Modules\Ventas\Support\DemosPorRubro::APERTURAS,
            'motivos' => \App\Modules\Ventas\Support\DemosPorRubro::MOTIVOS,
            'rubros' => array_map(
                fn ($d) => $d['etiqueta'],
                \App\Modules\Ventas\Support\DemosPorRubro::DEMOS
            ),
        ];

        return view('ventas::proposals.index', compact('project', 'proposals', 'demos', 'textos'));
    }

    public function store(Request $request)
    {
        /** @var Project $project */
        $project = app('active_project');
        $data = $request->validate($this->rules());

        $ultimo = (int) $project->proposals()
            ->where('number', 'like', 'PRO-%')
            ->selectRaw('MAX(CAST(SUBSTRING(number, 5) AS UNSIGNED)) AS n')
            ->value('n');
        $n = max($ultimo + 1, self::CORRELATIVO_INICIAL);
        $data['project_id'] = $project->id;
        $data['number']     = 'PRO-' . $n;
        $data['token']      = Str::random(40);
        $data['status']     = 'borrador';
        $data['plan_recomendado'] = $data['plan_recomendado'] ?? 'pro';
        // El precio sigue al plan recomendado salvo que se escriba otro.
        $data['price']         = $data['price']
            ?? ['start' => 490, 'pro' => 590, 'business' => 690][$data['plan_recomendado']] ?? 490;
        $data['price_renewal'] = $data['price_renewal'] ?? 100;
        $data['products_included'] = $data['products_included'] ?? 200;
        $data['valid_days']    = $data['valid_days'] ?? 15;

        $proposal = Proposal::create($data);

        return response()->json([
            'proposal' => $proposal,
            'url'      => route('proposal.publica', $proposal->token),
        ]);
    }

    public function update(Request $request, Proposal $proposal)
    {
        /** @var Project $project */
        $project = app('active_project');
        abort_unless($proposal->project_id === $project->id, 403);

        $data = $request->validate($this->rules(false));
        if (($data['status'] ?? null) === 'enviada' && !$proposal->sent_at) {
            $data['sent_at'] = now();
        }
        // Una propuesta cerrada deja fecha: sin esto no se sabe cuanto tardo
        // el cliente en decidir, ni cuales llevan semanas sin respuesta.
        if (in_array($data['status'] ?? null, ['aceptada', 'rechazada'], true)) {
            $data['closed_at'] = $proposal->closed_at ?? now();
        }
        /* Una propuesta sin token (creada antes de que existiera el enlace
           publico, o importada) tumbaba la edicion entera con un 500 al
           generar la URL. Se le da uno al vuelo: editarla no puede fallar por
           algo que el usuario no sabe ni que existe. */
        if (blank($proposal->token)) {
            $data['token'] = Str::random(40);
        }

        $proposal->update($data);
        $proposal->refresh();

        return response()->json([
            'proposal' => $proposal,
            'url'      => route('proposal.publica', $proposal->token),
        ]);
    }

    public function destroy(Proposal $proposal)
    {
        /** @var Project $project */
        $project = app('active_project');
        abort_unless($proposal->project_id === $project->id, 403);
        $proposal->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Vista PÚBLICA de la propuesta (la que ve el cliente por enlace).
     * Diseñada para verse bien en pantalla y al imprimir/guardar como PDF.
     */
    public function publica(string $token)
    {
        $proposal = Proposal::where('token', $token)->firstOrFail();
        $project  = $proposal->project;
        $settings = $project->settings()->pluck('value', 'key');

        // Marcar como vista/enviada la primera vez que el cliente la abre.
        if ($proposal->status === 'borrador') {
            $proposal->update(['status' => 'enviada', 'sent_at' => $proposal->sent_at ?? now()]);
        }

        return view('ventas::proposals.publica', compact('proposal', 'project', 'settings'));
    }
}
